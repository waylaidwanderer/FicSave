<?php
use \Masterminds\HTML5;
function getAdultFanfictionOrgChapter($url, $chapterNumber) {
    $response = cURL($url . "&chapter=" . $chapterNumber);
    $html = new HTML5();
    $html = $html->loadHTML($response);

    $chapter = new Chapter;
    $chapter->number = $chapterNumber;

    $chapterTitle = qp($html, 'select[name=chapnav]')->find('option[selected]')->text();
    $chapterTitle = trim(str_replace($chapterNumber.'.', '', $chapterTitle));
    $chapter->title = $chapterTitle;

    $content = stripAttributes(trim(qp(qp($html, 'form[name=form]')->find('tr')->get(3))->find('td')->innerHTML()));
    $chapter->content = $content;

    return $chapter;
}

function getAdultFanfictionOrgInfo($url) {
    $urlParts = parse_url($url);
    parse_str($urlParts['query'], $query);

    if (isset($query['no'])) {
        $storyId = $query['no'];
        if (is_numeric($storyId)) {
            $response = cURL($url);
            $html = new HTML5();
            $html = $html->loadHTML($response);

            $story = new Story;
            $story->id = $storyId;
            $urlParts = parse_url($url);
            $story->url = "{$urlParts['scheme']}://{$urlParts['host']}/story.php?no={$storyId}";

            $title = trim(str_replace('Story:', '', qp($html, 'title')->text()));
            if (empty($title)) {
                throw new FicSaveException("Could not retrieve title for story at $url.");
            } else {
                $story->title = $title;
            }

            $author = trim(qp(qp($html, 'tr.catdis')->find('td')->get(1))->find('a')->text());
            if (empty($author)) {
                throw new FicSaveException("Could not retrieve author for story at $url.");
            } else {
                $story->author = $author;
            }

            $numChapters = qp($html, 'select[name=chapnav]')->find('option')->count();
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