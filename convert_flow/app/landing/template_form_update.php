<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

if ($is_admin != "super") {
    alert_close("관리자만 접근 가능합니다.");
}

// POST 값 검증
$w = isset($_POST['w']) ? $_POST['w'] : '';
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$industry = isset($_POST['industry']) ? $_POST['industry'] : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$html_template = isset($_POST['html_template']) ? $_POST['html_template'] : '';
$css_template = isset($_POST['css_template']) ? $_POST['css_template'] : '';
$js_template = isset($_POST['js_template']) ? $_POST['js_template'] : '';

// 필수 입력값 검증
if (!$name) {
    alert("템플릿 이름을 입력해주세요.");
}

if (!$industry) {
    alert("산업 분류를 선택해주세요.");
}

if (!$html_template) {
    alert("HTML 코드를 입력해주세요.");
}

// 썸네일 이미지 처리
$thumbnail_url = '';

if ($w === 'u' && $id) {
    // 수정일 경우 기존 데이터 조회
    $sql = "SELECT thumbnail_url FROM landing_page_templates WHERE id = '{$id}'";
    $row = sql_fetch($sql);
    $thumbnail_url = $row['thumbnail_url'];
}

// 이미지 삭제 체크
if (isset($_POST['thumbnail_del']) && $_POST['thumbnail_del']) {
    if ($thumbnail_url) {
        @unlink(G5_DATA_PATH . '/landing_templates/' . basename($thumbnail_url));
        $thumbnail_url = '';
    }
}

// 새 이미지 업로드
if ($_FILES['thumbnail']['name']) {
    // 이미지 업로드 디렉토리 확인 및 생성
    $upload_dir = G5_DATA_PATH . '/landing_templates';
    
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, G5_DIR_PERMISSION);
        @chmod($upload_dir, G5_DIR_PERMISSION);
    }
    
    // 파일 업로드 처리
    $filename = $_FILES['thumbnail']['name'];
    $tmp_file = $_FILES['thumbnail']['tmp_name'];
    $filesize = $_FILES['thumbnail']['size'];
    $filetypes = array("image/jpeg", "image/jpg", "image/png", "image/gif");
    
    // 이미지 타입 확인
    if (!in_array($_FILES['thumbnail']['type'], $filetypes)) {
        alert("JPG, PNG, GIF 형식의 이미지만 업로드 가능합니다.");
    }
    
    // 파일 크기 검사 (최대 2MB)
    if ($filesize > 2097152) {
        alert("이미지 크기는 최대 2MB까지 가능합니다.");
    }
    
    // 중복 방지를 위한 파일명 변경
    $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
    $newfilename = date("YmdHis") . "_" . mt_rand(1000, 9999) . "." . $file_ext;
    $dest_file = $upload_dir . '/' . $newfilename;
    
    // 기존 파일 삭제 (수정 시)
    if ($w === 'u' && $thumbnail_url) {
        @unlink(G5_DATA_PATH . '/landing_templates/' . basename($thumbnail_url));
    }
    
    // 파일 이동
    if (move_uploaded_file($tmp_file, $dest_file)) {
        // 이미지 URL 저장
        $thumbnail_url = G5_DATA_URL . '/landing_templates/' . $newfilename;
        chmod($dest_file, G5_FILE_PERMISSION);
    } else {
        alert("파일 업로드에 실패했습니다.");
    }
}

// DB 처리
if ($w === 'u' && $id) {
    $sql = "UPDATE landing_page_templates SET
                name = '{$name}',
                industry = '{$industry}',
                description = '{$description}',
                html_template = '{$html_template}',
                css_template = '{$css_template}',
                js_template = '{$js_template}'";
    
    if ($thumbnail_url) {
        $sql .= ", thumbnail_url = '{$thumbnail_url}'";
    }
    
    $sql .= " WHERE id = '{$id}'";
    
    sql_query($sql);
    
    alert("템플릿이 수정되었습니다.", "template_list.php");
} else {
    $sql = "INSERT INTO landing_page_templates
                (name, industry, description, thumbnail_url, html_template, css_template, js_template, created_at)
            VALUES
                ('{$name}', '{$industry}', '{$description}', '{$thumbnail_url}', '{$html_template}', '{$css_template}', '{$js_template}', NOW())";
    
    sql_query($sql);
    
    alert("템플릿이 등록되었습니다.", "template_list.php");
}
?>