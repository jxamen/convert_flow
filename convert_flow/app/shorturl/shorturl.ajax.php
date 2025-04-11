<?php
/**
 * 단축 URL AJAX 처리 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 모델 로드
require_once CF_MODEL_PATH . '/shorturl.model.php';
$shorturl_model = new ShorturlModel();

// 액션 체크
if (empty($_GET['action'])) {
    echo json_encode(array('error' => '잘못된 요청입니다.'));
    exit;
}

$action = $_GET['action'];

// 랜딩페이지 목록 조회
if ($action === 'get_landing_pages') {
    if (empty($_GET['campaign_id'])) {
        echo json_encode(array());
        exit;
    }
    
    $campaign_id = intval($_GET['campaign_id']);
    
    $sql = "SELECT id, name FROM {$cf_table_prefix}landing_pages 
            WHERE user_id = '{$member['id']}' AND campaign_id = '$campaign_id' 
            ORDER BY name ASC";
    $result = sql_query($sql);
    
    $landing_pages = array();
    while($row = sql_fetch_array($result)) {
        $landing_pages[] = $row;
    }
    
    echo json_encode($landing_pages);
    exit;
}

// 소재 유형 목록 조회
if ($action === 'get_source_types') {
    $campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
    
    $types = $shorturl_model->get_source_types($campaign_id);
    
    echo json_encode($types);
    exit;
}

// 소재 이름 목록 조회
if ($action === 'get_source_names') {
    if (empty($_GET['source_type'])) {
        echo json_encode(array());
        exit;
    }
    
    $source_type = $_GET['source_type'];
    $campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
    
    $names = $shorturl_model->get_source_names($source_type, $campaign_id);
    
    echo json_encode($names);
    exit;
}

// 알 수 없는 액션
echo json_encode(array('error' => '지원되지 않는 요청입니다.'));
exit;
?>