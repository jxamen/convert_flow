<?php
include_once($_SERVER['DOCUMENT_ROOT']."/manager/common.php");
include_once(__DIR__ . '/config.php');
include_once(__DIR__ . '/functions.php');

$member['id'] = 1;

$connect_db = sql_connect(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD) or die('MySQL Connect Error!!!');
$select_db  = sql_select_db("convert_flow", $connect_db) or die('MySQL DB Error!!!');

// mysql connect resource $g5 배열에 저장 - 명랑폐인님 제안
$g5['connect_db'] = $connect_db;
?>