<?php

header("Content-Type: application/json");

include "settings.php";

$template = file_get_contents("sfgame_cfg/template.json");

$template = strtr($template,
	[
		'$domain' => $clientWeb,
		'$imgserver' => $imgServer
	]
);

echo $template;