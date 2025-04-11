<?php
/**
 * 단축 URL 리다이렉트 처리 페이지
 * 이 파일은 웹서버 설정에서 /RANDING/* 또는 임의의 경로로 들어오는 요청을 이 파일로 리다이렉트하도록 설정해야 합니다.
 * 
 * 예: Apache .htaccess 설정
 * RewriteEngine On
 * RewriteRule ^RANDING/(.*)$ /convert_flow/app/shorturl/shorturl_redirect.php?path=$1 [L]
 * RewriteRule ^([a-zA-Z0-9]{6})$ /convert_flow/app/shorturl/shorturl_redirect.php?code=$1 [L]
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 모델 로드
require_once CF_MODEL_PATH . '/shorturl.model.php';
$shorturl_model = new ShorturlModel();

// 요청 경로 또는 코드 확인
$path = isset($_GET['path']) ? 'RANDING/' . $_GET['path'] : '';
$code = isset($_GET['code']) ? $_GET['code'] : '';

// URL 정보 조회
$url_info = null;

if (!empty($path)) {
    // 경로로 조회
    $url_info = $shorturl_model->get_shorturl_by_path($path);
} else if (!empty($code)) {
    // 코드로 조회
    $url_info = $shorturl_model->get_shorturl_by_code($code);
} else {
    // 잘못된 요청
    echo '<script>alert("잘못된 URL입니다."); window.location.href="' . CF_URL . '";</script>';
    exit;
}

// URL 정보 확인
if (!$url_info) {
    echo '<script>alert("존재하지 않는 URL입니다."); window.location.href="' . CF_URL . '";</script>';
    exit;
}

// 만료 여부 확인
if ($shorturl_model->is_expired($url_info)) {
    echo '<script>alert("만료된 URL입니다."); window.location.href="' . CF_URL . '";</script>';
    exit;
}

// 클릭 이벤트 기록
$click_data = array(
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'referrer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''
);

$shorturl_model->record_click($url_info['id'], $click_data);

// 원본 URL로 리다이렉트
header('Location: ' . $url_info['original_url']);
exit;
?>