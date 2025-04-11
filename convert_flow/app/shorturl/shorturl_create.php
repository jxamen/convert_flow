<?php
/**
 * 단축 URL 생성 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 페이지 제목
$page_title = "단축 URL 생성";

// 모델 로드
require_once CF_MODEL_PATH . '/shorturl.model.php';
$shorturl_model = new ShorturlModel();

// 캠페인 모델 로드
require_once CF_MODEL_PATH . '/campaign.model.php';
$campaign_model = new CampaignModel();

// 캠페인 목록 조회
$sql = "SELECT id, name FROM {$cf_table_prefix}campaigns 
        WHERE user_id = '{$member['id']}' AND status = '활성'
        ORDER BY name ASC";
$campaign_result = sql_query($sql);
$campaigns = array();
while($row = sql_fetch_array($campaign_result)) {
    $campaigns[] = $row;
}

// 랜딩페이지 목록 조회 (캠페인 선택 시 AJAX로 업데이트)
$landing_pages = array();

// 사용자 도메인 목록 조회
$user_domains = $shorturl_model->get_user_domains($member['id']);

// 소재 유형 목록 조회
// 여기서는 자주 사용하는 기본 소재 유형들을 제공
$common_source_types = array('네이버', '구글', '페이스북', '인스타그램', '카카오', '블로그', '이메일', '배너', '기타');

// 광고 플랫폼 목록 조회
$sql = "SELECT p.id, p.name 
        FROM {$cf_table_prefix}ad_platforms p
        INNER JOIN {$cf_table_prefix}ad_accounts a ON p.id = a.platform_id
        WHERE a.user_id = '{$member['id']}' AND a.status = '활성'
        GROUP BY p.id
        ORDER BY p.name";
$platform_result = sql_query($sql);
$platforms = array();
while($row = sql_fetch_array($platform_result)) {
    $platforms[] = $row;
}

// 폼 제출 처리
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 단축 URL 데이터 구성
    $url_data = array(
        'user_id' => $member['id'],
        'original_url' => isset($_POST['original_url']) ? $_POST['original_url'] : '',
        'campaign_id' => isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0,
        'landing_id' => isset($_POST['landing_id']) ? intval($_POST['landing_id']) : 0,
        'domain' => isset($_POST['domain']) ? $_POST['domain'] : '',
        'source_type' => isset($_POST['source_type']) ? $_POST['source_type'] : '',
        'source_name' => isset($_POST['source_name']) ? $_POST['source_name'] : '',
        'expires_at' => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null,
        'path_type' => isset($_POST['path_type']) ? $_POST['path_type'] : 'random'
    );
    
    // 광고 소재 생성 및 연동이 활성화된 경우
    if (isset($_POST['create_ad_material']) && $_POST['create_ad_material'] == '1') {
        $ad_data = array(
            'platform_id' => isset($_POST['ad_platform']) ? intval($_POST['ad_platform']) : 0,
            'account_id' => isset($_POST['ad_account']) ? intval($_POST['ad_account']) : 0,
            'name' => isset($_POST['ad_material_name']) ? $_POST['ad_material_name'] : '',
            'type' => isset($_POST['ad_material_type']) ? $_POST['ad_material_type'] : '',
            'headline' => isset($_POST['ad_material_headline']) ? $_POST['ad_material_headline'] : '',
            'description' => isset($_POST['ad_material_description']) ? $_POST['ad_material_description'] : '',
            'campaign_id' => $url_data['campaign_id'],
            'landing_id' => $url_data['landing_id']
        );
        
        // 이미지 파일이 업로드된 경우 처리
        if (!empty($_FILES['ad_material_image']['name'])) {
            // 이미지 업로드 처리 로직
            $upload_dir = CF_PATH . '/uploads/ad_materials/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = time() . '_' . basename($_FILES['ad_material_image']['name']);
            $target_file = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['ad_material_image']['tmp_name'], $target_file)) {
                $ad_data['image_path'] = '/uploads/ad_materials/' . $filename;
            }
        }
        
        // 플랫폼 정보 가져오기
        $sql = "SELECT name FROM {$cf_table_prefix}ad_platforms WHERE id = '{$ad_data['platform_id']}'";
        $platform = sql_fetch($sql);
        $api_type = strtolower($platform['name']);
        
        // 광고 소재 생성 및 URL 연동
        $result = $shorturl_model->create_url_with_ad_material($url_data, $ad_data, $api_type);
        
        if ($result['success']) {
            // 성공 메시지와 함께 상세 페이지로 이동
            $msg = urlencode('단축 URL과 광고 소재가 성공적으로 생성되었습니다.');
            goto_url('shorturl_view.php?id=' . $result['url_id'] . '&msg=' . $msg . '&msg_type=success');
            exit;
        } else {
            $error = '처리 중 오류가 발생했습니다: ' . $result['error'];
        }
    } else {
        // 일반 URL 생성 처리
        $result = $shorturl_model->create_shorturl($url_data);
        
        if ($result) {
            // 성공 메시지와 함께 상세 페이지로 이동
            $msg = urlencode('단축 URL이 성공적으로 생성되었습니다.');
            goto_url('shorturl_view.php?id=' . $result . '&msg=' . $msg . '&msg_type=success');
            exit;
        } else {
            $error = '단축 URL 생성 중 오류가 발생했습니다. 다시 시도해 주세요.';
        }
    }
}

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">단축 URL 생성</h1>
    <p class="mb-4">마케팅 캠페인 및 소스별로 추적 가능한 단축 URL을 생성하세요.</p>

    <!-- 에러 메시지 표시 -->
    <?php if (!empty($error)) { ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>
    
    <!-- 성공 메시지 표시 -->
    <?php if (!empty($success)) { ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php } ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">단축 URL 정보 입력</h6>
        </div>
        <div class="card-body">
            <form method="post" action="shorturl_create.php" id="shorturlForm" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="original_url"><strong>원본 URL</strong></label>
                            <input type="url" class="form-control" id="original_url" name="original_url" value="<?php echo isset($_POST['original_url']) ? $_POST['original_url'] : ''; ?>">
                            <small class="form-text text-muted">
                                축약할 URL을 입력하세요. 비워두면 캠페인이나 랜딩페이지의 가상 URL이 생성됩니다.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="campaign_id"><strong>캠페인</strong></label>
                            <select class="form-control" id="campaign_id" name="campaign_id">
                                <option value="">캠페인 없음</option>
                                <?php foreach ($campaigns as $campaign) { ?>
                                <option value="<?php echo $campaign['id']; ?>" <?php echo (isset($_POST['campaign_id']) && $_POST['campaign_id'] == $campaign['id']) ? 'selected' : ''; ?>>
                                    <?php echo $campaign['name']; ?>
                                </option>
                                <?php } ?>
                            </select>
                            <small class="form-text text-muted">연결할 마케팅 캠페인을 선택하세요.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="landing_id"><strong>랜딩페이지</strong></label>
                            <select class="form-control" id="landing_id" name="landing_id" <?php echo empty($campaigns) ? 'disabled' : ''; ?>>
                                <option value="">랜딩페이지 없음</option>
                                <?php if (!empty($_POST['campaign_id']) && !empty($_POST['landing_id'])) { ?>
                                <option value="<?php echo $_POST['landing_id']; ?>" selected>
                                    현재 선택된 랜딩페이지
                                </option>
                                <?php } ?>
                            </select>
                            <small class="form-text text-muted">연결할 랜딩페이지를 선택하세요. 캠페인을 먼저 선택해야 합니다.</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="domain"><strong>도메인</strong></label>
                            <input type="text" class="form-control" id="domain" name="domain" list="domains" placeholder="https://example.com" value="<?php echo isset($_POST['domain']) ? $_POST['domain'] : ''; ?>">
                            <datalist id="domains">
                                <?php foreach ($user_domains as $domain) { ?>
                                <option value="<?php echo $domain; ?>">
                                <?php } ?>
                            </datalist>
                            <small class="form-text text-muted">
                                단축 URL에 사용할 도메인을 입력하세요. 입력하지 않으면 기본 도메인(<?php echo CF_URL; ?>)이 사용됩니다.
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="expires_at"><strong>만료일</strong></label>
                            <input type="date" class="form-control" id="expires_at" name="expires_at" value="<?php echo isset($_POST['expires_at']) ? $_POST['expires_at'] : ''; ?>">
                            <small class="form-text text-muted">URL의 만료일을 설정하세요. 비워두면 만료되지 않습니다.</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="source_type"><strong>소재 유형</strong></label>
                            <input type="text" class="form-control" id="source_type" name="source_type" list="source_types" value="<?php echo isset($_POST['source_type']) ? $_POST['source_type'] : ''; ?>">
                            <datalist id="source_types">
                                <?php foreach ($common_source_types as $type) { ?>
                                <option value="<?php echo $type; ?>">
                                <?php } ?>
                            </datalist>
                            <small class="form-text text-muted">
                                광고 소재 또는 유입 경로 유형을 입력하세요 (예: 네이버, 페이스북, 이메일).
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="source_name"><strong>소재 이름</strong></label>
                            <input type="text" class="form-control" id="source_name" name="source_name" value="<?php echo isset($_POST['source_name']) ? $_POST['source_name'] : ''; ?>">
                            <small class="form-text text-muted">
                                광고 소재 또는 게시물의 구체적인 이름을 입력하세요 (예: 9월_프로모션, 메인배너).
                            </small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="path_type"><strong>URL 구조</strong></label>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="path_type_random" name="path_type" value="random" class="custom-control-input" <?php echo (!isset($_POST['path_type']) || $_POST['path_type'] == 'random') ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="path_type_random">
                                    랜덤 코드만 사용 (예: <?php echo CF_URL; ?>/abcd1234)
                                </label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="path_type_campaign" name="path_type" value="campaign_only" class="custom-control-input" <?php echo (isset($_POST['path_type']) && $_POST['path_type'] == 'campaign_only') ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="path_type_campaign">
                                    캠페인 구조 (예: <?php echo CF_URL; ?>/RANDING/캠페인번호/랜덤코드)
                                </label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="path_type_campaign_landing" name="path_type" value="campaign_landing" class="custom-control-input" <?php echo (isset($_POST['path_type']) && $_POST['path_type'] == 'campaign_landing') ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="path_type_campaign_landing">
                                    캠페인 + 랜딩페이지 구조 (예: <?php echo CF_URL; ?>/RANDING/캠페인번호/랜딩번호/랜덤코드)
                                </label>
                            </div>
                            <small class="form-text text-muted mt-2">
                                단축 URL의 경로 구조를 선택하세요. 캠페인 또는 랜딩페이지 기반 구조를 선택하면 해당 정보가 URL에 포함됩니다.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group mb-0">
                            <label><strong>UTM 파라미터 자동 추가</strong></label>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="add_utm" name="add_utm" value="1" checked>
                                <label class="custom-control-label" for="add_utm">
                                    캠페인 및 소재 정보를 UTM 파라미터로 자동 추가
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                선택 시 utm_source, utm_campaign, utm_content 파라미터가 원본 URL에 자동으로 추가됩니다.
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- 광고 소재 연동 섹션 -->
                <?php if (!empty($platforms)) { ?>
                <div class="card mt-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">광고 소재 연동</h6>
                    </div>
                    <div class="card-body">
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input" id="create_ad_material" name="create_ad_material" value="1" <?php echo (isset($_POST['create_ad_material']) && $_POST['create_ad_material'] == '1') ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="create_ad_material">
                                API를 통해 광고 소재 생성 및 연동
                            </label>
                            <small class="form-text text-muted">
                                선택 시 연결된 광고 플랫폼 API를 통해 광고 소재를 자동 생성하고 이 URL과 연동합니다.
                            </small>
                        </div>
                        
                        <div id="adMaterialSection" style="display: <?php echo (isset($_POST['create_ad_material']) && $_POST['create_ad_material'] == '1') ? 'block' : 'none'; ?>;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ad_platform"><strong>광고 플랫폼</strong></label>
                                        <select class="form-control" id="ad_platform" name="ad_platform">
                                            <option value="">플랫폼 선택</option>
                                            <?php foreach ($platforms as $platform) { ?>
                                            <option value="<?php echo $platform['id']; ?>" <?php echo (isset($_POST['ad_platform']) && $_POST['ad_platform'] == $platform['id']) ? 'selected' : ''; ?>>
                                                <?php echo $platform['name']; ?>
                                            </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ad_account"><strong>광고 계정</strong></label>
                                        <select class="form-control" id="ad_account" name="ad_account" <?php echo (isset($_POST['ad_platform']) && $_POST['ad_platform']) ? '' : 'disabled'; ?>>
                                            <option value="">계정 선택</option>
                                            <?php
                                            if (isset($_POST['ad_platform']) && $_POST['ad_platform']) {
                                                $sql = "SELECT id, account_name, account_id FROM {$cf_table_prefix}ad_accounts 
                                                        WHERE user_id = '{$member['id']}' AND platform_id = '{$_POST['ad_platform']}' AND status = '활성'
                                                        ORDER BY account_name";
                                                $account_result = sql_query($sql);
                                                while ($account = sql_fetch_array($account_result)) {
                                                    $selected = (isset($_POST['ad_account']) && $_POST['ad_account'] == $account['id']) ? 'selected' : '';
                                                    echo '<option value="' . $account['id'] . '" ' . $selected . '>' . $account['account_name'] . ' (' . $account['account_id'] . ')</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ad_material_name"><strong>소재 이름</strong></label>
                                        <input type="text" class="form-control" id="ad_material_name" name="ad_material_name" value="<?php echo isset($_POST['ad_material_name']) ? $_POST['ad_material_name'] : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ad_material_type"><strong>소재 유형</strong></label>
                                        <select class="form-control" id="ad_material_type" name="ad_material_type">
                                            <option value="">유형 선택</option>
                                            <?php
                                            if (isset($_POST['ad_platform']) && $_POST['ad_platform']) {
                                                // 실제로는 AJAX로 가져오겠지만, 여기서는 간단하게 몇 가지 타입 제공
                                                $types = array(
                                                    array('value' => 'text', 'name' => '텍스트 광고'),
                                                    array('value' => 'image', 'name' => '이미지 광고'),
                                                    array('value' => 'banner', 'name' => '배너 광고'),
                                                    array('value' => 'video', 'name' => '비디오 광고')
                                                );
                                                foreach ($types as $type) {
                                                    $selected = (isset($_POST['ad_material_type']) && $_POST['ad_material_type'] == $type['value']) ? 'selected' : '';
                                                    echo '<option value="' . $type['value'] . '" ' . $selected . '>' . $type['name'] . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="ad_material_headline"><strong>제목/헤드라인</strong></label>
                                <input type="text" class="form-control" id="ad_material_headline" name="ad_material_headline" value="<?php echo isset($_POST['ad_material_headline']) ? $_POST['ad_material_headline'] : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="ad_material_description"><strong>설명</strong></label>
                                <textarea class="form-control" id="ad_material_description" name="ad_material_description" rows="3"><?php echo isset($_POST['ad_material_description']) ? $_POST['ad_material_description'] : ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label><strong>이미지</strong></label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="ad_material_image" name="ad_material_image">
                                    <label class="custom-file-label" for="ad_material_image">파일 선택</label>
                                </div>
                                <small class="form-text text-muted">
                                    광고 소재에 사용할 이미지를 선택하세요. (선택사항)
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>

                <div class="form-group mt-4 mb-0 text-right">
                    <a href="shorturl_list.php" class="btn btn-secondary">취소</a>
                    <button type="submit" class="btn btn-primary">단축 URL 생성</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 캠페인 선택 시 관련 랜딩페이지 목록 로드
    $('#campaign_id').change(function() {
        var campaignId = $(this).val();
        var landingSelect = $('#landing_id');
        
        // 랜딩페이지 셀렉트박스 초기화
        landingSelect.html('<option value="">랜딩페이지 없음</option>');
        
        if (!campaignId) {
            landingSelect.prop('disabled', true);
            return;
        }
        
        // 선택된 캠페인의 랜딩페이지 목록 불러오기
        $.ajax({
            url: 'shorturl_ajax.php',
            type: 'GET',
            data: {
                action: 'get_landing_pages',
                campaign_id: campaignId
            },
            dataType: 'json',
            success: function(data) {
                if (data && data.length > 0) {
                    $.each(data, function(i, landing) {
                        landingSelect.append($('<option></option>')
                            .attr('value', landing.id)
                            .text(landing.name));
                    });
                    landingSelect.prop('disabled', false);
                } else {
                    landingSelect.prop('disabled', true);
                }
            },
            error: function() {
                toastr.error('랜딩페이지 목록을 불러오는 중 오류가 발생했습니다.');
            }
        });
    });
    
    // 캠페인 또는 랜딩페이지 변경 시 URL 구조 라디오 버튼 업데이트
    function updatePathTypeOptions() {
        var campaignId = $('#campaign_id').val();
        var landingId = $('#landing_id').val();
        
        // 캠페인이 선택되지 않은 경우
        if (!campaignId) {
            $('#path_type_campaign, #path_type_campaign_landing').prop('disabled', true);
            $('#path_type_random').prop('checked', true);
        } else {
            $('#path_type_campaign').prop('disabled', false);
            
            // 랜딩페이지가 선택되지 않은 경우
            if (!landingId) {
                $('#path_type_campaign_landing').prop('disabled', true);
                
                // 캠페인+랜딩페이지 구조가 선택되어 있었다면 캠페인 구조로 변경
                if ($('#path_type_campaign_landing').prop('checked')) {
                    $('#path_type_campaign').prop('checked', true);
                }
            } else {
                $('#path_type_campaign_landing').prop('disabled', false);
            }
        }
    }
    
    // 초기 로드 시와 변경 시 URL 구조 옵션 업데이트
    updatePathTypeOptions();
    $('#campaign_id, #landing_id').change(updatePathTypeOptions);
    
    // 광고 소재 생성 체크박스 변경 시
    $('#create_ad_material').change(function() {
        if ($(this).is(':checked')) {
            $('#adMaterialSection').slideDown();
        } else {
            $('#adMaterialSection').slideUp();
        }
    });
    
    // 광고 플랫폼 변경 시 계정 목록 업데이트
    $('#ad_platform').change(function() {
        var platformId = $(this).val();
        var accountSelect = $('#ad_account');
        
        // 계정 셀렉트박스 초기화
        accountSelect.html('<option value="">계정 선택</option>');
        
        if (!platformId) {
            accountSelect.prop('disabled', true);
            return;
        }
        
        // 선택된 플랫폼의 계정 목록 불러오기
        $.ajax({
            url: 'shorturl_ajax.php',
            type: 'GET',
            data: {
                action: 'get_ad_accounts',
                platform_id: platformId
            },
            dataType: 'json',
            success: function(data) {
                if (data && data.length > 0) {
                    $.each(data, function(i, account) {
                        accountSelect.append($('<option></option>')
                            .attr('value', account.id)
                            .text(account.account_name + ' (' + account.account_id + ')'));
                    });
                    accountSelect.prop('disabled', false);
                    
                    // 소재 유형 목록 업데이트
                    updateAdMaterialTypes(platformId);
                } else {
                    accountSelect.prop('disabled', true);
                }
            },
            error: function() {
                toastr.error('광고 계정 목록을 불러오는 중 오류가 발생했습니다.');
            }
        });
    });
    
    // 광고 소재 유형 업데이트
    function updateAdMaterialTypes(platformId) {
        var materialTypeSelect = $('#ad_material_type');
        
        // 소재 유형 셀렉트박스 초기화
        materialTypeSelect.html('<option value="">유형 선택</option>');
        
        // AJAX로 플랫폼별 소재 유형 불러오기
        $.ajax({
            url: 'shorturl_ajax.php',
            type: 'GET',
            data: {
                action: 'get_ad_material_types',
                platform_id: platformId
            },
            dataType: 'json',
            success: function(data) {
                if (data && data.length > 0) {
                    $.each(data, function(i, type) {
                        materialTypeSelect.append($('<option></option>')
                            .attr('value', type.value)
                            .text(type.name));
                    });
                    materialTypeSelect.prop('disabled', false);
                } else {
                    materialTypeSelect.prop('disabled', true);
                }
            },
            error: function() {
                toastr.error('광고 소재 유형을 불러오는 중 오류가 발생했습니다.');
            }
        });
    }
    
    // 파일 업로드 시 파일명 표시
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
    });
    
    // 광고 소재 제목과 설명 생성 도움말
    $('#ad_material_headline, #ad_material_description').on('focus', function() {
        var campaignId = $('#campaign_id').val();
        var landingId = $('#landing_id').val();
        
        // 선택된 캠페인이나 랜딩페이지의 정보를 기반으로 제목과 설명 힌트 제공
        if (campaignId || landingId) {
            $.ajax({
                url: 'shorturl_ajax.php',
                type: 'GET',
                data: {
                    action: 'get_campaign_landing_info',
                    campaign_id: campaignId,
                    landing_id: landingId
                },
                dataType: 'json',
                success: function(data) {
                    if (data) {
                        if (!$('#ad_material_headline').val()) {
                            $('#ad_material_headline').attr('placeholder', '예: ' + data.suggested_headline);
                        }
                        if (!$('#ad_material_description').val()) {
                            $('#ad_material_description').attr('placeholder', '예: ' + data.suggested_description);
                        }
                    }
                }
            });
        }
    });
    
    // 폼 제출 전 유효성 검사
    $('#shorturlForm').on('submit', function(e) {
        var isValid = true;
        
        // 원본 URL 유효성 검사
        var originalUrl = $('#original_url').val();
        if (originalUrl && !isValidUrl(originalUrl)) {
            toastr.error('유효한 URL 형식이 아닙니다.');
            isValid = false;
        }
        
        // 광고 소재 생성 체크 시 추가 검사
        if ($('#create_ad_material').is(':checked')) {
            // 광고 플랫폼 선택 검사
            if (!$('#ad_platform').val()) {
                toastr.error('광고 플랫폼을 선택해주세요.');
                isValid = false;
            }
            
            // 광고 계정 선택 검사
            if (!$('#ad_account').val()) {
                toastr.error('광고 계정을 선택해주세요.');
                isValid = false;
            }
            
            // 소재 이름 검사
            if (!$('#ad_material_name').val()) {
                toastr.error('소재 이름을 입력해주세요.');
                isValid = false;
            }
            
            // 소재 유형 검사
            if (!$('#ad_material_type').val()) {
                toastr.error('소재 유형을 선택해주세요.');
                isValid = false;
            }
        }
        
        // 폼 제출 방지
        if (!isValid) {
            e.preventDefault();
        }
    });
    
    // URL 유효성 확인 함수
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch (e) {
            return false;
        }
    }
});
</script>