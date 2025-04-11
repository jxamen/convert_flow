<?php
/**
 * 단축 URL 삭제 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 모델 로드
require_once CF_MODEL_PATH . '/shorturl.model.php';
$shorturl_model = new ShorturlModel();

// ID 체크
if (empty($_GET['id'])) {
    alert('잘못된 접근입니다.');
}

$url_id = intval($_GET['id']);

// URL 정보 조회 및 접근 권한 확인
$url_info = $shorturl_model->get_shorturl($url_id, $member['id']);

if (!$url_info) {
    alert('존재하지 않는 단축 URL이거나 접근 권한이 없습니다.');
}

// URL 삭제
$result = $shorturl_model->delete_shorturl($url_id, $member['id']);

if ($result) {
    goto_url('shorturl_list.php?msg=' . urlencode('단축 URL이 성공적으로 삭제되었습니다.') . '&msg_type=success');
} else {
    alert('삭제 중 오류가 발생했습니다.');
}
?>