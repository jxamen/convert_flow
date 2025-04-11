<?php
/**
 * 광고 그룹 목록 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 로그인 체크
if (!$is_member) {
    alert('로그인이 필요한 서비스입니다.', CF_URL . '/login.php');
}

// 캠페인 ID 파라미터 체크
$campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
if ($campaign_id <= 0) {
    alert('잘못된 접근입니다.');
}

// 페이징 파라미터
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 20;

// 검색 파라미터
$search_keyword = isset($_GET['search_keyword']) ? trim($_GET['search_keyword']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$sort_field = isset($_GET['sort_field']) ? trim($_GET['sort_field']) : 'created_at';
$sort_order = isset($_GET['sort_order']) ? trim($_GET['sort_order']) : 'DESC';

// 검색 파라미터 배열 (페이징 링크용)
$search_params = array(
    'campaign_id' => $campaign_id,
    'search_keyword' => $search_keyword,
    'status' => $status,
    'sort_field' => $sort_field,
    'sort_order' => $sort_order,
    'page' => $page
);

// 캠페인 모델 로드
include_once CF_MODEL_PATH . '/campaign/campaign.model.php';
$campaign_model = new CampaignModel();

// 광고 그룹 모델 로드
include_once CF_MODEL_PATH . '/campaign/ad_group.model.php';
$ad_group_model = new AdGroupModel();

// 캠페인 정보 조회 (권한 체크용)
$campaign = $campaign_model->get_campaign($campaign_id, $member['id']);
if (!$campaign) {
    alert('존재하지 않거나 권한이 없는 캠페인입니다.');
}

// 광고 그룹 목록 조회 파라미터
$params = array(
    'page' => $page,
    'items_per_page' => $items_per_page,
    'search_keyword' => $search_keyword,
    'status' => $status,
    'sort_field' => $sort_field,
    'sort_order' => $sort_order
);

// 광고 그룹 목록 조회
$result = $ad_group_model->get_ad_groups_by_campaign($campaign_id, $params);
$ad_groups = $result['ad_groups'];
$total_count = $result['total_count'];
$total_pages = $result['total_pages'];

// 통계 기간 설정
$stat_range = isset($_GET['stat_range']) ? $_GET['stat_range'] : 'last30days';

$date_ranges = array(
    'today' => array('label' => '오늘', 'start' => date('Y-m-d'), 'end' => date('Y-m-d')),
    'yesterday' => array('label' => '어제', 'start' => date('Y-m-d', strtotime('-1 day')), 'end' => date('Y-m-d', strtotime('-1 day'))),
    'last7days' => array('label' => '최근 7일', 'start' => date('Y-m-d', strtotime('-6 days')), 'end' => date('Y-m-d')),
    'last30days' => array('label' => '최근 30일', 'start' => date('Y-m-d', strtotime('-29 days')), 'end' => date('Y-m-d')),
    'thismonth' => array('label' => '이번 달', 'start' => date('Y-m-01'), 'end' => date('Y-m-d')),
    'lastmonth' => array('label' => '지난 달', 'start' => date('Y-m-d', strtotime('first day of last month')), 'end' => date('Y-m-d', strtotime('last day of last month')))
);

// 캠페인 전체 통계
$campaign_stats = $campaign_model->get_campaign_statistics($campaign_id);

// 페이지 타이틀 설정
$g5['title'] = '광고 그룹 목록';
include_once CF_PATH . '/head.php';
?>

<div class="container-fluid">
    <!-- 페이지 타이틀 -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_view.php?id=<?php echo $campaign_id; ?>" class="text-primary"><?php echo $campaign['name']; ?></a> - 광고 그룹 목록
        </h1>
        <div>
            <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_add.php?campaign_id=<?php echo $campaign_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm mr-2">
                <i class="fas fa-plus fa-sm text-white-50"></i> 새 광고 그룹 추가
            </a>
            <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_view.php?id=<?php echo $campaign_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> 캠페인으로 돌아가기
            </a>
        </div>
    </div>

    <!-- 통계 요약 카드 -->
    <div class="row">
        <!-- 노출수 -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">노출수</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($campaign_stats['impressions']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-eye fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 클릭수 -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">클릭수</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($campaign_stats['clicks']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-mouse-pointer fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 전환수 -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">전환수</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($campaign_stats['conversions']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 광고비 -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">광고비</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($campaign_stats['cost']); ?>원</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-won-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 검색 및 필터 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">검색 및 필터</h6>
        </div>
        <div class="card-body">
            <form id="searchForm" method="get" action="">
                <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="search_keyword">검색어</label>
                        <input type="text" class="form-control" id="search_keyword" name="search_keyword" value="<?php echo $search_keyword; ?>" placeholder="광고 그룹명 검색">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="status">상태</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">전체</option>
                            <option value="활성" <?php echo $status == '활성' ? 'selected' : ''; ?>>활성</option>
                            <option value="비활성" <?php echo $status == '비활성' ? 'selected' : ''; ?>>비활성</option>
                            <option value="일시중지" <?php echo $status == '일시중지' ? 'selected' : ''; ?>>일시중지</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="sort_field">정렬 기준</label>
                        <div class="input-group">
                            <select class="form-control" id="sort_field" name="sort_field">
                                <option value="created_at" <?php echo $sort_field == 'created_at' ? 'selected' : ''; ?>>생성일</option>
                                <option value="name" <?php echo $sort_field == 'name' ? 'selected' : ''; ?>>광고 그룹명</option>
                                <option value="status" <?php echo $sort_field == 'status' ? 'selected' : ''; ?>>상태</option>
                                <option value="bid_amount" <?php echo $sort_field == 'bid_amount' ? 'selected' : ''; ?>>입찰가</option>
                            </select>
                            <select class="form-control" id="sort_order" name="sort_order">
                                <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>내림차순</option>
                                <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>오름차순</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 text-center">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> 검색</button>
                        <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_list.php?campaign_id=<?php echo $campaign_id; ?>" class="btn btn-secondary"><i class="fas fa-sync-alt"></i> 초기화</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 광고 그룹 목록 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">광고 그룹 목록</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">통계 기간:</div>
                    <?php foreach ($date_ranges as $range_key => $range_data) { ?>
                    <a class="dropdown-item <?php echo $stat_range == $range_key ? 'active' : ''; ?>" href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_list.php?campaign_id=<?php echo $campaign_id; ?>&stat_range=<?php echo $range_key; ?>"><?php echo $range_data['label']; ?></a>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($ad_groups)) { ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-folder-open fa-4x text-gray-300"></i>
                    </div>
                    <p class="lead text-gray-800">등록된 광고 그룹이 없습니다.</p>
                    <p class="text-gray-500">새 광고 그룹을 만들어 광고를 시작해보세요.</p>
                    <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_add.php?campaign_id=<?php echo $campaign_id; ?>" class="btn btn-primary"><i class="fas fa-plus"></i> 새 광고 그룹 만들기</a>
                </div>
            <?php } else { ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="adGroupTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="5%"><input type="checkbox" id="checkAll"></th>
                                <th width="25%">광고 그룹명</th>
                                <th width="10%">상태</th>
                                <th width="10%">타겟팅 유형</th>
                                <th width="10%">입찰가</th>
                                <th width="8%">노출수</th>
                                <th width="8%">클릭수</th>
                                <th width="8%">CTR</th>
                                <th width="8%">전환수</th>
                                <th width="8%">CPA</th>
                                <th width="12%">관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ad_groups as $ad_group) { ?>
                            <tr>
                                <td><input type="checkbox" class="ad-group-check" value="<?php echo $ad_group['id']; ?>"></td>
                                <td>
                                    <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_edit.php?id=<?php echo $ad_group['id']; ?>" class="font-weight-bold text-primary">
                                        <?php echo $ad_group['name']; ?>
                                    </a>
                                    <br>
                                    <small class="text-muted">생성일: <?php echo date("Y-m-d", strtotime($ad_group['created_at'])); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch ($ad_group['status']) {
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
                                    <span class="badge badge-<?php echo $status_class; ?>"><?php echo $ad_group['status']; ?></span>
                                </td>
                                <td><?php echo $ad_group['targeting_type']; ?></td>
                                <td><?php echo number_format($ad_group['bid_amount']); ?>원</td>
                                <td><?php echo number_format($ad_group['statistics']['impressions']); ?></td>
                                <td><?php echo number_format($ad_group['statistics']['clicks']); ?></td>
                                <td><?php echo number_format($ad_group['statistics']['ctr'], 2); ?>%</td>
                                <td><?php echo number_format($ad_group['statistics']['conversions']); ?></td>
                                <td><?php echo $ad_group['statistics']['conversions'] > 0 ? number_format($ad_group['statistics']['cpa']) : '-'; ?>원</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_list.php?ad_group_id=<?php echo $ad_group['id']; ?>" class="btn btn-info" title="광고 목록">
                                            <i class="fas fa-ad"></i>
                                        </a>
                                        <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_edit.php?id=<?php echo $ad_group['id']; ?>" class="btn btn-primary" title="수정">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-warning toggle-status" data-id="<?php echo $ad_group['id']; ?>" data-status="<?php echo $ad_group['status']; ?>" title="상태 변경">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger delete-ad-group" data-id="<?php echo $ad_group['id']; ?>" data-name="<?php echo $ad_group['name']; ?>" title="삭제">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- 일괄 작업 버튼 -->
                <div class="mb-3">
                    <button type="button" id="bulkActivate" class="btn btn-success btn-sm"><i class="fas fa-play"></i> 일괄 활성화</button>
                    <button type="button" id="bulkPause" class="btn btn-warning btn-sm"><i class="fas fa-pause"></i> 일괄 일시중지</button>
                    <button type="button" id="bulkDeactivate" class="btn btn-danger btn-sm"><i class="fas fa-stop"></i> 일괄 비활성화</button>
                </div>
                
                <!-- 페이징 -->
                <?php if ($total_pages > 1) { ?>
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
                                    echo '<li class="paginate_button page-item previous"><a href="?page='.($page-1).'&'.http_build_query(array_diff_key($search_params, array('page' => 0))).'" class="page-link">이전</a></li>';
                                }
                                
                                // 페이지 번호
                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    $active = $i == $page ? 'active' : '';
                                    echo '<li class="paginate_button page-item '.$active.'"><a href="?page='.$i.'&'.http_build_query(array_diff_key($search_params, array('page' => 0))).'" class="page-link">'.$i.'</a></li>';
                                }
                                
                                // 다음 버튼
                                if ($page < $total_pages) {
                                    echo '<li class="paginate_button page-item next"><a href="?page='.($page+1).'&'.http_build_query(array_diff_key($search_params, array('page' => 0))).'" class="page-link">다음</a></li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php } ?>
            <?php } ?>
        </div>
    </div>
</div>

<!-- 상태 변경 모달 -->
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">광고 그룹 상태 변경</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p>광고 그룹 상태를 변경하시겠습니까?</p>
                <form id="statusForm">
                    <input type="hidden" id="ad_group_id" name="ad_group_id" value="">
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
                <h5 class="modal-title" id="deleteModalLabel">광고 그룹 삭제</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong id="delete_ad_group_name"></strong> 광고 그룹을 정말 삭제하시겠습니까?</p>
                <p class="text-danger">이 작업은 되돌릴 수 없으며, 관련된 모든 광고도 함께 삭제됩니다.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">취소</button>
                <button class="btn btn-danger" id="confirmDelete">삭제</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 체크박스 전체 선택/해제
    $("#checkAll").on("click", function() {
        $(".ad-group-check").prop("checked", $(this).prop("checked"));
    });
    
    // 상태 변경 버튼 클릭
    $(".toggle-status").on("click", function() {
        var adGroupId = $(this).data("id");
        var currentStatus = $(this).data("status");
        
        $("#ad_group_id").val(adGroupId);
        
        // 현재 상태에 따라 기본값 설정
        if (currentStatus === "활성") {
            $("#new_status").val("일시중지");
        } else if (currentStatus === "일시중지") {
            $("#new_status").val("활성");
        } else {
            $("#new_status").val("활성");
        }
        
        $("#statusModal").modal("show");
    });
    
    // 상태 변경 확인
    $("#confirmStatus").on("click", function() {
        var adGroupId = $("#ad_group_id").val();
        var newStatus = $("#new_status").val();
        
        $.ajax({
            url: "<?php echo CF_CAMPAIGN_URL; ?>/ajax.action.php",
            type: "POST",
            data: {
                action: "change_ad_group_status",
                ad_group_id: adGroupId,
                status: newStatus
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    alert("광고 그룹 상태가 변경되었습니다.");
                    location.reload();
                } else {
                    alert("오류가 발생했습니다: " + response.message);
                }
            },
            error: function() {
                alert("서버 통신 오류가 발생했습니다.");
            }
        });
        
        $("#statusModal").modal("hide");
    });
    
    // 삭제 버튼 클릭
    $(".delete-ad-group").on("click", function() {
        var adGroupId = $(this).data("id");
        var adGroupName = $(this).data("name");
        
        $("#delete_ad_group_name").text(adGroupName);
        $("#confirmDelete").data("id", adGroupId);
        
        $("#deleteModal").modal("show");
    });
    
    // 삭제 확인
    $("#confirmDelete").on("click", function() {
        var adGroupId = $(this).data("id");
        
        $.ajax({
            url: "<?php echo CF_CAMPAIGN_URL; ?>/ajax.action.php",
            type: "POST",
            data: {
                action: "delete_ad_group",
                ad_group_id: adGroupId
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    alert("광고 그룹이 삭제되었습니다.");
                    location.reload();
                } else {
                    alert("오류가 발생했습니다: " + response.message);
                }
            },
            error: function() {
                alert("서버 통신 오류가 발생했습니다.");
            }
        });
        
        $("#deleteModal").modal("hide");
    });
    
    // 일괄 활성화
    $("#bulkActivate").on("click", function() {
        bulkStatusChange("활성");
    });
    
    // 일괄 일시중지
    $("#bulkPause").on("click", function() {
        bulkStatusChange("일시중지");
    });
    
    // 일괄 비활성화
    $("#bulkDeactivate").on("click", function() {
        bulkStatusChange("비활성");
    });
    
    // 일괄 상태 변경 함수
    function bulkStatusChange(status) {
        var selectedIds = [];
        
        $(".ad-group-check:checked").each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            alert("선택된 광고 그룹이 없습니다.");
            return;
        }
        
        if (confirm("선택한 " + selectedIds.length + "개의 광고 그룹을 " + status + " 상태로 변경하시겠습니까?")) {
            $.ajax({
                url: "<?php echo CF_CAMPAIGN_URL; ?>/ajax.action.php",
                type: "POST",
                data: {
                    action: "bulk_change_ad_group_status",
                    ad_group_ids: selectedIds,
                    status: status
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        alert("선택한 광고 그룹의 상태가 변경되었습니다.");
                        location.reload();
                    } else {
                        alert("오류가 발생했습니다: " + response.message);
                    }
                },
                error: function() {
                    alert("서버 통신 오류가 발생했습니다.");
                }
            });
        }
    }
});
</script>

<?php
include_once CF_PATH . '/tail.php';
?>