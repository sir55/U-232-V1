<?php
/**
 *   http://btdev.net:1337/svn/test/Installer09_Beta
 *   Licence Info: GPL
 *   Copyright (C) 2010 BTDev Installer v.1
 *   A bittorrent tracker source based on TBDev.net/tbsource/bytemonsoon.
 *   Project Leaders: Mindless,putyn.
 **/
/*
+------------------------------------------------
|   $Date$ 181010
|   $Revision$ 2.0
|   $Author$ laffin-stonebreath
|   $update09 Bigjoos
|   $URL$
|   $qlogin
|   
+------------------------------------------------
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'bittorrent.php');
dbconn();
//== 09 failed logins thanks to pdq - Retro
function failedloginscheck () {
global $TBDEV;
$total = 0;
$ip = sqlesc(getip());
$res = sql_query("SELECT SUM(attempts) FROM failedlogins WHERE ip=$ip") or sqlerr(__FILE__, __LINE__);
list($total) = mysql_fetch_row($res);
if ($total >= $TBDEV['failedlogins']) {
mysql_query("UPDATE failedlogins SET banned = 'yes' WHERE ip=$ip") or sqlerr(__FILE__, __LINE__);
stderr("Login Locked!", "You have been <b>Exceeded</b> the allowed maximum login attempts without successful login, therefore your ip address <b>(".htmlspecialchars($ip).")</b> has been locked for 24 hours.");
}
}
//==End

failedloginscheck();
if (!mkglobal("qlogin") || (strlen($qlogin=htmlspecialchars($qlogin))!=96))
	die(n00b);
function bark($text = "<b>LOL n00b</b>: This not a file for you!")
{
  stderr("Error", $text);
}

$hash1 = substr($qlogin,0,32);
$hash2 = substr($qlogin,32,32);
$hash3 = substr($qlogin,64,32);
$hash1 .= $hash2.$hash3;

$res = sql_query("SELECT id, username, passhash, enabled FROM users WHERE hash1 = ".sqlesc($hash1)." AND class >= ".UC_MODERATOR." AND status = 'confirmed' LIMIT 1");
$row = mysql_fetch_assoc($res);

if (!$row) {
$ip = sqlesc(getip());
$added = sqlesc(time());
$fail = (@mysql_fetch_row(@sql_query("select count(*) from loginattempts where ip=$ip"))) or sqlerr(__FILE__, __LINE__);
if ($fail[0] == 0)
sql_query("INSERT INTO loginattempts (ip, added, attempts) VALUES ($ip, $added, 1)") or sqlerr(__FILE__, __LINE__);
else
sql_query("UPDATE loginattempts SET attempts = attempts + 1 where ip=$ip") or sqlerr(__FILE__, __LINE__);
@fclose(@fopen(''.$dictbreaker.'/'.sha1($_SERVER['REMOTE_ADDR']),'w'));
bark();
}
if ($row['enabled'] == 'no')
bark("This account has been disabled.");
$passh = md5($row["passhash"].$_SERVER["REMOTE_ADDR"]);
logincookie($row["id"], $passh);

$ip = sqlesc(getip());
mysql_query("DELETE FROM loginattempts WHERE ip = $ip");

$HTMLOUT='';

$HTMLOUT .="<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN'
   'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>

<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
<title>{$TBDEV['site_name']} Redirecting</title>
<meta http-equiv='Refresh' content='1; URL=index.php' />
</head>
<body>
<p><br /></p>
<p><br /></p>
<p><br /></p>
<p><br /></p>
<p></p>
<p align='center'><strong>Welcome Back - ".htmlspecialchars($row['username']).".</strong></p><br />
<p align='center'><strong>Click <a href='index.php'>here</a> if you are not redirected automatically.</strong></p><br />
</body>
</html>";

print $HTMLOUT;
?>