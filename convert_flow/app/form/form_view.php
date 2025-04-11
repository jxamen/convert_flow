<?php
/**
 * 폼 미리보기 및 사용자용 폼 제출 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 폼 ID
$form_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($form_id <= 0) {
    alert('잘못된 접근입니다.', CF_FORM_URL . '/form_list.php');
}

// 폼 모델 로드
require_once CF_MODEL_PATH . '/form.model.php';
$form_model = new FormModel();

// 폼 정보 조회
$form = $form_model->get_form($form_id);
if (!$form) {
    alert('존재하지 않는 폼입니다.', CF_FORM_URL . '/form_list.php');
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

// 폼 제출 처리
$success_message = '';
$form_errors = array();
$form_data = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_form'])) {
    // CSRF 토큰 검증
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== md5($form_id . session_id())) {
        alert('유효하지 않은 요청입니다.', CF_FORM_URL . '/form_view.php?id=' . $form_id);
    }
    
    // 폼 데이터 수집 및 유효성 검사
    $valid = true;
    $lead_data = array();
    
    foreach ($fields as $field) {
        $field_name = 'field_' . $field['id'];
        $field_value = isset($_POST[$field_name]) ? $_POST[$field_name] : '';
        
        // 체크박스는 배열로 제출됨
        if ($field['type'] === 'checkbox' && is_array($field_value)) {
            $field_value = implode(', ', $field_value);
        }
        
        // 필수 필드 검사
        if ($field['is_required'] && $field_value === '') {
            $form_errors[$field_name] = empty($field['error_message']) ? 
                $field['label'] . ' 필드는 필수입니다.' : $field['error_message'];
            $valid = false;
        }
        
        // 유효성 규칙 검사
        if ($field_value !== '' && !empty($field['validation_rule'])) {
            $pattern = $field['validation_rule'];
            if (!preg_match("/$pattern/", $field_value)) {
                $form_errors[$field_name] = empty($field['error_message']) ? 
                    $field['label'] . ' 필드가 올바른 형식이 아닙니다.' : $field['error_message'];
                $valid = false;
            }
        }
        
        // 데이터 저장
        $form_data[$field_name] = $field_value;
        $lead_data[$field['label']] = $field_value;
    }
    
    // 유효성 검사 통과 시 데이터 저장
    if ($valid) {
        // 리드 데이터 저장을 위한 추가 정보 수집
        $utm_source = isset($_GET['utm_source']) ? $_GET['utm_source'] : '';
        $utm_medium = isset($_GET['utm_medium']) ? $_GET['utm_medium'] : '';
        $utm_campaign = isset($_GET['utm_campaign']) ? $_GET['utm_campaign'] : '';
        $utm_content = isset($_GET['utm_content']) ? $_GET['utm_content'] : '';
        $utm_term = isset($_GET['utm_term']) ? $_GET['utm_term'] : '';
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        
        // 리드 데이터 구성
        $lead = array(
            'form_id' => $form_id,
            'landing_page_id' => $form['landing_page_id'],
            'campaign_id' => null, // 추후 연결 가능
            'data' => json_encode($lead_data),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'referrer' => $referrer,
            'utm_source' => $utm_source,
            'utm_medium' => $utm_medium,
            'utm_campaign' => $utm_campaign,
            'utm_content' => $utm_content,
            'utm_term' => $utm_term,
            'status' => '신규'
        );
        
        // 리드 저장
        $lead_id = $form_model->save_lead($lead);
        
        if ($lead_id) {
            // 제출 성공 처리
            if (!empty($form['redirect_url'])) {
                // 리다이렉트 URL이 설정된 경우
                goto_url($form['redirect_url']);
            } else {
                // 성공 메시지 표시
                $success_message = $form['success_message'];
                $form_data = array(); // 폼 초기화
            }
        } else {
            alert('데이터 저장 중 오류가 발생했습니다. 나중에 다시 시도해주세요.', CF_FORM_URL . '/form_view.php?id=' . $form_id);
        }
    }
}

// CSRF 토큰 생성
$csrf_token = md5($form_id . session_id());

// 간소화된 헤더
include_once CF_PATH . '/app/form/form_header.php';
?>

<div class="container mt-4 mb-4">

    <?php if (!empty($form['complete_image_path'])): ?>
        <img src="<?php echo $form['complete_image_path']; ?>" class="img-fluid" style="width: 100%;">
    <?php else: ?>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php else: ?>
    <?php endif; ?>

    
    
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0"><?php echo $form['name']; ?></h3>
        </div>
        <div class="card-body">
            <?php if ($form['is_multi_step']): ?>
            <!-- 다단계 폼 진행 바 -->
            <div class="progress mb-4">
                <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
            
            <!-- 단계 표시 -->
            <div class="step-indicator mb-3">
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
                        $field_value = isset($form_data[$field_name]) ? $form_data[$field_name] : $field['default_value'];
                        $error_class = isset($form_errors[$field_name]) ? 'is-invalid' : '';
                        $error_message = isset($form_errors[$field_name]) ? $form_errors[$field_name] : '';
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
                                <input type="<?php echo $field['type']; ?>" class="form-control <?php echo $error_class; ?>" 
                                       id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" 
                                       value="<?php echo htmlspecialchars($field_value); ?>" 
                                       placeholder="<?php echo htmlspecialchars($placeholder); ?>" <?php echo $required; ?>>
                            
                            <?php elseif ($field['type'] === 'textarea'): ?>
                                <textarea class="form-control <?php echo $error_class; ?>" 
                                          id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" 
                                          rows="3" placeholder="<?php echo htmlspecialchars($placeholder); ?>" 
                                          <?php echo $required; ?>><?php echo htmlspecialchars($field_value); ?></textarea>
                            
                            <?php elseif ($field['type'] === 'select'): ?>
                                <select class="form-control <?php echo $error_class; ?>" 
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
                                    <input class="form-check-input <?php echo $error_class; ?>" type="radio" 
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
                                    <input class="form-check-input <?php echo $error_class; ?>" type="checkbox" 
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
                            
                            <?php if (!empty($error_message)): ?>
                            <div class="invalid-feedback">
                                <?php echo $error_message; ?>
                            </div>
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
                            <?php if ($form['cta_type'] == 'button'): ?>
                                <?php
                                // 버튼 크기 클래스 결정
                                $btn_size_class = '';
                                switch ($form['cta_button_size']) {
                                    case 'small':
                                        $btn_size_class = 'btn-sm';
                                        break;
                                    case 'large':
                                        $btn_size_class = 'btn-lg';
                                        break;
                                    default:
                                        $btn_size_class = '';
                                }
                                
                                // 버튼 스타일 설정
                                $btn_style = sprintf(
                                    'background-color: %s; color: %s; border-color: %s; border-radius: %dpx;',
                                    $form['cta_button_bg_color'],
                                    $form['cta_button_text_color'],
                                    $form['cta_button_border_color'],
                                    $form['cta_button_radius']
                                );
                                ?>
                                <button type="submit" name="submit_form" class="btn <?php echo $btn_size_class; ?>" style="<?php echo $btn_style; ?>"><?php echo $form['submit_button_text']; ?></button>
                            <?php else: ?>
                                <button type="submit" name="submit_form" class="btn p-0 border-0">
                                    <img src="<?php echo $form['cta_image_path']; ?>" alt="<?php echo $form['submit_button_text']; ?>">
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- jQuery UI -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
<script>
$(document).ready(function() {
    <?php if ($form['is_multi_step']): ?>
    var currentStep = 1;
    var totalSteps = <?php echo $total_steps; ?>;
    
    // 프로그레스 바 업데이트
    function updateProgress() {
        var percent = ((currentStep - 1) / totalSteps) * 100;
        $('.progress-bar').css('width', percent + '%').attr('aria-valuenow', percent).text(percent + '%');
        $('.current-step').text('단계 ' + currentStep);
    }
    
    // 다음 단계로 이동
    $('.next-step').click(function() {
        // 현재 단계 유효성 검사
        var isValid = validateStep($('.form-step[data-step="' + currentStep + '"]'));
        
        if (isValid) {
            // 현재 단계 숨기고 다음 단계 표시
            $('.form-step[data-step="' + currentStep + '"]').hide();
            currentStep++;
            $('.form-step[data-step="' + currentStep + '"]').show();
            updateProgress();
            
            // 페이지 상단으로 스크롤
            $('html, body').animate({
                scrollTop: $('#formSubmit').offset().top - 50
            }, 500);
        }
    });
    
    // 이전 단계로 이동
    $('.prev-step').click(function() {
        // 현재 단계 숨기고 이전 단계 표시
        $('.form-step[data-step="' + currentStep + '"]').hide();
        currentStep--;
        $('.form-step[data-step="' + currentStep + '"]').show();
        updateProgress();
        
        // 페이지 상단으로 스크롤
        $('html, body').animate({
            scrollTop: $('#formSubmit').offset().top - 50
        }, 500);
    });
    
    // 단계별 유효성 검사
    function validateStep(step) {
        var isValid = true;
        
        // 필수 필드 확인
        step.find('input[required], select[required], textarea[required]').each(function() {
            if ($(this).val() === '') {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        // 라디오 버튼과 체크박스 확인
        var radioGroups = {};
        step.find('input[type="radio"][required]').each(function() {
            var name = $(this).attr('name');
            radioGroups[name] = true;
        });
        
        $.each(radioGroups, function(name, value) {
            if (!$('input[name="' + name + '"]:checked').length) {
                $('input[name="' + name + '"]').addClass('is-invalid');
                isValid = false;
            } else {
                $('input[name="' + name + '"]').removeClass('is-invalid');
            }
        });
        
        return isValid;
    }
    
    // 초기 프로그레스 바 설정
    updateProgress();
    <?php endif; ?>
    
    <?php if ($form['auto_save_enabled']): ?>
    // 자동 저장 기능
    $('#formSubmit input, #formSubmit textarea, #formSubmit select').change(function() {
        var formData = $('#formSubmit').serialize();
        localStorage.setItem('form_<?php echo $form_id; ?>_data', formData);
    });
    
    // 저장된 데이터 복원
    var savedData = localStorage.getItem('form_<?php echo $form_id; ?>_data');
    if (savedData) {
        var dataArray = savedData.split('&');
        for (var i = 0; i < dataArray.length; i++) {
            var pair = dataArray[i].split('=');
            var name = decodeURIComponent(pair[0]);
            var value = decodeURIComponent(pair[1].replace(/\+/g, ' '));
            
            var field = $('[name="' + name + '"]');
            
            if (field.is('input[type="radio"]')) {
                $('input[name="' + name + '"][value="' + value + '"]').prop('checked', true);
            } else if (field.is('input[type="checkbox"]')) {
                if (value === 'on') {
                    field.prop('checked', true);
                }
            } else {
                field.val(value);
            }
        }
    }
    
    // 폼 제출 시 로컬 스토리지 삭제
    $('#formSubmit').submit(function() {
        localStorage.removeItem('form_<?php echo $form_id; ?>_data');
    });
    <?php endif; ?>
});
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/app/form/form_footer.php';
?>