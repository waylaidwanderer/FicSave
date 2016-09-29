<?php

namespace App\Http\Controllers;

use App\Enums\DownloadStatus;
use App\Ficsave\Download;
use Illuminate\Http\Request;

use App\Http\Requests;

class DownloadController extends Controller
{
    public function getIndex(Request $request, $bookId)
    {
        $userKey = 'user_'.$request->session()->getId();
        /** @var Download[] $downloads */
        $downloads = \Cache::get($userKey, []);
        if (!isset($downloads[$bookId]) || $downloads[$bookId]->getStatus() < DownloadStatus::DONE) abort(404);
        $download = $downloads[$bookId];
        $fileName = "{$download->getFileName()}.{$download->getFormat()}";
        $fileNameWithPath = storage_path('app/tmp').DIRECTORY_SEPARATOR.$fileName;
        if (file_exists($fileNameWithPath)) {
            $download->setStatus(DownloadStatus::SERVED);
            $downloads[$bookId] = $download;
            \Cache::put($userKey, $downloads, 15);
            $rename = "{$download->getStory()->title} - {$download->getStory()->author}.{$download->getFormat()}";
            return response()->download($fileNameWithPath, $rename);
        } else {
            unset($downloads[$bookId]);
            \Cache::put($userKey, $downloads, 15);
            return 'File not found: ' . $fileNameWithPath;
        }
    }
}
