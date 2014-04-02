<?php
ini_set('error_log', "../error.log");
include_once("config.php");
libxml_use_internal_errors(true);

if (isset($argv)) {
	$uniqid = $argv[1];
    $chapterNum = $argv[2];
    $url = $argv[3];

    $mysqli = initDB($_CONFIG);
    $debug = false;
    $getChapter = getChapter($url, $debug);

    storeChapter($_CONFIG, $mysqli, $uniqid, $chapterNum, $getChapter);
    exit(0);
}

function initDB($_CONFIG)
{
	$mysqli = new mysqli($_CONFIG["host"], $_CONFIG["username"], $_CONFIG["password"], $_CONFIG["database"]);
    if ($mysqli->connect_errno) {
        $error_msg = "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
        error_log($error_msg);
        $mysqli->close();
        return NULL;
    }
    return $mysqli;
}

function storeChapter($_CONFIG, $mysqli, $id, $chapterNum, $content)
{
	$content = $mysqli->real_escape_string($content);
	// First, replace UTF-8 characters.
	$content = str_replace(
	 array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
	 array("'", "'", '"', '"', '-', '--', '...'),
	 $content);
	// Next, replace their Windows-1252 equivalents.
	 $content = str_replace(
	 array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
	 array("'", "'", '"', '"', '-', '--', '...'),
	 $content);
	$store = $mysqli->query("INSERT INTO {$_CONFIG["table"]} (id, chapter, content) VALUES (\"$id\", $chapterNum, \"$content\");");
	if (!$store)
    {            
        $error_msg = "Failed to select insert story chapter. (" . $mysqli->connect_errno . ") " . $mysqli->error;
        error_log($error_msg);
        $mysqli->close();
    }
}

function getChapter($url, $debug)
{
    return extract_id(cURL($url), "storytext", $debug);
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
?>