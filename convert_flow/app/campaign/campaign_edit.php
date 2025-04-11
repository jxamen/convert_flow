<?php
/**
 * 캠페인 수정 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 페이지 제목
$page_title = "캠페인 수정";

// 캠페인 모델 로드
require_once CF_MODEL_PATH . '/campaign/campaign.model.php';
$campaign_model = new CampaignModel();

// 캠페인 ID 검증
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    alert('올바른 접근이 아닙니다.', 'campaign_list.php');
    exit;
}

$campaign_id = intval($_GET['id']);
$campaign = $campaign_model->get_campaign($campaign_id);

// 캠페인이 존재하지 않거나 현재 사용자의 것이 아닌 경우
if (!$campaign || ($is_admin !== "super" && $campaign['user_id'] != $member['id'])) {
    alert('존재하지 않거나 접근 권한이 없는 캠페인입니다.', 'campaign_list.php');
    exit;
}

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
            'name' => $_POST['name'],
            'status' => $_POST['status'],
            'start_date' => $_POST['start_date'],
            'end_date' => empty($_POST['end_date']) ? null : $_POST['end_date'],
            'budget' => empty($_POST['budget']) ? 0 : $_POST['budget'],
            'cpa_goal' => empty($_POST['cpa_goal']) ? 0 : $_POST['cpa_goal'],
            'daily_budget' => empty($_POST['daily_budget']) ? 0 : $_POST['daily_budget'],
            'description' => $_POST['description']
        );
        
        // 캠페인 수정
        $result = $campaign_model->update_campaign($campaign_id, $campaign_data);
        
        if ($result) {
            // 성공 메시지와 함께 목록 페이지로 이동
            $msg = urlencode('캠페인이 성공적으로 수정되었습니다.');
            goto_url('campaign_view.php?id=' . $campaign_id . '&msg=' . $msg . '&msg_type=success');
            exit;
        } else {
            $error = '캠페인 수정 중 오류가 발생했습니다. 다시 시도해 주세요.';
        }
    }
}

// 헤더 포함
include_once CF_PATH .'/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">캠페인 수정</h1>
    <p class="mb-4">캠페인 정보를 수정하고 관리하세요.</p>
    
    <!-- 브레드크럼 -->
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="campaign_list.php">캠페인 목록</a></li>
                    <li class="breadcrumb-item"><a href="campaign_view.php?id=<?php echo $campaign_id; ?>"><?php echo $campaign['name']; ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">수정</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- 에러 메시지 표시 -->
    <?php if (!empty($error)) { ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">캠페인 정보 수정</h6>
        </div>
        <div class="card-body">
            <form method="post" action="campaign_edit.php?id=<?php echo $campaign_id; ?>">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name"><strong>캠페인 이름</strong> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo $campaign['name']; ?>" required>
                            <small class="form-text text-muted">고유하고 식별하기 쉬운 이름을 입력하세요.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status"><strong>상태</strong></label>
                            <select class="form-control" id="status" name="status">
                                <option value="활성" <?php echo ($campaign['status'] == '활성') ? 'selected' : ''; ?>>활성</option>
                                <option value="비활성" <?php echo ($campaign['status'] == '비활성') ? 'selected' : ''; ?>>비활성</option>
                                <option value="일시중지" <?php echo ($campaign['status'] == '일시중지') ? 'selected' : ''; ?>>일시중지</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_date"><strong>시작일</strong> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $campaign['start_date']; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="end_date"><strong>종료일</strong></label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $campaign['end_date']; ?>">
                            <small class="form-text text-muted">비워두면 무기한으로 설정됩니다.</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="budget"><strong>총 예산</strong></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="budget" name="budget" value="<?php echo $campaign['budget']; ?>" min="0" step="1000">
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
                                <input type="number" class="form-control" id="daily_budget" name="daily_budget" value="<?php echo $campaign['daily_budget']; ?>" min="0" step="1000">
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
                                <input type="number" class="form-control" id="cpa_goal" name="cpa_goal" value="<?php echo $campaign['cpa_goal']; ?>" min="0" step="100">
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
                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo $campaign['description']; ?></textarea>
                    <small class="form-text text-muted">캠페인에 대한 추가 정보나 메모를 입력하세요.</small>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label><strong>캠페인 생성 정보</strong></label>
                            <p class="form-control-static">
                                <small>
                                    생성일: <?php echo $campaign['created_at']; ?><br>
                                    마지막 수정일: <?php echo $campaign['updated_at']; ?><br>
                                    캠페인 해시: <?php echo $campaign['campaign_hash']; ?>
                                </small>
                            </p>
                            <div class="mt-3">
                                <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_ad_accounts.php?campaign_id=<?php echo $campaign_id; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-ad"></i> 광고 계정 관리
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-0 text-right">
                            <a href="campaign_view.php?id=<?php echo $campaign_id; ?>" class="btn btn-secondary">취소</a>
                            <button type="submit" class="btn btn-primary">저장</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// 푸터 포함
include_once CF_PATH .'/footer.php';
?>
