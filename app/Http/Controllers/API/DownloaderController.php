<?php

namespace App\Http\Controllers\API;

use App\Enums\DownloadStatus;
use App\Ficsave\Download;
use App\Ficsave\Ficsave;
use App\Ficsave\FicSaveException;
use App\Helper;
use App\Jobs\StoryDownload;
use Cache;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class DownloaderController extends Controller
{
    public function postBegin(Request $request)
    {
        if ($request->session()->get('currentId', '') != $request->input('currentId')) return response()->json([
            'success' => false,
            'message' => 'A new session has been started in a different window! Please switch to the new session or refresh the page.'
        ]);
        if ($request->input('resume') != null) return response()->json([
            'success' => true,
            'downloads' => $request->session()->get('downloads')
        ]);

        try {
            $this->validate($request, [
                'url' => 'required|url',
                'format' => 'required|in:epub,mobi,txt,pdf',
                'email' => 'present|email'
            ]);
        } catch (ValidationException $ex) {
            return response()->json([
                'success' => false,
                'message' => implode(' ', $ex->validator->errors()->all())
            ]);
        }

        $json = [];
        $json['success'] = true;

        $url = $request->input('url');
        $format = $request->input('format');
        $email = strtolower($request->input('email', ''));
        if ($format == 'pdf') return response()->json([
            'success' => false,
            'message' => 'PDF is currently disabled. Please use another format.'
        ]);

        try {
            $story = Ficsave::getInfo($url);
            $download = new Download();
            $download->setSessionId($request->session()->getId())
                     ->setId(uniqid())
                     ->setStory($story)
                     ->setNumChapters($story->chapters)
                     ->setFormat($format)
                     ->setEmail($email);
            $userKey = 'user_' . $download->getSessionId();
            $downloads = [];
            if (Cache::has($userKey)) {
                $downloads = Cache::get($userKey);
            }
            $downloads[$download->getId()] = $download;
            Cache::put($userKey, $downloads, 15);
            StoryDownload::sendUpdate($download->getSessionId(), $downloads);
            $this->dispatch(new StoryDownload($download));
        } catch (FicSaveException $ex) {
            $json['success'] = false;
            $json['message'] = $ex->getMessage();
        }

        return response()->json($json);
    }
}
