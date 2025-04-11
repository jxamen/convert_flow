<?php
if (!defined('_CONVERT_FLOW_')) exit;

/**
 * 랜딩페이지 모델 클래스
 */
class LandingPageModel {
    private $table_prefix;
    private $conn;
    
    public function __construct() {
        global $conn, $cf_table_prefix;
        $this->conn = $conn;
        $this->table_prefix = $cf_table_prefix;
    }
    
    /**
     * 랜딩페이지 템플릿 목록 조회
     */
    public function get_templates($industry = '', $limit = 20, $offset = 0) {
        $where = "";
        
        if ($industry) {
            $where .= " AND industry = '$industry'";
        }
        
        $sql = "SELECT * FROM landing_page_templates
                WHERE 1 $where
                ORDER BY name ASC
                LIMIT $offset, $limit";
        
        return sql_fetch_all($sql);
    }
    
    /**
     * 랜딩페이지 템플릿 상세 조회
     */
    public function get_template($template_id) {
        $sql = "SELECT * FROM landing_page_templates
                WHERE id = '$template_id'";
        
        return sql_fetch($sql);
    }
    
    public function createLandingPage($data) {
        $user_id = $data['user_id'];
        $template_id = $data['template_id'] ?? null;
        $name = $this->conn->real_escape_string($data['name']);
        $slug = $this->generate_unique_slug($data['slug']);
        $html_content = $this->conn->real_escape_string($data['html_content'] ?? '');
        $css_content = $this->conn->real_escape_string($data['css_content'] ?? '');
        $js_content = $this->conn->real_escape_string($data['js_content'] ?? '');

        $sql = "INSERT INTO landing_pages (user_id, template_id, name, slug, html_content, css_content, js_content, status, created_at, updated_at)
                VALUES ('$user_id', " . ($template_id ? "'$template_id'" : 'NULL') . ", '$name', '$slug', '$html_content', '$css_content', '$js_content', '초안', NOW(), NOW())";
        return $this->conn->query($sql) ? $this->conn->insert_id : false;
    }

    public function updateLandingPage($id, $data) {
        $set = [];
        foreach ($data as $key => $value) {
            $value = $this->conn->real_escape_string($value);
            $set[] = "$key = '$value'";
        }
        $set[] = "updated_at = NOW()";
        $sql = "UPDATE landing_pages SET " . implode(', ', $set) . " WHERE id = '$id'";
        return $this->conn->query($sql);
    }

    public function deleteLandingPage($id) {
        $sql = "DELETE FROM landing_pages WHERE id = '$id'";
        return $this->conn->query($sql);
    }

    public function getBlocks($user_id) {
        $sql = "SELECT * FROM landing_page_blocks WHERE user_id = '$user_id' ORDER BY created_at DESC";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function addBlock($data) {
        $user_id = $data['user_id'];
        $name = $this->conn->real_escape_string($data['name']);
        $type = $data['type'];
        $html_content = $this->conn->real_escape_string($data['html_content']);
        $css_content = $this->conn->real_escape_string($data['css_content'] ?? '');
        $js_content = $this->conn->real_escape_string($data['js_content'] ?? '');

        $sql = "INSERT INTO landing_page_blocks (user_id, name, type, html_content, css_content, js_content, created_at, updated_at)
                VALUES ('$user_id', '$name', '$type', '$html_content', '$css_content', '$js_content', NOW(), NOW())";
        return $this->conn->query($sql) ? $this->conn->insert_id : false;
    }

    
    /**
     * 랜딩페이지 목록 조회
     */
    public function get_landing_pages($user_id = 0, $campaign_id = 0, $status = '', $limit = 10, $offset = 0) {
        $where = "";
        
        if ($user_id) {
            $where .= " AND user_id = '$user_id'";
        }
        
        if ($campaign_id) {
            $where .= " AND campaign_id = '$campaign_id'";
        }
        
        if ($status) {
            $where .= " AND status = '$status'";
        }
        
        $sql = "SELECT * FROM landing_pages
                WHERE 1 $where
                ORDER BY created_at DESC
                LIMIT $offset, $limit";
        
        return sql_fetch_all($sql);
    }
    
    /**
     * 랜딩페이지 상세 조회
     */
    public function get_landing_page($page_id) {
        $sql = "SELECT * FROM landing_pages
                WHERE id = '$page_id'";
        
        return sql_fetch($sql);
    }
    
    /**
     * 랜딩페이지 슬러그로 조회
     */
    public function get_landing_page_by_slug($slug) {
        $sql = "SELECT * FROM landing_pages
                WHERE slug = '$slug'";
        
        return sql_fetch($sql);
    }
    
    /**
     * 랜딩페이지 개수 조회
     */
    public function count_landing_pages($user_id = 0, $campaign_id = 0, $status = '') {
        $where = "";
        
        if ($user_id) {
            $where .= " AND user_id = '$user_id'";
        }
        
        if ($campaign_id) {
            $where .= " AND campaign_id = '$campaign_id'";
        }
        
        if ($status) {
            $where .= " AND status = '$status'";
        }
        
        $sql = "SELECT COUNT(*) as cnt FROM landing_pages
                WHERE 1 $where";
        
        $row = sql_fetch($sql);
        return $row['cnt'];
    }
    
    /**
     * 랜딩페이지 슬러그 중복 체크
     */
    public function check_slug_exists($slug, $exclude_id = 0) {
        $exclude = $exclude_id ? "AND id != '$exclude_id'" : "";
        
        $sql = "SELECT COUNT(*) as cnt FROM landing_pages
                WHERE slug = '$slug' $exclude";
        
        $row = sql_fetch($sql);
        return ($row['cnt'] > 0);
    }
    
    /**
     * 고유한 슬러그 생성
     */
    public function generate_unique_slug($base_slug, $exclude_id = 0) {
        $slug = $base_slug;
        $counter = 1;
        
        while ($this->check_slug_exists($slug, $exclude_id)) {
            $slug = $base_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * 랜딩페이지 생성
     */
    public function create_landing_page($data) {
        $user_id = isset($data['user_id']) ? $data['user_id'] : $_SESSION['user_id'];
        $campaign_id = isset($data['campaign_id']) ? $data['campaign_id'] : 'NULL';
        $template_id = isset($data['template_id']) ? $data['template_id'] : 'NULL';
        $name = sql_escape_string($data['name']);
        
        // 슬러그 생성
        $base_slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
        $base_slug = trim($base_slug, '-');
        $slug = $this->generate_unique_slug($base_slug);
        
        $status = isset($data['status']) ? $data['status'] : '초안';
        $html_content = isset($data['html_content']) ? sql_escape_string($data['html_content']) : '';
        $css_content = isset($data['css_content']) ? sql_escape_string($data['css_content']) : '';
        $js_content = isset($data['js_content']) ? sql_escape_string($data['js_content']) : '';
        $meta_title = isset($data['meta_title']) ? sql_escape_string($data['meta_title']) : $name;
        $meta_description = isset($data['meta_description']) ? sql_escape_string($data['meta_description']) : '';
        
        // 템플릿을 기반으로 초기 내용 설정
        if ($template_id != 'NULL') {
            $template = $this->get_template($template_id);
            
            if ($template) {
                $html_content = $html_content ?: $template['html_template'];
                $css_content = $css_content ?: $template['css_template'];
                $js_content = $js_content ?: $template['js_template'];
            }
        }
        
        $sql = "INSERT INTO landing_pages
                SET
                    user_id = '$user_id',
                    campaign_id = " . ($campaign_id == 'NULL' ? 'NULL' : "'$campaign_id'") . ",
                    template_id = " . ($template_id == 'NULL' ? 'NULL' : "'$template_id'") . ",
                    name = '$name',
                    slug = '$slug',
                    status = '$status',
                    html_content = '$html_content',
                    css_content = '$css_content',
                    js_content = '$js_content',
                    meta_title = '$meta_title',
                    meta_description = '$meta_description',
                    created_at = NOW(),
                    updated_at = NOW()";
        
        if ($status == '게시됨') {
            $sql .= ", published_at = NOW()";
        }
        
        $result = sql_query($sql, true);
        
        if ($result) {
            // 로그 기록
            write_log('landing_page', 'create', array(
                'landing_page_id' => $result,
                'name' => $data['name'],
                'campaign_id' => $campaign_id
            ));
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * 랜딩페이지 수정
     */
    public function update_landing_page($page_id, $data) {
        $set_values = array();
        $current_page = $this->get_landing_page($page_id);
        
        if (!$current_page) {
            return false;
        }
        
        if (isset($data['campaign_id'])) {
            $campaign_id = $data['campaign_id'] ? "'{$data['campaign_id']}'" : 'NULL';
            $set_values[] = "campaign_id = $campaign_id";
        }
        
        if (isset($data['template_id'])) {
            $template_id = $data['template_id'] ? "'{$data['template_id']}'" : 'NULL';
            $set_values[] = "template_id = $template_id";
        }
        
        if (isset($data['name'])) {
            $set_values[] = "name = '" . sql_escape_string($data['name']) . "'";
        }
        
        if (isset($data['slug'])) {
            $new_slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($data['slug']));
            $new_slug = trim($new_slug, '-');
            
            // 슬러그 중복 체크
            if ($new_slug !== $current_page['slug']) {
                $new_slug = $this->generate_unique_slug($new_slug, $page_id);
            }
            
            $set_values[] = "slug = '$new_slug'";
        }
        
        if (isset($data['status'])) {
            $set_values[] = "status = '" . $data['status'] . "'";
            
            // 게시됨으로 상태 변경 시 published_at 설정
            if ($data['status'] == '게시됨' && $current_page['status'] != '게시됨') {
                $set_values[] = "published_at = NOW()";
            }
        }
        
        if (isset($data['html_content'])) {
            $set_values[] = "html_content = '" . sql_escape_string($data['html_content']) . "'";
        }
        
        if (isset($data['css_content'])) {
            $set_values[] = "css_content = '" . sql_escape_string($data['css_content']) . "'";
        }
        
        if (isset($data['js_content'])) {
            $set_values[] = "js_content = '" . sql_escape_string($data['js_content']) . "'";
        }
        
        if (isset($data['meta_title'])) {
            $set_values[] = "meta_title = '" . sql_escape_string($data['meta_title']) . "'";
        }
        
        if (isset($data['meta_description'])) {
            $set_values[] = "meta_description = '" . sql_escape_string($data['meta_description']) . "'";
        }
        
        $set_values[] = "updated_at = NOW()";
        
        if (empty($set_values)) {
            return false;
        }
        
        $set_clause = implode(', ', $set_values);
        
        $sql = "UPDATE landing_pages
                SET $set_clause
                WHERE id = '$page_id'";
        
        $result = sql_query($sql);
        
        if ($result) {
            // 로그 기록
            write_log('landing_page', 'update', array(
                'landing_page_id' => $page_id,
                'data' => $data
            ));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 랜딩페이지 삭제
     */
    public function delete_landing_page($page_id) {
        // 랜딩페이지 정보 가져오기 (로그용)
        $page = $this->get_landing_page($page_id);
        
        if (!$page) {
            return false;
        }
        
        $sql = "DELETE FROM landing_pages
                WHERE id = '$page_id'";
        
        $result = sql_query($sql);
        
        if ($result) {
            // 로그 기록
            write_log('landing_page', 'delete', array(
                'landing_page_id' => $page_id,
                'name' => $page['name']
            ));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 랜딩페이지 상태 변경
     */
    public function change_landing_page_status($page_id, $status) {
        $sql = "UPDATE landing_pages
                SET 
                    status = '$status',
                    updated_at = NOW()";
        
        // 게시됨으로 상태 변경 시 published_at 설정
        if ($status == '게시됨') {
            $sql .= ", published_at = NOW()";
        }
        
        $sql .= " WHERE id = '$page_id'";
        
        $result = sql_query($sql);
        
        if ($result) {
            // 로그 기록
            write_log('landing_page', 'status_change', array(
                'landing_page_id' => $page_id,
                'status' => $status
            ));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 랜딩페이지 복제
     */
    public function clone_landing_page($page_id) {
        // 원본 랜딩페이지 정보 가져오기
        $original_page = $this->get_landing_page($page_id);
        
        if (!$original_page) {
            return false;
        }
        
        // 새 이름과 슬러그 생성
        $new_name = $original_page['name'] . ' (복사본)';
        $base_slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($new_name));
        $base_slug = trim($base_slug, '-');
        $new_slug = $this->generate_unique_slug($base_slug);
        
        $sql = "INSERT INTO landing_pages
                SET
                    user_id = '{$original_page['user_id']}',
                    campaign_id = " . ($original_page['campaign_id'] ? "'{$original_page['campaign_id']}'" : 'NULL') . ",
                    template_id = " . ($original_page['template_id'] ? "'{$original_page['template_id']}'" : 'NULL') . ",
                    name = '$new_name',
                    slug = '$new_slug',
                    status = '초안',
                    html_content = '" . sql_escape_string($original_page['html_content']) . "',
                    css_content = '" . sql_escape_string($original_page['css_content']) . "',
                    js_content = '" . sql_escape_string($original_page['js_content']) . "',
                    meta_title = '" . sql_escape_string($original_page['meta_title']) . "',
                    meta_description = '" . sql_escape_string($original_page['meta_description']) . "',
                    created_at = NOW(),
                    updated_at = NOW()";
        
        $result = sql_query($sql, true);
        
        if ($result) {
            // 로그 기록
            write_log('landing_page', 'clone', array(
                'original_id' => $page_id,
                'new_id' => $result,
                'name' => $new_name
            ));
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * 랜딩페이지 성과 지표 조회
     */
    public function get_landing_page_stats($page_id) {
        // 방문자 수
        $sql_visits = "SELECT COUNT(*) as visits 
                    FROM conversion_events ce
                    JOIN conversion_scripts cs ON ce.script_id = cs.id
                    JOIN campaigns c ON cs.campaign_id = c.id
                    JOIN landing_pages lp ON c.id = lp.campaign_id
                    WHERE lp.id = '$page_id'";
        $visits_row = sql_fetch($sql_visits);
        $visits = $visits_row['visits'];
        
        // 전환 수
        $sql_conversions = "SELECT COUNT(*) as conversions 
                        FROM conversion_events ce
                        JOIN conversion_scripts cs ON ce.script_id = cs.id
                        JOIN campaigns c ON cs.campaign_id = c.id
                        JOIN landing_pages lp ON c.id = lp.campaign_id
                        WHERE lp.id = '$page_id' 
                        AND ce.conversion_value > 0";
        $conversions_row = sql_fetch($sql_conversions);
        $conversions = $conversions_row['conversions'];
        
        // 전환율
        $conversion_rate = ($visits > 0) ? round(($conversions / $visits) * 100, 2) : 0;
        
        return array(
            'visits' => $visits,
            'conversions' => $conversions,
            'conversion_rate' => $conversion_rate
        );
    }
    
    /**
     * 랜딩페이지 블록 목록 조회
     */
    public function get_blocks($user_id = 0, $type = '', $limit = 20, $offset = 0) {
        $where = "";
        
        if ($user_id) {
            $where .= " AND (user_id = '$user_id' OR is_public = 1)";
        } else {
            $where .= " AND is_public = 1";
        }
        
        if ($type) {
            $where .= " AND type = '$type'";
        }
        
        $sql = "SELECT * FROM landing_page_blocks
                WHERE 1 $where
                ORDER BY created_at DESC
                LIMIT $offset, $limit";
        
        return sql_fetch_all($sql);
    }
    
    /**
     * 랜딩페이지 블록 상세 조회
     */
    public function get_block($block_id) {
        $sql = "SELECT * FROM landing_page_blocks
                WHERE id = '$block_id'";
        
        return sql_fetch($sql);
    }
    
    /**
     * 랜딩페이지 블록 생성
     */
    public function create_block($data) {
        $user_id = isset($data['user_id']) ? $data['user_id'] : $_SESSION['user_id'];
        $name = sql_escape_string($data['name']);
        $type = $data['type'];
        $html_content = sql_escape_string($data['html_content']);
        $css_content = isset($data['css_content']) ? sql_escape_string($data['css_content']) : '';
        $js_content = isset($data['js_content']) ? sql_escape_string($data['js_content']) : '';
        $is_public = isset($data['is_public']) ? ($data['is_public'] ? 1 : 0) : 0;
        
        $sql = "INSERT INTO landing_page_blocks
                SET
                    user_id = '$user_id',
                    name = '$name',
                    type = '$type',
                    html_content = '$html_content',
                    css_content = '$css_content',
                    js_content = '$js_content',
                    is_public = '$is_public',
                    created_at = NOW(),
                    updated_at = NOW()";
        
        $result = sql_query($sql, true);
        
        if ($result) {
            // 로그 기록
            write_log('landing_page', 'create_block', array(
                'block_id' => $result,
                'name' => $data['name'],
                'type' => $type
            ));
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * 랜딩페이지 블록 수정
     */
    public function update_block($block_id, $data) {
        $set_values = array();
        
        if (isset($data['name'])) {
            $set_values[] = "name = '" . sql_escape_string($data['name']) . "'";
        }
        
        if (isset($data['type'])) {
            $set_values[] = "type = '" . $data['type'] . "'";
        }
        
        if (isset($data['html_content'])) {
            $set_values[] = "html_content = '" . sql_escape_string($data['html_content']) . "'";
        }
        
        if (isset($data['css_content'])) {
            $set_values[] = "css_content = '" . sql_escape_string($data['css_content']) . "'";
        }
        
        if (isset($data['js_content'])) {
            $set_values[] = "js_content = '" . sql_escape_string($data['js_content']) . "'";
        }
        
        if (isset($data['is_public'])) {
            $set_values[] = "is_public = '" . ($data['is_public'] ? 1 : 0) . "'";
        }
        
        $set_values[] = "updated_at = NOW()";
        
        if (empty($set_values)) {
            return false;
        }
        
        $set_clause = implode(', ', $set_values);
        
        $sql = "UPDATE landing_page_blocks
                SET $set_clause
                WHERE id = '$block_id'";
        
        $result = sql_query($sql);
        
        if ($result) {
            // 로그 기록
            write_log('landing_page', 'update_block', array(
                'block_id' => $block_id,
                'data' => $data
            ));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 랜딩페이지 블록 삭제
     */
    public function delete_block($block_id) {
        // 블록 정보 가져오기 (로그용)
        $block = $this->get_block($block_id);
        
        if (!$block) {
            return false;
        }
        
        $sql = "DELETE FROM landing_page_blocks
                WHERE id = '$block_id'";
        
        $result = sql_query($sql);
        
        if ($result) {
            // 로그 기록
            write_log('landing_page', 'delete_block', array(
                'block_id' => $block_id,
                'name' => $block['name']
            ));
            
            return true;
        }
        
        return false;
    }
}
