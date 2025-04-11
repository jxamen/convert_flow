<?php
include_once($convert_flow_root . "/includes/_common.php");

$slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';

if (!$slug) {
    $response = array('available' => false);
    echo json_encode($response);
    exit;
}

// 슬러그 중복 확인
$sql = "SELECT COUNT(*) AS cnt FROM landing_pages WHERE slug = '{$slug}'";
$row = sql_fetch($sql);

$response = array('available' => ($row['cnt'] == 0));
echo json_encode($response);
?>