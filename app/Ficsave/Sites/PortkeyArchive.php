<?php

namespace App\Ficsave\Sites;

// Added by SrTobi <code.databyte@gmail.com> (https://github.com/SrTobi)


use App\Ficsave\Chapter;
use App\Ficsave\FicSaveException;
use App\Ficsave\Story;
use App\Helper;
use Masterminds\HTML5;

class PortkeyArchive
{
    public static function getChapter($url, $chapterNumber) {
        $chapter = new Chapter;
        $chapter->number = $chapterNumber;

        for ($i = 0; $i < 5; $i++) {
            $response = "";
            for ($j = 0; $j < 10; $j++) {
                $response = Helper::cURL($url . "/" . $chapterNumber);
                if (!empty($response)) break;
                sleep(1);
            }

            $html = new HTML5;
            $html = $html->loadHTML($response);

            $chapter->title = qp($html, 'nav.chapter-nav>h3')->text();
            // wrapAll does the wrapping but does not return the wrapping element
            // instead it returns the same list as filterPreg...
            // so we call parent which gives the parent of the first child
            // which is.... dam dam dam... the wrapping element
            // and then we simply take the wrapping element away by calling innerHtml :D... easy
            $wrapper = qp($html, 'div.chapter-container')->children("p")->filterPreg('/\S/')->wrapAll("<div />")->parent();
            // the filterPreg is used because portkey-archive adds a lot of empty p elements
            $chapter->content = Helper::stripAttributes($wrapper->innerHtml());

            if (!empty($chapter->content)) break;
            sleep(1);
        }

        return $chapter;
    }

    public static function getInfo($url) {
        $urlParts = parse_url($url);
        $pathParts = explode('/', $urlParts['path']);
        if (isset($pathParts[2])) {
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
                $story->url = "{$urlParts['scheme']}://{$urlParts['host']}/story/{$storyId}";

                $title = qp($html, '.story-title')->find('h2')->first()->text();
                if (empty($title)) {
                    throw new FicSaveException("Could not retrieve title for story at $url.");
                } else {
                    $story->title = $title;
                }

                $author = qp($html, '.story-title')->find('a.author-link')->first()->text();
                if (empty($author)) {
                    throw new FicSaveException("Could not retrieve author for story at $url.");
                } else {
                    $story->author = $author;
                }

                $description = qp($html, 'div.story-info');
                if ($description == null) {
                    throw new FicSaveException("Could not retrieve description for story at $url.");
                } else {
                    $description->remove("h3");
                    $description->remove("span.story-title");
                    // it seems that special characters are also escaped by portkey-archive in the story summary
                    // I think that is a bug! See https://www.portkey-archive.org/story/9147 for example
                    // in the search results it is rendered just fine. (entry 14 when doing a default search)
                    $story->description = Helper::stripAttributes(preg_replace('/<a(.*?)>(.*?)<\/a>/', '\2', preg_replace('/\r|\n/', '', trim($description->html()))));
                }

                $numChapters = qp($html, 'nav.chapter-list')->find('ol')->children("li")->count();
                $story->chapters = $numChapters == 0 ? 1 : $numChapters;

                return $story;
            } else {
                throw new FicSaveException("URL has an invalid story ID: $storyId.");
            }
        } else {
            throw new FicSaveException("URL is missing story ID.");
        }
    }
}
