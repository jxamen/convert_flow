<?php
/**
 * 광고 계정 관련 AJAX 처리
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 권한 체크
if (!$is_member) {
    echo json_encode(array('success' => false, 'message' => '로그인이 필요한 서비스입니다.'));
    exit;
}

// 액션 확인
$action = isset($_POST['action']) ? $_POST['action'] : '';

        
// 광고 계정 모델 로드
require_once CF_MODEL_PATH . '/ad_account.model.php';
$ad_account_model = new AdAccountModel();

// 액션별 처리
switch ($action) {
    // 플랫폼 코드 가져오기
    case 'get_platform_code':
        $platform_id = isset($_POST['platform_id']) ? intval($_POST['platform_id']) : 0;
        
        if ($platform_id <= 0) {
            echo json_encode(array('success' => false, 'message' => '유효하지 않은 플랫폼 ID입니다.'));
            exit;
        }
        
        $platform = $ad_account_model->get_platform($platform_id);
        
        if ($platform) {
            echo json_encode(array(
                'success' => true, 
                'platform_code' => $platform['platform_code'],
                'platform_name' => $platform['name']
            ));
        } else {
            echo json_encode(array('success' => false, 'message' => '플랫폼 정보를 찾을 수 없습니다.'));
        }
        break;
        
    // 플랫폼 필드 정보 가져오기
    case 'get_platform_fields':
        $platform_id = isset($_POST['platform_id']) ? intval($_POST['platform_id']) : 0;
        
        if ($platform_id <= 0) {
            echo json_encode(array('success' => false, 'message' => '유효하지 않은 플랫폼 ID입니다.'));
            exit;
        }
        
        $platform = $ad_account_model->get_platform($platform_id);
        
        if ($platform && isset($platform['field_settings_data'])) {
            echo json_encode(array(
                'success' => true, 
                'fields' => $platform['field_settings_data']
            ));
        } else {
            echo json_encode(array('success' => false, 'message' => '플랫폼 필드 설정을 찾을 수 없습니다.'));
        }
        break;
        
    default:
        echo json_encode(array('success' => false, 'message' => '지원하지 않는 액션입니다.'));
        break;
}