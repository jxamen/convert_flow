<?php
/**
 * 전환 스크립트 관련 AJAX 처리
 */

// 상수 정의
define('_CONVERT_FLOW_', true);

// 공통 파일 로드
include_once '../include/common.php';

// 로그인 체크
if (!$is_member) {
    echo json_encode(['status' => 'error', 'message' => '로그인이 필요합니다.']);
    exit;
}

// CSRF 방지를 위한 Referer 확인
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
if (!$referer || parse_url($referer, PHP_URL_HOST) !== $_SERVER['HTTP_HOST']) {
    echo json_encode(['status' => 'error', 'message' => '잘못된 접근입니다.']);
    exit;
}

// 모델 로드
require_once CF_INCLUDE_PATH . '/models/campaign.model.php';
require_once CF_INCLUDE_PATH . '/models/conversion.model.php';
$campaign_model = new CampaignModel();
$conversion_model = new ConversionModel();

// 요청 액션 확인
$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    // 스크립트 미리보기
    case 'preview':
        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
        $conversion_type = isset($_POST['conversion_type']) ? $_POST['conversion_type'] : '';
        
        // 기본 유효성 검사
        if (!$campaign_id || !$conversion_type) {
            echo json_encode(['status' => 'error', 'message' => '필수 파라미터가 누락되었습니다.']);
            exit;
        }
        
        // 캠페인 확인
        $campaign = $campaign_model->get_campaign($campaign_id);
        if (!$campaign || $campaign['user_id'] != $member['id']) {
            echo json_encode(['status' => 'error', 'message' => '유효하지 않은 캠페인입니다.']);
            exit;
        }
        
        // 스크립트 코드 생성
        $script_data = $conversion_model->generate_script_code($campaign_id, $conversion_type);
        
        if (!$script_data) {
            echo json_encode(['status' => 'error', 'message' => '스크립트 생성 중 오류가 발생했습니다.']);
            exit;
        }
        
        // 결과 반환
        echo json_encode([
            'status' => 'success',
            'script_code' => $script_data['script_code'],
            'installation_guide' => $script_data['installation_guide']
        ]);
        break;
        
    // 전환 스크립트 삭제
    case 'delete':
        $script_id = isset($_POST['script_id']) ? intval($_POST['script_id']) : 0;
        
        // 기본 유효성 검사
        if (!$script_id) {
            echo json_encode(['status' => 'error', 'message' => '필수 파라미터가 누락되었습니다.']);
            exit;
        }
        
        // 스크립트 확인
        $script = $conversion_model->get_conversion_script($script_id);
        if (!$script) {
            echo json_encode(['status' => 'error', 'message' => '존재하지 않는 스크립트입니다.']);
            exit;
        }
        
        // 캠페인 확인 (소유자 체크)
        $campaign = $campaign_model->get_campaign($script['campaign_id']);
        if (!$campaign || $campaign['user_id'] != $member['id']) {
            echo json_encode(['status' => 'error', 'message' => '권한이 없습니다.']);
            exit;
        }
        
        // 스크립트 삭제
        $result = $conversion_model->delete_conversion_script($script_id);
        if (!$result) {
            echo json_encode(['status' => 'error', 'message' => '스크립트 삭제 중 오류가 발생했습니다.']);
            exit;
        }
        
        // 결과 반환
        echo json_encode([
            'status' => 'success',
            'message' => '전환 스크립트가 성공적으로 삭제되었습니다.'
        ]);
        break;
        
    // 전환 이벤트 요약 조회
    case 'event_summary':
        $script_id = isset($_POST['script_id']) ? intval($_POST['script_id']) : 0;
        
        // 기본 유효성 검사
        if (!$script_id) {
            echo json_encode(['status' => 'error', 'message' => '필수 파라미터가 누락되었습니다.']);
            exit;
        }
        
        // 스크립트 확인
        $script = $conversion_model->get_conversion_script($script_id);
        if (!$script) {
            echo json_encode(['status' => 'error', 'message' => '존재하지 않는 스크립트입니다.']);
            exit;
        }
        
        // 캠페인 확인 (소유자 체크)
        $campaign = $campaign_model->get_campaign($script['campaign_id']);
        if (!$campaign || $campaign['user_id'] != $member['id']) {
            echo json_encode(['status' => 'error', 'message' => '권한이 없습니다.']);
            exit;
        }
        
        // 전환 이벤트 요약 조회
        $sql = "SELECT 
                COUNT(*) as total_events,
                SUM(CASE WHEN conversion_value > 0 THEN 1 ELSE 0 END) as total_conversions,
                SUM(conversion_value) as total_value,
                COUNT(DISTINCT DATE(conversion_time)) as active_days,
                MAX(conversion_time) as last_conversion
                FROM {$cf_table_prefix}conversion_events
                WHERE script_id = '$script_id'";
        $summary = sql_fetch($sql);
        
        // 소스별 통계
        $sql = "SELECT 
                IFNULL(source, '직접 방문') as source,
                COUNT(*) as count,
                SUM(CASE WHEN conversion_value > 0 THEN 1 ELSE 0 END) as conversions
                FROM {$cf_table_prefix}conversion_events
                WHERE script_id = '$script_id'
                GROUP BY IFNULL(source, '직접 방문')
                ORDER BY count DESC
                LIMIT 5";
        $source_result = sql_query($sql);
        
        $sources = [];
        while ($row = sql_fetch_array($source_result)) {
            $sources[] = $row;
        }
        
        // 기기별 통계
        $sql = "SELECT 
                IFNULL(device_type, '알 수 없음') as device_type,
                COUNT(*) as count,
                SUM(CASE WHEN conversion_value > 0 THEN 1 ELSE 0 END) as conversions
                FROM {$cf_table_prefix}conversion_events
                WHERE script_id = '$script_id'
                GROUP BY IFNULL(device_type, '알 수 없음')
                ORDER BY count DESC";
        $device_result = sql_query($sql);
        
        $devices = [];
        while ($row = sql_fetch_array($device_result)) {
            $devices[] = $row;
        }
        
        // 일별 추이 (최근 30일)
        $sql = "SELECT 
                DATE(conversion_time) as date,
                COUNT(*) as events,
                SUM(CASE WHEN conversion_value > 0 THEN 1 ELSE 0 END) as conversions
                FROM {$cf_table_prefix}conversion_events
                WHERE script_id = '$script_id'
                AND conversion_time >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
                GROUP BY DATE(conversion_time)
                ORDER BY date ASC";
        $daily_result = sql_query($sql);
        
        $daily_stats = [];
        while ($row = sql_fetch_array($daily_result)) {
            $daily_stats[] = $row;
        }
        
        // 결과 반환
        echo json_encode([
            'status' => 'success',
            'summary' => $summary,
            'sources' => $sources,
            'devices' => $devices,
            'daily_stats' => $daily_stats
        ]);
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => '유효하지 않은 요청입니다.']);
        break;
}
