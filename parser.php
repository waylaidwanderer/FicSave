<?php
ini_set('log_errors', true);
ini_set('error_log', "../error.log");

if (!isset($_POST["story_url"]) || empty($_POST["story_url"]) || !isset($_POST["format"]) || empty($_POST["format"]) || strpos($_POST["story_url"], 'fanfiction.net') === FALSE)
{
    header("Location: http://ficsave.com");
    exit(0);
}

$format = $_POST["format"];
$uniqid = uniqid();
session_start();
$_SESSION["uniqid"] = $uniqid;
$_SESSION["format"] = $format;
$story_url = $_POST["story_url"]; //"https://www.fanfiction.net/s/10063574/";
$story_id = getStoryID($story_url);
$story_url = "https://www.fanfiction.net/s/" . $story_id . "/";

$output = array();
$status = -1;
while ($status != 0) {
    exec("php exec.php $uniqid $story_url $format > /dev/null &", &$output, &$status);
    if ($status == 0) {
		echo "done";
        break;
    } else {
        sleep(1);
    }
}
exit(0);

function getStoryID($url)
{
    $out = $url;
    $startsAt = strpos($out, "/s/") + strlen("/s/");
    $endsAt = strpos($out, "/", $startsAt);
    $result = substr($out, $startsAt, $endsAt - $startsAt);
    return $result;
}
?>