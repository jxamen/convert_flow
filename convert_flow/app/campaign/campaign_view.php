<?php
/**
 * 캠페인 상세 보기 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 페이지 제목
$page_title = "캠페인 상세 보기";

// 권한 체크
if (!$is_member) {
    alert('로그인이 필요한 서비스입니다.', CF_URL . '/login.php');
}

// 캠페인 ID 확인
$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($campaign_id <= 0) {
    alert('유효하지 않은 캠페인 ID입니다.');
    exit;
}

// 캠페인 모델 로드
include_once CF_MODEL_PATH . '/campaign/campaign.model.php';
$campaign_model = new CampaignModel();

// 캠페인 정보 가져오기
$campaign = $campaign_model->get_campaign($campaign_id, $member['id']);
if (!$campaign) {
    alert('존재하지 않거나 접근 권한이 없는 캠페인입니다.');
    exit;
}

// 현재 탭 설정
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';
$valid_tabs = array('overview', 'ad_groups', 'ad_materials', 'stats', 'conversions');
if (!in_array($current_tab, $valid_tabs)) {
    $current_tab = 'overview';
}

// 통계 범위 설정
$stat_range = isset($_GET['stat_range']) ? trim($_GET['stat_range']) : '30days';
$date_ranges = array(
    'today' => array(
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d'),
        'label' => '오늘'
    ),
    'yesterday' => array(
        'start_date' => date('Y-m-d', strtotime('-1 day')),
        'end_date' => date('Y-m-d', strtotime('-1 day')),
        'label' => '어제'
    ),
    '7days' => array(
        'start_date' => date('Y-m-d', strtotime('-7 days')),
        'end_date' => date('Y-m-d'),
        'label' => '최근 7일'
    ),
    '30days' => array(
        'start_date' => date('Y-m-d', strtotime('-30 days')),
        'end_date' => date('Y-m-d'),
        'label' => '최근 30일'
    ),
    'this_month' => array(
        'start_date' => date('Y-m-01'),
        'end_date' => date('Y-m-d'),
        'label' => '이번 달'
    ),
    'last_month' => array(
        'start_date' => date('Y-m-01', strtotime('-1 month')),
        'end_date' => date('Y-m-t', strtotime('-1 month')),
        'label' => '지난 달'
    )
);

// 커스텀 날짜 범위 처리
$custom_date = false;
if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
    $custom_date = true;
    $stat_range = 'custom';
} else {
    // 선택된 날짜 범위
    $selected_range = $date_ranges[$stat_range];
    $start_date = $selected_range['start_date'];
    $end_date = $selected_range['end_date'];
}

// 데이터 로드 - 현재 탭에 필요한 데이터만 로드
if ($current_tab == 'overview') {
    // 캠페인 통계만 로드
    $campaign['statistics'] = $campaign_model->get_campaign_statistics($campaign_id, $start_date, $end_date);
} elseif ($current_tab == 'ad_groups') {
    // 광고 그룹 모델 로드
    include_once CF_MODEL_PATH . '/campaign/ad_group.model.php';
    $ad_group_model = new AdGroupModel();
    
    // 페이징 설정
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $items_per_page = isset($_GET['items_per_page']) ? intval($_GET['items_per_page']) : 10;
        
    // 정렬 설정
    $sort_field = isset($_GET['sort_field']) ? trim($_GET['sort_field']) : 'created_at';
    $sort_order = isset($_GET['sort_order']) ? trim($_GET['sort_order']) : 'DESC';
    
    // 검색 파라미터
    $search_params = array(
        'page' => $page,
        'items_per_page' => $items_per_page,
        'sort_field' => $sort_field,
        'sort_order' => $sort_order
    );
    
    // 광고 그룹 목록 가져오기    
    $ad_group_data = $ad_group_model->get_ad_groups_by_campaign($campaign_id, $search_params);
    $ad_groups = $ad_group_data['ad_groups'];
    $total_count = $ad_group_data['total_count'];
    $total_pages = $ad_group_data['total_pages'];

    // 각 광고 그룹별 통계 정보 로드
    foreach ($ad_groups as &$ad_group) {
        // 광고 그룹별 통계 데이터 가져오기
        $ad_group['statistics'] = $ad_group_model->get_ad_group_statistics($ad_group['id'], $start_date, $end_date);
        
        // 일별 통계 데이터 가져오기 (그래프용)
        $ad_group['daily_stats'] = $ad_group_model->get_daily_statistics($ad_group['id'], $start_date, $end_date);
        
        // 오늘 시간별 통계 데이터 초기화 (그래프용)
        $ad_group['hourly_stats'] = array();
    }
} elseif ($current_tab == 'ad_materials') {
    // 광고 모델 로드
    include_once CF_MODEL_PATH . '/campaign/ad.model.php';
    $ad_model = new AdModel();
    
    // 페이징 설정
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $items_per_page = isset($_GET['items_per_page']) ? intval($_GET['items_per_page']) : 10;
    
    // 정렬 설정
    $sort_field = isset($_GET['sort_field']) ? trim($_GET['sort_field']) : 'created_at';
    $sort_order = isset($_GET['sort_order']) ? trim($_GET['sort_order']) : 'DESC';
    
    // 검색 파라미터
    $search_params = array(
        'page' => $page,
        'items_per_page' => $items_per_page,
        'sort_field' => $sort_field,
        'sort_order' => $sort_order
    );
    
    // 광고 목록 가져오기
    $ad_data = $ad_model->get_ads_by_campaign($campaign_id, $search_params);
    $ads = $ad_data['ads'];
    $total_ad_count = $ad_data['total_count'];
    $total_ad_pages = $ad_data['total_pages'];
} elseif ($current_tab == 'stats') {
    // 일별 통계 가져오기 (제한된 범위)
    $daily_stats = $campaign_model->get_daily_statistics($campaign_id, $start_date, $end_date);
} elseif ($current_tab == 'conversions') {
    // 전환 모델 로드
    include_once CF_MODEL_PATH . '/campaign/conversion.model.php';
    $conversion_model = new ConversionModel();
    
    // 페이징 설정
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $items_per_page = isset($_GET['items_per_page']) ? intval($_GET['items_per_page']) : 10;
    
    try {
        // 전환 스크립트 목록
        $conversion_scripts = $conversion_model->get_conversion_scripts($campaign_id);
        
        // 전환 이벤트 가져오기
        $conversions = $conversion_model->get_conversion_events($campaign_id, $items_per_page, ($page - 1) * $items_per_page);
        $conversion_count = $conversion_model->count_conversion_events($campaign_id);
        $total_conversion_pages = ceil($conversion_count / $items_per_page);
    } catch (Exception $e) {
        error_log('전환 데이터 로드 중 오류 발생: ' . $e->getMessage());
        $conversion_scripts = array();
        $conversions = array();
        $conversion_count = 0;
        $total_conversion_pages = 1;
    }
}

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <!-- 페이지 제목 -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-bullhorn fa-fw"></i> <?php echo $campaign['name']; ?>
        </h1>
        <div>
            <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_edit.php?id=<?php echo $campaign_id; ?>" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
                <i class="fas fa-edit fa-sm text-white-50"></i> 캠페인 수정
            </a>
            <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_list.php" class="d-none d-sm-inline-block btn btn-secondary shadow-sm">
                <i class="fas fa-list fa-sm text-white-50"></i> 목록으로
            </a>
        </div>
    </div>

    <!-- 캠페인 기본 정보 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">캠페인 정보</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="statusDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="badge badge-<?php echo getStatusClass($campaign['status']); ?>"><?php echo $campaign['status']; ?></span>
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="statusDropdown">
                    <div class="dropdown-header">상태 변경:</div>
                    <a class="dropdown-item change-status" href="#" data-id="<?php echo $campaign_id; ?>" data-status="활성">활성</a>
                    <a class="dropdown-item change-status" href="#" data-id="<?php echo $campaign_id; ?>" data-status="일시중지">일시중지</a>
                    <a class="dropdown-item change-status" href="#" data-id="<?php echo $campaign_id; ?>" data-status="비활성">비활성</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger delete-campaign" href="#" data-id="<?php echo $campaign_id; ?>" data-name="<?php echo $campaign['name']; ?>">
                        <i class="fas fa-trash fa-sm fa-fw"></i> 캠페인 삭제
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="120">기간</th>
                            <td>
                                <?php echo $campaign['start_date']; ?>
                                <?php if ($campaign['end_date']): ?> ~ <?php echo $campaign['end_date']; ?><?php else: ?> ~ 무기한<?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>생성일</th>
                            <td><?php echo date("Y-m-d H:i", strtotime($campaign['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>설명</th>
                            <td><?php echo nl2br($campaign['description']); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="120">예산</th>
                            <td>
                                <?php if ($campaign['budget'] > 0): ?>총 예산: <?php echo number_format($campaign['budget']); ?>원<br><?php endif; ?>
                                <?php if ($campaign['daily_budget'] > 0): ?>일일 예산: <?php echo number_format($campaign['daily_budget']); ?>원<?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>목표 CPA</th>
                            <td><?php echo $campaign['cpa_goal'] ? number_format($campaign['cpa_goal']).'원' : '설정 없음'; ?></td>
                        </tr>
                        <tr>
                            <th>캠페인 해시</th>
                            <td><code><?php echo $campaign['campaign_hash']; ?></code></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 탭 메뉴 -->
    <ul class="nav nav-tabs" id="campaignTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?php echo $current_tab == 'overview' ? 'active' : ''; ?>" href="?id=<?php echo $campaign_id; ?>&tab=overview">
                <i class="fas fa-chart-line fa-fw"></i> 개요
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_tab == 'ad_groups' ? 'active' : ''; ?>" href="?id=<?php echo $campaign_id; ?>&tab=ad_groups">
                <i class="fas fa-layer-group fa-fw"></i> 광고 그룹
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_tab == 'ad_materials' ? 'active' : ''; ?>" href="?id=<?php echo $campaign_id; ?>&tab=ad_materials">
                <i class="fas fa-ad fa-fw"></i> 광고 소재
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_tab == 'stats' ? 'active' : ''; ?>" href="?id=<?php echo $campaign_id; ?>&tab=stats">
                <i class="fas fa-chart-bar fa-fw"></i> 상세 통계
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_tab == 'conversions' ? 'active' : ''; ?>" href="?id=<?php echo $campaign_id; ?>&tab=conversions">
                <i class="fas fa-check-circle fa-fw"></i> 전환
            </a>
        </li>
    </ul>

    <div class="tab-content p-0" id="campaignTabContent">
        <?php if ($current_tab == 'overview'): ?>
        <!-- 개요 탭 -->
        <div class="tab-pane fade show active">
            <!-- 통계 요약 -->
            <div class="row mt-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">노출 수</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($campaign['statistics']['impressions']); ?></div>
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
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">클릭 수</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($campaign['statistics']['clicks']); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-mouse-pointer fa-2x text-gray-300"></i>
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
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">전환 수</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($campaign['statistics']['conversions']); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">광고비</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($campaign['statistics']['cost']); ?>원</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-won-sign fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 성과 지표 카드 -->
            <div class="row">
                <div class="col-xl-6 col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">성과 지표</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="metric-item">
                                        <span class="metric-label">CTR(클릭률)</span>
                                        <span class="metric-value"><?php echo number_format($campaign['statistics']['ctr'], 2); ?>%</span>
                                        <div class="text-xs text-muted">클릭 수 / 노출 수 × 100</div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="metric-item">
                                        <span class="metric-label">CPC(클릭당 비용)</span>
                                        <span class="metric-value"><?php echo number_format($campaign['statistics']['cpc']); ?>원</span>
                                        <div class="text-xs text-muted">총 비용 / 클릭 수</div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="metric-item">
                                        <span class="metric-label">CPA(전환당 비용)</span>
                                        <span class="metric-value"><?php echo $campaign['statistics']['conversions'] > 0 ? number_format($campaign['statistics']['cpa']) : '-'; ?>원</span>
                                        <div class="text-xs text-muted">총 비용 / 전환 수</div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="metric-item">
                                        <span class="metric-label">ROAS(광고 수익률)</span>
                                        <span class="metric-value"><?php echo number_format($campaign['statistics']['roas'], 2); ?>%</span>
                                        <div class="text-xs text-muted">전환 가치 / 총 비용 × 100</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">통계 요약</h6>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-12">
                                    <div class="text-center mb-3">
                                        <div class="badge badge-primary p-2 mb-2">조회 기간: <?php echo $selected_range['label']; ?></div>
                                        <div class="text-xs text-muted"><?php echo $selected_range['start_date']; ?> ~ <?php echo $selected_range['end_date']; ?></div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="text-center">
                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="periodDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                기간 변경
                                            </button>
                                            <div class="dropdown-menu shadow animated--fade-in" aria-labelledby="periodDropdown">
                                                <?php foreach ($date_ranges as $range_key => $range_data): ?>
                                                    <a class="dropdown-item <?php echo $stat_range == $range_key ? 'active' : ''; ?>" href="?id=<?php echo $campaign_id; ?>&tab=overview&stat_range=<?php echo $range_key; ?>">
                                                        <?php echo $range_data['label']; ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 최근 전환 이벤트 -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">최근 전환 이벤트</h6>
                    <a href="?id=<?php echo $campaign_id; ?>&tab=conversions" class="btn btn-sm btn-primary">모두 보기</a>
                </div>
                <div class="card-body">
                    <?php
                    // 최근 전환 이벤트 가져오기 (최대 5개)
                    include_once CF_MODEL_PATH . '/campaign/conversion.model.php';
                    $conversion_model = new ConversionModel();
                    $recent_conversions = $conversion_model->get_conversion_events($campaign_id, 5, 0);
                    ?>
                    
                    <?php if (empty($recent_conversions)): ?>
                        <div class="text-center py-3">
                            <p class="mb-0 text-muted">아직 전환 이벤트가 없습니다.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>이벤트 유형</th>
                                        <th>발생 시간</th>
                                        <th>디바이스</th>
                                        <th>전환 가치</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_conversions as $conv): ?>
                                        <tr>
                                            <td><span class="badge badge-info"><?php echo $conv['event_name']; ?></span></td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($conv['event_time'])); ?></td>
                                            <td><?php echo ucfirst($conv['device_type']); ?></td>
                                            <td><?php echo number_format($conv['conversion_value']); ?>원</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($current_tab == 'ad_groups'): ?>
        <!-- 광고 그룹 탭 -->
        <div class="tab-pane fade show active">
            <div class="card shadow mb-4 mt-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">광고 그룹 목록</h6>
                    <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_add.php?campaign_id=<?php echo $campaign_id; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus fa-sm"></i> 광고 그룹 추가
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($ad_groups)): ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-layer-group fa-4x text-gray-300"></i>
                            </div>
                            <p class="lead text-gray-800">등록된 광고 그룹이 없습니다.</p>
                            <p class="text-gray-500">새 광고 그룹을 만들어 광고를 구성해보세요.</p>
                            <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_add.php?campaign_id=<?php echo $campaign_id; ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> 광고 그룹 추가하기
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- 날짜 선택기 -->
                        <div class="form-group mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">조회 기간</span>
                                        </div>
                                        <select id="statRange" class="form-control">
                                            <?php foreach ($date_ranges as $range_key => $range_data): ?>
                                                <option value="<?php echo $range_key; ?>" <?php echo ($stat_range == $range_key) ? 'selected' : ''; ?>>
                                                    <?php echo $range_data['label']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">시작일</span>
                                                </div>
                                                <input type="date" id="customStartDate" class="form-control" value="<?php echo $start_date; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">종료일</span>
                                                </div>
                                                <input type="date" id="customEndDate" class="form-control" value="<?php echo $end_date; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" id="applyCustomDateRange" class="btn btn-primary btn-block">적용</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 광고 그룹 리스트 및 통계 -->
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-hover" id="adGroupTable">
                                <thead>
                                    <tr>
                                        <th width="3%"><input type="checkbox" id="checkAllAdGroups"></th>
                                        <th width="20%">광고 그룹명</th>
                                        <th width="10%">상태</th>
                                        <th width="12%">노출 수</th>
                                        <th width="12%">클릭 수</th>
                                        <th width="12%">전환 수</th>
                                        <th width="12%">CPA</th>
                                        <th width="15%">관리</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ad_groups as $ad_group): ?>
                                    <tr data-id="<?php echo $ad_group['id']; ?>">
                                        <td><input type="checkbox" class="ad-group-check" value="<?php echo $ad_group['id']; ?>"></td>
                                        <td>
                                            <a href="#collapse_<?php echo $ad_group['id']; ?>" class="font-weight-bold" data-toggle="collapse" role="button" aria-expanded="false">
                                                <?php echo $ad_group['name']; ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo getStatusClass($ad_group['status']); ?>"><?php echo $ad_group['status']; ?></span>
                                        </td>
                                        <td class="text-right"><?php echo number_format($ad_group['statistics']['impressions']); ?></td>
                                        <td class="text-right"><?php echo number_format($ad_group['statistics']['clicks']); ?></td>
                                        <td class="text-right"><?php echo number_format($ad_group['statistics']['conversions']); ?></td>
                                        <td class="text-right">
                                            <?php if ($ad_group['statistics']['conversions'] > 0): ?>
                                                <?php echo number_format($ad_group['statistics']['cpa']); ?>원
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_view.php?id=<?php echo $ad_group['id']; ?>" class="btn btn-info" title="상세보기">
                                                    <i class="fas fa-search"></i>
                                                </a>
                                                <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_edit.php?id=<?php echo $ad_group['id']; ?>" class="btn btn-primary" title="수정">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-warning toggle-status" data-id="<?php echo $ad_group['id']; ?>" data-status="<?php echo $ad_group['status'] == '활성' ? '비활성' : '활성'; ?>" title="상태 변경">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger delete-ad-group" data-id="<?php echo $ad_group['id']; ?>" data-name="<?php echo $ad_group['name']; ?>" title="삭제">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="8" class="p-0">
                                            <div class="collapse" id="collapse_<?php echo $ad_group['id']; ?>">
                                                <div class="card card-body">
                                                    <div class="row">
                                                        <!-- 성과 지표 요약 -->
                                                        <div class="col-md-12">
                                                            <h6 class="font-weight-bold text-primary mb-3">성과 지표</h6>
                                                            <div class="row">
                                                                <div class="col-md-3">
                                                                    <div class="card mb-3">
                                                                        <div class="card-body p-3">
                                                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">클릭률 (CTR)</div>
                                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($ad_group['statistics']['ctr'], 2); ?>%</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="card mb-3">
                                                                        <div class="card-body p-3">
                                                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">클릭당 비용 (CPC)</div>
                                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($ad_group['statistics']['cpc']); ?>원</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="card mb-3">
                                                                        <div class="card-body p-3">
                                                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">전환율</div>
                                                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                                                <?php if ($ad_group['statistics']['clicks'] > 0): ?>
                                                                                    <?php echo number_format(($ad_group['statistics']['conversions'] / $ad_group['statistics']['clicks']) * 100, 2); ?>%
                                                                                <?php else: ?>
                                                                                    0.00%
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="card mb-3">
                                                                        <div class="card-body p-3">
                                                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">광고 수익률 (ROAS)</div>
                                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($ad_group['statistics']['roas'], 2); ?>%</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- 그래프 영역 -->
                                                        <div class="col-md-12 mt-3">
                                                            <ul class="nav nav-tabs" role="tablist">
                                                                <li class="nav-item">
                                                                    <a class="nav-link active" id="daily-tab-<?php echo $ad_group['id']; ?>" data-toggle="tab" href="#daily-<?php echo $ad_group['id']; ?>" role="tab">일별 추이</a>
                                                                </li>
                                                                <li class="nav-item">
                                                                    <a class="nav-link" id="hourly-tab-<?php echo $ad_group['id']; ?>" data-toggle="tab" href="#hourly-<?php echo $ad_group['id']; ?>" role="tab">시간별 추이</a>
                                                                </li>
                                                            </ul>
                                                            <div class="tab-content mt-3">
                                                                <div class="tab-pane fade show active" id="daily-<?php echo $ad_group['id']; ?>" role="tabpanel">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="card">
                                                                                <div class="card-header py-2 px-3">
                                                                                    <h6 class="m-0 font-weight-bold text-primary">일별 노출 및 클릭</h6>
                                                                                </div>
                                                                                <div class="card-body p-2">
                                                                                    <canvas id="dailyClicksChart_<?php echo $ad_group['id']; ?>" height="250"></canvas>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="card">
                                                                                <div class="card-header py-2 px-3">
                                                                                    <h6 class="m-0 font-weight-bold text-primary">일별 전환 및 CPA</h6>
                                                                                </div>
                                                                                <div class="card-body p-2">
                                                                                    <canvas id="dailyConversionsChart_<?php echo $ad_group['id']; ?>" height="250"></canvas>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="tab-pane fade" id="hourly-<?php echo $ad_group['id']; ?>" role="tabpanel">
                                                                    <div class="row">
                                                                        <div class="col-md-12 mb-3">
                                                                            <div class="card">
                                                                                <div class="card-header py-2 px-3">
                                                                                    <h6 class="m-0 font-weight-bold text-primary">시간별 통합 통계</h6>
                                                                                </div>
                                                                                <div class="card-body p-2">
                                                                                    <canvas id="hourlyStatsChart_<?php echo $ad_group['id']; ?>" height="250"></canvas>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <div class="col-md-6">
                                                                            <div class="card">
                                                                                <div class="card-header py-2 px-3">
                                                                                    <h6 class="m-0 font-weight-bold text-primary">시간별 노출 및 클릭</h6>
                                                                                </div>
                                                                                <div class="card-body p-2">
                                                                                    <canvas id="hourlyClicksChart_<?php echo $ad_group['id']; ?>" height="250"></canvas>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="card">
                                                                                <div class="card-header py-2 px-3">
                                                                                    <h6 class="m-0 font-weight-bold text-primary">시간별 전환 및 CPA</h6>
                                                                                </div>
                                                                                <div class="card-body p-2">
                                                                                    <canvas id="hourlyConversionsChart_<?php echo $ad_group['id']; ?>" height="250"></canvas>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- 일괄 작업 버튼 -->
                        <div class="mb-3 mt-3">
                            <button type="button" id="bulkActivateAdGroups" class="btn btn-success btn-sm"><i class="fas fa-play"></i> 일괄 활성화</button>
                            <button type="button" id="bulkPauseAdGroups" class="btn btn-warning btn-sm"><i class="fas fa-pause"></i> 일괄 일시중지</button>
                            <button type="button" id="bulkDeactivateAdGroups" class="btn btn-danger btn-sm"><i class="fas fa-stop"></i> 일괄 비활성화</button>
                        </div>
                        
                        <!-- 페이징 -->
                        <?php if ($total_pages > 1): ?>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <div class="dataTables_info">
                                    전체 <?php echo number_format($total_count); ?>개 중 <?php echo number_format(($page - 1) * $items_per_page + 1); ?>-<?php echo number_format(min($page * $items_per_page, $total_count)); ?>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-7">
                                <div class="dataTables_paginate paging_simple_numbers">
                                    <ul class="pagination">
                                        <?php
                                        $start_page = max(1, $page - 5);
                                        $end_page = min($total_pages, $page + 5);
                                        
                                        // 이전 버튼
                                        if ($page > 1) {
                                            echo '<li class="paginate_button page-item previous"><a href="?id='.$campaign_id.'&tab=ad_groups&page='.($page-1).'&stat_range='.$stat_range.'" class="page-link">이전</a></li>';
                                        }
                                        
                                        // 페이지 번호
                                        for ($i = $start_page; $i <= $end_page; $i++) {
                                            $active = $i == $page ? 'active' : '';
                                            echo '<li class="paginate_button page-item '.$active.'"><a href="?id='.$campaign_id.'&tab=ad_groups&page='.$i.'&stat_range='.$stat_range.'" class="page-link">'.$i.'</a></li>';
                                        }
                                        
                                        // 다음 버튼
                                        if ($page < $total_pages) {
                                            echo '<li class="paginate_button page-item next"><a href="?id='.$campaign_id.'&tab=ad_groups&page='.($page+1).'&stat_range='.$stat_range.'" class="page-link">다음</a></li>';
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

       <?php if ($current_tab == 'ad_materials'): ?>
       <!-- 광고 소재 탭 -->
       <div class="tab-pane fade show active">
           <div class="card shadow mb-4 mt-4">
               <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                   <h6 class="m-0 font-weight-bold text-primary">광고 소재 목록</h6>
                   <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_material_add.php?campaign_id=<?php echo $campaign_id; ?>" class="btn btn-primary btn-sm">
                       <i class="fas fa-plus fa-sm"></i> 광고 소재 추가
                   </a>
               </div>
               <div class="card-body">
                   <?php if (empty($ads)): ?>
                       <div class="text-center py-5">
                           <div class="mb-3">
                               <i class="fas fa-ad fa-4x text-gray-300"></i>
                           </div>
                           <p class="lead text-gray-800">등록된 광고 소재가 없습니다.</p>
                           <p class="text-gray-500">새 광고 소재를 만들어 캠페인을 시작해보세요.</p>
                           <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_material_add.php?campaign_id=<?php echo $campaign_id; ?>" class="btn btn-primary">
                               <i class="fas fa-plus"></i> 광고 소재 추가하기
                           </a>
                       </div>
                   <?php else: ?>
                       <div class="table-responsive">
                           <table class="table table-bordered" id="adMaterialTable" width="100%" cellspacing="0">
                               <thead>
                                   <tr>
                                       <th width="5%"><input type="checkbox" id="checkAllAds"></th>
                                       <th width="25%">광고명</th>
                                       <th width="15%">광고 그룹</th>
                                       <th width="10%">상태</th>
                                       <th width="10%">타입</th>
                                       <th width="10%">노출</th>
                                       <th width="10%">클릭</th>
                                       <th width="15%">관리</th>
                                   </tr>
                               </thead>
                               <tbody>
                                   <?php foreach ($ads as $ad): ?>
                                   <tr>
                                       <td><input type="checkbox" class="ad-material-check" value="<?php echo $ad['id']; ?>"></td>
                                       <td>
                                           <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_material_view.php?id=<?php echo $ad['id']; ?>" class="font-weight-bold text-primary">
                                               <?php echo $ad['ad_name']; ?>
                                           </a>
                                           <br>
                                           <small class="text-muted">생성일: <?php echo date("Y-m-d", strtotime($ad['created_at'])); ?></small>
                                       </td>
                                       <td><?php echo isset($ad['ad_group_name']) ? $ad['ad_group_name'] : ''; ?></td>
                                       <td>
                                           <?php
                                           $status_class = '';
                                           switch ($ad['status']) {
                                               case '활성':
                                                   $status_class = 'success';
                                                   break;
                                               case '비활성':
                                                   $status_class = 'danger';
                                                   break;
                                               case '일시중지':
                                                   $status_class = 'warning';
                                                   break;
                                           }
                                           ?>
                                           <span class="badge badge-<?php echo $status_class; ?>"><?php echo $ad['status']; ?></span>
                                       </td>
                                       <td><?php echo $ad['ad_type']; ?></td>
                                       <td><?php echo isset($ad['statistics']['impressions']) ? number_format($ad['statistics']['impressions']) : '0'; ?></td>
                                       <td><?php echo isset($ad['statistics']['clicks']) ? number_format($ad['statistics']['clicks']) : '0'; ?></td>
                                       <td>
                                           <div class="btn-group btn-group-sm">
                                               <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_material_view.php?id=<?php echo $ad['id']; ?>" class="btn btn-info" title="상세보기">
                                                   <i class="fas fa-search"></i>
                                               </a>
                                               <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_material_edit.php?id=<?php echo $ad['id']; ?>" class="btn btn-primary" title="수정">
                                                   <i class="fas fa-edit"></i>
                                               </a>
                                               <button type="button" class="btn btn-warning toggle-status" data-id="<?php echo $ad['id']; ?>" data-status="<?php echo $ad['status'] == '활성' ? '비활성' : '활성'; ?>" title="상태 변경">
                                                   <i class="fas fa-sync-alt"></i>
                                               </button>
                                               <button type="button" class="btn btn-danger delete-ad" data-id="<?php echo $ad['id']; ?>" data-name="<?php echo $ad['ad_name']; ?>" title="삭제">
                                                   <i class="fas fa-trash"></i>
                                               </button>
                                           </div>
                                       </td>
                                   </tr>
                                   <?php endforeach; ?>
                               </tbody>
                           </table>
                       </div>
                       
                       <!-- 일괄 작업 버튼 -->
                       <div class="mb-3">
                           <button type="button" id="bulkActivateAds" class="btn btn-success btn-sm"><i class="fas fa-play"></i> 일괄 활성화</button>
                           <button type="button" id="bulkPauseAds" class="btn btn-warning btn-sm"><i class="fas fa-pause"></i> 일괄 일시중지</button>
                           <button type="button" id="bulkDeactivateAds" class="btn btn-danger btn-sm"><i class="fas fa-stop"></i> 일괄 비활성화</button>
                       </div>
                       
                       <!-- 페이징 -->
                       <?php if ($total_ad_pages > 1): ?>
                       <div class="row">
                           <div class="col-sm-12 col-md-5">
                               <div class="dataTables_info">
                                   전체 <?php echo number_format($total_ad_count); ?>개 중 <?php echo number_format(($page - 1) * $items_per_page + 1); ?>-<?php echo number_format(min($page * $items_per_page, $total_ad_count)); ?>
                               </div>
                           </div>
                           <div class="col-sm-12 col-md-7">
                               <div class="dataTables_paginate paging_simple_numbers">
                                   <ul class="pagination">
                                       <?php
                                       $start_page = max(1, $page - 5);
                                       $end_page = min($total_ad_pages, $page + 5);
                                       
                                       // 이전 버튼
                                       if ($page > 1) {
                                           echo '<li class="paginate_button page-item previous"><a href="?id='.$campaign_id.'&tab=ad_materials&page='.($page-1).'&sort_field='.$sort_field.'&sort_order='.$sort_order.'" class="page-link">이전</a></li>';
                                       }
                                       
                                       // 페이지 번호
                                       for ($i = $start_page; $i <= $end_page; $i++) {
                                           $active = $i == $page ? 'active' : '';
                                           echo '<li class="paginate_button page-item '.$active.'"><a href="?id='.$campaign_id.'&tab=ad_materials&page='.$i.'&sort_field='.$sort_field.'&sort_order='.$sort_order.'" class="page-link">'.$i.'</a></li>';
                                       }
                                       
                                       // 다음 버튼
                                       if ($page < $total_ad_pages) {
                                           echo '<li class="paginate_button page-item next"><a href="?id='.$campaign_id.'&tab=ad_materials&page='.($page+1).'&sort_field='.$sort_field.'&sort_order='.$sort_order.'" class="page-link">다음</a></li>';
                                       }
                                       ?>
                                   </ul>
                               </div>
                           </div>
                       </div>
                       <?php endif; ?>
                   <?php endif; ?>
               </div>
           </div>
       </div>
       <?php endif; ?>

       <?php if ($current_tab == 'stats'): ?>
       <!-- 통계 탭 -->
       <div class="tab-pane fade show active">
           <div class="card shadow mb-4 mt-4">
               <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                   <h6 class="m-0 font-weight-bold text-primary">일별 통계</h6>
                   <div class="dropdown no-arrow">
                       <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                           <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                       </a>
                       <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                           <div class="dropdown-header">통계 기간:</div>
                           <?php foreach ($date_ranges as $range_key => $range_data): ?>
                           <a class="dropdown-item <?php echo $stat_range == $range_key ? 'active' : ''; ?>" href="?id=<?php echo $campaign_id; ?>&tab=stats&stat_range=<?php echo $range_key; ?>"><?php echo $range_data['label']; ?></a>
                           <?php endforeach; ?>
                           <div class="dropdown-divider"></div>
                           <a class="dropdown-item" href="#" id="exportStatsCSV">CSV로 내보내기</a>
                       </div>
                   </div>
               </div>
               <div class="card-body">
                   <!-- 통계 요약 -->
                   <div class="text-center mb-4">
                       <span class="badge badge-primary p-2 mb-2">조회 기간: <?php echo $selected_range['label']; ?></span>
                       <div class="text-xs text-muted"><?php echo $selected_range['start_date']; ?> ~ <?php echo $selected_range['end_date']; ?></div>
                   </div>
                   
                   <!-- 커스텀 날짜 선택 -->
                   <div class="stats-date-selector mb-4">
                       <form method="get" action="" class="form-inline justify-content-center">
                           <input type="hidden" name="id" value="<?php echo $campaign_id; ?>">
                           <input type="hidden" name="tab" value="stats">
                           <div class="form-group mr-2">
                               <label for="start_date" class="mr-2">시작일:</label>
                               <input type="date" id="start_date" name="start_date" class="form-control form-control-sm" value="<?php echo $start_date; ?>">
                           </div>
                           <div class="form-group mr-2">
                               <label for="end_date" class="mr-2">종료일:</label>
                               <input type="date" id="end_date" name="end_date" class="form-control form-control-sm" value="<?php echo $end_date; ?>">
                           </div>
                           <button type="submit" class="btn btn-primary btn-sm">검색</button>
                       </form>
                   </div>
                   
                   <!-- 통계 표시 -->
                   <div class="table-responsive">
                       <table class="table table-bordered table-hover" id="statsTable">
                           <thead>
                               <tr>
                                   <th>날짜</th>
                                   <th>노출 수</th>
                                   <th>클릭 수</th>
                                   <th>CTR</th>
                                   <th>비용</th>
                                   <th>CPC</th>
                                   <th>전환 수</th>
                                   <th>CPA</th>
                                   <th>전환 가치</th>
                                   <th>ROAS</th>
                               </tr>
                           </thead>
                           <tbody>
                               <?php if (empty($daily_stats)): ?>
                               <tr>
                                   <td colspan="10" class="text-center">해당 기간에 데이터가 없습니다.</td>
                               </tr>
                               <?php else: ?>
                                   <?php foreach ($daily_stats as $stat): ?>
                                   <tr>
                                       <td><?php echo $stat['date']; ?></td>
                                       <td><?php echo number_format($stat['impressions']); ?></td>
                                       <td><?php echo number_format($stat['clicks']); ?></td>
                                       <td><?php echo number_format($stat['ctr'], 2); ?>%</td>
                                       <td><?php echo number_format($stat['cost']); ?>원</td>
                                       <td><?php echo $stat['clicks'] > 0 ? number_format($stat['cost'] / $stat['clicks']) : '0'; ?>원</td>
                                       <td><?php echo number_format($stat['conversions']); ?></td>
                                       <td><?php echo $stat['conversions'] > 0 ? number_format($stat['cost'] / $stat['conversions']) : '0'; ?>원</td>
                                       <td><?php echo number_format($stat['conversion_value']); ?>원</td>
                                       <td><?php echo $stat['cost'] > 0 ? number_format(($stat['conversion_value'] / $stat['cost']) * 100, 2) : '0.00'; ?>%</td>
                                   </tr>
                                   <?php endforeach; ?>
                               <?php endif; ?>
                           </tbody>
                       </table>
                   </div>
               </div>
           </div>
       </div>
       <?php endif; ?>

       <?php if ($current_tab == 'conversions'): ?>
        <!-- 전환 탭 -->
        <div class="tab-pane fade show active">
            <!-- 전환 스크립트 카드 -->
            <div class="card shadow mb-4 mt-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">전환 스크립트</h6>
                    <a href="<?php echo CF_CAMPAIGN_URL; ?>/conversion_script_add.php?campaign_id=<?php echo $campaign_id; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus fa-sm"></i> 스크립트 생성
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($conversion_scripts)): ?>
                        <div class="text-center py-3">
                            <div class="mb-3">
                                <i class="fas fa-code fa-3x text-gray-300"></i>
                            </div>
                            <p class="text-gray-500 mb-3">아직 생성된 전환 스크립트가 없습니다.</p>
                            <p class="text-gray-700">전환 스크립트를 생성하여 웹사이트에 설치하면<br>구매, 회원가입 등의 전환 이벤트를 추적할 수 있습니다.</p>
                            <a href="<?php echo CF_CAMPAIGN_URL; ?>/conversion_script_add.php?campaign_id=<?php echo $campaign_id; ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> 전환 스크립트 생성하기
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>전환 유형</th>
                                        <th>생성일</th>
                                        <th>관리</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($conversion_scripts as $script): ?>
                                    <tr>
                                        <td><span class="badge badge-info"><?php echo $script['conversion_type']; ?></span></td>
                                        <td><?php echo date('Y-m-d', strtotime($script['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo CF_CAMPAIGN_URL; ?>/conversion_script_view.php?id=<?php echo $script['id']; ?>" class="btn btn-info" title="상세보기">
                                                    <i class="fas fa-search"></i>
                                                </a>
                                                <a href="<?php echo CF_CAMPAIGN_URL; ?>/conversion_script_edit.php?id=<?php echo $script['id']; ?>" class="btn btn-primary" title="수정">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger delete-script" data-id="<?php echo $script['id']; ?>" data-type="<?php echo $script['conversion_type']; ?>" title="삭제">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 전환 이벤트 카드 -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">전환 이벤트 목록</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink2">
                            <div class="dropdown-header">액션:</div>
                            <a class="dropdown-item" href="#" id="exportConversionsCSV">CSV로 내보내기</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($conversions)): ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-check-circle fa-3x text-gray-300"></i>
                            </div>
                            <p class="text-gray-500 mb-0">아직 기록된 전환 이벤트가 없습니다.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="conversionTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>이벤트 유형</th>
                                        <th>발생 시간</th>
                                        <th>디바이스</th>
                                        <th>전환 가치</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($conversions as $conv): ?>
                                    <tr>
                                        <td><?php echo $conv['id']; ?></td>
                                        <td>
                                            <?php if (isset($conv['event_name'])): ?>
                                                <span class="badge badge-info"><?php echo $conv['event_name']; ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">미분류</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo isset($conv['event_time']) ? date('Y-m-d H:i', strtotime($conv['event_time'])) : ''; ?></td>
                                        <td><?php echo isset($conv['device_type']) ? ucfirst($conv['device_type']) : '알 수 없음'; ?></td>
                                        <td><?php echo isset($conv['conversion_value']) ? number_format($conv['conversion_value']) : '0'; ?>원</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- 페이징 -->
                        <?php if ($total_conversion_pages > 1): ?>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <div class="dataTables_info">
                                    전체 <?php echo number_format($conversion_count); ?>개 중 <?php echo number_format(($page - 1) * $items_per_page + 1); ?>-<?php echo number_format(min($page * $items_per_page, $conversion_count)); ?>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-7">
                                <div class="dataTables_paginate paging_simple_numbers">
                                    <ul class="pagination">
                                        <?php
                                        $start_page = max(1, $page - 5);
                                        $end_page = min($total_conversion_pages, $page + 5);
                                        
                                        // 이전 버튼
                                        if ($page > 1) {
                                            echo '<li class="paginate_button page-item previous"><a href="?id='.$campaign_id.'&tab=conversions&page='.($page-1).'" class="page-link">이전</a></li>';
                                        }
                                        
                                        // 페이지 번호
                                        for ($i = $start_page; $i <= $end_page; $i++) {
                                            $active = $i == $page ? 'active' : '';
                                            echo '<li class="paginate_button page-item '.$active.'"><a href="?id='.$campaign_id.'&tab=conversions&page='.$i.'" class="page-link">'.$i.'</a></li>';
                                        }
                                        
                                        // 다음 버튼
                                        if ($page < $total_conversion_pages) {
                                            echo '<li class="paginate_button page-item next"><a href="?id='.$campaign_id.'&tab=conversions&page='.($page+1).'" class="page-link">다음</a></li>';
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
   </div>
</div>

<!-- 상태 변경 모달 -->
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
   <div class="modal-dialog" role="document">
       <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title" id="statusModalLabel">상태 변경</h5>
               <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                   <span aria-hidden="true">×</span>
               </button>
           </div>
           <div class="modal-body">
               <p><span id="status_item_type">항목</span> "<span id="status_item_name"></span>"의 상태를 변경하시겠습니까?</p>
               <form id="statusForm">
                   <input type="hidden" id="item_id" name="item_id" value="">
                   <input type="hidden" id="item_type" name="item_type" value="">
                   <div class="form-group">
                       <label for="new_status">새 상태</label>
                       <select class="form-control" id="new_status" name="new_status">
                           <option value="활성">활성</option>
                           <option value="비활성">비활성</option>
                           <option value="일시중지">일시중지</option>
                       </select>
                   </div>
               </form>
           </div>
           <div class="modal-footer">
               <button class="btn btn-secondary" type="button" data-dismiss="modal">취소</button>
               <button class="btn btn-primary" id="confirmStatus">변경</button>
           </div>
       </div>
   </div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
   <div class="modal-dialog" role="document">
       <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title" id="deleteModalLabel">삭제 확인</h5>
               <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                   <span aria-hidden="true">×</span>
               </button>
           </div>
           <div class="modal-body">
               <p><span id="delete_item_type">항목</span> "<span id="delete_item_name"></span>"을(를) 정말 삭제하시겠습니까?</p>
               <p class="text-danger">이 작업은 되돌릴 수 없습니다.</p>
               <input type="hidden" id="delete_item_id" value="">
               <input type="hidden" id="delete_item_action" value="">
           </div>
           <div class="modal-footer">
               <button class="btn btn-secondary" type="button" data-dismiss="modal">취소</button>
               <button class="btn btn-danger" id="confirmDelete">삭제</button>
           </div>
       </div>
   </div>
</div>




<!-- 차트 관련 자바스크립트 -->
<script>
$(function() {
    // 날짜 범위 변경 이벤트
    $('#statRange').on('change', function() {
        var rangeValue = $(this).val();
        window.location.href = '?id=<?php echo $campaign_id; ?>&tab=ad_groups&stat_range=' + rangeValue;
    });

    // 커스텀 날짜 범위 적용
    $('#applyCustomDateRange').on('click', function() {
        var startDate = $('#customStartDate').val();
        var endDate = $('#customEndDate').val();
        
        // 날짜 유효성 검사
        if (!startDate || !endDate) {
            alert("시작일과 종료일을 모두 선택해주세요.");
            return;
        }
        
        // 시작일이 종료일보다 나중인 경우
        if (new Date(startDate) > new Date(endDate)) {
            alert("시작일은 종료일보다 이전이어야 합니다.");
            return;
        }
        
        // 90일 초과 검사
        var start = new Date(startDate);
        var end = new Date(endDate);
        var diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
        if (diffDays > 90) {
            alert("조회 기간은 최대 90일까지 가능합니다.");
            return;
        }
        
        // 페이지 이동
        window.location.href = '?id=<?php echo $campaign_id; ?>&tab=ad_groups&start_date=' + startDate + '&end_date=' + endDate;
    });

    // 일별 통계 차트 생성
    <?php foreach ($ad_groups as $ad_group): ?>
    // 일별 클릭/노출 차트
    drawDailyClicksChart(<?php echo $ad_group['id']; ?>, <?php echo json_encode($ad_group['daily_stats']); ?>);
    
    // 일별 전환/CPA 차트
    drawDailyConversionsChart(<?php echo $ad_group['id']; ?>, <?php echo json_encode($ad_group['daily_stats']); ?>);
    <?php endforeach; ?>
    
    // 시간별 데이터 로드
    $('#loadHourlyStats').on('click', function() {
        var selectedDate = $('#statsDate').val();
        
        // 모든 광고 그룹의 시간별 데이터 로딩
        <?php foreach ($ad_groups as $ad_group): ?>
        loadHourlyData(<?php echo $ad_group['id']; ?>, startDate, endDate);
        <?php endforeach; ?>
    });
    
    // 시간별 데이터 로드 함수
    function loadHourlyData(adGroupId, startDate, endDate) {
        // 날짜 유효성 검사
        if (!startDate || !endDate) {
            alert("시작일과 종료일을 모두 선택해주세요.");
            return;
        }
        
        // 시작일이 종료일보다 나중인 경우
        if (new Date(startDate) > new Date(endDate)) {
            alert("시작일은 종료일보다 이전이어야 합니다.");
            return;
        }
        
        $.ajax({
            url: "<?php echo CF_CAMPAIGN_URL; ?>/ajax.action.php",
            type: "POST",
            dataType: "json",
            data: {
                action: "get_hourly_statistics",
                ad_group_id: adGroupId,
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                if (response.success) {
                    // 시간별 차트 업데이트
                    drawHourlyClicksChart(adGroupId, response.data);
                    drawHourlyConversionsChart(adGroupId, response.data);
                    
                    // 해당 탭 활성화
                    $('#hourly-tab-' + adGroupId).tab('show');
                } else {
                    alert("데이터 로딩 중 오류가 발생했습니다: " + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert("서버 통신 중 오류가 발생했습니다.");
                console.error("Ajax 오류:", error);
            }
        });
    }
    
    // 일별 클릭/노출 차트 그리기
    function drawDailyClicksChart(adGroupId, data) {
        var ctx = document.getElementById('dailyClicksChart_' + adGroupId).getContext('2d');
        
        // 데이터 추출
        var dates = [];
        var impressions = [];
        var clicks = [];
        
        for (var i = 0; i < data.length; i++) {
            dates.push(data[i].date);
            impressions.push(data[i].impressions);
            clicks.push(data[i].clicks);
        }
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [
                    {
                        label: '노출 수',
                        data: impressions,
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        pointRadius: 3,
                        pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                        pointBorderColor: 'rgba(78, 115, 223, 1)',
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                        pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        fill: true,
                        yAxisID: 'y-axis-1'
                    },
                    {
                        label: '클릭 수',
                        data: clicks,
                        backgroundColor: 'rgba(28, 200, 138, 0.05)',
                        borderColor: 'rgba(28, 200, 138, 1)',
                        pointRadius: 3,
                        pointBackgroundColor: 'rgba(28, 200, 138, 1)',
                        pointBorderColor: 'rgba(28, 200, 138, 1)',
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: 'rgba(28, 200, 138, 1)',
                        pointHoverBorderColor: 'rgba(28, 200, 138, 1)',
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        fill: false,
                        yAxisID: 'y-axis-2'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxTicksLimit: 7
                        }
                    }],
                    yAxes: [
                        {
                            id: 'y-axis-1',
                            type: 'linear',
                            position: 'left',
                            ticks: {
                                beginAtZero: true,
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            },
                            gridLines: {
                                color: "rgb(234, 236, 244)",
                                zeroLineColor: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineBorderDash: [2]
                            }
                        },
                        {
                            id: 'y-axis-2',
                            type: 'linear',
                            position: 'right',
                            ticks: {
                                beginAtZero: true,
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            },
                            gridLines: {
                                display: false
                            }
                        }
                    ]
                },
                legend: {
                    display: true,
                    position: 'top'
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
                            return datasetLabel + ': ' + tooltipItem.yLabel.toLocaleString();
                        }
                    }
                }
            }
        });
        $('#dailyClicksChart_' + adGroupId).attr('height', '250');
        $('#dailyClicksChart_' + adGroupId).css('height', '250px');
    }
    
    // 일별 전환/CPA 차트 그리기
    function drawDailyConversionsChart(adGroupId, data) {
        var ctx = document.getElementById('dailyConversionsChart_' + adGroupId).getContext('2d');
        
        // 데이터 추출
        var dates = [];
        var conversions = [];
        var cpas = [];
        
        for (var i = 0; i < data.length; i++) {
            dates.push(data[i].date);
            conversions.push(data[i].conversions);
            cpas.push(data[i].cpa);
        }
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [
                    {
                        label: '전환 수',
                        data: conversions,
                        backgroundColor: 'rgba(54, 185, 204, 0.05)',
                        borderColor: 'rgba(54, 185, 204, 1)',
                        pointRadius: 3,
                        pointBackgroundColor: 'rgba(54, 185, 204, 1)',
                        pointBorderColor: 'rgba(54, 185, 204, 1)',
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: 'rgba(54, 185, 204, 1)',
                        pointHoverBorderColor: 'rgba(54, 185, 204, 1)',
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        fill: true,
                        yAxisID: 'y-axis-1'
                    },
                    {
                        label: 'CPA (원)',
                        data: cpas,
                        backgroundColor: 'rgba(246, 194, 62, 0.05)',
                        borderColor: 'rgba(246, 194, 62, 1)',
                        pointRadius: 3,
                        pointBackgroundColor: 'rgba(246, 194, 62, 1)',
                        pointBorderColor: 'rgba(246, 194, 62, 1)',
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: 'rgba(246, 194, 62, 1)',
                        pointHoverBorderColor: 'rgba(246, 194, 62, 1)',
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        fill: false,
                        yAxisID: 'y-axis-2'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxTicksLimit: 7
                        }
                    }],
                    yAxes: [
                        {
                            id: 'y-axis-1',
                            type: 'linear',
                            position: 'left',
                            ticks: {
                                beginAtZero: true,
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            },
                            gridLines: {
                                color: "rgb(234, 236, 244)",
                                zeroLineColor: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineBorderDash: [2]
                            }
                        },
                        {
                            id: 'y-axis-2',
                            type: 'linear',
                            position: 'right',
                            ticks: {
                                beginAtZero: true,
                                callback: function(value) {
                                    return value.toLocaleString() + '원';
                                }
                            },
                            gridLines: {
                                display: false
                            }
                        }
                    ]
                },
                legend: {
                    display: true,
                    position: 'top'
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
                            var value = tooltipItem.yLabel.toLocaleString();
                            if (datasetLabel === 'CPA (원)') {
                                value += '원';
                            }
                            return datasetLabel + ': ' + value;
                        }
                    }
                }
            }
        });
        $('#dailyConversionsChart_' + adGroupId).attr('height', '250');
        $('#dailyConversionsChart_' + adGroupId).css('height', '250px');
    }
    
    // 차트 객체 저장용
    var hourlyClicksCharts = {};
    var hourlyConversionsCharts = {};
    
    // 시간별 클릭/노출 차트 그리기
    function drawHourlyClicksChart(adGroupId, data) {
        console.log('drawHourlyClicksChart 함수 호출됨', adGroupId);
        console.log('데이터:', data);

        
        var canvas = document.getElementById('hourlyClicksChart_' + adGroupId);
    
        if (!canvas) {
            console.error('차트 캔버스를 찾을 수 없음: hourlyClicksChart_' + adGroupId);
            return;
        }

        console.log('캔버스 찾음:', canvas);                
        var ctx = canvas.getContext('2d');
        
        
        // 데이터 추출
        var hours = [];
        var impressions = [];
        var clicks = [];
        
        for (var i = 0; i < data.length; i++) {
            hours.push(data[i].hour + '시');
            impressions.push(data[i].impressions);
            clicks.push(data[i].clicks);
        }
        
        // 기존 차트가 있으면 제거
        if (hourlyClicksCharts[adGroupId]) {
            console.log('기존 차트 제거');
            hourlyClicksCharts[adGroupId].destroy();
        }
        
        hourlyClicksCharts[adGroupId] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: hours,
                datasets: [
                    {
                        label: '노출 수',
                        data: impressions,
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        pointRadius: 3,
                        pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                        pointBorderColor: 'rgba(78, 115, 223, 1)',
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                        pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        fill: true,
                        yAxisID: 'y-axis-1'
                    },
                    {
                        label: '클릭 수',
                        data: clicks,
                        backgroundColor: 'rgba(28, 200, 138, 0.05)',
                        borderColor: 'rgba(28, 200, 138, 1)',
                        pointRadius: 3,
                        pointBackgroundColor: 'rgba(28, 200, 138, 1)',
                        pointBorderColor: 'rgba(28, 200, 138, 1)',
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: 'rgba(28, 200, 138, 1)',
                        pointHoverBorderColor: 'rgba(28, 200, 138, 1)',
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        fill: false,
                        yAxisID: 'y-axis-2'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxTicksLimit: 24
                        }
                    }],
                    yAxes: [
                        {
                            id: 'y-axis-1',
                            type: 'linear',
                            position: 'left',
                            ticks: {
                                beginAtZero: true,
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            },
                            gridLines: {
                                color: "rgb(234, 236, 244)",
                                zeroLineColor: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineBorderDash: [2]
                            }
                        },
                        {
                            id: 'y-axis-2',
                            type: 'linear',
                            position: 'right',
                            ticks: {
                                beginAtZero: true,
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            },
                            gridLines: {
                                display: false
                            }
                        }
                    ]
                },
                legend: {
                    display: true,
                    position: 'top'
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
                            return datasetLabel + ': ' + tooltipItem.yLabel.toLocaleString();
                        }
                    }
                }
            }
        });
        $('#hourlyClicksChart_' + adGroupId).attr('height', '250');
        $('#hourlyClicksChart_' + adGroupId).css('height', '250px');
        console.log('차트 생성 완료');
    
        // 차트 크기 확인
        console.log('캔버스 크기:', canvas.width, 'x', canvas.height);
    }
    
    // 시간별 전환/CPA 차트 그리기
    function drawHourlyConversionsChart(adGroupId, data) {
        var canvas = document.getElementById('hourlyConversionsChart_' + adGroupId);
    
        if (!canvas) {
            console.error('차트 캔버스를 찾을 수 없음: hourlyConversionsChart_' + adGroupId);
            return;
        }
        
        var ctx = document.getElementById('hourlyConversionsChart_' + adGroupId).getContext('2d');
        
        // 데이터 추출
        var hours = [];
        var conversions = [];
        var cpas = [];
        
        for (var i = 0; i < data.length; i++) {
            hours.push(data[i].hour + '시');
            conversions.push(data[i].conversions);
            cpas.push(data[i].cpa);
        }
        
        // 기존 차트가 있으면 제거
        if (hourlyConversionsCharts[adGroupId]) {
            hourlyConversionsCharts[adGroupId].destroy();
        }
        
        hourlyConversionsCharts[adGroupId] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: hours,
                datasets: [
                    {
                        label: '전환 수',
                        data: conversions,
                        backgroundColor: 'rgba(54, 185, 204, 0.05)',
                        borderColor: 'rgba(54, 185, 204, 1)',
                        pointRadius: 3,
                        pointBackgroundColor: 'rgba(54, 185, 204, 1)',
                        pointBorderColor: 'rgba(54, 185, 204, 1)',
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: 'rgba(54, 185, 204, 1)',
                        pointHoverBorderColor: 'rgba(54, 185, 204, 1)',
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        fill: true,
                        yAxisID: 'y-axis-1'
                    },
                    {
                        label: 'CPA (원)',
                        data: cpas,
                        backgroundColor: 'rgba(246, 194, 62, 0.05)',
                        borderColor: 'rgba(246, 194, 62, 1)',
                        pointRadius: 3,
                        pointBackgroundColor: 'rgba(246, 194, 62, 1)',
                        pointBorderColor: 'rgba(246, 194, 62, 1)',
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: 'rgba(246, 194, 62, 1)',
                        pointHoverBorderColor: 'rgba(246, 194, 62, 1)',
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        fill: false,
                        yAxisID: 'y-axis-2'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxTicksLimit: 24
                        }
                    }],
                    yAxes: [
                        {
                            id: 'y-axis-1',
                            type: 'linear',
                            position: 'left',
                            ticks: {
                                beginAtZero: true,
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            },
                            gridLines: {
                                color: "rgb(234, 236, 244)",
                                zeroLineColor: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineBorderDash: [2]
                            }
                        },
                        {
                            id: 'y-axis-2',
                            type: 'linear',
                            position: 'right',
                            ticks: {
                                beginAtZero: true,
                                callback: function(value) {
                                    return value.toLocaleString() + '원';
                                }
                            },
                            gridLines: {
                                display: false
                            }
                        }
                    ]
                },
                legend: {
                    display: true,
                    position: 'top'
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
                            var value = tooltipItem.yLabel.toLocaleString();
                            if (datasetLabel === 'CPA (원)') {
                                value += '원';
                            }
                            return datasetLabel + ': ' + value;
                        }
                    }
                }
            }
        });

        $('#hourlyConversionsChart_' + adGroupId).attr('height', '250');
        $('#hourlyConversionsChart_' + adGroupId).css('height', '250px');
    }
    
    // 광고 그룹 행 클릭 시 상세 정보 표시/숨김
    $('.table').on('click', 'a[data-toggle="collapse"]', function() {
        var targetId = $(this).attr('href');
        
        // 다른 아코디언 닫기
        $('.collapse').not(targetId).collapse('hide');
        
        // 목표한 아코디언 토글
        $(targetId).collapse('toggle');
    });
    
    // 페이지 로드 시 기본으로 첫 번째 광고 그룹의 시간별 데이터 로드
    $(document).ready(function() {
        if ($('#adGroupTable tbody tr').length > 0) {
            var today = new Date().toISOString().split('T')[0];
            $('#statsDate').val(today);            
            var startDate = $('#customStartDate').val();
            var endDate = $('#customEndDate').val();
            
            // 첫 번째 광고 그룹의 ID 가져오기
            var firstAdGroupId = $('#adGroupTable tbody tr:first-child').data('id');
            if (firstAdGroupId) {
                loadHourlyData(firstAdGroupId, startDate, endDate);
            }
        }
    });
});
</script>




<!-- 시간별 통계 데이터 로딩 및 차트 생성 -->
<script>
$(function() {
    // 차트 객체 저장용
    var hourlyCharts = {};
    
    // 시간별 데이터 로딩
    function loadHourlyStats() {
        var startDate = $('#customStartDate').val();
        var endDate = $('#customEndDate').val();

        // 날짜 유효성 검사
        if (!startDate || !endDate) {
            alert("시작일과 종료일을 모두 선택해주세요.");
            return;
        }
        
        // 시작일이 종료일보다 나중인 경우
        if (new Date(startDate) > new Date(endDate)) {
            alert("시작일은 종료일보다 이전이어야 합니다.");
            return;
        }
        
        // 90일 이상 기간 제한
        var timeDiff = Math.abs(new Date(endDate) - new Date(startDate));
        var dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
        if (dayDiff > 90) {
            alert("조회 기간은 최대 90일까지 가능합니다.");
            return;
        }
        
        // 로딩 표시
        $('#loadHourlyStats').html('<i class="fas fa-spinner fa-spin"></i> 로딩 중...').prop('disabled', true);
        
        <?php foreach ($ad_groups as $ad_group): ?>
            $.ajax({
                url: "<?php echo CF_CAMPAIGN_URL; ?>/ajax.action.php",
                type: "POST",
                dataType: "json",
                data: {
                    action: "get_period_statistics",
                    ad_group_id: <?php echo $ad_group['id']; ?>,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(data) {
                    if (data.success) {
                        updateHourlyChart(<?php echo $ad_group['id']; ?>, data.data);
                    } else {
                        alert("데이터 로딩 중 오류가 발생했습니다: " + data.message);
                    }
                },
                error: function() {
                    alert("서버 통신 중 오류가 발생했습니다.");
                },
                complete: function() {
                    // 모든 요청이 완료되면 버튼 상태 복원
                    $('#loadHourlyStats').html('데이터 조회').prop('disabled', false);
                }
            });
        <?php endforeach; ?>
    }
    
    // 시간별/기간별 차트 업데이트 함수
    function updateHourlyChart(adGroupId, hourlyData) {
        // 차트 데이터 준비
        var labels = [];
        var impressions = [];
        var clicks = [];
        var conversions = [];
        
        // 데이터 추출
        $.each(hourlyData, function(index, item) {
            // 날짜 또는 시간 라벨 결정
            var label = item.hour ? (item.hour + '시') : item.date;
            labels.push(label);
            impressions.push(item.impressions);
            clicks.push(item.clicks);
            conversions.push(item.conversions);
        });
        
        // 기존 차트가 있으면 파괴
        if (hourlyCharts[adGroupId]) {
            hourlyCharts[adGroupId].destroy();
        }
        
        // 통합 차트 생성
        var ctx = document.getElementById('hourlyStatsChart_' + adGroupId).getContext('2d');
        hourlyCharts[adGroupId] = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '노출 수',
                        data: impressions,
                        backgroundColor: 'rgba(78, 115, 223, 0.5)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 1,
                        order: 3
                    },
                    {
                        label: '클릭 수',
                        data: clicks,
                        backgroundColor: 'rgba(28, 200, 138, 0.5)',
                        borderColor: 'rgba(28, 200, 138, 1)',
                        borderWidth: 1,
                        order: 2
                    },
                    {
                        label: '전환 수',
                        data: conversions,
                        backgroundColor: 'rgba(246, 194, 62, 0.5)',
                        borderColor: 'rgba(246, 194, 62, 1)',
                        borderWidth: 1,
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxTicksLimit: 24
                        },
                        stacked: false
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        },
                        gridLines: {
                            color: "rgb(234, 236, 244)",
                            zeroLineColor: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        },
                        stacked: false
                    }]
                },
                legend: {
                    display: true,
                    position: 'top'
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
                    displayColors: true,
                    intersect: false,
                    mode: 'index',
                    caretPadding: 10,
                    callbacks: {
                        label: function(tooltipItem, chart) {
                            var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                            return datasetLabel + ': ' + tooltipItem.yLabel.toLocaleString();
                        }
                    }
                }
            }
        });
        
        $('#hourlyStatsChart_' + adGroupId).attr('height', '250');
        $('#hourlyStatsChart_' + adGroupId).css('height', '250px');
    }
    
    // 데이터 로드 버튼 클릭 이벤트
    $('#loadHourlyStats').on('click', function() {
        var startDate = $('#customStartDate').val();
        var endDate = $('#customEndDate').val();
        
        // 모든 광고 그룹의 시간별 데이터 로딩
        <?php foreach ($ad_groups as $ad_group): ?>
        loadHourlyData(<?php echo $ad_group['id']; ?>, startDate, endDate);
        <?php endforeach; ?>
    });
    
    // 기간 선택 드롭다운 클릭 이벤트
    $('.period-select').on('click', function(e) {
        e.preventDefault();
        var period = $(this).data('period');
        var startDate, endDate;
        var today = new Date();
        
        switch (period) {
            case 'today':
                startDate = new Date(today);
                endDate = new Date(today);
                break;
            case 'yesterday':
                startDate = new Date(today);
                startDate.setDate(startDate.getDate() - 1);
                endDate = new Date(startDate);
                break;
            case '7days':
                startDate = new Date(today);
                startDate.setDate(startDate.getDate() - 6); // 오늘 포함해서 7일이므로 6일 전
                endDate = new Date(today);
                break;
            case '30days':
                startDate = new Date(today);
                startDate.setDate(startDate.getDate() - 29); // 오늘 포함해서 30일이므로 29일 전
                endDate = new Date(today);
                break;
            case 'this_month':
                startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                endDate = new Date(today);
                break;
            case 'last_month':
                startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                endDate = new Date(today.getFullYear(), today.getMonth(), 0);
                break;
            default:
                return;
        }
        
        // 날짜 포맷팅 (YYYY-MM-DD)
        function formatDate(date) {
            var year = date.getFullYear();
            var month = (date.getMonth() + 1).toString().padStart(2, '0');
            var day = date.getDate().toString().padStart(2, '0');
            return year + '-' + month + '-' + day;
        }
        
        // 선택된 기간 설정
        $('#startDate').val(formatDate(startDate));
        $('#endDate').val(formatDate(endDate));
        
        // 데이터 로드
        loadHourlyStats();
    });
    
    // 페이지 로드 시 초기 데이터 로드
    $(document).ready(function() {
        <?php if ($tab == "ad_groups") { ?>
        // 차트 라이브러리가 로드되었는지 확인 후 데이터 로드
        if (typeof Chart !== 'undefined') {
            // 최근 30일이 기본 설정
            var today = new Date();
            var thirtyDaysAgo = new Date(today);
            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 29); // 30일 = 오늘 + 29일 전
            
            $('#startDate').val(thirtyDaysAgo.toISOString().split('T')[0]);
            $('#endDate').val(today.toISOString().split('T')[0]);
            
            loadHourlyStats();
        } else {
            console.error('Chart.js 라이브러리가 로드되지 않았습니다.');
        }
        <?php } ?>
    });
    
    // 광고 그룹 토글 이벤트 - 차트 리사이즈 처리
    $('a[data-toggle="collapse"]').on('shown.bs.collapse', function () {
        var targetId = $(this).attr('href');
        var adGroupId = targetId.replace('#adGroupCollapse', '');
        
        // 차트가 있는 경우 리사이즈
        if (hourlyCharts[adGroupId]) {
            hourlyCharts[adGroupId].resize();
        }
    });
});
</script>




<script>
$(function() {
   // 체크박스 전체 선택/해제
   $("#checkAllAdGroups").on("click", function() {
        $(".ad-group-check").prop("checked", $(this).prop("checked"));
    });
   
   $("#checkAllAds").on("click", function() {
       $(".ad-material-check").prop("checked", $(this).prop("checked"));
   });
   
   // 상태 변경 버튼 처리 - 캠페인
   $(".change-status").on("click", function(e) {
       e.preventDefault();
       var campaignId = $(this).data("id");
       var newStatus = $(this).data("status");
       
       $("#item_id").val(campaignId);
       $("#item_type").val("campaign");
       $("#status_item_type").text("캠페인");
       $("#status_item_name").text("<?php echo $campaign['name']; ?>");
       $("#new_status").val(newStatus);
       
       $("#statusModal").modal("show");
   });
   
   // 상태 변경 버튼 처리 - 광고 그룹 및 광고 소재
   $(".toggle-status").on("click", function() {
       var itemId = $(this).data("id");
       var newStatus = $(this).data("status");
       var itemName = $(this).closest("tr").find("a.font-weight-bold").text().trim();
       var itemType = "";
       
       // 버튼이 속한 테이블 ID로 아이템 타입 결정
       if ($(this).closest("table").attr("id") === "adGroupTable") {
           itemType = "ad_group";
           $("#status_item_type").text("광고 그룹");
       } else if ($(this).closest("table").attr("id") === "adMaterialTable") {
           itemType = "ad_material";
           $("#status_item_type").text("광고 소재");
       } else {
           // 기본값 설정
           itemType = "unknown";
           $("#status_item_type").text("항목");
       }
       
       $("#item_id").val(itemId);
       $("#item_type").val(itemType);
       $("#status_item_name").text(itemName);
       $("#new_status").val(newStatus);
       
       $("#statusModal").modal("show");
   });
   
   // 상태 변경 확인
   $("#confirmStatus").on("click", function() {
       var itemId = $("#item_id").val();
       var itemType = $("#item_type").val();
       var newStatus = $("#new_status").val();
       var actionUrl = "";
       var actionData = {};
       
       // 아이템 타입에 따라 액션 URL 및 데이터 설정
       if (itemType === "campaign") {
           actionUrl = "<?php echo CF_CAMPAIGN_URL; ?>/ajax.action.php";
           actionData = {
               action: "change_campaign_status",
               campaign_id: itemId,
               status: newStatus
           };
       } else if (itemType === "ad_group") {
           actionUrl = "<?php echo CF_CAMPAIGN_URL; ?>/ajax.action.php";
           actionData = {
               action: "change_ad_group_status",
               ad_group_id: itemId,
               status: newStatus
           };
       } else if (itemType === "ad_material") {
           actionUrl = "<?php echo CF_CAMPAIGN_URL; ?>/ajax.action.php";
           actionData = {
               action: "change_ad_material_status",
               ad_material_id: itemId,
               status: newStatus
           };
       } else {
           alert("알 수 없는 항목 유형입니다.");
           $("#statusModal").modal("hide");
           return;
       }
       
       $.ajax({
           url: actionUrl,
           type: "POST",
           dataType: "json",
           data: actionData,
           success: function(data) {
               if (data.success) {
                   alert("상태가 변경되었습니다.");
                   location.reload();
               } else {
                   alert("오류가 발생했습니다: " + data.message);
               }
           },
           error: function() {
               alert("서버 통신 중 오류가 발생했습니다.");
           }
       });
       
       $("#statusModal").modal("hide");
   });
   
   // 삭제 버튼 클릭 - 캠페인
   $(".delete-campaign").on("click", function(e) {
       e.preventDefault();
       var campaignId = $(this).data("id");
       var campaignName = $(this).data("name");
       
       $("#delete_item_id").val(campaignId);
       $("#delete_item_action").val("delete_campaign");
       $("#delete_item_type").text("캠페인");
       $("#delete_item_name").text(campaignName);
       
       $("#deleteModal").modal("show");
   });
   
   // 삭제 버튼 클릭 - 광고 그룹
   $(".delete-ad-group").on("click", function() {
       var adGroupId = $(this).data("id");
       var adGroupName = $(this).data("name");
       
       $("#delete_item_id").val(adGroupId);
       $("#delete_item_action").val("delete_ad_group");
       $("#delete_item_type").text("광고 그룹");
       $("#delete_item_name").text(adGroupName);
       
       $("#deleteModal").modal("show");
   });
   
   // 삭제 버튼 클릭 - 광고 소재
   $(".delete-ad").on("click", function() {
       var adId = $(this).data("id");
       var adName = $(this).data("name");
       
       $("#delete_item_id").val(adId);
       $("#delete_item_action").val("delete_ad_material");
       $("#delete_item_type").text("광고 소재");
       $("#delete_item_name").text(adName);
       
       $("#deleteModal").modal("show");
   });
   
   // 삭제 버튼 클릭 - 전환 스크립트
   $(".delete-script").on("click", function() {
       var scriptId = $(this).data("id");
       var scriptType = $(this).data("type");
       
       $("#delete_item_id").val(scriptId);
       $("#delete_item_action").val("delete_conversion_script");
       $("#delete_item_type").text("전환 스크립트");
       $("#delete_item_name").text(scriptType + " 스크립트");
       
       $("#deleteModal").modal("show");
   });
   
   // 삭제 확인
   $("#confirmDelete").on("click", function() {
       var itemId = $("#delete_item_id").val();
       var action = $("#delete_item_action").val();
       var actionData = {};
       
       // 액션에 따른 데이터 설정
       if (action === "delete_campaign") {
           actionData = {
               action: action,
               campaign_id: itemId
           };
       } else if (action === "delete_ad_group") {
           actionData = {
               action: action,
               ad_group_id: itemId
           };
       } else if (action === "delete_ad_material") {
           actionData = {
               action: action,
               ad_material_id: itemId
           };
       } else if (action === "delete_conversion_script") {
           actionData = {
               action: action,
               script_id: itemId
           };
       } else {
           alert("알 수 없는 액션입니다.");
           $("#deleteModal").modal("hide");
           return;
       }
       
       $.ajax({
           url: "<?php echo CF_CAMPAIGN_URL; ?>/ajax.action.php",
           type: "POST",
           dataType: "json",
           data: actionData,
           success: function(data) {
               if (data.success) {
                   if (action === "delete_campaign") {
                       alert("캠페인이 삭제되었습니다.");
                       location.href = "<?php echo CF_CAMPAIGN_URL; ?>/campaign_list.php";
                   } else {
                       alert("삭제되었습니다.");
                       location.reload();
                   }
               } else {
                   alert("오류가 발생했습니다: " + data.message);
               }
           },
           error: function() {
               alert("서버 통신 중 오류가 발생했습니다.");
           }
       });
       
       $("#deleteModal").modal("hide");
   });
   
   // 일괄 활성화 - 광고 그룹
   $("#bulkActivateAdGroups").on("click", function() {
       bulkStatusChange("ad_group", "활성");
   });
   
   // 일괄 일시중지 - 광고 그룹
   $("#bulkPauseAdGroups").on("click", function() {
       bulkStatusChange("ad_group", "일시중지");
   });
   
   // 일괄 비활성화 - 광고 그룹
   $("#bulkDeactivateAdGroups").on("click", function() {
       bulkStatusChange("ad_group", "비활성");
   });
   
   // 일괄 활성화 - 광고 소재
   $("#bulkActivateAds").on("click", function() {
       bulkStatusChange("ad_material", "활성");
   });
   
   // 일괄 일시중지 - 광고 소재
   $("#bulkPauseAds").on("click", function() {
       bulkStatusChange("ad_material", "일시중지");
   });
   
   // 일괄 비활성화 - 광고 소재
   $("#bulkDeactivateAds").on("click", function() {
       bulkStatusChange("ad_material", "비활성");
   });
   
   // 일괄 상태 변경 함수
   function bulkStatusChange(type, status) {
       var selectedIds = [];
       var checkboxClass = "";
       var action = "";
       var idField = "";
       
       // 타입에 따른 설정
       if (type === "ad_group") {
           checkboxClass = ".ad-group-check:checked";
           action = "bulk_change_ad_group_status";
           idField = "ad_group_ids";
       } else if (type === "ad_material") {
           checkboxClass = ".ad-material-check:checked";
           action = "bulk_change_ad_material_status";
           idField = "ad_material_ids";
       } else {
           alert("알 수 없는 타입입니다.");
           return;
       }
       
       // 선택된 ID 수집
       $(checkboxClass).each(function() {
           selectedIds.push($(this).val());
       });
       
       if (selectedIds.length === 0) {
           alert("선택된 항목이 없습니다.");
           return;
       }
       
       if (confirm("선택한 " + selectedIds.length + "개의 항목을 " + status + " 상태로 변경하시겠습니까?")) {
           var data = {
               action: action,
               status: status
           };
           data[idField] = selectedIds;
           
           $.ajax({
               url: "<?php echo CF_CAMPAIGN_URL; ?>/ajax.action.php",
               type: "POST",
               dataType: "json",
               data: data,
               success: function(response) {
                   if (response.success) {
                       alert("선택한 항목의 상태가 변경되었습니다.");
                       location.reload();
                   } else {
                       alert("오류가 발생했습니다: " + response.message);
                   }
               },
               error: function() {
                   alert("서버 통신 중 오류가 발생했습니다.");
               }
           });
       }
   }
   
   // 통계 데이터 CSV 내보내기
   $("#exportStatsCSV").on("click", function(e) {
       e.preventDefault();
       
       // 통계 테이블이 있는지 확인
       if ($("#statsTable").length === 0) {
           alert("내보낼 통계 데이터가 없습니다.");
           return;
       }
       
       var rows = [];
       var headers = [];
       
       // 헤더 추출
       $("#statsTable thead th").each(function() {
           headers.push($(this).text().trim());
       });
       rows.push(headers);
       
       // 데이터 추출
       $("#statsTable tbody tr").each(function() {
           var row = [];
           $(this).find("td").each(function() {
               // 천 단위 구분자 제거
               var cellText = $(this).text().trim().replace(/,/g, '');
               // % 기호 제거
               cellText = cellText.replace(/%/g, '');
               // "원" 제거
               cellText = cellText.replace(/원/g, '');
               row.push(cellText);
           });
           rows.push(row);
       });
       
       // CSV 생성
       var csvContent = "data:text/csv;charset=utf-8,";
       rows.forEach(function(rowArray) {
           var row = rowArray.join(",");
           csvContent += row + "\r\n";
       });
       
       // CSV 다운로드
       var encodedUri = encodeURI(csvContent);
       var link = document.createElement("a");
       link.setAttribute("href", encodedUri);
       link.setAttribute("download", "campaign_stats_<?php echo $campaign_id; ?>_<?php echo date('Ymd'); ?>.csv");
       document.body.appendChild(link);
       link.click();
       document.body.removeChild(link);
   });
   
   // 전환 데이터 CSV 내보내기
   $("#exportConversionsCSV").on("click", function(e) {
       e.preventDefault();
       
       // 전환 테이블이 있는지 확인
       if ($("#conversionTable").length === 0) {
           alert("내보낼 전환 데이터가 없습니다.");
           return;
       }
       
       var rows = [];
       var headers = [];
       
       // 헤더 추출
       $("#conversionTable thead th").each(function() {
           headers.push($(this).text().trim());
       });
       rows.push(headers);
       
       // 데이터 추출
       $("#conversionTable tbody tr").each(function() {
           var row = [];
           $(this).find("td").each(function() {
               // 이벤트 유형인 경우 배지 안의 텍스트 추출
               if ($(this).find(".badge").length > 0) {
                   row.push($(this).find(".badge").text().trim());
               } else {
                   // 천 단위 구분자 제거
                   var cellText = $(this).text().trim().replace(/,/g, '');
                   // "원" 제거
                   cellText = cellText.replace(/원/g, '');
                   row.push(cellText);
               }
           });
           rows.push(row);
       });
       
       // CSV 생성
       var csvContent = "data:text/csv;charset=utf-8,";
       rows.forEach(function(rowArray) {
           var row = rowArray.join(",");
           csvContent += row + "\r\n";
       });
       
       // CSV 다운로드
       var encodedUri = encodeURI(csvContent);
       var link = document.createElement("a");
       link.setAttribute("href", encodedUri);
       link.setAttribute("download", "campaign_conversions_<?php echo $campaign_id; ?>_<?php echo date('Ymd'); ?>.csv");
       document.body.appendChild(link);
       link.click();
       document.body.removeChild(link);
   });
   
   // 날짜 선택 폼 유효성 검사
   $('#start_date, #end_date').change(function() {
       var startDate = new Date($('#start_date').val());
       var endDate = new Date($('#end_date').val());
       
       // 시작일이 종료일보다 나중인 경우
       if (startDate > endDate) {
           alert('시작일은 종료일보다 이전이어야 합니다.');
           $('#start_date').val('<?php echo $start_date; ?>');
           $('#end_date').val('<?php echo $end_date; ?>');
           return;
       }
       
       // 날짜 범위가 90일을 초과하는 경우
       var timeDiff = endDate.getTime() - startDate.getTime();
       var dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
       if (dayDiff > 90) {
           alert('조회 기간은 최대 90일까지 가능합니다.');
           // 종료일로부터 90일 전으로 시작일 설정
           var newStartDate = new Date(endDate);
           newStartDate.setDate(endDate.getDate() - 90);
           $('#start_date').val(formatDate(newStartDate));
       }
   });
   
   // 날짜 포맷팅 함수
   function formatDate(date) {
       var year = date.getFullYear();
       var month = ('0' + (date.getMonth() + 1)).slice(-2);
       var day = ('0' + date.getDate()).slice(-2);
       return year + '-' + month + '-' + day;
   }
   
   // 상태에 따른 클래스 반환 함수
   function getStatusClass(status) {
       switch (status) {
           case '활성':
               return 'success';
           case '비활성':
               return 'danger';
           case '일시중지':
               return 'warning';
           default:
               return 'secondary';
       }
   }
});
</script>

<?php
// 상태에 따른 클래스 반환 함수
function getStatusClass($status) {
   switch ($status) {
       case '활성':
           return 'success';
       case '비활성':
           return 'danger';
       case '일시중지':
           return 'warning';
       default:
           return 'secondary';
   }
}

// 푸터 포함
include_once CF_PATH . '/footer.php';
?>