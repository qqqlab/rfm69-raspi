<?php
require_once('config.php');

//GET parameters
//t[topic]: topics to show (at least one required)
//topic: topic where clause, eg '%/vbat'
//interval: interval in seconds, default 300
//title: graph title
$t = @$_GET['t'];
$topic = @$_GET['topic']; 
$interval = @$_GET['interval'];
$title = @$_GET['title'];

//defaults
if($interval<=0) $interval=300;

//add topics to $t by wildcard
if($topic) {
  $res = $db->query("SELECT DISTINCT topic FROM log WHERE topic like '$topic'");
  while($row = $res->fetch_row()) {
    $t[$row[0]]=1;
  }
}

if(!$t) die("ERROR: specify at least one topic");

//map topic name to topic column 
$col = $t;
$c=0;
foreach($col as $k=>$v) $col[$k] = $c++; 
$colcnt = $c;

//build the json data array with columns data,val1,val2,val3,...
$topics = join("','",array_keys($t));
$sql = "
SELECT 
  (UNIX_TIMESTAMP(ts) DIV $interval) * $interval * 1000, 
  topic,
  avg(val) 
FROM log 
WHERE topic in ('$topics') 
GROUP BY 1,2 ORDER BY 1
";
$res = $db->query($sql);
if($db->error) die("ERROR: ".$db->error);

$d = '';
$row = null;
$ts_last = '';
while($r = $res->fetch_row()) {
  //echo "$r[0] $r[1] $r[2]<br>";
  $ts = $r[0];
  $topic = $r[1];
  $val = $r[2];  

  //add row to data
  if($ts_last != $ts) {
    if($row) $d .= '[new Date('.$ts.'),'.join(',',$row)."],\n";
    $row = array_fill(0,$colcnt,'null');
    $ts_last = $ts;
  }
  $row[ $col[$topic] ] = $val;
} 
$d .= '[new Date('.$ts.'),'.join(',',$row)."],\n";

$json_labels = '["date","'. join('","',array_keys($t)) .'"]';


//output the graph
?>
<html>
<head>
<script type="text/javascript" src="dygraph.min.js"></script>
<link rel="stylesheet" src="dygraph.css" />
<style>
  .dygraph-title { text-align: center; }
  .dygraph-legend { text-align: right; }
</style>
</head>
<body style="padding:0px;margin:0px">
<div id="graphdiv" style="width:100%;height:100%;padding:0px;margin:0px"></div>
<script type="text/javascript">
g = new Dygraph(document.getElementById("graphdiv"),
<?php
echo "[$d],{";
echo "labels: $json_labels,";
echo "title: '$title',";
?>
legend: 'always',
labelsSeparateLines: true,
});
</script>

