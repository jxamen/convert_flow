<?php
if (!defined('_CONVERT_FLOW_')) {
    define('_CONVERT_FLOW_', true);
}

// config.php 파일 로딩
include_once(__DIR__ . '/config.php');

// 사용자 정의 함수 파일 로딩
include_once(__DIR__ . '/functions.php');

// 세션 초기화
session_start();

// 로그인 체크
$is_member = false;
$is_admin = false;
$member = array();

if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
    $is_member = true;
    
    // 회원 정보 가져오기
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM {$cf_table_prefix}users WHERE id = '$user_id'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $member = mysqli_fetch_assoc($result);
        
        // 관리자 여부 확인
        if ($member['role'] == '관리자') {
            $is_admin = true;
        }
    } else {
        // 로그인 정보가 유효하지 않음
        unset($_SESSION['user_id']);
        $is_member = false;
    }
}

// XSS 방지를 위한 설정
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');

// CSRF 토큰 생성
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// 기본 언어 설정
$cf_lang = 'ko';
