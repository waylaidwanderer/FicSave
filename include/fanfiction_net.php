<?php
function getStoryAttributes($xpath, $story)
{
	$story_author = $xpath->query("//div[@id='profile_top']/a[1]");
	$story_title = $xpath->query("//div[@id='profile_top']/b");
	$story_desc = $xpath->query("//div[@id='profile_top']/div");
	$story_image = $xpath->query("//div[@id='profile_top']/span/img/@src");

	$story_chapters = $xpath->query("//select[@id='chap_select']/option");	

	// ========== GET STORY PROPERTIES ========== //
	foreach ($story_title as $title) {
	    $story["title"] = stripAccents(verify($title->nodeValue));
	    //echo "title: " . $story["title"] . "\r\n";
	}
	foreach ($story_author as $author)
	{
	    $story["author"] = verify($author->nodeValue);
        //echo "author: " . $story["author"] . "\r\n";
	}
	foreach ($story_desc as $desc) {
	    $story["desc"] = verify($desc->nodeValue);
	    //echo "description: " . $story["desc"] . "\r\n";
	}
	foreach ($story_image as $image) {
	    $story["image"] = verify($image->nodeValue);
	    //echo "image src: " . $story["image"] . "\r\n";
	}

	$story["numChapter"] = 1;
	$story["hasChapters"] = $xpath->evaluate("boolean(//select[@id='chap_select'])");

	if (!isset($story["author"]) || !isset($story["title"]) || !isset($story["desc"]))
	{
		throw new WrongFormatException("Data received contained invalid format.");
	}

    $story["numChapter"] = 1;
	$story["chapters"] = array();
	$story["chapters"]["title"] = array();
	$story["chapters"]["content"] = array();
	if ($story["hasChapters"])
	{
	    foreach ($story_chapters as $chapter) {
	        $new_url = $story["story_url"] . $story["numChapter"] . "/";
	        $title = verify($chapter->nodeValue);
	        if (startsWith($title, $story["numChapter"]."."))
	        {
	            $title = str_replace($story["numChapter"] . ". ", "", $title);
	            array_push($story["chapters"]["title"], $title);
	            array_push($story["chapters"]["content"], getChapter($new_url, $story["debug"]));
	        }
	        else
	        {
	            break;
	        }
	        $story["numChapter"]++;
	    }
	}
	else
	{
	    array_push($story["chapters"]["title"], $story["title"]);
	    array_push($story["chapters"]["content"], getChapter($story["story_url"], $story["debug"]));
	}

	return $story;
}

function getChapter($url, $debug)
{
	$content = extract_id(cURL($url), "storytext", $debug);
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

function verify($string)
{
    return trim(utf8_decode($string));
}
?>