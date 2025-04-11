<?php
/**
 * 랜딩페이지 블록 타입 등록/수정
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/landing_block_options.php';

// 관리자 권한 체크
if (!$is_admin) {
    alert('관리자만 접근할 수 있습니다.');
    goto_url(CF_URL);
}

// 페이지 제목 설정
$w = isset($_GET['id']) ? 'u' : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($w == 'u') {
    $page_title = "블록 타입 수정";
    $sql = "SELECT * FROM landing_block_types WHERE id = '$id'";
    $block_type = sql_fetch($sql);
    
    if (!$block_type) {
        alert('존재하지 않는 블록 타입입니다.');
        goto_url('./landing_block_types_list.php');
    }
} else {
    $page_title = "블록 타입 등록";
    $block_type = array(
        'name' => '',
        'category' => '',
        'description' => '',
        'icon' => ''
    );
}

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 필수 입력 검증
    $error = '';
    
    if (empty($_POST['name'])) {
        $error = '블록 타입 이름을 입력해주세요.';
    } else if (empty($_POST['category'])) {
        $error = '카테고리를 입력해주세요.';
    }
    
    if (empty($error)) {
        // 데이터 준비
        $data = array(
            'name' => $_POST['name'],
            'category' => $_POST['category'],
            'description' => $_POST['description'],
            'icon' => $_POST['icon']
        );
        
        // 데이터 준비
        if ($w == 'u') {
            // 수정
            $sql = "UPDATE landing_block_types 
                    SET
                        name = '" . sql_escape_string($_POST['name']) . "',
                        category = '" . sql_escape_string($_POST['category']) . "',
                        description = '" . sql_escape_string($_POST['description']) . "',
                        icon = '" . sql_escape_string($_POST['icon']) . "'
                    WHERE id = '$id'";
            $result = sql_query($sql);
            $msg = '블록 타입이 수정되었습니다.';
        } else {
            // 새로 등록
            $sql = "INSERT INTO landing_block_types
                    SET
                        name = '" . sql_escape_string($_POST['name']) . "',
                        category = '" . sql_escape_string($_POST['category']) . "',
                        description = '" . sql_escape_string($_POST['description']) . "',
                        icon = '" . sql_escape_string($_POST['icon']) . "',
                        created_at = NOW()";
            $result = sql_query($sql);
            $msg = '블록 타입이 등록되었습니다.';
        }

        alert($msg, './landing_block_types_list.phpd');
    }
}
// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><?php echo $page_title; ?></h1>
    <p class="mb-4">랜딩페이지 블록 타입 정보를 입력하세요.</p>

    <!-- 에러 메시지 표시 -->
    <?php if (!empty($error)) { ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php } ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">블록 타입 정보</h6>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <?php echo get_admin_token_fields(); ?>
                
                <div class="form-group row">
                    <label for="name" class="col-sm-2 col-form-label">이름 <span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $block_type['name']; ?>" required>
                        <small class="form-text text-muted">블록 타입의 이름을 입력하세요.</small>
                    </div>
                </div>
                
                <div class="form-group row">
                    <label for="category" class="col-sm-2 col-form-label">카테고리 <span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <div class="input-group">
                            <input type="text" class="form-control" id="category" name="category" value="<?php echo $block_type['category']; ?>" list="category-list" required>
                            <?php if (!empty($block_categories)) { ?>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">선택</button>
                                <div class="dropdown-menu">
                                    <?php foreach ($block_categories as $category=>$description) { ?>
                                    <a class="dropdown-item category-select" href="javascript:void(0);" data-value="<?php echo $category; ?>"><?php echo $category; ?></a>
                                    <?php } ?>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                        <datalist id="category-list">
                            <?php foreach ($block_categories as $category=>$description) { ?>
                            <option value="<?php echo $category; ?>">
                            <?php } ?>
                        </datalist>
                        <small class="form-text text-muted">블록 타입의 카테고리를 입력하거나 선택하세요. (예: 헤더, 컨텐츠, 이미지, 폼 등)</small>
                    </div>
                </div>
                
                <div class="form-group row">
                    <label for="description" class="col-sm-2 col-form-label">설명</label>
                    <div class="col-sm-10">
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo $block_type['description']; ?></textarea>
                        <small class="form-text text-muted">블록 타입에 대한 설명을 입력하세요.</small>
                    </div>
                </div>
                
                <div class="form-group row">
                    <label for="icon" class="col-sm-2 col-form-label">아이콘</label>
                    <div class="col-sm-10">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i id="icon-preview" class="<?php echo $block_type['icon']; ?>"></i></span>
                            </div>
                            <input type="text" class="form-control" id="icon" name="icon" value="<?php echo $block_type['icon']; ?>" placeholder="fas fa-th-large">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="icon-select-btn">아이콘 선택</button>
                            </div>
                        </div>
                        <small class="form-text text-muted">Font Awesome 아이콘 클래스를 입력하세요. (예: fas fa-heading, far fa-image)</small>
                    </div>
                </div>
                
                <div class="form-group row mb-0">
                    <div class="col-sm-10 offset-sm-2">
                        <a href="./landing_block_types_list.php" class="btn btn-secondary">취소</a>
                        <button type="submit" class="btn btn-primary">저장</button>
                        <?php if ($w == 'u') { ?>
                        <a href="javascript:void(0);" onclick="deleteBlockType(<?php echo $id; ?>, '<?php echo addslashes($block_type['name']); ?>')" class="btn btn-danger float-right">삭제</a>
                        <?php } ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 아이콘 선택 모달 -->
<div class="modal fade" id="iconSelectModal" tabindex="-1" role="dialog" aria-labelledby="iconSelectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="iconSelectModalLabel">아이콘 선택</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <input type="text" class="form-control" id="icon-search" placeholder="아이콘 검색...">
                </div>
                <div class="row">
                    <div class="col-12">
                        <ul class="nav nav-tabs" id="iconTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="solid-tab" data-toggle="tab" href="#solid-icons" role="tab" aria-controls="solid-icons" aria-selected="true">Solid</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="regular-tab" data-toggle="tab" href="#regular-icons" role="tab" aria-controls="regular-icons" aria-selected="false">Regular</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="brands-tab" data-toggle="tab" href="#brand-icons" role="tab" aria-controls="brand-icons" aria-selected="false">Brands</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="tab-content" id="iconTabContent">
                    <div class="tab-pane fade show active" id="solid-icons" role="tabpanel" aria-labelledby="solid-tab">
                        <div class="row icon-grid" id="solid-icon-grid">
                            <!-- Solid icons will be dynamically loaded here -->
                        </div>
                    </div>
                    <div class="tab-pane fade" id="regular-icons" role="tabpanel" aria-labelledby="regular-tab">
                        <div class="row icon-grid" id="regular-icon-grid">
                            <!-- Regular icons will be dynamically loaded here -->
                        </div>
                    </div>
                    <div class="tab-pane fade" id="brand-icons" role="tabpanel" aria-labelledby="brands-tab">
                        <div class="row icon-grid" id="brand-icon-grid">
                            <!-- Brand icons will be dynamically loaded here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">취소</button>
                <button class="btn btn-primary" type="button" id="select-icon-btn">선택</button>
            </div>
        </div>
    </div>
</div>

<!-- 블록 타입 삭제 확인 모달 -->
<div class="modal fade" id="deleteBlockTypeModal" tabindex="-1" role="dialog" aria-labelledby="deleteBlockTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteBlockTypeModalLabel">블록 타입 삭제 확인</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p>정말로 <strong id="blockTypeNameToDelete"></strong> 블록 타입을 삭제하시겠습니까?</p>
                <p class="text-danger">주의: 이 블록 타입을 사용하는 모든 블록 템플릿이 함께 삭제됩니다.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">취소</button>
                <a class="btn btn-danger" id="confirmDeleteButton" href="#">삭제</a>
            </div>
        </div>
    </div>
</div>

<style>
.icon-grid {
    max-height: 400px;
    overflow-y: auto;
    padding: 10px;
}
.icon-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 10px;
    margin-bottom: 10px;
    cursor: pointer;
    border: 1px solid #e3e6f0;
    border-radius: 4px;
    transition: all 0.2s;
}
.icon-item:hover {
    background-color: #f8f9fc;
    border-color: #4e73df;
}
.icon-item.selected {
    background-color: #4e73df;
    color: white;
    border-color: #4e73df;
}
.icon-preview {
    font-size: 24px;
    margin-bottom: 5px;
}
.icon-name {
    font-size: 10px;
    text-align: center;
    word-break: break-all;
}
</style>

<script>
$(document).ready(function() {
    // 카테고리 선택 이벤트
    $('.category-select').click(function() {
        var value = $(this).data('value');
        $('#category').val(value);
    });
    
    // 아이콘 입력 시 미리보기 업데이트
    $('#icon').on('input', function() {
        var iconClass = $(this).val();
        $('#icon-preview').attr('class', iconClass);
    });
    
    // 아이콘 선택 버튼 클릭
    $('#icon-select-btn').click(function() {
        loadIcons();
        $('#iconSelectModal').modal('show');
    });
    
    // 현재 선택된 아이콘 클래스
    var selectedIconClass = '';
    
    // 아이콘 그리드에서 아이콘 선택
    $(document).on('click', '.icon-item', function() {
        $('.icon-item').removeClass('selected');
        $(this).addClass('selected');
        selectedIconClass = $(this).data('icon');
    });
    
    // 선택 버튼 클릭
    $('#select-icon-btn').click(function() {
        if (selectedIconClass) {
            $('#icon').val(selectedIconClass);
            $('#icon-preview').attr('class', selectedIconClass);
            $('#iconSelectModal').modal('hide');
        } else {
            alert('아이콘을 선택해주세요.');
        }
    });
    
    // 아이콘 검색
    $('#icon-search').on('input', function() {
        var query = $(this).val().toLowerCase();
        $('.icon-item').each(function() {
            var iconName = $(this).data('name').toLowerCase();
            if (iconName.indexOf(query) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Font Awesome 아이콘 로드
    function loadIcons() {
        // 로드된 적이 없으면 아이콘 데이터 로드
        if ($('#solid-icon-grid').children().length === 0) {
            loadIconsForTab('solid');
        }
        
        // 탭 클릭 이벤트
        $('#iconTabs a').on('shown.bs.tab', function (e) {
            var targetId = $(e.target).attr('href');
            var type = targetId.replace('#', '').replace('-icons', '');
            
            if ($(targetId + ' .icon-grid').children().length === 0) {
                loadIconsForTab(type);
            }
        });
    }
    
    // 각 탭별 아이콘 로드
    function loadIconsForTab(type) {
        var prefix, icons;
        
        switch(type) {
            case 'solid':
                prefix = 'fas';
                icons = getSolidIcons();
                break;
            case 'regular':
                prefix = 'far';
                icons = getRegularIcons();
                break;
            case 'brand':
                prefix = 'fab';
                icons = getBrandIcons();
                break;
            default:
                prefix = 'fas';
                icons = getSolidIcons();
        }
        
        var html = '';
        for (var i = 0; i < icons.length; i++) {
            var iconClass = prefix + ' fa-' + icons[i];
            var iconName = icons[i];
            
            html += '<div class="col-md-2 col-sm-3 col-4">';
            html += '<div class="icon-item" data-icon="' + iconClass + '" data-name="' + iconName + '">';
            html += '<div class="icon-preview"><i class="' + iconClass + '"></i></div>';
            html += '<div class="icon-name">' + iconName + '</div>';
            html += '</div>';
            html += '</div>';
        }
        
        $('#' + type + '-icon-grid').html(html);
    }
    
    // 블록 타입 삭제 확인 함수
    window.deleteBlockType = function(id, name) {
        $('#blockTypeNameToDelete').text(name);
        $('#confirmDeleteButton').attr('href', 'landing_block_types_delete.php?id=' + id);
        $('#deleteBlockTypeModal').modal('show');
    };
    
    // Font Awesome 아이콘 목록
    function getSolidIcons() {
        return [
            'address-book', 'address-card', 'adjust', 'align-center', 'align-justify', 'align-left', 'align-right',
            'anchor', 'archive', 'arrow-alt-circle-down', 'arrow-alt-circle-left', 'arrow-alt-circle-right',
            'arrow-alt-circle-up', 'arrow-down', 'arrow-left', 'arrow-right', 'arrow-up', 'arrows-alt',
            'arrows-alt-h', 'arrows-alt-v', 'at', 'award', 'baby', 'baby-carriage', 'backspace', 'backward',
            'bacon', 'balance-scale', 'balance-scale-left', 'balance-scale-right', 'ban', 'band-aid',
            'barcode', 'bars', 'baseball-ball', 'basketball-ball', 'bath', 'battery-empty', 'battery-full',
            'battery-half', 'battery-quarter', 'battery-three-quarters', 'bed', 'beer', 'bell', 'bell-slash',
            'bezier-curve', 'bible', 'bicycle', 'biking', 'binoculars', 'biohazard', 'birthday-cake',
            'blender', 'blender-phone', 'blind', 'blog', 'bold', 'bolt', 'bomb', 'bone', 'bong', 'book',
            'book-dead', 'book-medical', 'book-open', 'book-reader', 'bookmark', 'border-all', 'border-none',
            'border-style', 'bowling-ball', 'box', 'box-open', 'boxes', 'braille', 'brain', 'bread-slice',
            'briefcase', 'briefcase-medical', 'broadcast-tower', 'broom', 'brush', 'bug', 'building',
            'bullhorn', 'bullseye', 'burn', 'bus', 'bus-alt', 'business-time', 'calculator', 'calendar',
            'calendar-alt', 'calendar-check', 'calendar-day', 'calendar-minus', 'calendar-plus',
            'calendar-times', 'calendar-week', 'camera', 'camera-retro', 'campground', 'candy-cane',
            'cannabis', 'capsules', 'car', 'car-alt', 'car-battery', 'car-crash', 'car-side', 'caret-down',
            'caret-left', 'caret-right', 'caret-square-down', 'caret-square-left', 'caret-square-right',
            'caret-square-up', 'caret-up', 'carrot', 'cart-arrow-down', 'cart-plus', 'cash-register', 'cat',
            'certificate', 'chair', 'chalkboard', 'chalkboard-teacher', 'charging-station', 'chart-area',
            'chart-bar', 'chart-line', 'chart-pie', 'check', 'check-circle', 'check-double', 'check-square',
            'cheese', 'chess', 'chess-bishop', 'chess-board', 'chess-king', 'chess-knight', 'chess-pawn',
            'chess-queen', 'chess-rook', 'chevron-circle-down', 'chevron-circle-left', 'chevron-circle-right',
            'chevron-circle-up', 'chevron-down', 'chevron-left', 'chevron-right', 'chevron-up', 'child',
            'church', 'circle', 'circle-notch', 'city', 'clinic-medical', 'clipboard', 'clipboard-check',
            'clipboard-list', 'clock', 'clone', 'closed-captioning', 'cloud', 'cloud-download-alt',
            'cloud-meatball', 'cloud-moon', 'cloud-moon-rain', 'cloud-rain', 'cloud-showers-heavy',
            'cloud-sun', 'cloud-sun-rain', 'cloud-upload-alt', 'cocktail', 'code', 'code-branch', 'coffee',
            'cog', 'cogs', 'coins', 'columns', 'comment', 'comment-alt', 'comment-dollar', 'comment-dots',
            'comment-medical', 'comment-slash', 'comments', 'comments-dollar', 'compact-disc', 'compass',
            'compress', 'compress-arrows-alt', 'concierge-bell', 'cookie', 'cookie-bite', 'copy',
            'copyright', 'couch', 'credit-card', 'crop', 'crop-alt', 'cross', 'crosshairs', 'crow', 'crown',
            'crutch', 'cube', 'cubes', 'cut', 'database', 'deaf', 'democrat', 'desktop', 'dharmachakra',
            'diagnoses', 'dice', 'dice-d20', 'dice-d6', 'dice-five', 'dice-four', 'dice-one', 'dice-six',
            'dice-three', 'dice-two', 'digital-tachograph', 'directions', 'divide', 'dizzy', 'dna', 'dog',
            'dollar-sign', 'dolly', 'dolly-flatbed', 'donate', 'door-closed', 'door-open', 'dot-circle',
            'dove', 'download', 'drafting-compass', 'dragon', 'draw-polygon', 'drum', 'drum-steelpan',
            'drumstick-bite', 'dumbbell', 'dumpster', 'dumpster-fire', 'dungeon', 'edit', 'egg', 'eject',
            'ellipsis-h', 'ellipsis-v', 'envelope', 'envelope-open', 'envelope-open-text', 'envelope-square',
            'equals', 'eraser', 'ethernet', 'euro-sign', 'exchange-alt', 'exclamation', 'exclamation-circle',
            'exclamation-triangle', 'expand', 'expand-arrows-alt', 'external-link-alt', 'external-link-square-alt',
            'eye', 'eye-dropper', 'eye-slash', 'fan', 'fast-backward', 'fast-forward', 'fax', 'feather',
            'feather-alt', 'female', 'fighter-jet', 'file', 'file-alt', 'file-archive', 'file-audio',
            'file-code', 'file-contract', 'file-csv', 'file-download', 'file-excel', 'file-export',
            'file-image', 'file-import', 'file-invoice', 'file-invoice-dollar', 'file-medical',
            'file-medical-alt', 'file-pdf', 'file-powerpoint', 'file-prescription', 'file-signature',
            'file-upload', 'file-video', 'file-word', 'fill', 'fill-drip', 'film', 'filter', 'fingerprint',
            'fire', 'fire-alt', 'fire-extinguisher', 'first-aid', 'fish', 'fist-raised', 'flag',
            'flag-checkered', 'flag-usa', 'flask', 'flushed', 'folder', 'folder-minus', 'folder-open',
            'folder-plus', 'font', 'football-ball', 'forward', 'frog', 'frown', 'frown-open', 'funnel-dollar',
            'futbol', 'gamepad', 'gas-pump', 'gavel', 'gem', 'genderless', 'ghost', 'gift', 'gifts',
            'glass-cheers', 'glass-martini', 'glass-martini-alt', 'glass-whiskey', 'glasses', 'globe',
            'globe-africa', 'globe-americas', 'globe-asia', 'globe-europe', 'golf-ball', 'gopuram',
            'graduation-cap', 'greater-than', 'greater-than-equal', 'grimace', 'grin', 'grin-alt',
            'grin-beam', 'grin-beam-sweat', 'grin-hearts', 'grin-squint', 'grin-squint-tears', 'grin-stars',
            'grin-tears', 'grin-tongue', 'grin-tongue-squint', 'grin-tongue-wink', 'grin-wink', 'grip-horizontal',
            'grip-lines', 'grip-lines-vertical', 'grip-vertical', 'guitar', 'h-square', 'hamburger', 'hammer',
            'hamsa', 'hand-holding', 'hand-holding-heart', 'hand-holding-usd', 'hand-lizard', 'hand-middle-finger',
            'hand-paper', 'hand-peace', 'hand-point-down', 'hand-point-left', 'hand-point-right',
            'hand-point-up', 'hand-pointer', 'hand-rock', 'hand-scissors', 'hand-spock', 'hands',
            'hands-helping', 'handshake', 'hanukiah', 'hard-hat', 'hashtag', 'hat-cowboy', 'hat-cowboy-side',
            'hat-wizard', 'heading', 'headphones', 'headphones-alt', 'headset', 'heart', 'heart-broken',
            'heartbeat', 'helicopter', 'highlighter', 'hiking', 'hippo', 'history', 'hockey-puck', 'holly-berry',
            'home', 'horse', 'horse-head', 'hospital', 'hospital-alt', 'hospital-symbol', 'hot-tub',
            'hotdog', 'hotel', 'hourglass', 'hourglass-end', 'hourglass-half', 'hourglass-start',
            'house-damage', 'hryvnia', 'i-cursor', 'ice-cream', 'icicles', 'icons', 'id-badge', 'id-card',
            'id-card-alt', 'igloo', 'image', 'images', 'inbox', 'indent', 'industry', 'infinity', 'info',
            'info-circle', 'italic', 'jedi', 'joint', 'journal-whills', 'kaaba', 'key', 'keyboard',
            'khanda', 'kiss', 'kiss-beam', 'kiss-wink-heart', 'kiwi-bird', 'landmark', 'language', 'laptop',
            'laptop-code', 'laptop-medical', 'laugh', 'laugh-beam', 'laugh-squint', 'laugh-wink',
            'layer-group', 'leaf', 'lemon', 'less-than', 'less-than-equal', 'level-down-alt', 'level-up-alt',
            'life-ring', 'lightbulb', 'link', 'lira-sign', 'list', 'list-alt', 'list-ol', 'list-ul',
            'location-arrow', 'lock', 'lock-open', 'long-arrow-alt-down', 'long-arrow-alt-left',
            'long-arrow-alt-right', 'long-arrow-alt-up', 'low-vision', 'luggage-cart', 'magic', 'magnet',
            'mail-bulk', 'male', 'map', 'map-marked', 'map-marked-alt', 'map-marker', 'map-marker-alt',
            'map-pin', 'map-signs', 'marker', 'mars', 'mars-double', 'mars-stroke', 'mars-stroke-h',
            'mars-stroke-v', 'mask', 'medal', 'medkit', 'meh', 'meh-blank', 'meh-rolling-eyes', 'memory',
            'menorah', 'mercury', 'meteor', 'microchip', 'microphone', 'microphone-alt', 'microphone-alt-slash',
            'microphone-slash', 'microscope', 'minus', 'minus-circle', 'minus-square', 'mitten', 'mobile',
            'mobile-alt', 'money-bill', 'money-bill-alt', 'money-bill-wave', 'money-bill-wave-alt',
            'money-check', 'money-check-alt', 'monument', 'moon', 'mortar-pestle', 'mosque', 'motorcycle',
            'mountain', 'mouse', 'mouse-pointer', 'mug-hot', 'music', 'network-wired', 'neuter', 'newspaper',
            'not-equal', 'notes-medical', 'object-group', 'object-ungroup', 'oil-can', 'om', 'otter',
            'outdent', 'pager', 'paint-brush', 'paint-roller', 'palette', 'pallet', 'paper-plane',
            'paperclip', 'parachute-box', 'paragraph', 'parking', 'passport', 'pastafarianism', 'paste',
            'pause', 'pause-circle', 'paw', 'peace', 'pen', 'pen-alt', 'pen-fancy', 'pen-nib', 'pen-square',
            'pencil-alt', 'pencil-ruler', 'people-carry', 'pepper-hot', 'percent', 'percentage', 'person-booth',
            'phone', 'phone-alt', 'phone-slash', 'phone-square', 'phone-square-alt', 'phone-volume',
            'photo-video', 'piggy-bank', 'pills', 'pizza-slice', 'place-of-worship', 'plane', 'plane-arrival',
            'plane-departure', 'play', 'play-circle', 'plug', 'plus', 'plus-circle', 'plus-square',
            'podcast', 'poll', 'poll-h', 'poo', 'poo-storm', 'poop', 'portrait', 'pound-sign', 'power-off',
            'pray', 'praying-hands', 'prescription', 'prescription-bottle', 'prescription-bottle-alt',
            'print', 'procedures', 'project-diagram', 'puzzle-piece', 'qrcode', 'question', 'question-circle',
            'quidditch', 'quote-left', 'quote-right', 'quran', 'radiation', 'radiation-alt', 'rainbow',
            'random', 'receipt', 'record-vinyl', 'recycle', 'redo', 'redo-alt', 'registered', 'remove-format',
            'reply', 'reply-all', 'republican', 'restroom', 'retweet', 'ribbon', 'ring', 'road', 'robot',
            'rocket', 'route', 'rss', 'rss-square', 'ruble-sign', 'ruler', 'ruler-combined', 'ruler-horizontal',
            'ruler-vertical', 'running', 'rupee-sign', 'sad-cry', 'sad-tear', 'satellite', 'satellite-dish',
            'save', 'school', 'screwdriver', 'scroll', 'sd-card', 'search', 'search-dollar', 'search-location',
            'search-minus', 'search-plus', 'seedling', 'server', 'shapes', 'share', 'share-alt',
            'share-alt-square', 'share-square', 'shekel-sign', 'shield-alt', 'ship', 'shipping-fast',
            'shoe-prints', 'shopping-bag', 'shopping-basket', 'shopping-cart', 'shower', 'shuttle-van',
            'sign', 'sign-in-alt', 'sign-language', 'sign-out-alt', 'signal', 'signature', 'sim-card',
            'sitemap', 'skating', 'skiing', 'skiing-nordic', 'skull', 'skull-crossbones', 'slash',
            'sleigh', 'sliders-h', 'smile', 'smile-beam', 'smile-wink', 'smog', 'smoking', 'smoking-ban',
            'sms', 'snowboarding', 'snowflake', 'snowman', 'snowplow', 'socks', 'solar-panel', 'sort',
            'sort-alpha-down', 'sort-alpha-down-alt', 'sort-alpha-up', 'sort-alpha-up-alt', 'sort-amount-down',
            'sort-amount-down-alt', 'sort-amount-up', 'sort-amount-up-alt', 'sort-down', 'sort-numeric-down',
            'sort-numeric-down-alt', 'sort-numeric-up', 'sort-numeric-up-alt', 'sort-up', 'spa',
            'space-shuttle', 'spell-check', 'spider', 'spinner', 'splotch', 'spray-can', 'square',
            'square-full', 'square-root-alt', 'stamp', 'star', 'star-and-crescent', 'star-half',
            'star-half-alt', 'star-of-david', 'star-of-life', 'step-backward', 'step-forward',
            'stethoscope', 'sticky-note', 'stop', 'stop-circle', 'stopwatch', 'store', 'store-alt',
            'stream', 'street-view', 'strikethrough', 'stroopwafel', 'subscript', 'subway', 'suitcase',
            'suitcase-rolling', 'sun', 'superscript', 'surprise', 'swatchbook', 'swimmer', 'swimming-pool',
            'synagogue', 'sync', 'sync-alt', 'syringe', 'table', 'table-tennis', 'tablet', 'tablet-alt',
            'tablets', 'tachometer-alt', 'tag', 'tags', 'tape', 'tasks', 'taxi', 'teeth', 'teeth-open',
            'temperature-high', 'temperature-low', 'tenge', 'terminal', 'text-height', 'text-width', 'th',
            'th-large', 'th-list', 'theater-masks', 'thermometer', 'thermometer-empty', 'thermometer-full',
            'thermometer-half', 'thermometer-quarter', 'thermometer-three-quarters', 'thumbs-down',
            'thumbs-up', 'thumbtack', 'ticket-alt', 'times', 'times-circle', 'tint', 'tint-slash',
            'tired', 'toggle-off', 'toggle-on', 'toilet', 'toilet-paper', 'toolbox', 'tools', 'tooth',
            'torah', 'torii-gate', 'tractor', 'trademark', 'traffic-light', 'train', 'tram',
            'transgender', 'transgender-alt', 'trash', 'trash-alt', 'trash-restore', 'trash-restore-alt',
            'tree', 'trophy', 'truck', 'truck-loading', 'truck-monster', 'truck-moving', 'truck-pickup',
            'tshirt', 'tty', 'tv', 'umbrella', 'umbrella-beach', 'underline', 'undo', 'undo-alt',
            'universal-access', 'university', 'unlink', 'unlock', 'unlock-alt', 'upload', 'user',
            'user-alt', 'user-alt-slash', 'user-astronaut', 'user-check', 'user-circle', 'user-clock',
            'user-cog', 'user-edit', 'user-friends', 'user-graduate', 'user-injured', 'user-lock',
            'user-md', 'user-minus', 'user-ninja', 'user-nurse', 'user-plus', 'user-secret', 'user-shield',
            'user-slash', 'user-tag', 'user-tie', 'user-times', 'users', 'users-cog', 'utensil-spoon',
            'utensils', 'vector-square', 'venus', 'venus-double', 'venus-mars', 'vial', 'vials',
            'video', 'video-slash', 'vihara', 'voicemail', 'volleyball-ball', 'volume-down', 'volume-mute',
            'volume-off', 'volume-up', 'vote-yea', 'vr-cardboard', 'walking', 'wallet', 'warehouse',
            'water', 'wave-square', 'weight', 'weight-hanging', 'wheelchair', 'wifi', 'wind', 'window-close',
            'window-maximize', 'window-minimize', 'window-restore', 'wine-bottle', 'wine-glass',
            'wine-glass-alt', 'won-sign', 'wrench', 'x-ray', 'yen-sign', 'yin-yang'
        ];
    }
    
    function getRegularIcons() {
        return [
            'address-book', 'address-card', 'angry', 'arrow-alt-circle-down', 'arrow-alt-circle-left',
            'arrow-alt-circle-right', 'arrow-alt-circle-up', 'bell', 'bell-slash', 'bookmark', 'building',
            'calendar', 'calendar-alt', 'calendar-check', 'calendar-minus', 'calendar-plus', 'calendar-times',
            'caret-square-down', 'caret-square-left', 'caret-square-right', 'caret-square-up', 'chart-bar',
            'check-circle', 'check-square', 'circle', 'clipboard', 'clock', 'clone', 'closed-captioning',
            'comment', 'comment-alt', 'comment-dots', 'comments', 'compass', 'copy', 'copyright', 'credit-card',
            'dizzy', 'dot-circle', 'edit', 'envelope', 'envelope-open', 'eye', 'eye-slash', 'file',
            'file-alt', 'file-archive', 'file-audio', 'file-code', 'file-excel', 'file-image', 'file-pdf',
            'file-powerpoint', 'file-video', 'file-word', 'flag', 'flushed', 'folder', 'folder-open',
            'frown', 'frown-open', 'futbol', 'gem', 'grimace', 'grin', 'grin-alt', 'grin-beam',
            'grin-beam-sweat', 'grin-hearts', 'grin-squint', 'grin-squint-tears', 'grin-stars', 'grin-tears',
            'grin-tongue', 'grin-tongue-squint', 'grin-tongue-wink', 'grin-wink', 'hand-lizard', 'hand-paper',
            'hand-peace', 'hand-point-down', 'hand-point-left', 'hand-point-right', 'hand-point-up',
            'hand-pointer', 'hand-rock', 'hand-scissors', 'hand-spock', 'handshake', 'hdd', 'heart',
            'hospital', 'hourglass', 'id-badge', 'id-card', 'image', 'images', 'keyboard', 'kiss',
            'kiss-beam', 'kiss-wink-heart', 'laugh', 'laugh-beam', 'laugh-squint', 'laugh-wink',
            'lemon', 'life-ring', 'lightbulb', 'list-alt', 'map', 'meh', 'meh-blank', 'meh-rolling-eyes',
            'minus-square', 'money-bill-alt', 'moon', 'newspaper', 'object-group', 'object-ungroup',
            'paper-plane', 'pause-circle', 'play-circle', 'plus-square', 'question-circle', 'registered',
            'sad-cry', 'sad-tear', 'save', 'share-square', 'smile', 'smile-beam', 'smile-wink',
            'snowflake', 'square', 'star', 'star-half', 'sticky-note', 'stop-circle', 'sun', 'surprise',
            'thumbs-down', 'thumbs-up', 'times-circle', 'tired', 'trash-alt', 'user', 'user-circle',
            'window-close', 'window-maximize', 'window-minimize', 'window-restore'
        ];
    }
    
    function getBrandIcons() {
        return [
            '500px', 'accessible-icon', 'accusoft', 'acquisitions-incorporated', 'adn', 'adobe',
            'adversal', 'affiliatetheme', 'airbnb', 'algolia', 'alipay', 'amazon', 'amazon-pay',
            'amilia', 'android', 'angellist', 'angrycreative', 'angular', 'app-store', 'app-store-ios',
            'apper', 'apple', 'apple-pay', 'artstation', 'asymmetrik', 'atlassian', 'audible', 'autoprefixer',
            'avianex', 'aviato', 'aws', 'bandcamp', 'battle-net', 'behance', 'behance-square', 'bimobject',
            'bitbucket', 'bitcoin', 'bity', 'black-tie', 'blackberry', 'blogger', 'blogger-b', 'bluetooth',
            'bluetooth-b', 'bootstrap', 'btc', 'buffer', 'buromobelexperte', 'buy-n-large', 'buysellads',
            'canadian-maple-leaf', 'cc-amazon-pay', 'cc-amex', 'cc-apple-pay', 'cc-diners-club',
            'cc-discover', 'cc-jcb', 'cc-mastercard', 'cc-paypal', 'cc-stripe', 'cc-visa', 'centercode',
            'centos', 'chrome', 'chromecast', 'cloudscale', 'cloudsmith', 'cloudversify', 'codepen',
            'codiepie', 'confluence', 'connectdevelop', 'contao', 'cotton-bureau', 'cpanel', 'creative-commons',
            'creative-commons-by', 'creative-commons-nc', 'creative-commons-nc-eu', 'creative-commons-nc-jp',
            'creative-commons-nd', 'creative-commons-pd', 'creative-commons-pd-alt', 'creative-commons-remix',
            'creative-commons-sa', 'creative-commons-sampling', 'creative-commons-sampling-plus',
            'creative-commons-share', 'creative-commons-zero', 'critical-role', 'css3', 'css3-alt',
            'cuttlefish', 'd-and-d', 'd-and-d-beyond', 'dashcube', 'delicious', 'deploydog', 'deskpro',
            'dev', 'deviantart', 'dhl', 'diaspora', 'digg', 'digital-ocean', 'discord', 'discourse',
            'dochub', 'docker', 'draft2digital', 'dribbble', 'dribbble-square', 'dropbox', 'drupal',
            'dyalog', 'earlybirds', 'ebay', 'edge', 'elementor', 'ello', 'ember', 'empire', 'envira',
            'erlang', 'ethereum', 'etsy', 'evernote', 'expeditedssl', 'facebook', 'facebook-f',
            'facebook-messenger', 'facebook-square', 'fantasy-flight-games', 'fedex', 'fedora', 'figma',
            'firefox', 'firefox-browser', 'first-order', 'first-order-alt', 'firstdraft', 'flickr',
            'flipboard', 'fly', 'font-awesome', 'font-awesome-alt', 'font-awesome-flag',
            'fonticons', 'fonticons-fi', 'fort-awesome', 'fort-awesome-alt', 'forumbee', 'foursquare',
            'free-code-camp', 'freebsd', 'fulcrum', 'galactic-republic', 'galactic-senate', 'get-pocket',
            'gg', 'gg-circle', 'git', 'git-alt', 'git-square', 'github', 'github-alt', 'github-square',
            'gitkraken', 'gitlab', 'gitter', 'glide', 'glide-g', 'gofore', 'goodreads', 'goodreads-g',
            'google', 'google-drive', 'google-play', 'google-plus', 'google-plus-g', 'google-plus-square',
            'google-wallet', 'gratipay', 'grav', 'gripfire', 'grunt', 'gulp', 'hacker-news',
            'hacker-news-square', 'hackerrank', 'hips', 'hire-a-helper', 'hooli', 'hornbill', 'hotjar',
            'houzz', 'html5', 'hubspot', 'ideal', 'imdb', 'instagram', 'intercom', 'internet-explorer',
            'invision', 'ioxhost', 'itch-io', 'itunes', 'itunes-note', 'java', 'jedi-order', 'jenkins',
            'jira', 'joget', 'joomla', 'js', 'js-square', 'jsfiddle', 'kaggle', 'keybase', 'keycdn', 'kickstarter', 'kickstarter-k',
            'korvue', 'laravel', 'lastfm', 'lastfm-square', 'leanpub', 'less', 'line', 'linkedin',
            'linkedin-in', 'linode', 'linux', 'lyft', 'magento', 'mailchimp', 'mandalorian', 'markdown',
            'mastodon', 'maxcdn', 'mdb', 'medapps', 'medium', 'medium-m', 'medrt', 'meetup', 'megaport',
            'mendeley', 'microblog', 'microsoft', 'mix', 'mixcloud', 'mizuni', 'modx', 'monero', 'napster',
            'neos', 'nimblr', 'node', 'node-js', 'npm', 'ns8', 'nutritionix', 'odnoklassniki',
            'odnoklassniki-square', 'old-republic', 'opencart', 'openid', 'opera', 'optin-monster',
            'orcid', 'osi', 'page4', 'pagelines', 'palfed', 'patreon', 'paypal', 'penny-arcade', 'periscope',
            'phabricator', 'phoenix-framework', 'phoenix-squadron', 'php', 'pied-piper', 'pied-piper-alt',
            'pied-piper-hat', 'pied-piper-pp', 'pinterest', 'pinterest-p', 'pinterest-square', 'playstation',
            'product-hunt', 'pushed', 'python', 'qq', 'quinscape', 'quora', 'r-project', 'raspberry-pi',
            'ravelry', 'react', 'reacteurope', 'readme', 'rebel', 'red-river', 'reddit', 'reddit-alien',
            'reddit-square', 'redhat', 'renren', 'replyd', 'researchgate', 'resolving', 'rev', 'rocketchat',
            'rockrms', 'safari', 'salesforce', 'sass', 'schlix', 'scribd', 'searchengin', 'sellcast',
            'sellsy', 'servicestack', 'shirtsinbulk', 'shopware', 'simplybuilt', 'sistrix', 'sith',
            'sketch', 'skyatlas', 'skype', 'slack', 'slack-hash', 'slideshare', 'snapchat',
            'snapchat-ghost', 'snapchat-square', 'soundcloud', 'sourcetree', 'speakap', 'speaker-deck',
            'spotify', 'squarespace', 'stack-exchange', 'stack-overflow', 'stackpath', 'staylinked',
            'steam', 'steam-square', 'steam-symbol', 'sticker-mule', 'strava', 'stripe', 'stripe-s',
            'studiovinari', 'stumbleupon', 'stumbleupon-circle', 'superpowers', 'supple', 'suse',
            'swift', 'symfony', 'teamspeak', 'telegram', 'telegram-plane', 'tencent-weibo', 'the-red-yeti',
            'themeco', 'themeisle', 'think-peaks', 'trade-federation', 'trello', 'tripadvisor', 'tumblr',
            'tumblr-square', 'twitch', 'twitter', 'twitter-square', 'typo3', 'uber', 'ubuntu', 'uikit',
            'umbraco', 'uniregistry', 'unity', 'untappd', 'ups', 'usb', 'usps', 'ussunnah', 'vaadin',
            'viacoin', 'viadeo', 'viadeo-square', 'viber', 'vimeo', 'vimeo-square', 'vimeo-v', 'vine',
            'vk', 'vnv', 'vuejs', 'waze', 'weebly', 'weibo', 'weixin', 'whatsapp', 'whatsapp-square',
            'whmcs', 'wikipedia-w', 'windows', 'wix', 'wizards-of-the-coast', 'wolf-pack-battalion',
            'wordpress', 'wordpress-simple', 'wpbeginner', 'wpexplorer', 'wpforms', 'wpressr', 'xbox',
            'xing', 'xing-square', 'y-combinator', 'yahoo', 'yammer', 'yandex', 'yandex-international',
            'yarn', 'yelp', 'yoast', 'youtube', 'youtube-square', 'zhihu'
        ];
    }
});
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>