<?php
/**
 * AJAX 요청 처리 파일
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 권한 체크
if (!$is_member) {
    echo json_encode(array('success' => false, 'message' => '로그인이 필요한 서비스입니다.'));
    exit;
}

// 액션 확인
$action = isset($_POST['action']) ? $_POST['action'] : '';

// 액션별 처리
switch ($action) {
    // 모든 캠페인 목록 가져오기
    case 'get_all_campaigns':
        try {
            // 캠페인 목록 조회
            include_once CF_MODEL_PATH . '/campaign/campaign.model.php';
            $campaign_model = new CampaignModel();
            $campaigns = $campaign_model->get_campaigns($member['id']);
            
            echo json_encode(array('success' => true, 'data' => $campaigns));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => '캠페인 목록을 가져오는 중 오류가 발생했습니다: ' . $e->getMessage()));
        }
        break;
        
    // 광고 계정별 캠페인 목록 가져오기
    case 'get_campaigns_by_account':
        $account_id = isset($_POST['account_id']) ? intval($_POST['account_id']) : 0;
        
        if ($account_id <= 0) {
            echo json_encode(array('success' => false, 'message' => '광고 계정 ID가 유효하지 않습니다.'));
            exit;
        }
        
        try {
            // 광고 계정 정보 조회
            include_once CF_MODEL_PATH . '/models/ad_account.model.php';
            $ad_account_model = new AdAccountModel();
            $ad_account = $ad_account_model->get_ad_account($account_id, $member['id']);
            
            if (!$ad_account) {
                echo json_encode(array('success' => false, 'message' => '광고 계정 정보를 찾을 수 없습니다.'));
                exit;
            }
            
            // 광고 계정에 연결된 캠페인 목록 조회
            include_once CF_MODEL_PATH . '/campaign/campaign.model.php';
            $campaign_model = new CampaignModel();
            $campaigns = $campaign_model->get_campaigns_by_ad_account($account_id);
            
            echo json_encode(array('success' => true, 'data' => $campaigns));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => '캠페인 목록을 가져오는 중 오류가 발생했습니다: ' . $e->getMessage()));
        }
        break;
    
    // 캠페인별 광고 그룹 목록 가져오기
    case 'get_ad_groups_by_campaign':
        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
        $external_only = isset($_POST['external_only']) ? boolval($_POST['external_only']) : false;
        
        if ($campaign_id <= 0) {
            echo json_encode(array('success' => false, 'message' => '캠페인 ID가 유효하지 않습니다.'));
            exit;
        }
        
        try {
            // 캠페인 정보 조회
            include_once CF_MODEL_PATH . '/campaign/campaign.model.php';
            $campaign_model = new CampaignModel();
            $campaign = $campaign_model->get_campaign($campaign_id, $member['id']);
            
            if (!$campaign) {
                echo json_encode(array('success' => false, 'message' => '캠페인 정보를 찾을 수 없습니다.'));
                exit;
            }
            
            // 캠페인에 연결된 광고 그룹 목록 조회
            include_once CF_MODEL_PATH . '/campaign/ad_group.model.php';
            $ad_group_model = new AdGroupModel();
            
            if ($external_only) {
                // 외부(API)에서 가져온 광고 그룹만 조회
                $params = array(
                    'external_only' => true
                );
                $result = $ad_group_model->get_ad_groups_by_campaign($campaign_id, $params);
                $ad_groups = $result['ad_groups'];
            } else {
                // 모든 광고 그룹 조회
                $result = $ad_group_model->get_ad_groups_by_campaign($campaign_id);
                $ad_groups = $result['ad_groups'];
            }
            
            echo json_encode(array('success' => true, 'data' => $ad_groups));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => '광고 그룹 목록을 가져오는 중 오류가 발생했습니다: ' . $e->getMessage()));
        }
        break;
    
    // 캠페인 상태 변경
    case 'change_campaign_status':
        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        
        if ($campaign_id <= 0) {
            echo json_encode(array('success' => false, 'message' => '캠페인 ID가 유효하지 않습니다.'));
            exit;
        }
        
        if (!in_array($status, array('활성', '비활성', '일시중지'))) {
            echo json_encode(array('success' => false, 'message' => '유효하지 않은 상태입니다.'));
            exit;
        }
        
        try {
            // 캠페인 상태 변경
            include_once CF_MODEL_PATH . '/campaign/campaign.model.php';
            $campaign_model = new CampaignModel();
            $result = $campaign_model->change_campaign_status($campaign_id, $status, $member['id']);
            
            if ($result) {
                echo json_encode(array('success' => true, 'message' => '캠페인 상태가 변경되었습니다.'));
            } else {
                echo json_encode(array('success' => false, 'message' => '캠페인 상태 변경에 실패했습니다.'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => '캠페인 상태 변경 중 오류가 발생했습니다: ' . $e->getMessage()));
        }
        break;
    
    // 광고 그룹 상태 변경
    case 'change_ad_group_status':
        $ad_group_id = isset($_POST['ad_group_id']) ? intval($_POST['ad_group_id']) : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        
        if ($ad_group_id <= 0) {
            echo json_encode(array('success' => false, 'message' => '광고 그룹 ID가 유효하지 않습니다.'));
            exit;
        }
        
        if (!in_array($status, array('활성', '비활성', '일시중지'))) {
            echo json_encode(array('success' => false, 'message' => '유효하지 않은 상태입니다.'));
            exit;
        }
        
        try {
            // 광고 그룹 상태 변경
            include_once CF_MODEL_PATH . '/campaign/ad_group.model.php';
            $ad_group_model = new AdGroupModel();
            $result = $ad_group_model->change_ad_group_status($ad_group_id, $status);
            
            if ($result) {
                echo json_encode(array('success' => true, 'message' => '광고 그룹 상태가 변경되었습니다.'));
            } else {
                echo json_encode(array('success' => false, 'message' => '광고 그룹 상태 변경에 실패했습니다.'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => '광고 그룹 상태 변경 중 오류가 발생했습니다: ' . $e->getMessage()));
        }
        break;
    
    // 광고 소재 상태 변경
    case 'change_ad_material_status':
        $ad_material_id = isset($_POST['ad_material_id']) ? intval($_POST['ad_material_id']) : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        
        if ($ad_material_id <= 0) {
            echo json_encode(array('success' => false, 'message' => '광고 소재 ID가 유효하지 않습니다.'));
            exit;
        }
        
        if (!in_array($status, array('활성', '비활성', '일시중지'))) {
            echo json_encode(array('success' => false, 'message' => '유효하지 않은 상태입니다.'));
            exit;
        }
        
        try {
            // 광고 소재 상태 변경
            include_once CF_MODEL_PATH . '/campaign/ad.model.php';
            $ad_model = new AdModel();
            $result = $ad_model->change_ad_material_status($ad_material_id, $status);
            
            if ($result) {
                echo json_encode(array('success' => true, 'message' => '광고 소재 상태가 변경되었습니다.'));
            } else {
                echo json_encode(array('success' => false, 'message' => '광고 소재 상태 변경에 실패했습니다.'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => '광고 소재 상태 변경 중 오류가 발생했습니다: ' . $e->getMessage()));
        }
        break;
    
    // 캠페인 삭제
    case 'delete_campaign':
        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
        
        if ($campaign_id <= 0) {
            echo json_encode(array('success' => false, 'message' => '캠페인 ID가 유효하지 않습니다.'));
            exit;
        }
        
        try {
            // 캠페인 삭제
            include_once CF_MODEL_PATH . '/campaign/campaign.model.php';
            $campaign_model = new CampaignModel();
            $result = $campaign_model->delete_campaign($campaign_id, $member['id']);
            
            if ($result) {
                echo json_encode(array('success' => true, 'message' => '캠페인이 삭제되었습니다.'));
            } else {
                echo json_encode(array('success' => false, 'message' => '캠페인 삭제에 실패했습니다.'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => '캠페인 삭제 중 오류가 발생했습니다: ' . $e->getMessage()));
        }
        break;
    
    // 광고 그룹 삭제
    case 'delete_ad_group':
        $ad_group_id = isset($_POST['ad_group_id']) ? intval($_POST['ad_group_id']) : 0;
        
        if ($ad_group_id <= 0) {
            echo json_encode(array('success' => false, 'message' => '광고 그룹 ID가 유효하지 않습니다.'));
            exit;
        }
        
        try {
            // 광고 그룹 삭제
            include_once CF_MODEL_PATH . '/campaign/ad_group.model.php';
            $ad_group_model = new AdGroupModel();
            $result = $ad_group_model->delete_ad_group($ad_group_id);
            
            if ($result) {
                echo json_encode(array('success' => true, 'message' => '광고 그룹이 삭제되었습니다.'));
            } else {
                echo json_encode(array('success' => false, 'message' => '광고 그룹 삭제에 실패했습니다.'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => '광고 그룹 삭제 중 오류가 발생했습니다: ' . $e->getMessage()));
        }
        break;
    
    // 광고 소재 삭제
    case 'delete_ad_material':
        $ad_material_id = isset($_POST['ad_material_id']) ? intval($_POST['ad_material_id']) : 0;
        
        if ($ad_material_id <= 0) {
            echo json_encode(array('success' => false, 'message' => '광고 소재 ID가 유효하지 않습니다.'));
            exit;
        }
        
        try {
            // 광고 소재 삭제
            include_once CF_MODEL_PATH . '/campaign/ad.model.php';
            $ad_model = new AdModel();
            $result = $ad_model->delete_ad_material($ad_material_id);
            
            if ($result) {
                echo json_encode(array('success' => true, 'message' => '광고 소재가 삭제되었습니다.'));
            } else {
                echo json_encode(array('success' => false, 'message' => '광고 소재 삭제에 실패했습니다.'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => '광고 소재 삭제 중 오류가 발생했습니다: ' . $e->getMessage()));
        }
        break;
    
    // 전환 스크립트 삭제
    case 'delete_conversion_script':
        $script_id = isset($_POST['script_id']) ? intval($_POST['script_id']) : 0;
        
        if ($script_id <= 0) {
            echo json_encode(array('success' => false, 'message' => '스크립트 ID가 유효하지 않습니다.'));
            exit;
        }
        
        try {
            // 전환 스크립트 삭제
            include_once CF_MODEL_PATH . '/campaign/conversion.model.php';
            $conversion_model = new ConversionModel();
            $result = $conversion_model->delete_conversion_script($script_id);
            
            if ($result) {
                echo json_encode(array('success' => true, 'message' => '전환 스크립트가 삭제되었습니다.'));
            } else {
                echo json_encode(array('success' => false, 'message' => '전환 스크립트 삭제에 실패했습니다.'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => '전환 스크립트 삭제 중 오류가 발생했습니다: ' . $e->getMessage()));
        }
        break;
    
    // 일괄 상태 변경 - 광고 그룹
    case 'bulk_change_ad_group_status':
        $ad_group_ids = isset($_POST['ad_group_ids']) ? $_POST['ad_group_ids'] : array();
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        
        if (empty($ad_group_ids)) {
            echo json_encode(array('success' => false, 'message' => '선택된 광고 그룹이 없습니다.'));
            exit;
        }
        
        if (!in_array($status, array('활성', '비활성', '일시중지'))) {
            echo json_encode(array('success' => false, 'message' => '유효하지 않은 상태입니다.'));
            exit;
        }
        
        try {
            // 광고 그룹 모델 로드
            include_once CF_MODEL_PATH . '/campaign/ad_group.model.php';
            $ad_group_model = new AdGroupModel();
            
            $success_count = 0;
            $failed_count = 0;
            
            foreach ($ad_group_ids as $ad_group_id) {
                $ad_group_id = intval($ad_group_id);
                if ($ad_group_id <= 0) continue;
                
                $result = $ad_group_model->change_ad_group_status($ad_group_id, $status);
                if ($result) {
                    $success_count++;
                } else {
                    $failed_count++;
                }
            }
            
            if ($success_count > 0) {
                $message = $success_count . '개의 광고 그룹 상태가 변경되었습니다.';
                if ($failed_count > 0) {
                    $message .= ' (' . $failed_count . '개 실패)';
                }
                echo json_encode(array('success' => true, 'message' => $message));
            } else {
                echo json_encode(array('success' => false, 'message' => '광고 그룹 상태 변경에 실패했습니다.'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => '광고 그룹 상태 변경 중 오류가 발생했습니다: ' . $e->getMessage()));
        }
        break;
    
    // 일괄 상태 변경 - 광고 소재
    case 'bulk_change_ad_material_status':
        $ad_material_ids = isset($_POST['ad_material_ids']) ? $_POST['ad_material_ids'] : array();
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        
        if (empty($ad_material_ids)) {
            echo json_encode(array('success' => false, 'message' => '선택된 광고 소재가 없습니다.'));
            exit;
        }
        
        if (!in_array($status, array('활성', '비활성', '일시중지'))) {
            echo json_encode(array('success' => false, 'message' => '유효하지 않은 상태입니다.'));
            exit;
        }
        
        try {
            // 광고 소재 모델 로드
            include_once CF_MODEL_PATH . '/campaign/ad.model.php';
            $ad_model = new AdModel();
            
            $success_count = 0;
            $failed_count = 0;
            
            foreach ($ad_material_ids as $ad_material_id) {
                $ad_material_id = intval($ad_material_id);
                if ($ad_material_id <= 0) continue;
                
                $result = $ad_model->change_ad_material_status($ad_material_id, $status);
                if ($result) {
                    $success_count++;
                } else {
                    $failed_count++;
                }
            }
            
            if ($success_count > 0) {
                $message = $success_count . '개의 광고 소재 상태가 변경되었습니다.';
                if ($failed_count > 0) {
                    $message .= ' (' . $failed_count . '개 실패)';
                }
                echo json_encode(array('success' => true, 'message' => $message));
            } else {
                echo json_encode(array('success' => false, 'message' => '광고 소재 상태 변경에 실패했습니다.'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => '광고 소재 상태 변경 중 오류가 발생했습니다: ' . $e->getMessage()));
        }
        break;
    
    // 일별 통계 데이터 가져오기
    case 'get_daily_statistics':
        $ad_group_id = isset($_POST['ad_group_id']) ? intval($_POST['ad_group_id']) : 0;
        $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
        
        if ($ad_group_id <= 0) {
            echo json_encode(array('success' => false, 'message' => '광고 그룹 ID가 유효하지 않습니다.'));
            exit;
        }
        
        // 날짜 형식 검증
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
            $end_date = date('Y-m-d');
        }
        
        try {
            // 광고 그룹 일별 통계 가져오기
            include_once CF_MODEL_PATH . '/campaign/ad_group.model.php';
            $ad_group_model = new AdGroupModel();
            $daily_stats = $ad_group_model->get_daily_statistics($ad_group_id, $start_date, $end_date);
            
            echo json_encode(array('success' => true, 'data' => $daily_stats));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => '일별 통계 데이터를 가져오는 중 오류가 발생했습니다: ' . $e->getMessage()));
        }
        break;
    
    // 시간별 통계 데이터 가져오기
    case 'get_hourly_statistics':
        $ad_group_id = isset($_POST['ad_group_id']) ? intval($_POST['ad_group_id']) : 0;
        $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
        
        if ($ad_group_id <= 0) {
            echo json_encode(array('success' => false, 'message' => '광고 그룹 ID가 유효하지 않습니다.'));
            exit;
        }
        
        // 날짜 형식 검증
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d');
        }
        
        try {
            // 광고 그룹 시간별 통계 가져오기
            include_once CF_MODEL_PATH . '/campaign/ad_group.model.php';
            $ad_group_model = new AdGroupModel();
            $hourly_stats = $ad_group_model->get_hourly_statistics($ad_group_id, $start_date, $end_date);
            
            echo json_encode(array('success' => true, 'data' => $hourly_stats));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'message' => '시간별 통계 데이터를 가져오는 중 오류가 발생했습니다: ' . $e->getMessage()));
        }
        break;
    
    // 기타 액션
    default:
        echo json_encode(array('success' => false, 'message' => '지원하지 않는 액션입니다.'));
        break;
}
?>