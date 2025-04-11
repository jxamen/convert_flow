<?php
if (!defined('_CONVERT_FLOW_')) exit;


/**
 * 폼 블록 렌더링 함수
 * 
 * @param int $form_id 폼 ID
 * @param FormModel $form_model 폼 모델 인스턴스
 * @return string 렌더링된 HTML
 */
function render_form_block($form_id, $form_model) {
    // 폼 정보 조회
    $form = $form_model->get_form($form_id);
    if (!$form) {
        return '<div class="alert alert-danger">폼을 찾을 수 없습니다. (ID: ' . $form_id . ')</div>';
    }
    
    // 필드 목록 조회
    $fields = $form_model->get_form_fields($form_id);
    
    // 다단계 폼인 경우 단계별로 필드 그룹화
    $step_fields = array();
    if (!empty($form['is_multi_step'])) {
        foreach ($fields as $field) {
            $step = $field['step_number'];
            if (!isset($step_fields[$step])) {
                $step_fields[$step] = array();
            }
            $step_fields[$step][] = $field;
        }
        ksort($step_fields);
    } else {
        $step_fields[1] = $fields;
    }
    
    // 총 단계 수
    $total_steps = !empty($form['is_multi_step']) ? count($step_fields) : 1;
    
    // CSRF 토큰 생성
    $csrf_token = md5($form_id . session_id());
    
    // 폼 HTML 생성
    $html = '<div class="form-container">';
    
    // 성공 메시지가 있으면 표시 (실제 제출은 미리보기에서 불가)
    if (!empty($form['success_message'])) {
        $html .= '<div class="alert alert-success d-none" id="form-success-' . $form_id . '">';
        $html .= $form['success_message'];
        $html .= '</div>';
    }
    
    $html .= '<form id="form-' . $form_id . '" class="landing-form" method="post" action="">';
    $html .= '<input type="hidden" name="csrf_token" value="' . $csrf_token . '">';
    $html .= '<input type="hidden" name="form_id" value="' . $form_id . '">';
    
    if (!empty($form['is_multi_step'])) {
        // 다단계 폼 진행 바
        $html .= '<div class="progress mb-4">';
        $html .= '<div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>';
        $html .= '</div>';
        
        // 단계 표시
        $html .= '<div class="step-indicator mb-3">';
        $html .= '<span class="current-step">단계 1</span> / <span class="total-steps">' . $total_steps . '</span>';
        $html .= '</div>';
    }
    
    foreach ($step_fields as $step => $step_field_list) {
        $html .= '<div class="form-step" data-step="' . $step . '" ' . ($step > 1 ? 'style="display: none;"' : '') . '>';
        
        foreach ($step_field_list as $field) {
            $field_name = 'field_' . $field['id'];
            $field_value = isset($field['default_value']) ? $field['default_value'] : '';
            $required = !empty($field['is_required']) ? 'required' : '';
            $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';
            
            $html .= '<div class="form-group">';
            
            if ($field['type'] !== 'hidden') {
                $html .= '<label for="' . $field_name . '">';
                $html .= $field['label'];
                if (!empty($field['is_required'])) {
                    $html .= ' <span class="text-danger">*</span>';
                }
                $html .= '</label>';
            }
            
            // 필드 유형별 입력 컨트롤 생성
            switch ($field['type']) {
                case 'text':
                case 'email':
                case 'tel':
                case 'number':
                case 'date':
                    $html .= '<input type="' . $field['type'] . '" class="form-control" id="' . $field_name . '" name="' . $field_name . '" value="' . htmlspecialchars($field_value) . '" placeholder="' . htmlspecialchars($placeholder) . '" ' . $required . '>';
                    break;
                    
                case 'textarea':
                    $html .= '<textarea class="form-control" id="' . $field_name . '" name="' . $field_name . '" rows="3" placeholder="' . htmlspecialchars($placeholder) . '" ' . $required . '>' . htmlspecialchars($field_value) . '</textarea>';
                    break;
                    
                case 'select':
                    $html .= '<select class="form-control" id="' . $field_name . '" name="' . $field_name . '" ' . $required . '>';
                    
                    if (!empty($placeholder)) {
                        $html .= '<option value="" ' . (empty($field_value) ? 'selected' : '') . '>' . htmlspecialchars($placeholder) . '</option>';
                    }
                    
                    // 옵션 처리
                    $options = array();
                    if (!empty($field['options'])) {
                        $options = json_decode($field['options'], true);
                        if (!is_array($options)) {
                            $options = array($field['options']);
                        }
                        
                        foreach ($options as $option) {
                            $option_value = is_array($option) ? ($option['value'] ?? $option) : $option;
                            $option_label = is_array($option) ? ($option['label'] ?? $option) : $option;
                            
                            $html .= '<option value="' . htmlspecialchars($option_value) . '" ' . ($field_value === $option_value ? 'selected' : '') . '>' . htmlspecialchars($option_label) . '</option>';
                        }
                    }
                    
                    $html .= '</select>';
                    break;
                    
                case 'radio':
                    $options = array();
                    if (!empty($field['options'])) {
                        $options = json_decode($field['options'], true);
                        if (!is_array($options)) {
                            $options = array($field['options']);
                        }
                        
                        foreach ($options as $idx => $option) {
                            $option_value = is_array($option) ? ($option['value'] ?? $option) : $option;
                            $option_label = is_array($option) ? ($option['label'] ?? $option) : $option;
                            
                            $html .= '<div class="form-check">';
                            $html .= '<input class="form-check-input" type="radio" name="' . $field_name . '" id="' . $field_name . '_' . $idx . '" value="' . htmlspecialchars($option_value) . '" ' . ($field_value === $option_value ? 'checked' : '') . ' ' . $required . '>';
                            $html .= '<label class="form-check-label" for="' . $field_name . '_' . $idx . '">' . htmlspecialchars($option_label) . '</label>';
                            $html .= '</div>';
                        }
                    }
                    break;
                    
                case 'checkbox':
                    $options = array();
                    if (!empty($field['options'])) {
                        $options = json_decode($field['options'], true);
                        if (!is_array($options)) {
                            $options = array($field['options']);
                        }
                        
                        $checked_values = explode(', ', $field_value);
                        
                        foreach ($options as $idx => $option) {
                            $option_value = is_array($option) ? ($option['value'] ?? $option) : $option;
                            $option_label = is_array($option) ? ($option['label'] ?? $option) : $option;
                            
                            $html .= '<div class="form-check">';
                            $html .= '<input class="form-check-input" type="checkbox" name="' . $field_name . '[]" id="' . $field_name . '_' . $idx . '" value="' . htmlspecialchars($option_value) . '" ' . (in_array($option_value, $checked_values) ? 'checked' : '') . '>';
                            $html .= '<label class="form-check-label" for="' . $field_name . '_' . $idx . '">' . htmlspecialchars($option_label) . '</label>';
                            $html .= '</div>';
                        }
                    } else {
                        // 단일 체크박스
                        $html .= '<div class="form-check">';
                        $html .= '<input class="form-check-input" type="checkbox" name="' . $field_name . '" id="' . $field_name . '" value="1" ' . ($field_value ? 'checked' : '') . ' ' . $required . '>';
                        $html .= '<label class="form-check-label" for="' . $field_name . '">' . $field['label'] . '</label>';
                        $html .= '</div>';
                    }
                    break;
                    
                case 'hidden':
                    $html .= '<input type="hidden" id="' . $field_name . '" name="' . $field_name . '" value="' . htmlspecialchars($field_value) . '">';
                    break;
            }
            
            $html .= '<div class="invalid-feedback">' . (!empty($field['error_message']) ? $field['error_message'] : '이 필드는 필수입니다.') . '</div>';
            $html .= '</div>'; // .form-group
        }
        
        // 폼 네비게이션 버튼
        $html .= '<div class="form-navigation text-center">';
        
        if (!empty($form['is_multi_step']) && $step > 1) {
            $html .= '<button type="button" class="btn btn-secondary prev-step w-100">이전</button>';
        }
        
        if (!empty($form['is_multi_step']) && $step < $total_steps) {
            $html .= '<button type="button" class="btn btn-primary next-step w-100">다음</button>';
        } else {
            // 버튼 스타일 설정
            $btn_style = '';
            $btn_class = 'btn btn-primary';
            
            if (isset($form['cta_type']) && $form['cta_type'] == 'button' && !empty($form['cta_button_bg_color'])) {
                $btn_style = 'background-color: ' . $form['cta_button_bg_color'] . '; ';
                $btn_style .= 'color: ' . ($form['cta_button_text_color'] ?? '#ffffff') . '; ';
                $btn_style .= 'border-color: ' . ($form['cta_button_border_color'] ?? $form['cta_button_bg_color']) . '; ';
                
                if (!empty($form['cta_button_radius'])) {
                    $btn_style .= 'border-radius: ' . $form['cta_button_radius'] . 'px; ';
                }
                
                // 버튼 크기 클래스
                if (!empty($form['cta_button_size'])) {
                    switch ($form['cta_button_size']) {
                        case 'small':
                            $btn_class .= ' btn-sm';
                            break;
                        case 'large':
                            $btn_class .= ' btn-lg';
                            break;
                    }
                }
            }
            
            if (isset($form['cta_type']) && $form['cta_type'] == 'image' && !empty($form['cta_image_path'])) {
                $html .= '<button type="submit" name="submit_form" class="btn p-0 border-0">';
                $html .= '<img src="' . $form['cta_image_path'] . '" alt="' . ($form['submit_button_text'] ?? '제출하기') . '">';
                $html .= '</button>';
            } else {
                $html .= '<button type="submit" name="submit_form" class="w-100 ' . $btn_class . '" style="' . $btn_style . '">';
                $html .= $form['submit_button_text'] ?? '제출하기';
                $html .= '</button>';
            }
        }
        
        $html .= '</div>'; // .form-navigation
        $html .= '</div>'; // .form-step
    }
    
    $html .= '</form>';
    $html .= '</div>'; // .form-container
    
    // 폼 관련 JavaScript 추가
    $html .= '<script>
    $(document).ready(function() {';
    
    if (!empty($form['is_multi_step'])) {
        // 다단계 폼 JavaScript
        $html .= '
        var currentStep = 1;
        var totalSteps = ' . $total_steps . ';
        
        // 프로그레스 바 업데이트
        function updateProgress() {
            var percent = Math.floor(((currentStep - 1) / totalSteps) * 100);
            $("#form-' . $form_id . ' .progress-bar").css("width", percent + "%").attr("aria-valuenow", percent).text(percent + "%");
            $("#form-' . $form_id . ' .current-step").text("단계 " + currentStep);
        }
        
        // 다음 단계로 이동
        $("#form-' . $form_id . ' .next-step").click(function() {
            // 현재 단계 유효성 검사
            var isValid = validateStep($("#form-' . $form_id . ' .form-step[data-step=\"" + currentStep + "\"]"));
            
            if (isValid) {
                // 현재 단계 숨기고 다음 단계 표시
                $("#form-' . $form_id . ' .form-step[data-step=\"" + currentStep + "\"]").hide();
                currentStep++;
                $("#form-' . $form_id . ' .form-step[data-step=\"" + currentStep + "\"]").show();
                updateProgress();
                
                // 페이지 상단으로 스크롤
                $("html, body").animate({
                    scrollTop: $("#form-' . $form_id . '").offset().top - 50
                }, 500);
            }
        });
        
        // 이전 단계로 이동
        $("#form-' . $form_id . ' .prev-step").click(function() {
            // 현재 단계 숨기고 이전 단계 표시
            $("#form-' . $form_id . ' .form-step[data-step=\"" + currentStep + "\"]").hide();
            currentStep--;
            $("#form-' . $form_id . ' .form-step[data-step=\"" + currentStep + "\"]").show();
            updateProgress();
            
            // 페이지 상단으로 스크롤
            $("html, body").animate({
                scrollTop: $("#form-' . $form_id . '").offset().top - 50
            }, 500);
        });
        
        // 단계별 유효성 검사
        function validateStep(step) {
            var isValid = true;
            
            // 필수 필드 확인
            step.find("input[required], select[required], textarea[required]").each(function() {
                if ($(this).val() === "") {
                    $(this).addClass("is-invalid");
                    isValid = false;
                } else {
                    $(this).removeClass("is-invalid");
                }
            });
            
            // 라디오 버튼과 체크박스 확인
            var radioGroups = {};
            step.find("input[type=\"radio\"][required]").each(function() {
                var name = $(this).attr("name");
                radioGroups[name] = true;
            });
            
            $.each(radioGroups, function(name, value) {
                if (!$("input[name=\"" + name + "\"]:checked").length) {
                    $("input[name=\"" + name + "\"]").addClass("is-invalid");
                    isValid = false;
                } else {
                    $("input[name=\"" + name + "\"]").removeClass("is-invalid");
                }
            });
            
            return isValid;
        }
        
        // 초기 프로그레스 바 설정
        updateProgress();';
    }
    
    if (!empty($form['auto_save_enabled'])) {
        // 자동 저장 기능
        $html .= '
        // 자동 저장 기능
        $("#form-' . $form_id . ' input, #form-' . $form_id . ' textarea, #form-' . $form_id . ' select").change(function() {
            var formData = $("#form-' . $form_id . '").serialize();
            localStorage.setItem("form_' . $form_id . '_data", formData);
        });
        
        // 저장된 데이터 복원
        var savedData = localStorage.getItem("form_' . $form_id . '_data");
        if (savedData) {
            var dataArray = savedData.split("&");
            for (var i = 0; i < dataArray.length; i++) {
                var pair = dataArray[i].split("=");
                var name = decodeURIComponent(pair[0]);
                var value = decodeURIComponent(pair[1].replace(/\\+/g, " "));
                
                var field = $("#form-' . $form_id . ' [name=\"" + name + "\"]");
                
                if (field.is("input[type=\"radio\"]")) {
                    $("#form-' . $form_id . ' input[name=\"" + name + "\"][value=\"" + value + "\"]").prop("checked", true);
                } else if (field.is("input[type=\"checkbox\"]")) {
                    if (value === "on") {
                        field.prop("checked", true);
                    }
                } else {
                    field.val(value);
                }
            }
        }
        
        // 폼 제출 시 로컬 스토리지 삭제
        $("#form-' . $form_id . '").submit(function() {
            localStorage.removeItem("form_' . $form_id . '_data");
        });';
    }
    
    // 미리보기 모드에서 폼 제출 처리
    $html .= '
        // 폼 제출 처리 (미리보기에서는 성공 메시지만 표시)
        $("#form-' . $form_id . '").submit(function(e) {
            e.preventDefault();
            
            // 유효성 검사
            var isValid = true;
            $(this).find("input[required], select[required], textarea[required]").each(function() {
                if ($(this).val() === "") {
                    $(this).addClass("is-invalid");
                    isValid = false;
                } else {
                    $(this).removeClass("is-invalid");
                }
            });
            
            if (!isValid) {
                return false;
            }
            
            // 미리보기 모드에서는 성공 메시지만 표시
            if (window.location.href.indexOf("preview") > -1) {
                $("#form-success-' . $form_id . '").removeClass("d-none");
                $(this).hide();
                alert("미리보기 모드에서는 실제 데이터가 제출되지 않습니다.");
                return false;
            }
            
            // 실제 제출 처리는 여기서 구현
            // 미리보기가 아닌 경우, 이 부분에서 AJAX를 통한 폼 제출 로직이 들어갈 수 있음
        });
        
        // 입력 필드 변경 시 유효성 검사
        $("#form-' . $form_id . ' input, #form-' . $form_id . ' select, #form-' . $form_id . ' textarea").on("change keyup", function() {
            if ($(this).prop("required") && $(this).val() !== "") {
                $(this).removeClass("is-invalid");
            }
        });
    });
    </script>';
    
    return $html;
}


// 랜딩페이지 블록 렌더링 함수에 여백 적용 추가
function render_block_with_margins($settings, $block_html) {
    // 기존 블록 템플릿 HTML 가져오기
    
    // 여백 스타일 생성
    $style_attr = '';
    $margin_top = isset($settings['margin_top']) ? intval($settings['margin_top']) : 0;
    $margin_right = isset($settings['margin_right']) ? intval($settings['margin_right']) : 0;
    $margin_bottom = isset($settings['margin_bottom']) ? intval($settings['margin_bottom']) : 0;
    $margin_left = isset($settings['margin_left']) ? intval($settings['margin_left']) : 0;
    
    if ($margin_top > 0 || $margin_right > 0 || $margin_bottom > 0 || $margin_left > 0) {
        $style_attr .= "margin-top:{$margin_top}px;";
        $style_attr .= "margin-right:{$margin_right}px;";
        $style_attr .= "margin-bottom:{$margin_bottom}px;";
        $style_attr .= "margin-left:{$margin_left}px;";
    }
    
    // 여백 스타일 적용을 위한 wrapper div 추가
    if (!empty($style_attr)) {
        $block_html = '<div style="' . $style_attr . '">' . $block_html . '</div>';
    }
    
    // 그 외 기존 블록 렌더링 로직 계속 진행...
    
    return $block_html;
}



/**
 * 관리자 CSRF 토큰 필드 생성
 * 관리자 페이지 폼에서 CSRF 공격 방지를 위한 토큰 필드 HTML 생성
 * 
 * @return string 토큰 필드 HTML
 */
function get_admin_token_fields() {
    global $member;
    
    $token = '';
    $admin_token = '';
    
    // 현재 시간 확인
    $current_time = time();

    // 토큰이 만료되었거나 없는 경우 새로 생성
    if (!isset($_SESSION['admin_token']) || 
        !isset($_SESSION['admin_token_time']) || 
        $_SESSION['admin_token_time'] < $current_time) {
        
        $admin_token = bin2hex(random_bytes(16)); // PHP 7.0 이상
        $_SESSION['admin_token'] = $admin_token;
        
        // 토큰 만료 시간 설정 (1시간)
        $_SESSION['admin_token_time'] = $current_time + 3600;
    } else {
        $admin_token = $_SESSION['admin_token'];
        
        // 만료 시간이 30분 이하로 남았으면 연장
        if ($_SESSION['admin_token_time'] - $current_time < 1800) {
            $_SESSION['admin_token_time'] = $current_time + 3600;
        }
    }
    
    // 토큰 생성 - USER ID + 시간 기반 해시
    $token = md5($member['id'] . $admin_token . $_SERVER['REMOTE_ADDR']);
    
    $fields = '';
    $fields .= '<input type="hidden" name="token" value="' . $token . '">';
    $fields .= '<input type="hidden" name="admin_time" value="' . $_SESSION['admin_token_time'] . '">';
    
    return $fields;
}

function check_admin_token_fields($token, $admin_time) {
    // 이미 시스템에 존재하는 함수의 유무 확인
    if (function_exists('check_admin_token')) {
        return check_admin_token($token, $admin_time);
    }
    
    // 함수가 없는 경우 간단히 구현
    // 실제로는 시스템에 맞는 전용 함수를 사용하는 것이 좋지만,
    // 이 코드는 예시용입니다.
    $max_time = 3600; // 1시간
    if (!$token || !$admin_time || $admin_time < time() - $max_time) {
        return false;
    }
    return true;
}


/**
 * 관리자 CSRF 토큰 값 생성
 * AJAX 요청 등에서 사용할 토큰 값 배열 생성
 * 
 * @return array 토큰 변수명과 값의 배열
 */
function get_admin_token_values() {
    global $member;
    
    $token = '';
    $admin_token = '';
    
    // 토큰 생성 (세션에 저장된 값이 있으면 그대로 사용, 없으면 새로 생성)
    if (isset($_SESSION['admin_token'])) {
        $admin_token = $_SESSION['admin_token'];
    } else {
        $admin_token = bin2hex(random_bytes(16)); // PHP 7.0 이상
        $_SESSION['admin_token'] = $admin_token;
    }
    
    // 토큰 만료 시간 설정 (1시간)
    if (!isset($_SESSION['admin_token_time'])) {
        $_SESSION['admin_token_time'] = time() + 3600;
    }
    
    // 토큰 생성 - USER ID + 시간 기반 해시
    $token = md5($member['id'] . $admin_token . $_SERVER['REMOTE_ADDR']);
    
    return array(
        'token' => $token,
        'admin_time' => $_SESSION['admin_token_time']
    );
}

/**
 * 관리자 CSRF 토큰 검증
 * 폼 전송된 토큰 값 검증
 * 
 * @param bool $return_bool 결과를 불리언으로 반환할지 여부
 * @return bool 검증 결과
 */
function check_admin_token($return_bool = false) {
    global $member;
    
    $token = isset($_POST['token']) ? $_POST['token'] : '';
    $admin_time = isset($_POST['admin_time']) ? intval($_POST['admin_time']) : 0;
    
    if (empty($token) || empty($admin_time)) {
        if ($return_bool) {
            return false;
        } else {
            die('올바른 방법으로 이용해 주세요.');
        }
    }

    // 토큰 만료 시간 확인
    $max_time = 3600; // 1시간
    if ($admin_time < time() -  $max_time) {
        if ($return_bool) {
            return false;
        } else {
            die('토큰이 만료되었습니다. 페이지를 새로고침한 후 다시 시도해 주세요.');
        }
    }
    
    // 토큰 검증
    $admin_token = isset($_SESSION['admin_token']) ? $_SESSION['admin_token'] : '';
    $expected_token = md5($member['id'] . $admin_token . $_SERVER['REMOTE_ADDR']);
    
    if ($token !== $expected_token) {
        if ($return_bool) {
            return false;
        } else {
            die('올바른 방법으로 이용해 주세요.');
        }
    }
    
    return true;
}

function get_token_fields() {
    global $member;
    
    $token = '';
    $ss_token = '';
    
    // 현재 시간 확인
    $current_time = time();

    // 토큰이 만료되었거나 없는 경우 새로 생성
    if (!isset($_SESSION['ss_token']) || 
        !isset($_SESSION['ss_token_time']) || 
        $_SESSION['ss_token_time'] < $current_time) {
        
        $ss_token = bin2hex(random_bytes(16)); // PHP 7.0 이상
        $_SESSION['ss_token'] = $ss_token;
        
        // 토큰 만료 시간 설정 (1시간)
        $_SESSION['ss_token_time'] = $current_time + 3600;
    } else {
        $ss_token = $_SESSION['ss_token'];
        
        // 만료 시간이 30분 이하로 남았으면 연장
        if ($_SESSION['ss_token_time'] - $current_time < 1800) {
            $_SESSION['ss_token_time'] = $current_time + 3600;
        }
    }
    
    // 토큰 생성 - USER ID + 시간 기반 해시
    $token = md5($member['id'] . $ss_token . $_SERVER['REMOTE_ADDR']);
    
    $fields = '';
    $fields .= '<input type="hidden" name="token" value="' . $token . '">';
    $fields .= '<input type="hidden" name="ss_token_time" value="' . $_SESSION['ss_token_time'] . '">';
    
    return $fields;
}


function check_token_field($return_bool = false) {
    global $member;
    
    $token = isset($_POST['token']) ? $_POST['token'] : '';
    $ss_token_time = isset($_POST['ss_token_time']) ? intval($_POST['ss_token_time']) : 0;
    
    if (empty($token) || empty($ss_token_time)) {
        if ($return_bool) {
            return false;
        } else {
            die('올바른 방법으로 이용해 주세요.');
        }
    }

    // 토큰 만료 시간 확인
    $max_time = 3600; // 1시간
    if ($ss_token_time < time() -  $max_time) {
        if ($return_bool) {
            return false;
        } else {
            die('토큰이 만료되었습니다. 페이지를 새로고침한 후 다시 시도해 주세요.');
        }
    }
    
    // 토큰 검증
    $token = isset($_SESSION['ss_token']) ? $_SESSION['ss_token'] : '';
    $expected_token = md5($member['id'] . $ss_token . $_SERVER['REMOTE_ADDR']);
    
    if ($token !== $expected_token) {
        if ($return_bool) {
            return false;
        } else {
            die('올바른 방법으로 이용해 주세요.');
        }
    }
    
    return true;
}


/**
 * 데이터베이스 테이블에 데이터를 삽입하는 함수
 * 
 * @param string $table 데이터를 삽입할 테이블 이름
 * @param array $data 삽입할 데이터 (필드명 => 값 형태의 연관 배열)
 * @return int|bool 성공 시 삽입된 행의 ID, 실패 시 false
 */
function sql_insert($table, $data) {
    global $conn; // 데이터베이스 연결 객체
    
    // 필드명과 값 배열 준비
    $fields = array_keys($data);
    $values = array_values($data);
    
    // SQL 쿼리 생성
    $field_str = implode(', ', $fields);
    
    // 값들을 SQL에 안전하게 포맷팅
    $value_placeholders = array();
    foreach ($values as $value) {
        $value_placeholders[] = "'" . sql_escape_string($value) . "'";
    }
    $value_str = implode(', ', $value_placeholders);
    
    // 최종 SQL 쿼리
    $sql = "INSERT INTO $table ($field_str) VALUES ($value_str)";
    
    // 쿼리 실행
    $result = sql_query($sql);
    
    if ($result) {
        // 마지막 삽입 ID 반환
        return sql_insert_id();
    }
    
    return false;
}


/**
 * 데이터베이스 테이블의 데이터를 업데이트하는 함수
 * 
 * @param string $table 테이블 이름
 * @param array $data 업데이트할 데이터 (필드명 => 값 형태의 배열)
 * @param array $where 업데이트 조건 (필드명 => 값 형태의 배열)
 * @return mixed 성공 시 true 또는 영향받은 행 수 반환, 실패 시 false 반환
 */
function sql_update($table, $data, $where) {
    global $mysqli; // 또는 $pdo, $conn 등 DB 연결 객체
    
    if (empty($table) || empty($data) || empty($where)) {
        return false;
    }
    
    $sets = array();
    foreach ($data as $key => $val) {
        $val = sql_escape_string($val);
        $sets[] = "`{$key}` = '{$val}'";
    }
    
    $conditions = array();
    foreach ($where as $key => $val) {
        $val = sql_escape_string($val);
        $conditions[] = "`{$key}` = '{$val}'";
    }
    
    $set_clause = implode(', ', $sets);
    $where_clause = implode(' AND ', $conditions);
    
    $sql = "UPDATE {$table} SET {$set_clause} WHERE {$where_clause}";
    
    // 로깅 (디버깅용)
    if (defined('CF_DEBUG') && CF_DEBUG) {
        error_log("SQL Update: " . $sql);
    }
    
    return sql_query($sql);
}


/**
 * 데이터베이스 테이블의 데이터를 삭제하는 함수
 * 
 * @param string $table 테이블 이름
 * @param array $where 삭제 조건 (필드명 => 값 형태의 배열)
 * @return mixed 성공 시 true 또는 영향받은 행 수 반환, 실패 시 false 반환
 */
function sql_delete($table, $where) {
    global $mysqli; // 또는 $pdo, $conn 등 DB 연결 객체
    
    if (empty($table) || empty($where)) {
        return false;
    }
    
    $conditions = array();
    foreach ($where as $key => $val) {
        $val = sql_escape_string($val);
        $conditions[] = "`{$key}` = '{$val}'";
    }
    
    $where_clause = implode(' AND ', $conditions);
    
    $sql = "DELETE FROM {$table} WHERE {$where_clause}";
    
    // 로깅 (디버깅용)
    if (defined('CF_DEBUG') && CF_DEBUG) {
        error_log("SQL Delete: " . $sql);
    }
    
    return sql_query($sql);
}


/**
 * SQL 결과 여러 행 가져오기
 */
function sql_fetch_all($sql) {    
    $result = sql_query($sql);
    
    $rows = array();
    if ($result) {
        while ($row = mysqli_fetch_array($result)) {
            $rows[] = $row;
        }
    }
    
    return $rows;
}


/**
 * 비밀번호 확인
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}


/**
 * 문자열 출력 시 HTML 특수문자 변환
 */
function clean_output($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * 랜덤 문자열 생성
 */
function generate_random_string($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random_string = '';
    
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $random_string;
}


/**
 * 로그인 체크
 */
function check_login() {
    global $is_member;
    
    if (!$is_member) {
        // 로그인 페이지로 이동
        goto_url(CF_URL . '/login.php');
        exit;
    }
}

/**
 * 관리자 권한 체크
 */
function check_admin() {
    global $is_admin;
    
    if (!$is_admin) {
        alert('관리자 권한이 필요합니다.');
        goto_url(CF_URL);
        exit;
    }
}


/**
 * 숫자 형식 변환
 */
function number_format_short($n) {
    if ($n < 1000) {
        return number_format($n);
    }
    
    if ($n < 1000000) {
        return number_format($n / 1000, 1) . 'K';
    }
    
    return number_format($n / 1000000, 1) . 'M';
}

/**
 * 날짜 형식 변환
 */
function format_date($date, $format = 'Y-m-d') {
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * 현재 페이지 URL 가져오기
 */
function get_current_url() {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * 폴더 생성
 */
function make_directory($path) {
    if (!is_dir($path)) {
        return mkdir($path, CF_DIR_PERMISSION, true);
    }
    
    return true;
}

/**
 * 파일 확장자 검사
 */
function check_file_ext($filename, $allowed_ext = array('jpg', 'jpeg', 'gif', 'png')) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, $allowed_ext);
}

/**
 * 파일 업로드
 */
function upload_file($file, $target_dir, $allowed_ext = array('jpg', 'jpeg', 'gif', 'png')) {
    $filename = basename($file['name']);
    $filesize = $file['size'];
    $filetype = $file['type'];
    $tmp_name = $file['tmp_name'];
    
    // 확장자 검사
    if (!check_file_ext($filename, $allowed_ext)) {
        return array('error' => '허용되지 않는 파일 형식입니다.');
    }
    
    // 디렉토리 생성
    make_directory($target_dir);
    
    // 파일명 중복 방지
    $new_filename = time() . '_' . generate_random_string(6) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
    $target_file = $target_dir . '/' . $new_filename;
    
    // 파일 업로드
    if (move_uploaded_file($tmp_name, $target_file)) {
        return array(
            'filename' => $new_filename,
            'filepath' => $target_file,
            'filesize' => $filesize,
            'filetype' => $filetype,
            'original_name' => $filename
        );
    } else {
        return array('error' => '파일 업로드에 실패했습니다.');
    }
}

/**
 * 알림 메시지 표시 (토스트)
 */
function show_toast_alert($message, $type = 'success', $duration = 3000) {
    echo "<script>showToast('$message', '$type', $duration);</script>";
}

/**
 * 페이지네이션 생성
 */
function list_paging($total_count, $page, $rows, $url, $add = '') {
    $total_page = ceil($total_count / $rows);
    $page = ($page > $total_page) ? $total_page : $page;
    $page = ($page < 1) ? 1 : $page;
    
    $start_page = max(1, $page - 4);
    $end_page = min($total_page, $page + 4);
    
    $prev_page = max(1, $page - 1);
    $next_page = min($total_page, $page + 1);
    
    $html = '<ul class="pagination justify-content-center">';
    
    // 처음 페이지 링크
    if ($page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=1' . $add . '">처음</a></li>';
    }
    
    // 이전 페이지 링크
    if ($page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . $prev_page . $add . '">이전</a></li>';
    }
    
    // 페이지 링크
    for ($i = $start_page; $i <= $end_page; $i++) {
        $active = ($i == $page) ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $url . '?page=' . $i . $add . '">' . $i . '</a></li>';
    }
    
    // 다음 페이지 링크
    if ($page < $total_page) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . $next_page . $add . '">다음</a></li>';
    }
    
    // 마지막 페이지 링크
    if ($page < $total_page) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . $total_page . $add . '">마지막</a></li>';
    }
    
    $html .= '</ul>';
    
    return $html;
}

/**
 * 캠페인 해시 생성
 */
function generate_campaign_hash() {
    return generate_random_string(CF_CAMPAIGN_HASH_LENGTH);
}

/**
 * 전환 스크립트 생성
 */
function generate_conversion_script($campaign_id, $conversion_type) {
    $script = "<!-- ConvertFlow 전환 추적 스크립트 -->\n";
    $script .= "<script>\n";
    $script .= "  (function() {\n";
    $script .= "    var cf = document.createElement('script');\n";
    $script .= "    cf.type = 'text/javascript';\n";
    $script .= "    cf.async = true;\n";
    $script .= "    cf.src = '" . CF_URL . "/api/conversion.js?id=" . $campaign_id . "&type=" . $conversion_type . "';\n";
    $script .= "    var s = document.getElementsByTagName('script')[0];\n";
    $script .= "    s.parentNode.insertBefore(cf, s);\n";
    $script .= "  })();\n";
    $script .= "</script>\n";
    
    return $script;
}

/**
 * UTM 파라미터 생성
 */
function generate_utm_params($campaign_name, $source = 'convertflow', $medium = 'cpa') {
    $params = array(
        'utm_source' => $source,
        'utm_medium' => $medium,
        'utm_campaign' => urlencode($campaign_name)
    );
    
    return http_build_query($params);
}

/**
 * 단축 URL 생성
 */
function create_short_url($url, $campaign_id = '') {
    $short_code = generate_random_string(6);
    
    $sql = "INSERT INTO {$cf_table_prefix}shortened_urls SET
            user_id = '{$_SESSION['user_id']}',
            campaign_id = '" . ($campaign_id ? $campaign_id : 'NULL') . "',
            original_url = '" . sql_escape_string($url) . "',
            short_code = '$short_code',
            created_at = NOW()";
    
    sql_query($sql);
    
    return CF_URL . '/s/' . $short_code;
}

/**
 * QR 코드 생성 URL 반환
 */
function get_qr_code_url($url) {
    // Google Chart API를 사용한 QR 코드 생성
    return 'https://chart.googleapis.com/chart?cht=qr&chl=' . urlencode($url) . '&chs=200x200&chld=L|0';
}

/**
 * 로그 기록
 */
function write_log($log_type, $action, $data = array(), $severity = '정보', $module = '') {
    global $member;

    $user_id = isset($member['id']) ? $member['id'] : 0;
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $sql = "INSERT INTO logs SET
        user_id = '$user_id',
        log_type = '" . sql_escape_string($log_type) . "',
        action = '" . sql_escape_string($action) . "',
        ip_address = '$ip',
        user_agent = '" . sql_escape_string($user_agent) . "',
        log_data = '" . sql_escape_string(json_encode($data)) . "',
        severity = '" . sql_escape_string($severity) . "',
        module = '" . sql_escape_string($module) . "',
        created_at = NOW()";

    sql_query($sql);
}
