<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2016-06-15
 * Time: 7:08 PM
 */

namespace App\Ficsave;


use App\Ficsave\Sites\AdultFanfiction;
use App\Ficsave\Sites\AsianFanfics;
use App\Ficsave\Sites\FanfictionNet;
use App\Ficsave\Sites\HpFanficArchive;

class Ficsave
{
    public static function getChapter($url, $chapterNumber, $metadata) {
        try {
            $urlParts = parse_url($url);
            $host = $urlParts['host'];
            if ($host == 'www.fanfiction.net' || $host == 'www.fictionpress.com') {
                return FanfictionNet::getChapter($url, $chapterNumber);
            } else if (strpos($host, '.adult-fanfiction.org') !== FALSE) {
                return AdultFanfiction::getChapter($url, $chapterNumber);
            } else if ($host == 'www.hpfanficarchive.com') {
                return HpFanficArchive::getChapter($url, $chapterNumber, $metadata);
            } else if ($host == 'www.asianfanfics.com') {
                return AsianFanfics::getChapter($url, $chapterNumber);
            } else {
                throw new FicSaveException("This should never happen.");
            }
        } catch (\Exception $ex) {
            throw new FicSaveException("Failed to download chapter {$chapterNumber} of {$url}.");
        }
    }

    public static function getInfo($url) {
        $urlParts = parse_url($url);
        if (isset($urlParts['host'])) {
            $host = $urlParts['host'];
            if ($host == 'www.fanfiction.net' || $host == 'm.fanfiction.net' || $host == 'www.fictionpress.com' || $host == 'm.fictionpress.com') {
                if ($host == 'm.fanfiction.net') {
                    $url = str_replace($host, 'www.fanfiction.net', $url);
                } else if ($host == 'm.fictionpress.com') {
                    $url = str_replace($host, 'www.fictionpress.com', $url);
                }
                return FanfictionNet::getInfo($url);
            } else if (strpos($host, '.adult-fanfiction.org') !== FALSE) {
                return AdultFanfiction::getInfo($url);
            } else if ($host == 'www.hpfanficarchive.com') {
                return HpFanficArchive::getInfo($url);
            } else if ($host == 'www.asianfanfics.com') {
                return AsianFanfics::getInfo($url);
            }
            throw new FicSaveException("That website is not supported by FicSave :(");
        } else {
            throw new FicSaveException("Invalid URL! Please make sure you have pasted the exact link.");
        }
    }
}
