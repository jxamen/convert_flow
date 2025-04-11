<?php
/**
 * 폼 API 연동 설정 페이지
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

// API 모델 로드
require_once CF_MODEL_PATH . '/api_endpoint.model.php';
$api_model = new ApiEndpointModel();

// 폼 정보 조회
$form = $form_model->get_form($form_id);
if (!$form) {
    alert('존재하지 않는 폼입니다.', 'form_list.php');
}

// 권한 체크 (관리자가 아니면서 폼 소유자가 아닌 경우)
if (!$is_admin && $form['user_id'] != $member['id']) {
    alert('권한이 없습니다.', 'form_list.php');
}

// 연결된 API 엔드포인트 조회
$endpoints = $api_model->get_endpoints_by_form($form_id);

// 사용자의 모든 API 엔드포인트 조회 (선택 옵션용)
$all_endpoints = $api_model->get_user_endpoints($member['id']);

// 폼 필드 조회
$fields = $form_model->get_form_fields($form_id);

// 메시지 저장
$message = '';

// 연결 설정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'connect_api':
            $endpoint_id = isset($_POST['endpoint_id']) ? intval($_POST['endpoint_id']) : 0;
            
            if ($endpoint_id > 0) {
                // 기존 API 엔드포인트 연결
                $result = $api_model->connect_form_to_endpoint($form_id, $endpoint_id);
                
                if ($result) {
                    $message = '<div class="alert alert-success">API 엔드포인트가 연결되었습니다.</div>';
                    
                    // 연결된 API 엔드포인트 목록 갱신
                    $endpoints = $api_model->get_endpoints_by_form($form_id);
                } else {
                    $message = '<div class="alert alert-danger">API 엔드포인트 연결 중 오류가 발생했습니다.</div>';
                }
            } else {
                $message = '<div class="alert alert-danger">유효하지 않은 API 엔드포인트입니다.</div>';
            }
            break;
            
        case 'create_api':
            $api_data = array(
                'user_id' => $member['id'],
                'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
                'url' => isset($_POST['url']) ? trim($_POST['url']) : '',
                'method' => isset($_POST['method']) ? trim($_POST['method']) : 'POST',
                'auth_type' => isset($_POST['auth_type']) ? trim($_POST['auth_type']) : 'none',
                'auth_username' => isset($_POST['auth_username']) ? trim($_POST['auth_username']) : '',
                'auth_password' => isset($_POST['auth_password']) ? trim($_POST['auth_password']) : '',
                'auth_token' => isset($_POST['auth_token']) ? trim($_POST['auth_token']) : '',
                'request_format' => isset($_POST['request_format']) ? trim($_POST['request_format']) : 'JSON',
                'is_test_mode' => isset($_POST['is_test_mode']) ? 1 : 0
            );
            
            // 헤더 데이터 처리
            $headers = array();
            if (isset($_POST['header_keys']) && isset($_POST['header_values'])) {
                $header_keys = $_POST['header_keys'];
                $header_values = $_POST['header_values'];
                
                foreach ($header_keys as $index => $key) {
                    if (!empty($key) && isset($header_values[$index])) {
                        $headers[$key] = $header_values[$index];
                    }
                }
            }
            
            if (!empty($headers)) {
                $api_data['headers'] = json_encode($headers);
            }
            
            // 유효성 검사
            if (empty($api_data['name']) || empty($api_data['url'])) {
                $message = '<div class="alert alert-danger">API 이름과 URL은 필수입니다.</div>';
            } else {
                // API 엔드포인트 생성
                $endpoint_id = $api_model->create_endpoint($api_data);
                
                if ($endpoint_id) {
                    // 폼과 API 엔드포인트 연결
                    $result = $api_model->connect_form_to_endpoint($form_id, $endpoint_id);
                    
                    if ($result) {
                        $message = '<div class="alert alert-success">API 엔드포인트가 생성되고 연결되었습니다.</div>';
                        
                        // 연결된 API 엔드포인트 목록 갱신
                        $endpoints = $api_model->get_endpoints_by_form($form_id);
                        
                        // 필드 매핑 페이지로 이동
                        goto_url("form_api_mapping.php?form_id={$form_id}&endpoint_id={$endpoint_id}");
                    } else {
                        $message = '<div class="alert alert-danger">API 엔드포인트 연결 중 오류가 발생했습니다.</div>';
                    }
                } else {
                    $message = '<div class="alert alert-danger">API 엔드포인트 생성 중 오류가 발생했습니다.</div>';
                }
            }
            break;
            
        case 'disconnect_api':
            $endpoint_id = isset($_POST['endpoint_id']) ? intval($_POST['endpoint_id']) : 0;
            
            if ($endpoint_id > 0) {
                // API 엔드포인트 연결 해제
                $result = $api_model->disconnect_form_from_endpoint($form_id, $endpoint_id);
                
                if ($result) {
                    $message = '<div class="alert alert-success">API 엔드포인트 연결이 해제되었습니다.</div>';
                    
                    // 연결된 API 엔드포인트 목록 갱신
                    $endpoints = $api_model->get_endpoints_by_form($form_id);
                } else {
                    $message = '<div class="alert alert-danger">API 엔드포인트 연결 해제 중 오류가 발생했습니다.</div>';
                }
            } else {
                $message = '<div class="alert alert-danger">유효하지 않은 API 엔드포인트입니다.</div>';
            }
            break;
    }
}

// 페이지 제목
$page_title = "폼 API 연동: " . $form['name'];

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">폼 API 연동: <?php echo $form['name']; ?></h1>
    <p class="mb-4">수집된 리드 데이터를 외부 API로 전송하기 위한 설정입니다.</p>
    
    <?php echo $message; ?>
    
    <!-- 연결된 API 엔드포인트 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">연결된 API 엔드포인트</h6>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#connectApiModal">
                <i class="fas fa-plus"></i> API 연결
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($endpoints)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 연결된 API 엔드포인트가 없습니다. '새 API 연결' 또는 '기존 API 연결' 버튼을 클릭하여 API를 연결하세요.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th>API 이름</th>
                        <th>URL</th>
                        <th>메소드</th>
                        <th>인증 방식</th>
                        <th>테스트 모드</th>
                        <th>필드 매핑</th>
                        <th>관리</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($endpoints as $endpoint): ?>
                        <tr>
                            <td><?php echo $endpoint['name']; ?></td>
                            <td><?php echo $endpoint['url']; ?></td>
                            <td><span class="badge badge-info"><?php echo $endpoint['method']; ?></span></td>
                            <td><?php echo ucfirst($endpoint['auth_type']); ?></td>
                            <td>
                                <?php if ($endpoint['is_test_mode']): ?>
                                <span class="badge badge-warning">테스트 모드</span>
                                <?php else: ?>
                                <span class="badge badge-success">운영 모드</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="form_api_mapping.php?form_id=<?php echo $form_id; ?>&endpoint_id=<?php echo $endpoint['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-exchange-alt"></i> 필드 매핑
                                </a>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="form_api_test.php?form_id=<?php echo $form_id; ?>&endpoint_id=<?php echo $endpoint['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-vial"></i> 테스트
                                    </a>
                                    <form method="post" onsubmit="return confirm('정말로 이 API 연결을 해제하시겠습니까?');" style="display: inline-block;">
                                        <input type="hidden" name="action" value="disconnect_api">
                                        <input type="hidden" name="endpoint_id" value="<?php echo $endpoint['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-unlink"></i> 연결 해제
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="mt-3">
                <a href="form_edit.php?id=<?php echo $form_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 폼 수정으로 돌아가기
                </a>
            </div>
        </div>
    </div>
    
    <!-- API 연동 설명 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">API 연동 방법</h6>
        </div>
        <div class="card-body">
            <h5 class="font-weight-bold">1. API 엔드포인트 설정</h5>
            <p>폼 데이터를 전송할 외부 API 엔드포인트를 연결합니다. 기존 API를 사용하거나 새로 생성할 수 있습니다.</p>
            
            <h5 class="font-weight-bold">2. 필드 매핑</h5>
            <p>폼 필드와 API 매개변수 간의 매핑을 설정합니다. 필요한 경우 데이터 변환 규칙을 지정할 수 있습니다.</p>
            
            <h5 class="font-weight-bold">3. 테스트</h5>
            <p>실제 데이터를 전송하기 전에 API 연동을 테스트하여 성공적으로 동작하는지 확인합니다.</p>
            
            <h5 class="font-weight-bold">4. 모니터링</h5>
            <p>연동 후 데이터 전송 상태를 모니터링하고 필요한 경우 설정을 조정합니다.</p>
            
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle"></i> 데이터는 폼 제출 시 자동으로 연결된 모든 API 엔드포인트로 전송됩니다. 실패한 전송은 자동으로 일정 횟수(3회) 재시도됩니다.
            </div>
        </div>
    </div>
</div>

<!-- API 연결 모달 -->
<div class="modal fade" id="connectApiModal" tabindex="-1" role="dialog" aria-labelledby="connectApiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="connectApiModalLabel">API 연결</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- 탭 메뉴 -->
                <ul class="nav nav-tabs" id="apiTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="existing-tab" data-toggle="tab" href="#existing" role="tab" aria-controls="existing" aria-selected="true">기존 API 사용</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="new-tab" data-toggle="tab" href="#new" role="tab" aria-controls="new" aria-selected="false">새 API 생성</a>
                    </li>
                </ul>
                
                <!-- 탭 내용 -->
                <div class="tab-content" id="apiTabsContent">
                    <!-- 기존 API 연결 탭 -->
                    <div class="tab-pane fade show active" id="existing" role="tabpanel" aria-labelledby="existing-tab">
                        <form method="post" action="form_api.php?id=<?php echo $form_id; ?>" class="mt-3">
                            <input type="hidden" name="action" value="connect_api">
                            
                            <div class="form-group">
                                <label for="endpoint_id">API 엔드포인트 선택</label>
                                <select class="form-control" id="endpoint_id" name="endpoint_id" required>
                                    <option value="">API 엔드포인트 선택...</option>
                                    <?php foreach ($all_endpoints as $endpoint): ?>
                                        <?php
                                        // 이미 연결된 엔드포인트는 제외
                                        $is_connected = false;
                                        foreach ($endpoints as $connected) {
                                            if ($connected['id'] == $endpoint['id']) {
                                                $is_connected = true;
                                                break;
                                            }
                                        }
                                        if ($is_connected) continue;
                                        ?>
                                        <option value="<?php echo $endpoint['id']; ?>"><?php echo $endpoint['name']; ?> (<?php echo $endpoint['url']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($all_endpoints)): ?>
                                <small class="form-text text-muted">사용 가능한 API 엔드포인트가 없습니다. '새 API 생성' 탭을 이용하세요.</small>
                                <?php else: ?>
                                <small class="form-text text-muted">이미 설정된 API 엔드포인트를 선택하여 빠르게 연결할 수 있습니다.</small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary" <?php echo empty($all_endpoints) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-link"></i> API 연결
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- 새 API 생성 탭 -->
                    <div class="tab-pane fade" id="new" role="tabpanel" aria-labelledby="new-tab">
                        <form method="post" action="form_api.php?id=<?php echo $form_id; ?>" class="mt-3">
                            <input type="hidden" name="action" value="create_api">
                            
                            <div class="form-group">
                                <label for="name">API 이름 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <small class="form-text text-muted">내부 관리용 이름입니다.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="url">API URL <span class="text-danger">*</span></label>
                                <input type="url" class="form-control" id="url" name="url" required>
                                <small class="form-text text-muted">데이터를 전송할 전체 URL을 입력하세요.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="method">HTTP 메소드</label>
                                <select class="form-control" id="method" name="method">
                                    <option value="POST">POST</option>
                                    <option value="GET">GET</option>
                                    <option value="PUT">PUT</option>
                                    <option value="PATCH">PATCH</option>
                                    <option value="DELETE">DELETE</option>
                                </select>
                                <small class="form-text text-muted">대부분의 API는 POST 메소드를 사용합니다.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="request_format">요청 형식</label>
                                <select class="form-control" id="request_format" name="request_format">
                                    <option value="JSON">JSON</option>
                                    <option value="XML">XML</option>
                                    <option value="FORM">Form URL Encoded</option>
                                </select>
                                <small class="form-text text-muted">데이터 전송 형식을 선택하세요.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="auth_type">인증 방식</label>
                                <select class="form-control" id="auth_type" name="auth_type">
                                    <option value="none">인증 없음</option>
                                    <option value="basic">Basic 인증</option>
                                    <option value="bearer">Bearer 토큰</option>
                                    <option value="api_key">API 키</option>
                                </select>
                                <small class="form-text text-muted">API 인증 방식을 선택하세요.</small>
                            </div>
                            
                            <!-- Basic 인증 필드 -->
                            <div id="basic-auth-fields" style="display: none;">
                                <div class="form-group">
                                    <label for="auth_username">사용자명</label>
                                    <input type="text" class="form-control" id="auth_username" name="auth_username">
                                </div>
                                
                                <div class="form-group">
                                    <label for="auth_password">비밀번호</label>
                                    <input type="password" class="form-control" id="auth_password" name="auth_password">
                                </div>
                            </div>
                            
                            <!-- Bearer 토큰 필드 -->
                            <div id="bearer-auth-fields" style="display: none;">
                                <div class="form-group">
                                    <label for="auth_token">Bearer 토큰</label>
                                    <input type="text" class="form-control" id="auth_token" name="auth_token">
                                </div>
                            </div>
                            
                            <!-- API 키 필드 (헤더로 처리) -->
                            <div id="api-key-fields" style="display: none;">
                                <div class="form-group">
                                    <label>API 키 헤더</label>
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="header_keys[]" placeholder="헤더 이름 (예: X-API-Key)">
                                        <input type="text" class="form-control" name="header_values[]" placeholder="헤더 값">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 헤더 필드 -->
                            <div class="form-group">
                                <label>추가 HTTP 헤더</label>
                                <div id="headers-container">
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="header_keys[]" placeholder="헤더 이름">
                                        <input type="text" class="form-control" name="header_values[]" placeholder="헤더 값">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-danger remove-header" disabled>
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary mt-2" id="add-header">
                                    <i class="fas fa-plus"></i> 헤더 추가
                                </button>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_test_mode" name="is_test_mode" checked>
                                    <label class="custom-control-label" for="is_test_mode">테스트 모드 활성화</label>
                                </div>
                                <small class="form-text text-muted">테스트 모드에서는 실제 데이터가 저장되지만 API로 전송되지는 않습니다.</small>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> API 생성 및 연결
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 인증 방식에 따른 필드 표시/숨김
    $('#auth_type').change(function() {
        var authType = $(this).val();
        
        // 모든 인증 필드 숨기기
        $('#basic-auth-fields, #bearer-auth-fields, #api-key-fields').hide();
        
        // 선택한 인증 방식에 따라 필드 표시
        if (authType === 'basic') {
            $('#basic-auth-fields').show();
        } else if (authType === 'bearer') {
            $('#bearer-auth-fields').show();
        } else if (authType === 'api_key') {
            $('#api-key-fields').show();
        }
    });
    
    // 헤더 추가 버튼 클릭 시
    $('#add-header').click(function() {
        var headerRow = `
            <div class="input-group mb-2">
                <input type="text" class="form-control" name="header_keys[]" placeholder="헤더 이름">
                <input type="text" class="form-control" name="header_values[]" placeholder="헤더 값">
                <div class="input-group-append">
                    <button type="button" class="btn btn-danger remove-header">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        
        $('#headers-container').append(headerRow);
    });
    
    // 헤더 삭제 버튼 클릭 시 (동적으로 생성된 요소에 대한 이벤트 바인딩)
    $(document).on('click', '.remove-header', function() {
        $(this).closest('.input-group').remove();
    });
});
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>