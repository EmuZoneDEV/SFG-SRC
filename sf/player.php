<?php

//other players, with stats, SMOrc, achievements, desc, mount, guild, etc...
class Player extends Entity{	
	public $data;
	public $equip = array();
	
	public $fortress;
	
	function __construct($playerData, $items){
		$this->setPlayerData($playerData, $items);
	}
	
	public function setPlayerData($playerData, $items){
		$this->data = $playerData;
		$this->ID = $playerData['ID'];

		$this->class = $playerData['class'];
		$this->lvl = $playerData['lvl'];

		// Fortress - Greg
		$sql = "SELECT * FROM fortress WHERE owner='{$this->ID}'";
		$qry = $GLOBALS['db']->prepare($sql);
		$qry->execute();
		$this->fortress = $qry->fetch(PDO::FETCH_ASSOC);
		
		$this->baseStats = [
			"str" => $playerData['str'],
			"dex" => $playerData['dex'],
			"int" => $playerData['intel'],
			"wit" => $playerData['wit'],
			"luck" => $playerData['luck']];

		if($items === false)
			$items = [];

		//shops, backpacks for subclass, player class won't recive any other slots than equip
		foreach($items as $item){
			if ($item["slot"] > 1000)
				continue;
			if($item['slot'] >= 100){
				$this->fortressBackpack[] = new Item($item);
			}else if($item['slot'] >= 50){
				$owner = floor($item['slot']/10)-5;
				$this->copycatEquip[$owner][] = new Item($item);
			}else if($item['slot'] >= 20){
				$this->shops[] = new Item($item);
			}else if($item['slot'] >= 10){
				$item =  new Item($item);
				$this->equip[] = $item;
				if($item->type == 1)
					$this->weapon = $item;
				else if($item->type == 2)
					$this->shield = $item;
			}else{
				$this->backpack[] = new Item($item);
			}
		}

		//set this->dmg && this->block
		//if class = rouge, block = 50%
		if($this->class == 1){
			//TODO: get shield block
			if(isset($this->shield))
				$this->block = 25;
			$mainStat = "str";
		}else if($this->class == 3 || $this->class == 4){
			$this->block = 50;
			$mainStat = "dex";
		}else{
			$this->block = 0;
			$mainStat = "int";
		}



		if($weapon = $this->getWeapon()){
			$dmgStat = $this->getTotalStats()[$mainStat];

			//dla pewnosci
			$portal = is_numeric($this->data['guild_portal']) ? 1 + ($this->data['guild_portal'] / 100) : 1;
			
			if($this->class == 4){
				$this->dmg_min = floor(0.625 * (1 + $dmgStat/10) * $portal);
				$this->dmg_max = floor(0.625 * (1 + $dmgStat/10) * $portal);
			}else{
				$this->dmg_min = floor($weapon->raw['dmg_min'] * (1 + $dmgStat/10) * $portal);
				$this->dmg_max = floor($weapon->raw['dmg_max'] * (1 + $dmgStat/10) * $portal);
			}
		}else{
			//default dmg without weapon 1-2
			$dmgStat = $this->getTotalStats()[$mainStat];
			$this->dmg_min = floor(1 + $dmgStat/10);
			$this->dmg_max = floor(2 * (1 + $dmgStat/10));

		}
		// var_dump($this->dmg_min);

		parent::NewEntity();

		//potion hp
		for($i = 1; $i <= 3; $i++){
			if($this->data['potion_type'.$i] == 16 && $this->data['potion_dur'.$i] > $GLOBALS["CURRTIME"]){
				$this->maxHp = round($this->maxHp * 1.25 * (1 + $this->data['portal'] / 100));
				$this->hp = round($this->maxHp);
			}
		}
	}

	public function getLookatSave(){
		$ret = [];
		for($i = 0; $i < 258; $i++)
			$ret[] = 0;
		//need 258 indexes or won't display, last is empty (afaik)
		$ret[] = "";


		//guild id
		$ret[161] = $this->data['guild'];
		
		// UID
		$ret[0] = $this->data['ID'];
		
		//lvl
		$ret[2] = 65536 + $this->data['lvl'];
		//exp
		$ret[3] = $this->data['exp'];
		
		if($this->data['expType'] == '1')
			$ret[3] *= 1000000000;
		
		//required exp
		$ret[4] = Player::getExp($this->data['lvl'] - 1);
		
		// honor
		$ret[5] = $this->data['honor'];
		
		// Golden frame
		$ret[167] = 32 * $this->data['gframe'];
		
		//rank by Greg
		$rank = $GLOBALS['db']->query("SELECT Count(*) as rank FROM players WHERE honor > ".$this->data['honor']);
		$rank = $rank->fetch(PDO::FETCH_ASSOC)['rank'] + 1;
		$ret[6] = $rank;

		$ret[20] = $this->data['class'];
		$face = explode(",", $this->data['face']);
		for($i = 0; $i < 9; $i++)
			$ret[8 + $i] = $face[$i];

		//race, gender
		$ret[18] = $this->data['race'];
		$ret[19] = $this->data['gender'];

		//base stats
		$ret[21] = $this->baseStats['str'];
		$ret[22] = $this->baseStats['dex'];
		$ret[23] = $this->baseStats['int'];
		$ret[24] = $this->baseStats['wit'];
		$ret[25] = $this->baseStats['luck'];

		//stats equip
		$equipStats = $this->getEquipStats();
		
		// Fortress data by Greg
		
		// Attack reward
		$reward = $this->getFortressEnemyReward();
		$ret[228] = $reward[0]; // Enemy win - wood
		$ret[229] = $reward[1]; // Enemy win - stone
		
		$ret[230] = $this->fortress['u2'] > 0 ? 65572 : 0; // Magicians?
		$ret[231] = $this->fortress['u3']; // Bowman count
		
		// Fortress buildings data
		$ret[208] = $this->fortress['b0'];
		$ret[209] = $this->fortress['b1'];
		$ret[210] = $this->fortress['b2'];
		$ret[211] = $this->fortress['b3'];
		$ret[212] = $this->fortress['b4'];
		$ret[213] = $this->fortress['b5'];
		$ret[214] = $this->fortress['b6'];
		$ret[215] = $this->fortress['b7'];
		$ret[216] = $this->fortress['b8'];
		$ret[217] = $this->fortress['b9'];
		$ret[218] = $this->fortress['b10'];
		$ret[219] = $this->fortress['b11'];
		
		

		$ret[26] = $equipStats['str'];
		$ret[27] = $equipStats['dex'];
		$ret[28] = $equipStats['int'];
		$ret[29] = $equipStats['wit'];
		$ret[30] = $equipStats['luck'];

		// var_dump($this->equip);
		foreach($this->equip as $item){
			$slot = ($item->raw['slot'] - 10) * 12;
			// $slot = 1*12;
			$itemSave = $item->getSave();
			for($i = 0; $i < 12; $i++)
				$ret[39 + $slot + $i] = $itemSave[$i];
		}



		$ret[159] = ($this->data['tower'] * 65536) + $this->data['mount'];


		//album
		if($this->hasAlbum())
			$ret[163] = 10000 + $this->data['album'];

		//armor
		// $ret[168] = 3705;

		//dps range
		if($weapon = $this->getWeapon()){
			$ret[169] = $weapon->raw['dmg_min'];
			$ret[170] = $weapon->raw['dmg_max'];
		}else{
			$ret[169] = 1;
			$ret[170] = 2;
		}
		
		// Invite
		$ret[205] = $this->data["noinv"];


		//pots
		for($i = 1; $i <= 3; $i++){
			if($this->data['potion_dur'.$i] > $GLOBALS["CURRTIME"]){
				$ret[193 + $i] = $this->data['potion_type'.$i];
				// $ret[495 + $i] = $this->data['potion_dur'.$i];
				$ret[199 + $i] = 25;
				if($this->data['potion_type'.$i] < 16){
					$stat = ($ret[193 + $i] - 1) % 5;
					$ret[26 + $stat] += ceil(($ret[21 + $stat] + $ret[26 + $stat]) * 0.25) + 1;
				}
			}
		}

		//portal bonuses
		$ret[252] = intval(((int)$this->data['portal'] * 256 + (int)$this->data['guild_portal']) * 65536);

		return join("/", $ret);
	}

	public function getFightHeader(){
		$ret = [];
		for($i = 0; $i <=46; $i++)
			$ret[] = 0;

		$ret[47] = "";

		$ret[0] = $this->data['ID'];
		$ret[1] = $this->data['name'];
		$ret[2] = $this->lvl;
		//max hp
		$ret[3] = $this->maxHp;

		//hp at start of fight (use for guild fights etc, will see)
		$ret[4] = $this->hp;

		//total stats
		$stats = $this->getTotalStats();
		$ret[5] = $stats['str'];
		$ret[6] = $stats['dex'];
		$ret[7] = $stats['int'];
		$ret[8] = $stats['wit'];
		$ret[9] = $stats['luck'];

		//face
		$face = explode(",", $this->data['face']);	
		for($i = 0; $i < 9; $i++)
			$ret[10 + $i] = $face[$i];

		$ret[20] = $this->data['race'];
		$ret[21] = $this->data['gender'];
		//class?? not sure, value was 1 originaly
		$ret[22] = $this->class;

		//weapon and shield bellow
		//Assassin fix by Greg
		if($this->class == 4){
			$this->getFightAssassinWeapons($ret);
		}else{
			if(isset($this->weapon)){
				$this->getFightWeapon($ret);
			}
			if(isset($this->shield)){
				$this->getFightShield($ret);
			}
		}
		
		return join("/", $ret);
	}
	
	public function getFightWeapon(&$ret){
		$ret[23] = 1;
		$ret[24] = $this->weapon->raw['item_id'];
		$ret[25] = $this->weapon->raw['dmg_min'];
		$ret[26] = $this->weapon->raw['dmg_max'];
		$ret[27] = $this->weapon->raw['a1'];
		$ret[28] = $this->weapon->raw['a2'];
		$ret[29] = $this->weapon->raw['a3'];
		$ret[30] = $this->weapon->raw['a4'];
		$ret[31] = $this->weapon->raw['a5'];
		$ret[32] = $this->weapon->raw['a6'];
		$ret[33] = $this->weapon->raw['value_silver'];
		$ret[34] = $this->weapon->raw['value_mush'];
	}
	
	public function getFightShield(&$ret){
		$ret[35] = 2;
		$ret[36] = $this->shield->raw['item_id'];
		$ret[37] = $this->shield->raw['dmg_min'];
		$ret[38] = $this->shield->raw['dmg_max'];
		$ret[39] = $this->shield->raw['a1'];
		$ret[40] = $this->shield->raw['a2'];
		$ret[41] = $this->shield->raw['a3'];
		$ret[42] = $this->shield->raw['a4'];
		$ret[43] = $this->shield->raw['a5'];
		$ret[44] = $this->shield->raw['a6'];
		$ret[45] = $this->shield->raw['value_silver'];
		$ret[46] = $this->shield->raw['value_mush'];
	}
	
	public function getFightAssassinWeapons(&$ret){
		// Greg
		
		$weaps = $this->getAssassinWeapons();
		
		if(isset($weaps[1])){
			$ret[23] = 1;
			$ret[24] = $weaps[1]->raw['item_id'];
			$ret[25] = $weaps[1]->raw['dmg_min'];
			$ret[26] = $weaps[1]->raw['dmg_max'];
			$ret[27] = $weaps[1]->raw['a1'];
			$ret[28] = $weaps[1]->raw['a2'];
			$ret[29] = $weaps[1]->raw['a3'];
			$ret[30] = $weaps[1]->raw['a4'];
			$ret[31] = $weaps[1]->raw['a5'];
			$ret[32] = $weaps[1]->raw['a6'];
			$ret[33] = $weaps[1]->raw['value_silver'];
			$ret[34] = $weaps[1]->raw['value_mush'];
		}
		if(isset($weaps[2])){
			$ret[35] = 1;
			$ret[36] = $weaps[2]->raw['item_id'];
			$ret[37] = $weaps[2]->raw['dmg_min'];
			$ret[38] = $weaps[2]->raw['dmg_max'];
			$ret[39] = $weaps[2]->raw['a1'];
			$ret[40] = $weaps[2]->raw['a2'];
			$ret[41] = $weaps[2]->raw['a3'];
			$ret[42] = $weaps[2]->raw['a4'];
			$ret[43] = $weaps[2]->raw['a5'];
			$ret[44] = $weaps[2]->raw['a6'];
			$ret[45] = $weaps[2]->raw['value_silver'];
			$ret[46] = $weaps[2]->raw['value_mush'];
		}
	}

	public function getName(){
		return $this->data['name'];
	}

	public function getWeapon(){
		foreach($this->equip as $item){
			if($item->type == 1)
				return $item;
		}
			
		return null;
	}
	
	public function getAssassinWeapons(){
		$ret = [];
		
		foreach($this->equip as $item){
			if($item->type == 1)
				if($item->slot == 8)
					$ret[1] = $item;
				else if($item->slot == 9)
					$ret[2] = $item;
		}
		
		return $ret;
	}

	public function getShield(){
		foreach($this->equip as $item)
			if($item->type == 2)
				return $item;
	}

	public function getEquipStats(){
		$stats = [
		"str" => "0",
		"dex" => "0",
		"int" => "0",
		"wit" => "0",
		"luck" => "0"];

		foreach($this->equip as $item){
			$itemStats = $item->stats;

			$stats['str'] += $itemStats['str'];
			$stats['dex'] += $itemStats['dex'];
			$stats['int'] += $itemStats['int'];
			$stats['wit'] += $itemStats['wit'];
			$stats['luck'] += $itemStats['luck'];
		}

		return $stats;
	}

	public function getTotalStats(){
		$stats = $this->baseStats;
		$equipStats = $this->getEquipStats();

		$stats['str'] += $equipStats['str'];
		$stats['dex'] += $equipStats['dex'];
		$stats['int'] += $equipStats['int'];
		$stats['wit'] += $equipStats['wit'];
		$stats['luck'] += $equipStats['luck'];

		$statNames = ['str', 'dex', 'int', 'wit', 'luck'];

		for($i = 1; $i <= 3; $i++){
			if($this->data['potion_dur'.$i] > $GLOBALS["CURRTIME"] && $this->data['potion_type'.$i] < 16){
				$statName = $statNames[($this->data['potion_type'.$i] - 1) % 5];
				$stats[$statName] = round($stats[$statName] * 1.25);
			}
		}

		return $stats;
	}

	public function hasAlbum(){
		//return isset($this->album);
		return $this->data['album'] >= 0 ? true : false;
	}
	
	public function fortressAttackLevels($imp = true) {
		//return '153/104/105/106';
		// Fortifications/Soldier/Archer/Mage
		
		$fort = Fortress::unitStats(4, $this->fortress['b11'])[2];
		
		$sold = Fortress::unitStats(1, $this->fortress['ul1'])[2];
		
		$mage = Fortress::unitStats(2, $this->fortress['ul2'])[2];
		
		$arc = Fortress::unitStats(3, $this->fortress['ul3'])[2];
		
		
		$data = [$fort, $sold, $mage, $arc];
		
		if ($imp)
			$data = implode('/', $data);
		
		return $data;
	}
	
	public function getFortressEnemyReward() {
		// Greg
		$wood = intval($this->fortress['wood'] * 0.011);
		$stone = intval($this->fortress['stone'] * 0.011);
		
		return array($wood, $stone);
	}
	
	// Unlimited lvls by Greg
	public static function getExp($lvl)
	{
		$exp = [100,110,121,133,146,161,177,250,500,750,7515,8925,10335,11975,13715,15730,17745,20250,22755,25620,28660,32060,35460,39535,43610,48155,52935,58260,63585,69760,75935,82785,89905,97695,105485,114465,123445,133260,143425,154545,165665,178210,190755,204430,218540,233785,249030,266140,283250,301715,320685,341170,361655,384360,407065,431545,456650,483530,510410,540065,569720,601435,633910,668670,703430,741410,779390,819970,861400,905425,949450,997485,1045520,1096550,1148600,1203920,1259240,1319085,1378930,1442480,1507225,1575675,1644125,1718090,1792055,1870205,1949685,2033720,2117755,2208040,2298325,2393690,2490600,2592590,2694580,2803985,2913390,3028500,3145390,3268435,3391480,3522795,3654110,3792255,3932345,4079265,4226185,4382920,4539655,4703955,4870500,5045205,5219910,5405440,5590970,5785460,5982490,6188480,6394470,6613125,6831780,7060320,7291640,7533530,7775420,8031275,8287130,8554570,8825145,9107305,9389465,9687705,9985945,10296845,10611275,10939230,11267185,11612760,11958335,12318585,12682650,13061390,13440130,13839160,14238190,14653230,15072545,15508870,15945195,16403485,16861775,17338505,17819980,18319895,18819810,19344795,19869780,20414715,20964770,21536005,22107240,22705735,23304230,23925545,24552535,25202340,25852145,26532725,27213305,27918540,28630050,29367610,30105170,30875945,31646720,32445505,33251010,34084530,34918050,35789075,36660100,37561220,38469755,39410080,40350405,41330960,42311515,43326065,44348735,45405405,46462075,47563900,48665725,49804020,50951005,52136360,53321715,54555530,55789345,57064175,58348500,59673840,60999180,62378435,63757690,65180715,66614100,68093535,69572970,71110105,72647240,74233350,75830465,77476555,79122645,80832985,82543325,84305910,86080505,87909870,89739235,91636870,93534505,95490375,97459260,99486380,101513500,103616290,105719080,107883715,110062180,112305475,114548770,116872700,119196630,121589225,123996780,126473000,128949220,131514215,134079210,136717090,139371155,142101400,144831645,147656105,150480565,153385655,156307860,159310695,162313530,165420140,168526750,171718645,174929030,178228565,181528100,184937365,188346630,191849945,195373130,198990370,202607610,206345275,210082940,213920015,217778100,221739815,225701530,229790630,233879730,238078150,242299140,246629445,250959750,255429090,259898430,264482960,269091720,273820565,278549410,283425105,288300800,293302740,298330180,303483865,308637550,313951595,319265640,324712695,330187105,335799860,341412615,347193920,352975225,358901970,364857940,370959350,377060760,383345695,389630630,396068325,402536785,409164155,415791525,422612215,429432905,436420230,443440385,450627180,457813975,465210300,472606625,480177945,487784290,495572280,503360270,511368340,519376410,527574890,535810100,544235725,552661350,561325655,569989960,578853765,587756750,596866840,605976930,615337095,624697260,634274025,643892430,653727435,663562440,673667980,683773520,694105920,704481995,715093150,725704305,736599015,747493725,758634285,769821230,781254025,792686820,804425165,816163510,828158780,856843923,903283173,921348836,939775812,958571328,977742755,997297610,1017243562,1037588434,1058340202,1079507006,1101097146,1123119089,1145581470,1168493100,1191862962,1215700221,1240014225,1264814510,1290110800,1315913016,1342231277,1369075902,1396457420,1424386569,1452874300,1481931786,1511570422,1541801830,1572637867,1604090624,1636172436,1668895885,1702273803,1736319279,1771045664,1806466578,1842595909,1879447828,1917036784,1955377520,1994485070,2034374772,2075062267,2116563512,2158894783,2202072678,2246114132,2291036414,2336857143,2383594286,2431266171,2479891495,2529489325,2580079111,2631680693,2684314307,2738000593,2792760605,2848615817,2905588134,2963699896,3022973894,3083433372,3145102040,3208004080,3272164162,3337607445,3404359594,3472446786,3541895722,3612733636,3684988309,3758688075,3833861837,3910539073,3988749855,4068524852,4149895349,4232893256,4317551121,4403902143,4491980186,4581819790,4673456186,4766925310,4862263816,4959509092,5058699274,5159873259,5263070725,5368332139,5475698782,5585212757,5696917013,5810855353,5927072460,6045613909,6166526187,6289856711,6415653845,6543966922,6674846261,6808343186,6944510049,7083400250,7225068255,7369569621,7516961013,7667300233,7820646238,7977059163,8136600346,8299332353,8465319000,8634625380,8807317888,8983464245,9163133530,9346396201,9533324125,9723990607,9918470419,10116839828,10319176624,10525560157,10736071360,10950792787,11169808643,11393204816,11621068912,11853490290,12090560096,12332371298,12579018724,12830599099,13087211081,13348955302,13615934408,13888253096,14166018158,14449338521,14738325292,15033091798,15333753634,15640428706,15953237280,16272302026,16597748067,16929703028,17268297088,17613663030,17965936291,18325255017,18691760117,19065595319,19446907226,19835845370,20232562278,20637213523,21049957794,21470956950,21900376089,22338383610,22785151282,23240854308,23705671394,24179784822,24663380519,25156648129,25659781092,26172976713,26696436248,27230364973,27774972272,28330471718,28897081152,29475022775,30064523230,30665813695,31279129969,31904712568,32542806820,33193662956,33857536215,34534686939,35225380678,35929888292,36648486058,37381455779,38129084894,38891666592,39669499924,40462889923,41272147721,42097590676,42939542489,43798333339,44674300006,45567786006,46479141726,47408724560,48356899052,49324037033,50310517773,51316728129,52343062691,53389923945,54457722424,55546876872,56657814410,57790970698,58946790112,60125725914,61328240433,62554805241,63805901346,65082019373,66383659760,67711332956,69065559615,70446870807,71855808223,73292924388,74758782875,76253958533,77779037704,79334618458,80921310827,82539737043,84190531784,85874342420,87591829268,89343665854,91130539171,92953149954,94812212953,96708457212,98642626357,100615478884,102627788461,104680344231,106773951115,108909430138,111087618740,113309371115,115575558537,117887069708,120244811102,122649707324,125102701471,127604755500,130156850610,132759987622,135415187375,138123491122,140885960945,143703680164,146577753767,149509308842,152499495019,155549484920,158660474618,161833684110,165070357793,168371764948,171739200247,175173984252,178677463937,182251013216,185896033480,189613954150,193406233233,197274357898,201219845056,205244241957,209349126796,213536109332,217806831518,222162968149,226606227512,231138352062,235761119103,240476341485,245285868315,250191585681,255195417395,260299325743,265505312258,270815418503,276231726873,281756361410,287391488639,293139318411,299002104780,304982146875,311081789813,317303425609,323649494121,330122484004,336724933684,343459432357,350328621004,357335193424,364481897293,371771535239,379206965944,386791105262,394526927368,402417465915,410465815233,418675131538,427048634169,435589606852,444301398989,453187426969,462251175508,471496199019,480926122999,490544645459,500355538368,510362649135,520569902118,530981300161,541600926164,552432944687,563481603581,574751235652,586246260365,597971185573,609930609284,622129221470,634571805899,647263242017,660208506858,673412676995,686880930535,700618549145,714630920128,728923538531,743502009301,758372049487,773539490477,789010280287,804790485892,820886295610,837304021523,854050101953,871131103992,888553726072,906324800593,924451296605,942940322537,961799128988,981035111568,1000655813799,1020668930075,1041082308677,1061903954850,1083142033947,1104804874626,1126900972119,1149438991561,1172427771392,1195876326820,1219793853356,1244189730424,1269073525032,1294454995533,1320344095443,1346750977352,1373685996899,1401159716837,1429182911174,1457766569398,1486921900785,1516660338801,1546993545577,1577933416489,1609492084819,1641681926515,1674515565045,1708005876346,1742165993873,1777009313750,1812549500025,1848800490026,1885776499827,1923492029823,1961961870420,2001201107828,2041225129984,2082049632584,2123690625236,2166164437741,2209487726495,2253677481025,2298751030646,2344726051259,2391620572284,2439452983730,2488242043404,2538006884272,2588767021958,2640542362397,2640542362397,39608135435955,79216270871910,158432541743820,316865083487640,633730166975280,1267460333950560,1901190500925840,3802381001851680,5703571502777520,8555357254166280,1.283303588124942E+16,6.41651794062479E+17,6.41651794062479E+17];
		
		if($lvl > 768){
			$ret = $exp[768];
			
			for($i = 768; $i < $lvl; $i++)
			{
				$ret *= 1.4;
			}
			
			if($ret > 4.3026616884253E+303)
				$ret = 4.3026616884253E+303;
			
			return $ret;
		}
		else
			return $exp[$lvl];
	}
}

?>