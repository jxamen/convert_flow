<?php
/********************
    상수 선언
********************/

// 이 상수가 정의되지 않으면 각각의 개별 페이지는 별도로 실행될 수 없음
define('_CONVERT_FLOW_', true);

// 기본 시간대 설정
date_default_timezone_set("Asia/Seoul");

/********************
    경로 상수
********************/

define('CF_DOMAIN', '');

$http_host = $_SERVER['HTTP_HOST'];
define('CF_HTTPS_DOMAIN', 'https://'.$http_host.'/convert_flow');

// 디버그 설정
define('CF_DEBUG', false);

// 데이터베이스 엔진 설정
define('CF_DB_ENGINE', 'InnoDB');

// 데이터베이스 문자셋
define('CF_DB_CHARSET', 'utf8mb4');

// 쿠키 도메인
define('CF_COOKIE_DOMAIN',  '');

// URL 경로
define('CF_URL', CF_HTTPS_DOMAIN);

// 실제 서버 경로
define('CF_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))));


// 주요 디렉토리 URL
define('CF_ADMIN_URL', CF_URL.'/admin');
define('CF_ASSETS_URL', CF_URL.'/assets');
define('CF_INCLUDE_URL', CF_URL.'/include');
define('CF_CSS_URL', CF_ASSETS_URL.'/css');
define('CF_JS_URL', CF_ASSETS_URL.'/js');
define('CF_APP_URL', CF_URL.'/app');
define('CF_CAMPAIGN_URL', CF_APP_URL.'/campaign');
define('CF_CONVERSION_URL', CF_APP_URL.'/conversion');
define('CF_LANDING_URL', CF_APP_URL.'/landing');
define('CF_FORM_URL', CF_APP_URL.'/form');
define('CF_LEAD_URL', CF_APP_URL.'/lead');
define('CF_SHORT_URL', CF_APP_URL.'/shorturl');
define('CF_PUSH_URL', CF_APP_URL.'/push');
define('CF_REPORT_URL', CF_APP_URL.'/report');
define('CF_API_URL', CF_URL.'/api');
define('CF_DATA_FORM_URL', CF_URL.'/data/form');
define('CF_DATA_RANDING_URL', CF_URL.'/data/randing');

define('CF_BLOCK_EDITOR_URL', CF_INCLUDE_URL.'/block_editor');


// 주요 디렉토리 경로
define('CF_ADMIN_PATH', CF_PATH.'/admin');
define('CF_DATA_PATH', CF_PATH.'/data');
define('CF_ASSETS_PATH', CF_PATH.'/assets');
define('CF_CSS_PATH', CF_ASSETS_PATH.'/css');
define('CF_JS_PATH', CF_ASSETS_PATH.'/js');
define('CF_API_PATH', CF_PATH.'/api');
define('CF_INCLUDE_PATH', CF_PATH.'/include');
define('CF_MODEL_PATH', CF_INCLUDE_PATH.'/models');

define('CF_DATA_FORM_PATH', CF_DATA_PATH.'/form');
define('CF_DATA_RANDING_PATH', CF_DATA_PATH.'/randing');

/********************
    시간 상수
********************/
define('CF_SERVER_TIME',    time());
define('CF_TIME_YMDHIS',    date('Y-m-d H:i:s', CF_SERVER_TIME));
define('CF_TIME_YMD',       substr(CF_TIME_YMDHIS, 0, 10));
define('CF_TIME_HIS',       substr(CF_TIME_YMDHIS, 11, 8));

// 퍼미션
define('CF_DIR_PERMISSION',  0755); // 디렉토리 생성시 퍼미션
define('CF_FILE_PERMISSION', 0644); // 파일 생성시 퍼미션

/********************
    기타 상수
********************/

// 암호화 함수 지정
define('CF_STRING_ENCRYPT_FUNCTION', 'create_hash');
define('CF_MYSQL_PASSWORD_LENGTH', 41);

// SQL 에러 표시
define('CF_DISPLAY_SQL_ERROR', true);

// URL에서 사용할 캠페인 ID 길이
define('CF_CAMPAIGN_HASH_LENGTH', 8);

// 데이터베이스 설정 파일
define('CF_DBCONFIG_FILE',  'dbconfig.php');

// 썸네일 jpg Quality 설정
define('CF_THUMB_JPG_QUALITY', 90);

// 썸네일 png Compress 설정
define('CF_THUMB_PNG_COMPRESS', 5);
