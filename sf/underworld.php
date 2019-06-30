<?php

// Underworld v1.3 by Greg

class Underworld{
	public $owner;
	public $data;
	public $haveIt = false;
	
	function __construct($owner = 0, $data = []){
		if(count($data) == 0)
			$data = Underworld::getData($owner);
		
		$this->data = $data;
		
		if(count($this->data) > 0){
			$this->haveIt = true;
			
			$this->owner = $this->data["owner"];
		}
	}
	
	public static function getData($owner){
		$qry = $GLOBALS["db"]->query("SELECT * FROM underworld WHERE owner = " . intval($owner));
		
		if($qry->rowCount() == 0)
			return [];
		
		$data = $qry->fetch(PDO::FETCH_ASSOC);
		
		return $data;
	}
	
	public function getSave(){
		$ret = [];
		
		for($i = 0; $i < 29; $i++)
			$ret[] = 0;
		
		if(!$this->haveIt)
			return implode("/", $ret);
		
		$fetch = $this->data;
	
		// Building levels
		$ret[1] = $fetch["heart"];
		$ret[2] = $fetch["gate"];
		$ret[6] = $fetch["torture"];
		$ret[10] = $fetch["keeper"];
		$ret[4] = $fetch["extractor"];
		$ret[5] = $fetch["goblin"];
		$ret[7] = $fetch["gladiator"];
		$ret[8] = $fetch["troll"];
		$ret[3] = $fetch["gold"];
		$ret[9] = $fetch["time"];
		
		$ret[11] = $fetch["soul"]; // Current souls
		$ret[14] = $this->globalSoulLimit(); // Max souls
		
		// Last gather
		$ret[20] = max($fetch["gathersoul"], $fetch["gathergold"]);
		
		// Soul extractor
		if($this->data["extractor"] > 0){
			$ret[12] = $this->getCurrentSoul(); // Current
			$ret[13] = $this->maxResources(1); // Max
			$ret[16] = $this->getHourlySoul();
		}
		
		// Gold mine
		if($this->data["gold"] > 0){
			$ret[17] = $this->getCurrentGold();
			$ret[18] = $this->maxResources(2);
			$ret[19] = $this->getHourlyGold();
		}
		
		// Time machine
		if($this->data["time"] > 0){
			$ret[26] = $this->data["timeamount"]; // Current
			$ret[27] = $this->maxResources(3); // Max
			$ret[28] = $this->getDailyThirst(); // Daily
		}
		
		// Battles
		$ret[25] = 0; // Done
		
		// Max lelkek ezen a szinten?
		$ret[24] = 0;
		
		//Building
		$ret[21] = $fetch["build_id"];
		$ret[22] = $fetch["build_end"];
		$ret[23] = $fetch["build_start"];
		
		return implode("/", $ret);
	}
	
	public function getEntityStats($id){
		$up = $this->data["u".$id."lvl"];
		
		$which = ["goblin", "troll", "keeper"][$id - 1];
		
		$lvl = floor(1 + round($up * 1.7) * (1 + $this->data[$which] / 100));
		
		if($id == 1)
			$stat = 10 + floor($up * 13.5);
		else if($id == 2)
			$stat = 10 + floor($up * 19.9);
		else if($id == 3)
			$stat = 10 + floor($up * 77.8);
		
		return [$lvl, $stat];
	}
	
	private function getEntityDamage($lvl, $stat, $multi){
		$dmg = 1 + floor($multi * $lvl);
		$minmax = 2 + floor($lvl * 35 / 100);

		$add = 1 + $stat / 10;
		
		$dmg_min = floor(($dmg - $minmax) * $add);
		$dmg_max = floor(($dmg + $minmax) * $add);
		
		return [$dmg_min, $dmg_max];
	}
	
	private function getEntityHP($stat, $lvl){
		$hp = round($stat * 4 * ($lvl + 1));
		
		return $hp;
	}
	
	public function getCurrentSoul(){
		$curr = ($GLOBALS["CURRTIME"] - $this->data["gathersoul"]) * $this->getHourlySoul() / 3600;
		
		$max = $this->maxResources(1);
		
		if($curr > $max)
			return $max;
		else
			return floor($curr);
	}

	public function getCurrentGold(){
		$curr = ($GLOBALS["CURRTIME"] - $this->data["gathergold"]) * $this->getHourlyGold() / 3600;
		
		$max = $this->maxResources(2);
		
		if($curr > $max)
			return $max;
		else
			return floor($curr);
	}
	
	public function addLeftThirst($left){
		if($left == 0)
			return; // Only execute this function on purpose
		
		$daily = $this->getDailyThirst();
		
		$daily += floor($daily / 4);
		
		if($left > $daily)
			$left = $daily;
		
		$this->data["timeamount"] += $left;
		
		$max = $this->maxResources(3);
		
		if($this->data["timeamount"] > $max)
			$this->data["timeamount"] = $max;
		
		$qry = $GLOBALS["db"]->prepare("UPDATE underworld SET timeamount = :time WHERE owner = :owner");
		$qry->bindParam(":time", $this->data["timeamount"]);
		$qry->bindParam(":owner", $this->owner);
		$qry->execute();
	}
	
	public function getDailyThirst(){
		$ret = [
			4,
			8,
			12,
			16,
			20,
			24,
			28,
			32,
			36,
			40,
			48,
			56,
			64,
			72,
			80
		];
		
		return $ret[$this->data["time"] - 1] ?? 0;
	}
	
	public function buildStart($id){
		if($this->data["build_id"] != 0)
			exit();
		
		$buildnames = $this->getBuildingNames();
		
		$buildname = $buildnames[$id - 1] ?? "";
		
		if($buildname == "")
			exit();
		
		$lvl = $this->data[$buildname];
		
		if($lvl >= 15)
			exit();
		
		$time = $this->getBuildingTime($lvl);
			
		$money = $this->getBuildingGoldSoul($id, ($lvl + 1));
		
		if($GLOBALS["acc"]->data["silver"] < $money[0] || $this->data["soul"] < $money[1])
			exit('Error:need more soul');
		
		$this->data["build_id"] = $id;
		$this->data["build_start"] = $GLOBALS["CURRTIME"];
		$this->data["build_end"] = $this->data["build_start"] + $time;
		
		$GLOBALS["acc"]->data["silver"] -= $money[0];
		$this->data["soul"] -= $money[1];
		
		$qry = $GLOBALS["db"]->prepare("UPDATE underworld SET build_id = :bid, build_start = :bstart, build_end = :bend, soul = :soul WHERE owner = :owner;UPDATE players SET silver = :silver WHERE ID = :id");
		$qry->bindParam(":bid", $this->data["build_id"]);
		$qry->bindParam(":bstart", $this->data["build_start"]);
		$qry->bindParam(":bend", $this->data["build_end"]);
		$qry->bindParam(":soul", $this->data["soul"]);
		$qry->bindParam(":owner", $this->owner);
		$qry->bindParam(":silver", $GLOBALS["acc"]->data["silver"]);
		$qry->bindParam(":id", $GLOBALS["acc"]->data["ID"]);
		$qry->execute();
	}
	
	public function buildStop(){
		$this->data["build_id"] = 0;
		$this->data["build_start"] = 0;
		$this->data["build_end"] = 0;
		
		$qry = $GLOBALS["db"]->prepare("UPDATE underworld SET build_id = 0, build_start = 0, build_end = 0 WHERE owner = :owner");
		$qry->bindParam(":owner", $this->owner);
		$qry->execute();
	}
	
	public function buildFinish(){
		global $acc;
		
		if($this->data["build_id"] == 0)
			exit();
		
		$mushcost = 0;
		
		if(($this->data["build_end"] - 2) > $GLOBALS["CURRTIME"])
			$mushcost = ceil(($this->data['build_end'] - $GLOBALS["CURRTIME"]) / 600);
		
		if($mushcost > $acc->data["mush"])
			exit("Error:need more coins");
		
		$acc->data["mush"] -= $mushcost;
		
		$id = $this->data["build_id"];
		
		$buildname = $this->getBuildingNames()[$id - 1];
		
		$this->data[$buildname]++;
		
		$addi = "";
		
		if($id == 3){
			$this->data["gathergold"] = $GLOBALS["CURRTIME"];
			$addi = ", gathergold = " . $this->data["gathergold"];
		}
		
		if($id == 4){
			$this->data["gathersoul"] = $GLOBALS["CURRTIME"];
			$addi = ", gathersoul = " . $this->data["gathersoul"];
		}
		
		$qry = $GLOBALS["db"]->prepare("UPDATE underworld SET $buildname = :lvl{$addi} WHERE owner = :owner;UPDATE players SET mush = :mush WHERE ID = :id");
		$qry->bindParam(":lvl", $this->data[$buildname]);
		$qry->bindParam(":owner", $this->owner);
		$qry->bindParam(":mush", $acc->data["mush"]);
		$qry->bindParam(":id", $acc->data["ID"]);
		$qry->execute();
		$qry = "";
		
		$this->buildStop();
	}
	
	public function gatherGold(){
		$curr = $this->getCurrentGold();
		
		$qry = $GLOBALS["db"]->prepare("UPDATE players SET silver = silver + $curr WHERE ID = :id");
		$qry->bindParam(":id", $this->owner);
		$qry->execute();
		
		$this->data["gathergold"] = $GLOBALS["CURRTIME"];
		
		$qry = $GLOBALS["db"]->prepare("UPDATE underworld SET gathergold = :gatgold WHERE owner = :owner");
		$qry->bindParam(":gatgold", $this->data["gathergold"]);
		$qry->bindParam(":owner", $this->owner);
		$qry->execute();
	
		return $curr;
	}

	public function gatherSoul(){
		$curr = $this->getCurrentSoul();
		
		$this->data["soul"] += $curr;
		
		$globallimit = $this->globalSoulLimit();
		
		if($this->data["soul"] > $globallimit)
			$this->data["soul"] = $globallimit;
		
		$this->data["gathersoul"] = $GLOBALS["CURRTIME"];
		
		$qry = $GLOBALS["db"]->prepare("UPDATE underworld SET soul = :soul, gathersoul = :gather WHERE owner = :owner");
		$qry->bindParam(":gather", $this->data["gathersoul"]);
		$qry->bindParam(":soul", $this->data["soul"]);
		$qry->bindParam(":owner", $this->owner);
		$qry->execute();
	}
	
	public function getHourlyGold(){
		$lvl = 200 + ($this->data["gold"] * 15);
		
		$e = (intval( $lvl * $lvl * ($lvl * 2 + 6.5) ) / 9 * 1 + intval( $lvl * 1 + 2 )) * $GLOBALS["goldbonus"];
		
		return round($e / 10);
	}
	
	public function getHourlySoul(){
		$ret = 700;
		
		for($i = 0; $i < $this->data["extractor"]; $i++){
			$ret *= 1.9;
		}
		
		return round($ret);
	}
	
	public function getBuildingNames(){
		$buildnames = [
			"heart",
			"gate",
			"gold",
			"extractor",
			"goblin",
			"torture",
			"gladiator",
			"troll",
			"time",
			"keeper"
		];
			
		return $buildnames;
	}
	
	public function getBuildingPrices(){
		$buildnames = $this->getBuildingNames();
		
		$ret = [];
		
		for($i = 0; $i < 10; $i++){
			$currbldname = $buildnames[$i];
			
			$ret[] = $this->getBuildingTime($this->data[$currbldname]);
			
			$money = $this->getBuildingGoldSoul(($i + 1), ($this->data[$currbldname] + 1));
			
			$ret[] = $money[0];
			$ret[] = $money[1];
		}
		
		$ret[] = "";
		
		return implode("/", $ret);
	}
	
	public function getUnitUpgradePrices(){
		$ids = [7, 8, 26];
		
		$ret = [];
		
		for($i = 1; $i <= 3; $i++){
			$ret[] = $ids[$i - 1];
			
			$price = $this->getUnitUpgradePrice($i);
			
			$ret[] = $price[0];
			$ret[] = $price[1];
		}
		
		$ret[] = "";
		
		return implode("/", $ret);
	}
	
	public function getUnitUpgradePrice($id){
		$lvl = $this->data["u".$id."lvl"];
		
		return [
			$this->getUnitUpgradeGold($id, $lvl),
			$this->getUnitUpgradeSoul($id, $lvl)
		];
	}
	
	public function upgradeUnit($id){
		global $acc;
		
		if($id < 1 || $id > 3)
			exit();
		
		$clmn_name = "u".$id."lvl";
		
		$lvl = &$this->data[$clmn_name];
		
		$price = $this->getUnitUpgradePrice($id);
		
		$acc->data["silver"] -= $price[0];
		$this->data["soul"] -= $price[1];
		
		if($acc->data["silver"] < 0)
			exit("Error:need more gold");
		
		if($this->data["soul"] < 0)
			exit("Error:need more souls");
		
		$lvl++;
		
		$qry = $GLOBALS["db"]->prepare("UPDATE players SET silver = :silver WHERE ID = :pid;UPDATE underworld SET soul = :soul, $clmn_name = :lvl WHERE owner = :owner");
		$qry->bindParam(":silver", $acc->data["silver"]);
		$qry->bindParam(":pid", $acc->data["ID"]);
		$qry->bindParam(":soul", $this->data["soul"]);
		$qry->bindParam(":lvl", $lvl);
		$qry->bindParam(":owner", $this->owner);
		$qry->execute();
	}
	
	public function maxResources($id){
		if($id == 2){
			$lvl = $this->data["gold"];
			
			$ret = 11210 * 100;
			
			for($i = 0; $i < $lvl; $i++)
				$ret *= 1.2;
			
			return floor($ret);
		}
		else if($id == 1){
			$lvl = $this->data["extractor"];
			
			$ret = 1500;
			
			$multi = 1.4;
			
			if($lvl > 9)
				$multi = 1.8;
			
			for($i = 0; $i < $lvl; $i++){
				$ret *= $multi;
			}
			
			return floor($ret);
		}
		else if($id == 3){
			$time = $this->data["time"];
			
			$out = 0;
			
			for($i = 0; $i < $time; $i++){
				$out += 100;
				
				if ($i > 9)
					$out += 100;
			}
			
			return $out;
		}
	}
	
	public function globalSoulLimit(){
		$lvl = $this->data["heart"];
		
		$ret = 1500;
		
		$multi = 1.4;
		
		if($lvl > 9)
			$multi = 1.8;
		
		for($i = 0; $i < $lvl; $i++){
			$ret *= $multi;
		}
		
		$ret = floor($ret / 1000) * 10000;
		
		return $ret;
	}
	
	private function generateTimeEnemy($length){
		global $acc;
		
		$xpbonus = $GLOBALS['xpbonus'];
		$xpGenVersion = $GLOBALS['xpGenVersion'];
		
		$exp = Account::getQuestExp($acc->data["lvl"], $xpbonus, $xpGenVersion, ($length / 5));
		$exp = round($exp / 20);
		
		$realStats = $acc->getRealStats();
		
		$w = $realStats["wit"];
		$weap = $acc->getWeapon();
		
		$prim = "dex";
		
		switch($acc->data["class"]){
			case 1: $prim = "str"; break;
			case 2: $prim = "intel"; break;
		}
		
		$prim = $realStats[$prim] * ((15 + $length) / 100);
		
		$dmg_min = round($weap->dmg_min * (1 + ($prim / 10)));
		$dmg_max = round($weap->dmg_max * (1 + ($prim / 10)));
		
		$hp = round($w * 4 * ($acc->data['lvl'] + 1) / 25);
		
		$monsterID = rand(1, 163);

		$monster = new Monster($acc->data['lvl'], 2, $realStats["str"], $realStats["dex"], $realStats["intel"], $realStats["wit"], $realStats["luck"], $dmg_min, $dmg_max, $hp, 1, -$monsterID, 1, $weap->id);
	
		return [$exp, $monster];
	}
	
	public function gatherTimeMachine(){
		// v2 - like original SF
		
		global $acc, $ret, $db;
		
		if($this->data["timeamount"] == 0)
			exit();
		
		$allxp = 0;
		$monsters = [];
		
		$time = $this->data["timeamount"];
		
		while($time > 0 && count($monsters) < 25){
			$length = rand(1, 2) * 5;
			
			if($time < 20)
				$length = $time;
			
			$round = $this->generateTimeEnemy($length);
			
			$allxp += $round[0];
			$monsters[] = $round[1];
			
			$time -= $length;
		}
		
		$simulation = new GroupSimulation([$acc], $monsters);
		$simulation->simulate();
		
		for($i = 0; $i < count($simulation->simulations); $i++){
			$fight = $i+1;
			$ret[] = "fightheader".$fight.".fighters:1/0/0/22/2/".$simulation->fightHeaders[$i];
			$ret[] = "fight".$fight.".r:".$simulation->simulations[$i]->fightLog;
			$ret[] = "winnerid".$fight.".s:".$simulation->simulations[$i]->winnerID;
		}
		
		$ret[] = 'fightadditionalplayers.r:'.$simulation->getAdditionals();
		
		$qryArgs[] = "quest_dur3 = quest_dur3";

		$rewardLog = [];
		for($i = 0; $i < 21; $i++)
			$rewardLog[] = 0;
		
		//rewarding
		if($simulation->win){
			
			//win true
			$rewardLog[0] = 1;
			//silver
			$rewardLog[2] = 0;
			//exp
			$rewardLog[3] = $allxp;

			$acc->addExp($rewardLog[3]);

			$qryArgs[] = "exp = ".$acc->data['exp'];
			$qryArgs[] = "lvl = ".$acc->data['lvl'];
		}
		
		$this->data["timeamount"] = $time;
		
		$db->exec("UPDATE players SET ".join(", ", $qryArgs)." WHERE ID = ".$acc->data['ID'] . ";UPDATE underworld SET timeamount = ".$this->data["timeamount"]." WHERE owner = ".$this->owner);
		
		$ret[] = "fightresult.battlereward:".join("/", $rewardLog)."/";
	}
	
	public function getBuildingTime($lvl){
		$time = [
			225,
			474,
			1000,
			2118,
			4500,
			7200,
			10260,
			16560,
			36000,
			78540,
			172800,
			288000,
			432000,
			554400,
			720000,
			0
			];
		
		return $time[$lvl];
	}
	
	public function getBuildingGoldSoul($id, $lvl){
		switch ($id) {
			case 1:
			//return [gold, soul]
			switch($lvl) {
				case 1: return [0, 0];
				case 2: return [200000, 616];
				case 3: return [300000, 1650];
				case 4: return [400000, 4220];
				case 5: return [500000, 11000];
				case 6: return [600000, 25080];
				case 7: return [700000, 45930];
				case 8: return [800000, 84150];
				case 9: return [900000, 198000];
				case 10: return [1000000, 439550];
				case 11: return [1100000, 902850];
				case 12: return [1200000, 2043300];
				case 13: return [1300000, 4118400];
				case 14: return [1400000, 7722000];
				case 15: return [1500000, 16632000];
				case 16: return [0, 0];
			}
			case 2:
			switch($lvl) {
				case 1: return [50000, 0];
				case 2: return [100000, 55];
				case 3: return [150000, 149];
				case 4: return [200000, 380];
				case 5: return [250000, 990];
				case 6: return [300000, 2255];
				case 7: return [350000, 4820];
				case 8: return [400000, 10090];
				case 9: return [450000, 26730];
				case 10: return [500000, 65930];
				case 11: return [550000, 135400];
				case 12: return [600000, 306500];
				case 13: return [650000, 617750];
				case 14: return [700000, 1158300];
				case 15: return [750000, 2494800];
				case 16: return [0, 0];
			}
			case 3:
			switch($lvl) {
				case 1: return [100000, 12];
				case 2: return [200000, 46];
				case 3: return [300000, 124];
				case 4: return [400000, 317];
				case 5: return [500000, 825];
				case 6: return [600000, 1880];
				case 7: return [700000, 4015];
				case 8: return [800000, 8415];
				case 9: return [900000, 19800];
				case 10: return [1000000, 43950];
				case 11: return [1100000, 90280];
				case 12: return [1200000, 204300];
				case 13: return [1300000, 411800];
				case 14: return [1400000, 772200];
				case 15: return [1500000, 1132600];
				case 16: return [0, 0];
			}
			case 4:
			switch($lvl) {
				case 1: return [25000, 0];
				case 2: return [50000, 462];
				case 3: return [75000, 1235];
				case 4: return [100000, 3165];
				case 5: return [125000, 8250];
				case 6: return [150000, 18810];
				case 7: return [175000, 40190];
				case 8: return [200000, 84150];
				case 9: return [225000, 198000];
				case 10: return [250000, 439550];
				case 11: return [275000, 902850];
				case 12: return [300000, 2043300];
				case 13: return [325000, 4118400];
				case 14: return [350000, 7722000];
				case 15: return [375000, 16632000];
				case 16: return [0, 0];
			}
			case 5:
			switch($lvl) {
				case 1: return [40000, 0];
				case 2: return [80000, 396];
				case 3: return [120000, 1060];
				case 4: return [160000, 2715];
				case 5: return [200000, 7070];
				case 6: return [240000, 16120];
				case 7: return [280000, 34450];
				case 8: return [320000, 63110];
				case 9: return [360000, 148500];
				case 10: return [400000, 329650];
				case 11: return [440000, 677150];
				case 12: return [480000, 1532500];
				case 13: return [520000, 3088800];
				case 14: return [540000, 6223932];
				case 15: return [580000, 12541222];
				case 16: return [0, 0];
			}
			case 6:
			switch($lvl) {
				case 1: return [66000, 148];
				case 2: return [132000, 554];
				case 3: return [198000, 1485];
				case 4: return [264000, 3800];
				case 5: return [330000, 9900];
				case 6: return [396000, 22570];
				case 7: return [462000, 41340];
				case 8: return [528000, 75730];
				case 9: return [594000, 178200];
				case 10: return [660000, 395600];
				case 11: return [726000, 812550];
				case 12: return [792000, 1389000];
				case 13: return [858000, 3706500];
				case 14: return [924000, 6949800];
				case 15: return [990000, 14968500];
				case 16: return [0, 0];
			}
			case 7:
			switch($lvl) {
				case 1: return [70000, 148];
				case 2: return [140000, 554];
				case 3: return [210000, 1485];
				case 4: return [280000, 3800];
				case 5: return [350000, 9900];
				case 6: return [420000, 22570];
				case 7: return [490000, 48230];
				case 8: return [560000, 100950];
				case 9: return [630000, 237600];
				case 10: return [700000, 527450];
				case 11: return [770000, 1083400];
				case 12: return [840000, 2452000];
				case 13: return [910000, 4942000];
				case 14: return [980000, 9266400];
				case 15: return [1050000, 19958000];
				case 16: return [0, 0];
			}
			case 8:
			switch($lvl) {
				case 1: return [99000, 165];
				case 2: return [198000, 616];
				case 3: return [297000, 1650];
				case 4: return [396000, 4220];
				case 5: return [495000, 11000];
				case 6: return [594000, 25080];
				case 7: return [693000, 45930];
				case 8: return [792000, 84150];
				case 9: return [891000, 198000];
				case 10: return [990000, 439550];
				case 11: return [1089000, 902850];
				case 12: return [1190000, 1850842];
				case 13: return [1290000, 3794226];
				case 14: return [1390000, 7778163];
				case 15: return [1490000, 15945234];
				case 16: return [0, 0];
			}
			case 9:
			switch($lvl) {
				case 1: return [1000000, 297];
				case 2: return [2000000, 1105];
				case 3: return [3000000, 2970];
				case 4: return [4000000, 5700];
				case 5: return [5000000, 11880];
				case 6: return [6000000, 22570];
				case 7: return [7000000, 41340];
				case 8: return [8000000, 75730];
				case 9: return [9000000, 178200];
				case 10: return [10000000, 395600];
				case 11: return [11000000, 812550];
				case 12: return [12000000, 1839000];
				case 13: return [13000000, 3706500];
				case 14: return [14000000, 6949800];
				case 15: return [15000000, 14968500];
				case 16: return [0, 0];
			}
			case 10:
			switch($lvl) {
				case 1: return [150000, 198];
				case 2: return [300000, 739];
				case 3: return [450000, 1980];
				case 4: return [600000, 5065];
				case 5: return [750000, 13200];
				case 6: return [900000, 25080];
				case 7: return [1050000, 45930];
				case 8: return [1200000, 84150];
				case 9: return [1350000, 198000];
				case 10: return [1500000, 439550];
				case 11: return [1650000, 902850];
				case 12: return [1800000, 2043300];
				case 13: return [1950000, 4118400];
				case 14: return [2100000, 7722000];
				case 15: return [2250000, 16632000];
				case 16: return [0, 0];
			}
		}
	}
	
	public function getUnitUpgradeSoul($id, $lvl){
		if($id == 1)
			$price = 300; // Goblin
		else if($id == 2)
			$price = 500; // Troll
		else if($id == 3)
			$price = 800; // Keeper
		
		for($i = 0; $i < $lvl; $i++){
			if($price > 30000000)
				$price *= 1.005 + (($id - 0.7) / 150);
			else if($price > 3000000)
				$price *= 1.01 + (($id - 0.7) / 110);
			else
				$price *= 1.3;
		}
		
		if($price > 101190000)
			$price = 101190000;
		
		return floor($price);
	}
	
	public function getUnitUpgradeGold($id, $lvl){
		if($id == 1)
			$price = 117; // Goblin
		else if($id == 2)
			$price = 130; // Troll
		else if($id == 3)
			$price = 1112; // Keeper
		
		for($i = 0; $i < $lvl; $i++){
			if($price > (3000000))
				$price *= 1.05;
			else
				$price *= 1.4;
		}
		
		return floor($price);
	}
	public function attack($id){
		global $acc, $db, $ret;
		
		// Get enemy
		$qry = $db->query("SELECT players.*, guilds.portal AS guild_portal FROM players LEFT JOIN guilds ON players.guild = guilds.ID WHERE players.ID = $id");
		
		if($qry->rowCount() < 1)
			exit("Error:player not found");
		
		$enemy = $qry->fetch(PDO::FETCH_ASSOC);
		
		$items = $db->query("SELECT * FROM items WHERE owner = $id AND slot BETWEEN 10 AND 19");
		$items = $items->fetchAll();
		
		$enemyObj = new Player($enemy, $items);
		
		// Get our fighters (defenders)
		$def = [];
		$all = 0;
		
		// Goblins
		$goblvl = $this->data["goblin"];
		
		if($goblvl > 0){
			$max = min($goblvl, 5);
			
			$stats = $this->getEntityStats(1);
			
			$lvl = $stats[0];
			$stat = $stats[1];
			$luck = floor($stat / 2);
			
			$dmg = $this->getEntityDamage($lvl, $stat, 2.4);
			
			$hp = $this->getEntityHP($stat, $lvl);
			
			for($i = 0; $i < $max; $i++){
				if($goblvl >= 14)
					$id = rand(909, 910);
				else if($goblvl >= 9)
					$id = rand(904, 908);
				else if($goblvl >= 6)
					$id = rand(901, 903);
				else
					$id = 900;
					
				// ($lvl, $class, $str, $agi, $int, $wit, $luck, $dmg_min, $dmg_max, $hp, $armor, $id, $exp, $weapon_id, $shield_id = 0)
				
				$def[$all] = new FortressMonster($lvl, 1, $stat, $stat, $stat, $stat, $luck, $dmg[0], $dmg[1], $hp, 0, $id, 0, 6, 1);
				
				$def[$all]->currCount = $i + 1;
				$def[$all]->allCount = $max;
				
				$all++;
			}
		}
		
		// Trolls
		$trllvl = $this->data["troll"];
		
		if($trllvl > 0){
			if($trllvl == 15)
				$max = 4;
			else if($trllvl >= 13)
				$max = 3;
			else if($trllvl >= 9)
				$max = 2;
			else
				$max = 1;
			
			if($max > 1)
				$id = 920 + $trllvl;
			else
				$id = 919 + $trllvl;
			
			$stats = $this->getEntityStats(2);
			
			$lvl = $stats[0];
			$stat = $stats[1];
			$luck = floor($stat / 2);
			
			$dmg = $this->getEntityDamage($lvl, $stat, 2.7);
			
			$hp = $this->getEntityHP($stat, $lvl);
			
			for($i = 0; $i < $max; $i++){			
				$def[$all] = new FortressMonster($lvl, 1, $stat, $stat, $stat, $stat, $luck, $dmg[0], $dmg[1], $hp, 0, $id, 0, 6, 1);
				
				$def[$all]->currCount = $i + 1;
				$def[$all]->allCount = $max;
				$def[$all]->block = 41;
				
				$all++;
				$id--;
			}
		}
		
		
		// Keeper
		
		$keeplvl = $this->data["keeper"];
		
		if($keeplvl > 0){
			$stats = $this->getEntityStats(3);
			
			$lvl = $stats[0];
			$stat = $stats[1];
			$luck = floor($stat / 2);
			
			$dmg = $this->getEntityDamage($lvl, $stat, 3.1);
			
			$hp = $this->getEntityHP($stat, $lvl);
			
			$id = 939 + $this->data["keeper"];
			
			$def[$all] = new Monster($lvl, 1, $stat, $stat, $stat, $stat, $luck, $dmg[0], $dmg[1], $hp, 0, $id, 0, 65535, 1);
			$def[$all]->block = 50;
		}
		
		// Simulation and fight end
		$simulation = new GroupSimulation($def, [$enemyObj]);
		$simulation->simulate();
		
		for($i = 0; $i < count($simulation->simulations); $i++){
			$fight = $i+1;
			$ret[] = "fightheader".$fight.".fighters:16/0/0/0/1/".$simulation->fightHeaders[$i];
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
		}
		
		$ret[] = "fightresult.battlereward:".join("/", $rewardLog)."/";
	}
	
	public static function createUnderworld($owner){
		$GLOBALS["db"]->exec("INSERT INTO underworld (owner) VALUES({$owner})");
	}
}