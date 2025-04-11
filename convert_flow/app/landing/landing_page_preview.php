<?php
/**
 * 랜딩페이지 미리보기
 * 실제 스타일과 블록이 적용된 화면을 보여줍니다.
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 랜딩페이지 ID 확인
$landing_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($landing_id <= 0) {
    alert('유효하지 않은 랜딩페이지입니다.');
    exit;
}

// 랜딩페이지 모델 로드
require_once CF_MODEL_PATH . '/landing_page.model.php';
$landing_model = new LandingPageModel();

// 블록 모델 로드
require_once CF_MODEL_PATH . '/landing_block.model.php';
$block_model = new LandingBlockModel();

// 폼 모델 로드 (추가)
require_once CF_MODEL_PATH . '/form.model.php';
$form_model = new FormModel();

// 랜딩페이지 정보 조회
$landing_page = $landing_model->get_landing_page($landing_id);

if (!$landing_page) {
    alert('존재하지 않는 랜딩페이지입니다.');
    exit;
}

// 관리자가 아니고 자신의 랜딩페이지가 아닌 경우 접근 제한
if ($is_admin !== "super" && $landing_page['user_id'] != $member['id']) {
    //alert('접근 권한이 없습니다.');
    //exit;
}

// 랜딩페이지의 블록 인스턴스 목록 조회
$block_instances = $block_model->get_page_blocks($landing_id);

// 랜딩페이지 캠페인 정보 조회
$campaign = array();
if ($landing_page['campaign_id'] > 0) {
    $sql = "SELECT * FROM campaigns WHERE id = '{$landing_page['campaign_id']}'";
    $campaign = sql_fetch($sql);
}

// 랜딩페이지 기본 메타 정보
$meta_title = !empty($landing_page['meta_title']) ? $landing_page['meta_title'] : $landing_page['name'];
$meta_description = !empty($landing_page['meta_description']) ? $landing_page['meta_description'] : '';

// 폼 데이터 캐싱용 배열
$form_cache = array();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $meta_title; ?> - 미리보기</title>
    
    <?php if (!empty($meta_description)) { ?>
    <meta name="description" content="<?php echo $meta_description; ?>">
    <?php } ?>
    
    <!-- 부트스트랩 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    
    <!-- 폰트어썸 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    
    
    <!-- 자바스크립트 라이브러리 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery UI -->
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    
    <!-- 기본 CSS -->
    <style>
        body {
            font-family: 'Noto Sans KR', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #fff;
            margin: 0;
            padding: 0;
        }
        .preview-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background-color: #333;
            color: #fff;
            padding: 8px 10px;
            z-index: 9999;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .preview-bar .btn {
            margin-left: 4px;
            padding: 3px 6px;
            font-size: 0.8rem;
        }
        .preview-title {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
        }
        .preview-controls {
            display: flex;
            align-items: center;
        }
        .advanced-controls {
            display: flex;
            align-items: center;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: #333;
            padding: 5px;
            border-radius: 0 0 4px 4px;
            box-shadow: 0 3px 5px rgba(0,0,0,0.2);
        }
        .preview-btn-group {
            position: relative;
        }
        .landing-content {
            margin-top: 40px;
            width: 1140px; /* 기본 콘텐츠 너비 설정 */
            max-width: 100%;
            margin-left: auto;
            margin-right: auto;
            padding: 0;
            box-sizing: border-box;
            transition: width 0.3s ease;
        }
        .block-wrapper {
            position: relative;
            margin-bottom: 0px;
        }
        .edit-overlay {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 10;
        }
        .edit-buttons {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .responsive-image {
            max-width: 100%;
            height: auto;
        }
        .image-caption {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
        
        /* 폼 요소 스타일 */
        .form-container {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .landing-form .form-group {
            margin-bottom: 20px;
        }
        .landing-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .landing-form .form-control {
            display: block;
            width: 100%;
            padding: 10px 15px;
            font-size: 16px;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: 4px;
            transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        }
        .landing-form .form-control:focus {
            color: #495057;
            background-color: #fff;
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        .landing-form .form-check {
            padding-left: 1.25rem;
            margin-bottom: 10px;
        }
        .landing-form .form-check-input {
            position: absolute;
            margin-top: .3rem;
            margin-left: -1.25rem;
        }
        .landing-form .btn-primary {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
            display: inline-block;
            text-align: center;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
        }
        .landing-form .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
        .landing-form .text-danger {
            color: #dc3545 !important;
        }
        .landing-form .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: .25rem;
            font-size: 80%;
            color: #dc3545;
        }


        /* landing_page_preview.php 파일의 <style> 태그 내부에 추가할 CSS */

        /* 폼 컨테이너 스타일 */
        .form-container {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        /* 폼 필드 스타일 */
        .landing-form .form-group {
            margin-bottom: 20px;
        }

        .landing-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .landing-form .form-control {
            display: block;
            width: 100%;
            padding: 10px 15px;
            font-size: 16px;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: 4px;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }

        .landing-form .form-control:focus {
            color: #495057;
            background-color: #fff;
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        /* 체크박스 및 라디오 버튼 스타일 */
        .landing-form .form-check {
            position: relative;
            padding-left: 1.25rem;
            margin-bottom: 10px;
        }

        .landing-form .form-check-input {
            position: absolute;
            margin-top: 0.3rem;
            margin-left: -1.25rem;
        }

        .landing-form .form-check-label {
            display: inline-block;
            margin-bottom: 0;
        }

        /* 버튼 스타일 */
        .landing-form .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .landing-form .btn-primary {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }

        .landing-form .btn-primary:hover {
            color: #fff;
            background-color: #0069d9;
            border-color: #0062cc;
        }

        .landing-form .btn-secondary {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
            margin-right: 10px;
        }

        .landing-form .btn-secondary:hover {
            color: #fff;
            background-color: #5a6268;
            border-color: #545b62;
        }

        /* 유효성 검사 스타일 */
        .landing-form .is-invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .landing-form .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 80%;
            color: #dc3545;
        }

        .landing-form .is-invalid ~ .invalid-feedback {
            display: block;
        }

        /* 폼 네비게이션 스타일 */
        .landing-form .form-navigation {
            margin-top: 20px;
            text-align: center;
        }

        /* 다단계 폼 스타일 */
        .landing-form .progress {
            height: 10px;
            margin-bottom: 20px;
            overflow: hidden;
            background-color: #e9ecef;
            border-radius: 0.25rem;
        }

        .landing-form .progress-bar {
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            background-color: #007bff;
            transition: width 0.6s ease;
        }

        .landing-form .step-indicator {
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        /* 성공 메시지 스타일 */
        .landing-form .alert {
            position: relative;
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
        }

        .landing-form .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        /* 블록 포커스 효과 */
        .block-wrapper:hover {
            outline: 1px dashed #ccc;
        }
        /* 모바일 뷰를 위한 추가 스타일 */
        .mobile-preview .landing-content {
            padding: 0px;
        }
        .mobile-view {
            transform-origin: top center;
            transition: all 0.3s;
        }
        /* 모바일에서 다르게 스타일링이 필요한 요소들 */
        .mobile-view img {
            max-width: 100% !important;
            height: auto !important;
        }
        .mobile-view .form-container {
            padding: 15px !important;
        }
        /* 미리보기 바 아이콘 커서 설정 */
        .preview-bar .btn, .preview-bar .dropdown-toggle {
            cursor: pointer;
        }
        
        /* 반응형 설정 */
        @media (max-width: 1199.98px) {
            .landing-content {
                width: 960px;
            }
        }
        @media (max-width: 991.98px) {
            .landing-content {
                width: 720px;
            }
        }
        @media (max-width: 767.98px) {
            .landing-content {
                width: 540px;
            }
            .btn-text {
                display: none;
            }
            .preview-title {
                max-width: 120px;
            }
        }
        @media (max-width: 575.98px) {
            .landing-content {
                width: 100%;
                padding: 0 10px;
            }
            .preview-bar {
                padding: 5px 8px;
            }
            .preview-title {
                max-width: 100px;
                font-size: 0.8rem;
            }
            .badge {
                display: none;
            }
        }
    </style>
    
    <!-- 랜딩페이지 글로벌 CSS -->
    <?php if (!empty($landing_page['css_content'])) { ?>
    <style>
        <?php echo $landing_page['css_content']; ?>
    </style>
    <?php } ?>
    
    <!-- 블록별 CSS -->
    <style>
        <?php 
        if (!empty($block_instances)) {
            foreach ($block_instances as $block) {
                // 템플릿에서 CSS 가져오기
                $sql = "SELECT css_content FROM landing_block_templates WHERE id = {$block['block_template_id']}";
                $template = sql_fetch($sql);
                if (!empty($template['css_content'])) {
                    echo $template['css_content'] . "\n";
                }
                if (!empty($block['custom_css'])) {
                    echo $block['custom_css'] . "\n";
                }
            }
        }
        ?>
    </style>
</head>
<body>
    <!-- 미리보기 상단 바 -->
    <div class="preview-bar">
        <div class="preview-title">
            <strong><?php echo $landing_page['name']; ?></strong> 미리보기
            <?php if (!empty($campaign)) { ?>
            <span class="badge badge-secondary ml-2"><?php echo $campaign['name']; ?></span>
            <?php } ?>
        </div>
        <div class="preview-controls">
            <div class="btn-group preview-btn-group">
                <button class="btn btn-sm btn-outline-light" id="toggleOverlay"><i class="fas fa-edit"></i><span class="btn-text"> 편집모드</span></button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-light dropdown-toggle" id="deviceSelector" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-desktop"></i><span class="btn-text"> 데스크탑</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item device-view active" href="#" data-device="desktop"><i class="fas fa-desktop"></i> 데스크탑</a>
                        <a class="dropdown-item device-view" href="#" data-device="tablet"><i class="fas fa-tablet-alt"></i> 태블릿</a>
                        <a class="dropdown-item device-view" href="#" data-device="mobile"><i class="fas fa-mobile-alt"></i> 모바일</a>
                    </div>
                </div>
                <button class="btn btn-sm btn-outline-light dropdown-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="advanced-controls" style="display: none;">
                    <a href="<?php echo CF_LANDING_URL; ?>/landing_page_blocks.php?landing_id=<?php echo $landing_id; ?>" class="btn btn-sm btn-primary"><i class="fas fa-th-large"></i><span class="btn-text"> 블록 관리</span></a>
                    <a href="<?php echo CF_LANDING_URL; ?>/landing_page_edit.php?id=<?php echo $landing_id; ?>" class="btn btn-sm btn-info"><i class="fas fa-cog"></i><span class="btn-text"> 페이지 설정</span></a>
                    <button class="btn btn-sm btn-secondary" onclick="window.close()"><i class="fas fa-times"></i><span class="btn-text"> 닫기</span></button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 랜딩페이지 콘텐츠 -->
    <div class="landing-content">
        <?php 
        // 블록 렌더링
        if (!empty($block_instances)) {
            foreach ($block_instances as $block) {
                // 설정 값 파싱 (JSON 문자열인 경우)
                if (is_string($block['settings'])) {
                    $settings = json_decode($block['settings'], true);
                } else {
                    $settings = $block['settings'];
                }
                
                if (!$settings) {
                    $settings = array();
                }
                
                // 블록 HTML 생성
                $block_html = '';
                
                // 폼 블록 특별 처리
                if ($block['type_name'] == 'form_block' && isset($settings['form_id'])) {
                    // 폼 ID로 실제 폼 컨텐츠를 렌더링
                    $form_id = intval($settings['form_id']);
                    $block_html = render_form_block($form_id, $form_model);
                } else {
                    // 일반 블록 처리
                    // 템플릿에서 HTML 가져오기
                    $sql = "SELECT html_content FROM landing_block_templates WHERE id = {$block['block_template_id']}";
                    $template = sql_fetch($sql);
                    $template_html = !empty($template['html_content']) ? $template['html_content'] : '';
                    
                    $block_html = !empty($block['custom_html']) ? $block['custom_html'] : $template_html;
                    $block_html = str_replace("\\\"", '"', $block_html);
                    
                    // 설정 값으로 템플릿 변수 대체
                    if (!empty($settings) && is_array($settings)) {
                        foreach ($settings as $key => $value) {
                            // HTML 속성에서의 템플릿 변수 처리 (src="{{image_url}}" 같은 경우)
                            $pattern1 = '/([a-zA-Z0-9_-]+=["\'])\{\{' . preg_quote($key, '/') . '\}\}(["\'])/'; 
                            $block_html = preg_replace($pattern1, '$1' . $value . '$2', $block_html);
                            
                            // 일반 템플릿 변수 처리
                            $pattern2 = '/\{\{' . preg_quote($key, '/') . '\}\}/'; 
                            $block_html = preg_replace($pattern2, $value, $block_html);
                        }
                    }
                    
                    // 남은 템플릿 변수 제거 (더 정확한 정규식 사용)
                    $block_html = preg_replace('/\{\{[^\}]+\}\}/', '', $block_html);
                }

                // 여백을 포함한 블록 렌더링
                $block_html = render_block_with_margins($settings, $block_html);
                
                echo '<div class="block-wrapper" data-block-id="'.$block['id'].'" data-block-type="'.$block['type_name'].'">';
                echo $block_html;
                
                // 편집 오버레이
                echo '<div class="edit-overlay">';
                echo '<div class="edit-buttons">';
                echo '<a href="'.CF_LANDING_URL.'/block_editor.php?block_id='.$block['id'].'&landing_id='.$landing_id.'" class="btn btn-primary btn-sm" target="_blank"><i class="fas fa-edit"></i> 편집</a> ';
                echo '<button class="btn btn-danger btn-sm delete-block" data-id="'.$block['id'].'"><i class="fas fa-trash"></i> 삭제</button>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<div class="text-center py-5">';
            echo '<div class="alert alert-info">';
            echo '<h4><i class="fas fa-info-circle"></i> 블록이 없습니다</h4>';
            echo '<p>이 랜딩페이지에 블록이 추가되지 않았습니다. 블록 관리 페이지에서 블록을 추가해 주세요.</p>';
            echo '<a href="'.CF_LANDING_URL.'/landing_page_blocks.php?landing_id='.$landing_id.'" class="btn btn-primary">블록 관리</a>';
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>

        <!-- 랜딩페이지 글로벌 JS -->
<?php if (!empty($landing_page['js_content'])) { 
    // 이스케이프된 따옴표와 특수 문자 처리
    $js_content = str_replace('\\\'', '\'', $landing_page['js_content']);
    $js_content = str_replace('\\"', '"', $js_content);
    $js_content = str_replace('\\\\', '\\', $js_content);
?>
<script>
    <?php echo $js_content; ?>
</script>
<?php } ?>
    
    <!-- 블록별 JS -->
    <?php 
    if (!empty($block_instances)) {
        foreach ($block_instances as $block) {
            // 템플릿에서 JS 가져오기
            $sql = "SELECT js_content FROM landing_block_templates WHERE id = {$block['block_template_id']}";
            $template = sql_fetch($sql);
            $template_js = !empty($template['js_content']) ? $template['js_content'] : '';
            
            if (!empty($template_js) || !empty($block['custom_js'])) {
                
                $template_js = stripslashes($template_js);
                $template_js = str_replace('\\\'', '\'', $template_js);
                $template_js = str_replace('\\"', '"', $template_js);
                
                echo '<script>';
                if (!empty($template_js)) {
                    echo $template_js . "\n";
                }
                if (!empty($block['custom_js'])) {
                    echo $block['custom_js'] . "\n";
                }
                echo '</script>';
            }
        }
    }
    ?>
    
    <!-- 미리보기 기능 JS -->
    <script>
    $(document).ready(function() {
        // 모바일 감지
        var isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        
        // 모바일인 경우 자동으로 모바일 뷰 적용
        if(isMobile) {
            applyDeviceView('mobile');
        } else {
            // 기본 레이아웃 너비 설정 (데스크탑)
            var defaultWidth = "1140px";
            $(".landing-content").css({
                "width": defaultWidth,
                "margin-top": "40px",
                "margin-left": "auto",
                "margin-right": "auto"
            });
        }
        
        // 오버레이 토글
        var overlayVisible = false;
        
        $("#toggleOverlay").on("click", function() {
            overlayVisible = !overlayVisible;
            if (overlayVisible) {
                $(".edit-overlay").fadeIn(200);
                $(this).addClass("active");
            } else {
                $(".edit-overlay").fadeOut(200);
                $(this).removeClass("active");
            }
        });
        
        // 블록 호버 효과
        $(".block-wrapper").hover(
            function() {
                if (overlayVisible) {
                    $(this).find(".edit-overlay").css("background-color", "rgba(0, 0, 0, 0.7)");
                }
            },
            function() {
                if (overlayVisible) {
                    $(this).find(".edit-overlay").css("background-color", "rgba(0, 0, 0, 0.5)");
                }
            }
        );
        
        // 블록 삭제 확인
        $(".delete-block").on("click", function() {
            if (confirm("정말로 이 블록을 삭제하시겠습니까?")) {
                var blockId = $(this).data("id");
                
                $.ajax({
                    url: "<?php echo CF_LANDING_URL; ?>/ajax.action.php",
                    type: "POST",
                    data: {
                        action: "delete_block",
                        block_id: blockId
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert("블록 삭제 중 오류가 발생했습니다.");
                    }
                });
            }
        });
        
        // 반응형 디자인 보기 함수화
        function applyDeviceView(device) {
            var width = "1140px"; // 데스크탑 기본 너비
            var htmlContent;
            
            if (device === "mobile") {
                width = "375px";
                // 드롭다운 버튼 아이콘 변경
                htmlContent = '<i class="fas fa-mobile-alt"></i><span class="btn-text"> 모바일</span>';
                $("#deviceSelector").html(htmlContent);
            } else if (device === "tablet") {
                width = "768px";
                // 드롭다운 버튼 아이콘 변경
                htmlContent = '<i class="fas fa-tablet-alt"></i><span class="btn-text"> 태블릿</span>';
                $("#deviceSelector").html(htmlContent);
            } else {
                // 드롭다운 버튼 아이콘 변경
                htmlContent = '<i class="fas fa-desktop"></i><span class="btn-text"> 데스크탑</span>';
                $("#deviceSelector").html(htmlContent);
            }
            
            $(".landing-content").css({
                "width": "100%",
                "padding:":"0px !important",
                "margin":"0px !important",
                "border": "none",
                "box-shadow": "none",
                "min-height": "calc(100vh - 40px)",
                "background-color": width !== "1140px" ? "#fff" : "transparent"
            });
            
            // 실제 내부 콘텐츠 스타일 조정
            if (device === "mobile") {
                $(".block-wrapper").addClass("mobile-view");
                $("body").addClass("mobile-preview");
            } else {
                $(".block-wrapper").removeClass("mobile-view");
                $("body").removeClass("mobile-preview");
            }
            
            $(".device-view").removeClass("active");
            $(".device-view[data-device='" + device + "']").addClass("active");
        }

        // 반응형 디자인 보기 이벤트 연결
        $(".device-view").on("click", function(e) {
            e.preventDefault();
            var device = $(this).data("device");
            applyDeviceView(device);
        });
        
        // 추가 메뉴 토글
        $("#menuToggle").on("click", function() {
            $(".advanced-controls").slideToggle(200);
        });
        
        // 폼 제출 방지 (미리보기에서는 실제 제출 방지)
        $(document).on("submit", "form", function(e) {
            e.preventDefault();
            alert("미리보기 모드에서는 폼이 제출되지 않습니다.");
            return false;
        });
        
        // 폼 유효성 검사 기능 (미리보기에서도 동작)
        $(document).on("blur", ".landing-form .form-control", function() {
            var $this = $(this);
            var required = $this.prop("required");
            
            if (required && $this.val().trim() === "") {
                $this.addClass("is-invalid");
            } else {
                $this.removeClass("is-invalid");
            }
        });
    });
    </script>
    
    <!-- 디버그 도구 -->
    <?php if ($is_admin === 'super') { ?>
    <div class="debug-panel" style="position: fixed; bottom: 10px; right: 10px; background: #fff; border: 1px solid #ddd; padding: 10px; max-width: 400px; max-height: 300px; overflow: auto; z-index: 9999; font-size: 12px; display: none;">
        <h5>DEBUG</h5>
        <button class="btn btn-sm btn-secondary" onclick="$('.debug-panel').hide()">닫기</button>
        <hr>
        <h6>블록 정보</h6>
        <pre><?php echo print_r($block_instances, true); ?></pre>
    </div>
    <button class="btn btn-sm btn-info" style="position: fixed; bottom: 10px; right: 10px; z-index: 9998;" onclick="$('.debug-panel').toggle()">디버그</button>
    <?php } ?>
</body>
</html>