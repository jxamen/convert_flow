<?php
/**
 * 광고 목록 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 로그인 체크
if (!$is_member) {
    alert('로그인이 필요한 서비스입니다.', CF_URL . '/login.php');
}

// 파라미터 체크
$ad_group_id = isset($_GET['ad_group_id']) ? intval($_GET['ad_group_id']) : 0;
$campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;

// 어떤 파라미터도 없는 경우
if ($ad_group_id <= 0 && $campaign_id <= 0) {
    alert('잘못된 접근입니다.');
}

// 캠페인 모델 로드
include_once CF_MODEL_PATH . '/campaign/campaign.model.php';
$campaign_model = new CampaignModel();

// 광고 그룹 모델 로드
include_once CF_MODEL_PATH . '/campaign/ad_group.model.php';
$ad_group_model = new AdGroupModel();

// 광고 모델 로드
include_once CF_MODEL_PATH . '/campaign/ad.model.php';
$ad_model = new AdModel();

// 광고 그룹에 속한 광고 목록을 조회하는 경우
if ($ad_group_id > 0) {
    // 광고 그룹 정보 조회
    $ad_group = $ad_group_model->get_ad_group($ad_group_id);
    if (!$ad_group) {
        alert('존재하지 않는 광고 그룹입니다.');
    }
    
    // 캠페인 정보 조회 (권한 체크용)
    $campaign = $campaign_model->get_campaign($ad_group['campaign_id'], $member['id']);
    if (!$campaign) {
        alert('권한이 없는 광고 그룹입니다.');
    }
    
    $campaign_id = $ad_group['campaign_id'];
    $parent_name = $ad_group['name'];
    $parent_type = 'ad_group';
    $parent_id = $ad_group_id;
}
// 캠페인에 속한 모든 광고 목록을 조회하는 경우
else {
    // 캠페인 정보 조회 (권한 체크용)
    $campaign = $campaign_model->get_campaign($campaign_id, $member['id']);
    if (!$campaign) {
        alert('존재하지 않거나 권한이 없는 캠페인입니다.');
    }
    
    $parent_name = $campaign['name'];
    $parent_type = 'campaign';
    $parent_id = $campaign_id;
}

// 페이징 파라미터
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 20;

// 검색 파라미터
$search_keyword = isset($_GET['search_keyword']) ? trim($_GET['search_keyword']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$ad_type = isset($_GET['ad_type']) ? trim($_GET['ad_type']) : '';
$sort_field = isset($_GET['sort_field']) ? trim($_GET['sort_field']) : 'created_at';
$sort_order = isset($_GET['sort_order']) ? trim($_GET['sort_order']) : 'DESC';

// 검색 파라미터 배열 (페이징 링크용)
$search_params = array(
    'search_keyword' => $search_keyword,
    'status' => $status,
    'ad_type' => $ad_type,
    'sort_field' => $sort_field,
    'sort_order' => $sort_order,
    'page' => $page
);

if ($ad_group_id > 0) {
    $search_params['ad_group_id'] = $ad_group_id;
} else {
    $search_params['campaign_id'] = $campaign_id;
}

// 광고 목록 조회 파라미터
$params = array(
    'page' => $page,
    'items_per_page' => $items_per_page,
    'search_keyword' => $search_keyword,
    'status' => $status,
    'ad_type' => $ad_type,
    'sort_field' => $sort_field,
    'sort_order' => $sort_order
);

// 광고 목록 조회
if ($ad_group_id > 0) {
    $result = $ad_model->get_ads_by_ad_group($ad_group_id, $params);
} else {
    $result = $ad_model->get_ads_by_campaign($campaign_id, $params);
}

$ads = $result['ads'];
$total_count = $result['total_count'];
$total_pages = $result['total_pages'];

// 광고 유형 옵션
$ad_types = array(
    '텍스트' => '텍스트 광고',
    '이미지' => '이미지 광고',
    '동영상' => '동영상 광고',
    '반응형' => '반응형 광고'
);

// 페이지 타이틀 설정
$g5['title'] = '광고 목록';
include_once CF_PATH . '/head.php';
?>

<div class="container-fluid">
    <!-- 페이지 타이틀 -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <?php if ($parent_type == 'campaign') { ?>
            <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_view.php?id=<?php echo $campaign_id; ?>" class="text-primary"><?php echo $parent_name; ?></a>
            <?php } else { ?>
            <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_view.php?id=<?php echo $campaign_id; ?>" class="text-primary"><?php echo $campaign['name']; ?></a> &gt;
            <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_edit.php?id=<?php echo $ad_group_id; ?>" class="text-primary"><?php echo $parent_name; ?></a>
            <?php } ?>
            - 광고 목록
        </h1>
        <div>
            <?php if ($parent_type == 'ad_group') { ?>
            <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_add.php?ad_group_id=<?php echo $ad_group_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm mr-2">
                <i class="fas fa-plus fa-sm text-white-50"></i> 새 광고 추가
            </a>
            <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_edit.php?id=<?php echo $ad_group_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> 광고 그룹으로 돌아가기
            </a>
            <?php } else { ?>
            <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_view.php?id=<?php echo $campaign_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> 캠페인으로 돌아가기
            </a>
            <?php } ?>
        </div>
    </div>

    <!-- 검색 및 필터 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">검색 및 필터</h6>
        </div>
        <div class="card-body">
            <form id="searchForm" method="get" action="">
                <?php if ($ad_group_id > 0) { ?>
                <input type="hidden" name="ad_group_id" value="<?php echo $ad_group_id; ?>">
                <?php } else { ?>
                <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">
                <?php } ?>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="search_keyword">검색어</label>
                        <input type="text" class="form-control" id="search_keyword" name="search_keyword" value="<?php echo $search_keyword; ?>" placeholder="광고 제목 검색">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="status">상태</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">전체</option>
                            <option value="활성" <?php echo $status == '활성' ? 'selected' : ''; ?>>활성</option>
                            <option value="비활성" <?php echo $status == '비활성' ? 'selected' : ''; ?>>비활성</option>
                            <option value="일시중지" <?php echo $status == '일시중지' ? 'selected' : ''; ?>>일시중지</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="ad_type">광고 유형</label>
                        <select class="form-control" id="ad_type" name="ad_type">
                            <option value="">전체</option>
                            <?php foreach ($ad_types as $key => $value) { ?>
                            <option value="<?php echo $key; ?>" <?php echo $ad_type == $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="sort_field">정렬 기준</label>
                        <div class="input-group">
                            <select class="form-control" id="sort_field" name="sort_field">
                                <option value="created_at" <?php echo $sort_field == 'created_at' ? 'selected' : ''; ?>>생성일</option>
                                <option value="headline" <?php echo $sort_field == 'headline' ? 'selected' : ''; ?>>광고제목</option>
                                <option value="status" <?php echo $sort_field == 'status' ? 'selected' : ''; ?>>상태</option>
                                <option value="ad_type" <?php echo $sort_field == 'ad_type' ? 'selected' : ''; ?>>광고 유형</option>
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
                        <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_list.php?<?php echo $ad_group_id > 0 ? 'ad_group_id='.$ad_group_id : 'campaign_id='.$campaign_id; ?>" class="btn btn-secondary"><i class="fas fa-sync-alt"></i> 초기화</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 광고 목록 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">광고 목록</h6>
            <?php if ($parent_type == 'ad_group') { ?>
            <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_add.php?ad_group_id=<?php echo $ad_group_id; ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> 새 광고 추가
            </a>
            <?php } ?>
        </div>
        <div class="card-body">
            <?php if (empty($ads)) { ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-ad fa-4x text-gray-300"></i>
                    </div>
                    <p class="lead text-gray-800">등록된 광고가 없습니다.</p>
                    <?php if ($parent_type == 'ad_group') { ?>
                    <p class="text-gray-500">새 광고를 만들어 광고를 시작해보세요.</p>
                    <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_add.php?ad_group_id=<?php echo $ad_group_id; ?>" class="btn btn-primary"><i class="fas fa-plus"></i> 새 광고 만들기</a>
                    <?php } else { ?>
                    <p class="text-gray-500">광고 그룹을 선택하여 광고를 추가하세요.</p>
                    <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_list.php?campaign_id=<?php echo $campaign_id; ?>" class="btn btn-primary"><i class="fas fa-list"></i> 광고 그룹 목록</a>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="adTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="5%"><input type="checkbox" id="checkAll"></th>
                                <th width="25%">광고 제목</th>
                                <?php if ($parent_type == 'campaign') { ?>
                                <th width="15%">광고 그룹</th>
                                <?php } ?>
                                <th width="10%">상태</th>
                                <th width="10%">유형</th>
                                <th width="10%">노출수</th>
                                <th width="10%">클릭수</th>
                                <th width="10%">CTR</th>
                                <th width="10%">전환수</th>
                                <th width="12%">관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ads as $ad) { ?>
                            <tr>
                                <td><input type="checkbox" class="ad-check" value="<?php echo $ad['id']; ?>"></td>
                                <td>
                                    <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_edit.php?id=<?php echo $ad['id']; ?>" class="font-weight-bold text-primary">
                                        <?php echo $ad['headline']; ?>
                                    </a>
                                    <br>
                                    <small class="text-muted"><?php echo mb_substr($ad['description'], 0, 30).(mb_strlen($ad['description']) > 30 ? '...' : ''); ?></small>
                                </td>
                                <?php if ($parent_type == 'campaign') { ?>
                                <td>
                                    <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_edit.php?id=<?php echo $ad['ad_group_id']; ?>">
                                        <?php echo $ad['ad_group_name']; ?>
                                    </a>
                                </td>
                                <?php } ?>
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
                                <td><?php echo number_format($ad['statistics']['impressions']); ?></td>
                                <td><?php echo number_format($ad['statistics']['clicks']); ?></td>
                                <td><?php echo number_format($ad['statistics']['ctr'], 2); ?>%</td>
                                <td><?php echo number_format($ad['statistics']['conversions']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_edit.php?id=<?php echo $ad['id']; ?>" class="btn btn-primary" title="수정">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-warning toggle-status" data-id="<?php echo $ad['id']; ?>" data-status="<?php echo $ad['status']; ?>" title="상태 변경">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger delete-ad" data-id="<?php echo $ad['id']; ?>" data-name="<?php echo $ad['headline']; ?>" title="삭제">
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
                <h5 class="modal-title" id="statusModalLabel">광고 상태 변경</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p>광고 상태를 변경하시겠습니까?</p>
                <form id="statusForm">
                    <input type="hidden" id="ad_id" name="ad_id" value="">
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
                <h5 class="modal-title" id="deleteModalLabel">광고 삭제</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong id="delete_ad_name"></strong> 광고를 정말 삭제하시겠습니까?</p>
                <p class="text-danger">이 작업은 되돌릴 수 없습니다.</p>
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
        $(".ad-check").prop("checked", $(this).prop("checked"));
    });
    
    // 상태 변경 버튼 클릭
    $(".toggle-status").on("click", function() {
        var adId = $(this).data("id");
        var currentStatus = $(this).data("status");
        
        $("#ad_id").val(adId);
        
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
        var adId = $("#ad_id").val();
        var newStatus = $("#new_status").val();
        
        $.ajax({
            url: "<?php echo CF_CAMPAIGN_URL; ?>/ajax.action.php",
            type: "POST",
            data: {
                action: "change_ad_status",
                ad_id: adId,
                status: newStatus
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    alert("광고 상태가 변경되었습니다.");
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
    $(".delete-ad").on("click", function() {
        var adId = $(this).data("id");
        var adName = $(this).data("name");
        
        $("#delete_ad_name").text(adName);
        $("#confirmDelete").data("id", adId);
        
        $("#deleteModal").modal("show");
    });
    
    // 삭제 확인
    $("#confirmDelete").on("click", function() {
        var adId = $(this).data("id");
        
        $.ajax({
            url: "<?php echo CF_CAMPAIGN_URL; ?>/ajax.action.php",
            type: "POST",
            data: {
                action: "delete_ad",
                ad_id: adId
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    alert("광고가 삭제되었습니다.");
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
        
        $(".ad-check:checked").each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            alert("선택된 광고가 없습니다.");
            return;
        }
        
        if (confirm("선택한 " + selectedIds.length + "개의 광고를 " + status + " 상태로 변경하시겠습니까?")) {
            $.ajax({
                url: "<?php echo CF_CAMPAIGN_URL; ?>/ajax.action.php",
                type: "POST",
                data: {
                    action: "bulk_change_ad_status",
                    ad_ids: selectedIds,
                    status: status
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        alert("선택한 광고의 상태가 변경되었습니다.");
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