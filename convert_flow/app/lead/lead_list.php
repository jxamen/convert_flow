<?php
/**
 * 리드 데이터 통합 관리 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 캠페인 필터 (선택적)
$campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;

// 폼 필터 (선택적)
$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;

// 페이지 번호
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

// 검색어
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 시작일 / 종료일 필터
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

// 상태 필터
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// 소스 필터
$source = isset($_GET['source']) ? trim($_GET['source']) : '';

// 캠페인 모델 로드
require_once CF_MODEL_PATH . '/campaign.model.php';
$campaign_model = new CampaignModel();

// 폼 모델 로드
require_once CF_MODEL_PATH . '/form.model.php';
$form_model = new FormModel();

// 권한 체크를 위한 쿼리 초기화
$where_my = '';
if (!$is_admin) {
    // 관리자가 아닌 경우 본인 소유 리드만 볼 수 있음
    $where_my = " AND (c.user_id = {$member['id']} OR f.user_id = {$member['id']})";
}

// 페이지당 표시 수
$rows = 20;

// 검색 및 필터 조건 적용
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

// 전체 리드 수 조회
$sql = "SELECT COUNT(*) as cnt FROM {$cf_table_prefix}leads l 
        LEFT JOIN {$cf_table_prefix}campaigns c ON l.campaign_id = c.id
        LEFT JOIN {$cf_table_prefix}forms f ON l.form_id = f.id
        $where";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

// 전체 페이지 계산
$total_page = ceil($total_count / $rows);
if ($total_page < 1) $total_page = 1;
if ($page > $total_page) $page = $total_page;

// 페이지 계산 및 LIMIT 적용
$from_record = ($page - 1) * $rows;

// 리드 데이터 조회 (캠페인명, 폼명 포함)
$sql = "SELECT l.*, f.name as form_name, c.name as campaign_name 
        FROM {$cf_table_prefix}leads l 
        LEFT JOIN {$cf_table_prefix}forms f ON l.form_id = f.id 
        LEFT JOIN {$cf_table_prefix}campaigns c ON l.campaign_id = c.id
        $where 
        ORDER BY l.created_at DESC 
        LIMIT $from_record, $rows";
$result = sql_query($sql);

$leads = array();
while ($row = sql_fetch_array($result)) {
    $leads[] = $row;
}

// 캠페인 목록 조회 (필터용)
$campaign_sql = "SELECT id, name FROM {$cf_table_prefix}campaigns";
if (!$is_admin) {
    $campaign_sql .= " WHERE user_id = {$member['id']}";
}
$campaign_sql .= " ORDER BY name ASC";
$campaign_result = sql_query($campaign_sql);

$campaigns = array();
while ($row = sql_fetch_array($campaign_result)) {
    $campaigns[$row['id']] = $row['name'];
}

// 폼 목록 조회 (필터용)
$form_sql = "SELECT id, name FROM {$cf_table_prefix}forms";
if (!$is_admin) {
    $form_sql .= " WHERE user_id = {$member['id']}";
}
$form_sql .= " ORDER BY name ASC";
$form_result = sql_query($form_sql);

$forms = array();
while ($row = sql_fetch_array($form_result)) {
    $forms[$row['id']] = $row['name'];
}

// 소스 목록 조회 (필터용)
$source_sql = "SELECT DISTINCT utm_source FROM {$cf_table_prefix}leads 
               WHERE utm_source != ''";
if ($campaign_id > 0) {
    $source_sql .= " AND campaign_id = " . intval($campaign_id);
}
if ($form_id > 0) {
    $source_sql .= " AND form_id = " . intval($form_id);
}
$source_sql .= " ORDER BY utm_source ASC";
$source_result = sql_query($source_sql);

$sources = array();
while ($row = sql_fetch_array($source_result)) {
    $sources[] = $row['utm_source'];
}

// 상태 목록 (옵션 변수로 관리)
$status_options = array(
    '신규' => '신규',
    '처리중' => '처리중',
    '전송완료' => '전송완료',
    '거부' => '거부',
    '중복' => '중복'
);

// 상태별 뱃지 클래스 (옵션 변수로 관리)
$status_badge_class = array(
    '신규' => 'badge-primary',
    '처리중' => 'badge-warning',
    '전송완료' => 'badge-success',
    '거부' => 'badge-danger',
    '중복' => 'badge-secondary'
);

// 페이지 제목
$page_title = "리드 데이터 관리";
if ($campaign_id > 0 && isset($campaigns[$campaign_id])) {
    $page_title .= " - " . $campaigns[$campaign_id];
} else if ($form_id > 0 && isset($forms[$form_id])) {
    $page_title .= " - " . $forms[$form_id];
}

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><?php echo $page_title; ?></h1>
    <p class="mb-4">
        모든 리드 데이터를 관리합니다. 캠페인이나 폼을 선택하여 특정 데이터만 볼 수 있습니다.
    </p>
    
    <!-- 필터 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">검색 및 필터</h6>
        </div>
        <div class="card-body">
            <form method="get" action="lead_list.php" class="mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="campaign_id">캠페인</label>
                            <select class="form-control" id="campaign_id" name="campaign_id">
                                <option value="">전체 캠페인</option>
                                <?php foreach ($campaigns as $id => $name): ?>
                                <option value="<?php echo $id; ?>" <?php echo ($campaign_id == $id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="form_id">폼</label>
                            <select class="form-control" id="form_id" name="form_id">
                                <option value="">전체 폼</option>
                                <?php foreach ($forms as $id => $name): ?>
                                <option value="<?php echo $id; ?>" <?php echo ($form_id == $id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="search">검색어</label>
                            <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="이름, 이메일, 전화번호 등 검색...">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="start_date">시작일</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="end_date">종료일</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">상태</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">전체 상태</option>
                                <?php foreach($status_options as $status_key => $status_name): ?>
                                <option value="<?php echo $status_key; ?>" <?php echo ($status == $status_key) ? 'selected' : ''; ?>><?php echo $status_name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="source">소스</label>
                            <select class="form-control" id="source" name="source">
                                <option value="">전체 소스</option>
                                <?php foreach ($sources as $src): ?>
                                <option value="<?php echo htmlspecialchars($src); ?>" <?php echo ($source == $src) ? 'selected' : ''; ?>><?php echo htmlspecialchars($src); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12 text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> 검색
                        </button>
                        <a href="lead_list.php" class="btn btn-secondary ml-1">
                            <i class="fas fa-undo"></i> 초기화
                        </a>
                        <?php if (count($leads) > 0): ?>
                        <a href="lead_export.php?campaign_id=<?php echo $campaign_id; ?>&form_id=<?php echo $form_id; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&source=<?php echo urlencode($source); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="btn btn-success ml-1">
                            <i class="fas fa-file-excel"></i> 엑셀 내보내기
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 통계 개요 카드 -->
    <div class="row mb-4">
        <!-- 전체 리드 수 -->
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">전체 리드</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_count); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 오늘 수집된 리드 수 -->
        <?php
        $today_sql = "SELECT COUNT(*) as cnt FROM {$cf_table_prefix}leads l 
                      LEFT JOIN {$cf_table_prefix}campaigns c ON l.campaign_id = c.id
                      LEFT JOIN {$cf_table_prefix}forms f ON l.form_id = f.id
                      WHERE DATE(l.created_at) = CURDATE()" . $where_my;
        
        if ($campaign_id > 0) {
            $today_sql .= " AND l.campaign_id = " . intval($campaign_id);
        }
        if ($form_id > 0) {
            $today_sql .= " AND l.form_id = " . intval($form_id);
        }
        
        $today_row = sql_fetch($today_sql);
        $today_count = $today_row['cnt'];
        ?>
        <div class="col-md-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">오늘 수집</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($today_count); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 각 상태별 리드 수 -->
        <?php
        $status_sql = "SELECT l.status, COUNT(*) as cnt 
                      FROM {$cf_table_prefix}leads l 
                      LEFT JOIN {$cf_table_prefix}campaigns c ON l.campaign_id = c.id
                      LEFT JOIN {$cf_table_prefix}forms f ON l.form_id = f.id
                      WHERE 1=1" . $where_my;
        
        if ($campaign_id > 0) {
            $status_sql .= " AND l.campaign_id = " . intval($campaign_id);
        }
        if ($form_id > 0) {
            $status_sql .= " AND l.form_id = " . intval($form_id);
        }
        
        $status_sql .= " GROUP BY l.status";
        
        $status_result = sql_query($status_sql);
        $status_counts = array();
        while ($status_row = sql_fetch_array($status_result)) {
            $status_counts[$status_row['status']] = $status_row['cnt'];
        }
        
        // 신규 리드 수
        $new_count = isset($status_counts['신규']) ? $status_counts['신규'] : 0;
        ?>
        <div class="col-md-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">신규 리드</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($new_count); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 전환율 (리드/방문자) -->
        <?php
        // 방문자 수 추정 (실제 시스템에서는 별도 통계 테이블 사용)
        $visitor_count = $total_count > 0 ? $total_count * 10 : 0; // 임의로 10배수 방문자 수 가정
        $conversion_rate = $visitor_count > 0 ? round(($total_count / $visitor_count) * 100, 1) : 0;
        ?>
        <div class="col-md-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">전환율</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $conversion_rate; ?>%</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 리드 데이터 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">리드 데이터 목록</h6>
            <span class="text-muted">총 <?php echo number_format($total_count); ?>개의 데이터</span>
        </div>
        <div class="card-body">
            <?php if (count($leads) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="leadDataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="12%">제출일시</th>
                            <th width="10%">캠페인</th>
                            <th width="10%">폼</th>
                            <th width="23%">데이터 요약</th>
                            <th width="12%">소스</th>
                            <th width="10%">상태</th>
                            <th width="18%">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td><?php echo $lead['id']; ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($lead['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($lead['campaign_name'] ?? '미지정'); ?></td>
                            <td><?php echo htmlspecialchars($lead['form_name'] ?? '미지정'); ?></td>
                            <td>
                                <?php
                                // JSON 데이터 파싱
                                $lead_data = json_decode($lead['data'], true);
                                if ($lead_data) {
                                    $summary = array();
                                    $counter = 0;
                                    
                                    foreach ($lead_data as $key => $value) {
                                        if ($counter < 3 && !empty($value)) {
                                            $summary[] = "<strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars(substr($value, 0, 20)) . (strlen($value) > 20 ? '...' : '');
                                            $counter++;
                                        }
                                    }
                                    
                                    echo implode('<br>', $summary);
                                    if (count($lead_data) > 3) {
                                        echo '<br><small class="text-muted">+ ' . (count($lead_data) - 3) . '개 더보기</small>';
                                    }
                                } else {
                                    echo '<span class="text-muted">데이터 없음</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if (!empty($lead['utm_source'])) {
                                    echo htmlspecialchars($lead['utm_source']);
                                    if (!empty($lead['utm_medium'])) {
                                        echo ' / ' . htmlspecialchars($lead['utm_medium']);
                                    }
                                } else {
                                    echo '<span class="text-muted">직접 방문</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <span class="badge <?php echo isset($status_badge_class[$lead['status']]) ? $status_badge_class[$lead['status']] : 'badge-info'; ?>">
                                    <?php echo htmlspecialchars($lead['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info view-lead" data-id="<?php echo $lead['id']; ?>">
                                    <i class="fas fa-eye"></i> 상세
                                </button>
                                <button type="button" class="btn btn-sm btn-primary edit-lead" data-id="<?php echo $lead['id']; ?>">
                                    <i class="fas fa-edit"></i> 상태
                                </button>
                                <button type="button" class="btn btn-sm btn-danger delete-lead" data-id="<?php echo $lead['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            <div class="d-flex justify-content-center mt-4">
                <ul class="pagination">
                    <?php
                    $start_page = max(1, $page - 4);
                    $end_page = min($total_page, $page + 4);
                    
                    // 페이지네이션 URL 파라미터 구성
                    $pagination_params = "campaign_id={$campaign_id}&form_id={$form_id}&search=" . urlencode($search) . "&status=" . urlencode($status) . "&source=" . urlencode($source) . "&start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date);
                    
                    // 처음 페이지로 이동
                    if ($page > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1&' . $pagination_params . '">&laquo;</a></li>';
                    }
                    
                    // 이전 페이지로 이동
                    if ($page > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=' . ($page - 1) . '&' . $pagination_params . '">&lt;</a></li>';
                    }
                    
                    // 페이지 번호 표시
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $page) {
                            echo '<li class="page-item active"><a class="page-link" href="#">' . $i . '</a></li>';
                        } else {
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $i . '&' . $pagination_params . '">' . $i . '</a></li>';
                        }
                    }
                    
                    // 다음 페이지로 이동
                    if ($page < $total_page) {
                        echo '<li class="page-item"><a class="page-link" href="?page=' . ($page + 1) . '&' . $pagination_params . '">&gt;</a></li>';
                    }
                    
                    // 마지막 페이지로 이동
                    if ($page < $total_page) {
                        echo '<li class="page-item"><a class="page-link" href="?page=' . $total_page . '&' . $pagination_params . '">&raquo;</a></li>';
                    }
                    ?>
                </ul>
            </div>
            <?php else: ?>
            <div class="text-center p-5">
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle"></i> 검색 조건에 맞는 리드 데이터가 없습니다.
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 리드 상세 보기 모달 -->
    <div class="modal fade" id="leadDetailModal" tabindex="-1" role="dialog" aria-labelledby="leadDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="leadDetailModalLabel">리드 상세 정보</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center p-5" id="leadDetailLoading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">로딩중...</span>
                        </div>
                        <p class="mt-2 text-muted">데이터를 불러오는 중입니다...</p>
                    </div>
                    
                    <div id="leadDetailContent" style="display: none;">
                        <!-- 리드 상세 정보 영역 -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="font-weight-bold mb-0">기본 정보</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>ID:</strong> <span id="leadDetailId"></span></p>
                                        <p><strong>제출일시:</strong> <span id="leadDetailSubmitted"></span></p>
                                        <p><strong>IP 주소:</strong> <span id="leadDetailIP"></span></p>
                                        <p><strong>폼:</strong> <span id="leadDetailForm"></span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>상태:</strong> <span id="leadDetailStatus"></span></p>
                                        <p><strong>소스:</strong> <span id="leadDetailSource"></span></p>
                                        <p><strong>사용자 기기:</strong> <span id="leadDetailDevice"></span></p>
                                        <p><strong>캠페인:</strong> <span id="leadDetailCampaign"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="font-weight-bold mb-0">제출 데이터</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="30%">필드</th>
                                                <th width="70%">값</th>
                                            </tr>
                                        </thead>
                                        <tbody id="leadDetailData">
                                            <!-- 동적으로 내용 추가 -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h6 class="font-weight-bold mb-0">UTM 파라미터</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Source:</strong> <span id="leadDetailUtmSource"></span></p>
                                        <p><strong>Medium:</strong> <span id="leadDetailUtmMedium"></span></p>
                                        <p><strong>Campaign:</strong> <span id="leadDetailUtmCampaign"></span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Content:</strong> <span id="leadDetailUtmContent"></span></p>
                                        <p><strong>Term:</strong> <span id="leadDetailUtmTerm"></span></p>
                                        <p><strong>리퍼러:</strong> <span id="leadDetailReferrer"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="editLeadBtn" class="btn btn-primary" data-toggle="modal" data-target="#leadStatusModal">
                        <i class="fas fa-edit"></i> 상태 변경
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 리드 상태 변경 모달 -->
    <div class="modal fade" id="leadStatusModal" tabindex="-1" role="dialog" aria-labelledby="leadStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="leadStatusModalLabel">리드 상태 변경</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="statusChangeForm">
                        <div class="form-group">
                            <label for="leadStatusSelect">새 상태 선택</label>
                            <select class="form-control" id="leadStatusSelect" name="status">
                                <?php foreach($status_options as $status_key => $status_name): ?>
                                <option value="<?php echo $status_key; ?>"><?php echo $status_name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="statusNote">상태 변경 메모 (선택사항)</label>
                            <textarea class="form-control" id="statusNote" name="note" rows="3" placeholder="상태 변경에 대한 메모를 입력하세요..."></textarea>
                        </div>
                        <input type="hidden" id="leadStatusId" name="lead_id" value="">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="button" id="saveLeadStatus" class="btn btn-primary">저장</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 리드 삭제 확인 모달 -->
    <div class="modal fade" id="deleteLeadModal" tabindex="-1" role="dialog" aria-labelledby="deleteLeadModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteLeadModalLabel">리드 삭제 확인</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>정말 이 리드 데이터를 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.</p>
                    <input type="hidden" id="deleteLeadId" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="button" id="confirmDeleteLead" class="btn btn-danger">삭제</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 캠페인 선택 시 자동 제출
    $('#campaign_id').change(function() {
        $(this).closest('form').submit();
    });
    
    // 폼 선택 시 자동 제출
    $('#form_id').change(function() {
        $(this).closest('form').submit();
    });
    
    // 리드 상세 보기
    $('.view-lead').click(function() {
        var leadId = $(this).data('id');
        $('#leadDetailLoading').show();
        $('#leadDetailContent').hide();
        $('#leadDetailModal').modal('show');
        
        // AJAX로 리드 상세 정보 가져오기
        $.ajax({
            url: 'lead_ajax.php',
            type: 'POST',
            data: {
                action: 'get_lead',
                lead_id: leadId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var lead = response.lead;
                    
                    // 기본 정보 채우기
                    $('#leadDetailId').text(lead.id);
                    $('#leadDetailSubmitted').text(lead.created_at);
                    $('#leadDetailIP').text(lead.ip_address || '-');
                    $('#leadDetailForm').text(lead.form_name || '미지정');
                    $('#leadDetailCampaign').text(lead.campaign_name || '미지정');
                    
                    // 상태 배지 표시
                    var statusBadgeClass = getStatusBadgeClass(lead.status);
                    $('#leadDetailStatus').html('<span class="badge ' + statusBadgeClass + '">' + lead.status + '</span>');
                    
                    $('#leadDetailSource').text(lead.utm_source || '직접 방문');
                    $('#leadDetailDevice').text(getUserDeviceInfo(lead.user_agent) || '-');
                    
                    // UTM 정보 채우기
                    $('#leadDetailUtmSource').text(lead.utm_source || '-');
                    $('#leadDetailUtmMedium').text(lead.utm_medium || '-');
                    $('#leadDetailUtmCampaign').text(lead.utm_campaign || '-');
                    $('#leadDetailUtmContent').text(lead.utm_content || '-');
                    $('#leadDetailUtmTerm').text(lead.utm_term || '-');
                    $('#leadDetailReferrer').text(lead.referrer || '-');
                    
                    // 폼 데이터 채우기
                    var formData = JSON.parse(lead.data);
                    var tableRows = '';
                    
                    for (var key in formData) {
                        if (formData.hasOwnProperty(key)) {
                            var value = formData[key];
                            
                            // 값이 없으면 처리
                            if (value === null || value === undefined || value === '') {
                                value = '<span class="text-muted">미입력</span>';
                            } else if (typeof value === 'string' && value.match(/^https?:\/\//)) {
                                // URL인 경우 링크로 표시
                                value = '<a href="' + value + '" target="_blank">' + value + '</a>';
                            } else if (typeof value === 'string' && value.includes('@') && value.includes('.')) {
                                // 이메일인 경우 링크로 표시
                                value = '<a href="mailto:' + value + '">' + value + '</a>';
                            } else if (typeof value === 'string' && value.match(/^\d{2,3}-?\d{3,4}-?\d{4}$/)) {
                                // 전화번호인 경우 링크로 표시
                                value = '<a href="tel:' + value + '">' + value + '</a>';
                            }
                            
                            tableRows += '<tr><td>' + key + '</td><td>' + value + '</td></tr>';
                        }
                    }
                    
                    $('#leadDetailData').html(tableRows);
                    $('#editLeadBtn').data('id', lead.id);
                    
                    // 로딩 숨기고 내용 표시
                    $('#leadDetailLoading').hide();
                    $('#leadDetailContent').show();
                    
                    // 상태 변경 모달 준비
                    $('#leadStatusId').val(lead.id);
                    $('#leadStatusSelect').val(lead.status);
                } else {
                    alert('리드 정보를 가져오는 중 오류가 발생했습니다: ' + response.message);
                    $('#leadDetailModal').modal('hide');
                }
            },
            error: function() {
                alert('서버 오류가 발생했습니다.');
                $('#leadDetailModal').modal('hide');
            }
        });
    });
    
    // 리드 상태 변경 버튼 클릭 시
    $('.edit-lead').click(function() {
        var leadId = $(this).data('id');
        $('#leadStatusId').val(leadId);
        
        // 현재 상태 가져오기
        $.ajax({
            url: 'lead_ajax.php',
            type: 'POST',
            data: {
                action: 'get_lead_status',
                lead_id: leadId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#leadStatusSelect').val(response.status);
                    $('#leadStatusModal').modal('show');
                } else {
                    alert('리드 상태 정보를 가져오는 중 오류가 발생했습니다: ' + response.message);
                }
            },
            error: function() {
                alert('서버 오류가 발생했습니다.');
            }
        });
    });
    
    // 상태 저장 버튼 클릭 시
    $('#saveLeadStatus').click(function() {
        var leadId = $('#leadStatusId').val();
        var newStatus = $('#leadStatusSelect').val();
        var note = $('#statusNote').val();
        
        $.ajax({
            url: 'lead_ajax.php',
            type: 'POST',
            data: {
                action: 'update_lead_status',
                lead_id: leadId,
                status: newStatus,
                note: note
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('리드 상태가 성공적으로 변경되었습니다.');
                    
                    // 페이지 리로드
                    location.reload();
                } else {
                    alert('리드 상태 변경 중 오류가 발생했습니다: ' + response.message);
                }
                $('#leadStatusModal').modal('hide');
            },
            error: function() {
                alert('서버 오류가 발생했습니다.');
                $('#leadStatusModal').modal('hide');
            }
        });
    });
    
    // 삭제 버튼 클릭 시
    $('.delete-lead').click(function() {
        var leadId = $(this).data('id');
        $('#deleteLeadId').val(leadId);
        $('#deleteLeadModal').modal('show');
    });
    
    // 삭제 확인 버튼 클릭 시
    $('#confirmDeleteLead').click(function() {
        var leadId = $('#deleteLeadId').val();
        
        $.ajax({
            url: 'lead_ajax.php',
            type: 'POST',
            data: {
                action: 'delete_lead',
                lead_id: leadId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('리드가 성공적으로 삭제되었습니다.');
                    
                    // 페이지 리로드
                    location.reload();
                } else {
                    alert('리드 삭제 중 오류가 발생했습니다: ' + response.message);
                }
                $('#deleteLeadModal').modal('hide');
            },
            error: function() {
                alert('서버 오류가 발생했습니다.');
                $('#deleteLeadModal').modal('hide');
            }
        });
    });
    
    // 상태별 배지 클래스 반환 함수
    function getStatusBadgeClass(status) {
        var badgeClass = {
            '신규': 'badge-primary',
            '처리중': 'badge-warning',
            '전송완료': 'badge-success',
            '거부': 'badge-danger',
            '중복': 'badge-secondary'
        };
        
        return badgeClass[status] || 'badge-info';
    }
    
    // 사용자 에이전트 정보 추출 함수
    function getUserDeviceInfo(userAgent) {
        if (!userAgent) return '알 수 없음';
        
        var deviceInfo = '';
        
        // 모바일 기기 확인
        if (userAgent.match(/Mobile|Android|iPhone|iPad|iPod|Windows Phone/i)) {
            if (userAgent.match(/Android/i)) {
                deviceInfo = 'Android';
            } else if (userAgent.match(/iPhone|iPad|iPod/i)) {
                deviceInfo = 'iOS';
            } else if (userAgent.match(/Windows Phone/i)) {
                deviceInfo = 'Windows Phone';
            } else {
                deviceInfo = '모바일';
            }
        } else {
            deviceInfo = 'PC';
        }
        
        // 브라우저 정보 추가
        if (userAgent.match(/Chrome/i) && !userAgent.match(/Edg/i)) {
            deviceInfo += ' / Chrome';
        } else if (userAgent.match(/Firefox/i)) {
            deviceInfo += ' / Firefox';
        } else if (userAgent.match(/MSIE|Trident/i)) {
            deviceInfo += ' / IE';
        } else if (userAgent.match(/Edg/i)) {
            deviceInfo += ' / Edge';
        } else if (userAgent.match(/Safari/i) && !userAgent.match(/Chrome/i)) {
            deviceInfo += ' / Safari';
        }
        
        return deviceInfo;
    }
    
    // 데이터 테이블 초기화 (선택적)
    if ($.fn.dataTable) {
        $('#leadDataTable').DataTable({
            "paging": false,
            "ordering": true,
            "info": false,
            "searching": false,
            "language": {
                "emptyTable": "데이터가 없습니다.",
                "zeroRecords": "검색 결과가 없습니다."
            }
        });
    }
});
</script>
<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>