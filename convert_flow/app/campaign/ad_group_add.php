<?php
/**
 * 광고 그룹 추가 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 페이지 제목
$page_title = "광고 그룹 추가";

// 권한 체크
if (!$is_member) {
    alert('로그인이 필요한 서비스입니다.', CF_URL . '/login.php');
}

// 캠페인 ID가 있는 경우 연결된 캠페인 정보를 가져옴
$campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
$campaign = array();

if ($campaign_id > 0) {
    include_once CF_MODEL_PATH . '/campaign/campaign.model.php';
    $campaign_model = new CampaignModel();
    $campaign = $campaign_model->get_campaign($campaign_id, $member['id']);
    
    if (!$campaign) {
        alert('존재하지 않거나 접근 권한이 없는 캠페인입니다.');
        exit;
    }
}

// 광고 계정 목록 가져오기
include_once CF_MODEL_PATH . '/ad_account.model.php';
$ad_account_model = new AdAccountModel();
$ad_accounts_data = $ad_account_model->get_user_ad_accounts($member['id']);
$ad_accounts = $ad_accounts_data['accounts'];

// 내부 캠페인 목록 가져오기 (미리 로드)
include_once CF_MODEL_PATH . '/campaign/campaign.model.php';
$campaign_model = new CampaignModel();
$campaigns_result = $campaign_model->get_campaigns($member['id']);
$campaigns = $campaigns_result['campaigns'];

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <!-- 페이지 제목 -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-layer-group fa-fw"></i> 광고 그룹 추가
        </h1>
        <div>
            <?php if ($campaign_id > 0): ?>
            <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_view.php?id=<?php echo $campaign_id; ?>&tab=ad_groups" class="d-none d-sm-inline-block btn btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> 캠페인으로 돌아가기
            </a>
            <?php else: ?>
            <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_list.php" class="d-none d-sm-inline-block btn btn-secondary shadow-sm">
                <i class="fas fa-list fa-sm text-white-50"></i> 광고 그룹 목록
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 광고 그룹 추가 폼 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">광고 그룹 정보</h6>
        </div>
        <div class="card-body">
            <form id="adGroupForm" method="post" action="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_add_update.php">
                <input type="hidden" name="action" value="add">
                
                <!-- 내부 캠페인 선택 -->
                <div class="form-group row">
                    <label for="campaign_id" class="col-sm-2 col-form-label">내부 캠페인 <span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <?php if ($campaign_id > 0): ?>
                        <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">
                        <p class="form-control-static mt-2"><?php echo $campaign['name']; ?></p>
                        <?php else: ?>
                        <select class="form-control" id="campaign_id" name="campaign_id" required>
                            <option value="">내부 캠페인을 선택하세요</option>
                            <?php foreach ($campaigns as $camp): ?>
                            <option value="<?php echo $camp['id']; ?>"><?php echo $camp['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">캠페인을 선택해주세요.</div>
                        <small class="form-text text-muted">광고 그룹이 소속될 내부 캠페인을 선택하세요.</small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 외부 광고 매체 연동 섹션 -->
                <div class="card mb-4">
                    <div class="card-header py-2">
                        <h6 class="m-0 font-weight-bold text-secondary">외부 광고 매체 연동 (선택사항)</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group row">
                            <label for="ad_account_id" class="col-sm-2 col-form-label">광고 계정</label>
                            <div class="col-sm-10">
                                <select class="form-control" id="ad_account_id" name="ad_account_id">
                                    <option value="">광고 계정을 선택하세요 (선택사항)</option>
                                    <?php foreach ($ad_accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>"><?php echo $account['account_name']; ?> (<?php echo $account['platform_name']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">광고 계정을 선택하면 외부 캠페인과 광고 그룹 목록을 불러올 수 있습니다.</small>
                            </div>
                        </div>
                        
                        <!-- 외부 캠페인 선택 (AJAX로 로드) -->
                        <div class="form-group row">
                            <label for="external_campaign_id" class="col-sm-2 col-form-label">외부 캠페인</label>
                            <div class="col-sm-10">
                                <select class="form-control" id="external_campaign_id" name="external_campaign_id" disabled>
                                    <option value="">광고 계정을 먼저 선택하세요</option>
                                </select>
                                <small class="form-text text-muted">내부 캠페인에 연결할 외부 광고 매체의 캠페인을 선택하세요. (선택사항)</small>
                                <div id="campaignLoading" class="mt-2 d-none">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="sr-only">로딩중...</span>
                                    </div>
                                    <small class="text-muted ml-2">캠페인 목록을 불러오는 중...</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 외부 광고 그룹 선택 (AJAX로 로드) -->
                        <div class="form-group row">
                            <label for="external_ad_group_id" class="col-sm-2 col-form-label">외부 광고 그룹</label>
                            <div class="col-sm-10">
                                <select class="form-control" id="external_ad_group_id" name="external_ad_group_id" disabled>
                                    <option value="">외부 캠페인을 먼저 선택하세요</option>
                                </select>
                                <small class="form-text text-muted">내부 광고 그룹에 연결할 외부 광고 그룹을 선택하세요. (선택사항)</small>
                                <div id="adGroupLoading" class="mt-2 d-none">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="sr-only">로딩중...</span>
                                    </div>
                                    <small class="text-muted ml-2">광고 그룹 목록을 불러오는 중...</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 광고 그룹 기본 정보 -->
                <div class="form-group row">
                    <label for="name" class="col-sm-2 col-form-label">광고 그룹명 <span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="name" name="name" required maxlength="100">
                        <div class="invalid-feedback">광고 그룹명을 입력해주세요.</div>
                    </div>
                </div>
                
                <div class="form-group row">
                    <label for="status" class="col-sm-2 col-form-label">상태</label>
                    <div class="col-sm-10">
                        <select class="form-control" id="status" name="status">
                            <option value="활성" selected>활성</option>
                            <option value="일시중지">일시중지</option>
                            <option value="비활성">비활성</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group row">
                    <label for="bid_amount" class="col-sm-2 col-form-label">입찰가 (원)</label>
                    <div class="col-sm-10">
                        <div class="input-group">
                            <input type="number" class="form-control" id="bid_amount" name="bid_amount" min="0" step="10">
                            <div class="input-group-append">
                                <span class="input-group-text">원</span>
                            </div>
                        </div>
                        <small class="form-text text-muted">입찰가는 0 이상의 숫자로 입력하세요. 미입력 시 0으로 설정됩니다.</small>
                    </div>
                </div>
                
                <div class="form-group row">
                    <label for="description" class="col-sm-2 col-form-label">설명</label>
                    <div class="col-sm-10">
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="form-group row mb-0">
                    <div class="col-sm-10 offset-sm-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 광고 그룹 저장
                        </button>
                        <?php if ($campaign_id > 0): ?>
                        <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_view.php?id=<?php echo $campaign_id; ?>&tab=ad_groups" class="btn btn-secondary">
                            <i class="fas fa-times"></i> 취소
                        </a>
                        <?php else: ?>
                        <a href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_list.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> 취소
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 외부 API 연동 스크립트 -->
<script>
$(function() {
    // 폼 유효성 검사
    $('#adGroupForm').on('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        $(this).addClass('was-validated');
    });
    
    // 광고 계정 선택 시 외부 캠페인 목록 로드
    $('#ad_account_id').on('change', function() {
        var accountId = $(this).val();
        var externalCampaignSelect = $('#external_campaign_id');
        var externalAdGroupSelect = $('#external_ad_group_id');
        
        // 선택 초기화
        externalCampaignSelect.empty().append('<option value="">광고 계정을 먼저 선택하세요</option>').prop('disabled', true);
        externalAdGroupSelect.empty().append('<option value="">외부 캠페인을 먼저 선택하세요</option>').prop('disabled', true);
        
        // 캠페인 로드 조건 - 광고 계정이 선택된 경우
        if (accountId) {
            // 로딩 표시
            $('#campaignLoading').removeClass('d-none');
            
            // AJAX 요청으로 캠페인 목록 가져오기
            $.ajax({
                url: '<?php echo CF_CAMPAIGN_URL; ?>/ajax.action.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_campaigns_by_account',
                    account_id: accountId
                },
                success: function(response) {
                    if (response.success) {
                        // 외부 캠페인 목록 갱신
                        var campaigns = response.data;
                        
                        externalCampaignSelect.empty().append('<option value="">외부 캠페인 선택 (선택사항)</option>');
                        
                        if (campaigns.length > 0) {
                            $.each(campaigns, function(i, campaign) {
                                externalCampaignSelect.append('<option value="' + campaign.id + '">' + campaign.name + '</option>');
                            });
                            externalCampaignSelect.prop('disabled', false);
                        } else {
                            externalCampaignSelect.append('<option value="" disabled>사용 가능한 외부 캠페인이 없습니다</option>');
                        }
                    } else {
                        alert('캠페인 목록을 가져오는 중 오류가 발생했습니다: ' + response.message);
                    }
                },
                error: function() {
                    alert('서버 통신 중 오류가 발생했습니다.');
                },
                complete: function() {
                    // 로딩 표시 제거
                    $('#campaignLoading').addClass('d-none');
                }
            });
        }
    });
    
    // 외부 캠페인 선택 시 광고 그룹 목록 로드
    $('#external_campaign_id').on('change', function() {
        var externalCampaignId = $(this).val();
        var externalAdGroupSelect = $('#external_ad_group_id');
        
        // 선택 초기화
        externalAdGroupSelect.empty().append('<option value="">외부 광고 그룹 선택 (선택사항)</option>').prop('disabled', true);
        
        if (!externalCampaignId) {
            return;
        }
        
        // 로딩 표시
        $('#adGroupLoading').removeClass('d-none');
        
        // AJAX 요청으로 광고 그룹 목록 가져오기
        $.ajax({
            url: '<?php echo CF_CAMPAIGN_URL; ?>/ajax.action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_ad_groups_by_campaign',
                campaign_id: externalCampaignId,
                external_only: true
            },
            success: function(response) {
                if (response.success) {
                    // 광고 그룹 목록 갱신
                    var adGroups = response.data;
                    
                    if (adGroups.length > 0) {
                        $.each(adGroups, function(i, adGroup) {
                            externalAdGroupSelect.append('<option value="' + adGroup.id + '">' + adGroup.name + '</option>');
                        });
                        externalAdGroupSelect.prop('disabled', false);
                    } else {
                        externalAdGroupSelect.append('<option value="" disabled>사용 가능한 외부 광고 그룹이 없습니다</option>');
                    }
                } else {
                    alert('광고 그룹 목록을 가져오는 중 오류가 발생했습니다: ' + response.message);
                }
            },
            error: function() {
                alert('서버 통신 중 오류가 발생했습니다.');
            },
            complete: function() {
                // 로딩 표시 제거
                $('#adGroupLoading').addClass('d-none');
            }
        });
    });
    
    // 외부 광고 그룹 선택 시 광고 그룹명 자동 입력
    $('#external_ad_group_id').on('change', function() {
        var adGroupId = $(this).val();
        var nameInput = $('#name');
        
        if (!adGroupId || nameInput.val()) {
            // 이미 광고 그룹명이 입력되어 있으면 변경하지 않음
            return;
        }
        
        // 선택한 옵션의 텍스트를 광고 그룹명으로 사용
        var selectedText = $(this).find('option:selected').text();
        nameInput.val(selectedText);
    });

    // 외부 캠페인 선택 시 광고 그룹명에 캠페인명 자동 추가
    $('#external_campaign_id').on('change', function() {
        var campaignId = $(this).val();
        var nameInput = $('#name');
        
        if (!campaignId || nameInput.val()) {
            // 이미 광고 그룹명이 입력되어 있으면 변경하지 않음
            return;
        }
        
        // 선택한 옵션의 텍스트를 광고 그룹명에 반영
        var selectedText = $(this).find('option:selected').text();
        if (selectedText !== '외부 캠페인 선택 (선택사항)') {
            nameInput.val(selectedText + ' - 그룹');
        }
    });
});
</script>

<?php include_once CF_PATH . '/footer.php'; ?>