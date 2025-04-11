<?php
 include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

if ($is_admin != "super") {
    alert("관리자만 접근 가능합니다.");
}

$menu_name1 = "랜딩페이지";
$menu_name2 = "템플릿 관리";

// 페이지네이션 설정
$sql_total = "SELECT COUNT(*) as cnt FROM landing_page_templates";
$row_total = sql_fetch($sql_total);
$total_count = $row_total['cnt'];

$rows = 10;
$total_page = ceil($total_count / $rows);
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

// 검색 조건 처리
$sfl = isset($_GET['sfl']) ? $_GET['sfl'] : '';
$stx = isset($_GET['stx']) ? $_GET['stx'] : '';
$sst = isset($_GET['sst']) ? $_GET['sst'] : 'created_at';
$sod = isset($_GET['sod']) ? $_GET['sod'] : 'desc';

$sql_search = '';
if ($stx) {
    if ($sfl === 'name') {
        $sql_search = " WHERE name LIKE '%{$stx}%' ";
    } else if ($sfl === 'industry') {
        $sql_search = " WHERE industry = '{$stx}' ";
    } else if ($sfl === 'description') {
        $sql_search = " WHERE description LIKE '%{$stx}%' ";
    }
}

// 템플릿 목록 조회
$sql = "SELECT * FROM landing_page_templates {$sql_search} ORDER BY {$sst} {$sod} LIMIT {$from_record}, {$rows}";
$result = sql_query($sql);

// 헤더 포함
include_once CF_PATH .'/header.php';
?>

<div class="card border-0 rounded shadow p-4">
    <div class="d-flex justify-content-between mb-4">
        <h5 class="card-title fw-bold">랜딩페이지 템플릿 관리</h5>
        <a href="template_form.php" class="btn btn-primary">템플릿 추가</a>
    </div>

    <!-- 검색 영역 -->
    <div class="mb-4">
        <form id="fsearch" name="fsearch" class="row g-3" method="get">
            <div class="col-md-3">
                <select name="sfl" id="sfl" class="form-control">
                    <option value="name" <?php echo ($sfl === 'name') ? 'selected' : ''; ?>>템플릿 이름</option>
                    <option value="industry" <?php echo ($sfl === 'industry') ? 'selected' : ''; ?>>산업 분류</option>
                    <option value="description" <?php echo ($sfl === 'description') ? 'selected' : ''; ?>>설명</option>
                </select>
            </div>
            <div class="col-md-7">
                <input type="text" name="stx" id="stx" value="<?php echo $stx; ?>" class="form-control" placeholder="검색어 입력">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">검색</button>
            </div>
        </form>
    </div>

    <!-- 템플릿 목록 -->
    <div class="table-responsive shadow rounded">
        <table class="table table-center bg-white mb-0">
            <thead>
                <tr>
                    <th class="border-bottom p-3" style="min-width: 50px;">번호</th>
                    <th class="border-bottom p-3" style="min-width: 180px;">미리보기</th>
                    <th class="border-bottom p-3" style="min-width: 150px;">템플릿 이름</th>
                    <th class="border-bottom p-3" style="min-width: 100px;">산업 분류</th>
                    <th class="border-bottom p-3" style="min-width: 200px;">설명</th>
                    <th class="border-bottom p-3" style="min-width: 100px;">등록일</th>
                    <th class="border-bottom p-3" style="min-width: 150px;">관리</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (sql_num_rows($result) > 0) {
                    for ($i = 0; $row = sql_fetch_array($result); $i++) {
                        $num = $total_count - ($page - 1) * $rows - $i;
                        $industry_name = '';
                        switch($row['industry']) {
                            case '금융': $industry_name = '<span class="badge bg-primary">금융</span>'; break;
                            case '교육': $industry_name = '<span class="badge bg-success">교육</span>'; break;
                            case '건강': $industry_name = '<span class="badge bg-info">건강</span>'; break;
                            case '소매': $industry_name = '<span class="badge bg-warning">소매</span>'; break;
                            case '여행': $industry_name = '<span class="badge bg-danger">여행</span>'; break;
                            default: $industry_name = '<span class="badge bg-secondary">기타</span>'; break;
                        }
                ?>
                <tr>
                    <td class="p-3"><?php echo $num; ?></td>
                    <td class="p-3">
                        <?php if ($row['thumbnail_url']) { ?>
                            <img src="<?php echo $row['thumbnail_url']; ?>" alt="<?php echo $row['name']; ?>" class="img-fluid rounded" style="max-height: 80px;">
                        <?php } else { ?>
                            <div class="bg-light text-center p-3 rounded">이미지 없음</div>
                        <?php } ?>
                    </td>
                    <td class="p-3"><?php echo $row['name']; ?></td>
                    <td class="p-3"><?php echo $industry_name; ?></td>
                    <td class="p-3"><?php echo cut_str($row['description'], 50); ?></td>
                    <td class="p-3"><?php echo substr($row['created_at'], 0, 10); ?></td>
                    <td class="p-3">
                        <a href="template_form.php?w=u&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">수정</a>
                        <a href="#" class="btn btn-sm btn-danger btn-template-delete" data-id="<?php echo $row['id']; ?>">삭제</a>
                        <a href="template_preview.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" target="_blank">미리보기</a>
                    </td>
                </tr>
                <?php
                    }
                } else {
                ?>
                <tr>
                    <td colspan="7" class="text-center p-3">등록된 템플릿이 없습니다.</td>
                </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- 페이지네이션 -->
    <div class="mt-4">
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mb-0">
                <?php
                list_paging($total_count, $page, $rows, $_SERVER['PHP_SELF'].$qstr);
                ?>
            </ul>
        </nav>
    </div>
</div>

<script>
$(document).ready(function() {
    // 템플릿 삭제 기능
    $('.btn-template-delete').click(function(e) {
        e.preventDefault();
        
        var templateId = $(this).data('id');
        
        if (confirm('이 템플릿을 삭제하시겠습니까?')) {
            $.ajax({
                url: 'template_delete.php',
                type: 'POST',
                data: {
                    'id': templateId
                },
                dataType: 'json',
                success: function(data) {
                    if (data.status == 'success') {
                        alert('템플릿이 삭제되었습니다.');
                        location.reload();
                    } else {
                        alert('삭제 실패: ' + data.message);
                    }
                },
                error: function() {
                    alert('서버 통신 오류가 발생했습니다.');
                }
            });
        }
    });
});
</script>

<?php
include_once CF_PATH . '/footer.php';
?>