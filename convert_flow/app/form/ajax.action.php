<?php
/**
 * 폼 AJAX 로드 처리
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 폼 ID
$form_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($form_id <= 0) {
    echo '잘못된 폼 ID입니다.';
    exit;
}

// 폼 모델 로드
require_once CF_MODEL_PATH . '/form.model.php';
$form_model = new FormModel();

// 폼 정보 조회
$form = $form_model->get_form($form_id);
if (!$form) {
    echo '존재하지 않는 폼입니다.';
    exit;
}

// 필드 목록 조회
$fields = $form_model->get_form_fields($form_id);

// 다단계 폼인 경우 단계별로 필드 그룹화
$step_fields = array();
if ($form['is_multi_step']) {
    foreach ($fields as $field) {
        $step = $field['step_number'];
        if (!isset($step_fields[$step])) {
            $step_fields[$step] = array();
        }
        $step_fields[$step][] = $field;
    }
    
    // 빈 단계가 없도록 순서 재정렬
    ksort($step_fields);
} else {
    $step_fields[1] = $fields;
}

// 총 단계 수
$total_steps = $form['is_multi_step'] ? count($step_fields) : 1;

// CSRF 토큰 생성
$csrf_token = md5($form_id . session_id());

// HTML 출력
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?php echo $form['name']; ?></h3>
    </div>
    <div class="card-body">
        <?php if ($form['is_multi_step']): ?>
        <!-- 다단계 폼 진행 바 -->
        <div class="progress">
            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
        </div>
        
        <!-- 단계 표시 -->
        <div class="step-indicator">
            <span class="current-step">단계 1</span> / <span class="total-steps"><?php echo $total_steps; ?></span>
        </div>
        <?php endif; ?>
        
        <form id="formSubmit" method="post" action="<?php echo CF_FORM_URL; ?>/form_view.php?id=<?php echo $form_id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <?php foreach ($step_fields as $step => $step_field_list): ?>
            <div class="form-step" data-step="<?php echo $step; ?>" <?php echo ($step > 1) ? 'style="display: none;"' : ''; ?>>
                <?php foreach ($step_field_list as $field): ?>
                    <?php
                    $field_name = 'field_' . $field['id'];
                    $field_value = $field['default_value'];
                    $required = $field['is_required'] ? 'required' : '';
                    $placeholder = $field['placeholder'];
                    
                    // 선택형 필드 옵션 파싱
                    $options = array();
                    if (in_array($field['type'], array('select', 'checkbox', 'radio')) && !empty($field['options'])) {
                        $options = json_decode($field['options'], true);
                        if (!is_array($options)) {
                            $options = array($field['options']);
                        }
                    }
                    ?>
                    
                    <div class="form-group">
                        <?php if ($field['type'] !== 'hidden'): ?>
                        <label for="<?php echo $field_name; ?>">
                            <?php echo $field['label']; ?>
                            <?php if ($field['is_required']): ?>
                            <span class="text-danger">*</span>
                            <?php endif; ?>
                        </label>
                        <?php endif; ?>
                        
                        <?php if ($field['type'] === 'text' || $field['type'] === 'email' || $field['type'] === 'tel' || $field['type'] === 'number' || $field['type'] === 'date'): ?>
                            <input type="<?php echo $field['type']; ?>" class="form-control" 
                                   id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" 
                                   value="<?php echo htmlspecialchars($field_value); ?>" 
                                   placeholder="<?php echo htmlspecialchars($placeholder); ?>" <?php echo $required; ?>>
                        
                        <?php elseif ($field['type'] === 'textarea'): ?>
                            <textarea class="form-control" 
                                      id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" 
                                      rows="3" placeholder="<?php echo htmlspecialchars($placeholder); ?>" 
                                      <?php echo $required; ?>><?php echo htmlspecialchars($field_value); ?></textarea>
                        
                        <?php elseif ($field['type'] === 'select'): ?>
                            <select class="form-control" 
                                    id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" 
                                    <?php echo $required; ?>>
                                <?php if (!empty($placeholder)): ?>
                                <option value="" <?php echo empty($field_value) ? 'selected' : ''; ?>><?php echo htmlspecialchars($placeholder); ?></option>
                                <?php endif; ?>
                                
                                <?php foreach ($options as $option): ?>
                                <option value="<?php echo htmlspecialchars($option); ?>" 
                                        <?php echo ($field_value === $option) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($option); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        
                        <?php elseif ($field['type'] === 'radio'): ?>
                            <?php foreach ($options as $option): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" 
                                       name="<?php echo $field_name; ?>" id="<?php echo $field_name . '_' . md5($option); ?>" 
                                       value="<?php echo htmlspecialchars($option); ?>" 
                                       <?php echo ($field_value === $option) ? 'checked' : ''; ?> 
                                       <?php echo $required; ?>>
                                <label class="form-check-label" for="<?php echo $field_name . '_' . md5($option); ?>">
                                    <?php echo htmlspecialchars($option); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        
                        <?php elseif ($field['type'] === 'checkbox'): ?>
                            <?php 
                            $checked_values = explode(', ', $field_value);
                            foreach ($options as $option): 
                            ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       name="<?php echo $field_name; ?>[]" id="<?php echo $field_name . '_' . md5($option); ?>" 
                                       value="<?php echo htmlspecialchars($option); ?>" 
                                       <?php echo in_array($option, $checked_values) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="<?php echo $field_name . '_' . md5($option); ?>">
                                    <?php echo htmlspecialchars($option); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        
                            <?php elseif ($field['type'] === 'hidden'): ?>
                            <input type="hidden" id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" 
                                   value="<?php echo htmlspecialchars($field_value); ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <div class="form-navigation text-center">
                    <?php if ($form['is_multi_step'] && $step > 1): ?>
                    <button type="button" class="btn btn-secondary prev-step">이전</button>
                    <?php endif; ?>
                    
                    <?php if ($form['is_multi_step'] && $step < $total_steps): ?>
                    <button type="button" class="btn btn-primary next-step">다음</button>
                    <?php else: ?>
                    <button type="submit" name="submit_form" class="btn btn-success"><?php echo $form['submit_button_text']; ?></button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </form>
    </div>
</div>

<script>
(function() {
    <?php if ($form['is_multi_step']): ?>
    var currentStep = 1;
    var totalSteps = <?php echo $total_steps; ?>;
    
    // 프로그레스 바 업데이트
    function updateProgress() {
        var percent = ((currentStep - 1) / totalSteps) * 100;
        document.querySelector('.progress-bar').style.width = percent + '%';
        document.querySelector('.progress-bar').setAttribute('aria-valuenow', percent);
        document.querySelector('.progress-bar').textContent = percent + '%';
        document.querySelector('.current-step').textContent = '단계 ' + currentStep;
    }
    
    // 다음 단계로 이동
    var nextButtons = document.querySelectorAll('.next-step');
    for (var i = 0; i < nextButtons.length; i++) {
        nextButtons[i].addEventListener('click', function() {
            // 현재 단계 유효성 검사
            var isValid = validateStep(document.querySelector('.form-step[data-step="' + currentStep + '"]'));
            
            if (isValid) {
                // 현재 단계 숨기고 다음 단계 표시
                document.querySelector('.form-step[data-step="' + currentStep + '"]').style.display = 'none';
                currentStep++;
                document.querySelector('.form-step[data-step="' + currentStep + '"]').style.display = 'block';
                updateProgress();
                
                // 페이지 상단으로 스크롤
                window.scrollTo({
                    top: document.getElementById('formSubmit').offsetTop - 50,
                    behavior: 'smooth'
                });
            }
        });
    }
    
    // 이전 단계로 이동
    var prevButtons = document.querySelectorAll('.prev-step');
    for (var i = 0; i < prevButtons.length; i++) {
        prevButtons[i].addEventListener('click', function() {
            // 현재 단계 숨기고 이전 단계 표시
            document.querySelector('.form-step[data-step="' + currentStep + '"]').style.display = 'none';
            currentStep--;
            document.querySelector('.form-step[data-step="' + currentStep + '"]').style.display = 'block';
            updateProgress();
            
            // 페이지 상단으로 스크롤
            window.scrollTo({
                top: document.getElementById('formSubmit').offsetTop - 50,
                behavior: 'smooth'
            });
        });
    }
    
    // 단계별 유효성 검사
    function validateStep(step) {
        var isValid = true;
        
        // 필수 필드 확인
        var requiredFields = step.querySelectorAll('input[required], select[required], textarea[required]');
        for (var i = 0; i < requiredFields.length; i++) {
            var field = requiredFields[i];
            if (field.value === '') {
                field.classList.add('is-invalid');
                isValid = false;
                
                // 유효성 오류 메시지 표시
                var errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                errorDiv.textContent = field.getAttribute('placeholder') || field.previousElementSibling.textContent + ' 필드는 필수입니다.';
                
                // 기존 오류 메시지가 있으면 제거
                var existingError = field.nextElementSibling;
                if (existingError && existingError.className === 'invalid-feedback') {
                    existingError.parentNode.removeChild(existingError);
                }
                
                field.parentNode.appendChild(errorDiv);
            } else {
                field.classList.remove('is-invalid');
                
                // 오류 메시지가 있으면 제거
                var existingError = field.nextElementSibling;
                if (existingError && existingError.className === 'invalid-feedback') {
                    existingError.parentNode.removeChild(existingError);
                }
            }
        }
        
        // 라디오 버튼 확인
        var radioGroups = {};
        var radioButtons = step.querySelectorAll('input[type="radio"][required]');
        for (var i = 0; i < radioButtons.length; i++) {
            var radio = radioButtons[i];
            var name = radio.getAttribute('name');
            radioGroups[name] = true;
        }
        
        for (var name in radioGroups) {
            var checkedRadio = step.querySelector('input[name="' + name + '"]:checked');
            if (!checkedRadio) {
                var radios = step.querySelectorAll('input[name="' + name + '"]');
                for (var i = 0; i < radios.length; i++) {
                    radios[i].classList.add('is-invalid');
                }
                
                // 유효성 오류 메시지 표시
                var radioContainer = radios[0].parentNode.parentNode;
                var errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback d-block';
                errorDiv.textContent = '옵션을 선택해주세요.';
                
                // 기존 오류 메시지가 있으면 제거
                var existingErrors = radioContainer.querySelectorAll('.invalid-feedback');
                for (var i = 0; i < existingErrors.length; i++) {
                    existingErrors[i].parentNode.removeChild(existingErrors[i]);
                }
                
                radioContainer.appendChild(errorDiv);
                isValid = false;
            } else {
                var radios = step.querySelectorAll('input[name="' + name + '"]');
                for (var i = 0; i < radios.length; i++) {
                    radios[i].classList.remove('is-invalid');
                }
                
                // 기존 오류 메시지 제거
                var radioContainer = radios[0].parentNode.parentNode;
                var existingErrors = radioContainer.querySelectorAll('.invalid-feedback');
                for (var i = 0; i < existingErrors.length; i++) {
                    existingErrors[i].parentNode.removeChild(existingErrors[i]);
                }
            }
        }
        
        return isValid;
    }
    
    // 초기 프로그레스 바 설정
    updateProgress();
    <?php endif; ?>
    
    <?php if ($form['auto_save_enabled']): ?>
    // 자동 저장 기능
    var formInputs = document.querySelectorAll('#formSubmit input, #formSubmit textarea, #formSubmit select');
    for (var i = 0; i < formInputs.length; i++) {
        formInputs[i].addEventListener('change', function() {
            var formData = new FormData(document.getElementById('formSubmit'));
            var formDataObj = {};
            
            for (var pair of formData.entries()) {
                formDataObj[pair[0]] = pair[1];
            }
            
            localStorage.setItem('form_<?php echo $form_id; ?>_data', JSON.stringify(formDataObj));
        });
    }
    
    // 저장된 데이터 복원
    var savedData = localStorage.getItem('form_<?php echo $form_id; ?>_data');
    if (savedData) {
        try {
            var dataObj = JSON.parse(savedData);
            
            for (var key in dataObj) {
                var field = document.querySelector('[name="' + key + '"]');
                
                if (field) {
                    if (field.type === 'radio') {
                        var radio = document.querySelector('[name="' + key + '"][value="' + dataObj[key] + '"]');
                        if (radio) {
                            radio.checked = true;
                        }
                    } else if (field.type === 'checkbox') {
                        field.checked = dataObj[key] === 'on';
                    } else {
                        field.value = dataObj[key];
                    }
                }
            }
        } catch (e) {
            console.error('저장된 폼 데이터 복원 오류:', e);
        }
    }
    
    // 폼 제출 시 로컬 스토리지 삭제
    document.getElementById('formSubmit').addEventListener('submit', function() {
        localStorage.removeItem('form_<?php echo $form_id; ?>_data');
    });
    <?php endif; ?>
})();
</script>