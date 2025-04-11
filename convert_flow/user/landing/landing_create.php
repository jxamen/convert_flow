<?php
include_once("../../includes/_common.php");

if (!$is_member) {
    alert("로그인이 필요한 서비스입니다.", G5_BBS_URL."/login.php?url=".urlencode($_SERVER['REQUEST_URI']));
}

$menu_name1 = "랜딩페이지";
$menu_name2 = "새 랜딩페이지 만들기";

// 캠페인 목록 (랜딩페이지 연결용)
$sql_campaign = "SELECT id, name FROM campaigns WHERE user_id = '{$member['mb_id']}' ORDER BY created_at DESC";
$result_campaign = sql_query($sql_campaign);

// 템플릿 목록 조회
$sql = "SELECT id, name, industry, description, thumbnail_url FROM landing_page_templates ORDER BY id DESC";
$result = sql_query($sql);

include_once("../head.php");
?>

<div class="card border-0 rounded shadow p-4">
    <div class="d-flex justify-content-between mb-4">
        <h5 class="card-title fw-bold">새 랜딩페이지 만들기</h5>
        <a href="landing_list.php" class="btn btn-secondary">내 랜딩페이지 목록</a>
    </div>

    <form name="flandingform" id="flandingform" method="post" action="landing_create_update.php">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="name" class="form-label">랜딩페이지 이름 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                
                <div class="mb-3">
                    <label for="slug" class="form-label">URL 경로 <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><?php echo G5_URL; ?>/landing/</span>
                        <input type="text" class="form-control" id="slug" name="slug" required>
                    </div>
                    <div id="slug-feedback" class="form-text">영문, 숫자, 하이픈(-)만 사용 가능합니다.</div>
                </div>
                
                <div class="mb-3">
                    <label for="campaign_id" class="form-label">연결할 캠페인</label>
                    <select class="form-select" id="campaign_id" name="campaign_id">
                        <option value="">선택하지 않음</option>
                        <?php for ($i=0; $row=sql_fetch_array($result_campaign); $i++) { ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="meta_title" class="form-label">SEO 제목</label>
                    <input type="text" class="form-control" id="meta_title" name="meta_title">
                </div>
                
                <div class="mb-3">
                    <label for="meta_description" class="form-label">SEO 설명</label>
                    <textarea class="form-control" id="meta_description" name="meta_description" rows="4"></textarea>
                </div>
            </div>
        </div>

        <h5 class="mb-3">템플릿 선택</h5>
        <div class="row mb-3">
            <div class="col-12">
                <div class="btn-group mb-3" role="group">
                    <button type="button" class="btn btn-outline-primary active filter-btn" data-filter="all">전체</button>
                    <button type="button" class="btn btn-outline-primary filter-btn" data-filter="금융">금융</button>
                    <button type="button" class="btn btn-outline-primary filter-btn" data-filter="교육">교육</button>
                    <button type="button" class="btn btn-outline-primary filter-btn" data-filter="건강">건강</button>
                    <button type="button" class="btn btn-outline-primary filter-btn" data-filter="소매">소매</button>
                    <button type="button" class="btn btn-outline-primary filter-btn" data-filter="여행">여행</button>
                    <button type="button" class="btn btn-outline-primary filter-btn" data-filter="기타">기타</button>
                </div>
            </div>
        </div>

        <div class="row template-grid">
            <div class="col-lg-4 col-md-6 mb-4 template-item" data-industry="none">
                <div class="card h-100 border">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="ti ti-file-plus" style="font-size: 64px;"></i>
                        </div>
                        <h5 class="card-title">빈 페이지로 시작</h5>
                        <p class="card-text">처음부터 직접 디자인하기</p>
                        <div class="form-check justify-content-center mt-3">
                            <input class="form-check-input" type="radio" name="template_id" id="template_none" value="0" checked>
                            <label class="form-check-label" for="template_none">선택</label>
                        </div>
                    </div>
                </div>
            </div>

            <?php for ($i=0; $row=sql_fetch_array($result); $i++) { ?>
            <div class="col-lg-4 col-md-6 mb-4 template-item" data-industry="<?php echo $row['industry']; ?>">
                <div class="card h-100 border">
                    <?php if ($row['thumbnail_url']) { ?>
                    <img src="<?php echo $row['thumbnail_url']; ?>" class="card-img-top" alt="<?php echo $row['name']; ?>" style="height: 180px; object-fit: cover;">
                    <?php } else { ?>
                    <div class="bg-light text-center p-5">
                        <i class="ti ti-template" style="font-size: 48px;"></i>
                        <p class="mb-0">미리보기 없음</p>
                    </div>
                    <?php } ?>
                    
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0"><?php echo $row['name']; ?></h5>
                            <span class="badge bg-primary"><?php echo $row['industry']; ?></span>
                        </div>
                        <p class="card-text small"><?php echo cut_str($row['description'], 100); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="template_id" id="template_<?php echo $row['id']; ?>" value="<?php echo $row['id']; ?>">
                                <label class="form-check-label" for="template_<?php echo $row['id']; ?>">선택</label>
                            </div>
                            <a href="../../admin/landing/template_preview.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-secondary" target="_blank">미리보기</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
            <button type="button" class="btn btn-secondary me-md-2" onclick="location.href='landing_list.php';">취소</button>
            <button type="submit" class="btn btn-primary">다음</button>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // URL 슬러그 유효성 검사
    $('#slug').on('input', function() {
        var slug = $(this).val();
        var safeSlug = slug.replace(/[^a-z0-9\-]/g, '').toLowerCase();
        
        if (slug !== safeSlug) {
            $(this).val(safeSlug);
            $('#slug-feedback').addClass('text-danger').text('영문 소문자, 숫자, 하이픈(-)만 사용 가능합니다.');
        } else {
            $('#slug-feedback').removeClass('text-danger').text('영문, 숫자, 하이픈(-)만 사용 가능합니다.');
        }
        
        // 슬러그 중복 확인 (AJAX 요청)
        if (slug.length > 2) {
            $.ajax({
                url: '../../admin/landing/check_slug.php',
                type: 'POST',
                data: { 'slug': slug },
                dataType: 'json',
                success: function(data) {
                    if (!data.available) {
                        $('#slug-feedback').addClass('text-danger').text('이미 사용 중인 URL입니다. 다른 URL을 입력해주세요.');
                    }
                }
            });
        }
    });
    
    // 템플릿 필터링
    $('.filter-btn').click(function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        
        var industry = $(this).data('filter');
        
        if (industry === 'all') {
            $('.template-item').show();
        } else {
            $('.template-item').hide();
            $('.template-item[data-industry="' + industry + '"]').show();
        }
    });
    
    // 폼 제출 전 유효성 검사
    $('#flandingform').on('submit', function(e) {
        if ($('#name').val().trim() === '') {
            alert('랜딩페이지 이름을 입력해주세요.');
            $('#name').focus();
            e.preventDefault();
            return false;
        }
        
        if ($('#slug').val().trim() === '') {
            alert('URL 경로를 입력해주세요.');
            $('#slug').focus();
            e.preventDefault();
            return false;
        }
        
        // 슬러그 유효성 검사
        var slug = $('#slug').val();
        var safeSlug = slug.replace(/[^a-z0-9\-]/g, '').toLowerCase();
        
        if (slug !== safeSlug) {
            alert('URL 경로는 영문 소문자, 숫자, 하이픈(-)만 사용할 수 있습니다.');
            $('#slug').focus().val(safeSlug);
            e.preventDefault();
            return false;
        }
        
        return true;
    });
});
</script>

<?php
include_once("../tail.php");
?>