<?php
require_once('graph.inc.php');
echo '
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
';
graph_head();
echo '
<style>
* {
  box-sizing: border-box;
}

/* Create two equal columns that floats next to each other */
.col {
  float: left;
  width: 50%;
  padding: 0px;
  height: 40vh; 
}

/* Clear floats after the columns */
.row:after {
  content: "";
  display: table;
  clear: both;
}
</style>

  </head>
  <body>
';
include "stats.inc.php";
echo '
<div class="row"><div class="col" id="g1"></div><div class="col" id="g2"></div></div>
<div class="row"><div class="col" id="g3"></div><div class="col" id="g4"></div></div> 
<div class="row"><div class="col" id="g5"></div><div class="col" id="g6"></div></div>
';

graph('g1',['title'=>'vbat','topic'=>'%/vbat%']);
graph('g2',['title'=>'lux','topic'=>'%/lux']);
graph('g3',['title'=>'vcc','topic'=>'%/vcc']);
graph('g4',['title'=>'vref','topic'=>'%/vref1']);
graph('g5',['title'=>'rssi','topic'=>'%/rssi']);
graph('g6',['title'=>'uptime','topic'=>'%/sec']);

echo '</body></html>';
