<?php

/***** SERVER SETTINGS *****/

// Gregplay.hu

// System settings
$gameName = "Shakes & Fidget Online";
$serverName = "s1";
$sandbox = true; // Sandbox mode, use TRUE only if you develope the script, it'll display errors and more info about not working things
$db = new PDO('mysql:host=localhost;dbname=sfgame;charset=utf8', 'root', 'password');
$passSalt = 'O2ZJJAnAPbKBxqIq4AcYLRsw9mA3D2D3';
$timezone = 'Europe/Budapest';
date_default_timezone_set($timezone);
$CURRTIME = time(); // If there are problems with the timezone, you can add to it.
$clientWeb = "localhost"; // Domain of the server
$imgServer = "http://img.unisfgame.ml";
$imgServer = "http://localhost/res/img/save";
$swf_version = 301;
$serverver = 1268; // Server version

// Captcha anti-cheat settings

//$enableCaptcha = true;
$captchaCli = ""; // Google reCaptcha v2 - client key
$captchaSrv = ""; // Google reCaptcha v2 - server key (secret)

// Welcome mail settings
$wmail_enable = false; // Enable welcome mail? (wasting database space - Greg)

$wmail_subject = 'Willkommen!'; // Subject

// The mail
$wmail_body = ''; // Mail body, Use $b for new lines

// Game settings
$xpbonus = 5;
$goldbonus = 8;
$currEvent = -1; // Current event:  -1 = automatic, 0 = Nothing, 1 = XP, 2 = Epic, 3 = Gold, 4 = Mushroom, 5 = birthday, 6 = Christmas, 7 = Easter, 8 = Halloween, 9 = oktoberfest
$enable_mushroom_event = false; // Set it to true on small amount of mushroom servers
$event_onlyWeekend = true; // Normal events only on weekends
$event_xpbonus = 8; // Special event xp
$event_goldbonus = 13; // Special event gold
$epicbonus = 20; // Epicchance, understand it in sf/item.php
$event_epicbonus = 40;
$mushbonus = 3;
$event_mushbonus = 1;
$xpGenVersion = 1; // XP Generation version (1 = easy, 2 = harder)
$weaponMultipliers = [2.3, 5.5, 2.8];
$statPlus = 5; // How much stat he gets on one click
$levelLimit = 2700; // Level limit
$startingGold = 1000; // Starting silver
$startingMush = 10; // Starting mushroom
$defAllDungUnlocked = true; // Unlock all dungeons when you register
$justBestPots = false; // Only XXL potions
$defUnderworld = true; // Default underworld unlocked? (false not working for now)