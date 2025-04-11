<?php
/**
 * 단축 URL 목록 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 페이지 제목
$page_title = "단축 URL 목록";

// 페이지 번호
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$rows = 10;
$offset = ($page - 1) * $rows;

// 검색 조건
$search_field = isset($_GET['search_field']) ? $_GET['search_field'] : '';
$search_keyword = isset($_GET['search_keyword']) ? trim($_GET['search_keyword']) : '';
$search_campaign_id = isset($_GET['search_campaign_id']) ? intval($_GET['search_campaign_id']) : 0;
$search_landing_id = isset($_GET['search_landing_id']) ? intval($_GET['search_landing_id']) : 0;
$search_source_type = isset($_GET['search_source_type']) ? trim($_GET['search_source_type']) : '';
$search_show_expired = isset($_GET['search_show_expired']) ? $_GET['search_show_expired'] : 'Y';

// 단축 URL 모델 로드
require_once CF_MODEL_PATH . '/shorturl.model.php';
$shorturl_model = new ShorturlModel();

// 캠페인 모델 로드
require_once CF_MODEL_PATH . '/campaign.model.php';
$campaign_model = new CampaignModel();

// 검색 파라미터 구성
$params = array(
    'campaign_id' => $search_campaign_id,
    'landing_id' => $search_landing_id,
    'source_type' => $search_source_type,
    'search_keyword' => $search_keyword,
    'show_expired' => $search_show_expired
);

// 단축 URL 목록 조회
$result = $shorturl_model->get_shorturl_list($member['id'], $params, $offset, $rows);
$total_count = $result['total_count'];
$list = $result['list'];
$total_page = ceil($total_count / $rows);

// 캠페인 목록 조회 (검색 필터용)
$sql = "SELECT id, name FROM {$cf_table_prefix}campaigns 
        WHERE user_id = '{$member['id']}' 
        ORDER BY name ASC";
$campaign_result = sql_query($sql);
$campaigns = array();
while($row = sql_fetch_array($campaign_result)) {
    $campaigns[] = $row;
}

// 랜딩페이지 목록 조회 (검색 필터용)
$landing_where = $search_campaign_id > 0 ? "AND campaign_id = '{$search_campaign_id}'" : "";
$sql = "SELECT id, name FROM {$cf_table_prefix}landing_pages 
        WHERE user_id = '{$member['id']}' {$landing_where}
        ORDER BY name ASC";
$landing_result = sql_query($sql);
$landing_pages = array();
while($row = sql_fetch_array($landing_result)) {
    $landing_pages[] = $row;
}

// 소재 유형 목록 조회 (검색 필터용)
$source_types = $shorturl_model->get_source_types($search_campaign_id);

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">단축 URL 목록</h1>
    <p class="mb-4">마케팅 캠페인 및 소스별 단축 URL을 관리하고 성과를 확인하세요.</p>

    <!-- 검색 및 필터 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">검색 및 필터</h6>
        </div>
        <div class="card-body">
            <form method="get" action="shorturl_list.php" class="mb-2">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search_campaign_id">캠페인</label>
                            <select name="search_campaign_id" id="search_campaign_id" class="form-control">
                                <option value="">모든 캠페인</option>
                                <?php foreach ($campaigns as $campaign) { ?>
                                <option value="<?php echo $campaign['id']; ?>" <?php echo ($search_campaign_id == $campaign['id']) ? 'selected' : ''; ?>><?php echo $campaign['name']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search_landing_id">랜딩페이지</label>
                            <select name="search_landing_id" id="search_landing_id" class="form-control">
                                <option value="">모든 랜딩페이지</option>
                                <?php foreach ($landing_pages as $landing) { ?>
                                <option value="<?php echo $landing['id']; ?>" <?php echo ($search_landing_id == $landing['id']) ? 'selected' : ''; ?>><?php echo $landing['name']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search_source_type">소재 유형</label>
                            <select name="search_source_type" id="search_source_type" class="form-control">
                                <option value="">모든 소재</option>
                                <?php foreach ($source_types as $type) { ?>
                                <option value="<?php echo $type; ?>" <?php echo ($search_source_type == $type) ? 'selected' : ''; ?>><?php echo $type; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search_show_expired">만료된 URL</label>
                            <select name="search_show_expired" id="search_show_expired" class="form-control">
                                <option value="Y" <?php echo ($search_show_expired == 'Y') ? 'selected' : ''; ?>>모두 표시</option>
                                <option value="N" <?php echo ($search_show_expired == 'N') ? 'selected' : ''; ?>>활성화된 URL만</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="search_field">검색 필드</label>
                            <select name="search_field" id="search_field" class="form-control">
                                <option value="original_url" <?php echo ($search_field == 'original_url') ? 'selected' : ''; ?>>원본 URL</option>
                                <option value="path" <?php echo ($search_field == 'path') ? 'selected' : ''; ?>>단축 경로</option>
                                <option value="random_code" <?php echo ($search_field == 'random_code') ? 'selected' : ''; ?>>랜덤 코드</option>
                                <option value="source_name" <?php echo ($search_field == 'source_name') ? 'selected' : ''; ?>>소재 이름</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="search_keyword">검색어</label>
                            <div class="input-group">
                                <input type="text" name="search_keyword" id="search_keyword" class="form-control" value="<?php echo $search_keyword; ?>" placeholder="검색어 입력">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> 검색</button>
                                    <a href="shorturl_list.php" class="btn btn-secondary"><i class="fas fa-sync-alt"></i> 초기화</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 단축 URL 목록 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">단축 URL 목록</h6>
            <div>
                <a href="shorturl_create.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> 단일 URL 생성</a>
                <a href="shorturl_bulk_create.php" class="btn btn-info btn-sm ml-2"><i class="fas fa-layer-group"></i> 일괄 URL 생성</a>
            </div>
        </div>
        <div class="card-body">
            <?php if(count($list) > 0) { ?>
            <div class="mb-3">
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAll">모두 선택</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnUnselectAll">선택 해제</button>
                </div>
                <div class="btn-group ml-2">
                    <button type="button" class="btn btn-sm btn-outline-success" id="btnActivate">선택 URL 활성화</button>
                    <button type="button" class="btn btn-sm btn-outline-warning" id="btnDeactivate">선택 URL 비활성화</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th width="3%"><input type="checkbox" id="checkAll"></th>
                        <th width="30%">단축 URL</th>
                        <th>캠페인/랜딩</th>
                        <th>소재</th>
                        <th>클릭</th>
                        <th>전환</th>
                        <th>상태</th>
                        <th>생성일</th>
                        <th width="10%">관리</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($list as $url) { 
                        // 만료 여부 확인
                        $is_expired = !empty($url['expires_at']) && strtotime($url['expires_at']) < time();
                        $status_class = $is_expired ? 'badge-secondary' : 'badge-success';
                        $status_text = $is_expired ? '만료됨' : '활성';

                        // 소재 정보 표시
                        $source_info = '';
                        if(!empty($url['source_type'])) {
                            $source_info .= $url['source_type'];
                            if(!empty($url['source_name'])) {
                                $source_info .= '<br><small>' . $url['source_name'] . '</small>';
                            }
                        } else {
                            $source_info = '-';
                        }
                        
                        // 원본 URL 정보
                        $original_url = !empty($url['original_url']) ? $url['original_url'] : '<span class="text-muted">가상 URL</span>';
                    ?>
                    <tr class="<?php echo $is_expired ? 'text-muted' : ''; ?>">
                        <td class="text-center">
                            <input type="checkbox" name="chk_url_id[]" value="<?php echo $url['id']; ?>" class="chk-url">
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <a href="<?php echo $url['short_url']; ?>" target="_blank" class="text-primary font-weight-bold">
                                    <?php echo $url['short_url']; ?>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-secondary ml-2 btn-copy-url" data-url="<?php echo $url['short_url']; ?>">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <?php if(!empty($url['qr_code_url'])) { ?>
                                <button type="button" class="btn btn-sm btn-outline-info ml-1 btn-show-qr" data-url="<?php echo $url['qr_code_url']; ?>">
                                    <i class="fas fa-qrcode"></i>
                                </button>
                                <?php } ?>
                            </div>
                            <small class="text-muted d-block text-truncate" title="<?php echo $url['original_url']; ?>">
                                <?php echo $original_url; ?>
                            </small>
                        </td>
                        <td>
                            <?php if($url['campaign_id'] > 0) { ?>
                            <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_view.php?id=<?php echo $url['campaign_id']; ?>">
                                <?php echo $url['campaign_name'] ?: '캠페인 #' . $url['campaign_id']; ?>
                            </a>
                            <?php } else { ?>
                            -
                            <?php } ?>
                            <?php if($url['landing_id'] > 0) { ?>
                            <br><small>
                                <a href="<?php echo CF_LANDING_URL; ?>/landing_page_view.php?id=<?php echo $url['landing_id']; ?>">
                                    <?php echo $url['landing_name'] ?: '랜딩 #' . $url['landing_id']; ?>
                                </a>
                            </small>
                            <?php } ?>
                        </td>
                        <td><?php echo $source_info; ?></td>
                        <td class="text-center"><?php echo number_format($url['click_count']); ?></td>
                        <td class="text-center"><?php echo isset($url['conversion_count']) ? number_format($url['conversion_count']) : '-'; ?></td>
                        <td class="text-center"><span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                        <td><?php echo substr($url['created_at'], 0, 10); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="shorturl_view.php?id=<?php echo $url['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-chart-line"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-info dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="shorturl_edit.php?id=<?php echo $url['id']; ?>">
                                        <i class="fas fa-edit fa-fw"></i> 수정
                                    </a>
                                    <a class="dropdown-item" href="shorturl_copy.php?id=<?php echo $url['id']; ?>">
                                        <i class="fas fa-copy fa-fw"></i> 복제
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <?php if($is_expired) { ?>
                                    <a class="dropdown-item" href="shorturl_status.php?id=<?php echo $url['id']; ?>&status=activate">
                                        <i class="fas fa-check fa-fw"></i> 활성화
                                    </a>
                                    <?php } else { ?>
                                    <a class="dropdown-item" href="shorturl_status.php?id=<?php echo $url['id']; ?>&status=deactivate">
                                        <i class="fas fa-times fa-fw"></i> 비활성화
                                    </a>
                                    <?php } ?>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteShorturl(<?php echo $url['id']; ?>, '<?php echo addslashes($url['short_url']); ?>')">
                                        <i class="fas fa-trash fa-fw"></i> 삭제
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            <?php
            if ($total_page > 0) {
                $params = array();
                if ($search_field) $params[] = "search_field=" . urlencode($search_field);
                if ($search_keyword) $params[] = "search_keyword=" . urlencode($search_keyword);
                if ($search_campaign_id) $params[] = "search_campaign_id=" . $search_campaign_id;
                if ($search_landing_id) $params[] = "search_landing_id=" . $search_landing_id;
                if ($search_source_type) $params[] = "search_source_type=" . urlencode($search_source_type);
                if ($search_show_expired) $params[] = "search_show_expired=" . $search_show_expired;
                $query_string = implode("&", $params);
                
                echo '<nav aria-label="페이지 네비게이션">';
                echo '<ul class="pagination justify-content-center">';
                
                $start_page = max(1, $page - 4);
                $end_page = min($total_page, $page + 4);
                
                // 이전 버튼
                if ($page > 1) {
                    echo '<li class="page-item"><a class="page-link" href="shorturl_list.php?page=1' . ($query_string ? '&' . $query_string : '') . '">&laquo;</a></li>';
                    echo '<li class="page-item"><a class="page-link" href="shorturl_list.php?page=' . ($page - 1) . ($query_string ? '&' . $query_string : '') . '">이전</a></li>';
                }
                
                // 페이지 번호
                for ($i = $start_page; $i <= $end_page; $i++) {
                    echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="shorturl_list.php?page=' . $i . ($query_string ? '&' . $query_string : '') . '">' . $i . '</a></li>';
                }
                
                // 다음 버튼
                if ($page < $total_page) {
                    echo '<li class="page-item"><a class="page-link" href="shorturl_list.php?page=' . ($page + 1) . ($query_string ? '&' . $query_string : '') . '">다음</a></li>';
                    echo '<li class="page-item"><a class="page-link" href="shorturl_list.php?page=' . $total_page . ($query_string ? '&' . $query_string : '') . '">&raquo;</a></li>';
                }
                
                echo '</ul>';
                echo '</nav>';
            }
            ?>
            
            <?php } else { ?>
            <div class="text-center py-5">
                <i class="fas fa-link fa-4x text-muted mb-3"></i>
                <p class="lead">등록된 단축 URL이 없습니다.</p>
                <p>새로운 단축 URL을 생성하려면 '단일 URL 생성' 또는 '일괄 URL 생성' 버튼을 클릭하세요.</p>
                <a href="shorturl_create.php" class="btn btn-primary"><i class="fas fa-plus"></i> 단축 URL 생성</a>
            </div>
            <?php } ?>
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
                <a id="downloadQrCode" href="#" download="qrcode.png" class="btn btn-primary"><i class="fas fa-download"></i> 다운로드</a>
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

<!-- URL 상태 일괄 변경 폼 (히든) -->
<form id="bulkStatusForm" action="shorturl_status.php" method="post">
    <input type="hidden" name="action" id="bulkAction" value="">
    <input type="hidden" name="url_ids" id="bulkUrlIds" value="">
    <input type="hidden" name="return_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
</form>

<script>
$(document).ready(function() {
    // 캠페인 선택 시 랜딩페이지 목록 업데이트
    $('#search_campaign_id').change(function() {
        let campaignId = $(this).val();
        if (campaignId) {
            // AJAX 요청으로 랜딩페이지 목록 가져오기
            $.ajax({
                url: 'shorturl_ajax.php',
                data: {
                    action: 'get_landing_pages',
                    campaign_id: campaignId
                },
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    // 랜딩페이지 셀렉트박스 업데이트
                    let options = '<option value="">모든 랜딩페이지</option>';
                    for (let i = 0; i < data.length; i++) {
                        options += '<option value="' + data[i].id + '">' + data[i].name + '</option>';
                    }
                    $('#search_landing_id').html(options);
                    
                    // 소재 유형 목록 업데이트
                    $.ajax({
                        url: 'shorturl_ajax.php',
                        data: {
                            action: 'get_source_types',
                            campaign_id: campaignId
                        },
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            let options = '<option value="">모든 소재</option>';
                            for (let i = 0; i < data.length; i++) {
                                options += '<option value="' + data[i] + '">' + data[i] + '</option>';
                            }
                            $('#search_source_type').html(options);
                        }
                    });
                }
            });
        } else {
            // 캠페인 선택 해제 시 기본 옵션으로 되돌리기
            $('#search_landing_id').html('<option value="">모든 랜딩페이지</option>');
            $('#search_source_type').html('<option value="">모든 소재</option>');
        }
    });
    
    // URL 복사 버튼 클릭 이벤트
    $('.btn-copy-url').click(function() {
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
    $('.btn-show-qr').click(function() {
        const qrUrl = $(this).data('url');
        $('#qrCodeImage').attr('src', qrUrl);
        $('#downloadQrCode').attr('href', qrUrl);
        $('#qrCodeModal').modal('show');
    });
    
    // 체크박스 전체 선택/해제
    $('#checkAll').change(function() {
        $('.chk-url').prop('checked', $(this).prop('checked'));
    });
    
    // 체크박스 개별 항목 변경 시 헤더 체크박스 상태 업데이트
    $('.chk-url').change(function() {
        let allChecked = $('.chk-url:checked').length === $('.chk-url').length;
        $('#checkAll').prop('checked', allChecked);
    });
    
    // 모두 선택/해제 버튼
    $('#btnSelectAll').click(function() {
        $('.chk-url').prop('checked', true);
        $('#checkAll').prop('checked', true);
    });
    
    $('#btnUnselectAll').click(function() {
        $('.chk-url').prop('checked', false);
        $('#checkAll').prop('checked', false);
    });
    
    // 선택 URL 활성화/비활성화 버튼
    $('#btnActivate, #btnDeactivate').click(function() {
        const action = $(this).attr('id') === 'btnActivate' ? 'activate' : 'deactivate';
        const selectedIds = $('.chk-url:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (selectedIds.length === 0) {
            toastr.warning('활성화/비활성화할 URL을 선택해주세요.');
            return;
        }
        
        // 폼 제출
        $('#bulkAction').val(action);
        $('#bulkUrlIds').val(selectedIds.join(','));
        $('#bulkStatusForm').submit();
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