<?php
use \Masterminds\HTML5;
function getFanfictionNetChapter($url, $chapterNumber) {
    $response = cURL($url . "/" . $chapterNumber);
    $html = new HTML5();
    $html = $html->loadHTML($response);

    $chapter = new Chapter;
    $chapter->number = $chapterNumber;

    $numChapters = qp($html, '#chap_select')->find('option')->count() / 2; // value is always doubled for some reason
    $numChapters = $numChapters == 0 ? 1 : $numChapters;
    if ($numChapters > 1) {
        $chapterTitleContainer = qp($html, '#chap_select')->find('option')->get($chapterNumber - 1);
        if ($chapterTitleContainer != NULL) {
            $chapterTitle = qp($chapterTitleContainer)->text();
            $chapterTitle = str_replace($chapterNumber.". ", "", $chapterTitle);
            $chapter->title = $chapterTitle;
        }
    } else {
        $title = qp($html, '#profile_top')->find('b')->first()->text();
        if (empty($title)) {
            $chapter->title = 'Chapter ' . $chapterNumber;
        } else {
            $chapter->title = $title;
        }
    }

    $chapter->content = stripAttributes(qp($html, '#storytext')->innerHTML());

    return $chapter;
}

function getFanfictionNetInfo($url) {
    $urlParts = parse_url($url);
    $pathParts = explode('/', $urlParts['path']);
    if (isset($pathParts[2])) {
        $storyId = $pathParts[2];
        if (is_numeric($storyId)) {
            $response = cURL($url);
            $html = new HTML5();
            $html = $html->loadHTML($response);

            $story = new Story;
            $story->id = $storyId;
            $urlParts = parse_url($url);
            $story->url = "{$urlParts['scheme']}://{$urlParts['host']}/s/{$storyId}";

            $title = qp($html, '#profile_top')->find('b')->first()->text();
            if (empty($title)) {
                throw new FicSaveException("Could not retrieve title for story at $url.");
            } else {
                $story->title = $title;
            }

            $author = qp($html, '#profile_top')->find('a')->first()->text();
            if (empty($author)) {
                throw new FicSaveException("Could not retrieve author for story at $url.");
            } else {
                $story->author = $author;
            }

            $description = qp($html, '#profile_top')->find('div')->get(2);
            if ($description == NULL) {
                throw new FicSaveException("Could not retrieve description for story at $url.");
            } else {
                $story->description = stripAttributes(preg_replace('/<a(.*?)>(.*?)<\/a>/', '\2', trim(qp($description)->html() . qp($description)->next()->html())));
            }

            $numChapters = qp($html, '#chap_select')->find('option')->count() / 2; // value is always doubled for some reason
            $story->chapters = $numChapters == 0 ? 1 : $numChapters;

            $coverImageUrl = qp($html, '#profile_top')->find('img')->first()->attr('src');
            if ($coverImageUrl != NULL) {
                $coverImageUrlParts = parse_url($coverImageUrl);
                if (!isset($coverImageUrlParts['scheme']) && substr($coverImageUrl, 0, 2) == '//') {
                    $coverImageUrl = $urlParts['scheme'] . ":" . $coverImageUrl;
                }
                $coverImageUrl = str_replace('/75/', '/180/', $coverImageUrl);
                $story->coverImageUrl = $coverImageUrl;
            }

            return $story;
        } else {
            throw new FicSaveException("URL has an invalid story ID: $storyId.");
        }
    } else {
        throw new FicSaveException("URL is missing story ID.");
    }
}