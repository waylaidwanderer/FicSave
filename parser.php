<?php
ini_set('log_errors', true);
ini_set('error_log', "../error.log");

if (!isset($_POST["story_url"]) || empty($_POST["story_url"]) || !isset($_POST["format"]) || empty($_POST["format"]))
{
    echo "error";
    exit(0);
}
if (isset($_POST["email"]) && !empty($_POST["email"]))
{
    $domain = substr(strrchr($_POST["email"], "@"), 1);
    $valid = array('kindle.com', 'free.kindle.com');
    if (!in_array($domain, $valid) || empty($domain))
    {
        echo "error";
        exit(0);
    }
}
$email = isset($_POST["email"]) ? $_POST["email"] : "";
$format = $_POST["format"];
$uniqid = uniqid();
session_start();
$_SESSION["uniqid"] = $uniqid;
$_SESSION["format"] = $format;
$story_url = $_POST["story_url"]; //"https://www.fanfiction.net/s/10063574/";
$story_url = validateStoryUrl($story_url);
if (empty($story_url)) {
    echo "error";    
} else {
    $output = array();
    $status = -1;
    $retry = 0;
    while ($status != 0) {
        if ($retry == 5)
        {
            echo "error";
            break;
        }
        exec("php exec.php ".escapeshellarg($uniqid)." ".escapeshellarg($story_url)." ".escapeshellarg($format)." ".escapeshellarg($email)." > /dev/null &", $output, $status);
        if ($status == 0) {
            echo "done";
            break;
        } else {
            $retry++;
            sleep(1);
        }
    }
}
exit(0);

function validateStoryUrl($url) {
    $parse = parse_url($url);
    $domain = $parse["host"];
    if (strpos($domain, "fanfiction.net") !== false)
    {
        $story_id = getFFNetStoryID($url);
        $url = "https://www.fanfiction.net/s/" . $story_id . "/";
        return $url;
    }
    else if (strpos($domain, "adult-fanfiction.org") !== false)
    {
        $url = explode("&", $url);
        $url = $url[0];
        return $url;
    }
    else if (strpos($domain, "fictionpress.com") !== false)
    {
        $story_id = getFFNetStoryID($url);
        $url = "https://www.fictionpress.com/s/" . $story_id . "/";
        return $url;
    } 

    return "";
}

function getFFNetStoryID($url)
{
    $out = $url;
    $startsAt = strpos($out, "/s/") + strlen("/s/");
    $endsAt = strpos($out, "/", $startsAt);
    $result = substr($out, $startsAt, $endsAt - $startsAt);
    return $result;
}
?>