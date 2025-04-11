<?php
/**
 * 블록 에디터 iframe 컨텐츠
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 파라미터
$template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;
$block_id = isset($_GET['block_id']) ? intval($_GET['block_id']) : 0;
$landing_id = isset($_GET['landing_id']) ? intval($_GET['landing_id']) : 0;
$ajax_url = isset($_GET['ajax_url']) ? $_GET['ajax_url'] : CF_INCLUDE_URL . '/block_editor/ajax.block_editor.php';

// 에디터 공통 모듈 파라미터 설정
$params = array(
    'template_id' => $template_id,
    'block_id' => $block_id,
    'landing_id' => $landing_id,
    'mode' => 'modal',
    'ajax_url' => $ajax_url
);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>블록 에디터</title>
    
    <!-- 직접 CDN 사용 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- jQuery 및 부트스트랩 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Toastr 알림 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <!-- CodeMirror 코어 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/codemirror.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/codemirror.min.js"></script>

    <!-- CodeMirror 모드 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/mode/javascript/javascript.min.js"></script>

    <!-- CodeMirror 테마 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/theme/monokai.min.css">

    <!-- TinyMCE -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.10.3/tinymce.min.js"></script>
    
    <!-- 추가 스타일 -->
    <style>
    body {
        padding: 0;
        margin: 0;
        overflow-x: hidden;
        font-family: Arial, sans-serif;
    }
    .block-editor-wrapper {
        padding: 15px;
    }
    .tab-content {
        padding-top: 20px;
    }
    .btn-primary {
        background-color: #4e73df;
        border-color: #4e73df;
    }
    .btn-secondary {
        background-color: #858796;
        border-color: #858796;
    }
    .nav-tabs .nav-link.active {
        color: #4e73df;
        border-color: #dddfeb #dddfeb #fff;
    }
    .CodeMirror {
        height: 500px;
        border: 1px solid #ddd;
    }

    .CodeMirror pre.CodeMirror-line, .CodeMirror pre.CodeMirror-line-like {
        padding: 0 34px;
    }
    </style>
</head>
<body>
    <?php 
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
        exit;
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
    
    <div class="block-editor-wrapper">
        <h5 class="block-editor-title mb-3">
            <?php echo $template ? $template['name'] : '블록 에디터'; ?>
        </h5>

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
            
            <?php if ($is_admin == "super") { ?>
            <!-- CSS 에디터 탭 -->
            <div class="tab-pane fade" id="css" role="tabpanel">
                <textarea id="cssEditor"><?php echo htmlspecialchars_decode($css_content); ?></textarea>
            </div>
            
            <?php if ($template_id > 0) { ?>
            <!-- JavaScript 에디터 탭 -->
            <div class="tab-pane fade" id="js" role="tabpanel">
                <textarea id="jsEditor"><?php echo htmlspecialchars_decode($js_content); ?></textarea>
            </div>
            <?php } ?>
            <?php } ?>
            
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
        </div>
    </div>

    <!-- 에디터 스크립트 직접 포함 -->
    <script>
    // 블록 및 랜딩페이지 ID 설정
    var templateId = <?php echo $template_id ?: 0; ?>;
    var blockId = <?php echo $block_id ?: 0; ?>;
    var landingId = <?php echo $landing_id ?: 0; ?>;
    var isTemplateEditor = <?php echo $template_id > 0 ? 'true' : 'false'; ?>;
    var editorMode = "modal";

    // AJAX URL
    var BLOCK_EDITOR_AJAX_URL = "<?php echo $ajax_url; ?>";
    
    // 코드 에디터 인스턴스
    var htmlEditor, cssEditor, jsEditor;
    var visualEditor;
    var isVisualEditorActive = true;
    var unsavedChanges = false;
    var editorsInitialized = false;

    // HTML 내용 정리 함수
    function cleanHtmlContent(html) {
        if (!html) return '';
        
        // 모든 이스케이프된 따옴표 처리
        return html
            .replace(/\\&quot;/g, '"')
            .replace(/&quot;/g, '"')
            .replace(/\\&#039;/g, "'")
            .replace(/&#039;/g, "'")
            .replace(/\\'/g, "'")
            .replace(/\\"/g, '"')
            .replace(/\\\\/g, '\\');
    }

    // CSS 내용 정리 함수
    function cleanCssContent(css) {
        if (!css) return '';
        
        // 이스케이프된 따옴표 처리
        return css
            .replace(/\\'/g, "'")
            .replace(/\\"/g, '"')
            .replace(/\\\\/g, '\\');
    }

    // 에디터 초기화
    function initBlockEditor() {
        // 중복 초기화 방지
        if (editorsInitialized) {
            console.log("에디터가 이미 초기화되었습니다. 중복 실행을 방지합니다.");
            return;
        }
        
        // 초기화 상태 설정
        editorsInitialized = true;
        
        console.log("에디터 초기화 시작");
        
        // HTML 에디터 초기화
        try {
            htmlEditor = CodeMirror.fromTextArea(
                document.getElementById("htmlEditor"),
                {
                    mode: "htmlmixed",
                    theme: "monokai",
                    lineNumbers: true,
                    indentUnit: 4,
                    lineWrapping: true,
                }
            );

            // 크기 조정
            htmlEditor.setSize("100%", 500);

            // 초기 값이 비어있는지 확인
            if (!htmlEditor.getValue().trim() && $("#htmlEditor").val().trim()) {
                htmlEditor.setValue($("#htmlEditor").val());
                console.log("HTML 값 수동 설정됨");
            }
        } catch (e) {
            console.error("HTML 에디터 초기화 실패:", e);
        }

        // CSS 에디터 초기화
        try {
            cssEditor = CodeMirror.fromTextArea(
                document.getElementById("cssEditor"),
                {
                    mode: "css",
                    theme: "monokai",
                    lineNumbers: true,
                    indentUnit: 4,
                    lineWrapping: true,
                    autoCloseBrackets: true,
                }
            );

            // 크기 조정
            cssEditor.setSize("100%", 500);

            // 초기 CSS 값 확인
            console.log("CSS 에디터 초기 값:", cssEditor.getValue());

            // 값이 비어있으면 원본 textarea에서 다시 가져옴
            if (!cssEditor.getValue().trim() && $("#cssEditor").val().trim()) {
                // 이스케이프된 따옴표 정리
                var cleanedCss = cleanCssContent($("#cssEditor").val());
                cssEditor.setValue(cleanedCss);
                console.log("CSS 값 정리되어 설정됨");
            }
        } catch (e) {
            console.error("CSS 에디터 초기화 실패:", e);
        }

        // JavaScript 에디터 초기화 (템플릿 에디터에만 있음)
        if (document.getElementById("jsEditor")) {
            try {
                jsEditor = CodeMirror.fromTextArea(
                    document.getElementById("jsEditor"),
                    {
                        mode: "javascript",
                        theme: "monokai",
                        lineNumbers: true,
                        indentUnit: 4,
                        lineWrapping: true,
                        autoCloseBrackets: true,
                    }
                );

                jsEditor.setSize("100%", 500);
            } catch (e) {
                console.error("JS 에디터 초기화 실패:", e);
            }
        }

        // TinyMCE 초기화
        initTinyMCE();

        // 탭 전환 이벤트
        $('#editorTabs a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
            var targetTab = $(e.target).attr("href");

            if (targetTab === "#visual") {
                // HTML에서 비주얼 에디터로 전환
                if (visualEditor && htmlEditor) {
                    console.log(
                        "비주얼 탭 활성화 - HTML 내용:",
                        htmlEditor.getValue()
                    );

                    // HTML 내용 가져오기 및 정리
                    var htmlContent = cleanHtmlContent(htmlEditor.getValue());
                    console.log("정리된 HTML 내용:", htmlContent);

                    // 정리된 HTML 내용 설정
                    visualEditor.setContent(htmlContent);

                    // CSS 적용 (지연)
                    setTimeout(function () {
                        injectStylesIntoTinyMCE();
                    }, 100);

                    isVisualEditorActive = true;
                }
            } else if (targetTab === "#html") {
                // 비주얼에서 HTML로 전환
                if (isVisualEditorActive && visualEditor && htmlEditor) {
                    var content = visualEditor.getContent();
                    // 스타일 태그 제거
                    content = content.replace(
                        /<style[^>]*>[\s\S]*?<\/style>/gi,
                        ""
                    );
                    
                    // 따옴표 처리
                    content = cleanHtmlContent(content);

                    htmlEditor.setValue(content);
                    isVisualEditorActive = false;
                }
            } else if (targetTab === "#preview") {
                // 미리보기 업데이트
                updatePreview();
            }
        });

        // 저장 버튼 클릭 이벤트
        $("#saveContent").on("click", function () {
            saveContent();
        });

        // 닫기 버튼 클릭 이벤트
        $("#closeEditor").on("click", function () {
            if (unsavedChanges) {
                if (!confirm("변경 사항이 저장되지 않았습니다. 정말 닫으시겠습니까?")) {
                    return;
                }
            }
            // 모달을 닫는 로직 (부모 페이지에서 처리)
            if (window.parent && window.parent.closeBlockEditorModal) {
                window.parent.closeBlockEditorModal();
            }
        });

        // HTML 에디터 변경사항 감지
        htmlEditor.on("change", function () {
            unsavedChanges = true;
        });

        // CSS 에디터 변경사항 감지
        cssEditor.on("change", function () {
            unsavedChanges = true;

            // CSS 변경 시 비주얼 에디터 업데이트 (활성화된 경우에만)
            if (isVisualEditorActive && visualEditor) {
                clearTimeout(window.cssUpdateTimer);
                window.cssUpdateTimer = setTimeout(function () {
                    injectStylesIntoTinyMCE();
                }, 500);
            }
        });

        if (jsEditor) {
            jsEditor.on("change", function () {
                unsavedChanges = true;
            });
        }

        // 페이지 이탈 시 경고
        $(window).on("beforeunload", function () {
            if (unsavedChanges) {
                return "변경사항이 저장되지 않았습니다. 정말 페이지를 떠나시겠습니까?";
            }
        });

        console.log("에디터 초기화 완료");
    }

    // TinyMCE 초기화
    function initTinyMCE() {
        tinymce.init({
            selector: "#visualEditor",
            height: 800,
            menubar: true,
            plugins: [
                "advlist autolink lists link image charmap print preview anchor",
                "searchreplace visualblocks code fullscreen",
                "insertdatetime media table paste code help wordcount",
            ],
            toolbar:
                "undo redo | formatselect | " +
                "bold italic backcolor | alignleft aligncenter " +
                "alignright alignjustify | bullist numlist outdent indent | " +
                "removeformat | help",
            content_css: [
                "https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;700;900&display=swap",
                "//fonts.googleapis.com/css?family=Lato:300,300i,400,400i",
                "//www.tiny.cloud/css/codepen.min.css",
            ],
            entity_encoding: 'raw',
            setup: function (editor) {
                editor.on("change", function () {
                    unsavedChanges = true;
                });

                visualEditor = editor;
            },
            init_instance_callback: function (editor) {
                console.log("TinyMCE 초기화 완료");

                // HTML 설정
                var initialContent = $("#htmlEditor").val();
                // HTML 내용 정리
                initialContent = cleanHtmlContent(initialContent);
                editor.setContent(initialContent);

                // CSS 적용 (지연 설정)
                setTimeout(function () {
                    injectStylesIntoTinyMCE();
                    // 초기 미리보기 업데이트
                    updatePreview();
                }, 200);
            },
            // 허용되는 HTML 요소 확장
            extended_valid_elements: "style,div[*],span[*],img[*]",
            // 부모-자식 요소 규칙 확장
            valid_children: "+body[style],+div[*]",
        });
    }

    // CSS를 비주얼 에디터에 직접 주입하는 함수
    function injectStylesIntoTinyMCE() {
        if (!visualEditor || !visualEditor.getDoc) {
            console.log(
                "비주얼 에디터가 준비되지 않았습니다. 잠시 후 다시 시도합니다."
            );
            return false;
        }

        try {
            // 에디터 iframe 문서 접근
            var editorDoc = visualEditor.getDoc();
            var editorHead = editorDoc.head;

            // CSS 내용 가져오기
            var cssContent = cssEditor.getValue();
            if (!cssContent.trim()) {
                console.log("CSS 내용이 비어있습니다.");
                return false;
            }

            // CSS 내용 정리 (이스케이프된 따옴표 처리)
            cssContent = cleanCssContent(cssContent);

            console.log("적용할 CSS:", cssContent.substring(0, 100) + "...");

            // 기존 스타일 요소 제거 (완전 초기화)
            var oldStyles = editorDoc.querySelectorAll('style[data-custom="true"]');
            oldStyles.forEach(function (style) {
                style.parentNode.removeChild(style);
            });

            // 새 스타일 요소 생성
            var styleElement = editorDoc.createElement("style");
            styleElement.setAttribute("type", "text/css");
            styleElement.setAttribute("data-custom", "true");
            styleElement.textContent = cssContent;

            // 헤드에 추가
            editorHead.appendChild(styleElement);

            // 폰트 링크 추가 (없는 경우)
            var fontLink = editorDoc.querySelector(
                'link[href*="fonts.googleapis.com/css2?family=Noto+Sans+KR"]'
            );
            if (!fontLink) {
                fontLink = editorDoc.createElement("link");
                fontLink.rel = "stylesheet";
                fontLink.href =
                    "https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;700;900&display=swap";
                editorHead.appendChild(fontLink);
            }

            console.log("스타일이 TinyMCE에 주입됨");
            return true;
        } catch (e) {
            console.error("TinyMCE에 스타일 주입 중 오류:", e);
            return false;
        }
    }

    // 미리보기 업데이트 함수
    function updatePreview() {
        try {
            // HTML 콘텐츠 가져오기
            var htmlContent;
            if (isVisualEditorActive && visualEditor && visualEditor.getContent) {
                // 비주얼 에디터에서 HTML 얻기 (스타일 태그 제거)
                htmlContent = visualEditor
                    .getContent()
                    .replace(/<style[^>]*>[\s\S]*?<\/style>/gi, "");
            } else {
                htmlContent = htmlEditor.getValue();
            }

            // HTML 정리 (이스케이프된 큰따옴표 변환)
            htmlContent = cleanHtmlContent(htmlContent);

            // CSS 콘텐츠 가져오기 및 정리
            var cssContent = cleanCssContent(cssEditor.getValue());

            // 미리보기 HTML 생성
            var previewHtml = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;700;900&display=swap" rel="stylesheet">
                    <style type="text/css">
                    ${cssContent}
                    </style>
                </head>
                <body>
                    ${htmlContent}
                </body>
                </html>
            `;

            // 미리보기 업데이트
            var previewFrame = document.getElementById("previewFrame");

            // iframe이 없으면 생성
            if (!previewFrame) {
                previewFrame = document.createElement("iframe");
                previewFrame.id = "previewFrame";
                previewFrame.style.width = "100%";
                previewFrame.style.height = "600px";
                previewFrame.style.border = "none";

                var previewContent = document.getElementById("previewContent");
                if (previewContent) {
                    previewContent.innerHTML = "";
                    previewContent.appendChild(previewFrame);
                }
            }

            // iframe 문서에 콘텐츠 쓰기
            var frameDoc =
                previewFrame.contentDocument || previewFrame.contentWindow.document;
            frameDoc.open();
            frameDoc.write(previewHtml);
            frameDoc.close();

            console.log("미리보기 업데이트 완료");
            return true;
        } catch (e) {
            console.error("미리보기 업데이트 중 오류:", e);

            // 기본 방식으로 폴백
            try {
                var previewContent = document.getElementById("previewContent");
                if (previewContent) {
                    var cssContent = cssEditor.getValue();
                    var htmlContent = htmlEditor.getValue();

                    previewContent.innerHTML = `
                        <style>${cssContent}</style>
                        ${htmlContent}
                    `;
                }
                return true;
            } catch (fallbackError) {
                console.error("기본 미리보기 방식도 실패:", fallbackError);
                return false;
            }
        }
    }

    // 블록 저장 함수
    function saveContent() {
        try {
            // 활성화된 에디터에 따라 업데이트
            if (isVisualEditorActive && visualEditor) {
                // 비주얼 에디터에서 HTML 내용만 가져오기
                var fullContent = visualEditor.getContent();
                var cleanedHtml = fullContent.replace(
                    /<style[^>]*>[\s\S]*?<\/style>/gi,
                    ""
                );

                // HTML 내용에서 이스케이프된 따옴표 수정
                cleanedHtml = cleanHtmlContent(cleanedHtml);

                htmlEditor.setValue(cleanedHtml);
            }

            var htmlContent = htmlEditor.getValue();
            var cssContent = cssEditor.getValue();

            // 관리자인 경우 JavaScript 값도 가져옴
            var jsContent = "";
            if (typeof jsEditor !== "undefined" && jsEditor) {
                jsContent = jsEditor.getValue();
            }

            // 저장 데이터 준비
            var saveData = {
                action: isTemplateEditor ? "save_template" : "save_block",
            };

            // 템플릿 에디터인 경우
            if (isTemplateEditor) {
                saveData.template_id = templateId;
                saveData.html_content = htmlContent;
                saveData.css_content = cssContent;
                saveData.js_content = jsContent;
            }
            // 블록 인스턴스 에디터인 경우
            else {
                saveData.block_id = blockId;
                saveData.landing_id = landingId;
                saveData.custom_html = htmlContent;
                saveData.custom_css = cssContent;
            }

            //console.log("저장할 데이터:", saveData);

            // AJAX 요청 URL - 전역 설정된 URL 사용
            var ajaxUrl = BLOCK_EDITOR_AJAX_URL;

            // AJAX 요청
            $.ajax({
                url: ajaxUrl,
                type: "POST",
                data: saveData,
                dataType: "json",
                success: function (response) {
                    if (response.success) {
                        // 성공 메시지 표시
                        toastr.success(
                            isTemplateEditor
                                ? "템플릿이 저장되었습니다."
                                : "블록이 저장되었습니다."
                        );
                        unsavedChanges = false;

                        // 미리보기 업데이트
                        updatePreview();
                        
                        // 부모 창으로 저장 완료 알림
                        if (window.parent && window.parent.blockEditorSaveCallback) {
                            window.parent.blockEditorSaveCallback({
                                templateId: templateId,
                                blockId: blockId,
                                landingId: landingId
                            });
                        }
                    } else {
                        // 오류 메시지 표시
                        toastr.error(
                            response.message || "저장 중 오류가 발생했습니다."
                        );
                    }
                },
                error: function (xhr, status, error) {
                    // 오류 메시지 표시
                    toastr.error("서버 통신 중 오류가 발생했습니다: " + error);
                    console.error("저장 중 AJAX 오류:", xhr, status, error);
                }
            });
        } catch (e) {
            console.error("저장 중 오류 발생:", e);
            toastr.error("저장 중 오류가 발생했습니다: " + e.message);
        }
    }

    // 글로벌 타이머 변수
    window.cssUpdateTimer = null;
    
    // 페이지 로드 시 에디터 초기화
    $(document).ready(function() {
        console.log("페이지 로드됨, 에디터 초기화 시작");
        initBlockEditor();


        setTimeout(checkAndSetCssContent, 500);
    });
    </script>
</body>
</html>