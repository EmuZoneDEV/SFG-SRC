<?php

$reflist = ["http://gregplay.hu", "http://localhost", "http://gregsfam.ml", "http://sfgush.gq"];

$can = false;

if (isset($_SERVER["HTTP_REFERER"]))
{
	foreach($reflist as $referer)
	{
		if (substr($_SERVER["HTTP_REFERER"], 0, strlen($referer)) == $referer)
			$can = true;
	}
}

$can = true;

if (!$can)
	exit();

$src = $_GET["source"];
$src = explode("/", $src);
$filename = $src[count($src) - 1];
unset($src[0]);
$src = implode("/", $src);

$ext = explode(".", $src);
$ext = $ext[count($ext) - 1];

if ($ext == "mp3")
{
	header('Content-Disposition: inline;filename="' . $filename . '"');
	header('Content-Type: audio/mpeg');
}
else
	header("Content-type: image/" . $ext);

header('Expires: 0');

if (file_exists("save/" . $src))
	$data = readfile("save/" . $src);
else
{
	$data = "";
	
	try{
		$data = @file_get_contents("https://playagames.akamaized.net/res/sfgame_new/" . $src);
	}catch (Exception $e) {
		$data = @file_get_contents("http://playagames.akamaized.net/res/sfgame_new/" . $src);
	}
	if ($data == "")
		exit("");
	
	$folderarr = explode("/", $src);
	unset($folderarr[count($folderarr) - 1]);
	$str = "";
	foreach($folderarr as $folder)
	{
		if (!file_exists("save/" . ($str == "" ? "" : $str . "/") . $folder))
			mkdir("save/" . ($str == "" ? "" : $str . "/") . $folder);
		
		$str .= $str == "" ? $folder : "/" . $folder;
	}
	
	file_put_contents("save/" . $src, $data);
}

header('Content-Length: ' . strlen($data));

echo $data;

exit();

?>