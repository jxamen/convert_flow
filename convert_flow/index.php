<?php
include_once './include/_common.php';


// 페이지 제목
$page_title = "대시보드";

if ($is_admin !== "super") {
    $search_mb_id1 = " AND user_id = '{$member['id']}'";
    $search_mb_id2 = " AND c.user_id = '{$member['id']}'";
}

// 헤더 포함
include_once './header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1 class="h3 mb-4 text-gray-800">대시보드</h1>
        </div>
    </div>
    
    <!-- 통계 카드 -->
    <div class="row">
        <!-- 전체 캠페인 수 -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">전체 캠페인</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $sql = "SELECT COUNT(*) as cnt FROM {$cf_table_prefix}campaigns WHERE (1) {$search_mb_id1}";
                                $row = sql_fetch($sql);
                                echo $row['cnt'];
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-flag fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 활성 캠페인 수 -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">활성 캠페인</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $sql = "SELECT COUNT(*) as cnt FROM {$cf_table_prefix}campaigns 
                                        WHERE status = '활성'
                                        AND (end_date IS NULL OR end_date >= CURDATE())
                                        {$search_mb_id1}";
                                $row = sql_fetch($sql);
                                echo $row['cnt'];
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 전체 전환 수 -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">전체 전환</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $sql = "SELECT COUNT(*) as cnt FROM {$cf_table_prefix}conversion_events ce
                                        JOIN {$cf_table_prefix}conversion_scripts cs ON ce.script_id = cs.id
                                        JOIN {$cf_table_prefix}campaigns c ON cs.campaign_id = c.id
                                        WHERE ce.conversion_value > 0 
                                        {$search_mb_id2}";
                                $row = sql_fetch($sql);
                                echo $row['cnt'];
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 전체 리드 수 -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">전체 리드</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $sql = "SELECT COUNT(*) as cnt FROM {$cf_table_prefix}leads l
                                        JOIN {$cf_table_prefix}forms c ON l.form_id = c.id
                                        WHERE (1) {$search_mb_id2}";
                                $row = sql_fetch($sql);
                                echo $row['cnt'];
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 차트 영역 -->
    <div class="row">
        <!-- 지난 30일 전환 추이 -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">지난 30일 전환 추이</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="conversionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- 소스별 전환 -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">소스별 전환</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="sourceChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <?php
                        $sql = "SELECT 
                                    IFNULL(ce.source, '직접 방문') as source,
                                    COUNT(*) as count
                                FROM {$cf_table_prefix}conversion_events ce
                                JOIN {$cf_table_prefix}conversion_scripts cs ON ce.script_id = cs.id
                                JOIN {$cf_table_prefix}campaigns c ON cs.campaign_id = c.id
                                WHERE (1) {$search_mb_id2}
                                GROUP BY IFNULL(ce.source, '직접 방문')
                                ORDER BY count DESC
                                LIMIT 5";
                        $result = sql_query($sql);
                        $sources = array();
                        while ($row = sql_fetch_array($result)) {
                            $sources[] = $row['source'];
                            echo '<span class="mr-2">
                                    <i class="fas fa-circle" style="color:' . get_random_color($row['source']) . '"></i> ' . $row['source'] . '
                                  </span>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 최근 캠페인 -->
    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">최근 캠페인</h6>
                    <a href="<?php echo CF_CAMPAIGN_URL ?>/campaign_list.php" class="btn btn-sm btn-primary">모든 캠페인 보기</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>캠페인명</th>
                                    <th>상태</th>
                                    <th>시작일</th>
                                    <th>전환</th>
                                    <th>CPA</th>
                                    <th>관리</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT c.*, 
                                        (SELECT COUNT(*) FROM {$cf_table_prefix}conversion_events ce
                                         JOIN {$cf_table_prefix}conversion_scripts cs ON ce.script_id = cs.id
                                         WHERE cs.campaign_id = c.id AND ce.conversion_value > 0) as conversions,
                                        (SELECT SUM(ce.conversion_value) FROM {$cf_table_prefix}conversion_events ce
                                         JOIN {$cf_table_prefix}conversion_scripts cs ON ce.script_id = cs.id
                                         WHERE cs.campaign_id = c.id) as total_value
                                        FROM {$cf_table_prefix}campaigns c
                                        WHERE (1) {$search_mb_id2}
                                        ORDER BY c.created_at DESC
                                        LIMIT 5";
                                $result = sql_query($sql);
                                while ($row = sql_fetch_array($result)) {
                                    $cpa = ($row['conversions'] > 0 && $row['budget'] > 0) ? number_format($row['budget'] / $row['conversions']) : '-';
                                    $status_class = '';
                                    
                                    switch ($row['status']) {
                                        case '활성':
                                            $status_class = 'badge-success';
                                            break;
                                        case '비활성':
                                            $status_class = 'badge-secondary';
                                            break;
                                        case '일시중지':
                                            $status_class = 'badge-warning';
                                            break;
                                    }
                                    
                                    echo '
                                    <tr>
                                        <td>' . $row['name'] . '</td>
                                        <td><span class="badge ' . $status_class . '">' . $row['status'] . '</span></td>
                                        <td>' . $row['start_date'] . '</td>
                                        <td>' . $row['conversions'] . '</td>
                                        <td>' . $cpa . '원</td>
                                        <td>
                                            <a href="' . CF_CAMPAIGN_URL . '/campaign_view.php?id=' . $row['id'] . '" class="btn btn-sm btn-info">보기</a>
                                            <a href="' . CF_CAMPAIGN_URL . '/campaign_edit.php?id=' . $row['id'] . '" class="btn btn-sm btn-primary">수정</a>
                                        </td>
                                    </tr>
                                    ';
                                }
                                
                                if (sql_num_rows($result) == 0) {
                                    echo '<tr><td colspan="6" class="text-center">등록된 캠페인이 없습니다.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 색상 생성 함수
function get_random_color($seed) {
    srand(crc32($seed));
    $r = rand(0, 200);
    $g = rand(0, 200);
    $b = rand(0, 200);
    return "rgb($r, $g, $b)";
}

// 지난 30일 전환 데이터 조회
$sql = "SELECT 
            DATE(ce.conversion_time) as date,
            COUNT(*) as pageviews,
            SUM(CASE WHEN ce.conversion_value > 0 THEN 1 ELSE 0 END) as conversions
        FROM {$cf_table_prefix}conversion_events ce
        JOIN {$cf_table_prefix}conversion_scripts cs ON ce.script_id = cs.id
        JOIN {$cf_table_prefix}campaigns c ON cs.campaign_id = c.id
        WHERE ce.conversion_time >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
        {$search_mb_id2}
        GROUP BY DATE(ce.conversion_time)
        ORDER BY DATE(ce.conversion_time)";
$result = sql_query($sql);

$dates = array();
$pageviews = array();
$conversions = array();

while ($row = sql_fetch_array($result)) {
    $dates[] = $row['date'];
    $pageviews[] = $row['pageviews'];
    $conversions[] = $row['conversions'];
}

// 소스별 전환 데이터 조회
$sql = "SELECT 
            IFNULL(ce.source, '직접 방문') as source,
            COUNT(*) as count
        FROM {$cf_table_prefix}conversion_events ce
        JOIN {$cf_table_prefix}conversion_scripts cs ON ce.script_id = cs.id
        JOIN {$cf_table_prefix}campaigns c ON cs.campaign_id = c.id
        WHERE ce.conversion_value > 0 
        {$search_mb_id2}
        GROUP BY IFNULL(ce.source, '직접 방문')
        ORDER BY count DESC
        LIMIT 5";
$result = sql_query($sql);

$source_labels = array();
$source_data = array();
$source_colors = array();

while ($row = sql_fetch_array($result)) {
    $source_labels[] = $row['source'];
    $source_data[] = $row['count'];
    $source_colors[] = get_random_color($row['source']);
}
?>

<!-- 차트 데이터 -->
<script>
// 전환 추이 차트
var ctx = document.getElementById("conversionChart");
var myLineChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($dates); ?>,
        datasets: [{
            label: "페이지뷰",
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
            data: <?php echo json_encode($pageviews); ?>,
        },
        {
            label: "전환",
            lineTension: 0.3,
            backgroundColor: "rgba(28, 200, 138, 0.05)",
            borderColor: "rgba(28, 200, 138, 1)",
            pointRadius: 3,
            pointBackgroundColor: "rgba(28, 200, 138, 1)",
            pointBorderColor: "rgba(28, 200, 138, 1)",
            pointHoverRadius: 3,
            pointHoverBackgroundColor: "rgba(28, 200, 138, 1)",
            pointHoverBorderColor: "rgba(28, 200, 138, 1)",
            pointHitRadius: 10,
            pointBorderWidth: 2,
            data: <?php echo json_encode($conversions); ?>,
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
                    beginAtZero: true
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
            display: true
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
            caretPadding: 10
        }
    }
});

// 소스별 전환 차트
var ctx2 = document.getElementById("sourceChart");
var myPieChart = new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($source_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($source_data); ?>,
            backgroundColor: <?php echo json_encode($source_colors); ?>,
            hoverBackgroundColor: <?php echo json_encode($source_colors); ?>,
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
            display: false
        },
        cutoutPercentage: 80,
    },
});
</script>

<?php
// 푸터 포함
include_once './footer.php';
?>
