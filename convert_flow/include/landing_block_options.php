<?php
/**
 * 랜딩페이지 블록 옵션 관리 파일
 * 이 파일은 블록 관련 옵션과 설정값을 관리합니다.
 */

// 블록 카테고리 목록
$block_categories = array(
    '헤더' => array('설명' => '페이지 상단에 표시되는 헤더 영역', '아이콘' => 'fa-heading'),
    '컨텐츠' => array('설명' => '본문 텍스트, 문단, 목록 등 컨텐츠 영역', '아이콘' => 'fa-align-left'),
    '이미지' => array('설명' => '이미지 갤러리, 슬라이더 등 시각적 요소', '아이콘' => 'fa-images'),
    '영상' => array('설명' => '유튜브 영상 등 시각적 요소', '아이콘' => 'fa-images'),
    '폼' => array('설명' => '사용자 입력을 위한, 신청 폼, 문의 폼 등', '아이콘' => 'fa-edit'),
    '버튼' => array('설명' => 'CTA 버튼, 링크 버튼 등 액션 요소', '아이콘' => 'fa-mouse-pointer'),
    '소셜' => array('설명' => '소셜 증빙, 후기, 공유 버튼 등', '아이콘' => 'fa-comments'),
    '푸터' => array('설명' => '페이지 하단에 표시되는 푸터 영역', '아이콘' => 'fa-window-minimize'),
    '기타' => array('설명' => '기타 특수 목적 블록', '아이콘' => 'fa-puzzle-piece'),
);

// 기본 블록 설정 값
$default_block_settings = array(
    // 헤더 블록 설정
    'header_basic' => array(
        'title' => '메인 타이틀',
        'subtitle' => '서브 타이틀',
        'background_color' => '#ffffff',
        'text_color' => '#333333',
        'logo_url' => '',
        'menu_items' => '홈,서비스,가격,문의',
        'menu_links' => '#home,#services,#pricing,#contact',
        'cta_text' => '지금 시작하기',
        'cta_link' => '#signup',
        'cta_color' => '#007bff'
    ),
    
    // 히어로 섹션 설정
    'hero_section' => array(
        'headline' => '강력한 마케팅 솔루션으로 비즈니스를 성장시키세요',
        'subheadline' => '쉽고 간편한 설정으로 효과적인 마케팅 캠페인을 만들어보세요',
        'background_type' => 'image', // image, color, gradient
        'background_image' => '',
        'background_color' => '#f8f9fa',
        'text_color' => '#333333',
        'button_text' => '자세히 알아보기',
        'button_link' => '#details',
        'button_color' => '#007bff',
        'show_arrow' => true
    ),
    
    // 기능 설명 블록 설정
    'features_block' => array(
        'title' => '주요 기능',
        'subtitle' => '당신의 비즈니스를 성공으로 이끌 핵심 기능',
        'columns' => 3,
        'feature1_title' => '강력한 분석',
        'feature1_icon' => 'fa-chart-line',
        'feature1_description' => '실시간 데이터 분석으로 마케팅 효과를 정확히 측정하세요',
        'feature2_title' => '자동화 마케팅',
        'feature2_icon' => 'fa-robot',
        'feature2_description' => '자동화된 캠페인으로 시간과 비용을 절약하세요',
        'feature3_title' => '손쉬운 통합',
        'feature3_icon' => 'fa-plug',
        'feature3_description' => '다양한 마케팅 도구와 손쉽게 연동하세요',
        'background_color' => '#ffffff',
        'text_color' => '#333333'
    ),
    
    // 신청 폼 블록 설정
    'signup_form' => array(
        'title' => '지금 신청하세요',
        'subtitle' => '무료 체험을 통해 서비스의 모든 기능을 경험해보세요',
        'button_text' => '신청하기',
        'show_name' => true,
        'show_email' => true,
        'show_phone' => true,
        'show_company' => false,
        'show_message' => false,
        'required_fields' => 'name,email',
        'success_message' => '신청이 완료되었습니다. 곧 담당자가 연락드릴 예정입니다.',
        'form_background' => '#f8f9fa',
        'button_color' => '#28a745'
    ),
    
    // 소셜 증빙 블록 설정
    'social_proof' => array(
        'title' => '고객 후기',
        'show_stars' => true,
        'testimonial1_name' => '홍길동',
        'testimonial1_company' => 'ABC 회사',
        'testimonial1_text' => '이 서비스를 사용한 후 마케팅 효율이 50% 이상 향상되었습니다. 정말 추천합니다!',
        'testimonial1_stars' => 5,
        'testimonial1_image' => '',
        'testimonial2_name' => '김철수',
        'testimonial2_company' => 'XYZ 기업',
        'testimonial2_text' => '사용하기 쉽고 효과적인 마케팅 도구입니다. 고객 전환율이 크게 향상되었습니다.',
        'testimonial2_stars' => 4,
        'testimonial2_image' => '',
        'testimonial3_name' => '이영희',
        'testimonial3_company' => '123 스타트업',
        'testimonial3_text' => '처음에는 반신반의했지만, 지금은 우리 마케팅의 핵심 도구입니다. 강력 추천합니다!',
        'testimonial3_stars' => 5,
        'testimonial3_image' => '',
        'background_color' => '#ffffff',
        'text_color' => '#333333'
    ),
    
    // 푸터 블록 설정
    'footer_basic' => array(
        'company_name' => '회사명',
        'address' => '서울 강남구 강남대로 123',
        'phone' => '02-123-4567',
        'email' => 'info@example.com',
        'copyright_text' => '© 2023 회사명. All rights reserved.',
        'show_social' => true,
        'facebook_url' => '#',
        'twitter_url' => '#',
        'instagram_url' => '#',
        'linkedin_url' => '#',
        'background_color' => '#333333',
        'text_color' => '#ffffff'
    ),
    
    // 가격표 블록 설정
    'pricing_table' => array(
        'title' => '가격 안내',
        'subtitle' => '당신의 비즈니스에 맞는 최적의 플랜을 선택하세요',
        'plan1_name' => '기본',
        'plan1_price' => '19,000',
        'plan1_period' => '월',
        'plan1_features' => '기본 기능,이메일 지원,1GB 스토리지',
        'plan1_button_text' => '선택하기',
        'plan1_button_link' => '#basic',
        'plan2_name' => '프로',
        'plan2_price' => '49,000',
        'plan2_period' => '월',
        'plan2_features' => '모든 기본 기능,우선 지원,10GB 스토리지,고급 분석',
        'plan2_button_text' => '선택하기',
        'plan2_button_link' => '#pro',
        'plan2_highlight' => true,
        'plan3_name' => '엔터프라이즈',
        'plan3_price' => '99,000',
        'plan3_period' => '월',
        'plan3_features' => '모든 프로 기능,24/7 지원,100GB 스토리지,맞춤형 솔루션,전담 매니저',
        'plan3_button_text' => '선택하기',
        'plan3_button_link' => '#enterprise',
        'background_color' => '#f8f9fa',
        'text_color' => '#333333',
        'highlight_color' => '#007bff'
    ),
    
    // 비디오 블록 설정
    'video_block' => array(
        'title' => '제품 소개 영상',
        'subtitle' => '우리 서비스가 어떻게 작동하는지 확인하세요',
        'video_type' => 'youtube', // youtube, vimeo, custom
        'video_id' => 'VIDEO_ID', // YouTube 또는 Vimeo ID
        'video_url' => '', // 커스텀 비디오 URL
        'autoplay' => false,
        'show_controls' => true,
        'background_color' => '#ffffff',
        'text_color' => '#333333'
    ),
    
    // 카운터 블록 설정
    'counter_block' => array(
        'title' => '우리의 성과',
        'subtitle' => '숫자로 보는 우리의 성공 스토리',
        'counter1_number' => '1000+',
        'counter1_label' => '고객',
        'counter1_icon' => 'fa-users',
        'counter2_number' => '50+',
        'counter2_label' => '국가',
        'counter2_icon' => 'fa-globe',
        'counter3_number' => '10M+',
        'counter3_label' => '전환',
        'counter3_icon' => 'fa-chart-line',
        'counter4_number' => '24/7',
        'counter4_label' => '지원',
        'counter4_icon' => 'fa-headset',
        'background_color' => '#f8f9fa',
        'text_color' => '#333333'
    ),
    
    // 콜투액션 블록 설정
    'cta_block' => array(
        'title' => '지금 시작하세요',
        'subtitle' => '더 이상 기다리지 마세요. 지금 바로 무료로 시작하세요!',
        'button_text' => '무료로 시작하기',
        'button_link' => '#signup',
        'show_secondary_button' => true,
        'secondary_button_text' => '자세히 알아보기',
        'secondary_button_link' => '#learn-more',
        'background_type' => 'color', // color, image, gradient
        'background_color' => '#007bff',
        'background_image' => '',
        'text_color' => '#ffffff',
        'button_color' => '#ffffff',
        'button_text_color' => '#007bff'
    )
);

// 블록별 필드 설명 (사용자 가이드)
$block_field_descriptions = array(
    'title' => '블록의 주요 제목을 입력하세요.',
    'subtitle' => '제목 아래에 표시될 부제목을 입력하세요.',
    'background_color' => '블록의 배경색을 선택하세요.',
    'text_color' => '블록 내 텍스트 색상을 선택하세요.',
    'button_text' => '버튼에 표시될 텍스트를 입력하세요.',
    'button_link' => '버튼 클릭 시 이동할 URL을 입력하세요.',
    'background_image' => '배경 이미지 URL을 입력하세요.',
    'logo_url' => '로고 이미지 URL을 입력하세요.',
    'show_social' => '소셜 미디어 아이콘 표시 여부를 선택하세요.',
    'required_fields' => '필수 입력 필드를 쉼표로 구분하여 입력하세요. (예: name,email)',
    'success_message' => '폼 제출 성공 시 표시할 메시지를 입력하세요.',
    'columns' => '컨텐츠를 표시할 열 수를 선택하세요.',
    'video_id' => 'YouTube 또는 Vimeo 비디오 ID를 입력하세요.',
    'autoplay' => '페이지 로드 시 비디오 자동 재생 여부를 선택하세요.',
    'testimonial1_stars' => '별점을 1~5 사이의 숫자로 입력하세요.',
    'plan1_features' => '플랜에 포함된 기능을 쉼표로 구분하여 입력하세요.',
    'plan2_highlight' => '이 플랜을 강조 표시할지 여부를 선택하세요.',
    'counter1_number' => '표시할 숫자 또는 텍스트를 입력하세요.',
    'counter1_icon' => 'Font Awesome 아이콘 클래스를 입력하세요. (예: fa-users)'
);

// 블록 유형별 필수 필드
$required_block_fields = array(
    'header_basic' => array('title', 'logo_url'),
    'hero_section' => array('headline', 'button_text', 'button_link'),
    'features_block' => array('title', 'feature1_title', 'feature1_description'),
    'signup_form' => array('title', 'button_text', 'success_message'),
    'social_proof' => array('title', 'testimonial1_name', 'testimonial1_text'),
    'footer_basic' => array('company_name', 'copyright_text'),
    'pricing_table' => array('title', 'plan1_name', 'plan1_price', 'plan1_features'),
    'video_block' => array('title', 'video_type'),
    'counter_block' => array('title', 'counter1_number', 'counter1_label'),
    'cta_block' => array('title', 'button_text', 'button_link')
);

/**
 * 블록 HTML 템플릿에서 설정 변수 대체
 * 
 * @param string $html HTML 템플릿
 * @param array $settings 설정 값 배열
 * @return string 설정 값이 적용된 HTML
 */
function apply_block_settings($html, $settings) {
    if (empty($html) || empty($settings) || !is_array($settings)) {
        return $html;
    }
    
    foreach ($settings as $key => $value) {
        $html = str_replace('{{' . $key . '}}', $value, $html);
    }
    
    // 남은 {{변수}} 태그 제거
    $html = preg_replace('/{{[^}]+}}/', '', $html);
    
    return $html;
}

/**
 * 블록 설정 필드 HTML 생성
 * 
 * @param array $settings 설정 값 배열
 * @param string $block_type 블록 유형
 * @return string 설정 필드 HTML
 */
function get_block_settings_fields_html($settings, $block_type) {
    global $block_field_descriptions, $required_block_fields;
    
    if (empty($settings) || !is_array($settings)) {
        return '<p class="text-muted">이 블록에는 설정 가능한 항목이 없습니다.</p>';
    }
    
    $required_fields = isset($required_block_fields[$block_type]) ? $required_block_fields[$block_type] : array();
    
    $html = '';
    
    foreach ($settings as $key => $value) {
        $field_id = "setting_" . $key;
        $field_label = str_replace('_', ' ', ucfirst($key));
        $field_desc = isset($block_field_descriptions[$key]) ? $block_field_descriptions[$key] : '';
        $is_required = in_array($key, $required_fields);
        
        // 필드 유형에 따라 다른 입력 필드 표시
        $html .= '<div class="form-group">';
        $html .= '<label for="' . $field_id . '">' . $field_label . ($is_required ? ' <span class="text-danger">*</span>' : '') . '</label>';
        
        if (is_bool($value)) {
            // 체크박스
            $html .= '<div class="custom-control custom-switch">';
            $html .= '<input type="checkbox" class="custom-control-input" id="' . $field_id . '" name="settings[' . $key . ']" value="1" ' . ($value ? 'checked' : '') . '>';
            $html .= '<label class="custom-control-label" for="' . $field_id . '"></label>';
            $html .= '</div>';
        } elseif (is_numeric($value)) {
            // 숫자 입력
            $html .= '<input type="number" class="form-control" id="' . $field_id . '" name="settings[' . $key . ']" value="' . $value . '"' . ($is_required ? ' required' : '') . '>';
        } elseif (preg_match('/^#[0-9a-f]{3,6}$/i', $value)) {
            // 색상 선택
            $html .= '<input type="color" class="form-control" id="' . $field_id . '" name="settings[' . $key . ']" value="' . $value . '"' . ($is_required ? ' required' : '') . '>';
        } elseif (strpos($key, 'image') !== false || strpos($key, 'logo') !== false || strpos($key, 'background') !== false && strpos($key, 'color') === false) {
            // 이미지 URL
            $html .= '<div class="input-group">';
            $html .= '<input type="text" class="form-control" id="' . $field_id . '" name="settings[' . $key . ']" value="' . $value . '"' . ($is_required ? ' required' : '') . '>';
            $html .= '<div class="input-group-append">';
            $html .= '<button class="btn btn-outline-secondary" type="button" onclick="openMediaLibrary(\'' . $field_id . '\')">선택</button>';
            $html .= '</div>';
            $html .= '</div>';
            
            if (!empty($value)) {
                $html .= '<div class="mt-2">';
                $html .= '<img src="' . $value . '" alt="Preview" class="img-thumbnail" style="max-height: 100px;">';
                $html .= '</div>';
            }
        } elseif (strlen($value) > 100 || strpos($key, 'text') !== false || strpos($key, 'content') !== false || strpos($key, 'description') !== false || strpos($key, 'features') !== false || strpos($key, 'message') !== false) {
            // 텍스트 영역
            $html .= '<textarea class="form-control" id="' . $field_id . '" name="settings[' . $key . ']" rows="3"' . ($is_required ? ' required' : '') . '>' . $value . '</textarea>';
        } else {
            // 기본 텍스트 입력
            $html .= '<input type="text" class="form-control" id="' . $field_id . '" name="settings[' . $key . ']" value="' . $value . '"' . ($is_required ? ' required' : '') . '>';
        }
        
        if (!empty($field_desc)) {
            $html .= '<small class="form-text text-muted">' . $field_desc . '</small>';
        }
        
        $html .= '</div>';
    }
    
    return $html;
}