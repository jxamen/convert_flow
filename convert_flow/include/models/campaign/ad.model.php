<?php
/**
 * 광고 모델 클래스
 * 
 * 광고 관련 데이터 처리를 담당하는 모델 클래스입니다.
 */
class AdModel {
    /**
     * 광고 그룹의 광고 목록을 가져옵니다.
     * 
     * @param int $ad_group_id 광고 그룹 ID
     * @param array $params 검색 조건
     * @return array 광고 목록
     */
    public function get_ads_by_ad_group($ad_group_id, $params = array()) {
        // 광고 그룹 정보 가져오기
        $ad_group_model = new AdGroupModel();
        $ad_group = $ad_group_model->get_ad_group($ad_group_id);
        
        if (!$ad_group) {
            return array(
                'ads' => array(),
                'total_count' => 0,
                'page' => 1,
                'items_per_page' => 20,
                'total_pages' => 0
            );
        }
        
        $external_ad_group_id = $ad_group['external_ad_group_id'];
        
        $where = array("external_ad_group_id = '{$external_ad_group_id}'");
        
        // 검색 조건 처리
        if (!empty($params['search_keyword'])) {
            $search_keyword = trim(sql_escape_string($params['search_keyword']));
            $where[] = "(headline LIKE '%{$search_keyword}%' OR description LIKE '%{$search_keyword}%')";
        }
        
        if (!empty($params['status'])) {
            $status = sql_escape_string($params['status']);
            $where[] = "status = '{$status}'";
        }
        
        if (!empty($params['ad_type'])) {
            $ad_type = sql_escape_string($params['ad_type']);
            $where[] = "ad_type = '{$ad_type}'";
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
        
        // 전체 광고 수 조회
        $count_sql = "SELECT COUNT(*) as cnt FROM ad_materials WHERE {$where_str}";
        $row = sql_fetch($count_sql);
        $total_count = $row['cnt'];
        
        // 광고 목록 조회
        $sql = "SELECT * FROM ad_materials WHERE {$where_str} ORDER BY {$order_by} LIMIT {$start}, {$items_per_page}";
        $result = sql_query($sql);
        
        $ads = array();
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            // 각 광고의 통계 데이터 추가
            $row['statistics'] = $this->get_ad_statistics($row['id']);
            $ads[] = $row;
        }
        
        return array(
            'ads' => $ads,
            'total_count' => $total_count,
            'page' => $page,
            'items_per_page' => $items_per_page,
            'total_pages' => ceil($total_count / $items_per_page)
        );
    }
    
    /**
     * 캠페인의 모든 광고 목록을 가져옵니다.
     * 
     * @param int $campaign_id 캠페인 ID
     * @param array $params 검색 조건
     * @return array 광고 목록
     */
    public function get_ads_by_campaign($campaign_id, $params = array()) {
        $campaign_id = intval($campaign_id);
        
        $where = array("a.campaign_id = '{$campaign_id}'");
        
        // 검색 조건 처리
        if (!empty($params['search_keyword'])) {
            $search_keyword = trim(sql_escape_string($params['search_keyword']));
            $where[] = "(a.headline LIKE '%{$search_keyword}%' OR a.description LIKE '%{$search_keyword}%')";
        }
        
        if (!empty($params['status'])) {
            $status = sql_escape_string($params['status']);
            $where[] = "a.status = '{$status}'";
        }
        
        if (!empty($params['ad_type'])) {
            $ad_type = sql_escape_string($params['ad_type']);
            $where[] = "a.ad_type = '{$ad_type}'";
        }
        
        if (!empty($params['ad_group_id'])) {
            $ad_group_id = intval($params['ad_group_id']);
            $where[] = "a.ad_group_id = '{$ad_group_id}'";
        }
        
        $where_str = implode(' AND ', $where);
        
        // 정렬 조건
        $order_by = "a.created_at DESC";
        if (!empty($params['sort_field']) && !empty($params['sort_order'])) {
            $sort_field = sql_escape_string($params['sort_field']);
            $sort_order = sql_escape_string($params['sort_order']);
            $order_by = "a.{$sort_field} {$sort_order}";
        }
        
        // 페이징 처리
        $page = isset($params['page']) ? max(1, intval($params['page'])) : 1;
        $items_per_page = isset($params['items_per_page']) ? intval($params['items_per_page']) : 20;
        $start = ($page - 1) * $items_per_page;
        
        // 전체 광고 수 조회
        $count_sql = "SELECT COUNT(*) as cnt FROM ad_materials a WHERE {$where_str}";
        $row = sql_fetch($sql);
        $total_count = $row['cnt'];
        
        // 광고 목록 조회 (광고 그룹 정보 포함)
        $sql = "SELECT a.*, ag.name as ad_group_name 
                FROM ad_materials a 
                LEFT JOIN ad_groups ag ON a.external_ad_group_id = ag.external_ad_group_id
                WHERE {$where_str} 
                ORDER BY {$order_by} 
                LIMIT {$start}, {$items_per_page}";
        $result = sql_query($sql);
        
        $ads = array();
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            // 각 광고의 통계 데이터 추가
            $row['statistics'] = $this->get_ad_statistics($row['id']);
            $ads[] = $row;
        }
        
        return array(
            'ads' => $ads,
            'total_count' => $total_count,
            'page' => $page,
            'items_per_page' => $items_per_page,
            'total_pages' => ceil($total_count / $items_per_page)
        );
    }
    
    /**
     * 특정 광고 정보를 가져옵니다.
     * 
     * @param int $ad_id 광고 ID
     * @return array|false 광고 정보 또는 false
     */
    public function get_ad($ad_id) {
        $ad_id = intval($ad_id);
        
        $sql = "SELECT a.*, ag.name as ad_group_name, c.name as campaign_name
                FROM ad_materials a
                LEFT JOIN ad_groups ag ON a.external_ad_group_id = ag.external_ad_group_id
                LEFT JOIN campaigns c ON a.campaign_id = c.id
                WHERE a.id = '{$ad_id}'";
        $ad = sql_fetch($sql);
        
        if (!$ad) {
            return false;
        }
        
        // 광고 통계 추가
        $ad['statistics'] = $this->get_ad_statistics($ad_id);
        
        // 추가 정보 파싱
        if (!empty($ad['external_data'])) {
            $ad['external_data_parsed'] = json_decode($ad['external_data'], true);
        }
        
        return $ad;
    }
    
    /**
     * 광고 통계 데이터를 가져옵니다.
     * 
     * @param int $ad_id 광고 ID
     * @param string $start_date 시작일 (YYYY-MM-DD)
     * @param string $end_date 종료일 (YYYY-MM-DD)
     * @return array 통계 데이터
     */
    public function get_ad_statistics($ad_id, $start_date = null, $end_date = null, $limit = 1000) {
        $ad_id = intval($ad_id);
        
        // 기본값: 최근 30일
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $start_date = sql_escape_string($start_date);
        $end_date = sql_escape_string($end_date);
        $limit = intval($limit);
        
        // 클릭 및 비용 데이터 (URL 클릭 이벤트 기반)
        $sql = "SELECT 
                    SUM(uc.impression) as impressions,
                    COUNT(uc.id) as clicks,
                    SUM(uc.ad_cost) as cost
                FROM url_clicks uc
                WHERE uc.ad_material_id = '{$ad_id}'
                AND DATE(uc.click_time) BETWEEN '{$start_date}' AND '{$end_date}'
                LIMIT {$limit}";
        $stats = sql_fetch($sql);
        
        // 전환 데이터 (conversion_events_log 테이블 기반)
        $sql = "SELECT 
                    COUNT(cel.id) as conversions,
                    SUM(cel.conversion_value) as conversion_value
                FROM conversion_events_log cel
                WHERE cel.ad_material_id = '{$ad_id}'
                AND DATE(cel.event_time) BETWEEN '{$start_date}' AND '{$end_date}'
                LIMIT {$limit}";
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
     * 광고를 추가합니다.
     * 
     * @param array $data 광고 데이터
     * @return int|false 추가된 광고 ID 또는 false
     */
    public function add_ad($data) {
        // 필수 필드 검증
        if (empty($data['campaign_id']) || empty($data['user_ad_account_id']) || 
            empty($data['ad_type']) || empty($data['headline'])) {
            return false;
        }
        
        $campaign_id = intval($data['campaign_id']);
        $user_ad_account_id = intval($data['user_ad_account_id']);
        $ad_type = sql_escape_string($data['ad_type']);
        $headline = sql_escape_string($data['headline']);
        $description = !empty($data['description']) ? sql_escape_string($data['description']) : '';
        
        // external 관련 데이터
        $external_campaign_id = !empty($data['external_campaign_id']) ? sql_escape_string($data['external_campaign_id']) : '';
        $external_ad_group_id = !empty($data['external_ad_group_id']) ? sql_escape_string($data['external_ad_group_id']) : '';
        $external_ad_id = !empty($data['external_ad_id']) ? sql_escape_string($data['external_ad_id']) : 'ad_' . uniqid();
        
        $ad_name = !empty($data['ad_name']) ? sql_escape_string($data['ad_name']) : $headline;
        $image_url = !empty($data['image_url']) ? sql_escape_string($data['image_url']) : '';
        $destination_url = !empty($data['destination_url']) ? sql_escape_string($data['destination_url']) : '';
        $status = isset($data['status']) ? sql_escape_string($data['status']) : '활성';
        
        // 추가 데이터 JSON 처리
        $external_data = array();
        if (!empty($data['external_data']) && is_array($data['external_data'])) {
            $external_data = $data['external_data'];
        }
        $external_data_json = !empty($external_data) ? "'" . sql_escape_string(json_encode($external_data, JSON_UNESCAPED_UNICODE)) . "'" : "NULL";
        
        $sql = "INSERT INTO ad_materials (
                    campaign_id, user_ad_account_id, external_campaign_id, external_ad_group_id, external_ad_id,
                    ad_name, ad_type, headline, description, image_url, destination_url, status, 
                    external_data, created_at, updated_at
                ) VALUES (
                    '{$campaign_id}', '{$user_ad_account_id}', '{$external_campaign_id}', '{$external_ad_group_id}', '{$external_ad_id}',
                    '{$ad_name}', '{$ad_type}', '{$headline}', '{$description}', '{$image_url}', '{$destination_url}', '{$status}',
                    {$external_data_json}, NOW(), NOW()
                )";
        
        sql_query($sql);
        $ad_id = sql_insert_id();
        
        if ($ad_id) {
            return $ad_id;
        }
        
        return false;
    }
    
    /**
     * 광고를 수정합니다.
     * 
     * @param int $ad_id 광고 ID
     * @param array $data 수정할 데이터
     * @return bool 성공 여부
     */
    public function update_ad($ad_id, $data) {
        $ad_id = intval($ad_id);
        
        // 기존 광고 정보 확인
        $ad = $this->get_ad($ad_id);
        if (!$ad) {
            return false;
        }
        
        $updates = array();
        
        if (isset($data['ad_name'])) {
            $updates[] = "ad_name = '" . sql_escape_string($data['ad_name']) . "'";
        }
        
        if (isset($data['headline'])) {
            $updates[] = "headline = '" . sql_escape_string($data['headline']) . "'";
        }
        
        if (isset($data['description'])) {
            $updates[] = "description = '" . sql_escape_string($data['description']) . "'";
        }
        
        if (isset($data['image_url'])) {
            $updates[] = "image_url = '" . sql_escape_string($data['image_url']) . "'";
        }
        
        if (isset($data['destination_url'])) {
            $updates[] = "destination_url = '" . sql_escape_string($data['destination_url']) . "'";
        }
        
        if (isset($data['status'])) {
            $updates[] = "status = '" . sql_escape_string($data['status']) . "'";
        }
        
        if (isset($data['external_ad_group_id'])) {
            $updates[] = "external_ad_group_id = '" . sql_escape_string($data['external_ad_group_id']) . "'";
        }
        
        // 외부 데이터 업데이트
        if (isset($data['external_data']) && is_array($data['external_data'])) {
            $external_data = $data['external_data'];
            $external_data_json = json_encode($external_data, JSON_UNESCAPED_UNICODE);
            $updates[] = "external_data = '" . sql_escape_string($external_data_json) . "'";
        }
        
        $updates[] = "updated_at = NOW()";
        
        if (empty($updates)) {
            return true; // 변경할 내용이 없음
        }
        
        $update_str = implode(', ', $updates);
        $sql = "UPDATE ad_materials SET {$update_str} WHERE id = '{$ad_id}'";
        
        return sql_query($sql);
    }
    
    /**
     * 광고 상태를 변경합니다.
     * 
     * @param int $ad_id 광고 ID
     * @param string $status 새 상태 (활성, 비활성, 일시중지)
     * @return bool 성공 여부
     */
    public function change_ad_status($ad_id, $status) {
        $ad_id = intval($ad_id);
        $status = sql_escape_string($status);
        
        $sql = "UPDATE ad_materials SET status = '{$status}', updated_at = NOW() WHERE id = '{$ad_id}'";
        return sql_query($sql);
    }
    
    /**
     * 광고를 삭제합니다.
     * 
     * @param int $ad_id 광고 ID
     * @return bool 성공 여부
     */
    public function delete_ad($ad_id) {
        $ad_id = intval($ad_id);
        
        $sql = "DELETE FROM ad_materials WHERE id = '{$ad_id}'";
        return sql_query($sql);
    }
    
    /**
     * 광고 그룹의 모든 광고 상태를 일괄 변경합니다.
     * 
     * @param int $ad_group_id 광고 그룹 ID
     * @param string $status 새 상태 (활성, 비활성, 일시중지)
     * @return bool 성공 여부
     */
    public function change_all_ads_status_by_group($ad_group_id, $status) {
        // 광고 그룹 정보 가져오기
        $ad_group_model = new AdGroupModel();
        $ad_group = $ad_group_model->get_ad_group($ad_group_id);
        
        if (!$ad_group || empty($ad_group['external_ad_group_id'])) {
            return false;
        }
        
        $external_ad_group_id = $ad_group['external_ad_group_id'];
        $status = sql_escape_string($status);
        
        $sql = "UPDATE ad_materials SET status = '{$status}', updated_at = NOW() WHERE external_ad_group_id = '{$external_ad_group_id}'";
        return sql_query($sql);
    }
    
    /**
     * 캠페인의 모든 광고 상태를 일괄 변경합니다.
     * 
     * @param int $campaign_id 캠페인 ID
     * @param string $status 새 상태 (활성, 비활성, 일시중지)
     * @return bool 성공 여부
     */
    public function change_all_ads_status_by_campaign($campaign_id, $status) {
        $campaign_id = intval($campaign_id);
        $status = sql_escape_string($status);
        
        $sql = "UPDATE ad_materials SET status = '{$status}', updated_at = NOW() WHERE campaign_id = '{$campaign_id}'";
        return sql_query($sql);
    }
    
    /**
     * 성과가 좋은 광고 목록을 가져옵니다.
     * 
     * @param int $campaign_id 캠페인 ID (선택 사항)
     * @param int $ad_group_id 광고 그룹 ID (선택 사항)
     * @param string $metric 기준 지표 (ctr, cpc, cpa, roas)
     * @param int $limit 반환할 광고 수
     * @return array 광고 목록 (성과 기준 정렬)
     */
    public function get_top_performing_ads($campaign_id = null, $ad_group_id = null, $metric = 'ctr', $limit = 5) {
        $where = array("status = '활성'");
        
        if ($campaign_id) {
            $where[] = "campaign_id = '" . intval($campaign_id) . "'";
        }
        
        if ($ad_group_id) {
            // 광고 그룹 정보 가져오기
            $ad_group_model = new AdGroupModel();
            $ad_group = $ad_group_model->get_ad_group($ad_group_id);
            
            if ($ad_group && !empty($ad_group['external_ad_group_id'])) {
                $where[] = "external_ad_group_id = '" . $ad_group['external_ad_group_id'] . "'";
            }
        }
        
        $where_str = implode(' AND ', $where);
        
        // 광고 목록 조회
        $sql = "SELECT * FROM ad_materials WHERE {$where_str} ORDER BY created_at DESC";
        $result = sql_query($sql);
        
        $ads = array();
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            // 각 광고의 통계 데이터 추가
            $row['statistics'] = $this->get_ad_statistics($row['id']);
            $ads[] = $row;
        }
        
        // 성과 지표 기준으로 정렬
        usort($ads, function($a, $b) use ($metric) {
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
        return array_slice($ads, 0, $limit);
    }
    
    /**
     * 광고 유형별 통계를 가져옵니다.
     * 
     * @param int $campaign_id 캠페인 ID
     * @return array 광고 유형별 통계 데이터
     */
    public function get_ad_type_statistics($campaign_id) {
        $campaign_id = intval($campaign_id);
        
        // 각 광고 유형별 광고 목록 조회
        $sql = "SELECT ad_type, COUNT(*) as cnt FROM ad_materials WHERE campaign_id = '{$campaign_id}' GROUP BY ad_type";
        $result = sql_query($sql);
        
        $type_stats = array();
        
        while ($row = sql_fetch_array($result)) {
            $ad_type = $row['ad_type'];
            $count = $row['cnt'];
            
            // 광고 유형별 통계
            $type_stats[$ad_type] = array(
                'ad_type' => $ad_type,
                'count' => (int)$count,
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
        
        // 유형별 통계 데이터 조회
        foreach ($type_stats as $ad_type => &$stats) {
            // 클릭 및 비용 데이터
            $sql = "SELECT 
                        SUM(ac.impressions) as impressions,
                        SUM(ac.clicks) as clicks,
                        SUM(ac.ad_cost) as cost
                    FROM ad_costs ac
                    JOIN ad_materials am ON ac.ad_material_id = am.id
                    WHERE am.campaign_id = '{$campaign_id}' AND am.ad_type = '{$ad_type}'";
            $row = sql_fetch($sql);
            
            // 전환 데이터
            $sql = "SELECT 
                        COUNT(cel.id) as conversions,
                        SUM(cel.conversion_value) as conversion_value
                    FROM conversion_events_log cel
                    JOIN ad_materials am ON cel.ad_material_id = am.id
                    WHERE am.campaign_id = '{$campaign_id}' AND am.ad_type = '{$ad_type}'";
            $conversion_row = sql_fetch($sql);
            
            // 통계 데이터 병합
            $stats['impressions'] = (int)$row['impressions'] ?: 0;
            $stats['clicks'] = (int)$row['clicks'] ?: 0;
            $stats['cost'] = (float)$row['cost'] ?: 0;
            $stats['conversions'] = (int)$conversion_row['conversions'] ?: 0;
            $stats['conversion_value'] = (float)$conversion_row['conversion_value'] ?: 0;
            
            // 계산된 지표 추가
            $stats['ctr'] = $stats['impressions'] > 0 ? ($stats['clicks'] / $stats['impressions']) * 100 : 0;
            $stats['cpc'] = $stats['clicks'] > 0 ? $stats['cost'] / $stats['clicks'] : 0;
            $stats['cpa'] = $stats['conversions'] > 0 ? $stats['cost'] / $stats['conversions'] : 0;
            $stats['roas'] = $stats['cost'] > 0 ? ($stats['conversion_value'] / $stats['cost']) * 100 : 0;
        }
        
        return array_values($type_stats);
    }


    /**
     * 캠페인의 광고 수를 조회합니다.
     * 
     * @param int $campaign_id 캠페인 ID
     * @return int 광고 수
     */
    public function count_ads_by_campaign($campaign_id) {
        $campaign_id = intval($campaign_id);
        
        $sql = "SELECT COUNT(*) as cnt FROM ad_materials WHERE campaign_id = '{$campaign_id}'";
        $row = sql_fetch($sql);
        
        return intval($row['cnt']);
    }
}