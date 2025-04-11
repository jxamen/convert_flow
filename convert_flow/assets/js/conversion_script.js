/**
 * 전환 추적 스크립트
 * 랜딩페이지에 삽입되어 전환 이벤트를 추적하고 광고 플랫폼에 전송
 */

// 전환 추적 객체 초기화
window.conversionTracker = window.conversionTracker || {};
window.conversionTracker.events = window.conversionTracker.events || {};
window.conversionTracker.campaignId =
  window.conversionTracker.campaignId || null;
window.conversionTracker.landingId = window.conversionTracker.landingId || null;

// 주요 광고 플랫폼 API 객체
window.conversionTracker.platforms = {
  // 구글 애널리틱스
  google_analytics: {
    isAvailable: function () {
      return typeof gtag === "function";
    },
    trackEvent: function (eventName, params) {
      if (this.isAvailable()) {
        gtag("event", eventName, params || {});
        return true;
      }
      return false;
    },
  },

  // 페이스북 픽셀
  facebook_pixel: {
    isAvailable: function () {
      return typeof fbq === "function";
    },
    trackEvent: function (eventName, params) {
      if (this.isAvailable()) {
        fbq("track", eventName, params || {});
        return true;
      }
      return false;
    },
  },

  // 구글 태그 매니저
  google_gtm: {
    isAvailable: function () {
      return typeof dataLayer !== "undefined" && Array.isArray(dataLayer);
    },
    trackEvent: function (eventName, params) {
      if (this.isAvailable()) {
        dataLayer.push({
          event: eventName,
          eventParams: params || {},
        });
        return true;
      }
      return false;
    },
  },

  // 네이버 광고
  naver: {
    isAvailable: function () {
      return typeof wcs === "object" && typeof wcs.inflow === "function";
    },
    trackEvent: function (eventName, params) {
      if (this.isAvailable()) {
        var _nasa = {};
        if (eventName === "purchase") {
          _nasa["cnv"] = wcs.cnv("1", params?.value || "1");
        } else if (eventName === "form_submit" || eventName === "lead") {
          _nasa["cnv"] = wcs.cnv("2", "1");
        } else if (eventName === "sign_up") {
          _nasa["cnv"] = wcs.cnv("3", "1");
        } else if (eventName === "add_to_cart") {
          _nasa["cnv"] = wcs.cnv("4", "1");
        } else {
          _nasa["cnv"] = wcs.cnv("5", "1");
        }
        wcs_do(_nasa);
        return true;
      }
      return false;
    },
  },

  // 카카오 픽셀
  kakao: {
    isAvailable: function () {
      return typeof kakaoPixel === "function";
    },
    trackEvent: function (eventName, params, pixelId) {
      if (this.isAvailable() && pixelId) {
        try {
          if (eventName === "purchase") {
            kakaoPixel(pixelId).purchase(params);
          } else if (eventName === "form_submit" || eventName === "lead") {
            kakaoPixel(pixelId).completeRegistration(params);
          } else if (eventName === "sign_up") {
            kakaoPixel(pixelId).completeRegistration(params);
          } else if (eventName === "add_to_cart") {
            kakaoPixel(pixelId).addToCart(params);
          } else if (eventName === "view_content") {
            kakaoPixel(pixelId).viewContent(params);
          } else {
            kakaoPixel(pixelId).pageView();
          }
          return true;
        } catch (e) {
          console.error("Kakao pixel error:", e);
          return false;
        }
      }
      return false;
    },
  },
};

// 이벤트 매핑 (범용 이벤트 코드 -> 플랫폼별 이벤트 코드)
window.conversionTracker.eventMap = {
  page_view: {
    google_analytics: "page_view",
    facebook_pixel: "PageView",
    google_gtm: "page_view",
    naver: "page_view",
    kakao: "pageView",
  },
  form_submit: {
    google_analytics: "generate_lead",
    facebook_pixel: "Lead",
    google_gtm: "form_submit",
    naver: "form_submit",
    kakao: "completeRegistration",
  },
  button_click: {
    google_analytics: "select_content",
    facebook_pixel: "Lead",
    google_gtm: "button_click",
    naver: "button_click",
    kakao: "participation",
  },
  scroll_depth: {
    google_analytics: "scroll",
    facebook_pixel: "ViewContent",
    google_gtm: "scroll",
    naver: "scroll",
    kakao: "viewContent",
  },
  time_on_page: {
    google_analytics: "engagement",
    facebook_pixel: "ViewContent",
    google_gtm: "time_on_page",
    naver: "time_on_page",
    kakao: "viewContent",
  },
  phone_call: {
    google_analytics: "phone_call",
    facebook_pixel: "Contact",
    google_gtm: "phone_call",
    naver: "phone_call",
    kakao: "participation",
  },
  purchase: {
    google_analytics: "purchase",
    facebook_pixel: "Purchase",
    google_gtm: "purchase",
    naver: "purchase",
    kakao: "purchase",
  },
};

// 이벤트 트래킹 함수
window.conversionTracker.trackEvent = function (eventCode, params) {
  // 콘솔에 이벤트 로깅
  console.log("Conversion event tracked:", eventCode, params);

  // 이벤트 매핑 가져오기
  var eventMapping = this.eventMap[eventCode];
  if (!eventMapping) {
    console.warn("Unknown event code:", eventCode);
    return false;
  }

  // 각 지원 플랫폼에 이벤트 전송
  for (var platform in this.platforms) {
    if (this.platforms.hasOwnProperty(platform) && eventMapping[platform]) {
      var platformEvent = eventMapping[platform];
      var result = this.platforms[platform].trackEvent(platformEvent, params);

      if (result) {
        console.log("Event sent to " + platform + ":", platformEvent);
      }
    }
  }

  // 서버에 이벤트 로깅 (옵션)
  if (this.campaignId || this.landingId) {
    this.logEvent(eventCode, params);
  }

  return true;
};

// 서버에 이벤트 로깅 (통계용)
window.conversionTracker.logEvent = function (eventCode, params) {
  var data = {
    event_code: eventCode,
    campaign_id: this.campaignId,
    landing_id: this.landingId,
    params: JSON.stringify(params || {}),
    url: window.location.href,
    referrer: document.referrer,
    user_agent: navigator.userAgent,
  };

  // 비동기 로깅 (결과를 기다리지 않음)
  var xhr = new XMLHttpRequest();
  xhr.open("POST", "/convert_flow/api/log_event.php", true);
  xhr.setRequestHeader("Content-Type", "application/json");
  xhr.send(JSON.stringify(data));
};

// 페이지 로드 시 페이지뷰 이벤트 자동 전송
window.addEventListener("load", function () {
  window.conversionTracker.trackEvent("page_view", {
    page_location: window.location.href,
    page_title: document.title,
  });

  // 폼 제출 이벤트 리스너 등록
  document.querySelectorAll("form").forEach(function (form) {
    form.addEventListener("submit", function (e) {
      window.conversionTracker.trackEvent("form_submit", {
        form_id: this.id || this.getAttribute("name") || "unknown",
        form_class: this.className,
      });
    });
  });

  // 전화번호 링크 클릭 이벤트 리스너 등록
  document.querySelectorAll('a[href^="tel:"]').forEach(function (link) {
    link.addEventListener("click", function (e) {
      window.conversionTracker.trackEvent("phone_call", {
        phone_number: this.getAttribute("href").replace("tel:", ""),
      });
    });
  });

  // 스크롤 깊이 트래킹
  var scrollDepthTracked = {};
  window.addEventListener("scroll", function () {
    var scrollDepth = Math.round(
      (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100
    );
    var depthPoints = [25, 50, 75, 100];

    for (var i = 0; i < depthPoints.length; i++) {
      var depth = depthPoints[i];
      if (scrollDepth >= depth && !scrollDepthTracked[depth]) {
        scrollDepthTracked[depth] = true;
        window.conversionTracker.trackEvent("scroll_depth", {
          scroll_depth: depth,
        });
      }
    }
  });

  // 체류 시간 트래킹
  var timeIntervals = [30, 60, 120, 300]; // 30초, 1분, 2분, 5분
  var timeTracked = {};

  for (var i = 0; i < timeIntervals.length; i++) {
    (function (interval) {
      setTimeout(function () {
        if (!timeTracked[interval]) {
          timeTracked[interval] = true;
          window.conversionTracker.trackEvent("time_on_page", {
            time_on_page: interval,
          });
        }
      }, interval * 1000);
    })(timeIntervals[i]);
  }
});

// 커스텀 트랙킹 기능 (개발자용)
window.trackConversion = function (eventCode, params) {
  return window.conversionTracker.trackEvent(eventCode, params);
};
