<?php
/**
 * 광고 그룹 모델 클래스
 * 
 * 광고 그룹 관련 데이터 처리를 담당하는 모델 클래스입니다.
 */
class AdGroupModel {
    /**
     * 캠페인의 광고 그룹 목록을 가져옵니다.
     * 
     * @param int $campaign_id 캠페인 ID
     * @param array $params 검색 조건
     * @return array 광고 그룹 목록
     */
    public function get_ad_groups_by_campaign($campaign_id, $params = array()) {
        $campaign_id = intval($campaign_id);
        
        $where = array("campaign_id = '{$campaign_id}'");
        
        // 검색 조건 처리
        if (!empty($params['search_keyword'])) {
            $search_keyword = trim(sql_escape_string($params['search_keyword']));
            $where[] = "(name LIKE '%{$search_keyword}%' OR description LIKE '%{$search_keyword}%')";
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
        
        // 전체 광고 그룹 수 조회
        $count_sql = "SELECT COUNT(*) as cnt FROM ad_groups WHERE {$where_str}";
        $row = sql_fetch($count_sql);
        $total_count = $row['cnt'];
        
        // 광고 그룹 목록 조회
        $sql = "SELECT * FROM ad_groups WHERE {$where_str} ORDER BY {$order_by} LIMIT {$start}, {$items_per_page}";
        $result = sql_query($sql);
        
        $ad_groups = array();
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            // 각 광고 그룹의 통계 데이터 추가
            $row['statistics'] = $this->get_ad_group_statistics($row['id']);
            
            // 광고 그룹별 광고 소재 개수
            if (!empty($row['external_ad_group_id'])) {
                $external_ad_group_id = $row['external_ad_group_id'];
                $sql = "SELECT COUNT(*) as cnt FROM ad_materials WHERE external_ad_group_id = '{$external_ad_group_id}'";
                $count = sql_fetch($sql);
                $row['ad_count'] = $count['cnt'];
            } else {
                $row['ad_count'] = 0;
            }
            
            $ad_groups[] = $row;
        }
        
        return array(
            'ad_groups' => $ad_groups,
            'total_count' => $total_count,
            'page' => $page,
            'items_per_page' => $items_per_page,
            'total_pages' => ceil($total_count / $items_per_page)
        );
    }
    
    /**
     * 특정 광고 그룹 정보를 가져옵니다.
     * 
     * @param int $ad_group_id 광고 그룹 ID
     * @return array|false 광고 그룹 정보 또는 false
     */
    public function get_ad_group($ad_group_id) {
        $ad_group_id = intval($ad_group_id);
        
        $sql = "SELECT * FROM ad_groups WHERE id = '{$ad_group_id}'";
        $ad_group = sql_fetch($sql);
        
        if (!$ad_group) {
            return false;
        }
        
        // 광고 그룹 통계 추가
        $ad_group['statistics'] = $this->get_ad_group_statistics($ad_group_id);
        
        // 광고 그룹에 연결된 광고 소재 수
        if (!empty($ad_group['external_ad_group_id'])) {
            $external_ad_group_id = $ad_group['external_ad_group_id'];
            $sql = "SELECT COUNT(*) as cnt FROM ad_materials WHERE external_ad_group_id = '{$external_ad_group_id}'";
            $row = sql_fetch($sql);
            $ad_group['ad_count'] = $row['cnt'];
        } else {
            $ad_group['ad_count'] = 0;
        }
        
        return $ad_group;
    }
    
    /**
     * 광고 그룹 통계 데이터를 가져옵니다.
     * 
     * @param int $ad_group_id 광고 그룹 ID
     * @param string $start_date 시작일 (YYYY-MM-DD)
     * @param string $end_date 종료일 (YYYY-MM-DD)
     * @return array 통계 데이터
     */
    public function get_ad_group_statistics($ad_group_id, $start_date = null, $end_date = null) {
        $ad_group_id = intval($ad_group_id);
        
        // 기본값: 최근 30일
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $start_date = sql_escape_string($start_date);
        $end_date = sql_escape_string($end_date);
        
        // 광고 그룹 정보 조회
        $ad_group = $this->get_ad_group_basic($ad_group_id);
        if (!$ad_group || empty($ad_group['external_ad_group_id'])) {
            return $this->get_empty_statistics($start_date, $end_date);
        }
        
        $external_ad_group_id = $ad_group['external_ad_group_id'];
        
        // 광고 그룹에 속한 광고 소재 ID 목록 조회
        $sql = "SELECT id FROM ad_materials WHERE external_ad_group_id = '{$external_ad_group_id}'";
        $result = sql_query($sql);
        
        $ad_ids = array();
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            $ad_ids[] = $row['id'];
        }
        
        // 광고 소재가 없는 경우 빈 통계 반환
        if (empty($ad_ids)) {
            return $this->get_empty_statistics($start_date, $end_date);
        }
        
        $ad_ids_str = implode(',', $ad_ids);
        
        // 클릭 및 비용 데이터 (URL 클릭 이벤트 기반)
        $sql = "SELECT 
                    SUM(uc.impression) as impressions,
                    COUNT(uc.id) as clicks,
                    SUM(uc.ad_cost) as cost
                FROM url_clicks uc
                WHERE uc.ad_material_id IN ({$ad_ids_str})
                AND DATE(uc.click_time) BETWEEN '{$start_date}' AND '{$end_date}'";
        $stats = sql_fetch($sql);
        
        // 전환 데이터 (conversion_events_log 테이블 기반)
        $sql = "SELECT 
                    COUNT(cel.id) as conversions,
                    SUM(cel.conversion_value) as conversion_value
                FROM conversion_events_log cel
                WHERE cel.ad_material_id IN ({$ad_ids_str})
                AND DATE(cel.event_time) BETWEEN '{$start_date}' AND '{$end_date}'";
        $conversion_stats = sql_fetch($sql);
        
        // 통계 데이터 병합
        return $this->calculate_statistics($stats, $conversion_stats, $start_date, $end_date);
    }


    /**
     * 빈 통계 데이터를 반환합니다.
     * 
     * @param string $start_date 시작일
     * @param string $end_date 종료일
     * @return array 빈 통계 데이터
     */
    private function get_empty_statistics($start_date, $end_date) {
        return array(
            'impressions' => 0,
            'clicks' => 0,
            'cost' => 0,
            'conversions' => 0,
            'conversion_value' => 0,
            'ctr' => 0,
            'cpc' => 0,
            'cpa' => 0,
            'roas' => 0,
            'start_date' => $start_date,
            'end_date' => $end_date
        );
    }


    /**
     * 통계 데이터를 계산합니다.
     * 
     * @param array $stats 클릭 및 비용 통계
     * @param array $conversion_stats 전환 통계
     * @param string $start_date 시작일
     * @param string $end_date 종료일
     * @return array 계산된 통계 데이터
     */
    private function calculate_statistics($stats, $conversion_stats, $start_date, $end_date) {
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
     * 기본 광고 그룹 정보만 가져옵니다 (통계 제외).
     * 
     * @param int $ad_group_id 광고 그룹 ID
     * @return array|false 광고 그룹 정보 또는 false
     */
    private function get_ad_group_basic($ad_group_id) {
        $ad_group_id = intval($ad_group_id);
        
        $sql = "SELECT * FROM ad_groups WHERE id = '{$ad_group_id}'";
        return sql_fetch($sql);
    }

    
    /**
     * 광고 그룹을 추가합니다.
     * 
     * @param array $data 광고 그룹 데이터
     * @return int|false 추가된 광고 그룹 ID 또는 false
     */
    public function add_ad_group($data) {
        // 필수 필드 검증
        if (empty($data['campaign_id']) || empty($data['name'])) {
            return false;
        }
        
        $campaign_id = intval($data['campaign_id']);
        $name = sql_escape_string($data['name']);
        $status = isset($data['status']) ? sql_escape_string($data['status']) : '활성';
        $targeting_type = isset($data['targeting_type']) ? sql_escape_string($data['targeting_type']) : '키워드';
        $bid_amount = !empty($data['bid_amount']) ? floatval($data['bid_amount']) : 0;
        $description = !empty($data['description']) ? sql_escape_string($data['description']) : '';
        
        // 타겟팅 정보 저장
        $targeting_data = array();
        if (isset($data['targeting_keywords'])) {
            $targeting_data['keywords'] = $data['targeting_keywords'];
        }
        if (isset($data['targeting_locations'])) {
            $targeting_data['locations'] = $data['targeting_locations'];
        }
        if (isset($data['targeting_devices'])) {
            $targeting_data['devices'] = $data['targeting_devices'];
        }
        if (isset($data['targeting_schedule'])) {
            $targeting_data['schedule'] = $data['targeting_schedule'];
        }
        
        $targeting_json = json_encode($targeting_data, JSON_UNESCAPED_UNICODE);
        
        $sql = "INSERT INTO ad_groups (
                    campaign_id, name, status, targeting_type, bid_amount, 
                    targeting_data, description, created_at
                ) VALUES (
                    '{$campaign_id}', '{$name}', '{$status}', '{$targeting_type}', '{$bid_amount}', 
                    '{$targeting_json}', '{$description}', NOW()
                )";
        
        sql_query($sql);
        $ad_group_id = sql_insert_id();
        
        if ($ad_group_id) {
            return $ad_group_id;
        }
        
        return false;
    }
    
    /**
     * 광고 그룹을 수정합니다.
     * 
     * @param int $ad_group_id 광고 그룹 ID
     * @param array $data 수정할 데이터
     * @return bool 성공 여부
     */
    public function update_ad_group($ad_group_id, $data) {
        $ad_group_id = intval($ad_group_id);
        
        // 기존 광고 그룹 정보 확인
        $ad_group = $this->get_ad_group($ad_group_id);
        if (!$ad_group) {
            return false;
        }
        
        $updates = array();
        
        if (isset($data['name'])) {
            $updates[] = "name = '" . sql_escape_string($data['name']) . "'";
        }
        
        if (isset($data['status'])) {
            $updates[] = "status = '" . sql_escape_string($data['status']) . "'";
        }
        
        if (isset($data['targeting_type'])) {
            $updates[] = "targeting_type = '" . sql_escape_string($data['targeting_type']) . "'";
        }
        
        if (isset($data['bid_amount'])) {
            $updates[] = "bid_amount = '" . floatval($data['bid_amount']) . "'";
        }
        
        if (isset($data['description'])) {
            $updates[] = "description = '" . sql_escape_string($data['description']) . "'";
        }
        
        // 타겟팅 정보 업데이트
        if (isset($data['targeting_keywords']) || isset($data['targeting_locations']) ||
            isset($data['targeting_devices']) || isset($data['targeting_schedule'])) {
            
            // 기존 타겟팅 데이터 가져오기
            $targeting_data = json_decode($ad_group['targeting_data'], true) ?: array();
            
            if (isset($data['targeting_keywords'])) {
                $targeting_data['keywords'] = $data['targeting_keywords'];
            }
            if (isset($data['targeting_locations'])) {
                $targeting_data['locations'] = $data['targeting_locations'];
            }
            if (isset($data['targeting_devices'])) {
                $targeting_data['devices'] = $data['targeting_devices'];
            }
            if (isset($data['targeting_schedule'])) {
                $targeting_data['schedule'] = $data['targeting_schedule'];
            }
            
            $targeting_json = json_encode($targeting_data, JSON_UNESCAPED_UNICODE);
            $updates[] = "targeting_data = '" . sql_escape_string($targeting_json) . "'";
        }
        
        $updates[] = "updated_at = NOW()";
        
        if (empty($updates)) {
            return true; // 변경할 내용이 없음
        }
        
        $update_str = implode(', ', $updates);
        $sql = "UPDATE ad_groups SET {$update_str} WHERE id = '{$ad_group_id}'";
        
        return sql_query($sql);
    }
    
    /**
     * 광고 그룹 상태를 변경합니다.
     * 
     * @param int $ad_group_id 광고 그룹 ID
     * @param string $status 새 상태 (활성, 비활성, 일시중지)
     * @return bool 성공 여부
     */
    public function change_ad_group_status($ad_group_id, $status) {
        $ad_group_id = intval($ad_group_id);
        $status = sql_escape_string($status);
        
        $sql = "UPDATE ad_groups SET status = '{$status}', updated_at = NOW() WHERE id = '{$ad_group_id}'";
        return sql_query($sql);
    }
    
    /**
     * 광고 그룹을 삭제합니다.
     * 
     * @param int $ad_group_id 광고 그룹 ID
     * @return bool 성공 여부
     */
    public function delete_ad_group($ad_group_id) {
        $ad_group_id = intval($ad_group_id);
        
        // 광고 그룹 관련 데이터 삭제 (광고 등)
        // 주의: 참조 무결성을 위해 외래 키 제약조건이 설정되어 있는 경우 불필요
        
        $sql = "DELETE FROM ad_groups WHERE id = '{$ad_group_id}'";
        return sql_query($sql);
    }
    
    /**
     * 캠페인의 모든 광고 그룹 상태를 일괄 변경합니다.
     * 
     * @param int $campaign_id 캠페인 ID
     * @param string $status 새 상태 (활성, 비활성, 일시중지)
     * @return bool 성공 여부
     */
    public function change_all_ad_groups_status($campaign_id, $status) {
        $campaign_id = intval($campaign_id);
        $status = sql_escape_string($status);
        
        $sql = "UPDATE ad_groups SET status = '{$status}', updated_at = NOW() WHERE campaign_id = '{$campaign_id}'";
        return sql_query($sql);
    }


    
    /**
     * 광고 그룹 내 키워드 목록을 가져옵니다.
     * 
     * @param int $ad_group_id 광고 그룹 ID
     * @return array 키워드 목록
     */
    public function get_keywords($ad_group_id) {
        $ad_group_id = intval($ad_group_id);
        
        // 광고 그룹 정보 가져오기
        $ad_group = $this->get_ad_group($ad_group_id);
        if (!$ad_group) {
            return array();
        }
        
        // 타겟팅 데이터에서 키워드 추출
        $targeting_data = json_decode($ad_group['targeting_data'], true) ?: array();
        $keywords = isset($targeting_data['keywords']) ? $targeting_data['keywords'] : array();
        
        return $keywords;
    }
    
    /**
     * 광고 그룹 내 위치 타겟팅 목록을 가져옵니다.
     * 
     * @param int $ad_group_id 광고 그룹 ID
     * @return array 위치 타겟팅 목록
     */
    public function get_location_targeting($ad_group_id) {
        $ad_group_id = intval($ad_group_id);
        
        // 광고 그룹 정보 가져오기
        $ad_group = $this->get_ad_group($ad_group_id);
        if (!$ad_group) {
            return array();
        }
        
        // 타겟팅 데이터에서 위치 정보 추출
        $targeting_data = json_decode($ad_group['targeting_data'], true) ?: array();
        $locations = isset($targeting_data['locations']) ? $targeting_data['locations'] : array();
        
        return $locations;
    }
    
    /**
     * 광고 그룹 내 디바이스 타겟팅 정보를 가져옵니다.
     * 
     * @param int $ad_group_id 광고 그룹 ID
     * @return array 디바이스 타겟팅 정보
     */
    public function get_device_targeting($ad_group_id) {
        $ad_group_id = intval($ad_group_id);
        
        // 광고 그룹 정보 가져오기
        $ad_group = $this->get_ad_group($ad_group_id);
        if (!$ad_group) {
            return array();
        }
        
        // 타겟팅 데이터에서 디바이스 정보 추출
        $targeting_data = json_decode($ad_group['targeting_data'], true) ?: array();
        $devices = isset($targeting_data['devices']) ? $targeting_data['devices'] : array();
        
        return $devices;
    }
    
    /**
     * 광고 그룹 내 일정 타겟팅 정보를 가져옵니다.
     * 
     * @param int $ad_group_id 광고 그룹 ID
     * @return array 일정 타겟팅 정보
     */
    public function get_schedule_targeting($ad_group_id) {
        $ad_group_id = intval($ad_group_id);
        
        // 광고 그룹 정보 가져오기
        $ad_group = $this->get_ad_group($ad_group_id);
        if (!$ad_group) {
            return array();
        }
        
        // 타겟팅 데이터에서 일정 정보 추출
        $targeting_data = json_decode($ad_group['targeting_data'], true) ?: array();
        $schedule = isset($targeting_data['schedule']) ? $targeting_data['schedule'] : array();
        
        return $schedule;
    }


    /**
     * 캠페인의 광고 그룹 수를 조회합니다.
     * 
     * @param int $campaign_id 캠페인 ID
     * @return int 광고 그룹 수
     */
    public function count_ad_groups_by_campaign($campaign_id) {
        $campaign_id = intval($campaign_id);
        
        $sql = "SELECT COUNT(*) as cnt FROM ad_groups WHERE campaign_id = '{$campaign_id}'";
        $row = sql_fetch($sql);
        
        return intval($row['cnt']);
    }




    /**
     * 시간별 광고 그룹 통계 데이터를 가져옵니다.
     * 
     * @param int $ad_group_id 광고 그룹 ID
     * @param string $start_date 시작일 (YYYY-MM-DD)
     * @param string $end_date 종료일 (YYYY-MM-DD)
     * @return array 시간별 통계 데이터
     */
    public function get_hourly_statistics($ad_group_id, $start_date, $end_date) {
        $ad_group_id = intval($ad_group_id);
        $start_date = sql_escape_string($start_date);
        $end_date = sql_escape_string($end_date);
        
        // 광고 그룹 정보 가져오기
        $sql = "SELECT * FROM ad_groups WHERE id = '{$ad_group_id}'";
        $ad_group = sql_fetch($sql);
        
        if (!$ad_group) {
            return array();
        }
        
        // 캠페인 ID 가져오기
        $campaign_id = $ad_group['campaign_id'];
        
        // 시간별 데이터 초기화 (0시부터 23시까지)
        $hourly_stats = array();
        for ($hour = 0; $hour < 24; $hour++) {
            $hourly_stats[$hour] = array(
                'hour' => $hour,
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
        
        // 시간별 클릭 및 비용 데이터 - API 없이도 캠페인 ID로 조회
        $sql = "SELECT 
                    HOUR(lpv.visit_time) as hour,
                    COUNT(lpv.id) as clicks,
                    0 as impressions,
                    0 as cost
                FROM landing_page_visits lpv
                WHERE lpv.campaign_id = '{$campaign_id}'
                AND DATE(lpv.visit_time) BETWEEN '{$start_date}' AND '{$end_date}'
                GROUP BY HOUR(lpv.visit_time)";
        $result = sql_query($sql);
        
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            $hour = intval($row['hour']);
            $hourly_stats[$hour]['impressions'] += (int)$row['impressions'] ?: 0;
            $hourly_stats[$hour]['clicks'] += (int)$row['clicks'] ?: 0;
            $hourly_stats[$hour]['cost'] += (float)$row['cost'] ?: 0;
        }
        
        // 시간별 전환 데이터 - API 없이도 캠페인 ID로 조회
        $sql = "SELECT 
                    HOUR(cel.event_time) as hour,
                    COUNT(cel.id) as conversions,
                    SUM(cel.conversion_value) as conversion_value
                FROM conversion_events_log cel
                WHERE cel.campaign_id = '{$campaign_id}'
                AND DATE(cel.event_time) BETWEEN '{$start_date}' AND '{$end_date}'
                GROUP BY HOUR(cel.event_time)";
        $result = sql_query($sql);
        
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            $hour = intval($row['hour']);
            $hourly_stats[$hour]['conversions'] += (int)$row['conversions'] ?: 0;
            $hourly_stats[$hour]['conversion_value'] += (float)$row['conversion_value'] ?: 0;
        }
        
        // 계산된 지표 추가
        foreach ($hourly_stats as &$stats) {
            $stats['ctr'] = $stats['impressions'] > 0 ? ($stats['clicks'] / $stats['impressions']) * 100 : 0;
            $stats['cpc'] = $stats['clicks'] > 0 ? $stats['cost'] / $stats['clicks'] : 0;
            $stats['cpa'] = $stats['conversions'] > 0 ? $stats['cost'] / $stats['conversions'] : 0;
            $stats['roas'] = $stats['cost'] > 0 ? ($stats['conversion_value'] / $stats['cost']) * 100 : 0;
        }
        
        // 배열 인덱스를 정리해서 반환
        return array_values($hourly_stats);
    }

        /**
     * 일별 광고 그룹 통계 데이터를 가져옵니다.
     * 
     * @param int $ad_group_id 광고 그룹 ID
     * @param string $start_date 시작일 (YYYY-MM-DD)
     * @param string $end_date 종료일 (YYYY-MM-DD)
     * @return array 일별 통계 데이터
     */
    public function get_daily_statistics($ad_group_id, $start_date = null, $end_date = null) {
        $ad_group_id = intval($ad_group_id);
        
        // 기본값: 최근 30일
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $start_date = sql_escape_string($start_date);
        $end_date = sql_escape_string($end_date);
        
        // 광고 그룹 정보 가져오기
        $sql = "SELECT * FROM ad_groups WHERE id = '{$ad_group_id}'";
        $ad_group = sql_fetch($sql);
        
        if (!$ad_group) {
            return array();
        }
        
        // 캠페인 ID 가져오기
        $campaign_id = $ad_group['campaign_id'];
        
        // 일자별 클릭 및 비용 데이터 - 랜딩페이지 방문 기록으로 대체
        $sql = "SELECT 
                    DATE(lpv.visit_time) as date,
                    COUNT(lpv.id) as clicks,
                    0 as impressions,
                    0 as cost
                FROM landing_page_visits lpv
                WHERE lpv.campaign_id = '{$campaign_id}'
                AND DATE(lpv.visit_time) BETWEEN '{$start_date}' AND '{$end_date}'
                GROUP BY DATE(lpv.visit_time)
                ORDER BY DATE(lpv.visit_time)";
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
                    DATE(cel.event_time) as date,
                    COUNT(cel.id) as conversions,
                    SUM(cel.conversion_value) as conversion_value
                FROM conversion_events_log cel
                WHERE cel.campaign_id = '{$campaign_id}'
                AND DATE(cel.event_time) BETWEEN '{$start_date}' AND '{$end_date}'
                GROUP BY DATE(cel.event_time)
                ORDER BY DATE(cel.event_time)";
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
}
