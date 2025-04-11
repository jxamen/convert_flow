<?php
/**
 * 캠페인 삭제 처리
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 관리자, 캠페인 소유자 검증
$is_owner = false;

// 캠페인 모델 로드
require_once CF_MODEL_PATH . '/campaign/campaign.model.php';
$campaign_model = new CampaignModel();

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

// 삭제 전 연관된 데이터 확인 (전환 스크립트, 전환 이벤트 등)
$sql = "SELECT COUNT(*) as cnt FROM {$cf_table_prefix}conversion_scripts WHERE campaign_id = '$campaign_id'";
$row = sql_fetch($sql);
$script_count = $row['cnt'];

$sql = "SELECT COUNT(*) as cnt FROM {$cf_table_prefix}landing_pages WHERE campaign_id = '$campaign_id'";
$row = sql_fetch($sql);
$landing_count = $row['cnt'];

// 관련 데이터가 있는 경우 사용자 확인 필요
if (($script_count > 0 || $landing_count > 0) && !isset($_GET['confirm'])) {
    $msg = "이 캠페인을 삭제하면 {$script_count}개의 전환 스크립트";
    if ($landing_count > 0) {
        $msg .= "와 {$landing_count}개의 랜딩페이지";
    }
    $msg .= "도 함께 삭제됩니다. 정말 삭제하시겠습니까?";
    
    echo '<script>
        if (confirm("' . $msg . '")) {
            location.href = "campaign_delete.php?id=' . $campaign_id . '&confirm=1";
        } else {
            history.back();
        }
    </script>';
    exit;
}

// 삭제 처리
$result = $campaign_model->delete_campaign($campaign_id);

if ($result) {
    // 성공 메시지와 함께 목록 페이지로 이동
    $msg = urlencode('캠페인이 성공적으로 삭제되었습니다.');
    goto_url('campaign_list.php?msg=' . $msg . '&msg_type=success');
} else {
    // 오류 메시지와 함께 목록 페이지로 이동
    $msg = urlencode('캠페인 삭제 중 오류가 발생했습니다.');
    goto_url('campaign_list.php?msg=' . $msg . '&msg_type=danger');
}
?>
