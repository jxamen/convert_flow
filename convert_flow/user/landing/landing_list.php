<?php
include_once("../../includes/_common.php");

if (!$is_member) {
    alert("로그인이 필요한 서비스입니다.", G5_BBS_URL."/login.php?url=".urlencode($_SERVER['REQUEST_URI']));
}

$menu_name1 = "랜딩페이지";
$menu_name2 = "내 랜딩페이지";

// 페이지네이션 설정
$sql_total = "SELECT COUNT(*) as cnt FROM landing_pages WHERE user_id = '{$member['mb_id']}'";
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
        $sql_search = " AND name LIKE '%{$stx}%' ";
    } else if ($sfl === 'slug') {
        $sql_search = " AND slug LIKE '%{$stx}%' ";
    }
}

// 랜딩페이지 목록 조회
$sql = "SELECT * FROM landing_pages WHERE user_id = '{$member['mb_id']}' {$sql_search} ORDER BY {$sst} {$sod} LIMIT {$from_record}, {$rows}";
$result = sql_query($sql);

include_once("../head.php");
?>

<div class="card border-0 rounded shadow p-4">
    <div class="d-flex justify-content-between mb-4">
        <h5 class="card-title fw-bold">내 랜딩페이지</h5>
        <a href="landing_create.php" class="btn btn-primary">새 랜딩페이지 만들기</a>
    </div>

    <!-- 검색 영역 -->
    <div class="mb-4">
        <form id="fsearch" name="fsearch" class="row g-3" method="get">
            <div class="col-md-3">
                <select name="sfl" id="sfl" class="form-select">
                    <option value="name" <?php echo ($sfl === 'name') ? 'selected' : ''; ?>>페이지 이름</option>
                    <option value="slug" <?php echo ($sfl === 'slug') ? 'selected' : ''; ?>>URL 경로</option>
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

    <!-- 랜딩페이지 목록 -->
    <div class="table-responsive shadow rounded">
        <table class="table table-center bg-white mb-0">
            <thead>
                <tr>
                    <th class="border-bottom p-3" style="min-width: 50px;">번호</th>
                    <th class="border-bottom p-3" style="min-width: 250px;">페이지 이름</th>
                    <th class="border-bottom p-3" style="min-width: 180px;">URL</th>
                    <th class="border-bottom p-3" style="min-width: 100px;">상태</th>
                    <th class="border-bottom p-3" style="min-width: 100px;">등록일</th>
                    <th class="border-bottom p-3" style="min-width: 150px;">관리</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (sql_num_rows($result) > 0) {
                    for ($i = 0; $row = sql_fetch_array($result); $i++) {
                        $num = $total_count - ($page - 1) * $rows - $i;
                        $status_name = '';
                        switch($row['status']) {
                            case '초안': $status_name = '<span class="badge bg-secondary">초안</span>'; break;
                            case '게시됨': $status_name = '<span class="badge bg-success">게시됨</span>'; break;
                            case '보관됨': $status_name = '<span class="badge bg-info">보관됨</span>'; break;
                            default: $status_name = '<span class="badge bg-secondary">초안</span>'; break;
                        }
                        $landing_url = G5_URL . '/landing/' . $row['slug'];
                ?>
                <tr>
                    <td class="p-3"><?php echo $num; ?></td>
                    <td class="p-3"><?php echo $row['name']; ?></td>
                    <td class="p-3">
                        <a href="<?php echo $landing_url; ?>" target="_blank" class="text-primary"><?php echo $row['slug']; ?></a>
                    </td>
                    <td class="p-3"><?php echo $status_name; ?></td>
                    <td class="p-3"><?php echo substr($row['created_at'], 0, 10); ?></td>
                    <td class="p-3">
                        <a href="landing_editor.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">편집</a>
                        <a href="landing_view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" target="_blank">미리보기</a>
                        <a href="#" class="btn btn-sm btn-danger btn-landing-delete" data-id="<?php echo $row['id']; ?>">삭제</a>
                    </td>
                </tr>
                <?php
                    }
                } else {
                ?>
                <tr>
                    <td colspan="6" class="text-center p-3">등록된 랜딩페이지가 없습니다.</td>
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
                $start_page = (((int)(($page - 1) / 10)) * 10) + 1;
                $end_page = $start_page + 9;
                if ($end_page > $total_page) $end_page = $total_page;

                if ($start_page > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=1'
                        . ($sfl ? '&sfl=' . $sfl : '')
                        . ($stx ? '&stx=' . $stx : '')
                        . '">처음</a></li>';
                }

                if ($page > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=' . ($page - 1)
                        . ($sfl ? '&sfl=' . $sfl : '')
                        . ($stx ? '&stx=' . $stx : '')
                        . '">이전</a></li>';
                }

                for ($i = $start_page; $i <= $end_page; $i++) {
                    $active = ($i == $page) ? 'active' : '';
                    echo '<li class="page-item ' . $active . '"><a class="page-link" href="?page=' . $i
                        . ($sfl ? '&sfl=' . $sfl : '')
                        . ($stx ? '&stx=' . $stx : '')
                        . '">' . $i . '</a></li>';
                }

                if ($page < $total_page) {
                    echo '<li class="page-item"><a class="page-link" href="?page=' . ($page + 1)
                        . ($sfl ? '&sfl=' . $sfl : '')
                        . ($stx ? '&stx=' . $stx : '')
                        . '">다음</a></li>';
                }

                if ($end_page < $total_page) {
                    echo '<li class="page-item"><a class="page-link" href="?page=' . $total_page
                        . ($sfl ? '&sfl=' . $sfl : '')
                        . ($stx ? '&stx=' . $stx : '')
                        . '">마지막</a></li>';
                }
                ?>
            </ul>
        </nav>
    </div>
</div>

<script>
$(document).ready(function() {
    // 랜딩페이지 삭제 기능
    $('.btn-landing-delete').click(function(e) {
        e.preventDefault();
        
        var landingId = $(this).data('id');
        
        if (confirm('이 랜딩페이지를 삭제하시겠습니까?')) {
            $.ajax({
                url: 'landing_delete.php',
                type: 'POST',
                data: {
                    'id': landingId
                },
                dataType: 'json',
                success: function(data) {
                    if (data.status == 'success') {
                        alert('랜딩페이지가 삭제되었습니다.');
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
include_once("../tail.php");
?>