<?php

$db = new mysqli('','root','blabla','sensor');

if($db->connect_error) die("db connect error ".$db->connect_error);

