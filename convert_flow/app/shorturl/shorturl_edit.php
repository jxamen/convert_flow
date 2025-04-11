<?php
/**
 * 단축 URL 수정 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 페이지 제목
$page_title = "단축 URL 수정";

// 모델 로드
require_once CF_MODEL_PATH . '/shorturl.model.php';
$shorturl_model = new ShorturlModel();

// 캠페인 모델 로드
require_once CF_MODEL_PATH . '/campaign.model.php';
$campaign_model = new CampaignModel();

// ID 체크
if (empty($_GET['id'])) {
    alert('잘못된 접근입니다.');
}

$url_id = intval($_GET['id']);

// 단축 URL 정보 조회
$url_info = $shorturl_model->get_shorturl($url_id, $member['id']);

if (!$url_info) {
    alert('존재하지 않는 단축 URL이거나 접근 권한이 없습니다.');
}

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
if ($url_info['campaign_id'] > 0) {
    $sql = "SELECT id, name FROM {$cf_table_prefix}landing_pages 
            WHERE user_id = '{$member['id']}' AND campaign_id = '{$url_info['campaign_id']}'
            ORDER BY name ASC";
    $landing_result = sql_query($sql);
    while($row = sql_fetch_array($landing_result)) {
        $landing_pages[] = $row;
    }
}

// 사용자 도메인 목록 조회
$user_domains = $shorturl_model->get_user_domains($member['id']);

// 소재 유형 목록 조회
// 여기서는 자주 사용하는 기본 소재 유형들을 제공
$common_source_types = array('네이버', '구글', '페이스북', '인스타그램', '카카오', '블로그', '이메일', '배너', '기타');

// URL 경로 구조 분석
$path_type = 'random';
if (strpos($url_info['path'], 'RANDING/') === 0) {
    $parts = explode('/', $url_info['path']);
    
    if (count($parts) >= 4) {
        $path_type = 'campaign_landing';
    } else if (count($parts) >= 3) {
        $path_type = 'campaign_only';
    }
}

// 폼 제출 처리
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 필수 입력값 검증
    if (empty($_POST['original_url'])) {
        $error = '원본 URL을 입력해주세요.';
    } else {
        // 단축 URL 데이터 구성
        $url_data = array(
            'original_url' => $_POST['original_url'],
            'campaign_id' => isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0,
            'landing_id' => isset($_POST['landing_id']) ? intval($_POST['landing_id']) : 0,
            'domain' => isset($_POST['domain']) ? $_POST['domain'] : '',
            'source_type' => isset($_POST['source_type']) ? $_POST['source_type'] : '',
            'source_name' => isset($_POST['source_name']) ? $_POST['source_name'] : '',
            'expires_at' => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null,
            'update_path' => isset($_POST['update_path']) ? true : false
        );
        
        // URL 수정 처리
        $result = $shorturl_model->update_shorturl($url_id, $url_data, $member['id']);
        
        if ($result) {
            // 성공 메시지 설정 후 현재 페이지 유지
            $success = '단축 URL이 성공적으로 수정되었습니다.';
            
            // 업데이트된 정보 다시 로드
            $url_info = $shorturl_model->get_shorturl($url_id, $member['id']);
        } else {
            $error = '단축 URL 수정 중 오류가 발생했습니다. 다시 시도해 주세요.';
        }
    }
}

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">단축 URL 수정</h1>
    <p class="mb-4">단축 URL 정보를 수정하고 관리하세요.</p>

    <!-- 에러 메시지 표시 -->
    <?php if (!empty($error)) { ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>
    
    <!-- 성공 메시지 표시 -->
    <?php if (!empty($success)) { ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php } ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">단축 URL 정보 수정</h6>
            <div>
                <a href="shorturl_view.php?id=<?php echo $url_id; ?>" class="btn btn-secondary btn-sm">취소</a>
            </div>
        </div>
        <div class="card-body">
            <form method="post" action="shorturl_edit.php?id=<?php echo $url_id; ?>" id="shorturlForm">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="short_url"><strong>단축 URL</strong></label>
                            <input type="text" class="form-control bg-light" id="short_url" value="<?php echo !empty($url_info['domain']) ? $url_info['domain'] . '/' . $url_info['path'] : CF_URL . '/' . $url_info['path']; ?>" readonly>
                            <small class="form-text text-muted">
                                생성된 단축 URL은 직접 수정할 수 없습니다. 원본 URL과 관련 정보를 수정할 수 있습니다.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="original_url"><strong>원본 URL</strong> <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="original_url" name="original_url" value="<?php echo $url_info['original_url']; ?>" required>
                            <small class="form-text text-muted">
                                최종 사용자가 접속할 웹 페이지 주소입니다.
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
                                <option value="<?php echo $campaign['id']; ?>" <?php echo ($url_info['campaign_id'] == $campaign['id']) ? 'selected' : ''; ?>>
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
                            <select class="form-control" id="landing_id" name="landing_id" <?php echo $url_info['campaign_id'] > 0 ? '' : 'disabled'; ?>>
                                <option value="">랜딩페이지 없음</option>
                                <?php foreach ($landing_pages as $landing) { ?>
                                <option value="<?php echo $landing['id']; ?>" <?php echo ($url_info['landing_id'] == $landing['id']) ? 'selected' : ''; ?>>
                                    <?php echo $landing['name']; ?>
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
                            <input type="text" class="form-control" id="domain" name="domain" list="domains" placeholder="https://example.com" value="<?php echo $url_info['domain']; ?>">
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
                            <input type="date" class="form-control" id="expires_at" name="expires_at" value="<?php echo !empty($url_info['expires_at']) ? date('Y-m-d', strtotime($url_info['expires_at'])) : ''; ?>">
                            <small class="form-text text-muted">URL의 만료일을 설정하세요. 비워두면 만료되지 않습니다.</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="source_type"><strong>소재 유형</strong></label>
                            <input type="text" class="form-control" id="source_type" name="source_type" list="source_types" value="<?php echo $url_info['source_type']; ?>">
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
                            <input type="text" class="form-control" id="source_name" name="source_name" value="<?php echo $url_info['source_name']; ?>">
                            <small class="form-text text-muted">
                                광고 소재 또는 게시물의 구체적인 이름을 입력하세요 (예: 9월_프로모션, 메인배너).
                            </small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="update_path" name="update_path" value="1">
                                <label class="custom-control-label" for="update_path">
                                    URL 경로 구조 업데이트
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                캠페인 또는 랜딩페이지 정보를 변경한 경우, 이 옵션을 체크하면 URL 경로 구조가 업데이트됩니다. (현재 구조: <?php echo $path_type; ?>)
                            </small>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4 mb-0 text-right">
                    <a href="shorturl_view.php?id=<?php echo $url_id; ?>" class="btn btn-secondary">취소</a>
                    <button type="submit" class="btn btn-primary">변경사항 저장</button>
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
    
    // 폼 제출 전 유효성 검사
    $('#shorturlForm').submit(function(e) {
        var originalUrl = $('#original_url').val();
        
        // URL 형식 검사
        if (!originalUrl.match(/^(http|https):\/\/[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}(:[0-9]{1,5})?(\/.*)?$/i)) {
            toastr.error('유효한 URL 형식이 아닙니다. http:// 또는 https://로 시작하는 주소를 입력하세요.');
            e.preventDefault();
            return false;
        }
        
        // 소재 유형과 이름이 모두 입력되었는지 확인
        var sourceType = $('#source_type').val();
        var sourceName = $('#source_name').val();
        
        if ((sourceType && !sourceName) || (!sourceType && sourceName)) {
            toastr.warning('소재 유형과 소재 이름은 둘 다 입력하거나 둘 다 비워두세요.');
            // 경고만 표시하고 제출은 허용
        }
        
        return true;
    });
});
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>