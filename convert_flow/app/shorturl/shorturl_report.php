<?php
/**
 * 단축 URL 보고서 생성 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 모델 로드
require_once CF_MODEL_PATH . '/shorturl.model.php';
$shorturl_model = new ShorturlModel();

// ID 체크
if (empty($_GET['id'])) {
    alert('잘못된 접근입니다.');
}

$url_id = intval($_GET['id']);

// 단축 URL 정보 조회
$url_info = $shorturl_model->get_shorturl($url_id, $member['id']);

if (!$url_info) {
    alert('존재하지 않는 단축 URL이거나 접근 권한이 없습니다.');
}

// 통계 기간 설정
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// 클릭 통계 조회
$click_stats = $shorturl_model->get_click_stats($url_id, 'day', $start_date, $end_date);
$device_stats = $shorturl_model->get_click_stats($url_id, 'device', $start_date, $end_date);
$browser_stats = $shorturl_model->get_click_stats($url_id, 'browser', $start_date, $end_date);
$os_stats = $shorturl_model->get_click_stats($url_id, 'os', $start_date, $end_date);

// 완전한 단축 URL 생성
$short_url = !empty($url_info['domain']) ? $url_info['domain'] . '/' . $url_info['path'] : CF_URL . '/' . $url_info['path'];

// 총 클릭수 계산
$total_clicks = 0;
foreach ($click_stats as $stat) {
    $total_clicks += $stat['value'];
}

// Excel 파일 생성
require_once CF_INCLUDE_PATH . '/PHPExcel.php';

// 새 워크북 생성
$excel = new PHPExcel();

// 메타데이터 설정
$excel->getProperties()
    ->setCreator("ConvertFlow")
    ->setLastModifiedBy("ConvertFlow")
    ->setTitle("단축 URL 보고서")
    ->setSubject("단축 URL: " . $short_url)
    ->setDescription("기간: " . $start_date . " ~ " . $end_date);

// 첫 번째 시트 - 요약
$sheet = $excel->setActiveSheetIndex(0);
$sheet->setTitle("요약 정보");

// 제목 및 기본 정보
$sheet->setCellValue('A1', '단축 URL 성과 보고서');
$sheet->setCellValue('A3', '기본 정보');
$sheet->setCellValue('A4', '단축 URL:');
$sheet->setCellValue('B4', $short_url);
$sheet->setCellValue('A5', '원본 URL:');
$sheet->setCellValue('B5', $url_info['original_url']);
$sheet->setCellValue('A6', '캠페인:');
$sheet->setCellValue('B6', $url_info['campaign_name'] ?: '-');
$sheet->setCellValue('A7', '랜딩페이지:');
$sheet->setCellValue('B7', $url_info['landing_name'] ?: '-');
$sheet->setCellValue('A8', '소재 유형:');
$sheet->setCellValue('B8', $url_info['source_type'] ?: '-');
$sheet->setCellValue('A9', '소재 이름:');
$sheet->setCellValue('B9', $url_info['source_name'] ?: '-');
$sheet->setCellValue('A10', '생성일:');
$sheet->setCellValue('B10', date('Y-m-d', strtotime($url_info['created_at'])));
$sheet->setCellValue('A11', '보고서 기간:');
$sheet->setCellValue('B11', $start_date . ' ~ ' . $end_date);

// 성과 요약
$sheet->setCellValue('A13', '성과 요약');
$sheet->setCellValue('A14', '총 클릭수:');
$sheet->setCellValue('B14', $total_clicks);
$sheet->setCellValue('A15', '전환수:');
$sheet->setCellValue('B15', $url_info['conversion_count'] ?: 0);

// 전환율 계산
$conversion_rate = 0;
if (isset($url_info['conversion_count']) && $url_info['conversion_count'] > 0 && $total_clicks > 0) {
    $conversion_rate = round(($url_info['conversion_count'] / $total_clicks) * 100, 2);
}
$sheet->setCellValue('A16', '전환율:');
$sheet->setCellValue('B16', $conversion_rate . '%');

// 스타일 설정
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A13')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A4:A16')->getFont()->setBold(true);

// 열 너비 설정
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(50);

// 두 번째 시트 - 일별 클릭 데이터
$excel->createSheet(1);
$sheet = $excel->setActiveSheetIndex(1);
$sheet->setTitle("일별 클릭");

// 헤더 설정
$sheet->setCellValue('A1', '날짜');
$sheet->setCellValue('B1', '클릭수');
$sheet->getStyle('A1:B1')->getFont()->setBold(true);

// 데이터 입력
$row = 2;
foreach ($click_stats as $stat) {
    $sheet->setCellValue('A' . $row, $stat['label']);
    $sheet->setCellValue('B' . $row, $stat['value']);
    $row++;
}

// 열 너비 설정
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(15);

// 세 번째 시트 - 기기별 클릭 데이터
$excel->createSheet(2);
$sheet = $excel->setActiveSheetIndex(2);
$sheet->setTitle("기기별 클릭");

// 헤더 설정
$sheet->setCellValue('A1', '기기 유형');
$sheet->setCellValue('B1', '클릭수');
$sheet->setCellValue('C1', '비율 (%)');
$sheet->getStyle('A1:C1')->getFont()->setBold(true);

// 데이터 입력
$row = 2;
foreach ($device_stats as $stat) {
    $percentage = round(($stat['value'] / $total_clicks) * 100, 2);
    $sheet->setCellValue('A' . $row, ucfirst($stat['label']));
    $sheet->setCellValue('B' . $row, $stat['value']);
    $sheet->setCellValue('C' . $row, $percentage);
    $row++;
}

// 열 너비 설정
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(15);

// 네 번째 시트 - 브라우저별 클릭 데이터
$excel->createSheet(3);
$sheet = $excel->setActiveSheetIndex(3);
$sheet->setTitle("브라우저별 클릭");

// 헤더 설정
$sheet->setCellValue('A1', '브라우저');
$sheet->setCellValue('B1', '클릭수');
$sheet->setCellValue('C1', '비율 (%)');
$sheet->getStyle('A1:C1')->getFont()->setBold(true);

// 데이터 입력
$row = 2;
foreach ($browser_stats as $stat) {
    $percentage = round(($stat['value'] / $total_clicks) * 100, 2);
    $sheet->setCellValue('A' . $row, $stat['label']);
    $sheet->setCellValue('B' . $row, $stat['value']);
    $sheet->setCellValue('C' . $row, $percentage);
    $row++;
}

// 열 너비 설정
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(15);

// 다섯 번째 시트 - OS별 클릭 데이터
$excel->createSheet(4);
$sheet = $excel->setActiveSheetIndex(4);
$sheet->setTitle("OS별 클릭");

// 헤더 설정
$sheet->setCellValue('A1', '운영체제');
$sheet->setCellValue('B1', '클릭수');
$sheet->setCellValue('C1', '비율 (%)');
$sheet->getStyle('A1:C1')->getFont()->setBold(true);

// 데이터 입력
$row = 2;
foreach ($os_stats as $stat) {
    $percentage = round(($stat['value'] / $total_clicks) * 100, 2);
    $sheet->setCellValue('A' . $row, $stat['label']);
    $sheet->setCellValue('B' . $row, $stat['value']);
    $sheet->setCellValue('C' . $row, $percentage);
    $row++;
}

// 열 너비 설정
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(15);

// 첫 번째 시트로 돌아가기
$excel->setActiveSheetIndex(0);

// 파일명 설정
$filename = 'shorturl_report_' . $url_id . '_' . date('Ymd') . '.xlsx';

// 헤더 설정
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Excel 파일 출력
$writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
$writer->save('php://output');
exit;
?>