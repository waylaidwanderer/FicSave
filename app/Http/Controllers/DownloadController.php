<?php

namespace App\Http\Controllers;

use App\Enums\DownloadStatus;
use Illuminate\Http\Request;

use App\Http\Requests;

class DownloadController extends Controller
{
    public function getIndex(Request $request, $bookId)
    {
        $downloads = $request->session()->get('downloads');
        if (!isset($downloads[$bookId]) || $downloads[$bookId]['status'] < DownloadStatus::DONE) abort(404);
        $download = $downloads[$bookId];
        $fileName = "{$download['fileName']}.{$download['format']}";
        $fileNameWithPath = storage_path('app/tmp').DIRECTORY_SEPARATOR.$fileName;
        if (file_exists($fileNameWithPath)) {
            $downloads[$bookId]['status'] = DownloadStatus::SERVED;
            $request->session()->set('downloads', $downloads);
            $rename = "{$download['story']['title']} - {$download['story']['author']}.{$download['format']}";
            return response()->download($fileNameWithPath, $rename);
        } else {
            unset($downloads[$bookId]);
            $request->session()->set('downloads', $downloads);
            return 'File not found: ' . $fileNameWithPath;
        }
    }
}
