<?php
/**
 * 랜딩페이지 전환 설정 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 랜딩페이지 ID
$landing_id = isset($_GET['landing_id']) ? intval($_GET['landing_id']) : 0;
if ($landing_id <= 0) {
    alert('잘못된 접근입니다.', CF_LANDING_URL . '/landing_list.php');
}

// 광고 계정 모델 로드
require_once CF_MODEL_PATH . '/ad_account.model.php';
$ad_account_model = new AdAccountModel();

// 랜딩페이지 모델 로드
require_once CF_MODEL_PATH . '/landing.model.php';
$landing_model = new LandingModel();

// 랜딩페이지 정보 조회
$landing = $landing_model->get_landing($landing_id);
if (!$landing) {
    alert('존재하지 않는 랜딩페이지입니다.', CF_LANDING_URL . '/landing_list.php');
}

// 권한 체크 (관리자가 아니면서 소유자가 아닌 경우)
if (!$is_admin && $landing['user_id'] != $member['id']) {
    alert('권한이 없습니다.', CF_LANDING_URL . '/landing_list.php');
}

// 캠페인 정보 조회
$campaign = array();
if ($landing['campaign_id']) {
    require_once CF_MODEL_PATH . '/campaign.model.php';
    $campaign_model = new CampaignModel();
    $campaign = $campaign_model->get_campaign($landing['campaign_id']);
}

// 랜딩페이지에 설정된 전환 이벤트 조회
$landing_events = $ad_account_model->get_landing_conversions($landing_id);

// 전환 이벤트 목록 조회
$events = $ad_account_model->get_conversion_events();

// 랜딩페이지의 HTML 요소 분석 (샘플)
$elements = array();
if (!empty($landing['html_content'])) {
    preg_match_all('/<(a|button|form)[^>]*id=["\'](.*?)["\']/i', $landing['html_content'], $id_matches);
    preg_match_all('/<(a|button|form)[^>]*class=["\'](.*?)["\']/i', $landing['html_content'], $class_matches);
    
    // ID 속성 요소 추가
    for ($i = 0; $i < count($id_matches[0]); $i++) {
        $elements[] = array(
            'type' => $id_matches[1][$i],
            'selector' => '#' . $id_matches[2][$i],
            'description' => ucfirst($id_matches[1][$i]) . ' with ID: ' . $id_matches[2][$i]
        );
    }
    
    // Class 속성 요소 추가
    for ($i = 0; $i < count($class_matches[0]); $i++) {
        $classes = explode(' ', $class_matches[2][$i]);
        foreach ($classes as $class) {
            if (trim($class)) {
                $elements[] = array(
                    'type' => $class_matches[1][$i],
                    'selector' => '.' . trim($class),
                    'description' => ucfirst($class_matches[1][$i]) . ' with class: ' . trim($class)
                );
            }
        }
    }
}

// 페이지 제목
$page_title = "전환 설정: " . $landing['name'];

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">전환 설정: <?php echo htmlspecialchars($landing['name']); ?></h1>
    <p class="mb-4">
        이 랜딩페이지에서 발생하는 전환 이벤트를 설정합니다. 설정된 이벤트는 연결된 광고 계정에 전환 정보를 전송합니다.
        <a href="<?php echo CF_LANDING_URL; ?>/landing_edit.php?id=<?php echo $landing_id; ?>" class="btn btn-sm btn-primary ml-2">
            <i class="fas fa-arrow-left"></i> 랜딩페이지로 돌아가기
        </a>
    </p>
    
    <?php if (empty($campaign)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> 이 랜딩페이지는 캠페인에 연결되어 있지 않습니다. 전환 이벤트를 설정하려면 먼저 캠페인에 연결하세요.
    </div>
    <?php elseif (empty($landing['html_content'])): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> 이 랜딩페이지에 HTML 콘텐츠가 없습니다. 콘텐츠를 추가한 후 전환 이벤트를 설정하세요.
    </div>
    <?php else: ?>
    <!-- 전환 이벤트 설정 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">전환 이벤트 설정</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="eventsTable">
                    <thead>
                        <tr>
                            <th width="20%">이벤트</th>
                            <th width="20%">트리거 유형</th>
                            <th width="30%">트리거 요소</th>
                            <th width="20%">상태</th>
                            <th width="10%">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): 
                            // 이 이벤트에 대한 설정 찾기
                            $event_setting = null;
                            foreach ($landing_events as $le) {
                                if ($le['event_id'] == $event['id']) {
                                    $event_setting = $le;
                                    break;
                                }
                            }
                            
                            $is_enabled = ($event_setting !== null);
                            $trigger_action = $is_enabled ? $event_setting['trigger_action'] : 'pageview';
                            $trigger_element = $is_enabled ? $event_setting['trigger_element'] : '';
                        ?>
                        <tr data-event-id="<?php echo $event['id']; ?>" data-event-code="<?php echo $event['event_code']; ?>">
                            <td>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input event-toggle" id="event_<?php echo $event['id']; ?>" <?php echo $is_enabled ? 'checked' : ''; ?>>
                                    <label class="custom-control-label font-weight-bold" for="event_<?php echo $event['id']; ?>"><?php echo htmlspecialchars($event['name']); ?></label>
                                </div>
                                <small class="text-muted d-block mt-1"><?php echo htmlspecialchars($event['description']); ?></small>
                            </td>
                            <td>
                                <select class="form-control event-trigger" <?php echo !$is_enabled ? 'disabled' : ''; ?>>
                                    <option value="pageview" <?php echo $trigger_action === 'pageview' ? 'selected' : ''; ?>>페이지 로드 시</option>
                                    <option value="submit" <?php echo $trigger_action === 'submit' ? 'selected' : ''; ?>>폼 제출 시</option>
                                    <option value="click" <?php echo $trigger_action === 'click' ? 'selected' : ''; ?>>요소 클릭 시</option>
                                    <option value="scroll" <?php echo $trigger_action === 'scroll' ? 'selected' : ''; ?>>스크롤 시</option>
                                    <option value="custom" <?php echo $trigger_action === 'custom' ? 'selected' : ''; ?>>사용자 정의</option>
                                </select>
                            </td>
                            <td>
                                <div class="element-select-container" <?php echo ($trigger_action !== 'click' && $trigger_action !== 'submit') ? 'style="display:none"' : ''; ?>>
                                    <select class="form-control element-select" <?php echo !$is_enabled ? 'disabled' : ''; ?>>
                                        <option value="">선택하세요</option>
                                        <?php foreach ($elements as $element): ?>
                                        <option value="<?php echo htmlspecialchars($element['selector']); ?>" <?php echo $trigger_element === $element['selector'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($element['description']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                        <option value="custom" <?php echo !in_array($trigger_element, array_column($elements, 'selector')) && $trigger_element ? 'selected' : ''; ?>>직접 입력</option>
                                    </select>
                                    <div class="custom-element-container mt-2" <?php echo (!in_array($trigger_element, array_column($elements, 'selector')) && $trigger_element) ? '' : 'style="display:none"'; ?>>
                                        <input type="text" class="form-control custom-element" value="<?php echo !in_array($trigger_element, array_column($elements, 'selector')) ? htmlspecialchars($trigger_element) : ''; ?>" placeholder="CSS 선택자 입력 (예: #submit-button, .form-submit)">
                                    </div>
                                </div>
                                
                                <div class="scroll-options" <?php echo $trigger_action !== 'scroll' ? 'style="display:none"' : ''; ?>>
                                    <div class="input-group">
                                        <input type="number" class="form-control scroll-depth" value="<?php echo $trigger_action === 'scroll' ? (int)$trigger_element : 50; ?>" min="1" max="100">
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="custom-script-container mt-2" <?php echo $trigger_action !== 'custom' ? 'style="display:none"' : ''; ?>>
                                    <textarea class="form-control custom-script" rows="3" placeholder="자바스크립트 코드 입력"><?php echo $trigger_action === 'custom' ? htmlspecialchars($event_setting['custom_script']) : ''; ?></textarea>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?php echo $is_enabled ? 'badge-success' : 'badge-secondary'; ?>">
                                    <?php echo $is_enabled ? '활성' : '비활성'; ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger remove-event" <?php echo !$is_enabled ? 'disabled' : ''; ?>>
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="text-center mt-4">
                <button type="button" id="saveConversions" class="btn btn-primary">
                    <i class="fas fa-save"></i> 설정 저장
                </button>
            </div>
        </div>
    </div>
    
    <!-- 전환 스크립트 미리보기 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">전환 스크립트 미리보기</h6>
        </div>
        <div class="card-body">
            <p class="text-muted">이 랜딩페이지에 자동으로 삽입되는 전환 스크립트입니다. 연결된 광고 계정의 스크립트가 포함됩니다.</p>
            
            <div class="card bg-light mt-3">
                <div class="card-body">
                    <pre class="mb-0" style="max-height: 300px; overflow-y: auto;"><code id="scriptPreview">// 전환 스크립트를 생성 중입니다...</code></pre>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <button class="btn btn-sm btn-info" id="refreshScript">
                    <i class="fas fa-sync-alt"></i> 스크립트 새로고침
                </button>
                <button class="btn btn-sm btn-info copy-script" data-toggle="tooltip" title="클립보드에 복사">
                    <i class="fas fa-copy"></i> 스크립트 복사
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // 이벤트 활성화 토글 처리
    $('.event-toggle').change(function() {
        var $row = $(this).closest('tr');
        var isEnabled = $(this).prop('checked');
        
        // 트리거 선택 필드 활성화/비활성화
        $row.find('select, input, textarea').prop('disabled', !isEnabled);
        
        // 상태 배지 업데이트
        var $badge = $row.find('.badge');
        if (isEnabled) {
            $badge.removeClass('badge-secondary').addClass('badge-success').text('활성');
            $row.find('.remove-event').prop('disabled', false);
        } else {
            $badge.removeClass('badge-success').addClass('badge-secondary').text('비활성');
            $row.find('.remove-event').prop('disabled', true);
        }
    });
    
    // 트리거 유형 변경 시 추가 설정 표시
    $('.event-trigger').change(function() {
        var $row = $(this).closest('tr');
        var triggerType = $(this).val();
        
        // 요소 선택 컨테이너 표시/숨김
        var $elementContainer = $row.find('.element-select-container');
        var $scrollOptions = $row.find('.scroll-options');
        var $customScriptContainer = $row.find('.custom-script-container');
        
        $elementContainer.hide();
        $scrollOptions.hide();
        $customScriptContainer.hide();
        
        if (triggerType === 'click' || triggerType === 'submit') {
            $elementContainer.show();
        } else if (triggerType === 'scroll') {
            $scrollOptions.show();
        } else if (triggerType === 'custom') {
            $customScriptContainer.show();
        }
    });
    
    // 요소 선택 변경 시 커스텀 입력 표시
    $('.element-select').change(function() {
        var $container = $(this).closest('.element-select-container');
        var $customContainer = $container.find('.custom-element-container');
        
        if ($(this).val() === 'custom') {
            $customContainer.show();
        } else {
            $customContainer.hide();
        }
    });
    
    // 이벤트 제거 버튼 클릭 시
    $('.remove-event').click(function() {
        var $row = $(this).closest('tr');
        $row.find('.event-toggle').prop('checked', false).trigger('change');
    });
    
    // 전환 설정 저장
    $('#saveConversions').click(function() {
        var events = [];
        
        // 활성화된 모든 이벤트 정보 수집
        $('.event-toggle:checked').each(function() {
            var $row = $(this).closest('tr');
            var eventId = $row.data('event-id');
            var triggerAction = $row.find('.event-trigger').val();
            var triggerElement = '';
            var customScript = '';
            
            if (triggerAction === 'click' || triggerAction === 'submit') {
                var $elementSelect = $row.find('.element-select');
                if ($elementSelect.val() === 'custom') {
                    triggerElement = $row.find('.custom-element').val();
                } else {
                    triggerElement = $elementSelect.val();
                }
            } else if (triggerAction === 'scroll') {
                triggerElement = $row.find('.scroll-depth').val();
            } else if (triggerAction === 'custom') {
                customScript = $row.find('.custom-script').val();
            }
            
            events.push({
                event_id: eventId,
                trigger_action: triggerAction,
                trigger_element: triggerElement,
                custom_script: customScript
            });
        });
        
        // AJAX로 설정 저장
        $.ajax({
            url: 'ad_account_ajax.php',
            type: 'POST',
            data: {
                action: 'save_landing_conversions',
                landing_id: <?php echo $landing_id; ?>,
                events: JSON.stringify(events)
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('전환 설정이 저장되었습니다.');
                    // 스크립트 새로고침
                    refreshScript();
                } else {
                    alert('전환 설정 저장 중 오류가 발생했습니다: ' + response.message);
                }
            },
            error: function() {
                alert('서버 오류가 발생했습니다.');
            }
        });
    });
    
    // 전환 스크립트 새로고침
    function refreshScript() {
        $.ajax({
            url: 'ad_account_ajax.php',
            type: 'POST',
            data: {
                action: 'get_landing_script',
                landing_id: <?php echo $landing_id; ?>
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#scriptPreview').text(response.script);
                } else {
                    $('#scriptPreview').text('// ' + response.message);
                }
            },
            error: function() {
                $('#scriptPreview').text('// 서버 오류가 발생했습니다.');
            }
        });
    }
    
    // 새로고침 버튼 클릭 시
    $('#refreshScript').click(refreshScript);
    
    // 전환 스크립트 복사 기능
    $('.copy-script').click(function() {
        var scriptCode = $('#scriptPreview').text();
        
        // 클립보드에 복사
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(scriptCode).select();
        document.execCommand('copy');
        $temp.remove();
        
        // 툴팁 업데이트
        $(this).attr('title', '복사 완료!')
            .tooltip('dispose')
            .tooltip('show');
        
        // 2초 후 툴팁 복원
        var $btn = $(this);
        setTimeout(function() {
            $btn.attr('title', '클립보드에 복사')
               .tooltip('dispose')
               .tooltip();
       }, 2000);
   });
   
   // 툴팁 초기화
   $('[data-toggle="tooltip"]').tooltip();
   
   // 페이지 로드 시 스크립트 로드
   refreshScript();
});
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>