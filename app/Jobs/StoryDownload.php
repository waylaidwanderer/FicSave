<?php

namespace App\Jobs;

use App\Enums\DownloadStatus;
use App\Ficsave\Download;
use App\Ficsave\Ficsave;
use App\Ficsave\FicSaveException;
use App\Helper;
use App\Jobs\Job;
use Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use PHPePub\Core\EPub;
use PHPePub\Helpers\FileHelper;

class StoryDownload extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $download;

    /**
     * Create a new job instance.
     *
     * @param Download $download
     */
    public function __construct(Download $download)
    {
        $this->download = $download;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        ini_set('memory_limit', '512M');
        set_time_limit(0);

        $download = $this->download;
        $story = $download->getStory();

        $download->setStatus(DownloadStatus::DOWNLOADING);
        $this->save();

        for ($i = 0; $i < $download->getNumChapters(); $i++) {
            try {
                $downloadedChapter = Ficsave::getChapter($story->url, $download->getCurrentChapter(), $story->metadata);
                if (!isset($downloadedChapter->content) || empty($downloadedChapter->content)) throw new FicSaveException();
                $download->addChapter($downloadedChapter);

                if ($download->getCurrentChapter() == $download->getNumChapters()) {
                    $download->setStatus(DownloadStatus::DOWNLOAD_COMPLETE);
                } else {
                    $download->setCurrentChapter($download->getCurrentChapter() + 1);
                    $this->save();
                }
            } catch (\Exception $ex) {
                if ($ex instanceof FicSaveException) {
                    \Log::error("Failed to download chapter {$download->getCurrentChapter()} of {$story->url}");
                } else {
                    \Log::error($ex);
                }
                $download->setStatus(DownloadStatus::ERROR);
                $download->setStatusMessage("Failed to download chapter {$download->getCurrentChapter()}.");
                $this->save();
                return;
            }
        }

        $download->setStatus(DownloadStatus::BUILDING);
        $this->save();

        try {
            $contentStart =
                "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
                . "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
                . "    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
                . "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
                . "<head>"
                . "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n"
                . "<title>".$story->title."</title>\n"
                . "<style type=\"text/css\">\n"
                . "body{font-family:'Arial',sans-serif;}\n"
                . "</style>\n"
                . "</head>\n"
                . "<body>\n";
            $contentEnd = "</body>\n</html>\n";

            if ($download->getFormat() == 'pdf') {
                $download->setStatus(DownloadStatus::ERROR);
                $download->setStatusMessage('PDF is currently disabled. Please use another format.');
                $this->save();
                return;
            }

            $book = new EPub;
            $book->setTitle($story->title);
            $book->setAuthor($story->author, $story->author);
            $book->setIdentifier($download->getId(), EPub::IDENTIFIER_UUID);
            $book->setSourceURL($story->url);
            if (!empty($story->description)) {
                $book->setDescription($story->description);
            }
            $book->addChapter($story->title . " by " . $story->author,
                'Cover.html',
                $contentStart .
                '<div style="text-align: center;">' .
                '<h1>' . htmlspecialchars($story->title) . '</h1>' .
                '<h3><i>by ' . $story->author . '</i></h3>' .
                '<div style="text-align: left;">' . $story->description . '</div>' .
                '<div style="text-align: left;">URL: <a href="' . $story->url . '">' . $story->url . '</a></div>' .
                '</div>' .
                $contentEnd);
            if ($download->getNumChapters() > 1) $book->buildTOC();
            $chapterNum = 1;
            foreach ($download->getChapters() as $chapter) {
                $chapterTitle = htmlspecialchars($chapter->title);
                if ($book->addChapter($chapterNum . ". " . $chapterTitle,
                        FileHelper::sanitizeFileName($chapter->title).".html",
                        $contentStart . '<h2 style="text-align: center;">' . $chapterTitle . '</h2>' . PHP_EOL . '<div>' . PHP_EOL . $chapter->content . PHP_EOL . '</div>' . $contentEnd) === false) {
                    $download->setStatus(DownloadStatus::ERROR);
                    $download->setStatusMessage("Failed to generate chapter {$chapter->number} of eBook.");
                    $this->save();
                    return;
                }
                $chapterNum++;
            }

            if (!$book->finalize()) {
                $download->setStatus(DownloadStatus::ERROR);
                $download->setStatusMessage('Failed to finalize eBook generation.');
                $this->save();
                return;
            }

            $fileName = FileHelper::sanitizeFileName($download->getId()."_".$story->title." - ".$story->author);
            $filePath = storage_path('app/tmp');
            $download->setFileName($fileName);
            if ($book->saveBook($fileName, $filePath) === false) {
                $download->setStatus(DownloadStatus::ERROR);
                $download->setStatusMessage('Failed to generate eBook.');
                $this->save();
                return;
            }

            $fileNameWithPath = $filePath.DIRECTORY_SEPARATOR.$download->getFileName();
            if ($download->getFormat() != 'epub') {
                if (file_exists("{$fileNameWithPath}.{$download->getFormat()}")) {
                    \Log::warning("{$fileNameWithPath}.{$download->getFormat()} already exists, waiting for build to complete...");
                } else {
                    try {
                        // set UTF8-encoding for foreign characters
                        $locale='en_US.UTF-8';
                        setlocale(LC_ALL,$locale);
                        putenv('LC_ALL='.$locale);
                        exec("ebook-convert {$fileNameWithPath}.epub {$fileNameWithPath}.{$download->getFormat()} --margin-left 36 --margin-right 36 --margin-top 36 --margin-bottom 36 2>&1", $output);
                        $result = implode("\n", $output);
                        if (strpos($result, 'saved to') === false) {
                            \Log::error('Could not save file');
                            \Log::error($output);
                            $download->setStatus(DownloadStatus::ERROR);
                            $download->setStatusMessage('Failed to convert eBook to requested format.');
                        } else if (strpos($result, 'Killed') !== false && strpos($result, 'saved to') === false) {
                            \Log::error('Not enough memory.');
                            \Log::error($output);
                            $download->setStatus(DownloadStatus::ERROR);
                            $download->setStatusMessage('Failed to convert eBook to requested format. File may be too large.');
                        } else {
                            unlink($fileNameWithPath.'.epub');
                        }
                    } catch (\Exception $ex) {
                        $download->setStatus(DownloadStatus::ERROR);
                        $download->setStatusMessage('Failed to convert eBook to requested format. Please try again later.');
                    }
                }
            }
            if (empty($download->getEmail())) {
                $download->setStatus(DownloadStatus::DONE);
                $download->clearChapters();
                $this->save();
            } else {
                if (!Helper::mailAttachment($download->getId(), $download->getFileName().'.'.$download->getFormat(), $filePath, $download->getEmail())) {
                    $download->setStatus(DownloadStatus::ERROR);
                    $download->setStatusMessage('Failed to send email. Please try again later.');
                    $this->save();
                    return;
                }
                $download->setStatus(DownloadStatus::EMAILED);
                $download->clearChapters();
                $this->save();
                try {
                    unlink($fileNameWithPath.'.'.$download->getFormat());
                } catch (\Exception $ex) {

                }
            }
        } catch (\Exception $ex) {
            \Log::error($ex);
            $download->setStatus(DownloadStatus::ERROR);
            $download->setStatusMessage('Failed to build eBook.');
            $this->save();
            return;
        }
    }

    function save()
    {
        $userKey = 'user_' . $this->download->getSessionId();
        if (Cache::has($userKey)) {
            $downloads = Cache::get($userKey);
            $downloads[$this->download->getId()] = $this->download;
            Cache::put($userKey, $downloads, 15);
            $this->sendUpdate($this->download->getSessionId(), $downloads);
        } else {
            throw new \Exception('User session not found.');
        }
    }

    public static function sendUpdate($sessionId, $downloads)
    {
        Helper::sendServerWebsocketMessage([
            'type' => 'update',
            'data' => [
                'id' => $sessionId,
                'downloads' => $downloads
            ]
        ]);
    }
}
