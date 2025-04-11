<?php
/**
 * 전역 광고 계정 관리 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 사용자 광고 계정 모델 로드
require_once CF_MODEL_PATH . '/ad_account.model.php';
$ad_account_model = new AdAccountModel();

// 액션 처리
$action = isset($_GET['action']) ? $_GET['action'] : '';
$account_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($action === 'add' && isset($_POST['submit'])) {
    // 계정 추가 처리
    $platform_id = isset($_POST['platform_id']) ? intval($_POST['platform_id']) : 0;
    $account_name = isset($_POST['account_name']) ? trim($_POST['account_name']) : '';
    
    // 선택된 플랫폼 정보 가져오기
    $platform = $ad_account_model->get_platform($platform_id);
    
    // 기본 계정 데이터
    $account_data = array(
        'user_id' => $member['id'],
        'platform_id' => $platform_id,
        'account_name' => $account_name,
        'is_default' => isset($_POST['is_default']) ? 1 : 0,
    );
    
    // 플랫폼 설정에 따른 필드 추가
    if ($platform && isset($platform['field_settings_data'])) {
        foreach ($platform['field_settings_data'] as $field) {
            if (isset($_POST[$field['name']])) {
                $account_data[$field['name']] = trim($_POST[$field['name']]);
            }
        }
    }
    
    // 필수값인 account_id가 없는 경우 다른 필드로 대체
    if (!isset($account_data['account_id']) && isset($account_data['customer_id'])) {
        $account_data['account_id'] = $account_data['customer_id'];
    }
    
    // 추가 설정 데이터
    if (isset($_POST['settings']) && is_array($_POST['settings'])) {
        $account_data['settings'] = $_POST['settings'];
    }
    
    $result = $ad_account_model->add_account($account_data);
    if ($result) {
        alert('광고 계정이 추가되었습니다.', 'ad_accounts.php');
    } else {
        alert('광고 계정 추가에 실패했습니다.', 'ad_accounts.php?action=add');
    }
} else if ($action === 'edit' && isset($_POST['submit'])) {
    // 계정 수정 처리
    $account = $ad_account_model->get_account($account_id);
    if (!$account || $account['user_id'] != $member['id']) {
        alert('존재하지 않는 계정이거나 권한이 없습니다.', 'ad_accounts.php');
    }
    
    $platform = $ad_account_model->get_platform($account['platform_id']);
    
    // 기본 계정 데이터
    $account_data = array(
        'account_name' => isset($_POST['account_name']) ? trim($_POST['account_name']) : '',
        'is_default' => isset($_POST['is_default']) ? 1 : 0,
    );
    
    // 플랫폼 설정에 따른 필드 추가
    if ($platform && isset($platform['field_settings_data'])) {
        foreach ($platform['field_settings_data'] as $field) {
            if (isset($_POST[$field['name']])) {
                $account_data[$field['name']] = trim($_POST[$field['name']]);
            }
        }
    }
    
    // 추가 설정 데이터
    if (isset($_POST['settings']) && is_array($_POST['settings'])) {
        $account_data['settings'] = $_POST['settings'];
    }
    
    $result = $ad_account_model->update_account($account_id, $account_data);
    if ($result) {
        alert('광고 계정이 수정되었습니다.', 'ad_accounts.php');
    } else {
        alert('광고 계정 수정에 실패했습니다.', 'ad_accounts.php?action=edit&id=' . $account_id);
    }
} else if ($action === 'delete') {
    // 계정 삭제 처리
    $result = $ad_account_model->delete_account($account_id, $member['id']);
    if ($result) {
        alert('광고 계정이 삭제되었습니다.', 'ad_accounts.php');
    } else {
        alert('광고 계정 삭제에 실패했습니다.', 'ad_accounts.php');
    }
}

// 플랫폼 목록 조회
$platforms = $ad_account_model->get_platforms();

// 사용자 광고 계정 목록 조회
$accounts = $ad_account_model->get_user_accounts($member['id']);

// 페이지 제목
$page_title = "광고 계정 관리";

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">광고 계정 관리</h1>
    <p class="mb-4">
        전환 추적을 위한 광고 플랫폼 계정을 관리합니다. 여기서 설정한 계정은 모든 캠페인에서 사용할 수 있습니다.
    </p>

    <?php if ($action === 'add'): ?>
    <!-- 계정 추가 폼 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">새 광고 계정 추가</h6>
        </div>
        <div class="card-body">
            <form method="post" action="ad_accounts.php?action=add">
                <div class="form-group">
                    <label for="platform_id">광고 플랫폼</label>
                    <select class="form-control" id="platform_id" name="platform_id" required>
                        <option value="">플랫폼 선택</option>
                        <?php foreach ($platforms as $platform): ?>
                        <option value="<?php echo $platform['id']; ?>" data-code="<?php echo $platform['platform_code']; ?>"><?php echo htmlspecialchars($platform['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="account_name">계정 이름</label>
                    <input type="text" class="form-control" id="account_name" name="account_name" placeholder="관리용 계정 이름 (예: 회사 구글 애즈)" required>
                </div>
                
                <!-- 플랫폼별 동적 필드 영역 -->
                <div id="dynamic_fields" class="mt-4">
                    <p class="text-center text-muted">플랫폼을 선택하면 필요한 입력 필드가 표시됩니다.</p>
                </div>
                
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="is_default" name="is_default">
                        <label class="custom-control-label" for="is_default">기본 계정으로 설정</label>
                    </div>
                    <small class="form-text text-muted">이 플랫폼의 기본 계정으로 설정하면 새 캠페인에 자동으로 적용됩니다.</small>
                </div>
                
                <div class="text-center mt-4">
                    <a href="ad_accounts.php" class="btn btn-secondary">취소</a>
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 계정 추가
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php elseif ($action === 'edit' && $account_id > 0): ?>
    <!-- 계정 수정 폼 -->
    <?php
    $account = $ad_account_model->get_account($account_id);
    if (!$account || $account['user_id'] != $member['id']) {
        alert('존재하지 않는 계정이거나 권한이 없습니다.', 'ad_accounts.php');
    }
    $platform = $ad_account_model->get_platform($account['platform_id']);
    
    // 계정 설정 데이터
    $settings_data = array();
    if (!empty($account['settings'])) {
        $settings_data = json_decode($account['settings'], true) ?: array();
    }
    ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">광고 계정 수정: <?php echo htmlspecialchars($account['account_name']); ?></h6>
        </div>
        <div class="card-body">
            <form method="post" action="ad_accounts.php?action=edit&id=<?php echo $account_id; ?>">
                <div class="form-group">
                    <label>광고 플랫폼</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($platform['name']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="account_name">계정 이름</label>
                    <input type="text" class="form-control" id="account_name" name="account_name" value="<?php echo htmlspecialchars($account['account_name']); ?>" required>
                </div>
                
                <!-- 플랫폼별 동적 필드 영역 -->
                <div id="dynamic_fields" class="mt-4">
                    <?php if (isset($platform['field_settings_data'])): ?>
                        <?php foreach ($platform['field_settings_data'] as $field): ?>
                            <div class="form-group">
                                <label for="<?php echo $field['id']; ?>"><?php echo $field['label']; ?><?php echo $field['required'] ? ' <span class="text-danger">*</span>' : ''; ?></label>
                                
                                <?php if ($field['type'] === 'textarea'): ?>
                                    <textarea class="form-control" id="<?php echo $field['id']; ?>" name="<?php echo $field['name']; ?>" placeholder="<?php echo $field['placeholder']; ?>" <?php echo $field['required'] ? 'required' : ''; ?>><?php echo isset($account[$field['name']]) ? htmlspecialchars($account[$field['name']]) : ''; ?></textarea>
                                <?php else: ?>
                                    <input type="<?php echo $field['type']; ?>" class="form-control" id="<?php echo $field['id']; ?>" name="<?php echo $field['name']; ?>" placeholder="<?php echo $field['placeholder']; ?>" value="<?php echo isset($account[$field['name']]) ? htmlspecialchars($account[$field['name']]) : ''; ?>" <?php echo $field['required'] ? 'required' : ''; ?>>
                                <?php endif; ?>
                                
                                <?php if (!empty($field['description'])): ?>
                                    <small class="form-text text-muted"><?php echo $field['description']; ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-muted">이 플랫폼에 대한 설정이 정의되지 않았습니다.</p>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="is_default" name="is_default" <?php echo $account['is_default'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="is_default">기본 계정으로 설정</label>
                    </div>
                    <small class="form-text text-muted">이 플랫폼의 기본 계정으로 설정하면 새 캠페인에 자동으로 적용됩니다.</small>
                </div>
                
                <div class="text-center mt-4">
                    <a href="ad_accounts.php" class="btn btn-secondary">취소</a>
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 계정 수정
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php else: ?>
    <!-- 계정 목록 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
           <h6 class="m-0 font-weight-bold text-primary">등록된 광고 계정</h6>
           <a href="ad_accounts.php?action=add" class="btn btn-sm btn-primary">
               <i class="fas fa-plus"></i> 새 계정 추가
           </a>
       </div>
       <div class="card-body">
           <?php if (isset($accounts['accounts']) && count($accounts['accounts']) > 0): ?>
           <div class="table-responsive">
               <table class="table table-bordered" width="100%" cellspacing="0">
                   <thead>
                       <tr>
                           <th>플랫폼</th>
                           <th>계정 이름</th>
                           <th>계정 ID</th>
                           <th>상태</th>
                           <th>기본 계정</th>
                           <th>관리</th>
                       </tr>
                   </thead>
                   <tbody>
                       <?php foreach ($accounts['accounts'] as $account): ?>
                       <tr>
                           <td>
                               <?php if (!empty($account['platform_icon'])): ?>
                               <img src="<?php echo $account['platform_icon']; ?>" alt="<?php echo htmlspecialchars($account['platform_name']); ?>" width="20" height="20" class="mr-1">
                               <?php endif; ?>
                               <?php echo htmlspecialchars($account['platform_name']); ?>
                           </td>
                           <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                           <td><?php echo htmlspecialchars($account['account_id']); ?></td>
                           <td>
                               <span class="badge <?php echo $account['status'] === '활성' ? 'badge-success' : ($account['status'] === '인증필요' ? 'badge-warning' : 'badge-secondary'); ?>">
                                   <?php echo $account['status']; ?>
                               </span>
                           </td>
                           <td>
                               <?php if ($account['is_default']): ?>
                               <span class="badge badge-primary">기본 계정</span>
                               <?php else: ?>
                               <span class="text-muted">-</span>
                               <?php endif; ?>
                           </td>
                           <td>
                               <a href="ad_accounts.php?action=edit&id=<?php echo $account['id']; ?>" class="btn btn-sm btn-primary">
                                   <i class="fas fa-edit"></i> 수정
                               </a>
                               <a href="ad_accounts.php?action=delete&id=<?php echo $account['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('정말 이 계정을 삭제하시겠습니까?');">
                                   <i class="fas fa-trash"></i> 삭제
                               </a>
                           </td>
                       </tr>
                       <?php endforeach; ?>
                   </tbody>
               </table>
           </div>
           <?php else: ?>
           <div class="text-center p-5">
               <p class="text-muted mb-0">
                   <i class="fas fa-info-circle"></i> 등록된 광고 계정이 없습니다. 새 계정을 추가해 주세요.
               </p>
           </div>
           <?php endif; ?>
       </div>
   </div>
   <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // 플랫폼 선택 시 동적 필드 생성
    $('#platform_id').change(function() {
        var platformId = $(this).val();
        
        if (!platformId) {
            $('#dynamic_fields').html('<p class="text-center text-muted">플랫폼을 선택하면 필요한 입력 필드가 표시됩니다.</p>');
            return;
        }
        
        // AJAX로 플랫폼 필드 정보 가져오기
        $.ajax({
            url: 'ad_account_ajax.php',
            type: 'POST',
            data: {
                action: 'get_platform_fields',
                platform_id: platformId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.fields) {
                    generateDynamicFields(response.fields);
                } else {
                    $('#dynamic_fields').html('<p class="text-center text-muted">이 플랫폼에 대한 설정이 정의되지 않았습니다.</p>');
                }
            },
            error: function() {
                $('#dynamic_fields').html('<p class="text-center text-danger">필드 정보를 가져오는 중 오류가 발생했습니다.</p>');
            }
        });
    });
    
    // 플랫폼별 동적 필드 생성 함수
    function generateDynamicFields(fields) {
        var $container = $('#dynamic_fields');
        $container.empty();
        
        if (fields && fields.length > 0) {
            for (var i = 0; i < fields.length; i++) {
                var field = fields[i];
                var fieldHtml = '';
                
                fieldHtml += '<div class="form-group">';
                fieldHtml += '<label for="' + field.id + '">' + field.label + (field.required ? ' <span class="text-danger">*</span>' : '') + '</label>';
                
                if (field.type === 'textarea') {
                    fieldHtml += '<textarea class="form-control" id="' + field.id + '" name="' + field.name + '" placeholder="' + field.placeholder + '"' + (field.required ? ' required' : '') + '></textarea>';
                } else {
                    fieldHtml += '<input type="' + field.type + '" class="form-control" id="' + field.id + '" name="' + field.name + '" placeholder="' + field.placeholder + '"' + (field.required ? ' required' : '') + '>';
                }
                
                if (field.description) {
                    fieldHtml += '<small class="form-text text-muted">' + field.description + '</small>';
                }
                
                fieldHtml += '</div>';
                
                $container.append(fieldHtml);
            }
        }
    }
});
</script>
<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>