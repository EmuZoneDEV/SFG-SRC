<?php

$ssid = $_COOKIE["cptinfo"] ?? "";

$ssid = explode("_", $ssid);

if(count($ssid) != 2 || $ssid[0] == "no"){
	header("Location: index.php");
	
	exit();
}

$ssid = $ssid[0];

include "settings.php";

$qry = $db->prepare("SELECT ID FROM players WHERE ssid = :ssid");

$qry->bindParam(":ssid", $ssid);

$qry->execute();

if($qry->rowCount() < 1)
	exit('<!DOCTYPE html><html><body><script type="text/javascript"> window.reload(); </script></body></html>');

$pid = $qry->fetchAll()[0]["ID"];

$captcha = $_POST["g-recaptcha-response"] ?? "";

if($captcha != ""){
	$http = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$captchaSrv&response=$captcha");

	$json = json_decode($http, true);
	
	if($json["success"]){
		// Captcha solved, update player data
		
		$time = $CURRTIME + 450;
		
		$db->exec("UPDATE players SET lastCaptcha = $time WHERE ID = $pid");
		
		setcookie("cptinfo", "no");
		
		header("Location: index.php");
		
		exit();
	}
}

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Human Verification</title>
		<script src='https://www.google.com/recaptcha/api.js'></script>
	</head>
	<body>
		<center>
			<h1>Solve the captcha!</h1>
			<form method="post" action="captcha.php">
				<div class="g-recaptcha" data-sitekey="<?php echo $captchaCli; ?>"></div>
				<br><button style="font-size:20px;text-decoration:bold;">OK</button>
			</form>
		</center>
	</body>
</html>