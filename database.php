<?php
include_once("config.php");

function initDB()
{
	global $_CONFIG;
	$mysqli = new mysqli($_CONFIG["host"], $_CONFIG["username"], $_CONFIG["password"], $_CONFIG["database"]);
    if ($mysqli->connect_errno) {
        $error_msg = "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
        error_log($error_msg);
        $mysqli->close();
        return NULL;
    }
    return $mysqli;
}

function getNumCompletedChapters($mysqli, $uniqid)
{
	global $_CONFIG;
	$get = $mysqli->query("SELECT * FROM {$_CONFIG["table"]} WHERE id = \"$uniqid\";");
	if (!$get)
    {            
        $error_msg = "Failed to get number of chapters. (" . $mysqli->connect_errno . ") " . $mysqli->error;
        error_log($error_msg);
        $mysqli->close();
        return -1;
    }
	return $get->num_rows;
}

function getChaptersFromDB($mysqli, $uniqid, &$chapterArray)
{
	global $_CONFIG;
	$get = $mysqli->query("SELECT * FROM {$_CONFIG["table"]} WHERE id = \"$uniqid\" ORDER BY chapter ASC;");
	if (!$get)
    {            
        $error_msg = "Failed to get chapters. (" . $mysqli->connect_errno . ") " . $mysqli->error;
        error_log($error_msg);
        $mysqli->close();        
    }
	while ($row = $get->fetch_array())
	{
		array_push($chapterArray, $row["content"]);
	}
	$clear = $mysqli->query("DELETE FROM {$_CONFIG["table"]} where id = \"$uniqid\";");
}
?>