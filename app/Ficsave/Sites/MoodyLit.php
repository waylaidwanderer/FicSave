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

class MoodyLit
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

        $paragraphs = qp($html, 'p');
        foreach ($paragraphs as $p){
            $chapter->content .= $p->html();
        }

        #$chapter->content = Helper::stripAttributes(qp($html, '#chapter-content')->innerHTML());
        #$chapter->content = qp($html, '.chapter-content')->innerHTML();

        return $chapter;
    }

    public static function getInfo($url) {

        $response = "";
        for ($i = 0; $i < 10; $i++) {
            $response = Helper::cURL($url);
            if (!empty($response)) break;
            sleep(1);
        }
        $html = new HTML5;
        $html = $html->loadHTML($response);

        $story = new Story;

        # CYA: I don't know if id must be numeric or not, so force it to a unique number based on url.
        $storyId = base_convert(hash("md5",$url),16,10);
        $story->id = $storyId;
        $story->url = $url;

        $title = trim(qp($html, 'h1.leader')->text());
        if (empty($title)) {
            throw new FicSaveException("Could not retrieve title for story at $url.");
        } else {
            $story->title = $title;
        }

        $author = "V. Moody";
        if (empty($author)) {
            throw new FicSaveException("Could not retrieve author for story at $url.");
        } else {
            $story->author = $author;
        }

        $a = qp($html,'div.align-justify');
        $description = $a->innerHTML();
        if ($description == null) {
            throw new FicSaveException("Could not retrieve description for story at $url.");
        } else {
            $story->description = $description;
        }

        # Chapters are listed in a table. Count the rows and store the URLs.

        $tableRows = qp($html, 'table#category-book-toc')->find('tbody')->find('tr');
        $story->metadata = array();

        foreach ($tableRows as $row) {
            $a = $row->find('a');

            $url = "http://moodylit.com" . ($a->attr('href'));
            $t = trim($a->text());

            # Push array of URL & title onto metadata array
            $story->metadata[] = array($url,$t);
        }

        $numChapters = sizeof($story->metadata);
        $story->chapters = $numChapters == 0 ? 1 : $numChapters;

        $coverImageUrl = qp($html, 'img.book-cover')->attr("src");
        if ($coverImageUrl != null) {
            $story->coverImageUrl = "http://moodylit.com" . $coverImageUrl;
        }

        return $story;
    }
}
