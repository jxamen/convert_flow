<?php
/**
 * 폼 관련 모델
 */
if (!defined('_CONVERT_FLOW_')) exit;

// 테이블 구조 변경 (자동 업데이트)
$form_alter_queries = array(
    "ALTER TABLE forms ADD COLUMN IF NOT EXISTS cta_type ENUM('button', 'image') DEFAULT 'button' COMMENT 'CTA 버튼 유형';",
    "ALTER TABLE forms ADD COLUMN IF NOT EXISTS cta_button_bg_color VARCHAR(20) DEFAULT '#007bff' COMMENT '버튼 배경색';",
    "ALTER TABLE forms ADD COLUMN IF NOT EXISTS cta_button_text_color VARCHAR(20) DEFAULT '#ffffff' COMMENT '버튼 글자색';",
    "ALTER TABLE forms ADD COLUMN IF NOT EXISTS cta_button_size ENUM('small', 'medium', 'large') DEFAULT 'medium' COMMENT '버튼 크기';",
    "ALTER TABLE forms ADD COLUMN IF NOT EXISTS cta_button_radius INT DEFAULT 4 COMMENT '버튼 라운드 크기(px)';",
    "ALTER TABLE forms ADD COLUMN IF NOT EXISTS cta_button_border_color VARCHAR(20) DEFAULT '#007bff' COMMENT '버튼 테두리 색상';",
    "ALTER TABLE forms ADD COLUMN IF NOT EXISTS cta_image_path VARCHAR(255) DEFAULT NULL COMMENT 'CTA 이미지 경로';",
    "ALTER TABLE forms ADD COLUMN IF NOT EXISTS complete_type ENUM('text', 'image') DEFAULT 'text' COMMENT '완료 메시지 유형';",
    "ALTER TABLE forms ADD COLUMN IF NOT EXISTS complete_text_bg_color VARCHAR(20) DEFAULT '#d4edda' COMMENT '완료 메시지 배경색';",
    "ALTER TABLE forms ADD COLUMN IF NOT EXISTS complete_text_color VARCHAR(20) DEFAULT '#155724' COMMENT '완료 메시지 글자색';",
    "ALTER TABLE forms ADD COLUMN IF NOT EXISTS complete_text_size ENUM('small', 'medium', 'large') DEFAULT 'medium' COMMENT '완료 메시지 글자 크기';",
    "ALTER TABLE forms ADD COLUMN IF NOT EXISTS complete_image_path VARCHAR(255) DEFAULT NULL COMMENT '완료 메시지 이미지 경로';"
);

// 테이블 구조 업데이트 실행
foreach ($form_alter_queries as $query) {
    sql_query($query);
}

class FormModel {
    /**
     * 폼 생성
     * @param array $form_data 폼 데이터
     * @return int 생성된 폼 ID 또는 실패 시 false
     */
    public function create_form($form_data) {
        global $cf_table_prefix;
        
        
        
        $sql = "INSERT INTO forms
                SET user_id = '" . sql_escape_string($form_data['user_id']) . "',
                    landing_page_id = " . ($form_data['landing_page_id'] ? intval($form_data['landing_page_id']) : 'NULL') . ",
                    name = '" . sql_escape_string($form_data['name']) . "',
                    submit_button_text = '" . sql_escape_string($form_data['submit_button_text']) . "',
                    success_message = '" . sql_escape_string($form_data['success_message']) . "',
                    redirect_url = '" . sql_escape_string($form_data['redirect_url']) . "',
                    is_multi_step = '" . sql_escape_string($form_data['is_multi_step']) . "',
                    auto_save_enabled = '" . sql_escape_string($form_data['auto_save_enabled']) . "',
                    cta_type = '" . sql_escape_string(isset($form_data['cta_type']) ? $form_data['cta_type'] : 'button') . "',
                    cta_button_bg_color = '" . sql_escape_string(isset($form_data['cta_button_bg_color']) ? $form_data['cta_button_bg_color'] : '#007bff') . "',
                    cta_button_text_color = '" . sql_escape_string(isset($form_data['cta_button_text_color']) ? $form_data['cta_button_text_color'] : '#ffffff') . "',
                    cta_button_size = '" . sql_escape_string(isset($form_data['cta_button_size']) ? $form_data['cta_button_size'] : 'medium') . "',
                    cta_button_radius = " . intval(isset($form_data['cta_button_radius']) ? $form_data['cta_button_radius'] : 4) . ",
                    cta_button_border_color = '" . sql_escape_string(isset($form_data['cta_button_border_color']) ? $form_data['cta_button_border_color'] : '#007bff') . "',
                    cta_image_path = '" . sql_escape_string($cta_image_path) . "',
                    complete_type = '" . sql_escape_string(isset($form_data['complete_type']) ? $form_data['complete_type'] : 'text') . "',
                    complete_text_bg_color = '" . sql_escape_string(isset($form_data['complete_text_bg_color']) ? $form_data['complete_text_bg_color'] : '#d4edda') . "',
                    complete_text_color = '" . sql_escape_string(isset($form_data['complete_text_color']) ? $form_data['complete_text_color'] : '#155724') . "',
                    complete_text_size = '" . sql_escape_string(isset($form_data['complete_text_size']) ? $form_data['complete_text_size'] : 'medium') . "',
                    complete_image_path = '" . sql_escape_string($complete_image_path) . "',
                    created_at = NOW(),
                    updated_at = NOW()";
        
        sql_query($sql);
        $form_id = sql_insert_id();



        // 업로드 디렉토리 설정 및 생성
        $upload_dir = CF_DATA_FORM_PATH . '/';
        
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, CF_DIR_PERMISSION, true);
        }

        // 이미지 업로드 처리
        $cta_image_path = '';
        if (isset($_FILES['cta_image']) && $_FILES['cta_image']['name']) {
            // 파일 크기 제한 (0.5MB)
            $max_size = 1 * 512 * 512; // 0.5MB
            if ($_FILES['cta_image']['size'] > $max_size) {
                alert('파일 크기가 너무 큽니다.');
                return false;
            }
            
            // 파일명 결정 (고유한 파일명 생성)
            $file_ext = pathinfo($_FILES['cta_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'cta_' . $form_id . '.' . $file_ext;
            $file_path = $upload_dir . '/' . $new_filename;

            // 동일 파일 삭제 후 업로드
            @unlink($file_path);

            
            // 이미지 파일 저장
            if (!move_uploaded_file($_FILES['cta_image']['tmp_name'], $file_path)) {
                alert('파일을 저장하는 중 오류가 발생했습니다.');
                return false;
            }
            
            // 파일 권한 설정
            chmod($file_path, CF_FILE_PERMISSION);
            
            // 웹에서 접근 가능한 URL 경로 생성
            $cta_image_path = CF_DATA_FORM_URL . '/' . $new_filename;
        }


        
        // 완료 이미지 업로드 처리
        $complete_image_path = '';
        if (isset($_FILES['complete_image']) && $_FILES['complete_image']['name']) {
            // 파일 크기 제한 (1MB)
            $max_size = 1 * 1024 * 1024; // 1MB
            if ($_FILES['complete_image']['size'] > $max_size) {
                alert('파일 크기가 너무 큽니다.');
                return false;
            }

            
            // 파일명 결정 (고유한 파일명 생성)
            $file_ext = pathinfo($_FILES['complete_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'complete_' . $form_id . '.' . $file_ext;
            echo $file_path = $upload_dir . '/' . $new_filename;

            // 동일 파일 삭제 후 업로드
            @unlink($file_path);

            
            // 이미지 파일 저장
            if (!move_uploaded_file($_FILES['complete_image']['tmp_name'], $file_path)) {
                alert('파일을 저장하는 중 오류가 발생했습니다.');
                return false;
            }
            
            // 파일 권한 설정
            chmod($file_path, CF_FILE_PERMISSION);
            
            // 웹에서 접근 가능한 URL 경로 생성
            $complete_image_path = CF_DATA_FORM_URL . '/' . $new_filename;
        }


        $sql = "UPDATE forms 
                SET 
                    cta_image_path      = '{$cta_image_path}',
                    complete_image_path = '{$complete_image_path}'
                WHERE id = '{$form_id}'";
        sql_query($sql);
        
        return $form_id;
    }

    
    /**
     * 폼 정보 조회
     * @param int $form_id 폼 ID
     * @return array 폼 정보 또는 없을 경우 false
     */
    public function get_form($form_id) {
        global $cf_table_prefix;
        
        $sql = "SELECT * FROM forms WHERE id = " . intval($form_id);
        $row = sql_fetch($sql);
        
        return $row;
    }
    
    /**
     * 폼 수정
     * @param int $form_id 폼 ID
     * @param array $form_data 폼 데이터
     * @return bool 성공 여부
     */
    public function update_form($form_id, $form_data) {
        global $cf_table_prefix;
        
        // 현재 폼 정보 조회 (이미지 경로 유지 등을 위해)
        $current_form = $this->get_form($form_id);
        
        // 업로드 디렉토리 설정 및 생성
        $upload_dir = CF_DATA_FORM_PATH . '/';
        
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, CF_DIR_PERMISSION, true);
        }

        // 이미지 업로드 처리
        $cta_image_path = '';
        if (isset($_FILES['cta_image']) && $_FILES['cta_image']['name']) {
            // 파일 크기 제한 (0.5MB)
            $max_size = 1 * 512 * 512; // 0.5MB
            if ($_FILES['cta_image']['size'] > $max_size) {
                alert('파일 크기가 너무 큽니다.');
                return false;
            }
            
            // 파일명 결정 (고유한 파일명 생성)
            $file_ext = pathinfo($_FILES['cta_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'cta_' . $form_id . '.' . $file_ext;
            $file_path = $upload_dir . '/' . $new_filename;

            // 동일 파일 삭제 후 업로드
            @unlink($file_path);

            
            // 이미지 파일 저장
            if (!move_uploaded_file($_FILES['cta_image']['tmp_name'], $file_path)) {
                alert('파일을 저장하는 중 오류가 발생했습니다.');
                return false;
            }
            
            // 파일 권한 설정
            chmod($file_path, CF_FILE_PERMISSION);
            
            // 웹에서 접근 가능한 URL 경로 생성
            $cta_image_path = CF_DATA_FORM_URL . '/' . $new_filename;

            $sql_cta_image_path = ", cta_image_path = '" . sql_escape_string($cta_image_path) . "'";
        }


        
        // 완료 이미지 업로드 처리
        $complete_image_path = '';
        if (isset($_FILES['complete_image']) && $_FILES['complete_image']['name']) {
            // 파일 크기 제한 (1MB)
            $max_size = 1 * 1024 * 1024; // 1MB
            if ($_FILES['complete_image']['size'] > $max_size) {
                alert('파일 크기가 너무 큽니다.');
                return false;
            }

            
            // 파일명 결정 (고유한 파일명 생성)
            $file_ext = pathinfo($_FILES['complete_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'complete_' . $form_id . '.' . $file_ext;
            echo $file_path = $upload_dir . '/' . $new_filename;

            // 동일 파일 삭제 후 업로드
            @unlink($file_path);

            
            // 이미지 파일 저장
            if (!move_uploaded_file($_FILES['complete_image']['tmp_name'], $file_path)) {
                alert('파일을 저장하는 중 오류가 발생했습니다.');
                return false;
            }
            
            // 파일 권한 설정
            chmod($file_path, CF_FILE_PERMISSION);
            
            // 웹에서 접근 가능한 URL 경로 생성
            $complete_image_path = CF_DATA_FORM_URL . '/' . $new_filename;

            $sql_complete_image_path = ",complete_image_path = '" . sql_escape_string($complete_image_path) . "'";
        }


        
        $sql = "UPDATE forms
                SET landing_page_id = " . ($form_data['landing_page_id'] ? intval($form_data['landing_page_id']) : 'NULL') . ",
                    name = '" . sql_escape_string($form_data['name']) . "',
                    submit_button_text = '" . sql_escape_string($form_data['submit_button_text']) . "',
                    success_message = '" . sql_escape_string($form_data['success_message']) . "',
                    redirect_url = '" . sql_escape_string($form_data['redirect_url']) . "',
                    is_multi_step = '" . sql_escape_string($form_data['is_multi_step']) . "',
                    auto_save_enabled = '" . sql_escape_string($form_data['auto_save_enabled']) . "',
                    cta_type = '" . sql_escape_string(isset($form_data['cta_type']) ? $form_data['cta_type'] : 'button') . "',
                    cta_button_bg_color = '" . sql_escape_string(isset($form_data['cta_button_bg_color']) ? $form_data['cta_button_bg_color'] : '#007bff') . "',
                    cta_button_text_color = '" . sql_escape_string(isset($form_data['cta_button_text_color']) ? $form_data['cta_button_text_color'] : '#ffffff') . "',
                    cta_button_size = '" . sql_escape_string(isset($form_data['cta_button_size']) ? $form_data['cta_button_size'] : 'medium') . "',
                    cta_button_radius = " . intval(isset($form_data['cta_button_radius']) ? $form_data['cta_button_radius'] : 4) . ",
                    cta_button_border_color = '" . sql_escape_string(isset($form_data['cta_button_border_color']) ? $form_data['cta_button_border_color'] : '#007bff') . "',
                    
                    complete_type = '" . sql_escape_string(isset($form_data['complete_type']) ? $form_data['complete_type'] : 'text') . "',
                    complete_text_bg_color = '" . sql_escape_string(isset($form_data['complete_text_bg_color']) ? $form_data['complete_text_bg_color'] : '#d4edda') . "',
                    complete_text_color = '" . sql_escape_string(isset($form_data['complete_text_color']) ? $form_data['complete_text_color'] : '#155724') . "',
                    complete_text_size = '" . sql_escape_string(isset($form_data['complete_text_size']) ? $form_data['complete_text_size'] : 'medium') . "',
                    updated_at = NOW()
                    {$sql_cta_image_path}
                    {$sql_complete_image_path}
                WHERE id = " . intval($form_id);
        
        return sql_query($sql);
    }
    
    /**
     * 폼 삭제
     * @param int $form_id 폼 ID
     * @return bool 성공 여부
     */
    public function delete_form($form_id) {
        global $cf_table_prefix;
        
        // 관련 데이터 삭제
        sql_query("DELETE FROM form_fields WHERE form_id = " . intval($form_id));
        sql_query("DELETE FROM form_conditional_logic WHERE form_id = " . intval($form_id));
        
        // 폼 삭제
        $sql = "DELETE FROM forms WHERE id = " . intval($form_id);
        
        return sql_query($sql);
    }
    
    /**
     * 폼 필드 추가
     * @param array $field_data 필드 데이터
     * @return int 생성된 필드 ID 또는 실패 시 false
     */
    public function add_field($field_data) {
        global $cf_table_prefix;
        
        // 현재 최대 순서 조회
        $sql = "SELECT MAX(field_order) as max_order FROM form_fields 
                WHERE form_id = " . intval($field_data['form_id']);
        $row = sql_fetch($sql);
        $max_order = isset($row['max_order']) ? intval($row['max_order']) : 0;
        
        $sql = "INSERT INTO form_fields
                SET form_id = " . intval($field_data['form_id']) . ",
                    label = '" . sql_escape_string($field_data['label']) . "',
                    type = '" . sql_escape_string($field_data['type']) . "',
                    placeholder = '" . sql_escape_string($field_data['placeholder']) . "',
                    default_value = '" . sql_escape_string($field_data['default_value']) . "',
                    is_required = '" . sql_escape_string($field_data['is_required']) . "',
                    validation_rule = '" . sql_escape_string($field_data['validation_rule']) . "',
                    error_message = '" . sql_escape_string($field_data['error_message']) . "',
                    step_number = " . intval($field_data['step_number']) . ",
                    field_order = " . ($max_order + 1) . ",
                    options = " . ($field_data['options'] ? "'" . sql_escape_string($field_data['options']) . "'" : 'NULL') . ",
                    created_at = NOW(),
                    updated_at = NOW()";
        
        sql_query($sql);
        $field_id = sql_insert_id();
        
        return $field_id;
    }
    
    /**
     * 필드 정보 조회
     * @param int $field_id 필드 ID
     * @return array 필드 정보 또는 없을 경우 false
     */
    public function get_field($field_id) {
        global $cf_table_prefix;
        
        $sql = "SELECT * FROM form_fields WHERE id = " . intval($field_id);
        $row = sql_fetch($sql);
        
        return $row;
    }
    
    /**
     * 폼 필드 목록 조회
     * @param int $form_id 폼 ID
     * @return array 필드 목록
     */
    public function get_form_fields($form_id) {
        global $cf_table_prefix;
        
        $sql = "SELECT * FROM form_fields 
                WHERE form_id = " . intval($form_id) . " 
                ORDER BY field_order ASC";
        $result = sql_query($sql);
        
        $fields = array();
        while ($row = sql_fetch_array($result)) {
            $fields[] = $row;
        }
        
        return $fields;
    }
    
    /**
     * 필드 업데이트
     * @param int $field_id 필드 ID
     * @param array $field_data 필드 데이터
     * @return bool 성공 여부
     */
    public function update_field($field_id, $field_data) {
        global $cf_table_prefix;
        
        $sql = "UPDATE form_fields
                SET label = '" . sql_escape_string($field_data['label']) . "',
                    type = '" . sql_escape_string($field_data['type']) . "',
                    placeholder = '" . sql_escape_string($field_data['placeholder']) . "',
                    default_value = '" . sql_escape_string($field_data['default_value']) . "',
                    is_required = '" . sql_escape_string($field_data['is_required']) . "',
                    validation_rule = '" . sql_escape_string($field_data['validation_rule']) . "',
                    error_message = '" . sql_escape_string($field_data['error_message']) . "',
                    step_number = " . intval($field_data['step_number']) . ",
                    options = " . ($field_data['options'] ? "'" . sql_escape_string($field_data['options']) . "'" : 'NULL') . ",
                    updated_at = NOW()
                WHERE id = " . intval($field_id);
        
        return sql_query($sql);
    }
    
    /**
     * 필드 삭제
     * @param int $field_id 필드 ID
     * @return bool 성공 여부
     */
    public function delete_field($field_id) {
        global $cf_table_prefix;
        
        // 관련 조건부 로직 삭제
        sql_query("DELETE FROM form_conditional_logic 
                  WHERE source_field_id = " . intval($field_id) . " 
                  OR target_field_id = " . intval($field_id));
        
        // 필드 삭제
        $sql = "DELETE FROM form_fields WHERE id = " . intval($field_id);
        
        return sql_query($sql);
    }
    
    /**
     * 필드 순서 업데이트
     * @param array $field_orders 필드 ID와 순서 배열
     * @return bool 성공 여부
     */
    public function update_field_orders($field_orders) {
        global $cf_table_prefix;
        
        foreach ($field_orders as $field) {
            $sql = "UPDATE form_fields
                    SET field_order = " . intval($field['order']) . "
                    WHERE id = " . intval($field['id']);
            sql_query($sql);
        }
        
        return true;
    }
    
    /**
     * 리드 데이터 저장
     * @param array $lead_data 리드 데이터
     * @return int 생성된 리드 ID 또는 실패 시 false
     */
    public function save_lead($lead_data) {
        global $cf_table_prefix;
        
        // 중복 체크 (이메일이나 전화번호 등으로 판단 가능)
        $duplicate_id = null;
        $lead_json = $lead_data['data'];
        $lead_arr = json_decode($lead_json, true);
        
        // 이메일 필드가 있는지 확인 (예시)
        $email = null;
        foreach ($lead_arr as $key => $value) {
            if (strpos(strtolower($key), 'email') !== false && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $email = $value;
                break;
            }
        }
        
        // 이메일로 중복 체크
        if ($email) {
            $sql = "SELECT id FROM leads 
                    WHERE form_id = " . intval($lead_data['form_id']) . " 
                    AND data LIKE '%" . sql_escape_string($email) . "%'
                    ORDER BY id DESC LIMIT 1";
            $row = sql_fetch($sql);
            if ($row) {
                $duplicate_id = $row['id'];
                $lead_data['status'] = '중복';
                $lead_data['duplicate_of'] = $duplicate_id;
            }
        }
        
        $sql = "INSERT INTO leads
                SET form_id = " . intval($lead_data['form_id']) . ",
                    landing_page_id = " . ($lead_data['landing_page_id'] ? intval($lead_data['landing_page_id']) : 'NULL') . ",
                    campaign_id = " . ($lead_data['campaign_id'] ? intval($lead_data['campaign_id']) : 'NULL') . ",
                    data = '" . sql_escape_string($lead_data['data']) . "',
                    ip_address = '" . sql_escape_string($lead_data['ip_address']) . "',
                    user_agent = '" . sql_escape_string($lead_data['user_agent']) . "',
                    referrer = '" . sql_escape_string($lead_data['referrer']) . "',
                    utm_source = '" . sql_escape_string($lead_data['utm_source']) . "',
                    utm_medium = '" . sql_escape_string($lead_data['utm_medium']) . "',
                    utm_campaign = '" . sql_escape_string($lead_data['utm_campaign']) . "',
                    utm_content = '" . sql_escape_string($lead_data['utm_content']) . "',
                    utm_term = '" . sql_escape_string($lead_data['utm_term']) . "',
                    status = '" . sql_escape_string($lead_data['status']) . "',
                    duplicate_of = " . ($duplicate_id ? intval($duplicate_id) : 'NULL') . ",
                    created_at = NOW(),
                    updated_at = NOW()";
        
        sql_query($sql);
        $lead_id = sql_insert_id();
        
        return $lead_id;
    }
    
    /**
     * 조건부 로직 추가
     * @param array $logic_data 조건부 로직 데이터
     * @return int 생성된 로직 ID 또는 실패 시 false
     */
    public function add_conditional_logic($logic_data) {
        global $cf_table_prefix;
        
        $sql = "INSERT INTO form_conditional_logic
                SET form_id = " . intval($logic_data['form_id']) . ",
                    source_field_id = " . intval($logic_data['source_field_id']) . ",
                    target_field_id = " . intval($logic_data['target_field_id']) . ",
                    compare_operator = '" . sql_escape_string($logic_data['compare_operator']) . "',
                    value = '" . sql_escape_string($logic_data['value']) . "',
                    action = '" . sql_escape_string($logic_data['action']) . "',
                    created_at = NOW(),
                    updated_at = NOW()";
        
        sql_query($sql);
        $logic_id = sql_insert_id();
        
        return $logic_id;
    }
    
    /**
     * 폼 복제
     * @param int $form_id 원본 폼 ID
     * @param int $user_id 새 폼 소유자 ID
     * @return int 복제된 폼 ID 또는 실패 시 false
     */
    public function duplicate_form($form_id, $user_id) {
        global $cf_table_prefix;
        
        // 원본 폼 정보 조회
        $original_form = $this->get_form($form_id);
        if (!$original_form) {
            return false;
        }
        
        // 새 폼 이름 (복사본 표시)
        $new_name = $original_form['name'] . ' (사본)';
        
        // 폼 복제
        $new_form_data = array(
            'user_id' => $user_id,
            'landing_page_id' => $original_form['landing_page_id'],
            'name' => $new_name,
            'submit_button_text' => $original_form['submit_button_text'],
            'success_message' => $original_form['success_message'],
            'redirect_url' => $original_form['redirect_url'],
            'is_multi_step' => $original_form['is_multi_step'],
            'auto_save_enabled' => $original_form['auto_save_enabled']
        );
        
        $new_form_id = $this->create_form($new_form_data);
        if (!$new_form_id) {
            return false;
        }
        
        // 필드 복제
        $original_fields = $this->get_form_fields($form_id);
        $field_id_map = array(); // 원본 ID -> 새 ID 매핑
        
        foreach ($original_fields as $field) {
            $new_field_data = array(
                'form_id' => $new_form_id,
                'label' => $field['label'],
                'type' => $field['type'],
                'placeholder' => $field['placeholder'],
                'default_value' => $field['default_value'],
                'is_required' => $field['is_required'],
                'validation_rule' => $field['validation_rule'],
                'error_message' => $field['error_message'],
                'step_number' => $field['step_number'],
                'options' => $field['options']
            );
            
            $new_field_id = $this->add_field($new_field_data);
            $field_id_map[$field['id']] = $new_field_id;
        }
        
        // 조건부 로직 복제
        $sql = "SELECT * FROM form_conditional_logic 
                WHERE form_id = " . intval($form_id);
        $result = sql_query($sql);
        
        while ($logic = sql_fetch_array($result)) {
            // 원본 필드 ID가 매핑에 있는지 확인
            if (isset($field_id_map[$logic['source_field_id']]) && isset($field_id_map[$logic['target_field_id']])) {
                $new_logic_data = array(
                    'form_id' => $new_form_id,
                    'source_field_id' => $field_id_map[$logic['source_field_id']],
                    'target_field_id' => $field_id_map[$logic['target_field_id']],
                    'compare_operator' => $logic['compare_operator'],
                    'value' => $logic['value'],
                    'action' => $logic['action']
                );
                
                $this->add_conditional_logic($new_logic_data);
            }
        }
        
        return $new_form_id;
    }
    
    /**
     * 폼 임베드 코드 생성
     * @param int $form_id 폼 ID
     * @return string 임베드 코드
     */
    public function get_embed_code($form_id) {
        $form_url = CF_FORM_URL . '/form_view.php?id=' . intval($form_id);
        
        $iframe_code = '<iframe src="' . $form_url . '" width="100%" height="500" frameborder="0" scrolling="auto"></iframe>';
        $script_code = '<script src="' . CF_FORM_URL . '/form_embed.js" id="cf-form-script" data-form-id="' . intval($form_id) . '"></script>';
        
        return array(
            'iframe' => $iframe_code,
            'script' => $script_code
        );
    }
    
    /**
     * 리드 목록 조회
     * @param int $form_id 폼 ID
     * @param int $offset 시작 위치
     * @param int $limit 개수
     * @param string $search 검색어
     * @return array 리드 목록
     */
    public function get_leads($form_id, $offset = 0, $limit = 20, $search = '') {
        global $cf_table_prefix;
        
        $where = "WHERE form_id = " . intval($form_id);
        
        if (!empty($search)) {
            $where .= " AND data LIKE '%" . sql_escape_string($search) . "%'";
        }
        
        $sql = "SELECT * FROM leads 
                $where
                ORDER BY created_at DESC
                LIMIT $offset, $limit";
        $result = sql_query($sql);
        
        $leads = array();
        while ($row = sql_fetch_array($result)) {
            $leads[] = $row;
        }
        
        return $leads;
    }
    
    /**
     * 리드 개수 조회
     * @param int $form_id 폼 ID
     * @param string $search 검색어
     * @return int 리드 개수
     */
    public function count_leads($form_id, $search = '') {
        global $cf_table_prefix;
        
        $where = "WHERE form_id = " . intval($form_id);
        
        if (!empty($search)) {
            $where .= " AND data LIKE '%" . sql_escape_string($search) . "%'";
        }
        
        $sql = "SELECT COUNT(*) as cnt FROM leads $where";
        $row = sql_fetch($sql);
        
        return $row['cnt'];
    }
}
?>