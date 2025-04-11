<?php
/**
 * 단축 URL 모델 클래스
 */
class ShorturlModel {
    private $table_prefix;
    
    /**
     * 생성자
     */
    public function __construct() {
        global $cf_table_prefix;
        $this->table_prefix = $cf_table_prefix;
    }
    
    /**
     * 단축 URL 생성
     * 
     * @param array $data 단축 URL 데이터
     * @return int|bool 성공 시 생성된 ID, 실패 시 false
     */
    public function create_shorturl($data) {
        // 필수 필드 검증
        if (empty($data['user_id']) || empty($data['original_url'])) {
            return false;
        }
        
        // 랜덤 코드 생성
        $random_code = $this->generate_random_code();
        
        // 이미 사용 중인 코드인지 확인
        while ($this->check_code_exists($random_code)) {
            $random_code = $this->generate_random_code();
        }
        
        // 도메인 처리
        $domain = !empty($data['domain']) ? $data['domain'] : '';
        
        // URL 경로 구성
        $path_type = isset($data['path_type']) ? $data['path_type'] : 'random';
        
        // URL 구조 결정
        switch ($path_type) {
            case 'campaign_landing':
                // RANDING/캠페인번호/랜딩번호/랜덤코드
                if (!empty($data['campaign_id']) && !empty($data['landing_id'])) {
                    $path = 'RANDING/' . $data['campaign_id'] . '/' . $data['landing_id'] . '/' . $random_code;
                } else {
                    $path = $random_code;
                }
                break;
                
            case 'campaign_only':
                // RANDING/캠페인번호/랜덤코드
                if (!empty($data['campaign_id'])) {
                    $path = 'RANDING/' . $data['campaign_id'] . '/' . $random_code;
                } else {
                    $path = $random_code;
                }
                break;
                
            case 'random':
            default:
                $path = $random_code;
                break;
        }
        
        // 데이터 준비
        $sql_data = array(
            'user_id' => $data['user_id'],
            'campaign_id' => isset($data['campaign_id']) ? $data['campaign_id'] : 0,
            'landing_id' => isset($data['landing_id']) ? $data['landing_id'] : 0,
            'original_url' => $data['original_url'],
            'domain' => $domain,
            'path' => $path,
            'random_code' => $random_code,
            'source_type' => isset($data['source_type']) ? $data['source_type'] : '',
            'source_name' => isset($data['source_name']) ? $data['source_name'] : '',
            'expires_at' => isset($data['expires_at']) ? $data['expires_at'] : null
        );
        
        // 데이터베이스에 저장
        $result = sql_insert($this->table_prefix . 'shortened_urls', $sql_data);
        
        if ($result) {
            // QR 코드 생성
            $this->generate_qr_code($result, $domain, $path);
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * 랜덤 코드 생성
     * 
     * @param int $length 코드 길이
     * @return string 생성된 코드
     */
    private function generate_random_code($length = 6) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        
        return $code;
    }
    
    /**
     * 코드 중복 확인
     * 
     * @param string $code 확인할 코드
     * @return bool 존재하면 true, 아니면 false
     */
    private function check_code_exists($code) {
        $sql = "SELECT COUNT(*) as cnt FROM shortened_urls WHERE random_code = '$code'";
        $row = sql_fetch($sql);
        return ($row['cnt'] > 0);
    }
    
    /**
     * QR 코드 생성 및 저장
     * 
     * @param int $id 단축 URL ID
     * @param string $domain 도메인
     * @param string $path 경로
     * @return bool 성공 여부
     */
    private function generate_qr_code($id, $domain, $path) {
        // QR 코드 생성 로직 (외부 라이브러리 필요)
        // 여기서는 Google Chart API를 사용한 예시
        global $cf_url;
        
        // 전체 URL 생성
        $base_url = !empty($domain) ? $domain : $cf_url;
        $short_url = $base_url . '/' . $path;
        
        $qr_url = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($short_url);
        $qr_file = "/assets/qrcodes/" . $id . ".png";
        $qr_file_path = CF_PATH . $qr_file;
        
        // 디렉토리 존재 여부 확인 및 생성
        $qr_dir = dirname($qr_file_path);
        if (!is_dir($qr_dir)) {
            @mkdir($qr_dir, 0755, true);
        }
        
        // QR 코드 이미지 다운로드
        $img_content = @file_get_contents($qr_url);
        if ($img_content !== false) {
            file_put_contents($qr_file_path, $img_content);
            
            // 데이터베이스 업데이트
            $qr_code_url = $cf_url . $qr_file;
            sql_update($this->table_prefix . 'shortened_urls', array('qr_code_url' => $qr_code_url), array('id' => $id));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 단축 URL 목록 조회
     * 
     * @param int $user_id 사용자 ID
     * @param array $params 검색 조건
     * @param int $offset 시작 위치
     * @param int $limit 조회 개수
     * @return array 단축 URL 목록
     */
    public function get_shorturl_list($user_id, $params = array(), $offset = 0, $limit = 10) {
        $where = "WHERE su.user_id = '$user_id'";
        
        // 검색 조건 추가
        if (!empty($params['campaign_id'])) {
            $where .= " AND su.campaign_id = '{$params['campaign_id']}'";
        }
        
        if (!empty($params['landing_id'])) {
            $where .= " AND su.landing_id = '{$params['landing_id']}'";
        }
        
        if (!empty($params['source_type'])) {
            $where .= " AND su.source_type = '{$params['source_type']}'";
        }
        
        if (!empty($params['search_keyword'])) {
            $keyword = sql_escape_string($params['search_keyword']);
            $where .= " AND (su.original_url LIKE '%$keyword%' OR su.path LIKE '%$keyword%' OR su.random_code LIKE '%$keyword%' OR su.source_name LIKE '%$keyword%')";
        }
        
        // 만료된 URL 필터링
        if (isset($params['show_expired']) && $params['show_expired'] === 'N') {
            $where .= " AND (su.expires_at IS NULL OR su.expires_at > NOW())";
        }
        
        // 총 개수 조회
        $sql = "SELECT COUNT(*) as cnt FROM shortened_urls su $where";
        $row = sql_fetch($sql);
        $total_count = $row['cnt'];
        
        // 목록 조회
        $sql = "SELECT su.*, 
                (SELECT COUNT(*) FROM url_clicks WHERE url_id = su.id) as click_count,
                c.name as campaign_name,
                l.name as landing_name
                FROM shortened_urls su 
                LEFT JOIN campaigns c ON su.campaign_id = c.id 
                LEFT JOIN landing_pages l ON su.landing_id = l.id 
                $where 
                ORDER BY su.created_at DESC 
                LIMIT $offset, $limit";
        
        $result = sql_query($sql);
        $list = array();
        
        while ($row = sql_fetch_array($result)) {
            // 완전한 단축 URL 생성
            $base_url = !empty($row['domain']) ? $row['domain'] : CF_URL;
            $row['short_url'] = $base_url . '/' . $row['path'];
            
            $list[] = $row;
        }
        
        return array(
            'total_count' => $total_count,
            'list' => $list
        );
    }
    
    /**
     * 단축 URL 상세 조회
     * 
     * @param int $id 단축 URL ID
     * @param int $user_id 사용자 ID
     * @return array|bool 단축 URL 정보
     */
    public function get_shorturl($id, $user_id = null) {
        $where = "WHERE su.id = '$id'";
        
        if ($user_id !== null) {
            $where .= " AND su.user_id = '$user_id'";
        }
        
        $sql = "SELECT su.*,
                c.name as campaign_name,
                l.name as landing_name,
                (SELECT COUNT(*) FROM url_clicks WHERE url_id = su.id) as click_count,
                (SELECT COUNT(*) FROM conversion_events ce 
                 JOIN conversion_scripts cs ON ce.script_id = cs.id 
                 WHERE cs.campaign_id = su.campaign_id AND ce.source = su.source_type 
                 AND ce.utm_content = su.source_name) as conversion_count
                FROM shortened_urls su 
                LEFT JOIN campaigns c ON su.campaign_id = c.id 
                LEFT JOIN landing_pages l ON su.landing_id = l.id 
                $where";
        
        $url_info = sql_fetch($sql);
        
        if ($url_info) {
            // 완전한 단축 URL 생성
            $base_url = !empty($url_info['domain']) ? $url_info['domain'] : CF_URL;
            $url_info['short_url'] = $base_url . '/' . $url_info['path'];
        }
        
        return $url_info;
    }
    
    /**
     * 랜덤 코드로 URL 정보 조회
     * 
     * @param string $code 랜덤 코드
     * @return array|bool URL 정보
     */
    public function get_shorturl_by_code($code) {
        $sql = "SELECT * FROM shortened_urls WHERE random_code = '$code'";
        return sql_fetch($sql);
    }
    
    /**
     * 경로로 URL 정보 조회
     * 
     * @param string $path URL 경로
     * @return array|bool URL 정보
     */
    public function get_shorturl_by_path($path) {
        $sql = "SELECT * FROM shortened_urls WHERE path = '$path'";
        return sql_fetch($sql);
    }
    
    /**
     * 단축 URL 수정
     * 
     * @param int $id 단축 URL ID
     * @param array $data 수정 데이터
     * @param int $user_id 사용자 ID
     * @return bool 성공 여부
     */
    public function update_shorturl($id, $data, $user_id = null) {
        // 수정 권한 검증
        if ($user_id !== null) {
            $url = $this->get_shorturl($id);
            if (!$url || $url['user_id'] != $user_id) {
                return false;
            }
        }
        
        // 수정 가능한 필드
        $update_data = array();
        
        if (isset($data['original_url'])) {
            $update_data['original_url'] = $data['original_url'];
        }
        
        if (isset($data['campaign_id'])) {
            $update_data['campaign_id'] = $data['campaign_id'];
        }
        
        if (isset($data['landing_id'])) {
            $update_data['landing_id'] = $data['landing_id'];
        }
        
        if (isset($data['domain'])) {
            $update_data['domain'] = $data['domain'];
        }
        
        if (isset($data['source_type'])) {
            $update_data['source_type'] = $data['source_type'];
        }
        
        if (isset($data['source_name'])) {
            $update_data['source_name'] = $data['source_name'];
        }
        
        if (isset($data['expires_at'])) {
            $update_data['expires_at'] = $data['expires_at'];
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        // URL 경로 재생성 (선택적)
        if ((isset($data['campaign_id']) || isset($data['landing_id'])) && isset($data['update_path']) && $data['update_path']) {
            $url = $this->get_shorturl($id);
            $path_type = 'random';
            
            // 캠페인+랜딩페이지가 있으면 RANDING/캠페인번호/랜딩번호/랜덤코드
            if ((!empty($data['campaign_id']) || !empty($url['campaign_id'])) && 
                (!empty($data['landing_id']) || !empty($url['landing_id']))) {
                $path_type = 'campaign_landing';
                $campaign_id = isset($data['campaign_id']) ? $data['campaign_id'] : $url['campaign_id'];
                $landing_id = isset($data['landing_id']) ? $data['landing_id'] : $url['landing_id'];
                $update_data['path'] = 'RANDING/' . $campaign_id . '/' . $landing_id . '/' . $url['random_code'];
            }
            // 캠페인만 있으면 RANDING/캠페인번호/랜덤코드
            else if (!empty($data['campaign_id']) || !empty($url['campaign_id'])) {
                $path_type = 'campaign_only';
                $campaign_id = isset($data['campaign_id']) ? $data['campaign_id'] : $url['campaign_id'];
                $update_data['path'] = 'RANDING/' . $campaign_id . '/' . $url['random_code'];
            }
            
            // QR 코드 업데이트
            if (isset($update_data['path'])) {
                $domain = isset($data['domain']) ? $data['domain'] : $url['domain'];
                $this->generate_qr_code($id, $domain, $update_data['path']);
            }
        }
        
        return sql_update($this->table_prefix . 'shortened_urls', $update_data, array('id' => $id));
    }
    
    /**
     * 단축 URL 삭제
     * 
     * @param int $id 단축 URL ID
     * @param int $user_id 사용자 ID
     * @return bool 성공 여부
     */
    public function delete_shorturl($id, $user_id = null) {
        // 삭제 권한 검증
        if ($user_id !== null) {
            $url = $this->get_shorturl($id);
            if (!$url || $url['user_id'] != $user_id) {
                return false;
            }
        }
        
        // 클릭 데이터 삭제
        sql_query("DELETE FROM url_clicks WHERE url_id = '$id'");
        
        // QR 코드 이미지 삭제
        $url = $this->get_shorturl($id);
        if ($url && !empty($url['qr_code_url'])) {
            $qr_file = str_replace(CF_URL, CF_PATH, $url['qr_code_url']);
            if (file_exists($qr_file)) {
                @unlink($qr_file);
            }
        }
        
        // 단축 URL 삭제
        return sql_delete($this->table_prefix . 'shortened_urls', array('id' => $id));
    }
    
    /**
     * 클릭 이벤트 기록
     * 
     * @param int $url_id 단축 URL ID
     * @param array $data 클릭 데이터
     * @return int|bool 성공 시 생성된 ID, 실패 시 false
     */
    public function record_click($url_id, $data = array()) {
        // URL 정보 가져오기
        $url_info = $this->get_shorturl($url_id);
        if (!$url_info) {
            return false;
        }
        
        $click_data = array(
            'url_id' => $url_id,
            'campaign_id' => $url_info['campaign_id'],
            'landing_id' => $url_info['landing_id'],
            'ip_address' => isset($data['ip_address']) ? $data['ip_address'] : $_SERVER['REMOTE_ADDR'],
            'user_agent' => isset($data['user_agent']) ? $data['user_agent'] : $_SERVER['HTTP_USER_AGENT'],
            'referrer' => isset($data['referrer']) ? $data['referrer'] : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''),
            'source_type' => $url_info['source_type'],
            'source_name' => $url_info['source_name'],
            'ad_cost' => isset($data['ad_cost']) ? $data['ad_cost'] : 0,
            'impression' => isset($data['impression']) ? $data['impression'] : 0
        );
        
        // 디바이스 타입 결정
        $user_agent = strtolower($click_data['user_agent']);
        
        if (strpos($user_agent, 'mobile') !== false || strpos($user_agent, 'android') !== false || strpos($user_agent, 'iphone') !== false) {
            $click_data['device_type'] = 'mobile';
        } else if (strpos($user_agent, 'tablet') !== false || strpos($user_agent, 'ipad') !== false) {
            $click_data['device_type'] = 'tablet';
        } else {
            $click_data['device_type'] = 'desktop';
        }
        
        // 브라우저 결정
        if (strpos($user_agent, 'chrome') !== false) {
            $click_data['browser'] = 'Chrome';
        } else if (strpos($user_agent, 'firefox') !== false) {
            $click_data['browser'] = 'Firefox';
        } else if (strpos($user_agent, 'safari') !== false) {
            $click_data['browser'] = 'Safari';
        } else if (strpos($user_agent, 'edge') !== false) {
            $click_data['browser'] = 'Edge';
        } else if (strpos($user_agent, 'opera') !== false) {
            $click_data['browser'] = 'Opera';
        } else if (strpos($user_agent, 'msie') !== false || strpos($user_agent, 'trident') !== false) {
            $click_data['browser'] = 'Internet Explorer';
        } else {
            $click_data['browser'] = '기타';
        }
        
        // OS 결정
        if (strpos($user_agent, 'windows') !== false) {
            $click_data['os'] = 'Windows';
        } else if (strpos($user_agent, 'macintosh') !== false || strpos($user_agent, 'mac os') !== false) {
            $click_data['os'] = 'MacOS';
        } else if (strpos($user_agent, 'linux') !== false) {
            $click_data['os'] = 'Linux';
        } else if (strpos($user_agent, 'android') !== false) {
            $click_data['os'] = 'Android';
        } else if (strpos($user_agent, 'iphone') !== false || strpos($user_agent, 'ipad') !== false || strpos($user_agent, 'ipod') !== false) {
            $click_data['os'] = 'iOS';
        } else {
            $click_data['os'] = '기타';
        }
        
        return sql_insert($this->table_prefix . 'url_clicks', $click_data);
    }
    
    /**
     * 클릭 통계 조회
     * 
     * @param int $url_id 단축 URL ID
     * @param string $group_by 그룹화 기준 (day, week, month, device, browser, os)
     * @param string $start_date 시작일
     * @param string $end_date 종료일
     * @return array 통계 데이터
     */
    public function get_click_stats($url_id, $group_by = 'day', $start_date = '', $end_date = '') {
        $where = "WHERE url_id = '$url_id'";
        
        if ($start_date) {
            $where .= " AND click_time >= '$start_date 00:00:00'";
        }
        
        if ($end_date) {
            $where .= " AND click_time <= '$end_date 23:59:59'";
        }
        
        $sql = "SELECT ";
        
        switch ($group_by) {
            case 'day':
                $sql .= "DATE(click_time) as label, COUNT(*) as value";
                $sql .= " FROM url_clicks $where GROUP BY DATE(click_time) ORDER BY DATE(click_time)";
                break;
                
            case 'week':
                $sql .= "DATE_FORMAT(click_time, '%Y-%u') as label, COUNT(*) as value";
                $sql .= " FROM url_clicks $where GROUP BY DATE_FORMAT(click_time, '%Y-%u') ORDER BY label";
                break;
                
            case 'month':
                $sql .= "DATE_FORMAT(click_time, '%Y-%m') as label, COUNT(*) as value";
                $sql .= " FROM url_clicks $where GROUP BY DATE_FORMAT(click_time, '%Y-%m') ORDER BY label";
                break;
                
            case 'device':
                $sql .= "device_type as label, COUNT(*) as value";
                $sql .= " FROM url_clicks $where GROUP BY device_type ORDER BY value DESC";
                break;
                
            case 'browser':
                $sql .= "browser as label, COUNT(*) as value";
                $sql .= " FROM url_clicks $where GROUP BY browser ORDER BY value DESC";
                break;
                
            case 'os':
                $sql .= "os as label, COUNT(*) as value";
                $sql .= " FROM url_clicks $where GROUP BY os ORDER BY value DESC";
                break;
                
            case 'source_type':
                $sql .= "source_type as label, COUNT(*) as value";
                $sql .= " FROM url_clicks $where GROUP BY source_type ORDER BY value DESC";
                break;
                
            case 'source_name':
                $sql .= "source_name as label, COUNT(*) as value";
                $sql .= " FROM url_clicks $where GROUP BY source_name ORDER BY value DESC";
                break;
                
            default:
                $sql .= "DATE(click_time) as label, COUNT(*) as value";
                $sql .= " FROM url_clicks $where GROUP BY DATE(click_time) ORDER BY DATE(click_time)";
                break;
        }
        
        $result = sql_query($sql);
        $stats = array();
        
        while ($row = sql_fetch_array($result)) {
            $stats[] = $row;
        }
        
        return $stats;
    }
    
    /**
     * 캠페인별 URL 성과 통계 조회
     * 
     * @param int $campaign_id 캠페인 ID
     * @param array $params 검색 조건
     * @return array 통계 데이터
     */
    public function get_campaign_url_stats($campaign_id, $params = array()) {
        $where = "WHERE su.campaign_id = '$campaign_id'";
        
        if (!empty($params['start_date'])) {
            $where .= " AND uc.click_time >= '{$params['start_date']} 00:00:00'";
        }
        
        if (!empty($params['end_date'])) {
            $where .= " AND uc.click_time <= '{$params['end_date']} 23:59:59'";
        }
        
        if (!empty($params['landing_id'])) {
            $where .= " AND su.landing_id = '{$params['landing_id']}'";
        }
        
        if (!empty($params['source_type'])) {
            $where .= " AND su.source_type = '{$params['source_type']}'";
        }
        
        // URL별 통계 조회
        $sql = "SELECT 
                su.id, 
                su.path, 
                su.original_url, 
                su.source_type, 
                su.source_name,
                COUNT(uc.id) as click_count,
                SUM(uc.ad_cost) as total_cost,
                SUM(uc.impression) as total_impression,
                (SELECT COUNT(*) FROM conversion_events ce 
                 JOIN conversion_scripts cs ON ce.script_id = cs.id 
                 WHERE cs.campaign_id = su.campaign_id AND ce.source = su.source_type 
                 AND ce.utm_content = su.source_name) as conversion_count
                FROM shortened_urls su
                LEFT JOIN url_clicks uc ON su.id = uc.url_id
                $where
                GROUP BY su.id
                ORDER BY click_count DESC";
        
        $result = sql_query($sql);
        $stats = array();
        
        while ($row = sql_fetch_array($result)) {
            // CTR 계산 (클릭수/노출수)
            $row['ctr'] = ($row['total_impression'] > 0) ? round(($row['click_count'] / $row['total_impression']) * 100, 2) : 0;
            
            // CPC 계산 (비용/클릭수)
            $row['cpc'] = ($row['click_count'] > 0) ? round($row['total_cost'] / $row['click_count'], 2) : 0;
            
            // CPA 계산 (비용/전환수)
            $row['cpa'] = ($row['conversion_count'] > 0) ? round($row['total_cost'] / $row['conversion_count'], 2) : 0;
            
            // 전환율 계산 (전환수/클릭수)
            $row['conversion_rate'] = ($row['click_count'] > 0) ? round(($row['conversion_count'] / $row['click_count']) * 100, 2) : 0;
            
            // 완전한 단축 URL 생성
            $base_url = !empty($row['domain']) ? $row['domain'] : CF_URL;
            $row['short_url'] = $base_url . '/' . $row['path'];
            
            $stats[] = $row;
        }
        
        return $stats;
    }
    
    /**
     * 소재 유형/명 목록 조회
     * 
     * @param int $campaign_id 캠페인 ID (선택)
     * @return array 소재 목록
     */
    public function get_source_types($campaign_id = 0) {
        $where = "";
        if ($campaign_id > 0) {
            $where = "WHERE campaign_id = '$campaign_id'";
        }
        
        // 소재 유형 목록
        $sql = "SELECT DISTINCT source_type FROM shortened_urls $where ORDER BY source_type";
        $result = sql_query($sql);
        $types = array();
        
        while ($row = sql_fetch_array($result)) {
            if (!empty($row['source_type'])) {
                $types[] = $row['source_type'];
            }
        }
        
        return $types;
    }
    
   /**
     * 소재 이름 목록 조회
     * 
     * @param string $source_type 소재 유형
     * @param int $campaign_id 캠페인 ID (선택)
     * @return array 소재 이름 목록
     */
    public function get_source_names($source_type, $campaign_id = 0) {
        $where = "WHERE source_type = '$source_type'";
        
        if ($campaign_id > 0) {
            $where .= " AND campaign_id = '$campaign_id'";
        }
        
        // 소재 이름 목록
        $sql = "SELECT DISTINCT source_name FROM shortened_urls 
                $where AND source_name != '' ORDER BY source_name";
        $result = sql_query($sql);
        $names = array();
        
        while ($row = sql_fetch_array($result)) {
            $names[] = $row['source_name'];
        }
        
        return $names;
    }
    
    /**
     * 단축 URL 일괄 생성
     * 
     * @param array $common_data 공통 데이터
     * @param array $source_list 소재 목록
     * @return array 생성 결과
     */
    public function create_bulk_shorturls($common_data, $source_list) {
        if (empty($common_data['user_id']) || empty($common_data['original_url']) || empty($source_list)) {
            return array(
                'success' => 0,
                'failed' => 0,
                'ids' => array()
            );
        }
        
        $success = 0;
        $failed = 0;
        $ids = array();
        
        foreach ($source_list as $source) {
            // 공통 데이터 복사
            $data = $common_data;
            
            // 소재 정보 추가
            $data['source_type'] = isset($source['type']) ? $source['type'] : '';
            $data['source_name'] = isset($source['name']) ? $source['name'] : '';
            
            // URL에 소재 정보 추가
            $url_has_query = (strpos($data['original_url'], '?') !== false);
            $separator = $url_has_query ? '&' : '?';
            
            // UTM 파라미터 추가
            $utm_params = array();
            
            if (!empty($data['source_type'])) {
                $utm_params[] = 'utm_source=' . urlencode($data['source_type']);
            }
            
            if (!empty($data['source_name'])) {
                $utm_params[] = 'utm_content=' . urlencode($data['source_name']);
            }
            
            if (!empty($common_data['campaign_id'])) {
                // 캠페인 이름 조회
                $sql = "SELECT name FROM campaigns WHERE id = '{$common_data['campaign_id']}'";
                $campaign = sql_fetch($sql);
                
                if ($campaign) {
                    $utm_params[] = 'utm_campaign=' . urlencode($campaign['name']);
                }
            }
            
            if (!empty($utm_params)) {
                $data['original_url'] .= $separator . implode('&', $utm_params);
            }
            
            // 단축 URL 생성
            $result = $this->create_shorturl($data);
            
            if ($result) {
                $success++;
                $ids[] = $result;
            } else {
                $failed++;
            }
        }
        
        return array(
            'success' => $success,
            'failed' => $failed,
            'ids' => $ids
        );
    }
    
    /**
     * 통계 대시보드용 요약 데이터 조회
     * 
     * @param int $user_id 사용자 ID
     * @param array $params 검색 조건
     * @return array 통계 데이터
     */
    public function get_dashboard_stats($user_id, $params = array()) {
        $campaign_where = '';
        $url_where = "WHERE su.user_id = '$user_id'";
        $click_where = '';
        
        // 기간 필터링
        if (!empty($params['start_date'])) {
            $click_where .= " AND uc.click_time >= '{$params['start_date']} 00:00:00'";
        }
        
        if (!empty($params['end_date'])) {
            $click_where .= " AND uc.click_time <= '{$params['end_date']} 23:59:59'";
        }
        
        // 캠페인 필터링
        if (!empty($params['campaign_id'])) {
            $campaign_id = $params['campaign_id'];
            $campaign_where .= " AND c.id = '$campaign_id'";
            $url_where .= " AND su.campaign_id = '$campaign_id'";
        }
        
        // 랜딩페이지 필터링
        if (!empty($params['landing_id'])) {
            $landing_id = $params['landing_id'];
            $url_where .= " AND su.landing_id = '$landing_id'";
        }
        
        // 소재 유형 필터링
        if (!empty($params['source_type'])) {
            $source_type = $params['source_type'];
            $url_where .= " AND su.source_type = '$source_type'";
        }
        
        // 총 URL 수
        $sql = "SELECT COUNT(*) as total_urls FROM shortened_urls su $url_where";
        $row = sql_fetch($sql);
        $total_urls = $row['total_urls'];
        
        // 총 클릭수
        $sql = "SELECT COUNT(*) as total_clicks 
                FROM url_clicks uc 
                JOIN shortened_urls su ON uc.url_id = su.id 
                WHERE su.user_id = '$user_id' $click_where";
        $row = sql_fetch($sql);
        $total_clicks = $row['total_clicks'];
        
        // 일별 클릭 추이
        $sql = "SELECT DATE(uc.click_time) as date, COUNT(*) as clicks 
                FROM url_clicks uc 
                JOIN shortened_urls su ON uc.url_id = su.id 
                WHERE su.user_id = '$user_id' $click_where 
                GROUP BY DATE(uc.click_time) 
                ORDER BY DATE(uc.click_time) DESC 
                LIMIT 30";
        $result = sql_query($sql);
        $daily_clicks = array();
        
        while ($row = sql_fetch_array($result)) {
            $daily_clicks[] = $row;
        }
        
        // 디바이스별 클릭 비율
        $sql = "SELECT uc.device_type, COUNT(*) as clicks 
                FROM url_clicks uc 
                JOIN shortened_urls su ON uc.url_id = su.id 
                WHERE su.user_id = '$user_id' $click_where 
                GROUP BY uc.device_type 
                ORDER BY clicks DESC";
        $result = sql_query($sql);
        $device_stats = array();
        
        while ($row = sql_fetch_array($result)) {
            $device_stats[] = $row;
        }
        
        // 소재 유형별 클릭 비율
        $sql = "SELECT su.source_type, COUNT(uc.id) as clicks 
                FROM url_clicks uc 
                JOIN shortened_urls su ON uc.url_id = su.id 
                WHERE su.user_id = '$user_id' AND su.source_type != '' $click_where 
                GROUP BY su.source_type 
                ORDER BY clicks DESC";
        $result = sql_query($sql);
        $source_stats = array();
        
        while ($row = sql_fetch_array($result)) {
            $source_stats[] = $row;
        }
        
        // 캠페인별 클릭 비율
        $sql = "SELECT c.id, c.name, COUNT(uc.id) as clicks 
                FROM url_clicks uc 
                JOIN shortened_urls su ON uc.url_id = su.id 
                JOIN campaigns c ON su.campaign_id = c.id 
                WHERE su.user_id = '$user_id' AND su.campaign_id > 0 $campaign_where $click_where 
                GROUP BY su.campaign_id 
                ORDER BY clicks DESC
                LIMIT 10";
        $result = sql_query($sql);
        $campaign_stats = array();
        
        while ($row = sql_fetch_array($result)) {
            $campaign_stats[] = $row;
        }
        
        // 클릭이 가장 많은 URL 목록
        $sql = "SELECT su.id, su.path, su.original_url, su.domain, su.source_type, su.source_name, 
                COUNT(uc.id) as clicks 
                FROM url_clicks uc 
                JOIN shortened_urls su ON uc.url_id = su.id 
                WHERE su.user_id = '$user_id' $click_where 
                GROUP BY su.id 
                ORDER BY clicks DESC 
                LIMIT 10";
        $result = sql_query($sql);
        $top_urls = array();
        
        while ($row = sql_fetch_array($result)) {
            // 완전한 단축 URL 생성
            $base_url = !empty($row['domain']) ? $row['domain'] : CF_URL;
            $row['short_url'] = $base_url . '/' . $row['path'];
            
            $top_urls[] = $row;
        }
        
        return array(
            'total_urls' => $total_urls,
            'total_clicks' => $total_clicks,
            'daily_clicks' => $daily_clicks,
            'device_stats' => $device_stats,
            'source_stats' => $source_stats,
            'campaign_stats' => $campaign_stats,
            'top_urls' => $top_urls
        );
    }
    
    /**
     * 경로 파싱 (RANDING/캠페인번호/랜딩번호/랜덤코드 형식)
     * 
     * @param string $path 경로
     * @return array 파싱 결과
     */
    public function parse_path($path) {
        $result = array(
            'campaign_id' => 0,
            'landing_id' => 0,
            'random_code' => $path,
            'type' => 'random'
        );
        
        // RANDING/캠페인번호/랜딩번호/랜덤코드 형식인지 확인
        if (strpos($path, 'RANDING/') === 0) {
            $parts = explode('/', $path);
            
            if (count($parts) >= 3) {
                $result['type'] = 'campaign_only';
                $result['campaign_id'] = intval($parts[1]);
                $result['random_code'] = $parts[2];
                
                // 랜딩페이지 번호가 있는 경우
                if (count($parts) >= 4) {
                    $result['type'] = 'campaign_landing';
                    $result['landing_id'] = intval($parts[2]);
                    $result['random_code'] = $parts[3];
                }
            }
        }
        
        return $result;
    }
    
    /**
     * 랜딩페이지 정보 조회
     * 
     * @param int $id 랜딩페이지 ID
     * @return array|bool 랜딩페이지 정보
     */
    public function get_landing_page($id) {
        $sql = "SELECT * FROM landing_pages WHERE id = '$id'";
        return sql_fetch($sql);
    }
    
    /**
     * 캠페인 정보 조회
     * 
     * @param int $id 캠페인 ID
     * @return array|bool 캠페인 정보
     */
    public function get_campaign($id) {
        $sql = "SELECT * FROM campaigns WHERE id = '$id'";
        return sql_fetch($sql);
    }
    
    /**
     * URL 만료 여부 확인
     * 
     * @param array $url URL 정보
     * @return bool 만료 여부
     */
    public function is_expired($url) {
        if (empty($url['expires_at'])) {
            return false;
        }
        
        return (strtotime($url['expires_at']) < time());
    }
    
    /**
     * 일괄 URL 비활성화
     * 
     * @param array $ids URL ID 배열
     * @param int $user_id 사용자 ID
     * @return int 비활성화된 URL 수
     */
    public function deactivate_urls($ids, $user_id = null) {
        if (empty($ids)) {
            return 0;
        }
        
        $id_list = implode(',', array_map('intval', $ids));
        $where = "id IN ($id_list)";
        
        if ($user_id !== null) {
            $where .= " AND user_id = '$user_id'";
        }
        
        $expires_at = date('Y-m-d H:i:s');
        
        $sql = "UPDATE shortened_urls 
                SET expires_at = '$expires_at' 
                WHERE $where";
                
        sql_query($sql);
        return sql_affected_rows();
    }
    
    /**
     * 일괄 URL 활성화
     * 
     * @param array $ids URL ID 배열
     * @param int $user_id 사용자 ID
     * @return int 활성화된 URL 수
     */
    public function activate_urls($ids, $user_id = null) {
        if (empty($ids)) {
            return 0;
        }
        
        $id_list = implode(',', array_map('intval', $ids));
        $where = "id IN ($id_list)";
        
        if ($user_id !== null) {
            $where .= " AND user_id = '$user_id'";
        }
        
        $sql = "UPDATE shortened_urls 
                SET expires_at = NULL 
                WHERE $where";
                
        sql_query($sql);
        return sql_affected_rows();
    }
    
    /**
     * 사용자 도메인 목록 조회
     * 
     * @param int $user_id 사용자 ID
     * @return array 도메인 목록
     */
    public function get_user_domains($user_id) {
        $sql = "SELECT DISTINCT domain 
                FROM shortened_urls 
                WHERE user_id = '$user_id' AND domain != '' 
                ORDER BY domain";
        $result = sql_query($sql);
        $domains = array();
        
        while ($row = sql_fetch_array($result)) {
            $domains[] = $row['domain'];
        }
        
        return $domains;
    }
    
    /**
     * 단축 URL 복제
     * 
     * @param int $id 단축 URL ID
     * @param array $overrides 덮어쓸 데이터
     * @param int $user_id 사용자 ID
     * @return int|bool 성공 시 새 ID, 실패 시 false
     */
    public function duplicate_shorturl($id, $overrides = array(), $user_id = null) {
        // 원본 URL 정보 조회
        $url = $this->get_shorturl($id, $user_id);
        if (!$url) {
            return false;
        }
        
        // 복제 데이터 준비
        $data = array(
            'user_id' => $url['user_id'],
            'campaign_id' => $url['campaign_id'],
            'landing_id' => $url['landing_id'],
            'original_url' => $url['original_url'],
            'domain' => $url['domain'],
            'source_type' => $url['source_type'],
            'source_name' => $url['source_name'] . ' (복사)',
            'path_type' => $this->get_path_type($url['path']),
            'expires_at' => $url['expires_at']
        );
        
        // 덮어쓸 데이터 적용
        foreach ($overrides as $key => $value) {
            $data[$key] = $value;
        }
        
        // 새 URL 생성
        return $this->create_shorturl($data);
    }
    
    /**
     * 경로 타입 결정
     * 
     * @param string $path URL 경로
     * @return string 경로 타입 (campaign_landing, campaign_only, random)
     */
    private function get_path_type($path) {
        if (strpos($path, 'RANDING/') === 0) {
            $parts = explode('/', $path);
            
            if (count($parts) >= 4) {
                return 'campaign_landing';
            } elseif (count($parts) >= 3) {
                return 'campaign_only';
            }
        }
        
        return 'random';
    }
}