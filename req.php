<?php

// Settings file
include 'settings.php';

// System from here
$errlvl = $sandbox ? E_ALL ^ E_DEPRECATED : 0;
error_reporting($errlvl);

$mobile = true;

if(!isset($_GET["mobile"]) || $_GET["mobile"] != "laj44o7hg8")
	$mobile = false;

// Referer check anti-cheat
include "sf/anti/referer.php";


// Functions
include 'sf/misc.php';
include 'sf/chat.php';
include 'sf/album.php';
include 'sf/entity.php';
include 'sf/fortress.php';
include 'sf/player.php';
include 'sf/achievements.php';
include 'sf/account.php';
include 'sf/item.php';
include 'sf/simulate.php';
include 'sf/guild.php';
include 'sf/pets.php';
include 'sf/blacksmith.php';
include 'sf/password.php';
include 'sf/underworld.php';

date_default_timezone_set($timezone);

$ip = $_SERVER['REMOTE_ADDR'];

if(!isset($_GET['req']))
	exit("Error:wrong request");

if(!isset($_SERVER['HTTP_USER_AGENT']))
	exit("Error:no user agent");

$req = substr( $_GET['req'], 16);
//$req = base64_decode(str_pad(strtr($req, '-_', '+/'), strlen($req) % 4, '=', STR_PAD_RIGHT));
$req = str_pad(strtr($req, '-_', '+/'), strlen($req) % 4, '=', STR_PAD_RIGHT);

$key = '[_/$VV&*Qg&)r?~g';
$iv = 'jXT#/vz]3]5X7Jl\\';
$keyId = substr( $_GET['req'], 0, 16);
if($keyId == "0-0K36aS2567C735")
	$key = "5O4ddy4KZLs41n6W";
else if($keyId != "0-00000000000000")
	exit("Error:cryptoid not found&cryptoid:0-0K36aS2567C735&cryptokey:5O4ddy4KZLs41n6W");

//$req = mcrypt_decrypt (MCRYPT_RIJNDAEL_128, $key, $req, MCRYPT_MODE_CBC, $iv);
$req = openssl_decrypt($req, "AES-128-CBC", $key, OPENSSL_ZERO_PADDING, $iv);

$req = rtrim ( $req, "\0");

// EXPERIMENTAL injection-fix by Jessi
$zeichen = array();
$zeichen[] = "--";
$zeichen[] = "*";
$zeichen[] = "+";
$zeichen[] = '"';
$zeichen[] = "'";
$zeichen[] = '\'';
$rq = str_replace($zeichen,'',$req);

// ctracker - Jessi & Greg
$checkreq = explode(":", $req, 2);
$checkreq[0] = explode("|", $checkreq[0])[0];
$checkreq = implode(":", $checkreq);

$cracktrack = strtolower($checkreq . $_GET["rnd"] . $_GET["c"]);
$checkworm = $cracktrack;
$wormprotector = [
	'drop', 'select from', 'delete', 'update'
];

foreach($wormprotector as $worm)
{
	$checkworm = strtr($checkworm, [$worm => "*"]);
	$checkworm = strtr($checkworm, [urldecode($worm) => "*"]);
	
	if($cracktrack != $checkworm){
		  $cremotead = $_SERVER['REMOTE_ADDR'];
		  $cremotead .= ":" . $_SERVER['HTTP_USER_AGENT'];
		  $cremotead .= ":" . $cracktrack;
		  file_put_contents("att.dat", (file_get_contents("att.dat") . PHP_EOL . $cremotead));
		  exit("Error:bad word " . urldecode($worm));
	}
}
// eof

// Verify count and random by Greg

$_GET["c"] = intval($_GET["c"]);

if(!is_numeric(explode(".", $_GET["rnd"])[1]))
	exit();

$_GET["rnd"] = "0." . explode(".", $_GET["rnd"])[1];


$db->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_TO_STRING);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("SET sql_mode=''");

if($sandbox)
	$orireq = $req;

$req = explode("|", $req);

if (!isset($req[1]))
	$req = ["00000000000000000000000000000000", "accountcheck:Greg"];

$ssid = $req[0];
$req = explode(":", $req[1]);

$act = $req[0];
$args = explode("/", $req[1]);

$badwords = [
	".7m.pl",
	".5v.pl",
	".blaced.",
	"DELETE",
	"DROP",
];
foreach($badwords as $bw)
{
	if(preg_match(("/" . $bw . "/i"), ($_SERVER["QUERY_STRING"] . $req[1])))
		exit("Error:not allowed word $bw");
}

if(strlen($ssid) != 32)
	exit("Error:invalid ssid length");

if($ssid != "00000000000000000000000000000000"){
	// Session modification check anti-cheat
	include "sf/anti/lastsess.php";
	
	if($act == "poll"){
		$qry = $db->prepare("SELECT players.ID, players.highest, players.logs, players.perm, players.whisper, players.perm, players.banned, players.captchaFlag, players.lastCaptcha, players.poll, players.guild, fortress.ut1, fortress.ut2, fortress.ut3, fortress.uttime1, fortress.uttime2, fortress.uttime3 FROM players LEFT JOIN fortress ON players.ID = fortress.owner WHERE ssid = :ssid");
	}else{
		$qry = $db->prepare("SELECT ID, highest, logs, poll, perm, guild, banned, captchaFlag, lastCaptcha, perm FROM players WHERE ssid = :ssid");
	}
	$qry->bindParam(':ssid', $ssid);
	$qry->execute();

	if($qry->rowCount() == 0)
		exit("Error:sessionid invalid");
	
	$playerData = $qry->fetch(PDO::FETCH_ASSOC);
	$playerID = $playerData['ID'];
	$playerPoll = $playerData['poll'];
	$playerGuild = $playerData['guild'];
	$playerPerm = $playerData['perm'];
	
	if($playerData['banned'] != 0) {
		exit('Error:admin lock permanent');
	}
	
	// Captcha if captchaFlag
	if($playerData["captchaFlag"] != 0 && $CURRTIME >= $playerData["lastCaptcha"]){
		setcookie("cptinfo", ($ssid . "_" . $CURRTIME));
		
		exit("Error:captcha");
	}else
		setcookie("cptinfo", "no");
	
	// Anti-cheat against requesters
	if($act != 'poll' && $playerPerm < 10) {
		$rrr = intval($_GET["c"]);
		
		$logs = $playerData["logs"];
		
		if($logs == "")
			$rz = [];
		else
		{
			$rz = explode(",", $logs);
		}
		
		$antiact = 0;
		
		if($rrr == $playerData["highest"])
			$antiact = 2;
		else if (($playerData["highest"] - $rrr) > 5)
			$antiact = 1;
		else
		{
			foreach($rz as $num)
			{
				if ($rrr == $num)
					$antiact = 2;
			}
		}
		
		if ($antiact > 0)
		{
			$db->exec("UPDATE players SET maycheat = maycheat + 1 WHERE ID = $playerID");
			
			if($antiact == 2)
				$db->exec("UPDATE players SET warned = 2 WHERE ID = $playerID");
			
			exit("Error:fake request");
		}
		
		$rz[] = $rrr;
		
		$rz = array_slice($rz, -10);
		
		$logs = implode(",", $rz);
		
		$sql = "UPDATE players SET logs = '$logs', highest = $rrr WHERE ID = '$playerID'";
		$db->exec($sql);
	}
}

$ret = [];

switch($act){
	case "getserverversion":
		// By Greg
		
		echo "serverversion:$serverver&Success:";
		
		break;
	case 'accountcreate':
	
		//"00000000000000000000000000000000|accountcreate:sp/pass/ddask@ldpwqe.com/2/8/3/3,302,4,6,5,6,1,2,3/0/sfgame_new_flash/pl
		//race/gender/class/face/
		
		// Check IP block
		if(Misc::isIpBlocked($ip))
			exit("Error:your ip is blocked");
		
		$name = $args[0];
		
		// Check for names - fix by Greg
		if(!Misc::isNameAllowed($name))
			exit('Error:name is not avaible');
		
		$pass = $args[1];
		$mail = $args[2];

		$gender = $args[3];
		$race = $args[4];
		$class = $args[5];

		$face = $args[6];

		
		$qry = $db->prepare("SELECT name FROM players WHERE name = :name");
		$qry->bindParam(':name', $name);
		$qry->execute();

		if($qry->fetch( PDO::FETCH_ASSOC ))
			exit("Error:character exists");
		
		// Password system v2
		$passgen = new Password($pass, false);
		$passgen->createKey($name);
		
		$pass = $passgen->encrypt();
		
		$startingGold *= 100;
		
		$ips = json_encode([$ip]);
		
		$qry = $db->prepare("INSERT INTO players(name, password, email, face, race, gender, class, silver, mush, ip)
			VALUES(:name, :pass, :mail, :face, :race, :gender, :class, $startingGold, $startingMush, :ip)");
		
		$qry->bindParam(':name', $name);
		$qry->bindParam(':pass', $pass);
		$qry->bindParam(':mail', $mail);
		$qry->bindParam(':face', $face);
		$qry->bindParam(':race', $race);
		$qry->bindParam(':gender', $gender);
		$qry->bindParam(':class', $class);
		$qry->bindParam(':ip', $ips);
		$qry->execute();

		$qry = $db->prepare("SELECT ID FROM players WHERE name = :name");
		$qry->bindParam(':name', $name);
		$qry->execute();

		$pid = $qry->fetch(PDO::FETCH_ASSOC)['ID'];
		
		//insert a nice welcoming message :P
		if(!$sandbox && $wmail_enable)
			$db->exec("INSERT INTO messages(sender, reciver, time, topic, message) VALUES(0, $pid, ".$GLOBALS["CURRTIME"].", '$wmail_subject', '$wmail_body')");

		//fortress
		$db->exec("INSERT INTO fortress(owner) VALUES($pid)");

		//copycats
		$db->exec("INSERT INTO copycats(owner, class, str, dex, intel, wit) VALUES($pid, 1, 1046, 358, 531, 1065);
					INSERT INTO copycats(owner, class, str, dex, intel, wit) VALUES($pid, 2, 358, 531, 1046, 799);
					INSERT INTO copycats(owner, class, str, dex, intel, wit) VALUES($pid, 3, 358, 1046, 531, 799);");


		//starting weapon
		if($class != 4){
			$weapon = Item::genItem(1000001, 1, $class);
			$weapon['value_silver'] = 1;
			$weapon['item_id'] = 1 + ($class - 1) * 1000;
			$db->exec('INSERT INTO items(owner, slot, type, item_id, dmg_min, dmg_max, a1, a2, a3, a4, a5, a6, value_silver, value_mush) VALUES('.$pid.', 18, '.join(', ', $weapon).')');
		}

		//album
		$db->exec('INSERT INTO items(owner, slot, type, item_id, value_silver) VALUES('.$pid.', 0, 13, 1, 1)');

		//shops
		for($i = 0; $i < 12; $i++){
			$type = $i < 6 ? rand(1, 7) : rand(8, 10);
			$item = Item::genItem($type, 1, $class);
			$slot = 20 + $i;
			$db->exec('INSERT INTO items(owner, slot, type, item_id, dmg_min, dmg_max, a1, a2, a3, a4, a5, a6, value_silver, value_mush) VALUES('.$pid.', '.$slot.', '.join(', ', $item).')');
		}

		$updateArgs = [];
		//quests
		for($i = 1; $i <= 3; $i++){
			$quest = Account::generateQuest(1);
			$updateArgs[] = "quest_exp$i = ".$quest['exp'];
			$updateArgs[] = "quest_silver$i = ".$quest['silver'];
			$updateArgs[] = "quest_dur$i = ".$quest['duration'];
		}
		
		// Dungs unlocked on default?
		if(!$defAllDungUnlocked)
		{
			// Set dungeons to locked
			
			for($i = 1; $i < 15; $i++){
				$updateArgs[] = "d$i = 0";
				$updateArgs[] = "dd$i = 0";
			}
		}

		$db->exec('UPDATE players SET '.join(', ', $updateArgs).' WHERE ID = '.$pid);
		
		// Default Underworld - Greg
		if($defUnderworld)
			Underworld::createUnderworld($pid);
		
		//resp
		exit("skipallow:1&timestamp:".$GLOBALS["CURRTIME"]."&playerid:$pid&tracking.s:signup&success:");

		break; 
	case 'accountcheck':
		//success if name avalible, error in case of login
		//if keyid default, give out another keyset
		
		// Check IP block
		if(Misc::isIpBlocked($ip))
			exit("Error:your ip is blocked");
		
		$keyId = "0-0K36aS2567C735";
		$key = "5O4ddy4KZLs41n6W";

		$name = $args[0];
		
		// Check for names - fix by Greg
		if(!Misc::isNameAllowed($name))
			exit('Error:name is not avaible');

		$qry = $db->prepare("SELECT name FROM players WHERE name = :name");
		$qry->bindParam(':name', $name);
		$qry->execute();

		//if character exists -> login
		if($qry->fetch( PDO::FETCH_ASSOC ))
			exit("Error:character exists&cryptoid:$keyId&cryptokey:$key");

		//if name is free
		exit("Success:&cryptoid:$keyId&cryptokey:$key");
		break;
	case 'accountlogin':
		
		// Check IP block
		if(Misc::isIpBlocked($ip))
			exit("Error:your ip is blocked");
		
		$qry = $db->prepare("SELECT players.*, fortress.*, guilds.portal AS guild_portal, guilds.instructor, guilds.treasure, guilds.dungeon AS raid FROM players LEFT JOIN fortress ON players.ID = fortress.owner LEFT JOIN guilds ON players.guild = guilds.ID WHERE players.name = :name");
		$qry->bindParam(':name', $args[0]);
		$qry->execute();

		$playerData = $qry->fetch ( PDO::FETCH_ASSOC );

		$playerID = $playerData["ID"];
		

		if($qry->rowCount() == 0)
			exit('Error:player not found');
		
		$H = explode('$S', $args[1]);
		
		if(count($H) != 2)
			exit("Error:wrong password sys");
		
		$pass = substr($H[1], 0, (strlen($args[2]) * -1));
		$pw = $H[0];
		
		// Password system v2 - get decrypted password
		$passgen = new Password($playerData['password'], true);
		$passgen->createKey($playerData["name"]);
		$userpass = $passgen->decrypt();
		
		// Password check by Greg (working only with Greg's SWF)
		if(sha1($pass) != sha1($userpass)) { // Normal password check
			if($pass == $playerData['randpw']) { // Auto login/Face login (without real password)
				// Logged in
			}else{
				exit("Error:wrong pass");
			}
		}
		
		// Check ban
		if($playerData['banned'] != 0) {
			exit('Error:admin lock permanent');
		}
		
		$now = Misc::getNow();
		
		$leftthirst = 0;
		
		if($playerData['newday'] != $now) {
			$leftthirst += round($playerData["thirst"] / 60);
			
			$newday = $now;
			$playerData['beers'] = 0;
			$playerData['thirst'] = 6000;
			$playerData['mush'] += 5000;
			$mush = $playerData['mush'];
			$db->exec("UPDATE players SET newday = '$newday', beers = '0', thirst = '6000', mush = '$mush' WHERE ID = '".$playerData['ID']."'");
		}

		//get items
		$items = $db->query("SELECT * FROM items WHERE owner = ".$playerData['ID']." ORDER BY slot ASC");
		$items = $items->fetchAll(PDO::FETCH_ASSOC);

		// gen ssid and randpw (Fix by Greg)
		// also whispers are empty cuz echoed it
		// reset logs (anti-cheat)
		$ssid = md5(microtime() . $playerID);
		$loco = rand(1, 999);
		$time = $GLOBALS["CURRTIME"];
		
		// New IP system
		if(strlen($playerData["ip"]) < 5)
			$ips = [];
		else
			$ips = json_decode($playerData["ip"], true);
		
		// Browser
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE)
			$ip2 = 'IE';
		else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== FALSE) //For Supporting IE 11
			$ip2 =  'IE';
		else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== FALSE)
			$ip2 = 'Firefox';
		else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== FALSE)
			$ip2 = 'Chrome';
		else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== FALSE)
			$ip2 = "OperaM";
		else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') !== FALSE)
			$ip2 = "Opera";
		else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== FALSE)
			$ip2 = "Safari";
		else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== FALSE)
			$ip2 = "Android";
		else
			$ip2 = "Cust-" . base64_encode($_SERVER['HTTP_USER_AGENT']);
		
		if(!in_array($ip, $ips))
			$ips[] = $ip;
		
		if (!in_array($ip2, $ips))
			$ips[] = $ip2;
		
		$ips = json_encode($ips);
		
		if ($mobile)
			$randpw = "";
		else
			$randpw = ", randpw = '$pw'";
		
		
		$db->exec("UPDATE players SET highest = '-1', logs = '', whisper = '', ip = '$ips', ssid = '$ssid'{$randpw}, poll = $time WHERE ID = '".$playerData['ID']."'");

		//get copycats
		$copycats = $db->query("SELECT * FROM copycats WHERE owner = ".$playerData['ID']." ORDER BY class ASC");
		$copycats = $copycats->fetchAll();

		//get messages
		$messages = $db->query('SELECT messages.ID, players.name, messages.hasRead, messages.topic, messages.time 
				FROM messages LEFT JOIN players ON messages.sender = players.ID WHERE reciver = '.$playerData['ID'].' ORDER BY time DESC');
		$messages = $messages->fetchAll(PDO::FETCH_ASSOC);

		//create account obj
		$acc = new Account($playerData, $items, $copycats, true);

		$acc->data['new_msg'] = $db->query('SELECT Count(ID) AS c FROM messages WHERE reciver = '.$playerData['ID'].' AND hasRead = false')->fetch(PDO::FETCH_ASSOC)['c'];
		
		$ret[] = "login count:".$loco;
		$ret[] = "sessionid:".$ssid;
		$ret[] = "inboxcapacity:1000";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "owndescription.s:".$playerData['description'];
		$ret[] = "ownplayername.r:".$acc->getName();
		$acc->data['allplayer'] = $db->query("SELECT Count(*) AS c FROM players WHERE honor > -1")->fetch(PDO::FETCH_ASSOC)['c'];
		$ret[] = "maxrank:".$acc->data['allplayer'];
		$ret[] = "skipallow:1";
		
		$uw = new Underworld($acc->data["ID"]);
		
		if($defUnderworld && !$uw->haveIt){
			Underworld::createUnderworld($playerID);
			
			$uw = new Underworld($acc->data["ID"]);
		}
		
		if($uw->haveIt){
			$ret[] = "underworldprice.underworldPrice(10):".$uw->getBuildingPrices();
			$ret[] = "underworldupgradeprice.underworldupgradePrice(3):".$uw->getUnitUpgradePrices();
			$ret[] = "underworldmaxsouls:0";
			
			$uw->addLeftThirst($leftthirst);
		}
		
		if($acc->hasTower()){
			$ret[] = "owntower.towerSave:".$acc->getTowerSave($uw);
			$ret[] = "owntowerlevel:0";
		}
		
		$ret[] = "fortresspricereroll:".$acc->fortressRerollPrice();
		$ret[] = "fortressprice.fortressPrice(13):".$acc->getFortressPriceSave();
		$ret[] = "fortressGroupPrice.fortressPrice:".$acc->getHallOfKnightsPriceSave();
		$ret[] = "unitprice.fortressPrice(3):".$acc->getTrainUnitsPrice();
		$ret[] = "upgradeprice.upgradePrice(3):".$acc->getUpgradeUnitsPrice();
		$ret[] = "unitlevel(4):".$acc->getUnitLvls();
		
		// Pets by Greg
		$pD = new Pets($acc->data["pets"], $acc->data["petsFed"], $acc->data["petsDung"], $acc->data["petsPvP"], $acc->data["petsBest2"], null, $acc->data["blacksmith"], $acc->data["pethonor"]);
		if($pD->havePets()) {
			$ret[] = "petsdefensetype:" . $pD->pvpData[0][1];
			$ret[] = "ownpets.petsSave:" . $pD->getPetsSave();
		}

		if(($fortressBackpackSize = $acc->getFortressBackpackSize()) > 0)
			$ret[] = "fortresschest.item(".$fortressBackpackSize."):".$acc->getFortressBackpackSave();


		$ret[] = "singleportalenemylevel:200";

		//guild		
		if($acc->hasGuild()){
			$guild = new Guild($acc->data['guild']);

			$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
			$ret[] = "owngrouppotion.r:".$guild->getPotionData();
			$ret[] = "owngroupknights.r:".$guild->getHokData();
			$ret[] = "owngroupname.r:".$guild->data['name'];
			$ret[] = "owngroupdescription.s:".$guild->data['descr'];
			$ret[] = "owngroupmember.r:".$guild->getMemberList();
			$ret[] = "owngrouprank:".$guild->getRank();
			if(($oga = $guild->getOwnGroupAttack()) !== false)
				$ret[] = $oga;

			
			$chattime = $db->query("SELECT Max(chattime) as chattime FROM guildchat WHERE guildID = $playerData[guild]")->fetch(PDO::FETCH_ASSOC)['chattime'];
			$chat = Chat::getChat($playerData['guild']);

			$ret[] = 'chathistory.s(5):'.Chat::formatChat($chat);
			$ret[] = "chattime:$chattime";
		}

		// Whispers by Greg
		$whisper = Chat::formatWhispers($playerData['whisper']);
		if($whisper != '') {
			$ret[] = 'chatwhisper.s:'.$whisper;
		}

		//  Witch by Greg
		$ret[] = "witch.witchData:9/{$acc->getWitchData()}/1452384000/0/1402139157/9/6/51/1387968268/0/61/1389353441/5/31/1390907951/8/101/1392626428/1/71/1394196822/2/41/1396169319/4/81/1398237044/7/11/1400137421/3/91/1402139201/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/";
		
		$ret[] = "tavernspecial:".Misc::getEvent()[0];
		$ret[] = 'wagesperhour:'.Account::getWagesPerHour($playerData['lvl']);
		$ret[] = "dragongoldbonus:13";
		$ret[] = "toilettfull:".$acc->toiletFullToday();
		
		$ret[] = 'messagelist.r:'.Chat::formatMessages($messages);
		
		// Combatlog by Greg
		$ret[] = "combatloglist.s:".$acc->getCombatLog();

		// Friends by Greg - v2
		$ret[] = "friendlist.r:".$acc->friendList();
		
		
		if($acc->hasAlbum())
			$ret[] = "scrapbook.r:".$acc->album->data;
		$ret[] = "skipallow:1";
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = "serverversion:$serverver";
		
		// Achievments by Greg
		$achi = $acc->achievements->getText();
		
		$ret[] = "achievement(120):" . implode("/", $achi);
		
		// Boi I ran
		//$ret[] = "fortresswalllevel:3";
		
		$ret[] = "success:";


		break;
	case 'playerarenaenemy':
		//act used to get new enemies for arena

		$acc = new Account(null, null, false, false);



		//set new enemies for arena if time is up or have no enemies
		if($acc->data['arena_nme1'] == 0){
			//alg: get rank, get 20 enemies around, select 3 at random
			$rank = $db->query("SELECT Count(*) as rank FROM players WHERE ID <> {$acc->data['ID']} AND honor > ".$acc->data['honor']);
			$rank = $rank->fetch(PDO::FETCH_ASSOC)['rank'];

			if($rank < 10)
				$rank = 0;
			else
				$rank -= 10;

			$playerpool = $db->query("SELECT ID FROM players FORCE INDEX(honor) WHERE ID <> {$acc->data['ID']} AND honor >= 0 ORDER BY honor DESC, ID DESC LIMIT $rank, 20")->fetchAll(PDO::FETCH_ASSOC);

			if(count($playerpool) < 4)
				exit('Error:no player data');

			//shuffle once
			shuffle($playerpool);

			//shuffle while play in first 3
			while($playerpool[0] == $playerID || $playerpool[1] == $playerID || $playerpool[2] == $playerID)
				shuffle($playerpool);


			$acc->data['arena_nme1'] = $playerpool[0]['ID'];
			$acc->data['arena_nme2'] = $playerpool[1]['ID'];
			$acc->data['arena_nme3'] = $playerpool[2]['ID'];

			$db->exec("UPDATE players SET arena_nme1 = ".$playerpool[0]['ID'].", arena_nme2 = ".$playerpool[1]['ID'].", arena_nme3 = ".$playerpool[2]['ID']." WHERE ID = $playerID");
		}


		$ret[] = 'Success:';
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();

		break;
	case 'playerdungeonopen':
		// Rework by Greg
	
		//normal dungeon
		//|playerdungeonopen:||||||
		//shadow world dungeon
		//|playerdungeonopen:1||||
		
		$acc = new Account(null, null, false, false);
		
		$hasKey = $acc->keyInInv();
		
		if(!is_numeric($hasKey))
		{
			$db->exec("UPDATE players SET d" . $hasKey->id . " = 2 WHERE ID = " . $acc->data["ID"]);
			$db->exec("DELETE FROM items WHERE id = {$hasKey->raw["ID"]} AND owner = " . $acc->data["ID"]);
			
			$acc = new Account(null, null, false, false);
			
			$ret[] = "dungeonopened:".$hasKey->id;
			$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		}

		/*$dungs = $db->query("SELECT d1, d2, d3, d4, d5, d6, d7, d8, d9, d10, d11, d12, d13, d14, d15, dd1, dd2, dd3, dd4, dd5, dd6, dd7, dd8, dd9, dd10, dd11, dd12, dd13, dd14 FROM players 
			WHERE ID = $playerID");
		$dungs = $dungs->fetch(PDO::FETCH_ASSOC);*/


		$faces = dungeonFaces::getFaces($acc->data);

		$dungeonfaces = $faces[0];

		$shadowfaces = $faces[1];

		$ret[] = "dungeonfaces(16):".join("/", $dungeonfaces);
		$ret[] = "shadowfaces(16):".join("/", $shadowfaces);
		$ret[] = "Success:";
		
		break;
	case 'playershadowbattle':

		//args for query
		$qryArgs = [];

		$acc = new Account(null, null, true, true);


		// if(dung complete)
		$dung = $acc->data['dd'.$args[0]] - 1;
		if($dung < 0)
			exit("Error:");
		if($args[0] < 14 && $dung > 10)
			exit();
		if($acc->data['dungeon_time'] > $GLOBALS["CURRTIME"]){ //if time not up
			if($acc->data['mush'] <= 0)
				exit("Error:need more coins");
			$acc->data['mush']--;
			$qryArgs[] = "mush = mush - 1";
			$acc->data['dungeon_time'] = 0;
			$qryArgs[] = "dungeon_time = ".$acc->data['dungeon_time'];
		}else{
			$acc->data['dungeon_time'] = $GLOBALS["CURRTIME"] + 3600;
			$qryArgs[] = "dungeon_time = ".$acc->data['dungeon_time'];
		}

		if(($freeSlot = $acc->getFreeBackpackSlot()) === false)
			exit("Error:need a free slot");


		//if dung 9 boss, monster = acc, ID -1?
		if($args[0] == 9 && $acc->data['d9'] == 11){
			$monster = clone($acc);
			$monster->exp = 10000000;
			$monster->gold = 1000000;
			$monster->ID = 0;
			$monster->hp = round($monster->hp * 2.5);
			$monster->maxHp = $monster->hp;
			//fightheader takes id from here
			$monster->data['ID'] = 0;
			$mirrorFight = true;
		}else{
			$monster = Monster::getShadowMonster($args[0], $dung);
			
			if($monster == null){
				$monster = Monster::getDungMonster($args[0], $dung);
				$monster->buff();
			}
			
			$mirrorFight = false;
		}

		$playerGroup = $acc->copycats;
		$playerGroup[] = $acc;

		$simulation = new GroupSimulation($playerGroup, [$monster]);
		$simulation->simulate();

		$bg = $args[0] + 50;
		for($i = 0; $i < count($simulation->simulations); $i++){
			$fight = $i+1;
			$ret[] = "fightheader".$fight.".fighters:4/0/0/".$bg."/1/".$simulation->fightHeaders[$i];
			$ret[] = "fight".$fight.".r:".$simulation->simulations[$i]->fightLog;
			$ret[] = "winnerid".$fight.".s:".$simulation->simulations[$i]->winnerID;
		}
		
		$ret[] = 'fightadditionalplayers.r:'.$simulation->getAdditionals();


		$rewardLog = [];
		for($i = 0; $i < 21; $i++)
			$rewardLog[] = 0;

		if($simulation->win){
			//win true
			$rewardLog[0] = 1;
			//silver
			// $rewardLog[2] = 1;
			//exp
			$rewardLog[3] = $monster->exp;

			$acc->addExp($monster->exp);

			$qryArgs[] = "exp = ".$acc->data['exp'];
			$qryArgs[] = "lvl = ".$acc->data['lvl'];
			
			$acc->data['dd'.$args[0]]++;

			$dung += 2;
			$qryArgs[] = "dd".$args[0]." = ".$acc->data['dd'.$args[0]];


			//item reward, always epic, random class, no silver value, NEVER SHIELD
			while(($itemid = mt_rand(1, 7)) == 2);
			$item = Item::genItem($itemid, $acc->lvl, mt_rand(1, 3), 100, 0, 'tower');
			$item['value_silver'] = 0;
			$itemReward = $acc->insertItem($item, $freeSlot);

			$i = 9;
			foreach($item as $s){
				$rewardLog[$i] = $s;
				$i++;
			}
			

			//album
			if($acc->hasAlbum() && !$mirrorFight){
				$a1 = $acc->album->addMonster($monster->ID);
				$a2 = $acc->album->addItem($itemReward);
				if($a1 || $a2){
					$acc->album->encode();

					$ret[] = "scrapbook.r:".$acc->album->data;
					$acc->data['album'] = $acc->album->count;

					// $db->query("UPDATE players SET album = ".$acc->album->count.", album_data = '".$acc->album->data."' WHERE ID = ".$acc->data['ID']);
					$qryArgs[] = "album = ".$acc->album->count;
					$qryArgs[] = "album_data = '".$acc->album->data."'";
				}
			}
		}

		$db->exec("UPDATE players SET ".join(", ", $qryArgs)." WHERE ID = ".$acc->data['ID']);

		$ret[] = "fightresult.battlereward:".join("/", $rewardLog);
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = 'owntower.towerSave:'.$acc->getTowerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];



		break;
	case 'playertowerbattle':
		//arg 0 = tower lvl, fuck your input m8

		//args for query
		$qryArgs = [];

		$acc = new Account(null, null, true, true);
		
		if(($freeSlot = $acc->getFreeBackpackSlot()) === false)
			exit("Error:need a free slot");
		if($acc->data['dungeon_time'] > $GLOBALS["CURRTIME"]){ //if time not up
			if($acc->data['mush'] <= 0)
				exit("Error:need more coins");
			$acc->data['mush']--;
			$qryArgs[] = "mush = mush - 1";
			$acc->data['dungeon_time'] = 0;
			$qryArgs[] = "dungeon_time = ".$acc->data['dungeon_time'];
		}else{
			$acc->data['dungeon_time'] = $GLOBALS["CURRTIME"] + 3600;
			$qryArgs[] = "dungeon_time = ".$acc->data['dungeon_time'];
		}

		$monster = Monster::getTowerMonster($acc->data['tower']);
		
		if($monster === null)
			exit("Error:tower closed");
		
		$playerGroup = $acc->copycats;
		$playerGroup[] = $acc;

		$simulation = new GroupSimulation($playerGroup, [$monster]);
		$simulation->simulate();
		
		for($i = 0; $i < count($simulation->simulations); $i++){
			$fight = $i+1;
			$ret[] = "fightheader".$fight.".fighters:5/0/0/0/1/".$simulation->fightHeaders[$i];
			$ret[] = "fight".$fight.".r:".$simulation->simulations[$i]->fightLog;
			$ret[] = "winnerid".$fight.".s:".$simulation->simulations[$i]->winnerID;
		}
		
		$ret[] = 'fightadditionalplayers.r:'.$simulation->getAdditionals();
		
		$rewardLog = [];
		for($i = 0; $i < 21; $i++)
			$rewardLog[] = 0;

		if($simulation->win){
			//win true
			$rewardLog[0] = 1;
			//silver
			$rewardLog[2] = 1;
			//no exp for tower
			// $rewardLog[3] = $monster->exp;

			$acc->data['tower']++;
			$qryArgs[] = "tower = tower + 1";


			//item reward, always epic for random claass, no silver value, NOT SHIELD
			while(($itemid = mt_rand(1, 7)) == 2);
			$item = Item::genItem($itemid, $acc->lvl, mt_rand(1, 3), 100, 0, 'tower');
			$item['value_silver'] = 0;
			$itemReward = $acc->insertItem($item, $freeSlot);

			$i = 9;
			foreach($item as $s){
				$rewardLog[$i] = $s;
				$i++;
			}
			

			//album
			if($acc->hasAlbum()){
				$a1 = $acc->album->addMonster($monster->ID);
				$a2 = $acc->album->addItem($itemReward);
				if($a1 || $a2){
					$acc->album->encode();

					$ret[] = "scrapbook.r:".$acc->album->data;
					$acc->data['album'] = $acc->album->count;

					// $db->query("UPDATE players SET album = ".$acc->album->count.", album_data = '".$acc->album->data."' WHERE ID = ".$acc->data['ID']);
					$qryArgs[] = "album = ".$acc->album->count;
					$qryArgs[] = "album_data = '".$acc->album->data."'";
				}
			}
		}

		$db->exec("UPDATE players SET ".join(", ", $qryArgs)." WHERE ID = ".$acc->data['ID']);

		$ret[] = "fightresult.battlereward:".join("/", $rewardLog)."/";
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
	
		break;
	case 'playerdungeonbattle':

		//args for query
		$qryArgs = [];

		$acc = new Account(null, null, true, true);

		//error checking
		if(($freeSlot = $acc->getFreeBackpackSlot()) === false)
			exit("Error:need a free slot");
		$dung = $acc->data['d'.$args[0]] - 1;
		if($dung < 0)//if closed
			exit("Error:");
		if($args[0] < 14 && $dung > 10) // if complete
			exit();
		if($acc->data['dungeon_time'] > $GLOBALS["CURRTIME"]){ //if time not up
			if($acc->data['mush'] <= 0)
				exit("Error:need more coins");
			$acc->data['mush']--;
			$qryArgs[] = "mush = mush - 1";
			$acc->data['dungeon_time'] = 0;
			$qryArgs[] = "dungeon_time = ".$acc->data['dungeon_time'];
		}else{
			$acc->data['dungeon_time'] = $GLOBALS["CURRTIME"] + 3600;
			$qryArgs[] = "dungeon_time = ".$acc->data['dungeon_time'];
		}

		//if dung 9 boss, monster = acc, ID -1?
		if($args[0] == 9 && $acc->data['d9'] == 11){
			$monster = clone($acc);
			$monster->exp = 10000000;
			$monster->gold = 1000000;
			$monster->ID = 0;
			//fightheader takes id from here
			$monster->data['ID'] = 0;
			$mirrorFight = true;
		}else{
			$monster = Monster::getDungMonster($args[0], $dung);
			$mirrorFight = false;
		}
		
		if($monster === null)
			exit("Error:coming soon");
		
		$bg = $args[0] + 50;
		$ret[] = "fightheader.fighters:4/0/0/".$bg."/2/".$acc->getFightHeader().$monster->getFightHeader();

		$simulation = new Simulation($acc, $monster);
		$simulation->simulate();

		$ret[] = "fight.r:".$simulation->fightLog;
		$ret[] = "winnerid:".$simulation->winnerID;

		$rewardLog = [];
		for($i = 0; $i < 21; $i++)
			$rewardLog[] = 0;
		
		//rewarding
		if($simulation->winnerID == $acc->data['ID']){
			// Unlock new dungeon?
			if($args[0] >= 9 && $args[0] < 13 && $dung == 10 && $acc->data["d".($args[0]+1)] < 2){
				// Unlock next dungeon
				
				$acc->data["d".($args[0]+1)] = 2;
				
				$qryArgs[] = "d".($args[0]+1) . " = 2";
			}
			
			//win true
			$rewardLog[0] = 1;
			//silver
			$rewardLog[2] = 0;
			//exp
			$rewardLog[3] = $monster->exp;

			$acc->addExp($monster->exp);

			$qryArgs[] = "exp = ".$acc->data['exp'];
			$qryArgs[] = "lvl = ".$acc->data['lvl'];

			//displaying of dung
			$acc->data['d'.$args[0]] ++;

			$dung += 2;
			$qryArgs[] = "d".$args[0]." = ".$dung;

			//item reward
			$itemChance = $dung == 12 ? 100 : 50;
			$epicChance = $dung == 12 ? 100 : 50;
			if($itemChance > rand(0, 99)){
				$item = Item::genItem(rand(1, 10), $acc->lvl, $acc->class, $epicChance, 0, 'dungeon');
				$itemReward = $acc->insertItem($item, $freeSlot);

				$i = 9;
				foreach($item as $s){
					$rewardLog[$i] = $s;
					$i++;
				}
			}

			//album
			if($acc->hasAlbum() && !$mirrorFight){
				$a1 = $acc->album->addMonster($monster->ID);
				$a2 = isset($itemReward) ? $acc->album->addItem($itemReward) : false;
				if($a1 || $a2){
					$acc->album->encode();

					$ret[] = "scrapbook.r:".$acc->album->data;
					$acc->data['album'] = $acc->album->count;

					// $db->query("UPDATE players SET album = ".$acc->album->count.", album_data = '".$acc->album->data."' WHERE ID = ".$acc->data['ID']);
					$qryArgs[] = "album = ".$acc->album->count;
					$qryArgs[] = "album_data = '".$acc->album->data."'";
				}
			}
		}
		
		$db->exec("UPDATE players SET ".join(", ", $qryArgs)." WHERE ID = ".$acc->data['ID']);

		$ret[] = "fightresult.battlereward:".join("/", $rewardLog)."/";
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = 'owntower.towerSave:'.$acc->getTowerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		
		// Twister = get shadowfaces (this fixed the no face change at twister after win)
		if($args[0] == 15)
		{
			$dungs = [];
			
			for($i = 1; $i <= 14; $i++)
				$dungs["dd".$i] = $acc->data["dd".$i];
			
			$dungs["d15"] = $acc->data["d15"];
			
			$dungs["d16"] = $acc->data["d16"];
			$dungs["dd16"] = $acc->data["dd16"];
			
			$ret[] = "shadowfaces(16):".join("/", dungeonFaces::getFaces($dungs, false, true)[1]);
		}

		break;
	case 'playerportalbattle':

		//args for query
		$qryArgs = [];

		$acc = new Account(null, null, false, true);

		//error checking | no need for a free slot here
		// if(($freeSlot = $acc->getFreeBackpackSlot()) === false)
		// 	exit("Error:need a free slot");

		//if time not up | now = current day since start of the year
		if( ($now = Misc::getNow(false)) == $acc->data['portal_time'])
			exit("Error:portal cooldown notice");

		//set new date and update db
		$acc->data['portal_time'] = $now;
		$qryArgs[] = 'portal_time = '.$acc->data['portal_time'];


		//set monster current hp to the hp from database
		$monster = Monster::getPortalMonster($acc->data['portal'] + 1);
		$monster->hp = $acc->data['portal_hp'];

		$ret[] = "fightheader.fighters:6/0/0/1/2/".$acc->getFightHeader().$monster->getFightHeader();


		$simulation = new Simulation($acc, $monster);
		$simulation->simulate();

		$ret[] = "fight.r:".$simulation->fightLog;
		$ret[] = "winnerid:".$simulation->winnerID;

		$rewardLog = [];
		for($i = 0; $i < 21; $i++)
			$rewardLog[] = 0;
		
		//rewarding
		if($simulation->winnerID == $acc->data['ID']){
			//win true
			$rewardLog[0] = 1;

			$acc->data['portal']++;
			$qryArgs[] = 'portal = '.$acc->data['portal'];

			//update mob hp in database
			if($acc->data['portal'] < 50){
				$acc->data['portal_hp'] = Monster::getPortalMonster($acc->data['portal'] + 1)->hp;
				$qryArgs[] = 'portal_hp = '.$acc->data['portal_hp'];
			}

			//album
			if($acc->hasAlbum()){
				if($acc->album->addMonster($monster->ID)){
					$acc->album->encode();

					$ret[] = "scrapbook.r:".$acc->album->data;
					$acc->data['album'] = $acc->album->count;

					// $db->query("UPDATE players SET album = ".$acc->album->count.", album_data = '".$acc->album->data."' WHERE ID = ".$acc->data['ID']);
					$qryArgs[] = "album = ".$acc->album->count;
					$qryArgs[] = "album_data = '".$acc->album->data."'";
				}
			}
		}else{
			//if lost, update database with remaining hp of mob

			$qryArgs[] = 'portal_hp = '.$monster->hp;
			$acc->data['portal_hp'] = $monster->hp;

		}
		
		$db->exec("UPDATE players SET ".join(", ", $qryArgs)." WHERE ID = ".$acc->data['ID']);

		$ret[] = "fightresult.battlereward:".join("/", $rewardLog)."/";
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		break;
	case 'groupportalbattle':

		$qryArgs = [];
		$time = $GLOBALS["CURRTIME"];

		$acc = new Account(null, null, false, true);


		$now = md5(date("Y-m-d", $time));
		$portal = md5(date("Y-m-d", $acc->data['gportal_time']));
	
		$guild = new Guild($playerGuild);

		// Check by Greg
		if($now == $portal)
			exit();
		
		//set new date and update db
		$acc->data['gportal_time'] = $time;
		$guild->guildPortalCD($playerID);

		if($guild->data['portal'] >= 50)
			exit('Error:');

		$monster = Monster::getGuildPortalMonster($guild->data['portal']);
		$monster->hp = $guild->data['portal_hp'];

		$ret[] = "fightheader.fighters:7/0/0/0/1/".$acc->getFightHeader().$monster->getFightHeader();

		$simulation = new Simulation($acc, $monster);
		$simulation->simulate();

		$ret[] = "fight.r:".$simulation->fightLog;
		$ret[] = "winnerid:".$simulation->winnerID;

		$rewardLog = [];
		for($i = 0; $i < 21; $i++)
			$rewardLog[] = 0;
		
		//rewarding
		if($simulation->winnerID == $acc->data['ID']){
			//win true
			$rewardLog[0] = 1;

			//chat log
			$dmgdealt = $guild->data['portal_hp'] - $monster->hp;
			$log = "#pw#".$acc->data['name']."#".$guild->data['portal']."#$dmgdealt";

			$guild->data['portal']++;
			$acc->data['guild_portal']++;
			$qryArgs[] = 'portal = '.$guild->data['portal'];

			//update mob hp in database
			if($guild->data['portal'] < 50){
				$guild->data['portal_hp'] = Monster::getGuildPortalMonster($guild->data['portal'])->hp;
				$qryArgs[] = 'portal_hp = '.$guild->data['portal_hp'];
			}

			//album for all members
			$guild->addAlbumMonster($monster->ID + 1);


		}else{
			//if lost, update database with remaining hp of mob
			$dmgdealt = $guild->data['portal_hp'] - $monster->hp;

			$qryArgs[] = 'portal_hp = '.$monster->hp;
			$guild->data['portal_hp'] = $monster->hp;
			
			$hpLeftPrc = round($guild->data['portal_hp'] / $monster->maxHp * 100);
			$log = "#po#".$acc->data['name']."#".$guild->data['portal']."#$dmgdealt#$hpLeftPrc";
		}


		
		//insert log && update guilds
		$db->exec("UPDATE guilds SET ".join(", ", $qryArgs)." WHERE ID = ".$guild->data['ID']);

		$chattime = Chat::chatInsert($log, $playerGuild, $playerID);
		$chat = Chat::getChat($playerGuild);

		//update player portal time and poll
		$db->exec("UPDATE players SET gportal_time = ".$acc->data['gportal_time'].", poll = $time WHERE ID = $playerID");


		$ret[] = 'chathistory.s(5):'.Chat::formatChat($chat);
		$ret[] = "chattime:$chattime";
		$ret[] = "fightresult.battlereward:".join("/", $rewardLog)."/";
		$ret[] = "owngroupsave.groupSave:".$guild->getGroupSave();
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:$time";


		break;
	case 'playertowerbuylevel':

		$acc = new Account(null, null, true, false);

		$copycat = $acc->copycats[$args[0] - 1];

		if(($cost = Copycat::getLvlCost($copycat->data['lvl'])) > $acc->data['silver'])
			exit('Error:need more gold');

		$acc->data['silver'] -= $cost;
		$db->exec("UPDATE players SET silver = silver - $cost WHERE ID = ".$acc->data['ID']);

		$copycat->lvlUp();

		$ret[] = 'Success:';
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		$ret[] = 'owntowerlevel:200';
		$ret[] = 'owntower.towerSave:'.$acc->getTowerSave();
		$ret[] = 'timestamp:'.$GLOBALS["CURRTIME"];

		break;
	case 'playersetface':
	
		$acc = new Account(null, null, false, false);
		
		if($acc->data["silver"] < 100)
			exit("Error:need more gold");
		
		$acc->data["race"] = $args[0];
		$acc->data["gender"] = $args[1];
		$acc->data["face"] = $args[2];
		$acc->data["silver"] -= 100;
		
		$qry = $db->prepare("UPDATE players SET race = :race, gender = :gender, face = :face, silver = silver - 100 WHERE ID = ".$playerID);
		$qry->bindParam(":race", $args[0]);
		$qry->bindParam(":gender", $args[1]);
		$qry->bindParam(":face", $args[2]);
		$qry->execute();
		
		$ret[] = 'Success:';
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();

		break;
	case 'groupgethalloffame':

		if(strlen($args[1]) > 2){
		
			$qry = $db->prepare('SELECT ID, honor FROM guilds WHERE name = :name');
			$qry->bindParam(':name', $args[1]);
			$qry->execute();

			if($qry->rowCount() == 0)
				exit('Error:group not found');

			$p = $qry->fetch(PDO::FETCH_ASSOC);

			$qry = $db->query('SELECT Count(*) AS rank FROM guilds WHERE honor > '.$p['honor'].' OR (honor = '.$p['honor'].' AND ID > '.$p['ID'].')');

			$args[0] = $qry->fetch(PDO::FETCH_ASSOC)['rank'];
			// var_dump($args[0]);
		}

		$args[0] -= $args[2] + 1;
		if($args[0] < 0)
			$args[0] = 0;

		//SELECT guilds.*, count(players.guild) AS membercount FROM guilds LEFT JOIN players ON guilds.ID = players.guild GROUP BY guild ORDER BY membercount DESC LIMIT 15;
		$qry = $db->prepare("SELECT guilds.ID as gID, guilds.name, GROUP_CONCAT(players.name ORDER BY guild_rank) AS leader, Count(*) AS membercount, guilds.honor, '0' FROM guilds FORCE INDEX(honor) LEFT JOIN players ON guilds.ID = players.guild WHERE guilds.honor >= 0 GROUP BY players.guild 
			ORDER BY guilds.honor DESC, guilds.ID DESC LIMIT :f, 30");
		$qry->bindParam(':f', $args[0], PDO::PARAM_INT);
		$qry->execute();

		$guilds = $qry->fetchAll( PDO::FETCH_ASSOC );

		
		$list = [];
		$rank = $args[0] + 1;
		// for($i = 0; $i < count($guilds); $i++) {
		// 	var_dump($guilds[$i]);
		// 	// $list[] = "$rank,$guilds[$i]"
		// }
		foreach($guilds as $g){
			$g['leader'] = explode(',', $g['leader'])[0];
			$list[] = "$rank,$g[name],$g[leader],$g[membercount],$g[honor],0"; 
			$rank++;
		}

						//rank, name, leader, memberc, honor, fightstatus
		// ranklistgroup.r:1,Asgard United,guzii,50,37728,0;
		$ret[] = 'ranklistgroup.r:'.join(';', $list);
		$ret[] = "Success:";

		break;
	case 'playergethalloffame':
		
		$args[0] = intval($args[0]);
		
		if(strlen($args[1]) > 2){
			
			$args[1] = Account::formatUser($args[1]);

			$qry = $db->prepare('SELECT ID, honor FROM players WHERE name = :name');
			$qry->bindParam(':name', $args[1]);
			$qry->execute();

			if($qry->rowCount() == 0)
				exit('Error:player not found');

			$p = $qry->fetch(PDO::FETCH_ASSOC);

			$qry = $db->query('SELECT Count(*) AS rank FROM players WHERE (honor > '.$p['honor'].' OR (honor = '.$p['honor'].' AND ID > '.$p['ID'].'))');

			$args[0] = $qry->fetch(PDO::FETCH_ASSOC)['rank'];
		}

		$args[0] -= $args[2] + 1;
		if($args[0] < 0)
			$args[0] = 0;

		
		$qry = $db->prepare("SELECT players.name, guilds.name AS gname, players.lvl, players.honor, players.class FROM players FORCE INDEX(honor) LEFT JOIN guilds ON players.guild = guilds.ID 
			WHERE players.honor >= 0 ORDER BY players.honor DESC, players.ID DESC LIMIT {$args[0]}, 30");
		$qry->execute();

		$players = $qry->fetchAll( PDO::FETCH_ASSOC );
		
		$list = [];
		for($i = 0; $i < count($players); $i++) {
			$rank = $args[0] + $i + 1;
			$list[] = $rank.','.join(',', $players[$i]);
		}
		
		//rank, name, gname, lvl, honor, class
		$ret[] = "Ranklistplayer.r:".join(';', $list);
		$ret[] = "Success:";
		$ret[] = "args:".implode("/", $args);

		break;
	case 'playerlookat':
		
		$acc = new Account(null, null, false, false);
		
		$args[0] = $acc::formatUser($args[0]);
		
		// Other player data - Fortress data by Greg
		
		if($args[0] == "?"){
			$qry = $db->query("SELECT players.*, guilds.portal AS guild_portal, guilds.name AS gname FROM players LEFT join guilds ON players.guild = guilds.ID WHERE players.ID = (SELECT enemyid FROM fortress WHERE owner = $playerID)");
		}else if(is_numeric($args[0])){
			$qry = $db->query("SELECT players.*, guilds.portal AS guild_portal, guilds.name AS gname FROM players LEFT join guilds ON players.guild = guilds.ID WHERE players.ID = $args[0]");
		}else{
			$qry = $db->prepare("SELECT players.*, guilds.portal AS guild_portal, guilds.name AS gname FROM players LEFT JOIN guilds ON players.guild = guilds.ID WHERE players.name = :name");
			$qry->bindParam(':name', $args[0]);
			$qry->execute();
		}

		if($qry->rowCount() <= 0)
			exit('Error:player not found');

		$playerData = $qry->fetch(PDO::FETCH_ASSOC);

		$qry = $db->query("SELECT * FROM items WHERE owner = '{$playerData['ID']}' AND slot BETWEEN 10 AND 19");
		$items = $qry->fetchAll(PDO::FETCH_ASSOC);
		$player = new Player($playerData, $items);

		$ret[] = "otherplayergroupname.r:".$playerData['gname'];
		$ret[] = "otherplayer.playerlookat:".$player->getLookatSave();
		$ret[] = "otherdescription.s:".$playerData['description'];
		$ret[] = "b"; // Hey Beter
		$ret[] = "otherplayername.r:".$player->data['name'];
		$ret[] = "otherplayerunitlevel(4):".$player->fortressAttackLevels();
		
		// By Greg
		$ret[] = "otherplayerfriendstatus:".$acc->otherPlayerFriendStatus($playerData['ID']);
		
		$ret[] = "otherplayerfortressrank:0"; // Fortress rank not working
		$ret[] = "soldieradvice:0"; // Removed it cuz it's hard to make
		$ret[] = "fortresspricereroll:".$acc->fortressRerollPrice(); // Reroll price
		$ret[] = "success:";
		break;
	case 'playerpollscrapbook':
		//dunno when this is called or why

		$ret[] = "Success:";
		$albumData = $db->query("SELECT album_data FROM players WHERE ID = $playerID")->fetch(PDO::FETCH_ASSOC)['album_data'];

		$ret[] = "scrapbook.r:$albumData";


		break;
	case 'playerscrapbookcorrupt':
		//if scrapbook count in player data and count from data don't match, client calls this

		$ret[] = "Success:";
		$albumData = $db->query("SELECT album_data FROM players WHERE ID = $playerID")->fetch(PDO::FETCH_ASSOC)['album_data'];

		$ret[] = "scrapbook.r:$albumData";

		break;
	case 'playeradventurestart':
		//arg 0 = quest

		$acc = new Account(null, null, false, false);

		$acc->questStart($args[0]);


		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];


		break;
	case 'playeradventurestop':

		$acc = new Account(null, null, false, false);

		$acc->questStop();


		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];

		break;
	case 'playeradventurefinished':
		$acc = new Account(null, null, false, true);

		// Fix by Greg
		if($acc->data['status_extra'] == 0)
			exit("Error:no quest atm");
		
		//see if skipped, take mushrooms
		if($acc->data['warned'] != 0 || ($acc->data['status_time'] > $GLOBALS["CURRTIME"] && ($acc->data['mush'] <= 0 && $args[0] != "2")))
			exit('Error:need more coins');
		else if($args[0] == "2")
			exit("Error:coming soon");
		
		$realStats = $acc->getRealStats();
		
		$w = $realStats["wit"];
		$weap = $acc->getWeapon();
		
		if($weap == null){
			$weapdata = [
				"type" => 1,
				"item_id" => 1,
				"dmg_min" => 1,
				"dmg_max" => 2,
				"a1" => 0,
				"a2" => 0,
				"a3" => 0,
				"a4" => 0,
				"a5" => 0,
				"a6" => 0,
				"value_silver" => 0,
				"value_mush" => 0,
				"slot" => 8
			];
			
			$weap = new Item($weapdata);
		}
		
		$prim = "dex";
		
		switch($acc->data["class"]){
			case 1: $prim = "str"; break;
			case 2: $prim = "intel"; break;
		}
		
		$prim = $realStats[$prim] * 0.35;
		
		$dmg_min = round($weap->dmg_min * (1 + ($prim / 10)));
		$dmg_max = round($weap->dmg_max * (1 + ($prim / 10)));
		
		$hp = round($w * 4 * ($acc->data['lvl'] + 1) / 20);
		
		$monsterID = ($acc->data['quest_exp'.$acc->data['status_extra']] % 163) + 1;

		$monster = new Monster($acc->data['lvl'], 2, $realStats["str"], $realStats["dex"], $realStats["intel"], $realStats["wit"], $realStats["luck"], $dmg_min, $dmg_max, $hp, 1, -$monsterID, 1, $weap->id);
		
		$bg = $acc->questBackground($acc->data['quest_exp'.$acc->data['status_extra']]);
		
		$ret[] = "fightheader.fighters:1/0/0/".$bg."/0/".$acc->getFightHeader().$monster->getFightHeader();
		
		$simulation = new Simulation($acc, $monster);
		$simulation->simulate();

		$win = $simulation->winnerID == $acc->data['ID'];
		
		// Quests anti-cheat
		include "sf/anti/quests.php";
		
		$ret[] = "fight.r:".$simulation->fightLog;
		$ret[] = "winnerid:".$simulation->winnerID;
		$ret[] = "fightresult.battlereward:".$acc->questFinish($win, $monsterID, $args[0]);
		$ret[] = "Success:";
		
		if(isset($RENEW))
			$acc = new Account(null, null, false, true);
		
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		
		if(($fortressBackpackSize = $acc->getFortressBackpackSize()) > 0)
			$ret[] = "fortresschest.item(".$fortressBackpackSize."):".$acc->getFortressBackpackSave();

		break;
	case 'playerworkstart':

		$hours = intval($args[0]);

		$qry = $db->prepare('SELECT ID, status FROM players WHERE ssid = :ssid');
		$qry->bindParam(':ssid', $ssid);
		$qry->execute();

		$playerData = $qry->fetch(PDO::FETCH_ASSOC);

		if($playerData['status'] != 0)
			exit();

		$statusTime = $GLOBALS["CURRTIME"]+ 3600 * $hours;

		$ret[] = 'Success:';
		$ret[] = '#ownplayersave.playerSave:45/1/47/'.$statusTime;

		$db->exec("UPDATE players SET status = 1, status_time = $statusTime, status_extra = $hours WHERE ID = ".$playerData['ID']);
		break;
	case 'playerworkstop':

		$ret[] = 'Success:';
		$ret[] = '#ownplayersave.playerSave:45/0/47/0';

		$qry = $db->prepare('UPDATE players SET status = 0, status_extra = 0, status_time = 0 WHERE ssid = :ssid');
		$qry->bindParam(':ssid', $ssid);
		$qry->execute();

		break;
	case 'playerworkfinished':

		$qry = $db->prepare("SELECT ID, lvl, silver, status, status_extra, status_time FROM players WHERE ssid = :ssid");
		$qry->bindParam(':ssid', $ssid);
		$qry->execute();
		$playerData = $qry->fetch(PDO::FETCH_ASSOC);

		$reward = Account::getWagesPerHour($playerData['lvl']) * $playerData['status_extra'];

		$db->exec("UPDATE players SET workedhours = workedhours + ".$playerData['status_extra'].", status = 0, status_extra = 0, status_time = 0, silver = silver + $reward WHERE ID = ".$playerData['ID']);

		$ret[] = 'Success:';
		$ret[] = "workreward:$reward";

		$reward += $playerData['silver'];

		$ret[] = "#ownplayersave.playerSave:13/$reward/45/0/47/0";

		break;
	case 'playeritemmove':
	
		if($args[0] == 1  && $args[2] == 1)
			exit("Success:");

		if($args[0] == 4  && $args[2] == 4)
			exit("Success:");

		if($args[0] == 3  && $args[2] == 3)
			exit("Success:");

		if($args[0] == 1 && $args[2] == 12)
			exit('Error:you cannot sell from here');

		if($args[2] == 3 || $args[2] == 4)
			if($args[0] == 1)
				exit('Error:you cannot sell from here');


		$itemBought = false;

		//if source shops, load album
		if($args[0] == 3 || $args[0] == 4)
			$itemBought = true;
		

		$acc = new Account(null, null, true, $itemBought);

		$acc->moveItem($args);
		
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		if(($fortressBackpackSize = $acc->getFortressBackpackSize()) > 0)
			$ret[] = "fortresschest.item(".$fortressBackpackSize."):".$acc->getFortressBackpackSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = "Success:";



		break;
	case 'playertoilettflush':

		$acc = new Account(null, null);

		if($acc->toiletFull() != false)
			exit('Error:toilett is not full');

		if(($freeSlot = $acc->getFreeBackpackSlot()) === false)
			exit('Error:need a free slot');

		$db->exec("UPDATE players SET wcaura = wcaura + 1, wcexp = 0 WHERE ID = $playerID");
		$acc->data['wcaura']++;
		$acc->data['wcexp'] = 0;

		//last arg - epic chance
		$item = Item::genItem(rand(1, 10), $acc->lvl, $acc->class, 100);

		$acc->insertItem($item, $freeSlot);


		$freeSlot += $freeSlot >= 100 ? -94 : +1;
		$ret[] = 'Success:';
		$ret[] = 'toilettspawnslot:'.$freeSlot;
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		if($freeSlot >= 100)
			$ret[] = "fortresschest.item(".$acc->getFortressBackpackSize()."):".$acc->getFortressBackpackSave();

		break;
	case 'playerpotionkill':

		$acc = new Account(null, null, false, false);

		$acc->data['potion_dur'.$args[0]] = 0;
		$acc->data['potion_type'.$args[0]] = 0;

		$db->exec("UPDATE players SET potion_dur$args[0] = 0 WHERE ID = ".$acc->data['ID']);

		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];

		break;
	case 'playerattributincrease': // Statbuy


		// exit('Error:need more coins');

		$args[0]--;
		
		if($args[0] < 0 || $args[0] > 4)
			exit();

		$stat = ['str', 'dex', 'intel', 'wit', 'luck'][$args[0]];

		$qry = $db->prepare('SELECT ID, silver, '.$stat.' FROM players WHERE ssid = :ssid');
		$qry->bindParam(':ssid', $ssid);
		$qry->execute();
		$playerData = $qry->fetch(PDO::FETCH_ASSOC);

		$price = Account::getStatPrice($playerData[$stat] - 10);

		if($price > $playerData['silver'])
			exit("Error:need more gold");

		$db->exec("UPDATE players SET silver = silver - $price, $stat = $stat + $statPlus WHERE ID = $playerData[ID]");

		$ret[] = 'Success:';
		$ret[] = '#ownplayersave.playerSave:13/'.($playerData['silver'] - $price).'/'.($args[0] + 30).'/'.($playerData[$stat] + 25).'/'.($args[0] + 40).'/'.($playerData[$stat] - 9);



		break;
	case 'playerbeerbuy':

		$qry = $db->prepare('SELECT ID, thirst, beers, mush, class FROM players WHERE ssid = :ssid');
		$qry->bindParam(':ssid', $ssid);
		$qry->execute();
		$playerData = $qry->fetch(PDO::FETCH_ASSOC);


		if($playerData['thirst'] > 4800)
			exit("Error:2muchthirst");

		/*if($playerData['mush'] <= 0)
			exit('Error:need more coins');*/

		if($playerData['beers'] >= 11)
			exit("Error:max beers");

		//$playerData['beers']++;
		$playerData['thirst'] += 1200;
		$playerData['mush']--;

		

		//temporary for reseting portal timers, to revert, just switch out the comments and edit playersave
		$db->exec('UPDATE players SET thirst = thirst + 1200, beers = 0 WHERE ID = '.$playerData['ID']);

		$guild = new Guild($playerGuild);
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();

		$ret[] = 'Success:';
		$ret[] = '#ownplayersave.playerSave:14/'.$playerData['mush'].'/456/'.$playerData['thirst'].'/457/'.$playerData['beers'].'/29/'.$playerData['class'];

		break;
	case 'playernewwares':

		$acc = new Account(null, null, false, false);

		$acc->rerollShop($args[0]);

		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];

		break;
	case 'playermountbuy':

		$qry = $db->prepare('SELECT ID, silver, mush, mount, mount_time, tower, quest_dur1, quest_dur2, quest_dur3 FROM players WHERE ssid = :ssid');
		$qry->bindParam(':ssid', $ssid);
		$qry->execute();
		$playerData = $qry->fetch(PDO::FETCH_ASSOC);

		//mush cost
		$costMush = [0, 0, 1, 25][$args[0] - 1];
		if($costMush > $playerData['mush'])
			exit('Error:need more coins');

		//gold cost
		$costSilver = [100, 500, 1000, 0][$args[0] - 1];
		if($costSilver > $playerData['silver'])
			exit('Error:need more gold');

		$playerData['mush'] -= $costMush;
		$playerData['silver'] -= $costSilver;
		$resp = [];

		//if same mount, and time not expired, just inscrease the time
		if($playerData['mount'] == $args[0] && $playerData['mount_time'] > $GLOBALS["CURRTIME"]){
			$playerData['mount_time'] += 1209600;
		}else{
			$playerData['mount'] = $args[0];
			$playerData['mount_time'] = $GLOBALS["CURRTIME"] + 1209600;

			$mountMultiplier = [0.9, 0.8, 0.7, 0.5][$args[0] - 1];

			for($i = 1; $i <= 3; $i++){
				$resp[] = (240 + $i).'/'.ceil($playerData["quest_dur$i"] * $mountMultiplier);
			}
		}


		$resp[] = '13/'.$playerData['silver'].'/14/'.$playerData['mush'].'/286/'.($playerData['tower'] * 65536 + $args[0]).'/451/'.$playerData['mount_time'];

		$db->exec("UPDATE players SET mush = mush - $costMush, silver = silver - $costSilver, mount = $args[0], mount_time = ".$playerData['mount_time'].' WHERE ID = '.$playerData['ID']);
		
		$ret[] = 'Success:';
		$ret[] = '#ownplayersave.playerSave:'.join('/', $resp);
		$ret[] = 'timestamp:'.$GLOBALS["CURRTIME"];

		break;
	case 'playerwitchenchantitem':
		//arg 0 = enchant id counted from left to right

		//table of item types per enchant
		$itemType = [5, 6, 3, 10, 7, 4, 8, 1, 9][$args[0] - 1];

		$acc = new Account(null, null, false, false);
		
		$encd = false;
		
		$allenc = true;
		
		foreach($acc->equip as $item){
			if($item->type == $itemType && !$item->enchanted){
				$item->enchant();
				$encd = true;
				break;
			}
			else if (!$item->enchanted)
				$allenc = false;
		}
		
		if($encd) {
			$sql = "UPDATE players SET silver = silver - {$acc->fortressRerollPrice()} WHERE ID = '{$acc->data['ID']}'";
			$db->exec($sql);
			$acc->data['silver'] -= $acc->fortressRerollPrice();
		}
		
		$ret[] = 'Success:';
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		$ret[] = 'timestamp'.$GLOBALS["CURRTIME"];

		break;
	case 'playermessagesend':

		//args: reciver/topic/message

		//reserve single numeric topic for system, simplier solution
		if(strlen($args[1]) == 1 && is_numeric($args[1]))
			exit();

		$qry = $db->prepare('SELECT ID, friends FROM players WHERE name = :name');
		$qry->bindParam(':name', $args[0]);
		$qry->execute();
		$fetch = $qry->fetch(PDO::FETCH_ASSOC);
		
		if($qry->rowCount() == 0)
			exit('Error:recipient not found');

		if(Account::isUserIgnored($fetch['friends'], $playerID))
			exit("Error:player not found"); // player is ignored
		
		$reciver = $fetch['ID'];
		$time = $GLOBALS["CURRTIME"];
		
		$qry = $db->prepare("INSERT INTO messages(sender, reciver, time, topic, message) VALUES($playerID, $reciver, $time, :topic, :message)");
		$qry->bindParam(':topic', $args[1]);
		$qry->bindParam(':message', $args[2]);
		$qry->execute();

		exit('Success:');

		break;
	case 'fortressbuildstart':
		//building id
		$args[0] --;

		$acc = new Account(null, null, false, false);

		$acc->fortressBuild($args[0]);


		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];

		break;
	case 'fortressbuildstop':
		// TODO: Give back reduced resources

		$acc = new Account(null, null, false, false);

		$acc->fortressBuildStop();

		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];

		break;
	case 'fortressbuildfinished':

		$acc = new Account(null, null, false, false);
		
		$acc->fortressBuildFinish();

		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "fortressprice.fortressPrice(13):".$acc->getFortressPriceSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];

		//i guess if upgraded bank, mines or other shit that need update, include them
		$args[0]--;
		if($args[0] == 9){ 
			//fortress backpack
			$ret[] = "fortresschest.item(".$acc->getFortressBackpackSize()."):".$acc->getFortressBackpackSave();
		}

		break;
	case 'fortressgemstonestart':

		$acc = new Account(null, null, false, false);

		$acc->fortressDigStart();

		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "fortressprice.fortressPrice(13):".$acc->getFortressPriceSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		
		break;
	case 'fortressgemstonestop':

		$acc = new Account(null, null, false, false);

		$acc->fortressDigStop();

		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];

		break;
	case 'fortressgemstonefinished':

		$acc = new Account(null, null, false, false);

		//client check if there is a free slot, doesn't send the request if there is no space, but it's better to keep this here
		if(($freeSlot = $acc->getFreeBackpackSlot()) === false)
			exit("Error:need a free slot");

		//checks if time's up, if enough mushrooms, resets db
		$acc->fortressDigFinish();

		// allhok by Greg
		if($acc->hasGuild()) {
			$allhok = $acc::getAllHok($acc->data['guild']);
		}else{
			//$allhok = 0;
			$allhok = $acc->data['hok']; // v2 - No guild, but we keep the user's hok
		}
		
		//class is not needed, but have it anyway in case i wanna have higher chance for class specific gems or whatever
		//send hall of knights level as epic chance, so bigger gem stat	(by Greg)
		$gem = Item::genItem(15, $acc->lvl, $acc->class, $allhok, $acc->data['b4']);

		$acc->insertItem($gem, $freeSlot);


		$freeSlot += $freeSlot >= 100 ? -94 : +1;
		$ret[] = "gemstonebackpackslot:".$freeSlot;
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];

		break;
	case 'fortressgather':

		$acc = new Account(null, null, false, false);

		$acc->fortressGather($args[0]);

		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "fortressprice.fortressPrice(13):".$acc->getFortressPriceSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];

		break;
	case 'fortressenemy':
		
		$acc = new Account(null, null, false, false);
		
		if($args[0] == '1')
			$acc->newFortressEnemy(); // New enemy
		
		if($acc->data["enemyid"] == 0)
			$acc->newFortressEnemy(false); // New enemy (required) - without money
		
		
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		
		// Reusing playerlookat so we just need the id
		$ret[] = "otherplayer.playerlookat:{$acc->data['enemyid']}/0/65537/0/100/100/2/0/2/307/305/5/304/1/4/9/0/0/6/1/3/10/10/10/10/10/0/3/0/3/3/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/65537/2001/2/6/2/4/5/3/3/3/1/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/10005/0/0/0/0/0/2/6/0/0/0/123/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/";
		$ret[] = "Success:";


		break;
	case 'fortressattack':
		// Fortress Attack by Greg
		
		$q = $args[0]; // How much soldiers

		if($q < 1) {
			exit(); // Zero soldiers wtf
		}
		
		$acc = new Account(null, null, false, false);
		
		$ret[] = implode("&", $acc->fortressAttack($q));
		$ret[] = "Success:";
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		
		break;
	case 'fortressupgrade':

		$acc = new Account(null, null, false, false);

		$acc->fortressUnitUpgrade($args[0]);

		$ret[] = 'Success:';
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		$ret[] = 'unitprice.fortressPrice(3):'.$acc->getTrainUnitsPrice();
		$ret[] = 'upgradeprice.upgradePrice(3):'.$acc->getUpgradeUnitsPrice();
		$ret[] = 'unitlevel(4):'.$acc->getUnitLvls();

		break;
	case 'fortressbuildunitstart':

		$acc = new Account(null, null, false, false);

		$acc->fortressUnitTrain($args[0], $args[1]);

		$ret[] = 'Success:';
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();


		break;
	case 'fortressgroupbonusupgrade':

		// Upgrading this fortress bonus shit
		// Aka hall of knights upgrade
		// By Greg
	
		$acc = new Account(null, null, false, false);
		$guild = new Guild($playerGuild);

		$hokp = Fortress::getHallOfKnightsPrice($acc->data['hok']);
		
		if(!$hokp[4]) {
			exit("Success:");
		}
		
		if($hokp[2] > $acc->data['wood'] || $hokp[3] > $acc->data['stone']) {
			exit();
		}
		
		$acc->data['wood'] -= $hokp[2];
		$acc->data['stone'] -= $hokp[3];
		$acc->data['hok'] += 1;
		
		// Update datas
		$db->exec("UPDATE fortress SET wood = '{$acc->data['wood']}', stone = '{$acc->data['stone']}', hok = '{$acc->data['hok']}' WHERE owner = '$playerID'");

		$ret[] = 'Success:';
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		$ret[] = 'othergroup.groupSave:'.$guild->getGroupSave();
		$ret[] = "fortressGroupPrice.fortressPrice:".$acc->getHallOfKnightsPriceSave();

		break;
	case 'groupfound':
		//create guild
		//arg 0 name

		if(preg_match('/[^A-Za-z0-9 ]/', $args[0]))
			exit('Error:groupname is not available');
		if(strlen($args[0]) > 17)
			exit('Error:groupname is not available');
		if(strlen($args[0]) < 4)
			exit('Error:groupname is not available');
		if(is_numeric($args[0]))
			exit('Error:groupname is not available');

		$qry = $db->prepare('SELECT ID FROM guilds WHERE name = :name');
		$qry->bindParam(':name', $args[0]);
		$qry->execute();

		if($qry->rowCount() > 0)
			exit('Error:groupname is not available');

		
		$db->exec("INSERT INTO guilds(name) VALUES('$args[0]')");
		$guildID = $db->lastInsertId();

		$playerData = $db->query("SELECT name, lvl, silver FROM players WHERE ID = $playerID")->fetch(PDO::FETCH_ASSOC);

		if($playerData['silver'] < 1000)
			exit('Error:need more gold');
		$playerData['silver'] -= 1000;

		$db->exec("UPDATE players SET guild = $guildID, guild_rank = 1, silver = silver - 1000, event_trigger_count = 0, guild_fight = 0 WHERE ID = $playerID");

		$time = $GLOBALS["CURRTIME"];
		$message = '#in#'.$playerData['name'];

		$db->exec("INSERT INTO guildchat(guildID, playerID, message, time, chattime) VALUES($guildID, $playerID, '$message', $time, 1)");


		$ret[] = 'Success:';
		//443 = guild join date
		$ret[] = "#ownplayersave:2/$time/13/$playerData[silver]/435/$guildID/443/0";
		$ret[] = "timestamp:$time";

		$ret[] = 'owngroupsave.groupSave:'.Guild::getCreateGroupSave($guildID, $playerID, $playerData['lvl']);
		$ret[] = 'owngrouppotion.r:0,0,0,0,0,0,';	
		$ret[] = 'owngroupknights.r:0,';
		$ret[] = 'owngroupname.r:'.$args[0];
		$ret[] = 'owngroupdescription.s:';
		$ret[] = 'owngroupmember.r:'.$playerData['name'];
		$ret[] = 'owngrouprank:0';// rank = SELECT COUNT(ID) FROM guilds WHERE honor > 99
		$ret[] = "chathistory.s(5):$message////";
		$ret[] = "chattime:1";

		break;
	case 'playerarenafight':

		$qryArgs = [];

		//get opponent
		$qry = $db->prepare("SELECT players.*, guilds.portal AS guild_portal FROM players LEFT JOIN guilds ON players.guild = guilds.ID WHERE players.name = :name");
		$qry->bindParam(':name', $args[0]);
		$qry->execute();

		//player not found
		if($qry->rowCount() == 0)
			exit("Error:player not found");

		$opponentData = $qry->fetch(PDO::FETCH_ASSOC);

		$items = $db->query("SELECT * FROM items WHERE owner = ".$opponentData['ID']." AND slot BETWEEN 10 AND 19");
		$items = $items->fetchAll(PDO::FETCH_ASSOC);

		$opponent = new Player($opponentData, $items);

		//init account
		$acc = new Account(null, null, false, true);
		
		// Check if self fight
		if($acc->data['ID'] == $opponentData['ID'])
			exit('Error:');
		
		if($acc->data['arena_time'] > $GLOBALS["CURRTIME"]){
			if($acc->data['mush'] < 0)
				exit('Error:need more coins');
			else{
				$acc->data['mush']--;
				$qryArgs[] = 'mush = mush - 1';
			}
		}else{
			$acc->data['arena_time'] = $GLOBALS["CURRTIME"] + 600;
			$qryArgs[] = 'arena_time = '.$acc->data['arena_time'];
		}

		$ret[] = "fightheader.fighters:0/0/0/0/1/".$acc->getFightHeader().$opponent->getFightHeader();

		$simulation = new Simulation($acc, $opponent);
		$simulation->simulate();

		//max honor diff = 2k
		//formula: 100 + (opponent.honor - player.honor) / (max honor diff / 100)
		if($opponent->data['honor'] > $acc->data['honor'])
			$honor = min(200, 100 + round(($opponent->data['honor'] - $acc->data['honor']) / 20));
		else
			$honor = max(0, 100 + round(($opponent->data['honor'] - $acc->data['honor']) / 20));
		

		$rewardLog = [];
		for($i = 0; $i < 21; $i++)
			$rewardLog[] = 0;

		//album items before player save
		if($simulation->winnerID == $acc->data['ID']){

			// Set fights won + 1
			$acc->data['fightswon']++;
			$qryArgs[] = "fightswon = fightswon + 1";
			
			$rewardLog[0] = 1;

			$rewardLog[5] = $honor;
			$qryArgs[] = "honor = honor + $honor";

			$db->exec("UPDATE players SET honor = GREATEST(0, honor - $honor) WHERE ID = ".$opponent->data['ID']);

			if($acc->hasAlbum() && $acc->album->addItems($opponent->equip)){
				$acc->album->encode();
				// $db->exec("UPDATE players SET album_data = '".$acc->album->data."', album = ".$acc->album->count." WHERE ID = ".$acc->data['ID']);
				$qryArgs[] = 'album_data = "'.$acc->album->data.'", album = '.$acc->album->count;
				$ret[] = "scrapbook.r:".$acc->album->data;
				$acc->data['album'] = $acc->album->count;
			}
		}else{
			$honor = 200 - $honor;

			$rewardLog[5] = '-'.$honor;
			$qryArgs[] = "honor = GREATEST(0, honor - $honor)";

			$db->exec("UPDATE players SET honor = honor + $honor WHERE ID = ".$opponent->data['ID']);
		}

		//reset arena enemies
		for($i = 1; $i <= 3; $i++){
			$acc->data["arena_nme$i"] = 0;
			$qryArgs[] = "arena_nme$i = 0";
		}


		$db->exec("UPDATE players SET ".join(',', $qryArgs)." WHERE ID = $playerID");


		$ret[] = "fight.r:".$simulation->fightLog;
		$ret[] = "winnerid:".$simulation->winnerID;
		$ret[] = "Success:";
		$ret[] = "fightresult.battlereward:".join("/", $rewardLog)."/";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		// $ret[] = "combatloglist.s:178047146,Ragnarak,1,0,1453561071,0;1829371711,Mrozu,0,9,1453548804,0;2019179682,Arbuz,0,0,1453542838,0;608863024,Fort Szatana,1,2,1453536855,0;749062124,Schwarze Seelen,0,2,1453529461,0;1756257553,Smoke,1,9,1453527707,0;1430622921,KleinesGrÃ¼nesMÃ¤nnchen,1,0,1453498654,0;690391557,Fort Szatana,1,2,1453494628,0;209357733,Schwarze Seelen,0,2,1453484127,0;1069100244,Yufie,0,0,1453470289,0;1577088167,Fort Szatana,1,2,1453449488,0;1085402077,KeMi,0,0,1453448193,0;1891565546,Schwarze Seelen,0,2,1453439977,0;615269297,FaiX,0,0,1453410393,0;2118894081,Gnadenlos,1,2,1453405221,0;59208430,Mysticwoman,1,0,1453392274,0;937567384,Gnadenlos,1,2,1453355203,0;1508609115,Schwarze Seelen,0,2,1453352471,0;1891729205,spino,1,0,1453329887,0;35842795,Gnadenlos,1,2,1453313103,0;931279938,Schwarze Seelen,0,2,1453310626,0;1192275299,Yulivee,0,0,1453297521,0;1141435371,Aviro,0,0,1453280641,0;721157918,Yulivee,0,0,1453277920,0;2033375401,Yulivee,0,0,1453274653,0;1245941037,Gnadenlos,1,2,1453268569,0;1954219141,Schwarze Seelen,0,2,1453267929,0;1987180995,Petter,0,0,1453229942,0;932256980,Gnadenlos,1,2,1453225438,0;1134002502,Schwarze Seelen,0,2,1453225037,0;1463110198,Mysticwoman,1,0,1453209612,0;908422992,crpzh,1,0,1453207557,0;1239827316,Swordrain,0,0,1453206326,0;1135909002,Gnadenlos,1,2,1453180647,0;1367861018,crpzh,0,0,1453168359,0;811858250,X9Rambo6X,1,0,1453156913,0;866597934,Momochi,0,9,1453151581,0;1426181582,Terrorman79,1,0,1453148036,0;165539174,18,0,3,1453136739,0;1214503715,ChallEnGeRRR,0,0,1453131933,0;1220250895,zarondechanger,1,0,1453114143,0;1927290930,17,1,3,1453093834,0;784169800,GrupaAzoty,1,2,1453082945,0;173641481,crpzh,0,0,1453068685,0;2030313976,crpzh,0,0,1453053120,0;1703023368,Deathrix,1,0,1453052841,0;1171886111,16,1,3,1453051246,0;601901905,FaiX,1,0,1453038432,0;576197653,Jan,0,0,1453035790,0;1854324173,audia17,1,0,1453032401,0;";



		break;
	case 'wheeloffortune':
		
		// Wheel of fortune by Greg
		
		// Load basic datas
		$qry = $db->prepare("SELECT lvl, wheelcounts, newwheel, mush FROM players WHERE ssid = :ssid");
		$qry->bindParam(':ssid', $ssid);
		$qry->execute();
		$prpa = $qry->fetch(PDO::FETCH_ASSOC);
		$prlvl = $prpa['lvl'];
		
		if($prpa['wheelcounts'] >= 20) {
			exit("Error:need more gold"); // Anit-cheat lol
		}
		
		if($prpa['newwheel'] > $GLOBALS["CURRTIME"]) {
			$prpa['mush'] -= 1;
			if($prpa['mush'] < 0) {
				exit("Error:need more coins");
			}
		}
		
		$prpa['newwheel'] = strtotime('tomorrow');
		
		$db->exec("UPDATE players SET mush = mush - 1, wheelcounts = wheelcounts + 1, newwheel = '".$prpa['newwheel']."' WHERE ID = $playerID");
		
		$qry = $db->prepare("SELECT b0, wood, stone FROM fortress WHERE owner = :pid");
		$qry->bindParam(':pid', $playerID);
		$qry->execute();
		$fortdata = $qry->fetch(PDO::FETCH_ASSOC);
	
		$acc = new Account(null, null, false, false);
	
		// Lets start
		$r = rand(1, 5); // 1 = Gold, 2 = XP, 3 = Wood, 4 = Stone, 5 = Mushroom
		
		$more = rand(1, 3) == 3 ? TRUE : FALSE;
		
		// Where are
		$bonus = array(4, 2, 6, 8);
		$normal = array(9, 7, 1, 3);
		
		// Default values
		$waa = 0;
		$muu = 0;
		
		// Generate what and how much
		if($more) {
			// Give more
			switch($r) {
				case 1 :
					$waa = $bonus[0];
					$muu = Account::getQuestGold($prlvl, $goldbonus) * 4;
				break;
				case 2 :
					$waa = $bonus[1];
					$muu = $acc->generateQuest($prlvl, 0, 2)['exp'];
				break;
				case 3 :
					$waa = $bonus[2];
					$muu = intval( (Fortress::getGlobalMaxResources(1, $fortdata['b0']) / 7) * (rand(1000, 1100) / 1000) );
				break;
				case 4 :
					$waa = $bonus[3];
					$muu = intval( (Fortress::getGlobalMaxResources(2, $fortdata['b0']) / 7) * (rand(1000, 1100) / 1000) );
				break;
			}
		}else{
			// Give normal
			switch($r) {
				case 1 :
					$waa = $normal[0];
					$muu = Account::getQuestGold($prlvl, $goldbonus) * 2;
				break;
				case 2 :
					$waa = $normal[1];
					$muu = $acc->generateQuest($prlvl, 0, 1)['exp'];
				break;
				case 3 :
					$waa = $normal[2];
					$muu = intval( (Fortress::getGlobalMaxResources(1, $fortdata['b0']) / 7) * (rand(500, 550) / 1000) );
				break;
				case 4 :
					$waa = $normal[3];
					$muu = intval( (Fortress::getGlobalMaxResources(2, $fortdata['b0']) / 7) * (rand(500, 550) / 1000) );
				break;
			}
		}
		
		if($r == 5) {
			$waa = 0;
			$muu = 3;
		}
		
		// Give things to player 1 = Gold, 2 = XP, 3 = Wood, 4 = Stone, 5 = Mushroom
		switch($r) {
			case 1 :
				$db->exec("UPDATE players SET silver = silver + $muu WHERE ID = $playerID");
			break;
			case 2 :
				$acc->addExp($muu);
				$db->exec("UPDATE players SET exp = ".$acc->data['exp'].", lvl = ".$acc->data['lvl']." WHERE ID = ".$playerID);
			break;
			case 3 :
				$new_wood = $fortdata['wood'] + $muu;
				if( $new_wood > Fortress::getGlobalMaxResources(1, $fortdata['b0']) ) {
					$new_wood = Fortress::getGlobalMaxResources(1, $fortdata['b0']);
				}
				$db->exec("UPDATE fortress SET wood = $new_wood WHERE owner = $playerID");
			break;
			case 4 :
				$new_stone = $fortdata['stone'] + $muu;
				if( $new_stone > Fortress::getGlobalMaxResources(2, $fortdata['b0']) ) {
					$new_stone = Fortress::getGlobalMaxResources(2, $fortdata['b0']);
				}
				$db->exec("UPDATE fortress SET stone = $new_stone WHERE owner = $playerID");
			break;
			case 5 :
				$db->exec("UPDATE players SET mush = mush + $muu WHERE ID = $playerID");
			break;
		}
		
		$acc = new Account(null, null, false, false);
		
		$ret[] = "Success:";
		$ret[] = "wheelresult(2):{$waa}/{$muu}";
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		//$ret[] = '#ownplayersave:2/1659705906/14/12/545/2/772/3';
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		break;
	case 'playermessageview':

		//arg0 = messageID

		$qry = $db->prepare("SELECT ID, message FROM messages WHERE reciver = $playerID AND ID = :msgid");
		$qry->bindParam(':msgid', $args[0]);
		$qry->execute();
		$msg = $qry->fetch(PDO::FETCH_ASSOC);

		if($qry->rowCount()>0){
				
			$db->exec('UPDATE messages SET hasRead = true WHERE ID = '.$msg['ID']);

			$ret[] = 'messagetext.s:'.$msg['message'];
			$ret[] = 'Success:';
		}

		break;
	case 'playermessagedelete':

		if($args[0] == -1){
			$db->exec("DELETE FROM messages WHERE reciver = $playerID");

			exit('Success:&messagelist.r:');
		}else{
			$db->exec("DELETE FROM messages WHERE reciver = $playerID AND ID = $args[0]");

			$messages = $db->query("SELECT messages.ID, players.name, messages.hasRead, messages.topic, messages.time 
				FROM messages LEFT JOIN players ON messages.sender = players.ID WHERE reciver = $playerID ORDER BY time DESC");
			$messages = $messages->fetchAll(PDO::FETCH_ASSOC);
			// $msgs = [];
			// foreach($messages as $msg){
			// 	if(strlen($msg['name']) == 0)
			// 		$msg['name'] = 'admin';
			// 	$msgs[] = join(',', $msg);
			// }

			$ret[] = 'Success:';
			$ret[] = 'messagelist.r:'.Chat::formatMessages($messages);
		}



		break;
	case 'grouplookat':
		// arg 0 = guild name

		$qry = $db->prepare('SELECT ID, descr FROM guilds WHERE name = :name');
		$qry->bindParam(':name', $args[0]);
		$qry->execute();

		$fetch = $qry->fetch(PDO::FETCH_ASSOC);
		
		if($qry->rowCount() == 0)
			exit('Error:group not found');
		
		$guild = new Guild($fetch['ID']);

		$ret[] = 'Success:';
		$ret[] = 'othergroup.groupSave:'.$guild->getGroupSave();
		$ret[] = 'othergroupdescription.s:'.$fetch['descr'];
		$ret[] = 'othergroupname.r:'.$guild->data['name'];
		$ret[] = 'othergroupmember.s:'.$guild->getMemberList();
		$ret[] = 'othergrouprank:1';
		$ret[] = 'othergroupfightcost:'.Guild::getAttackCost($guild->data['base']);
		if(($oga = $guild->getOtherGroupAttack()) !== false)
			$ret[] = $oga;

		break;
	case 'groupsetofficer':

		$time = $GLOBALS["CURRTIME"];
		$names = $db->query("SELECT name, guild_rank FROM players WHERE ID = $playerID OR ID = $args[0] ORDER BY guild_rank")->fetchAll();

		$promote = $names[1]['guild_rank'] == 2? 3 : 2;
		
		if(!$db->exec("UPDATE players SET guild_rank = $promote WHERE ID = $args[0] AND guild = $playerGuild"))
			exit('Error:');

		$ftime = gmdate("H:i", $GLOBALS["CURRTIME"] + 3600);
		$name1 = $names[0]['name'];
		$name2 = $names[1]['name'];
		$message = "#ra#$ftime $name1#$promote#$name2";
		
		$chattime = Chat::chatInsert($message, $playerGuild, $playerID);
		
		$chat = Chat::getChat($playerGuild);

		//update player poll
		$db->exec("UPDATE players SET poll = $time WHERE ID = $playerID");

		$guild = new Guild($playerGuild);

		$ret[] = 'Success:';
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
		$ret[] = 'chathistory.s(5):'.Chat::formatChat($chat);
		$ret[] = "chattime:$chattime";

		break;
	case 'groupsetleader':
		$qry = $db->query("SELECT name, guild, guild_rank FROM players WHERE ID = $args[0] OR ID = $playerID ORDER BY guild_rank DESC")->fetchAll(PDO::FETCH_ASSOC);
		$newleader = $qry[0];
		$player = $qry[1];

		if($newleader['guild'] != $player['guild'] || $player['guild_rank'] != 1)
			exit();

		$db->exec("UPDATE players SET guild_rank = 2 WHERE ID = $playerID;UPDATE players SET guild_rank = 1 WHERE ID = $args[0]");

		$message = "#rv#$newleader[name]#$player[name]";

		$chattime = Chat::chatInsert($message, $playerGuild, $playerID);

		$guild = new Guild($playerGuild);

		$ret[] ='Success:';
		$ret[] ='owngroupsave.groupSave:'.$guild->getGroupSave();
		$ret[] ='owngroupmember.r:'.$guild->getMemberList();
		$ret[] ='chathistory.s(5):'.Chat::formatChat(Chat::getChat($playerGuild));
		$ret[] ="chattime:$chattime";

		break;
	case 'groupremovemember':

		$time = $GLOBALS["CURRTIME"];	


		if($playerID == $args[0]){
			$acc = new Account(null, null, false, false);

			//disband guild
			if($acc->data['guild_rank'] == 1){
				$db->exec("INSERT INTO messages(sender, reciver, time, topic, message) SELECT $playerID, players.ID, UNIX_TIMESTAMP(), '1', '{$acc->data['gname']}' FROM players WHERE guild = $playerGuild AND players.ID != $playerID;
					UPDATE players SET guild = 0, guild_rank = 3, guild_fight = 0 WHERE guild = $playerGuild;
					DELETE FROM guilds WHERE ID = $playerGuild;
					DELETE FROM guildchat WHERE guildID = $playerGuild;
					DELETE FROM guildinvites WHERE guildID = $playerGuild;
					DELETE FROM guildfights WHERE guildAttacker = $playerGuild OR guildDefender = $playerGuild LIMIT 2;");

			}else{
				$ftime = gmdate("H:i", $time + 3600);
				$name = $acc->data['name'];
				$message = "#ou#$ftime $name";

				Chat::chatInsert($message, $playerGuild, $playerID);
				$db->exec("UPDATE players SET guild = 0, guild_rank = 3, guild_fight = 0, event_trigger_count = 0 WHERE ID = $playerID");
			}

			$acc->data['guild'] = 0;
			$acc->data['guild_rank'] = 3;

			$ret[] = 'Success:';
			$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
			break;
		}

		//see if just removing invite
		if(!$db->exec("DELETE FROM guildinvites WHERE guildID = $playerGuild AND playerID = $args[0]")){

			//send message to the kicked player


			$db->exec("UPDATE players SET guild = 0, guild_rank = 3, guild_fight = 0, event_trigger_count = 0 WHERE ID = $args[0]");

			$ftime = gmdate("H:i", $time + 3600);
			$name = $db->query("SELECT name FROM players WHERE ID = $args[0]")->fetch(PDO::FETCH_ASSOC)['name'];
			$message = "#ou#$ftime $name";

			//get chat before updating poll
			$chattime = Chat::chatInsert($message, $playerGuild, $playerID);

			$chat = Chat::getChat($playerGuild);

			//update player poll
			$db->exec("UPDATE players SET poll = $time WHERE ID = $playerID");
			$ret[] = 'chathistory.s(5):'.Chat::formatChat($chat);
			$ret[] = "chattime:$chattime";
		}
		
		$guild = new Guild($playerGuild);

		$ret[] = 'Success:';
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();

		break;
	case 'groupinvitemember':

		$acc = new Account(null, null, false, false);
	
		$guild = new Guild($playerGuild);

		if(!$guild->hasFreeInvitePlace())
			exit('Error:group is full');

		$time = $GLOBALS["CURRTIME"];
		$gName = $guild->data['name'];

		$qry = $db->prepare("SELECT ID, friends, guild, noinv FROM players WHERE name = :name");
		$qry->bindParam(":name", $args[0]);
		$qry->execute();
		
		if($qry->rowCount() < 1)
			exit('Error:player not found');
		
		$fetchF = $qry->fetchAll()[0];
		
		$uID = $fetchF['ID'];
		
		if($acc->data['guild'] == $fetchF['guild'])
			exit("Success:"); // Fix by Greg
		
		if($acc::isUserIgnored($fetchF['friends'], $playerID))
			exit('Error:player not found');
		
		if($fetchF["noinv"] == 1)
			exit("Error:noinvite");
		
		//insert message - Greg fix
		//$qry = $db->prepare("INSERT INTO messages(sender, reciver, time, topic, message) SELECT $playerID, players.ID, $time, 5, '$gName' FROM players WHERE name = :name");
		$qry = $db->prepare("INSERT INTO messages(sender, reciver, time, topic, message) VALUES ($playerID, $uID, $time, 5, '$gName')");
		//$qry->bindParam(':name', $args[0]);
		$qry->execute();

		//insert invite
		$qry = $db->prepare("INSERT INTO guildinvites(guildID, playerID) SELECT $playerGuild, players.ID FROM players WHERE name = :name");
		$qry->bindParam(':name', $args[0]);
		$qry->execute();

		//update invites in guild object
		$invited = $db->query("SELECT players.ID, players.name, players.lvl FROM guildinvites LEFT JOIN players ON guildinvites.playerID = players.ID WHERE guildinvites.guildID = $playerGuild ORDER BY players.lvl DESC");
		$guild->invites = $invited->fetchAll(PDO::FETCH_ASSOC);

		

		$ret[] = 'Success:';
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
		$ret[] = 'owngroupmember.r:'.$guild->getMemberList();

		break;
	case 'groupinviteaccept':

		if($playerGuild != 0)
			exit('Error:must leave group first');

		$qry = $db->prepare("SELECT guilds.ID, event_trigger_count FROM guilds WHERE name = :name");
		$qry->bindParam(':name', $args[0]);
		$qry->execute();
		$obj = $qry->fetch(PDO::FETCH_ASSOC);
		$guildID = $obj['ID'];
		$etc = $obj['event_trigger_count'];

		if($qry->rowCount() == 0)
			exit('Error:group not found');

		if(!$db->exec("DELETE FROM guildinvites WHERE guildID = $guildID AND playerID = $playerID"))
			exit('Error:you are not invited');

		$acc = new Account(null, null, false, false);
		$acc->data['guild'] = $guildID;
		$acc->data['guild_rank'] = 3;
		$acc->data['event_trigger_count'] = $etc;

		//insert message
		$message = '#in#'.$acc->data['name'];
		$time = $GLOBALS["CURRTIME"];
		// $db->exec("INSERT INTO guildchat(guildID, playerID, message, time) VALUES($guildID, $playerID, '$message', $time)");
		$chattime = Chat::chatInsert($message, $guildID, $playerID);
		$chat = Chat::getChat($guildID);

		$db->exec("UPDATE players SET guild = $guildID, guild_rank = 3, event_trigger_count = $etc, guild_fight = 0 WHERE ID = $playerID");


		$guild = new Guild($guildID);

		$ret[] = 'Success:';
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
		$ret[] = "owngrouppotion.r:".$guild->getPotionData();
		$ret[] = "owngroupname.r:".$guild->data['name'];
		$ret[] = "owngroupdescription.s:".$guild->data['descr'];
		$ret[] = "owngroupmember.r:".$guild->getMemberList();
		$ret[] = "owngrouprank:".$guild->getRank();
		$ret[] = "chathistory.s(5):".Chat::formatChat($chat);
		$ret[] = "chattime:$chattime";

		break;
	case 'groupspendgold':
		
		// Greg's SQL Fix
		$args[0] = intval($args[0]);
		
		$time = $GLOBALS["CURRTIME"];

		$playerData = $db->query("SELECT players.name, players.silver, guilds.silver AS gsilver FROM players LEFT JOIN guilds ON players.guild = guilds.ID WHERE players.ID = $playerID")->fetch(PDO::FETCH_ASSOC);
		$playerSilver = $playerData['silver'];
		$name = $playerData['name'];
		$gSilver = $playerData['gsilver'];
		
		if($args[0] < 0)
			exit();
		
		if($playerSilver < $args[0])
			exit("Error:need more gold");
		if($gSilver >= 1000000000)
			exit('Error:group chest is full');
		
		if($gSilver + $args[0] > 1000000000)
			$args[0] = 1000000000 - $gSilver;

		$ftime = gmdate("H:i", $time + 3600);
		$message = "#dg#$ftime $name#$args[0]";

		//get chat before updating poll
		// $db->exec("INSERT INTO guildchat(guildID, playerID, message, time) VALUES($playerGuild, $playerID, '$message', $time)");

		// $chat = $db->query("SELECT players.name, guildchat.message, guildchat.time FROM guildchat LEFT JOIN players ON guildchat.playerID = players.ID 
		// 	WHERE guildchat.guildID = $playerGuild AND guildchat.time > $playerPoll ORDER BY guildchat.time DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
		$chattime = Chat::chatInsert($message, $playerGuild, $playerID);
		$chat = Chat::getChat($playerGuild);

		//update player and guild gold
		$db->exec("UPDATE players SET donatesilver = donatesilver + $args[0], silver = silver - $args[0], poll = $time WHERE ID = $playerID;UPDATE guilds SET silver = silver + $args[0] WHERE ID = $playerGuild;");

		$guild = new Guild($playerGuild);
		
		$playerSilver -= $args[0];

		$ret[] = 'Success:';
		$ret[] = "#ownplayersave:2/$time/13/$playerSilver";//.$acc->getPlayerSave();
		$ret[] = "timestamp:$time";
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
		$ret[] = 'chathistory.s(5):'.Chat::formatChat($chat);
		$ret[] = "chattime:$chattime";

		break;
	case 'groupspendcoins':

		// Greg's SQL Fix
		$args[0] = intval($args[0]);
	
		$time = $GLOBALS["CURRTIME"];

		$playerData = $db->query("SELECT players.name, players.mush, guilds.mush AS gmush FROM players LEFT JOIN guilds ON players.guild = guilds.ID WHERE players.ID = $playerID")->fetch(PDO::FETCH_ASSOC);
		$playerMush = $playerData['mush'];
		$name = $playerData['name'];
		$gMush = $playerData['gmush'];

		if($args[0] < 0)
			exit();
		
		if($playerMush < $args[0])
			exit("Error:need more coins");
		if($gMush >= 10000)
			exit('Error:group chest is full');

		if($gMush + $args[0] > 10000)
			$args[0] = 10000 - $gSilver;


		$ftime = gmdate("H:i", $time + 3600);
		$message = "#dm#$ftime $name#$args[0]";

		//get chat before updating poll
		// $db->exec("INSERT INTO guildchat(guildID, playerID, message, time) VALUES($playerGuild, $playerID, '$message', $time)");
		// $chat = $db->query("SELECT players.name, guildchat.message, guildchat.time FROM guildchat LEFT JOIN players ON guildchat.playerID = players.ID 
		// 	WHERE guildchat.guildID = $playerGuild AND guildchat.time > $playerPoll ORDER BY guildchat.time DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
		$chattime = Chat::chatInsert($message, $playerGuild, $playerID);
		$chat = Chat::getChat($playerGuild);


		//update player and guild gold
		$db->exec("UPDATE players SET donatemush = donatemush + $args[0], mush = mush - $args[0], poll = $time WHERE ID = $playerID;UPDATE guilds SET mush = mush + $args[0] WHERE ID = $playerGuild;");

		//load guild
		$guild = new Guild($playerGuild);

		//insert this in chat #dg#14:29 Pan Marcel#38500
		$time = $GLOBALS["CURRTIME"];

		
		$playerMush -= $args[0];

		$ret[] = 'Success:';
		$ret[] = "#ownplayersave:2/$time/14/$playerMush/437/$playerMush";//.$acc->getPlayerSave();
		$ret[] = "timestamp:$time";
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
		$ret[] = 'chathistory.s(5):'.Chat::formatChat($chat);
		$ret[] = "chattime:$chattime";

		// $message = "#dm#$time $name#$donation";


		break;
	case 'groupincreasebuilding':
		
		$player = $db->query("SELECT name, guild_rank FROM players WHERE ID = $playerID")->fetch(PDO::FETCH_ASSOC);

		if($player['guild_rank'] == 3)
			exit();

		$guild = new Guild($playerGuild);
		
		if($args[0] == '0') {
			// Catapult by Greg
			if($guild->data['mush'] < 5)
				exit('Error:need more coins');
			
			if($guild->data['catapult'] >= 3)
				exit();
			
			$guild->data['mush'] -= 5;
			$guild->data['catapult']++;
			
			$db->exec("UPDATE guilds SET catapult = catapult + 1, mush = mush - 5 WHERE ID = $playerGuild");
			
		}else{
			// Building
			$building = ['base', 'treasure', 'instructor'][$args[0] - 1];

			if($guild->data[$building] >= 50)
				exit();

			$cost = Guild::getGuildBuildingCost($guild->data[$building]);

			if($guild->data['silver'] < $cost['silver'])
				exit('Error:need more gold');

			if($guild->data['mush'] < $cost['mushroom'])
				exit('Error:need more coins');

			$guild->data[$building] ++;
			$guild->data['silver'] -= $cost['silver'];
			$guild->data['mush'] -= $cost['mushroom'];

			$db->exec("UPDATE guilds SET $building = $building + 1, silver = silver - $cost[silver], mush = mush - $cost[mushroom] WHERE ID = $playerGuild");
		}
			
		//log
		$time = $GLOBALS["CURRTIME"];
		$ftime = gmdate("H:i", $time + 3600);
		$message = "#bd#$ftime $player[name]#$args[0]";

		//get chat before updating poll
		// $db->exec("INSERT INTO guildchat(guildID, playerID, message, time) VALUES($playerGuild, $playerID, '$message', $time)");
		// $chat = $db->query("SELECT players.name, guildchat.message, guildchat.time FROM guildchat LEFT JOIN players ON guildchat.playerID = players.ID 
		// 	WHERE guildchat.guildID = $playerGuild AND guildchat.time > $playerPoll ORDER BY guildchat.time DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
		$chattime = Chat::chatInsert($message, $playerGuild, $playerID);
		$chat = Chat::getChat($playerGuild);

		//update player poll
		$db->exec("UPDATE players SET poll = $time WHERE ID = $playerID");


		$ret[] = 'Success:';
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
		$ret[] = 'chathistory.s(5):'.Chat::formatChat($chat);
		$ret[] = "chattime:$chattime";

		break;
	case 'groupchat':

		$time = $GLOBALS["CURRTIME"];
		
		$limit = 5;
		
		$ins = $args[0];
		
		if(substr($ins, 0, 2) == "--"){
			$spc = explode(" ", substr($ins, 2));
			
			$date = date("H:i", $GLOBALS["CURRTIME"]);
			
			switch($spc[0]){
				case "help":
					$ret[] = "chatwhisper.s:$date [System]:§ Commands";
					break;
				default:
					$ret[] = 'chatwhisper.s:';
			}
			
			$ret[] = 'Success:';
			
			exit(implode("&", $ret));
		}
		
		$chattime = Chat::chatInsert($ins, $playerGuild, $playerID, $playerPerm);
		$chat = Chat::getChat($playerGuild, $limit);

		$db->exec("UPDATE players SET poll = $time WHERE ID = $playerID");

		$ret[] = 'Success:';
		$ret[] = 'chathistory.s(5):'.Chat::formatChat($chat);
		$ret[] = "chattime:$chattime";


		break;
	case 'groupraiddeclare':
		// Raid
		$args[0] = 1000000;
	case 'groupattackdeclare':

		// SQL fix - Greg
		$args[0] = intval($args[0]);
		
		$guild = new Guild($playerGuild);
		$guild->declareFight($args[0], $playerID);



		$ret[] = 'Success:';
		// $ret[] = '#ownplayersave:508/1';
		$ret[] = 'timestamp:'.$GLOBALS["CURRTIME"];
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
		if($args[0] != 1000000){
			$aname = $db->query("SELECT name FROM guilds WHERE ID = $args[0]")->fetch(PDO::FETCH_ASSOC)['name'];
			$ret[] = "owngroupattack.r:".$aname;
		}

		break;
	case 'groupreadyattack':

		if($playerGuild == 0)
			exit();

		$fight = $db->query("SELECT guild_fight FROM players WHERE ID = $playerID")->fetch(PDO::FETCH_ASSOC)['guild_fight'];
		$fight++;
		if($fight != 1 && $fight != 3)
			exit();
		$db->exec("UPDATE players SET guild_fight = $fight WHERE ID = $playerID");

		$guild = new Guild($playerGuild);

		$ret[] = 'Success:';
		$ret[] = "#ownplayersave:508/$fight";
		$ret[] = 'timestamp:'.$GLOBALS["CURRTIME"];
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();

		break;
	case 'groupreadydefense':

		if($playerGuild == 0)
			exit();

		$fight = $db->query("SELECT guild_fight FROM players WHERE ID = $playerID")->fetch(PDO::FETCH_ASSOC)['guild_fight'];
		$fight += 2;
		if($fight != 2 && $fight != 3)
			exit();
		$db->exec("UPDATE players SET guild_fight = $fight WHERE ID = $playerID");

		$guild = new Guild($playerGuild);

		$ret[] = 'Success:';
		$ret[] = "#ownplayersave:508/$fight";
		$ret[] = 'timestamp:'.$GLOBALS["CURRTIME"];
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();


		break;
	case 'groupgetbattle':
		// Fixed by Greg
		
		$time = $GLOBALS["CURRTIME"];

		// //SEE IF SIM FIGHT and shiet
		$guildData = $db->query("SELECT event_trigger_count, dungeon, honor, name FROM guilds WHERE ID = $playerGuild")->fetch(PDO::FETCH_ASSOC);

		$fights = $db->query("SELECT guildfights.ID, guildfights.guildAttacker, g1.name AS attacker, guildfights.guildDefender, g2.name AS defender, time 
			FROM guildfights LEFT JOIN guilds AS g1 ON guildfights.guildAttacker = g1.ID LEFT JOIN guilds AS g2 ON guildfights.guildDefender = g2.ID 
			WHERE (guildfights.guildAttacker = $playerGuild OR guildfights.guildDefender = $playerGuild) AND time <= $time ORDER BY time ASC");
		

		// //if simfight
		if(($n = $fights->rowCount()) > 0){
			$fights = $fights->fetchAll(PDO::FETCH_ASSOC);

			//delete fight from db right away
			$db->exec("DELETE FROM guildfights WHERE guildAttacker = $playerGuild OR guildDefender = $playerGuild LIMIT 2;");

			//update trigger count, defenders guild too
			foreach($fights as $fight){
				if($fight['guildDefender'] != 1000000)
					$db->exec("UPDATE guilds SET event_trigger_count = event_trigger_count + 1 WHERE ID = $fight[guildAttacker] OR ID = $fight[guildDefender]");
				else
					$db->exec("UPDATE guilds SET event_trigger_count = event_trigger_count + 1 WHERE ID = $fight[guildAttacker]");
				$guildData['event_trigger_count']++;
			}

			//ALG: loop through fights incase there are 2, always ordered by time ascending. Display only the lastest, which is simulated as 2nd
			//		if the fight is in guildfights table, it hasn't been simulated, simulate and add to logs
			//		always simulate from the perspective of the attacker, defender can use GroupSimulation::reverseGuildFightLog() on displaying

			foreach($fights as $fight){

				//fight log, plain string, fuck it
				$fightLog = [];

				//get players
				$players = $db->query("SELECT players.*, guilds.portal AS guild_portal FROM players LEFT JOIN guilds ON players.guild = guilds.ID WHERE players.guild = $fight[guildAttacker] ORDER BY lvl ASC")->fetchAll(PDO::FETCH_ASSOC);
				$playerObjects = [];
				$items = $db->query("SELECT players.ID AS pid, items.* FROM items LEFT JOIN players ON items.owner = players.ID WHERE players.guild = $fight[guildAttacker] AND items.slot BETWEEN 10 AND 19");
				$items = $items->fetchAll(PDO::FETCH_GROUP);
				foreach($players as $player){
					$items_p = $items[$player['ID']] ?? [];
					@$playerObjects[] = new Player($player, $items_p);
				}

				//get opponents, see if guild raid
				if($fight['guildDefender'] == 1000000){
					//just get shit from guild data, only players from guild call this
					$opponentObjects = Monster::getGuildRaid($guildData['dungeon']);
				}else{
					//get other guild members here
					$opponents = $db->query("SELECT players.*, guilds.portal AS guild_portal, guilds.honor as ghonor FROM players LEFT JOIN guilds ON players.guild = guilds.ID WHERE players.guild = $fight[guildDefender] ORDER BY lvl ASC")->fetchAll(PDO::FETCH_ASSOC);
					$opponentObjects = [];
					$items = $db->query("SELECT players.ID as pid, items.* FROM items LEFT JOIN players ON items.owner = players.ID WHERE players.guild = $fight[guildDefender] AND items.slot BETWEEN 10 AND 19");
					$items = $items->fetchAll(PDO::FETCH_GROUP);
					foreach($opponents as $opponent){
						$items_p = $items[$opponent['ID']] ?? [];
						@$opponentObjects[] = new Player($opponent, $items_p);
					}
				}


				//simulate fight
				$simulation = new GroupSimulation($playerObjects, $opponentObjects);
				$simulation->simulate();

				//output logs
				for($i = 0; $i < count($simulation->simulations); $i++){
					$fightn = $i+1;
					$fightLog[] = "fightheader".$fightn.".fighters:3/0/0/1/1/".$simulation->fightHeaders[$i];
					$fightLog[] = "fight".$fightn.".r:".$simulation->simulations[$i]->fightLog;
					$fightLog[] = "winnerid".$fightn.".s:".$simulation->simulations[$i]->winnerID;
				}
				$fightLog[] = 'fightadditionalplayers.r:'.$simulation->getAdditionals();

				
				if($fight['guildDefender'] == 1000000){
					//insert raid chat logs
					$guildData['dungeon']++;
					if($simulation->win)
						$chatTime = Chat::chatInsert("#rplus#$guildData[dungeon]#", $playerGuild, 0);
					else
						$chatTime = Chat::chatInsert("#rminus#$guildData[dungeon]#", $playerGuild, 0);
				}else{
					//count out honor

					//max honor diff = 2k								
					//formula: 100 + (opponent.honor - player.honor) / (max honor diff / (max diff / 100))
					$attHonor = $guildData['honor'];
					$defHonor = $opponents[0]['ghonor'];

					if(abs($diff = $defHonor - $attHonor) < 2000)
						$honor = 100 + round($diff / 20);
					else
						$honor = 0;

					//update guilds honor
					if($simulation->win)
					{
						if($honor > $defHonor)
							$honor = $defHonor; // No farming on enemy guild pls
						
						$db->exec("UPDATE guilds SET honor = honor + $honor WHERE ID = $fight[guildAttacker]; UPDATE guilds SET honor = GREATEST(0, honor - $honor) WHERE ID = $fight[guildDefender]");
					}
					else
					{
						if($honor > $attHonor)
							$honor = $attHonor;	// No farming on enemy guild pls			
						
						$db->exec("UPDATE guilds SET honor = honor + $honor WHERE ID = $fight[guildDefender]; UPDATE guilds SET honor = GREATEST(0, honor - $honor) WHERE ID = $fight[guildAttacker]");
					}
					

					//need names
					// $attName = $players[0]['gname'];
					// $defName = $opponents[0]['gname'];
					$attName = $fight['attacker'];
					$defName = $fight['defender'];


					//fight logs, both guilds
					if($simulation->win){
						$chatTimeAtt = Chat::chatInsert("#aplus#$defName#$honor#", $fight['guildAttacker'], 0);
						$chatTimeDef = Chat::chatInsert("#dminus#$attName#$honor#", $fight['guildDefender'], 0);
					}else{
						$chatTimeAtt = Chat::chatInsert("#aminus#$defName#$honor#", $fight['guildAttacker'], 0);
						$chatTimeDef = Chat::chatInsert("#dplus#$attName#$honor#", $fight['guildDefender'], 0);
					}
					
				}

				//battlereward
				$battleReward = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
				
				// endLog
				$endLog = "";
				
				// Raid or normal
				if($fight['guildDefender'] == 1000000)
				{
					$endLog = "0";
					
					if($simulation->win)
					{
						$endLog .= "/1";
						$battleReward[0] = 1;
						//add reward for dungeon
						$db->exec("UPDATE guilds SET dungeon = dungeon + 1 WHERE ID = $fight[guildAttacker]");
					}
					else
						$endLog .= "/0";
					
					$endLog .= "/" . $guildData['dungeon'];
				}
				else
				{
					$endLog = "1";
					
					if($simulation->win)
						$winner = $fight['guildAttacker'];
					else
						$winner = $fight['guildDefender'];
					
					$endLog .= "/" . $winner . "/" . $honor;
					
					if($playerGuild == $winner)
					{
						$battleReward[0] = 1;
						$battleReward[6] = $honor;
					}
				}

				$endBattleData = 'fightresult.battlereward:'.join('/', $battleReward);
				
				$fightLog = join('&', $fightLog);
				//save logs to db
				$db->exec("INSERT INTO guildfightlogs(guildAttacker, guildDefender, log, endLog, time) VALUES($fight[guildAttacker], $fight[guildDefender], '$fightLog', '$endLog', $fight[time])");

				//UPDATE player guild_fight of both guilds | this now is temporary
				$db->exec("UPDATE players SET guild_fight = 0 WHERE guild = $fight[guildAttacker] OR guild = $fight[guildDefender]");
			}
			
			if($args[0] == 1)
			{
				$ret[] = $fightLog;
				$ret[] = $endBattleData;
			}

		}else if($args[0] == 1){
			//else if fight already simulated and player wants to see the fight, pull the logs ***** AND time > 0
			$log = $db->query("SELECT log, endLog FROM guildfightlogs WHERE (guildAttacker = $playerGuild OR guildDefender = $playerGuild) ORDER BY time DESC LIMIT 1");
			$fetch = $log->fetch(PDO::FETCH_ASSOC);
			$ret[] = $fetch['log'];
			
			// Get battlereward by Greg
			
			//battlereward
			$battleReward = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
			
			$endLog = explode("/", $fetch["endLog"]);
			
			if($endLog[0] == "0" && $endLog[1] == "1")
				$battleReward[0] = 1;
			else if($endLog[0] == "1" && $playerGuild == $endLog[1])
			{
				$battleReward[0] = 1;
				$battleReward[6] = $endLog[2];
			}
			
			$ret[] = 'fightresult.battlereward:'.join('/', $battleReward);
		}

		
		//update player trigger count
		$db->exec("UPDATE players SET event_trigger_count = $guildData[event_trigger_count] WHERE ID = $playerID");

		$guild = new Guild($playerGuild);

		$ret[] = 'Success:';
		$ret[] = '#ownplayersave:509/'.$guildData['event_trigger_count'];
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
		

		break;
	case "playerwhisper":
	case 'playermessagewhisper':
		// Whispering by Greg
		// 19:37 Brayght:§ Szia
		
		// Args: 0 = name, 1 = message
		
		
		if(strlen($args[1]) > 255)
			exit("Error:text too long");
		
		$acc = new Account(null, null, false, false);
		
		$args[0] = $acc::formatUser($args[0]);
		
		if(!Misc::isNameAllowed($args[0]))
			exit("Error:player not found");
		
		$msg = $acc->data['name'] . ':' . ($GLOBALS["CURRTIME"]) . ':' . $args[1];
		
		$sql = "SELECT ID, name, whisper, friends, guild FROM players WHERE name='{$args[0]}'";
		
		$qry = $db->query($sql);
		
		if($qry->rowCount() == 0)
			exit('Error:player not found');
		
		$fetch = $qry->fetchAll()[0];
		
		if($fetch["guild"] <= 0)
			exit("Error:player no guild");
		
		if(Account::isUserIgnored($fetch['friends'], $acc->data["ID"]))
			exit("Error:player not found"); // player is ignored
		
		if($fetch['whisper'] == '') {
			$whisper = $msg;
		}else{
			$whisper = $fetch['whisper'] . '/' . $msg;
		}
		
		$sql = "UPDATE players SET whisper = :whisper WHERE ID = '{$fetch['ID']}'";
		
		$qry = $db->prepare($sql);
		
		$qry->bindParam(":whisper", $whisper);
		
		$qry->execute();
		
		//$db->exec($sql);
		
		$ret[] = "Success:";
		break;
	case 'playercombatlogmark':
		// I don't need this
		$ret[] = 'Success:';
		break;
	case 'playerlastfightstore':
		$ret[] = 'Success:';
		break;
	case 'playercombatlogview':
		// View log by Greg
		
		$type = intval(substr($args[0], 0, 1));
		
		$id = intval(substr($args[0], 1));
		
		switch($type){
			case 2:
			case 3:
				$log = $db->query("SELECT log, endLog FROM guildfightlogs WHERE ((guildAttacker = $playerGuild OR guildDefender = $playerGuild) AND ID = $id) ORDER BY time DESC LIMIT 1");
				
				if($log->rowCount() == 0)
					exit("not found");
				
				$fetch = $log->fetch(PDO::FETCH_ASSOC);
				$ret[] = $fetch['log'];
				
				//battlereward
				$battleReward = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
				
				$endLog = explode("/", $fetch["endLog"]);
				
				if($endLog[0] == "0" && $endLog[1] == "1")
					$battleReward[0] = 1;
				else if($endLog[0] == "1" && $playerGuild == $endLog[1])
				{
					$battleReward[0] = 1;
					$battleReward[6] = $endLog[2];
				}
				
				$ret[] = 'fightresult.battlereward:'.join('/', $battleReward);
				
				$ret[] = "Success:";
				break;
			default:
				exit("Error:player not found");
		}
		break;
	case 'playersetdescription':

		$qry = $db->prepare("UPDATE players SET description = :description WHERE ID = $playerID");
		$qry->bindParam(':description', $args[0]);
		$qry->execute();

		$ret[] = 'Success:';
		break;
	case 'poll':
		$ret[] = 'Success:';
		$time = $GLOBALS["CURRTIME"];
		$update = false;


		//fortress unit train
		if($playerData['ut1'] > 0 || $playerData['ut2'] > 0 || $playerData['ut3'] > 0){
			$accUpdate = false;
			$qryArgs = [];
			for($i = 1; $i <= 3; $i++){
				if($playerData["ut$i"] > 0 && ($timeElapsed = $GLOBALS["CURRTIME"] - $playerData["uttime$i"]) > 600){

					$accUpdate = true;
					$units = floor($timeElapsed / 600);
					if($units > $playerData["ut$i"]) {
						// Important fix by Greg - no minus soldier training, no more than max soldiers
						$units = $playerData["ut$i"];
					}
					if($units < $playerData["ut$i"])
						$newtime = $playerData["uttime$i"] + $units * 600;
					else
						$newtime = 0;
					$qryArgs[] = "ut$i = ut$i - $units, u$i = u$i + $units, uttime$i = $newtime";
				}
			}

			if($accUpdate){
				$db->exec("UPDATE fortress SET ".join(',', $qryArgs)." WHERE owner = $playerID");
				$acc = new Account();

				$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
			}
		}else if($playerPoll < $time - 30){
			//check messages with 30 sec intervall, this is incase chat is polling to reduce the load somewhat significantly
			$messages = $db->query("SELECT COUNT(*) FROM messages WHERE reciver = $playerID AND messages.time > $playerPoll")->fetch(PDO::FETCH_ASSOC);
			$newMessages = $messages['COUNT(*)'];
			
			if($newMessages > 0){
				$update = true;

				$messages = $db->query("SELECT messages.ID, players.name, messages.hasRead, messages.topic, messages.time 
					FROM messages LEFT JOIN players ON messages.sender = players.ID WHERE messages.reciver = $playerID ORDER BY time DESC");
				$messages = $messages->fetchAll(PDO::FETCH_ASSOC);
				// $msgs = [];
				// foreach($messages as $msg){
				// 	if(strlen($msg['name']) == 0)
				// 		$msg['name'] = 'admin';
				// 	$msgs[] = join(',', $msg);
				// }

				$ret[] = 'messagelist.r:'.Chat::formatMessages($messages);
				$ret[] = '#ownplayersave.playerSave:434/'.$messages[0]['ID'];

				//check if kick from guild message, load playerdata
			}
			
			$whisper = $db->query("SELECT whisper FROM players WHERE ID = '$playerID'")->fetchAll()[0]['whisper'];
		
			// Whispers by Greg
			$whisper = Chat::formatWhispers($whisper);
			if($whisper != '') {
				$update = true;
				$ret[] = 'chatwhisper.s:'.$whisper;
				$db->exec("UPDATE players SET whisper = '' WHERE ID = '$playerID'");
			}
		}


		//types: 1 - quest, 2 - guild, 3 - raid, 4 - dungeon, 5 - tower, 6 - portal, 7 = gportal, 8 - fortressAtt, 9 - fortressDef, 10 - dark dungeons
		//ID,target name, win, type, time, marked
		// $ret[] = "combatloglist.s:1234,603,0,1,1456680807,0;1234,Nowakowscy,1,2,1456677448,0;";

		//guild chat and guild refreshing
		if($playerGuild > 0){
			// $chat = $db->query("SELECT players.name, guildchat.message, guildchat.time FROM guildchat LEFT JOIN players ON guildchat.playerID = players.ID 
			// 	WHERE guildchat.guildID = $playerGuild AND guildchat.time > $playerPoll ORDER BY guildchat.time DESC LIMIT 5");
			$chat = $db->query("SELECT Max(time) as newm, Max(chattime) as chattime FROM guildchat WHERE guildID = $playerGuild")->fetch(PDO::FETCH_ASSOC);
			
			if($chat['newm'] > $playerPoll){
				$update = true;
				$chattime = $chat['chattime'];
				$chat = Chat::getChat($playerGuild);
				$ret[] = 'chathistory.s(5):'.Chat::formatChat($chat);
				$ret[] = "chattime:$chattime";

				//IF system message, poll guild dataww
				if(Chat::containsSystemMessage($chat) || $playerPoll < $time - 10){
					$guild = new Guild($playerGuild);
					$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
					$ret[] = "owngrouppotion.r:".$guild->getPotionData();
					$ret[] = "owngroupname.r:".$guild->data['name'];
					//$ret[] = "owngroupdescription.s:".$guild->data['descr']; // TODO DESCR
					$ret[] = "owngroupmember.r:".$guild->getMemberList();
					$ret[] = "owngrouprank:".$guild->getRank();
					if(($oga = $guild->getOtherGroupAttack()) !== false)
						$ret[] = $oga;
				}

				//pull all shit above and bellow, namelist, potionlist, etc...
			}else if($playerPoll < $time - 30){
				//check time and load guild here every 60 sec or something?
				$update = true;
				$guild = new Guild($playerGuild);
				$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
				$ret[] = "owngrouppotion.r:".$guild->getPotionData();
				$ret[] = "owngroupname.r:".$guild->data['name'];
				//$ret[] = "owngroupdescription.s:".$guild->data['descr']; // TODO DESCR
				$ret[] = "owngroupmember.r:".$guild->getMemberList();
				$ret[] = "owngrouprank:".$guild->getRank();
				if(($oga = $guild->getOwnGroupAttack()) !== false)
					$ret[] = $oga;
			}
		}




		//LIMIT THIS, CHECK IF 1 MIN GONE BY, chat will rek dis
		//update variable is true if player has recieved a message or read chat, without it shit will keep pulling
		if($update || $playerPoll < $time - 90)
			$db->exec("UPDATE players SET poll = $time WHERE ID = $playerID");

		
		break;
		
	case 'groupsetdescription' :
		// By Greg
		// args 0 is description
			
			
		$qry = $db->prepare("SELECT guild_rank FROM players WHERE ID = '$playerID'");
		$qry->execute();
		$gr = $qry->fetch(PDO::FETCH_ASSOC)['guild_rank'];
		
		if($gr != 1) {
			exit('Error:');
		}
		
		$qry = $db->prepare("UPDATE guilds SET descr = :desc WHERE ID = '$playerGuild'");
		$qry->bindParam(":desc", $args[0]);
		$qry->execute();
		
		$ret[] = "Success:";
	break;
	
	case "playergamblegold" :
		// By Greg
		// args 0 is how much the player would gamble
		
		if($args[0] < 0)
			exit();
		
		if(rand(1, 3) == 3) {
			$gamble = $args[0];
		}else{
			$gamble = $args[0] * -1;
		}
		
		$qry = $db->prepare("SELECT silver FROM players WHERE ssid = :ssid");
		$qry->bindParam(':ssid', $ssid);
		$qry->execute();
		$prpa = $qry->fetch(PDO::FETCH_ASSOC);
		
		$prpa['silver'] = $prpa['silver'] + $gamble;
		
		if($prpa['silver'] < 0) {
			$prpa['silver'] = 0;
		}
		
		$db->exec("UPDATE players SET silver = '".$prpa['silver']."' WHERE ID = $playerID");
		
		$acc = new Account(null, null, false, false);
		
		$ret[] = "Success:";
		$ret[] = "gamblegoldvalue:".$gamble;
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
	break;
	case "playergamblecoins" :
		// By Greg
		// args 0 is how much the player would gamble
		
		if($args[0] < 0)
			exit();
		
		if(rand(1, 3) == 3) {
			$gamble = $args[0];
		}else{
			$gamble = $args[0] * -1;
		}
		
		$qry = $db->prepare("SELECT mush FROM players WHERE ssid = :ssid");
		$qry->bindParam(':ssid', $ssid);
		$qry->execute();
		$prpa = $qry->fetch(PDO::FETCH_ASSOC);
		
		$prpa['mush'] = $prpa['mush'] + $gamble;
		
		if($prpa['mush'] < 0) {
			$prpa['mush'] = 0;
		}
		
		$db->exec("UPDATE players SET mush = '".$prpa['mush']."' WHERE ID = $playerID");
		
		$acc = new Account(null, null, false, false);
		
		$ret[] = "Success:";
		$ret[] = "gamblecoinsvalue:".$gamble;
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
	break;
	case "playergoldframebuy" :
		// By Greg
		$qry = $db->prepare("SELECT mush, gframe FROM players WHERE ID = '$playerID'");
		$qry->bindParam(':ssid', $ssid);
		$qry->execute();
		$prpa = $qry->fetch(PDO::FETCH_ASSOC);
		if($prpa['gframe'] == 1) {
			exit('Error:');
		}
		
		$prpa['mush'] -= 1000;
		if($prpa['mush'] < 0) {
			exit('Error:need more coins');
		}
		
		$db->exec("UPDATE players SET gframe = '1', mush = '{$prpa['mush']}' WHERE ID = $playerID");
		
		$acc = new Account(null, null, false, false);
		
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
	break;
	case "petsgetstats" :
		// Pet stats by Greg
		
		$pet = intval($args[0]);
		
		$pD = new Pets();
		
		$pS = $pD->getPetStats($pet);
		
		if($pS[0] < 1)
			exit("Success:"); // Don't have pet wtf, kys tryhard hacker
		
		$ret[] = "ownpetsstats.petsStats:$pet/$pS[0]/50/$pS[2]/" . implode("/", $pS[3]) . "/0/0/0/0/0/4/4/0/";
		$ret[] = "Success:";
	break;
	case "petsdungeonfight" :
		// Dungeon fight of pets by Greg
		$acc = new Account(null, null, false, false); // New acc
		
		$place = $args[1]; // Place
		$mypet = $args[3]; // Fighting pet
		
		if($acc->data['dungeon_time'] > $GLOBALS["CURRTIME"]){ //if time not up
			if($acc->data['mush'] <= 0)
				exit("Error:need more coins");
			$acc->data['mush']--;
			$acc->data['dungeon_time'] = 0;
			$db->exec("UPDATE players SET mush = mush - 1, dungeon_time = 0 WHERE ID = ".$acc->data['ID']);
		}else{
			$acc->data['dungeon_time'] = $GLOBALS["CURRTIME"] + 3600;
			$db->exec("UPDATE players SET dungeon_time = '{$acc->data['dungeon_time']}' WHERE ID = ".$acc->data['ID']);
		}
		
		$freeSlot = $acc->getFreeBackpackSlot();
		if($freeSlot === false) {
			exit("Error:need a free slot");
		}
		
		$pD = new Pets($acc->data["pets"], $acc->data["petsFed"], $acc->data["petsDung"], $acc->data["petsPvP"], $acc->data["petsBest2"], null, $acc->data["blacksmith"], $acc->data["pethonor"]);
		
		$enemyLvl = 1 + ($pD->dungData[$place - 1] * 2);
		
		$enemyId = ($place - 1) * 20 + $pD->dungData[$place - 1] + 1;
		
		$me = $pD->getPetStats($mypet);
		if($me[0] == 0)
			exit("Error:");
		
		$enemy = $pD->getPetStats($enemyId, $enemyLvl);
		
		$meM = $pD->petToMonster($me);
		$meM->convertToMyPet();
		
		$enemyM = $pD->petToMonster($enemy);
		
		$ret[] = "fightheader.fighters:13/0/0/49/2/".$meM->getFightHeader().$enemyM->getFightHeader();

		$simulation = new Simulation($meM, $enemyM);
		$simulation->simulate();


		$ret[] = "fight.r:". $simulation->fightLog;
		$ret[] = "winnerid:". $simulation->winnerID;
		
		$rewardLog = [];
		for($i = 0; $i < 21; $i++)
			$rewardLog[] = 0;
		
		$win = $simulation->winnerID == 100000;
		
		// rewarding
		if($win)
		{
			// win true
			$rewardLog[0] = 1;
			
			// Give item
			$it['type'] = 16;
			$it['item_id'] = 30 + $place;
			$it['dmg_max'] = 0;
			$it['dmg_min'] = 0;
			$it['a1'] = 0;
			$it['a2'] = 0;
			$it['a3'] = 0;
			$it['a4'] = 0;
			$it['a5'] = 0;
			$it['a6'] = 0;
			$it['value_silver'] = 1;
			$it['value_mush'] = 0;
			
			$rewardLog[9] = $it['type'];
			$rewardLog[10] = $it['item_id'];
			$GLOBALS['acc']->insertItem($it, $GLOBALS['freeSlot']);
			
			// +1 pet dung lvl
			$pD->dungData[$place - 1]++;
			$db->exec("UPDATE players SET petsDung = '" . implode("/", $pD->dungData) . "' WHERE ssid = '$ssid'");
		}

		$ret[] = "fightresult.battlereward:".join("/", $rewardLog)."/";
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "ownpets.petsSave:" . $pD->getPetsSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
	break;
	case "petspvpfight" :
		$now = Misc::getNow();
		
		$acc = new Account(null, null, false, false); // New acc
		
		$freeSlot = $acc->getFreeBackpackSlot();
		if($freeSlot === false) {
			exit("Error:need a free slot");
		}
		
		$pclass = intval($args[2]) - 1; // Attack enemy with which pet class
		
		if($pclass < 0 || $pclass > 4)
			exit();
		
		$myPets = [];
		$enemyPets = [];
		
		// My Pet class
		$pD = new Pets($acc->data["pets"], $acc->data["petsFed"], $acc->data["petsDung"], $acc->data["petsPvP"], $acc->data["petsBest2"], null, $acc->data["blacksmith"], $acc->data["pethonor"]);
		
		if ($pD->pvpData[1][$pclass] == $now)
			exit();
		
		for($i = ($pclass * 20); $i < ($pclass * 20 + 20); $i++)
		{
			$stats = $pD->getPetStats($i + 1);
			
			if($stats[0] == 0)
				continue;
			
			$temp = $pD->petToMonster($stats);
			$temp->convertToMyPet();
			
			$myPets[] = $temp;
		}
		
		usort($myPets, function($a, $b) {
			return $a->lvl - $b->lvl;
		});
		
		// Enemy Pet class
		$epD = new Pets(null, null, null, null, $pD->pvpData[0][3], $pD->pvpData[0][0]);
		
		for($i = (($pD->pvpData[0][1] - 1) * 20); $i < (($pD->pvpData[0][1] - 1) * 20 + 20); $i++)
		{
			$stats = $epD->getPetStats($i + 1);
			
			if($stats[0] == 0)
				continue;
			
			$enemyPets[] = $epD->petToMonster($stats);
		}
		
		usort($enemyPets, function($a, $b) {
			return $a->lvl - $b->lvl;
		});
		
		//simulate fight
		$simulation = new GroupSimulation($myPets, $enemyPets);
		$simulation->simulate();
		
		//output logs
		for($i = 0; $i < count($simulation->simulations); $i++)
		{
			$fightn = $i+1;
			$fightLog[] = "fightheader".$fightn.".fighters:14/0/0/0/1/".$simulation->fightHeaders[$i];
			$fightLog[] = "fight".$fightn.".r:".$simulation->simulations[$i]->fightLog;
			$fightLog[] = "winnerid".$fightn.".s:".$simulation->simulations[$i]->winnerID;
		}
		
		$fightLog[] = 'fightadditionalplayers.r:'.$simulation->getAdditionals();
		
		$enemyFetch = $db->query("SELECT name, pethonor FROM players WHERE ID = " . $pD->pvpData[0][0])->fetchAll()[0];
		
		$enemyName = $enemyFetch["name"];
		
		$enemyHonor = $enemyFetch["pethonor"];
		
		$fightLog[] = 'fightgroups.r:100000,100001,' . $acc->data["name"] . ',' . $enemyName;
		
		$battleReward = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
		
		// Calculate honor
		if($enemyHonor > $acc->data['pethonor'])
			$honor = min(200, 100 + round(($enemyHonor - $acc->data['pethonor']) / 20));
		else
			$honor = max(0, 100 + round(($enemyHonor - $acc->data['pethonor']) / 20));
		
		if($simulation->win)
		{
			$battleReward[0] = 1;
			
			// Give item
			$it['type'] = 16;
			$it['item_id'] = 30 + $args[2];
			$it['dmg_max'] = 0;
			$it['dmg_min'] = 0;
			$it['a1'] = 0;
			$it['a2'] = 0;
			$it['a3'] = 0;
			$it['a4'] = 0;
			$it['a5'] = 0;
			$it['a6'] = 0;
			$it['value_silver'] = 1;
			$it['value_mush'] = 0;
			
			$battleReward[9] = $it['type'];
			$battleReward[10] = $it['item_id'];
			$acc->insertItem($it, $GLOBALS['freeSlot']);
			
			$battleReward[5] = $honor;
			
			$acc->data["pethonor"] += $honor;
			
			$enemyQuery = "pethonor = GREATEST(0, pethonor - $honor)";
		}
		else
		{
			$honor = 200 - $honor;
			
			$battleReward[5] = -1 * $honor;
			
			$acc->data["pethonor"] -= $honor;
			
			$enemyQuery = "pethonor = pethonor + $honor";
		}
		
		if($acc->data["pethonor"] < 0)
			$acc->data["pethonor"] = 0;
		
		$pD->honor = $acc->data["pethonor"];
		
		$db->exec("UPDATE players SET $enemyQuery WHERE ID = ".$pD->pvpData[0][0]);
		
		$pD->pvpData[1][$pclass] = $now;
		
		$petsPvP = json_encode($pD->pvpData);
		
		$qry = $db->prepare("UPDATE players SET petsPvp = :pvp, pethonor = :honor WHERE ID = $playerID");
		$qry->bindParam(":pvp", $petsPvP);
		$qry->bindParam(":honor", $acc->data["pethonor"]);
		$qry->execute();
		
		$pD->newPvPEnemy(); // Get a new enemy
		
		$ret[] = "Success:";
		$ret[] = implode("&", $fightLog);
		$ret[] = 'fightresult.battlereward:'.join('/', $battleReward);
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = "ownpets.petsSave:".$pD->getPetsSave();
		$ret[] = "petsdefensetype:" . $pD->pvpData[0][1];
	break;
	case 'playerfriendset' :
		// Set player friend status by Greg - v2
		// Args: 0 = ID, 1 = Status
		
		$id = intval($args[0]);
		$status = intval($args[1]);
		
		$acc = new Account(null, null, false, false); // New acc
		
		if($id == $acc->data["ID"])
			exit(); // WTF LEL
		
		$acc->setFriendStatus($id, $status);
		
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "friendlist.r:".$acc->friendList();
		$ret[] = "Success:";
	break;
	case 'petsgethalloffame' :
		if(strlen($args[1]) > 2){
			
			$args[1] = Account::formatUser($args[1]);

			$qry = $db->prepare('SELECT ID, pethonor FROM players WHERE name = :name');
			$qry->bindParam(':name', $args[1]);
			$qry->execute();

			if($qry->rowCount() == 0)
				exit('Error:player not found');

			$p = $qry->fetch(PDO::FETCH_ASSOC);

			$qry = $db->query('SELECT Count(*) AS rank FROM players WHERE pethonor > '.$p['pethonor'].' OR (pethonor = '.$p['pethonor'].' AND ID > '.$p['ID'].')');

			$args[0] = $qry->fetch(PDO::FETCH_ASSOC)['rank'];
		}

		$args[0] -= $args[2] + 1;
		if($args[0] < 0)
			$args[0] = 0;

		
		$qry = $db->prepare("SELECT players.name, guilds.name AS gname, players.petsDung, players.pethonor FROM players FORCE INDEX(pethonor) LEFT JOIN guilds ON players.guild = guilds.ID 
			WHERE players.pethonor >= 0 ORDER BY players.pethonor DESC, players.ID DESC LIMIT {$args[0]}, 30");
		$qry->execute();

		$players = $qry->fetchAll( PDO::FETCH_ASSOC );
		
		$list = [];
		for($i = 0; $i < count($players); $i++) {
			$players[$i]["petsDung"] = explode("/", $players[$i]["petsDung"])[5] ?? 0;
			
			$rank = $args[0] + $i + 1;
			$list[] = $rank.','.join(',', $players[$i]).",0";
		}
		
		//rank, name, gname, lvl, honor
		$ret[] = "RanklistPets.r:".join(';', $list);
		$ret[] = "Success:";
	break;
	case 'fortressgethalloffame' :
		// Another useless..........
		
		$sql = "SELECT * FROM players WHERE ssid = '$ssid'";
		$qry = $db->query($sql);
		$name = $qry->fetch(PDO::FETCH_ASSOC)["name"];
		
		$ret[] = "Ranklistfortress.r:1,kreszko1,,218,1417,0;2,gergc33,Fairy Tail,250,1417,0;3,Rara,Fáraók Céhe,238,1417,0;4,Disturbed,,249,1416,0;5,Drub,,248,1415,0;6,Vasznpszvotsz,,228,1413,0;7,Essneki,,266,1413,0;1598,stark2015,EmErGiNgS,226,1413,0;8,Lazuman,,244,1413,0;9,Piskota,,226,1413,0;10,Harcicickány,,254,1412,0;11,DeepDildoHentaiMonster,Magyar Betyár Sereg,209,1412,0;12,oldtibi,Kitaszítottak,290,1412,0;1604,EthanW,rablok céh,257,1412,0;1605,AkcioooK,,258,1412,0;1606," . $name . ",NOT WORKING,285,1412,0;1607,adrianbuzi,SCBP,210,1411,0;1608,ladymoon,Shadow Sword,249,1411,0;1609,benedek11,,240,1411,0;1610,Szaura,,249,1410,0;1611,kompusz123,,231,1410,0;1612,Tóbi,We are the best,267,1410,0;1613,laczkovics123,,249,1410,0;1614,Sir Andreas,,258,1410,0;1615,Mazs,Kekuravtyemuj,211,1409,0;1616,A20,awful,227,1409,0;1617,Khyra,,238,1409,0;1618,Lanselot,,239,1408,0;1619,Bridget01,Angyalok És Ördögök,253,1407,0;1620,IKIHUN,,229,1407,0;1621,varázslatos márkócska,EmErGiNgS,223,1407,0;&Success:";
	break;
	case "playersetnogroupinvite":
		// No group invite by Greg
		
		$val = $args[0] == 1 ? 1 : 0;
		
		$db->exec("UPDATE players SET noinv = $val WHERE ID = $playerID");
		
		$acc = new Account(null, null, false, false); // New acc
		
		
		
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		
		break;
	case 'groupmessagesendaround':
		// Guild mail by Greg
		
		$subject = $args[0];
		$body = $args[1];
		
		$time = $GLOBALS["CURRTIME"];
		
		$qry = $db->query("SELECT ID FROM players WHERE guild = $playerGuild");
		
		foreach($qry->fetchAll() as $row){
			$reciver = $row['ID'];
			
			$qry = $db->prepare("INSERT INTO messages(sender, reciver, time, topic, message) VALUES($playerID, $reciver, $time, :topic, :message)");
			$qry->bindParam(':topic', $subject);
			$qry->bindParam(':message', $body);
			$qry->execute();
		}
	
		exit("Success:");
	
		break;
	case "underworldgather":
		// Gather by Greg
		
		$acc = new Account(null, null, true, true);
		
		$uw = new Underworld($acc->data["ID"]);
		
		if($args[0] == "1"){
			// Gold mine
			
			$added = $uw->gatherGold();
			
			$acc->data["silver"] += $added;
		}
		else if($args[0] == "2"){
			// Soul extractor
			
			$uw->gatherSoul();
		}
		else if($args[0] == "3"){
			// Time machine
			
			$uw->gatherTimeMachine();
		}
		
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = "Success:";
		$ret[] = "owntower.towerSave:".$acc->getTowerSave($uw);
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		
		break;
	
	case "underworldbuildstart":
		// Build start by Greg
		
		$acc = new Account(null, null, true, false);
		
		$uw = new Underworld($acc->data["ID"]);
		
		$uw->buildStart($args[0]);
		
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = "Success:";
		$ret[] = "owntower.towerSave:".$acc->getTowerSave($uw);
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		
		break;
	
	case "underworldbuildstop":
		// Build stop by Greg
		// TODO: Give back resources
		
		$acc = new Account(null, null, true, false);
		
		$uw = new Underworld($acc->data["ID"]);
		
		$uw->buildStop();
		
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = "Success:";
		$ret[] = "owntower.towerSave:".$acc->getTowerSave($uw);
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		
		break;
		
	case "underworldbuildfinished":
		// Build finish by Greg
		
		$acc = new Account(null, null, true, false);
		
		$uw = new Underworld($acc->data["ID"]);
		
		$uw->buildFinish();
		
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = "Success:";
		$ret[] = "owntower.towerSave:".$acc->getTowerSave($uw);
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "underworldprice.underworldPrice(10):".$uw->getBuildingPrices();
		$ret[] = "underworldupgradeprice.underworldupgradePrice(3):".$uw->getUnitUpgradePrices();
		
		break;
		
	case "underworldupgradeunit":
		// Upgrading unit by Greg
		
		$acc = new Account(null, null, true, false);
		
		$uw = new Underworld($acc->data["ID"]);
		
		$uw->upgradeUnit(intval($args[0]));
		
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = "Success:";
		$ret[] = "owntower.towerSave:".$acc->getTowerSave($uw);
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "underworldprice.underworldPrice(10):".$uw->getBuildingPrices();
		$ret[] = "underworldupgradeprice.underworldupgradePrice(3):".$uw->getUnitUpgradePrices();
		
		break;
		
	case "underworldattack":
		// Attack by Greg
		
		$acc = new Account(null, null, true, false);
		
		$uw = new Underworld($acc->data["ID"]);
		
		$uw->attack(intval($args[0]));
		
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = "Success:";
		$ret[] = "owntower.towerSave:".$acc->getTowerSave($uw);
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "underworldprice.underworldPrice(10):".$uw->getBuildingPrices();
		$ret[] = "underworldupgradeprice.underworldupgradePrice(3):".$uw->getUnitUpgradePrices();
	
		break;
		
	default:
	
		if($sandbox)
		{
			var_dump($act);
			var_dump($args);
			$ret[] = "";
			$ret[] = "Error: not implemented";
		}
		else
			$ret[] = "Success:";


	break;
}

echo join("&", $ret);
?>