<?php
/**
 * 폼 목록 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 페이지 제목
$page_title = "폼 관리";

// 페이지 번호
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$rows = 10;
$offset = ($page - 1) * $rows;

// 검색 조건
$search_field = isset($_GET['search_field']) ? $_GET['search_field'] : '';
$search_keyword = isset($_GET['search_keyword']) ? trim($_GET['search_keyword']) : '';

// 폼 모델 로드
require_once CF_MODEL_PATH . '/form.model.php';
$form_model = new FormModel();

// 관리자가 아닌 경우 자신의 폼만 볼 수 있음
$user_condition = '';
if (!$is_admin) {
    $user_condition = " AND f.user_id = '{$member['id']}'";
}

// 검색 WHERE 조건 구성
$search_where = '';
if ($search_field && $search_keyword) {
    $search_where .= " AND {$search_field} LIKE '%" . sql_escape_string($search_keyword) . "%'";
}

// 전체 폼 개수 조회
$sql = "SELECT COUNT(*) as cnt 
       FROM {$cf_table_prefix}forms f 
       WHERE (1) {$search_where} {$user_condition}";
$row = sql_fetch($sql);
$total_count = $row['cnt'];
$total_page = ceil($total_count / $rows);

// 폼 목록 조회
$sql = "SELECT f.*, 
        l.name as landing_page_name,
        (SELECT COUNT(*) FROM {$cf_table_prefix}form_fields WHERE form_id = f.id) as field_count,
        (SELECT COUNT(*) FROM {$cf_table_prefix}leads WHERE form_id = f.id) as lead_count
       FROM {$cf_table_prefix}forms f
       LEFT JOIN {$cf_table_prefix}landing_pages l ON f.landing_page_id = l.id
       WHERE (1) {$search_where} {$user_condition}
       ORDER BY f.created_at DESC
       LIMIT $offset, $rows";
$result = sql_query($sql);

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">폼 관리</h1>
    <p class="mb-4">사용자 데이터를 수집하는 폼을 관리하고 새로운 폼을 생성하세요.</p>

    <!-- 검색 및 필터 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">검색 및 필터</h6>
        </div>
        <div class="card-body">
            <form method="get" action="form_list.php" class="form-inline">
                <div class="form-group mr-2 mb-2">
                    <select name="search_field" class="form-control">
                        <option value="f.name" <?php echo ($search_field == 'f.name') ? 'selected' : ''; ?>>폼 이름</option>
                        <option value="f.submit_button_text" <?php echo ($search_field == 'f.submit_button_text') ? 'selected' : ''; ?>>제출 버튼</option>
                        <option value="l.name" <?php echo ($search_field == 'l.name') ? 'selected' : ''; ?>>랜딩페이지</option>
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <input type="text" name="search_keyword" class="form-control" value="<?php echo $search_keyword; ?>" placeholder="검색어 입력">
                </div>
                <button type="submit" class="btn btn-primary mb-2"><i class="fas fa-search"></i> 검색</button>
                <a href="form_list.php" class="btn btn-secondary mb-2 ml-2"><i class="fas fa-sync-alt"></i> 초기화</a>
            </form>
        </div>
    </div>

    <!-- 폼 목록 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">폼 목록</h6>
            <a href="form_create.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> 새 폼 생성</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th>이름</th>
                        <th>연결된 랜딩페이지</th>
                        <th>필드 수</th>
                        <th>다단계</th>
                        <th>리드 수</th>
                        <th>생성일</th>
                        <th>관리</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    if (sql_num_rows($result) > 0) {
                        while ($row = sql_fetch_array($result)) {
                            // 다단계 여부
                            $multi_step = $row['is_multi_step'] ? '<span class="badge badge-success">예</span>' : '<span class="badge badge-secondary">아니오</span>';
                            
                            echo '
                            <tr>
                                <td>' . $row['name'] . '</td>
                                <td>' . (!empty($row['landing_page_name']) ? $row['landing_page_name'] : '<span class="text-muted">없음</span>') . '</td>
                                <td>' . $row['field_count'] . '</td>
                                <td>' . $multi_step . '</td>
                                <td>' . $row['lead_count'] . '</td>
                                <td>' . substr($row['created_at'], 0, 10) . '</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="form_edit.php?id=' . $row['id'] . '" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> 수정</a>
                                        <button type="button" class="btn btn-sm btn-info dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <span class="sr-only">Toggle Dropdown</span>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="form_fields.php?form_id=' . $row['id'] . '"><i class="fas fa-list"></i> 필드 관리</a>
                                            <a class="dropdown-item" href="form_view.php?id=' . $row['id'] . '" target="_blank"><i class="fas fa-eye"></i> 폼 보기</a>
                                            <a class="dropdown-item" href="form_embed.php?id=' . $row['id'] . '"><i class="fas fa-code"></i> 임베드 코드</a>
                                            <a class="dropdown-item" href="form_api.php?id=' . $row['id'] . '"><i class="fas fa-plug"></i> API 연동</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="form_copy.php?id=' . $row['id'] . '"><i class="fas fa-copy"></i> 복제</a>
                                            <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteForm(' . $row['id'] . ', \'' . addslashes($row['name']) . '\')"><i class="fas fa-trash"></i> 삭제</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            ';
                        }
                    } else {
                        echo '<tr><td colspan="7" class="text-center">등록된 폼이 없습니다.</td></tr>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            
            <ul class="pagination justify-content-center">
                <?php echo list_paging($total_count, $page, $rows, $_SERVER['PHP_SELF'] . '?' . $qstr); ?>
            </ul>
        </div>
    </div>
</div>

<!-- 폼 삭제 확인 모달 -->
<div class="modal fade" id="deleteFormModal" tabindex="-1" role="dialog" aria-labelledby="deleteFormModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteFormModalLabel">폼 삭제 확인</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p>정말로 <strong id="formNameToDelete"></strong> 폼을 삭제하시겠습니까?</p>
                <p class="text-danger">이 작업은 되돌릴 수 없으며, 모든 관련 데이터(필드, 조건부 로직, 리드 데이터)도 함께 삭제됩니다.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">취소</button>
                <a class="btn btn-danger" id="confirmDeleteButton" href="#">삭제</a>
            </div>
        </div>
    </div>
</div>

<script>
// 폼 삭제 확인 함수
function deleteForm(id, name) {
    $('#formNameToDelete').text(name);
    $('#confirmDeleteButton').attr('href', 'form_delete.php?id=' + id);
    $('#deleteFormModal').modal('show');
}
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>