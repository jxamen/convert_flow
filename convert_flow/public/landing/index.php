<?php
include_once("../../includes/_common.php");
include_once("../../lib/landing_page.lib.php");

// URL에서 슬러그 추출
$request_uri = $_SERVER['REQUEST_URI'];
$parts = explode('/landing/', $request_uri);

if (count($parts) > 1) {
    $slug = trim($parts[1], '/');
    $slug = strtok($slug, '?'); // URL 파라미터 제거
} else {
    // 슬러그가 없는 경우 404 페이지 표시
    header('HTTP/1.0 404 Not Found');
    include_once('../../404.php');
    exit;
}

// 슬러그로 랜딩페이지 정보 조회
$landing = get_landing_page_by_slug($slug);

if (!$landing) {
    // 존재하지 않는 랜딩페이지인 경우 404 페이지 표시
    header('HTTP/1.0 404 Not Found');
    include_once('../../404.php');
    exit;
}

// 게시 상태 확인
if ($landing['status'] != '게시됨') {
    // 관리자나 소유자가 아니면 접근 제한
    if (!$is_admin && (!$is_member || $landing['user_id'] != $member['mb_id'])) {
        alert('이 페이지는 현재 공개되지 않았습니다.');
    }
}

// 방문 카운터 증가 (추가 기능)
$sql = "UPDATE landing_pages SET views = views + 1 WHERE id = '{$landing['id']}'";
sql_query($sql);

// 추적 파라미터 처리 (UTM 등)
$tracking_params = array();
foreach ($_GET as $key => $value) {
    if (strpos($key, 'utm_') === 0) {
        $tracking_params[$key] = $value;
    }
}

// 랜딩페이지 컨텐츠 출력
?><!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php if ($landing['meta_title']) { ?>
    <title><?php echo $landing['meta_title']; ?></title>
    <meta property="og:title" content="<?php echo $landing['meta_title']; ?>">
    <?php } else { ?>
    <title><?php echo $landing['name']; ?></title>
    <meta property="og:title" content="<?php echo $landing['name']; ?>">
    <?php } ?>
    
    <?php if ($landing['meta_description']) { ?>
    <meta name="description" content="<?php echo $landing['meta_description']; ?>">
    <meta property="og:description" content="<?php echo $landing['meta_description']; ?>">
    <?php } ?>
    
    <style>
        /* 기본 스타일 */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Noto Sans KR', sans-serif;
        }
        
        /* 사용자 정의 CSS */
        <?php echo $landing['css_content']; ?>
    </style>
</head>
<body>
    <?php echo $landing['html_content']; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- 사용자 정의 JavaScript -->
    <script>
        <?php echo $landing['js_content']; ?>
    </script>
</body>
</html><?php
?>