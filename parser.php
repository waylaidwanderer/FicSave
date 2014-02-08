<?php
set_time_limit(0);
if (!isset($_POST["story_url"]) || empty($_POST["story_url"]) || !isset($_POST["format"]) || empty($_POST["format"]) || strpos($_POST["story_url"], 'fanfiction.net') === FALSE)
{
    header("Location: {$_SERVER["HTTP_REFERER"]}");
    exit(0);
}
$story_url = $_POST["story_url"]; //"https://www.fanfiction.net/s/10063574/";
$story_id = getStoryID($story_url);
$story_url = "https://www.fanfiction.net/s/" . $story_id . "/";
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML(cURL($story_url));
 
$xpath = new DOMXPath($dom);

$story_author = $xpath->query("//div[@id='profile_top']/a");
$story_title = $xpath->query("//div[@id='profile_top']/b");
$story_desc = $xpath->query("//div[@id='profile_top']/div");
$story_image = $xpath->query("//div[@id='profile_top']/span/img/@src");

$story_chapters = $xpath->query("//select[@id='chap_select']/option");

$story = array();

// ========== GET STORY PROPERTIES ========== //
foreach ($story_title as $title) {
    $story["title"] = verify($title->nodeValue);
    //echo "title: " . $story["title"] . "<br>";
}
foreach ($story_author as $author)
{
    if (!empty($author->nodeValue))
    {
        $story["author"] = verify($author->nodeValue);
        //echo "author: " . $story["author"] . "<br>";
    }
}
foreach ($story_desc as $desc) {
    $story["desc"] = verify($desc->nodeValue);
    //echo "description: " . $story["desc"] . "<br>";
}
foreach ($story_image as $image) {
    $story["image"] = verify($image->nodeValue);
    //echo "image src: " . $story["image"] . "<br>";
}

if (!isset($story["author"]) || !isset($story["title"]) || !isset($story["desc"]))
{
	header("Location: {$_SERVER["HTTP_REFERER"]}");
    exit(0);
}

// ========== GET STORY CHAPTERS ========== //
$numChapter = 1;
$hasChapters = $xpath->evaluate("boolean(//select[@id='chap_select'])");
$story["chapters"] = array();
$story["chapters"]["title"] = array();
$story["chapters"]["content"] = array();
if ($hasChapters)
{
    foreach ($story_chapters as $chapter) {
        $new_url = $story_url . "/" . $numChapter . "/";
        $title = verify($chapter->nodeValue);
        if (startsWith($title, "$numChapter."))
        {
            $title = str_replace($numChapter . ". ", "", $title);
            array_push($story["chapters"]["title"], $title);            
            array_push($story["chapters"]["content"], getChapter($new_url));
            //echo $story["chapters"]["title"][$numChapter] . "<br>";
            //echo $story["chapters"]["content"][$numChapter];
            $numChapter++;
        }
        else
        {
            break;
        }
    }
}
else
{
    array_push($story["chapters"]["content"], getChapter($story_url));
    //echo $story["chapters"]["content"][$numChapter];
}

// ========== CREATE EPUB ========== //
$content_start =
"<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
. "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
. "    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
. "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
. "<head>"
. "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n"
. "<link rel=\"stylesheet\" type=\"text/css\" href=\"styles.css\" />\n"
. "<title>" . $story["title"] . "</title>\n"
. "</head>\n"
. "<body>\n";

$bookEnd = "</body>\n</html>\n";

if ($_POST["format"] == "epub")
{
	chdir("epub");
	include_once("EPub.php");
    // setting timezone for time functions used for logging to work properly
    date_default_timezone_set('UTC');

    $book = new EPub();

    // Title and Identifier are mandatory!
    $book->setTitle($story["title"]);
    $book->setIdentifier($story_url, EPub::IDENTIFIER_URI); // Could also be the ISBN number, prefered for published books, or a UUID.
    $book->setLanguage("en"); // Not needed, but included for the example, Language is mandatory, but EPub defaults to "en". Use RFC3066 Language codes, such as "en", "da", "fr" etc.
    $book->setDescription($story["desc"]);
    $book->setAuthor($story["author"], "");
    $book->setPublisher("FanFiction.net", "https://www.fanfiction.net/"); // I hope this is a non existant address :)
    $book->setDate(time()); // Strictly not needed as the book date defaults to time().
    //$book->setRights("Copyright and licence information specific for the book."); // As this is generated, this _could_ contain the name or licence information of the user who purchased the book, if needed. If this is used that way, the identifier must also be made unique for the book.
    $book->setSourceURL($story_url);

    // A book need styling, in this case we use static text, but it could have been a file.
    $cssData = "body {\n  margin-left: .5em;\n  margin-right: .5em;\n  text-align: justify;\n}\n\np {\n  font-family: serif;\n  font-size: 10pt;\n  text-align: justify;\n  text-indent: 1em;\n  margin-top: 0px;\n  margin-bottom: 1ex;\n}\n\nh1, h2 {\n  font-family: sans-serif;\n  font-style: italic;\n  text-align: center;\n width: 100%;\n}\n\nh1 {\n    margin-bottom: 2px;\n}\n\nh2 {\n    margin-top: -2px;\n    margin-bottom: 2px;\n}\n";
    $book->addCSSFile("styles.css", "css1", $cssData);

    //$book->setCoverImage("Cover.jpg", getImagePath($story_id), "image/jpeg");

    // Add cover page
    $cover = $content_start . "<h1>{$story["title"]}</h1>\n<h2>by: {$story["author"]}</h2>\n" . $bookEnd;
    $book->addChapter($story["title"], "Cover.html", $cover);
    $book->buildTOC(NULL, "toc", "Table of Contents", TRUE, TRUE);

    for ($i = 0; $i < $numChapter; $i++)
    {
        $title = isset($story["chapters"]["title"][$i]) ? $story["chapters"]["title"][$i] : "";
        $content = isset($story["chapters"]["content"][$i]) ? $story["chapters"]["content"][$i] : "";
        if (!empty($content) && !empty($title))
        {
            $title = "Chapter " . ($i + 1) . ": " . $title;
            $filename = "Chapter" . ($i + 1) . ".html";
            $book->addChapter($title, $filename, $content_start . "<h2>$title</h2>" . $content . $bookEnd, true, EPub::EXTERNAL_REF_ADD);
        }
    }

    $book->finalize(); // Finalize the book, and build the archive.

    //deleteImage($story_id);

    // Send the book to the client. ".epub" will be appended if missing.
    $zipData = $book->sendBook($story["title"] . " - " . $story["author"]);

    // After this point your script should call exit. If anything is written to the output,
    // it'll be appended to the end of the book, causing the epub file to become corrupt.
}
else if ($_POST["format"] == "pdf")
{
	$book_html = "";

	$book_html .= "<style>@page chapter {
	  size: A4 portrait;
	  margin: 2cm;
	}

	.chapterPage {
	   page: chapter;
	   page-break-after: always;
	}</style>";

	$cover = $content_start . "<div class='chapterPage'><h1>{$story["title"]}</h1>\n<h2>by: {$story["author"]}</h2></div>\n";
	$book_html .= $cover;

    for ($i = 0; $i < $numChapter; $i++)
    {    	
        $title = isset($story["chapters"]["title"][$i]) ? $story["chapters"]["title"][$i] : "";
        $content = isset($story["chapters"]["content"][$i]) ? $story["chapters"]["content"][$i] : "";        
        if (!empty($content) && !empty($title))
        {
            $title = "Chapter " . ($i + 1) . ": " . $title;
            $html = "<div class='chapterPage'><h2>$title</h2>" . $content . "</div>";
            $book_html .= $html;
        }
    }

    $book_html .= $bookEnd;

	chdir("pdf");
	require_once("dompdf_config.inc.php");
	$dompdf = new DOMPDF();
	$dompdf->load_html($book_html);
	$dompdf->render();

	$dompdf->stream($story["title"] . " - " . $story["author"] . ".pdf");
}
else if ($_POST["format"] == "mobi")
{
    include_once("mobi/MOBI.php");
    $book_html = "";

    $cover = $content_start . "<div class='chapterPage'><h1>{$story["title"]}</h1>\n<h2>by: {$story["author"]}</h2></div>\n";
    $book_html .= $cover;    

    $book_html .= $bookEnd;

    //Create the MOBI object
    $mobi = new MOBI();
    $mobiContent = new MOBIFile();

	$mobiContent->set("title", $story["title"]);
	$mobiContent->set("author", $story["author"]);



	for ($i = 0; $i < $numChapter; $i++)
    {       
        $title = isset($story["chapters"]["title"][$i]) ? $story["chapters"]["title"][$i] : "";
        $content = isset($story["chapters"]["content"][$i]) ? $story["chapters"]["content"][$i] : "";        
        if (!empty($content) && !empty($title))
        {
            $title = "Chapter " . ($i + 1) . ": " . $title;
            $mobiContent->appendChapterTitle($title);
            $document = new DOMDocument();
			$document->loadHTML($content);

			$texts = array ();
			$elementList = $document->getElementsByTagName("p");
			foreach($elementList as $element)
			{
				$mobiContent->appendParagraph($element->textContent);
			}			
	    	$mobiContent->appendPageBreak();
        }
    }
    
    $mobi->setContentProvider($mobiContent);
    //Send the mobi file as download
    $mobi->download($story["title"] . " - " . $story["author"] .".mobi");
}

exit(0);

function saveImage($story_id, $data)
{
    $fp = fopen("temp/$story_id.jpg","w");
    fwrite($fp, $data);
    fclose($fp);
}

function deleteImage($story_id)
{
    return unlink("temp/$story_id.jpg");
}

function getImagePath($story_id)
{
    return file_get_contents("temp/$story_id.jpg");
}

function startsWith($haystack, $needle)
{
    return $needle === "" || strpos($haystack, $needle) === 0;
}

function getChapter($url)
{
    return extract_id(cURL($url), "storytext");
}

function getStoryID($url)
{
    $out = $url;
    $startsAt = strpos($out, "/s/") + strlen("/s/");
    $endsAt = strpos($out, "/", $startsAt);
    $result = substr($out, $startsAt, $endsAt - $startsAt);
    return $result;
}

function get_string_between($string, $start, $end){
    $string = " ".$string;
    $ini = strpos($string,$start);
    if ($ini == 0) return "";
    $ini += strlen($start);
    $len = strpos($string,$end,$ini) - $ini;
    return substr($string,$ini,$len);
}

function verify($string)
{
    if (empty($string))
    {
        echo "error";
        exit(0);
    }
    else
    {
        return trim($string);
    }
}

function cURL($url)
{
    global $story_url;
    $curl_handle=curl_init();
    curl_setopt($curl_handle,CURLOPT_URL,$url);
    curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,30);
    curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,30);
    curl_setopt($curl_handle,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0');
    curl_setopt($curl_handle, CURLOPT_REFERER, $story_url);
    $buffer = curl_exec($curl_handle);
    curl_close($curl_handle);
    return $buffer;
}

/**	 
 * Extract an element by ID from an HTML document
 * Thanks http://codjng.blogspot.com/2009/10/unicode-problem-when-using-domdocument.html
 *
 * @param string $content A website
 *
 * @return string HTML content
 */

function extract_id( $content, $id ) {
	// use mb_string if available
	if ( function_exists( 'mb_convert_encoding' ) )
		$content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
	$dom= new DOMDocument();
	$dom->loadHTML( $content );
	$dom->preserveWhiteSpace = false;
    $element = $dom->getElementById( $id );
	$innerHTML = innerHTML( $element );
	return( $innerHTML ); 
}

/**	 
 * Helper, returns the innerHTML of an element
 *
 * @param object DOMElement
 *
 * @return string one element's HTML content
 */

function innerHTML( $contentdiv ) {
	$r = '';
	$elements = $contentdiv->childNodes;
	foreach( $elements as $element ) { 
		if ( $element->nodeType == XML_TEXT_NODE ) {
			$text = $element->nodeValue;
			// IIRC the next line was for working around a
			// WordPress bug
			//$text = str_replace( '<', '&lt;', $text );
			$r .= $text;
		}	 
		// FIXME we should return comments as well
		elseif ( $element->nodeType == XML_COMMENT_NODE ) {
			$r .= '';
		}	 
		else {
			$r .= '<';
			$r .= $element->nodeName;
			if ( $element->hasAttributes() ) { 
				$attributes = $element->attributes;
				foreach ( $attributes as $attribute )
					$r .= " {$attribute->nodeName}='{$attribute->nodeValue}'" ;
			}	 
			$r .= '>';
			$r .= innerHTML( $element );
			$r .= "</{$element->nodeName}>";
		}	 
	}	 
	return $r;
}
?>