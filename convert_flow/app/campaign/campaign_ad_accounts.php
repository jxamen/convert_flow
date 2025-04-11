<?php
/**
 * 캠페인 광고 계정 설정 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 캠페인 ID
$campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
if ($campaign_id <= 0) {
    alert('잘못된 접근입니다.', CF_CAMPAIGN_URL . '/campaign_list.php');
}

// 사용자 광고 계정 모델 로드
require_once CF_MODEL_PATH . '/ad_account.model.php';
$ad_account_model = new AdAccountModel();

// 캠페인 모델 로드
require_once CF_MODEL_PATH . '/campaign.model.php';
$campaign_model = new CampaignModel();

// 캠페인 정보 조회
$campaign = $campaign_model->get_campaign($campaign_id);
if (!$campaign) {
    alert('존재하지 않는 캠페인입니다.', CF_CAMPAIGN_URL . '/campaign_list.php');
}

// 권한 체크 (관리자가 아니면서 캠페인 소유자가 아닌 경우)
if (!$is_admin && $campaign['user_id'] != $member['id']) {
    alert('권한이 없습니다.', CF_CAMPAIGN_URL . '/campaign_list.php');
}

// 액션 처리
$action = isset($_GET['action']) ? $_GET['action'] : '';
$account_id = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;

if ($action === 'add' && isset($_POST['submit'])) {
    // 계정 연결 추가 처리
    $user_ad_account_id = isset($_POST['user_ad_account_id']) ? intval($_POST['user_ad_account_id']) : 0;
    $custom_settings = isset($_POST['custom_settings']) ? json_encode($_POST['custom_settings']) : '{}';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $result = $ad_account_model->add_campaign_account($campaign_id, $user_ad_account_id, $custom_settings, $is_active);
    if ($result) {
        alert('광고 계정이 연결되었습니다.', 'campaign_ad_accounts.php?campaign_id=' . $campaign_id);
    } else {
        alert('광고 계정 연결에 실패했습니다.', 'campaign_ad_accounts.php?campaign_id=' . $campaign_id . '&action=add');
    }
} else if ($action === 'edit' && isset($_POST['submit'])) {
    // 계정 연결 수정 처리
    $custom_settings = isset($_POST['custom_settings']) ? json_encode($_POST['custom_settings']) : '{}';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $result = $ad_account_model->update_campaign_account($account_id, $custom_settings, $is_active);
    if ($result) {
        alert('광고 계정 설정이 업데이트되었습니다.', 'campaign_ad_accounts.php?campaign_id=' . $campaign_id);
    } else {
        alert('광고 계정 설정 업데이트에 실패했습니다.', 'campaign_ad_accounts.php?campaign_id=' . $campaign_id . '&action=edit&account_id=' . $account_id);
    }
} else if ($action === 'delete') {
    // 계정 연결 삭제 처리
    $result = $ad_account_model->delete_campaign_account($account_id);
    if ($result) {
        alert('광고 계정 연결이 삭제되었습니다.', 'campaign_ad_accounts.php?campaign_id=' . $campaign_id);
    } else {
        alert('광고 계정 연결 삭제에 실패했습니다.', 'campaign_ad_accounts.php?campaign_id=' . $campaign_id);
    }
}

// 사용자 광고 계정 목록 조회
$user_accounts = $ad_account_model->get_user_accounts($campaign['user_id']);

// 이미 연결된 계정 ID 목록
$connected_account_ids = $ad_account_model->get_connected_account_ids($campaign_id);

// 캠페인 광고 계정 목록 조회
$campaign_accounts = $ad_account_model->get_campaign_accounts($campaign_id);

// 페이지 제목
$page_title = "캠페인 광고 계정 설정: " . $campaign['name'];

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">광고 계정 설정: <?php echo htmlspecialchars($campaign['name']); ?></h1>
    <p class="mb-4">
        이 캠페인에서 사용할 광고 계정을 관리합니다. 캠페인별 설정은 전역 설정보다 우선 적용됩니다.
        <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_edit.php?id=<?php echo $campaign_id; ?>" class="btn btn-sm btn-primary ml-2">
            <i class="fas fa-arrow-left"></i> 캠페인으로 돌아가기
        </a>
    </p>

    <?php if ($action === 'add'): ?>
    <!-- 계정 연결 추가 폼 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">새 광고 계정 연결</h6>
        </div>
        <div class="card-body">
            <form method="post" action="campaign_ad_accounts.php?campaign_id=<?php echo $campaign_id; ?>&action=add">
                <div class="form-group">
                    <label for="user_ad_account_id">광고 계정 선택</label>
                    <select class="form-control" id="user_ad_account_id" name="user_ad_account_id" required>
                        <option value="">계정 선택</option>
                        <?php foreach ($user_accounts as $account): ?>
                            <?php if (!in_array($account['id'], $connected_account_ids)): ?>
                            <option value="<?php echo $account['id']; ?>" data-platform="<?php echo $account['platform_code']; ?>">
                                <?php echo htmlspecialchars($account['platform_name'] . ' - ' . $account['account_name']); ?>
                            </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <?php if (count($user_accounts) === count($connected_account_ids)): ?>
                    <small class="form-text text-warning">모든 광고 계정이 이미 연결되어 있습니다. 새 계정을 추가하려면 <a href="ad_accounts.php?action=add" target="_blank">광고 계정 관리</a>에서 등록하세요.</small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                        <label class="custom-control-label" for="is_active">활성화</label>
                    </div>
                    <small class="form-text text-muted">비활성화하면 이 캠페인에서 해당 계정의 전환 스크립트가 생성되지 않습니다.</small>
                </div>
                
                <div id="custom_settings_container" class="mt-4">
                    <!-- 계정별 커스텀 설정은 JavaScript로 동적 생성 -->
                </div>
                
                <div class="text-center mt-4">
                    <a href="campaign_ad_accounts.php?campaign_id=<?php echo $campaign_id; ?>" class="btn btn-secondary">취소</a>
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 계정 연결
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php elseif ($action === 'edit' && $account_id > 0): ?>
    <!-- 계정 연결 수정 폼 -->
    <?php
    $campaign_account = $ad_account_model->get_campaign_account($account_id);
    if (!$campaign_account || $campaign_account['campaign_id'] != $campaign_id) {
        alert('존재하지 않는 계정 연결이거나 권한이 없습니다.', 'campaign_ad_accounts.php?campaign_id=' . $campaign_id);
    }
    $user_account = $ad_account_model->get_account_with_platform($campaign_account['user_ad_account_id']);
    ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">광고 계정 설정 편집: <?php echo htmlspecialchars($user_account['account_name']); ?></h6>
        </div>
        <div class="card-body">
            <form method="post" action="campaign_ad_accounts.php?campaign_id=<?php echo $campaign_id; ?>&action=edit&account_id=<?php echo $account_id; ?>">
                <div class="form-group">
                    <label>광고 플랫폼</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_account['platform_name']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>계정 이름</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_account['account_name']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>추적 ID</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_account['tracking_id']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" <?php echo $campaign_account['is_active'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="is_active">활성화</label>
                    </div>
                    <small class="form-text text-muted">비활성화하면 이 캠페인에서 해당 계정의 전환 스크립트가 생성되지 않습니다.</small>
                </div>
                
                <div id="custom_settings_container" class="mt-4" data-platform="<?php echo $user_account['platform_code']; ?>" data-settings='<?php echo htmlspecialchars($campaign_account['custom_settings']); ?>'>
                    <!-- 계정별 커스텀 설정은 JavaScript로 동적 생성 -->
                </div>
                
                <div class="text-center mt-4">
                    <a href="campaign_ad_accounts.php?campaign_id=<?php echo $campaign_id; ?>" class="btn btn-secondary">취소</a>
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 설정 저장
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php else: ?>
    <!-- 연결된 계정 목록 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">연결된 광고 계정</h6>
            <a href="campaign_ad_accounts.php?campaign_id=<?php echo $campaign_id; ?>&action=add" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> 계정 연결
            </a>
        </div>
        <div class="card-body">
            <?php if (count($campaign_accounts) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>플랫폼</th>
                            <th>계정 이름</th>
                            <th>추적 ID</th>
                            <th>상태</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaign_accounts as $account): ?>
                        <tr>
                            <td>
                                <?php if (!empty($account['platform_icon'])): ?>
                                <img src="<?php echo $account['platform_icon']; ?>" alt="<?php echo htmlspecialchars($account['platform_name']); ?>" width="20" height="20" class="mr-1">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($account['platform_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                            <td><?php echo htmlspecialchars($account['tracking_id']); ?></td>
                            <td>
                                <span class="badge <?php echo $account['is_active'] ? 'badge-success' : 'badge-secondary'; ?>">
                                    <?php echo $account['is_active'] ? '활성' : '비활성'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="campaign_ad_accounts.php?campaign_id=<?php echo $campaign_id; ?>&action=edit&account_id=<?php echo $account['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> 설정
                                </a>
                                <a href="campaign_ad_accounts.php?campaign_id=<?php echo $campaign_id; ?>&action=delete&account_id=<?php echo $account['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('정말 이 계정 연결을 삭제하시겠습니까?');">
                                    <i class="fas fa-unlink"></i> 연결 해제
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 전환 스크립트 미리보기 -->
            <div class="mt-5">
                <h5 class="text-primary font-weight-bold">생성된 전환 스크립트</h5>
                <p class="text-muted">이 캠페인의 랜딩페이지에 삽입될 전환 스크립트입니다. 이 스크립트는 자동으로 랜딩페이지의 &lt;head&gt; 태그 내에 삽입됩니다.</p>
                
                <div class="card bg-light mt-3">
                    <div class="card-body">
                        <pre class="mb-0" style="max-height: 300px; overflow-y: auto;"><code><?php echo htmlspecialchars($ad_account_model->generate_conversion_scripts($campaign_id)); ?></code></pre>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <button class="btn btn-sm btn-info copy-script" data-toggle="tooltip" title="클립보드에 복사">
                        <i class="fas fa-copy"></i> 스크립트 복사
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div class="text-center p-5">
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle"></i> 연결된 광고 계정이 없습니다. 계정을 연결해 주세요.
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 이벤트 설정 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">전환 이벤트 설정</h6>
        </div>
        <div class="card-body">
            <p class="text-muted">캠페인의 랜딩페이지에서 발생할 수 있는 전환 이벤트를 설정합니다. 이 설정은 모든 연결된 광고 계정에 적용됩니다.</p>
            
            <!-- 이벤트 목록 및 설정 영역 -->
            <div class="table-responsive">
                <table class="table table-bordered" id="eventsTable">
                    <thead>
                        <tr>
                            <th>이벤트</th>
                            <th>설명</th>
                            <th>트리거</th>
                            <th>상태</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // 이벤트 목록 조회
                        $events = $ad_account_model->get_conversion_events();
                        $campaign_events = $ad_account_model->get_campaign_events($campaign_id);
                        
                        foreach ($events as $event):
                            $is_enabled = false;
                            $trigger = '';
                            
                            // 이 캠페인에 설정된 이벤트인지 확인
                            foreach ($campaign_events as $campaign_event) {
                                if ($campaign_event['event_id'] == $event['id']) {
                                    $is_enabled = true;
                                    $trigger = $campaign_event['trigger_action'];
                                    break;
                                }
                            }
                        ?>
                        <tr>
                            <td>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input event-toggle" id="event_<?php echo $event['id']; ?>" data-event-id="<?php echo $event['id']; ?>" <?php echo $is_enabled ? 'checked' : ''; ?>>
                                    <label class="custom-control-label font-weight-bold" for="event_<?php echo $event['id']; ?>"><?php echo htmlspecialchars($event['name']); ?></label>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($event['description']); ?></td>
                            <td>
                                <select class="form-control event-trigger" data-event-id="<?php echo $event['id']; ?>" <?php echo !$is_enabled ? 'disabled' : ''; ?>>
                                    <option value="pageview" <?php echo $trigger === 'pageview' ? 'selected' : ''; ?>>페이지 조회 시</option>
                                    <option value="submit" <?php echo $trigger === 'submit' ? 'selected' : ''; ?>>폼 제출 시</option>
                                    <option value="click" <?php echo $trigger === 'click' ? 'selected' : ''; ?>>버튼 클릭 시</option>
                                    <option value="scroll" <?php echo $trigger === 'scroll' ? 'selected' : ''; ?>>특정 위치까지 스크롤 시</option>
                                    <option value="custom" <?php echo $trigger === 'custom' ? 'selected' : ''; ?>>사용자 정의</option>
                                </select>
                            </td>
                            <td>
                                <span class="badge <?php echo $is_enabled ? 'badge-success' : 'badge-secondary'; ?>">
                                    <?php echo $is_enabled ? '활성' : '비활성'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="text-center mt-4">
                <button type="button" id="saveEventSettings" class="btn btn-primary">
                    <i class="fas fa-save"></i> 이벤트 설정 저장
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // 플랫폼별 커스텀 설정 필드 정의
    var platformSettings = {
        'google_gtm': [
            {name: 'auto_events', label: '자동 이벤트 추적', type: 'checkbox', default: true},
            {name: 'dataLayer', label: '데이터 레이어 이름', type: 'text', default: 'dataLayer'}
        ],
        'google_analytics': [
            {name: 'anonymize_ip', label: 'IP 익명화', type: 'checkbox', default: true},
            {name: 'ecommerce', label: 'e커머스 추적 활성화', type: 'checkbox', default: false}
        ],
        'google_ads': [
            {name: 'value_tracking', label: '전환 가치 추적', type: 'checkbox', default: false},
            {name: 'phone_conversion', label: '전화 전환 추적', type: 'checkbox', default: false}
        ],
        'facebook_pixel': [
            {name: 'advanced_matching', label: '고급 매칭 사용', type: 'checkbox', default: false},
            {name: 'event_id', label: '이벤트 중복 제거 ID 사용', type: 'checkbox', default: false}
        ],
        'naver': [
            {name: 'use_common', label: '공통 전환 코드 사용', type: 'checkbox', default: true}
        ],
        'kakao': [
            {name: 'track_app_install', label: '앱 설치 추적', type: 'checkbox', default: false}
        ]
    };
    
    // 계정 선택 시 커스텀 설정 필드 생성
    $('#user_ad_account_id').change(function() {
        var platformCode = $(this).find('option:selected').data('platform');
        generateCustomSettings(platformCode);
    });
    
    // 페이지 로드 시 편집 모드에서 커스텀 설정 필드 생성
    var $container = $('#custom_settings_container');
    if ($container.data('platform')) {
        var platformCode = $container.data('platform');
        var settingsData = {};
        
        try {
            settingsData = JSON.parse($container.data('settings'));
        } catch (e) {
            console.error('설정 데이터 파싱 오류:', e);
        }
        
        generateCustomSettings(platformCode, settingsData);
    }
    
    // 커스텀 설정 필드 생성 함수
    function generateCustomSettings(platformCode, settingsData) {
        var $container = $('#custom_settings_container');
        $container.empty();
        
        if (!platformSettings[platformCode] || platformSettings[platformCode].length === 0) {
            $container.append('<p class="text-muted">이 플랫폼에는 추가 설정이 없습니다.</p>');
            return;
        }
        
        $container.append('<h5 class="mb-3">플랫폼별 추가 설정</h5>');
        
        $.each(platformSettings[platformCode], function(index, setting) {
            var fieldValue = settingsData && settingsData[setting.name] !== undefined 
                ? settingsData[setting.name] 
                : setting.default;
            
            var fieldHtml = '';
            
            if (setting.type === 'checkbox') {
                fieldHtml = `
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="setting_${setting.name}" 
                                   name="custom_settings[${setting.name}]" ${fieldValue ? 'checked' : ''}>
                            <label class="custom-control-label" for="setting_${setting.name}">${setting.label}</label>
                        </div>
                    </div>
                `;
            } else if (setting.type === 'text') {
                fieldHtml = `
                    <div class="form-group">
                        <label for="setting_${setting.name}">${setting.label}</label>
                        <input type="text" class="form-control" id="setting_${setting.name}" 
                               name="custom_settings[${setting.name}]" value="${fieldValue || ''}">
                    </div>
                `;
            } else if (setting.type === 'select') {
                var optionsHtml = '';
                $.each(setting.options, function(value, text) {
                    optionsHtml += `<option value="${value}" ${fieldValue == value ? 'selected' : ''}>${text}</option>`;
                });
                
                fieldHtml = `
                    <div class="form-group">
                        <label for="setting_${setting.name}">${setting.label}</label>
                        <select class="form-control" id="setting_${setting.name}" name="custom_settings[${setting.name}]">
                            ${optionsHtml}
                        </select>
                    </div>
                `;
            }
            
            $container.append(fieldHtml);
        });
    }
    
    // 이벤트 활성화 토글 처리
    $('.event-toggle').change(function() {
        var eventId = $(this).data('event-id');
        var isEnabled = $(this).prop('checked');
        
        // 트리거 선택 필드 활성화/비활성화
        $('select.event-trigger[data-event-id="' + eventId + '"]').prop('disabled', !isEnabled);
        
        // 상태 배지 업데이트
        var $badge = $(this).closest('tr').find('.badge');
        if (isEnabled) {
            $badge.removeClass('badge-secondary').addClass('badge-success').text('활성');
        } else {
            $badge.removeClass('badge-success').addClass('badge-secondary').text('비활성');
        }
    });
    
    // 이벤트 설정 저장 처리
    $('#saveEventSettings').click(function() {
        var events = [];
        
        // 활성화된 모든 이벤트 정보 수집
        $('.event-toggle:checked').each(function() {
            var eventId = $(this).data('event-id');
            var triggerAction = $('select.event-trigger[data-event-id="' + eventId + '"]').val();
            
            events.push({
                event_id: eventId,
                trigger_action: triggerAction
            });
        });
        
        // AJAX로 이벤트 설정 저장
        $.ajax({
            url: '<?php echo CF_CAMPAIGN_URL; ?>/ad_account_ajax.php',
            type: 'POST',
            data: {
                action: 'save_campaign_events',
                campaign_id: <?php echo $campaign_id; ?>,
                events: JSON.stringify(events)
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('이벤트 설정이 저장되었습니다.');
                } else {
                    alert('이벤트 설정 저장 중 오류가 발생했습니다: ' + response.message);
                }
            },
            error: function() {
                alert('서버 오류가 발생했습니다.');
            }
        });
    });
    
    // 전환 스크립트 복사 기능
    $('.copy-script').click(function() {
        var scriptCode = $('pre code').text();
        
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
});
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>