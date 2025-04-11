<?php
/**
 * 블록 저장 AJAX 핸들러
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// AJAX 요청 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => '잘못된 요청 방식입니다.'));
    exit;
}

// 관리자 권한 체크
if (!$is_admin) {
    echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
    exit;
}


// 액션 처리
$action = isset($_POST['action']) ? $_POST['action'] : '';


if ($action == "save_template") {
    $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
    $html_content = isset($_POST['html_content']) ? $_POST['html_content'] : '';
    $css_content = isset($_POST['css_content']) ? $_POST['css_content'] : '';
    $js_content = isset($_POST['js_content']) ? $_POST['js_content'] : '';
    
    if (!$template_id) {
        $response = array('success' => false, 'message' => '템플릿 ID가 필요합니다.');
    }
    
    // 템플릿 존재 확인
    $sql = "SELECT * FROM landing_block_templates WHERE id = {$template_id}";
    $template = sql_fetch($sql);
    
    if (!$template) {
        $response = array('success' => false, 'message' => '존재하지 않는 템플릿입니다.');
    }
    
    // 템플릿 업데이트
    $sql = "UPDATE landing_block_templates 
            SET html_content = '" . sql_escape_string($html_content) . "',
                css_content = '" . sql_escape_string($css_content) . "',
                js_content = '" . sql_escape_string($js_content) . "'
            WHERE id = {$template_id}";
    
    if (sql_query($sql)) {
        $response = array(
            'success' => true,
            'message' => '템플릿이 성공적으로 저장되었습니다.'
        );
        
        // 로그 기록
        $log_data = json_encode(array(
            'template_id' => $template_id
        ));
        
        $sql = "INSERT INTO logs
                SET user_id = {$member['id']},
                    log_type = '관리자_활동',
                    action = '템플릿 비주얼 에디터로 수정',
                    log_data = '" . sql_escape_string($log_data) . "',
                    ip_address = '" . sql_escape_string($_SERVER['REMOTE_ADDR']) . "',
                    created_at = NOW()";
        sql_query($sql);
    } else {
        $response = array('success' => false, 'message' => '템플릿 저장 중 오류가 발생했습니다.');
    }
}
else if ($action == "get_token") {
    // 관리자 권한 체크
    if (!$is_admin) {
        echo json_encode(array('success' => false, 'message' => '관리자만 사용할 수 있습니다.'));
        exit;
    }
    
    // 새 토큰 발급
    $admin_time = time();
    $token = md5($admin_time . $_SERVER['REMOTE_ADDR'] . mt_rand());
    
    echo json_encode(array(
        'success' => true,
        'token' => $token,
        'admin_time' => $admin_time
    ));
}
else if ($action == "delete_block_type") {
    // 토큰 검증
    $token = isset($_POST['token']) ? $_POST['token'] : '';
    $admin_time = isset($_POST['admin_time']) ? $_POST['admin_time'] : '';
    
    if ($token && $admin_time) {
        $token_valid = check_admin_token_fields($token, $admin_time);
        if (!$token_valid) {
            echo json_encode(array('success' => false, 'message' => '토큰이 만료되었습니다. 페이지를 새로고침한 후 다시 시도해 주세요.'));
            exit;
        }
    }
    
    // 블록 타입 삭제
    // 블록 타입 ID
    $type_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($type_id <= 0) {
        echo json_encode(array('success' => false, 'message' => '유효하지 않은 블록 타입 ID입니다.'));
        exit;
    }
    
    // 관리자 권한 체크
    if (!$is_admin) {
        echo json_encode(array('success' => false, 'message' => '관리자만 블록 타입을 삭제할 수 있습니다.'));
        exit;
    }
    
    // 블록 모델 로드
    require_once CF_MODEL_PATH . '/landing_block.model.php';
    $block_model = new LandingBlockModel();
    
    // 블록 타입 삭제
    $result = $block_model->delete_block_type($type_id);
    
    if ($result) {
        echo json_encode(array('success' => true, 'message' => '블록 타입이 성공적으로 삭제되었습니다.'));
    } else {
        echo json_encode(array('success' => false, 'message' => '블록 타입 삭제 중 오류가 발생했습니다.'));
    }
}
else if ($action == "get_template") {    
    // 템플릿 ID
    $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;

    if ($template_id <= 0) {
        echo json_encode(array('success' => false, 'message' => '유효하지 않은 템플릿 ID입니다.'));
        exit;
    }

    // 템플릿 정보 조회
    $sql = "SELECT t.*, bt.name as type_name, bt.category 
            FROM {$cf_table_prefix}landing_block_templates t
            JOIN {$cf_table_prefix}landing_block_types bt ON t.block_type_id = bt.id
            WHERE t.id = $template_id";
    $template = sql_fetch($sql);

    if (!$template) {
        echo json_encode(array('success' => false, 'message' => '템플릿 정보를 찾을 수 없습니다.'));
        exit;
    }

    // 템플릿 사용 수 조회
    $sql = "SELECT COUNT(*) as cnt FROM {$cf_table_prefix}landing_page_block_instances WHERE block_template_id = $template_id";
    $usage_count = sql_fetch($sql);

    // 템플릿 상세 HTML 생성
    ob_start();
    ?>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">기본 정보</h6>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">ID</th>
                            <td><?php echo $template['id']; ?></td>
                        </tr>
                        <tr>
                            <th>이름</th>
                            <td><?php echo $template['name']; ?></td>
                        </tr>
                        <tr>
                            <th>블록 타입</th>
                            <td><?php echo $template['type_name']; ?></td>
                        </tr>
                        <tr>
                            <th>카테고리</th>
                            <td><?php echo $template['category']; ?></td>
                        </tr>
                        <tr>
                            <th>공개 여부</th>
                            <td><?php echo $template['is_public'] ? '<span class="badge badge-success">공개</span>' : '<span class="badge badge-secondary">비공개</span>'; ?></td>
                        </tr>
                        <tr>
                            <th>사용 수</th>
                            <td><?php echo $usage_count['cnt']; ?></td>
                        </tr>
                        <tr>
                            <th>생성일</th>
                            <td><?php echo $template['created_at']; ?></td>
                        </tr>
                    </table>
                    
                    <?php if (!empty($template['thumbnail'])) { ?>
                    <div class="mt-3">
                        <h6 class="font-weight-bold">썸네일</h6>
                        <img src="<?php echo $template['thumbnail']; ?>" alt="<?php echo $template['name']; ?>" class="img-fluid border">
                    </div>
                    <?php } ?>
                    
                    <?php if (!empty($template['default_settings'])) { ?>
                    <div class="mt-3">
                        <h6 class="font-weight-bold">기본 설정 값</h6>
                        <pre class="bg-light p-3 border rounded"><?php echo json_encode(json_decode($template['default_settings']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">코드</h6>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="codeViewerTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="html-view-tab" data-toggle="tab" href="#htmlViewer" role="tab" aria-controls="htmlViewer" aria-selected="true">HTML</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="css-view-tab" data-toggle="tab" href="#cssViewer" role="tab" aria-controls="cssViewer" aria-selected="false">CSS</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="js-view-tab" data-toggle="tab" href="#jsViewer" role="tab" aria-controls="jsViewer" aria-selected="false">JavaScript</a>
                        </li>
                    </ul>
                    <div class="tab-content mt-3" id="codeViewerTabsContent">
                        <div class="tab-pane fade show active" id="htmlViewer" role="tabpanel" aria-labelledby="html-view-tab">
                            <textarea id="htmlViewEditor" rows="15" class="form-control code-editor" readonly><?php echo htmlspecialchars($template['html_content']); ?></textarea>
                        </div>
                        <div class="tab-pane fade" id="cssViewer" role="tabpanel" aria-labelledby="css-view-tab">
                            <textarea id="cssViewEditor" rows="15" class="form-control code-editor" readonly><?php echo htmlspecialchars($template['css_content']); ?></textarea>
                        </div>
                        <div class="tab-pane fade" id="jsViewer" role="tabpanel" aria-labelledby="js-view-tab">
                            <textarea id="jsViewEditor" rows="15" class="form-control code-editor" readonly><?php echo htmlspecialchars($template['js_content']); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // 코드 에디터 초기화
    function initViewTemplateEditors() {
        // CodeMirror 또는 기타 코드 에디터가 있다면 초기화
        if (typeof CodeMirror !== 'undefined') {
            window.htmlViewEditor = CodeMirror.fromTextArea(document.getElementById('htmlViewEditor'), {
                mode: 'htmlmixed',
                lineNumbers: true,
                theme: 'monokai',
                readOnly: true
            });
            
            window.cssViewEditor = CodeMirror.fromTextArea(document.getElementById('cssViewEditor'), {
                mode: 'css',
                lineNumbers: true,
                theme: 'monokai',
                readOnly: true
            });
            
            window.jsViewEditor = CodeMirror.fromTextArea(document.getElementById('jsViewEditor'), {
                mode: 'javascript',
                lineNumbers: true,
                theme: 'monokai',
                readOnly: true
            });
        }
    }

    // 페이지 로드 시 에디터 초기화
    $(document).ready(function() {
        initViewTemplateEditors();
    });
    </script>
    <?php
    $html = ob_get_clean();

    echo json_encode(array(
        'success' => true,
        'html' => $html,
        'template' => $template
    ));
}
?>