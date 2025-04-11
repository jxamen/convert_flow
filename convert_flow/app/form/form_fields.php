<?php
/**
 * 폼 필드 관리 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 폼 ID 체크
$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
if ($form_id <= 0) {
    alert('잘못된 접근입니다.', 'form_list.php');
}

// 신규 생성 여부 체크 (메시지 표시용)
$is_new = isset($_GET['new']) ? $_GET['new'] : '';

// 페이지 제목
$page_title = "폼 필드 관리";

// 모델 로드
require_once CF_MODEL_PATH . '/form.model.php';
$form_model = new FormModel();

// 폼 정보 조회
$form = $form_model->get_form($form_id);
if (!$form) {
    alert('존재하지 않는 폼입니다.', 'form_list.php');
}

// 권한 체크 (관리자가 아니면서 폼 소유자가 아닌 경우)
if (!$is_admin && $form['user_id'] != $member['id']) {
    alert('권한이 없습니다.', 'form_list.php');
}

// 필드 유형별 옵션 정보 (자바스크립트에서 사용)
$field_type_options = array(
    'text' => array('label' => '텍스트 입력', 'has_placeholder' => true, 'has_options' => false),
    'email' => array('label' => '이메일', 'has_placeholder' => true, 'has_options' => false),
    'tel' => array('label' => '전화번호', 'has_placeholder' => true, 'has_options' => false),
    'textarea' => array('label' => '텍스트 영역', 'has_placeholder' => true, 'has_options' => false),
    'select' => array('label' => '드롭다운 선택', 'has_placeholder' => true, 'has_options' => true),
    'checkbox' => array('label' => '체크박스', 'has_placeholder' => false, 'has_options' => true),
    'radio' => array('label' => '라디오 버튼', 'has_placeholder' => false, 'has_options' => true),
    'date' => array('label' => '날짜 선택', 'has_placeholder' => true, 'has_options' => false),
    'number' => array('label' => '숫자 입력', 'has_placeholder' => true, 'has_options' => false),
    'hidden' => array('label' => '숨김 필드', 'has_placeholder' => false, 'has_options' => false)
);

// 필드 목록 조회
$fields = $form_model->get_form_fields($form_id);

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">
        폼 필드 관리: <?php echo $form['name']; ?>
    </h1>
    <p class="mb-4">폼 필드를 추가, 편집하거나 순서를 변경하세요.</p>

    <?php if ($is_new): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> 폼이 성공적으로 생성되었습니다. 이제 필드를 추가해주세요.
    </div>
    <?php endif; ?>

    <!-- 필드 관리 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">필드 목록</h6>
            <div>
                <button type="button" class="btn btn-success btn-sm mr-2" id="saveOrder">
                    <i class="fas fa-save"></i> 순서 저장
                </button>
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addFieldModal">
                    <i class="fas fa-plus"></i> 새 필드 추가
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if ($form['is_multi_step']): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 이 폼은 다단계로 설정되어 있습니다. 각 필드의 단계를 설정할 수 있습니다.
            </div>
            <?php endif; ?>

            <?php if (empty($fields)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 등록된 필드가 없습니다. '새 필드 추가' 버튼을 클릭하여 필드를 추가하세요.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th style="width: 50px;">순서</th>
                        <th>레이블</th>
                        <th>유형</th>
                        <th>필수</th>
                        <?php if ($form['is_multi_step']): ?>
                        <th>단계</th>
                        <?php endif; ?>
                        <th style="width: 120px;">관리</th>
                    </tr>
                    </thead>
                    <tbody id="sortableFields">
                    <?php foreach ($fields as $field): ?>
                        <tr data-id="<?php echo $field['id']; ?>">
                            <td class="handle text-center">
                                <i class="fas fa-grip-vertical text-muted"></i>
                            </td>
                            <td><?php echo $field['label']; ?></td>
                            <td><?php echo $field_type_options[$field['type']]['label']; ?></td>
                            <td>
                                <?php echo $field['is_required'] ? '<span class="badge badge-success">필수</span>' : '<span class="badge badge-secondary">선택</span>'; ?>
                            </td>
                            <?php if ($form['is_multi_step']): ?>
                            <td>단계 <?php echo $field['step_number']; ?></td>
                            <?php endif; ?>
                            <td>
                                <button type="button" class="btn btn-sm btn-info editField" data-id="<?php echo $field['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger deleteField" data-id="<?php echo $field['id']; ?>" data-label="<?php echo $field['label']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <div class="mt-3 text-center">
                <a href="form_list.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 폼 목록으로
                </a>
                <a href="form_view.php?id=<?php echo $form_id; ?>" target="_blank" class="btn btn-info">
                    <i class="fas fa-eye"></i> 폼 미리보기
                </a>
                <a href="form_edit.php?id=<?php echo $form_id; ?>" class="btn btn-primary">
                    <i class="fas fa-cog"></i> 폼 설정
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 필드 추가 모달 -->
<div class="modal fade" id="addFieldModal" tabindex="-1" role="dialog" aria-labelledby="addFieldModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addFieldModalLabel">새 필드 추가</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addFieldForm">
                    <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
                    
                    <div class="form-group">
                        <label for="label">필드 레이블 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="label" name="label" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">필드 유형 <span class="text-danger">*</span></label>
                        <select class="form-control" id="type" name="type" required>
                            <?php foreach ($field_type_options as $type => $info): ?>
                            <option value="<?php echo $type; ?>"><?php echo $info['label']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" id="placeholderGroup">
                        <label for="placeholder">플레이스홀더</label>
                        <input type="text" class="form-control" id="placeholder" name="placeholder">
                        <small class="form-text text-muted">입력 필드에 표시될 안내 텍스트입니다.</small>
                        </div>
                    
                    <div class="form-group" id="defaultValueGroup">
                        <label for="default_value">기본값</label>
                        <input type="text" class="form-control" id="default_value" name="default_value">
                        <small class="form-text text-muted">필드의 초기 값입니다.</small>
                    </div>
                    
                    <div class="form-group" id="optionsGroup" style="display: none;">
                        <label for="options">옵션 목록 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="options" name="options" rows="4" placeholder="한 줄에 하나의 옵션을 입력하세요."></textarea>
                        <small class="form-text text-muted">선택형 필드의 옵션입니다. 한 줄에 하나의 옵션을 입력하세요.</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_required" name="is_required">
                            <label class="custom-control-label" for="is_required">필수 입력</label>
                        </div>
                        <small class="form-text text-muted">필드를 필수로 설정하면 사용자가 반드시 입력해야 합니다.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="validation_rule">유효성 검사 규칙</label>
                        <input type="text" class="form-control" id="validation_rule" name="validation_rule">
                        <small class="form-text text-muted">정규식 또는 특정 규칙을 입력할 수 있습니다. (예: ^\d{3}-\d{3,4}-\d{4}$ - 전화번호 형식)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="error_message">오류 메시지</label>
                        <input type="text" class="form-control" id="error_message" name="error_message">
                        <small class="form-text text-muted">유효성 검사 실패 시 표시할 메시지입니다.</small>
                    </div>
                    
                    <?php if ($form['is_multi_step']): ?>
                    <div class="form-group">
                        <label for="step_number">단계 번호</label>
                        <select class="form-control" id="step_number" name="step_number">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?>단계</option>
                            <?php endfor; ?>
                        </select>
                        <small class="form-text text-muted">필드가 표시될 단계를 선택하세요.</small>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="step_number" value="1">
                    <?php endif; ?>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="saveField">저장</button>
            </div>
        </div>
    </div>
</div>

<!-- 필드 편집 모달 -->
<div class="modal fade" id="editFieldModal" tabindex="-1" role="dialog" aria-labelledby="editFieldModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editFieldModalLabel">필드 편집</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editFieldForm">
                    <input type="hidden" name="field_id" id="edit_field_id">
                    
                    <div class="form-group">
                        <label for="edit_label">필드 레이블 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_label" name="label" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_type">필드 유형 <span class="text-danger">*</span></label>
                        <select class="form-control" id="edit_type" name="type" required>
                            <?php foreach ($field_type_options as $type => $info): ?>
                            <option value="<?php echo $type; ?>"><?php echo $info['label']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" id="editPlaceholderGroup">
                        <label for="edit_placeholder">플레이스홀더</label>
                        <input type="text" class="form-control" id="edit_placeholder" name="placeholder">
                        <small class="form-text text-muted">입력 필드에 표시될 안내 텍스트입니다.</small>
                    </div>
                    
                    <div class="form-group" id="editDefaultValueGroup">
                        <label for="edit_default_value">기본값</label>
                        <input type="text" class="form-control" id="edit_default_value" name="default_value">
                        <small class="form-text text-muted">필드의 초기 값입니다.</small>
                    </div>
                    
                    <div class="form-group" id="editOptionsGroup" style="display: none;">
                        <label for="edit_options">옵션 목록 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="edit_options" name="options" rows="4" placeholder="한 줄에 하나의 옵션을 입력하세요."></textarea>
                        <small class="form-text text-muted">선택형 필드의 옵션입니다. 한 줄에 하나의 옵션을 입력하세요.</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_is_required" name="is_required">
                            <label class="custom-control-label" for="edit_is_required">필수 입력</label>
                        </div>
                        <small class="form-text text-muted">필드를 필수로 설정하면 사용자가 반드시 입력해야 합니다.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_validation_rule">유효성 검사 규칙</label>
                        <input type="text" class="form-control" id="edit_validation_rule" name="validation_rule">
                        <small class="form-text text-muted">정규식 또는 특정 규칙을 입력할 수 있습니다. (예: ^\d{3}-\d{3,4}-\d{4}$ - 전화번호 형식)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_error_message">오류 메시지</label>
                        <input type="text" class="form-control" id="edit_error_message" name="error_message">
                        <small class="form-text text-muted">유효성 검사 실패 시 표시할 메시지입니다.</small>
                    </div>
                    
                    <?php if ($form['is_multi_step']): ?>
                    <div class="form-group">
                        <label for="edit_step_number">단계 번호</label>
                        <select class="form-control" id="edit_step_number" name="step_number">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?>단계</option>
                            <?php endfor; ?>
                        </select>
                        <small class="form-text text-muted">필드가 표시될 단계를 선택하세요.</small>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="step_number" value="1">
                    <?php endif; ?>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="updateField">업데이트</button>
            </div>
        </div>
    </div>
</div>

<!-- 필드 삭제 확인 모달 -->
<div class="modal fade" id="deleteFieldModal" tabindex="-1" role="dialog" aria-labelledby="deleteFieldModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteFieldModalLabel">필드 삭제 확인</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>정말로 <strong id="fieldNameToDelete"></strong> 필드를 삭제하시겠습니까?</p>
                <p class="text-danger">이 작업은 되돌릴 수 없으며, 해당 필드의 모든 데이터도 함께 삭제됩니다.</p>
                <input type="hidden" id="fieldIdToDelete">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteField">삭제</button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery UI -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">

<script>
$(document).ready(function() {
    // 필드 타입별 옵션 정보
    var fieldTypeOptions = <?php echo json_encode($field_type_options); ?>;
    
    // 필드 유형 변경 시 관련 항목 표시/숨김 처리
    function toggleFieldOptions(fieldType, prefix) {
        prefix = prefix || '';
        var placeholderGroupId = prefix + 'placeholderGroup';
        var optionsGroupId = prefix + 'optionsGroup';
        
        // 플레이스홀더 필드
        if (fieldTypeOptions[fieldType].has_placeholder) {
            $('#' + placeholderGroupId).show();
        } else {
            $('#' + placeholderGroupId).hide();
        }
        
        // 옵션 필드 (select, checkbox, radio)
        if (fieldTypeOptions[fieldType].has_options) {
            $('#' + optionsGroupId).show();
        } else {
            $('#' + optionsGroupId).hide();
        }
    }
    
    // 초기 필드 타입에 따라 옵션 설정
    toggleFieldOptions($('#type').val());
    toggleFieldOptions($('#edit_type').val(), 'edit');
    
    // 필드 타입 변경 시 이벤트
    $('#type').change(function() {
        toggleFieldOptions($(this).val());
    });
    
    $('#edit_type').change(function() {
        toggleFieldOptions($(this).val(), 'edit');
    });
    
    // 테이블 드래그 앤 드롭 순서 변경
    $('#sortableFields').sortable({
        handle: '.handle',
        axis: 'y',
        update: function() {
            // 순서 변경 시 저장 버튼 강조
            $('#saveOrder').addClass('btn-warning').removeClass('btn-success')
                .html('<i class="fas fa-exclamation-triangle"></i> 순서 저장 필요');
        }
    });
    
    // 순서 저장 버튼 클릭 시
    $('#saveOrder').click(function() {
        var orderData = [];
        
        // 현재 순서 데이터 수집
        $('#sortableFields tr').each(function(index) {
            orderData.push({
                id: $(this).data('id'),
                order: index + 1
            });
        });
        
        // AJAX로 순서 업데이트
        $.ajax({
            url: '<?php echo CF_FORM_URL; ?>/form_field_ajax.php',
            type: 'POST',
            data: {
                action: 'update_field_orders',
                form_id: <?php echo $form_id; ?>,
                field_orders: orderData
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // 성공 시 버튼 상태 복원
                    $('#saveOrder').removeClass('btn-warning').addClass('btn-success')
                        .html('<i class="fas fa-save"></i> 순서 저장');
                    
                    // 알림 표시
                    alert('필드 순서가 저장되었습니다.');
                } else {
                    alert('오류: ' + response.message);
                }
            },
            error: function() {
                alert('순서 저장 중 오류가 발생했습니다.');
            }
        });
    });
    
    // 새 필드 저장
    $('#saveField').click(function() {
        var formData = $('#addFieldForm').serialize();
        
        // AJAX로 필드 추가
        $.ajax({
            url: '<?php echo CF_FORM_URL; ?>/form_field_ajax.php',
            type: 'POST',
            data: formData + '&action=add_field',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // 성공 시 페이지 새로고침
                    location.reload();
                } else {
                    alert('오류: ' + response.message);
                }
            },
            error: function() {
                alert('필드 저장 중 오류가 발생했습니다.');
            }
        });
    });
    
    // 필드 편집 버튼 클릭 시
    $('.editField').click(function() {
        var fieldId = $(this).data('id');
        
        // AJAX로 필드 정보 가져오기
        $.ajax({
            url: '<?php echo CF_FORM_URL; ?>/form_field_ajax.php',
            type: 'POST',
            data: {
                action: 'get_field',
                field_id: fieldId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var field = response.field;
                    
                    // 폼에 데이터 채우기
                    $('#edit_field_id').val(field.id);
                    $('#edit_label').val(field.label);
                    $('#edit_type').val(field.type).trigger('change');
                    $('#edit_placeholder').val(field.placeholder);
                    $('#edit_default_value').val(field.default_value);
                    $('#edit_is_required').prop('checked', field.is_required == 1);
                    $('#edit_validation_rule').val(field.validation_rule);
                    $('#edit_error_message').val(field.error_message);
                    
                    if (field.options) {
                        var options = '';
                        try {
                            var optionsArray = JSON.parse(field.options);
                            options = optionsArray.join('\n');
                        } catch (e) {
                            options = field.options;
                        }
                        $('#edit_options').val(options);
                    } else {
                        $('#edit_options').val('');
                    }
                    
                    if ($('#edit_step_number').length) {
                        $('#edit_step_number').val(field.step_number);
                    }
                    
                    // 모달 표시
                    $('#editFieldModal').modal('show');
                } else {
                    alert('오류: ' + response.message);
                }
            },
            error: function() {
                alert('필드 정보를 가져오는 중 오류가 발생했습니다.');
            }
        });
    });
    
    // 필드 업데이트
    $('#updateField').click(function() {
        var formData = $('#editFieldForm').serialize();
        
        // AJAX로 필드 업데이트
        $.ajax({
            url: '<?php echo CF_FORM_URL; ?>/form_field_ajax.php',
            type: 'POST',
            data: formData + '&action=update_field',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // 성공 시 페이지 새로고침
                    location.reload();
                } else {
                    alert('오류: ' + response.message);
                }
            },
            error: function() {
                alert('필드 업데이트 중 오류가 발생했습니다.');
            }
        });
    });
    
    // 필드 삭제 버튼 클릭 시
    $('.deleteField').click(function() {
        var fieldId = $(this).data('id');
        var fieldLabel = $(this).data('label');
        
        // 삭제 확인 모달에 정보 설정
        $('#fieldIdToDelete').val(fieldId);
        $('#fieldNameToDelete').text(fieldLabel);
        $('#deleteFieldModal').modal('show');
    });
    
    // 필드 삭제 확인
    $('#confirmDeleteField').click(function() {
        var fieldId = $('#fieldIdToDelete').val();
        
        // AJAX로 필드 삭제
        $.ajax({
            url: '<?php echo CF_FORM_URL; ?>/form_field_ajax.php',
            type: 'POST',
            data: {
                action: 'delete_field',
                field_id: fieldId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // 성공 시 페이지 새로고침
                    location.reload();
                } else {
                    alert('오류: ' + response.message);
                }
            },
            error: function() {
                alert('필드 삭제 중 오류가 발생했습니다.');
            }
        });
    });
});
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>