<?php
require 'sites/fanfictionnet.php';
require 'sites/adultfanfiction.php';
require 'sites/hpfanficarchive.php';
require 'sites/asianfanfics.php';

function getChapter($url, $chapterNumber, $metadata) {
    try {
        $urlParts = parse_url($url);
        $host = $urlParts['host'];
        if ($host == 'www.fanfiction.net' || $host == 'www.fictionpress.com') {
            return getFanfictionNetChapter($url, $chapterNumber);
        } else if (strpos($host, '.adult-fanfiction.org') !== FALSE) {
            return getAdultFanfictionOrgChapter($url, $chapterNumber);
        } else if ($host == 'www.hpfanficarchive.com') {
            return getHPFanficArchiveChapter($url, $chapterNumber, $metadata);
        } else if ($host == 'www.asianfanfics.com') {
            return getAsianFanficsChapter($url, $chapterNumber);
        } else {
            throw new FicSaveException("This should never happen.");
        }
    } catch (Exception $ex) {
        throw new FicSaveException("Failed to download chapter {$chapterNumber} of {$url}.");
    }
}

function getInfo($url) {
    try {
        $urlParts = parse_url($url);
        if (isset($urlParts['host'])) {
            $host = $urlParts['host'];
            if ($host == 'www.fanfiction.net' || $host == 'm.fanfiction.net' || $host == 'www.fictionpress.com' || $host == 'm.fictionpress.com') {
                if ($host == 'm.fanfiction.net') {
                    $url = str_replace($host, 'www.fanfiction.net', $url);
                } else if ($host == 'm.fictionpress.com') {
                    $url = str_replace($host, 'www.fictionpress.com', $url);
                }
                return getFanfictionNetInfo($url);
            } else if (strpos($host, '.adult-fanfiction.org') !== FALSE) {
                return getAdultFanfictionOrgInfo($url);
            } else if ($host == 'www.hpfanficarchive.com') {
                return getHPFanficArchiveInfo($url);
            } else if ($host == 'www.asianfanfics.com') {
                return getAsianFanficsInfo($url);
            }
            throw new FicSaveException("That website is not supported by FicSave :(");
        } else {
            throw new FicSaveException("Invalid URL! Please make sure you have pasted the exact link.");
        }
    } catch (Exception $ex) {
        throw new FicSaveException("Invalid URL! Please make sure you have pasted the exact link.");
    }
}

function stripAttributes($html) {
    return preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/i",'<$1$2>', $html);
}