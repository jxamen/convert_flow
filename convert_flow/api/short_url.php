<?php
/**
 * 단축 URL 처리 API
 * 
 * 생성된 단축 URL로 접속 시 처리하는 파일입니다.
 * 전환 추적과 리다이렉션을 담당합니다.
 */

// 상수 정의
define('_CONVERT_FLOW_', true);

// 공통 파일 로드
require_once dirname(__FILE__, 2) . '/include/common.php';

// 파라미터 확인
$short_code = isset($_GET['code']) ? trim($_GET['code']) : '';

if (empty($short_code)) {
    // 유효한 코드가 아닌 경우 홈으로 이동
    header('Location: ' . CF_URL);
    exit;
}

// 데이터베이스에서 URL 정보 조회
$sql = "SELECT * FROM shortened_urls 
        WHERE short_code = '" . sql_escape_string($short_code) . "'";
$url_data = sql_fetch($sql);

if (!$url_data) {
    // 유효한 코드가 아닌 경우 홈으로 이동
    header('Location: ' . CF_URL);
    exit;
}

// 만료 여부 확인
if ($url_data['expires_at'] && strtotime($url_data['expires_at']) < time()) {
    // 만료된 URL인 경우 홈으로 이동
    header('Location: ' . CF_URL);
    exit;
}

// 클릭 정보 기록
$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

// 디바이스 타입 감지
$device_type = 'desktop';
if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $user_agent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($user_agent, 0, 4))) {
    $device_type = 'mobile';
} else if (preg_match('/android|ipad|playbook|silk/i', $user_agent)) {
    $device_type = 'tablet';
}

// 브라우저 감지
$browser = 'unknown';
if (preg_match('/MSIE|Trident/i', $user_agent)) {
    $browser = 'Internet Explorer';
} else if (preg_match('/Firefox/i', $user_agent)) {
    $browser = 'Firefox';
} else if (preg_match('/Chrome/i', $user_agent)) {
    $browser = 'Chrome';
} else if (preg_match('/Safari/i', $user_agent)) {
    $browser = 'Safari';
} else if (preg_match('/Opera|OPR/i', $user_agent)) {
    $browser = 'Opera';
} else if (preg_match('/Edge/i', $user_agent)) {
    $browser = 'Edge';
}

// OS 감지
$os = 'unknown';
if (preg_match('/windows|win32|win64/i', $user_agent)) {
    $os = 'Windows';
} else if (preg_match('/macintosh|mac os x/i', $user_agent)) {
    $os = 'Mac OS';
} else if (preg_match('/linux/i', $user_agent)) {
    $os = 'Linux';
} else if (preg_match('/ubuntu/i', $user_agent)) {
    $os = 'Ubuntu';
} else if (preg_match('/iphone|ipad|ipod/i', $user_agent)) {
    $os = 'iOS';
} else if (preg_match('/android/i', $user_agent)) {
    $os = 'Android';
}

// GeoIP 정보 (옵션)
$country = '';
$region = '';
$city = '';

// 클릭 정보 저장
$sql = "INSERT INTO url_clicks
        SET
            url_id = '{$url_data['id']}',
            click_time = NOW(),
            ip_address = '$ip_address',
            user_agent = '" . sql_escape_string($user_agent) . "',
            referrer = '" . sql_escape_string($referrer) . "',
            device_type = '$device_type',
            browser = '$browser',
            os = '$os',
            country = '$country',
            region = '$region',
            city = '$city'";
sql_query($sql);

// 원본 URL로 리다이렉션
$original_url = $url_data['original_url'];

// UTM 매개변수 추가 (캠페인 정보가 있는 경우)
if ($url_data['campaign_id']) {
    // 캠페인 정보 조회
    $sql = "SELECT * FROM campaigns WHERE id = '{$url_data['campaign_id']}'";
    $campaign = sql_fetch($sql);
    
    if ($campaign) {
        // URL에 UTM 매개변수 추가
        $separator = (strpos($original_url, '?') !== false) ? '&' : '?';
        $utm_params = "utm_source=convertflow&utm_medium=shorturl&utm_campaign=" . urlencode($campaign['name']);
        $original_url .= $separator . $utm_params;
    }
}

// 리다이렉션
header('Location: ' . $original_url);
exit;
