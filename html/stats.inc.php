<?php
require_once('config.inc.php');

//some stats
$rows = $db->query("select count(*) from log")->fetch_row()[0];
$rows1h = $db->query("select count(*) from log where ts>current_timestamp-3600")->fetch_row()[0];
$rows1d = $db->query("select count(*) from log where ts>current_timestamp-24*3600")->fetch_row()[0];
$last = $db->query("select max(ts) from log")->fetch_row()[0];
echo "stats: $rows rows, $rows1d rows/day, $rows1h rows/hour, last $last";

