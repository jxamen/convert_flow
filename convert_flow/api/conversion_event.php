<?php
/**
 * 전환 이벤트 처리 API
 * 
 * 클라이언트에서 전송된 전환 이벤트를 처리하고 데이터베이스에 저장합니다.
 * 
 * 요청 매개변수:
 * - action: 'pageview' 또는 'conversion'
 * - campaign: 캠페인 해시
 * - type: 전환 유형
 * - value: 전환 가치 (옵션)
 * - url: 현재 페이지 URL
 * - referrer: 이전 페이지 URL
 * - utm_*: UTM 매개변수
 */

// 헤더 설정
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// CORS preflight 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// 상수 정의
define('_CONVERT_FLOW_', true);

// 공통 파일 로드
require_once dirname(__FILE__, 2) . '/include/common.php';

// 요청 매개변수 확인
if (!isset($_POST['action']) || !isset($_POST['campaign'])) {
    echo json_encode([
        'status' => 'error',
        'message' => '필수 매개변수가 누락되었습니다.'
    ]);
    exit;
}

// 모델 로드
require_once CF_INCLUDE_PATH . '/models/campaign.model.php';
require_once CF_INCLUDE_PATH . '/models/conversion.model.php';

$campaign_model = new CampaignModel();
$conversion_model = new ConversionModel();

// 캠페인 해시로 캠페인 정보 조회
$campaign = $campaign_model->get_campaign_by_hash($_POST['campaign']);

if (!$campaign) {
    echo json_encode([
        'status' => 'error',
        'message' => '유효하지 않은 캠페인입니다.'
    ]);
    exit;
}

// 액션에 따른 처리
$action = $_POST['action'];

switch ($action) {
    case 'pageview':
        // 페이지뷰 기록
        processPageview($campaign, $conversion_model);
        break;
        
    case 'conversion':
        // 전환 이벤트 기록
        processConversion($campaign, $conversion_model);
        break;
        
    default:
        echo json_encode([
            'status' => 'error',
            'message' => '유효하지 않은 액션입니다.'
        ]);
        exit;
}

/**
 * 페이지뷰 처리
 */
function processPageview($campaign, $conversion_model) {
    // 전환 스크립트 정보 조회
    $conversion_type = isset($_POST['type']) ? $_POST['type'] : '기타';
    $scripts = $conversion_model->get_conversion_scripts($campaign['id']);
    
    // 스크립트가 없는 경우, 새로 생성
    $script_id = 0;
    
    foreach ($scripts as $script) {
        if ($script['conversion_type'] == $conversion_type) {
            $script_id = $script['id'];
            break;
        }
    }
    
    if (!$script_id) {
        // 자동 스크립트 생성
        $script_data = $conversion_model->generate_script_code($campaign['id'], $conversion_type);
        
        if ($script_data) {
            $script_id = $conversion_model->create_conversion_script([
                'campaign_id' => $campaign['id'],
                'conversion_type' => $conversion_type,
                'script_code' => $script_data['script_code'],
                'installation_guide' => $script_data['installation_guide']
            ]);
        }
    }
    
    if (!$script_id) {
        echo json_encode([
            'status' => 'error',
            'message' => '전환 스크립트를 생성할 수 없습니다.'
        ]);
        exit;
    }
    
    // 페이지뷰 정보 구성
    $data = [
        'script_id' => $script_id,
        'source' => isset($_POST['utm_source']) ? $_POST['utm_source'] : '',
        'medium' => isset($_POST['utm_medium']) ? $_POST['utm_medium'] : '',
        'campaign' => isset($_POST['utm_campaign']) ? $_POST['utm_campaign'] : '',
        'utm_content' => isset($_POST['utm_content']) ? $_POST['utm_content'] : '',
        'utm_term' => isset($_POST['utm_term']) ? $_POST['utm_term'] : '',
        'conversion_value' => 0, // 페이지뷰는 전환 가치가 없음
        'additional_data' => [
            'url' => isset($_POST['url']) ? $_POST['url'] : '',
            'referrer' => isset($_POST['referrer']) ? $_POST['referrer'] : ''
        ]
    ];
    
    // 이벤트 기록
    $result = $conversion_model->record_conversion_event($data);
    
    echo json_encode([
        'status' => 'success',
        'message' => '페이지뷰가 기록되었습니다.',
        'event_id' => $result
    ]);
    exit;
}

/**
 * 전환 이벤트 처리
 */
function processConversion($campaign, $conversion_model) {
    // 전환 스크립트 정보 조회
    $conversion_type = isset($_POST['type']) ? $_POST['type'] : '기타';
    $scripts = $conversion_model->get_conversion_scripts($campaign['id']);
    
    // 스크립트가 없는 경우, 새로 생성
    $script_id = 0;
    
    foreach ($scripts as $script) {
        if ($script['conversion_type'] == $conversion_type) {
            $script_id = $script['id'];
            break;
        }
    }
    
    if (!$script_id) {
        // 자동 스크립트 생성
        $script_data = $conversion_model->generate_script_code($campaign['id'], $conversion_type);
        
        if ($script_data) {
            $script_id = $conversion_model->create_conversion_script([
                'campaign_id' => $campaign['id'],
                'conversion_type' => $conversion_type,
                'script_code' => $script_data['script_code'],
                'installation_guide' => $script_data['installation_guide']
            ]);
        }
    }
    
    if (!$script_id) {
        echo json_encode([
            'status' => 'error',
            'message' => '전환 스크립트를 생성할 수 없습니다.'
        ]);
        exit;
    }
    
    // 추가 데이터 구성
    $additional_data = [
        'url' => isset($_POST['url']) ? $_POST['url'] : '',
        'referrer' => isset($_POST['referrer']) ? $_POST['referrer'] : ''
    ];
    
    // POST 데이터에서 추가 정보 추출
    foreach ($_POST as $key => $value) {
        if (!in_array($key, ['action', 'campaign', 'type', 'url', 'referrer', 'utm_source', 
                            'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'value'])) {
            $additional_data[$key] = $value;
        }
    }
    
    // 전환 정보 구성
    $data = [
        'script_id' => $script_id,
        'source' => isset($_POST['utm_source']) ? $_POST['utm_source'] : '',
        'medium' => isset($_POST['utm_medium']) ? $_POST['utm_medium'] : '',
        'campaign' => isset($_POST['utm_campaign']) ? $_POST['utm_campaign'] : '',
        'utm_content' => isset($_POST['utm_content']) ? $_POST['utm_content'] : '',
        'utm_term' => isset($_POST['utm_term']) ? $_POST['utm_term'] : '',
        'conversion_value' => isset($_POST['value']) ? floatval($_POST['value']) : 0,
        'additional_data' => $additional_data
    ];
    
    // 이벤트 기록
    $result = $conversion_model->record_conversion_event($data);
    
    echo json_encode([
        'status' => 'success',
        'message' => '전환 이벤트가 기록되었습니다.',
        'event_id' => $result
    ]);
    exit;
}
