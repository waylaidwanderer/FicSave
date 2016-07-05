<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2016-06-15
 * Time: 7:38 PM
 */

namespace App\Ficsave\Sites;


use App\Ficsave\Chapter;
use App\Ficsave\FicSaveException;
use App\Ficsave\Story;
use App\Helper;
use Masterminds\HTML5;

class HpFanficArchive
{
    public static function getChapter($url, $chapterNumber, $metadata) {
        $response = "";
        for ($i = 0; $i < 10; $i++) {
            $response = Helper::cURL($url . "&chapter=" . $chapterNumber);
            if (!empty($response)) break;
            sleep(1);
        }
        $html = new HTML5;
        $html = $html->loadHTML($response);

        $chapter = new Chapter;
        $chapter->number = $chapterNumber;
        $chapter->title = $metadata[$chapterNumber - 1];
        $chapter->content = Helper::stripAttributes(qp($html, '#story')->innerHTML());

        return $chapter;
    }

    public static function getInfo($url) {
        $urlParts = parse_url($url);
        parse_str($urlParts['query'], $query);
        if (isset($query['sid'])) {
            $storyId = $query['sid'];
            if (is_numeric($storyId)) {
                $url = "{$urlParts['scheme']}://{$urlParts['host']}/stories/viewstory.php?sid={$storyId}";

                $response = "";
                for ($i = 0; $i < 10; $i++) {
                    $response = Helper::cURL($url);
                    if (!empty($response)) break;
                    sleep(1);
                }
                $html = new HTML5;
                $html = $html->loadHTML($response);

                $story = new Story;
                $story->id = $storyId;
                $story->url = $url;

                $title = qp($html, '#pagetitle')->find('a[href^="viewstory"]')->first()->text();
                if (empty($title)) {
                    throw new FicSaveException("Could not retrieve title for story at $url.");
                } else {
                    $story->title = $title;
                }

                $author = qp($html, '#pagetitle')->find('a[href^="viewuser"]')->first()->text();
                if (empty($author)) {
                    throw new FicSaveException("Could not retrieve author for story at $url.");
                } else {
                    $story->author = $author;
                }

                $description = qp($html, '#mainpage')->find('.block')->get(1);
                if ($description == null) {
                    throw new FicSaveException("Could not retrieve description for story at $url.");
                } else {
                    $story->description = Helper::stripAttributes(preg_replace('/<a(.*?)>(.*?)<\/a>/', '\2', trim(qp($description)->find('.content')->first()->innerHTML())));
                }

                $chaptersBlock = qp($html, '#mainpage')->find('.block')->get(3);
                if ($chaptersBlock == null) {
                    throw new FicSaveException("Could not get number of chapters for story at $url.");
                } else {
                    $chapterLinks = qp($chaptersBlock)->find('a[href^="viewstory"]');
                    $numChapters = $chapterLinks->count();
                    if ($numChapters > 0) {
                        $story->chapters = $numChapters;
                        $story->metadata = array();
                        foreach ($chapterLinks as $chapterLink) {
                            $story->metadata[] = $chapterLink->text();
                        }
                    } else {
                        throw new FicSaveException("Could not get number of chapters for story at $url.");
                    }
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
