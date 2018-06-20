<?php
require_once('./../config.php');
date_default_timezone_set('America/Los_Angeles');
$username = $_REQUEST['username'];
$layout_id = $_REQUEST['layout'];
$period_id = $_REQUEST['period'];
$survey_date = date("Y-m-d h:i:s A");

$dbh = new PDO($dbhost, $dbh_insert_user, $dbh_insert_pw);
$dbh->beginTransaction();
$survey_r_query = $dbh->prepare('INSERT INTO survey_record (surveyed_by, layout_id, survey_date, survey_period_id)
                                 VALUES (:username, :lay_id, :in_date, :in_period)');


$dbh->setAttribute(PDO::ATTR_AUTOCOMMIT, FALSE);

$survey_r_query->bindParam(':username', $username, PDO::PARAM_STR);
$survey_r_query->bindParam(':lay_id', $layout_id, PDO::PARAM_INT);
$survey_r_query->bindParam(':in_date', $survey_date, PDO::PARAM_STR);
$survey_r_query->bindParam(':in_period', $period_id, PDO::PARAM_INT);

$survey_r_query->execute();
$data = array('survey_id' => $dbh->lastInsertId());
$dbh->commit();

print json_encode($data);
