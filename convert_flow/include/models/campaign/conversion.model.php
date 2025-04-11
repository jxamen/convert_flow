<?php
if (!defined('_CONVERT_FLOW_')) exit;

/**
 * 전환 모델 클래스
 */
class ConversionModel {
    /**
     * 캠페인별 전환 이벤트 목록 조회
     * 
     * @param int $campaign_id 캠페인 ID
     * @param int $limit 조회할 최대 개수
     * @param int $offset 시작 위치 (페이징)
     * @return array 전환 이벤트 목록
     */
    public function get_conversion_events($campaign_id = 0, $limit = 100, $offset = 0) {
        $where = '';
        if ($campaign_id > 0) {
            $campaign_id = intval($campaign_id);
            $where = "WHERE cel.campaign_id = '{$campaign_id}'";
        }
        
        $limit = intval($limit);
        $offset = intval($offset);
        
        $sql = "SELECT cel.*, ce.name as event_name, ce.event_code
                FROM conversion_events_log cel
                LEFT JOIN conversion_events ce ON cel.event_id = ce.id
                {$where}
                ORDER BY cel.event_time DESC
                LIMIT {$offset}, {$limit}";
        
        $result = sql_query($sql);
        
        $data = array();
        while ($row = sql_fetch_array($result)) {
            $data[] = $row;
        }
        
        return $data;
    }
    
    /**
     * 전환 스크립트 목록 조회
     */
    public function get_conversion_scripts($campaign_id) {
        $sql = "SELECT cs.*, ce.name as event_name, ce.event_code
                FROM conversion_scripts cs
                JOIN conversion_events ce ON cs.event_id = ce.id
                WHERE cs.campaign_id = '$campaign_id'
                ORDER BY cs.created_at DESC";
        
        return sql_fetch_all($sql);
    }
    
    /**
     * 전환 스크립트 상세 조회
     */
    public function get_conversion_script($script_id) {
        $sql = "SELECT cs.*, ce.name as event_name, ce.event_code
                FROM conversion_scripts cs
                JOIN conversion_events ce ON cs.event_id = ce.id
                WHERE cs.id = '$script_id'";
        
        return sql_fetch($sql);
    }
    
    /**
     * 전환 스크립트 생성
     */
    public function create_conversion_script($data) {
        // 필수 필드 검증
        if (empty($data['campaign_id']) || empty($data['event_id']) || empty($data['script_code'])) {
            return false;
        }
        
        $campaign_id = intval($data['campaign_id']);
        $event_id = intval($data['event_id']);
        $conversion_type = !empty($data['conversion_type']) ? sql_escape_string($data['conversion_type']) : '구매완료';
        $script_code = sql_escape_string($data['script_code']);
        $installation_guide = !empty($data['installation_guide']) ? sql_escape_string($data['installation_guide']) : '';
        
        $sql = "INSERT INTO conversion_scripts
                SET
                    campaign_id = '$campaign_id',
                    event_id = '$event_id',
                    conversion_type = '$conversion_type',
                    script_code = '$script_code',
                    installation_guide = '$installation_guide',
                    created_at = NOW(),
                    updated_at = NOW()";
        
        sql_query($sql);
        $script_id = sql_insert_id();
        
        if ($script_id) {
            // 로그 기록
            write_log('conversion', 'create_script', array(
                'script_id' => $script_id,
                'campaign_id' => $campaign_id,
                'event_id' => $event_id,
                'conversion_type' => $conversion_type
            ));
            
            return $script_id;
        }
        
        return false;
    }
    
    /**
     * 전환 스크립트 수정
     */
    public function update_conversion_script($script_id, $data) {
        $set_values = array();
        
        if (isset($data['event_id'])) {
            $set_values[] = "event_id = '" . intval($data['event_id']) . "'";
        }
        
        if (isset($data['conversion_type'])) {
            $set_values[] = "conversion_type = '" . sql_escape_string($data['conversion_type']) . "'";
        }
        
        if (isset($data['script_code'])) {
            $set_values[] = "script_code = '" . sql_escape_string($data['script_code']) . "'";
        }
        
        if (isset($data['installation_guide'])) {
            $set_values[] = "installation_guide = '" . sql_escape_string($data['installation_guide']) . "'";
        }
        
        $set_values[] = "updated_at = NOW()";
        
        if (empty($set_values)) {
            return false;
        }
        
        $set_clause = implode(', ', $set_values);
        
        $sql = "UPDATE conversion_scripts
                SET $set_clause
                WHERE id = '$script_id'";
        
        $result = sql_query($sql);
        
        if ($result) {
            // 로그 기록
            write_log('conversion', 'update_script', array(
                'script_id' => $script_id,
                'data' => $data
            ));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 전환 스크립트 삭제
     */
    public function delete_conversion_script($script_id) {
        // 스크립트 정보 가져오기 (로그용)
        $script = $this->get_conversion_script($script_id);
        
        if (!$script) {
            return false;
        }
        
        $sql = "DELETE FROM conversion_scripts
                WHERE id = '$script_id'";
        
        $result = sql_query($sql);
        
        if ($result) {
            // 로그 기록
            write_log('conversion', 'delete_script', array(
                'script_id' => $script_id,
                'campaign_id' => $script['campaign_id'],
                'event_id' => $script['event_id']
            ));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 전환 이벤트 기록
     */
    public function record_conversion_event($data) {
        // 필수 필드 검증
        if (empty($data['campaign_id']) || empty($data['landing_page_id']) || empty($data['event_id'])) {
            return false;
        }
        
        $campaign_id = intval($data['campaign_id']);
        $landing_page_id = intval($data['landing_page_id']);
        $event_id = intval($data['event_id']);
        $url_id = !empty($data['url_id']) ? intval($data['url_id']) : "NULL";
        $ad_material_id = !empty($data['ad_material_id']) ? intval($data['ad_material_id']) : "NULL";
        $platform_id = !empty($data['platform_id']) ? intval($data['platform_id']) : "NULL";
        $conversion_value = isset($data['conversion_value']) ? floatval($data['conversion_value']) : 0;
        
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        $source = isset($data['source']) ? sql_escape_string($data['source']) : '';
        $medium = isset($data['medium']) ? sql_escape_string($data['medium']) : '';
        
        // 디바이스 타입 감지
        $device_type = 'desktop';
        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $user_agent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($user_agent, 0, 4))) {
            $device_type = 'mobile';
        } else if (preg_match('/android|ipad|playbook|silk/i', $user_agent)) {
            $device_type = 'tablet';
        }
        
        // 추가 데이터 JSON 처리
        $additional_data = isset($data['additional_data']) ? json_encode($data['additional_data']) : 'NULL';
        if ($additional_data !== 'NULL') {
            $additional_data = "'" . sql_escape_string($additional_data) . "'";
        }
        
        $country = !empty($data['country']) ? "'" . sql_escape_string($data['country']) . "'" : 'NULL';
        $city = !empty($data['city']) ? "'" . sql_escape_string($data['city']) . "'" : 'NULL';
        
        // 이벤트 로그 기록
        $sql = "INSERT INTO conversion_events_log
                SET
                    campaign_id = '{$campaign_id}',
                    landing_page_id = '{$landing_page_id}',
                    event_id = '{$event_id}',
                    url_id = {$url_id},
                    ad_material_id = {$ad_material_id},
                    platform_id = {$platform_id},
                    event_time = NOW(),
                    conversion_value = '{$conversion_value}',
                    ip_address = '{$ip_address}',
                    user_agent = '" . sql_escape_string($user_agent) . "',
                    device_type = '{$device_type}',
                    source = '{$source}',
                    medium = '{$medium}',
                    country = {$country},
                    city = {$city},
                    additional_data = {$additional_data}";
        
        sql_query($sql);
        $event_log_id = sql_insert_id();
        
        if ($event_log_id) {
            // 로그 기록
            write_log('conversion', 'record_event', array(
                'event_log_id' => $event_log_id,
                'campaign_id' => $campaign_id,
                'event_id' => $event_id,
                'conversion_value' => $conversion_value
            ));
            
            return $event_log_id;
        }
        
        return false;
    }
    
    /**
     * 전환 이벤트 목록 조회
     */
    public function get_conversion_events_log($campaign_id, $limit = 100, $offset = 0) {
        $sql = "SELECT cel.*, ce.name as event_name, ce.event_code, lp.name as landing_page_name
                FROM conversion_events_log cel
                JOIN conversion_events ce ON cel.event_id = ce.id
                JOIN landing_pages lp ON cel.landing_page_id = lp.id
                WHERE cel.campaign_id = '{$campaign_id}'
                ORDER BY cel.event_time DESC
                LIMIT {$offset}, {$limit}";
        
        return sql_fetch_all($sql);
    }
    
    

    /**
     * 캠페인별 전환 이벤트 개수 조회
     * 
     * @param int $campaign_id 캠페인 ID
     * @return int 전환 이벤트 개수
     */
    public function count_conversion_events($campaign_id = 0) {
        $where = '';
        if ($campaign_id > 0) {
            $campaign_id = intval($campaign_id);
            $where = "WHERE campaign_id = '{$campaign_id}'";
        }
        
        $sql = "SELECT COUNT(*) as cnt FROM conversion_events_log {$where}";
        $row = sql_fetch($sql);
        
        return intval($row['cnt']);
    }
    
    /**
     * 전환 유형별 통계 조회
     */
    public function get_conversion_type_stats($campaign_id) {
        $sql = "SELECT 
                    ce.id as event_id,
                    ce.name as event_name,
                    ce.event_code,
                    COUNT(cel.id) as count,
                    SUM(cel.conversion_value) as value
                FROM conversion_events_log cel
                JOIN conversion_events ce ON cel.event_id = ce.id
                WHERE cel.campaign_id = '{$campaign_id}'
                GROUP BY ce.id, ce.name, ce.event_code
                ORDER BY count DESC";
        
        return sql_fetch_all($sql);
    }
    
    /**
     * 전환 스크립트 자동 생성
     */
    public function generate_script_code($campaign_id, $event_id) {
        // 캠페인 정보 조회
        $campaign_model = new CampaignModel();
        $campaign = $campaign_model->get_campaign($campaign_id);
        
        if (!$campaign) {
            return false;
        }
        
        // 이벤트 정보 조회
        $sql = "SELECT * FROM conversion_events WHERE id = '{$event_id}'";
        $event = sql_fetch($sql);
        
        if (!$event) {
            return false;
        }
        
        $conversion_type = $event['name'];
        $event_code = $event['event_code'];
        
        // 스크립트 코드 생성
        $script_code = "<!-- ConvertFlow 전환 추적 스크립트 - {$conversion_type} -->\n";
        $script_code .= "<script type=\"text/javascript\">\n";
        $script_code .= "    (function() {\n";
        $script_code .= "        var cf = document.createElement('script');\n";
        $script_code .= "        cf.type = 'text/javascript';\n";
        $script_code .= "        cf.async = true;\n";
        $script_code .= "        cf.src = '" . CF_URL . "/api/conversion.js?campaign=" . $campaign['campaign_hash'] . "&event=" . $event_code . "';\n";
        $script_code .= "        var s = document.getElementsByTagName('script')[0];\n";
        $script_code .= "        s.parentNode.insertBefore(cf, s);\n";
        $script_code .= "    })();\n";
        $script_code .= "</script>\n";
        
        // 설치 가이드 생성
        $installation_guide = "<!-- {$conversion_type} 전환 추적 설치 가이드 -->\n\n";
        
        switch ($conversion_type) {
            case '구매완료':
                $installation_guide .= "이 스크립트를 결제 완료 페이지에 삽입하세요.\n";
                $installation_guide .= "전환 금액을 추적하려면 다음과 같이 사용하세요:\n\n";
                $installation_guide .= "<script>\n";
                $installation_guide .= "    // 주문 완료 후 실행\n";
                $installation_guide .= "    if (typeof ConvertFlow !== 'undefined') {\n";
                $installation_guide .= "        ConvertFlow.trackConversion({\n";
                $installation_guide .= "            value: 결제금액, // 예: 50000\n";
                $installation_guide .= "            orderId: '주문번호' // 선택 사항\n";
                $installation_guide .= "        });\n";
                $installation_guide .= "    }\n";
                $installation_guide .= "</script>\n";
                break;
                
            case '회원가입':
                $installation_guide .= "이 스크립트를 회원가입 완료 페이지에 삽입하세요.\n";
                $installation_guide .= "추가 정보를 추적하려면 다음과 같이 사용하세요:\n\n";
                $installation_guide .= "<script>\n";
                $installation_guide .= "    // 회원가입 완료 후 실행\n";
                $installation_guide .= "    if (typeof ConvertFlow !== 'undefined') {\n";
                $installation_guide .= "        ConvertFlow.trackConversion({\n";
                $installation_guide .= "            userId: '회원ID', // 선택 사항\n";
                $installation_guide .= "            userType: '회원유형' // 선택 사항\n";
                $installation_guide .= "        });\n";
                $installation_guide .= "    }\n";
                $installation_guide .= "</script>\n";
                break;
                
            case '다운로드':
                $installation_guide .= "이 스크립트를 다운로드 버튼이 있는 페이지나 다운로드 완료 페이지에 삽입하세요.\n";
                $installation_guide .= "다운로드 정보를 추적하려면 다음과 같이 사용하세요:\n\n";
                $installation_guide .= "<script>\n";
                $installation_guide .= "    // 다운로드 버튼 클릭 시 실행\n";
                $installation_guide .= "    document.getElementById('download-button').addEventListener('click', function() {\n";
                $installation_guide .= "        if (typeof ConvertFlow !== 'undefined') {\n";
                $installation_guide .= "            ConvertFlow.trackConversion({\n";
                $installation_guide .= "                fileId: '파일ID', // 선택 사항\n";
                $installation_guide .= "                fileName: '파일이름' // 선택 사항\n";
                $installation_guide .= "            });\n";
                $installation_guide .= "        }\n";
                $installation_guide .= "    });\n";
                $installation_guide .= "</script>\n";
                break;
                
            case '문의하기':
                $installation_guide .= "이 스크립트를 문의 폼이 제출되는 페이지나 문의 완료 페이지에 삽입하세요.\n";
                $installation_guide .= "문의 정보를 추적하려면 다음과 같이 사용하세요:\n\n";
                $installation_guide .= "<script>\n";
                $installation_guide .= "    // 문의 폼 제출 시 실행\n";
                $installation_guide .= "    document.getElementById('inquiry-form').addEventListener('submit', function() {\n";
                $installation_guide .= "        if (typeof ConvertFlow !== 'undefined') {\n";
                $installation_guide .= "            ConvertFlow.trackConversion({\n";
                $installation_guide .= "                inquiryType: '문의유형', // 선택 사항\n";
                $installation_guide .= "                email: '이메일' // 선택 사항\n";
                $installation_guide .= "            });\n";
                $installation_guide .= "        }\n";
                $installation_guide .= "    });\n";
                $installation_guide .= "</script>\n";
                break;
                
            default:
                $installation_guide .= "이 스크립트를 전환이 발생하는 페이지에 삽입하세요.\n";
                $installation_guide .= "추가 정보를 추적하려면 다음과 같이 사용하세요:\n\n";
                $installation_guide .= "<script>\n";
                $installation_guide .= "    if (typeof ConvertFlow !== 'undefined') {\n";
                $installation_guide .= "        ConvertFlow.trackConversion({\n";
                $installation_guide .= "            // 추가 데이터를 여기에 입력하세요\n";
                $installation_guide .= "            key1: 'value1',\n";
                $installation_guide .= "            key2: 'value2'\n";
                $installation_guide .= "        });\n";
                $installation_guide .= "    }\n";
                $installation_guide .= "</script>\n";
                break;
        }
        
        return array(
            'script_code' => $script_code,
            'installation_guide' => $installation_guide,
            'conversion_type' => $conversion_type
        );
    }
}