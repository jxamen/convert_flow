<?php
/**
 * 랜딩페이지 블록 관리 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 페이지 제목
$page_title = "랜딩페이지 블록 관리";

// 블록 모델 로드
require_once CF_MODEL_PATH . '/landing_block.model.php';
$block_model = new LandingBlockModel();

// 랜딩페이지 ID (필수)
$landing_id = isset($_GET['landing_id']) ? intval($_GET['landing_id']) : 0;
if ($landing_id <= 0) {
    alert('유효하지 않은 랜딩페이지입니다.');
    goto_url(CF_LANDING_URL . '/landing_page_list.php');
}

// 랜딩페이지 정보 조회
$sql = "SELECT * FROM landing_pages WHERE id = $landing_id";
$landing_page = sql_fetch($sql);

if (!$landing_page) {
    alert('존재하지 않는 랜딩페이지입니다.');
    goto_url(CF_LANDING_URL . '/landing_page_list.php');
}

// 캠페인 정보 조회
$campaign_name = '';
if (!empty($landing_page['campaign_id'])) {
    $sql = "SELECT name FROM campaigns WHERE id = {$landing_page['campaign_id']}";
    $campaign = sql_fetch($sql);
    if ($campaign) {
        $campaign_name = $campaign['name'];
    }
}

// 블록 타입 목록 조회
$block_types = $block_model->get_block_types();

// 블록 템플릿 목록 조회
$block_templates = $block_model->get_block_templates();

// 페이지의 블록 인스턴스 목록 조회
$page_blocks = $block_model->get_page_blocks($landing_id);

// 액션 처리
$action = isset($_POST['action']) ? $_POST['action'] : '';


// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">랜딩페이지 블록 관리</h1>
    <p class="mb-4">랜딩페이지 "<strong><?php echo $landing_page['name']; ?></strong>"의 블록을 관리합니다.</p>
    
    <?php if (!empty($campaign_name)) { ?>
    <div class="mb-3">
        <span class="badge badge-primary">캠페인: <?php echo $campaign_name; ?></span>
    </div>
    <?php } ?>
    
    <div class="row">
        <!-- 블록 목록 -->
        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">블록 목록</h6>
                    <div class="dropdown no-arrow">
                        <button class="btn btn-sm btn-success" type="button" data-toggle="modal" data-target="#addBlockModal">
                            <i class="fas fa-plus fa-sm"></i> 블록 추가
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($page_blocks)) { ?>
                        <div class="text-center py-5">
                            <p class="text-muted mb-0">추가된 블록이 없습니다.</p>
                            <p class="text-muted">오른쪽 상단의 '블록 추가' 버튼을 클릭하여 블록을 추가하세요.</p>
                        </div>
                    <?php } else { ?>
                        <div class="blocks-container">
                            <ul id="blockSortable" class="list-group">
                                <?php foreach ($page_blocks as $block) { ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center" data-block-id="<?php echo $block['id']; ?>">
                                    <div class="block-info">
                                        <i class="fas fa-grip-vertical handle mr-2 text-muted"></i>
                                        <span class="badge badge-info mr-2"><?php echo $block['category']; ?></span>
                                        <strong><?php echo $block['template_name']; ?></strong>
                                    </div>
                                    <div class="block-actions">
                                        <button class="btn btn-sm btn-primary edit-block" data-block-id="<?php echo $block['id']; ?>">
                                            <i class="fas fa-edit fa-sm"></i> 설정
                                        </button>
                                        
                                        <button class="btn btn-sm btn-primary edit-block-editor" data-block-id="<?php echo $block['id']; ?>" data-landing-id="<?php echo $landing_id; ?>"><i class="fas fa-edit"></i> 블록 편집</button>

                                        <button class="btn btn-sm btn-danger delete-block" data-block-id="<?php echo $block['id']; ?>" data-template-name="<?php echo $block['template_name']; ?>">
                                            <i class="fas fa-trash fa-sm"></i> 삭제
                                        </button>
                                    </div>
                                </li>
                                <?php } ?>
                            </ul>
                        </div>
                    <?php } ?>
                </div>
                <div class="card-footer">
                    <a href="<?php echo CF_LANDING_URL; ?>/landing_page_edit.php?id=<?php echo $landing_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left fa-sm"></i> 랜딩페이지 편집으로 돌아가기
                    </a>
                    <a href="<?php echo CF_LANDING_URL; ?>/landing_page_preview.php?id=<?php echo $landing_id; ?>" class="btn btn-info float-right" target="_blank">
                        <i class="fas fa-eye fa-sm"></i> 미리보기
                    </a>
                </div>
            </div>
        </div>
        
        <!-- 랜딩페이지 레이아웃 미리보기 -->
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">레이아웃 미리보기</h6>
                </div>
                <div class="card-body">
                    <div class="landing-preview border p-2" style="height: 600px; overflow-y: auto;">
                        <?php if (empty($page_blocks)) { ?>
                            <div class="text-center py-5">
                                <p class="text-muted">블록을 추가하면 레이아웃 미리보기가 표시됩니다.</p>
                            </div>
                        <?php } else { ?>
                            <div class="blocks-preview">
                                <?php foreach ($page_blocks as $block) { ?>
                                <div class="block-preview mb-3 p-2 border" data-block-id="<?php echo $block['id']; ?>">
                                    <div class="block-header bg-light p-2 mb-2">
                                        <span class="badge badge-info mr-2"><?php echo $block['category']; ?></span>
                                        <strong><?php echo $block['template_name']; ?></strong>
                                    </div>
                                    <div class="block-content p-2">
                                        <!-- 여기에 블록 미리보기 내용 -->
                                        <p class="text-muted mb-0">블록 미리보기가 표시됩니다.</p>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 블록 추가 모달 -->
<div class="modal fade" id="addBlockModal" tabindex="-1" role="dialog" aria-labelledby="addBlockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addBlockModalLabel">블록 추가</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="blockTypesTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="all-tab" data-toggle="tab" href="#all" role="tab" aria-controls="all" aria-selected="true">전체</a>
                    </li>
                    <?php
                    $categories = array();
                    // 카테고리 목록 생성
                    foreach ($block_types as $type) {
                        if (isset($type['category']) && !empty($type['category']) && !in_array($type['category'], $categories)) {
                            $categories[] = $type['category'];
                        }
                    }

                    // 카테고리가 없으면 기본값 추가
                    if (empty($categories)) {
                        $categories[] = '기본';
                    }
                    
                    foreach ($categories as $key => $category) {
                        // 고유 ID 생성 (한글 등 다국어 지원)
                        $category_id = 'cat-' . $key;
                        
                        // 영문/숫자가 있으면 그것을 활용
                        $simple_id = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $category));
                        if (!empty($simple_id)) {
                            $category_id = $simple_id;
                        }
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" id="<?php echo $category_id; ?>-tab" data-toggle="tab" href="#<?php echo $category_id; ?>" role="tab" aria-controls="<?php echo $category_id; ?>" aria-selected="false"><?php echo $category; ?></a>
                    </li>
                    <?php } ?>
                </ul>
                <div class="tab-content p-3" id="blockTypesContent">
                    <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
                        <div class="row">
                            <?php foreach ($block_templates as $template) { ?>
                            <div class="col-md-4 col-lg-3 mb-4">
                                <div class="card h-100">
                                    <?php if (!empty($template['thumbnail'])) { ?>
                                    <img class="card-img-top" src="<?php echo $template['thumbnail']; ?>" alt="<?php echo $template['name']; ?>">
                                    <?php } else { ?>
                                    <div class="card-img-top bg-light text-center py-4">
                                        <i class="fas fa-puzzle-piece fa-3x text-muted"></i>
                                    </div>
                                    <?php } ?>
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo $template['name']; ?></h6>
                                        <span class="badge badge-info"><?php echo $template['category']; ?></span>
                                    </div>
                                    <div class="card-footer">
                                        <form method="post">
                                            <?php echo get_admin_token_fields(); ?>
                                            <input type="hidden" name="action" value="add_block">
                                            <input type="hidden" name="landing_id" value="<?php echo $landing_id; ?>">
                                            <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                            <button type="button" class="btn btn-sm btn-primary btn-block add-block">추가하기</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                    
                    <?php foreach ($categories as $key => $category) {
                        // 고유 ID 생성 (한글 등 다국어 지원)
                        $category_id = 'cat-' . $key;
                    ?>
                    <div class="tab-pane fade" id="<?php echo $category_id; ?>" role="tabpanel" aria-labelledby="<?php echo $category_id; ?>-tab">
                        <div class="row">
                            <?php
                            $category_templates = array_filter($block_templates, function($template) use ($category) {
                                return $template['category'] == $category;
                            });
                            
                            foreach ($category_templates as $template) {
                            ?>
                            <div class="col-md-4 col-lg-3 mb-4">
                                <div class="card h-100">
                                    <?php if (!empty($template['thumbnail'])) { ?>
                                    <img class="card-img-top" src="<?php echo $template['thumbnail']; ?>" alt="<?php echo $template['name']; ?>">
                                    <?php } else { ?>
                                    <div class="card-img-top bg-light text-center py-4">
                                        <i class="fas fa-puzzle-piece fa-3x text-muted"></i>
                                    </div>
                                    <?php } ?>
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo $template['name']; ?></h6>
                                    </div>
                                    <div class="card-footer">
                                        <form method="post">
                                            <?php echo get_admin_token_fields(); ?>
                                            <input type="hidden" name="action" value="add_block">
                                            <input type="hidden" name="landing_id" value="<?php echo $landing_id; ?>">
                                            <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                            <button type="button" class="btn btn-sm btn-primary btn-block add-block">추가하기</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<!-- 블록 삭제 확인 모달 -->
<div class="modal fade" id="deleteBlockModal" tabindex="-1" role="dialog" aria-labelledby="deleteBlockModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteBlockModalLabel">블록 삭제 확인</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p>정말로 <strong id="deleteBlockName"></strong> 블록을 삭제하시겠습니까?</p>
                <p class="text-danger">이 작업은 되돌릴 수 없습니다.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">취소</button>
                <form method="post" action="" id="deleteBlockForm">
                    <?php echo get_admin_token_fields(); ?>
                    <input type="hidden" name="action" value="delete_block">
                    <input type="hidden" name="block_id" id="deleteBlockId" value="">
                    <button type="button" class="btn btn-danger" id="delete-block">삭제</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 블록 편집 모달 -->
<div class="modal fade" id="editBlockModal" tabindex="-1" role="dialog" aria-labelledby="editBlockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editBlockModalLabel">블록 편집</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="blockEditor" class="p-3">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">로딩중...</span>
                        </div>
                        <p class="mt-2">블록 정보 로딩중...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="saveBlockChanges">변경사항 저장</button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery UI -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
<script>
$(document).ready(function() {
    // 블록 순서 변경 (드래그 앤 드롭)
    $("#blockSortable").sortable({
        handle: ".handle",
        update: function(event, ui) {
            var blockOrders = {};
            $("#blockSortable li").each(function(index) {
                var blockId = $(this).data("block-id");
                blockOrders[blockId] = index + 1;
            });
            
            // AJAX 요청으로 순서 업데이트
            $.ajax({
                url: "<?php echo CF_LANDING_URL; ?>/ajax.action.php",
                type: "POST",
                data: {
                    action: "update_order",
                    block_order: blockOrders,
                    <?php echo get_admin_token_values(); ?>
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error("서버 요청 중 오류가 발생했습니다.");
                }
            });
        }
    });
    
    // 블록 삭제 버튼 클릭
    $(".delete-block").on("click", function() {
        var blockId = $(this).data("block-id");
        var templateName = $(this).data("template-name");
        
        $("#deleteBlockId").val(blockId);
        $("#deleteBlockName").text(templateName);
        $("#deleteBlockModal").modal("show");
    });
    
    // 블록 편집 버튼 클릭
    $(".edit-block").on("click", function() {
        var blockId = $(this).data("block-id");
        
        // 블록 정보 로드
        $.ajax({
            url: "<?php echo CF_LANDING_URL; ?>/ajax.action.php",
            type: "POST",
            data: {
                action:"get_block",
                block_id: blockId
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // 블록 에디터 폼 표시
                    $("#blockEditor").html(response.template.html_content);
                    $("#editBlockModal").modal("show");
                    
                    // 코드 에디터 초기화 (HTML, CSS, JS)
                    if (typeof initCodeEditors === "function") {
                        initCodeEditors();
                    }
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error("블록 정보를 불러오는 중 오류가 발생했습니다.");
            }
        });
    });

    
    
    // 블록 변경사항 저장
    $("#delete-block").on("click", function() { 
        var form = $("#deleteBlockForm");

        $.ajax({
            url: "<?php echo CF_LANDING_URL; ?>/ajax.action.php",
            type: "POST",
            data: form.serialize(),
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // 성공 시 처리 (예: 페이지 새로고침 또는 메시지 표시)
                    toastr.success("블록이 삭제 되었습니다.");
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error("서버 요청 중 오류가 발생했습니다.");
            }
        });
    });

    // 블록 변경사항 저장
    $("#saveBlockChanges").on("click", function() {
        var form = $("#blockEditForm");
        
        if (form.length === 0) {
            toastr.error("편집 폼을 찾을 수 없습니다.");
            return;
        }
        
        // 코드 에디터 내용 동기화 (CSS, JS)
        if (typeof syncCodeEditors === "function") {
            syncCodeEditors();
        }

        // FormData 객체 생성 (파일 업로드 지원)
        var formData = new FormData(form[0]);
            
        // AJAX로 폼 제출
        $.ajax({
            url: form.attr("action"),
            type: form.attr("method"),
            data: formData,       
            dataType: "json",     
            processData: false,  // 필수: FormData 처리 방지
            contentType: false,  // 필수: Content-Type 헤더 설정 방지
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $("#editBlockModal").modal("hide");
                    
                    // 페이지 새로고침
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error("서버 요청 중 오류가 발생했습니다.");
            }
        });
    });


    // 추가하기 버튼 클릭
    $(".add-block").on("click", function() {        
        // 클릭된 버튼의 상위 폼 선택
        var form = $(this).closest("form");
        
        $.ajax({
            url: "<?php echo CF_LANDING_URL; ?>/ajax.action.php",
            type: "POST",
            data: form.serialize(),
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // 성공 시 처리 (예: 페이지 새로고침 또는 메시지 표시)
                    toastr.success("블록이 추가되었습니다.");
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error("서버 요청 중 오류가 발생했습니다.");
            }
        });
    });
});
</script>


<script>
    let BLOCK_EDITOR_URL = "<?php echo CF_BLOCK_EDITOR_URL;?>";
</script>
<!-- 모달 호출 스크립트 -->
<script src="<?php echo CF_JS_URL; ?>/block_editor_modal.js"></script>
<script>
$(document).ready(function() {
    // 블록 편집 버튼 클릭 이벤트
    $('.edit-block-editor').on('click', function() {
        var blockId = $(this).data('block-id');
        var landingId = $(this).data('landing-id');
        
        // 블록 에디터 모달 열기
        openBlockEditorModal({
            block_id: blockId,
            landing_id: landingId,
            title: '블록 편집',
            onSave: function(data) {
                setTimeout(function() {
                    //location.reload();
                }, 1000);
            }
        });
    });
});
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>