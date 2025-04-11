<?php
/**
 * 광고 플랫폼 필드 설정 관리 페이지 (관리자용)
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 관리자 권한 체크
if ($member['mb_level'] < 9) {
    alert('관리자만 접근할 수 있습니다.', CF_URL);
}

// 사용자 광고 계정 모델 로드
require_once CF_MODEL_PATH . '/ad_account.model.php';
$ad_account_model = new AdAccountModel();

// 액션 처리
$action = isset($_GET['action']) ? $_GET['action'] : '';
$platform_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($action === 'save' && $platform_id > 0 && isset($_POST['submit'])) {
    // 필드 설정 저장
    $fields = isset($_POST['fields']) ? $_POST['fields'] : array();
    
    // 필드 배열 정리
    $field_settings = array();
    for ($i = 0; $i < count($fields['id']); $i++) {
        if (empty($fields['id'][$i])) continue;
        
        $field_settings[] = array(
            'id' => $fields['id'][$i],
            'name' => $fields['name'][$i],
            'label' => $fields['label'][$i],
            'type' => $fields['type'][$i],
            'placeholder' => $fields['placeholder'][$i],
            'description' => $fields['description'][$i],
            'required' => isset($fields['required'][$i])
        );
    }
    
    $result = $ad_account_model->update_platform_field_settings($platform_id, $field_settings);
    if ($result) {
        alert('필드 설정이 저장되었습니다.', 'ad_platform_settings.php');
    } else {
        alert('필드 설정 저장에 실패했습니다.', 'ad_platform_settings.php?action=edit&id=' . $platform_id);
    }
} else if ($action === 'add_platform' && isset($_POST['submit_platform'])) {
    // 플랫폼 추가 처리
    $platform_name = isset($_POST['platform_name']) ? trim($_POST['platform_name']) : '';
    $platform_code = isset($_POST['platform_code']) ? trim($_POST['platform_code']) : '';
    $platform_api_endpoint = isset($_POST['api_endpoint']) ? trim($_POST['api_endpoint']) : '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($platform_name) || empty($platform_code)) {
        alert('플랫폼 이름과 코드는 필수 입력 항목입니다.', 'ad_platform_settings.php?action=add_platform');
    }
    
    // 플랫폼 추가
    $platform_data = array(
        'name' => $platform_name,
        'platform_code' => $platform_code,
        'api_endpoint' => $platform_api_endpoint,
        'is_active' => $is_active
    );
    
    $result = $ad_account_model->add_platform($platform_data);
    if ($result) {
        alert('새 광고 플랫폼이 추가되었습니다.', 'ad_platform_settings.php');
    } else {
        alert('광고 플랫폼 추가에 실패했습니다.', 'ad_platform_settings.php?action=add_platform');
    }
} else if ($action === 'edit_platform' && isset($_POST['submit_platform'])) {
    // 플랫폼 수정 처리
    $platform_name = isset($_POST['platform_name']) ? trim($_POST['platform_name']) : '';
    $platform_code = isset($_POST['platform_code']) ? trim($_POST['platform_code']) : '';
    $platform_api_endpoint = isset($_POST['api_endpoint']) ? trim($_POST['api_endpoint']) : '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($platform_name) || empty($platform_code)) {
        alert('플랫폼 이름과 코드는 필수 입력 항목입니다.', 'ad_platform_settings.php?action=edit_platform&id=' . $platform_id);
    }
    
    // 플랫폼 수정
    $platform_data = array(
        'name' => $platform_name,
        'platform_code' => $platform_code,
        'api_endpoint' => $platform_api_endpoint,
        'is_active' => $is_active
    );
    
    $result = $ad_account_model->update_platform($platform_id, $platform_data);
    if ($result) {
        alert('광고 플랫폼 정보가 수정되었습니다.', 'ad_platform_settings.php');
    } else {
        alert('광고 플랫폼 수정에 실패했습니다.', 'ad_platform_settings.php?action=edit_platform&id=' . $platform_id);
    }
} else if ($action === 'delete_platform' && $platform_id > 0) {
    // 플랫폼 삭제 처리
    $result = $ad_account_model->delete_platform($platform_id);
    if ($result) {
        alert('광고 플랫폼이 삭제되었습니다.', 'ad_platform_settings.php');
    } else {
        alert('광고 플랫폼 삭제에 실패했습니다. 이 플랫폼을 사용 중인 계정이 있는지 확인하세요.', 'ad_platform_settings.php');
    }
}

// 플랫폼 목록 조회
$platforms = $ad_account_model->get_platforms(false); // 비활성 플랫폼도 포함

// 페이지 제목
$page_title = "광고 플랫폼 설정 관리";

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">광고 플랫폼 설정 관리</h1>
    <p class="mb-4">
        각 광고 플랫폼별 설정을 관리합니다. 새 플랫폼을 추가하거나 기존 플랫폼의 필드 설정을 수정할 수 있습니다.
    </p>

    <?php if ($action === 'add_platform'): ?>
    <!-- 새 광고 플랫폼 추가 폼 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">새 광고 플랫폼 추가</h6>
            <a href="ad_platform_settings.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> 목록으로 돌아가기
            </a>
        </div>
        <div class="card-body">
            <form method="post" action="ad_platform_settings.php?action=add_platform">
                <div class="form-group">
                    <label for="platform_name">플랫폼 이름 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="platform_name" name="platform_name" placeholder="예: 네이버 광고" required>
                    <small class="form-text text-muted">사용자에게 표시되는 광고 플랫폼 이름입니다.</small>
                </div>
                
                <div class="form-group">
                    <label for="platform_code">플랫폼 코드 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="platform_code" name="platform_code" placeholder="예: naver" required>
                    <small class="form-text text-muted">시스템 내부에서 사용되는 고유 코드입니다. 영문 소문자와 언더스코어(_)만 사용하세요.</small>
                </div>
                
                <div class="form-group">
                    <label for="api_endpoint">API 엔드포인트</label>
                    <input type="text" class="form-control" id="api_endpoint" name="api_endpoint" placeholder="예: https://api.naver.com">
                    <small class="form-text text-muted">API 연동에 사용되는 기본 URL입니다.</small>
                </div>
                
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                        <label class="custom-control-label" for="is_active">활성화</label>
                        <small class="form-text text-muted">비활성화된 플랫폼은 사용자에게 표시되지 않습니다.</small>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="ad_platform_settings.php" class="btn btn-secondary">취소</a>
                    <button type="submit" name="submit_platform" class="btn btn-primary">
                        <i class="fas fa-save"></i> 플랫폼 추가
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php elseif ($action === 'edit_platform' && $platform_id > 0): ?>
    <!-- 광고 플랫폼 정보 수정 폼 -->
    <?php
    $platform = $ad_account_model->get_platform($platform_id);
    if (!$platform) {
        alert('존재하지 않는 플랫폼입니다.', 'ad_platform_settings.php');
    }
    ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">광고 플랫폼 정보 수정: <?php echo htmlspecialchars($platform['name']); ?></h6>
            <a href="ad_platform_settings.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> 목록으로 돌아가기
            </a>
        </div>
        <div class="card-body">
            <form method="post" action="ad_platform_settings.php?action=edit_platform&id=<?php echo $platform_id; ?>">
                <div class="form-group">
                    <label for="platform_name">플랫폼 이름 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="platform_name" name="platform_name" value="<?php echo htmlspecialchars($platform['name']); ?>" required>
                    <small class="form-text text-muted">사용자에게 표시되는 광고 플랫폼 이름입니다.</small>
                </div>
                
                <div class="form-group">
                    <label for="platform_code">플랫폼 코드 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="platform_code" name="platform_code" value="<?php echo htmlspecialchars($platform['platform_code']); ?>" required>
                    <small class="form-text text-muted">시스템 내부에서 사용되는 고유 코드입니다. 영문 소문자와 언더스코어(_)만 사용하세요.</small>
                </div>
                
                <div class="form-group">
                    <label for="api_endpoint">API 엔드포인트</label>
                    <input type="text" class="form-control" id="api_endpoint" name="api_endpoint" value="<?php echo htmlspecialchars($platform['api_endpoint']); ?>">
                    <small class="form-text text-muted">API 연동에 사용되는 기본 URL입니다.</small>
                </div>
                
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" <?php echo $platform['is_active'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="is_active">활성화</label>
                        <small class="form-text text-muted">비활성화된 플랫폼은 사용자에게 표시되지 않습니다.</small>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="ad_platform_settings.php" class="btn btn-secondary">취소</a>
                    <button type="submit" name="submit_platform" class="btn btn-primary">
                        <i class="fas fa-save"></i> 플랫폼 정보 수정
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php elseif ($action === 'edit' && $platform_id > 0): ?>
    <!-- 플랫폼 필드 설정 수정 폼 -->
    <?php
    $platform = $ad_account_model->get_platform($platform_id);
    if (!$platform) {
        alert('존재하지 않는 플랫폼입니다.', 'ad_platform_settings.php');
    }
    ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">플랫폼 필드 설정: <?php echo htmlspecialchars($platform['name']); ?></h6>
            <a href="ad_platform_settings.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> 목록으로 돌아가기
            </a>
        </div>
        <div class="card-body">
            <form method="post" action="ad_platform_settings.php?action=save&id=<?php echo $platform_id; ?>">
                <div class="form-group">
                    <label>플랫폼 코드</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($platform['platform_code']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>플랫폼 이름</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($platform['name']); ?>" readonly>
                </div>
                
                <div class="text-right mb-3">
                    <a href="ad_platform_settings.php?action=edit_platform&id=<?php echo $platform_id; ?>" class="btn btn-sm btn-info">
                        <i class="fas fa-edit"></i> 플랫폼 정보 수정
                    </a>
                </div>
                
                <hr class="my-4">
                <h5 class="mb-3">필드 설정</h5>
                
                <div id="fields-container">
                    <?php
                    $field_settings = isset($platform['field_settings_data']) ? $platform['field_settings_data'] : array();
                    $field_count = count($field_settings);
                    if ($field_count == 0) {
                        // 기본 필드 1개 추가
                        $field_settings[] = array(
                            'id' => 'account_id',
                            'name' => 'account_id',
                            'label' => '계정 ID',
                            'type' => 'text',
                            'placeholder' => '광고 플랫폼 계정 ID를 입력하세요',
                            'description' => '광고 플랫폼에서의 계정 ID입니다',
                            'required' => true
                        );
                        $field_count = 1;
                    }
                    
                    for ($i = 0; $i < $field_count; $i++):
                        $field = $field_settings[$i];
                    ?>
                    <div class="field-row border p-3 mb-3 rounded">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>필드 ID</label>
                                    <input type="text" class="form-control" name="fields[id][]" value="<?php echo htmlspecialchars($field['id']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>필드 이름</label>
                                    <input type="text" class="form-control" name="fields[name][]" value="<?php echo htmlspecialchars($field['name']); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>라벨</label>
                                    <input type="text" class="form-control" name="fields[label][]" value="<?php echo htmlspecialchars($field['label']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>필드 타입</label>
                                    <select class="form-control" name="fields[type][]" required>
                                        <option value="text" <?php echo $field['type'] === 'text' ? 'selected' : ''; ?>>텍스트 (text)</option>
                                        <option value="password" <?php echo $field['type'] === 'password' ? 'selected' : ''; ?>>비밀번호 (password)</option>
                                        <option value="textarea" <?php echo $field['type'] === 'textarea' ? 'selected' : ''; ?>>텍스트영역 (textarea)</option>
                                        <option value="number" <?php echo $field['type'] === 'number' ? 'selected' : ''; ?>>숫자 (number)</option>
                                        <option value="email" <?php echo $field['type'] === 'email' ? 'selected' : ''; ?>>이메일 (email)</option>
                                        <option value="url" <?php echo $field['type'] === 'url' ? 'selected' : ''; ?>>URL (url)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>플레이스홀더</label>
                            <input type="text" class="form-control" name="fields[placeholder][]" value="<?php echo htmlspecialchars($field['placeholder']); ?>">
                        </div>
                        <div class="form-group">
                            <label>설명</label>
                            <textarea class="form-control" name="fields[description][]" rows="2"><?php echo htmlspecialchars($field['description']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="required_<?php echo $i; ?>" name="fields[required][]" value="1" <?php echo isset($field['required']) && $field['required'] ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="required_<?php echo $i; ?>">필수 입력</label>
                            </div>
                        </div>
                        <?php if ($i > 0): ?>
                        <button type="button" class="btn btn-sm btn-danger remove-field">
                            <i class="fas fa-trash"></i> 이 필드 삭제
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
                
                <div class="text-center mb-4">
                    <button type="button" id="add-field" class="btn btn-info">
                        <i class="fas fa-plus"></i> 새 필드 추가
                    </button>
                </div>
                
                <div class="text-center mt-4">
                    <a href="ad_platform_settings.php" class="btn btn-secondary">취소</a>
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 설정 저장
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php else: ?>
    <!-- 플랫폼 목록 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">광고 플랫폼 목록</h6>
            <a href="ad_platform_settings.php?action=add_platform" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> 새 플랫폼 추가
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>이름</th>
                            <th>코드</th>
                            <th>API 엔드포인트</th>
                            <th>상태</th>
                            <th>필드 수</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($platforms) > 0): ?>
                            <?php foreach ($platforms as $platform): ?>
                            <tr>
                                <td><?php echo $platform['id']; ?></td>
                                <td><?php echo htmlspecialchars($platform['name']); ?></td>
                                <td><code><?php echo htmlspecialchars($platform['platform_code']); ?></code></td>
                                <td>
                                    <?php if (!empty($platform['api_endpoint'])): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($platform['api_endpoint']); ?></small>
                                    <?php else: ?>
                                    <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($platform['is_active']): ?>
                                    <span class="badge badge-success">활성</span>
                                    <?php else: ?>
                                    <span class="badge badge-secondary">비활성</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $field_count = isset($platform['field_settings_data']) ? count($platform['field_settings_data']) : 0;
                                    echo $field_count;
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="ad_platform_settings.php?action=edit&id=<?php echo $platform['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-sliders-h"></i> 필드 설정
                                        </a>
                                        <a href="ad_platform_settings.php?action=edit_platform&id=<?php echo $platform['id']; ?>" class="btn btn-info">
                                            <i class="fas fa-edit"></i> 정보 수정
                                        </a>
                                        <a href="ad_platform_settings.php?action=delete_platform&id=<?php echo $platform['id']; ?>" class="btn btn-danger" onclick="return confirm('정말 이 플랫폼을 삭제하시겠습니까?\n이 작업은 되돌릴 수 없으며, 연결된 모든 계정에 영향을 미칠 수 있습니다.');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <p class="text-muted mb-0">등록된 광고 플랫폼이 없습니다.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // 필드 추가 버튼 클릭
    $('#add-field').click(function() {
        var fieldCount = $('.field-row').length;
        var newFieldHtml = `
            <div class="field-row border p-3 mb-3 rounded">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>필드 ID</label>
                            <input type="text" class="form-control" name="fields[id][]" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>필드 이름</label>
                            <input type="text" class="form-control" name="fields[name][]" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>라벨</label>
                            <input type="text" class="form-control" name="fields[label][]" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>필드 타입</label>
                            <select class="form-control" name="fields[type][]" required>
                                <option value="text">텍스트 (text)</option>
                                <option value="password">비밀번호 (password)</option>
                                <option value="textarea">텍스트영역 (textarea)</option>
                                <option value="number">숫자 (number)</option>
                                <option value="email">이메일 (email)</option>
                                <option value="url">URL (url)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>플레이스홀더</label>
                    <input type="text" class="form-control" name="fields[placeholder][]">
                </div>
                <div class="form-group">
                    <label>설명</label>
                    <textarea class="form-control" name="fields[description][]" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="required_${fieldCount}" name="fields[required][]" value="1">
                        <label class="custom-control-label" for="required_${fieldCount}">필수 입력</label>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-danger remove-field">
                    <i class="fas fa-trash"></i> 이 필드 삭제
                </button>
            </div>
        `;
        
        $('#fields-container').append(newFieldHtml);
    });
    
    // 필드 삭제 버튼 클릭 (동적 요소에 이벤트 바인딩)
    $(document).on('click', '.remove-field', function() {
        $(this).closest('.field-row').remove();
    });
    
    // 플랫폼 코드 자동 생성 (이름 입력 시)
    $('#platform_name').on('input', function() {
        if ($('#platform_code').val() === '') {
            var platformName = $(this).val();
            var platformCode = platformName
                .toLowerCase()
                .replace(/[^a-z0-9가-힣]/g, '_')  // 영문자, 숫자, 한글이 아닌 모든 것을 언더스코어로 대체
                .replace(/[가-힣]/g, function(match) {  // 한글을 로마자로 대략적으로 변환
                    // 간단한 한글→영문 변환
                    var code = match.charCodeAt(0) - 44032;
                    return String.fromCharCode(97 + (code % 24));  // 자음에 따라 a-z 배정
                })
                .replace(/_+/g, '_')  // 여러 개의 언더스코어를 하나로 합침
                .replace(/^_|_$/g, ''); // 앞뒤 언더스코어 제거
            
            $('#platform_code').val(platformCode);
        }
    });
});
</script>
<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>