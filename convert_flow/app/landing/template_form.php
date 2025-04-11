<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

if ($is_admin != "super") {
    alert("관리자만 접근 가능합니다.");
}

$menu_name1 = "랜딩페이지";
$menu_name2 = "템플릿 관리";

$w = isset($_GET['w']) ? $_GET['w'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$template = array();
if ($w === 'u' && $id > 0) {
    $sql = "SELECT * FROM landing_page_templates WHERE id = '{$id}'";
    $template = sql_fetch($sql);
    
    if (!$template['id']) {
        alert('존재하지 않는 템플릿입니다.');
    }
}

$title = ($w === 'u') ? '템플릿 수정' : '템플릿 등록';

// 헤더 포함
include_once CF_PATH .'/header.php';
?>

<div class="card border-0 rounded shadow p-4">
    <div class="d-flex justify-content-between mb-4">
        <h5 class="card-title fw-bold"><?php echo $title; ?></h5>
        <a href="template_list.php" class="btn btn-secondary">목록으로</a>
    </div>

    <form name="ftemplateform" id="ftemplateform" method="post" action="template_form_update.php" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="w" value="<?php echo $w; ?>">
        <input type="hidden" name="id" value="<?php echo $id; ?>">

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="name" class="form-label">템플릿 이름 <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" required value="<?php echo isset($template['name']) ? $template['name'] : ''; ?>">
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="industry" class="form-label">산업 분류 <span class="text-danger">*</span></label>
                <select class="form-control" id="industry" name="industry" required>
                    <option value="">선택하세요</option>
                    <option value="금융" <?php echo (isset($template['industry']) && $template['industry'] === '금융') ? 'selected' : ''; ?>>금융</option>
                    <option value="교육" <?php echo (isset($template['industry']) && $template['industry'] === '교육') ? 'selected' : ''; ?>>교육</option>
                    <option value="건강" <?php echo (isset($template['industry']) && $template['industry'] === '건강') ? 'selected' : ''; ?>>건강</option>
                    <option value="소매" <?php echo (isset($template['industry']) && $template['industry'] === '소매') ? 'selected' : ''; ?>>소매</option>
                    <option value="여행" <?php echo (isset($template['industry']) && $template['industry'] === '여행') ? 'selected' : ''; ?>>여행</option>
                    <option value="기타" <?php echo (isset($template['industry']) && $template['industry'] === '기타') ? 'selected' : ''; ?>>기타</option>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">템플릿 설명</label>
            <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($template['description']) ? $template['description'] : ''; ?></textarea>
        </div>

        <div class="mb-3">
            <label for="thumbnail" class="form-label">미리보기 이미지</label>
            <?php if (isset($template['thumbnail_url']) && $template['thumbnail_url']) { ?>
            <div class="mb-2">
                <img src="<?php echo $template['thumbnail_url']; ?>" alt="현재 이미지" class="img-fluid rounded" style="max-height: 200px;">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="thumbnail_del" id="thumbnail_del" value="1">
                    <label class="form-check-label" for="thumbnail_del">이미지 삭제</label>
                </div>
            </div>
            <?php } ?>
            <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
            <div class="form-text">권장 크기: 800x600px, 최대 2MB</div>
        </div>

        <div class="mb-3">
            <label for="html_template" class="form-label">HTML 코드 <span class="text-danger">*</span></label>
            <textarea class="form-control" id="html_template" name="html_template" rows="10" required><?php echo isset($template['html_template']) ? htmlspecialchars($template['html_template']) : ''; ?></textarea>
        </div>

        <div class="mb-3">
            <label for="css_template" class="form-label">CSS 코드</label>
            <textarea class="form-control" id="css_template" name="css_template" rows="8"><?php echo isset($template['css_template']) ? $template['css_template'] : ''; ?></textarea>
        </div>

        <div class="mb-3">
            <label for="js_template" class="form-label">JavaScript 코드</label>
            <textarea class="form-control" id="js_template" name="js_template" rows="6"><?php echo isset($template['js_template']) ? $template['js_template'] : ''; ?></textarea>
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <button type="button" class="btn btn-secondary me-md-2" onclick="location.href='template_list.php';">취소</button>
            <button type="button" class="btn btn-success me-md-2" id="btn-preview">미리보기</button>
            <button type="submit" class="btn btn-primary" id="btn-submit"><?php echo ($w === 'u') ? '수정하기' : '등록하기'; ?></button>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // 코드 에디터 적용
    $('#html_template, #css_template, #js_template').each(function() {
        $(this).on('keydown', function(e) {
            if (e.keyCode === 9) { // 탭 키
                e.preventDefault();
                
                var start = this.selectionStart;
                var end = this.selectionEnd;
                
                // 탭 문자를 삽입
                this.value = this.value.substring(0, start) + "    " + this.value.substring(end);
                
                // 커서 위치 조정
                this.selectionStart = this.selectionEnd = start + 4;
            }
        });
    });
    
    // 폼 제출 전 유효성 검사
    $('#ftemplateform').on('submit', function(e) {
        if ($('#name').val().trim() === '') {
            alert('템플릿 이름을 입력해주세요.');
            $('#name').focus();
            e.preventDefault();
            return false;
        }
        
        if ($('#industry').val() === '') {
            alert('산업 분류를 선택해주세요.');
            $('#industry').focus();
            e.preventDefault();
            return false;
        }
        
        if ($('#html_template').val().trim() === '') {
            alert('HTML 코드를 입력해주세요.');
            $('#html_template').focus();
            e.preventDefault();
            return false;
        }
        
        return true;
    });
    
    // 미리보기 기능
    $('#btn-preview').click(function() {
        // 임시 폼 생성
        var $form = $('<form>', {
            action: 'template_preview.php',
            method: 'post',
            target: '_blank'
        });
        
        // 필요한 데이터 추가
        $form.append($('<input>', {
            type: 'hidden',
            name: 'html_template',
            value: $('#html_template').val()
        }));
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'css_template',
            value: $('#css_template').val()
        }));
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'js_template',
            value: $('#js_template').val()
        }));
        
        // 폼 제출
        $form.appendTo('body').submit().remove();
    });
});
</script>

<?php
include_once CF_PATH . '/footer.php';
?>