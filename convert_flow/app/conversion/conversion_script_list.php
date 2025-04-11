<?php
/**
 * 전환 스크립트 목록 페이지
 */
// 공통 파일 로드
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';


// 페이지 제목
$page_title = "전환 스크립트 목록";

// 캠페인 ID
$campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;

// 모델 로드
require_once CF_MODEL_PATH . '/campaign.model.php';
require_once CF_MODEL_PATH . '/conversion.model.php';
$campaign_model = new CampaignModel();
$conversion_model = new ConversionModel();

// 캠페인 정보 조회
$campaign = array();
if ($campaign_id) {
    $campaign = $campaign_model->get_campaign($campaign_id);
    
    // 캠페인이 존재하지 않거나 현재 사용자의 것이 아닌 경우
    if (!$campaign || $campaign['user_id'] != $member['id']) {
        alert('유효하지 않은 캠페인입니다.', 'campaign_list.php');
        exit;
    }
    
    $page_title .= " - " . $campaign['name'];
}

// 모든 캠페인 목록 조회 (드롭다운용)
$sql = "SELECT id, name FROM {$cf_table_prefix}campaigns 
        WHERE user_id = '{$member['id']}' 
        ORDER BY name ASC";
$all_campaigns = sql_query($sql);

// 전환 스크립트 목록 조회
$scripts = array();
if ($campaign_id) {
    // 특정 캠페인의 스크립트만 조회
    $sql = "SELECT cs.*, 
            (SELECT COUNT(*) FROM {$cf_table_prefix}conversion_events ce WHERE ce.script_id = cs.id) as event_count,
            (SELECT COUNT(*) FROM {$cf_table_prefix}conversion_events ce WHERE ce.script_id = cs.id AND ce.conversion_value > 0) as conversion_count
            FROM {$cf_table_prefix}conversion_scripts cs
            WHERE cs.campaign_id = '$campaign_id'
            ORDER BY cs.created_at DESC";
} else {
    // 사용자의 모든 스크립트 조회
    $sql = "SELECT cs.*, c.name as campaign_name,
            (SELECT COUNT(*) FROM {$cf_table_prefix}conversion_events ce WHERE ce.script_id = cs.id) as event_count,
            (SELECT COUNT(*) FROM {$cf_table_prefix}conversion_events ce WHERE ce.script_id = cs.id AND ce.conversion_value > 0) as conversion_count
            FROM {$cf_table_prefix}conversion_scripts cs
            JOIN {$cf_table_prefix}campaigns c ON cs.campaign_id = c.id
            WHERE c.user_id = '{$member['id']}'
            ORDER BY cs.created_at DESC";
}
$script_result = sql_query($sql);

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">전환 스크립트 목록</h1>
        <?php if ($campaign_id) { ?>
        <a href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_view.php?id=<?php echo $campaign_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> 캠페인으로 돌아가기
        </a>
        <?php } ?>
    </div>

    <?php if ($campaign_id && $campaign) { ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">선택된 캠페인</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $campaign['name']; ?></div>
                        </div>
                        <div class="col-auto">
                            <a href="<?php echo CF_CONVERSION_URL; ?>/conversion_script_create.php?campaign_id=<?php echo $campaign_id; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus fa-sm"></i> 새 스크립트 추가
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php } else { ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">캠페인 선택</h6>
                </div>
                <div class="card-body">
                    <form method="get" action="conversion_script_list.php" class="form-inline">
                        <div class="form-group mr-2">
                            <select name="campaign_id" class="form-control">
                                <option value="">모든 캠페인</option>
                                <?php while ($row = sql_fetch_array($all_campaigns)) { ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">선택</button>
                        
                        <div class="ml-auto">
                            <div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle" type="button" id="createScriptDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-plus fa-sm"></i> 새 스크립트 추가
                                </button>
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="createScriptDropdown">
                                    <?php
                                    sql_data_seek($all_campaigns, 0); // 커서 초기화
                                    while ($row = sql_fetch_array($all_campaigns)) {
                                        echo '<a class="dropdown-item" href="' . CF_CONVERSION_URL . '/conversion_script_create.php?campaign_id=' . $row['id'] . '">' . $row['name'] . '</a>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>

    <!-- 전환 스크립트 목록 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">전환 스크립트 목록</h6>
        </div>
        <div class="card-body">
            <?php if (sql_num_rows($script_result) > 0) { ?>
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>전환 유형</th>
                            <?php if (!$campaign_id) { ?>
                            <th>캠페인</th>
                            <?php } ?>
                            <th>페이지뷰</th>
                            <th>전환</th>
                            <th>전환율</th>
                            <th>생성일</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($script = sql_fetch_array($script_result)) {
                            // 전환율 계산
                            $conversion_rate = ($script['event_count'] > 0) ? round(($script['conversion_count'] / $script['event_count']) * 100, 2) : 0;
                            
                            echo '<tr>';
                            echo '<td>' . $script['conversion_type'] . '</td>';
                            
                            if (!$campaign_id) {
                                echo '<td><a href="' . CF_CAMPAIGN_URL . '/campaign_view.php?id=' . $script['campaign_id'] . '">' . $script['campaign_name'] . '</a></td>';
                            }
                            
                            echo '<td>' . number_format($script['event_count']) . '</td>';
                            echo '<td>' . number_format($script['conversion_count']) . '</td>';
                            echo '<td>' . $conversion_rate . '%</td>';
                            echo '<td>' . substr($script['created_at'], 0, 10) . '</td>';
                            echo '<td>
                                <div class="btn-group">
                                    <a href="' . CF_CONVERSION_URL . '/conversion_script_view.php?id=' . $script['id'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> 보기</a>
                                    <button type="button" class="btn btn-sm btn-info dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <span class="sr-only">Toggle Dropdown</span>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="' . CF_CONVERSION_URL . '/conversion_script_edit.php?id=' . $script['id'] . '"><i class="fas fa-edit"></i> 수정</a>
                                        <a class="dropdown-item" href="' . CF_CONVERSION_URL . '/conversion_event_list.php?script_id=' . $script['id'] . '"><i class="fas fa-exchange-alt"></i> 전환 이벤트</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteScript(' . $script['id'] . ', \'' . $script['conversion_type'] . '\')"><i class="fas fa-trash"></i> 삭제</a>
                                    </div>
                                </div>
                            </td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php } else { ?>
            <div class="text-center py-4">
                <p class="lead text-gray-800">등록된 전환 스크립트가 없습니다.</p>
                <?php if ($campaign_id) { ?>
                <a href="<?php echo CF_CONVERSION_URL; ?>/conversion_script_create.php?campaign_id=<?php echo $campaign_id; ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> 새 스크립트 추가하기
                </a>
                <?php } ?>
            </div>
            <?php } ?>
        </div>
    </div>

    <?php if (sql_num_rows($script_result) > 0) { ?>
    <!-- 전환 유형별 통계 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">전환 유형별 통계</h6>
        </div>
        <div class="card-body">
            <div class="chart-pie pt-4 pb-2">
                <canvas id="conversionTypeChart"></canvas>
            </div>
            <div class="mt-4 text-center small">
                <?php
                // 전환 유형별 데이터 조회
                if ($campaign_id) {
                    $sql = "SELECT 
                            cs.conversion_type,
                            COUNT(ce.id) as count
                            FROM {$cf_table_prefix}conversion_scripts cs
                            LEFT JOIN {$cf_table_prefix}conversion_events ce ON cs.id = ce.script_id AND ce.conversion_value > 0
                            WHERE cs.campaign_id = '$campaign_id'
                            GROUP BY cs.conversion_type
                            ORDER BY count DESC";
                } else {
                    $sql = "SELECT 
                            cs.conversion_type,
                            COUNT(ce.id) as count
                            FROM {$cf_table_prefix}conversion_scripts cs
                            JOIN {$cf_table_prefix}campaigns c ON cs.campaign_id = c.id
                            LEFT JOIN {$cf_table_prefix}conversion_events ce ON cs.id = ce.script_id AND ce.conversion_value > 0
                            WHERE c.user_id = '{$member['id']}'
                            GROUP BY cs.conversion_type
                            ORDER BY count DESC";
                }
                $type_result = sql_query($sql);
                
                $conversion_types = array();
                $conversion_counts = array();
                $colors = array('#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b');
                $color_index = 0;
                
                while ($row = sql_fetch_array($type_result)) {
                    $conversion_types[] = $row['conversion_type'];
                    $conversion_counts[] = $row['count'];
                    
                    $color = isset($colors[$color_index]) ? $colors[$color_index] : sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                    $color_index++;
                    
                    echo '<span class="mr-2">
                            <i class="fas fa-circle" style="color:' . $color . '"></i> ' . $row['conversion_type'] . '
                          </span>';
                }
                ?>
            </div>
        </div>
    </div>
    <?php } ?>
</div>

<!-- 스크립트 삭제 확인 모달 -->
<div class="modal fade" id="deleteScriptModal" tabindex="-1" role="dialog" aria-labelledby="deleteScriptModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteScriptModalLabel">전환 스크립트 삭제 확인</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p>정말로 <strong id="scriptTypeToDelete"></strong> 전환 스크립트를 삭제하시겠습니까?</p>
                <p class="text-danger">이 작업은 되돌릴 수 없으며, 모든 관련 전환 이벤트 데이터도 함께 삭제됩니다.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">취소</button>
                <a class="btn btn-danger" id="confirmDeleteButton" href="#">삭제</a>
            </div>
        </div>
    </div>
</div>

<script>
// 스크립트 삭제 확인 함수
function deleteScript(id, type) {
    $('#scriptTypeToDelete').text(type);
    $('#confirmDeleteButton').attr('href', '<?php echo CF_CONVERSION_URL; ?>/conversion_script_delete.php?id=' + id + '&campaign_id=<?php echo $campaign_id; ?>');
    $('#deleteScriptModal').modal('show');
}

// 전환 유형별 차트
<?php if (sql_num_rows($script_result) > 0) { ?>
var ctx = document.getElementById("conversionTypeChart");
var myPieChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($conversion_types); ?>,
        datasets: [{
            data: <?php echo json_encode($conversion_counts); ?>,
            backgroundColor: <?php echo json_encode(array_slice($colors, 0, count($conversion_types))); ?>,
            hoverBackgroundColor: <?php echo json_encode(array_slice($colors, 0, count($conversion_types))); ?>,
            hoverBorderColor: "rgba(234, 236, 244, 1)",
        }],
    },
    options: {
        maintainAspectRatio: false,
        tooltips: {
            backgroundColor: "rgb(255,255,255)",
            bodyFontColor: "#858796",
            borderColor: '#dddfeb',
            borderWidth: 1,
            xPadding: 15,
            yPadding: 15,
            displayColors: false,
            caretPadding: 10,
        },
        legend: {
            display: false
        },
        cutoutPercentage: 70,
    },
});
<?php } ?>
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>
