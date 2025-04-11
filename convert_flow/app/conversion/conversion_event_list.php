<?php
/**
 * 전환 이벤트 목록 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 페이지 제목
$page_title = "전환 이벤트 목록";

// 모델 로드
require_once CF_MODEL_PATH . '/campaign.model.php';
require_once CF_MODEL_PATH . '/conversion.model.php';
$campaign_model = new CampaignModel();
$conversion_model = new ConversionModel();

// 파라미터 검증
$campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
$script_id = isset($_GET['script_id']) ? intval($_GET['script_id']) : 0;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20; // 페이지당 표시할 항목 수
$offset = ($page - 1) * $limit;

// 캠페인 또는 스크립트 정보 조회
$campaign = null;
$script = null;
$page_subtitle = "";

if ($script_id > 0) {
    // 특정 스크립트의 이벤트 조회
    $script = $conversion_model->get_conversion_script($script_id);
    if (!$script) {
        alert('존재하지 않는 전환 스크립트입니다.', 'campaign_list.php');
        exit;
    }
    
    $campaign_id = $script['campaign_id'];
    $campaign = $campaign_model->get_campaign($campaign_id);
    $page_subtitle = "스크립트: " . $script['conversion_type'];
} elseif ($campaign_id > 0) {
    // 특정 캠페인의 모든 이벤트 조회
    $campaign = $campaign_model->get_campaign($campaign_id);
    $page_subtitle = "캠페인: " . $campaign['name'];
} else {
    // 모든 이벤트 조회 (관리자만 가능)
    if ($is_admin !== "super") {
        alert('접근 권한이 없습니다.', 'campaign_list.php');
        exit;
    }
}

// 캠페인이 존재하지 않거나 접근 권한이 없는 경우
if ($campaign_id > 0 && (!$campaign || ($is_admin !== "super" && $campaign['user_id'] != $member['id']))) {
    alert('존재하지 않거나 접근 권한이 없는 캠페인입니다.', 'campaign_list.php');
    exit;
}

// 필터링 조건
$search_type = isset($_GET['search_type']) ? trim($_GET['search_type']) : '';
$search_keyword = isset($_GET['search_keyword']) ? trim($_GET['search_keyword']) : '';
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
} elseif ($is_admin !== "super") {
    // 관리자가 아닌 경우, 자신의 캠페인만 볼 수 있도록 제한
    $where_clause .= " AND c.user_id = '{$member['id']}'";
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

// 검색 필터링
if ($search_keyword) {
    switch ($search_type) {
        case 'ip':
            $where_clause .= " AND ce.ip_address LIKE '%$search_keyword%'";
            break;
        case 'utm_campaign':
            $where_clause .= " AND ce.utm_campaign LIKE '%$search_keyword%'";
            break;
        case 'user_agent':
            $where_clause .= " AND ce.user_agent LIKE '%$search_keyword%'";
            break;
    }
}

// 총 이벤트 수 조회
$sql = "SELECT COUNT(*) as cnt 
        FROM {$cf_table_prefix}conversion_events ce
        LEFT JOIN {$cf_table_prefix}conversion_scripts cs ON ce.script_id = cs.id
        LEFT JOIN {$cf_table_prefix}campaigns c ON ce.campaign_id = c.id
        $where_clause";
$cnt_result = sql_query($sql);
$row = sql_fetch_array($cnt_result);
$total_count = $row['cnt'];
$total_page = ceil($total_count / $limit);

// 이벤트 데이터 조회
$sql = "SELECT ce.*, cs.conversion_type, c.name as campaign_name 
        FROM {$cf_table_prefix}conversion_events ce
        LEFT JOIN {$cf_table_prefix}conversion_scripts cs ON ce.script_id = cs.id
        LEFT JOIN {$cf_table_prefix}campaigns c ON ce.campaign_id = c.id
        $where_clause
        ORDER BY ce.conversion_time DESC
        LIMIT $offset, $limit";
$result = sql_query($sql);

// 소스 목록 조회 (필터용)
$sql = "SELECT DISTINCT source FROM {$cf_table_prefix}conversion_events 
        WHERE campaign_id = '$campaign_id' AND source IS NOT NULL AND source != ''
        ORDER BY source";
$source_result = sql_query($sql);

// 소스별 통계 데이터 조회
$sql = "SELECT 
            IFNULL(ce.source, 'direct') as source, 
            COUNT(*) as event_count,
            SUM(CASE WHEN ce.conversion_value > 0 THEN 1 ELSE 0 END) as conversion_count,
            SUM(ce.conversion_value) as total_value
        FROM {$cf_table_prefix}conversion_events ce
        WHERE ce.campaign_id = '$campaign_id'
        GROUP BY IFNULL(ce.source, 'direct')
        ORDER BY event_count DESC";
$source_stats_result = sql_query($sql);

// 헤더 포함
include_once CF_PATH .'/header.php';
?>

<div class="container-fluid">
    <!-- 페이지 제목 -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            전환 이벤트 목록
            <?php if ($page_subtitle) { ?>
                <small class="text-muted"> - <?php echo $page_subtitle; ?></small>
            <?php } ?>
        </h1>
        <div>
            <?php if ($campaign_id) { ?>
                <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_view.php?id=<?php echo $campaign_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50"></i> 캠페인으로 돌아가기
                </a>
            <?php } else { ?>
                <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_list.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                    <i class="fas fa-list fa-sm text-white-50"></i> 캠페인 목록
                </a>
            <?php } ?>
        </div>
    </div>

    <!-- 통계 요약 -->
    <?php if ($campaign_id) { ?>
    <div class="row">
        <?php
        $total_events = 0;
        $total_conversions = 0;
        $total_value = 0;
        $sources = array();

        while ($row = sql_fetch_array($source_stats_result)) {
            $total_events += $row['event_count'];
            $total_conversions += $row['conversion_count'];
            $total_value += $row['total_value'];
            $sources[] = $row;
        }
        
        // 전환율 계산
        $conversion_rate = $total_events > 0 ? round(($total_conversions / $total_events) * 100, 2) : 0;
        
        // CPA 계산
        $cpa = $total_conversions > 0 ? round($total_value / $total_conversions, 2) : 0;
        
        // 데이터 재설정
        sql_data_seek($source_stats_result, 0);
        ?>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">총 이벤트</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_events); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_conversions); ?></div>
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_value); ?>원</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-won-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>

    <!-- 필터 폼 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">검색 필터</h6>
        </div>
        <div class="card-body">
            <form method="get" action="" class="form-inline">
                <?php if ($campaign_id) { ?>
                    <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">
                <?php } ?>
                <?php if ($script_id) { ?>
                    <input type="hidden" name="script_id" value="<?php echo $script_id; ?>">
                <?php } ?>
                
                <div class="form-group mr-2 mb-2">
                    <label for="date_start" class="mr-2">기간</label>
                    <input type="date" class="form-control" id="date_start" name="date_start" value="<?php echo $date_start; ?>">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="date_end" class="mr-2">~</label>
                    <input type="date" class="form-control" id="date_end" name="date_end" value="<?php echo $date_end; ?>">
                </div>
                
                <?php if ($campaign_id) { ?>
                <div class="form-group mr-2 mb-2">
                    <label for="source" class="mr-2">소스</label>
                    <select class="form-control" id="source" name="source">
                        <option value="">전체</option>
                        <option value="direct" <?php echo $source_filter === 'direct' ? 'selected' : ''; ?>>직접 방문</option>
                        <?php while ($row = sql_fetch_array($source_result)) { ?>
                            <option value="<?php echo $row['source']; ?>" <?php echo $source_filter === $row['source'] ? 'selected' : ''; ?>>
                                <?php echo $row['source']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <?php } ?>
                
                <div class="form-group mr-2 mb-2">
                    <label for="device" class="mr-2">디바이스</label>
                    <select class="form-control" id="device" name="device">
                        <option value="">전체</option>
                        <option value="desktop" <?php echo $device_filter === 'desktop' ? 'selected' : ''; ?>>데스크톱</option>
                        <option value="mobile" <?php echo $device_filter === 'mobile' ? 'selected' : ''; ?>>모바일</option>
                        <option value="tablet" <?php echo $device_filter === 'tablet' ? 'selected' : ''; ?>>태블릿</option>
                        <option value="other" <?php echo $device_filter === 'other' ? 'selected' : ''; ?>>기타</option>
                    </select>
                </div>
                
                <div class="form-group mr-2 mb-2">
                    <label for="search_type" class="mr-2">검색어</label>
                    <select class="form-control" id="search_type" name="search_type">
                        <option value="ip" <?php echo $search_type === 'ip' ? 'selected' : ''; ?>>IP 주소</option>
                        <option value="utm_campaign" <?php echo $search_type === 'utm_campaign' ? 'selected' : ''; ?>>UTM 캠페인</option>
                        <option value="user_agent" <?php echo $search_type === 'user_agent' ? 'selected' : ''; ?>>사용자 에이전트</option>
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <input type="text" class="form-control" id="search_keyword" name="search_keyword" value="<?php echo $search_keyword; ?>" placeholder="검색어 입력">
                </div>
                
                <button type="submit" class="btn btn-primary mb-2">검색</button>
                <a href="<?php echo $_SERVER['SCRIPT_NAME'] . ($campaign_id ? '?campaign_id=' . $campaign_id : '') . ($script_id ? '&script_id=' . $script_id : ''); ?>" class="btn btn-secondary mb-2 ml-2">초기화</a>
            </form>
        </div>
    </div>

    <?php if ($campaign_id && count($sources) > 0) { ?>
    <!-- 소스별 통계 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">소스별 통계</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>소스</th>
                            <th>이벤트 수</th>
                            <th>전환 수</th>
                            <th>전환율</th>
                            <th>전환 가치</th>
                            <th>평균 전환 가치</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sources as $source) { 
                            $source_conversion_rate = $source['event_count'] > 0 ? round(($source['conversion_count'] / $source['event_count']) * 100, 2) : 0;
                            $source_avg_value = $source['conversion_count'] > 0 ? round($source['total_value'] / $source['conversion_count'], 2) : 0;
                        ?>
                        <tr>
                            <td><?php echo $source['source'] === 'direct' ? '직접 방문' : $source['source']; ?></td>
                            <td><?php echo number_format($source['event_count']); ?></td>
                            <td><?php echo number_format($source['conversion_count']); ?></td>
                            <td><?php echo $source_conversion_rate; ?>%</td>
                            <td><?php echo number_format($source['total_value']); ?>원</td>
                            <td><?php echo number_format($source_avg_value); ?>원</td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php } ?>

    <!-- 전환 이벤트 목록 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">전환 이벤트 목록 (총 <?php echo number_format($total_count); ?>개)</h6>
            <?php if ($campaign_id) { ?>
            <a href="conversion_event_export.php?campaign_id=<?php echo $campaign_id; ?>" class="btn btn-sm btn-success">
                <i class="fas fa-file-excel fa-sm text-white-50"></i> 엑셀 내보내기
            </a>
            <?php } ?>
        </div>
        <div class="card-body">
            <?php if (sql_num_rows($result) > 0) { ?>
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <?php if (!$campaign_id) { ?>
                            <th>캠페인</th>
                            <?php } ?>
                            <?php if (!$script_id) { ?>
                            <th>전환 유형</th>
                            <?php } ?>
                            <th>전환 시간</th>
                            <th>소스</th>
                            <th>매체</th>
                            <th>캠페인</th>
                            <th>디바이스</th>
                            <th>전환 가치</th>
                            <th>IP 주소</th>
                            <th>상세</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = sql_fetch_array($result)) { ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <?php if (!$campaign_id) { ?>
                            <td>
                                <a href="<?php echo CF_ADMIN_URL; ?>/campaign/campaign_view.php?id=<?php echo $row['campaign_id']; ?>">
                                    <?php echo $row['campaign_name']; ?>
                                </a>
                            </td>
                            <?php } ?>
                            <?php if (!$script_id) { ?>
                            <td><?php echo $row['conversion_type']; ?></td>
                            <?php } ?>
                            <td><?php echo $row['conversion_time']; ?></td>
                            <td><?php echo $row['source'] ? $row['source'] : '직접 방문'; ?></td>
                            <td><?php echo $row['medium'] ? $row['medium'] : '-'; ?></td>
                            <td><?php echo $row['utm_campaign'] ? $row['utm_campaign'] : '-'; ?></td>
                            <td><?php echo $row['device_type'] ? $row['device_type'] : '알 수 없음'; ?></td>
                            <td><?php echo number_format($row['conversion_value']); ?>원</td>
                            <td><?php echo $row['ip_address']; ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info view-event-details" data-id="<?php echo $row['id']; ?>">
                                    상세
                                </button>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            <div class="mt-4 d-flex justify-content-center">
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($page > 1) { ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo str_replace('&page=' . $page, '', $_SERVER['QUERY_STRING']); ?>&page=1" aria-label="First">
                                    <span aria-hidden="true">&laquo;&laquo;</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo str_replace('&page=' . $page, '', $_SERVER['QUERY_STRING']); ?>&page=<?php echo $page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php } ?>
                        
                        <?php
                        $start_page = max(1, $page - 5);
                        $end_page = min($total_page, $page + 5);
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $active = $i == $page ? 'active' : '';
                            echo '<li class="page-item ' . $active . '"><a class="page-link" href="?' . str_replace('&page=' . $page, '', $_SERVER['QUERY_STRING']) . '&page=' . $i . '">' . $i . '</a></li>';
                        }
                        ?>
                        
                        <?php if ($page < $total_page) { ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo str_replace('&page=' . $page, '', $_SERVER['QUERY_STRING']); ?>&page=<?php echo $page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo str_replace('&page=' . $page, '', $_SERVER['QUERY_STRING']); ?>&page=<?php echo $total_page; ?>" aria-label="Last">
                                    <span aria-hidden="true">&raquo;&raquo;</span>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </nav>
            </div>
            <?php } else { ?>
            <div class="text-center py-4">
                <p class="lead text-gray-800">조회된 전환 이벤트가 없습니다.</p>
            </div>
            <?php } ?>
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
                                    <th>IP 주소</th><td id="event-ip"></td>
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

<script>
$(document).ready(function() {
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
            s[0] = s[0].replace(/\\B(?=(?:\\d{3})+(?!\\d))/g, sep);
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
include_once CF_PATH .'/footer.php';
?>