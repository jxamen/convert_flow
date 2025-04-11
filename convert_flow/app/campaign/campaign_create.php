<?php
/**
 * 캠페인 생성 페이지
 */
 include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';


// 페이지 제목
$page_title = "캠페인 생성";

// 캠페인 모델 로드
require_once CF_MODEL_PATH . '/campaign/campaign.model.php';
$campaign_model = new CampaignModel();

// 템플릿 목록 조회
$sql = "SELECT * FROM campaign_templates 
        WHERE user_id = '{$member['id']}' 
        ORDER BY name ASC";
$template_result = sql_query($sql);

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 필수 입력 검증
    $error = '';
    
    if (empty($_POST['name'])) {
        $error = '캠페인 이름을 입력해주세요.';
    } else if (empty($_POST['start_date'])) {
        $error = '시작일을 입력해주세요.';
    }
    
    // 종료일이 시작일보다 이전인지 확인
    if (!empty($_POST['end_date']) && strtotime($_POST['end_date']) < strtotime($_POST['start_date'])) {
        $error = '종료일은 시작일 이후여야 합니다.';
    }
    
    if (empty($error)) {
        // 캠페인 데이터 구성
        $campaign_data = array(
            'user_id' => $member['id'],
            'name' => $_POST['name'],
            'status' => $_POST['status'],
            'start_date' => $_POST['start_date'],
            'end_date' => empty($_POST['end_date']) ? '' : $_POST['end_date'],
            'budget' => empty($_POST['budget']) ? 0 : $_POST['budget'],
            'cpa_goal' => empty($_POST['cpa_goal']) ? 0 : $_POST['cpa_goal'],
            'daily_budget' => empty($_POST['daily_budget']) ? 0 : $_POST['daily_budget'],
            'description' => $_POST['description']
        );
        
        // 템플릿에서 생성하는 경우 템플릿 데이터 로드
        if (!empty($_POST['template_id'])) {
            $template_id = $_POST['template_id'];
            $sql = "SELECT * FROM campaign_templates WHERE id = '$template_id'";
            $template = sql_fetch($sql);
            
            if ($template) {
                $template_data = json_decode($template['template_data'], true);
                
                // 템플릿 데이터 적용
                if (empty($campaign_data['daily_budget']) && !empty($template['daily_budget'])) {
                    $campaign_data['daily_budget'] = $template['daily_budget'];
                }
                
                if (empty($campaign_data['cpa_goal']) && !empty($template['cpa_goal'])) {
                    $campaign_data['cpa_goal'] = $template['cpa_goal'];
                }
                
                // 기타 템플릿 설정 적용
                if (!empty($template_data)) {
                    // 여기에 추가 템플릿 설정을 적용할 수 있습니다.
                }
            }
        }
        
        // 캠페인 생성
        $result = $campaign_model->create_campaign($campaign_data);
        
        if ($result) {
            // 성공 메시지와 함께 상세 페이지로 이동
            $msg = urlencode('캠페인이 성공적으로 생성되었습니다.');
            goto_url('campaign_view.php?id=' . $result . '&msg=' . $msg . '&msg_type=success');
            exit;
        } else {
            $error = '캠페인 생성 중 오류가 발생했습니다. 다시 시도해 주세요.';
        }
    }
}

// 헤더 포함
include_once CF_PATH .'/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">캠페인 생성</h1>
    <p class="mb-4">새로운 마케팅 캠페인을 생성하고 관리하세요.</p>

    <!-- 에러 메시지 표시 -->
    <?php if (!empty($error)) { ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">캠페인 정보 입력</h6>
        </div>
        <div class="card-body">
            <form method="post" action="campaign_create.php">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name"><strong>캠페인 이름</strong> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_POST['name']) ? $_POST['name'] : ''; ?>" required>
                            <small class="form-text text-muted">고유하고 식별하기 쉬운 이름을 입력하세요.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status"><strong>상태</strong></label>
                            <select class="form-control" id="status" name="status">
                                <option value="활성" <?php echo (isset($_POST['status']) && $_POST['status'] == '활성') ? 'selected' : ''; ?>>활성</option>
                                <option value="비활성" <?php echo (isset($_POST['status']) && $_POST['status'] == '비활성') ? 'selected' : ''; ?>>비활성</option>
                                <option value="일시중지" <?php echo (isset($_POST['status']) && $_POST['status'] == '일시중지') ? 'selected' : ''; ?>>일시중지</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_date"><strong>시작일</strong> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="end_date"><strong>종료일</strong></label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo isset($_POST['end_date']) ? $_POST['end_date'] : ''; ?>">
                            <small class="form-text text-muted">비워두면 무기한으로 설정됩니다.</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="budget"><strong>총 예산</strong></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="budget" name="budget" value="<?php echo isset($_POST['budget']) ? $_POST['budget'] : ''; ?>" min="0" step="1000">
                                <div class="input-group-append">
                                    <span class="input-group-text">원</span>
                                </div>
                            </div>
                            <small class="form-text text-muted">캠페인의 총 예산을 입력하세요.</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="daily_budget"><strong>일일 예산</strong></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="daily_budget" name="daily_budget" value="<?php echo isset($_POST['daily_budget']) ? $_POST['daily_budget'] : ''; ?>" min="0" step="1000">
                                <div class="input-group-append">
                                    <span class="input-group-text">원</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="cpa_goal"><strong>목표 CPA</strong></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="cpa_goal" name="cpa_goal" value="<?php echo isset($_POST['cpa_goal']) ? $_POST['cpa_goal'] : ''; ?>" min="0" step="100">
                                <div class="input-group-append">
                                    <span class="input-group-text">원</span>
                                </div>
                            </div>
                            <small class="form-text text-muted">전환당 목표 비용을 입력하세요.</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description"><strong>설명</strong></label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo isset($_POST['description']) ? $_POST['description'] : ''; ?></textarea>
                    <small class="form-text text-muted">캠페인에 대한 추가 정보나 메모를 입력하세요.</small>
                </div>

                <?php if (sql_num_rows($template_result) > 0) { ?>
                <div class="form-group">
                    <label for="template_id"><strong>템플릿 사용</strong></label>
                    <select class="form-control" id="template_id" name="template_id">
                        <option value="">템플릿 사용하지 않음</option>
                        <?php while ($template = sql_fetch_array($template_result)) { ?>
                        <option value="<?php echo $template['id']; ?>" <?php echo (isset($_POST['template_id']) && $_POST['template_id'] == $template['id']) ? 'selected' : ''; ?>>
                            <?php echo $template['name']; ?>
                        </option>
                        <?php } ?>
                    </select>
                    <small class="form-text text-muted">저장된 템플릿을 사용하면 설정 값이 자동으로 적용됩니다.</small>
                </div>
                <?php } ?>

                <div class="form-group mb-0 text-right">
                    <a href="campaign_list.php" class="btn btn-secondary">취소</a>
                    <button type="submit" class="btn btn-primary">캠페인 생성</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// 푸터 포함
include_once CF_PATH .'/footer.php';
?>
