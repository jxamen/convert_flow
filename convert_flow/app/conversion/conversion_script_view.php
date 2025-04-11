<?php
/**
 * 전환 스크립트 상세 보기 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 페이지 제목
$page_title = "전환 스크립트 상세";

// 모델 로드
require_once CF_MODEL_PATH . '/campaign.model.php';
require_once CF_MODEL_PATH . '/conversion.model.php';
$campaign_model = new CampaignModel();
$conversion_model = new ConversionModel();

// 스크립트 ID 검증
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    alert('올바른 접근이 아닙니다.', 'campaign_list.php');
    exit;
}

$script_id = intval($_GET['id']);
$script = $conversion_model->get_conversion_script($script_id);

// 스크립트가 존재하지 않는 경우
if (!$script) {
    alert('존재하지 않는 전환 스크립트입니다.', 'campaign_list.php');
    exit;
}

// 캠페인 정보 조회
$campaign_id = $script['campaign_id'];
$campaign = $campaign_model->get_campaign($campaign_id);

// 캠페인이 존재하지 않거나 현재 사용자의 것이 아닌 경우
if (!$campaign || ($is_admin !== "super" && $campaign['user_id'] != $member['id'])) {
    alert('존재하지 않거나 접근 권한이 없는 캠페인입니다.', 'campaign_list.php');
    exit;
}

// 스크립트 성과 지표 조회
$sql = "SELECT 
            COUNT(*) as event_count,
            SUM(CASE WHEN conversion_value > 0 THEN 1 ELSE 0 END) as conversion_count,
            SUM(conversion_value) as total_value
        FROM {$cf_table_prefix}conversion_events
        WHERE script_id = '$script_id'";
$stats = sql_fetch($sql);

// 전환율 계산
$conversion_rate = $stats['event_count'] > 0 ? round(($stats['conversion_count'] / $stats['event_count']) * 100, 2) : 0;

// 소스별 통계 데이터 조회
$sql = "SELECT 
            IFNULL(source, 'direct') as source, 
            COUNT(*) as event_count,
            SUM(CASE WHEN conversion_value > 0 THEN 1 ELSE 0 END) as conversion_count,
            SUM(conversion_value) as total_value
        FROM {$cf_table_prefix}conversion_events
        WHERE script_id = '$script_id'
        GROUP BY IFNULL(source, 'direct')
        ORDER BY event_count DESC";
$source_stats_result = sql_query($sql);

// 최근 전환 이벤트 조회
$sql = "SELECT * 
        FROM {$cf_table_prefix}conversion_events
        WHERE script_id = '$script_id'
        ORDER BY conversion_time DESC
        LIMIT 10";
$events_result = sql_query($sql);

// 알림 메시지 처리
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$msg_type = isset($_GET['msg_type']) ? $_GET['msg_type'] : 'info';

// 헤더 포함
include_once CF_PATH .'/header.php';
?>

<div class="container-fluid">
    <!-- 페이지 제목 -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $script['conversion_type']; ?> 전환 스크립트</h1>
        <div>
            <a href="<?php echo CF_CONVERSION_URL; ?>/conversion_script_edit.php?id=<?php echo $script_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-edit fa-sm text-white-50"></i> 스크립트 수정
            </a>
            <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_view.php?id=<?php echo $campaign_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm ml-2">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> 캠페인으로 돌아가기
            </a>
        </div>
    </div>

    <!-- 알림 메시지 표시 -->
    <?php if (!empty($msg)) { ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php } ?>

    <!-- 스크립트 성과 요약 -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">페이지뷰</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['event_count']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-eye fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">전환</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['conversion_count']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">전환율</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $conversion_rate; ?>%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo min($conversion_rate, 100); ?>%" aria-valuenow="<?php echo $conversion_rate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">전환 가치</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['total_value']); ?>원</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-won-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 스크립트 정보 및 코드 -->
    <div class="row">
        <div class="col-lg-5">
            <!-- 스크립트 정보 -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">스크립트 정보</h6>
                </div>
                <div class="card-body">
                    <p><strong>캠페인:</strong> <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_view.php?id=<?php echo $campaign_id; ?>"><?php echo $campaign['name']; ?></a></p>
                    <p><strong>전환 유형:</strong> <?php echo $script['conversion_type']; ?></p>
                    <p><strong>생성일:</strong> <?php echo $script['created_at']; ?></p>
                    <p><strong>마지막 수정일:</strong> <?php echo $script['updated_at']; ?></p>
                    
                    <div class="mt-4">
                        <h6 class="font-weight-bold">설치 안내</h6>
                        <div class="card bg-light p-3">
                            <?php echo nl2br($script['installation_guide']); ?>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="<?php echo CF_CONVERSION_URL; ?>/conversion_event_list.php?script_id=<?php echo $script_id; ?>" class="btn btn-primary">
                            <i class="fas fa-list"></i> 모든 이벤트 보기
                        </a>
                        <a href="<?php echo CF_CONVERSION_URL; ?>/conversion_event_export.php?script_id=<?php echo $script_id; ?>" class="btn btn-success ml-2">
                            <i class="fas fa-file-excel"></i> 데이터 내보내기
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- 소스별 통계 -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">소스별 통계</h6>
                </div>
                <div class="card-body">
                    <?php if (sql_num_rows($source_stats_result) > 0) { ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>소스</th>
                                    <th>이벤트</th>
                                    <th>전환</th>
                                    <th>전환율</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                while ($row = sql_fetch_array($source_stats_result)) {
                                    $src_conversion_rate = $row['event_count'] > 0 ? round(($row['conversion_count'] / $row['event_count']) * 100, 2) : 0;
                                    echo '<tr>';
                                    echo '<td>' . ($row['source'] === 'direct' ? '직접 방문' : $row['source']) . '</td>';
                                    echo '<td>' . number_format($row['event_count']) . '</td>';
                                    echo '<td>' . number_format($row['conversion_count']) . '</td>';
                                    echo '<td>' . $src_conversion_rate . '%</td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <?php } else { ?>
                    <div class="text-center py-4">
                        <p class="text-muted">소스 데이터가 없습니다.</p>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-7">
            <!-- 스크립트 코드 -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">JavaScript 코드</h6>
                    <button class="btn btn-sm btn-info" id="copy-script">
                        <i class="fas fa-copy"></i> 코드 복사
                    </button>
                </div>
                <div class="card-body">
                    <div class="bg-light p-3 rounded">
                        <pre class="mb-0"><code id="script-code" class="language-javascript"><?php echo htmlspecialchars($script['script_code']); ?></code></pre>
                    </div>
                </div>
            </div>
            
            <!-- 최근 전환 이벤트 -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">최근 전환 이벤트</h6>
                </div>
                <div class="card-body">
                    <?php if (sql_num_rows($events_result) > 0) { ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>시간</th>
                                    <th>소스</th>
                                    <th>디바이스</th>
                                    <th>전환 가치</th>
                                    <th>상세</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($event = sql_fetch_array($events_result)) { ?>
                                <tr>
                                    <td><?php echo $event['conversion_time']; ?></td>
                                    <td><?php echo $event['source'] ? $event['source'] : '직접 방문'; ?></td>
                                    <td><?php echo $event['device_type'] ? $event['device_type'] : '알 수 없음'; ?></td>
                                    <td><?php echo number_format($event['conversion_value']); ?>원</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info view-event-details" data-id="<?php echo $event['id']; ?>">
                                            상세
                                        </button>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-right mt-2">
                        <a href="<?php echo CF_CONVERSION_URL; ?>/conversion_event_list.php?script_id=<?php echo $script_id; ?>" class="btn btn-sm btn-primary">모든 이벤트 보기</a>
                    </div>
                    <?php } else { ?>
                    <div class="text-center py-4">
                        <p class="lead text-gray-800">아직 전환 이벤트가 없습니다.</p>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 이벤트 상세 정보 모달 -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1" role="dialog" aria-labelledby="eventDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventDetailsModalLabel">전환 이벤트 상세 정보</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <div id="eventDetailsContent" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">기본 정보</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>이벤트 ID</th>
                                    <td id="event-id"></td>
                                </tr>
                                <tr>
                                    <th>전환 시간</th>
                                    <td id="event-time"></td>
                                </tr>
                                <tr>
                                    <th>전환 유형</th>
                                    <td id="event-type"></td>
                                </tr>
                                <tr>
                                    <th>전환 가치</th>
                                    <td id="event-value"></td>
                                </tr>
                            </table>
                            
                            <h6 class="font-weight-bold mt-3">트래픽 소스</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>소스</th>
                                    <td id="event-source"></td>
                                </tr>
                                <tr>
                                    <th>매체</th>
                                    <td id="event-medium"></td>
                                </tr>
                                <tr>
                                    <th>캠페인</th>
                                    <td id="event-campaign"></td>
                                </tr>
                                <tr>
                                    <th>콘텐츠</th>
                                    <td id="event-content"></td>
                                </tr>
                                <tr>
                                    <th>키워드</th>
                                    <td id="event-term"></td>
                                </tr>
                                <tr>
                                    <th>참조 URL</th>
                                    <td id="event-referrer"></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">방문자 정보</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>IP 주소</th>
                                    <td id="event-ip"></td>
                                </tr>
                                <tr>
                                    <th>국가</th>
                                    <td id="event-country"></td>
                                </tr>
                                <tr>
                                    <th>지역</th>
                                    <td id="event-region"></td>
                                </tr>
                                <tr>
                                    <th>도시</th>
                                    <td id="event-city"></td>
                                </tr>
                                <tr>
                                    <th>디바이스 유형</th>
                                    <td id="event-device"></td>
                                </tr>
                                <tr>
                                    <th>브라우저</th>
                                    <td id="event-browser"></td>
                                </tr>
                                <tr>
                                    <th>운영체제</th>
                                    <td id="event-os"></td>
                                </tr>
                            </table>
                            
                            <h6 class="font-weight-bold mt-3">추가 데이터</h6>
                            <div id="event-additional-data" class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                                <pre></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo CF_ADMIN_URL; ?>/assets/vendor/prismjs/prism.js"></script>
<script>
$(document).ready(function() {
    // 코드 복사 기능
    $('#copy-script').click(function() {
        var codeElement = document.getElementById('script-code');
        var range = document.createRange();
        range.selectNode(codeElement);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
        document.execCommand('copy');
        window.getSelection().removeAllRanges();
        
        $(this).html('<i class="fas fa-check"></i> 복사됨');
        setTimeout(function() {
            $('#copy-script').html('<i class="fas fa-copy"></i> 코드 복사');
        }, 2000);
    });
    
    // 이벤트 상세 정보 조회
    $('.view-event-details').on('click', function() {
        var eventId = $(this).data('id');
        $('#eventDetailsContent').hide();
        $('.spinner-border').show();
        $('#eventDetailsModal').modal('show');
        
        $.ajax({
            url: '<?php echo CF_ADMIN_URL; ?>/conversion/get_event_details.php',
            type: 'GET',
            data: { id: eventId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    var event = response.data;
                    
                    $('#event-id').text(event.id);
                    $('#event-time').text(event.conversion_time);
                    $('#event-type').text(event.conversion_type);
                    $('#event-value').text(number_format(event.conversion_value) + '원');
                    
                    $('#event-source').text(event.source || '직접 방문');
                    $('#event-medium').text(event.medium || '-');
                    $('#event-campaign').text(event.utm_campaign || '-');
                    $('#event-content').text(event.utm_content || '-');
                    $('#event-term').text(event.utm_term || '-');
                    $('#event-referrer').text(event.referrer || '-');
                    
                    $('#event-ip').text(event.ip_address || '-');
                    $('#event-country').text(event.country || '-');
                    $('#event-region').text(event.region || '-');
                    $('#event-city').text(event.city || '-');
                    $('#event-device').text(event.device_type || '알 수 없음');
                    $('#event-browser').text(event.browser || '-');
                    $('#event-os').text(event.os || '-');
                    
                    // 추가 데이터가 있는 경우
                    if (event.additional_data) {
                        try {
                            var jsonData = JSON.parse(event.additional_data);
                            $('#event-additional-data pre').text(JSON.stringify(jsonData, null, 2));
                        } catch (e) {
                            $('#event-additional-data pre').text(event.additional_data);
                        }
                        $('#event-additional-data').show();
                    } else {
                        $('#event-additional-data pre').text('추가 데이터가 없습니다.');
                        $('#event-additional-data').show();
                    }
                    
                    $('.spinner-border').hide();
                    $('#eventDetailsContent').show();
                } else {
                    alert('이벤트 정보를 불러오는데 실패했습니다.');
                    $('#eventDetailsModal').modal('hide');
                }
            },
            error: function() {
                alert('서버 오류가 발생했습니다.');
                $('#eventDetailsModal').modal('hide');
            }
        });
    });
    
    // 숫자 포맷팅 함수
    function number_format(number, decimals, dec_point, thousands_sep) {
        number = (number + '').replace(',', '').replace(' ', '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function(n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    }
});
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>