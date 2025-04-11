<?php
/**
 * 폼 필드 AJAX 처리
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// AJAX 요청 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => '잘못된 요청 방식입니다.'));
    exit;
}

// 사용자 권한 체크
if (!$is_member) {
    echo json_encode(array('success' => false, 'message' => '로그인이 필요합니다.'));
    exit;
}

// 모델 로드
require_once CF_MODEL_PATH . '/form.model.php';
$form_model = new FormModel();

// 액션 처리
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action) {
    switch ($action) {
        // 필드 추가
        case 'add_field':
            $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
            
            // 폼 존재 여부 확인
            $form = $form_model->get_form($form_id);
            if (!$form) {
                echo json_encode(array('success' => false, 'message' => '존재하지 않는 폼입니다.'));
                exit;
            }
            
            // 권한 확인 (관리자거나 폼 소유자인지)
            if (!$is_admin && $form['user_id'] != $member['id']) {
                echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
                exit;
            }
            
            // 필수 필드 확인
            $label = isset($_POST['label']) ? trim($_POST['label']) : '';
            $type = isset($_POST['type']) ? trim($_POST['type']) : '';
            
            if (empty($label) || empty($type)) {
                echo json_encode(array('success' => false, 'message' => '필수 항목이 누락되었습니다.'));
                exit;
            }
            
            // 필드 유형에 따른 특수 처리
            $options = null;
            if (in_array($type, array('select', 'checkbox', 'radio')) && isset($_POST['options'])) {
                $options_text = trim($_POST['options']);
                if (!empty($options_text)) {
                    $options_array = explode("\n", $options_text);
                    $options_array = array_map('trim', $options_array);
                    $options_array = array_filter($options_array);
                    
                    if (!empty($options_array)) {
                        $options = json_encode($options_array);
                    }
                }
                
                if ($options === null) {
                    echo json_encode(array('success' => false, 'message' => '선택형 필드에는 최소 하나의 옵션이 필요합니다.'));
                    exit;
                }
            }
            
            // 필드 데이터 구성
            $field_data = array(
                'form_id' => $form_id,
                'label' => $label,
                'type' => $type,
                'placeholder' => isset($_POST['placeholder']) ? trim($_POST['placeholder']) : '',
                'default_value' => isset($_POST['default_value']) ? trim($_POST['default_value']) : '',
                'is_required' => isset($_POST['is_required']) ? 1 : 0,
                'validation_rule' => isset($_POST['validation_rule']) ? trim($_POST['validation_rule']) : '',
                'error_message' => isset($_POST['error_message']) ? trim($_POST['error_message']) : '',
                'options' => $options,
                'step_number' => isset($_POST['step_number']) ? intval($_POST['step_number']) : 1
            );
            
            // 필드 추가
            $field_id = $form_model->add_field($field_data);
            
            if ($field_id) {
                echo json_encode(array('success' => true, 'message' => '필드가 추가되었습니다.', 'field_id' => $field_id));
            } else {
                echo json_encode(array('success' => false, 'message' => '필드 추가 중 오류가 발생했습니다.'));
            }
            break;
            
        // 필드 가져오기
        case 'get_field':
            $field_id = isset($_POST['field_id']) ? intval($_POST['field_id']) : 0;
            
            if ($field_id <= 0) {
                echo json_encode(array('success' => false, 'message' => '유효하지 않은 필드 ID입니다.'));
                exit;
            }
            
            // 필드 정보 조회
            $field = $form_model->get_field($field_id);
            
            if (!$field) {
                echo json_encode(array('success' => false, 'message' => '존재하지 않는 필드입니다.'));
                exit;
            }
            
            // 폼 정보 조회 (권한 확인용)
            $form = $form_model->get_form($field['form_id']);
            
            // 권한 확인
            if (!$is_admin && $form['user_id'] != $member['id']) {
                echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
                exit;
            }
            
            echo json_encode(array('success' => true, 'field' => $field));
            break;
            
        // 필드 업데이트
        case 'update_field':
            $field_id = isset($_POST['field_id']) ? intval($_POST['field_id']) : 0;
            
            if ($field_id <= 0) {
                echo json_encode(array('success' => false, 'message' => '유효하지 않은 필드 ID입니다.'));
                exit;
            }
            
            // 필드 정보 조회
            $field = $form_model->get_field($field_id);
            
            if (!$field) {
                echo json_encode(array('success' => false, 'message' => '존재하지 않는 필드입니다.'));
                exit;
            }
            
            // 폼 정보 조회 (권한 확인용)
            $form = $form_model->get_form($field['form_id']);
            
            // 권한 확인
            if (!$is_admin && $form['user_id'] != $member['id']) {
                echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
                exit;
            }
            
            // 필수 필드 확인
            $label = isset($_POST['label']) ? trim($_POST['label']) : '';
            $type = isset($_POST['type']) ? trim($_POST['type']) : '';
            
            if (empty($label) || empty($type)) {
                echo json_encode(array('success' => false, 'message' => '필수 항목이 누락되었습니다.'));
                exit;
            }
            
            // 필드 유형에 따른 특수 처리
            $options = null;
            if (in_array($type, array('select', 'checkbox', 'radio')) && isset($_POST['options'])) {
                $options_text = trim($_POST['options']);
                if (!empty($options_text)) {
                    $options_array = explode("\n", $options_text);
                    $options_array = array_map('trim', $options_array);
                    $options_array = array_filter($options_array);
                    
                    if (!empty($options_array)) {
                        $options = json_encode($options_array);
                    }
                }
                
                if ($options === null) {
                    echo json_encode(array('success' => false, 'message' => '선택형 필드에는 최소 하나의 옵션이 필요합니다.'));
                    exit;
                }
            }
            
            // 필드 데이터 구성
            $field_data = array(
                'label' => $label,
                'type' => $type,
                'placeholder' => isset($_POST['placeholder']) ? trim($_POST['placeholder']) : '',
                'default_value' => isset($_POST['default_value']) ? trim($_POST['default_value']) : '',
                'is_required' => isset($_POST['is_required']) ? 1 : 0,
                'validation_rule' => isset($_POST['validation_rule']) ? trim($_POST['validation_rule']) : '',
                'error_message' => isset($_POST['error_message']) ? trim($_POST['error_message']) : '',
                'options' => $options,
                'step_number' => isset($_POST['step_number']) ? intval($_POST['step_number']) : 1
            );
            
            // 필드 업데이트
            $result = $form_model->update_field($field_id, $field_data);
            
            if ($result) {
                echo json_encode(array('success' => true, 'message' => '필드가 업데이트되었습니다.'));
            } else {
                echo json_encode(array('success' => false, 'message' => '필드 업데이트 중 오류가 발생했습니다.'));
            }
            break;
            
        // 필드 삭제
        case 'delete_field':
            $field_id = isset($_POST['field_id']) ? intval($_POST['field_id']) : 0;
            
            if ($field_id <= 0) {
                echo json_encode(array('success' => false, 'message' => '유효하지 않은 필드 ID입니다.'));
                exit;
            }
            
            // 필드 정보 조회
            $field = $form_model->get_field($field_id);
            
            if (!$field) {
                echo json_encode(array('success' => false, 'message' => '존재하지 않는 필드입니다.'));
                exit;
            }
            
            // 폼 정보 조회 (권한 확인용)
            $form = $form_model->get_form($field['form_id']);
            
            // 권한 확인
            if (!$is_admin && $form['user_id'] != $member['id']) {
                echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
                exit;
            }
            
            // 필드 삭제
            $result = $form_model->delete_field($field_id);
            
            if ($result) {
                echo json_encode(array('success' => true, 'message' => '필드가 삭제되었습니다.'));
            } else {
                echo json_encode(array('success' => false, 'message' => '필드 삭제 중 오류가 발생했습니다.'));
            }
            break;
            
        // 필드 순서 업데이트
        case 'update_field_orders':
            $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
            $field_orders = isset($_POST['field_orders']) ? $_POST['field_orders'] : array();
            
            if ($form_id <= 0 || empty($field_orders)) {
                echo json_encode(array('success' => false, 'message' => '유효하지 않은 요청입니다.'));
                exit;
            }
            
            // 폼 정보 조회 (권한 확인용)
            $form = $form_model->get_form($form_id);
            
            if (!$form) {
                echo json_encode(array('success' => false, 'message' => '존재하지 않는 폼입니다.'));
                exit;
            }
            
            // 권한 확인
            if (!$is_admin && $form['user_id'] != $member['id']) {
                echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
                exit;
            }
            
            // 순서 업데이트
            $result = $form_model->update_field_orders($field_orders);
            
            if ($result) {
                echo json_encode(array('success' => true, 'message' => '필드 순서가 업데이트되었습니다.'));
            } else {
                echo json_encode(array('success' => false, 'message' => '필드 순서 업데이트 중 오류가 발생했습니다.'));
            }
            break;
            
        default:
            echo json_encode(array('success' => false, 'message' => '알 수 없는 작업입니다.'));
            break;
    }
} else {
    echo json_encode(array('success' => false, 'message' => '작업이 지정되지 않았습니다.'));
}
?>