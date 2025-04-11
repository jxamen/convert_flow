<?php
include_once("../../includes/_common.php");

if (!$is_member) {
    alert("로그인이 필요한 서비스입니다.", G5_BBS_URL."/login.php?url=".urlencode($_SERVER['REQUEST_URI']));
}

// POST 값 검증
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
$campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
$template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
$meta_title = isset($_POST['meta_title']) ? trim($_POST['meta_title']) : '';
$meta_description = isset($_POST['meta_description']) ? trim($_POST['meta_description']) : '';

// 필수 입력값 검증
if (!$name) {
    alert("랜딩페이지 이름을 입력해주세요.");
}

if (!$slug) {
    alert("URL 경로를 입력해주세요.");
}

// 슬러그 중복 확인
$sql = "SELECT COUNT(*) AS cnt FROM landing_pages WHERE slug = '{$slug}'";
$row = sql_fetch($sql);

if ($row['cnt'] > 0) {
    alert("이미 사용 중인 URL입니다. 다른 URL을 입력해주세요.");
}

// 템플릿 데이터 가져오기
$html_content = '';
$css_content = '';
$js_content = '';

if ($template_id > 0) {
    $sql = "SELECT html_template, css_template, js_template FROM landing_page_templates WHERE id = '{$template_id}'";
    $template = sql_fetch($sql);
    
    if ($template) {
        $html_content = $template['html_template'];
        $css_content = $template['css_template'];
        $js_content = $template['js_template'];
    }
}

// 랜딩페이지 등록
$sql = "INSERT INTO landing_pages
            (user_id, campaign_id, template_id, name, slug, status, html_content, css_content, js_content, meta_title, meta_description, created_at)
        VALUES
            ('{$member['mb_id']}', " . ($campaign_id ? "'{$campaign_id}'" : "NULL") . ", " . ($template_id ? "'{$template_id}'" : "NULL") . ", '{$name}', '{$slug}', '초안', '{$html_content}', '{$css_content}', '{$js_content}', '{$meta_title}', '{$meta_description}', NOW())";

sql_query($sql);

$landing_id = sql_insert_id();

if ($landing_id) {
    // 에디터 페이지로 이동
    goto_url("landing_editor.php?id={$landing_id}");
} else {
    alert("랜딩페이지 생성에 실패했습니다. 다시 시도해주세요.", "landing_create.php");
}
?>