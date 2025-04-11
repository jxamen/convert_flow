<?php
/**
 * 전환 스크립트 수정 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 페이지 제목
$page_title = "전환 스크립트 수정";

// 모델 로드
require_once CF_MODEL_PATH . '/campaign.model.php';
require_once CF_MODEL_PATH . '/conversion.model.php';
$campaign_model = new CampaignModel();
$conversion_model = new ConversionModel();

// 스크립트 ID 검증
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    alert('올바른 접근이 아닙니다.', 'campaign_list.php');
    exit;
}

$script_id = intval($_GET['id']);
$script = $conversion_model->get_conversion_script($script_id);

// 스크립트가 존재하지 않는 경우
if (!$script) {
    alert('존재하지 않는 전환 스크립트입니다.', 'campaign_list.php');
    exit;
}

// 캠페인 정보 조회
$campaign_id = $script['campaign_id'];
$campaign = $campaign_model->get_campaign($campaign_id);

// 캠페인이 존재하지 않거나 현재 사용자의 것이 아닌 경우
if (!$campaign || ($is_admin !== "super" && $campaign['user_id'] != $member['id'])) {
    alert('존재하지 않거나 접근 권한이 없는 캠페인입니다.', 'campaign_list.php');
    exit;
}

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_script'])) {
    // 기본 정보 검증
    $conversion_type = isset($_POST['conversion_type']) ? trim($_POST['conversion_type']) : '';
    $installation_guide = isset($_POST['installation_guide']) ? trim($_POST['installation_guide']) : '';
    $script_code = isset($_POST['script_code']) ? trim($_POST['script_code']) : '';
    
    if (empty($conversion_type) || empty($script_code)) {
        alert('전환 유형과 스크립트 코드는 필수 입력 항목입니다.', '');
        exit;
    }
    
    // 스크립트 업데이트
    $update_data = array(
        'conversion_type' => $conversion_type,
        'script_code' => $script_code,
        'installation_guide' => $installation_guide,
        'updated_at' => date('Y-m-d H:i:s')
    );
    
    $result = $conversion_model->update_conversion_script($script_id, $update_data);
    
    if ($result) {
        alert('전환 스크립트가 성공적으로 업데이트되었습니다.', 'conversion_script_view.php?id=' . $script_id . '&msg=스크립트가 성공적으로 업데이트되었습니다.&msg_type=success');
        exit;
    } else {
        alert('스크립트 업데이트 중 오류가 발생했습니다. 다시 시도해주세요.', '');
        exit;
    }
}

// 전환 유형 목록
$conversion_types = array(
    '구매완료' => '구매 완료(결제)',
    '회원가입' => '회원 가입',
    '다운로드' => '다운로드',
    '문의하기' => '문의 제출',
    '기타' => '기타 전환'
);

// 헤더 포함
include_once CF_PATH .'/header.php';
?>

<div class="container-fluid">
    <!-- 페이지 제목 -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">전환 스크립트 수정</h1>
        <div>
            <a href="conversion_script_view.php?id=<?php echo $script_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> 스크립트로 돌아가기
            </a>
            <a href="<?php echo CF_ADMIN_URL; ?>/campaign/campaign_view.php?id=<?php echo $campaign_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm ml-2">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> 캠페인으로 돌아가기
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <!-- 스크립트 수정 폼 -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">스크립트 정보 수정</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="" id="scriptForm">
                        <div class="form-group row">
                            <label for="campaign_name" class="col-sm-2 col-form-label">캠페인</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="campaign_name" value="<?php echo $campaign['name']; ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="conversion_type" class="col-sm-2 col-form-label">전환 유형 <span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <select class="form-control" id="conversion_type" name="conversion_type" required>
                                    <option value="">전환 유형 선택</option>
                                    <?php foreach ($conversion_types as $value => $label) { ?>
                                        <option value="<?php echo $value; ?>" <?php echo $script['conversion_type'] === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php } ?>
                                </select>
                                <small class="form-text text-muted">전환 이벤트의 유형을 선택하세요.</small>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="script_code" class="col-sm-2 col-form-label">JavaScript 코드 <span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <textarea class="form-control code-editor" id="script_code" name="script_code" rows="12" required><?php echo htmlspecialchars($script['script_code']); ?></textarea>
                                <small class="form-text text-muted">웹사이트에 삽입될 JavaScript 코드입니다. 변환 추적을 위한 기능이 포함되어야 합니다.</small>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="installation_guide" class="col-sm-2 col-form-label">설치 안내</label>
                            <div class="col-sm-10">
                                <textarea class="form-control" id="installation_guide" name="installation_guide" rows="5"><?php echo htmlspecialchars($script['installation_guide']); ?></textarea>
                                <small class="form-text text-muted">사용자를 위한, 스크립트 설치 방법에 대한 안내 메시지를 작성하세요.</small>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <div class="col-sm-10 offset-sm-2">
                                <button type="submit" name="update_script" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 스크립트 업데이트
                                </button>
                                <a href="conversion_script_view.php?id=<?php echo $script_id; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> 취소
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 도움말 섹션 -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">전환 스크립트 작성 도움말</h6>
                </div>
                <div class="card-body">
                    <h5>스크립트 작성 지침</h5>
                    <ul>
                        <li>스크립트는 웹 페이지의 <code>&lt;head&gt;</code> 또는 <code>&lt;body&gt;</code> 태그 안에 삽입되어야 합니다.</li>
                        <li>전환 데이터는 <code>CF.trackConversion()</code> 함수를 통해 전송됩니다.</li>
                        <li>스크립트는 페이지 로드 시 자동으로 실행되거나, 특정 이벤트(예: 구매 버튼 클릭)에 연결될 수 있습니다.</li>
                        <li>스크립트에 중요한 비즈니스 로직이나 민감한 정보가 포함되지 않도록 주의하세요.</li>
                    </ul>
                    
                    <h5 class="mt-4">코드 예시</h5>
                    <div class="bg-light p-3 rounded">
                        <pre><code class="language-javascript">// 기본 추적 코드
(function(w,d,s,l,i){w[l]=w[l]||[];
    var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='cfLayer'?'&l='+l:'';
    j.async=true;j.src='<?php echo CF_URL; ?>/js/cf.js';
    f.parentNode.insertBefore(j,f);
})(window,document,'script','cfLayer','<?php echo $campaign['campaign_hash']; ?>');

// 페이지 로드 시 기본 페이지뷰 추적
cfLayer.push({event: 'pageView'});

// 전환 발생 시 호출할 함수 (예: 구매 완료 페이지에서)
function trackPurchase(orderId, value) {
    cfLayer.push({
        event: 'conversion',
        conversionType: '구매완료',
        conversionId: orderId,
        conversionValue: value
    });
}</code></pre>
                    </div>
                    
                    <h5 class="mt-4">주요 함수 및 매개변수</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>매개변수</th>
                                <th>설명</th>
                                <th>필수 여부</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>event</code></td>
                                <td>이벤트 유형 ('pageView', 'conversion')</td>
                                <td>필수</td>
                            </tr>
                            <tr>
                                <td><code>conversionType</code></td>
                                <td>전환 유형 (구매완료, 회원가입 등)</td>
                                <td>필수 (conversion 이벤트)</td>
                            </tr>
                            <tr>
                                <td><code>conversionId</code></td>
                                <td>전환의 고유 식별자 (주문번호 등)</td>
                                <td>선택</td>
                            </tr>
                            <tr>
                                <td><code>conversionValue</code></td>
                                <td>전환의 금전적 가치</td>
                                <td>선택</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo CF_ASSETS_URL; ?>/vendor/codemirror/lib/codemirror.js"></script>
<script src="<?php echo CF_ASSETS_URL; ?>/vendor/codemirror/mode/javascript/javascript.js"></script>
<script src="<?php echo CF_ASSETS_URL; ?>/vendor/codemirror/addon/edit/matchbrackets.js"></script>
<script>
$(document).ready(function() {
    // 코드 에디터 초기화
    var editor = CodeMirror.fromTextArea(document.getElementById('script_code'), {
        mode: "javascript",
        lineNumbers: true,
        matchBrackets: true,
        indentUnit: 4,
        indentWithTabs: false,
        theme: "default"
    });
    
    // 폼 제출 전 코드 에디터 내용을 textarea에 동기화
    $('#scriptForm').on('submit', function() {
        editor.save();
    });
});
</script>

<?php
// 푸터 포함
include_once CF_PATH .'/footer.php';
?>