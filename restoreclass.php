<?php
/**
 *   http://btdev.net:1337/svn/test/Installer09_Beta
 *   Licence Info: GPL
 *   Copyright (C) 2010 BTDev Installer v.1
 *   A bittorrent tracker source based on TBDev.net/tbsource/bytemonsoon.
 *   Project Leaders: Mindless,putyn.
 **/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'bittorrent.php');
require_once(INCL_DIR.'user_functions.php');
dbconn(false);
loggedinorreturn();
sql_query("UPDATE users SET override_class='255' WHERE id = " . $CURUSER['id']);
$mc1->delete_value('MyUser_'.$CURUSER['id']);
header("Location: {$TBDEV['baseurl']}/index.php");
die();

?>