<?php
/**
 * 광고 플랫폼별 설정 정보
 * 각 플랫폼에 필요한 입력 필드와 설명 정의
 */
if (!defined('_CONVERT_FLOW_')) exit;

$platform_settings = array(
    // 네이버 광고
    'naver' => array(
        'name' => '네이버 광고',
        'api_endpoint' => 'https://api.searchad.naver.com',
        'fields' => array(
            array(
                'id' => 'customer_id',
                'name' => 'customer_id',
                'label' => 'CUSTOMER_ID',
                'type' => 'text',
                'placeholder' => '네이버 광고 CUSTOMER_ID를 입력하세요',
                'description' => '네이버 검색광고 API에서 사용되는 고객 ID입니다',
                'required' => true
            ),
            array(
                'id' => 'access_license',
                'name' => 'access_license',
                'label' => '엑세스 라이선스',
                'type' => 'text',
                'placeholder' => '네이버 API 엑세스 라이선스를 입력하세요',
                'description' => '네이버 개발자 센터에서 발급받은 엑세스 라이선스 키입니다',
                'required' => true
            ),
            array(
                'id' => 'secret_key',
                'name' => 'secret_key',
                'label' => '비밀키',
                'type' => 'password',
                'placeholder' => '네이버 API 비밀키를 입력하세요',
                'description' => '네이버 개발자 센터에서 발급받은 비밀키입니다',
                'required' => true
            ),
            array(
                'id' => 'tracking_id',
                'name' => 'tracking_id',
                'label' => '전환 추적 ID',
                'type' => 'text',
                'placeholder' => '전환 추적을 위한 ID를 입력하세요',
                'description' => '네이버 광고 전환 추적을 위한 ID입니다',
                'required' => false
            )
        )
    ),
    
    // 구글 애즈
    'google_ads' => array(
        'name' => '구글 애즈',
        'api_endpoint' => 'https://api.searchad.naver.com',
        'fields' => array(
            array(
                'id' => 'account_id',
                'name' => 'account_id',
                'label' => '계정 ID',
                'type' => 'text',
                'placeholder' => '구글 애즈 계정 ID를 입력하세요',
                'description' => '구글 애즈 계정의 고유 ID입니다',
                'required' => true
            ),
            array(
                'id' => 'tracking_id',
                'name' => 'tracking_id',
                'label' => '전환 ID',
                'type' => 'text',
                'placeholder' => '예: AW-XXXXXXXXXX',
                'description' => '구글 애즈 전환 추적을 위한 ID입니다',
                'required' => false
            ),
            array(
                'id' => 'conversion_label',
                'name' => 'conversion_label',
                'label' => '전환 레이블',
                'type' => 'text',
                'placeholder' => '예: ABCDEFGHIJ',
                'description' => '전환 추적을 위한 레이블입니다',
                'required' => false
            ),
            array(
                'id' => 'refresh_token',
                'name' => 'refresh_token',
                'label' => 'OAuth 리프레시 토큰',
                'type' => 'text',
                'placeholder' => 'OAuth 리프레시 토큰을 입력하세요',
                'description' => 'API 인증을 위한 리프레시 토큰입니다',
                'required' => false
            )
        )
    ),
    
    // 페이스북(메타) 광고
    'facebook' => array(
        'name' => '페이스북 광고',
        'api_endpoint' => 'https://api.searchad.naver.com',
        'fields' => array(
            array(
                'id' => 'account_id',
                'name' => 'account_id',
                'label' => '광고 계정 ID',
                'type' => 'text',
                'placeholder' => '예: act_123456789',
                'description' => '페이스북 광고 계정 ID입니다',
                'required' => true
            ),
            array(
                'id' => 'pixel_id',
                'name' => 'pixel_id',
                'label' => '픽셀 ID',
                'type' => 'text',
                'placeholder' => '예: 123456789012345',
                'description' => '페이스북 픽셀 ID입니다',
                'required' => false
            ),
            array(
                'id' => 'access_token',
                'name' => 'access_token',
                'label' => '액세스 토큰',
                'type' => 'textarea',
                'placeholder' => '페이스북 API 액세스 토큰을 입력하세요',
                'description' => '장기 액세스 토큰을 입력해주세요',
                'required' => true
            )
        )
    ),
    
    // 카카오 모먼트
    'kakao' => array(
        'name' => '카카오 모먼트',
        'api_endpoint' => 'https://api.searchad.naver.com',
        'fields' => array(
            array(
                'id' => 'account_id',
                'name' => 'account_id',
                'label' => '광고 계정 ID',
                'type' => 'text',
                'placeholder' => '카카오 광고 계정 ID를 입력하세요',
                'description' => '카카오 모먼트 광고 계정 ID입니다',
                'required' => true
            ),
            array(
                'id' => 'api_key',
                'name' => 'api_key',
                'label' => 'REST API 키',
                'type' => 'text',
                'placeholder' => '카카오 개발자 센터에서 발급받은 REST API 키를 입력하세요',
                'description' => '카카오 API 사용을 위한 키입니다',
                'required' => true
            ),
            array(
                'id' => 'tracking_id',
                'name' => 'tracking_id',
                'label' => '픽셀 ID',
                'type' => 'text',
                'placeholder' => '예: XXXXXXXXXX',
                'description' => '카카오 픽셀 ID입니다',
                'required' => false
            )
        )
    )
);

return $platform_settings;