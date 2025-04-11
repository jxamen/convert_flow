<?php
/**
 * 단축 URL 일괄 생성 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 페이지 제목
$page_title = "단축 URL 일괄 생성";

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

// 사용자 도메인 목록 조회
$user_domains = $shorturl_model->get_user_domains($member['id']);

// 소재 유형 목록 조회
// 여기서는 자주 사용하는 기본 소재 유형들을 제공
$common_source_types = array('네이버', '구글', '페이스북', '인스타그램', '카카오', '블로그', '이메일', '배너', '기타');

// 폼 제출 처리
$error = '';
$success = '';
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 필수 입력값 검증
    if (empty($_POST['original_url'])) {
        $error = '원본 URL을 입력해주세요.';
    } else if (empty($_POST['source_list'])) {
        $error = '최소 하나 이상의 소재를 추가해주세요.';
    } else {
        // 소재 목록 파싱
        $source_list = json_decode($_POST['source_list'], true);
        
        if (empty($source_list)) {
            $error = '소재 정보가 올바르지 않습니다.';
        } else {
            // 공통 데이터 구성
            $common_data = array(
                'user_id' => $member['id'],
                'original_url' => $_POST['original_url'],
                'campaign_id' => isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0,
                'landing_id' => isset($_POST['landing_id']) ? intval($_POST['landing_id']) : 0,
                'domain' => isset($_POST['domain']) ? $_POST['domain'] : '',
                'expires_at' => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null,
                'path_type' => isset($_POST['path_type']) ? $_POST['path_type'] : 'random'
            );
            
            // URL 일괄 생성 처리
            $result = $shorturl_model->create_bulk_shorturls($common_data, $source_list);
            
            if ($result && $result['success'] > 0) {
                $success = $result['success'] . '개의 단축 URL이 성공적으로 생성되었습니다.';
                
                if ($result['failed'] > 0) {
                    $success .= ' (' . $result['failed'] . '개 실패)';
                }
            } else {
                $error = '단축 URL 생성 중 오류가 발생했습니다. 다시 시도해 주세요.';
            }
        }
    }
}

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">단축 URL 일괄 생성</h1>
    <p class="mb-4">여러 소재에 대한 단축 URL을 한 번에 생성하세요.</p>

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
            <h6 class="m-0 font-weight-bold text-primary">공통 설정</h6>
        </div>
        <div class="card-body">
            <form method="post" action="shorturl_bulk_create.php" id="shorturlForm">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="original_url"><strong>원본 URL</strong> <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="original_url" name="original_url" value="<?php echo isset($_POST['original_url']) ? $_POST['original_url'] : ''; ?>" required>
                            <small class="form-text text-muted">
                                축약할 전체 URL을 입력하세요. 최종 사용자가 접속할 웹 페이지 주소입니다.
                                각 소재별 UTM 파라미터는 자동으로 추가됩니다.
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
                
                <!-- 소재 리스트 관리 -->
                <div class="card mt-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">소재 목록</h6>
                        <button type="button" class="btn btn-primary btn-sm" id="btnAddSource">
                            <i class="fas fa-plus"></i> 소재 추가
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="sourceTable">
                                <thead>
                                    <tr>
                                        <th>소재 유형</th>
                                        <th>소재 이름</th>
                                        <th width="150px">관리</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 소재 항목이 여기에 동적으로 추가됨 -->
                                </tbody>
                            </table>
                        </div>
                        <p class="text-center mb-0" id="emptySourceMessage">
                            소재를 추가하려면 '소재 추가' 버튼을 클릭하세요.
                        </p>
                        
                        <!-- 소재 리스트 히든 필드 -->
                        <input type="hidden" name="source_list" id="sourceList" value="">
                    </div>
                </div>

                <div class="form-group mt-4 mb-0 text-right">
                    <a href="shorturl_list.php" class="btn btn-secondary">취소</a>
                    <button type="submit" class="btn btn-primary">URL 일괄 생성</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 소재 추가 모달 -->
<div class="modal fade" id="addSourceModal" tabindex="-1" role="dialog" aria-labelledby="addSourceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSourceModalLabel">소재 추가</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="sourceForm">
                    <div class="form-group">
                        <label for="sourceType">소재 유형 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sourceType" list="source_types" required>
                        <datalist id="source_types">
                            <?php foreach ($common_source_types as $type) { ?>
                            <option value="<?php echo $type; ?>">
                            <?php } ?>
                        </datalist>
                        <small class="form-text text-muted">
                            마케팅 채널 또는 광고 유형을 입력하세요 (예: 네이버, 구글, 페이스북).
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="sourceName">소재 이름 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sourceName" required>
                        <small class="form-text text-muted">
                            구체적인 광고 소재 또는 캠페인 구분을 입력하세요 (예: 메인배너, 9월프로모션).
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="btnSaveSource">추가</button>
            </div>
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
    
    // 소재 목록 관리
    var sourceList = [];
    
    // 소재 추가 버튼 클릭
    $('#btnAddSource').click(function() {
        $('#sourceForm')[0].reset();
        $('#addSourceModal').modal('show');
    });
    
    // 소재 저장 버튼 클릭
    $('#btnSaveSource').click(function() {
        var sourceType = $('#sourceType').val();
        var sourceName = $('#sourceName').val();
        
        // 유효성 검사
        if (!sourceType || !sourceName) {
            toastr.error('소재 유형과의 소재 이름을 모두 입력해주세요.');
            return;
        }
        
        // 중복 체크
        var isDuplicate = sourceList.some(function(source) {
            return source.type === sourceType && source.name === sourceName;
        });
        
        if (isDuplicate) {
            toastr.error('이미 동일한 소재가 목록에 있습니다.');
            return;
        }
        
        // 소재 목록에 추가
        sourceList.push({
            type: sourceType,
            name: sourceName
        });
        
        // 테이블 업데이트
        updateSourceTable();
        
        // 모달 닫기
        $('#addSourceModal').modal('hide');
    });
    
    // 테이블 업데이트 함수
    function updateSourceTable() {
        var tableBody = $('#sourceTable tbody');
        tableBody.empty();
        
        // 소재 항목 추가
        sourceList.forEach(function(source, index) {
            var row = $('<tr></tr>');
            row.append($('<td></td>').text(source.type));
            row.append($('<td></td>').text(source.name));
            
            var actionsCell = $('<td class="text-center"></td>');
            var deleteBtn = $('<button type="button" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> 삭제</button>');
            
            deleteBtn.click(function() {
                sourceList.splice(index, 1);
                updateSourceTable();
            });
            
            actionsCell.append(deleteBtn);
            row.append(actionsCell);
            
            tableBody.append(row);
        });
        
        // 빈 메시지 표시/숨김
        if (sourceList.length > 0) {
            $('#emptySourceMessage').hide();
            $('#sourceTable').show();
        } else {
            $('#emptySourceMessage').show();
            $('#sourceTable').hide();
        }
        
        // 히든 필드 업데이트
        $('#sourceList').val(JSON.stringify(sourceList));
    }
    
    // 초기 테이블 설정
    $('#sourceTable').hide();
    
    // 폼 제출 전 유효성 검사
    $('#shorturlForm').submit(function(e) {
        var originalUrl = $('#original_url').val();
        
        // URL 형식 검사
        if (!originalUrl.match(/^(http|https):\/\/[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}(:[0-9]{1,5})?(\/.*)?$/i)) {
            toastr.error('유효한 URL 형식이 아닙니다. http:// 또는 https://로 시작하는 주소를 입력하세요.');
            e.preventDefault();
            return false;
        }
        
        // 캠페인+랜딩페이지 구조 선택 시 관련 필드 검사
        if ($('#path_type_campaign_landing').prop('checked')) {
            if (!$('#campaign_id').val() || !$('#landing_id').val()) {
                toastr.error('캠페인+랜딩페이지 URL 구조를 선택한 경우 캠페인과 랜딩페이지를 모두 선택해야 합니다.');
                e.preventDefault();
                return false;
            }
        }
        
        // 캠페인 구조 선택 시 관련 필드 검사
        if ($('#path_type_campaign').prop('checked') && !$('#campaign_id').val()) {
            toastr.error('캠페인 URL 구조를 선택한 경우 캠페인을 선택해야 합니다.');
            e.preventDefault();
            return false;
        }
        
        // 소재 목록 검사
        if (sourceList.length === 0) {
            toastr.error('최소 하나 이상의 소재를 추가해주세요.');
            e.preventDefault();
            return false;
        }
        
        return true;
    });
    
    <?php if ($result && $result['success'] > 0) { ?>
    // 생성 성공 시 소재 목록 초기화
    sourceList = [];
    updateSourceTable();
    <?php } ?>
});
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>