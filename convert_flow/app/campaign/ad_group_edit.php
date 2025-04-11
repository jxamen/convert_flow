<?php
/**
 * 광고 그룹 편집 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 로그인 체크
if (!$is_member) {
    alert('로그인이 필요한 서비스입니다.', CF_URL . '/login.php');
}

// 파라미터 체크
$ad_group_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($ad_group_id <= 0) {
    alert('잘못된 접근입니다.');
}

// 캠페인 모델 로드
include_once CF_MODEL_PATH . '/campaign/campaign.model.php';
$campaign_model = new CampaignModel();

// 광고 그룹 모델 로드
include_once CF_MODEL_PATH . '/campaign/ad_group.model.php';
$ad_group_model = new AdGroupModel();

// 광고 그룹 정보 조회
$ad_group = $ad_group_model->get_ad_group($ad_group_id);
if (!$ad_group) {
    alert('존재하지 않는 광고 그룹입니다.');
}

// 캠페인 정보 조회 (권한 체크용)
$campaign = $campaign_model->get_campaign($ad_group['campaign_id'], $member['id']);
if (!$campaign) {
    alert('권한이 없는 광고 그룹입니다.');
}

// 타겟팅 데이터 파싱
$targeting_data = json_decode($ad_group['targeting_data'], true) ?: array();
$targeting_keywords = isset($targeting_data['keywords']) ? $targeting_data['keywords'] : array();
$targeting_locations = isset($targeting_data['locations']) ? $targeting_data['locations'] : array();
$targeting_devices = isset($targeting_data['devices']) ? $targeting_data['devices'] : array('desktop', 'mobile', 'tablet');
$targeting_schedule = isset($targeting_data['schedule']) ? $targeting_data['schedule'] : array();

// 스케줄 데이터 기본값 설정
$schedule_days = isset($targeting_schedule['days']) ? $targeting_schedule['days'] : array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun');
$schedule_start_time = isset($targeting_schedule['start_time']) ? $targeting_schedule['start_time'] : '00:00';
$schedule_end_time = isset($targeting_schedule['end_time']) ? $targeting_schedule['end_time'] : '23:00';

// 모든 요일이 선택되었는지 확인
$all_days_selected = count($schedule_days) === 7;

// 하루 종일 선택되었는지 확인
$all_day_selected = $schedule_start_time === '00:00' && $schedule_end_time === '23:00';

// 폼 처리
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : '활성';
    $targeting_type = isset($_POST['targeting_type']) ? trim($_POST['targeting_type']) : '키워드';
    $bid_amount = isset($_POST['bid_amount']) ? floatval($_POST['bid_amount']) : 0;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    // 타겟팅 데이터
    $targeting_keywords = isset($_POST['targeting_keywords']) ? $_POST['targeting_keywords'] : array();
    $targeting_locations = isset($_POST['targeting_locations']) ? $_POST['targeting_locations'] : array();
    $targeting_devices = isset($_POST['targeting_devices']) ? $_POST['targeting_devices'] : array();
    $targeting_schedule = isset($_POST['targeting_schedule']) ? $_POST['targeting_schedule'] : array();
    
    // 유효성 검사
    if (empty($name)) {
        $msg = '광고 그룹 이름을 입력하세요.';
    } else if ($bid_amount <= 0) {
        $msg = '입찰가는 0보다 커야 합니다.';
    } else {
        // 데이터 수정
        $data = array(
            'name' => $name,
            'status' => $status,
            'targeting_type' => $targeting_type,
            'bid_amount' => $bid_amount,
            'description' => $description,
            'targeting_keywords' => $targeting_keywords,
            'targeting_locations' => $targeting_locations,
            'targeting_devices' => $targeting_devices,
            'targeting_schedule' => $targeting_schedule
        );
        
        // 광고 그룹 수정
        $result = $ad_group_model->update_ad_group($ad_group_id, $data);
        
        if ($result) {
            alert('광고 그룹이 수정되었습니다.', CF_CAMPAIGN_URL . '/ad_list.php?ad_group_id=' . $ad_group_id);
        } else {
            $msg = '광고 그룹 수정 중 오류가 발생했습니다.';
        }
    }
}

// 디바이스 타입 옵션
$device_types = array(
    'desktop' => '데스크톱',
    'mobile' => '모바일',
    'tablet' => '태블릿'
);

// 타겟팅 타입 옵션
$targeting_types = array(
    '키워드' => '키워드 타겟팅',
    '위치' => '위치 타겟팅',
    '관심사' => '관심사 타겟팅',
    '인구통계' => '인구통계 타겟팅',
    '리마케팅' => '리마케팅'
);

// 페이지 타이틀 설정
$g5['title'] = '광고 그룹 편집';
include_once CF_PATH . '/head.php';
?>

<div class="container-fluid">
    <!-- 페이지 타이틀 -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_view.php?id=<?php echo $ad_group['campaign_id']; ?>" class="text-primary"><?php echo $campaign['name']; ?></a> - 광고 그룹 편집
        </h1>
        <div>
            <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_list.php?ad_group_id=<?php echo $ad_group_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm mr-2">
                <i class="fas fa-ad fa-sm text-white-50"></i> 광고 목록
            </a>
            <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_list.php?campaign_id=<?php echo $ad_group['campaign_id']; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> 광고 그룹 목록으로 돌아가기
            </a>
        </div>
    </div>

    <!-- 알림 메시지 -->
    <?php if ($msg) { ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $msg; ?>
    </div>
    <?php } ?>

    <!-- 광고 그룹 편집 폼 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">광고 그룹 정보 수정</h6>
        </div>
        <div class="card-body">
            <form method="post" action="" id="adGroupForm">
                <!-- 기본 정보 탭 -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">광고 그룹 이름 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo $ad_group['name']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="status">상태</label>
                            <select class="form-control" id="status" name="status">
                                <option value="활성" <?php echo $ad_group['status'] == '활성' ? 'selected' : ''; ?>>활성</option>
                                <option value="비활성" <?php echo $ad_group['status'] == '비활성' ? 'selected' : ''; ?>>비활성</option>
                                <option value="일시중지" <?php echo $ad_group['status'] == '일시중지' ? 'selected' : ''; ?>>일시중지</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="targeting_type">타겟팅 유형</label>
                            <select class="form-control" id="targeting_type" name="targeting_type">
                                <?php foreach ($targeting_types as $key => $value) { ?>
                                <option value="<?php echo $key; ?>" <?php echo $ad_group['targeting_type'] == $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="bid_amount">입찰가(원) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="bid_amount" name="bid_amount" value="<?php echo $ad_group['bid_amount']; ?>" min="1" required>
                            <small class="form-text text-muted">클릭당 지불할 최대 금액을 설정합니다.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="description">광고 그룹 설명</label>
                            <textarea class="form-control" id="description" name="description" rows="5"><?php echo $ad_group['description']; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>디바이스 타겟팅</label>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="device_desktop" name="targeting_devices[]" value="desktop" <?php echo in_array('desktop', $targeting_devices) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="device_desktop">데스크톱</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="device_mobile" name="targeting_devices[]" value="mobile" <?php echo in_array('mobile', $targeting_devices) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="device_mobile">모바일</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="device_tablet" name="targeting_devices[]" value="tablet" <?php echo in_array('tablet', $targeting_devices) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="device_tablet">태블릿</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 키워드 타겟팅 -->
                <div id="keywordTargetingSection" class="targeting-section mt-4" style="<?php echo $ad_group['targeting_type'] !== '키워드' ? 'display:none;' : ''; ?>">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="m-0 font-weight-bold">키워드 타겟팅</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="keywords">키워드 목록</label>
                                <textarea class="form-control" id="keywords" rows="5" placeholder="각 키워드를 줄바꿈으로 구분하여 입력하세요."></textarea>
                                <small class="form-text text-muted">예: 온라인 마케팅, CPA 마케팅, 광고 캠페인, 마케팅 자동화</small>
                            </div>
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-sm" id="addKeywords">키워드 추가</button>
                            </div>
                            <div class="form-group">
                                <label>추가된 키워드</label>
                                <div id="keywordList" class="border p-3 min-height-100">
                                    <?php if (empty($targeting_keywords)) { ?>
                                    <p class="text-muted mb-0">추가된 키워드가 없습니다.</p>
                                    <?php } else { ?>
                                        <?php foreach ($targeting_keywords as $keyword) { ?>
                                        <div class="keyword-item">
                                            <?php echo $keyword; ?>
                                            <span class="remove-btn">×</span>
                                            <input type="hidden" name="targeting_keywords[]" value="<?php echo $keyword; ?>">
                                        </div>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 위치 타겟팅 -->
                <div id="locationTargetingSection" class="targeting-section mt-4" style="<?php echo $ad_group['targeting_type'] !== '위치' ? 'display:none;' : ''; ?>">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="m-0 font-weight-bold">위치 타겟팅</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="region">지역</label>
                                        <select class="form-control" id="region">
                                            <option value="">선택하세요</option>
                                            <option value="서울">서울</option>
                                            <option value="경기">경기</option>
                                            <option value="인천">인천</option>
                                            <option value="부산">부산</option>
                                            <option value="대구">대구</option>
                                            <option value="광주">광주</option>
                                            <option value="대전">대전</option>
                                            <option value="울산">울산</option>
                                            <option value="세종">세종</option>
                                            <option value="강원">강원</option>
                                            <option value="충북">충북</option>
                                            <option value="충남">충남</option>
                                            <option value="전북">전북</option>
                                            <option value="전남">전남</option>
                                            <option value="경북">경북</option>
                                            <option value="경남">경남</option>
                                            <option value="제주">제주</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="city">도시</label>
                                        <input type="text" class="form-control" id="city" placeholder="도시명 입력">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="radius">반경(km)</label>
                                        <select class="form-control" id="radius">
                                            <option value="1">1km</option>
                                            <option value="5">5km</option>
                                            <option value="10" selected>10km</option>
                                            <option value="20">20km</option>
                                            <option value="50">50km</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-sm" id="addLocation">위치 추가</button>
                            </div>
                            <div class="form-group">
                                <label>추가된 위치</label>
                                <div id="locationList" class="border p-3 min-height-100">
                                    <?php if (empty($targeting_locations)) { ?>
                                    <p class="text-muted mb-0">추가된 위치가 없습니다.</p>
                                    <?php } else { ?>
                                        <?php 
                                        // 위치 데이터 형식에 따라 처리
                                        if (isset($targeting_locations['region'])) {
                                            $regions = $targeting_locations['region'];
                                            $cities = $targeting_locations['city'];
                                            $radiuses = $targeting_locations['radius'];
                                            
                                            for ($i = 0; $i < count($regions); $i++) {
                                                $locationText = "";
                                                if (!empty($regions[$i])) {
                                                    $locationText .= $regions[$i];
                                                }
                                                if (!empty($cities[$i])) {
                                                    $locationText .= (strlen($locationText) > 0 ? " " : "") . $cities[$i];
                                                }
                                                $locationText .= " (" . $radiuses[$i] . "km)";
                                        ?>
                                        <div class="location-item">
                                            <?php echo $locationText; ?>
                                            <span class="remove-btn">×</span>
                                            <input type="hidden" name="targeting_locations[region][]" value="<?php echo $regions[$i]; ?>">
                                            <input type="hidden" name="targeting_locations[city][]" value="<?php echo $cities[$i]; ?>">
                                            <input type="hidden" name="targeting_locations[radius][]" value="<?php echo $radiuses[$i]; ?>">
                                        </div>
                                        <?php 
                                            }
                                        }
                                        ?>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 일정 타겟팅 -->
                <div id="scheduleTargetingSection" class="targeting-section mt-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="m-0 font-weight-bold">일정 타겟팅</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>요일 선택</label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="day_all" <?php echo $all_days_selected ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="day_all">모든 요일</label>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-3">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input day-checkbox" id="day_mon" name="targeting_schedule[days][]" value="mon" <?php echo in_array('mon', $schedule_days) ? 'checked' : ''; ?> <?php echo $all_days_selected ? 'disabled' : ''; ?>>
                                            <label class="custom-control-label" for="day_mon">월요일</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input day-checkbox" id="day_tue" name="targeting_schedule[days][]" value="tue" <?php echo in_array('tue', $schedule_days) ? 'checked' : ''; ?> <?php echo $all_days_selected ? 'disabled' : ''; ?>>
                                            <label class="custom-control-label" for="day_tue">화요일</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input day-checkbox" id="day_wed" name="targeting_schedule[days][]" value="wed" <?php echo in_array('wed', $schedule_days) ? 'checked' : ''; ?> <?php echo $all_days_selected ? 'disabled' : ''; ?>>
                                            <label class="custom-control-label" for="day_wed">수요일</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input day-checkbox" id="day_thu" name="targeting_schedule[days][]" value="thu" <?php echo in_array('thu', $schedule_days) ? 'checked' : ''; ?> <?php echo $all_days_selected ? 'disabled' : ''; ?>>
                                            <label class="custom-control-label" for="day_thu">목요일</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-3">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input day-checkbox" id="day_fri" name="targeting_schedule[days][]" value="fri" <?php echo in_array('fri', $schedule_days) ? 'checked' : ''; ?> <?php echo $all_days_selected ? 'disabled' : ''; ?>>
                                            <label class="custom-control-label" for="day_fri">금요일</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input day-checkbox" id="day_sat" name="targeting_schedule[days][]" value="sat" <?php echo in_array('sat', $schedule_days) ? 'checked' : ''; ?> <?php echo $all_days_selected ? 'disabled' : ''; ?>>
                                            <label class="custom-control-label" for="day_sat">토요일</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input day-checkbox" id="day_sun" name="targeting_schedule[days][]" value="sun" <?php echo in_array('sun', $schedule_days) ? 'checked' : ''; ?> <?php echo $all_days_selected ? 'disabled' : ''; ?>>
                                            <label class="custom-control-label" for="day_sun">일요일</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>시간대 선택</label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="time_all" <?php echo $all_day_selected ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="time_all">하루 종일</label>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <label for="start_time">시작 시간</label>
                                        <select class="form-control" id="start_time" name="targeting_schedule[start_time]" <?php echo $all_day_selected ? 'disabled' : ''; ?>>
                                            <?php for ($i = 0; $i < 24; $i++) { 
                                                $time = sprintf('%02d:00', $i);
                                            ?>
                                            <option value="<?php echo $time; ?>" <?php echo $schedule_start_time == $time ? 'selected' : ''; ?>><?php echo $time; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_time">종료 시간</label>
                                        <select class="form-control" id="end_time" name="targeting_schedule[end_time]" <?php echo $all_day_selected ? 'disabled' : ''; ?>>
                                            <?php for ($i = 0; $i < 24; $i++) { 
                                                $time = sprintf('%02d:00', $i);
                                            ?>
                                            <option value="<?php echo $time; ?>" <?php echo $schedule_end_time == $time ? 'selected' : ''; ?>><?php echo $time; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary">광고 그룹 수정</button>
                    <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_list.php?campaign_id=<?php echo $ad_group['campaign_id']; ?>" class="btn btn-secondary">취소</a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.min-height-100 {
    min-height: 100px;
}
.keyword-item, .location-item {
    display: inline-block;
    background-color: #f8f9fc;
    border: 1px solid #e3e6f0;
    border-radius: 3px;
    padding: 5px 10px;
    margin: 5px;
    position: relative;
}
.keyword-item .remove-btn, .location-item .remove-btn {
    position: absolute;
    top: -5px;
    right: -5px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background-color: #e74a3b;
    color: white;
    font-size: 10px;
    line-height: 16px;
    text-align: center;
    cursor: pointer;
}
</style>

<script>
$(document).ready(function() {
    // 키워드 추가
    $("#addKeywords").on("click", function() {
        var keywordsText = $("#keywords").val().trim();
        
        if (keywordsText === "") {
            alert("키워드를 입력하세요.");
            return;
        }
        
        var keywordArray = keywordsText.split("\n");
        var keywordList = $("#keywordList");
        
        // 처음 추가하는 경우 안내 메시지 제거
        if (keywordList.find(".text-muted").length > 0) {
            keywordList.empty();
        }
        
        // 키워드 추가
        $.each(keywordArray, function(index, keyword) {
            keyword = keyword.trim();
            if (keyword !== "") {
                var keywordItem = $('<div class="keyword-item"></div>');
                keywordItem.text(keyword);
                keywordItem.append('<span class="remove-btn">×</span>');
                keywordItem.append('<input type="hidden" name="targeting_keywords[]" value="' + keyword + '">');
                keywordList.append(keywordItem);
            }
        });
        
        // 입력창 초기화
        $("#keywords").val("");
    });
    
    // 위치 추가
    $("#addLocation").on("click", function() {
        var region = $("#region").val();
        var city = $("#city").val().trim();
        var radius = $("#radius").val();
        
        if (region === "" && city === "") {
            alert("지역이나 도시를 선택/입력하세요.");
            return;
        }
        
        var locationText = "";
        if (region !== "") {
            locationText += region;
        }
        if (city !== "") {
            locationText += (locationText !== "" ? " " : "") + city;
        }
        locationText += " (" + radius + "km)";
        
        var locationList = $("#locationList");
        
        // 처음 추가하는 경우 안내 메시지 제거
        if (locationList.find(".text-muted").length > 0) {
            locationList.empty();
        }
        
        // 위치 추가
        var locationItem = $('<div class="location-item"></div>');
        locationItem.text(locationText);
        locationItem.append('<span class="remove-btn">×</span>');
        locationItem.append('<input type="hidden" name="targeting_locations[region][]" value="' + region + '">');
        locationItem.append('<input type="hidden" name="targeting_locations[city][]" value="' + city + '">');
        locationItem.append('<input type="hidden" name="targeting_locations[radius][]" value="' + radius + '">');
        locationList.append(locationItem);
        
        // 입력창 초기화
        $("#region").val("");
        $("#city").val("");
        $("#radius").val("10");
    });
    
    // 키워드/위치 삭제
    $(document).on("click", ".remove-btn", function() {
        $(this).parent().remove();
        
        // 키워드 목록 확인
        if ($("#keywordList").children().length === 0) {
            $("#keywordList").html('<p class="text-muted mb-0">추가된 키워드가 없습니다.</p>');
        }
        
        // 위치 목록 확인
        if ($("#locationList").children().length === 0) {
            $("#locationList").html('<p class="text-muted mb-0">추가된 위치가 없습니다.</p>');
        }
    });
    
    // 모든 요일 체크박스 처리
    $("#day_all").on("change", function() {
        if ($(this).is(":checked")) {
            $(".day-checkbox").prop("checked", true).prop("disabled", true);
        } else {
            $(".day-checkbox").prop("disabled", false);
        }
    });
    
    // 하루 종일 체크박스 처리
    $("#time_all").on("change", function() {
        if ($(this).is(":checked")) {
            $("#start_time, #end_time").prop("disabled", true);
        } else {
            $("#start_time, #end_time").prop("disabled", false);
        }
    });
    
    // 타겟팅 유형에 따른 섹션 표시
    function updateTargetingSections() {
        var targetingType = $("#targeting_type").val();
        
        $(".targeting-section").hide();
        
        if (targetingType === '키워드') {
            $("#keywordTargetingSection").show();
        } else if (targetingType === '위치') {
            $("#locationTargetingSection").show();
        }
        
        // 일정 타겟팅은 항상 표시
        $("#scheduleTargetingSection").show();
    }
    
    // 초기 타겟팅 섹션 설정
    updateTargetingSections();
    
    // 타겟팅 유형 변경 시 섹션 업데이트
    $("#targeting_type").on("change", function() {
        updateTargetingSections();
    });
    
    // 폼 제출 전 유효성 검사
    $("#adGroupForm").on("submit", function(e) {
        var name = $("#name").val().trim();
        var bidAmount = $("#bid_amount").val();
        
        if (name === "") {
            alert("광고 그룹 이름을 입력하세요.");
            $("#name").focus();
            e.preventDefault();
            return false;
        }
        
        if (bidAmount <= 0) {
            alert("입찰가는 0보다 커야 합니다.");
            $("#bid_amount").focus();
            e.preventDefault();
            return false;
        }
        
        return true;
    });
});
</script>

<?php
include_once CF_PATH . '/tail.php';
?>