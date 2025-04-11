<?php
/**
 * 캠페인 상태 변경 처리
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 관리자, 캠페인 소유자 검증
$is_owner = false;

// 캠페인 모델 로드
require_once CF_MODEL_PATH . '/campaign/campaign.model.php';
$campaign_model = new CampaignModel();

// 캠페인 ID와 상태 검증
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    alert('올바른 접근이 아닙니다.', 'campaign_list.php');
    exit;
}

$campaign_id = intval($_GET['id']);
$status = isset($_GET['status']) ? $_GET['status'] : '';

// 상태값 검증
if (!in_array($status, array('활성', '비활성', '일시중지'))) {
    alert('올바른 상태값이 아닙니다.', 'campaign_list.php');
    exit;
}

// 캠페인 정보 조회
$campaign = $campaign_model->get_campaign($campaign_id);

// 캠페인이 존재하지 않거나 현재 사용자의 것이 아닌 경우
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

// 이미 같은 상태인 경우
if ($campaign['status'] === $status) {
    $redirect_url = 'campaign_view.php?id=' . $campaign_id;
    $msg = urlencode('이미 ' . $status . ' 상태입니다.');
    goto_url($redirect_url . '&msg=' . $msg);
    exit;
}

// 상태 변경 처리
$result = $campaign_model->change_campaign_status($campaign_id, $status);

if ($result) {
    // 성공 메시지와 함께 상세 페이지로 이동
    $redirect_url = 'campaign_view.php?id=' . $campaign_id;
    $msg = urlencode('캠페인 상태가 ' . $status . '(으)로 변경되었습니다.');
    goto_url($redirect_url . '&msg=' . $msg . '&msg_type=success');
} else {
    // 오류 메시지와 함께 상세 페이지로 이동
    $redirect_url = 'campaign_view.php?id=' . $campaign_id;
    $msg = urlencode('캠페인 상태 변경 중 오류가 발생했습니다.');
    goto_url($redirect_url . '&msg=' . $msg . '&msg_type=danger');
}
?>
