<?php
/**
 * 랜딩페이지 목록
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 페이지 제목
$page_title = "랜딩페이지 목록";

// 페이지 번호
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$rows = 10;
$offset = ($page - 1) * $rows;

// 검색 조건
$search_field = isset($_GET['search_field']) ? $_GET['search_field'] : '';
$search_keyword = isset($_GET['search_keyword']) ? trim($_GET['search_keyword']) : '';
$search_status = isset($_GET['search_status']) ? $_GET['search_status'] : '';
$search_campaign = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;

// 랜딩페이지 모델 로드
require_once CF_MODEL_PATH . '/landing_page.model.php';
$landing_model = new LandingPageModel();

// 관리자가 아닌 경우 자신의 랜딩페이지만 볼 수 있음
$user_condition = '';
if ($is_admin !== "super") {
    $user_condition = " AND l.user_id = '{$member['id']}'";
}

// 검색 WHERE 조건 구성
$search_where = '';
if ($search_field && $search_keyword) {
    $search_where .= " AND {$search_field} LIKE '%" . sql_escape_string($search_keyword) . "%'";
}
if ($search_status) {
    $search_where .= " AND l.status = '" . sql_escape_string($search_status) . "'";
}
if ($search_campaign > 0) {
    $search_where .= " AND l.campaign_id = " . $search_campaign;
}

// 전체 랜딩페이지 개수 조회
$sql = "SELECT COUNT(*) as cnt 
       FROM {$cf_table_prefix}landing_pages l 
       WHERE (1) {$search_where} {$user_condition}";
$row = sql_fetch($sql);
$total_count = $row['cnt'];
$total_page = ceil($total_count / $rows);

// 랜딩페이지 목록 조회
$sql = "SELECT l.*, 
        c.name as campaign_name,
        t.name as template_name,
        (SELECT COUNT(*) FROM {$cf_table_prefix}landing_page_block_instances WHERE landing_page_id = l.id) as block_count
       FROM {$cf_table_prefix}landing_pages l
       LEFT JOIN {$cf_table_prefix}campaigns c ON l.campaign_id = c.id
       LEFT JOIN {$cf_table_prefix}landing_page_templates t ON l.template_id = t.id
       WHERE (1) {$search_where} {$user_condition}
       ORDER BY l.created_at DESC
       LIMIT $offset, $rows";
$result = sql_query($sql);

// 캠페인 목록 조회 (검색 필터용)
$campaign_sql = "SELECT id, name FROM {$cf_table_prefix}campaigns";
if ($is_admin !== "super") {
    $campaign_sql .= " WHERE user_id = '{$member['id']}'";
}
$campaign_sql .= " ORDER BY name ASC";
$campaign_result = sql_query($campaign_sql);
$campaigns = array();
while ($row = sql_fetch_array($campaign_result)) {
    $campaigns[$row['id']] = $row['name'];
}

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">랜딩페이지 목록</h1>
    <p class="mb-4">모든 랜딩페이지를 관리하고 성과를 확인하세요.</p>

    <!-- 검색 및 필터 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">검색 및 필터</h6>
        </div>
        <div class="card-body">
            <form method="get" action="landing_page_list.php" class="form-inline">
                <div class="form-group mr-2 mb-2">
                    <select name="search_field" class="form-control">
                        <option value="l.name" <?php echo ($search_field == 'l.name') ? 'selected' : ''; ?>>페이지명</option>
                        <option value="l.slug" <?php echo ($search_field == 'l.slug') ? 'selected' : ''; ?>>슬러그</option>
                        <option value="l.meta_title" <?php echo ($search_field == 'l.meta_title') ? 'selected' : ''; ?>>메타 타이틀</option>
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <input type="text" name="search_keyword" class="form-control" value="<?php echo $search_keyword; ?>" placeholder="검색어 입력">
                </div>
                <div class="form-group mr-2 mb-2">
                    <select name="search_status" class="form-control">
                        <option value="">상태 전체</option>
                        <option value="초안" <?php echo ($search_status == '초안') ? 'selected' : ''; ?>>초안</option>
                        <option value="게시됨" <?php echo ($search_status == '게시됨') ? 'selected' : ''; ?>>게시됨</option>
                        <option value="보관됨" <?php echo ($search_status == '보관됨') ? 'selected' : ''; ?>>보관됨</option>
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <select name="campaign_id" class="form-control">
                        <option value="0">캠페인 전체</option>
                        <?php foreach ($campaigns as $id => $name) { ?>
                        <option value="<?php echo $id; ?>" <?php echo ($search_campaign == $id) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mb-2"><i class="fas fa-search"></i> 검색</button>
                <a href="landing_page_list.php" class="btn btn-secondary mb-2 ml-2"><i class="fas fa-sync-alt"></i> 초기화</a>
            </form>
        </div>
    </div>

    <!-- 랜딩페이지 목록 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">랜딩페이지 목록</h6>
            <a href="landing_page_create.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> 새 랜딩페이지</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th>이름</th>
                        <th>캠페인</th>
                        <th>상태</th>
                        <th>템플릿</th>
                        <th>블록 수</th>
                        <th>생성일</th>
                        <th>게시일</th>
                        <th>관리</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    if (sql_num_rows($result) > 0) {
                        while ($row = sql_fetch_array($result)) {
                            // 상태에 따른 배지 스타일
                            $status_class = '';
                            switch ($row['status']) {
                                case '게시됨':
                                    $status_class = 'badge-success';
                                    break;
                                case '초안':
                                    $status_class = 'badge-secondary';
                                    break;
                                case '보관됨':
                                    $status_class = 'badge-warning';
                                    break;
                            }
                            
                            echo '
                            <tr>
                                <td>' . $row['name'] . '</td>
                                <td>' . (!empty($row['campaign_name']) ? $row['campaign_name'] : '<span class="text-muted">없음</span>') . '</td>
                                <td><span class="badge ' . $status_class . '">' . $row['status'] . '</span></td>
                                <td>' . (!empty($row['template_name']) ? $row['template_name'] : '<span class="text-muted">없음</span>') . '</td>
                                <td>' . $row['block_count'] . '</td>
                                <td>' . substr($row['created_at'], 0, 10) . '</td>
                                <td>' . (!empty($row['published_at']) ? substr($row['published_at'], 0, 10) : '<span class="text-muted">-</span>') . '</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="landing_page_preview.php?id=' . $row['id'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> 보기</a>
                                        <button type="button" class="btn btn-sm btn-info dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <span class="sr-only">Toggle Dropdown</span>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="landing_page_edit.php?id=' . $row['id'] . '"><i class="fas fa-edit"></i> 수정</a>
                                            <a class="dropdown-item" href="landing_page_blocks.php?landing_id=' . $row['id'] . '"><i class="fas fa-th-large"></i> 블록 관리</a>
                                            <a class="dropdown-item" href="landing_page_preview.php?id=' . $row['id'] . '" target="_blank"><i class="fas fa-desktop"></i> 미리보기</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="landing_page_status.php?id=' . $row['id'] . '&status=게시됨"><i class="fas fa-check"></i> 게시</a>
                                            <a class="dropdown-item" href="landing_page_status.php?id=' . $row['id'] . '&status=초안"><i class="fas fa-pencil-alt"></i> 초안으로 변경</a>
                                            <a class="dropdown-item" href="landing_page_status.php?id=' . $row['id'] . '&status=보관됨"><i class="fas fa-archive"></i> 보관</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="landing_page_copy.php?id=' . $row['id'] . '"><i class="fas fa-copy"></i> 복제</a>
                                            <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteLandingPage(' . $row['id'] . ', \'' . addslashes($row['name']) . '\')"><i class="fas fa-trash"></i> 삭제</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            ';
                        }
                    } else {
                        echo '<tr><td colspan="8" class="text-center">등록된 랜딩페이지가 없습니다.</td></tr>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            
            <ul class="pagination justify-content-center">
                <?php echo list_paging($total_count, $page, $rows, $_SERVER['PHP_SELF'].$qstr); ?>
            </ul>
        </div>
    </div>
</div>

<!-- 랜딩페이지 삭제 확인 모달 -->
<div class="modal fade" id="deleteLandingPageModal" tabindex="-1" role="dialog" aria-labelledby="deleteLandingPageModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteLandingPageModalLabel">랜딩페이지 삭제 확인</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p>정말로 <strong id="landingPageNameToDelete"></strong> 랜딩페이지를 삭제하시겠습니까?</p>
                <p class="text-danger">이 작업은 되돌릴 수 없으며, 모든 관련 데이터(블록, 폼 데이터 등)도 함께 삭제됩니다.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">취소</button>
                <a class="btn btn-danger" id="confirmDeleteButton" href="#">삭제</a>
            </div>
        </div>
    </div>
</div>

<script>
// 랜딩페이지 삭제 확인 함수
function deleteLandingPage(id, name) {
    $('#landingPageNameToDelete').text(name);
    $('#confirmDeleteButton').attr('href', 'landing_page_delete.php?id=' + id);
    $('#deleteLandingPageModal').modal('show');
}
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>