<?php
/**
 * 리드 데이터 AJAX 처리 (통합 관리)
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 요청 유형 확인
$action = isset($_POST['action']) ? $_POST['action'] : '';

// 모델 로드
require_once CF_MODEL_PATH . '/form.model.php';
$form_model = new FormModel();

require_once CF_MODEL_PATH . '/campaign.model.php';
$campaign_model = new CampaignModel();

// 응답 배열 초기화
$response = array(
    'success' => false,
    'message' => '잘못된 요청입니다.'
);

// 요청 처리
switch ($action) {
    case 'get_lead':
        // 리드 ID 획득
        $lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
        
        if ($lead_id <= 0) {
            $response['message'] = '잘못된 리드 ID입니다.';
            break;
        }
        
        // 리드 정보 조회 (폼 정보, 캠페인 정보 포함)
        $sql = "SELECT l.*, f.name as form_name, c.name as campaign_name 
                FROM leads l 
                LEFT JOIN forms f ON l.form_id = f.id 
                LEFT JOIN campaigns c ON l.campaign_id = c.id 
                WHERE l.id = " . intval($lead_id);
        $lead = sql_fetch($sql);
        
        if (!$lead) {
            $response['message'] = '존재하지 않는 리드입니다.';
            break;
        }
        
        // 권한 확인 (관리자 또는 관련 캠페인/폼 소유자)
        $has_permission = false;
        
        if ($is_admin) {
            $has_permission = true;
        } else {
            // 폼 소유자 확인
            if ($lead['form_id']) {
                $form = $form_model->get_form($lead['form_id']);
                if ($form && $form['user_id'] == $member['id']) {
                    $has_permission = true;
                }
            }
            
            // 캠페인 소유자 확인
            if (!$has_permission && $lead['campaign_id']) {
                $campaign = $campaign_model->get_campaign($lead['campaign_id']);
                if ($campaign && $campaign['user_id'] == $member['id']) {
                    $has_permission = true;
                }
            }
        }
        
        if (!$has_permission) {
            $response['message'] = '권한이 없습니다.';
            break;
        }
        
        // 응답 설정
        $response['success'] = true;
        $response['message'] = '리드 정보를 성공적으로 조회했습니다.';
        $response['lead'] = $lead;
        break;
    
    case 'get_lead_status':
        // 리드 ID 획득
        $lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
        
        if ($lead_id <= 0) {
            $response['message'] = '잘못된 리드 ID입니다.';
            break;
        }
        
        // 리드 상태 조회
        $sql = "SELECT l.id, l.form_id, l.campaign_id, l.status 
                FROM leads l 
                WHERE l.id = " . intval($lead_id);
        $lead = sql_fetch($sql);
        
        if (!$lead) {
            $response['message'] = '존재하지 않는 리드입니다.';
            break;
        }
        
        // 권한 확인 (관리자 또는 관련 캠페인/폼 소유자)
        $has_permission = false;
        
        if ($is_admin) {
            $has_permission = true;
        } else {
            // 폼 소유자 확인
            if ($lead['form_id']) {
                $form = $form_model->get_form($lead['form_id']);
                if ($form && $form['user_id'] == $member['id']) {
                    $has_permission = true;
                }
            }
            
            // 캠페인 소유자 확인
            if (!$has_permission && $lead['campaign_id']) {
                $campaign = $campaign_model->get_campaign($lead['campaign_id']);
                if ($campaign && $campaign['user_id'] == $member['id']) {
                    $has_permission = true;
                }
            }
        }
        
        if (!$has_permission) {
            $response['message'] = '권한이 없습니다.';
            break;
        }
        
        // 응답 설정
        $response['success'] = true;
        $response['message'] = '리드 상태 정보를 성공적으로 조회했습니다.';
        $response['status'] = $lead['status'];
        break;
    
    case 'update_lead_status':
        // 파라미터 확인
        $lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        $note = isset($_POST['note']) ? trim($_POST['note']) : '';
        
        if ($lead_id <= 0 || empty($status)) {
            $response['message'] = '필수 파라미터가 누락되었습니다.';
            break;
        }
        
        // 리드 정보 확인
        $sql = "SELECT l.id, l.form_id, l.campaign_id, l.status 
                FROM leads l 
                WHERE l.id = " . intval($lead_id);
        $lead = sql_fetch($sql);
        
        if (!$lead) {
            $response['message'] = '존재하지 않는 리드입니다.';
            break;
        }
        
        // 권한 확인 (관리자 또는 관련 캠페인/폼 소유자)
        $has_permission = false;
        
        if ($is_admin) {
            $has_permission = true;
        } else {
            // 폼 소유자 확인
            if ($lead['form_id']) {
                $form = $form_model->get_form($lead['form_id']);
                if ($form && $form['user_id'] == $member['id']) {
                    $has_permission = true;
                }
            }
            
            // 캠페인 소유자 확인
            if (!$has_permission && $lead['campaign_id']) {
                $campaign = $campaign_model->get_campaign($lead['campaign_id']);
                if ($campaign && $campaign['user_id'] == $member['id']) {
                    $has_permission = true;
                }
            }
        }
        
        if (!$has_permission) {
            $response['message'] = '권한이 없습니다.';
            break;
        }
        
        // 상태 유효성 검사
        $valid_statuses = array('신규', '처리중', '전송완료', '거부', '중복');
        if (!in_array($status, $valid_statuses)) {
            $response['message'] = '유효하지 않은 상태입니다.';
            break;
        }
        
        // 기존 상태 저장
        $prev_status = $lead['status'];
        
        // 상태 변경 처리
        $update_sql = "UPDATE leads SET 
                    status = '" . sql_escape_string($status) . "',
                    updated_at = NOW()
                    WHERE id = " . intval($lead_id);
        $result = sql_query($update_sql);

        if (!$result) {
            $response['message'] = '상태 변경 중 오류가 발생했습니다.';
            break;
        }

        /*
        // 상태 변경 로그 기록 (lead_logs 테이블이 있을 경우에만)
        if (isset('lead_logs')) {
            $log_sql = "INSERT INTO lead_logs SET
                lead_id = " . intval($lead_id) . ",
                user_id = " . intval($member['id']) . ",
                action = 'status_change',
                prev_value = '" . sql_escape_string($prev_status) . "',
                new_value = '" . sql_escape_string($status) . "',
                note = '" . sql_escape_string($note) . "',
                created_at = NOW()";
            sql_query($log_sql);
        }
        */

        // 응답 설정
        $response['success'] = true;
        $response['message'] = '리드 상태가 성공적으로 변경되었습니다.';
        break;

        case 'delete_lead':
        // 리드 ID 획득
        $lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;

        if ($lead_id <= 0) {
            $response['message'] = '잘못된 리드 ID입니다.';
            break;
        }

        // 리드 정보 확인
        $sql = "SELECT l.id, l.form_id, l.campaign_id 
                FROM leads l 
                WHERE l.id = " . intval($lead_id);
        $lead = sql_fetch($sql);

        if (!$lead) {
            $response['message'] = '존재하지 않는 리드입니다.';
            break;
        }

        // 권한 확인 (관리자 또는 관련 캠페인/폼 소유자)
        $has_permission = false;

        if ($is_admin) {
            $has_permission = true;
        } else {
            // 폼 소유자 확인
            if ($lead['form_id']) {
                $form = $form_model->get_form($lead['form_id']);

                if ($form && $form['user_id'] == $member['id']) {
                    $has_permission = true;
                }
            }

            // 캠페인 소유자 확인
            if (!$has_permission && $lead['campaign_id']) {
                $campaign = $campaign_model->get_campaign($lead['campaign_id']);

                if ($campaign && $campaign['user_id'] == $member['id']) {
                    $has_permission = true;
                }
            }
        }

        if (!$has_permission) {
            $response['message'] = '권한이 없습니다.';
            break;
        }

        // 삭제 처리
        $delete_sql = "DELETE FROM leads WHERE id = " . intval($lead_id);
        $result = sql_query($delete_sql);

        if (!$result) {
            $response['message'] = '리드 삭제 중 오류가 발생했습니다.';
            break;
        }

        // 응답 설정
        $response['success'] = true;
        $response['message'] = '리드가 성공적으로 삭제되었습니다.';
    break;

    default:
        $response['message'] = '지원하지 않는 액션입니다.';
        break;
    }

    // JSON 응답 출력
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;