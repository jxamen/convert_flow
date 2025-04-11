/**
 * 블록 비주얼 에디터 공통 스크립트
 */

// 코드 에디터 인스턴스
var htmlEditor, cssEditor, jsEditor;
var visualEditor;
var isVisualEditorActive = true;
var unsavedChanges = false;

// 페이지 로드 시 CSS 데이터 확인 및 설정
$(document).ready(function () {
    // CSS textarea 확인
    var cssTextarea = document.getElementById("cssEditor");
    console.log("CSS textarea 요소:", cssTextarea);

    if (cssTextarea) {
        console.log("CSS textarea 값:", cssTextarea.value);

        // CSS 값이 없는 경우 서버에서 데이터 직접 요청
        if (!cssTextarea.value.trim()) {
            console.log("CSS 값이 비어있음. 서버에서 데이터 요청 시도");

            // AJAX로 CSS 데이터 요청
            $.ajax({
                url: BLOCK_EDITOR_AJAX_URL,
                type: "POST",
                data: {
                    action: "get_block_data",
                    block_id: blockId,
                    landing_id: landingId,
                    template_id: templateId,
                },
                dataType: "json",
                success: function (response) {
                    if (response.success) {
                        console.log("서버에서 CSS 데이터 받음:", response);

                        // CSS 데이터가 있으면 설정
                        if (response.css_content) {
                            cssTextarea.value = response.css_content;

                            // CSS 에디터가 이미 초기화되었으면 값 설정
                            if (typeof cssEditor !== "undefined" && cssEditor) {
                                cssEditor.setValue(response.css_content);
                                console.log("CSS 에디터에 값 설정됨");
                            }
                        }
                    } else {
                        console.log("CSS 데이터 요청 실패:", response.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX 요청 오류:", error);
                },
            });
        }
    }

    // 정상적인 에디터 초기화 시도
    initBlockEditor();
});

// 에디터 초기화
function initBlockEditor() {
    // 먼저 원본 데이터 확인 (디버깅용)
    console.log("HTML 데이터:", $("#htmlEditor").val());
    console.log("CSS 데이터:", $("#cssEditor").val());

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
            cssEditor.setValue($("#cssEditor").val());
            console.log("CSS 값 수동 설정됨");
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

    // HTML 내용 정리 함수
    function cleanHtmlContent(html) {
        // 이스케이프된 큰따옴표와 작은따옴표를 일반 따옴표로 변환
        return html
            .replace(/\\&quot;/g, '"')
            .replace(/&quot;/g, '"')
            .replace(/\\'/g, "'")
            .replace(/&#39;/g, "'")
            .replace(/\\\\/g, "\\"); // 이스케이프된 백슬래시 처리
    }

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
                }, 200);

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

                htmlEditor.setValue(content);
                isVisualEditorActive = false;
            }
        } else if (targetTab === "#preview") {
            // 미리보기 업데이트
            updatePreview();
        }
    });

    // 저장 버튼 클릭 이벤트
    $("#saveContent").click(function () {
        saveContent();
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

    // 디버그 버튼 추가
    addDebugButton();
}

// 디버그 버튼 추가
function addDebugButton() {
    if (!document.getElementById("debugButton")) {
        var debugBtn = document.createElement("button");
        debugBtn.id = "debugButton";
        debugBtn.className = "btn btn-sm btn-warning mb-2";
        debugBtn.innerHTML = "CSS 문제 해결";
        debugBtn.style.marginLeft = "10px";
        debugBtn.onclick = function () {
            fixCssIssues();
        };

        var tabsElement = document.getElementById("editorTabs");
        if (tabsElement) {
            tabsElement.parentNode.insertBefore(
                debugBtn,
                tabsElement.nextSibling
            );
        }
    }
}

// CSS 문제 해결 함수
function fixCssIssues() {
    try {
        // 1. CSS 에디터에 CSS가 안 나오는 문제 해결
        var cssTextarea = document.getElementById("cssEditor");
        var cssValue = cssTextarea.value;

        alert(
            "CSS 문제 해결을 시도합니다.\n\n현재 CSS 텍스트 영역 값 길이: " +
                (cssValue ? cssValue.length : 0) +
                "자"
        );

        if (cssValue && !cssEditor.getValue()) {
            cssEditor.setValue(cssValue);
            alert("CSS 에디터에 값이 설정되었습니다.");
        }

        // 2. 비주얼 에디터에 CSS 적용
        if (injectStylesIntoTinyMCE()) {
            alert("비주얼 에디터에 CSS 스타일이 적용되었습니다.");
        } else {
            alert("비주얼 에디터에 CSS 적용 실패. 콘솔을 확인하세요.");
        }

        // 3. 미리보기 업데이트
        if (updatePreview()) {
            alert("미리보기가 업데이트되었습니다.");
        } else {
            alert("미리보기 업데이트 실패. 콘솔을 확인하세요.");
        }
    } catch (e) {
        alert("오류 발생: " + e.message);
        console.error("CSS 문제 해결 중 오류:", e);
    }
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
            editor.setContent(initialContent);

            // CSS 적용 (지연 설정)
            setTimeout(function () {
                injectStylesIntoTinyMCE();
                // 초기 미리보기 업데이트
                updatePreview();
            }, 1000);
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

// 강력한 미리보기 업데이트 함수
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

        // HTML 정리 (이스케이프된 따옴표 변환)
        htmlContent = cleanHtmlContent(htmlContent);

        // CSS 콘텐츠 가져오기
        var cssContent = cssEditor.getValue();

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
            previewFrame.style.height = "800px";
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

// HTML, JavaScript 내용 정리 함수 - 작은따옴표, 이스케이프 문자 등을 올바르게 처리
function cleanHtmlContent(content) {
    if (!content) return "";

    return content
        .replace(/\\&quot;/g, '"')
        .replace(/&quot;/g, '"')
        .replace(/\\&#39;/g, "'")
        .replace(/&#39;/g, "'")
        .replace(/\\'/g, "'")
        .replace(/\\\\/g, "\\")
        .replace(/\\\$/g, "$");
}

// 블록 저장 통합 함수
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

            // HTML 내용 정리
            cleanedHtml = cleanHtmlContent(cleanedHtml);

            htmlEditor.setValue(cleanedHtml);
        }

        var htmlContent = htmlEditor.getValue();
        var cssContent = cssEditor.getValue();

        // 관리자인 경우 JavaScript 값도 가져옴
        var jsContent = "";
        if (typeof jsEditor !== "undefined" && jsEditor) {
            jsContent = jsEditor.getValue();

            // JavaScript 내용 정리 (작은따옴표와 이스케이프 문자 처리)
            jsContent = cleanHtmlContent(jsContent);
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

        console.log("저장할 데이터:", saveData);

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
            },
        });
    } catch (e) {
        console.error("저장 중 오류 발생:", e);
        toastr.error("저장 중 오류가 발생했습니다: " + e.message);
    }
}

// 글로벌 타이머 변수
window.cssUpdateTimer = null;
