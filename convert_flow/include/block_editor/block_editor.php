<?php
/**
 * 블록 에디터 공통 모듈
 * 관리자 및 사용자 페이지에서 모달로 사용 가능
 */
if (!defined('_CONVERT_FLOW_')) exit;

// 파라미터 확인
$template_id = isset($params['template_id']) ? intval($params['template_id']) : 0;
$block_id = isset($params['block_id']) ? intval($params['block_id']) : 0;
$landing_id = isset($params['landing_id']) ? intval($params['landing_id']) : 0;
$mode = isset($params['mode']) ? $params['mode'] : 'modal'; // modal 또는 page
$ajax_url = isset($params['ajax_url']) ? $params['ajax_url'] : CF_LANDING_URL . '/ajax.action.php';

// 블록 모델 로드
//require_once CF_MODEL_PATH . '/landing_block.model.php';
//$block_model = new LandingBlockModel();

// 블록이나 템플릿 정보 직접 조회 (get_block 메소드 대신 직접 SQL 사용)
$template = null;
$block = null;

if ($template_id > 0) {
    // 템플릿 정보 조회
    $sql = "SELECT * FROM landing_block_templates WHERE id = " . intval($template_id);
    $template = sql_fetch($sql);
    
    // default_settings를 JSON 디코드
    if ($template && isset($template['default_settings']) && !empty($template['default_settings'])) {
        $template['default_settings'] = json_decode($template['default_settings'], true);
    }
} else if ($block_id > 0) {
    // 블록 인스턴스 정보 조회
    $sql = "SELECT * FROM landing_page_block_instances WHERE id = " . intval($block_id);
    $block = sql_fetch($sql);
    
    // settings를 JSON 디코드
    if ($block && isset($block['settings']) && !empty($block['settings'])) {
        $block['settings'] = json_decode($block['settings'], true);
    }
    
    if ($block) {
        // 연결된 템플릿 정보 조회
        $sql = "SELECT * FROM landing_block_templates WHERE id = " . intval($block['block_template_id']);
        $template = sql_fetch($sql);
        
        // default_settings를 JSON 디코드
        if ($template && isset($template['default_settings']) && !empty($template['default_settings'])) {
            $template['default_settings'] = json_decode($template['default_settings'], true);
        }
    }
}

// 없는 경우 오류 처리
if (!$template && !$block) {
    echo '<div class="alert alert-danger">존재하지 않는 블록 또는 템플릿입니다.</div>';
    return;
}

// 데이터 준비
$html_content = '';
$css_content = '';
$js_content = '';

if ($block_id > 0 && $block) {
    // 블록 인스턴스인 경우
    $html_content = !empty($block['custom_html']) ? $block['custom_html'] : $template['html_content'];
    $css_content = !empty($block['custom_css']) ? $block['custom_css'] : $template['css_content'];
} else if ($template) {
    // 템플릿인 경우
    $html_content = $template['html_content'];
    $css_content = $template['css_content'];
    $js_content = isset($template['js_content']) ? $template['js_content'] : '';
}

// CSS 내용에서 이스케이프된 따옴표 처리
$css_content = stripslashes($css_content);
$css_content = str_replace('\\\'', '\'', $css_content);
$css_content = str_replace('\\"', '"', $css_content);

// HTML 내용에서 이스케이프된 따옴표 처리
$html_content = stripslashes($html_content);
$html_content = str_replace('\\\'', '\'', $html_content);
$html_content = str_replace('\\"', '"', $html_content);

// JavaScript 내용에서 이스케이프된 따옴표 처리
$js_content = stripslashes($js_content);
$js_content = str_replace('\\\'', '\'', $js_content);
$js_content = str_replace('\\"', '"', $js_content);
?>

<?php if ($mode == 'modal'): // 모달 모드일 때 컨테이너 추가 ?>
<!-- 모달 모드일 때의 컨테이너 -->
<div class="block-editor-container">
<?php endif; ?>

<!-- 에디터 UI -->
<div class="block-editor-wrapper">
    <h3 class="block-editor-title mb-3">
        <?php echo $template ? $template['name'] : '블록 에디터'; ?>
    </h3>

    <!-- 편집 모드 선택 탭 -->
    <ul class="nav nav-tabs mb-4" id="editorTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="visual-tab" data-toggle="tab" href="#visual" role="tab">
                <i class="fas fa-eye"></i> 비주얼 에디터
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="html-tab" data-toggle="tab" href="#html" role="tab">
                <i class="fas fa-code"></i> HTML 에디터
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="css-tab" data-toggle="tab" href="#css" role="tab">
                <i class="fas fa-paint-brush"></i> CSS 에디터
            </a>
        </li>
        <?php if ($template_id > 0): ?>
        <li class="nav-item">
            <a class="nav-link" id="js-tab" data-toggle="tab" href="#js" role="tab">
                <i class="fas fa-file-code"></i> JavaScript 에디터
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link" id="preview-tab" data-toggle="tab" href="#preview" role="tab">
                <i class="fas fa-desktop"></i> 미리보기
            </a>
        </li>
    </ul>
    
    <!-- 에디터 콘텐츠 -->
    <div class="tab-content" id="editorTabContent">
        <!-- 비주얼 에디터 탭 -->
        <div class="tab-pane fade show active" id="visual" role="tabpanel">
            <div id="visualEditor">
                <!-- TinyMCE가 여기에 로드됩니다 -->
            </div>
        </div>
        
        <!-- HTML 에디터 탭 -->
        <div class="tab-pane fade" id="html" role="tabpanel">
            <textarea id="htmlEditor"><?php echo htmlspecialchars_decode($html_content); ?></textarea>
        </div>
        
        <!-- CSS 에디터 탭 -->
        <div class="tab-pane fade" id="css" role="tabpanel">
            <textarea id="cssEditor"><?php echo htmlspecialchars_decode($css_content); ?></textarea>
        </div>
        
        <?php if ($template_id > 0): ?>
        <!-- JavaScript 에디터 탭 -->
        <div class="tab-pane fade" id="js" role="tabpanel">
            <textarea id="jsEditor"><?php echo htmlspecialchars_decode($js_content); ?></textarea>
        </div>
        <?php endif; ?>
        
        <!-- 미리보기 탭 -->
        <div class="tab-pane fade" id="preview" role="tabpanel">
            <div class="bg-light p-3 rounded">
                <div id="previewContent" class="border p-3 bg-white rounded">
                    <!-- 미리보기 내용이 여기에 로드됩니다 -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- 저장 버튼 -->
    <div class="text-center mt-4">
        <button id="saveContent" class="btn btn-primary">
            <i class="fas fa-save"></i> 변경사항 저장
        </button>
        
        <?php if ($mode == 'modal'): ?>
        <button id="closeEditor" class="btn btn-secondary ml-2">
            <i class="fas fa-times"></i> 닫기
        </button>
        <?php endif; ?>
    </div>
</div>

<script>
// 블록 및 랜딩페이지 ID 설정
var templateId = <?php echo $template_id ?: 0; ?>;
var blockId = <?php echo $block_id ?: 0; ?>;
var landingId = <?php echo $landing_id ?: 0; ?>;
var isTemplateEditor = <?php echo $template_id > 0 ? 'true' : 'false'; ?>;
var editorMode = "<?php echo $mode; ?>";

// AJAX URL
var BLOCK_EDITOR_AJAX_URL = "<?php echo $ajax_url; ?>";
</script>

<?php if ($mode == 'modal'): // 모달 모드일 때 컨테이너 닫기 ?>
</div>
<?php endif; ?>