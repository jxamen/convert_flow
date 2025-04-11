<?php
/**
 * 광고 그룹 추가/수정 처리 파일
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 권한 체크
if (!$is_member) {
    alert_close('로그인이 필요한 서비스입니다.');
    exit;
}

// CSRF 방지
check_token();

// 액션 확인
$action = isset($_POST['action']) ? $_POST['action'] : '';
if (!in_array($action, array('add', 'update'))) {
    alert('잘못된 요청입니다.');
    exit;
}

// 광고 그룹 모델 로드
include_once CF_MODEL_PATH . '/campaign/ad_group.model.php';
$ad_group_model = new AdGroupModel();

// 공통 필드 검증
$campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : '활성';
$bid_amount = isset($_POST['bid_amount']) ? floatval($_POST['bid_amount']) : 0;
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// 필수 필드 검증
if ($campaign_id <= 0) {
    alert('캠페인을 선택해주세요.');
    exit;
}

if (empty($name)) {
    alert('광고 그룹명을 입력해주세요.');
    exit;
}

// 캠페인 모델 로드 및 캠페인 검증
include_once CF_MODEL_PATH . '/campaign/campaign.model.php';
$campaign_model = new CampaignModel();
$campaign = $campaign_model->get_campaign($campaign_id, $member['id']);

if (!$campaign) {
    alert('존재하지 않거나 접근 권한이 없는 캠페인입니다.');
    exit;
}

// 광고 그룹 데이터 준비
$ad_group_data = array(
    'campaign_id' => $campaign_id,
    'name' => $name,
    'status' => $status,
    'bid_amount' => $bid_amount,
    'description' => $description
);

// 외부 광고 그룹 정보 (API에서 불러온 경우)
if ($action == 'add' && isset($_POST['external_ad_group_id']) && !empty($_POST['external_ad_group_id'])) {
    $external_ad_group_id = trim($_POST['external_ad_group_id']);
    $ad_group_data['external_ad_group_id'] = $external_ad_group_id;
    
    // 광고 계정 정보 (외부 광고 그룹 연결 시 필요)
    if (isset($_POST['ad_account_id']) && !empty($_POST['ad_account_id'])) {
        $ad_account_id = intval($_POST['ad_account_id']);
        $ad_group_data['user_ad_account_id'] = $ad_account_id;
    }
}

// 작업 처리
if ($action == 'add') {
    // 광고 그룹 추가
    $ad_group_id = $ad_group_model->add_ad_group($ad_group_data);
    
    if ($ad_group_id) {
        // 성공 시 캠페인의 광고 그룹 탭으로 이동
        alert('광고 그룹이 추가되었습니다.', CF_CAMPAIGN_URL . '/campaign_view.php?id=' . $campaign_id . '&tab=ad_groups');
    } else {
        alert('광고 그룹 추가 중 오류가 발생했습니다.');
    }
} else {
    // 광고 그룹 수정
    $ad_group_id = isset($_POST['ad_group_id']) ? intval($_POST['ad_group_id']) : 0;
    
    if ($ad_group_id <= 0) {
        alert('잘못된 광고 그룹 ID입니다.');
        exit;
    }
    
    $result = $ad_group_model->update_ad_group($ad_group_id, $ad_group_data);
    
    if ($result) {
        // 성공 시 광고 그룹 상세 페이지로 이동
        alert('광고 그룹이 수정되었습니다.', CF_CAMPAIGN_URL . '/ad_group_view.php?id=' . $ad_group_id);
    } else {
        alert('광고 그룹 수정 중 오류가 발생했습니다.');
    }
}
?>