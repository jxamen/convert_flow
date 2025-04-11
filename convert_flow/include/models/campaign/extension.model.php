<?php
/**
 * 광고 확장 모델 클래스
 * 
 * 광고 확장 관련 데이터 처리를 담당하는 모델 클래스입니다.
 */
class ExtensionModel {
    /**
     * 사용자의 광고 확장 목록을 가져옵니다.
     * 
     * @param int $user_id 사용자 ID
     * @param array $params 검색 조건
     * @return array 광고 확장 목록
     */
    public function get_extensions($user_id, $params = array()) {
        $user_id = intval($user_id);
        
        $where = array("user_id = '{$user_id}'");
        
        // 검색 조건 처리
        if (!empty($params['search_keyword'])) {
            $search_keyword = trim(sql_escape_string($params['search_keyword']));
            $where[] = "(name LIKE '%{$search_keyword}%' OR content LIKE '%{$search_keyword}%')";
        }
        
        if (!empty($params['type'])) {
            $type = sql_escape_string($params['type']);
            $where[] = "type = '{$type}'";
        }
        
        if (isset($params['status'])) {
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
        
        // 전체 확장 수 조회
        $count_sql = "SELECT COUNT(*) as cnt FROM extensions WHERE {$where_str}";
        $row = sql_fetch($count_sql);
        $total_count = $row['cnt'];
        
        // 확장 목록 조회
        $sql = "SELECT * FROM extensions WHERE {$where_str} ORDER BY {$order_by} LIMIT {$start}, {$items_per_page}";
        $result = sql_query($sql);
        
        $extensions = array();
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            // 각 확장의 추가 데이터 파싱
            if (!empty($row['content'])) {
                $row['content_data'] = json_decode($row['content'], true);
            }
            
            // 확장이 연결된 광고 수
            $sql = "SELECT COUNT(*) as cnt FROM ad_extension_links WHERE extension_id = '{$row['id']}'";
            $count = sql_fetch($sql);
            $row['ad_count'] = $count['cnt'];
            
            $extensions[] = $row;
        }
        
        return array(
            'extensions' => $extensions,
            'total_count' => $total_count,
            'page' => $page,
            'items_per_page' => $items_per_page,
            'total_pages' => ceil($total_count / $items_per_page)
        );
    }
    
    /**
     * 특정 광고 확장 정보를 가져옵니다.
     * 
     * @param int $extension_id 확장 ID
     * @param int $user_id 사용자 ID (권한 체크용, 선택 사항)
     * @return array|false 확장 정보 또는 false
     */
    public function get_extension($extension_id, $user_id = null) {
        $extension_id = intval($extension_id);
        
        $where = "id = '{$extension_id}'";
        if ($user_id !== null) {
            $user_id = intval($user_id);
            $where .= " AND user_id = '{$user_id}'";
        }
        
        $sql = "SELECT * FROM extensions WHERE {$where}";
        $extension = sql_fetch($sql);
        
        if (!$extension) {
            return false;
        }
        
        // 추가 데이터 파싱
        if (!empty($extension['content'])) {
            $extension['content_data'] = json_decode($extension['content'], true);
        }
        
        // 확장이 연결된 광고 목록
        $sql = "SELECT a.id, a.headline, a.ad_type
                FROM ads a
                JOIN ad_extension_links ael ON a.id = ael.ad_id
                WHERE ael.extension_id = '{$extension_id}'
                ORDER BY a.headline ASC";
        $result = sql_query($sql);
        
        $linked_ads = array();
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            $linked_ads[] = $row;
        }
        
        $extension['linked_ads'] = $linked_ads;
        
        return $extension;
    }
    
    /**
     * 광고 확장을 추가합니다.
     * 
     * @param array $data 확장 데이터
     * @return int|false 추가된 확장 ID 또는 false
     */
    public function add_extension($data) {
        // 필수 필드 검증
        if (empty($data['user_id']) || empty($data['name']) || empty($data['type'])) {
            return false;
        }
        
        $user_id = intval($data['user_id']);
        $name = sql_escape_string($data['name']);
        $type = sql_escape_string($data['type']);
        $status = isset($data['status']) ? sql_escape_string($data['status']) : '활성';
        
        // 확장 타입별 컨텐츠 처리
        $content_data = array();
        
        switch ($type) {
            case '사이트링크':
                if (!empty($data['links']) && is_array($data['links'])) {
                    $content_data['links'] = $data['links'];
                }
                break;
                
            case '콜아웃':
                if (!empty($data['callouts']) && is_array($data['callouts'])) {
                    $content_data['callouts'] = $data['callouts'];
                }
                break;
                
            case '전화번호':
                if (!empty($data['phone'])) {
                    $content_data['phone'] = $data['phone'];
                }
                if (!empty($data['country_code'])) {
                    $content_data['country_code'] = $data['country_code'];
                }
                break;
                
            case '위치':
                if (!empty($data['address'])) {
                    $content_data['address'] = $data['address'];
                }
                if (!empty($data['city'])) {
                    $content_data['city'] = $data['city'];
                }
                if (!empty($data['region'])) {
                    $content_data['region'] = $data['region'];
                }
                if (!empty($data['postal_code'])) {
                    $content_data['postal_code'] = $data['postal_code'];
                }
                if (!empty($data['country'])) {
                    $content_data['country'] = $data['country'];
                }
                break;
                
            case '가격':
                if (!empty($data['price_items']) && is_array($data['price_items'])) {
                    $content_data['price_items'] = $data['price_items'];
                }
                if (!empty($data['price_header'])) {
                    $content_data['price_header'] = $data['price_header'];
                }
                if (!empty($data['price_description'])) {
                    $content_data['price_description'] = $data['price_description'];
                }
                break;
                
            case '앱':
                if (!empty($data['app_store'])) {
                    $content_data['app_store'] = $data['app_store'];
                }
                if (!empty($data['app_id'])) {
                    $content_data['app_id'] = $data['app_id'];
                }
                if (!empty($data['app_name'])) {
                    $content_data['app_name'] = $data['app_name'];
                }
                if (!empty($data['app_url'])) {
                    $content_data['app_url'] = $data['app_url'];
                }
                break;
        }
        
        $content_json = !empty($content_data) ? json_encode($content_data, JSON_UNESCAPED_UNICODE) : '';
        
        $sql = "INSERT INTO extensions (
                    user_id, name, type, status, content, created_at
                ) VALUES (
                    '{$user_id}', '{$name}', '{$type}', '{$status}', '{$content_json}', NOW()
                )";
        
        sql_query($sql);
        $extension_id = sql_insert_id();
        
        if ($extension_id) {
            return $extension_id;
        }
        
        return false;
    }
    
    /**
     * 광고 확장을 수정합니다.
     * 
     * @param int $extension_id 확장 ID
     * @param array $data 수정할 데이터
     * @return bool 성공 여부
     */
    public function update_extension($extension_id, $data) {
        $extension_id = intval($extension_id);
        
        // 기존 확장 정보 확인
        $extension = $this->get_extension($extension_id);
        if (!$extension) {
            return false;
        }
        
        $updates = array();
        
        if (isset($data['name'])) {
            $updates[] = "name = '" . sql_escape_string($data['name']) . "'";
        }
        
        if (isset($data['status'])) {
            $updates[] = "status = '" . sql_escape_string($data['status']) . "'";
        }
        
        // 확장 컨텐츠 업데이트
        $content_data = !empty($extension['content']) ? json_decode($extension['content'], true) : array();
        $content_updated = false;
        
        switch ($extension['type']) {
            case '사이트링크':
                if (isset($data['links']) && is_array($data['links'])) {
                    $content_data['links'] = $data['links'];
                    $content_updated = true;
                }
                break;
                
            case '콜아웃':
                if (isset($data['callouts']) && is_array($data['callouts'])) {
                    $content_data['callouts'] = $data['callouts'];
                    $content_updated = true;
                }
                break;
                
            case '전화번호':
                if (isset($data['phone'])) {
                    $content_data['phone'] = $data['phone'];
                    $content_updated = true;
                }
                if (isset($data['country_code'])) {
                    $content_data['country_code'] = $data['country_code'];
                    $content_updated = true;
                }
                break;
                
            case '위치':
                if (isset($data['address'])) {
                    $content_data['address'] = $data['address'];
                    $content_updated = true;
                }
                if (isset($data['city'])) {
                    $content_data['city'] = $data['city'];
                    $content_updated = true;
                }
                if (isset($data['region'])) {
                    $content_data['region'] = $data['region'];
                    $content_updated = true;
                }
                if (isset($data['postal_code'])) {
                    $content_data['postal_code'] = $data['postal_code'];
                    $content_updated = true;
                }
                if (isset($data['country'])) {
                    $content_data['country'] = $data['country'];
                    $content_updated = true;
                }
                break;
                
            case '가격':
                if (isset($data['price_items']) && is_array($data['price_items'])) {
                    $content_data['price_items'] = $data['price_items'];
                    $content_updated = true;
                }
                if (isset($data['price_header'])) {
                    $content_data['price_header'] = $data['price_header'];
                    $content_updated = true;
                }
                if (isset($data['price_description'])) {
                    $content_data['price_description'] = $data['price_description'];
                    $content_updated = true;
                }
                break;
                
            case '앱':
                if (isset($data['app_store'])) {
                    $content_data['app_store'] = $data['app_store'];
                    $content_updated = true;
                }
                if (isset($data['app_id'])) {
                    $content_data['app_id'] = $data['app_id'];
                    $content_updated = true;
                }
                if (isset($data['app_name'])) {
                    $content_data['app_name'] = $data['app_name'];
                    $content_updated = true;
                }
                if (isset($data['app_url'])) {
                    $content_data['app_url'] = $data['app_url'];
                    $content_updated = true;
                }
                break;
        }
        
        if ($content_updated) {
            $content_json = json_encode($content_data, JSON_UNESCAPED_UNICODE);
            $updates[] = "content = '" . sql_escape_string($content_json) . "'";
        }
        
        $updates[] = "updated_at = NOW()";
        
        if (empty($updates)) {
            return true; // 변경할 내용이 없음
        }
        
        $update_str = implode(', ', $updates);
        $sql = "UPDATE extensions SET {$update_str} WHERE id = '{$extension_id}'";
        
        return sql_query($sql);
    }
    
    /**
     * 광고 확장 상태를 변경합니다.
     * 
     * @param int $extension_id 확장 ID
     * @param string $status 새 상태 (활성, 비활성)
     * @return bool 성공 여부
     */
    public function change_extension_status($extension_id, $status) {
        $extension_id = intval($extension_id);
        $status = sql_escape_string($status);
        
        $sql = "UPDATE extensions SET status = '{$status}', updated_at = NOW() WHERE id = '{$extension_id}'";
        return sql_query($sql);
    }
    
    /**
     * 광고 확장을 삭제합니다.
     * 
     * @param int $extension_id 확장 ID
     * @return bool 성공 여부
     */
    public function delete_extension($extension_id) {
        $extension_id = intval($extension_id);
        
        // 확장과 광고의 연결 해제
        $sql = "DELETE FROM ad_extension_links WHERE extension_id = '{$extension_id}'";
        sql_query($sql);
        
        // 확장 삭제
        $sql = "DELETE FROM extensions WHERE id = '{$extension_id}'";
        return sql_query($sql);
    }
    
    /**
     * 특정 타입의 확장 목록을 가져옵니다.
     * 
     * @param int $user_id 사용자 ID
     * @param string $type 확장 타입
     * @return array 확장 목록
     */
    public function get_extensions_by_type($user_id, $type) {
        $user_id = intval($user_id);
        $type = sql_escape_string($type);
        
        $sql = "SELECT * FROM extensions 
                WHERE user_id = '{$user_id}' AND type = '{$type}' AND status = '활성'
                ORDER BY name ASC";
        $result = sql_query($sql);
        
        $extensions = array();
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            // 추가 데이터 파싱
            if (!empty($row['content'])) {
                $row['content_data'] = json_decode($row['content'], true);
            }
            
            $extensions[] = $row;
        }
        
        return $extensions;
    }
    
    /**
     * 확장 타입 목록을 가져옵니다.
     * 
     * @return array 확장 타입 목록
     */
    public function get_extension_types() {
        return array(
            array(
                'code' => '사이트링크',
                'name' => '사이트링크 확장',
                'description' => '광고에 추가 링크를 표시합니다.'
            ),
            array(
                'code' => '콜아웃',
                'name' => '콜아웃 확장',
                'description' => '광고에 추가 텍스트를 표시합니다.'
            ),
            array(
                'code' => '전화번호',
                'name' => '전화번호 확장',
                'description' => '광고에 전화번호를 표시하여 클릭 시 전화를 걸 수 있게 합니다.'
            ),
            array(
                'code' => '위치',
                'name' => '위치 확장',
                'description' => '광고에 비즈니스 위치 정보를 표시합니다.'
            ),
            array(
                'code' => '가격',
                'name' => '가격 확장',
                'description' => '광고에 제품이나 서비스 가격을 표시합니다.'
            ),
            array(
                'code' => '앱',
                'name' => '앱 확장',
                'description' => '모바일 앱 다운로드 링크를 광고에 추가합니다.'
            )
        );
    }
}