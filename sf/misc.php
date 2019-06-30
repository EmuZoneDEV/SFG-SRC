<?php

// By Greg 100%

class Misc
{
	public static function getEvent() {
		$ce = &$GLOBALS['currEvent'];
		
		if ($ce == -1)
		{
			$year = date('Y');
			$month = date('n');
			
			$count = 0;
			$day_count = cal_days_in_month(CAL_GREGORIAN, $month, $year);

			for ($i = 1; $i <= $day_count; $i++)
			{
				$date = $year . '/' . $month . '/'. $i;
				$get_name = date('l', strtotime($date));
				$day_name = substr($get_name, 0, 3);
				
				if($day_name == 'Sat')
				{
					$count++;
				}
			}
			
			$ce = $count % 5;
			if ($ce == 0)
				$ce = 1;
		}
		
		$b1 = 0; // XP Bonus
		$b2 = 0; // Gold Bonus
		$b3 = 0; // Mush Bonus
		$b4 = 0; // Epic Bonus
		
		$wd = date('D');
		
		if ($ce > 4 && $ce < 10) {
			// All bonus
			$b1 = 1;
			$b2 = 1;
			$b3 = 1;
			$b4 = 1;
		}else{
			// No weeekend?
			if ($wd != 'Sat' && $wd != 'Sun' && $GLOBALS["event_onlyWeekend"]) {
				return array(0, 0, 0, 0, 0); // Return no event
			}
			
			switch($ce) {
				case 1 :
					$b1 = 1;
				break;
				case 2 :
					$b4 = 1;
				break;
				case 3 :
					$b2 = 1;
				break;
				case 4 :
					$b3 = 1;
				break;
			}
		}
		return array($ce, $b1, $b2, $b3, $b4);
	}
	
	public static function getNow($new = true)
	{
		if (!$new)
			return floor(($GLOBALS["CURRTIME"] - strtotime("2010-01-01")) / 86400) % 365;
		
		$datetime1 = new DateTime("2010-01-01");

		$datetime2 = new DateTime(date("Y-m-d", $GLOBALS["CURRTIME"]));
		
		$difference = $datetime1->diff($datetime2);
		
		$now = $difference->m * 32 + $difference->d;
		
		return $now;
	}
	
	public static function biggest($nums)
	{
		if(!is_array($nums))
			return $nums;
		
		$ret = 0;
		
		for($i = 0; $i < count($nums); $i++)
		{
			if ($nums[$i] > $ret)
				$ret = $nums[$i];
		}
		
		return $ret;
	}
	
	public static function isIpBlocked($ip)
	{
		// By Greg
		
		$qry = $GLOBALS["db"]->prepare("SELECT Count(id) AS c FROM ipbans WHERE ip = :ip");
		
		$qry->bindParam(":ip", $ip);
		
		$qry->execute();
		
		return $qry->fetchAll()[0]["c"] > 0;
	}
	
	public static function isNameAllowed($name){
		if(strlen($name) > 26 || strlen($name) < 3)
			return false;
		
		if(preg_match('/[^A-Za-z0-9 ]/', $name))
			return false;
		
		if(
			preg_match('/admin/i', strtolower($name)) ||
			preg_match('/tulaj/i', strtolower($name)) ||
			preg_match('/system/i', strtolower($name)) ||
			preg_match('/owner/i', strtolower($name)) ||
			preg_match('/staff/i', strtolower($name))
		){
			return false;
		}
		
		if(is_numeric($name))
			return false;
		
		return true;
	}
}