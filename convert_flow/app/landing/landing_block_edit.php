<?php
/**
 * 랜딩페이지 블록 편집 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 페이지 제목
$page_title = "블록 편집";

// 관리자 권한 체크
if (!$is_admin) {
    alert('관리자만 접근할 수 있습니다.');
    goto_url(CF_URL);
}

// 블록 ID
$block_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($block_id <= 0) {
    alert('유효하지 않은 블록입니다.');
    exit;
}

// 블록 모델 로드
require_once CF_MODEL_PATH . '/landing_block.model.php';
$block_model = new LandingBlockModel();

// 블록 정보 조회
$block = $block_model->get_block_instance($block_id);
if (!$block) {
    alert('존재하지 않는 블록입니다.');
    exit;
}

// 랜딩페이지 정보 조회
$landing_id = $block['landing_page_id'];
$sql = "SELECT * FROM {$cf_table_prefix}landing_pages WHERE id = '$landing_id'";
$landing_page = sql_fetch($sql);

if (!$landing_page) {
    alert('연결된 랜딩페이지를 찾을 수 없습니다.');
    exit;
}

// 블록 템플릿 정보 조회
$template_id = $block['block_template_id'];
$sql = "SELECT lbt.*, lbty.name as type_name, lbty.category 
        FROM {$cf_table_prefix}landing_block_templates lbt
        JOIN {$cf_table_prefix}landing_block_types lbty ON lbt.block_type_id = lbty.id
        WHERE lbt.id = '$template_id'";
$template = sql_fetch($sql);

if (!$template) {
    alert('블록 템플릿을 찾을 수 없습니다.');
    exit;
}

// 블록 설정 정보
$settings = !empty($block['settings']) ? json_decode($block['settings'], true) : array();
$default_settings = !empty($template['default_settings']) ? json_decode($template['default_settings'], true) : array();

// 병합된 설정 (기본값 + 사용자 설정)
$merged_settings = array_merge($default_settings, $settings);

// 폼 목록 조회 (이미지/동영상/폼 블록 등에 사용)
$forms = array();
$sql = "SELECT id, name FROM {$cf_table_prefix}forms WHERE user_id = '{$member['id']}' ORDER BY name";
$result = sql_query($sql);
while ($row = sql_fetch_array($result)) {
    $forms[$row['id']] = $row['name'];
}

// 액션 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_block') {
    // 블록 설정 데이터
    $block_settings = array();
    
    // 파일 업로드 처리 (이미지 블록)
    if (isset($_FILES['image']) && $_FILES['image']['name']) {
        $upload_dir = CF_PATH . '/uploads/blocks/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0755, true);
        }
        
        $upload_file = $upload_dir . basename($_FILES['image']['name']);
        $upload_url = CF_URL . '/uploads/blocks/' . basename($_FILES['image']['name']);
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_file)) {
            $block_settings['image_url'] = $upload_url;
        }
    }
    
    // 텍스트 설정 처리
    if (isset($_POST['heading'])) {
        $block_settings['heading'] = $_POST['heading'];
    }
    
    if (isset($_POST['subheading'])) {
        $block_settings['subheading'] = $_POST['subheading'];
    }
    
    if (isset($_POST['content'])) {
        $block_settings['content'] = $_POST['content'];
    }
    
    // 이미지 설정 처리
    if (isset($_POST['image_url']) && !isset($block_settings['image_url'])) {
        $block_settings['image_url'] = $_POST['image_url'];
    }
    
    if (isset($_POST['image_alt'])) {
        $block_settings['image_alt'] = $_POST['image_alt'];
    }
    
    if (isset($_POST['image_caption'])) {
        $block_settings['image_caption'] = $_POST['image_caption'];
    }
    
    // 비디오 설정 처리
    if (isset($_POST['video_url'])) {
        $block_settings['video_url'] = $_POST['video_url'];
    }
    
    if (isset($_POST['video_poster'])) {
        $block_settings['video_poster'] = $_POST['video_poster'];
    }
    
    // 폼 설정 처리
    if (isset($_POST['form_id'])) {
        $block_settings['form_id'] = $_POST['form_id'];
    }
    
    // 버튼 설정 처리
    if (isset($_POST['button_text'])) {
        $block_settings['button_text'] = $_POST['button_text'];
    }
    
    if (isset($_POST['button_url'])) {
        $block_settings['button_url'] = $_POST['button_url'];
    }
    
    if (isset($_POST['button_style'])) {
        $block_settings['button_style'] = $_POST['button_style'];
    }
    
    // 갤러리 설정 처리
    if (isset($_POST['gallery_images']) && is_array($_POST['gallery_images'])) {
        $block_settings['gallery_images'] = $_POST['gallery_images'];
    }
    
    // 사용자 정의 코드
    $custom_html = isset($_POST['custom_html']) ? $_POST['custom_html'] : '';
    $custom_css = isset($_POST['custom_css']) ? $_POST['custom_css'] : '';
    $custom_js = isset($_POST['custom_js']) ? $_POST['custom_js'] : '';
    
    // 블록 업데이트
    $update_data = array(
        'settings' => json_encode($block_settings),
        'custom_html' => $custom_html,
        'custom_css' => $custom_css,
        'custom_js' => $custom_js
    );
    
    $result = $block_model->update_block_instance($block_id, $update_data);
    
    if ($result) {
        $msg = urlencode('블록이 성공적으로 업데이트되었습니다.');
        goto_url(CF_LANDING_URL . "/landing_page_blocks.php?landing_id=$landing_id&msg=$msg");
    } else {
        alert('블록 업데이트 중 오류가 발생했습니다.');
    }
}

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">블록 편집</h1>
    <p class="mb-4">'<?php echo $template['name']; ?>' 블록을 편집합니다.</p>
    
    <div class="row">
        <!-- 블록 설정 -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">블록 설정</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_block">
                        
                        <!-- 공통 설정 영역 (블록 타입별로 다르게 표시) -->
                        <?php if ($template['type_name'] === 'heading' || $template['type_name'] === 'text') { ?>
                            <!-- 제목 및 텍스트 블록 설정 -->
                            <div class="form-group">
                                <label for="heading"><strong>제목</strong></label>
                                <input type="text" class="form-control" id="heading" name="heading" value="<?php echo isset($merged_settings['heading']) ? $merged_settings['heading'] : ''; ?>">
                            </div>
                            
                            <?php if ($template['type_name'] === 'heading') { ?>
                                <div class="form-group">
                                    <label for="subheading"><strong>부제목</strong></label>
                                    <input type="text" class="form-control" id="subheading" name="subheading" value="<?php echo isset($merged_settings['subheading']) ? $merged_settings['subheading'] : ''; ?>">
                                </div>
                            <?php } ?>
                            
                            <div class="form-group">
                                <label for="content"><strong>내용</strong></label>
                                <textarea class="form-control" id="content" name="content" rows="5"><?php echo isset($merged_settings['content']) ? $merged_settings['content'] : ''; ?></textarea>
                            </div>
                            
                        <?php } else if ($template['type_name'] === 'image') { ?>
                            <!-- 이미지 블록 설정 -->
                            <div class="form-group">
                                <label for="image"><strong>이미지 업로드</strong></label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="image" name="image" accept="image/*">
                                    <label class="custom-file-label" for="image">이미지 선택</label>
                                </div>
                                <small class="form-text text-muted">새 이미지를 업로드하려면 파일을 선택하세요. 업로드하지 않으면 기존 이미지가 유지됩니다.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="image_url"><strong>이미지 URL</strong></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="image_url" name="image_url" value="<?php echo isset($merged_settings['image_url']) ? $merged_settings['image_url'] : ''; ?>">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" onclick="openMediaLibrary('image_url')">미디어 라이브러리</button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">이미지 URL을 직접 입력하거나 미디어 라이브러리에서 선택할 수 있습니다.</small>
                            </div>
                            
                            <?php if (isset($merged_settings['image_url']) && !empty($merged_settings['image_url'])) { ?>
                                <div class="form-group">
                                    <label><strong>현재 이미지</strong></label>
                                    <div class="mb-2">
                                        <img src="<?php echo $merged_settings['image_url']; ?>" alt="현재 이미지" class="img-thumbnail" style="max-height: 200px;">
                                    </div>
                                </div>
                            <?php } ?>
                            
                            <div class="form-group">
                                <label for="image_alt"><strong>대체 텍스트</strong></label>
                                <input type="text" class="form-control" id="image_alt" name="image_alt" value="<?php echo isset($merged_settings['image_alt']) ? $merged_settings['image_alt'] : ''; ?>">
                                <small class="form-text text-muted">이미지를 설명하는 대체 텍스트입니다. 접근성을 위해 반드시 입력해주세요.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="image_caption"><strong>이미지 캡션</strong></label>
                                <input type="text" class="form-control" id="image_caption" name="image_caption" value="<?php echo isset($merged_settings['image_caption']) ? $merged_settings['image_caption'] : ''; ?>">
                            </div>
                            
                        <?php } else if ($template['type_name'] === 'video') { ?>
                            <!-- 비디오 블록 설정 -->
                            <div class="form-group">
                                <label for="video_url"><strong>비디오 URL</strong></label>
                                <input type="text" class="form-control" id="video_url" name="video_url" value="<?php echo isset($merged_settings['video_url']) ? $merged_settings['video_url'] : ''; ?>">
                                <small class="form-text text-muted">YouTube, Vimeo 등의 비디오 URL 또는 직접 업로드한 비디오 파일 URL을 입력하세요.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="video_poster"><strong>썸네일 이미지 URL</strong></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="video_poster" name="video_poster" value="<?php echo isset($merged_settings['video_poster']) ? $merged_settings['video_poster'] : ''; ?>">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" onclick="openMediaLibrary('video_poster')">미디어 라이브러리</button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">비디오가 로드되기 전에 표시될 썸네일 이미지입니다.</small>
                            </div>
                            
                            <?php if (isset($merged_settings['video_url']) && !empty($merged_settings['video_url'])) { ?>
                                <div class="form-group">
                                    <label><strong>비디오 미리보기</strong></label>
                                    <div class="embed-responsive embed-responsive-16by9">
                                        <?php
                                        $video_url = $merged_settings['video_url'];
                                        
                                        // YouTube 비디오인 경우
                                        if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                                            preg_match('/(?:youtube\\.com\\/(?:[^\\/]+\\/.+\\/|(?:v|e(?:mbed)?)\\/|.*[?&]v=)|youtu\\.be\\/)([^"&?\\/\\s]{11})/', $video_url, $matches);
                                            if (isset($matches[1])) {
                                                $youtube_id = $matches[1];
                                                echo '<iframe class="embed-responsive-item" src="https://www.youtube.com/embed/' . $youtube_id . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                                            }
                                        }
                                        // Vimeo 비디오인 경우
                                        else if (strpos($video_url, 'vimeo.com') !== false) {
                                            preg_match('/vimeo\\.com\\/(?:channels\\/(?:\\w+\\/)?|groups\\/(?:[^\\/]*)\\/videos\\/|album\\/(?:\\d+)\\/video\\/|)(\\d+)(?:$|\\/|\\?)/', $video_url, $matches);
                                            if (isset($matches[1])) {
                                                $vimeo_id = $matches[1];
                                                echo '<iframe class="embed-responsive-item" src="https://player.vimeo.com/video/' . $vimeo_id . '" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
                                            }
                                        }
                                        // 직접 업로드한 비디오인 경우
                                        else {
                                            $poster = isset($merged_settings['video_poster']) ? ' poster="' . $merged_settings['video_poster'] . '"' : '';
                                            echo '<video class="embed-responsive-item" controls' . $poster . '><source src="' . $video_url . '"></video>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php } ?>
                            
                        <?php } else if ($template['type_name'] === 'form') { ?>
                            <!-- 폼 블록 설정 -->
                            <div class="form-group">
                                <label for="form_id"><strong>연결할 폼 선택</strong></label>
                                <select class="form-control" id="form_id" name="form_id">
                                    <option value="">선택하세요</option>
                                    <?php foreach ($forms as $id => $name) { ?>
                                        <option value="<?php echo $id; ?>" <?php echo (isset($merged_settings['form_id']) && $merged_settings['form_id'] == $id) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                    <?php } ?>
                                </select>
                                <small class="form-text text-muted">연결할 폼이 없다면 먼저 폼을 생성해주세요.</small>
                            </div>
                            
                            <?php if (empty($forms)) { ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 사용 가능한 폼이 없습니다. <a href="<?php echo CF_FORM_URL; ?>/form_create.php" target="_blank">폼 생성하기</a>
                                </div>
                            <?php } ?>
                            
                        <?php } else if ($template['type_name'] === 'button') { ?>
                            <!-- 버튼 블록 설정 -->
                            <div class="form-group">
                                <label for="button_text"><strong>버튼 텍스트</strong></label>
                                <input type="text" class="form-control" id="button_text" name="button_text" value="<?php echo isset($merged_settings['button_text']) ? $merged_settings['button_text'] : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="button_url"><strong>버튼 URL</strong></label>
                                <input type="text" class="form-control" id="button_url" name="button_url" value="<?php echo isset($merged_settings['button_url']) ? $merged_settings['button_url'] : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="button_style"><strong>버튼 스타일</strong></label>
                                <select class="form-control" id="button_style" name="button_style">
                                    <option value="primary" <?php echo (isset($merged_settings['button_style']) && $merged_settings['button_style'] == 'primary') ? 'selected' : ''; ?>>기본 (블루)</option>
                                    <option value="secondary" <?php echo (isset($merged_settings['button_style']) && $merged_settings['button_style'] == 'secondary') ? 'selected' : ''; ?>>보조 (그레이)</option>
                                    <option value="success" <?php echo (isset($merged_settings['button_style']) && $merged_settings['button_style'] == 'success') ? 'selected' : ''; ?>>성공 (그린)</option>
                                    <option value="danger" <?php echo (isset($merged_settings['button_style']) && $merged_settings['button_style'] == 'danger') ? 'selected' : ''; ?>>위험 (레드)</option>
                                    <option value="warning" <?php echo (isset($merged_settings['button_style']) && $merged_settings['button_style'] == 'warning') ? 'selected' : ''; ?>>경고 (옐로우)</option>
                                    <option value="info" <?php echo (isset($merged_settings['button_style']) && $merged_settings['button_style'] == 'info') ? 'selected' : ''; ?>>정보 (라이트 블루)</option>
                                    <option value="light" <?php echo (isset($merged_settings['button_style']) && $merged_settings['button_style'] == 'light') ? 'selected' : ''; ?>>밝은 (화이트)</option>
                                    <option value="dark" <?php echo (isset($merged_settings['button_style']) && $merged_settings['button_style'] == 'dark') ? 'selected' : ''; ?>>어두운 (블랙)</option>
                                    <option value="outline-primary" <?php echo (isset($merged_settings['button_style']) && $merged_settings['button_style'] == 'outline-primary') ? 'selected' : ''; ?>>테두리 기본</option>
                                    <option value="outline-secondary" <?php echo (isset($merged_settings['button_style']) && $merged_settings['button_style'] == 'outline-secondary') ? 'selected' : ''; ?>>테두리 보조</option>
                                </select>
                            </div>
                            
                        <?php } else if ($template['type_name'] === 'gallery') { ?>
                            <!-- 갤러리 블록 설정 -->
                            <div id="gallery-container">
                                <div class="form-group">
                                    <label><strong>갤러리 이미지</strong></label>
                                    <div class="gallery-items">
                                        <?php
                                        $gallery_images = isset($merged_settings['gallery_images']) ? $merged_settings['gallery_images'] : array();
                                        if (!empty($gallery_images)) {
                                            foreach ($gallery_images as $index => $image) {
                                                ?>
                                                <div class="gallery-item mb-3 border p-2">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <img src="<?php echo $image['url']; ?>" class="img-thumbnail" alt="">
                                                        </div>
                                                        <div class="col-md-8">
                                                            <div class="form-group">
                                                                <label>이미지 URL</label>
                                                                <div class="input-group">
                                                                    <input type="text" class="form-control" name="gallery_images[<?php echo $index; ?>][url]" value="<?php echo $image['url']; ?>">
                                                                    <div class="input-group-append">
                                                                        <button class="btn btn-outline-secondary" type="button" onclick="openMediaLibrary('gallery_images[<?php echo $index; ?>][url]')">선택</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>대체 텍스트</label>
                                                                <input type="text" class="form-control" name="gallery_images[<?php echo $index; ?>][alt]" value="<?php echo isset($image['alt']) ? $image['alt'] : ''; ?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>캡션</label>
                                                                <input type="text" class="form-control" name="gallery_images[<?php echo $index; ?>][caption]" value="<?php echo isset($image['caption']) ? $image['caption'] : ''; ?>">
                                                            </div>
                                                            <button type="button" class="btn btn-danger btn-sm remove-gallery-item"><i class="fas fa-trash"></i> 삭제</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </div>
                                    <button type="button" class="btn btn-success btn-sm mt-2" id="add-gallery-item"><i class="fas fa-plus"></i> 이미지 추가</button>
                                </div>
                            </div>
                            
                            <template id="gallery-item-template">
                                <div class="gallery-item mb-3 border p-2">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <img src="" class="img-thumbnail" alt="">
                                        </div>
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label>이미지 URL</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" name="gallery_images[INDEX][url]" value="">
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary" type="button" onclick="openMediaLibrary('gallery_images[INDEX][url]')">선택</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>대체 텍스트</label>
                                                <input type="text" class="form-control" name="gallery_images[INDEX][alt]" value="">
                                            </div>
                                            <div class="form-group">
                                                <label>캡션</label>
                                                <input type="text" class="form-control" name="gallery_images[INDEX][caption]" value="">
                                            </div>
                                            <button type="button" class="btn btn-danger btn-sm remove-gallery-item"><i class="fas fa-trash"></i> 삭제</button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            
                        <?php } else { ?>
                            <!-- 기타 블록 타입에 대한 설정 -->
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 이 블록 타입(<?php echo $template['type_name']; ?>)에 대한 특별한 설정이 없습니다. 사용자 정의 HTML, CSS, JS를 사용하여 블록을 편집할 수 있습니다.
                            </div>
                        <?php } ?>
                        
                        <!-- 고급 설정 영역 (사용자 정의 코드) -->
                        <div class="mt-4">
                            <h5 class="border-bottom pb-2">고급 설정</h5>
                            
                            <div class="form-group">
                                <label for="custom_html"><strong>사용자 정의 HTML</strong></label>
                                <textarea class="form-control code-editor" id="custom_html" name="custom_html" rows="6"><?php echo $block['custom_html']; ?></textarea>
                                <small class="form-text text-muted">이 필드를 비워두면 기본 템플릿이 사용됩니다.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="custom_css"><strong>사용자 정의 CSS</strong></label>
                                <textarea class="form-control code-editor" id="custom_css" name="custom_css" rows="6"><?php echo $block['custom_css']; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="custom_js"><strong>사용자 정의 JavaScript</strong></label>
                                <textarea class="form-control code-editor" id="custom_js" name="custom_js" rows="6"><?php echo $block['custom_js']; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-group mb-0 mt-4 text-right">
                            <a href="<?php echo CF_LANDING_URL; ?>/landing_page_blocks.php?landing_id=<?php echo $landing_id; ?>" class="btn btn-secondary">취소</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 저장</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- 미리보기 -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">블록 미리보기</h6>
                </div>
                <div class="card-body">
                    <div class="block-preview mb-2">
                        <?php
                        // 템플릿 HTML 가져오기
                        $template_html = $template['html_content'];
                        
                        // 사용자 정의 HTML이 있으면 사용
                        $block_html = !empty($block['custom_html']) ? $block['custom_html'] : $template_html;
                        
                        // 설정 값으로 템플릿 변수 대체
                        if (!empty($merged_settings)) {
                            foreach ($merged_settings as $key => $value) {
                                // HTML 속성에서의 템플릿 변수 처리 (src="{{image_url}}" 같은 경우)
                               $pattern1 = '/([a-zA-Z0-9_-]+=["\'])\{\{' . preg_quote($key, '/') . '\}\}(["\'])/'; 
                                $block_html = preg_replace($pattern1, '$1' . $value . '$2', $block_html);
                                
                                // 일반 템플릿 변수 처리
                                $pattern2 = '/\{\{' . preg_quote($key, '/') . '\}\}/'; 
                                $block_html = preg_replace($pattern2, $value, $block_html);
                            }
                        }
                        
                        // 남은 템플릿 변수 제거
                        $block_html = preg_replace('/\{\{[^\}]+\}\}/', '', $block_html);
                        
                        // 미리보기 출력
                        echo $block_html;
                        ?>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="<?php echo CF_LANDING_URL; ?>/landing_page_preview.php?id=<?php echo $landing_id; ?>" class="btn btn-info btn-sm" target="_blank">
                            <i class="fas fa-eye"></i> 전체 랜딩페이지 미리보기
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- 블록 템플릿 정보 -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">블록 정보</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>타입:</strong> <?php echo $template['type_name']; ?>
                    </div>
                    <div class="mb-2">
                        <strong>카테고리:</strong> <?php echo $template['category']; ?>
                    </div>
                    <div class="mb-2">
                        <strong>템플릿 이름:</strong> <?php echo $template['name']; ?>
                    </div>
                    <?php if (!empty($template['thumbnail'])) { ?>
                        <div class="my-3">
                            <img src="<?php echo $template['thumbnail']; ?>" alt="<?php echo $template['name']; ?>" class="img-thumbnail">
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 미디어 라이브러리 모달 (예시) -->
<div class="modal fade" id="mediaLibraryModal" tabindex="-1" role="dialog" aria-labelledby="mediaLibraryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mediaLibraryModalLabel">미디어 라이브러리</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="mediaLibraryContent">
                    <p class="text-center py-3">미디어 라이브러리를 로드 중입니다...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<!-- Code Editor JS - CodeMirror -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/theme/monokai.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/mode/css/css.min.js"></script>

<script>
// 코드 에디터 초기화
var htmlEditor, cssEditor, jsEditor;

$(document).ready(function() {
    // CodeMirror 초기화
    initCodeEditors();
    
    // 커스텀 파일 입력 필드 처리
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
    
    // 이미지 URL 입력 시 미리보기 업데이트
    $('#image_url').on('change', function() {
        var imageUrl = $(this).val();
        if (imageUrl) {
            if ($('.block-preview img').length) {
                $('.block-preview img').attr('src', imageUrl);
            }
        }
    });
    
    // 갤러리 아이템 추가
    $('#add-gallery-item').on('click', function() {
        var template = $('#gallery-item-template').html();
        var index = $('.gallery-item').length;
        template = template.replace(/INDEX/g, index);
        $('.gallery-items').append(template);
        
        // 이벤트 재바인딩
        bindGalleryEvents();
    });
    
    // 갤러리 아이템 이벤트 바인딩
    bindGalleryEvents();
    
    // 비디오 URL 변경 시 미리보기 업데이트
    $('#video_url').on('change', function() {
        // 비디오 미리보기 업데이트 로직이 필요합니다.
    });
});

// 갤러리 이벤트 바인딩
function bindGalleryEvents() {
    // 갤러리 아이템 삭제
    $('.remove-gallery-item').off('click').on('click', function() {
        $(this).closest('.gallery-item').remove();
    });
    
    // 갤러리 이미지 URL 변경 시 미리보기 업데이트
    $('.gallery-items input[name$="[url]"]').off('change').on('change', function() {
        var imageUrl = $(this).val();
        if (imageUrl) {
            $(this).closest('.gallery-item').find('img').attr('src', imageUrl);
        }
    });
}

// 코드 에디터 초기화
function initCodeEditors() {
    // HTML 에디터
    if (document.getElementById('custom_html')) {
        htmlEditor = CodeMirror.fromTextArea(document.getElementById('custom_html'), {
            mode: 'htmlmixed',
            theme: 'monokai',
            lineNumbers: true,
            lineWrapping: true
        });
    }
    
    // CSS 에디터
    if (document.getElementById('custom_css')) {
        cssEditor = CodeMirror.fromTextArea(document.getElementById('custom_css'), {
            mode: 'css',
            theme: 'monokai',
            lineNumbers: true,
            lineWrapping: true
        });
    }
    
    // JavaScript 에디터
    if (document.getElementById('custom_js')) {
        jsEditor = CodeMirror.fromTextArea(document.getElementById('custom_js'), {
            mode: 'javascript',
            theme: 'monokai',
            lineNumbers: true,
            lineWrapping: true
        });
    }
}

// 폼 제출 전 에디터 내용 동기화
function syncCodeEditors() {
    if (htmlEditor) {
        htmlEditor.save();
    }
    if (cssEditor) {
        cssEditor.save();
    }
    if (jsEditor) {
        jsEditor.save();
    }
}

// 미디어 라이브러리 열기
function openMediaLibrary(targetField) {
    $('#mediaLibraryModal').modal('show');
    
    // 미디어 라이브러리 콘텐츠 로드
    $.ajax({
        url: "<?php echo CF_URL; ?>/media_library.php",
        type: "GET",
        data: {
            target: targetField,
            mode: 'ajax'
        },
        success: function(response) {
            $('#mediaLibraryContent').html(response);
        },
        error: function() {
            $('#mediaLibraryContent').html('<div class="alert alert-danger">미디어 라이브러리를 로드하는 중 오류가 발생했습니다.</div>');
        }
    });
}

// 미디어 라이브러리에서 파일 선택 완료 핸들러
function selectMedia(url, targetField) {
    $('#' + targetField).val(url);
    
    // 미리보기 업데이트 (해당하는 경우)
    if (targetField === 'image_url') {
        if ($('.block-preview img').length) {
            $('.block-preview img').attr('src', url);
        }
    } else if (targetField === 'video_poster') {
        // 비디오 썸네일 업데이트
    } else if (targetField.includes('gallery_images')) {
        // 갤러리 이미지 업데이트
        var input = $('input[name="' + targetField + '"]');
        if (input.length) {
            input.val(url);
            input.closest('.gallery-item').find('img').attr('src', url);
        }
    }
    
    $('#mediaLibraryModal').modal('hide');
}
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>