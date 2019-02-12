<?php
require_once('config.php');

//group by interval in seconds
if(isset($_GET['interval'])) $interval=$_GET['interval'];
if($interval<=0) $interval=300;


//========================================================
//graph
//========================================================
if(isset($_GET['go'])) {
  //map topic name to topic column 
  $col = $_GET['t'];
  $c=0;
  foreach($col as $k=>$v) $col[$k] = $c++;
  $colcnt = $c;

  //build the json data array with columns data,val1,val2,val3,...
  $topics = join("','",array_keys($_GET['t']));
  $sql = "
SELECT 
  FROM_UNIXTIME((UNIX_TIMESTAMP(ts) DIV $interval) * $interval), 
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
}


$json_labels = '["date","'. join('","',array_keys($_GET['t'])) .'"]';
graph($d, $json_labels) ;











//==================================================
//form
//==================================================
echo "<br><br><br><form name='form1'>";

$res = $db->query("select distinct topic from log order by 1");
$i=0;
echo "<table border=0><tr>";
while($r = $res->fetch_row()) {
  $topic = $r[0];
  echo "<td>";
  echo "<input name='t[$topic]' type='checkbox' " . ($_GET['t'][$topic] ? 'checked' : '') . ">$topic ";
  echo "</td>";
  $i++;
  if($i%5==0) echo '</tr><tr>';
}
echo "</tr></table>";

echo "interval<input name='interval' value='$interval'> sec ";
echo "<input type='submit' value='go' name='go'>";
echo "</form>";

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



function jsrow($ts,$row) {
  return '[ new Date("'.$ts.'"),'.join(',',$row)."],\n";
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
<div id="graphdiv" style="width:100%;height:100%"></div>
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
