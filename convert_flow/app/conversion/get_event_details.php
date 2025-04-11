<?php
/**
 * 전환 이벤트 상세 정보 조회 API
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 모델 로드
require_once CF_INCLUDE_PATH . '/models/conversion.model.php';
$conversion_model = new ConversionModel();

// AJAX 요청 처리
header('Content-Type: application/json');

// 이벤트 ID 유효성 검사
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => '유효하지 않은 이벤트 ID입니다.'
    ]);
    exit;
}

$event_id = intval($_GET['id']);

// 이벤트 정보 조회
$sql = "SELECT ce.*, cs.conversion_type, c.name as campaign_name, c.user_id
        FROM {$cf_table_prefix}conversion_events ce
        LEFT JOIN {$cf_table_prefix}conversion_scripts cs ON ce.script_id = cs.id
        LEFT JOIN {$cf_table_prefix}campaigns c ON ce.campaign_id = c.id
        WHERE ce.id = '$event_id'";

$result = sql_query($sql);
$event = sql_fetch_array($result);

// 이벤트가 존재하지 않거나 접근 권한이 없는 경우
if (!$event || ($is_admin !== "super" && $event['user_id'] != $member['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => '존재하지 않거나 접근 권한이 없는 이벤트입니다.'
    ]);
    exit;
}

// 응답 데이터 구성
echo json_encode([
    'status' => 'success',
    'data' => $event
]);
?>