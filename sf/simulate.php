<?php

class Simulation{

	private $player;
	private $opponent;

	//winner id
	public $winner;

	public $fightLog;

	//db ID of winner
	public $winnerID;

	//the longer the fights go on, the higher the hits
	private $progressionMultiplier;

	//reference needed for group simulation
	function __construct(&$player, &$opponent){
		$this->player = $player;
		$this->opponent = $opponent;
		//exit("Error:".$this->player->ID);
	}

	public function simulate(){
		//block = rand(1, 100), 0 = no block
		//hit types:
		// 0 = normal | 1 = crit | 2 = catapult | 3 = blok | 4 = dodge


		//log structure: hitter id/hit type/hp after hit

		$this->fightLog = [];

		//randomize who begins fight
		if(rand(0, 1) == 1){
			$first = "player";
			$second = "opponent";
		}else{
			$first = "opponent";
			$second = "player";
		}

		$blocks = ($this->player->class != 2 && $this->opponent->class != 2);
		$this->progressionMultiplier = 1.0;

		while($this->opponent->hp > 0 && $this->player->hp > 0){
			if($this->$first->hp > 0){
				if($this->$first->class == 4)
					$this->assassinHit($first, $second, $blocks);
				else
					$this->hit($first, $second, $blocks);
			}

			if($this->$second->hp > 0){
				if($this->$second->class == 4)
					$this->assassinHit($second, $first, $blocks);
				else
					$this->hit($second, $first, $blocks);
			}

			if($this->progressionMultiplier < 1.7)
				$this->progressionMultiplier += 0.1;
			else
				$this->progressionMultiplier += 0.05;
		}

		$this->fightLog = join(",", $this->fightLog);
		
		if($this->player->hp > 0)
			$this->winnerID = $this->player->ID;
		else{
			//monsters need negative value on this because fucking reasons
			$this->winnerID = $this->opponent->ID;
			if(get_class($this->opponent) == "Monster")
				$this->winnerID = abs($this->winnerID) * -1;
		}


	}


	//TODO: count dmg with armor and shit
	private function hit($hitter, $target, $block, $dmg_min = 0, $dmg_max = 0, $typePlus = 0){
		//make this a float later
		//$crit = (rand(0,100) < $this->$hitter->crit);
		
		// Real crit by Greg
		
		$crit = $this->$hitter->getTotalStats()["luck"] * 5 / ($this->$target->lvl * 2);
		
		if($crit > 50)
			$crit = 50;
		
		$crit = rand(0, 100) < $crit;

		//if warrior has no shield / wmoved to player, if no shield block chance = 0
		// if($this->$target->class == 1 && !isset($this->$target->shield))
		// 	$block = false;
	
		// Greg
		$dmg_min = $dmg_min == 0 ? $this->$hitter->dmg_min : $dmg_min;
		$dmg_max = $dmg_max == 0 ? $this->$hitter->dmg_max : $dmg_max;

		//block/dodge
		if($block)
			$block = (rand(0, 100) < $this->$target->block);

		//log structure: hitter id/hit type/hp after hit
		$this->fightLog[] = $this->$hitter->ID;

		if($block)
			$hitType = ($this->$target->class == 1) ? 3 : 4;
		else if($crit)
			$hitType = 1;
		else
			$hitType = 0;
		
		if($hitType <= 1)
			$hitType += $typePlus;
		
		$this->fightLog[] = $hitType;

		//TODO: count the dmg
		
		$dmg = round(rand(intval($this->$hitter->dmg_min), intval($dmg_max)) * $this->progressionMultiplier);

		if($crit)
			$dmg *= 1.7;
		
		$dmg = round($dmg);
		
		if(!$block)
			$this->$target->dmg($dmg);
		$this->fightLog[] = $this->$target->hp;
	}
	
	private function assassinHit($hitter, $target, $block){
		// Assassin hit by Greg
		
		$weaps = $this->$hitter->getAssassinWeapons();
		
		if(isset($weaps[1])){
			$dmg_min = floor($this->$hitter->dmg_min * $weaps[1]->raw["dmg_min"]);
			$dmg_max = floor($this->$hitter->dmg_min * $weaps[1]->raw["dmg_max"]);
			
			$this->hit($hitter, $target, $block, $dmg_min, $dmg_max, 0);
		}
		else
			$this->hit($hitter, $target, $block, 0, 0, 0);
		
		if($this->$target->hp <= 0)
			return;
		
		if(isset($weaps[2])){
			$dmg_min = floor($this->$hitter->dmg_min * $weaps[2]->raw["dmg_min"]);
			$dmg_max = floor($this->$hitter->dmg_min * $weaps[2]->raw["dmg_max"]);
			
			$this->hit($hitter, $target, $block, $dmg_min, $dmg_max, 10);
		}
		else
			$this->hit($hitter, $target, $block, 0, 0, 10);
	}


}




class GroupSimulation{

	public $playerGroup;
	public $opponentGroup;

	//array of simulation objects to access fight logs and winners
	public $simulations = [];

	//array of fight headers, contain starting hp, so they need to be set before fight simulation
	public $fightHeaders = [];

	//boolean, true if playerGroup wins
	public $win;
	
	// For additional fighters
	public $lasts = [0, 0];

	function __construct($playerGroup, $opponentGroup){
		$this->playerGroup = $playerGroup;
		$this->opponentGroup = $opponentGroup;
	}

	public function simulate(){
		$playerLast = &$this->playerGroup[count($this->playerGroup) - 1];
		$opponentLast = &$this->opponentGroup[count($this->opponentGroup) - 1];

		//counters
		$pc = 0;
		$oc = 0;

		while($playerLast->hp > 0 && $opponentLast->hp > 0){
			//scene, background etc handled outside
			$this->fightHeaders[] = $this->playerGroup[$pc]->getFightHeader().$this->opponentGroup[$oc]->getFightHeader();

			$simulation = new Simulation($this->playerGroup[$pc], $this->opponentGroup[$oc]);
			$simulation->simulate();


			$this->simulations[] = $simulation;
			
			//increase counter depending on winner
			if($simulation->winnerID == $this->playerGroup[$pc]->ID)
				$oc++;
			else
				$pc++;
		}

		//set winner
		$this->win = $playerLast->hp > 0;
		
		// set lasts
		$this->lasts = [$pc, $oc];
	}

	public static function reverseGuildFightLog($log){

		return $log;

		$log = split('&', $log);

		foreach($log as $line){
			switch($line){

			}
		}
	}
	
	public function getAdditionals()
	{
		$c = $this->lasts[0];
		
		$meAddi = ["", "", "", "", ""];
		
		for($i = 0; $i <= 4; $i++)
		{
			if (!isset($this->playerGroup[$c + 1 + $i]))
				break;
			
			$p = $this->playerGroup[$c + 1 + $i];
			
			$temp = &$meAddi[$i];
			
			if (isset($p->data["name"]))
				$temp = $p->data["name"];
			else if (isset($p->ID2) && $p->ID2 < 0)
				$temp = $p->ID2;
			else
				$temp = $p->ID;
			
			if(property_exists($p, "currCount"))
				$temp .= "_" . $p->currCount . "_" . $p->allCount;
		}
		
		$c = $this->lasts[1];
		
		$youAddi = ["", "", "", "", ""];
		
		for($i = 0; $i <= 4; $i++)
		{
			if (!isset($this->opponentGroup[$c + 1 + $i]))
				break;
			
			$p = $this->opponentGroup[$c + 1 + $i];
			
			$temp = &$youAddi[$i];
			
			if (isset($p->data["name"]))
				$temp = $p->data["name"];
			else if (isset($p->ID2) && $p->ID2 < 0)
				$temp = $p->ID2;
			else
				$temp = $p->ID;
			
			if(property_exists($p, "currCount"))
				$temp .= "_" . $p->currCount . "_" . $p->allCount;
		}
		
		return implode(",", $meAddi) . "," . implode(",", $youAddi) . ",";
	}
}

?>