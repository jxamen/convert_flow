<?php
/**
 * 단축 URL 상세 조회 페이지
 * 파일명: shorturl_view.php
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 페이지 제목
$page_title = "단축 URL 상세";

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

// 클릭 통계 조회 (기본: 최근 30일)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$group_by = isset($_GET['group_by']) ? $_GET['group_by'] : 'day';

// 클릭 추이 데이터
$click_stats = $shorturl_model->get_click_stats($url_id, $group_by, $start_date, $end_date);

// 기기별 클릭 데이터
$device_stats = $shorturl_model->get_click_stats($url_id, 'device', $start_date, $end_date);

// 브라우저별 클릭 데이터
$browser_stats = $shorturl_model->get_click_stats($url_id, 'browser', $start_date, $end_date);

// OS별 클릭 데이터
$os_stats = $shorturl_model->get_click_stats($url_id, 'os', $start_date, $end_date);

// 완전한 단축 URL 생성
$short_url = !empty($url_info['domain']) ? $url_info['domain'] . '/' . $url_info['path'] : CF_URL . '/' . $url_info['path'];

// 만료 여부 확인
$is_expired = $shorturl_model->is_expired($url_info);

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 text-gray-800">단축 URL 상세</h1>
        <div>
            <a href="shorturl_list.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> 목록으로</a>
            <a href="shorturl_edit.php?id=<?php echo $url_id; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> 수정</a>
            <a href="shorturl_copy.php?id=<?php echo $url_id; ?>" class="btn btn-info btn-sm"><i class="fas fa-copy"></i> 복제</a>
        </div>
    </div>

    <!-- URL 정보 카드 -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">URL 정보</h6>
                    <span class="badge <?php echo $is_expired ? 'badge-secondary' : 'badge-success'; ?>">
                        <?php echo $is_expired ? '만료됨' : '활성'; ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">단축 URL</label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?php echo $short_url; ?>" readonly>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" id="btnCopyUrl" data-url="<?php echo $short_url; ?>">
                                        <i class="fas fa-copy"></i> 복사
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">원본 URL</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" rows="2" readonly><?php echo $url_info['original_url']; ?></textarea>
                            <small class="form-text text-muted">
                                <a href="<?php echo $url_info['original_url']; ?>" target="_blank">
                                    <i class="fas fa-external-link-alt"></i> 원본 페이지 열기
                                </a>
                            </small>
                        </div>
                    </div>
                    
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">연결된 캠페인</label>
                        <div class="col-sm-9">
                            <?php if ($url_info['campaign_id'] > 0 && !empty($url_info['campaign_name'])) { ?>
                            <p class="form-control-plaintext">
                                <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_view.php?id=<?php echo $url_info['campaign_id']; ?>">
                                    <?php echo $url_info['campaign_name']; ?>
                                </a>
                            </p>
                            <?php } else { ?>
                            <p class="form-control-plaintext">-</p>
                            <?php } ?>
                        </div>
                    </div>
                    
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">연결된 랜딩페이지</label>
                        <div class="col-sm-9">
                            <?php if ($url_info['landing_id'] > 0 && !empty($url_info['landing_name'])) { ?>
                            <p class="form-control-plaintext">
                                <a href="<?php echo CF_LANDING_URL; ?>/landing_page_view.php?id=<?php echo $url_info['landing_id']; ?>">
                                    <?php echo $url_info['landing_name']; ?>
                                </a>
                            </p>
                            <?php } else { ?>
                            <p class="form-control-plaintext">-</p>
                            <?php } ?>
                        </div>
                    </div>
                    
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">소재 정보</label>
                        <div class="col-sm-9">
                            <?php if (!empty($url_info['source_type']) || !empty($url_info['source_name'])) { ?>
                            <p class="form-control-plaintext">
                                <?php echo !empty($url_info['source_type']) ? '<strong>유형: </strong>' . $url_info['source_type'] : ''; ?>
                                <?php echo !empty($url_info['source_type']) && !empty($url_info['source_name']) ? ' | ' : ''; ?>
                                <?php echo !empty($url_info['source_name']) ? '<strong>이름: </strong>' . $url_info['source_name'] : ''; ?>
                            </p>
                            <?php } else { ?>
                            <p class="form-control-plaintext">-</p>
                            <?php } ?>
                        </div>
                    </div>
                    
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">생성일 / 만료일</label>
                        <div class="col-sm-9">
                            <p class="form-control-plaintext">
                                <?php echo date('Y-m-d H:i', strtotime($url_info['created_at'])); ?>
                                <?php echo !empty($url_info['expires_at']) ? ' / ' . date('Y-m-d', strtotime($url_info['expires_at'])) : ' / 무기한'; ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if (!empty($url_info['qr_code_url'])) { ?>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">QR 코드</label>
                        <div class="col-sm-9">
                            <a href="<?php echo $url_info['qr_code_url']; ?>" download="qrcode.png" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-qrcode"></i> QR 코드 다운로드
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-secondary ml-2" id="btnShowQr" data-url="<?php echo $url_info['qr_code_url']; ?>">
                                <i class="fas fa-eye"></i> QR 코드 보기
                            </a>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">성과 요약</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                총 클릭수
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo number_format($url_info['click_count']); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-mouse-pointer fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                전환수
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo isset($url_info['conversion_count']) ? number_format($url_info['conversion_count']) : '0'; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                전환율
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php 
                                                    $conversion_rate = 0;
                                                    if (isset($url_info['conversion_count']) && $url_info['click_count'] > 0) {
                                                        $conversion_rate = round(($url_info['conversion_count'] / $url_info['click_count']) * 100, 2);
                                                    }
                                                    echo $conversion_rate . '%'; 
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-percent fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                최근 클릭
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php
                                                    $days_since_click = 'N/A';
                                                    if ($url_info['click_count'] > 0) {
                                                        // 최근 클릭 시간 구하기
                                                        $sql = "SELECT click_time FROM {$cf_table_prefix}url_clicks 
                                                                WHERE url_id = '{$url_id}' 
                                                                ORDER BY click_time DESC LIMIT 1";
                                                        $last_click = sql_fetch($sql);
                                                        if ($last_click) {
                                                            $last_click_time = strtotime($last_click['click_time']);
                                                            $now = time();
                                                            $days_since = floor(($now - $last_click_time) / (60 * 60 * 24));
                                                            
                                                            if ($days_since == 0) {
                                                                $days_since_click = '오늘';
                                                            } else {
                                                                $days_since_click = $days_since . '일 전';
                                                            }
                                                        }
                                                    }
                                                    echo $days_since_click;
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 d-flex justify-content-between">
                        <?php if (!$is_expired) { ?>
                            <a href="shorturl_status.php?id=<?php echo $url_id; ?>&status=deactivate" class="btn btn-warning">
                                <i class="fas fa-times-circle"></i> URL 비활성화
                            </a>
                        <?php } else { ?>
                            <a href="shorturl_status.php?id=<?php echo $url_id; ?>&status=activate" class="btn btn-success">
                                <i class="fas fa-check-circle"></i> URL 활성화
                            </a>
                        <?php } ?>
                        <a href="javascript:void(0);" onclick="deleteShorturl(<?php echo $url_id; ?>, '<?php echo addslashes($short_url); ?>')" class="btn btn-danger">
                            <i class="fas fa-trash"></i> URL 삭제
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 기간 선택 필터 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">통계 기간 및 분류 설정</h6>
        </div>
        <div class="card-body">
            <form method="get" action="shorturl_view.php" class="form-inline">
                <input type="hidden" name="id" value="<?php echo $url_id; ?>">
                
                <div class="form-group mr-3">
                    <label for="start_date" class="mr-2">시작일</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                
                <div class="form-group mr-3">
                    <label for="end_date" class="mr-2">종료일</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                
                <div class="form-group mr-3">
                    <label for="group_by" class="mr-2">날짜 분류</label>
                    <select class="form-control" id="group_by" name="group_by">
                        <option value="day" <?php echo $group_by == 'day' ? 'selected' : ''; ?>>일별</option>
                        <option value="week" <?php echo $group_by == 'week' ? 'selected' : ''; ?>>주별</option>
                        <option value="month" <?php echo $group_by == 'month' ? 'selected' : ''; ?>>월별</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">적용</button>
                
                <div class="ml-auto">
                    <a href="<?php echo $_SERVER['REQUEST_URI']; ?>" class="btn btn-info btn-sm" id="btnDownloadReport">
                        <i class="fas fa-download"></i> 보고서 다운로드
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- 클릭 통계 차트 -->
    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">클릭 추이</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="clickChartLine"></canvas>
                    </div>
                    <hr>
                    <div class="text-center small">
                        선택 기간: <?php echo $start_date; ?> ~ <?php echo $end_date; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">기기별 클릭 비율</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4">
                        <canvas id="deviceChartPie"></canvas>
                    </div>
                    <hr>
                    <div class="text-center small">
                        <?php
                            $device_colors = array('desktop' => 'primary', 'mobile' => 'success', 'tablet' => 'info', 'other' => 'warning');
                            foreach ($device_stats as $device) {
                                $color = isset($device_colors[$device['label']]) ? $device_colors[$device['label']] : 'secondary';
                                echo '<span class="mr-2"><i class="fas fa-circle text-' . $color . '"></i> ' . ucfirst($device['label']) . '</span>';
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 브라우저 및 OS 통계 차트 -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">브라우저별 클릭</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="browserChartBar"></canvas>
                    </div>
                    <hr>
                    <div class="text-center small">
                        주요 브라우저 분포
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">OS별 클릭</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="osChartBar"></canvas>
                    </div>
                    <hr>
                    <div class="text-center small">
                        주요 운영체제 분포
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QR 코드 모달 -->
<div class="modal fade" id="qrCodeModal" tabindex="-1" role="dialog" aria-labelledby="qrCodeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrCodeModalLabel">QR 코드</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="qrCodeImage" src="" class="img-fluid" alt="QR 코드">
            </div>
            <div class="modal-footer">
                <a id="downloadQrCode" href="#" download="qrcode.png" class="btn btn-primary">
                    <i class="fas fa-download"></i> 다운로드
                </a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<!-- 단축 URL 삭제 확인 모달 -->
<div class="modal fade" id="deleteShorturlModal" tabindex="-1" role="dialog" aria-labelledby="deleteShorturlModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteShorturlModalLabel">단축 URL 삭제 확인</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p>정말로 이 단축 URL을 삭제하시겠습니까?</p>
                <p><strong id="shorturlToDelete"></strong></p>
                <p class="text-danger">이 작업은 되돌릴 수 없으며, 모든 관련 데이터(클릭 이력 등)도 함께 삭제됩니다.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">취소</button>
                <a class="btn btn-danger" id="confirmDeleteButton" href="#">삭제</a>
            </div>
        </div>
    </div>
</div>

<script>
// 차트 데이터 준비
<?php
// 클릭 차트 데이터
$click_labels = array();
$click_data = array();

foreach ($click_stats as $stat) {
    $click_labels[] = $stat['label'];
    $click_data[] = $stat['value'];
}

// 기기별 차트 데이터
$device_labels = array();
$device_data = array();
$device_bg_colors = array('#4e73df', '#1cc88a', '#36b9cc', '#f6c23e');

foreach ($device_stats as $index => $stat) {
    $device_labels[] = ucfirst($stat['label']);
    $device_data[] = $stat['value'];
}

// 브라우저별 차트 데이터
$browser_labels = array();
$browser_data = array();
$browser_bg_colors = array('#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796');

foreach ($browser_stats as $index => $stat) {
    if ($index < 6) { // 상위 6개만 표시
        $browser_labels[] = $stat['label'];
        $browser_data[] = $stat['value'];
    }
}

// OS별 차트 데이터
$os_labels = array();
$os_data = array();
$os_bg_colors = array('#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b');

foreach ($os_stats as $index => $stat) {
    if ($index < 5) { // 상위 5개만 표시
        $os_labels[] = $stat['label'];
        $os_data[] = $stat['value'];
    }
}
?>

// DOM 로드 완료 후 실행
$(document).ready(function() {
    // URL 복사 버튼 클릭 이벤트
    $('#btnCopyUrl').click(function() {
        const url = $(this).data('url');
        const tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(url).select();
        document.execCommand('copy');
        tempInput.remove();
        
        // 복사 완료 알림
        toastr.success('URL이 클립보드에 복사되었습니다.');
    });
    
    // QR 코드 표시 버튼 클릭 이벤트
    $('#btnShowQr').click(function(e) {
        e.preventDefault();
        const qrUrl = $(this).data('url');
        $('#qrCodeImage').attr('src', qrUrl);
        $('#downloadQrCode').attr('href', qrUrl);
        $('#qrCodeModal').modal('show');
    });

    // 클릭 추이 차트 초기화
    var clickCtx = document.getElementById("clickChartLine");
    var clickChartLine = new Chart(clickCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($click_labels); ?>,
            datasets: [{
                label: "클릭수",
                lineTension: 0.3,
                backgroundColor: "rgba(78, 115, 223, 0.05)",
                borderColor: "rgba(78, 115, 223, 1)",
                pointRadius: 3,
                pointBackgroundColor: "rgba(78, 115, 223, 1)",
                pointBorderColor: "rgba(78, 115, 223, 1)",
                pointHoverRadius: 3,
                pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: <?php echo json_encode($click_data); ?>,
            }],
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                xAxes: [{
                    time: {
                        unit: 'date'
                    },
                    gridLines: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 7
                    }
                }],
                yAxes: [{
                    ticks: {
                        maxTicksLimit: 5,
                        padding: 10,
                        // y축 값은 항상 정수로 표시
                        callback: function(value, index, values) {
                            return Number(value).toLocaleString();
                        }
                    },
                    gridLines: {
                        color: "rgb(234, 236, 244)",
                        zeroLineColor: "rgb(234, 236, 244)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    }
                }],
            },
            legend: {
                display: false
            },
            tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    titleMarginBottom: 10,
                    titleFontColor: '#6e707e',
                    titleFontSize: 14,
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    intersect: false,
                    mode: 'index',
                    caretPadding: 10,
                    callbacks: {
                        label: function(tooltipItem, chart) {
                            var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                            return datasetLabel + ': ' + Number(tooltipItem.yLabel).toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // 기기별 클릭 비율 차트 초기화
    var deviceCtx = document.getElementById("deviceChartPie");
    var deviceChartPie = new Chart(deviceCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($device_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($device_data); ?>,
                backgroundColor: <?php echo json_encode($device_bg_colors); ?>,
                hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#f1c034'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                caretPadding: 10,
            },
            legend: {
                display: true,
                position: 'bottom'
            },
            cutoutPercentage: 70,
        },
    });

    // 브라우저별 클릭 차트 초기화
    var browserCtx = document.getElementById("browserChartBar");
    var browserChartBar = new Chart(browserCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($browser_labels); ?>,
            datasets: [{
                label: "클릭",
                backgroundColor: <?php echo json_encode($browser_bg_colors); ?>,
                hoverBackgroundColor: ["#2e59d9", "#17a673", "#2c9faf", "#f1c034", "#e53a2d", "#757083"],
                borderColor: "#4e73df",
                data: <?php echo json_encode($browser_data); ?>,
            }],
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                xAxes: [{
                    gridLines: {
                        display: false,
                        drawBorder: false
                    },
                    maxBarThickness: 25,
                }],
                yAxes: [{
                    ticks: {
                        min: 0,
                        maxTicksLimit: 5,
                        padding: 10,
                        callback: function(value, index, values) {
                            return Number(value).toLocaleString();
                        }
                    },
                    gridLines: {
                        color: "rgb(234, 236, 244)",
                        zeroLineColor: "rgb(234, 236, 244)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    }
                }],
            },
            legend: {
                display: false
            },
            tooltips: {
                titleMarginBottom: 10,
                titleFontColor: '#6e707e',
                titleFontSize: 14,
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                caretPadding: 10,
                callbacks: {
                    label: function(tooltipItem, chart) {
                        var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                        return datasetLabel + ': ' + Number(tooltipItem.yLabel).toLocaleString();
                    }
                }
            },
        }
    });

    // OS별 클릭 차트 초기화
    var osCtx = document.getElementById("osChartBar");
    var osChartBar = new Chart(osCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($os_labels); ?>,
            datasets: [{
                label: "클릭",
                backgroundColor: <?php echo json_encode($os_bg_colors); ?>,
                hoverBackgroundColor: ["#2e59d9", "#17a673", "#2c9faf", "#f1c034", "#e53a2d"],
                borderColor: "#4e73df",
                data: <?php echo json_encode($os_data); ?>,
            }],
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                xAxes: [{
                    gridLines: {
                        display: false,
                        drawBorder: false
                    },
                    maxBarThickness: 25,
                }],
                yAxes: [{
                    ticks: {
                        min: 0,
                        maxTicksLimit: 5,
                        padding: 10,
                        callback: function(value, index, values) {
                            return Number(value).toLocaleString();
                        }
                    },
                    gridLines: {
                        color: "rgb(234, 236, 244)",
                        zeroLineColor: "rgb(234, 236, 244)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    }
                }],
            },
            legend: {
                display: false
            },
            tooltips: {
                titleMarginBottom: 10,
                titleFontColor: '#6e707e',
                titleFontSize: 14,
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                caretPadding: 10,
                callbacks: {
                    label: function(tooltipItem, chart) {
                        var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                        return datasetLabel + ': ' + Number(tooltipItem.yLabel).toLocaleString();
                    }
                }
            },
        }
    });

    // 보고서 다운로드 버튼 이벤트
    $('#btnDownloadReport').click(function(e) {
        e.preventDefault();
        toastr.info('보고서를 준비하고 있습니다...');
        
        // AJAX로 보고서 다운로드 요청
        window.location.href = 'shorturl_report.php?id=<?php echo $url_id; ?>&start_date=' + $('#start_date').val() + '&end_date=' + $('#end_date').val();
    });
});

// 단축 URL 삭제 확인 함수
function deleteShorturl(id, url) {
    $('#shorturlToDelete').text(url);
    $('#confirmDeleteButton').attr('href', 'shorturl_delete.php?id=' + id);
    $('#deleteShorturlModal').modal('show');
}
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>