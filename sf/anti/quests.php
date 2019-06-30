<?php

// Quests anti-cheat by Greg

if($acc->data["questTimes"] == "")
	$qT = [];
else
	$qT = json_decode($acc->data["questTimes"], true);

foreach($qT as $key => $val)
{
	if($val < ($GLOBALS["CURRTIME"] - 10) && $acc->data["warned"] == 0)
unset($qT[$key]);
}

$qT[] = $GLOBALS["CURRTIME"];

$qryArgs = [];

if(count($qT) >= 18)
	$qryArgs[] = "warned = 1";

$qT = json_encode($qT);

$qryArgs[] = "questTimes = '$qT'";

// Http bot anti-cheat
$ssid = md5(microtime() . $acc->data["ID"]);

$qryArgs[] = "ssid = '$ssid'";
$qryArgs[] = "lastssid = '" . $acc->data["ssid"] . "'";

// Update values
$db->exec("UPDATE players SET " . implode(", ", $qryArgs) . " WHERE ID = " . $acc->data["ID"]);

$ret[] = "sessionid:".$ssid;