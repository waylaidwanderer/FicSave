<?php

namespace App\Http\Controllers\API;

use App\Enums\DownloadStatus;
use App\Ficsave\Ficsave;
use App\Ficsave\FicSaveException;
use App\Helper;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use PHPePub\Core\EPub;
use PHPePub\Helpers\FileHelper;

class DownloaderController extends Controller
{
    public function postBegin(Request $request)
    {
        $json = [];
        $json['success'] = true;

        $url = $request->input('url');
        $format = strtolower($request->input('format'));
        $email = strtolower($request->input('email', ''));
        $resume = $request->input('resume');
        $currentId = $request->input('currentId');
        if ($format == 'pdf') {
            $json['success'] = false;
            $json['message'] = "PDF is currently disabled. Please use another format.";
        } else if ($request->session()->get('currentId', '') != $currentId) {
            $json['success'] = false;
            $json['message'] = "A new session has been started in a different window! Please switch to the new session or refresh the page.";
        } else if ($resume != null) {
            $json['downloads'] = $request->session()->get('downloads');
        } else if (!empty($url) && !empty($format)) {
            try {
                $story = Ficsave::getInfo($url);
                $download = [
                    'id' => uniqid(),
                    'story' => (array) $story,
                    'currentChapter' => 1,
                    'totalChapters' => $story->chapters,
                    'format' => $format,
                    'email' => (empty($email) ? '' : $email),
                    'status' => DownloadStatus::PENDING,
                    'statusMessage' => '',
                    'fileName' => '',
                    'timestamp' => time()
                ];
                $downloads = $request->session()->get('downloads');
                $downloads[$download['id']] = $download;
                $request->session()->set('downloads', $downloads);
                $json['downloads'] = $downloads;
            } catch (FicSaveException $ex) {
                $json['success'] = false;
                $json['message'] = $ex->getMessage();
            }
        } else {
            $json['success'] = false;
            $json['message'] = "URL cannot be empty!";
        }

        return response()->json($json);
    }

    public function postProcess(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(0);
        $json = array();
        $json['success'] = true;

        $currentId = $request->input('currentId');
        if ($request->session()->get('currentId', '') != $currentId) {
            $json['success'] = false;
            $json['message'] = "Downloading has been resumed in a different window!";
        } else {
            $activeDownloads = 0;
            $activeBuilds = 0;
            $downloads = $request->session()->get('downloads', []);
            foreach ($downloads as &$download) {
                if ($download['status'] == DownloadStatus::PENDING) {
                    $download['status'] = DownloadStatus::DOWNLOADING;
                    $request->session()->set($download['id'], []);
                } else if ($download['status'] == DownloadStatus::DOWNLOADING) {
                    if ($activeDownloads >= 3) continue;
                    $activeDownloads++;
                    try {
                        $downloadedChapter = (array) Ficsave::getChapter($download['story']['url'], $download['currentChapter'], $download['story']['metadata']);
                        if (!isset($downloadedChapter['content']) || empty($downloadedChapter['content'])) throw new FicSaveException();
                        $request->session()->push($download['id'], $downloadedChapter);

                        if ($download['currentChapter'] == $download['totalChapters']) {
                            $download['status'] = DownloadStatus::DOWNLOAD_COMPLETE;
                        } else {
                            $download['currentChapter']++;
                        }
                    } catch (\Exception $ex) {
                        if ($ex instanceof FicSaveException) {
                            \Log::error("Failed to download chapter {$download['currentChapter']} of {$download['story']['url']}");
                        } else {
                            \Log::error($ex);
                        }
                        $download['status'] = DownloadStatus::ERROR;
                        $download['statusMessage'] = "Failed to download chapter {$download['currentChapter']}.";
                        $request->session()->forget($download['id']);
                    }
                } else if ($download['status'] == DownloadStatus::DOWNLOAD_COMPLETE) {
                    $download['status'] = DownloadStatus::BUILDING;
                } else if ($download['status'] == DownloadStatus::BUILDING) {
                    if ($activeBuilds >= 1) continue;
                    $activeBuilds++;
                    try {
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

                        if ($download['format'] == 'pdf') {
                            $download['status'] = DownloadStatus::ERROR;
                            $download['statusMessage'] = "PDF is currently disabled. Please use another format.";
                            /*$pdf = new \mikehaertl\wkhtmlto\Pdf([
                                'no-outline',         // Make Chrome not complain
                                'margin-top'    => 0,
                                'margin-right'  => 0,
                                'margin-bottom' => 0,
                                'margin-left'   => 0,
                                'disable-smart-shrinking',
                            ]);
                            $contentStart =
                                "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
                                . "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
                                . "    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
                                . "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
                                . "<head>"
                                . "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n"
                                . "<title>".$download['story']['title']."</title>\n"
                                . "<style type=\"text/css\">\n"
                                . "body{margin:0;padding:0;width:21cm;height:29.7cm}#print-area{position:relative;top:1cm;left:1cm;width:19cm;height:27.6cm;font-size:10px;font-family:Arial}\n"
                                . "</style>\n"
                                . "</head>\n"
                                . "<body>\n";
                            $pdf->addCover($contentStart .
                                '<div style="text-align: center;">' .
                                '<h1>' . htmlspecialchars($download['story']['title']) . '</h1>' .
                                '<h3><i>by ' . $download['story']['author'] . '</i></h3>' .
                                '<div style="text-align: left;">' . $download['story']['description'] . '</div>' .
                                '<div style="text-align: left;">URL: <a href="'.$download['story']['url'].'">' . $download['story']['url'] . '</a></div>' .
                                '</div>' .
                                $contentEnd);
                            $chapterNum = 1;
                            foreach ($_SESSION[$download['id']] as $chapter) {
                                $chapterTitle = htmlspecialchars($chapter['title']);
                                $pdf->addPage($contentStart . '<h2 style="text-align: center;">' . $chapterTitle . '</h2>' . PHP_EOL . '<div>' . PHP_EOL . $chapter['content'] . PHP_EOL . '</div>' . $contentEnd);
                                $chapterNum++;
                            }
                            if ($download['totalChapters'] > 1) {
                                $pdf->addToc();
                            }
                            if ($download['status'] != Status::ERROR) {
                                $fileName = $download['id']."_".$download['story']['title']." - ".$download['story']['author'];
                                $fileName1 = str_replace(StaticData::$forbiddenCharacters, '', $fileName);
                                $fileName2 = preg_replace('/[\s-]+/', '-', $fileName1);
                                $download['fileName'] = trim($fileName2, '.-_');
                                $filePath = dirname(__FILE__).DIRECTORY_SEPARATOR."tmp";
                                $fileNameWithPath = $filePath.DIRECTORY_SEPARATOR.$download['fileName'].'.pdf';
                                if ($pdf->saveAs($fileNameWithPath)) {
                                    if ($download['status'] != Status::ERROR) {
                                        if (empty($download['email'])) {
                                            $download['status'] = Status::DONE;
                                        } else {
                                            if (mailAttachment($download['id'], $download['fileName'].'.'.$download['format'], $filePath, $download['email'])) {
                                                $download['status'] = Status::EMAILED;
                                                unlink($fileNameWithPath);
                                            } else {
                                                $download['status'] = Status::ERROR;
                                                $download['statusMessage'] = "Failed to send email!";
                                            }
                                            unset($_SESSION[$download['id']]);
                                        }
                                    }
                                } else {
                                    $download['status'] = Status::ERROR;
                                    $download['statusMessage'] = "Failed to generate eBook.";
                                    unset($_SESSION[$download['id']]);
                                }
                            }*/
                        } else {
                            $book = new EPub;
                            $book->setTitle($download['story']['title']);
                            $book->setAuthor($download['story']['author'], $download['story']['author']);
                            $book->setIdentifier($download['id'], EPub::IDENTIFIER_UUID);
                            $book->setSourceURL($download['story']['url']);
                            if (!empty($download['story']['description'])) {
                                $book->setDescription($download['story']['description']);
                            }

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
                            if ($download['totalChapters'] > 1) $book->buildTOC();
                            $chapterNum = 1;
                            $storyDownload = $request->session()->get($download['id']);
                            if ($storyDownload == null) {
                                $download['status'] = DownloadStatus::ERROR;
                                $download['statusMessage'] = "Failed to retrieve data for eBook.";
                            } else {
                                // TODO: investigate null cause
                                foreach ($storyDownload as $chapter) {
                                    $chapterTitle = htmlspecialchars($chapter['title']);
                                    if ($book->addChapter($chapterNum . ". " . $chapterTitle,
                                            FileHelper::sanitizeFileName($chapter['title']).".html",
                                            $contentStart . '<h2 style="text-align: center;">' . $chapterTitle . '</h2>' . PHP_EOL . '<div>' . PHP_EOL . $chapter['content'] . PHP_EOL . '</div>' . $contentEnd) === false) {
                                        $download['status'] = DownloadStatus::ERROR;
                                        $download['statusMessage'] = "Failed to generate chapter {$chapter['number']} of eBook.";
                                        $request->session()->forget($download['id']);
                                        break;
                                    }
                                    $chapterNum++;
                                }
                            }
                            if ($download['status'] != DownloadStatus::ERROR) {
                                if ($book->finalize()) {
                                    $fileName = FileHelper::sanitizeFileName($download['id']."_".$download['story']['title']." - ".$download['story']['author']);
                                    $filePath = storage_path('app/tmp');
                                    $download['fileName'] = $fileName;
                                    if ($book->saveBook($fileName, $filePath) === FALSE) {
                                        $download['status'] = DownloadStatus::ERROR;
                                        $download['statusMessage'] = "Failed to generate eBook.";
                                        $request->session()->forget($download['id']);
                                    } else {
                                        $fileNameWithPath = $filePath.DIRECTORY_SEPARATOR.$download['fileName'];
                                        if ($download['format'] != 'epub') {
                                            if (file_exists("{$fileNameWithPath}.{$download['format']}")) {
                                                \Log::warning("{$fileNameWithPath}.{$download['format']} already exists, waiting for build to complete...");
                                            } else {
                                                try {
                                                    // set UTF8-encoding for foreign characters
                                                    $locale='en_US.UTF-8';
                                                    setlocale(LC_ALL,$locale);
                                                    putenv('LC_ALL='.$locale);
                                                    exec("ebook-convert {$fileNameWithPath}.epub {$fileNameWithPath}.{$download['format']} --margin-left 36 --margin-right 36 --margin-top 36 --margin-bottom 36 2>&1", $output);
                                                    $result = implode("\n", $output);
                                                    if (strpos($result, 'saved to') === false) {
                                                        \Log::error('Could not save file');
                                                        \Log::error($output);
                                                        $download['status'] = DownloadStatus::ERROR;
                                                        $download['statusMessage'] = "Failed to convert eBook to requested format.";
                                                        $request->session()->forget($download['id']);
                                                    } else if (strpos($result, 'Killed') !== false && strpos($result, 'saved to') === false) {
                                                        \Log::error('Not enough memory.');
                                                        \Log::error($output);
                                                        $download['status'] = DownloadStatus::ERROR;
                                                        $download['statusMessage'] = "Failed to convert eBook to requested format. File may be too large.";
                                                        $request->session()->forget($download['id']);
                                                    } else {
                                                        unlink($fileNameWithPath.'.epub');
                                                    }
                                                } catch (\Exception $ex) {
                                                    $download['status'] = DownloadStatus::ERROR;
                                                    $download['statusMessage'] = "Failed to convert eBook to requested format. Please try again later.";
                                                }
                                            }
                                        }
                                        if ($download['status'] != DownloadStatus::ERROR) {
                                            if (empty($download['email'])) {
                                                $download['status'] = DownloadStatus::DONE;
                                            } else {
                                                if (Helper::mailAttachment($download['id'], $download['fileName'].'.'.$download['format'], $filePath, $download['email'])) {
                                                    $download['status'] = DownloadStatus::EMAILED;
                                                    try {
                                                        unlink($fileNameWithPath.'.'.$download['format']);
                                                    } catch (\Exception $ex) {

                                                    }
                                                } else {
                                                    $download['status'] = DownloadStatus::ERROR;
                                                    $download['statusMessage'] = "Failed to send email!";
                                                }
                                                $request->session()->forget($download['id']);
                                            }
                                        }
                                    }
                                } else {
                                    $download['status'] = DownloadStatus::ERROR;
                                    $download['statusMessage'] = "Failed to finalize eBook generation.";
                                    $request->session()->forget($download['id']);
                                }
                            }
                        }
                    } catch (\Exception $ex) {
                        \Log::error($ex);
                        $download['status'] = DownloadStatus::ERROR;
                        $download['statusMessage'] = "Failed to build eBook.";
                        $request->session()->forget($download['id']);
                    }
                } else if ($download['status'] >= DownloadStatus::DONE) {
                    $fileName = "{$download['fileName']}.{$download['format']}";
                    $fileNameWithPath = storage_path('app/tmp').DIRECTORY_SEPARATOR.$fileName;
                    if (!file_exists($fileNameWithPath) && $download['status'] != DownloadStatus::EMAILED) {
                        $downloads = $request->session()->get('downloads');
                        unset($downloads[$download['id']]);
                        $request->session()->set('downloads', $downloads);
                    }
                } else if ($download['status'] == DownloadStatus::ERROR) {
                    $downloads = $request->session()->get('downloads');
                    unset($downloads[$download['id']]);
                    $request->session()->set('downloads', $downloads);
                }
            }
            $request->session()->set('downloads', $downloads);
            $json['downloads'] = $downloads;
        }

        return response()->json($json);
    }
}
