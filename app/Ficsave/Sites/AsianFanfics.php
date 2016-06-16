<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2016-06-15
 * Time: 7:34 PM
 */

namespace App\Ficsave\Sites;


use App\Ficsave\Chapter;
use App\Ficsave\FicSaveException;
use App\Ficsave\Story;
use App\Helper;
use Masterminds\HTML5;

class AsianFanfics
{
    public static function getChapter($url, $chapterNumber) {
        $response = Helper::cURL($url . "/" . $chapterNumber);
        $html = new HTML5;
        $html = $html->loadHTML($response);

        $chapter = new Chapter;
        $chapter->number = $chapterNumber;
        $chapter->title = trim(str_replace($chapterNumber, '', qp($html, 'select[name="chapterNav"]')->find('option[selected]')->last()->text()));
        $chapter->content = Helper::stripAttributes(qp($html, '#bodyText')->innerHTML());

        return $chapter;
    }

    public static function getInfo($url) {
        $urlParts = parse_url($url);
        $pathParts = explode('/', $urlParts['path']);
        if (isset($pathParts[3])) {
            $storyId = $pathParts[3];
            if (is_numeric($storyId)) {
                $url = "{$urlParts['scheme']}://{$urlParts['host']}/story/view/{$storyId}";

                $response = Helper::cURL($url);
                $html = new HTML5;
                $html = $html->loadHTML($response);

                $story = new Story;
                $story->id = $storyId;
                $story->url = $url;

                $title = trim(qp($html, 'h1.title')->first()->text());
                if (empty($title)) {
                    throw new FicSaveException("Could not retrieve title for story at $url.");
                } else {
                    $story->title = $title;
                }

                $author = qp(qp($html, 'span.text--info')->get(0))->next()->text();
                if (empty($author)) {
                    throw new FicSaveException("Could not retrieve author for story at $url.");
                } else {
                    $story->author = $author;
                }

                $description = qp($html, '#bodyText')->find('h2')->first()->next();
                if ($description == NULL) {
                    throw new FicSaveException("Could not retrieve description for story at $url.");
                } else {
                    $story->description = Helper::stripAttributes(trim($description->innerHTML()));
                }

                $story->chapters = qp($html, 'select[name="chapterNav"]')->find('option')->count() - 1;

                return $story;
            } else {
                throw new FicSaveException("URL has an invalid story ID: $storyId.");
            }
        } else {
            throw new FicSaveException("URL is missing story ID.");
        }
    }
}
