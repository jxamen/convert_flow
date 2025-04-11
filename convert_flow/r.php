<?php
/**
 * 단축 URL 리다이렉션 처리
 */
include_once __DIR__ . '/include/_common.php';

// 단축 코드 가져오기
$code = isset($_GET['code']) ? trim($_GET['code']) : '';

if (empty($code)) {
    // 코드가 없으면 메인 페이지로 이동
    header('Location: ' . CF_URL);
    exit;
}

// 단축 URL 모델 로드
require_once CF_MODEL_PATH . '/shorturl.model.php';
$shorturl_model = new ShorturlModel();

// 코드로 URL 정보 조회
$url_info = $shorturl_model->get_shorturl_by_code($code);

if (!$url_info) {
    // 존재하지 않는 코드
    echo '존재하지 않는 URL입니다.';
    exit;
}

// 만료 확인
if ($url_info['expires_at'] && strtotime($url_info['expires_at']) < time()) {
    echo '만료된 URL입니다.';
    exit;
}

// 클릭 기록
$shorturl_model->record_click($url_info['id']);

// 리다이렉션
header('Location: ' . $url_info['original_url']);
exit;
?>
