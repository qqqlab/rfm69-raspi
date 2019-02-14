<?php
require_once('config.php');


if(isset($_GET['delete_topic'])) {
  $topic = $_GET['delete_topic'];
  $db->query("delete from log where topic = '$topic'");
}

//group by interval in seconds
$interval=0;
if(isset($_GET['interval'])) $interval=$_GET['interval'];
if($interval<=0) $interval=300;


//========================================================
//graph
//========================================================
if(isset($_GET['t'])) {
  //map topic name to topic column 
  $col = $_GET['t'];
  $c=0;
  foreach($col as $k=>$v) $col[$k] = $c++;
  $colcnt = $c;

  //build the json data array with columns data,val1,val2,val3,...
  $topics = join("','",array_keys($_GET['t']));
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
  echo $db->error;

  $d = '[';
  $row = null;
  $ts_last = '';
  while($r = $res->fetch_row()) {
    //echo "$r[0] $r[1] $r[2]<br>";
    $ts = $r[0];
    $topic = $r[1];
    $val = $r[2];  

    //add row to data
    if($ts_last != $ts) {
      if($row) $d .= jsrow($ts, $row);
      $row = array_fill(0,$colcnt,'null');
      $ts_last = $ts;
    }
    $row[ $col[$topic] ] = $val;
  } 
  $d .= jsrow($ts, $row).']';

  $json_labels = '["date","'. join('","',array_keys($_GET['t'])) .'"]';
  graph($d, $json_labels) ;

//  echo "<pre>$d</pre>";
}


//==================================================
//form
//==================================================
echo "<br><br><br><form name='form1'>";

if(!@$_GET['byval']) {
  //group by node
  $res = $db->query("select distinct topic from log order by 1");
  $last_part1='';
  echo "<table border=0><tr>";
  while($r = $res->fetch_row()) {
    $topic = $r[0];
    $part1 = substr("$topic",0, strrpos($topic,'/')); //all exept last element
    $part2 = substr("$topic", (strrpos($topic,'/') + 1)); //last element
    if($last_part1 && $last_part1 != $part1) echo "</tr>";
    if($last_part1 != $part1) echo "<td><b>$part1</b></td><td>";
    echo "<input name='t[$topic]' type='checkbox' " . (@$_GET['t'][$topic] ? 'checked' : '') . ">";
    echo "$part2 ";
    $last_part1 = $part1;
  }
  echo "</tr></table>";
}else{
  //group by value
  $res = $db->query("select distinct topic from log order by substring_index(topic,'/',-1),1");
  $last_part2='';
  echo "<table border=0><tr>";
  while($r = $res->fetch_row()) {
    $topic = $r[0];
    $part1 = substr("$topic",0, strrpos($topic,'/')); //all exept last element
    $part2 = substr("$topic", (strrpos($topic,'/') + 1)); //last element
    if($last_part2 && $last_part2 != $part2) echo "</tr>";
    if($last_part2 != $part2) echo "<td><b>$part2</b></td><td>";
    echo "<input name='t[$topic]' type='checkbox' " . (@$_GET['t'][$topic] ? 'checked' : '') . ">";
    echo "$part1 ";
    $last_part2 = $part2;
  }
  echo "</tr></table>";
}
echo "<hr />";

//interval
echo "interval<input name='interval' value='$interval'> sec ";

//date range - TODO

//go button
echo "<input type='submit' value='go' name='go'>";

//check all/uncheck all
?>
<script type="text/javascript" language="javascript">// <![CDATA[
function checkAll(formname, checktoggle)
{
  var checkboxes = new Array(); 
  checkboxes = document[formname].getElementsByTagName('input');
 
  for (var i=0; i<checkboxes.length; i++)  {
    if (checkboxes[i].type == 'checkbox')   {
      checkboxes[i].checked = checktoggle;
    }
  }
}
// ]]></script>
<a onclick="javascript:checkAll('form1', true);" href="javascript:void();">check all</a>
<a onclick="javascript:checkAll('form1', false);" href="javascript:void();">uncheck all</a>
<?php

//group by value
echo "<input name='byval' type='checkbox' " . (@$_GET['byval'] ? 'checked' : '') . " onChange='this.form.submit()'>group by value";
echo "<hr />";

//some stats
$rows = $db->query("select count(*) from log")->fetch_row()[0];
$rows1h = $db->query("select count(*) from log where ts>current_timestamp-3600")->fetch_row()[0];
$rows1d = $db->query("select count(*) from log where ts>current_timestamp-24*3600")->fetch_row()[0];
$last = $db->query("select max(ts) from log")->fetch_row()[0];
echo "stats: $rows rows, $rows1d rows/day, $rows1h rows/hour, last $last";

echo "<hr />";
echo "delete topic:<input value='' name='delete_topic'>";
echo "</form>";





//javascript timestamp is in milliseconds
function jsrow($ts,$row) {
  return '[new Date('.$ts.'),'.join(',',$row)."],\n";
}


function graph($json_data,$json_labels) {
?>
<html>
<head>
<script type="text/javascript"
  src="dygraph.min.js"></script>
<link rel="stylesheet" src="dygraph.css" />
</head>
<body>
<div id="graphdiv" style="width:100%;height:80%"></div>
<script type="text/javascript">
  g = new Dygraph(

    // containing div
    document.getElementById("graphdiv"),
<?php echo $json_data; ?>,
{ labels: <?php echo $json_labels; ?> }
  );
</script>
<?php
}


