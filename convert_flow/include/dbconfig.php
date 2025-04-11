<?php
if (!defined('_CONVERT_FLOW_')) exit;

// 데이터베이스 연결 정보
$cf_mysql_host = 'localhost';
$cf_mysql_user = 'root';
$cf_mysql_password = '';
$cf_mysql_db = 'convert_flow';
$cf_mysql_port = '3306';
$cf_table_prefix = 'cf_'; // 테이블 접두사

// connection
$conn = mysqli_connect($cf_mysql_host, $cf_mysql_user, $cf_mysql_password, $cf_mysql_db, $cf_mysql_port);
if (!$conn) {
    die("데이터베이스 연결 실패: " . mysqli_connect_error());
}

// charset 설정
mysqli_set_charset($conn, CF_DB_CHARSET);
