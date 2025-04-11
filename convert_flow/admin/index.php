<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';


// 페이지 제목
$page_title = "관리자 대시보드";

// 헤더 포함
include_once CF_PATH . '/header.php';

// 기간 설정 (기본: 30일)
$period = isset($_GET['period']) ? intval($_GET['period']) : 30;
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime("-{$period} days"));
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">관리자 대시보드</h1>
        <div>
            <form id="period-form" class="form-inline">
                <div class="form-group mr-2">
                    <select class="form-control" name="period" onchange="document.getElementById('period-form').submit();">
                        <option value="7" <?php echo $period == 7 ? 'selected' : ''; ?>>최근 7일</option>
                        <option value="30" <?php echo $period == 30 ? 'selected' : ''; ?>>최근 30일</option>
                        <option value="90" <?php echo $period == 90 ? 'selected' : ''; ?>>최근 90일</option>
                        <option value="365" <?php echo $period == 365 ? 'selected' : ''; ?>>최근 1년</option>
                    </select>
                </div>
                <a href="ad_platform_settings.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-cog fa-sm"></i> 광고 플랫폼 설정
                </a>
            </form>
        </div>
    </div>
    
    <!-- 통계 카드 -->
    <div class="row">
        <!-- 전체 사용자 수 -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                전체 사용자</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $sql = "SELECT COUNT(*) as cnt FROM members WHERE level < 9";
                                $row = sql_fetch($sql);
                                echo number_format($row['cnt']) . '명';
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

        <!-- 광고 플랫폼 수 -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                광고 플랫폼</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $sql = "SELECT COUNT(*) as cnt FROM ad_platforms WHERE is_active = 1";
                                $row = sql_fetch($sql);
                                echo number_format($row['cnt']) . '개';
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bullhorn fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 전체 광고 계정 수 -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                광고 계정</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $sql = "SELECT COUNT(*) as cnt FROM user_ad_accounts";
                                $row = sql_fetch($sql);
                                echo number_format($row['cnt']) . '개';
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-id-card fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 전체 캠페인 수 -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                전체 캠페인</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $sql = "SELECT COUNT(*) as cnt FROM campaigns";
                                $row = sql_fetch($sql);
                                echo number_format($row['cnt']) . '개';
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
    </div>

    <!-- 차트 영역 -->
    <div class="row">
        <!-- 기간별 광고 계정 등록 추이 -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">기간별 광고 계정 등록 추이</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="accountsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- 플랫폼별 계정 수 -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">플랫폼별 계정 수</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="platformChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <?php
                        $sql = "SELECT p.name, COUNT(a.id) as account_count
                                FROM ad_platforms p
                                LEFT JOIN user_ad_accounts a ON p.id = a.platform_id
                                WHERE p.is_active = 1
                                GROUP BY p.id
                                ORDER BY account_count DESC";
                        $result = sql_query($sql);
                        $platforms = array();
                        while ($row = sql_fetch_array($result)) {
                            $platforms[] = $row['name'];
                            echo '<span class="mr-2">
                                    <i class="fas fa-circle" style="color:' . get_random_color($row['name']) . '"></i> ' . $row['name'] . '
                                  </span>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- 최근 광고 계정 -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">최근 등록된 광고 계정</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>계정명</th>
                                    <th>플랫폼</th>
                                    <th>사용자</th>
                                    <th>등록일</th>
                                    <th>상태</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT a.*, p.name as platform_name, m.name as user_name
                                        FROM user_ad_accounts a
                                        JOIN ad_platforms p ON a.platform_id = p.id
                                        JOIN members m ON a.user_id = m.id
                                        ORDER BY a.created_at DESC
                                        LIMIT 5";
                                $result = sql_query($sql);
                                while ($row = sql_fetch_array($result)) {
                                    $status_class = $row['status'] === '활성' ? 'badge-success' : 'badge-secondary';
                                    
                                    echo '
                                    <tr>
                                        <td>' . $row['account_name'] . '</td>
                                        <td>' . $row['platform_name'] . '</td>
                                        <td>' . $row['user_name'] . '</td>
                                        <td>' . substr($row['created_at'], 0, 10) . '</td>
                                        <td><span class="badge ' . $status_class . '">' . $row['status'] . '</span></td>
                                    </tr>
                                    ';
                                }
                                
                                if (sql_num_rows($result) == 0) {
                                    echo '<tr><td colspan="5" class="text-center">등록된 광고 계정이 없습니다.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 최근 캠페인 -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">최근 등록된 캠페인</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>캠페인명</th>
                                    <th>사용자</th>
                                    <th>시작일</th>
                                    <th>상태</th>
                                    <th>예산</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT c.*, m.name as user_name
                                        FROM campaigns c
                                        JOIN members m ON c.user_id = m.id
                                        ORDER BY c.created_at DESC
                                        LIMIT 5";
                                $result = sql_query($sql);
                                while ($row = sql_fetch_array($result)) {
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
                                        <td>' . $row['user_name'] . '</td>
                                        <td>' . $row['start_date'] . '</td>
                                        <td><span class="badge ' . $status_class . '">' . $row['status'] . '</span></td>
                                        <td>' . number_format($row['budget']) . '원</td>
                                    </tr>
                                    ';
                                }
                                
                                if (sql_num_rows($result) == 0) {
                                    echo '<tr><td colspan="5" class="text-center">등록된 캠페인이 없습니다.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 광고 플랫폼 목록 -->
    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">광고 플랫폼 목록</h6>
                    <a href="ad_platform_settings.php?action=add_platform" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> 새 플랫폼 추가
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>이름</th>
                                    <th>코드</th>
                                    <th>계정 수</th>
                                    <th>상태</th>
                                    <th>필드 수</th>
                                    <th>관리</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT p.*, 
                                        (SELECT COUNT(*) FROM user_ad_accounts WHERE platform_id = p.id) as account_count
                                        FROM ad_platforms p
                                        ORDER BY p.name ASC";
                                $result = sql_query($sql);
                                while ($row = sql_fetch_array($result)) {
                                    $field_count = 0;
                                    if (!empty($row['field_settings'])) {
                                        $field_settings = json_decode($row['field_settings'], true);
                                        $field_count = is_array($field_settings) ? count($field_settings) : 0;
                                    }
                                    
                                    $status_class = $row['is_active'] ? 'badge-success' : 'badge-secondary';
                                    $status_text = $row['is_active'] ? '활성' : '비활성';
                                    
                                    echo '
                                    <tr>
                                        <td>' . $row['name'] . '</td>
                                        <td><code>' . $row['platform_code'] . '</code></td>
                                        <td>' . number_format($row['account_count']) . '</td>
                                        <td><span class="badge ' . $status_class . '">' . $status_text . '</span></td>
                                        <td>' . $field_count . '</td>
                                        <td>
                                            <a href="ad_platform_settings.php?action=edit&id=' . $row['id'] . '" class="btn btn-sm btn-primary">
                                                <i class="fas fa-sliders-h"></i> 필드 설정
                                            </a>
                                            <a href="ad_platform_settings.php?action=edit_platform&id=' . $row['id'] . '" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i> 수정
                                            </a>
                                        </td>
                                    </tr>
                                    ';
                                }
                                
                                if (sql_num_rows($result) == 0) {
                                    echo '<tr><td colspan="6" class="text-center">등록된 광고 플랫폼이 없습니다.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 광고 그룹 및 광고 통계 -->
    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">광고 그룹 및 광고 통계</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">총 광고 그룹</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php
                                                $sql = "SELECT COUNT(*) as cnt FROM ad_groups";
                                                $row = sql_fetch($sql);
                                                echo number_format($row['cnt']) . '개';
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-layer-group fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">활성 광고 그룹</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php
                                                $sql = "SELECT COUNT(*) as cnt FROM ad_groups WHERE status = '활성'";
                                                $row = sql_fetch($sql);
                                                echo number_format($row['cnt']) . '개';
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">총 광고 소재</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php
                                                $sql = "SELECT COUNT(*) as cnt FROM ad_materials";
                                                $row = sql_fetch($sql);
                                                echo number_format($row['cnt']) . '개';
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-ad fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">총 전환 수</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php
                                                $sql = "SELECT COUNT(*) as cnt FROM conversion_events_log WHERE conversion_value > 0";
                                                $row = sql_fetch($sql);
                                                echo number_format($row['cnt']) . '건';
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
                    </div>
                    
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>광고 그룹명</th>
                                    <th>캠페인</th>
                                    <th>전환 수</th>
                                    <th>클릭 수</th>
                                    <th>비용</th>
                                    <th>CPA</th>
                                    <th>상태</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT ag.*, c.name as campaign_name,
                                        (SELECT COUNT(*) FROM conversion_events_log cel 
                                         JOIN ad_materials am ON cel.ad_material_id = am.id
                                         WHERE am.ad_group_id = ag.id AND cel.conversion_value > 0) as conversions,
                                        (SELECT COUNT(*) FROM url_clicks uc 
                                         JOIN ad_materials am ON uc.ad_material_id = am.id
                                         WHERE am.ad_group_id = ag.id) as clicks,
                                        (SELECT SUM(uc.ad_cost) FROM url_clicks uc 
                                         JOIN ad_materials am ON uc.ad_material_id = am.id
                                         WHERE am.ad_group_id = ag.id) as cost
                                        FROM ad_groups ag
                                        JOIN campaigns c ON ag.campaign_id = c.id
                                        ORDER BY ag.created_at DESC
                                        LIMIT 10";
                                $result = sql_query($sql);
                                while ($row = sql_fetch_array($result)) {
                                    $cpa = ($row['conversions'] > 0 && $row['cost'] > 0) ? number_format($row['cost'] / $row['conversions']) : '-';
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
                                        <td>' . $row['campaign_name'] . '</td>
                                        <td>' . number_format($row['conversions']) . '</td>
                                        <td>' . number_format($row['clicks']) . '</td>
                                        <td>' . number_format($row['cost']) . '원</td>
                                        <td>' . $cpa . '원</td>
                                        <td><span class="badge ' . $status_class . '">' . $row['status'] . '</span></td>
                                    </tr>
                                    ';
                                }
                                
                                if (sql_num_rows($result) == 0) {
                                    echo '<tr><td colspan="7" class="text-center">등록된 광고 그룹이 없습니다.</td></tr>';
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

// 기간별 광고 계정 등록 추이 데이터 조회
$sql = "SELECT 
            DATE(created_at) as date,
            COUNT(*) as count
        FROM user_ad_accounts
        WHERE created_at >= '{$start_date}'
        AND created_at <= '{$end_date}'
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at)";
$result = sql_query($sql);

$dates = array();
$accounts = array();

// 선택된 기간의 모든 날짜 배열 생성
$date_period = new DatePeriod(
    new DateTime($start_date),
    new DateInterval('P1D'),
    new DateTime($end_date . ' +1 day')
);

foreach ($date_period as $date) {
    $date_str = $date->format('Y-m-d');
    $dates[] = $date_str;
    $accounts[$date_str] = 0;
}

// 데이터 채우기
while ($row = sql_fetch_array($result)) {
    if (isset($accounts[$row['date']])) {
        $accounts[$row['date']] = (int)$row['count'];
    }
}

// 누적 계정 수 계산
$sql = "SELECT COUNT(*) as total FROM user_ad_accounts WHERE created_at < '{$start_date}'";
$row = sql_fetch($sql);
$cumulative_count = (int)$row['total'];

$cumulative_accounts = array();
foreach ($accounts as $date => $count) {
    $cumulative_count += $count;
    $cumulative_accounts[] = $cumulative_count;
}

// 플랫폼별 계정 수 데이터 조회
$sql = "SELECT 
            p.name as platform_name,
            COUNT(a.id) as account_count
        FROM ad_platforms p
        LEFT JOIN user_ad_accounts a ON p.id = a.platform_id
        WHERE p.is_active = 1
        GROUP BY p.id
        ORDER BY account_count DESC";
$result = sql_query($sql);

$platform_labels = array();
$platform_data = array();
$platform_colors = array();

while ($row = sql_fetch_array($result)) {
    $platform_labels[] = $row['platform_name'];
    $platform_data[] = (int)$row['account_count'];
    $platform_colors[] = get_random_color($row['platform_name']);
}
?>

<!-- 차트 스크립트 -->
<script>
// 기간별 광고 계정 등록 추이 차트
var ctx = document.getElementById("accountsChart");
var myLineChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($dates); ?>,
        datasets: [{
            label: "신규 등록",
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
            data: <?php echo json_encode(array_values($accounts)); ?>,
        },
        {
            label: "누적 계정 수",
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
            data: <?php echo json_encode($cumulative_accounts); ?>,
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

// 플랫폼별 계정 수 차트
var ctx2 = document.getElementById("platformChart");
var myPieChart = new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($platform_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($platform_data); ?>,
            backgroundColor: <?php echo json_encode($platform_colors); ?>,
            hoverBackgroundColor: <?php echo json_encode($platform_colors); ?>,
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
include_once CF_PATH . '/footer.php';
?>