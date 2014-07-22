<?php
if (php_sapi_name() != 'cli')
{
	header("Location: http://ficsave.com");
	exit(0);
}	
chdir("tmp");
$files = glob("*");
$time  = time();
foreach ($files as $file)
{
	if (is_file($file))
  		if ($time - filemtime($file) >= 60*60) // 1 hour
    		unlink($file);
}
?>