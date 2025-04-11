-- 사용자 테이블
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '사용자 ID',
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '로그인 ID',
    password VARCHAR(255) NOT NULL COMMENT '암호화된 비밀번호',
    email VARCHAR(100) NOT NULL UNIQUE COMMENT '이메일',
    full_name VARCHAR(100) COMMENT '실명',
    role ENUM('관리자', '편집자', '뷰어') DEFAULT '뷰어' COMMENT '권한',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='시스템 사용자';

-- 사용자 설정 테이블
CREATE TABLE user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '설정 ID',
    user_id INT NOT NULL COMMENT '사용자 ID',
    notification_email BOOLEAN DEFAULT TRUE COMMENT '이메일 알림',
    notification_slack BOOLEAN DEFAULT FALSE COMMENT 'Slack 알림',
    notification_sms BOOLEAN DEFAULT FALSE COMMENT 'SMS 알림',
    slack_webhook_url VARCHAR(255) COMMENT 'Slack 웹훅 URL',
    phone_number VARCHAR(20) COMMENT 'SMS 전화번호',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='사용자별 설정';



-- 광고 플랫폼 테이블
CREATE TABLE ad_platforms (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '플랫폼 ID',
    platform_code VARCHAR(50) NOT NULL UNIQUE COMMENT '플랫폼 코드',
    name VARCHAR(100) NOT NULL COMMENT '플랫폼 이름',
    description TEXT COMMENT '설명',
    api_endpoint VARCHAR(255) COMMENT 'API 엔드포인트',
    auth_type ENUM('api_key', 'oauth', 'basic') NOT NULL COMMENT '인증 방식',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    INDEX idx_platform_code (platform_code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='지원되는 광고 플랫폼';

-- 사용자 광고 계정 테이블
CREATE TABLE user_ad_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '계정 ID',
    user_id INT NOT NULL COMMENT '사용자 ID',
    platform_id INT NOT NULL COMMENT '플랫폼 ID',
    account_name VARCHAR(100) NOT NULL COMMENT '계정 표시명',
    account_id VARCHAR(100) NOT NULL COMMENT '플랫폼 계정 ID',
    access_token TEXT COMMENT 'OAuth 액세스 토큰',
    refresh_token TEXT COMMENT 'OAuth 리프레시 토큰',
    token_expires_at TIMESTAMP NULL COMMENT '토큰 만료일시',
    api_key VARCHAR(255) COMMENT 'API 키',
    status ENUM('활성', '비활성', '인증필요') DEFAULT '활성' COMMENT '상태',
    last_synced_at TIMESTAMP NULL COMMENT '마지막 동기화 일시',
    settings JSON COMMENT '추가 설정',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (platform_id) REFERENCES ad_platforms(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_platform_account (user_id, platform_id, account_id),
    INDEX idx_status (status),
    INDEX idx_last_synced_at (last_synced_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='사용자별 광고 계정';

-- 광고 계정 토큰 테이블
CREATE TABLE ad_account_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '토큰 ID',
    user_ad_account_id INT NOT NULL COMMENT '광고 계정 ID',
    token_type VARCHAR(50) NOT NULL COMMENT '토큰 유형',
    token_value TEXT NOT NULL COMMENT '토큰 값',
    expires_at TIMESTAMP NULL COMMENT '만료 시간',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (user_ad_account_id) REFERENCES user_ad_accounts(id) ON DELETE CASCADE,
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='광고 계정 인증 토큰';



-- 캠페인 테이블
CREATE TABLE campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '캠페인 ID',
    user_id INT NOT NULL COMMENT '사용자 ID',
    name VARCHAR(100) NOT NULL COMMENT '캠페인명',
    campaign_hash VARCHAR(32) NOT NULL COMMENT 'URL 추적용 해시',
    status ENUM('활성', '비활성', '일시중지') DEFAULT '활성' COMMENT '상태',
    start_date DATE NOT NULL COMMENT '시작일',
    end_date DATE COMMENT '종료일 (NULL은 무기한)',
    budget DECIMAL(12, 2) COMMENT '총 예산',
    daily_budget DECIMAL(10, 2) COMMENT '일일 예산',
    cpa_goal DECIMAL(10, 2) COMMENT '목표 CPA',
    description TEXT COMMENT '설명',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_campaign_hash (campaign_hash),
    INDEX idx_status (status),
    INDEX idx_date_range (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='마케팅 캠페인';

-- 외부 캠페인 연결 테이블
CREATE TABLE external_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '연결 ID',
    campaign_id INT NOT NULL COMMENT '내부 캠페인 ID',
    user_ad_account_id INT NOT NULL COMMENT '광고 계정 ID',
    external_campaign_id VARCHAR(100) NOT NULL COMMENT '외부 캠페인 ID',
    external_campaign_name VARCHAR(255) COMMENT '외부 캠페인명',
    status VARCHAR(50) COMMENT '외부 캠페인 상태',
    budget DECIMAL(12, 2) COMMENT '예산',
    start_date DATE COMMENT '시작일',
    end_date DATE COMMENT '종료일',
    external_data JSON COMMENT '원본 데이터',
    last_synced_at TIMESTAMP NULL COMMENT '마지막 동기화 일시',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_ad_account_id) REFERENCES user_ad_accounts(id) ON DELETE CASCADE,
    UNIQUE KEY uk_account_ext_campaign (user_ad_account_id, external_campaign_id),
    INDEX idx_last_synced_at (last_synced_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='외부 캠페인 연결';

-- 캠페인 템플릿 테이블
CREATE TABLE campaign_templates (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '템플릿 ID',
    user_id INT NOT NULL COMMENT '사용자 ID',
    name VARCHAR(100) NOT NULL COMMENT '템플릿명',
    target_region VARCHAR(255) COMMENT '타겟 지역',
    daily_budget DECIMAL(10, 2) COMMENT '일일 예산',
    cpa_goal DECIMAL(10, 2) COMMENT '목표 CPA',
    template_data JSON NOT NULL COMMENT '템플릿 데이터',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='캠페인 템플릿';




-- 광고 소재 테이블
CREATE TABLE ad_materials (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '소재 ID',
    campaign_id INT NOT NULL COMMENT '캠페인 ID',
    user_ad_account_id INT NOT NULL COMMENT '광고 계정 ID',
    external_campaign_id VARCHAR(100) COMMENT '외부 캠페인 ID',
    external_ad_group_id VARCHAR(100) COMMENT '외부 광고 그룹 ID',
    external_ad_id VARCHAR(100) COMMENT '외부 광고 ID',
    ad_name VARCHAR(255) COMMENT '광고명',
    ad_type VARCHAR(50) COMMENT '광고 유형',
    headline VARCHAR(255) COMMENT '제목',
    description TEXT COMMENT '설명',
    image_url VARCHAR(255) COMMENT '이미지 URL',
    destination_url VARCHAR(1024) COMMENT '목적지 URL',
    status VARCHAR(50) DEFAULT '활성' COMMENT '상태',
    external_data JSON COMMENT '원본 데이터',
    last_synced_at TIMESTAMP NULL COMMENT '마지막 동기화 일시',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_ad_account_id) REFERENCES user_ad_accounts(id) ON DELETE CASCADE,
    INDEX idx_external_ids (external_campaign_id, external_ad_group_id, external_ad_id),
    INDEX idx_ad_type (ad_type),
    INDEX idx_status (status),
    INDEX idx_last_synced_at (last_synced_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='광고 소재';

-- 광고 그룹 테이블 (선택적)
CREATE TABLE ad_groups (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '광고 그룹 ID',
    campaign_id INT NOT NULL COMMENT '캠페인 ID',
    user_ad_account_id INT NOT NULL COMMENT '광고 계정 ID',
    external_campaign_id VARCHAR(100) COMMENT '외부 캠페인 ID',
    external_ad_group_id VARCHAR(100) NOT NULL COMMENT '외부 광고 그룹 ID',
    name VARCHAR(255) NOT NULL COMMENT '광고 그룹명',
    status VARCHAR(50) DEFAULT '활성' COMMENT '상태',
    targeting_type VARCHAR(50) COMMENT '타겟팅 유형',
    targeting_data JSON COMMENT '타겟팅 데이터',
    bid_amount DECIMAL(10, 2) COMMENT '입찰가',
    external_data JSON COMMENT '원본 데이터',
    last_synced_at TIMESTAMP NULL COMMENT '마지막 동기화 일시',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_ad_account_id) REFERENCES user_ad_accounts(id) ON DELETE CASCADE,
    UNIQUE KEY uk_account_ext_adgroup (user_ad_account_id, external_ad_group_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='광고 그룹';



-- 단축 URL 테이블
CREATE TABLE shortened_urls (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '단축 URL ID',
    user_id INT NOT NULL COMMENT '사용자 ID',
    campaign_id INT NOT NULL COMMENT '캠페인 ID',
    ad_material_id INT COMMENT '광고 소재 ID',
    original_url VARCHAR(2048) NOT NULL COMMENT '원본 URL',
    random_code VARCHAR(20) NOT NULL COMMENT '고유 랜덤 코드',
    path VARCHAR(255) NOT NULL COMMENT 'URL 경로',
    utm_source VARCHAR(100) COMMENT 'UTM 소스',
    utm_medium VARCHAR(100) COMMENT 'UTM 매체',
    utm_campaign VARCHAR(100) COMMENT 'UTM 캠페인',
    utm_content VARCHAR(100) COMMENT 'UTM 콘텐츠',
    utm_term VARCHAR(100) COMMENT 'UTM 키워드',
    qr_code_url VARCHAR(255) COMMENT 'QR 코드 URL',
    expiration_date DATE COMMENT '만료일',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (ad_material_id) REFERENCES ad_materials(id) ON DELETE SET NULL,
    UNIQUE KEY uk_random_code (random_code),
    INDEX idx_path (path),
    INDEX idx_utm (utm_source, utm_medium, utm_campaign)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='단축 URL';

-- URL 클릭 이벤트 테이블
CREATE TABLE url_clicks (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '클릭 ID',
    url_id INT NOT NULL COMMENT '단축 URL ID',
    campaign_id INT NOT NULL COMMENT '캠페인 ID',
    ad_material_id INT COMMENT '광고 소재 ID',
    platform_id INT COMMENT '플랫폼 ID',
    click_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '클릭 시간',
    ip_address VARCHAR(45) COMMENT 'IP 주소',
    user_agent TEXT COMMENT '사용자 에이전트',
    referrer VARCHAR(2048) COMMENT '참조 URL',
    device_type ENUM('desktop', 'mobile', 'tablet', 'other') DEFAULT 'other' COMMENT '기기 유형',
    browser VARCHAR(50) COMMENT '브라우저',
    os VARCHAR(50) COMMENT '운영체제',
    country VARCHAR(50) COMMENT '국가',
    region VARCHAR(100) COMMENT '지역',
    city VARCHAR(100) COMMENT '도시',
    impression INT DEFAULT 0 COMMENT '노출 수',
    ad_cost DECIMAL(10, 2) DEFAULT 0 COMMENT '광고비',
    FOREIGN KEY (url_id) REFERENCES shortened_urls(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (ad_material_id) REFERENCES ad_materials(id) ON DELETE SET NULL,
    FOREIGN KEY (platform_id) REFERENCES ad_platforms(id) ON DELETE SET NULL,
    INDEX idx_click_time (click_time),
    INDEX idx_device (device_type, browser, os),
    INDEX idx_location (country, region, city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='URL 클릭 이벤트';



-- URL 그룹 테이블
CREATE TABLE url_groups (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '그룹 ID',
    user_id INT NOT NULL COMMENT '사용자 ID',
    name VARCHAR(100) NOT NULL COMMENT '그룹명',
    description TEXT COMMENT '설명',
    color VARCHAR(20) COMMENT '표시 색상',
    icon VARCHAR(50) COMMENT '아이콘',
    is_favorite BOOLEAN DEFAULT FALSE COMMENT '즐겨찾기 여부',
    campaign_id INT COMMENT '연결된 캠페인 ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_is_favorite (is_favorite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='URL 그룹 관리';


-- 전환 이벤트 정의 테이블
CREATE TABLE conversion_events (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '이벤트 ID',
    event_code VARCHAR(50) NOT NULL UNIQUE COMMENT '이벤트 코드',
    name VARCHAR(100) NOT NULL COMMENT '이벤트명',
    description TEXT COMMENT '설명',
    is_custom TINYINT(1) DEFAULT 0 COMMENT '사용자 정의 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    INDEX idx_event_code (event_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='전환 이벤트 정의';

-- 전환 스크립트 테이블
CREATE TABLE conversion_scripts (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '스크립트 ID',
    campaign_id INT NOT NULL COMMENT '캠페인 ID',
    event_id INT NOT NULL COMMENT '이벤트 ID',
    conversion_type ENUM('구매완료', '회원가입', '다운로드', '문의하기', '기타') NOT NULL COMMENT '전환 유형',
    script_code TEXT NOT NULL COMMENT 'JavaScript 코드',
    installation_guide TEXT COMMENT '설치 가이드',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES conversion_events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='전환 추적 스크립트';

-- 전환 이벤트 로그 테이블
CREATE TABLE conversion_events_log (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '로그 ID',
    campaign_id INT NOT NULL COMMENT '캠페인 ID',
    landing_page_id INT NOT NULL COMMENT '랜딩페이지 ID',
    event_id INT NOT NULL COMMENT '이벤트 ID',
    url_id INT COMMENT '단축 URL ID',
    ad_material_id INT COMMENT '광고 소재 ID',
    platform_id INT COMMENT '플랫폼 ID',
    event_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '발생 시간',
    conversion_value DECIMAL(12, 2) DEFAULT 0 COMMENT '전환 가치',
    ip_address VARCHAR(45) COMMENT 'IP 주소',
    user_agent TEXT COMMENT '사용자 에이전트',
    device_type ENUM('desktop', 'mobile', 'tablet', 'unknown') DEFAULT 'unknown' COMMENT '기기 유형',
    source VARCHAR(100) COMMENT '소스',
    medium VARCHAR(100) COMMENT '매체',
    country VARCHAR(50) COMMENT '국가',
    city VARCHAR(100) COMMENT '도시',
    additional_data JSON COMMENT '추가 데이터',
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (landing_page_id) REFERENCES landing_pages(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES conversion_events(id) ON DELETE CASCADE,
    FOREIGN KEY (url_id) REFERENCES shortened_urls(id) ON DELETE SET NULL,
    FOREIGN KEY (ad_material_id) REFERENCES ad_materials(id) ON DELETE SET NULL,
    FOREIGN KEY (platform_id) REFERENCES ad_platforms(id) ON DELETE SET NULL,
    INDEX idx_event_time (event_time),
    INDEX idx_source_medium (source, medium),
    INDEX idx_device (device_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='전환 이벤트 로그';

-- 플랫폼별 전환 이벤트 매핑 테이블
CREATE TABLE platform_events (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '매핑 ID',
    platform_id INT NOT NULL COMMENT '플랫폼 ID',
    event_id INT NOT NULL COMMENT '이벤트 ID',
    platform_event_code VARCHAR(100) NOT NULL COMMENT '플랫폼 이벤트 코드',
    script_snippet TEXT COMMENT '스크립트 스니펫',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (platform_id) REFERENCES ad_platforms(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES conversion_events(id) ON DELETE CASCADE,
    UNIQUE KEY uk_platform_event (platform_id, event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='플랫폼별 전환 이벤트 매핑';



-- 광고 비용 테이블
CREATE TABLE ad_costs (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '비용 ID',
    campaign_id INT NOT NULL COMMENT '캠페인 ID',
    user_ad_account_id INT NOT NULL COMMENT '광고 계정 ID',
    external_campaign_id VARCHAR(100) COMMENT '외부 캠페인 ID',
    external_ad_group_id VARCHAR(100) COMMENT '외부 광고 그룹 ID',
    ad_material_id INT COMMENT '광고 소재 ID',
    cost_date DATE NOT NULL COMMENT '비용 발생일',
    ad_cost DECIMAL(12, 2) NOT NULL DEFAULT 0 COMMENT '광고비',
    clicks INT NOT NULL DEFAULT 0 COMMENT '클릭 수',
    impressions INT NOT NULL DEFAULT 0 COMMENT '노출 수',
    conversions INT DEFAULT 0 COMMENT '전환 수',
    conversion_value DECIMAL(12, 2) DEFAULT 0 COMMENT '전환 가치',
    source VARCHAR(100) COMMENT '소스',
    medium VARCHAR(100) COMMENT '매체',
    external_data JSON COMMENT '원본 데이터',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_ad_account_id) REFERENCES user_ad_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (ad_material_id) REFERENCES ad_materials(id) ON DELETE SET NULL,
    UNIQUE KEY uk_cost_entry (campaign_id, user_ad_account_id, external_campaign_id, ad_material_id, cost_date),
    INDEX idx_cost_date (cost_date),
    INDEX idx_source_medium (source, medium)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='광고 비용';

-- 캠페인별 광고 계정 설정 테이블
CREATE TABLE campaign_ad_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '설정 ID',
    campaign_id INT NOT NULL COMMENT '캠페인 ID',
    user_ad_account_id INT NOT NULL COMMENT '광고 계정 ID',
    custom_settings JSON COMMENT '커스텀 설정',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_ad_account_id) REFERENCES user_ad_accounts(id) ON DELETE CASCADE,
    UNIQUE KEY uk_campaign_account (campaign_id, user_ad_account_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='캠페인별 광고 계정 설정';




-- API 동기화 작업 테이블
CREATE TABLE api_sync_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '작업 ID',
    user_id INT NOT NULL COMMENT '사용자 ID',
    user_ad_account_id INT NOT NULL COMMENT '광고 계정 ID',
    job_type ENUM('캠페인가져오기', '소재가져오기', '비용가져오기', '전환설정') NOT NULL COMMENT '작업 유형',
    status ENUM('대기중', '진행중', '완료', '실패') DEFAULT '대기중' COMMENT '상태',
    start_date DATE COMMENT '데이터 시작일',
    end_date DATE COMMENT '데이터 종료일',
    parameters JSON COMMENT '작업 파라미터',
    result_summary JSON COMMENT '결과 요약',
    error_message TEXT COMMENT '오류 메시지',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
    started_at TIMESTAMP NULL COMMENT '시작 시간',
    completed_at TIMESTAMP NULL COMMENT '완료 시간',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_ad_account_id) REFERENCES user_ad_accounts(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_job_type (job_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API 동기화 작업';

-- API 호출 로그 테이블
CREATE TABLE api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '로그 ID',
    platform_id INT NOT NULL COMMENT '플랫폼 ID',
    user_ad_account_id INT COMMENT '광고 계정 ID',
    request_type VARCHAR(10) NOT NULL COMMENT '요청 유형',
    endpoint VARCHAR(255) NOT NULL COMMENT 'API 엔드포인트',
    request_headers TEXT COMMENT '요청 헤더',
    request_body TEXT COMMENT '요청 본문',
    response_code INT COMMENT '응답 코드',
    response_body TEXT COMMENT '응답 본문',
    execution_time DECIMAL(10, 3) COMMENT '실행 시간(초)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
    FOREIGN KEY (platform_id) REFERENCES ad_platforms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_ad_account_id) REFERENCES user_ad_accounts(id) ON DELETE SET NULL,
    INDEX idx_created_at (created_at),
    INDEX idx_response_code (response_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API 호출 로그';




-- 시스템 로그 테이블
CREATE TABLE system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '로그 ID',
    user_id INT COMMENT '사용자 ID',
    log_type ENUM('사용자활동', '시스템이벤트', '오류', '보안', '성능') NOT NULL COMMENT '로그 유형',
    action VARCHAR(255) NOT NULL COMMENT '수행된 작업',
    ip_address VARCHAR(45) COMMENT 'IP 주소',
    user_agent TEXT COMMENT '사용자 에이전트',
    log_data JSON COMMENT '추가 데이터',
    severity ENUM('정보', '경고', '오류', '심각') DEFAULT '정보' COMMENT '심각도',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_log_type (log_type),
    INDEX idx_severity (severity),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='시스템 로그';

-- 성과 보고서 테이블
CREATE TABLE performance_reports (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '보고서 ID',
    user_id INT NOT NULL COMMENT '사용자 ID',
    campaign_id INT COMMENT '캠페인 ID (NULL이면 전체)',
    report_name VARCHAR(100) NOT NULL COMMENT '보고서명',
    report_type ENUM('일간', '주간', '월간', '커스텀') NOT NULL COMMENT '보고서 주기',
    start_date DATE NOT NULL COMMENT '시작일',
    end_date DATE NOT NULL COMMENT '종료일',
    metrics JSON NOT NULL COMMENT '포함 지표',
    dimensions JSON COMMENT '분석 차원',
    filters JSON COMMENT '필터 조건',
    format ENUM('PDF', 'CSV', 'EXCEL', 'HTML') DEFAULT 'PDF' COMMENT '형식',
    schedule JSON COMMENT '자동 발송 일정',
    recipients TEXT COMMENT '수신자 이메일',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    INDEX idx_date_range (start_date, end_date),
    INDEX idx_report_type (report_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='성과 보고서 설정';

-- 대시보드 위젯 테이블
CREATE TABLE dashboard_widgets (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '위젯 ID',
    user_id INT NOT NULL COMMENT '사용자 ID',
    name VARCHAR(100) NOT NULL COMMENT '위젯명',
    type ENUM('캠페인성과', '전환추이', '리드현황', 'A/B테스트결과', '지출현황', '고객획득비용') NOT NULL COMMENT '위젯 유형',
    settings JSON NOT NULL COMMENT '위젯 설정',
    layout_position INT DEFAULT 0 COMMENT '대시보드 위치',
    layout_size ENUM('소', '중', '대') DEFAULT '중' COMMENT '위젯 크기',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='대시보드 위젯';
    



-- 랜딩페이지 템플릿 테이블
CREATE TABLE landing_page_templates (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '템플릿 ID',
    name VARCHAR(100) NOT NULL COMMENT '템플릿명',
    industry ENUM('금융', '교육', '건강', '소매', '여행', '기타') NOT NULL COMMENT '산업 분류',
    description TEXT COMMENT '설명',
    thumbnail_url VARCHAR(255) COMMENT '미리보기 이미지',
    html_template TEXT NOT NULL COMMENT 'HTML 템플릿',
    css_template TEXT COMMENT 'CSS 템플릿',
    js_template TEXT COMMENT 'JS 템플릿',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    INDEX idx_industry (industry)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='랜딩페이지 템플릿';

-- 랜딩페이지 테이블
CREATE TABLE landing_pages (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '랜딩페이지 ID',
    user_id INT NOT NULL COMMENT '사용자 ID',
    campaign_id INT COMMENT '캠페인 ID',
    template_id INT COMMENT '템플릿 ID',
    name VARCHAR(100) NOT NULL COMMENT '랜딩페이지명',
    slug VARCHAR(100) NOT NULL COMMENT 'URL 경로명',
    status ENUM('초안', '게시됨', '보관됨') DEFAULT '초안' COMMENT '상태',
    html_content TEXT COMMENT 'HTML 내용',
    css_content TEXT COMMENT 'CSS 내용',
    js_content TEXT COMMENT 'JS 내용',
    meta_title VARCHAR(255) COMMENT 'SEO 타이틀',
    meta_description TEXT COMMENT 'SEO 설명',
    custom_domain VARCHAR(255) COMMENT '커스텀 도메인',
    is_responsive BOOLEAN DEFAULT TRUE COMMENT '반응형 여부',
    published_url VARCHAR(255) COMMENT '게시된 URL',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    published_at TIMESTAMP NULL COMMENT '게시일시',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (template_id) REFERENCES landing_page_templates(id) ON DELETE SET NULL,
    UNIQUE KEY uk_user_slug (user_id, slug),
    INDEX idx_status (status),
    INDEX idx_published_at (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='랜딩페이지';

-- 블록 타입 테이블
CREATE TABLE landing_block_types (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '블록 타입 ID',
    name VARCHAR(50) NOT NULL COMMENT '타입명',
    description TEXT COMMENT '설명',
    icon VARCHAR(100) COMMENT '아이콘 경로',
    category ENUM('헤더', '컨텐츠', '이미지', '폼', '버튼', '소셜', '푸터', '기타') NOT NULL COMMENT '카테고리',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='랜딩페이지 블록 타입';

-- 블록 템플릿 테이블
CREATE TABLE landing_block_templates (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '블록 템플릿 ID',
    block_type_id INT NOT NULL COMMENT '블록 타입 ID',
    name VARCHAR(100) NOT NULL COMMENT '템플릿명',
    thumbnail VARCHAR(255) COMMENT '미리보기 이미지',
    html_content TEXT NOT NULL COMMENT 'HTML 내용',
    css_content TEXT COMMENT 'CSS 스타일',
    js_content TEXT COMMENT 'JS 코드',
    default_settings JSON COMMENT '기본 설정',
    is_public BOOLEAN DEFAULT TRUE COMMENT '공개 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    FOREIGN KEY (block_type_id) REFERENCES landing_block_types(id) ON DELETE CASCADE,
    INDEX idx_block_type_id (block_type_id),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='랜딩페이지 블록 템플릿';

-- 랜딩페이지 블록 인스턴스 테이블
CREATE TABLE landing_page_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '블록 ID',
    landing_page_id INT NOT NULL COMMENT '랜딩페이지 ID',
    block_template_id INT NOT NULL COMMENT '블록 템플릿 ID',
    block_order INT NOT NULL COMMENT '순서',
    settings JSON COMMENT '설정 값',
    custom_html TEXT COMMENT '사용자 정의 HTML',
    custom_css TEXT COMMENT '사용자 정의 CSS',
    custom_js TEXT COMMENT '사용자 정의 JS',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (landing_page_id) REFERENCES landing_pages(id) ON DELETE CASCADE,
    FOREIGN KEY (block_template_id) REFERENCES landing_block_templates(id) ON DELETE CASCADE,
    INDEX idx_landing_page_id (landing_page_id),
    INDEX idx_block_order (block_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='랜딩페이지 블록';

-- 랜딩페이지 방문 로그 테이블
CREATE TABLE landing_page_visits (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '방문 ID',
    landing_page_id INT NOT NULL COMMENT '랜딩페이지 ID',
    campaign_id INT COMMENT '캠페인 ID',
    url_id INT COMMENT '단축 URL ID',
    ad_material_id INT COMMENT '광고 소재 ID',
    visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '방문 시간',
    ip_address VARCHAR(45) COMMENT 'IP 주소',
    user_agent TEXT COMMENT '사용자 에이전트',
    referrer VARCHAR(2048) COMMENT '참조 URL',
    device_type ENUM('desktop', 'mobile', 'tablet', 'other') DEFAULT 'other' COMMENT '기기 유형',
    utm_source VARCHAR(100) COMMENT 'UTM 소스',
    utm_medium VARCHAR(100) COMMENT 'UTM 매체',
    utm_campaign VARCHAR(100) COMMENT 'UTM 캠페인',
    utm_content VARCHAR(100) COMMENT 'UTM 콘텐츠',
    utm_term VARCHAR(100) COMMENT 'UTM 키워드',
    session_id VARCHAR(100) COMMENT '세션 ID',
    country VARCHAR(50) COMMENT '국가',
    region VARCHAR(100) COMMENT '지역',
    city VARCHAR(100) COMMENT '도시',
    FOREIGN KEY (landing_page_id) REFERENCES landing_pages(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (url_id) REFERENCES shortened_urls(id) ON DELETE SET NULL,
    FOREIGN KEY (ad_material_id) REFERENCES ad_materials(id) ON DELETE SET NULL,
    INDEX idx_visit_time (visit_time),
    INDEX idx_device_type (device_type),
    INDEX idx_utm (utm_source, utm_medium, utm_campaign)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='랜딩페이지 방문 로그';

-- 랜딩페이지 전환 설정 테이블
CREATE TABLE landing_page_conversions (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '설정 ID',
    landing_page_id INT NOT NULL COMMENT '랜딩페이지 ID',
    event_id INT NOT NULL COMMENT '이벤트 ID',
    trigger_element VARCHAR(255) COMMENT '트리거 요소',
    trigger_action ENUM('click', 'submit', 'pageview', 'scroll', 'custom') NOT NULL COMMENT '트리거 액션',
    custom_script TEXT COMMENT '커스텀 스크립트',
    is_active BOOLEAN DEFAULT TRUE COMMENT '활성화 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (landing_page_id) REFERENCES landing_pages(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES conversion_events(id) ON DELETE CASCADE,
    INDEX idx_landing_page_id (landing_page_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='랜딩페이지 전환 설정';





-- 폼 테이블
CREATE TABLE forms (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '폼 ID',
    user_id INT NOT NULL COMMENT '사용자 ID',
    landing_page_id INT COMMENT '랜딩페이지 ID',
    name VARCHAR(100) NOT NULL COMMENT '폼명',
    submit_button_text VARCHAR(50) DEFAULT '제출하기' COMMENT '제출 버튼 텍스트',
    success_message TEXT COMMENT '성공 메시지',
    redirect_url VARCHAR(255) COMMENT '리다이렉트 URL',
    is_multi_step BOOLEAN DEFAULT FALSE COMMENT '다단계 여부',
    auto_save_enabled BOOLEAN DEFAULT TRUE COMMENT '자동 저장 활성화',
    cta_type ENUM('button','image') DEFAULT 'button' COMMENT 'CTA 버튼 유형',
    cta_button_bg_color VARCHAR(20) DEFAULT '#007bff' COMMENT '버튼 배경색',
    cta_button_text_color VARCHAR(20) DEFAULT '#ffffff' COMMENT '버튼 글자색',
    cta_button_size ENUM('small','medium','large') DEFAULT 'medium' COMMENT '버튼 크기',
    cta_button_radius INT DEFAULT 4 COMMENT '버튼 라운드 크기(px)',
    cta_button_border_color VARCHAR(20) DEFAULT '#007bff' COMMENT '버튼 테두리 색상',
    cta_image_path VARCHAR(255) DEFAULT NULL COMMENT 'CTA 이미지 경로',
    complete_type ENUM('text','image') DEFAULT 'text' COMMENT '완료 메시지 유형',
    complete_text_bg_color VARCHAR(20) DEFAULT '#d4edda' COMMENT '완료 메시지 배경색',
    complete_text_color VARCHAR(20) DEFAULT '#155724' COMMENT '완료 메시지 글자색',
    complete_text_size ENUM('small','medium','large') DEFAULT 'medium' COMMENT '완료 메시지 글자 크기',
    complete_image_path VARCHAR(255) DEFAULT NULL COMMENT '완료 메시지 이미지 경로',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (landing_page_id) REFERENCES landing_pages(id) ON DELETE CASCADE,
    INDEX idx_landing_page_id (landing_page_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='폼';

-- 폼 필드 테이블
CREATE TABLE form_fields (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '필드 ID',
    form_id INT NOT NULL COMMENT '폼 ID',
    label VARCHAR(100) NOT NULL COMMENT '라벨',
    type ENUM('text', 'email', 'tel', 'textarea', 'select', 'checkbox', 'radio', 'date', 'number', 'hidden') NOT NULL COMMENT '유형',
    placeholder VARCHAR(100) COMMENT '플레이스홀더',
    default_value VARCHAR(255) COMMENT '기본값',
    is_required BOOLEAN DEFAULT FALSE COMMENT '필수 여부',
    validation_rule VARCHAR(255) COMMENT '유효성 검사 규칙',
    error_message VARCHAR(255) COMMENT '오류 메시지',
    step_number INT DEFAULT 1 COMMENT '다단계 번호',
    field_order INT DEFAULT 0 COMMENT '순서',
    options JSON COMMENT '선택지 옵션',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
    INDEX idx_form_id (form_id),
    INDEX idx_field_order (field_order),
    INDEX idx_step_number (step_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='폼 필드';

-- 폼 조건부 로직 테이블
CREATE TABLE form_conditional_logic (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '로직 ID',
    form_id INT NOT NULL COMMENT '폼 ID',
    source_field_id INT NOT NULL COMMENT '조건 필드 ID',
    target_field_id INT NOT NULL COMMENT '대상 필드 ID',
    compare_operator ENUM('equals', 'not_equals', 'contains', 'greater_than', 'less_than') NOT NULL COMMENT '비교 연산자',
    value TEXT NOT NULL COMMENT '비교 값',
    action ENUM('show', 'hide', 'enable', 'disable', 'require') NOT NULL COMMENT '수행 동작',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
    FOREIGN KEY (source_field_id) REFERENCES form_fields(id) ON DELETE CASCADE,
    FOREIGN KEY (target_field_id) REFERENCES form_fields(id) ON DELETE CASCADE,
    INDEX idx_form_id (form_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='폼 조건부 로직';




-- 리드 데이터 테이블
CREATE TABLE leads (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '리드 ID',
    form_id INT NOT NULL COMMENT '폼 ID',
    campaign_id INT COMMENT '캠페인 ID',
    landing_page_id INT COMMENT '랜딩페이지 ID',
    data JSON NOT NULL COMMENT '제출 데이터',
    ip_address VARCHAR(45) COMMENT 'IP 주소',
    user_agent TEXT COMMENT '사용자 에이전트',
    referrer VARCHAR(255) COMMENT '참조 URL',
    utm_source VARCHAR(100) COMMENT 'UTM 소스',
    utm_medium VARCHAR(100) COMMENT 'UTM 매체',
    utm_campaign VARCHAR(100) COMMENT 'UTM 캠페인',
    utm_content VARCHAR(100) COMMENT 'UTM 콘텐츠',
    utm_term VARCHAR(100) COMMENT 'UTM 키워드',
    status ENUM('신규', '처리중', '전송완료', '거부', '중복') DEFAULT '신규' COMMENT '상태',
    duplicate_of INT COMMENT '중복 원본 ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (landing_page_id) REFERENCES landing_pages(id) ON DELETE SET NULL,
    FOREIGN KEY (duplicate_of) REFERENCES leads(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_utm (utm_source, utm_medium, utm_campaign)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='리드 데이터';

-- API 엔드포인트 테이블
CREATE TABLE api_endpoints (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'API ID',
    user_id INT NOT NULL COMMENT '사용자 ID',
    name VARCHAR(100) NOT NULL COMMENT 'API명',
    url VARCHAR(255) NOT NULL COMMENT 'API URL',
    method ENUM('GET', 'POST', 'PUT', 'PATCH', 'DELETE') DEFAULT 'POST' COMMENT 'HTTP 메소드',
    headers JSON COMMENT '요청 헤더',
    auth_type ENUM('none', 'basic', 'bearer', 'api_key') DEFAULT 'none' COMMENT '인증 방식',
    auth_username VARCHAR(100) COMMENT '인증 사용자명',
    auth_password VARCHAR(255) COMMENT '인증 비밀번호',
    auth_token VARCHAR(255) COMMENT '인증 토큰',
    request_format ENUM('JSON', 'XML', 'FORM') DEFAULT 'JSON' COMMENT '요청 형식',
    is_test_mode BOOLEAN DEFAULT FALSE COMMENT '테스트 모드',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API 엔드포인트';

-- 필드 매핑 테이블
CREATE TABLE field_mappings (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '매핑 ID',
    api_endpoint_id INT NOT NULL COMMENT 'API ID',
    source_field VARCHAR(100) NOT NULL COMMENT '소스 필드명',
    target_field VARCHAR(100) NOT NULL COMMENT '대상 필드명',
    transformation_rule VARCHAR(255) COMMENT '변환 규칙',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (api_endpoint_id) REFERENCES api_endpoints(id) ON DELETE CASCADE,
    INDEX idx_api_endpoint_id (api_endpoint_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='필드 매핑';

-- 리드 전송 내역 테이블
CREATE TABLE lead_transmissions (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '전송 ID',
    lead_id INT NOT NULL COMMENT '리드 ID',
    api_endpoint_id INT NOT NULL COMMENT 'API ID',
    status ENUM('대기', '성공', '실패', '재시도') DEFAULT '대기' COMMENT '상태',
    attempts INT DEFAULT 0 COMMENT '시도 횟수',
    last_attempt TIMESTAMP NULL COMMENT '마지막 시도 시간',
    response_code INT COMMENT '응답 코드',
    response_body TEXT COMMENT '응답 본문',
    error_message TEXT COMMENT '오류 메시지',
    request_payload TEXT COMMENT '요청 페이로드',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (api_endpoint_id) REFERENCES api_endpoints(id) ON DELETE CASCADE,
    INDEX idx_lead_id (lead_id),
    INDEX idx_status (status),
    INDEX idx_last_attempt (last_attempt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='리드 전송 내역';



-- A/B 테스트 테이블
CREATE TABLE ab_tests (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '테스트 ID',
    user_id INT NOT NULL COMMENT '사용자 ID',
    campaign_id INT COMMENT '캠페인 ID',
    name VARCHAR(100) NOT NULL COMMENT '테스트명',
    status ENUM('활성', '비활성', '완료', '분석중') DEFAULT '활성' COMMENT '상태',
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '시작 일시',
    end_date TIMESTAMP NULL COMMENT '종료 일시',
    traffic_split_method ENUM('균등', '가중치', '자동최적화') DEFAULT '균등' COMMENT '트래픽 분배 방식',
    target_metric ENUM('전환율', 'CTR', '체류시간', '구매액') DEFAULT '전환율' COMMENT '목표 지표',
    confidence_level INT DEFAULT 95 COMMENT '신뢰도 수준',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_status (status),
    INDEX idx_date_range (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='A/B 테스트';

-- A/B 테스트 변형 테이블
CREATE TABLE ab_test_variants (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '변형 ID',
    ab_test_id INT NOT NULL COMMENT 'A/B 테스트 ID',
    name VARCHAR(100) NOT NULL COMMENT '변형명',
    landing_page_id INT COMMENT '랜딩페이지 ID',
    is_control BOOLEAN DEFAULT FALSE COMMENT '대조군 여부',
    traffic_percentage INT DEFAULT 50 COMMENT '트래픽 비율',
    description TEXT COMMENT '설명',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (ab_test_id) REFERENCES ab_tests(id) ON DELETE CASCADE,
    FOREIGN KEY (landing_page_id) REFERENCES landing_pages(id) ON DELETE SET NULL,
    INDEX idx_ab_test_id (ab_test_id),
    INDEX idx_is_control (is_control)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='A/B 테스트 변형';

-- A/B 테스트 데이터 테이블
CREATE TABLE ab_test_data (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '데이터 ID',
    variant_id INT NOT NULL COMMENT '변형 ID',
    visits INT DEFAULT 0 COMMENT '방문자 수',
    conversions INT DEFAULT 0 COMMENT '전환 수',
    conversion_value DECIMAL(10, 2) DEFAULT 0 COMMENT '전환 가치',
    bounce_count INT DEFAULT 0 COMMENT '이탈 수',
    average_time_seconds INT DEFAULT 0 COMMENT '평균 체류시간',
    date DATE NOT NULL COMMENT '날짜',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (variant_id) REFERENCES ab_test_variants(id) ON DELETE CASCADE,
    UNIQUE KEY uk_variant_date (variant_id, date),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='A/B 테스트 데이터';




-- 푸시 토큰 테이블
CREATE TABLE push_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '토큰 ID',
    token VARCHAR(255) NOT NULL UNIQUE COMMENT '브라우저 푸시 토큰',
    user_agent TEXT COMMENT '브라우저/기기 정보',
    domain VARCHAR(255) NOT NULL COMMENT '구독 도메인',
    landing_page_id INT COMMENT '랜딩페이지 ID',
    campaign_id INT COMMENT '캠페인 ID',
    ip_address VARCHAR(45) COMMENT 'IP 주소',
    device_type ENUM('desktop', 'mobile', 'tablet', 'other') DEFAULT 'other' COMMENT '기기 유형',
    browser VARCHAR(100) COMMENT '브라우저',
    os VARCHAR(100) COMMENT '운영체제',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '마지막 활동 일시',
    is_active BOOLEAN DEFAULT TRUE COMMENT '활성 상태',
    FOREIGN KEY (landing_page_id) REFERENCES landing_pages(id) ON DELETE SET NULL,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    INDEX idx_domain (domain),
    INDEX idx_is_active (is_active),
    INDEX idx_device_type (device_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='푸시 알림 토큰';

-- 푸시 캠페인 테이블
CREATE TABLE push_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '푸시 캠페인 ID',
    user_id INT NOT NULL COMMENT '사용자 ID',
    campaign_id INT COMMENT '연결된 마케팅 캠페인 ID',
    name VARCHAR(100) NOT NULL COMMENT '푸시 캠페인명',
    title VARCHAR(100) NOT NULL COMMENT '알림 제목',
    message TEXT NOT NULL COMMENT '알림 메시지',
    image_url VARCHAR(255) COMMENT '알림 이미지 URL',
    deep_link VARCHAR(255) COMMENT '클릭 시 이동 URL',
    schedule_time TIMESTAMP NULL COMMENT '예약 발송 시간',
    sent_time TIMESTAMP NULL COMMENT '실제 발송 시간',
    status ENUM('초안', '예약됨', '발송중', '완료', '취소됨') DEFAULT '초안' COMMENT '상태',
    custom_data JSON COMMENT '추가 데이터',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_schedule_time (schedule_time),
    INDEX idx_sent_time (sent_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='푸시 알림 캠페인';

-- 푸시 캠페인 세그먼트 테이블
CREATE TABLE push_campaign_segments (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '세그먼트 ID',
    push_campaign_id INT NOT NULL COMMENT '푸시 캠페인 ID',
    domain VARCHAR(255) COMMENT '타겟 도메인',
    landing_page_id INT COMMENT '타겟 랜딩페이지 ID',
    campaign_id INT COMMENT '타겟 마케팅 캠페인 ID',
    device_type VARCHAR(50) COMMENT '타겟 기기 유형',
    browser VARCHAR(50) COMMENT '타겟 브라우저',
    os VARCHAR(50) COMMENT '타겟 운영체제',
    country VARCHAR(50) COMMENT '타겟 국가',
    region VARCHAR(100) COMMENT '타겟 지역',
    city VARCHAR(100) COMMENT '타겟 도시',
    created_within_days INT COMMENT '생성 기간(일)',
    custom_conditions JSON COMMENT '사용자 정의 조건',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    FOREIGN KEY (push_campaign_id) REFERENCES push_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (landing_page_id) REFERENCES landing_pages(id) ON DELETE SET NULL,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    INDEX idx_push_campaign_id (push_campaign_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='푸시 캠페인 세그먼트';

-- 푸시 발송 내역 테이블
CREATE TABLE push_deliveries (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '발송 ID',
    push_campaign_id INT NOT NULL COMMENT '푸시 캠페인 ID',
    token_id INT NOT NULL COMMENT '토큰 ID',
    sent_at TIMESTAMP NULL COMMENT '발송 시간',
    opened_at TIMESTAMP NULL COMMENT '열람 시간',
    clicked_at TIMESTAMP NULL COMMENT '클릭 시간',
    status ENUM('대기', '발송됨', '실패', '열람', '클릭') DEFAULT '대기' COMMENT '상태',
    error_message TEXT COMMENT '오류 메시지',
    device_info JSON COMMENT '기기 정보',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    FOREIGN KEY (push_campaign_id) REFERENCES push_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (token_id) REFERENCES push_tokens(id) ON DELETE CASCADE,
    INDEX idx_push_campaign_id (push_campaign_id),
    INDEX idx_token_id (token_id),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='푸시 알림 발송 내역';

-- 푸시 템플릿 테이블
CREATE TABLE push_templates (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '템플릿 ID',
    user_id INT NOT NULL COMMENT '사용자 ID',
    name VARCHAR(100) NOT NULL COMMENT '템플릿명',
    title VARCHAR(100) NOT NULL COMMENT '제목',
    message TEXT NOT NULL COMMENT '메시지',
    image_url VARCHAR(255) COMMENT '이미지 URL',
    deep_link_pattern VARCHAR(255) COMMENT 'URL 패턴',
    variables JSON COMMENT '변수 정의',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='푸시 알림 템플릿';




-- 도메인 테이블
CREATE TABLE `domains` (
  `id` int(11) NOT NULL COMMENT '도메인 고유 식별자',
  `user_id` int(11) NOT NULL COMMENT '사용자 ID',
  `domain` varchar(255) NOT NULL COMMENT '도메인 주소',
  `is_default` tinyint(1) DEFAULT 0 COMMENT '기본 도메인 여부',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT '생성 시간',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정 시간'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='사용자 도메인 관리 테이블';








-- 푸시 성과 분석 뷰
CREATE OR REPLACE VIEW v_push_campaign_performance AS
SELECT 
    pc.id AS push_campaign_id,
    pc.name AS push_campaign_name,
    pc.title AS push_title,
    c.id AS campaign_id,
    c.name AS campaign_name,
    COUNT(pd.id) AS total_sent,
    COUNT(CASE WHEN pd.status = '발송됨' THEN 1 END) AS delivered,
    COUNT(CASE WHEN pd.status = '실패' THEN 1 END) AS failed,
    COUNT(CASE WHEN pd.status = '열람' OR pd.status = '클릭' THEN 1 END) AS opened,
    COUNT(CASE WHEN pd.status = '클릭' THEN 1 END) AS clicked,
    CASE 
        WHEN COUNT(pd.id) > 0 THEN COUNT(CASE WHEN pd.status = '발송됨' THEN 1 END) / COUNT(pd.id) * 100 
        ELSE 0 
    END AS delivery_rate,
    CASE 
        WHEN COUNT(CASE WHEN pd.status = '발송됨' THEN 1 END) > 0 THEN COUNT(CASE WHEN pd.status = '열람' OR pd.status = '클릭' THEN 1 END) / COUNT(CASE WHEN pd.status = '발송됨' THEN 1 END) * 100 
        ELSE 0 
    END AS open_rate,
    CASE 
        WHEN COUNT(CASE WHEN pd.status = '열람' OR pd.status = '클릭' THEN 1 END) > 0 THEN COUNT(CASE WHEN pd.status = '클릭' THEN 1 END) / COUNT(CASE WHEN pd.status = '열람' OR pd.status = '클릭' THEN 1 END) * 100 
        ELSE 0 
    END AS click_rate,
    COUNT(DISTINCT cel.id) AS conversions,
    CASE 
        WHEN COUNT(CASE WHEN pd.status = '클릭' THEN 1 END) > 0 THEN COUNT(DISTINCT cel.id) / COUNT(CASE WHEN pd.status = '클릭' THEN 1 END) * 100 
        ELSE 0 
    END AS conversion_rate
FROM 
    push_campaigns pc
LEFT JOIN 
    campaigns c ON pc.campaign_id = c.id
LEFT JOIN 
    push_deliveries pd ON pc.id = pd.push_campaign_id
LEFT JOIN 
    push_tokens pt ON pd.token_id = pt.id
LEFT JOIN 
    conversion_events_log cel ON 
        pc.campaign_id = cel.campaign_id AND 
        DATE(pd.clicked_at) = DATE(cel.event_time) AND
        pt.device_type = cel.device_type
GROUP BY 
    pc.id, pc.name, pc.title, c.id, c.name;



-- 1. 캠페인별 일일 성과 요약 뷰
CREATE OR REPLACE VIEW v_campaign_daily_performance AS
SELECT 
    c.id AS campaign_id,
    c.name AS campaign_name,
    c.status AS campaign_status,
    ac.cost_date AS date,
    SUM(ac.impressions) AS impressions,
    SUM(ac.clicks) AS clicks,
    SUM(ac.ad_cost) AS cost,
    SUM(ac.conversions) AS conversions,
    SUM(ac.conversion_value) AS conversion_value,
    CASE 
        WHEN SUM(ac.impressions) > 0 THEN (SUM(ac.clicks) / SUM(ac.impressions)) * 100 
        ELSE 0 
    END AS ctr,
    CASE 
        WHEN SUM(ac.clicks) > 0 THEN SUM(ac.ad_cost) / SUM(ac.clicks)
        ELSE 0 
    END AS cpc,
    CASE 
        WHEN SUM(ac.conversions) > 0 THEN SUM(ac.ad_cost) / SUM(ac.conversions)
        ELSE 0 
    END AS cpa,
    CASE 
        WHEN SUM(ac.ad_cost) > 0 THEN (SUM(ac.conversion_value) / SUM(ac.ad_cost)) * 100
        ELSE 0 
    END AS roas
FROM 
    campaigns c
LEFT JOIN 
    ad_costs ac ON c.id = ac.campaign_id
GROUP BY 
    c.id, c.name, c.status, ac.cost_date;

-- 2. 소재별 성과 요약 뷰
CREATE OR REPLACE VIEW v_ad_material_performance AS
SELECT 
    am.id AS ad_material_id,
    am.ad_name AS ad_name,
    am.ad_type AS ad_type,
    am.headline AS headline,
    c.id AS campaign_id,
    c.name AS campaign_name,
    p.id AS platform_id,
    p.name AS platform_name,
    SUM(ac.impressions) AS impressions,
    SUM(ac.clicks) AS clicks,
    SUM(ac.ad_cost) AS cost,
    SUM(ac.conversions) AS conversions,
    SUM(ac.conversion_value) AS conversion_value,
    CASE 
        WHEN SUM(ac.impressions) > 0 THEN (SUM(ac.clicks) / SUM(ac.impressions)) * 100 
        ELSE 0 
    END AS ctr,
    CASE 
        WHEN SUM(ac.clicks) > 0 THEN SUM(ac.ad_cost) / SUM(ac.clicks)
        ELSE 0 
    END AS cpc,
    CASE 
        WHEN SUM(ac.conversions) > 0 THEN SUM(ac.ad_cost) / SUM(ac.conversions)
        ELSE 0 
    END AS cpa,
    CASE 
        WHEN SUM(ac.ad_cost) > 0 THEN (SUM(ac.conversion_value) / SUM(ac.ad_cost)) * 100
        ELSE 0 
    END AS roas
FROM 
    ad_materials am
JOIN 
    campaigns c ON am.campaign_id = c.id
JOIN 
    user_ad_accounts uaa ON am.user_ad_account_id = uaa.id
JOIN 
    ad_platforms p ON uaa.platform_id = p.id
LEFT JOIN 
    ad_costs ac ON am.id = ac.ad_material_id
GROUP BY 
    am.id, am.ad_name, am.ad_type, am.headline, c.id, c.name, p.id, p.name;

-- 3. 플랫폼별 성과 요약 뷰
CREATE OR REPLACE VIEW v_platform_performance AS
SELECT 
    p.id AS platform_id,
    p.name AS platform_name,
    c.id AS campaign_id,
    c.name AS campaign_name,
    SUM(ac.impressions) AS impressions,
    SUM(ac.clicks) AS clicks,
    SUM(ac.ad_cost) AS cost,
    SUM(ac.conversions) AS conversions,
    SUM(ac.conversion_value) AS conversion_value,
    CASE 
        WHEN SUM(ac.impressions) > 0 THEN (SUM(ac.clicks) / SUM(ac.impressions)) * 100 
        ELSE 0 
    END AS ctr,
    CASE 
        WHEN SUM(ac.clicks) > 0 THEN SUM(ac.ad_cost) / SUM(ac.clicks)
        ELSE 0 
    END AS cpc,
    CASE 
        WHEN SUM(ac.conversions) > 0 THEN SUM(ac.ad_cost) / SUM(ac.conversions)
        ELSE 0 
    END AS cpa,
    CASE 
        WHEN SUM(ac.ad_cost) > 0 THEN (SUM(ac.conversion_value) / SUM(ac.ad_cost)) * 100
        ELSE 0 
    END AS roas
FROM 
    ad_platforms p
JOIN 
    user_ad_accounts uaa ON p.id = uaa.platform_id
JOIN 
    ad_costs ac ON uaa.id = ac.user_ad_account_id
JOIN 
    campaigns c ON ac.campaign_id = c.id
GROUP BY 
    p.id, p.name, c.id, c.name;

-- 4. 기간별 전환 추이 뷰
CREATE OR REPLACE VIEW v_conversion_trends AS
SELECT 
    c.id AS campaign_id,
    c.name AS campaign_name,
    DATE(cel.event_time) AS conversion_date,
    ce.id AS event_id,
    ce.name AS event_name,
    COUNT(cel.id) AS conversion_count,
    SUM(cel.conversion_value) AS conversion_value
FROM 
    conversion_events_log cel
JOIN 
    campaigns c ON cel.campaign_id = c.id
JOIN 
    conversion_events ce ON cel.event_id = ce.id
GROUP BY 
    c.id, c.name, DATE(cel.event_time), ce.id, ce.name
ORDER BY 
    conversion_date DESC;

-- 5. 디바이스별 성과 요약 뷰
CREATE OR REPLACE VIEW v_device_performance AS
SELECT 
    c.id AS campaign_id,
    c.name AS campaign_name,
    uc.device_type,
    COUNT(uc.id) AS click_count,
    COUNT(DISTINCT cel.id) AS conversion_count,
    SUM(uc.ad_cost) AS cost,
    SUM(cel.conversion_value) AS conversion_value,
    CASE 
        WHEN COUNT(uc.id) > 0 THEN COUNT(DISTINCT cel.id) / COUNT(uc.id) * 100
        ELSE 0 
    END AS conversion_rate,
    CASE 
        WHEN COUNT(DISTINCT cel.id) > 0 THEN SUM(uc.ad_cost) / COUNT(DISTINCT cel.id)
        ELSE 0 
    END AS cpa,
    CASE 
        WHEN SUM(uc.ad_cost) > 0 THEN (SUM(cel.conversion_value) / SUM(uc.ad_cost)) * 100
        ELSE 0 
    END AS roas
FROM 
    campaigns c
JOIN 
    url_clicks uc ON c.id = uc.campaign_id
LEFT JOIN 
    conversion_events_log cel ON uc.campaign_id = cel.campaign_id 
        AND uc.device_type = cel.device_type
        AND DATE(uc.click_time) = DATE(cel.event_time)
GROUP BY 
    c.id, c.name, uc.device_type;

-- 6. 캠페인 ROI 계산 뷰
CREATE OR REPLACE VIEW v_campaign_roi AS
SELECT 
    c.id AS campaign_id,
    c.name AS campaign_name,
    c.status AS campaign_status,
    c.start_date,
    c.end_date,
    SUM(ac.ad_cost) AS total_cost,
    SUM(ac.clicks) AS total_clicks,
    SUM(ac.impressions) AS total_impressions,
    SUM(ac.conversions) AS total_conversions,
    SUM(ac.conversion_value) AS total_conversion_value,
    CASE 
        WHEN SUM(ac.ad_cost) > 0 THEN ((SUM(ac.conversion_value) - SUM(ac.ad_cost)) / SUM(ac.ad_cost)) * 100
        ELSE 0 
    END AS roi,
    CASE 
        WHEN SUM(ac.ad_cost) > 0 THEN (SUM(ac.conversion_value) / SUM(ac.ad_cost)) * 100
        ELSE 0 
    END AS roas,
    CASE 
        WHEN SUM(ac.clicks) > 0 THEN SUM(ac.ad_cost) / SUM(ac.clicks)
        ELSE 0 
    END AS cpc,
    CASE 
        WHEN SUM(ac.conversions) > 0 THEN SUM(ac.ad_cost) / SUM(ac.conversions)
        ELSE 0 
    END AS cpa
FROM 
    campaigns c
LEFT JOIN 
    ad_costs ac ON c.id = ac.campaign_id
GROUP BY 
    c.id, c.name, c.status, c.start_date, c.end_date;





-- 랜딩페이지 성과 요약 뷰
CREATE OR REPLACE VIEW v_landing_page_performance AS
SELECT 
    lp.id AS landing_page_id,
    lp.name AS landing_page_name,
    lp.status AS landing_page_status,
    c.id AS campaign_id,
    c.name AS campaign_name,
    COUNT(DISTINCT lpv.id) AS visits,
    COUNT(DISTINCT cel.id) AS conversions,
    SUM(cel.conversion_value) AS conversion_value,
    CASE 
        WHEN COUNT(DISTINCT lpv.id) > 0 THEN COUNT(DISTINCT cel.id) / COUNT(DISTINCT lpv.id) * 100 
        ELSE 0 
    END AS conversion_rate,
    CASE 
        WHEN COUNT(DISTINCT cel.id) > 0 THEN SUM(cel.conversion_value) / COUNT(DISTINCT cel.id) 
        ELSE 0 
    END AS avg_conversion_value
FROM 
    landing_pages lp
LEFT JOIN 
    campaigns c ON lp.campaign_id = c.id
LEFT JOIN 
    landing_page_visits lpv ON lp.id = lpv.landing_page_id
LEFT JOIN 
    conversion_events_log cel ON lp.id = cel.landing_page_id AND DATE(lpv.visit_time) = DATE(cel.event_time)
GROUP BY 
    lp.id, lp.name, lp.status, c.id, c.name;


    

-- 폼 전환율 분석 뷰
CREATE OR REPLACE VIEW v_form_conversion_analysis AS
SELECT 
    f.id AS form_id,
    f.name AS form_name,
    lp.id AS landing_page_id,
    lp.name AS landing_page_name,
    c.id AS campaign_id,
    c.name AS campaign_name,
    COUNT(l.id) AS lead_count,
    COUNT(DISTINCT lpv.id) AS form_views,
    CASE 
        WHEN COUNT(DISTINCT lpv.id) > 0 THEN COUNT(l.id) / COUNT(DISTINCT lpv.id) * 100 
        ELSE 0 
    END AS form_conversion_rate,
    AVG(TIMESTAMPDIFF(SECOND, lpv.visit_time, l.created_at)) AS avg_completion_time_seconds
FROM 
    forms f
JOIN 
    landing_pages lp ON f.landing_page_id = lp.id
LEFT JOIN 
    campaigns c ON lp.campaign_id = c.id
LEFT JOIN 
    landing_page_visits lpv ON lp.id = lpv.landing_page_id
LEFT JOIN 
    leads l ON f.id = l.form_id
GROUP BY 
    f.id, f.name, lp.id, lp.name, c.id, c.name;