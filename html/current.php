<?php
//------------------------------------------------
//calculate current in uA
//------------------------------------------------

$dt_setpoint = 7200; //seconds
$incremental = false; //full rebuild
//$incremental = true; //incremental build
$capacity = 470; //in mF

//-------------------------------------------------

require_once('config.inc.php');

$res1 = $db->query("SELECT DISTINCT topic FROM log WHERE topic LIKE '%/vbat%' ORDER BY 1");
while($row1 = $res1->fetch_assoc()) {
  $topic = $row1['topic'];
  $curtopic = str_replace('vbat','cur',$topic);
  if(!$incremental) {
    $sql = "DELETE FROM log WHERE topic='$curtopic'";
    echo "$sql\n";
    $db->query($sql) or die($db->error);
  }
  $a = $db->query("SELECT ts,unix_timestamp(ts) as t,val FROM log WHERE topic='$topic' ORDER BY ts DESC")->fetch_all();
  $cnt = sizeof($a);
  echo "TOPIC:$topic -> $curtopic - $cnt rows ... ";
  $i2 = 1;
  foreach($a as $i1=>$r1){
    while($i2<$cnt) {
      $r2 = $a[$i2];
      $dt = $r1[1] - $r2[1];
      if($dt >= $dt_setpoint) {
        $current =  $capacity * ($r1[2] - $r2[2]) / $dt;
        if($dt < 2*$dt_setpoint) {
          //echo "time: $r1[0] - $r2[0] vbat: $r1[2] - $r2[2] dt: $dt cur: $current\n";
          $sql = "INSERT INTO log(ts,topic,val) VALUES ('$r1[0]','$curtopic','$current')";
          $rv = $db->query($sql);
          //echo "$sql -> $rv\n";
          if(!$rv) goto next_topic;
        }
        break;
      }
      $i2++;
    }
    if($i1 % 1000 == 999) echo ($i1+1)." ";
  }
next_topic:
}
