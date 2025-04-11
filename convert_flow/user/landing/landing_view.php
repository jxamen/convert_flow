<?php
include_once("../../includes/_common.php");

if (!$is_member) {
    alert("로그인이 필요한 서비스입니다.", G5_BBS_URL."/login.php?url=".urlencode($_SERVER['REQUEST_URI']));
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 랜딩페이지 정보 조회 및 소유권 확인
$sql = "SELECT * FROM landing_pages WHERE id = '{$id}'";
if (!$is_admin) {
    $sql .= " AND user_id = '{$member['mb_id']}'";
}
$landing = sql_fetch($sql);

if (!$landing) {
    alert('존재하지 않는 랜딩페이지이거나 볼 권한이 없습니다.');
}

// 템플릿 데이터
$html_content = $landing['html_content'];
$css_content = $landing['css_content'];
$js_content = $landing['js_content'];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $landing['name']; ?> - 미리보기</title>
    
    <?php if ($landing['meta_title']) { ?>
    <meta name="title" content="<?php echo $landing['meta_title']; ?>">
    <?php } ?>
    
    <?php if ($landing['meta_description']) { ?>
    <meta name="description" content="<?php echo $landing['meta_description']; ?>">
    <?php } ?>
    
    <!-- 부트스트랩 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- 템플릿 CSS -->
    <style>
        /* 기본 스타일 */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Noto Sans KR', sans-serif;
        }
        
        /* 미리보기 툴바 */
        .preview-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: #343a40;
            color: white;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
        }
        
        .preview-toolbar .btn-close {
            color: white;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .preview-toolbar .btn-close:hover {
            opacity: 0.8;
        }
        
        .preview-content {
            margin-top: 50px;
        }
        
        /* 반응형 프리뷰 버튼 */
        .device-buttons {
            display: flex;
            gap: 10px;
        }
        
        .device-btn {
            background: none;
            border: none;
            color: #adb5bd;
            cursor: pointer;
            padding: 4px 8px;
        }
        
        .device-btn.active {
            color: white;
            border-bottom: 2px solid white;
        }
        
        /* 템플릿 CSS */
        <?php echo $css_content; ?>
    </style>
</head>
<body>
    <!-- 미리보기 툴바 -->
    <div class="preview-toolbar">
        <div class="device-buttons">
            <button type="button" class="device-btn active" data-width="100%"><i class="ti ti-desktop"></i> 데스크톱</button>
            <button type="button" class="device-btn" data-width="768px"><i class="ti ti-tablet"></i> 태블릿</button>
            <button type="button" class="device-btn" data-width="375px"><i class="ti ti-mobile"></i> 모바일</button>
        </div>
        <span><?php echo $landing['name']; ?> - 미리보기</span>
        <div>
            <a href="landing_editor.php?id=<?php echo $landing['id']; ?>" class="btn btn-sm btn-primary me-2">편집하기</a>
            <button type="button" class="btn-close" onclick="window.close();">&times;</button>
        </div>
    </div>
    
    <!-- 미리보기 컨텐츠 -->
    <div class="preview-content">
        <?php echo $html_content; ?>
    </div>
    
    <!-- 부트스트랩 & jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- 템플릿 JavaScript -->
    <script>
        <?php echo $js_content; ?>
    </script>
    
    <!-- 미리보기 제어 스크립트 -->
    <script>
        $(document).ready(function() {
            // 반응형 디바이스 전환
            $('.device-btn').click(function() {
                // 활성 버튼 변경
                $('.device-btn').removeClass('active');
                $(this).addClass('active');
                
                // 컨텐츠 너비 조정
                var width = $(this).data('width');
                $('.preview-content').css({
                    'width': width,
                    'margin-left': width === '100%' ? '0' : 'auto',
                    'margin-right': width === '100%' ? '0' : 'auto',
                    'border': width === '100%' ? 'none' : '1px solid #dee2e6',
                    'margin-top': '50px'
                });
            });
        });
    </script>
</body>
</html>