<?php
//test
require 'vendor/autoload.php';
require 'include/ficsave.php';
require 'include/sites.php';

session_start();
if (!isset($_SESSION['downloads'])) {
    $_SESSION['downloads'] = array();
}

$app = new \Slim\Slim();
$app->config(array(
    'log.level' => \Slim\Log::DEBUG,
    'log.enable' => true,
    'log.writer' => new \Slim\Logger\DateTimeFileWriter(array(
        'path' => dirname(__FILE__) . DIRECTORY_SEPARATOR . 'log',
        'name_format' => 'Y-m-d',
        'message_format' => '[%label% - %date%] %message%'
    )),
    'debug' => false
));

$app->get("/", function() use ($app) {
    require 'pages/index.php';
});

$app->get("/reset", function() use ($app) {
    session_unset();
    session_destroy();
    session_write_close();
    setcookie(session_name(),'',0,'/');
    session_regenerate_id(true);
    $app->redirect('/');
});

$app->group("/api", function() use ($app) {
    $app->group("/downloader", function() use ($app) {
        $app->post("/begin", function() use ($app) {
            $json = array();
            $json['success'] = true;

            $url = $app->request->post('url');
            $format = $app->request->post('format');
            $email = $app->request->post('email');
            $resume = $app->request->post('resume');
            $currentId = $app->request->post('currentId');
            if (isset($_SESSION['currentId']) && $currentId != $_SESSION['currentId']) {
                $json['success'] = false;
                $json['message'] = "A new session has been started in a different window! Please switch to the new session or refresh the page.";
            } else if ($resume != null) {
                $json['downloads'] = $_SESSION['downloads'];
            } else if (!empty($url) && !empty($format)) {
                try {
                    $story = getInfo($url);
                    $download = array(
                        'id' => uniqid(),
                        'story' => (array) $story,
                        'currentChapter' => 1,
                        'totalChapters' => $story->chapters,
                        'format' => $format,
                        'email' => ($email == null ? '' : $email),
                        'status' => Status::PENDING,
                        'statusMessage' => '',
                        'fileName' => '',
                        'timestamp' => time()
                    );
                    $_SESSION['downloads'][$download['id']] = $download;
                    $json['downloads'] = $_SESSION['downloads'];
                } catch (FicSaveException $ex) {
                    $json['success'] = false;
                    $json['message'] = $ex->getMessage();
                }
            } else {
                $json['success'] = false;
                $json['message'] = "URL cannot be empty!";
            }

            $app->response()->headers()->set('Content-Type', 'application/json');
            $app->response()->body(json_encode($json));
        });

        $app->post("/process", function() use ($app) {
            ini_set('memory_limit', '512M');
            $json = array();
            $json['success'] = true;

            $currentId = $app->request->post('currentId');
            if (isset($_SESSION['currentId']) && $currentId != $_SESSION['currentId']) {
                $json['success'] = false;
                $json['message'] = "Downloading has been resumed in a different window!";
            } else {
                $activeDownloads = 0;
                $activeBuilds = 0;
                foreach ($_SESSION['downloads'] as &$download) {
                    if ($download['status'] == Status::PENDING) {
                        $download['status'] = Status::DOWNLOADING;
                        $_SESSION[$download['id']] = array();
                    } else if ($download['status'] == Status::DOWNLOADING) {
                        if ($activeDownloads >= 3) {
                            continue;
                        }
                        $activeDownloads++;
                        try {
                            $_SESSION[$download['id']][] = (array) getChapter($download['story']['url'], $download['currentChapter'], $download['story']['metadata']);
                            if ($download['currentChapter'] == $download['totalChapters']) {
                                $download['status'] = Status::DOWNLOAD_COMPLETE;
                            } else {
                                $download['currentChapter']++;
                            }
                        } catch (Exception $ex) {
                            $app->getLog()->error($ex);
                            $download['status'] = Status::ERROR;
                            $download['statusMessage'] = "Failed to download chapter {$download['currentChapter']}.";
                            unset($_SESSION[$download['id']]);
                        }
                    } else if ($download['status'] == Status::DOWNLOAD_COMPLETE) {
                        $download['status'] = Status::BUILDING;
                    } else if ($download['status'] == Status::BUILDING) {
                        if ($activeBuilds >= 1) {
                            continue;
                        }
                        $activeBuilds++;
                        try {
                            $book = new \PHPePub\Core\EPub();
                            $book->setTitle($download['story']['title']);
                            $book->setAuthor($download['story']['author'], $download['story']['author']);
                            $book->setIdentifier($download['id'], PHPePub\Core\EPub::IDENTIFIER_UUID);
                            $book->setSourceURL($download['story']['url']);
                            if (!empty($download['story']['description'])) {
                                $book->setDescription($download['story']['description']);
                            }
                            $contentStart =
                                "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
                                . "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
                                . "    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
                                . "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
                                . "<head>"
                                . "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n"
                                . "<title>".$download['story']['title']."</title>\n"
                                . "<style type=\"text/css\">\n"
                                . "body{font-family:'Arial',sans-serif;}\n"
                                . "</style>\n"
                                . "</head>\n"
                                . "<body>\n";
                            $contentEnd = "</body>\n</html>\n";

                            $book->addChapter($download['story']['title'] . " by " . $download['story']['author'],
                                'Cover.html',
                                $contentStart .
                                '<div style="text-align: center;">' .
                                '<h1>' . htmlspecialchars($download['story']['title']) . '</h1>' .
                                '<h3><i>by ' . $download['story']['author'] . '</i></h3>' .
                                '<div style="text-align: left;">' . $download['story']['description'] . '</div>' .
                                '<div style="text-align: left;">URL: <a href="'.$download['story']['url'].'">' . $download['story']['url'] . '</a></div>' .
                                '</div>' .
                                $contentEnd);
                            if ($download['totalChapters'] > 1) {
                                $book->buildTOC();
                            }
                            foreach ($_SESSION[$download['id']] as $chapter) {
                                $chapterTitle = htmlspecialchars($chapter['title']);
                                if ($book->addChapter($chapterTitle,
                                        $book->sanitizeFileName($chapter['title']).".html",
                                        $contentStart . '<h2 style="text-align: center;">' . $chapterTitle . '</h2>' . PHP_EOL . '<div>' . PHP_EOL . $chapter['content'] . PHP_EOL . '</div>' . $contentEnd) === FALSE) {
                                    $download['status'] = Status::ERROR;
                                    $download['statusMessage'] = "Failed to generate chapter {$chapter['number']} of eBook.";
                                    unset($_SESSION[$download['id']]);
                                    break;
                                }
                            }
                            if ($download['status'] != Status::ERROR) {
                                if ($book->finalize()) {
                                    $fileName = $download['id']."_".$download['story']['title']." - ".$download['story']['author'];
                                    $filePath = dirname(__FILE__).DIRECTORY_SEPARATOR."tmp";
                                    if ($book->saveBook($fileName, $filePath) === FALSE) {
                                        $download['status'] = Status::ERROR;
                                        $download['statusMessage'] = "Failed to generate eBook.";
                                        unset($_SESSION[$download['id']]);
                                    } else {
                                        $download['fileName'] = $book->sanitizeFileName($fileName);
                                        $fileNameWithPath = $filePath.DIRECTORY_SEPARATOR.$download['fileName'];
                                        if ($download['format'] != 'epub') {
                                            if (file_exists("{$fileNameWithPath}.{$download['format']}")) {
                                                $app->getLog()->warn("{$fileNameWithPath}.{$download['format']} already exists, waiting for build to complete...");
                                            } else {
                                                set_time_limit(0);
                                                try {
                                                    // set UTF8-encoding for foreign characters
                                                    $locale='en_US.UTF-8';
                                                    setlocale(LC_ALL,$locale);
                                                    putenv('LC_ALL='.$locale);
                                                    $result = exec("ebook-convert {$fileNameWithPath}.epub {$fileNameWithPath}.{$download['format']} --margin-left 36 --margin-right 36 --margin-top 36 --margin-bottom 36 2>&1", $output);
                                                    if (strpos($result, 'saved to') === FALSE) {
                                                        $app->getLog()->error("Could not save file.");
                                                        $app->getLog()->error($output);
                                                        $download['status'] = Status::ERROR;
                                                        $download['statusMessage'] = "Failed to convert eBook to requested format.";
                                                        unset($_SESSION[$download['id']]);
                                                    } else if (strpos($result, 'Killed') !== FALSE) {
                                                        $app->getLog()->error("Not enough memory.");
                                                        $app->getLog()->error($output);
                                                        $download['status'] = Status::ERROR;
                                                        $download['statusMessage'] = "Failed to convert eBook to requested format. File may be too large.";
                                                        unset($_SESSION[$download['id']]);
                                                    } else {
                                                        unlink($fileNameWithPath.'.epub');
                                                    }
                                                } catch (Exception $ex) {
                                                    $download['status'] = Status::ERROR;
                                                    $download['statusMessage'] = "Failed to convert eBook to requested format. Please try again later.";
                                                }
                                            }
                                        }
                                        if ($download['status'] != Status::ERROR) {
                                            if (empty($download['email'])) {
                                                $download['status'] = Status::DONE;
                                            } else {
                                                if (mailAttachment($download['id'], $download['fileName'].'.'.$download['format'], $filePath, $download['email'])) {
                                                    $download['status'] = Status::EMAILED;
                                                    unlink($fileNameWithPath.'.'.$download['format']);
                                                } else {
                                                    $download['status'] = Status::ERROR;
                                                    $download['statusMessage'] = "Failed to send email!";
                                                }
                                                unset($_SESSION[$download['id']]);
                                            }
                                        }
                                    }
                                } else {
                                    $download['status'] = Status::ERROR;
                                    $download['statusMessage'] = "Failed to finalize eBook generation.";
                                    unset($_SESSION[$download['id']]);
                                }
                            }
                        } catch (Exception $ex) {
                            $app->getLog()->error($ex);
                            $download['status'] = Status::ERROR;
                            $download['statusMessage'] = "Failed to build eBook.";
                            unset($_SESSION[$download['id']]);
                        }
                    } else if ($download['status'] >= Status::DONE) {
                        $fileName = "{$download['fileName']}.{$download['format']}";
                        $fileNameWithPath = dirname(__FILE__).DIRECTORY_SEPARATOR."tmp".DIRECTORY_SEPARATOR.$fileName;
                        if (!file_exists($fileNameWithPath) && $download['status'] != Status::EMAILED) {
                            unset($_SESSION['downloads'][$download['id']]);
                        }
                    } else if ($download['status'] == Status::ERROR) {
                        unset($_SESSION['downloads'][$download['id']]);
                    }
                }
                $json['downloads'] = $_SESSION['downloads'];
            }

            $app->response()->headers()->set('Content-Type', 'application/json');
            $app->response()->body(json_encode($json));
        });
    });
    $app->group("/donation", function() use ($app) {
        $app->post("/paypal/new", function() use ($app) {
            // http://ficsave.com/api/donation/paypal/new
            require 'include/custom/ipn/paypal.php';
        });
    });
});

$app->get("/download/:bookId", function($bookId) use ($app) {
    if (isset($_SESSION['downloads'][$bookId])) {
        $download = $_SESSION['downloads'][$bookId];
        if ($download['status'] >= Status::DONE) {
            $fileName = "{$download['fileName']}.{$download['format']}";
            $fileNameWithPath = dirname(__FILE__).DIRECTORY_SEPARATOR."tmp".DIRECTORY_SEPARATOR.$fileName;
            if (file_exists($fileNameWithPath)) {
                $_SESSION['downloads'][$bookId]['status'] = Status::SERVED;
                $rename = "{$download['story']['title']} - {$download['story']['author']}.{$download['format']}";
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.basename($rename).'"');
                header("Cache-Control: no-cache, must-revalidate");
                header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                header('Pragma: public');
                header('Content-Length: ' . filesize($fileNameWithPath));
                ob_clean();
                flush();
                readfile($fileNameWithPath);
                exit();
            } else {
                unset($_SESSION['downloads'][$bookId]);
            }
        }
    }
    $app->notFound();
});

$app->run();