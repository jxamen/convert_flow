/**
 * ConvertFlow 전환 추적 스크립트
 * 
 * 이 스크립트는 웹사이트에서 전환 이벤트를 추적하고 기록하는 기능을 담당합니다.
 * URL 매개변수:
 *   - campaign: 캠페인 해시
 *   - type: 전환 유형 (구매완료, 회원가입, 다운로드, 문의하기 등)
 * 
 * 사용법:
 * 1. 스크립트 로드: <script src="https://도메인/api/conversion.js?campaign=HASH&type=TYPE"></script>
 * 2. 전환 추적: ConvertFlow.trackConversion({ value: 10000, ... });
 */

(function(window) {
    'use strict';
    
    // 기본 설정
    var ConvertFlow = {
        version: '1.0.0',
        endpoint: '/api/conversion_event.php',
        campaign: null,
        type: null,
        initialized: false,
        debug: false
    };
    
    /**
     * 로그 출력 함수
     */
    ConvertFlow.log = function(message, level) {
        if (!this.debug) return;
        
        level = level || 'log';
        
        if (typeof console !== 'undefined' && console[level]) {
            console[level]('[ConvertFlow] ' + message);
        }
    };
    
    /**
     * URL 매개변수에서 값 추출
     */
    ConvertFlow.getUrlParam = function(name) {
        var scriptTags = document.getElementsByTagName('script');
        
        for (var i = 0; i < scriptTags.length; i++) {
            var src = scriptTags[i].src;
            
            if (src && src.indexOf('conversion.js') !== -1) {
                var regex = new RegExp('[?&]' + name + '=([^&#]*)');
                var results = regex.exec(src);
                
                if (results) {
                    return decodeURIComponent(results[1].replace(/\+/g, ' '));
                }
            }
        }
        
        return null;
    };
    
    /**
     * 초기화 함수
     */
    ConvertFlow.init = function() {
        // URL 매개변수에서 캠페인과 전환 유형 가져오기
        this.campaign = this.getUrlParam('campaign');
        this.type = this.getUrlParam('type');
        this.debug = this.getUrlParam('debug') === 'true';
        
        // 시작 로그
        this.log('Initializing v' + this.version);
        
        // 매개변수 체크
        if (!this.campaign) {
            this.log('Missing campaign parameter', 'error');
            return;
        }
        
        this.log('Campaign: ' + this.campaign);
        this.log('Type: ' + (this.type || 'default'));
        
        this.initialized = true;
        this.trackPageview();
    };
    
    /**
     * 페이지뷰 추적
     */
    ConvertFlow.trackPageview = function() {
        if (!this.initialized) {
            this.log('Not initialized. Call init() first', 'warn');
            return;
        }
        
        var data = {
            action: 'pageview',
            campaign: this.campaign,
            type: this.type,
            url: window.location.href,
            referrer: document.referrer
        };
        
        this.sendRequest(data);
    };
    
    /**
     * 전환 추적
     */
    ConvertFlow.trackConversion = function(data) {
        if (!this.initialized) {
            this.log('Not initialized. Call init() first', 'warn');
            return;
        }
        
        data = data || {};
        data.action = 'conversion';
        data.campaign = this.campaign;
        data.type = this.type;
        data.url = window.location.href;
        
        this.log('Tracking conversion', data);
        this.sendRequest(data);
    };
    
    /**
     * 서버 요청 전송
     */
    ConvertFlow.sendRequest = function(data) {
        var xhr = new XMLHttpRequest();
        var params = [];
        
        // URL 매개변수 구성
        for (var key in data) {
            if (data.hasOwnProperty(key) && data[key] !== null) {
                params.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
            }
        }
        
        // 쿠키에서 utm 매개변수 추가
        var utmParams = this.getUtmParams();
        for (var param in utmParams) {
            if (utmParams.hasOwnProperty(param)) {
                params.push(encodeURIComponent(param) + '=' + encodeURIComponent(utmParams[param]));
            }
        }
        
        // 요청 전송
        xhr.open('POST', this.endpoint, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    ConvertFlow.log('Request successful', 'info');
                    try {
                        var response = JSON.parse(xhr.responseText);
                        ConvertFlow.log(response);
                    } catch (e) {
                        ConvertFlow.log('Invalid JSON response', 'error');
                    }
                } else {
                    ConvertFlow.log('Request failed: ' + xhr.status, 'error');
                }
            }
        };
        
        xhr.send(params.join('&'));
    };
    
    /**
     * URL이나 쿠키에서 UTM 매개변수 추출
     */
    ConvertFlow.getUtmParams = function() {
        var params = {};
        var utm_params = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
        
        // URL에서 추출
        var url = window.location.search.substring(1);
        var urlParams = url.split('&');
        
        for (var i = 0; i < urlParams.length; i++) {
            var param = urlParams[i].split('=');
            var name = decodeURIComponent(param[0]);
            
            if (utm_params.indexOf(name) !== -1) {
                var value = param.length > 1 ? decodeURIComponent(param[1]) : '';
                params[name] = value;
                
                // UTM 매개변수를 쿠키에 저장
                this.setCookie(name, value, 30);
            }
        }
        
        // 쿠키에서 추출 (URL에 없는 경우)
        for (var j = 0; j < utm_params.length; j++) {
            var utmParam = utm_params[j];
            
            if (!params[utmParam]) {
                var cookieValue = this.getCookie(utmParam);
                
                if (cookieValue) {
                    params[utmParam] = cookieValue;
                }
            }
        }
        
        return params;
    };
    
    /**
     * 쿠키 설정
     */
    ConvertFlow.setCookie = function(name, value, days) {
        var expires = '';
        
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        
        document.cookie = name + '=' + value + expires + '; path=/';
    };
    
    /**
     * 쿠키 값 가져오기
     */
    ConvertFlow.getCookie = function(name) {
        var nameEQ = name + '=';
        var ca = document.cookie.split(';');
        
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            
            while (c.charAt(0) === ' ') {
                c = c.substring(1, c.length);
            }
            
            if (c.indexOf(nameEQ) === 0) {
                return c.substring(nameEQ.length, c.length);
            }
        }
        
        return null;
    };
    
    /**
     * 세션 ID 생성
     */
    ConvertFlow.generateSessionId = function() {
        var d = new Date().getTime();
        var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = (d + Math.random() * 16) % 16 | 0;
            d = Math.floor(d / 16);
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
        return uuid;
    };
    
    /**
     * 세션 ID 관리
     */
    ConvertFlow.getSessionId = function() {
        var sessionId = this.getCookie('cf_session');
        
        if (!sessionId) {
            sessionId = this.generateSessionId();
            this.setCookie('cf_session', sessionId, 1); // 1일 유효
        }
        
        return sessionId;
    };
    
    // 전역 변수로 노출
    window.ConvertFlow = ConvertFlow;
    
    // 스크립트 로드 시 자동 초기화
    ConvertFlow.init();
    
})(window);
