<?php
/**
 * 캠페인 복제 처리
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 관리자, 캠페인 소유자 검증
$is_owner = false;

// 캠페인 모델 로드
require_once CF_MODEL_PATH . '/campaign/campaign.model.php';
require_once CF_MODEL_PATH . '/campaign/conversion.model.php';
$campaign_model = new CampaignModel();
$conversion_model = new ConversionModel();

// 캠페인 ID 검증
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    alert('올바른 접근이 아닙니다.', 'campaign_list.php');
    exit;
}

$campaign_id = intval($_GET['id']);

// 캠페인 정보 조회
$campaign = $campaign_model->get_campaign($campaign_id);

// 캠페인이 존재하지 않는 경우
if (!$campaign) {
    alert('존재하지 않는 캠페인입니다.', 'campaign_list.php');
    exit;
}

// 권한 검증
if ($is_admin === "super" || $campaign['user_id'] == $member['id']) {
    $is_owner = true;
}

if (!$is_owner) {
    alert('해당 캠페인에 대한 권한이 없습니다.', 'campaign_list.php');
    exit;
}

// 복제할 캠페인 데이터 구성
$copy_data = array(
    'user_id' => $member['id'],
    'name' => $campaign['name'] . ' (복제)',
    'status' => '비활성', // 복제 시 초기 상태는 비활성
    'start_date' => date('Y-m-d'), // 현재 날짜로 설정
    'end_date' => $campaign['end_date'],
    'budget' => $campaign['budget'],
    'cpa_goal' => $campaign['cpa_goal'],
    'daily_budget' => $campaign['daily_budget'],
    'description' => $campaign['description']
);

// 트랜잭션 시작
sql_query("START TRANSACTION");

try {
    // 캠페인 복제 생성
    $new_campaign_id = $campaign_model->create_campaign($copy_data);
    
    if (!$new_campaign_id) {
        throw new Exception('캠페인 복제 중 오류가 발생했습니다.');
    }
    
    // 전환 스크립트 복제 여부 확인
    if (isset($_GET['copy_scripts']) && $_GET['copy_scripts'] == 1) {
        // 원본 캠페인의 전환 스크립트 목록 조회
        $sql = "SELECT * FROM {$cf_table_prefix}conversion_scripts WHERE campaign_id = '$campaign_id'";
        $result = sql_query($sql);
        
        while ($script = sql_fetch_array($result)) {
            // 각 스크립트 복제
            $script_data = array(
                'campaign_id' => $new_campaign_id,
                'conversion_type' => $script['conversion_type'],
                'script_code' => $script['script_code'],
                'installation_guide' => $script['installation_guide']
            );
            
            $conversion_model->create_script($script_data);
        }
    }
    
    // 랜딩 페이지 복제 여부 확인 (필요시 추가)
    
    // 트랜잭션 커밋
    sql_query("COMMIT");
    
    // 성공 메시지와 함께 새 캠페인 상세 페이지로 이동
    $msg = urlencode('캠페인이 성공적으로 복제되었습니다.');
    goto_url('campaign_view.php?id=' . $new_campaign_id . '&msg=' . $msg . '&msg_type=success');
    
} catch (Exception $e) {
    // 오류 발생 시 롤백
    sql_query("ROLLBACK");
    
    // 오류 메시지와 함께 원본 캠페인 페이지로 이동
    $msg = urlencode($e->getMessage());
    goto_url('campaign_view.php?id=' . $campaign_id . '&msg=' . $msg . '&msg_type=danger');
}
?>
