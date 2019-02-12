<?php
//=======================================
// show debug info
//=======================================
error_reporting(E_ALL);
ini_set('display_errors', true);

//=======================================
// parse ini file - first remove comment lines starting with # (php parser only accepts ; as comment)
//=======================================
$s = file_get_contents('../config/listen.ini');
$s = '';
$f = fopen('../config/listen.ini','r') or die("could not open ini file");
while( ($line = fgets($f,10000)) !== false) {
  if(trim($line) && trim($line)[0]!="#") $s.=$line;
}
fclose($f);
$ini = parse_ini_string($s,true);

//=======================================
// open database connection
//=======================================
$db = new mysqli($ini['db_host'],$ini['db_user'],$ini['db_pass'],$ini['db_name']);
if($db->connect_error) die("db connect error ".$db->connect_error);

