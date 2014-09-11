<?php
ini_set('log_errors', true);
ini_set('error_log', "../error.log");

if (!isset($_POST["story_url"]) || empty($_POST["story_url"]) || !isset($_POST["format"]) || empty($_POST["format"]) || strpos($_POST["story_url"], 'fanfiction.net') === FALSE)
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
$story_id = getStoryID($story_url);
$story_url = "https://www.fanfiction.net/s/" . $story_id . "/";


$output = array();
$status = -1;
$retry = 0;
while ($status != 0) {
    if ($retry == 5)
    {
        echo "error";
        break;
    }
    exec("php exec.php ".escapeshellarg($uniqid)." ".escapeshellarg($story_url)." ".escapeshellarg($format)." ".escapeshellarg($email)." > /dev/null &", &$output, &$status);
    if ($status == 0) {
		echo "done";
        break;
    } else {
        $retry++;
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