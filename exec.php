<?php
set_time_limit(0);
ini_set('log_errors', true);
ini_set('error_log', "../error.log");
libxml_use_internal_errors(true);

if (isset($argv)) {
	$uniqid = $argv[1];
    $story_url = $argv[2];
    $format = $argv[3];
    $email = isset($argv[4]) ? $argv[4] : "";

    $debug = false;
    
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
	    $story["title"] = stripAccents(verify($title->nodeValue));
	    //echo "title: " . $story["title"] . "\r\n";
	}
	foreach ($story_author as $author)
	{
	    if (!empty($author->nodeValue))
	    {
	        $story["author"] = verify($author->nodeValue);
	        //echo "author: " . $story["author"] . "\r\n";
	    }
	}
	foreach ($story_desc as $desc) {
	    $story["desc"] = verify($desc->nodeValue);
	    //echo "description: " . $story["desc"] . "\r\n";
	}
	foreach ($story_image as $image) {
	    $story["image"] = verify($image->nodeValue);
	    //echo "image src: " . $story["image"] . "\r\n";
	}

	if (!isset($story["author"]) || !isset($story["title"]) || !isset($story["desc"]))
	{
	    exit(0);
	}

    $numChapter = 1;
	$hasChapters = $xpath->evaluate("boolean(//select[@id='chap_select'])");
	$story["chapters"] = array();
	$story["chapters"]["title"] = array();
	$story["chapters"]["content"] = array();
	if ($hasChapters)
	{
	    foreach ($story_chapters as $chapter) {
	        $new_url = $story_url . $numChapter . "/";
	        $title = verify($chapter->nodeValue);
	        if (startsWith($title, "$numChapter."))
	        {
	            $title = str_replace($numChapter . ". ", "", $title);
	            array_push($story["chapters"]["title"], $title);
	            array_push($story["chapters"]["content"], getChapter($new_url, $debug));
	        }
	        else
	        {
	            break;
	        }
	        $numChapter++;
	    }
	}
	else
	{
	    array_push($story["chapters"]["title"], $story["title"]);
	    array_push($story["chapters"]["content"], getChapter($story_url, $debug));
	}

	// ========== CREATE EBOOK ========== //
	$content_start =
	"<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
	. "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
	. "    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
	. "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
	. "<head>"
	. "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />\n"
	. "<link rel=\"stylesheet\" type=\"text/css\" href=\"styles.css\" />\n"
	. "<title>" . $story["title"] . "</title>\n"
	. "</head>\n"
	. "<body>\n";

	$bookEnd = "</body>\n</html>\n";

	if ($format == "epub" || $format == "pdf")
	{
	    include_once("epub/EPub.php");	    
	    // setting timezone for time functions used for logging to work properly
	    date_default_timezone_set('UTC');

	    $book = new EPub();

	    // Title and Identifier are mandatory!
	    $book->setTitle($story["title"]);
	    $book->setIdentifier($story_url, EPub::IDENTIFIER_URI); // Could also be the ISBN number, prefered for published books, or a UUID.
	    $book->setDescription($story["desc"]);
	    $book->setAuthor($story["author"], "");
	    $book->setPublisher("FanFiction.net", "https://www.fanfiction.net/");
	    $book->setSourceURL($story_url);

	    // A book need styling, in this case we use static text, but it could have been a file.
	    $cssData = "body {\n  margin-left: .5em;\n  margin-right: .5em;\n  font-family: serif;\n  font-size: 12pt;\n}\n\np {\n text-indent: 1em;\n  margin-top: 0px;\n  margin-bottom: 1ex;\n}\n\nh1, h2 {\n  font-family: sans-serif;\n  font-style: bold;\n  text-align: center;\n width: 100%;\n font-size: 14px;\n }\n\nh1 {\n font-size: 16px; \n}\n";
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
	    $filename = $uniqid . "_" . $story["title"] . " - " . $story["author"];
	    $book->saveBook($filename, "./tmp");
	    if ($format == "pdf")
	    {
	        $storyPath = "./tmp/" . $filename;
	        if (file_exists("$storyPath.epub"))
	        {
	            $output = shell_exec("xvfb-run -a ebook-convert \"$storyPath.epub\" \"./tmp/$uniqid.pdf\"");
	            if (strpos($output, "PDF output written to") !== FALSE)
	            {	                
	                rename("./tmp/$uniqid.pdf", "$storyPath.pdf");
	                if (file_exists("$storyPath.epub"))
	                    unlink("$storyPath.epub");
	            }
	            else
	            {
	                error_log($output);
	                exit(0);
	            }
	        }
	    }
	    if (!empty($email))
	    {
	    	mailAttachment($filename.".".$format, "./tmp/", $email, $uniqid);
	    }
	}
	else if ($format == "mobi")
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
	            $mobiContent->appendParagraph($content);  
	            $mobiContent->appendPageBreak();
	        }
	    }
	    
	    $mobi->setContentProvider($mobiContent);
	    $filename = $uniqid . "_" . $story["title"] . " - " . $story["author"] .".mobi";
	    $mobi->save("./tmp/" . $filename);
	    if (!empty($email))
	    {
	    	mailAttachment($filename, "./tmp/", $email, $uniqid);
	    }
	}
    exit(0);
}

function mailAttachment($filename, $path, $mailto, $uniqid) {
	$rename_explode = explode("{$uniqid}_", $filename);
	$rename = $rename_explode[1];
	copy($path.$filename, $path.$rename);
	$filename = $rename;
	$from_name = "FicSave";
	$from_mail = "delivery@ficsave.com";
	$replyto = "noreply@ficsave.com";
	$subject = $filename;
	$message = "Here's your ebook, courtesy of FicSave.com!\r\nFollow us on Twitter @FicSave and tell your friends about us!";
    $file = $path.$filename;
    $file_size = filesize($file);
    $handle = fopen($file, "r");
    $content = fread($handle, $file_size);
    fclose($handle);
    $content = chunk_split(base64_encode($content));
    $uid = md5(uniqid(time()));
    $header = "From: ".$from_name." <".$from_mail.">\r\n";
    $header .= "Reply-To: ".$replyto."\r\n";
    $header .= "MIME-Version: 1.0\r\n";
    $header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
    $header .= "This is a multi-part message in MIME format.\r\n";
    $header .= "--".$uid."\r\n";
    $header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
    $header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $header .= $message."\r\n\r\n";
    $header .= "--".$uid."\r\n";
    $header .= "Content-Type: application/octet-stream; name=\"".$filename."\"\r\n"; // use different content types here
    $header .= "Content-Transfer-Encoding: base64\r\n";
    $header .= "Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n";
    $header .= $content."\r\n\r\n";
    $header .= "--".$uid."--";
    if (mail($mailto, $subject, "", $header)) {
        return true;
    } else {
        return false;
    }
return $message;
}

function stripAccents($str) {
    $unwanted_array = array('Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                        'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                        'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                        'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                        'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'Ğ'=>'G', 'İ'=>'I', 'Ş'=>'S', 'ğ'=>'g',
                        'ı'=>'i', 'ş'=>'s', 'ü'=>'u', 'ă'=>'a', 'Ă'=>'A', 'ș'=>'s', 'Ș'=>'S', 'ț'=>'t', 'Ț'=>'T');
	return strtr($str, $unwanted_array);
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

function extract_id($content, $id, $debug) {
	// use mb_string if available
	if ( function_exists( 'mb_convert_encoding' ) )
		$content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
	$dom= new DOMDocument();
	$dom->loadHTML( $content );
	$dom->preserveWhiteSpace = false;
    $element = $dom->getElementById($id);
    $innerHTML = $dom->saveHTML($element);
    if ($debug) {
    	file_put_contents("../log.html", $innerHTML);
    }
	//$innerHTML = innerHTML( $element );
	return $innerHTML; 
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

function cURL($url)
{
	$buffer = "";
	while (empty($buffer)) {
		$curl_handle=curl_init();
	    curl_setopt($curl_handle,CURLOPT_URL,$url);
	    curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,30);
	    curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,30);
	    curl_setopt($curl_handle,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0');
	    curl_setopt($curl_handle, CURLOPT_REFERER, $url);
	    $buffer = curl_exec($curl_handle);
	    curl_close($curl_handle);
	    if (empty($buffer)) {
	    	sleep(2);
	    }
	    else {
	    	break;
	    }
	}
    return $buffer;
}

function startsWith($haystack, $needle)
{
    return $needle === "" || strpos($haystack, $needle) === 0;
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
        return trim(utf8_decode($string));
    }
}
?>