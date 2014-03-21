<?php
ini_set('error_log', realpath(dirname("../..")) . "/error.log");
session_start();
$mode = isset($_POST["mode"]) ? $_POST["mode"] : "";
$url = isset($_POST["story_url"]) ? $_POST["story_url"] : "";
$format = isset($_POST["format"]) ? $_POST["format"] : "";

if ($mode == "fetch")
{
	$uniqid = uniqid();
	$_SESSION["uniqid"] = $uniqid;
	$output = array();
	$status = -1;
	while ($status != 0) {
		exec("php parser_test.php $url $format $uniqid > /dev/null &", &$output, &$status);
		if ($status == 0) {
			break;
		} else {
			sleep(1);
		}
	}
	
	echo "done";
}
else
{
	if (isset($_SESSION["uniqid"]))
	{
		$files = scandir('output/');
		$found = false;
		foreach($files as $file) {
			if (strpos($file, $_SESSION["uniqid"]) !== FALSE)
			{
				$found = true;
				session_unset();
				$servefile = "output/" . $file;
				$filename = explode("_", $file);
				$filename = $filename[1];
				header('Content-Description: File Transfer');
			    header('Content-Type: application/octet-stream');
			    header('Content-Disposition: attachment; filename="'.$filename.'"');
			    header('Expires: 0');
			    header('Cache-Control: must-revalidate');
			    header('Pragma: public');
			    header('Content-Length: ' . filesize($servefile));
			    ob_clean();
			    flush();
			    readfile($servefile);			    
			    exit;
			}
		}
		if (!$found)
		{
			echo '<html><head><meta http-equiv="Content-type" content="text/html;charset=UTF-8"></head><body>';
			echo "<div id=\"waiting\">waiting</div></body></html>";
		}
	}
}
exit(0);
?>