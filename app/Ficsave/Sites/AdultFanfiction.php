<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2016-06-15
 * Time: 7:32 PM
 */

namespace App\Ficsave\Sites;


use App\Ficsave\Chapter;
use App\Ficsave\FicSaveException;
use App\Ficsave\Story;
use App\Helper;
use Masterminds\HTML5;

class AdultFanfiction
{
    public static function getChapter($url, $chapterNumber) {
        $urlParts = parse_url($url);
        parse_str($urlParts['query'], $query);
        if (isset($query['no'])) {
            $storyId = $query['no'];
            $url = "{$urlParts['scheme']}://{$urlParts['host']}/story.php?no={$storyId}";
            $response = "";
            for ($i = 0; $i < 10; $i++) {
                $response = Helper::cURL($url . "&chapter=" . $chapterNumber);
                if (!empty($response)) break;
                sleep(1);
            }
            $response = Helper::normalizeText($response);
            $html = new HTML5;
            $html = $html->loadHTML($response);

            $chapter = new Chapter;
            $chapter->number = $chapterNumber;

            $chapterTitle = qp($html, '#snav')->find("a[href=\"/story.php?no={$storyId}&chapter={$chapterNumber}\"]")->text();
            $chapterTitle = trim(str_replace($chapterNumber.'-', '', $chapterTitle));
            $chapter->title = $chapterTitle;

            $contentDiv = qp($html, '#contentdata');
            $content = Helper::stripAttributes(trim($contentDiv->find('tr:nth-child(3)')->find('td')->innerHtml()));
            $chapter->content = $content;

            return $chapter;
        }
        return null;
    }

    public static function getInfo($url) {
        $urlParts = parse_url($url);
        parse_str($urlParts['query'], $query);
        if (isset($query['no'])) {
            $storyId = $query['no'];
            if (is_numeric($storyId)) {
                $url = "{$urlParts['scheme']}://{$urlParts['host']}/story.php?no={$storyId}";
                $response = "";
                for ($i = 0; $i < 10; $i++) {
                    $response = Helper::cURL($url);
                    if (!empty($response)) break;
                    sleep(1);
                }
                $response = Helper::normalizeText($response);
                $html = new HTML5;
                $html = $html->loadHTML($response);

                $story = new Story;
                $story->id = $storyId;
                $story->url = $url;

                $content = qp($html, '#contentdata');
                $title = $content->find('h2')->text();
                if (empty($title)) {
                    throw new FicSaveException("Could not retrieve title for story at $url.");
                } else {
                    $story->title = $title;
                }

                $author = $content->find('a[href^="http://members.adult-fanfiction.org/profile.php?no="]')->text();
                if (empty($author)) {
                    throw new FicSaveException("Could not retrieve author for story at $url.");
                } else {
                    $story->author = $author;
                }

                $numChapters = 0;
                $chapterLinks = $content->find('.pagination')->first()->find('li');
                if ($chapterLinks->count() == 1) {
                    $numChapters = 1;
                } else if ($chapterLinks->count() > 1) {
                    $chapterUrl = $chapterLinks->find('a')->last()->attr('href');
                    parse_str($chapterUrl, $chapterParts);
                    $numChapters = $chapterParts['chapter'];
                }
                if ($numChapters > 0) {
                    $story->chapters = $numChapters;
                } else {
                    throw new FicSaveException("Could not get number of chapters for story at $url.");
                }

                return $story;
            } else {
                throw new FicSaveException("URL has an invalid story ID: $storyId.");
            }
        } else {
            throw new FicSaveException("URL is missing story ID.");
        }
    }
}
