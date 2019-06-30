setInterval(function(){
	if(typeof canRunAds == 'undefined' || canRunAds !== true)
		window.location.href = "adblock.php";
}, 1000);