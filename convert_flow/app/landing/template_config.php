<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 템플릿 유형 옵션
$cflow_template_types = array(
    'landing_page' => '랜딩페이지',
    'ad_content' => '광고 콘텐츠',
    'campaign' => '캠페인 설정',
    'form' => '신청 폼',
    'conversion' => '전환 스크립트'
);

// 산업군 옵션
$cflow_template_industries = array(
    '핸드폰' => '핸드폰',
    '인터넷가입' => '인터넷가입',
    '금융' => '금융',
    '교육' => '교육',
    '건강' => '건강',
    '소매' => '소매',
    '여행' => '여행',
    '기타' => '기타'
);

// 타겟 지역 추천 옵션
$cflow_template_regions = array(
    '서울',
    '경기',
    '인천',
    '부산',
    '대구',
    '광주',
    '대전',
    '울산',
    '세종',
    '강원',
    '충북',
    '충남',
    '전북',
    '전남',
    '경북',
    '경남',
    '제주',
    '전국'
);

// 템플릿 유형별 기본 내용
$cflow_template_default_contents = array(
    'landing_page' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>랜딩페이지 템플릿</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 40px 0; }
        .cta-button { display: inline-block; background-color: #4CAF50; color: white; padding: 15px 30px; text-decoration: none; font-size: 18px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>주목할 만한 제목</h1>
            <p>고객의 관심을 끌 수 있는 부제목을 작성하세요</p>
            <a href="#contact" class="cta-button">지금 신청하기</a>
        </div>
    </div>
</body>
</html>',
    'ad_content' => '{
    "title": "효과적인 광고 제목을 입력하세요",
    "description": "광고에 대한 설명을 입력하세요. 간결하면서도 호기심을 자극하는 문구가 좋습니다.",
    "cta": "지금 확인하기",
    "imageUrl": "https://example.com/image.jpg"
}',
    'campaign' => '{
    "name": "캠페인 이름",
    "startDate": "2023-01-01",
    "endDate": "2023-12-31",
    "dailyBudget": 50000,
    "targetAudience": {
        "age": [25, 45],
        "gender": "all",
        "interests": ["여행", "쇼핑"]
    },
    "adPlatforms": ["Google", "Facebook", "Naver"]
}',
    'form' => '<form id="contact-form">
    <div class="form-group">
        <label for="name">이름</label>
        <input type="text" id="name" name="name" required>
    </div>
    <div class="form-group">
        <label for="email">이메일</label>
        <input type="email" id="email" name="email" required>
    </div>
    <div class="form-group">
        <label for="phone">연락처</label>
        <input type="tel" id="phone" name="phone">
    </div>
    <div class="form-group">
        <label for="message">메시지</label>
        <textarea id="message" name="message" rows="4"></textarea>
    </div>
    <button type="submit">제출하기</button>
</form>',
    'conversion' => '<script>
// 전환 추적 스크립트
function trackConversion(eventName, eventValue) {
    // 전환 이벤트 기록
    console.log("Conversion tracked:", eventName, eventValue);
    
    // 여기에 실제 전환 추적 코드를 추가하세요
    // 예: Google Analytics, Facebook Pixel 등
    
    // Google Analytics 예시
    if (typeof gtag === "function") {
        gtag("event", eventName, {
            "value": eventValue
        });
    }
    
    // Facebook Pixel 예시
    if (typeof fbq === "function") {
        fbq("track", eventName, {
            value: eventValue,
            currency: "KRW"
        });
    }
}

// 구매 완료 이벤트 예시
document.addEventListener("DOMContentLoaded", function() {
    const purchaseButton = document.querySelector(".purchase-button");
    if (purchaseButton) {
        purchaseButton.addEventListener("click", function() {
            trackConversion("purchase", 50000);
        });
    }
});
</script>'
);
