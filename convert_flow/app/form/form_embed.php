<?php
/**
 * 폼 임베드 코드 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 폼 ID
$form_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($form_id <= 0) {
    alert('잘못된 접근입니다.', 'form_list.php');
}

// 폼 모델 로드
require_once CF_MODEL_PATH . '/form.model.php';
$form_model = new FormModel();

// 폼 정보 조회
$form = $form_model->get_form($form_id);
if (!$form) {
    alert('존재하지 않는 폼입니다.', 'form_list.php');
}

// 권한 체크 (관리자가 아니면서 폼 소유자가 아닌 경우)
if (!$is_admin && $form['user_id'] != $member['id']) {
    alert('권한이 없습니다.', 'form_list.php');
}

// 임베드 코드 생성
$embed_code = $form_model->get_embed_code($form_id);

// 페이지 제목
$page_title = "폼 임베드 코드: " . $form['name'];

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">폼 임베드 코드: <?php echo $form['name']; ?></h1>
    <p class="mb-4">외부 웹사이트에 폼을 삽입하기 위한 코드입니다. 웹사이트 HTML에 붙여넣으세요.</p>
    
    <!-- 폼 임베드 코드 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">임베드 코드</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 두 가지 임베드 방식 중 선택하여 사용할 수 있습니다. 일반적으로 스크립트 방식이 더 나은 통합 경험을 제공합니다.
            </div>
            
            <div class="form-group">
                <label for="iframe-code">iframe 방식</label>
                <textarea class="form-control" id="iframe-code" rows="3" readonly><?php echo htmlspecialchars($embed_code['iframe']); ?></textarea>
                <small class="form-text text-muted">가장 간단한 방식이지만, 스타일 통합이 어려울 수 있습니다.</small>
            </div>
            
            <div class="form-group">
                <label for="script-code">자바스크립트 방식 (권장)</label>
                <textarea class="form-control" id="script-code" rows="3" readonly><?php echo htmlspecialchars($embed_code['script']); ?></textarea>
                <small class="form-text text-muted">더 나은 통합 경험을 제공하며, 페이지 스타일과 자연스럽게 어울립니다.</small>
            </div>
            
            <div class="text-center">
                <button type="button" class="btn btn-primary" onclick="copyToClipboard('iframe-code')">
                    <i class="fas fa-copy"></i> iframe 코드 복사
                </button>
                <button type="button" class="btn btn-primary ml-2" onclick="copyToClipboard('script-code')">
                    <i class="fas fa-copy"></i> 스크립트 코드 복사
                </button>
            </div>
        </div>
    </div>
    
    <!-- 미리보기 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">폼 미리보기</h6>
        </div>
        <div class="card-body">
            <div class="embed-responsive embed-responsive-16by9">
                <iframe class="embed-responsive-item" src="<?php echo CF_FORM_URL; ?>/form_view.php?id=<?php echo $form_id; ?>"></iframe>
            </div>
            
            <div class="text-center mt-3">
                <a href="form_edit.php?id=<?php echo $form_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 폼 수정으로 돌아가기
                </a>
                <a href="form_view.php?id=<?php echo $form_id; ?>" class="btn btn-primary" target="_blank">
                    <i class="fas fa-external-link-alt"></i> 새 창에서 보기
                </a>
            </div>
        </div>
    </div>
    
    <!-- 사용 방법 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">임베드 사용 방법</h6>
        </div>
        <div class="card-body">
            <h5 class="font-weight-bold">1. 코드 복사</h5>
            <p>위에서 제공된 iframe 또는 스크립트 코드를 복사하세요.</p>
            
            <h5 class="font-weight-bold">2. 웹사이트에 삽입</h5>
            <p>복사한 코드를 웹사이트의 HTML 코드에 붙여넣으세요. 폼을 표시하고 싶은 위치에 넣어야 합니다.</p>
            
            <h5 class="font-weight-bold">3. 스타일 조정 (선택 사항)</h5>
            <p>iframe을 사용하는 경우, 필요에 따라 width와 height 속성을 조정하여 폼 크기를 변경할 수 있습니다.</p>
            
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 주의: 폼을 수정하면 임베드된 모든 폼에 자동으로 반영됩니다. 중요한 변경 전에는 미리 테스트하세요.
            </div>
        </div>
    </div>
</div>

<script>
// 클립보드 복사 함수
function copyToClipboard(elementId) {
    var el = document.getElementById(elementId);
    el.select();
    document.execCommand('copy');
    
    // 복사 성공 메시지
    alert('코드가 클립보드에 복사되었습니다.');
}
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>