<?php
ini_set('log_errors', true);
session_start();
$get = isset($_GET["get"]);
$format = isset($_SESSION["format"]) ? $_SESSION["format"] : "";
$format = isset($_GET["format"]) ? $_GET["format"] : $format;
$uniqid = isset($_SESSION["uniqid"]) ? $_SESSION["uniqid"] : "";
$uniqid = isset($_GET["uniqid"]) ? $_GET["uniqid"] : $uniqid;
if (empty($format) || empty($uniqid))
{
	echo "error: ";
	if (empty($format)) {
		echo "missing format. ";
	}
	if (empty($uniqid)) {
		echo "missing unique ID. ";
	}
	exit(0);
}
chdir("tmp");
ini_set('error_log', "../../error.log");
$files = glob("{$uniqid}_*.{$format}");
if (isset($files[0]) && !empty($files[0]))
{
	if ($get)
	{		
		$file = $files[0];
		$rename_explode = explode("{$uniqid}_", $file);
		$rename = $rename_explode[1];
		if (file_exists($file))
		{
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.basename($rename).'"');
			header("Cache-Control: no-cache, must-revalidate");
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header('Pragma: public');
			header('Content-Length: ' . filesize($file));			
			ob_clean();
			flush();
			readfile($file);
		}
	}
	else
	{
		echo "done|$format|$uniqid";
	}
}
else
{
	echo "waiting";
}

exit(0);
?>