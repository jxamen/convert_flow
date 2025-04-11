<?php
/**
 * 전환 이벤트 데이터 엑셀 내보내기
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 모델 로드
require_once CF_INCLUDE_PATH . '/models/campaign.model.php';
require_once CF_INCLUDE_PATH . '/models/conversion.model.php';
$campaign_model = new CampaignModel();
$conversion_model = new ConversionModel();

// 파라미터 검증
$campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
$script_id = isset($_GET['script_id']) ? intval($_GET['script_id']) : 0;

// 캠페인 또는 스크립트 정보 조회
$campaign = null;
$script = null;
$filename = "conversion_events_";

if ($script_id > 0) {
    // 특정 스크립트의 이벤트 조회
    $script = $conversion_model->get_conversion_script($script_id);
    if (!$script) {
        alert('존재하지 않는 전환 스크립트입니다.', 'campaign_list.php');
        exit;
    }
    
    $campaign_id = $script['campaign_id'];
    $campaign = $campaign_model->get_campaign($campaign_id);
    $filename .= "script_" . $script_id . "_" . date('Ymd');
} elseif ($campaign_id > 0) {
    // 특정 캠페인의 모든 이벤트 조회
    $campaign = $campaign_model->get_campaign($campaign_id);
    $filename .= "campaign_" . $campaign_id . "_" . date('Ymd');
} else {
    alert('캠페인 또는 스크립트 ID가 필요합니다.', 'campaign_list.php');
    exit;
}

// 캠페인이 존재하지 않거나 접근 권한이 없는 경우
if (!$campaign || ($is_admin !== "super" && $campaign['user_id'] != $member['id'])) {
    alert('존재하지 않거나 접근 권한이 없는 캠페인입니다.', 'campaign_list.php');
    exit;
}

// 필터링 조건
$date_start = isset($_GET['date_start']) ? trim($_GET['date_start']) : date('Y-m-d', strtotime('-30 days'));
$date_end = isset($_GET['date_end']) ? trim($_GET['date_end']) : date('Y-m-d');
$source_filter = isset($_GET['source']) ? trim($_GET['source']) : '';
$device_filter = isset($_GET['device']) ? trim($_GET['device']) : '';

// 쿼리 조건 구성
$where_clause = "WHERE 1=1";

if ($script_id > 0) {
    $where_clause .= " AND ce.script_id = '$script_id'";
} elseif ($campaign_id > 0) {
    $where_clause .= " AND ce.campaign_id = '$campaign_id'";
}

// 날짜 범위 필터링
if ($date_start) {
    $where_clause .= " AND DATE(ce.conversion_time) >= '$date_start'";
}
if ($date_end) {
    $where_clause .= " AND DATE(ce.conversion_time) <= '$date_end'";
}

// 소스 필터링
if ($source_filter) {
    if ($source_filter === 'direct') {
        $where_clause .= " AND (ce.source IS NULL OR ce.source = '')";
    } else {
        $where_clause .= " AND ce.source = '$source_filter'";
    }
}

// 디바이스 필터링
if ($device_filter) {
    $where_clause .= " AND ce.device_type = '$device_filter'";
}

// 이벤트 데이터 조회
$sql = "SELECT 
            ce.id,
            ce.conversion_time, 
            cs.conversion_type,
            ce.source,
            ce.medium,
            ce.utm_campaign,
            ce.utm_content,
            ce.utm_term,
            ce.referrer,
            ce.conversion_value,
            ce.device_type,
            ce.browser,
            ce.os,
            ce.ip_address,
            ce.country,
            ce.region,
            ce.city
        FROM {$cf_table_prefix}conversion_events ce
        LEFT JOIN {$cf_table_prefix}conversion_scripts cs ON ce.script_id = cs.id
        $where_clause
        ORDER BY ce.conversion_time DESC";
$result = sql_query($sql);

// 엑셀 파일 생성
require_once(CF_LIB_PATH . '/PHPExcel/PHPExcel.php');

// 새 PHPExcel 객체 생성
$objPHPExcel = new PHPExcel();

// 메타데이터 설정
$objPHPExcel->getProperties()
    ->setCreator("Convert Flow")
    ->setLastModifiedBy("Convert Flow")
    ->setTitle("전환 이벤트 데이터")
    ->setSubject("전환 이벤트 데이터 내보내기")
    ->setDescription("캠페인 전환 이벤트 목록");

// 활성 시트 설정
$objPHPExcel->setActiveSheetIndex(0);
$objPHPExcel->getActiveSheet()->setTitle('전환 이벤트');

// 헤더 설정
$objPHPExcel->getActiveSheet()
    ->setCellValue('A1', '이벤트 ID')
    ->setCellValue('B1', '전환 시간')
    ->setCellValue('C1', '전환 유형')
    ->setCellValue('D1', '전환 가치')
    ->setCellValue('E1', '소스')
    ->setCellValue('F1', '매체')
    ->setCellValue('G1', '캠페인')
    ->setCellValue('H1', '콘텐츠')
    ->setCellValue('I1', '키워드')
    ->setCellValue('J1', '참조 URL')
    ->setCellValue('K1', '디바이스')
    ->setCellValue('L1', '브라우저')
    ->setCellValue('M1', '운영체제')
    ->setCellValue('N1', 'IP 주소')
    ->setCellValue('O1', '국가')
    ->setCellValue('P1', '지역')
    ->setCellValue('Q1', '도시');

// 데이터 설정
$row = 2;
while ($event = sql_fetch_array($result)) {
    $objPHPExcel->getActiveSheet()
        ->setCellValue('A' . $row, $event['id'])
        ->setCellValue('B' . $row, $event['conversion_time'])
        ->setCellValue('C' . $row, $event['conversion_type'])
        ->setCellValue('D' . $row, $event['conversion_value'])
        ->setCellValue('E' . $row, $event['source'] ? $event['source'] : '직접 방문')
        ->setCellValue('F' . $row, $event['medium'])
        ->setCellValue('G' . $row, $event['utm_campaign'])
        ->setCellValue('H' . $row, $event['utm_content'])
        ->setCellValue('I' . $row, $event['utm_term'])
        ->setCellValue('J' . $row, $event['referrer'])
        ->setCellValue('K' . $row, $event['device_type'])
        ->setCellValue('L' . $row, $event['browser'])
        ->setCellValue('M' . $row, $event['os'])
        ->setCellValue('N' . $row, $event['ip_address'])
        ->setCellValue('O' . $row, $event['country'])
        ->setCellValue('P' . $row, $event['region'])
        ->setCellValue('Q' . $row, $event['city']);
    $row++;
}

// 컬럼 너비 자동 조정
foreach(range('A', 'Q') as $col) {
    $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

// 헤더 스타일 설정
$objPHPExcel->getActiveSheet()->getStyle('A1:Q1')->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle('A1:Q1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('DDDDDD');

// 파일 출력
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
exit;
?>