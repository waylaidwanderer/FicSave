<?php
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