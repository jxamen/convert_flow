<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 템플릿 관련 함수 모음
 */

/**
 * 템플릿 산업 목록 반환
 * @return array 산업 목록
 */
function get_template_industry_list() {
    return array(
        '금융' => '금융',
        '교육' => '교육',
        '건강' => '건강',
        '소매' => '소매',
        '여행' => '여행',
        '기타' => '기타'
    );
}

/**
 * 템플릿 정보 가져오기
 * @param int $id 템플릿 ID
 * @return array 템플릿 정보
 */
function get_template($id) {
    $sql = "SELECT * FROM landing_page_templates WHERE id = '$id'";
    return sql_fetch($sql);
}

/**
 * 템플릿 목록 가져오기
 * @param array $params 검색 조건
 * @param int $limit 한 페이지에 표시할 개수
 * @param int $offset 시작 위치
 * @return array 템플릿 목록
 */
function get_templates($params = array(), $limit = 10, $offset = 0) {
    $where = "WHERE 1=1";
    
    // 산업별 필터링
    if (isset($params['industry']) && $params['industry']) {
        $where .= " AND industry = '{$params['industry']}'";
    }
    
    // 검색어 필터링
    if (isset($params['search']) && $params['search']) {
        $where .= " AND (name LIKE '%{$params['search']}%' OR description LIKE '%{$params['search']}%')";
    }
    
    // 정렬 조건
    $order_by = isset($params['order_by']) ? $params['order_by'] : 'created_at';
    $order_dir = isset($params['order_dir']) ? $params['order_dir'] : 'DESC';
    
    $sql = "SELECT * FROM landing_page_templates $where ORDER BY $order_by $order_dir LIMIT $offset, $limit";
    $result = sql_query($sql);
    
    $list = array();
    for ($i = 0; $row = sql_fetch_array($result); $i++) {
        $list[$i] = $row;
    }
    
    return $list;
}

/**
 * 템플릿 업데이트
 * @param int $id 템플릿 ID
 * @param array $data 업데이트할 데이터
 * @return bool 성공 여부
 */
function update_template($id, $data) {
    $fields = array();
    
    if (isset($data['name'])) {
        $fields[] = "name = '{$data['name']}'";
    }
    
    if (isset($data['industry'])) {
        $fields[] = "industry = '{$data['industry']}'";
    }
    
    if (isset($data['description'])) {
        $fields[] = "description = '{$data['description']}'";
    }
    
    if (isset($data['thumbnail_url'])) {
        $fields[] = "thumbnail_url = '{$data['thumbnail_url']}'";
    }
    
    if (isset($data['html_template'])) {
        $fields[] = "html_template = '{$data['html_template']}'";
    }
    
    if (isset($data['css_template'])) {
        $fields[] = "css_template = '{$data['css_template']}'";
    }
    
    if (isset($data['js_template'])) {
        $fields[] = "js_template = '{$data['js_template']}'";
    }
    
    if (empty($fields)) {
        return false;
    }
    
    $fields[] = "updated_at = NOW()";
    
    $fields_str = implode(", ", $fields);
    $sql = "UPDATE landing_page_templates SET $fields_str WHERE id = '$id'";
    
    return sql_query($sql);
}

/**
 * 템플릿 삭제
 * @param int $id 템플릿 ID
 * @return bool 성공 여부
 */
function delete_template($id) {
    $sql = "DELETE FROM landing_page_templates WHERE id = '$id'";
    return sql_query($sql);
}

/**
 * 템플릿 썸네일 업로드
 * @param array $file $_FILES 배열
 * @param string $old_thumbnail 기존 썸네일 URL (있으면 삭제)
 * @return string|bool 업로드된 파일 URL 또는 실패 시 false
 */
function upload_template_thumbnail($file, $old_thumbnail = '') {
    // 이미지 업로드 디렉토리
    $upload_dir = G5_DATA_PATH . '/landing_templates';
    
    // 디렉토리 생성
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, G5_DIR_PERMISSION);
        @chmod($upload_dir, G5_DIR_PERMISSION);
    }
    
    // 업로드 파일 정보
    $filename = $file['name'];
    $tmp_file = $file['tmp_name'];
    $filesize = $file['size'];
    $file_type = $file['type'];
    
    // 허용된 이미지 타입
    $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif');
    
    // 파일 타입 검사
    if (!in_array($file_type, $allowed_types)) {
        return false;
    }
    
    // 파일 크기 검사 (2MB 제한)
    if ($filesize > 2097152) {
        return false;
    }
    
    // 중복 방지를 위한 파일명 변경
    $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
    $new_filename = date("YmdHis") . "_" . mt_rand(1000, 9999) . "." . $file_ext;
    $dest_file = $upload_dir . '/' . $new_filename;
    
    // 기존 파일 삭제
    if ($old_thumbnail) {
        $old_file = G5_DATA_PATH . '/landing_templates/' . basename($old_thumbnail);
        if (file_exists($old_file)) {
            @unlink($old_file);
        }
    }
    
    // 파일 업로드
    if (move_uploaded_file($tmp_file, $dest_file)) {
        chmod($dest_file, G5_FILE_PERMISSION);
        return G5_DATA_URL . '/landing_templates/' . $new_filename;
    } else {
        return false;
    }
}
?>