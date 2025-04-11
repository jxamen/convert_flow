<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 랜딩페이지 관련 함수 모음
 */

/**
 * 랜딩페이지 상태 목록 반환
 * @return array 상태 목록
 */
function get_landing_page_status_list() {
    return array(
        '초안' => '초안',
        '게시됨' => '게시됨',
        '보관됨' => '보관됨'
    );
}

/**
 * 랜딩페이지 URL 생성
 * @param string $slug 랜딩페이지 슬러그
 * @return string 완성된 URL
 */
function get_landing_page_url($slug) {
    return G5_URL . '/landing/' . $slug;
}

/**
 * 랜딩페이지 정보 가져오기
 * @param int $id 랜딩페이지 ID
 * @return array 랜딩페이지 정보
 */
function get_landing_page($id) {
    $sql = "SELECT * FROM landing_pages WHERE id = '$id'";
    return sql_fetch($sql);
}

/**
 * 랜딩페이지 슬러그로 정보 가져오기
 * @param string $slug 랜딩페이지 슬러그
 * @return array 랜딩페이지 정보
 */
function get_landing_page_by_slug($slug) {
    $sql = "SELECT * FROM landing_pages WHERE slug = '$slug'";
    return sql_fetch($sql);
}

/**
 * 랜딩페이지 목록 가져오기
 * @param array $params 검색 조건
 * @param int $limit 한 페이지에 표시할 개수
 * @param int $offset 시작 위치
 * @return array 랜딩페이지 목록
 */
function get_landing_pages($params = array(), $limit = 10, $offset = 0) {
    $where = "WHERE 1=1";
    
    // 사용자별 필터링
    if (isset($params['user_id']) && $params['user_id']) {
        $where .= " AND user_id = '{$params['user_id']}'";
    }
    
    // 상태별 필터링
    if (isset($params['status']) && $params['status']) {
        $where .= " AND status = '{$params['status']}'";
    }
    
    // 캠페인별 필터링
    if (isset($params['campaign_id']) && $params['campaign_id']) {
        $where .= " AND campaign_id = '{$params['campaign_id']}'";
    }
    
    // 검색어 필터링
    if (isset($params['search']) && $params['search']) {
        $where .= " AND (name LIKE '%{$params['search']}%' OR slug LIKE '%{$params['search']}%')";
    }
    
    // 정렬 조건
    $order_by = isset($params['order_by']) ? $params['order_by'] : 'created_at';
    $order_dir = isset($params['order_dir']) ? $params['order_dir'] : 'DESC';
    
    $sql = "SELECT * FROM landing_pages $where ORDER BY $order_by $order_dir LIMIT $offset, $limit";
    $result = sql_query($sql);
    
    $list = array();
    for ($i = 0; $row = sql_fetch_array($result); $i++) {
        $list[$i] = $row;
    }
    
    return $list;
}

/**
 * 랜딩페이지 업데이트
 * @param int $id 랜딩페이지 ID
 * @param array $data 업데이트할 데이터
 * @return bool 성공 여부
 */
function update_landing_page($id, $data) {
    $fields = array();
    
    if (isset($data['name'])) {
        $fields[] = "name = '{$data['name']}'";
    }
    
    if (isset($data['slug'])) {
        $fields[] = "slug = '{$data['slug']}'";
    }
    
    if (isset($data['status'])) {
        $fields[] = "status = '{$data['status']}'";
    }
    
    if (isset($data['html_content'])) {
        $fields[] = "html_content = '{$data['html_content']}'";
    }
    
    if (isset($data['css_content'])) {
        $fields[] = "css_content = '{$data['css_content']}'";
    }
    
    if (isset($data['js_content'])) {
        $fields[] = "js_content = '{$data['js_content']}'";
    }
    
    if (isset($data['meta_title'])) {
        $fields[] = "meta_title = '{$data['meta_title']}'";
    }
    
    if (isset($data['meta_description'])) {
        $fields[] = "meta_description = '{$data['meta_description']}'";
    }
    
    if (isset($data['campaign_id'])) {
        if ($data['campaign_id']) {
            $fields[] = "campaign_id = '{$data['campaign_id']}'";
        } else {
            $fields[] = "campaign_id = NULL";
        }
    }
    
    if (empty($fields)) {
        return false;
    }
    
    $fields[] = "updated_at = NOW()";
    
    $fields_str = implode(", ", $fields);
    $sql = "UPDATE landing_pages SET $fields_str WHERE id = '$id'";
    
    return sql_query($sql);
}

/**
 * 랜딩페이지 삭제
 * @param int $id 랜딩페이지 ID
 * @param int $user_id 사용자 ID (권한 확인용)
 * @return bool 성공 여부
 */
function delete_landing_page($id, $user_id = '') {
    $where = "WHERE id = '$id'";
    
    // 사용자 권한 확인
    if ($user_id) {
        $where .= " AND user_id = '$user_id'";
    }
    
    $sql = "DELETE FROM landing_pages $where";
    return sql_query($sql);
}

/**
 * 랜딩페이지 게시 상태 변경
 * @param int $id 랜딩페이지 ID
 * @param string $status 변경할 상태
 * @return bool 성공 여부
 */
function change_landing_page_status($id, $status) {
    $sql = "UPDATE landing_pages SET status = '$status', updated_at = NOW() WHERE id = '$id'";
    return sql_query($sql);
}
?>