<?php
session_start();
require_once('./../config.php');

$get_id =  $_REQUEST['survey_id'];


$dbh = new PDO($dbhost, $dbh_select_user, $dbh_select_pw);

$stmt1 = $dbh->prepare("SELECT survey_id, survey_record.layout_id, survey_date, floor FROM survey_record JOIN layout ON survey_record.layout_id = layout.layout_id WHERE survey_id = :get_id");
/*statment for after layout is selected*/
$stmt1->bindParam(':get_id', $get_id, PDO::PARAM_STR);

$stmt1->execute();

$survey_id_result = $stmt1->fetchAll();

print json_encode($survey_id_result);
