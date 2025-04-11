<?php
/**
 * 랜딩페이지 블록 타입 및 템플릿 관리
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 관리자 권한 체크
if (!$is_admin) {
    alert('관리자만 접근할 수 있습니다.');
    goto_url(CF_URL);
}

// 페이지 제목
$page_title = "블록 템플릿 관리";

// 블록 모델 로드
require_once CF_MODEL_PATH . '/landing_block.model.php';
$block_model = new LandingBlockModel();

// 블록 옵션 파일 로드
include_once CF_INCLUDE_PATH . '/landing_block_options.php';

// 현재 탭
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'types';

// 블록 타입 목록 조회
$block_types = $block_model->get_block_types();

// 블록 템플릿 목록 조회
$block_templates = $block_model->get_block_templates(0, false);

// 액션 처리
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action) {
    // CSRF 토큰 검증
    check_admin_token();
    
    switch ($action) {
        // 블록 템플릿 추가
        case 'add_block_template':
            $block_type_id = isset($_POST['block_type_id']) ? intval($_POST['block_type_id']) : 0;
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $thumbnail = isset($_POST['thumbnail']) ? trim($_POST['thumbnail']) : '';
            $html_content = isset($_POST['html_content']) ? $_POST['html_content'] : '';
            $css_content = isset($_POST['css_content']) ? $_POST['css_content'] : '';
            $js_content = isset($_POST['js_content']) ? $_POST['js_content'] : '';
            $is_public = isset($_POST['is_public']) ? 1 : 0;
            
            if ($block_type_id <= 0 || empty($name) || empty($html_content)) {
                alert('블록 타입, 이름, HTML 콘텐츠는 필수 입력 항목입니다.');
                break;
            }
            
            // 블록 타입 조회
            $sql = "SELECT * FROM landing_block_types WHERE id = $block_type_id";
            $block_type = sql_fetch($sql);
            
            if (!$block_type) {
                alert('유효하지 않은 블록 타입입니다.');
                break;
            }
            
            // 블록 타입별 기본 설정 가져오기
            //$default_settings = isset($default_block_settings[$block_type['name']]) ? $default_block_settings[$block_type['name']] : array();
            
            if ($default_settings) {
                $sql_default_settings = ", default_settings    = '" . sql_escape_string($default_settings) . "'";               
            } else {
                $sql_default_settings = ', default_settings    = null';
            }

            $sql = "INSERT INTO landing_block_templates 
                    SET 
                        block_type_id       = '" . sql_escape_string($block_type_id) . "',
                        name                = '" . sql_escape_string($name) . "',
                        thumbnail           = '" . sql_escape_string($thumbnail) . "',
                        html_content        = '" . sql_escape_string($html_content) . "',
                        css_content         = '" . sql_escape_string($css_content) . "',
                        js_content          = '" . sql_escape_string($js_content) . "',
                        is_public           = '" . sql_escape_string($is_public) . "'
                        {$sql_default_settings}";
            $result = sql_query($sql);
            
            if ($result) {
                $msg = '블록 템플릿이 추가되었습니다.';
                $redirect_url = CF_ADMIN_URL . "/landing/landing_block_template_list.php?msg=" . urlencode($msg);
                goto_url($redirect_url);
            } else {
                alert('블록 템플릿 추가 중 오류가 발생했습니다.');
            }
            break;
            
        // 블록 템플릿 삭제
        // 블록 템플릿 수정
        case 'edit_block_template':
            $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $thumbnail = isset($_POST['thumbnail']) ? trim($_POST['thumbnail']) : '';
            $html_content = isset($_POST['html_content']) ? $_POST['html_content'] : '';
            $css_content = isset($_POST['css_content']) ? $_POST['css_content'] : '';
            $js_content = isset($_POST['js_content']) ? $_POST['js_content'] : '';
            $default_settings = isset($_POST['default_settings']) ? $_POST['default_settings'] : '';
            $is_public = isset($_POST['is_public']) ? 1 : 0;

            
            if ($template_id <= 0 || empty($name) || empty($html_content)) {
                alert('템플릿 ID, 이름, HTML 콘텐츠는 필수 입력 항목입니다.');
                break;
            }
            
            // 블록 템플릿 존재 확인
            $sql = "SELECT * FROM landing_block_templates WHERE id = $template_id";
            $template = sql_fetch($sql);
            
            if (!$template) {
                alert('유효하지 않은 블록 템플릿입니다.');
                break;
            }


            if ($default_settings) {
                $sql_default_settings = ", default_settings    = '" . sql_escape_string($default_settings) . "'";               
            } else {
                $sql_default_settings = ', default_settings    = null';
            }
                        
            $sql = "update landing_block_templates 
                    set 
                        name                = '$name',
                        thumbnail           = '$thumbnail',
                        html_content        = '$html_content',
                        css_content         = '$css_content',
                        js_content          = '$js_content',
                        is_public           = '$is_public'
                        {$sql_default_settings}
                    where id = '{$template_id}'";
            $result = sql_query($sql);
            
            if ($result) {
                $msg = '블록 템플릿이 수정되었습니다.';
                $redirect_url = CF_ADMIN_URL . "/landing/landing_block_template_list.php?msg=" . urlencode($msg);
                goto_url($redirect_url);
            } else {
                alert('블록 템플릿 수정 중 오류가 발생했습니다.');
            }
            break;
            
        case 'delete_block_template':
            $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
            
            if ($template_id <= 0) {
                alert('유효하지 않은 블록 템플릿 ID입니다.');
                break;
            }
            
            // 사용 중인 블록 인스턴스 확인
            $sql = "SELECT COUNT(*) as cnt FROM landing_page_block_instances WHERE block_template_id = $template_id";
            $row = sql_fetch($sql);
            
            if ($row['cnt'] > 0) {
                alert("이 템플릿을 사용하는 블록 인스턴스가 {$row['cnt']}개 있습니다. 먼저 해당 인스턴스를 삭제해주세요.");
                break;
            }
            
            $sql = "delete from landing_block_templates where id = '{$template_id}'";
            $result = sql_query($sql);
            
            if ($result) {
                $msg = '블록 템플릿이 삭제되었습니다.';
                $redirect_url = CF_ADMIN_URL . "/landing/landing_block_template_list.php?msg=" . urlencode($msg);
                goto_url($redirect_url);
            } else {
                alert('블록 템플릿 삭제 중 오류가 발생했습니다.');
            }
            break;
    }
 }
 
 // 헤더 포함
 include_once CF_PATH . '/header.php';
 ?>

<div class="container-fluid">
   <?php
   // CSRF 토큰 저장
   $token_fields = get_admin_token_fields();
   echo $token_fields;
   preg_match('/name="token" value="([^"]+)"/', $token_fields, $token_matches);
   preg_match('/name="admin_time" value="([^"]+)"/', $token_fields, $time_matches);
   $admin_token = isset($token_matches[1]) ? $token_matches[1] : '';
   $admin_time = isset($time_matches[1]) ? $time_matches[1] : '';
   ?>
   <input type="hidden" id="adminToken" value="<?php echo $admin_token; ?>">
   <input type="hidden" id="adminTime" value="<?php echo $admin_time; ?>">
   
   <h1 class="h3 mb-2 text-gray-800"><?php echo $page_title?></h1>
   <p class="mb-4">랜딩페이지의 블록 템플릿을 관리합니다.</p>
   
   <!-- 블록 템플릿 관리 -->
   <div class="card shadow mb-4">
       <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
           <h6 class="m-0 font-weight-bold text-primary">블록 템플릿 목록</h6>
           <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addBlockTemplateModal">
               <i class="fas fa-plus fa-sm"></i> 블록 템플릿 추가
           </button>
       </div>
       <div class="card-body">
           <div class="table-responsive">
               <table class="table table-bordered" width="100%" cellspacing="0">
                   <thead>
                       <tr>
                           <th>ID</th>
                           <th>이름</th>
                           <th>블록 타입</th>
                           <th>카테고리</th>
                           <th>미리보기</th>
                           <th>공개 여부</th>
                           <th>사용 수</th>
                           <th>관리</th>
                       </tr>
                   </thead>
                   <tbody>
                       <?php
                       if (count($block_templates) > 0) {
                           foreach ($block_templates as $template) {
                               // 사용 수 조회
                               $sql = "SELECT COUNT(*) as cnt FROM landing_page_block_instances WHERE block_template_id = {$template['id']}";
                               $usage_count = sql_fetch($sql);
                               
                               echo '<tr>';
                               echo '<td>' . $template['id'] . '</td>';
                               echo '<td>' . $template['name'] . '</td>';
                               echo '<td>' . $template['type_name'] . '</td>';
                               echo '<td>' . $template['category'] . '</td>';
                               echo '<td>';
                               if (!empty($template['thumbnail'])) {
                                   echo '<img src="' . $template['thumbnail'] . '" alt="' . $template['name'] . '" class="img-thumbnail" style="max-height: 50px;">';
                               } else {
                                   echo '<span class="text-muted">없음</span>';
                               }
                               echo '</td>';
                               echo '<td>' . ($template['is_public'] ? '<span class="badge badge-success">공개</span>' : '<span class="badge badge-secondary">비공개</span>') . '</td>';
                               echo '<td>' . $usage_count['cnt'] . '</td>';
                               echo '<td>';
                               echo '<a href="#" class="btn btn-sm btn-info view-block-template" data-id="' . $template['id'] . '"><i class="fas fa-eye fa-sm"></i> 보기</a> ';
                               echo '<button type="button" class="btn btn-sm btn-primary edit-block-template" data-id="' . $template['id'] . '" data-name="' . $template['name'] . '"><i class="fas fa-edit fa-sm"></i> 수정</button> ';
                               echo '<button type="button" class="btn btn-sm btn-danger delete-block-template" data-id="' . $template['id'] . '" data-name="' . $template['name'] . '"><i class="fas fa-trash fa-sm"></i> 삭제</button>';

                               echo '<button class="btn btn-sm btn-primary edit-template" data-template-id="' . $template['id'] . '" data-landing-id="' . $landing_id . '"><i class="fas fa-edit"></i> 블록 편집</button>';

                               echo '</td>';
                               echo '</tr>';
                           }
                       } else {
                           echo '<tr><td colspan="8" class="text-center">등록된 블록 템플릿이 없습니다.</td></tr>';
                       }
                       ?>
                   </tbody>
               </table>
           </div>
       </div>
   </div>
</div>


<!-- 블록 템플릿 추가 모달 -->
<div class="modal fade" id="addBlockTemplateModal" tabindex="-1" role="dialog" aria-labelledby="addBlockTemplateModalLabel" aria-hidden="true">
   <div class="modal-dialog modal-lg" role="document">
       <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title" id="addBlockTemplateModalLabel">블록 템플릿 추가</h5>
               <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                   <span aria-hidden="true">×</span>
               </button>
           </div>
           <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
               <?php echo get_admin_token_fields(); ?>
               <input type="hidden" name="action" value="add_block_template">
               <div class="modal-body">
                   <div class="row">
                       <div class="col-md-6">
                           <div class="form-group">
                               <label for="blockTypeId">블록 타입 <span class="text-danger">*</span></label>
                               <select class="form-control" id="blockTypeId" name="block_type_id" required>
                                   <option value="">선택하세요</option>
                                   <?php foreach ($block_types as $type) { ?>
                                   <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?> (<?php echo $type['category']; ?>)</option>
                                   <?php } ?>
                               </select>
                           </div>
                           <div class="form-group">
                               <label for="templateName">이름 <span class="text-danger">*</span></label>
                               <input type="text" class="form-control" id="templateName" name="name" required>
                           </div>
                           <div class="form-group">
                               <label for="templateThumbnail">썸네일 URL</label>
                               <div class="input-group">
                                   <input type="text" class="form-control" id="templateThumbnail" name="thumbnail">
                                   <div class="input-group-append">
                                       <button class="btn btn-outline-secondary" type="button" onclick="openMediaLibrary('templateThumbnail')">선택</button>
                                   </div>
                               </div>
                           </div>
                           <div class="form-group">
                               <div class="custom-control custom-switch">
                                   <input type="checkbox" class="custom-control-input" id="isPublic" name="is_public" value="1" checked>
                                   <label class="custom-control-label" for="isPublic">공개</label>
                               </div>
                               <small class="form-text text-muted">공개 설정 시 모든 사용자가 이 템플릿을 사용할 수 있습니다.</small>
                           </div>
                           <div class="form-group">
                                <label for="defaultSettings">기본 설정 값 (JSON)</label>
                                <textarea class="form-control code-editor" id="defaultSettings" name="default_settings" rows="6"></textarea>
                                <small class="form-text text-muted">블록의 기본 설정값을 JSON 형식으로 입력하세요.</small>
                            </div>
                       </div>
                       <div class="col-md-6">
                           <div class="form-group">
                               <label for="htmlContent">HTML 내용 <span class="text-danger">*</span></label>
                               <textarea class="form-control code-editor" id="htmlContent" name="html_content" rows="6" required></textarea>
                               <small class="form-text text-muted">변수는 {{변수명}} 형식으로 사용할 수 있습니다.</small>
                           </div>
                           <div class="form-group">
                               <label for="cssContent">CSS 스타일</label>
                               <textarea class="form-control code-editor" id="cssContent" name="css_content" rows="6"></textarea>
                           </div>
                           <div class="form-group">
                               <label for="jsContent">JavaScript 코드</label>
                               <textarea class="form-control code-editor" id="jsContent" name="js_content" rows="6"></textarea>
                           </div>
                       </div>
                   </div>
               </div>
               <div class="modal-footer">
                   <button class="btn btn-secondary" type="button" data-dismiss="modal">취소</button>
                   <button type="submit" class="btn btn-primary">추가</button>
               </div>
           </form>
       </div>
   </div>
</div>

<!-- 블록 템플릿 삭제 확인 모달 -->
<div class="modal fade" id="deleteBlockTemplateModal" tabindex="-1" role="dialog" aria-labelledby="deleteBlockTemplateModalLabel" aria-hidden="true">
   <div class="modal-dialog" role="document">
       <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title" id="deleteBlockTemplateModalLabel">블록 템플릿 삭제 확인</h5>
               <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                   <span aria-hidden="true">×</span>
               </button>
           </div>
           <div class="modal-body">
               <p>정말로 <strong id="deleteBlockTemplateName"></strong> 블록 템플릿을 삭제하시겠습니까?</p>
               <p class="text-danger">이 작업은 되돌릴 수 없습니다. 이 템플릿을 사용하는 블록 인스턴스가 있는 경우 삭제할 수 없습니다.</p>
           </div>
           <div class="modal-footer">
               <button class="btn btn-secondary" type="button" data-dismiss="modal">취소</button>
               <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                   <?php echo get_admin_token_fields(); ?>
                   <input type="hidden" name="action" value="delete_block_template">
                   <input type="hidden" name="template_id" id="deleteBlockTemplateId" value="">
                   <button type="submit" class="btn btn-danger">삭제</button>
               </form>
           </div>
       </div>
   </div>
</div>

<!-- 블록 템플릿 수정 모달 -->
<div class="modal fade" id="editBlockTemplateModal" tabindex="-1" role="dialog" aria-labelledby="editBlockTemplateModalLabel" aria-hidden="true">
   <div class="modal-dialog modal-lg" role="document">
       <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title" id="editBlockTemplateModalLabel">블록 템플릿 수정</h5>
               <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                   <span aria-hidden="true">×</span>
               </button>
           </div>
           <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
               <?php echo get_admin_token_fields(); ?>
               <input type="hidden" name="action" value="edit_block_template">
               <input type="hidden" name="template_id" id="editTemplateId" value="">
               <div class="modal-body">
                   <div class="row">
                       <div class="col-md-6">
                           <div class="form-group">
                               <label for="editBlockTypeId">블록 타입</label>
                               <input type="text" class="form-control" id="editBlockTypeName" readonly>
                           </div>
                           <div class="form-group">
                               <label for="editTemplateName">이름 <span class="text-danger">*</span></label>
                               <input type="text" class="form-control" id="editTemplateName" name="name" required>
                           </div>
                           <div class="form-group">
                               <label for="editTemplateThumbnail">썸네일 URL</label>
                               <div class="input-group">
                                   <input type="text" class="form-control" id="editTemplateThumbnail" name="thumbnail">
                                   <div class="input-group-append">
                                       <button class="btn btn-outline-secondary" type="button" onclick="openMediaLibrary('editTemplateThumbnail')">선택</button>
                                   </div>
                               </div>
                           </div>
                           <div class="form-group">
                               <div class="custom-control custom-switch">
                                   <input type="checkbox" class="custom-control-input" id="editIsPublic" name="is_public" value="1">
                                   <label class="custom-control-label" for="editIsPublic">공개</label>
                               </div>
                               <small class="form-text text-muted">공개 설정 시 모든 사용자가 이 템플릿을 사용할 수 있습니다.</small>
                           </div>
                           <div class="form-group">
                                <label for="editDefaultSettings">기본 설정 값 (JSON)</label>
                                <textarea class="form-control code-editor" id="editDefaultSettings" name="default_settings" rows="10"></textarea>
                                <small class="form-text text-muted">블록의 기본 설정값을 JSON 형식으로 입력하세요.</small>
                            </div>
                       </div>
                       <div class="col-md-6">
                           <div class="form-group">
                               <label for="editHtmlContent">HTML 내용 <span class="text-danger">*</span></label>
                               <textarea class="form-control code-editor" id="editHtmlContent" name="html_content" rows="6" required></textarea>
                               <small class="form-text text-muted">변수는 {{변수명}} 형식으로 사용할 수 있습니다.</small>
                           </div>
                           <div class="form-group">
                               <label for="editCssContent">CSS 스타일</label>
                               <textarea class="form-control code-editor" id="editCssContent" name="css_content" rows="6"></textarea>
                           </div>
                           <div class="form-group">
                               <label for="editJsContent">JavaScript 코드</label>
                               <textarea class="form-control code-editor" id="editJsContent" name="js_content" rows="6"></textarea>
                           </div>
                       </div>
                   </div>
               </div>
               <div class="modal-footer">
                   <button class="btn btn-secondary" type="button" data-dismiss="modal">취소</button>
                   <button type="submit" class="btn btn-primary" id="editTemplateSubmit">수정</button>
               </div>
           </form>
       </div>
   </div>
</div>

<!-- 블록 템플릿 상세 모달 -->
<div class="modal fade" id="viewBlockTemplateModal" tabindex="-1" role="dialog" aria-labelledby="viewBlockTemplateModalLabel" aria-hidden="true">
   <div class="modal-dialog modal-xl" role="document">
       <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title" id="viewBlockTemplateModalLabel">블록 템플릿 상세</h5>
               <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                   <span aria-hidden="true">×</span>
               </button>
           </div>
           <div class="modal-body">
               <div id="templateDetailContent">
                   <div class="text-center py-5">
                       <div class="spinner-border text-primary" role="status">
                           <span class="sr-only">로딩중...</span>
                       </div>
                       <p class="mt-2">템플릿 정보 로딩중...</p>
                   </div>
               </div>
           </div>
           <div class="modal-footer">
               <button class="btn btn-secondary" type="button" data-dismiss="modal">닫기</button>
           </div>
       </div>
   </div>
</div>

<script>
    let BLOCK_EDITOR_URL = "<?php echo CF_BLOCK_EDITOR_URL;?>";
</script>
<!-- 모달 호출 스크립트 -->
<script src="<?php echo CF_JS_URL; ?>/block_editor_modal.js"></script>
<script>
$(document).ready(function() {
    // 블록 편집 버튼 클릭 이벤트
    $('.edit-template').on('click', function() {
        var templateId = $(this).data('template-id');
        var landingId = $(this).data('landing-id');
        
        // 블록 에디터 모달 열기
        openBlockEditorModal({
            template_id: templateId,
            landing_id: landingId,
            title: '블록 템플릿 편집',
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