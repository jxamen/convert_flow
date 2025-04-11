<?php
/**
 * 캠페인 추가 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 페이지 제목
$page_title = "새 캠페인 만들기";

// 권한 체크
if (!$is_member) {
    alert('로그인이 필요한 서비스입니다.', CF_URL . '/login.php');
}

// 캠페인 모델 로드
include_once CF_MODEL_PATH . '/campaign/campaign.model.php';
$campaign_model = new CampaignModel();

// 캠페인 추가 처리
$w = '';
if (isset($_POST['campaign_add']) && $_POST['campaign_add']) {
    // 캠페인 데이터 구성
    $campaign_data = array(
        'user_id' => $member['id'],
        'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
        'status' => isset($_POST['status']) ? trim($_POST['status']) : '활성',
        'start_date' => isset($_POST['start_date']) ? trim($_POST['start_date']) : date('Y-m-d'),
        'end_date' => isset($_POST['end_date']) && !empty($_POST['end_date']) ? trim($_POST['end_date']) : null,
        'budget' => isset($_POST['budget']) ? floatval($_POST['budget']) : 0,
        'daily_budget' => isset($_POST['daily_budget']) ? floatval($_POST['daily_budget']) : 0,
        'cpa_goal' => isset($_POST['cpa_goal']) ? floatval($_POST['cpa_goal']) : 0,
        'description' => isset($_POST['description']) ? trim($_POST['description']) : ''
    );
    
    // 필수 입력 체크
    if (empty($campaign_data['name'])) {
        alert('캠페인 이름을 입력해주세요.');
    }
    
    if (empty($campaign_data['start_date'])) {
        alert('시작 날짜를 입력해주세요.');
    }
    
    // 캠페인 추가
    $campaign_id = $campaign_model->add_campaign($campaign_data);
    
    if ($campaign_id) {
        // 성공 메시지와 함께 리다이렉트
        alert('캠페인이 추가되었습니다.', CF_CAMPAIGN_URL . '/campaign_view.php?id=' . $campaign_id);
    } else {
        alert('캠페인 추가 중 오류가 발생했습니다.');
    }
}

// 템플릿 목록 가져오기
$templates = $campaign_model->get_campaign_templates($member['id']);

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <!-- 페이지 제목 -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">새 캠페인 만들기</h1>
        <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_list.php" class="d-none d-sm-inline-block btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> 캠페인 목록
        </a>
    </div>

    <!-- 캠페인 추가 폼 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">캠페인 정보 입력</h6>
        </div>
        <div class="card-body">
            <form id="campaignForm" method="post" action="">
                <input type="hidden" name="campaign_add" value="1">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name"><strong>캠페인 이름</strong> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="캠페인 이름을 입력하세요" required>
                            <small class="form-text text-muted">고유한 이름으로 캠페인을 식별합니다.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="status"><strong>캠페인 상태</strong></label>
                            <select class="form-control" id="status" name="status">
                                <option value="활성">활성</option>
                                <option value="일시중지">일시중지</option>
                                <option value="비활성">비활성</option>
                            </select>
                            <small class="form-text text-muted">캠페인의 현재 상태를 설정합니다.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="start_date"><strong>시작 날짜</strong> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                            <small class="form-text text-muted">캠페인이 시작되는 날짜입니다.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date"><strong>종료 날짜</strong></label>
                            <input type="date" class="form-control" id="end_date" name="end_date">
                            <small class="form-text text-muted">캠페인이 종료되는 날짜입니다. 비워두면 무기한으로 설정됩니다.</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="budget"><strong>전체 예산</strong></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="budget" name="budget" placeholder="0" min="0" step="1000">
                                <div class="input-group-append">
                                    <span class="input-group-text">원</span>
                                </div>
                            </div>
                            <small class="form-text text-muted">이 캠페인의 전체 예산입니다. 0으로 설정하면 제한이 없습니다.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="daily_budget"><strong>일일 예산</strong></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="daily_budget" name="daily_budget" placeholder="0" min="0" step="1000">
                                <div class="input-group-append">
                                    <span class="input-group-text">원</span>
                                </div>
                            </div>
                            <small class="form-text text-muted">이 캠페인의 일일 예산입니다. 0으로 설정하면 제한이 없습니다.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="cpa_goal"><strong>목표 고객 획득 비용 (CPA)</strong></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="cpa_goal" name="cpa_goal" placeholder="0" min="0" step="100">
                                <div class="input-group-append">
                                    <span class="input-group-text">원</span>
                                </div>
                            </div>
                            <small class="form-text text-muted">전환당 목표 비용입니다. 자동 입찰 전략에 사용됩니다.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="description"><strong>캠페인 설명</strong></label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="캠페인에 대한 설명을 입력하세요"></textarea>
                            <small class="form-text text-muted">이 캠페인의 목적, 대상 등에 대한 메모를 남겨두세요.</small>
                        </div>
                    </div>
                </div>
                
                <!-- 템플릿 선택 -->
                <?php if (!empty($templates)) { ?>
                <div class="border-top pt-3 mt-3">
                    <h5 class="mb-3">템플릿 사용하기</h5>
                    <div class="form-group">
                        <label for="template_id">저장된 템플릿에서 설정 불러오기</label>
                        <select class="form-control" id="template_id">
                            <option value="">템플릿 선택...</option>
                            <?php foreach ($templates as $template) { ?>
                            <option value="<?php echo $template['id']; ?>"><?php echo $template['name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <?php } ?>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> 캠페인 만들기</button>
                    <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_list.php" class="btn btn-secondary btn-lg ml-2">취소</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 폼 유효성 검사
    $("#campaignForm").on("submit", function(e) {
        var name = $("#name").val().trim();
        var start_date = $("#start_date").val();
        
        if (name === "") {
            alert("캠페인 이름을 입력해주세요.");
            $("#name").focus();
            e.preventDefault();
            return false;
        }
        
        if (start_date === "") {
            alert("시작 날짜를 입력해주세요.");
            $("#start_date").focus();
            e.preventDefault();
            return false;
        }
        
        return true;
    });
    
    // 템플릿 선택 시 폼 채우기
    $("#template_id").on("change", function() {
        var templateId = $(this).val();
        
        if (templateId === "") {
            return;
        }
        
        // AJAX로 템플릿 정보 가져오기
        $.ajax({
            url: "<?php echo CF_CAMPAIGN_URL; ?>/ajax.action.php",
            type: "POST",
            data: {
                action: "get_campaign_template",
                template_id: templateId
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    var template = response.template;
                    
                    // 폼 필드 채우기
                    if (template.target_region) {
                        $("#target_region").val(template.target_region);
                    }
                    
                    if (template.daily_budget) {
                        $("#daily_budget").val(template.daily_budget);
                    }
                    
                    if (template.cpa_goal) {
                        $("#cpa_goal").val(template.cpa_goal);
                    }
                    
                    // 추가 필드가 있다면 여기에 추가
                    
                } else {
                    alert("템플릿 정보를 가져오는 데 실패했습니다: " + response.message);
                }
            },
            error: function() {
                alert("서버 통신 오류가 발생했습니다.");
            }
        });
    });
    
    // 날짜 유효성 검사
    $("#end_date").on("change", function() {
        var startDate = new Date($("#start_date").val());
        var endDate = new Date($(this).val());
        
        if (endDate < startDate) {
            alert("종료 날짜는 시작 날짜 이후여야 합니다.");
            $(this).val("");
        }
    });
    
    $("#start_date").on("change", function() {
        var startDate = new Date($(this).val());
        var endDate = new Date($("#end_date").val());
        
        if ($("#end_date").val() !== "" && endDate < startDate) {
            alert("시작 날짜는 종료 날짜 이전이어야 합니다.");
            $("#end_date").val("");
        }
    });
});
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>