<?php
class Story {
    var $url = '';
    var $id = '';
    var $title = '';
    var $author = '';
    var $description = '';
    var $coverImageUrl = '';
    var $chapters = 0;
    var $metadata;
}

abstract class Status {
    const PENDING = 0;
    const DOWNLOADING = 1;
    const DOWNLOAD_COMPLETE = 2;
    const BUILDING = 3;
    const DONE = 4;
    const SERVED = 5;
    const EMAILED = 6;
    const ERROR = -1;
}

class Chapter {
    var $number = 0;
    var $title = '';
    var $content = '';
}

class FicSaveException extends Exception { }

function mailAttachment($downloadId, $fileName, $path, $email) {
    $rename_explode = explode("{$downloadId}_", $fileName);
    $rename = $rename_explode[1];
    $fromName = "FicSave";
    $fromEmail = "delivery@ficsave.com";
    $replyEmail = "noreply@ficsave.com";
    $subject = "[FicSave] " . $rename;
    $message = "Here's your ebook, courtesy of FicSave.com!\r\nFollow us on Twitter @FicSave and tell your friends about us!";
    $file = $path.DIRECTORY_SEPARATOR.$fileName;
    $handle = fopen($file, "r");
    $content = fread($handle, filesize($file));
    fclose($handle);
    $content = chunk_split(base64_encode($content));
    $uid = md5(uniqid(time()));
    $header = "From: ".$fromName." <".$fromEmail.">\r\n";
    $header .= "Reply-To: ".$replyEmail."\r\n";
    $header .= "MIME-Version: 1.0\r\n";
    $header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
    $header .= "This is a multi-part message in MIME format.\r\n";
    $header .= "--".$uid."\r\n";
    $header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
    $header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $header .= $message."\r\n\r\n";
    $header .= "--".$uid."\r\n";
    $header .= "Content-Type: application/octet-stream; name=\"".$rename."\"\r\n"; // use different content types here
    $header .= "Content-Transfer-Encoding: base64\r\n";
    $header .= "Content-Disposition: attachment; filename=\"".$rename."\"\r\n\r\n";
    $header .= $content."\r\n\r\n";
    $header .= "--".$uid."--";
    if (mail($email, $subject, "", $header)) {
        return true;
    }
    return false;
}

function cURL($url, $referrer = '') {
    $curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, $url);
    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl_handle, CURLOPT_REFERER, $referrer);
    curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0');
    curl_setopt($curl_handle, CURLOPT_REFERER, $url);
    $response = curl_exec($curl_handle);
    curl_close($curl_handle);
    return $response;
}