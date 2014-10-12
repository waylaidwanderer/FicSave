<?php
require("include/Encoding.php");

function getStoryAttributes($xpath, $story)
{
	$parsedUrl = parse_url($story["story_url"]);
	$host = explode('.', $parsedUrl['host']);
	$subdomain = $host[0];
	$cookies = "HasVisited=bypass page next time; path=/; domain=$subdomain.adult-fanfiction.org";

	$story_author = $xpath->query("//tr[5]/td[2]");
	$story_title = $xpath->query("//html/head/title");
	$story_chapters = $xpath->query("//select[@name='chapnav']/option");	

	// ========== GET STORY PROPERTIES ========== //
	foreach ($story_title as $title) {
	    $story["title"] = str_replace("Story: ", '', stripAccents(trim($title->nodeValue)));
	}
	foreach ($story_author as $author)
	{
	    $story["author"] = str_replace("Author: ", '', str_replace("\t", '', str_replace("\n", '', $author->nodeValue)));
	}

	if (!isset($story["author"]) || !isset($story["title"]))
	{
		throw new WrongFormatException("Data received contained invalid format. Author: {$story['author']}, Title: {$story['title']}");
	}

	$story["desc"] = "";
    $story["numChapter"] = 1;
	$story["chapters"] = array();
	$story["chapters"]["title"] = array();
	$story["chapters"]["content"] = array();
	foreach ($story_chapters as $chapter) {
	    $new_url = $story["story_url"] . "&chapter=" . $story["numChapter"];
	    $title = trim($chapter->nodeValue);
	    if (startsWith($title, $story["numChapter"]."."))
	    {
	        $title = str_replace($story["numChapter"] . ". ", "", $title);
	        array_push($story["chapters"]["title"], $title);
	        array_push($story["chapters"]["content"], \ForceUTF8\Encoding::fixUTF8(getChapter($new_url, $story["debug"], $cookies, $story["uniqid"])));
	    }
	    else
	    {
	        break;
	    }
	    $story["numChapter"]++;
	}

	return $story;
}

function getChapter($url, $debug, $cookies, $uniqid)
{
	$dom = new DOMDocument();
	$dom->loadHTML(cURL($url, $debug, $cookies, $uniqid));
	$xpath = new DOMXPath($dom);
	$xpath_content = $xpath->query("//tr[4]/td");
	$content = "";
	foreach ($xpath_content as $node_content) {		
	    $content = $node_content->ownerDocument->saveHTML($node_content);	    
	}
	$content = str_replace(
	array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
	array("'", "'", '"', '"', '-', '--', '...'),
	$content);
	$content = str_replace(
	array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
	array("'", "'", '"', '"', '-', '--', '...'),
	$content);
    return $content;
}
?>