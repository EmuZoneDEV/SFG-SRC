<?php

$reflist = ["http://gregplay.hu", "http://localhost", "http://gregsfam.ml", "http://sfgush.gq"];

$can = false;

foreach($reflist as $referer)
{
	if (isset($_SERVER["HTTP_REFERER"]))
	{
		if (substr($_SERVER["HTTP_REFERER"], 0, strlen($referer)) == $referer)
			$can = true;
	}
	else
		$can = true;
}

if (!$can)
	exit();

$src = $_GET["source"];

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
	try{
		$data = @file_get_contents("http://img.playa-games.com/papaya/" . $src);
	}catch (Exception $e) {
		exit("???");
	}
	if ($data == "")
		exit("???");
	
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