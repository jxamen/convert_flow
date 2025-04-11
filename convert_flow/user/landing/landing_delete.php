<?php
include_once("../../includes/_common.php");

if (!$is_member) {
    $response = array('status' => 'error', 'message' => '로그인이 필요한 서비스입니다.');
    echo json_encode($response);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if (!$id) {
    $response = array('status' => 'error', 'message' => '잘못된 요청입니다.');
    echo json_encode($response);
    exit;
}

// 랜딩페이지 정보 조회 및 소유권 확인
$sql = "SELECT * FROM landing_pages WHERE id = '{$id}' AND user_id = '{$member['mb_id']}'";
$row = sql_fetch($sql);

if (!$row) {
    $response = array('status' => 'error', 'message' => '존재하지 않는 랜딩페이지이거나 삭제 권한이 없습니다.');
    echo json_encode($response);
    exit;
}

// 랜딩페이지 삭제
$sql = "DELETE FROM landing_pages WHERE id = '{$id}' AND user_id = '{$member['mb_id']}'";
sql_query($sql);

// 응답 전송
$response = array('status' => 'success', 'message' => '랜딩페이지가 삭제되었습니다.');
echo json_encode($response);
?>