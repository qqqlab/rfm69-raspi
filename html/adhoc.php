<?php
require_once('config.inc.php');
require_once('graph.inc.php');

if(isset($_GET['delete_topic'])) {
  $topic = $_GET['delete_topic'];
  $db->query("delete from log where topic = '$topic'");
}

echo '<html><head>';
graph_head();
echo '</head><body>';
echo '<div id="g1" style="width:100%;height:80%"></div>';
if(@$_GET['t']) graph('g1',$_GET);

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
echo "interval<input name='interval' value='".@$_GET['interval']."'> sec ";

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
include('stats.inc.php');
echo "<hr />";

echo "delete topic:<input value='' name='delete_topic'>";
echo "</form>";

echo '</body></html>';
