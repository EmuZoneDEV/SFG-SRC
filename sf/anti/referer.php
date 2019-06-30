<?php

// Referer anti-cheat by Greg

if(!$mobile)
{
	$cw = explode("/", $clientWeb)[0];
	
	$referer = $_SERVER["HTTP_REFERER"] ?? "";
	$referer = explode("//", $referer);

	if (count($referer) < 2)
	{
		exit("Error:referer not found");
	}

	if (explode("/", $referer[1])[0] != $cw && explode("/", $referer[1])[0] != ("www." . $cw))
		exit("Error:referer invalid");
}