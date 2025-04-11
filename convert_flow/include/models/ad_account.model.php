<?php
if (!defined('_CONVERT_FLOW_')) exit;

/**
 * 광고 계정 모델 클래스
 * 
 * 광고 계정 관련 데이터 처리를 담당하는 모델 클래스입니다.
 * 구글 애즈, 페이스북, 네이버, 카카오 등 광고 매체별 계정을 관리합니다.
 */
class AdAccountModel {
    /**
     * 사용자의 광고 계정 목록을 가져옵니다.
     * 
     * @param int $user_id 사용자 ID
     * @param array $params 검색 조건
     * @return array 광고 계정 목록
     */
    public function get_user_ad_accounts($user_id, $params = array()) {
        $user_id = intval($user_id);
        
        $where = array("user_id = '{$user_id}'");
        
        // 검색 조건 처리
        if (!empty($params['search_keyword'])) {
            $search_keyword = trim(sql_escape_string($params['search_keyword']));
            $where[] = "(account_name LIKE '%{$search_keyword}%' OR account_id LIKE '%{$search_keyword}%')";
        }
        
        if (!empty($params['platform_id'])) {
            $platform_id = intval($params['platform_id']);
            $where[] = "platform_id = '{$platform_id}'";
        }
        
        if (!empty($params['status'])) {
            $status = sql_escape_string($params['status']);
            $where[] = "status = '{$status}'";
        }
        
        $where_str = implode(' AND ', $where);
        
        // 정렬 조건
        $order_by = "created_at DESC";
        if (!empty($params['sort_field']) && !empty($params['sort_order'])) {
            $sort_field = sql_escape_string($params['sort_field']);
            $sort_order = sql_escape_string($params['sort_order']);
            $order_by = "{$sort_field} {$sort_order}";
        }
        
        // 페이징 처리
        $page = isset($params['page']) ? max(1, intval($params['page'])) : 1;
        $items_per_page = isset($params['items_per_page']) ? intval($params['items_per_page']) : 20;
        $start = ($page - 1) * $items_per_page;
        
        // 전체 광고 계정 수 조회
        $count_sql = "SELECT COUNT(*) as cnt FROM user_ad_accounts WHERE {$where_str}";
        $row = sql_fetch($count_sql);
        $total_count = $row['cnt'];
        
        // 광고 계정 목록 조회
        $sql = "SELECT a.*, p.name as platform_name, p.platform_code 
                FROM user_ad_accounts a
                JOIN ad_platforms p ON a.platform_id = p.id
                WHERE {$where_str} 
                ORDER BY {$order_by} 
                LIMIT {$start}, {$items_per_page}";
        $result = sql_query($sql);
        
        $accounts = array();
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            // 추가 정보 파싱
            if (!empty($row['settings'])) {
                $row['settings_data'] = json_decode($row['settings'], true);
            }
            
            $accounts[] = $row;
        }
        
        return array(
            'accounts' => $accounts,
            'total_count' => $total_count,
            'page' => $page,
            'items_per_page' => $items_per_page,
            'total_pages' => ceil($total_count / $items_per_page)
        );
    }
    
    /**
     * 특정 광고 계정 정보를 가져옵니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param int $user_id 사용자 ID (권한 체크용)
     * @return array|false 광고 계정 정보 또는 false
     */
    public function get_ad_account($account_id, $user_id = null) {
        $account_id = intval($account_id);
        
        $where = "a.id = '{$account_id}'";
        if ($user_id !== null) {
            $user_id = intval($user_id);
            $where .= " AND a.user_id = '{$user_id}'";
        }
        
        $sql = "SELECT a.*, p.name as platform_name, p.platform_code, p.api_endpoint 
                FROM user_ad_accounts a
                JOIN ad_platforms p ON a.platform_id = p.id
                WHERE {$where}";
        $account = sql_fetch($sql);
        
        if (!$account) {
            return false;
        }
        
        // 추가 정보 파싱
        if (!empty($account['settings'])) {
            $account['settings_data'] = json_decode($account['settings'], true);
        }
        
        // 액세스 토큰 정보 가져오기(필요 시)
        $sql = "SELECT * FROM ad_account_tokens 
                WHERE user_ad_account_id = '{$account_id}' 
                ORDER BY expires_at DESC 
                LIMIT 1";
        $token = sql_fetch($sql);
        
        if ($token) {
            $account['token_info'] = $token;
        }
        
        return $account;
    }
    
    /**
     * 사용 가능한 광고 플랫폼 목록을 가져옵니다.
     * 
     * @param bool $active_only 활성화된 플랫폼만 가져올지 여부
     * @return array 광고 플랫폼 목록
     */
    public function get_ad_platforms($active_only = true) {
        $where = '';
        if ($active_only) {
            $where = "WHERE is_active = 1";
        }
        
        $sql = "SELECT * FROM ad_platforms {$where} ORDER BY name ASC";
        $result = sql_query($sql);
        
        $platforms = array();
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            // field_settings JSON 파싱
            if (!empty($row['field_settings'])) {
                $row['field_settings_data'] = json_decode($row['field_settings'], true);
            } else {
                // 기본 필드 설정 - 모든 플랫폼에 공통적인 기본 필드
                $row['field_settings_data'] = $this->get_default_field_settings($row['platform_code']);
            }
            
            $platforms[] = $row;
        }
        
        return $platforms;
    }



    /**
     * 새 광고 플랫폼을 추가합니다.
     * 
     * @param array $data 플랫폼 데이터
     * @return int|false 추가된 플랫폼 ID 또는 false
     */
    public function add_platform($data) {
        // 필수 필드 검증
        if (empty($data['name']) || empty($data['platform_code'])) {
            return false;
        }
        
        $name = sql_escape_string($data['name']);
        $platform_code = sql_escape_string($data['platform_code']);
        $api_endpoint = !empty($data['api_endpoint']) ? "'" . sql_escape_string($data['api_endpoint']) . "'" : "NULL";
        $is_active = isset($data['is_active']) ? intval($data['is_active']) : 1;
        
        // 중복 코드 검사
        $sql = "SELECT COUNT(*) as cnt FROM ad_platforms WHERE platform_code = '{$platform_code}'";
        $row = sql_fetch($sql);
        if ($row['cnt'] > 0) {
            return false; // 이미 존재하는 코드
        }
        
        $sql = "INSERT INTO ad_platforms (
                    name, platform_code, api_endpoint, is_active, created_at, updated_at
                ) VALUES (
                    '{$name}', '{$platform_code}', {$api_endpoint}, '{$is_active}', NOW(), NOW()
                )";
        
        sql_query($sql);
        $platform_id = sql_insert_id();
        
        return $platform_id;
    }

    /**
     * 광고 플랫폼 정보를 수정합니다.
     * 
     * @param int $platform_id 플랫폼 ID
     * @param array $data 수정할 데이터
     * @return bool 성공 여부
     */
    public function update_platform($platform_id, $data) {
        $platform_id = intval($platform_id);
        
        // 기존 플랫폼 정보 확인
        $platform = $this->get_platform($platform_id);
        if (!$platform) {
            return false;
        }
        
        $updates = array();
        
        if (isset($data['name'])) {
            $updates[] = "name = '" . sql_escape_string($data['name']) . "'";
        }
        
        if (isset($data['platform_code'])) {
            // 중복 코드 검사 (자신 제외)
            $platform_code = sql_escape_string($data['platform_code']);
            $sql = "SELECT COUNT(*) as cnt FROM ad_platforms WHERE platform_code = '{$platform_code}' AND id != '{$platform_id}'";
            $row = sql_fetch($sql);
            if ($row['cnt'] > 0) {
                return false; // 이미 존재하는 코드
            }
            
            $updates[] = "platform_code = '" . $platform_code . "'";
        }
        
        if (isset($data['api_endpoint'])) {
            $api_endpoint = !empty($data['api_endpoint']) ? "'" . sql_escape_string($data['api_endpoint']) . "'" : "NULL";
            $updates[] = "api_endpoint = " . $api_endpoint;
        }
        
        if (isset($data['is_active'])) {
            $updates[] = "is_active = '" . intval($data['is_active']) . "'";
        }
        
        $updates[] = "updated_at = NOW()";
        
        if (empty($updates)) {
            return true; // 변경할 내용이 없음
        }
        
        $update_str = implode(', ', $updates);
        $sql = "UPDATE ad_platforms SET {$update_str} WHERE id = '{$platform_id}'";
        
        return sql_query($sql);
    }

    /**
     * 광고 플랫폼을 삭제합니다.
     * 
     * @param int $platform_id 플랫폼 ID
     * @return bool 성공 여부
     */
    public function delete_platform($platform_id) {
        $platform_id = intval($platform_id);
        
        // 플랫폼 정보 가져오기
        $platform = $this->get_platform($platform_id);
        if (!$platform) {
            return false;
        }
        
        // 이 플랫폼을 사용하는 계정이 있는지 확인
        $sql = "SELECT COUNT(*) as cnt FROM user_ad_accounts WHERE platform_id = '{$platform_id}'";
        $row = sql_fetch($sql);
        if ($row['cnt'] > 0) {
            return false; // 사용 중인 플랫폼은 삭제 불가
        }
        
        // 플랫폼 삭제
        $sql = "DELETE FROM ad_platforms WHERE id = '{$platform_id}'";
        return sql_query($sql);
    }


    /**
     * 플랫폼별 기본 필드 설정을 가져옵니다.
     * 
     * @param string $platform_code 플랫폼 코드
     * @return array 기본 필드 설정
     */
    private function get_default_field_settings($platform_code) {
        $default_fields = array(
            // 모든 플랫폼에 공통적인 기본 필드
            array(
                'id' => 'account_id',
                'name' => 'account_id',
                'label' => '계정 ID',
                'type' => 'text',
                'placeholder' => '광고 플랫폼 계정 ID를 입력하세요',
                'description' => '광고 플랫폼에서의 계정 ID입니다',
                'required' => true
            )
        );
        
        // 플랫폼별 특화 기본 필드
        switch ($platform_code) {
            case 'naver':
                return array_merge($default_fields, array(
                    array(
                        'id' => 'customer_id',
                        'name' => 'customer_id',
                        'label' => 'CUSTOMER_ID',
                        'type' => 'text',
                        'placeholder' => '네이버 광고 CUSTOMER_ID를 입력하세요',
                        'description' => '네이버 검색광고 API에서 사용되는 고객 ID입니다',
                        'required' => true
                    ),
                    array(
                        'id' => 'access_license',
                        'name' => 'access_license',
                        'label' => '엑세스 라이선스',
                        'type' => 'text',
                        'placeholder' => '네이버 API 엑세스 라이선스를 입력하세요',
                        'description' => '네이버 개발자 센터에서 발급받은 엑세스 라이선스 키입니다',
                        'required' => true
                    ),
                    array(
                        'id' => 'secret_key',
                        'name' => 'secret_key',
                        'label' => '비밀키',
                        'type' => 'password',
                        'placeholder' => '네이버 API 비밀키를 입력하세요',
                        'description' => '네이버 개발자 센터에서 발급받은 비밀키입니다',
                        'required' => true
                    )
                ));
                
            case 'google_ads':
                return array_merge($default_fields, array(
                    array(
                        'id' => 'tracking_id',
                        'name' => 'tracking_id',
                        'label' => '전환 ID',
                        'type' => 'text',
                        'placeholder' => '예: AW-XXXXXXXXXX',
                        'description' => '구글 애즈 전환 추적을 위한 ID입니다',
                        'required' => false
                    ),
                    array(
                        'id' => 'conversion_label',
                        'name' => 'conversion_label',
                        'label' => '전환 레이블',
                        'type' => 'text',
                        'placeholder' => '예: ABCDEFGHIJ',
                        'description' => '전환 추적을 위한 레이블입니다',
                        'required' => false
                    )
                ));
                
            case 'facebook':
                return array_merge($default_fields, array(
                    array(
                        'id' => 'pixel_id',
                        'name' => 'pixel_id',
                        'label' => '픽셀 ID',
                        'type' => 'text',
                        'placeholder' => '예: 123456789012345',
                        'description' => '페이스북 픽셀 ID입니다',
                        'required' => false
                    ),
                    array(
                        'id' => 'access_token',
                        'name' => 'access_token',
                        'label' => '액세스 토큰',
                        'type' => 'textarea',
                        'placeholder' => '페이스북 API 액세스 토큰을 입력하세요',
                        'description' => '장기 액세스 토큰을 입력해주세요',
                        'required' => true
                    )
                ));
                
            case 'kakao':
                return array_merge($default_fields, array(
                    array(
                        'id' => 'api_key',
                        'name' => 'api_key',
                        'label' => 'REST API 키',
                        'type' => 'text',
                        'placeholder' => '카카오 개발자 센터에서 발급받은 REST API 키를 입력하세요',
                        'description' => '카카오 API 사용을 위한 키입니다',
                        'required' => true
                    ),
                    array(
                        'id' => 'tracking_id',
                        'name' => 'tracking_id',
                        'label' => '픽셀 ID',
                        'type' => 'text',
                        'placeholder' => '예: XXXXXXXXXX',
                        'description' => '카카오 픽셀 ID입니다',
                        'required' => false
                    )
                ));
                
            default:
                return $default_fields;
        }
    }


    /**
     * 플랫폼별 필드 설정을 업데이트합니다.
     * 
     * @param int $platform_id 플랫폼 ID
     * @param array $field_settings 필드 설정 데이터
     * @return bool 성공 여부
     */
    public function update_platform_field_settings($platform_id, $field_settings) {
        $platform_id = intval($platform_id);
        $field_settings_json = json_encode($field_settings, JSON_UNESCAPED_UNICODE);
        
        $sql = "UPDATE ad_platforms SET field_settings = '" . sql_escape_string($field_settings_json) . "', updated_at = NOW() WHERE id = '{$platform_id}'";
        return sql_query($sql);
    }
    
    /**
     * 광고 계정을 추가합니다.
     * 
     * @param array $data 광고 계정 데이터
     * @return int|false 추가된 광고 계정 ID 또는 false
     */
    public function add_ad_account($data) {
        // 필수 필드 검증
        if (empty($data['user_id']) || empty($data['platform_id']) || 
            empty($data['account_name']) || empty($data['account_id'])) {
            return false;
        }
        
        $user_id = intval($data['user_id']);
        $platform_id = intval($data['platform_id']);
        $account_name = sql_escape_string($data['account_name']);
        $account_id = sql_escape_string($data['account_id']);
        
        // 선택적 필드 설정
        $access_token = !empty($data['access_token']) ? "'" . sql_escape_string($data['access_token']) . "'" : "NULL";
        $refresh_token = !empty($data['refresh_token']) ? "'" . sql_escape_string($data['refresh_token']) . "'" : "NULL";
        $token_expires_at = !empty($data['token_expires_at']) ? "'" . sql_escape_string($data['token_expires_at']) . "'" : "NULL";
        $api_key = !empty($data['api_key']) ? "'" . sql_escape_string($data['api_key']) . "'" : "NULL";
        $status = isset($data['status']) ? "'" . sql_escape_string($data['status']) . "'" : "'활성'";
        
        // 중복 계정 확인
        $sql = "SELECT COUNT(*) as cnt FROM user_ad_accounts 
                WHERE user_id = '{$user_id}' AND platform_id = '{$platform_id}' AND account_id = '{$account_id}'";
        $row = sql_fetch($sql);
        
        if ($row['cnt'] > 0) {
            return false; // 이미 등록된 계정
        }
        
        // 추가 설정 데이터 JSON 처리
        $settings = array();
        if (!empty($data['settings']) && is_array($data['settings'])) {
            $settings = $data['settings'];
        }
        $settings_json = !empty($settings) ? "'" . sql_escape_string(json_encode($settings, JSON_UNESCAPED_UNICODE)) . "'" : "NULL";
        
        $sql = "INSERT INTO user_ad_accounts (
                    user_id, platform_id, account_name, account_id, 
                    access_token, refresh_token, token_expires_at, api_key, 
                    status, settings, created_at, updated_at
                ) VALUES (
                    '{$user_id}', '{$platform_id}', '{$account_name}', '{$account_id}', 
                    {$access_token}, {$refresh_token}, {$token_expires_at}, {$api_key}, 
                    {$status}, {$settings_json}, NOW(), NOW()
                )";
        
        sql_query($sql);
        $account_id = sql_insert_id();
        
        // 토큰 데이터 추가
        if ($account_id && !empty($data['access_token'])) {
            $this->add_account_token($account_id, 'access_token', $data['access_token'], $data['token_expires_at']);
        }
        
        // 로그 기록
        if ($account_id) {
            write_log('ad_account', 'add_account', array(
                'account_id' => $account_id,
                'user_id' => $user_id,
                'platform_id' => $platform_id,
                'account_name' => $account_name
            ));
        }
        
        return $account_id;
    }
    
    /**
     * 광고 계정 토큰을 추가합니다.
     * 
     * @param int $user_ad_account_id 광고 계정 ID
     * @param string $token_type 토큰 유형
     * @param string $token_value 토큰 값
     * @param string $expires_at 만료 시간
     * @return int|false 추가된 토큰 ID 또는 false
     */
    public function add_account_token($user_ad_account_id, $token_type, $token_value, $expires_at = null) {
        $user_ad_account_id = intval($user_ad_account_id);
        $token_type = sql_escape_string($token_type);
        $token_value = sql_escape_string($token_value);
        $expires_at = $expires_at ? "'" . sql_escape_string($expires_at) . "'" : "NULL";
        
        $sql = "INSERT INTO ad_account_tokens (
                    user_ad_account_id, token_type, token_value, expires_at, created_at, updated_at
                ) VALUES (
                    '{$user_ad_account_id}', '{$token_type}', '{$token_value}', {$expires_at}, NOW(), NOW()
                )";
        
        sql_query($sql);
        return sql_insert_id();
    }
    
    /**
     * 광고 계정을 수정합니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param array $data 수정할 데이터
     * @return bool 성공 여부
     */
    public function update_ad_account($account_id, $data) {
        $account_id = intval($account_id);
        
        // 기존 광고 계정 정보 확인
        $account = $this->get_ad_account($account_id);
        if (!$account) {
            return false;
        }
        
        $updates = array();
        
        if (isset($data['account_name'])) {
            $updates[] = "account_name = '" . sql_escape_string($data['account_name']) . "'";
        }
        
        if (isset($data['status'])) {
            $updates[] = "status = '" . sql_escape_string($data['status']) . "'";
        }
        
        if (isset($data['access_token'])) {
            $updates[] = "access_token = '" . sql_escape_string($data['access_token']) . "'";
            
            // 토큰 만료일 업데이트
            if (isset($data['token_expires_at'])) {
                $updates[] = "token_expires_at = '" . sql_escape_string($data['token_expires_at']) . "'";
                
                // 토큰 테이블에도 추가
                $this->add_account_token($account_id, 'access_token', $data['access_token'], $data['token_expires_at']);
            }
        }
        
        if (isset($data['refresh_token'])) {
            $updates[] = "refresh_token = '" . sql_escape_string($data['refresh_token']) . "'";
            
            // 토큰 테이블에도 추가
            $this->add_account_token($account_id, 'refresh_token', $data['refresh_token']);
        }
        
        if (isset($data['api_key'])) {
            $updates[] = "api_key = '" . sql_escape_string($data['api_key']) . "'";
        }
        
        // 설정 데이터 업데이트
        if (isset($data['settings']) && is_array($data['settings'])) {
            // 기존 설정 데이터 병합
            $current_settings = array();
            if (!empty($account['settings'])) {
                $current_settings = json_decode($account['settings'], true);
            }
            
            $merged_settings = array_merge($current_settings, $data['settings']);
            $settings_json = json_encode($merged_settings, JSON_UNESCAPED_UNICODE);
            
            $updates[] = "settings = '" . sql_escape_string($settings_json) . "'";
        }
        
        $updates[] = "updated_at = NOW()";
        
        if (empty($updates)) {
            return true; // 변경할 내용이 없음
        }
        
        $update_str = implode(', ', $updates);
        $sql = "UPDATE user_ad_accounts SET {$update_str} WHERE id = '{$account_id}'";
        
        $result = sql_query($sql);
        
        // 로그 기록
        if ($result) {
            write_log('ad_account', 'update_account', array(
                'account_id' => $account_id,
                'updates' => $updates
            ));
        }
        
        return $result;
    }
    
    /**
     * 광고 계정 상태를 변경합니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param string $status 새 상태 (활성, 비활성, 인증필요)
     * @return bool 성공 여부
     */
    public function change_account_status($account_id, $status) {
        $account_id = intval($account_id);
        $status = sql_escape_string($status);
        
        $sql = "UPDATE user_ad_accounts SET status = '{$status}', updated_at = NOW() WHERE id = '{$account_id}'";
        $result = sql_query($sql);
        
        // 로그 기록
        if ($result) {
            write_log('ad_account', 'change_status', array(
                'account_id' => $account_id,
                'status' => $status
            ));
        }
        
        return $result;
    }
    
    /**
     * 광고 계정을 삭제합니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @return bool 성공 여부
     */
    public function delete_ad_account($account_id) {
        $account_id = intval($account_id);
        
        // 계정 정보 가져오기 (로그용)
        $account = $this->get_ad_account($account_id);
        
        if (!$account) {
            return false;
        }
        
        // 계정에 연결된 토큰 삭제
        $sql = "DELETE FROM ad_account_tokens WHERE user_ad_account_id = '{$account_id}'";
        sql_query($sql);
        
        // 광고 계정 삭제
        $sql = "DELETE FROM user_ad_accounts WHERE id = '{$account_id}'";
        $result = sql_query($sql);
        
        // 로그 기록
        if ($result) {
            write_log('ad_account', 'delete_account', array(
                'account_id' => $account_id,
                'user_id' => $account['user_id'],
                'platform_id' => $account['platform_id'],
                'account_name' => $account['account_name']
            ));
        }
        
        return $result;
    }
    
    /**
     * 광고 계정 토큰을 갱신합니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param string $refresh_token 리프레시 토큰
     * @return bool 성공 여부
     */
    public function refresh_account_token($account_id) {
        $account_id = intval($account_id);
        
        // 계정 정보 가져오기
        $account = $this->get_ad_account($account_id);
        
        if (!$account || empty($account['refresh_token'])) {
            return false;
        }
        
        // 광고 플랫폼에 따라 토큰 갱신 로직 처리
        $platform_code = $account['platform_code'];
        $refresh_token = $account['refresh_token'];
        
        switch ($platform_code) {
            case 'google':
                $result = $this->refresh_google_token($account_id, $refresh_token);
                break;
                
            case 'facebook':
                $result = $this->refresh_facebook_token($account_id, $refresh_token);
                break;
                
            case 'naver':
                $result = $this->refresh_naver_token($account_id, $refresh_token);
                break;
                
            case 'kakao':
                $result = $this->refresh_kakao_token($account_id, $refresh_token);
                break;
                
            default:
                $result = false;
        }
        
        // 토큰 갱신 시간 업데이트
        if ($result) {
            sql_query("UPDATE user_ad_accounts SET last_synced_at = NOW() WHERE id = '{$account_id}'");
        }
        
        return $result;
    }
    
    /**
     * 구글 애즈 토큰을 갱신합니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param string $refresh_token 리프레시 토큰
     * @return bool 성공 여부
     */
    private function refresh_google_token($account_id, $refresh_token) {
        // 구글 OAuth 클라이언트 ID와 시크릿 설정
        $client_id = '구글_클라이언트_ID';
        $client_secret = '구글_클라이언트_시크릿';
        
        // 토큰 갱신 요청 데이터
        $post_data = array(
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token'
        );
        
        // API 요청
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // 응답 파싱
        if ($http_code == 200) {
            $token_data = json_decode($response, true);
            
            if (!empty($token_data['access_token'])) {
                // 새 액세스 토큰 저장
                $access_token = $token_data['access_token'];
                $expires_in = $token_data['expires_in']; // 일반적으로 3600초(1시간)
                
                // 만료 시간 계산
                $expires_at = date('Y-m-d H:i:s', time() + $expires_in);
                
                // 계정 정보 업데이트
                $update_data = array(
                    'access_token' => $access_token,
                    'token_expires_at' => $expires_at
                );
                
                $this->update_ad_account($account_id, $update_data);
                
                // 토큰 테이블에 기록
                $this->add_account_token($account_id, 'access_token', $access_token, $expires_at);
                
                // API 호출 로그
                write_log('api', 'google_token_refresh', array(
                    'account_id' => $account_id,
                    'http_code' => $http_code,
                    'result' => 'success'
                ));
                
                return true;
            }
        }
        
        // 실패 로그
        write_log('api', 'google_token_refresh', array(
            'account_id' => $account_id,
            'http_code' => $http_code,
            'response' => $response,
            'result' => 'fail'
        ));
        
        return false;
    }
    
    /**
     * 페이스북 토큰을 갱신합니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param string $refresh_token 리프레시 토큰
     * @return bool 성공 여부
     */
    private function refresh_facebook_token($account_id, $refresh_token) {
        // 페이스북 앱 ID와 시크릿 설정
        $app_id = '페이스북_앱_ID';
        $app_secret = '페이스북_앱_시크릿';
        
        // 토큰 갱신 요청 데이터
        $post_data = array(
            'grant_type' => 'fb_exchange_token',
            'client_id' => $app_id,
            'client_secret' => $app_secret,
            'fb_exchange_token' => $refresh_token
        );
        
        // API 요청
        $ch = curl_init('https://graph.facebook.com/v18.0/oauth/access_token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // 응답 파싱
        if ($http_code == 200) {
            $token_data = json_decode($response, true);
            
            if (!empty($token_data['access_token'])) {
                // 새 액세스 토큰 저장
                $access_token = $token_data['access_token'];
                
                // 장기 토큰은 일반적으로 60일 유효
                $expires_at = date('Y-m-d H:i:s', strtotime('+60 days'));
                
                // 계정 정보 업데이트
                $update_data = array(
                    'access_token' => $access_token,
                    'token_expires_at' => $expires_at
                );
                
                $this->update_ad_account($account_id, $update_data);
                
                // 토큰 테이블에 기록
                $this->add_account_token($account_id, 'access_token', $access_token, $expires_at);
                
                // API 호출 로그
                write_log('api', 'facebook_token_refresh', array(
                    'account_id' => $account_id,
                    'http_code' => $http_code,
                    'result' => 'success'
                ));
                
                return true;
            }
        }
        
        // 실패 로그
        write_log('api', 'facebook_token_refresh', array(
            'account_id' => $account_id,
            'http_code' => $http_code,
            'response' => $response,
            'result' => 'fail'
        ));
        
        return false;
    }
    
    /**
     * 네이버 토큰을 갱신합니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param string $refresh_token 리프레시 토큰
     * @return bool 성공 여부
     */
    private function refresh_naver_token($account_id, $refresh_token) {
        // 네이버 클라이언트 ID와 시크릿 설정
        $client_id = '네이버_클라이언트_ID';
        $client_secret = '네이버_클라이언트_시크릿';
        
        // 토큰 갱신 요청 데이터
        $post_data = array(
            'grant_type' => 'refresh_token',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refresh_token
        );
        
        // API 요청
        $ch = curl_init('https://nid.naver.com/oauth2.0/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // 응답 파싱
        if ($http_code == 200) {
            $token_data = json_decode($response, true);
            
            if (!empty($token_data['access_token'])) {
                // 새 액세스 토큰 저장
                $access_token = $token_data['access_token'];
                $expires_in = $token_data['expires_in']; // 일반적으로 3600초(1시간)
                
                // 만료 시간 계산
                $expires_at = date('Y-m-d H:i:s', time() + $expires_in);
                
                // 계정 정보 업데이트
                $update_data = array(
                    'access_token' => $access_token,
                    'token_expires_at' => $expires_at
                );
                
                $this->update_ad_account($account_id, $update_data);
                
                // 토큰 테이블에 기록
                $this->add_account_token($account_id, 'access_token', $access_token, $expires_at);
                
                // API 호출 로그
                write_log('api', 'naver_token_refresh', array(
                    'account_id' => $account_id,
                    'http_code' => $http_code,
                    'result' => 'success'
                ));
                
                return true;
            }
        }
        
        // 실패 로그
        write_log('api', 'naver_token_refresh', array(
            'account_id' => $account_id,
            'http_code' => $http_code,
            'response' => $response,
            'result' => 'fail'
        ));
        
        return false;
    }
    
    /**
     * 카카오 토큰을 갱신합니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param string $refresh_token 리프레시 토큰
     * @return bool 성공 여부
     */
    private function refresh_kakao_token($account_id, $refresh_token) {
        // 카카오 앱 키
        $app_key = '카카오_앱_키';
        
        // 토큰 갱신 요청 데이터
        $post_data = array(
            'grant_type' => 'refresh_token',
            'client_id' => $app_key,
            'refresh_token' => $refresh_token
        );
        
        // API 요청
        $ch = curl_init('https://kauth.kakao.com/oauth/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // 응답 파싱
        if ($http_code == 200) {
            $token_data = json_decode($response, true);
            
            if (!empty($token_data['access_token'])) {
                // 새 액세스 토큰 저장
                $access_token = $token_data['access_token'];
                $expires_in = $token_data['expires_in']; // 일반적으로 21600초(6시간)
                
                // 새 리프레시 토큰이 있으면 저장
                $new_refresh_token = !empty($token_data['refresh_token']) ? $token_data['refresh_token'] : $refresh_token;
                
                // 만료 시간 계산
                $expires_at = date('Y-m-d H:i:s', time() + $expires_in);
                
                // 계정 정보 업데이트
                $update_data = array(
                    'access_token' => $access_token,
                    'refresh_token' => $new_refresh_token,
                    'token_expires_at' => $expires_at
                );
                
                $this->update_ad_account($account_id, $update_data);
                
                // 토큰 테이블에 기록
                $this->add_account_token($account_id, 'access_token', $access_token, $expires_at);
                
                // API 호출 로그
                write_log('api', 'kakao_token_refresh', array(
                    'account_id' => $account_id,
                    'http_code' => $http_code,
                    'result' => 'success'
                ));
                
                return true;
            }
        }
        
        // 실패 로그
        write_log('api', 'kakao_token_refresh', array(
            'account_id' => $account_id,
            'http_code' => $http_code,
            'response' => $response,
            'result' => 'fail'
        ));
        
        return false;
    }
    
    /**
     * 광고 계정 데이터를 동기화합니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param array $params 동기화 옵션
     * @return bool 성공 여부
     */
    public function sync_account_data($account_id, $params = array()) {
        $account_id = intval($account_id);
        
        // 계정 정보 가져오기
        $account = $this->get_ad_account($account_id);
        
        if (!$account) {
            return false;
        }
        
        // 토큰 유효성 확인
        if (!empty($account['token_expires_at']) && strtotime($account['token_expires_at']) < time()) {
            // 토큰 갱신 시도
            $this->refresh_account_token($account_id);
            
            // 계정 정보 새로 가져오기
            $account = $this->get_ad_account($account_id);
        }
        
        // 광고 플랫폼에 따라 동기화 로직 처리
        $platform_code = $account['platform_code'];
        
        // 동기화 작업 생성
        $job_data = array(
            'user_id' => $account['user_id'],
            'user_ad_account_id' => $account_id,
            'job_type' => !empty($params['job_type']) ? $params['job_type'] : '캠페인가져오기',
            'status' => '대기중',
            'start_date' => !empty($params['start_date']) ? $params['start_date'] : date('Y-m-d', strtotime('-30 days')),
            'end_date' => !empty($params['end_date']) ? $params['end_date'] : date('Y-m-d'),
            'parameters' => !empty($params) ? json_encode($params, JSON_UNESCAPED_UNICODE) : NULL
        );
        
        $sql = "INSERT INTO api_sync_jobs (
                    user_id, user_ad_account_id, job_type, status,
                    start_date, end_date, parameters, created_at
                ) VALUES (
                    '{$job_data['user_id']}', 
                    '{$job_data['user_ad_account_id']}', 
                    '{$job_data['job_type']}', 
                    '{$job_data['status']}',
                    '{$job_data['start_date']}', 
                    '{$job_data['end_date']}', 
                    " . ($job_data['parameters'] ? "'" . sql_escape_string($job_data['parameters']) . "'" : "NULL") . ",
                    NOW()
                )";
        
        sql_query($sql);
        $job_id = sql_insert_id();
        
        if (!$job_id) {
            return false;
        }
        
        // 동기화 작업을 비동기로 처리하기 위해
        // 여기서는 작업을 생성하고 백그라운드 처리를 위한 기능만 구현
        
        // 로그 기록
        write_log('api', 'sync_job_created', array(
            'job_id' => $job_id,
            'account_id' => $account_id,
            'job_type' => $job_data['job_type']
        ));
        
        return true;
    }
    
    /**
     * 광고 계정 활동 로그를 가져옵니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param int $limit 조회할 최대 개수
     * @param int $offset 시작 위치 (페이징)
     * @return array 로그 목록
     */
    public function get_account_logs($account_id, $limit = 100, $offset = 0) {
        $account_id = intval($account_id);
        $limit = intval($limit);
        $offset = intval($offset);
        
        $sql = "SELECT * FROM api_logs 
                WHERE user_ad_account_id = '{$account_id}' 
                ORDER BY created_at DESC 
                LIMIT {$offset}, {$limit}";
        
        $result = sql_query($sql);
        
        $logs = array();
        while ($row = sql_fetch_array($result)) {
            $logs[] = $row;
        }
        
        return $logs;
    }
    
    /**
     * 광고 계정 동기화 작업 목록을 가져옵니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param int $limit 조회할 최대 개수
     * @param int $offset 시작 위치 (페이징)
     * @return array 작업 목록
     */
    public function get_account_sync_jobs($account_id, $limit = 20, $offset = 0) {
        $account_id = intval($account_id);
        $limit = intval($limit);
        $offset = intval($offset);
        
        $sql = "SELECT * FROM api_sync_jobs 
                WHERE user_ad_account_id = '{$account_id}' 
                ORDER BY created_at DESC 
                LIMIT {$offset}, {$limit}";
        
        $result = sql_query($sql);
        
        $jobs = array();
        while ($row = sql_fetch_array($result)) {
            // 파라미터 JSON 변환
            if (!empty($row['parameters'])) {
                $row['parameters_data'] = json_decode($row['parameters'], true);
            }
            
            // 결과 요약 JSON 변환
            if (!empty($row['result_summary'])) {
                $row['result_data'] = json_decode($row['result_summary'], true);
            }
            
            $jobs[] = $row;
        }
        
        return $jobs;
    }
    
    /**
     * 광고 계정에 API 테스트를 수행합니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @return array 테스트 결과
     */
    public function test_api_connection($account_id) {
        $account_id = intval($account_id);
        
        // 계정 정보 가져오기
        $account = $this->get_ad_account($account_id);
        
        if (!$account) {
            return array(
                'success' => false,
                'message' => '유효하지 않은 광고 계정입니다.'
            );
        }
        
        // 광고 플랫폼에 따라 API 테스트 로직 처리
        $platform_code = $account['platform_code'];
        $api_endpoint = $account['api_endpoint'];
        $access_token = $account['access_token'];
        
        // 응답을 저장할 변수
        $result = array(
            'success' => false,
            'message' => '',
            'data' => null,
            'account_info' => array(
                'name' => $account['account_name'],
                'platform' => $account['platform_name'],
                'status' => $account['status']
            )
        );
        
        // 플랫폼별 API 테스트
        switch ($platform_code) {
            case 'google':
                $result = $this->test_google_api($account);
                break;
                
            case 'facebook':
                $result = $this->test_facebook_api($account);
                break;
                
            case 'naver':
                $result = $this->test_naver_api($account);
                break;
                
            case 'kakao':
                $result = $this->test_kakao_api($account);
                break;
                
            default:
                $result['message'] = '지원되지 않는 플랫폼입니다.';
        }
        
        // 로그 기록
        write_log('api', 'api_connection_test', array(
            'account_id' => $account_id,
            'platform' => $platform_code,
            'result' => $result['success'] ? 'success' : 'fail',
            'message' => $result['message']
        ));
        
        return $result;
    }
    
    /**
     * 구글 애즈 API 테스트를 수행합니다.
     * 
     * @param array $account 광고 계정 정보
     * @return array 테스트 결과
     */
    private function test_google_api($account) {
        $result = array(
            'success' => false,
            'message' => '',
            'data' => null
        );
        
        if (empty($account['access_token'])) {
            $result['message'] = '액세스 토큰이 없습니다. 계정 인증이 필요합니다.';
            return $result;
        }
        
        // 계정 정보 API 호출
        $url = 'https://googleads.googleapis.com/v14/customers:listAccessibleCustomers';
        
        $headers = array(
            'Authorization: Bearer ' . $account['access_token'],
            'developer-token: ' . 'DEV_TOKEN', // 실제 개발자 토큰으로 교체 필요
            'Content-Type: application/json'
        );
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // API 호출 로그
        $log_data = array(
            'platform_id' => $account['platform_id'],
            'user_ad_account_id' => $account['id'],
            'request_type' => 'GET',
            'endpoint' => $url,
            'request_headers' => json_encode($headers),
            'response_code' => $http_code,
            'response_body' => $response,
            'execution_time' => 0
        );
        
        $this->log_api_call($log_data);
        
        // 응답 처리
        if ($http_code == 200) {
            $response_data = json_decode($response, true);
            
            if (isset($response_data['resourceNames']) && !empty($response_data['resourceNames'])) {
                $result['success'] = true;
                $result['message'] = '구글 애즈 API 연결에 성공했습니다.';
                $result['data'] = $response_data;
            } else {
                $result['message'] = '접근 가능한 광고 계정이 없습니다.';
            }
        } else {
            $error_data = json_decode($response, true);
            $result['message'] = '구글 애즈 API 연결에 실패했습니다. HTTP 코드: ' . $http_code;
            
            if (!empty($error_data['error']['message'])) {
                $result['message'] .= ' (' . $error_data['error']['message'] . ')';
            }
        }
        
        return $result;
    }
    
    /**
     * 페이스북 API 테스트를 수행합니다.
     * 
     * @param array $account 광고 계정 정보
     * @return array 테스트 결과
     */
    private function test_facebook_api($account) {
        $result = array(
            'success' => false,
            'message' => '',
            'data' => null
        );
        
        if (empty($account['access_token'])) {
            $result['message'] = '액세스 토큰이 없습니다. 계정 인증이 필요합니다.';
            return $result;
        }
        
        // 계정 정보 API 호출
        $url = 'https://graph.facebook.com/v18.0/me/adaccounts?fields=name,account_id,account_status&access_token=' . urlencode($account['access_token']);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // API 호출 로그
        $log_data = array(
            'platform_id' => $account['platform_id'],
            'user_ad_account_id' => $account['id'],
            'request_type' => 'GET',
            'endpoint' => $url,
            'response_code' => $http_code,
            'response_body' => $response,
            'execution_time' => 0
        );
        
        $this->log_api_call($log_data);
        
        // 응답 처리
        if ($http_code == 200) {
            $response_data = json_decode($response, true);
            
            if (isset($response_data['data']) && !empty($response_data['data'])) {
                $result['success'] = true;
                $result['message'] = '페이스북 API 연결에 성공했습니다.';
                $result['data'] = $response_data;
            } else {
                $result['message'] = '접근 가능한 광고 계정이 없습니다.';
            }
        } else {
            $error_data = json_decode($response, true);
            $result['message'] = '페이스북 API 연결에 실패했습니다. HTTP 코드: ' . $http_code;
            
            if (!empty($error_data['error']['message'])) {
                $result['message'] .= ' (' . $error_data['error']['message'] . ')';
            }
        }
        
        return $result;
    }
    
    /**
     * 네이버 API 테스트를 수행합니다.
     * 
     * @param array $account 광고 계정 정보
     * @return array 테스트 결과
     */
    private function test_naver_api($account) {
        $result = array(
            'success' => false,
            'message' => '',
            'data' => null
        );
        
        if (empty($account['access_token'])) {
            $result['message'] = '액세스 토큰이 없습니다. 계정 인증이 필요합니다.';
            return $result;
        }
        
        // 네이버 광고 API 엔드포인트는 네이버 광고 API 사용 가이드에 따라 변경해야 함
        $url = 'https://api.naver.com/ncc/campaigns';
        
        $headers = array(
            'Authorization: Bearer ' . $account['access_token'],
            'Content-Type: application/json'
        );
        
        // 네이버 광고 계정 ID 추가 (API 사용 방식에 따라 변경 필요)
        if (!empty($account['account_id'])) {
            $headers[] = 'X-API-KEY: ' . $account['account_id'];
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // API 호출 로그
        $log_data = array(
            'platform_id' => $account['platform_id'],
            'user_ad_account_id' => $account['id'],
            'request_type' => 'GET',
            'endpoint' => $url,
            'request_headers' => json_encode($headers),
            'response_code' => $http_code,
            'response_body' => $response,
            'execution_time' => 0
        );
        
        $this->log_api_call($log_data);
        
        // 응답 처리
        if ($http_code == 200) {
            $response_data = json_decode($response, true);
            
            $result['success'] = true;
            $result['message'] = '네이버 광고 API 연결에 성공했습니다.';
            $result['data'] = $response_data;
        } else {
            $error_data = json_decode($response, true);
            $result['message'] = '네이버 광고 API 연결에 실패했습니다. HTTP 코드: ' . $http_code;
            
            if (!empty($error_data['code'])) {
                $result['message'] .= ' (에러 코드: ' . $error_data['code'] . ')';
            }
        }
        
        return $result;
    }
    
    /**
     * 카카오 API 테스트를 수행합니다.
     * 
     * @param array $account 광고 계정 정보
     * @return array 테스트 결과
     */
    private function test_kakao_api($account) {
        $result = array(
            'success' => false,
            'message' => '',
            'data' => null
        );
        
        if (empty($account['access_token'])) {
            $result['message'] = '액세스 토큰이 없습니다. 계정 인증이 필요합니다.';
            return $result;
        }
        
        // 카카오 모먼트(광고 API) 엔드포인트
        $url = 'https://apis.moment.kakao.com/openapi/v5/adaccounts';
        
        $headers = array(
            'Authorization: Bearer ' . $account['access_token'],
            'Content-Type: application/json'
        );
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // API 호출 로그
        $log_data = array(
            'platform_id' => $account['platform_id'],
            'user_ad_account_id' => $account['id'],
            'request_type' => 'GET',
            'endpoint' => $url,
            'request_headers' => json_encode($headers),
            'response_code' => $http_code,
            'response_body' => $response,
            'execution_time' => 0
        );
        
        $this->log_api_call($log_data);
        
        // 응답 처리
        if ($http_code == 200) {
            $response_data = json_decode($response, true);
            
            if (isset($response_data['adAccounts'])) {
                $result['success'] = true;
                $result['message'] = '카카오 모먼트 API 연결에 성공했습니다.';
                $result['data'] = $response_data;
            } else {
                $result['message'] = '접근 가능한 광고 계정이 없습니다.';
            }
        } else {
            $error_data = json_decode($response, true);
            $result['message'] = '카카오 모먼트 API 연결에 실패했습니다. HTTP 코드: ' . $http_code;
            
            if (!empty($error_data['msg'])) {
                $result['message'] .= ' (' . $error_data['msg'] . ')';
            }
        }
        
        return $result;
    }
    
    /**
     * API 호출 로그를 저장합니다.
     * 
     * @param array $log_data 로그 데이터
     * @return int|false 로그 ID 또는 false
     */
    private function log_api_call($log_data) {
        // 필수 필드 검증
        if (empty($log_data['platform_id']) || empty($log_data['endpoint'])) {
            return false;
        }
        
        $platform_id = intval($log_data['platform_id']);
        $user_ad_account_id = !empty($log_data['user_ad_account_id']) ? intval($log_data['user_ad_account_id']) : "NULL";
        $request_type = !empty($log_data['request_type']) ? "'" . sql_escape_string($log_data['request_type']) . "'" : "'GET'";
        $endpoint = "'" . sql_escape_string($log_data['endpoint']) . "'";
        
        $request_headers = !empty($log_data['request_headers']) ? "'" . sql_escape_string($log_data['request_headers']) . "'" : "NULL";
        $request_body = !empty($log_data['request_body']) ? "'" . sql_escape_string($log_data['request_body']) . "'" : "NULL";
        $response_code = !empty($log_data['response_code']) ? intval($log_data['response_code']) : "NULL";
        
        // 응답 내용이 너무 길면 자르기
        $response_body = !empty($log_data['response_body']) ? $log_data['response_body'] : "";
        if (strlen($response_body) > 10000) {
            $response_body = substr($response_body, 0, 10000) . '...';
        }
        $response_body = !empty($response_body) ? "'" . sql_escape_string($response_body) . "'" : "NULL";
        
        $execution_time = !empty($log_data['execution_time']) ? floatval($log_data['execution_time']) : 0;
        
        $sql = "INSERT INTO api_logs (
                    platform_id, user_ad_account_id, request_type, endpoint,
                    request_headers, request_body, response_code, response_body,
                    execution_time, created_at
                ) VALUES (
                    '{$platform_id}', {$user_ad_account_id}, {$request_type}, {$endpoint},
                    {$request_headers}, {$request_body}, {$response_code}, {$response_body},
                    '{$execution_time}', NOW()
                )";
        
        sql_query($sql);
        return sql_insert_id();
    }
    
    /**
     * 광고 계정별 통계 정보를 가져옵니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param string $start_date 시작일 (YYYY-MM-DD)
     * @param string $end_date 종료일 (YYYY-MM-DD)
     * @return array 통계 데이터
     */
    public function get_account_statistics($account_id, $start_date = null, $end_date = null) {
        $account_id = intval($account_id);
        
        // 기본값: 최근 30일
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $start_date = sql_escape_string($start_date);
        $end_date = sql_escape_string($end_date);
        
        // 광고 계정에 연결된 캠페인 ID 목록 조회
        $sql = "SELECT DISTINCT campaign_id 
                FROM external_campaigns 
                WHERE user_ad_account_id = '{$account_id}'";
        $result = sql_query($sql);
        
        $campaign_ids = array();
        while ($row = sql_fetch_array($result)) {
            $campaign_ids[] = $row['campaign_id'];
        }
        
        // 통계를 저장할 배열
        $statistics = array(
            'impressions' => 0,
            'clicks' => 0,
            'cost' => 0,
            'conversions' => 0,
            'conversion_value' => 0,
            'campaigns_count' => count($campaign_ids),
            'start_date' => $start_date,
            'end_date' => $end_date
        );
        
        // 캠페인이 없으면 빈 통계 반환
        if (empty($campaign_ids)) {
            return $statistics;
        }
        
        $campaign_ids_str = implode(',', $campaign_ids);
        
        // 비용 및 클릭 데이터
        $sql = "SELECT 
                    SUM(impressions) as total_impressions,
                    SUM(clicks) as total_clicks,
                    SUM(ad_cost) as total_cost
                FROM ad_costs
                WHERE campaign_id IN ({$campaign_ids_str})
                AND cost_date BETWEEN '{$start_date}' AND '{$end_date}'";
        $cost_row = sql_fetch($sql);
        
        // 전환 데이터
        $sql = "SELECT 
                    COUNT(id) as total_conversions,
                    SUM(conversion_value) as total_value
                FROM conversion_events_log
                WHERE campaign_id IN ({$campaign_ids_str})
                AND DATE(event_time) BETWEEN '{$start_date}' AND '{$end_date}'";
        $conversion_row = sql_fetch($sql);
        
        // 통계 데이터 병합
        $statistics['impressions'] = (int)$cost_row['total_impressions'] ?: 0;
        $statistics['clicks'] = (int)$cost_row['total_clicks'] ?: 0;
        $statistics['cost'] = (float)$cost_row['total_cost'] ?: 0;
        $statistics['conversions'] = (int)$conversion_row['total_conversions'] ?: 0;
        $statistics['conversion_value'] = (float)$conversion_row['total_value'] ?: 0;
        
        // 계산된 지표 추가
        $statistics['ctr'] = $statistics['impressions'] > 0 ? ($statistics['clicks'] / $statistics['impressions']) * 100 : 0;
        $statistics['cpc'] = $statistics['clicks'] > 0 ? $statistics['cost'] / $statistics['clicks'] : 0;
        $statistics['cpa'] = $statistics['conversions'] > 0 ? $statistics['cost'] / $statistics['conversions'] : 0;
        $statistics['roas'] = $statistics['cost'] > 0 ? ($statistics['conversion_value'] / $statistics['cost']) * 100 : 0;
        
        return $statistics;
    }

    /**
     * 일별 계정 통계 데이터를 가져옵니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param string $start_date 시작일 (YYYY-MM-DD)
     * @param string $end_date 종료일 (YYYY-MM-DD)
     * @return array 일별 통계 데이터
     */
    public function get_daily_account_statistics($account_id, $start_date = null, $end_date = null) {
        $account_id = intval($account_id);
        
        // 기본값: 최근 30일
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $start_date = sql_escape_string($start_date);
        $end_date = sql_escape_string($end_date);
        
        // 광고 계정에 연결된 캠페인 ID 목록 조회
        $sql = "SELECT DISTINCT campaign_id 
                FROM external_campaigns 
                WHERE user_ad_account_id = '{$account_id}'";
        $result = sql_query($sql);
        
        $campaign_ids = array();
        while ($row = sql_fetch_array($result)) {
            $campaign_ids[] = $row['campaign_id'];
        }
        
        // 캠페인이 없으면 빈 데이터 반환
        if (empty($campaign_ids)) {
            return array();
        }
        
        $campaign_ids_str = implode(',', $campaign_ids);
        
        // 일별 비용 및 클릭 데이터
        $sql = "SELECT 
                    cost_date as date,
                    SUM(impressions) as impressions,
                    SUM(clicks) as clicks,
                    SUM(ad_cost) as cost
                FROM ad_costs
                WHERE campaign_id IN ({$campaign_ids_str})
                AND cost_date BETWEEN '{$start_date}' AND '{$end_date}'
                GROUP BY cost_date
                ORDER BY cost_date";
        $result = sql_query($sql);
        
        $daily_stats = array();
        while ($row = sql_fetch_array($result)) {
            $date = $row['date'];
            $daily_stats[$date] = array(
                'date' => $date,
                'impressions' => (int)$row['impressions'],
                'clicks' => (int)$row['clicks'],
                'cost' => (float)$row['cost'],
                'conversions' => 0,
                'conversion_value' => 0
            );
        }
        
        // 일별 전환 데이터
        $sql = "SELECT 
                    DATE(event_time) as date,
                    COUNT(id) as conversions,
                    SUM(conversion_value) as conversion_value
                FROM conversion_events_log
                WHERE campaign_id IN ({$campaign_ids_str})
                AND DATE(event_time) BETWEEN '{$start_date}' AND '{$end_date}'
                GROUP BY DATE(event_time)
                ORDER BY DATE(event_time)";
        $result = sql_query($sql);
        
        while ($row = sql_fetch_array($result)) {
            $date = $row['date'];
            if (isset($daily_stats[$date])) {
                $daily_stats[$date]['conversions'] = (int)$row['conversions'];
                $daily_stats[$date]['conversion_value'] = (float)$row['conversion_value'];
            } else {
                $daily_stats[$date] = array(
                    'date' => $date,
                    'impressions' => 0,
                    'clicks' => 0,
                    'cost' => 0,
                    'conversions' => (int)$row['conversions'],
                    'conversion_value' => (float)$row['conversion_value']
                );
            }
        }
        
        // 날짜 범위의 모든 날짜를 확인하고 누락된 날짜 추가
        $current_date = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);
        $end_date_obj->modify('+1 day'); // 종료일 포함
        
        while ($current_date < $end_date_obj) {
            $date_str = $current_date->format('Y-m-d');
            
            if (!isset($daily_stats[$date_str])) {
                $daily_stats[$date_str] = array(
                    'date' => $date_str,
                    'impressions' => 0,
                    'clicks' => 0,
                    'cost' => 0,
                    'conversions' => 0,
                    'conversion_value' => 0
                );
            }
            
            $current_date->modify('+1 day');
        }
        
        // 날짜순으로 정렬
        ksort($daily_stats);
        
        // 계산된 지표 추가 및 배열로 변환
        $result_stats = array();
        foreach ($daily_stats as $date => $stats) {
            // CTR(클릭률) 계산
            $stats['ctr'] = $stats['impressions'] > 0 ? ($stats['clicks'] / $stats['impressions']) * 100 : 0;
            
            // CPC(클릭당 비용) 계산
            $stats['cpc'] = $stats['clicks'] > 0 ? $stats['cost'] / $stats['clicks'] : 0;
            
            // CPA(전환당 비용) 계산
            $stats['cpa'] = $stats['conversions'] > 0 ? $stats['cost'] / $stats['conversions'] : 0;
            
            // ROAS(광고 수익률) 계산
            $stats['roas'] = $stats['cost'] > 0 ? ($stats['conversion_value'] / $stats['cost']) * 100 : 0;
            
            $result_stats[] = $stats;
        }
        
        return $result_stats;
    }
    
    /**
     * 광고 계정의 매체별 통계를 가져옵니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param string $start_date 시작일 (YYYY-MM-DD)
     * @param string $end_date 종료일 (YYYY-MM-DD)
     * @return array 매체별 통계 데이터
     */
    public function get_platform_statistics($account_id, $start_date = null, $end_date = null) {
        $account_id = intval($account_id);
        
        // 기본값: 최근 30일
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $start_date = sql_escape_string($start_date);
        $end_date = sql_escape_string($end_date);
        
        // 광고 계정 정보 가져오기
        $account = $this->get_ad_account($account_id);
        
        if (!$account) {
            return array();
        }
        
        // 해당 플랫폼 데이터만 반환
        $platform_id = $account['platform_id'];
        $platform_name = $account['platform_name'];
        
        // 광고 계정에 연결된 캠페인 ID 목록 조회
        $sql = "SELECT DISTINCT campaign_id 
                FROM external_campaigns 
                WHERE user_ad_account_id = '{$account_id}'";
        $result = sql_query($sql);
        
        $campaign_ids = array();
        while ($row = sql_fetch_array($result)) {
            $campaign_ids[] = $row['campaign_id'];
        }
        
        // 캠페인이 없으면 빈 데이터 반환
        if (empty($campaign_ids)) {
            return array(
                'name' => $platform_name,
                'impressions' => 0,
                'clicks' => 0,
                'cost' => 0,
                'conversions' => 0,
                'conversion_value' => 0,
                'ctr' => 0,
                'cpc' => 0,
                'cpa' => 0,
                'roas' => 0
            );
        }
        
        $campaign_ids_str = implode(',', $campaign_ids);
        
        // 비용 및 클릭 데이터
        $sql = "SELECT 
                    SUM(impressions) as impressions,
                    SUM(clicks) as clicks,
                    SUM(ad_cost) as cost
                FROM ad_costs
                WHERE campaign_id IN ({$campaign_ids_str})
                AND cost_date BETWEEN '{$start_date}' AND '{$end_date}'";
        $cost_row = sql_fetch($sql);
        
        // 전환 데이터
        $sql = "SELECT 
                    COUNT(id) as conversions,
                    SUM(conversion_value) as conversion_value
                FROM conversion_events_log
                WHERE campaign_id IN ({$campaign_ids_str})
                AND DATE(event_time) BETWEEN '{$start_date}' AND '{$end_date}'";
        $conversion_row = sql_fetch($sql);
        
        // 통계 데이터 조합
        $impressions = (int)$cost_row['impressions'] ?: 0;
        $clicks = (int)$cost_row['clicks'] ?: 0;
        $cost = (float)$cost_row['cost'] ?: 0;
        $conversions = (int)$conversion_row['conversions'] ?: 0;
        $conversion_value = (float)$conversion_row['conversion_value'] ?: 0;
        
        // 계산된 지표
        $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
        $cpc = $clicks > 0 ? $cost / $clicks : 0;
        $cpa = $conversions > 0 ? $cost / $conversions : 0;
        $roas = $cost > 0 ? ($conversion_value / $cost) * 100 : 0;
        
        return array(
            'name' => $platform_name,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'cost' => $cost,
            'conversions' => $conversions,
            'conversion_value' => $conversion_value,
            'ctr' => $ctr,
            'cpc' => $cpc,
            'cpa' => $cpa,
            'roas' => $roas
        );
    }
    
    /**
     * 시간별 통계 데이터를 가져옵니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param string $date 날짜 (YYYY-MM-DD)
     * @return array 시간별 통계 데이터
     */
    public function get_hourly_statistics($account_id, $date = null) {
        $account_id = intval($account_id);
        
        // 기본값: 오늘
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        $date = sql_escape_string($date);
        
        // 광고 계정에 연결된 캠페인 ID 목록 조회
        $sql = "SELECT DISTINCT campaign_id 
                FROM external_campaigns 
                WHERE user_ad_account_id = '{$account_id}'";
        $result = sql_query($sql);
        
        $campaign_ids = array();
        while ($row = sql_fetch_array($result)) {
            $campaign_ids[] = $row['campaign_id'];
        }
        
        // 캠페인이 없으면 빈 데이터 반환
        if (empty($campaign_ids)) {
            return array();
        }
        
        $campaign_ids_str = implode(',', $campaign_ids);
        
        // 시간별 클릭 데이터
        $sql = "SELECT 
                    HOUR(click_time) as hour,
                    COUNT(*) as clicks,
                    SUM(ad_cost) as cost
                FROM url_clicks
                WHERE campaign_id IN ({$campaign_ids_str})
                AND DATE(click_time) = '{$date}'
                GROUP BY HOUR(click_time)
                ORDER BY HOUR(click_time)";
        $result = sql_query($sql);
        
        // 시간별 데이터 초기화 (0~23시)
        $hourly_stats = array();
        for ($i = 0; $i < 24; $i++) {
            $hourly_stats[$i] = array(
                'hour' => $i,
                'clicks' => 0,
                'cost' => 0,
                'conversions' => 0,
                'conversion_value' => 0
            );
        }
        
        // 클릭 데이터 설정
        while ($row = sql_fetch_array($result)) {
            $hour = (int)$row['hour'];
            $hourly_stats[$hour]['clicks'] = (int)$row['clicks'];
            $hourly_stats[$hour]['cost'] = (float)$row['cost'];
        }
        
        // 시간별 전환 데이터
        $sql = "SELECT 
                    HOUR(event_time) as hour,
                    COUNT(*) as conversions,
                    SUM(conversion_value) as conversion_value
                FROM conversion_events_log
                WHERE campaign_id IN ({$campaign_ids_str})
                AND DATE(event_time) = '{$date}'
                GROUP BY HOUR(event_time)
                ORDER BY HOUR(event_time)";
        $result = sql_query($sql);
        
        // 전환 데이터 설정
        while ($row = sql_fetch_array($result)) {
            $hour = (int)$row['hour'];
            $hourly_stats[$hour]['conversions'] = (int)$row['conversions'];
            $hourly_stats[$hour]['conversion_value'] = (float)$row['conversion_value'];
        }
        
        // 배열로 변환
        return array_values($hourly_stats);
    }
    
    /**
     * API 동기화 작업 상태를 업데이트합니다.
     * 
     * @param int $job_id 작업 ID
     * @param string $status 상태
     * @param array $data 결과 데이터
     * @return bool 성공 여부
     */
    public function update_sync_job_status($job_id, $status, $data = array()) {
        $job_id = intval($job_id);
        $status = sql_escape_string($status);
        
        $updates = array("status = '{$status}'");
        
        if ($status == '진행중') {
            $updates[] = "started_at = NOW()";
        } else if ($status == '완료' || $status == '실패') {
            $updates[] = "completed_at = NOW()";
        }
        
        if (!empty($data['error_message'])) {
            $error_message = sql_escape_string($data['error_message']);
            $updates[] = "error_message = '{$error_message}'";
        }
        
        if (!empty($data['result_summary'])) {
            $result_summary = sql_escape_string(json_encode($data['result_summary'], JSON_UNESCAPED_UNICODE));
            $updates[] = "result_summary = '{$result_summary}'";
        }
        
        $update_str = implode(', ', $updates);
        $sql = "UPDATE api_sync_jobs SET {$update_str} WHERE id = '{$job_id}'";
        
        return sql_query($sql);
    }
    
    /**
     * 광고 계정에 대한 권한을 확인합니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param int $user_id 사용자 ID
     * @return bool 권한 여부
     */
    public function check_account_permission($account_id, $user_id) {
        $account_id = intval($account_id);
        $user_id = intval($user_id);
        
        $sql = "SELECT COUNT(*) as cnt FROM user_ad_accounts WHERE id = '{$account_id}' AND user_id = '{$user_id}'";
        $row = sql_fetch($sql);
        
        return ($row['cnt'] > 0);
    }
    
    /**
     * 광고 계정 카운트를 가져옵니다.
     * 
     * @param int $user_id 사용자 ID
     * @param array $conditions 조건
     * @return int 광고 계정 수
     */
    public function count_ad_accounts($user_id, $conditions = array()) {
        $user_id = intval($user_id);
        
        $where = array("user_id = '{$user_id}'");
        
        if (!empty($conditions['platform_id'])) {
            $platform_id = intval($conditions['platform_id']);
            $where[] = "platform_id = '{$platform_id}'";
        }
        
        if (!empty($conditions['status'])) {
            $status = sql_escape_string($conditions['status']);
            $where[] = "status = '{$status}'";
        }
        
        $where_str = implode(' AND ', $where);
        
        $sql = "SELECT COUNT(*) as cnt FROM user_ad_accounts WHERE {$where_str}";
        $row = sql_fetch($sql);
        
        return intval($row['cnt']);
    }
    
    /**
     * 특정 광고 플랫폼 정보를 가져옵니다.
     * 
     * @param int $platform_id 플랫폼 ID
     * @return array|false 플랫폼 정보 또는 false
     */
    public function get_platform($platform_id) {
        $platform_id = intval($platform_id);
        
        $sql = "SELECT * FROM ad_platforms WHERE id = '{$platform_id}'";
        $platform = sql_fetch($sql);
        
        if ($platform) {
            // field_settings JSON 파싱
            if (!empty($platform['field_settings'])) {
                $platform['field_settings_data'] = json_decode($platform['field_settings'], true);
            } else {
                // 기본 필드 설정
                $platform['field_settings_data'] = $this->get_default_field_settings($platform['platform_code']);
            }
        }
        
        return $platform;
    }
    
    /**
     * 플랫폼 코드로 광고 플랫폼 정보를 가져옵니다.
     * 
     * @param string $platform_code 플랫폼 코드
     * @return array|false 플랫폼 정보 또는 false
     */
    public function get_platform_by_code($platform_code) {
        $platform_code = sql_escape_string($platform_code);
        
        $sql = "SELECT * FROM ad_platforms WHERE platform_code = '{$platform_code}'";
        return sql_fetch($sql);
    }
    
    /**
     * 광고 계정을 추가합니다.
     * 
     * @param array $data 광고 계정 데이터
     * @return int|false 추가된 광고 계정 ID 또는 false
     */
    public function add_account($data) {
        // add_ad_account 메서드 별칭 (기존 코드 호환성)
        return $this->add_ad_account($data);
    }
    
    /**
     * 광고 계정을 수정합니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param array $data 수정할 데이터
     * @return bool 성공 여부
     */
    public function update_account($account_id, $data) {
        // update_ad_account 메서드 별칭 (기존 코드 호환성)
        return $this->update_ad_account($account_id, $data);
    }
    
    /**
     * 광고 계정을 삭제합니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param int $user_id 사용자 ID
     * @return bool 성공 여부
     */
    public function delete_account($account_id, $user_id = null) {
        $account_id = intval($account_id);
        
        // 권한 체크 (user_id가 제공된 경우)
        if ($user_id !== null) {
            if (!$this->check_account_permission($account_id, $user_id)) {
                return false;
            }
        }
        
        // delete_ad_account 메서드 호출 (기존 코드 호환성)
        return $this->delete_ad_account($account_id);
    }
    
    /**
     * 특정 광고 계정 정보를 가져옵니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @return array|false 광고 계정 정보 또는 false
     */
    public function get_account($account_id) {
        // get_ad_account 메서드 별칭 (기존 코드 호환성)
        return $this->get_ad_account($account_id);
    }
    
    /**
     * 사용자의 광고 계정 목록을 가져옵니다.
     * 
     * @param int $user_id 사용자 ID
     * @param array $params 검색 조건
     * @return array 광고 계정 목록
     */
    public function get_user_accounts($user_id, $params = array()) {
        // get_user_ad_accounts 메서드 별칭 (기존 코드 호환성)
        return $this->get_user_ad_accounts($user_id, $params);
    }
    
    /**
     * 사용 가능한 광고 플랫폼 목록을 가져옵니다.
     * 
     * @param bool $active_only 활성화된 플랫폼만 가져올지 여부
     * @return array 광고 플랫폼 목록
     */
    public function get_platforms($active_only = true) {
        // get_ad_platforms 메서드 별칭 (기존 코드 호환성)
        return $this->get_ad_platforms($active_only);
    }
    
    /**
     * 계정 ID로 외부 캠페인 목록을 가져옵니다.
     * 
     * @param int $account_id 광고 계정 ID
     * @param array $params 검색 조건
     * @return array 외부 캠페인 목록
     */
    public function get_external_campaigns($account_id, $params = array()) {
        $account_id = intval($account_id);
        
        $where = array("user_ad_account_id = '{$account_id}'");
        
        // 검색 조건 처리
        if (!empty($params['search_keyword'])) {
            $search_keyword = trim(sql_escape_string($params['search_keyword']));
            $where[] = "(external_campaign_name LIKE '%{$search_keyword}%' OR external_campaign_id LIKE '%{$search_keyword}%')";
        }
        
        if (isset($params['status']) && $params['status'] !== '') {
            $status = sql_escape_string($params['status']);
            $where[] = "status = '{$status}'";
        }
        
        $where_str = implode(' AND ', $where);
        
        // 정렬 조건
        $order_by = "last_synced_at DESC";
        if (!empty($params['sort_field']) && !empty($params['sort_order'])) {
            $sort_field = sql_escape_string($params['sort_field']);
            $sort_order = sql_escape_string($params['sort_order']);
            $order_by = "{$sort_field} {$sort_order}";
        }
        
        // 페이징 처리
        $page = isset($params['page']) ? max(1, intval($params['page'])) : 1;
        $items_per_page = isset($params['items_per_page']) ? intval($params['items_per_page']) : 20;
        $start = ($page - 1) * $items_per_page;
        
        // 전체 캠페인 수 조회
        $count_sql = "SELECT COUNT(*) as cnt FROM external_campaigns WHERE {$where_str}";
        $row = sql_fetch($count_sql);
        $total_count = $row['cnt'];
        
        // 캠페인 목록 조회
        $sql = "SELECT * FROM external_campaigns 
                WHERE {$where_str} 
                ORDER BY {$order_by} 
                LIMIT {$start}, {$items_per_page}";
        $result = sql_query($sql);
        
        $campaigns = array();
        while ($row = sql_fetch_array($result)) {
            // 외부 데이터 JSON 변환
            if (!empty($row['external_data'])) {
                $row['external_data_decoded'] = json_decode($row['external_data'], true);
            }
            
            $campaigns[] = $row;
        }
        
        return array(
            'campaigns' => $campaigns,
            'total_count' => $total_count,
            'page' => $page,
            'items_per_page' => $items_per_page,
            'total_pages' => ceil($total_count / $items_per_page)
        );
    }
}