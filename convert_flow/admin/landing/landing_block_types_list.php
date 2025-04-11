<?php
/**
 * 랜딩페이지 블록 타입 목록
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 관리자 권한 체크
if (!$is_admin) {
    alert('관리자만 접근할 수 있습니다.');
    goto_url(CF_URL);
}

// 페이지 제목
$page_title = "블록 타입 관리";

// 페이지 번호
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$rows = 15;
$offset = ($page - 1) * $rows;

// 검색 조건
$search_field = isset($_GET['search_field']) ? $_GET['search_field'] : '';
$search_keyword = isset($_GET['search_keyword']) ? trim($_GET['search_keyword']) : '';
$search_category = isset($_GET['search_category']) ? $_GET['search_category'] : '';

// 블록 모델 로드
require_once CF_MODEL_PATH . '/landing_block.model.php';
$block_model = new LandingBlockModel();

// 검색 WHERE 조건 구성
$search_where = '';
if ($search_field && $search_keyword) {
    $search_where .= " AND {$search_field} LIKE '%" . sql_escape_string($search_keyword) . "%'";
}
if ($search_category) {
    $search_where .= " AND category = '" . sql_escape_string($search_category) . "'";
}

// 전체 블록 타입 개수 조회
$sql = "SELECT COUNT(*) as cnt 
       FROM landing_block_types 
       WHERE (1) {$search_where}";
$row = sql_fetch($sql);
$total_count = $row['cnt'];
$total_page = ceil($total_count / $rows);

// 블록 타입 목록 조회
$sql = "SELECT * 
       FROM landing_block_types
       WHERE (1) {$search_where}
       ORDER BY category, name
       LIMIT $offset, $rows";
$result = sql_query($sql);

// 블록 카테고리 목록 조회 (필터용)
$category_sql = "SELECT DISTINCT category FROM landing_block_types ORDER BY category";
$category_result = sql_query($category_sql);

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">블록 타입 관리</h1>
    <p class="mb-4">랜딩페이지 블록 타입을 관리합니다. 카테고리별로 블록 타입을 추가하고 수정할 수 있습니다.</p>

    <!-- 검색 및 필터 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">검색 및 필터</h6>
        </div>
        <div class="card-body">
            <form method="get" action="landing_block_types_list.php" class="form-inline">
                <div class="form-group mr-2 mb-2">
                    <select name="search_field" class="form-control">
                        <option value="name" <?php echo ($search_field == 'name') ? 'selected' : ''; ?>>타입명</option>
                        <option value="description" <?php echo ($search_field == 'description') ? 'selected' : ''; ?>>설명</option>
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <input type="text" name="search_keyword" class="form-control" value="<?php echo $search_keyword; ?>" placeholder="검색어 입력">
                </div>
                <div class="form-group mr-2 mb-2">
                    <select name="search_category" class="form-control">
                        <option value="">카테고리 전체</option>
                        <?php
                        while ($category = sql_fetch_array($category_result)) {
                            $selected = ($search_category == $category['category']) ? 'selected' : '';
                            echo '<option value="' . $category['category'] . '" ' . $selected . '>' . $category['category'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mb-2"><i class="fas fa-search"></i> 검색</button>
                <a href="landing_block_types_list.php" class="btn btn-secondary mb-2 ml-2"><i class="fas fa-sync-alt"></i> 초기화</a>
            </form>
        </div>
    </div>

    <!-- 블록 타입 목록 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">블록 타입 목록</h6>
            <a href="landing_block_types_form.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> 새 블록 타입</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>이름</th>
                        <th>카테고리</th>
                        <th>설명</th>
                        <th>아이콘</th>
                        <th>등록일</th>
                        <th>관리</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    if (sql_num_rows($result) > 0) {
                        while ($row = sql_fetch_array($result)) {
                            echo '
                            <tr>
                                <td>' . $row['id'] . '</td>
                                <td>' . $row['name'] . '</td>
                                <td><span class="badge badge-info">' . $row['category'] . '</span></td>
                                <td>' . nl2br($row['description']) . '</td>
                                <td>';
                            
                            if (!empty($row['icon'])) {
                                echo '<i class="' . $row['icon'] . '"></i> ' . $row['icon'];
                            } else {
                                echo '<span class="text-muted">없음</span>';
                            }
                            
                            echo '</td>
                                <td>' . substr($row['created_at'], 0, 16) . '</td>
                                <td>
                                    <a href="landing_block_types_form.php?id=' . $row['id'] . '" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> 수정</a>
                                    <a href="javascript:void(0);" onclick="deleteBlockType(' . $row['id'] . ', \'' . addslashes($row['name']) . '\')" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> 삭제</a>
                                </td>
                            </tr>
                            ';
                        }
                    } else {
                        echo '<tr><td colspan="7" class="text-center">등록된 블록 타입이 없습니다.</td></tr>';
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

<!-- 블록 타입 삭제 확인 모달 -->
<div class="modal fade" id="deleteBlockTypeModal" tabindex="-1" role="dialog" aria-labelledby="deleteBlockTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteBlockTypeModalLabel">블록 타입 삭제 확인</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p>정말로 <strong id="blockTypeNameToDelete"></strong> 블록 타입을 삭제하시겠습니까?</p>
                <p class="text-danger">주의: 이 블록 타입을 사용하는 모든 블록 템플릿이 함께 삭제됩니다.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">취소</button>
                <a href="javascript:void(0);" id="confirmDeleteButton" class="btn btn-danger float-right">삭제</a>
            </div>
        </div>
    </div>
</div>

<script>
// 블록 타입 삭제 확인 함수
function deleteBlockType(id, name) {
    $('#blockTypeNameToDelete').text(name);

    $('#confirmDeleteButton').off('click').on('click', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '<?php echo CF_ADMIN_URL; ?>/ajax.action.php',
            type: 'POST',
            data: {
                action: 'delete_block_type',
                id: id,
                <?php echo get_admin_token_values(); ?>
            },
            dataType: 'json',
            success: function(response) {
                $('#deleteBlockTypeModal').modal('hide');
                
                if (response.success) {
                    toastr.success(response.message);
                    // 성공 시 페이지 새로고침
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                $('#deleteBlockTypeModal').modal('hide');
                toastr.error('서버 요청 중 오류가 발생했습니다.');
            }
        });
    });
    
    $('#deleteBlockTypeModal').modal('show');
}
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>