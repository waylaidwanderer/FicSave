<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2016-06-15
 * Time: 7:36 PM
 */

namespace App\Ficsave\Sites;


use App\Ficsave\Chapter;
use App\Ficsave\FicSaveException;
use App\Ficsave\Story;
use App\Helper;
use Masterminds\HTML5;

class RoyalRoad
{
    public static function getChapter($url, $chapterNumber, $metadata) {
        $chapter = new Chapter;
        $chapter->number = $chapterNumber;
        $chapter->title = $metadata[$chapterNumber - 1 ][1];

        $response = "";
        for ($j = 0; $j < 10; $j++) {
            $response = Helper::cURL($metadata[$chapterNumber - 1][0]);
            if (!empty($response)) break;
            sleep(1);
        }

        $html = new HTML5;
        $html = $html->loadHTML($response);

        #$chapter->content = Helper::stripAttributes(qp($html, '#chapter-content')->innerHTML());
        $chapter->content = qp($html, '.chapter-content')->innerHTML();

        return $chapter;
    }

    public static function getInfo($url) {
        $urlParts = parse_url($url);
        $pathParts = explode('/', $urlParts['path']);
        if (isset($pathParts[3])) {
            $storyId = $pathParts[2];
            if (is_numeric($storyId)) {
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
                $urlParts = parse_url($url);
                $story->url = "{$urlParts['scheme']}://{$urlParts['host']}/fiction/{$storyId}/{$pathParts[3]}";

                $title = qp($html, 'meta[name="twitter:title"]')->attr("content");
                if (empty($title)) {
                    throw new FicSaveException("Could not retrieve title for story at $url.");
                } else {
                    $story->title = $title;
                }

                $author = qp($html, 'meta[name="twitter:creator"]')->attr("content");
                if (empty($author)) {
                    throw new FicSaveException("Could not retrieve author for story at $url.");
                } else {
                    $story->author = $author;
                }

                $description = qp($html, 'div.description')->text();
                if ($description == null) {
                    throw new FicSaveException("Could not retrieve description for story at $url.");
                } else {
                    $story->description = $description;
                        #Helper::stripAttributes(preg_replace('/<a(.*?)>(.*?)<\/a>/', '\2', trim(qp($description)->html() . qp($description)->next()->html())));
                }

                # Chapters are listed in a table. Count the rows and store the URLs.

                $tableRows = qp($html, 'table#chapters')->find('tbody')->find('tr');
                #$story->chaptersList = array();
                $story->metadata = array();

                foreach ($tableRows as $row) {
                    $a = $row->find('td')->first()->find('a');

                    $url = "http://www.royalroad.com" . ($a->attr('href'));
                    $t = trim($a->text());

                    # Push array of URL & title onto metadata array
                    $story->metadata[] = array($url,$t);
                }

                $numChapters = sizeof($story->metadata);
                $story->chapters = $numChapters == 0 ? 1 : $numChapters;

                $coverImageUrl = qp($html, 'meta[name="og:image"]')->attr("content");
                if ($coverImageUrl == null || $coverImageUrl === "") {
                    $coverImageUrl = qp($html, 'meta[name="twitter:image"]')->attr("content");
                }
                if ($coverImageUrl == null || $coverImageUrl === "") {
                    $coverImageUrl = qp($html, 'img.thumbnail')->attr("src");
                }
                if ($coverImageUrl == null || $coverImageUrl === "") {
                    $coverImageUrl = qp($html, 'img.thumbnail')->attr("src");
                }
                if ($coverImageUrl != null) {
                    if( strpos($coverImageUrl, "http") === 0 ){
                        $story->coverImageUrl = $coverImageUrl;
                    }
                    else{
                        $story->coverImageUrl = "http://www.royalroad.com" . $coverImageUrl;
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
