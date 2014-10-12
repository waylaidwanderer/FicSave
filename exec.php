<?php
set_time_limit(0);
ini_set('log_errors', true);
ini_set('error_log', "../error.log");
date_default_timezone_set('UTC');
libxml_use_internal_errors(true);

if (isset($argv)) {
	$story = array();

	$uniqid = $argv[1];
	$story["uniqid"] = $uniqid;
    $story["story_url"] = $argv[2];
    $format = $argv[3];
    $email = isset($argv[4]) ? $argv[4] : "";

    $story["debug"] = false;
    $cookies = "";    

    $parse = parse_url($story["story_url"]);
	$domain = $parse["host"];
	if (strpos($domain, "fanfiction.net") !== false || strpos($domain, "fictionpress.com") !== false) {
		include_once("include/fanfiction_net.php");
	} else if (strpos($domain, "adult-fanfiction.org") !== false) {
		$host = explode('.', $parse['host']);
		$subdomain = $host[0];
	    $cookies = "HasVisited=bypass page next time; path=/; domain=$subdomain.adult-fanfiction.org";
		include_once("include/adultfanfiction_org.php");
	}
    
	$dom = new DOMDocument();
	$dom->loadHTML(cURL($story["story_url"], $story["debug"], $cookies, $uniqid));
	
	$xpath = new DOMXPath($dom);

	try {
		$story = getStoryAttributes($xpath, $story);
	} catch (WrongFormatException $ex) {
		error_log("" . $ex);
		echo "error";
		exit(0);
	}	

	$numChapter = $story["numChapter"];

	// ========== CREATE EBOOK ========== //
	$content_start =
	"<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
	. "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
	. "    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
	. "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
	. "<head>"
	. "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />\n"
	. "<title>" . $story["title"] . "</title>\n"
	. "<style>.coverPage { text-align: center; height: 100%; width: 100%; }</style>\n"
	. "</head>\n"
	. "<body>\n";

	$bookEnd = "</body>\n</html>\n";

	if ($format == "pdf")
	{
		require_once("pdf/tcpdf.php");
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor($story["author"]);
		$pdf->SetTitle($story["title"]);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		$pdf->setFontSubsetting(false);
		$pdf->SetFont('helvetica', '', 12);

		$filename = $uniqid . "_" . $story["title"] . " - " . $story["author"] . ".pdf";

		$pdf->AddPage();
		$cover = $content_start . "<div class='coverPage'><h1>{$story["title"]}</h1>\n<h2>by: {$story["author"]}</h2></div>" . $bookEnd;
		$pdf->writeHTMLCell(0, 0, '', '', $cover, 0, 1, 0, true, 'C', true);

		for ($i = 0; $i < $numChapter; $i++)
	    {
	        $title = isset($story["chapters"]["title"][$i]) ? $story["chapters"]["title"][$i] : "";
	        $content = isset($story["chapters"]["content"][$i]) ? $story["chapters"]["content"][$i] : "";	        
	        if (!empty($content) && !empty($title))
	        {
	            $title = formatTitle($title, $i);
				$pdf->AddPage();
				$pdf->Bookmark($title, 0, 0, '', 'B', array(0,0,0));
				$pdf->writeHTMLCell(0, 0, '', '', "<h2>$title</h2>", 0, 1, 0, true, 'C', true);
				$html = $content_start . $content . $bookEnd;
				$pdf->writeHTML($html, true, false, true, false, '');
	        }
	    }

		$pdf->addTOCPage();
		$pdf->SetFont('helvetica', 'B', 16);
		$pdf->MultiCell(0, 0, 'Table Of Contents', 0, 'C', 0, 1, '', '', true, 0);
		$pdf->Ln();
		$pdf->SetFont('helvetica', '', 12);
		$pdf->addTOC(2, 'helvetica', '.', 'TOC', 'B', array(0,0,0));
		$pdf->endTOCPage();
		$pdf->Output("./tmp/" . $filename, 'F');

		if (!empty($email))
	    {
	    	mailAttachment($filename, "./tmp/", $email, $uniqid);
	    }
	}
	else if ($format == "epub")
	{		
	    require_once("epub/EPub.php");
	    $book = new EPub();
	    $book->setTitle($story["title"]);
	    $book->setIdentifier($story["story_url"], EPub::IDENTIFIER_URI);
	    $book->setDescription($story["desc"]);
	    $book->setAuthor($story["author"], "");
	    $book->setPublisher("FanFiction.net", "https://www.fanfiction.net/");
	    $book->setSourceURL($story["story_url"]);
	    //$book->setCoverImage("Cover.jpg", getImagePath($story_id), "image/jpeg");

	    $cover = $content_start . "<div class='coverPage'><h1>{$story["title"]}</h1>\n<h2>by: {$story["author"]}</h2></div>" . $bookEnd;
	    $book->addChapter($story["title"], "Cover.html", $cover);
	    $book->buildTOC();

	    for ($i = 0; $i < $numChapter; $i++)
	    {
	        $title = isset($story["chapters"]["title"][$i]) ? $story["chapters"]["title"][$i] : "";
	        $content = isset($story["chapters"]["content"][$i]) ? $story["chapters"]["content"][$i] : "";
	        if (!empty($content) && !empty($title))
	        {
	            $title = formatTitle($title, $i);
	            $filename = "Chapter" . ($i + 1) . ".html";       
	            $book->addChapter($title, $filename, $content_start . "<h2>$title</h2>" . $content . $bookEnd, true, EPub::EXTERNAL_REF_ADD);
	        }
	    }

	    $book->finalize();
	    $filename = $uniqid . "_" . $story["title"] . " - " . $story["author"] . ".epub";
	    $book->saveBook($filename, "./tmp");

	    if (!empty($email))
	    {
	    	mailAttachment($filename, "./tmp/", $email, $uniqid);
	    }
	}
	else if ($format == "mobi")
	{
	    include_once("mobi/MOBI.php");
	    $mobi = new MOBI();
	    $mobiContent = new MOBIFile();
	    $mobiContent->set("title", $story["title"]);
	    $mobiContent->set("author", $story["author"]);
	    $mobiContent->set("toc", true);

	    $mobiContent->appendChapterTitle($story["title"]);
        $mobiContent->appendParagraph("by: " . $story["author"]);  
        $mobiContent->appendPageBreak();

	    for ($i = 0; $i < $numChapter; $i++)
	    {       
	        $title = isset($story["chapters"]["title"][$i]) ? $story["chapters"]["title"][$i] : "";
	        $content = isset($story["chapters"]["content"][$i]) ? $story["chapters"]["content"][$i] : "";
	        if (!empty($content) && !empty($title))
	        {
	            $title = formatTitle($title, $i);
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
	else if ($format == "txt")
	{
		include_once("include/html2text.php");

		$output = $story["title"] . "\r\n\r\nby " . $story["author"] . "\r\n\r\n\r\n";
		for ($i = 0; $i < $numChapter; $i++)
		{
			$title = isset($story["chapters"]["title"][$i]) ? $story["chapters"]["title"][$i] : "";
	        $content = isset($story["chapters"]["content"][$i]) ? $story["chapters"]["content"][$i] : "";
	        if (!empty($content) && !empty($title))
	        {
	            $title = formatTitle($title, $i);
	            $content = convert_html_to_text($content);
	            $output .= $title . "\r\n\r\n" . $content . "\r\n\r\n";
	        }
		}
		$filename = $uniqid . "_" . $story["title"] . " - " . $story["author"] .".txt";
		file_put_contents("./tmp/" . $filename, $output);

		if (!empty($email))
	    {
	    	mailAttachment($filename, "./tmp/", $email, $uniqid);
	    }
	}
    exit(0);
}
else
{
	exit(0);
}

function formatTitle($title, $chapterNum)
{
	$chapterTitle = "Chapter " . ($chapterNum + 1);
    $titleReplace = trim(str_replace($chapterTitle, "", $title));
    $replace = false;
    $replaceChar = '';
    if (!empty($titleReplace)) {
    	if ($titleReplace[0] == ':') {
	    	$replace = true;
	    	$replaceChar = ':';
	    } else if ($titleReplace[0] == '-') {
	    	$replace = true;
	    	$replaceChar = '-';
	    }
	    if ($replace) {
	    	$titleReplace = trim(mb_substr($titleReplace, 1));
	    }
    }    
    $title = $chapterTitle == $title ? $title : (empty($titleReplace) ? $chapterTitle : $chapterTitle . ": " . $titleReplace);
    return $title;
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

function cURL($url, $debug = false, $cookies = "", $uniqid = "")
{
	$cookie_jar = empty($cookies) ? "" : tempnam("/tmp", $uniqid."_cookie");

	$buffer = "";
	while (empty($buffer)) {
		$curl_handle=curl_init();
	    curl_setopt($curl_handle,CURLOPT_URL,$url);
	    curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,30);
	    curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,30);
	    curl_setopt($curl_handle, CURLOPT_COOKIEFILE, $cookie_jar);
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
	if ($debug) {
    	file_put_contents("../log.html", $buffer);
    }
    return $buffer;
}

function startsWith($haystack, $needle)
{
    return $needle === "" || strpos($haystack, $needle) === 0;
}

class WrongFormatException extends Exception
{
    // Redefine the exception so message isn't optional
    public function __construct($message, $code = 0, Exception $previous = null) {
        // some code
    
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
?>