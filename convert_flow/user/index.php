<?php
include_once("../includes/_common.php");

if (!$is_member) {
    alert("로그인이 필요한 서비스입니다.", G5_BBS_URL."/login.php?url=".urlencode($_SERVER['REQUEST_URI']));
}

// 사용자 대시보드 페이지로 리다이렉트
header("Location: landing/landing_list.php");
exit;
?>