<?php
include_once($convert_flow_root . "/includes/_common.php");

if ($is_admin != "super") {
    $response = array('status' => 'error', 'message' => '관리자만 접근 가능합니다.');
    echo json_encode($response);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if (!$id) {
    $response = array('status' => 'error', 'message' => '잘못된 요청입니다.');
    echo json_encode($response);
    exit;
}

// 템플릿 정보 조회
$sql = "SELECT thumbnail_url FROM landing_page_templates WHERE id = '{$id}'";
$row = sql_fetch($sql);

if (!$row) {
    $response = array('status' => 'error', 'message' => '존재하지 않는 템플릿입니다.');
    echo json_encode($response);
    exit;
}

// 썸네일 이미지 삭제
if ($row['thumbnail_url']) {
    @unlink(G5_DATA_PATH . '/landing_templates/' . basename($row['thumbnail_url']));
}

// 템플릿 삭제
$sql = "DELETE FROM landing_page_templates WHERE id = '{$id}'";
sql_query($sql);

// 응답 전송
$response = array('status' => 'success', 'message' => '템플릿이 삭제되었습니다.');
echo json_encode($response);
?>