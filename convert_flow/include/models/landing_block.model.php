<?php
/**
 * 랜딩페이지 블록 관리 모델
 */
class LandingBlockModel {
    private $table_prefix;
    
    /**
     * 생성자
     */
    public function __construct() {
        global $cf_table_prefix;
        $this->table_prefix = $cf_table_prefix;
    }
    
     /**
     * 블록 인스턴스 정보 조회
     * @param int $block_id 블록 ID
     * @return array|null 블록 정보 배열 또는 null
     */
    public function get_block($block_id) {
        if (empty($block_id)) return null;
        
        $sql = "SELECT * FROM landing_page_block_instances WHERE id = " . intval($block_id);
        $result = sql_fetch($sql);
        
        if ($result) {
            // settings를 JSON 디코드
            if (isset($result['settings']) && !empty($result['settings'])) {
                $result['settings'] = json_decode($result['settings'], true);
            }
            return $result;
        }
        
        return null;
    }
    

    
    /**
     * 특정 블록 템플릿 정보 조회
     *
     * @param int $template_id 템플릿 ID
     * @return array|false 템플릿 정보
     */
    public function get_template($template_id) {
        if (!$template_id) {
            return false;
        }
        
        $sql = "SELECT lbt.*, lbt.id as id, lbt2.name as type_name 
                FROM landing_block_templates lbt
                LEFT JOIN landing_block_types lbt2 ON lbt.block_type_id = lbt2.id
                WHERE lbt.id = {$template_id}";
        
        return sql_fetch($sql);
    }

/**
    /**
     * 블록 타입 목록 조회
     *
     * @param string $category 블록 카테고리 (옵션)
     * @return array 블록 타입 목록
     */
    public function get_block_types($category = '') {
        $where = '';
        if (!empty($category)) {
            $where = "WHERE category = '" . sql_escape_string($category) . "'";
        }
        
        $sql = "SELECT * FROM landing_block_types $where ORDER BY category, name";
        $result = sql_query($sql);
        
        $types = array();
        while ($row = sql_fetch_array($result)) {
            $types[] = $row;
        }
        
        return $types;
    }
    
    /**
     * 블록 템플릿 목록 조회
     *
     * @param int $type_id 블록 타입 ID (옵션)
     * @param bool $public_only 공개 템플릿만 조회 여부
     * @return array 블록 템플릿 목록
     */
    public function get_block_templates($type_id = 0, $public_only = true) {
        $where = array();
        
        if ($type_id > 0) {
            $where[] = "block_type_id = " . intval($type_id);
        }
        
        if ($public_only) {
            $where[] = "is_public = 1";
        }
        
        $where_clause = empty($where) ? '' : "WHERE " . implode(" AND ", $where);
        
        $sql = "SELECT t.*, bt.name as type_name, bt.category 
                FROM landing_block_templates t
                JOIN landing_block_types bt ON t.block_type_id = bt.id
                $where_clause
                ORDER BY bt.category, t.name";
        
        $result = sql_query($sql);
        
        $templates = array();
        while ($row = sql_fetch_array($result)) {
            $templates[] = $row;
        }
        
        return $templates;
    }
    
    /**
     * 특정 랜딩페이지의 블록 인스턴스 목록 조회
     *
     * @param int $landing_page_id 랜딩페이지 ID
     * @return array 블록 인스턴스 목록
     */
    public function get_page_blocks($landing_page_id) {
        $sql = "SELECT bi.*, bt.name as template_name, typ.name as type_name, typ.category
                FROM landing_page_block_instances bi
                JOIN landing_block_templates bt ON bi.block_template_id = bt.id
                JOIN landing_block_types typ ON bt.block_type_id = typ.id
                WHERE bi.landing_page_id = " . intval($landing_page_id) . "
                ORDER BY bi.block_order";
        
        $result = sql_query($sql);
        
        $blocks = array();
        while ($row = sql_fetch_array($result)) {
            if (!empty($row['settings'])) {
                $row['settings'] = json_decode($row['settings'], true);
            } else {
                $row['settings'] = array();
            }
            $blocks[] = $row;
        }
        
        return $blocks;
    }
    
    /**
     * 블록 인스턴스 추가
     *
     * @param array $data 블록 데이터
     * @return int|bool 성공 시 블록 ID, 실패 시 false
     */
    public function add_block_instance($data) {
        if (empty($data['landing_page_id']) || empty($data['block_template_id'])) {
            return false;
        }
        
        // 현재 가장 높은 순서 값 조회
        $sql = "SELECT MAX(block_order) as max_order 
                FROM landing_page_block_instances 
                WHERE landing_page_id = " . intval($data['landing_page_id']);
        $order_row = sql_fetch($sql);
        $block_order = $order_row['max_order'] ? $order_row['max_order'] + 1 : 1;
        
        $insert_data = array(
            'landing_page_id' => $data['landing_page_id'],
            'block_template_id' => $data['block_template_id'],
            'block_order' => $block_order,
            'settings' => isset($data['settings']) ? json_encode($data['settings']) : '{}',
            'custom_html' => isset($data['custom_html']) ? $data['custom_html'] : '',
            'custom_css' => isset($data['custom_css']) ? $data['custom_css'] : '',
            'custom_js' => isset($data['custom_js']) ? $data['custom_js'] : ''
        );
        
        return sql_insert('landing_page_block_instances', $insert_data);
    }
    
    /**
     * 블록 인스턴스 업데이트
     *
     * @param int $block_id 블록 ID
     * @param array $data 업데이트 데이터
     * @return bool 성공 여부
     */
    public function update_block_instance($block_id, $data) {
        $update_data = array();
        
        if (isset($data['settings'])) {
            $update_data['settings'] = $data['settings'];
        }
        
        if (isset($data['custom_html'])) {
            $update_data['custom_html'] = $data['custom_html'];
        }
        
        if (isset($data['custom_css'])) {
            $update_data['custom_css'] = $data['custom_css'];
        }
        
        if (isset($data['custom_js'])) {
            $update_data['custom_js'] = $data['custom_js'];
        }
        
        if (isset($data['block_order'])) {
            $update_data['block_order'] = $data['block_order'];
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        return sql_update('landing_page_block_instances', $update_data, array('id' => $block_id));
    }
    

    
    /**
     * 블록 인스턴스
     *
     * @param int $block_id 블록 ID
     * @return bool 성공 여부
     */
    public function get_block_instance($block_id) {
        // 블록 타입을 사용하는 템플릿 조회
        $sql = "SELECT * FROM landing_page_block_instances WHERE id = '$block_id'";
        return sql_fetch($sql);
    }


    /**
     * 블록 인스턴스 삭제
     *
     * @param int $block_id 블록 ID
     * @return bool 성공 여부
     */
    public function delete_block_instance($block_id) {
        return sql_delete('landing_page_block_instances', array('id' => $block_id));
    }
    
    /**
     * 블록 인스턴스 순서 업데이트
     *
     * @param array $block_orders 블록 ID와 순서 배열
     * @return bool 성공 여부
     */
    public function update_block_orders($block_orders) {
        if (empty($block_orders) || !is_array($block_orders)) {
            return false;
        }
        
        $success = true;
        
        foreach ($block_orders as $block_id => $order) {
            $result = sql_update(
                'landing_page_block_instances',
                array('block_order' => $order),
                array('id' => $block_id)
            );
            
            if (!$result) {
                $success = false;
            }
        }
        
        return $success;
    }


    /**
     * 블록 타입 추가
     *
     * @param array $data 블록 타입 데이터
     * @return int|bool 성공 시 블록 타입 ID, 실패 시 false
     */
    public function add_block_type($data) {
        if (empty($data['name']) || empty($data['category'])) {
            return false;
        }
        
        $sql = "INSERT INTO landing_block_types
                SET
                    name = '" . sql_escape_string($data['name']) . "',
                    category = '" . sql_escape_string($data['category']) . "',
                    description = '" . sql_escape_string(isset($data['description']) ? $data['description'] : '') . "',
                    icon = '" . sql_escape_string(isset($data['icon']) ? $data['icon'] : '') . "',
                    created_at = NOW()";
        
        sql_query($sql);
        
        // 마지막으로 삽입된 ID 반환
        return sql_insert_id();
    }

    /**
     * 블록 타입 업데이트
     *
     * @param int $type_id 블록 타입 ID
     * @param array $data 업데이트 데이터
     * @return bool 성공 여부
     */
    public function update_block_type($type_id, $data) {
        if ($type_id <= 0 || empty($data)) {
            return false;
        }
        
        $set_parts = array();
        
        if (isset($data['name'])) {
            $set_parts[] = "name = '" . sql_escape_string($data['name']) . "'";
        }
        
        if (isset($data['category'])) {
            $set_parts[] = "category = '" . sql_escape_string($data['category']) . "'";
        }
        
        if (isset($data['description'])) {
            $set_parts[] = "description = '" . sql_escape_string($data['description']) . "'";
        }
        
        if (isset($data['icon'])) {
            $set_parts[] = "icon = '" . sql_escape_string($data['icon']) . "'";
        }
        
        if (empty($set_parts)) {
            return false;
        }
        
        $sql = "UPDATE landing_block_types 
                SET
                    " . implode(",\n                ", $set_parts) . "
                WHERE id = '$type_id'";
        return sql_query($sql);
    }

    /**
     * 블록 타입 삭제
     *
     * @param int $type_id 블록 타입 ID
     * @return bool 성공 여부
     */
    public function delete_block_type($type_id) {
        if ($type_id <= 0) {
            return false;
        }
        
        // 블록 타입을 사용하는 템플릿 조회
        $sql = "SELECT id FROM landing_block_templates WHERE block_type_id = '$type_id'";
        $result = sql_query($sql);
        
        $template_ids = array();
        while ($row = sql_fetch_array($result)) {
            $template_ids[] = $row['id'];
        }
        
        // 트랜잭션 시작
        sql_query("BEGIN");
        
        try {
            // 템플릿이 있으면, 해당 템플릿을 사용하는 블록 인스턴스 삭제
            if (!empty($template_ids)) {
                $template_ids_str = implode(',', $template_ids);
                $sql = "DELETE FROM landing_page_block_instances WHERE block_template_id IN ($template_ids_str)";
                sql_query($sql);
                
                // 템플릿 삭제
                $sql = "DELETE FROM landing_block_templates WHERE block_type_id = '$type_id'";
                sql_query($sql);
            }
            
            // 블록 타입 삭제
            $sql = "DELETE FROM landing_block_types WHERE id = '$type_id'";
            sql_query($sql);
            
            // 트랜잭션 커밋
            sql_query("COMMIT");
            
            return true;
        } catch (Exception $e) {
            // 오류 발생 시 롤백
            sql_query("ROLLBACK");
            return false;
        }
    }

    /**
     * 블록 타입 정보 조회
     *
     * @param int $type_id 블록 타입 ID
     * @return array|bool 블록 타입 정보 또는 실패 시 false
     */
    public function get_block_type($type_id) {
        if ($type_id <= 0) {
            return false;
        }
        
        $sql = "SELECT * FROM landing_block_types WHERE id = '$type_id'";
        return sql_fetch($sql);
    }
    
    /**
     * 랜딩페이지 HTML 생성
     *
     * @param int $landing_page_id 랜딩페이지 ID
     * @return array 랜딩페이지 HTML, CSS, JS
     */
    public function generate_landing_page_content($landing_page_id) {
        // 랜딩페이지 정보 조회
        $sql = "SELECT * FROM landing_pages WHERE id = " . intval($landing_page_id);
        $landing_page = sql_fetch($sql);
        
        if (!$landing_page) {
            return array(
                'html' => '',
                'css' => '',
                'js' => ''
            );
        }
        
        // 블록 인스턴스 목록 조회
        $blocks = $this->get_page_blocks($landing_page_id);
        
        // 블록 템플릿 ID 목록 추출
        $template_ids = array();
        foreach ($blocks as $block) {
            $template_ids[] = $block['block_template_id'];
        }
        
        // 블록 템플릿 정보 조회
        $templates = array();
        if (!empty($template_ids)) {
            $sql = "SELECT * FROM landing_block_templates 
                    WHERE id IN (" . implode(',', $template_ids) . ")";
            $result = sql_query($sql);
            
            while ($row = sql_fetch_array($result)) {
                $templates[$row['id']] = $row;
            }
        }
        
        // HTML, CSS, JS 초기화
        $html_parts = array();
        $css_parts = array();
        $js_parts = array();
        
        // 기본 템플릿에서 가져온 콘텐츠 추가
        if (!empty($landing_page['template_id'])) {
            $sql = "SELECT * FROM landing_page_templates WHERE id = " . intval($landing_page['template_id']);
            $template = sql_fetch($sql);
            
            if ($template) {
                $css_parts[] = $template['css_template'];
                $js_parts[] = $template['js_template'];
                
                // 템플릿 HTML은 블록 위치를 표시하는 주석 태그를 포함해야 함
                $html_template = $template['html_template'];
                // 주석 태그 위치에 블록 컨텐츠 삽입 (주석 태그: <!-- BLOCKS_CONTENT -->)
                $html_template = preg_replace('/<!-- BLOCKS_CONTENT -->/', '{BLOCKS_CONTENT}', $html_template);
                
                $html_parts[] = $html_template;
            }
        }
        
        // 블록 컨텐츠 생성
        $blocks_html = '';
        foreach ($blocks as $block) {
            $template = isset($templates[$block['block_template_id']]) ? $templates[$block['block_template_id']] : null;
            
            if ($template) {
                // 블록 HTML 컨텐츠 가져오기
                $block_html = $template['html_content'];
                
                // 설정 값으로 태그 대체
                if (!empty($block['settings']) && is_array($block['settings'])) {
                    foreach ($block['settings'] as $key => $value) {
                        $block_html = str_replace('{{' . $key . '}}', $value, $block_html);
                    }
                }
                
                // 사용자 정의 HTML 추가
                if (!empty($block['custom_html'])) {
                    $block_html = $block['custom_html'];
                }
                
                $blocks_html .= $block_html;
                
                // CSS 추가
                if (!empty($template['css_content'])) {
                    $css_parts[] = $template['css_content'];
                }
                
                // 사용자 정의 CSS 추가
                if (!empty($block['custom_css'])) {
                    $css_parts[] = $block['custom_css'];
                }
                
                // JavaScript 추가
                if (!empty($template['js_content'])) {
                    $js_parts[] = $template['js_content'];
                }
                
                // 사용자 정의 JavaScript 추가
                if (!empty($block['custom_js'])) {
                    $js_parts[] = $block['custom_js'];
                }
            }
        }
        
        // HTML 최종 조합
        $html = '';
        if (!empty($html_parts)) {
            $html = str_replace('{BLOCKS_CONTENT}', $blocks_html, $html_parts[0]);
        } else {
            $html = $blocks_html;
        }
        
        // 메타 태그 추가
        if (!empty($landing_page['meta_title'])) {
            $html = str_replace('<title>{{meta_title}}</title>', '<title>' . $landing_page['meta_title'] . '</title>', $html);
        }
        
        if (!empty($landing_page['meta_description'])) {
            $meta_desc = '<meta name="description" content="' . htmlspecialchars($landing_page['meta_description']) . '">';
            $html = str_replace('</head>', $meta_desc . "\n</head>", $html);
        }
        
        // 랜딩페이지 자체 콘텐츠 추가
        if (!empty($landing_page['html_content'])) {
            if (empty($html)) {
                $html = $landing_page['html_content'];
            }
        }
        
        if (!empty($landing_page['css_content'])) {
            $css_parts[] = $landing_page['css_content'];
        }
        
        if (!empty($landing_page['js_content'])) {
            $js_parts[] = $landing_page['js_content'];
        }
        
        // CSS, JS 최종 조합
        $css = implode("\n\n", array_filter($css_parts));
        $js = implode("\n\n", array_filter($js_parts));
        
        return array(
            'html' => $html,
            'css' => $css,
            'js' => $js
        );
    }
}