<?php

// By Greg

$qry = $db->prepare("SELECT ID, sessWarnLvl FROM players WHERE lastssid = :ssid");

$qry->bindParam(':ssid', $ssid);
$qry->execute();

if($qry->rowCount() == 1)
{
	// Warn or add sesswarnlvl

	$fetch = $qry->fetchAll()[0];

	$qryArgs = [];

	$warnLvl = $fetch["sessWarnLvl"] + 1;

	$pid = $fetch["ID"];

	$db->exec("UPDATE players SET sessWarnLvl = $warnLvl WHERE ID = $pid");

	exit("Error:session hack");
}