/**
 * 블록 에디터 모달 관련 함수
 */

// 전역 모달 객체
var blockEditorModal = null;

// 블록 에디터 모달 열기
function openBlockEditorModal(options) {
    // 옵션 기본값 설정
    var settings = $.extend(
        {
            template_id: 0,
            block_id: 0,
            landing_id: 0,
            ajax_url: BLOCK_EDITOR_URL + "/ajax.block_editor.php",
            title: "블록 에디터",
            width: "90%",
            height: "90%",
            onSave: function (data) {
                console.log("저장됨:", data);
                location.reload();
            },
            onClose: function () {
                console.log("모달 닫힘");
            },
        },
        options
    );

    // 모달 HTML 생성
    if (!$("#blockEditorModal").length) {
        $("body").append(`
        <div class="modal fade" id="blockEditorModal" tabindex="-1" role="dialog" aria-labelledby="blockEditorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document" style="max-width: ${settings.width}; height: ${settings.height};">
                <div class="modal-content h-100">
                    <div class="modal-header">
                        <h5 class="modal-title" id="blockEditorModalLabel">${settings.title}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0" style="height: calc(100% - 120px);">
                        <iframe id="blockEditorFrame" style="width: 100%; height: 100%; border: none;"></iframe>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                    </div>
                </div>
            </div>
        </div>
        `);
    }

    // 모달 객체 저장
    blockEditorModal = $("#blockEditorModal");

    // iframe 소스 URL 생성
    var editorUrl = BLOCK_EDITOR_URL + "/editor_frame.php";
    editorUrl += "?template_id=" + settings.template_id;
    editorUrl += "&block_id=" + settings.block_id;
    editorUrl += "&landing_id=" + settings.landing_id;
    editorUrl += "&ajax_url=" + encodeURIComponent(settings.ajax_url);
    editorUrl += "&t=" + new Date().getTime(); // 캐시 방지

    console.log("에디터 프레임 URL:", editorUrl);

    // iframe 소스 설정 및 모달 표시
    $("#blockEditorFrame").attr("src", editorUrl);
    blockEditorModal.modal("show");

    // 모달 닫기 이벤트
    blockEditorModal.on("hidden.bs.modal", function () {
        settings.onClose();
    });

    // 모달 저장 콜백 등록
    window.blockEditorSaveCallback = function (data) {
        settings.onSave(data);
    };

    // 모달 닫기 함수 등록
    window.closeBlockEditorModal = function () {
        blockEditorModal.modal("hide");
    };

    // iframe 로드 확인
    $("#blockEditorFrame").on("load", function () {
        console.log("iframe이 로드되었습니다.");
    });
}
