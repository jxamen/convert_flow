<?php
/**
 * 캠페인 모델 클래스
 * 
 * 캠페인 관련 데이터 처리를 담당하는 모델 클래스입니다.
 */
class CampaignModel {
    /**
     * 모든 캠페인 목록을 가져옵니다.
     * 
     * @param int $user_id 사용자 ID
     * @param array $params 검색 조건
     * @return array 캠페인 목록
     */
    public function get_campaigns($user_id, $params = array()) {
        $where = array();
        $where[] = "user_id = '{$user_id}'";
        
        // 검색 조건 처리
        if (!empty($params['search_keyword'])) {
            $search_keyword = trim(sql_escape_string($params['search_keyword']));
            $where[] = "(name LIKE '%{$search_keyword}%' OR description LIKE '%{$search_keyword}%')";
        }
        
        if (!empty($params['status'])) {
            $status = sql_escape_string($params['status']);
            $where[] = "status = '{$status}'";
        }
        
        if (!empty($params['start_date'])) {
            $start_date = sql_escape_string($params['start_date']);
            $where[] = "start_date >= '{$start_date}'";
        }
        
        if (!empty($params['end_date'])) {
            $end_date = sql_escape_string($params['end_date']);
            $where[] = "end_date <= '{$end_date}'";
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
        
        // 전체 캠페인 수 조회
        $count_sql = "SELECT COUNT(*) as cnt FROM campaigns WHERE {$where_str}";
        $row = sql_fetch($count_sql);
        $total_count = $row['cnt'];
        
        // 캠페인 목록 조회
        $sql = "SELECT * FROM campaigns WHERE {$where_str} ORDER BY {$order_by} LIMIT {$start}, {$items_per_page}";
        $result = sql_query($sql);
            
        $campaigns = array();
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            // 각 캠페인의 통계 데이터 추가
            $row['statistics'] = $this->get_campaign_statistics($row['id']);
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
    
    /**
     * 캠페인 정보를 가져옵니다.
     * 
     * @param int $campaign_id 캠페인 ID
     * @param int $user_id 사용자 ID (권한 체크용)
     * @return array|false 캠페인 정보 또는 false
     */
    public function get_campaign($campaign_id, $user_id = null) {
        $campaign_id = intval($campaign_id);
        
        $where = "id = '{$campaign_id}'";
        if ($user_id !== null) {
            $user_id = intval($user_id);
            $where .= " AND user_id = '{$user_id}'";
        }
        
        $sql = "SELECT * FROM campaigns WHERE {$where}";
        $campaign = sql_fetch($sql);
        
        if (!$campaign) {
            return false;
        }
        
        // 캠페인 통계 추가
        $campaign['statistics'] = $this->get_campaign_statistics($campaign_id);
        
        // 캠페인에 연결된 광고 그룹 수
        $sql = "SELECT COUNT(*) as cnt FROM ad_groups WHERE campaign_id = '{$campaign_id}'";
        $row = sql_fetch($sql);
        $campaign['ad_group_count'] = $row['cnt'];
        
        // 캠페인에 연결된 광고 수
        $sql = "SELECT COUNT(*) as cnt FROM ad_materials WHERE campaign_id = '{$campaign_id}'";
        $row = sql_fetch($sql);
        $campaign['ad_count'] = $row['cnt'];
        
        return $campaign;
    }
    
    /**
     * 캠페인 통계 데이터를 가져옵니다.
     * 
     * @param int $campaign_id 캠페인 ID
     * @param string $start_date 시작일 (YYYY-MM-DD)
     * @param string $end_date 종료일 (YYYY-MM-DD)
     * @return array 통계 데이터
     */
    public function get_campaign_statistics($campaign_id, $start_date = null, $end_date = null) {
        $campaign_id = intval($campaign_id);
    
        // 기본값: 최근 30일
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $start_date = sql_escape_string($start_date);
        $end_date = sql_escape_string($end_date);
        //$limit = intval($limit);
        
        // 클릭 및 비용 데이터 (URL 클릭 이벤트 기반) - LIMIT 추가
        $sql = "SELECT 
                    SUM(uc.impression) as impressions,
                    COUNT(uc.id) as clicks,
                    SUM(uc.ad_cost) as cost
                FROM url_clicks uc
                WHERE uc.campaign_id = '{$campaign_id}'
                AND DATE(uc.click_time) BETWEEN '{$start_date}' AND '{$end_date}'";
                // LIMIT {$limit}
        $stats = sql_fetch($sql);
        
        // 전환 데이터 (conversion_events_log 테이블 기반) - LIMIT 추가
        $sql = "SELECT 
                    COUNT(cel.id) as conversions,
                    SUM(cel.conversion_value) as conversion_value
                FROM conversion_events_log cel
                JOIN landing_pages lp ON cel.landing_page_id = lp.id
                WHERE lp.campaign_id = '{$campaign_id}'
                AND DATE(cel.event_time) BETWEEN '{$start_date}' AND '{$end_date}'";
                //LIMIT {$limit}
        $conversion_stats = sql_fetch($sql);
        
        // 통계 데이터 병합
        $statistics = array(
            'impressions' => (int)$stats['impressions'] ?: 0,
            'clicks' => (int)$stats['clicks'] ?: 0,
            'cost' => (float)$stats['cost'] ?: 0,
            'conversions' => (int)$conversion_stats['conversions'] ?: 0,
            'conversion_value' => (float)$conversion_stats['conversion_value'] ?: 0,
            'start_date' => $start_date,
            'end_date' => $end_date
        );
        
        // 계산된 지표 추가
        $statistics['ctr'] = $statistics['impressions'] > 0 ? ($statistics['clicks'] / $statistics['impressions']) * 100 : 0;
        $statistics['cpc'] = $statistics['clicks'] > 0 ? $statistics['cost'] / $statistics['clicks'] : 0;
        $statistics['cpa'] = $statistics['conversions'] > 0 ? $statistics['cost'] / $statistics['conversions'] : 0;
        $statistics['roas'] = $statistics['cost'] > 0 ? ($statistics['conversion_value'] / $statistics['cost']) * 100 : 0;
        
        return $statistics;
    }
    
    /**
     * 일별 캠페인 통계 데이터를 가져옵니다.
     * 
     * @param int $campaign_id 캠페인 ID
     * @param string $start_date 시작일 (YYYY-MM-DD)
     * @param string $end_date 종료일 (YYYY-MM-DD)
     * @return array 일별 통계 데이터
     */
    public function get_daily_statistics($campaign_id, $start_date = null, $end_date = null) {
        $campaign_id = intval($campaign_id);
        
        // 기본값: 최근 30일
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $start_date = sql_escape_string($start_date);
        $end_date = sql_escape_string($end_date);
        
        // 일자별 클릭 및 비용 데이터 (테이블 구조 변경)
        $sql = "SELECT 
                    cost_date as date,
                    SUM(impressions) as impressions,
                    SUM(clicks) as clicks,
                    SUM(ad_cost) as cost
                FROM ad_costs
                WHERE campaign_id = '{$campaign_id}'
                AND cost_date BETWEEN '{$start_date}' AND '{$end_date}'
                GROUP BY cost_date
                ORDER BY cost_date";
        $result = sql_query($sql);
        
        $click_stats = array();
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            $click_stats[$row['date']] = array(
                'date' => $row['date'],
                'impressions' => (int)$row['impressions'] ?: 0,
                'clicks' => (int)$row['clicks'] ?: 0,
                'cost' => (float)$row['cost'] ?: 0
            );
        }
        
        // 일자별 전환 데이터
        $sql = "SELECT 
                    DATE(event_time) as date,
                    COUNT(id) as conversions,
                    SUM(conversion_value) as conversion_value
                FROM conversion_events_log
                WHERE campaign_id = '{$campaign_id}'
                AND DATE(event_time) BETWEEN '{$start_date}' AND '{$end_date}'
                GROUP BY DATE(event_time)
                ORDER BY DATE(event_time)";
        $result = sql_query($sql);
        
        $conversion_stats = array();
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            $conversion_stats[$row['date']] = array(
                'conversions' => (int)$row['conversions'] ?: 0,
                'conversion_value' => (float)$row['conversion_value'] ?: 0
            );
        }
        
        // 날짜 범위 내 모든 날짜 생성
        $current_date = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);
        $daily_stats = array();
        
        while ($current_date <= $end_date_obj) {
            $date_str = $current_date->format('Y-m-d');
            
            $stats = array(
                'date' => $date_str,
                'impressions' => 0,
                'clicks' => 0,
                'cost' => 0,
                'conversions' => 0,
                'conversion_value' => 0
            );
            
            // 클릭 통계 병합
            if (isset($click_stats[$date_str])) {
                $stats['impressions'] = $click_stats[$date_str]['impressions'];
                $stats['clicks'] = $click_stats[$date_str]['clicks'];
                $stats['cost'] = $click_stats[$date_str]['cost'];
            }
            
            // 전환 통계 병합
            if (isset($conversion_stats[$date_str])) {
                $stats['conversions'] = $conversion_stats[$date_str]['conversions'];
                $stats['conversion_value'] = $conversion_stats[$date_str]['conversion_value'];
            }
            
            // 계산된 지표 추가
            $stats['ctr'] = $stats['impressions'] > 0 ? ($stats['clicks'] / $stats['impressions']) * 100 : 0;
            $stats['cpc'] = $stats['clicks'] > 0 ? $stats['cost'] / $stats['clicks'] : 0;
            $stats['cpa'] = $stats['conversions'] > 0 ? $stats['cost'] / $stats['conversions'] : 0;
            $stats['roas'] = $stats['cost'] > 0 ? ($stats['conversion_value'] / $stats['cost']) * 100 : 0;
            
            $daily_stats[] = $stats;
            $current_date->modify('+1 day');
        }
        
        return $daily_stats;
    }
    
    /**
     * 캠페인을 추가합니다.
     * 
     * @param array $data 캠페인 데이터
     * @return int|false 추가된 캠페인 ID 또는 false
     */
    public function add_campaign($data) {
        // 필수 필드 검증
        if (empty($data['user_id']) || empty($data['name']) || empty($data['start_date'])) {
            return false;
        }
        
        $user_id = intval($data['user_id']);
        $name = sql_escape_string($data['name']);
        $status = isset($data['status']) ? sql_escape_string($data['status']) : '활성';
        $start_date = sql_escape_string($data['start_date']);
        $end_date = !empty($data['end_date']) ? "'{$data['end_date']}'" : "NULL";
        $budget = !empty($data['budget']) ? floatval($data['budget']) : 0;
        $daily_budget = !empty($data['daily_budget']) ? floatval($data['daily_budget']) : 0;
        $cpa_goal = !empty($data['cpa_goal']) ? floatval($data['cpa_goal']) : 0;
        $description = !empty($data['description']) ? sql_escape_string($data['description']) : '';
        
        $sql = "INSERT INTO campaigns (
                    user_id, name, status, start_date, end_date, 
                    budget, daily_budget, cpa_goal, description, created_at
                ) VALUES (
                    '{$user_id}', '{$name}', '{$status}', '{$start_date}', {$end_date}, 
                    '{$budget}', '{$daily_budget}', '{$cpa_goal}', '{$description}', NOW()
                )";
        
        sql_query($sql);
        $campaign_id = sql_insert_id();
        
        if ($campaign_id) {
            // 캠페인 해시 생성 (URL에서 사용)
            $hash = substr(md5($campaign_id . time()), 0, CF_CAMPAIGN_HASH_LENGTH);
            sql_query("UPDATE campaigns SET campaign_hash = '{$hash}' WHERE id = '{$campaign_id}'");
            
            return $campaign_id;
        }
        
        return false;
    }
    
    /**
     * 캠페인을 수정합니다.
     * 
     * @param int $campaign_id 캠페인 ID
     * @param array $data 수정할 데이터
     * @return bool 성공 여부
     */
    public function update_campaign($campaign_id, $data) {
        $campaign_id = intval($campaign_id);
        
        // 기존 캠페인 정보 확인
        $campaign = $this->get_campaign($campaign_id);
        if (!$campaign) {
            return false;
        }
        
        $updates = array();
        
        if (isset($data['name'])) {
            $updates[] = "name = '" . sql_escape_string($data['name']) . "'";
        }
        
        if (isset($data['status'])) {
            $updates[] = "status = '" . sql_escape_string($data['status']) . "'";
        }
        
        if (isset($data['start_date'])) {
            $updates[] = "start_date = '" . sql_escape_string($data['start_date']) . "'";
        }
        
        if (isset($data['end_date'])) {
            if (empty($data['end_date'])) {
                $updates[] = "end_date = NULL";
            } else {
                $updates[] = "end_date = '" . sql_escape_string($data['end_date']) . "'";
            }
        }
        
        if (isset($data['budget'])) {
            $updates[] = "budget = '" . floatval($data['budget']) . "'";
        }
        
        if (isset($data['daily_budget'])) {
            $updates[] = "daily_budget = '" . floatval($data['daily_budget']) . "'";
        }
        
        if (isset($data['cpa_goal'])) {
            $updates[] = "cpa_goal = '" . floatval($data['cpa_goal']) . "'";
        }
        
        if (isset($data['description'])) {
            $updates[] = "description = '" . sql_escape_string($data['description']) . "'";
        }
        
        $updates[] = "updated_at = NOW()";
        
        if (empty($updates)) {
            return true; // 변경할 내용이 없음
        }
        
        $update_str = implode(', ', $updates);
        $sql = "UPDATE campaigns SET {$update_str} WHERE id = '{$campaign_id}'";
        
        return sql_query($sql);
    }
    
    /**
     * 캠페인 상태를 변경합니다.
     * 
     * @param int $campaign_id 캠페인 ID
     * @param string $status 새 상태 (활성, 비활성, 일시중지)
     * @return bool 성공 여부
     */
    public function change_campaign_status($campaign_id, $status) {
        $campaign_id = intval($campaign_id);
        $status = sql_escape_string($status);
        
        $sql = "UPDATE campaigns SET status = '{$status}', updated_at = NOW() WHERE id = '{$campaign_id}'";
        return sql_query($sql);
    }
    
    /**
     * 캠페인을 삭제합니다.
     * 
     * @param int $campaign_id 캠페인 ID
     * @return bool 성공 여부
     */
    public function delete_campaign($campaign_id) {
        $campaign_id = intval($campaign_id);
        
        // 캠페인 관련 데이터 삭제 (광고 그룹, 광고 등)
        // 주의: 참조 무결성을 위해 외래 키 제약조건이 설정되어 있는 경우 불필요
        
        $sql = "DELETE FROM campaigns WHERE id = '{$campaign_id}'";
        return sql_query($sql);
    }
    
    /**
     * 캠페인 템플릿 목록을 가져옵니다.
     * 
     * @param int $user_id 사용자 ID
     * @return array 템플릿 목록
     */
    public function get_campaign_templates($user_id) {
        $user_id = intval($user_id);
        
        $sql = "SELECT * FROM campaign_templates WHERE user_id = '{$user_id}' ORDER BY name ASC";
        $result = sql_query($sql);
        
        $templates = array();
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            $templates[] = $row;
        }
        
        return $templates;
    }
    
    /**
     * 캠페인 템플릿을 가져옵니다.
     * 
     * @param int $template_id 템플릿 ID
     * @return array|false 템플릿 정보 또는 false
     */
    public function get_campaign_template($template_id) {
        $template_id = intval($template_id);
        
        $sql = "SELECT * FROM campaign_templates WHERE id = '{$template_id}'";
        return sql_fetch($sql);
    }
    
    /**
     * 캠페인의 성과를 분석하여 순위를 매깁니다.
     * 
     * @param int $user_id 사용자 ID
     * @param string $metric 기준 지표 (ctr, cpc, cpa, roas)
     * @param int $limit 반환할 캠페인 수
     * @return array 캠페인 목록 (성과 기준 정렬)
     */
    public function get_top_performing_campaigns($user_id, $metric = 'roas', $limit = 5) {
        $user_id = intval($user_id);
        $limit = intval($limit);
        
        // 캠페인 목록 조회
        $sql = "SELECT * FROM campaigns WHERE user_id = '{$user_id}' AND status = '활성' ORDER BY created_at DESC";
        $result = sql_query($sql);
        
        $campaigns = array();
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            // 각 캠페인의 통계 데이터 추가
            $row['statistics'] = $this->get_campaign_statistics($row['id']);
            $campaigns[] = $row;
        }
        
        // 성과 지표 기준으로 정렬
        usort($campaigns, function($a, $b) use ($metric) {
            // 지표가 높을수록 좋은 것들 (CTR, ROAS)
            if (in_array($metric, array('ctr', 'roas'))) {
                return $b['statistics'][$metric] <=> $a['statistics'][$metric];
            }
            // 지표가 낮을수록 좋은 것들 (CPC, CPA)
            else {
                return $a['statistics'][$metric] <=> $b['statistics'][$metric];
            }
        });
        
        // 상위 N개만 반환
        return array_slice($campaigns, 0, $limit);
    }
}