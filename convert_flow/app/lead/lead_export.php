<?php
/**
 * 리드 데이터 엑셀 내보내기 (통합 관리)
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 캠페인 필터 (선택적)
$campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;

// 폼 필터 (선택적)
$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;

// 검색어 및 필터
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$source = isset($_GET['source']) ? trim($_GET['source']) : '';

// 모델 로드
require_once CF_MODEL_PATH . '/form.model.php';
$form_model = new FormModel();

require_once CF_MODEL_PATH . '/campaign.model.php';
$campaign_model = new CampaignModel();

// 권한 체크를 위한 쿼리 초기화
$where_my = '';
if (!$is_admin) {
    // 관리자가 아닌 경우 본인 소유 리드만 볼 수 있음
    $where_my = " AND (c.user_id = {$member['id']} OR f.user_id = {$member['id']})";
}

// 검색 조건 구성
$where = "WHERE 1=1" . $where_my;

if ($campaign_id > 0) {
    $where .= " AND l.campaign_id = " . intval($campaign_id);
}

if ($form_id > 0) {
    $where .= " AND l.form_id = " . intval($form_id);
}

if (!empty($search)) {
    $where .= " AND l.data LIKE '%" . sql_escape_string($search) . "%'";
}

if (!empty($start_date)) {
    $where .= " AND l.created_at >= '" . sql_escape_string($start_date) . " 00:00:00'";
}

if (!empty($end_date)) {
    $where .= " AND l.created_at <= '" . sql_escape_string($end_date) . " 23:59:59'";
}

if (!empty($status)) {
    $where .= " AND l.status = '" . sql_escape_string($status) . "'";
}

if (!empty($source)) {
    $where .= " AND l.utm_source = '" . sql_escape_string($source) . "'";
}

// 리드 데이터 조회 (폼 및 캠페인 정보 포함)
$sql = "SELECT l.*, f.name as form_name, c.name as campaign_name 
        FROM {$cf_table_prefix}leads l 
        LEFT JOIN {$cf_table_prefix}forms f ON l.form_id = f.id 
        LEFT JOIN {$cf_table_prefix}campaigns c ON l.campaign_id = c.id
        $where 
        ORDER BY l.created_at DESC";
$result = sql_query($sql);

// 리드 데이터 수집
$leads = array();
while ($row = sql_fetch_array($result)) {
    $leads[] = $row;
}

// 파일명 설정
$filename = "리드데이터_" . date('Ymd') . ".csv";
if ($campaign_id > 0) {
    $campaign = $campaign_model->get_campaign($campaign_id);
    if ($campaign) {
        $filename = $campaign['name'] . "_리드데이터_" . date('Ymd') . ".csv";
    }
} else if ($form_id > 0) {
    $form = $form_model->get_form($form_id);
    if ($form) {
        $filename = $form['name'] . "_리드데이터_" . date('Ymd') . ".csv";
    }
}

// 헤더 설정 (다운로드 유도)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// 출력 버퍼 시작
ob_start();

// CSV 파일 생성을 위한 파일 핸들 열기
$output = fopen('php://output', 'w');

// UTF-8 BOM 추가 (Excel에서 한글 깨짐 방지)
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// 헤더 행 작성
$headers = array('ID', '제출일시', '캠페인', '폼', '상태', 'IP 주소', '소스', '매체');

// 필드명 데이터 추가 - 가장 많은 필드를 가진 리드 찾기
$field_headers = array();
foreach ($leads as $lead) {
    $lead_data = json_decode($lead['data'], true);
    if (is_array($lead_data)) {
        foreach ($lead_data as $key => $value) {
            if (!in_array($key, $field_headers)) {
                $field_headers[] = $key;
            }
        }
    }
}

// 헤더에 필드명 추가
$headers = array_merge($headers, $field_headers);
fputcsv($output, $headers);

// 데이터 행 작성
foreach ($leads as $lead) {
    $row = array(
        $lead['id'],                                  // ID
        $lead['created_at'],                         // 제출일시
        $lead['campaign_name'] ?? '미지정',           // 캠페인
        $lead['form_name'] ?? '미지정',               // 폼명
        $lead['status'],                             // 상태
        $lead['ip_address'],                         // IP 주소
        $lead['utm_source'],                         // 소스
        $lead['utm_medium']                          // 매체
    );
    
    // 폼 필드 데이터 추가
    $lead_data = json_decode($lead['data'], true);
    
    // 각 헤더 필드에 대해 데이터 채우기
    foreach ($field_headers as $header) {
        $value = isset($lead_data[$header]) ? $lead_data[$header] : '';
        $row[] = $value;
    }
    
    // CSV 행 작성
    fputcsv($output, $row);
}

fclose($output);
ob_end_flush();
exit;