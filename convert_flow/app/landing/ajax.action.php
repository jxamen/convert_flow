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


// 이미지 파일 처리 함수
function process_image_upload($landing_id, $block_id) {
    global $cf_table_prefix;

    // 파일이 업로드되었는지 확인
    if (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] == UPLOAD_ERR_NO_FILE) {
        return false;
    }
    
    // 파일 업로드 에러 체크
    if ($_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {        
        alert('파일 업로드 중 오류가 발생했습니다.');
        return false;
    }
    
    // 허용된 MIME 타입 확인
    $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
    $file_info = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $file_info->file($_FILES['image_file']['tmp_name']);
    
    if (!in_array($mime_type, $allowed_types)) {
        alert('허용되지 않는 파일 형식입니다. JPG, PNG, GIF, WebP 형식만 업로드할 수 있습니다.');
        return false;
    }
    
    // 파일 크기 제한 (1MB)
    $max_size = 1 * 1024 * 1024; // 1MB
    if ($_FILES['image_file']['size'] > $max_size) {
        alert('파일 크기가 너무 큽니다. 1MB 이하의 파일만 업로드할 수 있습니다.');
        return false;
    }
    
    // 업로드 디렉토리 설정 및 생성
    $upload_dir = CF_DATA_PATH . '/landing/block_images/'. $landing_id;
    
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, CF_DIR_PERMISSION, true)) {
            alert('업로드 디렉토리를 생성할 수 없습니다.');
            return false;
        }
    }
    
    // 파일명 결정 (고유한 파일명 생성)
    $file_ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
    $new_filename = 'image_' . $block_id . '.' . $file_ext;
    $file_path = $upload_dir . '/' . $new_filename;

    // 동일 파일 삭제 후 업로드
    @unlink($file_path);
    
    // 이미지 파일 저장
    if (!move_uploaded_file($_FILES['image_file']['tmp_name'], $file_path)) {
        alert('파일을 저장하는 중 오류가 발생했습니다.');
        return false;
    }
    
    // 파일 권한 설정
    chmod($file_path, CF_FILE_PERMISSION);
    
    // 웹에서 접근 가능한 URL 경로 생성
    $relative_path = str_replace(CF_PATH, '', $file_path);
    $file_url = CF_URL . $relative_path;
    
    // 이미지 정보를 블록 설정에 저장
    $block_instance = sql_fetch("SELECT * FROM landing_page_block_instances WHERE id = '{$block_id}'");
    if (!$block_instance) {
        return false;
    }
    
    // 블록 인스턴스에서 settings 가져오기
    $settings = array();
    if (isset($block_instance['settings']) && is_string($block_instance['settings'])) {
        // JSON 문자열을 배열로 변환
        $settings = json_decode($block_instance['settings'], true);
        // 디코딩 실패 시 빈 배열로 설정
        if ($settings === null) {
            $settings = array();
        }
    } else {
        // settings가 없거나 문자열이 아닌 경우 빈 배열로 설정
        $settings = array();
    }

    $settings['image_upload_method'] = 'upload';
    $settings['uploaded_image_path'] = $file_url;
    
    // 이전 업로드 파일 경로가 있다면 저장 (나중에 삭제를 위해)
    if (isset($settings['previous_uploaded_file'])) {
        $old_files = isset($settings['old_files']) ? $settings['old_files'] : array();
        $old_files[] = $settings['previous_uploaded_file'];
        $settings['old_files'] = $old_files;
    }
    
    $settings['previous_uploaded_file'] = $file_url;
    
    // 블록 인스턴스 업데이트
    sql_query("UPDATE landing_page_block_instances SET 
              settings = '" . sql_escape_string(json_encode($settings)) . "',
              updated_at = NOW() 
              WHERE id = '{$block_id}'");
    
    return $file_url;
}

// 이미지 파일 삭제 함수 (필요시 사용)
function delete_block_image($file_url) {
    if (empty($file_url)) {
        return false;
    }
    
    // URL을 파일 경로로 변환
    $file_path = str_replace(CF_URL, CF_PATH, $file_url);
    
    // 파일이 존재하면 삭제
    if (file_exists($file_path) && is_file($file_path)) {
        return @unlink($file_path);
    }
    
    return false;
}




// 블록 모델 로드
require_once CF_MODEL_PATH . '/landing_block.model.php';
$block_model = new LandingBlockModel();

// 액션 처리
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action) {
    switch ($action) {
        // 블록 추가
        case 'add_block':
            $landing_id = isset($_POST['landing_id']) ? intval($_POST['landing_id']) : 0;
            $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
            
            if ($template_id > 0) {
                $result = $block_model->add_block_instance(array(
                    'landing_page_id' => $landing_id,
                    'block_template_id' => $template_id
                ));

                if ($result) {
                    echo json_encode(array('success' => true, 'message' => '블록이 추가되었습니다.'));
                } else {
                    echo json_encode(array('success' => false, 'message' => '블록 추가 중 오류가 발생했습니다.'));
                }
            } else { 
                echo json_encode(array('success' => false, 'message' => '유효하지 않은 블록 템플릿입니다.'));
            }
            break;
            
        // 블록 삭제
        case 'delete_block':
            $block_id = isset($_POST['block_id']) ? intval($_POST['block_id']) : 0;
            
            if ($block_id > 0) {
                $result = $block_model->delete_block_instance($block_id);
                
                if ($result) {
                    echo json_encode(array('success' => true, 'message' => '블록이 삭제되었습니다.'));
                } else {
                    echo json_encode(array('success' => false, 'message' => '블록 삭제 중 오류가 발생했습니다.'));
                }
            } else {
                echo json_encode(array('success' => false, 'message' => '유효하지 않은 블록입니다.'));
            }
            break;
            
        // 블록 순서 업데이트
        case 'update_order':
            $block_orders = isset($_POST['block_order']) ? $_POST['block_order'] : array();
            
            if (!empty($block_orders)) {
                $result = $block_model->update_block_orders($block_orders);
                
                if ($result) {
                    echo json_encode(array('success' => true, 'message' => '블록 순서가 업데이트되었습니다.'));
                } else {
                    echo json_encode(array('success' => false, 'message' => '블록 순서 업데이트 중 오류가 발생했습니다.'));
                }
                exit;
            } else {
                echo json_encode(array('success' => false, 'message' => '업데이트할 블록 정보가 없습니다.'));
                exit;
            }
        break;  

        case 'update_block':
            // 블록 ID
            $block_id = isset($_POST['block_id']) ? intval($_POST['block_id']) : 0;
            $landing_id = isset($_POST['landing_id']) ? intval($_POST['landing_id']) : 0;
            $block_category = isset($_POST['block_category']) ? $_POST['block_category'] : '';

            if ($block_id <= 0) {
                echo json_encode(array('success' => false, 'message' => '유효하지 않은 블록 ID입니다.'));
                exit;
            }
        
            // 블록 존재 확인
            $block = $block_model->get_block_instance($block_id);
            if (!$block) {
                echo json_encode(array('success' => false, 'message' => '블록을 찾을 수 없습니다.'));
                exit;
            }
        
            // 설정 데이터
            $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
            $custom_html = isset($_POST['custom_html']) ? $_POST['custom_html'] : '';
            $custom_css = isset($_POST['custom_css']) ? $_POST['custom_css'] : '';
            $custom_js = isset($_POST['custom_js']) ? $_POST['custom_js'] : '';
        
            // 설정이 문자열로 왔을 경우 JSON으로 변환
            if (is_string($settings) && !empty($settings)) {
                $settings = json_decode($settings, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    echo json_encode(array('success' => false, 'message' => '유효하지 않은 설정 데이터입니다.'));
                    exit;
                }
            }

            $block_type = sql_fetch("SELECT * FROM landing_block_types WHERE id = '{$template['block_type_id']}'");

            // 이미지 블록인 경우 파일 처리
            if ($block_category === '이미지') {
                
                // 이미지 업로드 방식 확인
               $upload_method = isset($settings['image_upload_method']) ? $settings['image_upload_method'] : 'url';
                
                if ($upload_method === 'upload') {
                    // 파일 업로드 처리
                    $file_url = process_image_upload($landing_id, $block_id);
                    if ($file_url) {
                        // 업로드 성공 시 URL을 설정에 반영
                        $settings['image_url'] = $file_url;
                    } else if (!empty($_FILES['image_file']['name'])) {
                        // 업로드 실패 메시지를 이미 표시했으므로 여기서는 종료
                        exit;
                    }
                }
            }

            
            // 여백 값 가져오기
            $margin_top = isset($_POST['settings']['margin_top']) ? intval($_POST['settings']['margin_top']) : 0;
            $margin_right = isset($_POST['settings']['margin_right']) ? intval($_POST['settings']['margin_right']) : 0;
            $margin_bottom = isset($_POST['settings']['margin_bottom']) ? intval($_POST['settings']['margin_bottom']) : 0;
            $margin_left = isset($_POST['settings']['margin_left']) ? intval($_POST['settings']['margin_left']) : 0;

            // 설정에 여백 추가
            if (is_array($settings)) {
                $settings['margin_top'] = $margin_top;
                $settings['margin_right'] = $margin_right;
                $settings['margin_bottom'] = $margin_bottom;
                $settings['margin_left'] = $margin_left;
            }
        
            // 업데이트 데이터
            $update_data = array(
                'settings' => is_array($settings) ? json_encode($settings) : '{}',
                'custom_html' => $custom_html,
                'custom_css' => $custom_css,
                'custom_js' => $custom_js
            );
        
            // 블록 업데이트
            $result = $block_model->update_block_instance($block_id, $update_data);
        
            if ($result) {
                echo json_encode(array('success' => true, 'message' => '블록이 성공적으로 업데이트되었습니다.'));
            } else {
                echo json_encode(array('success' => false, 'message' => '블록 업데이트 중 오류가 발생했습니다.'));
            }
        break;

        case 'get_block':
            // 블록 ID
            $block_id = isset($_POST['block_id']) ? intval($_POST['block_id']) : 0;
            
            if ($block_id <= 0) {
                echo json_encode(array(
                    'success' => false, 
                    'message' => '유효하지 않은 블록 ID입니다.'
                ));
                exit;
            }
                    
            // 블록 정보 조회
            $block = $block_model->get_block_instance($block_id);
        
            if (!$block) {
                echo json_encode(array(
                    'success' => false, 
                    'message' => '블록을 찾을 수 없습니다.'
                ));
                exit;
            }
        
            // 블록 설정 정보 가져오기
            $settings = !empty($block['settings']) ? json_decode($block['settings'], true) : array();
            

            // 블록 템플릿 정보 조회
            $sql = "SELECT lbt.*, lbty.name as type_name, lbty.category 
                    FROM landing_block_templates lbt
                    JOIN landing_block_types lbty ON lbt.block_type_id = lbty.id
                    WHERE lbt.id = " . intval($block['block_template_id']);
            $template = sql_fetch($sql);
            
            // 폼 목록 조회 (이미지/동영상/폼 블록 등에 사용)
            $forms = array();
            $sql = "SELECT id, name FROM forms WHERE user_id = '{$member['id']}' ORDER BY name";
            $result = sql_query($sql);
            while ($row = sql_fetch_array($result)) {
                $forms[$row['id']] = $row['name'];
            }
            
            // 기본 설정 값 가져오기
            $default_settings = !empty($template['default_settings']) ? json_decode($template['default_settings'], true) : array();            
            
            // 기본값 + 사용자 설정 병합
            $merged_settings = array_merge($default_settings, $settings);
            
            // 블록 편집 폼 HTML 생성
            $form_html = '<form id="blockEditForm" method="post" multipart="multipart/form-data" action="' . CF_LANDING_URL . '/ajax.action.php">';
            $form_html .= '<input type="hidden" name="action" value="update_block">';
            $form_html .= '<input type="hidden" name="landing_id" value="' . $block['landing_page_id'] . '">';
            $form_html .= '<input type="hidden" name="block_id" value="' . $block_id . '">';
            $form_html .= '<input type="hidden" name="block_category" value="' . $template['category'] . '">';
            
            // 블록 타입에 따른 설정 필드 구성
            switch ($template['category']) {
                case '헤더':
                case 'text':
                    // 제목 및 텍스트 블록 설정
                    $form_html .= '<div class="form-group">';
                    $form_html .= '<label for="heading"><strong>제목</strong></label>';
                    $form_html .= '<input type="text" class="form-control" id="heading" name="settings[heading]" value="' . (isset($merged_settings['heading']) ? htmlspecialchars($merged_settings['heading']) : '') . '">';
                    $form_html .= '</div>';
                    
                    if ($template['type_name'] === 'heading') {
                        $form_html .= '<div class="form-group">';
                        $form_html .= '<label for="subheading"><strong>부제목</strong></label>';
                        $form_html .= '<input type="text" class="form-control" id="subheading" name="settings[subheading]" value="' . (isset($merged_settings['subheading']) ? htmlspecialchars($merged_settings['subheading']) : '') . '">';
                        $form_html .= '</div>';
                    }
                    
                    $form_html .= '<div class="form-group">';
                    $form_html .= '<label for="content"><strong>내용</strong></label>';
                    $form_html .= '<textarea class="form-control" id="content" name="settings[content]" rows="5">' . (isset($merged_settings['content']) ? htmlspecialchars($merged_settings['content']) : '') . '</textarea>';
                    $form_html .= '</div>';
                break;
                
                case '이미지':
                    // 이미지 블록 설정
                    $form_html .= '<div class="form-group">';
                    $form_html .= '<label><strong>이미지 추가 방식</strong></label>';
                    $form_html .= '<div class="btn-group btn-group-toggle w-100 mb-3" data-toggle="buttons">';
                    $url_active = empty($merged_settings['image_upload_method']) || $merged_settings['image_upload_method'] == 'url' ? 'active' : '';
                    $upload_active = isset($merged_settings['image_upload_method']) && $merged_settings['image_upload_method'] == 'upload' ? 'active' : '';
                    $form_html .= '<label class="btn btn-outline-primary ' . $url_active . '">';
                    $form_html .= '<input type="radio" name="settings[image_upload_method]" value="url" ' . ($url_active ? 'checked' : '') . '> URL 직접 입력';
                    $form_html .= '</label>';
                    $form_html .= '<label class="btn btn-outline-primary ' . $upload_active . '">';
                    $form_html .= '<input type="radio" name="settings[image_upload_method]" value="upload" ' . ($upload_active ? 'checked' : '') . '> 파일 업로드';
                    $form_html .= '</label>';
                    $form_html .= '</div>';
                    $form_html .= '</div>';
                    
                    // URL 입력 방식 (기본)
                    $url_display = empty($merged_settings['image_upload_method']) || $merged_settings['image_upload_method'] == 'url' ? 'block' : 'none';
                    $form_html .= '<div id="image_url_section" class="form-group" style="display: ' . $url_display . ';">';
                    $form_html .= '<label for="image_url"><strong>이미지 URL</strong></label>';
                    $form_html .= '<div class="input-group">';
                    $form_html .= '<input type="text" class="form-control" id="image_url" name="settings[image_url]" value="' . (isset($merged_settings['image_url']) ? htmlspecialchars($merged_settings['image_url']) : '') . '">';
                    $form_html .= '<div class="input-group-append">';
                    $form_html .= '</div></div>';
                    $form_html .= '<small class="form-text text-muted">이미지 URL을 직접 입력하거나 미디어 라이브러리에서 선택할 수 있습니다.</small>';
                    $form_html .= '</div>';
                    
                    // 파일 업로드 방식
                    $upload_display = isset($merged_settings['image_upload_method']) && $merged_settings['image_upload_method'] == 'upload' ? 'block' : 'none';
                    $form_html .= '<div id="image_upload_section" class="form-group" style="display: ' . $upload_display . ';">';
                    $form_html .= '<label for="image_file"><strong>이미지 파일</strong></label>';
                    $form_html .= '<div class="custom-file">';
                    $form_html .= '<input type="file" class="custom-file-input" id="image_file" name="image_file" accept="image/*">';
                    $form_html .= '<label class="custom-file-label" for="image_file">파일 선택...</label>';
                    $form_html .= '</div>';
                    $form_html .= '<small class="form-text text-muted">허용된 파일 형식: JPG, JPEG, PNG, GIF, WebP (최대 5MB)</small>';
                    
                    // 이미 업로드된 파일이 있는 경우 표시
                    if (isset($merged_settings['uploaded_image_path']) && !empty($merged_settings['uploaded_image_path'])) {
                        $form_html .= '<div class="mt-2">';
                        $form_html .= '<span class="text-info">현재 업로드된 파일: ' . basename($merged_settings['uploaded_image_path']) . '</span>';
                        $form_html .= '<input type="hidden" name="settings[uploaded_image_path]" value="' . htmlspecialchars($merged_settings['uploaded_image_path']) . '">';
                        $form_html .= '</div>';
                    }
                    $form_html .= '</div>';
                    
                    // 현재 이미지 미리보기
                    $image_src = '';
                    if (isset($merged_settings['image_upload_method']) && $merged_settings['image_upload_method'] == 'upload' && isset($merged_settings['uploaded_image_path'])) {
                        $image_src = $merged_settings['uploaded_image_path'];
                    } elseif (isset($merged_settings['image_url']) && !empty($merged_settings['image_url'])) {
                        $image_src = $merged_settings['image_url'];
                    }
                    
                    if (!empty($image_src)) {
                        $form_html .= '<div class="form-group">';
                        $form_html .= '<label><strong>현재 이미지</strong></label>';
                        $form_html .= '<div class="mb-2">';
                        $form_html .= '<img src="' . $image_src . '" alt="현재 이미지" class="img-thumbnail" style="max-height: 200px;">';
                        $form_html .= '</div></div>';
                    }
                    
                    
                    // 이미지 업로드 방식 전환 JavaScript
                    $form_html .= '<script>
                    $(document).ready(function() {
                        $("input[name=\'settings[image_upload_method]\']").change(function() {
                            if ($(this).val() == "url") {
                                $("#image_url_section").show();
                                $("#image_upload_section").hide();
                            } else {
                                $("#image_url_section").hide();
                                $("#image_upload_section").show();
                            }
                        });
                        
                        // 파일 업로드 커스텀 라벨 처리
                        $("#image_file").change(function() {
                            var fileName = $(this).val().split("\\\\").pop();
                            $(this).siblings(".custom-file-label").addClass("selected").html(fileName || "파일 선택...");
                        });
                    });
                    </script>';
                    break;
                    
                case '영상':
                    // 비디오 블록 설정
                    $form_html .= '<div class="form-group">';
                    $form_html .= '<label for="video_url"><strong>비디오 URL</strong></label>';
                    $form_html .= '<input type="text" class="form-control" id="video_url" name="settings[video_url]" value="' . (isset($merged_settings['video_url']) ? htmlspecialchars($merged_settings['video_url']) : '') . '">';
                    $form_html .= '<small class="form-text text-muted">YouTube, Vimeo 등의 비디오 URL 또는 직접 업로드한 비디오 파일 URL을 입력하세요.</small>';
                    $form_html .= '</div>';
                    
                    $form_html .= '<div class="form-group">';
                    $form_html .= '<label for="video_poster"><strong>썸네일 이미지 URL</strong></label>';
                    $form_html .= '<div class="input-group">';
                    $form_html .= '<input type="text" class="form-control" id="video_poster" name="settings[video_poster]" value="' . (isset($merged_settings['video_poster']) ? htmlspecialchars($merged_settings['video_poster']) : '') . '">';
                    $form_html .= '<div class="input-group-append">';
                    $form_html .= '<button class="btn btn-outline-secondary" type="button" onclick="openMediaLibrary(\'video_poster\')">미디어 라이브러리</button>';
                    $form_html .= '</div></div>';
                    $form_html .= '<small class="form-text text-muted">비디오가 로드되기 전에 표시될 썸네일 이미지입니다.</small>';
                    $form_html .= '</div>';
                    break;
                    
                case '폼':
                    // 폼 블록 설정
                    $form_html .= '<div class="form-group">';
                    $form_html .= '<label for="form_id"><strong>연결할 폼 선택</strong></label>';
                    $form_html .= '<select class="form-control" id="form_id" name="settings[form_id]">';
                    $form_html .= '<option value="">선택하세요</option>';
                    
                    foreach ($forms as $id => $name) {
                        $selected = (isset($merged_settings['form_id']) && $merged_settings['form_id'] == $id) ? 'selected' : '';
                        $form_html .= '<option value="' . $id . '" ' . $selected . '>' . $name . '</option>';
                    }
                    
                    $form_html .= '</select>';
                    $form_html .= '</div>';
                    
                    if (empty($forms)) {
                        $form_html .= '<div class="alert alert-info">';
                        $form_html .= '<i class="fas fa-info-circle"></i> 사용 가능한 폼이 없습니다. <a href="' . CF_FORM_URL . '/form_create.php" target="_blank">폼 생성하기</a>';
                        $form_html .= '</div>';
                    }
                    break;
                    
                case '버튼':
                    // 버튼 블록 설정
                    $form_html .= '<div class="form-group">';
                    $form_html .= '<label for="button_text"><strong>버튼 텍스트</strong></label>';
                    $form_html .= '<input type="text" class="form-control" id="button_text" name="settings[button_text]" value="' . (isset($merged_settings['button_text']) ? htmlspecialchars($merged_settings['button_text']) : '') . '">';
                    $form_html .= '</div>';
                    
                    $form_html .= '<div class="form-group">';
                    $form_html .= '<label for="button_url"><strong>버튼 URL</strong></label>';
                    $form_html .= '<input type="text" class="form-control" id="button_url" name="settings[button_url]" value="' . (isset($merged_settings['button_url']) ? htmlspecialchars($merged_settings['button_url']) : '') . '">';
                    $form_html .= '</div>';
                    
                    $form_html .= '<div class="form-group">';
                    $form_html .= '<label for="button_style"><strong>버튼 스타일</strong></label>';
                    $form_html .= '<select class="form-control" id="button_style" name="settings[button_style]">';
                    
                    $button_styles = array(
                        'primary' => '기본 (블루)',
                        'secondary' => '보조 (그레이)',
                        'success' => '성공 (그린)',
                        'danger' => '위험 (레드)',
                        'warning' => '경고 (옐로우)',
                        'info' => '정보 (라이트 블루)',
                        'light' => '밝은 (화이트)',
                        'dark' => '어두운 (블랙)',
                        'outline-primary' => '테두리 기본',
                        'outline-secondary' => '테두리 보조'
                    );
                    
                    foreach ($button_styles as $value => $label) {
                        $selected = (isset($merged_settings['button_style']) && $merged_settings['button_style'] == $value) ? 'selected' : '';
                        $form_html .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
                    }
                    
                    $form_html .= '</select>';
                    $form_html .= '</div>';
                    break;
                    
                default:
                    // 기타 블록 타입에 대한 설정
                    $form_html .= '<div class="alert alert-info">';
                    $form_html .= '<i class="fas fa-info-circle"></i> 이 블록 타입(' . $template['type_name'] . ')에 대한 특별한 설정이 없습니다. 사용자 정의 HTML, CSS, JS를 사용하여 블록을 편집할 수 있습니다.';
                    $form_html .= '</div>';
                    break;
            }
            


            // 블록 공통 설정 - 여백 옵션
            $form_html .= '<div class="mt-4">';
            $form_html .= '<div class="form-group">';   
            $form_html .= '<label><strong>여백 설정</strong></label>';

            // 여백 컨트롤을 담을 그리드 레이아웃 생성
            $form_html .= '<div class="row">';

            // 상단 여백
            $form_html .= '<div class="col-md-3">';
            $form_html .= '<div class="form-group">';
            $form_html .= '<label for="margin_top"><strong>상단 여백</strong></label>';
            $form_html .= '<div class="input-group">';
            $form_html .= '<input type="number" class="form-control" id="margin_top" name="settings[margin_top]" min="0" max="100" value="' . (isset($merged_settings['margin_top']) ? intval($merged_settings['margin_top']) : 0) . '">';
            $form_html .= '<div class="input-group-append">';
            $form_html .= '<span class="input-group-text">px</span>';
            $form_html .= '</div></div>';
            $form_html .= '</div></div>';

            // 오른쪽 여백
            $form_html .= '<div class="col-md-3">';
            $form_html .= '<div class="form-group">';
            $form_html .= '<label for="margin_right"><strong>오른쪽 여백</strong></label>';
            $form_html .= '<div class="input-group">';
            $form_html .= '<input type="number" class="form-control" id="margin_right" name="settings[margin_right]" min="0" max="100" value="' . (isset($merged_settings['margin_right']) ? intval($merged_settings['margin_right']) : 0) . '">';
            $form_html .= '<div class="input-group-append">';
            $form_html .= '<span class="input-group-text">px</span>';
            $form_html .= '</div></div>';
            $form_html .= '</div></div>';

            // 하단 여백
            $form_html .= '<div class="col-md-3">';
            $form_html .= '<div class="form-group">';
            $form_html .= '<label for="margin_bottom"><strong>하단 여백</strong></label>';
            $form_html .= '<div class="input-group">';
            $form_html .= '<input type="number" class="form-control" id="margin_bottom" name="settings[margin_bottom]" min="0" max="100" value="' . (isset($merged_settings['margin_bottom']) ? intval($merged_settings['margin_bottom']) : 0) . '">';
            $form_html .= '<div class="input-group-append">';
            $form_html .= '<span class="input-group-text">px</span>';
            $form_html .= '</div></div>';
            $form_html .= '</div></div>';

            // 왼쪽 여백
            $form_html .= '<div class="col-md-3">';
            $form_html .= '<div class="form-group">';
            $form_html .= '<label for="margin_left"><strong>왼쪽 여백</strong></label>';
            $form_html .= '<div class="input-group">';
            $form_html .= '<input type="number" class="form-control" id="margin_left" name="settings[margin_left]" min="0" max="100" value="' . (isset($merged_settings['margin_left']) ? intval($merged_settings['margin_left']) : 0) . '">';
            $form_html .= '<div class="input-group-append">';
            $form_html .= '<span class="input-group-text">px</span>';
            $form_html .= '</div></div>';
            $form_html .= '</div></div>';

            // 그리드 종료
            $form_html .= '</div></div>';

            // 여백 미리보기 추가 (시각적 도움)
            $form_html .= '<div class="mt-3 mb-3">';
            $form_html .= '<div class="form-group">';            
            $form_html .= '<label><strong>여백 미리보기</strong></label>';
            $form_html .= '<div class="p-3 bg-light border">';
            $form_html .= '<div id="marginPreview" class="border border-primary d-flex justify-content-center align-items-center" style="';
            $form_html .= 'margin-top: ' . (isset($merged_settings['margin_top']) ? intval($merged_settings['margin_top']) : 0) . 'px;';
            $form_html .= 'margin-right: ' . (isset($merged_settings['margin_right']) ? intval($merged_settings['margin_right']) : 0) . 'px;';
            $form_html .= 'margin-bottom: ' . (isset($merged_settings['margin_bottom']) ? intval($merged_settings['margin_bottom']) : 0) . 'px;';
            $form_html .= 'margin-left: ' . (isset($merged_settings['margin_left']) ? intval($merged_settings['margin_left']) : 0) . 'px;';
            $form_html .= 'height: 100px;">';
            $form_html .= '<div class="text-center">블록 컨텐츠</div>';
            $form_html .= '</div></div>';
            $form_html .= '</div></div>';

            // 여백 설정 섹션 종료
            $form_html .= '</div>';

            // 여백 미리보기 실시간 업데이트 스크립트
            $form_html .= '<script>
            $(document).ready(function() {
                // 여백 입력 필드 변경 시 미리보기 업데이트
                $("#margin_top, #margin_right, #margin_bottom, #margin_left").on("input", function() {
                    updateMarginPreview();
                });
                
                function updateMarginPreview() {
                    var marginTop = $("#margin_top").val() || 0;
                    var marginRight = $("#margin_right").val() || 0;
                    var marginBottom = $("#margin_bottom").val() || 0;
                    var marginLeft = $("#margin_left").val() || 0;
                    
                    $("#marginPreview").css({
                        "margin-top": marginTop + "px",
                        "margin-right": marginRight + "px",
                        "margin-bottom": marginBottom + "px",
                        "margin-left": marginLeft + "px"
                    });
                }
            });
            </script>';



            // 고급 설정 영역 (사용자 정의 코드)
            $form_html .= '<div class="mt-4">';
            $form_html .= '<div class="card">';
            $form_html .= '<div class="card-header" id="advancedSettingsHeader">';
            $form_html .= '<h5 class="mb-0">';
            $form_html .= '<button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#advancedSettingsCollapse" aria-expanded="false" aria-controls="advancedSettingsCollapse">';
            $form_html .= '<i class="fas fa-cog mr-2"></i>고급 설정';
            $form_html .= '</button>';
            $form_html .= '</h5>';
            $form_html .= '</div>';

            $form_html .= '<div id="advancedSettingsCollapse" class="collapse" aria-labelledby="advancedSettingsHeader">';
            $form_html .= '<div class="card-body">';

            $form_html .= '<div class="form-group">';
            $form_html .= '<label for="custom_html"><strong>사용자 정의 HTML</strong></label>';
            $form_html .= '<textarea class="form-control code-editor" id="custom_html" name="custom_html" rows="3">' . htmlspecialchars($block['custom_html']) . '</textarea>';
            $form_html .= '<small class="form-text text-muted">이 필드를 비워두면 기본 템플릿이 사용됩니다.</small>';
            $form_html .= '</div>';

            $form_html .= '<div class="form-group">';
            $form_html .= '<label for="custom_css"><strong>사용자 정의 CSS</strong></label>';
            $form_html .= '<textarea class="form-control code-editor" id="custom_css" name="custom_css" rows="3">' . htmlspecialchars($block['custom_css']) . '</textarea>';
            $form_html .= '</div>';

            $form_html .= '<div class="form-group">';
            $form_html .= '<label for="custom_js"><strong>사용자 정의 JavaScript</strong></label>';
            $form_html .= '<textarea class="form-control code-editor" id="custom_js" name="custom_js" rows="3">' . htmlspecialchars($block['custom_js']) . '</textarea>';
            $form_html .= '</div>';

            $form_html .= '</div>'; // card-body 끝
            $form_html .= '</div>'; // collapse 끝
            $form_html .= '</div>'; // card 끝
            $form_html .= '</div>'; // mt-4 끝

            $form_html .= '</form>';

            // 코드 에디터 초기화를 위한 스크립트를 추가합니다
            $form_html .= '<script>
            $(document).ready(function() {
                // 코드 에디터가 초기화된 상태를 추적하기 위한 변수
                var editorsInitialized = false;
                
                // 고급 설정 토글 시 코드 에디터 초기화
                $("#advancedSettingsCollapse").on("shown.bs.collapse", function() {
                    if (!editorsInitialized) {
                        // 여기에 코드 에디터 초기화 로직을 추가 (예: CodeMirror)
                        // CodeMirror 사용 예:
                        // var htmlEditor = CodeMirror.fromTextArea(document.getElementById("custom_html"), {
                        //     mode: "htmlmixed",
                        //     lineNumbers: true,
                        //     theme: "monokai"
                        // });
                        editorsInitialized = true;
                    }
                });
            });
            </script>';
            
            // 응답 데이터 구성
            $response = array(
                'success' => true,
                'block' => array(
                    'id' => $block['id'],
                    'block_template_id' => $block['block_template_id'],
                    'settings' => $settings,
                    'custom_html' => $block['custom_html'],
                    'custom_css' => $block['custom_css'],
                    'custom_js' => $block['custom_js']
                ),
                'template' => array(
                    'id' => $template['id'],
                    'name' => $template['name'],
                    'type_name' => $template['type_name'],
                    'category' => $template['category'],
                    'html_content' => $form_html
                )
            );
            
            echo json_encode($response);
            exit;
    }
}
?>