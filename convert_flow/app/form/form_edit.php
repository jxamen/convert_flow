<?php
/**
 * 폼 수정 페이지
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 폼 ID
$form_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($form_id <= 0) {
    alert('잘못된 접근입니다.', 'form_list.php');
}

// 폼 모델 로드
require_once CF_MODEL_PATH . '/form.model.php';
$form_model = new FormModel();

// 폼 정보 조회
$form = $form_model->get_form($form_id);
if (!$form) {
    alert('존재하지 않는 폼입니다.', 'form_list.php');
}

// 권한 체크 (관리자가 아니면서 폼 소유자가 아닌 경우)
if (!$is_admin && $form['user_id'] != $member['id']) {
    alert('권한이 없습니다.', 'form_list.php');
}

// 폼 정보 수정 처리
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $form_data = array(
        'landing_page_id' => isset($_POST['landing_page_id']) ? intval($_POST['landing_page_id']) : null,
        'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
        'submit_button_text' => isset($_POST['submit_button_text']) ? trim($_POST['submit_button_text']) : '제출하기',
        'success_message' => isset($_POST['success_message']) ? trim($_POST['success_message']) : '폼이 성공적으로 제출되었습니다.',
        'redirect_url' => isset($_POST['redirect_url']) ? trim($_POST['redirect_url']) : '',
        'is_multi_step' => isset($_POST['is_multi_step']) ? 1 : 0,
        'auto_save_enabled' => isset($_POST['auto_save_enabled']) ? 1 : 0,
        'cta_type' => isset($_POST['cta_type']) ? trim($_POST['cta_type']) : 'button',
        'cta_button_bg_color' => isset($_POST['cta_button_bg_color']) ? trim($_POST['cta_button_bg_color']) : '#007bff',
        'cta_button_text_color' => isset($_POST['cta_button_text_color']) ? trim($_POST['cta_button_text_color']) : '#ffffff',
        'cta_button_size' => isset($_POST['cta_button_size']) ? trim($_POST['cta_button_size']) : 'medium',
        'cta_button_radius' => isset($_POST['cta_button_radius']) ? intval($_POST['cta_button_radius']) : 4,
        'cta_button_border_color' => isset($_POST['cta_button_border_color']) ? trim($_POST['cta_button_border_color']) : '#007bff',
        'complete_type' => isset($_POST['complete_type']) ? trim($_POST['complete_type']) : 'text',
        'complete_text_bg_color' => isset($_POST['complete_text_bg_color']) ? trim($_POST['complete_text_bg_color']) : '#d4edda',
        'complete_text_color' => isset($_POST['complete_text_color']) ? trim($_POST['complete_text_color']) : '#155724',
        'complete_text_size' => isset($_POST['complete_text_size']) ? trim($_POST['complete_text_size']) : 'medium'
    );
    
    // 유효성 검사
    if (empty($form_data['name'])) {
        $message = '<div class="alert alert-danger">폼 이름은 필수입니다.</div>';
    } else {
        // 폼 수정
        $result = $form_model->update_form($form_id, $form_data);
        
        if ($result) {
            $message = '<div class="alert alert-success">폼 정보가 성공적으로 업데이트되었습니다.</div>';
            
            // 폼 정보 다시 조회
            $form = $form_model->get_form($form_id);
        } else {
            $message = '<div class="alert alert-danger">폼 수정 중 오류가 발생했습니다.</div>';
        }
    }
}

// 페이지 제목
$page_title = "폼 수정: " . $form['name'];

// 랜딩페이지 목록 조회 (연결 옵션용)
$landing_pages = array();
$landing_sql = "SELECT id, name FROM {$cf_table_prefix}landing_pages";
if (!$is_admin) {
    $landing_sql .= " WHERE user_id = '{$member['id']}'";
}
$landing_sql .= " ORDER BY name ASC";
$landing_result = sql_query($landing_sql);
while ($row = sql_fetch_array($landing_result)) {
    $landing_pages[$row['id']] = $row['name'];
}

// 헤더 포함
include_once CF_PATH . '/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">폼 수정: <?php echo $form['name']; ?></h1>
    <p class="mb-4">폼의 기본 정보를 수정합니다. 필드 관리는 별도 페이지에서 가능합니다.</p>
    
    <?php echo $message; ?>
    
    <!-- 폼 수정 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">폼 기본 정보</h6>
        </div>
        <div class="card-body">
            <form method="post" action="form_edit.php?id=<?php echo $form_id; ?>" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">폼 이름 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($form['name']); ?>">
                    <small class="form-text text-muted">내부 관리용 이름입니다. 사용자에게 표시되지 않습니다.</small>
                </div>
                
                <div class="form-group">
                    <label for="landing_page_id">연결 랜딩페이지</label>
                    <select class="form-control" id="landing_page_id" name="landing_page_id">
                        <option value="">랜딩페이지 없음 (독립형 폼)</option>
                        <?php foreach ($landing_pages as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo ($form['landing_page_id'] == $id) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">이 폼을 특정 랜딩페이지에 연결할 수 있습니다. 비워두면 독립형 폼으로 사용됩니다.</small>
                </div>
                
                <div class="form-group">
                    <label for="submit_button_text">제출 버튼 텍스트</label>
                    <input type="text" class="form-control" id="submit_button_text" name="submit_button_text" value="<?php echo htmlspecialchars($form['submit_button_text']); ?>">
                    <small class="form-text text-muted">폼 제출 버튼에 표시될 텍스트입니다.</small>
                </div>
                
                <div class="form-group">
                    <label for="redirect_url">리다이렉트 URL</label>
                    <input type="url" class="form-control" id="redirect_url" name="redirect_url" value="<?php echo htmlspecialchars($form['redirect_url']); ?>">
                    <small class="form-text text-muted">폼 제출 후 사용자를 리다이렉트할 URL입니다. 비워두면 성공 메시지만 표시됩니다.</small>
                </div>
                
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="is_multi_step" name="is_multi_step" <?php echo ($form['is_multi_step']) ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="is_multi_step">다단계 폼 사용</label>
                    </div>
                    <small class="form-text text-muted">폼을 여러 단계로 나누어 사용자 부담을 줄입니다.</small>
                </div>
                
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="auto_save_enabled" name="auto_save_enabled" <?php echo ($form['auto_save_enabled']) ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="auto_save_enabled">자동 저장 활성화</label>
                    </div>
                    <small class="form-text text-muted">사용자가 폼 작성 중 이탈해도 데이터를 보존합니다.</small>
                </div>

                <hr>
                
                <!-- CTA 설정 -->
                <h5 class="text-primary font-weight-bold mb-3">제출 버튼 설정</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="cta_type">CTA 유형</label>
                            <select class="form-control" id="cta_type" name="cta_type">
                                <option value="button" <?php echo ($form['cta_type'] == 'button') ? 'selected' : ''; ?>>버튼</option>
                                <option value="image" <?php echo ($form['cta_type'] == 'image') ? 'selected' : ''; ?>>이미지</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- 버튼 설정 영역 - 첫 번째 행 -->
                    <div class="col-md-8" id="button_settings_row1" <?php echo ($form['cta_type'] == 'image') ? 'style="display: none;"' : ''; ?>>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cta_button_bg_color">배경색</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control" id="cta_button_bg_color" name="cta_button_bg_color" value="<?php echo $form['cta_button_bg_color']; ?>" style="width: 50px;">
                                        <input type="text" class="form-control" id="cta_button_bg_color_text" value="<?php echo $form['cta_button_bg_color']; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cta_button_text_color">글자색</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control" id="cta_button_text_color" name="cta_button_text_color" value="<?php echo $form['cta_button_text_color']; ?>" style="width: 50px;">
                                        <input type="text" class="form-control" id="cta_button_text_color_text" value="<?php echo $form['cta_button_text_color']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 버튼 설정 영역 - 두 번째 행 -->
                <div class="row" id="button_settings_row2" <?php echo ($form['cta_type'] == 'image') ? 'style="display: none;"' : ''; ?>>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="cta_button_size">버튼 크기</label>
                            <select class="form-control" id="cta_button_size" name="cta_button_size">
                                <option value="small" <?php echo ($form['cta_button_size'] == 'small') ? 'selected' : ''; ?>>작게</option>
                                <option value="medium" <?php echo ($form['cta_button_size'] == 'medium') ? 'selected' : ''; ?>>중간</option>
                                <option value="large" <?php echo ($form['cta_button_size'] == 'large') ? 'selected' : ''; ?>>크게</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="cta_button_radius">라운드 크기 (px)</label>
                            <div class="input-group">
                                <input type="range" class="custom-range" id="cta_button_radius_range" min="0" max="50" step="1" value="<?php echo $form['cta_button_radius']; ?>" style="margin-top: 10px;">
                                <input type="number" class="form-control ml-2" id="cta_button_radius" name="cta_button_radius" value="<?php echo $form['cta_button_radius']; ?>" style="width: 70px;">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="cta_button_border_color">테두리 색상</label>
                            <div class="input-group">
                                <input type="color" class="form-control" id="cta_button_border_color" name="cta_button_border_color" value="<?php echo $form['cta_button_border_color']; ?>" style="width: 50px;">
                                <input type="text" class="form-control" id="cta_button_border_color_text" value="<?php echo $form['cta_button_border_color']; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 이미지 설정 영역 -->
                <div class="row" id="image_settings" <?php echo ($form['cta_type'] == 'button') ? 'style="display: none;"' : ''; ?>>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="cta_image">CTA 이미지 업로드</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="cta_image" name="cta_image" accept="image/*">
                                <label class="custom-file-label" for="cta_image">이미지 선택...</label>
                            </div>
                            <small class="form-text text-muted">권장 크기: 200px × 50px, 최대 파일 크기: 1MB</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>미리보기</label>
                            <div class="image-preview mt-2 text-center border rounded p-2">
                                <?php if (!empty($form['cta_image_path'])): ?>
                                    <img src="<?php echo $form['cta_image_path']; ?>" class="img-fluid" style="max-height: 50px;" alt="CTA 이미지">
                                <?php else: ?>
                                    <p class="text-muted small mb-0">이미지를 업로드하면 여기에 미리보기가 표시됩니다.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 버튼 미리보기 -->
                <div class="row" id="button_preview_container" <?php echo ($form['cta_type'] == 'image') ? 'style="display: none;"' : ''; ?>>
                    <div class="col-12">
                        <div class="form-group">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-3">버튼 미리보기</h6>
                                    <button type="button" id="button_preview" class="btn" style="background-color: <?php echo $form['cta_button_bg_color']; ?>; color: <?php echo $form['cta_button_text_color']; ?>; border-color: <?php echo $form['cta_button_border_color']; ?>; border-radius: <?php echo $form['cta_button_radius']; ?>px;">
                                        <?php echo htmlspecialchars($form['submit_button_text']); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <!-- 완료 메시지 설정 -->
                <h5 class="text-primary font-weight-bold mb-3">완료 메시지 설정</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="complete_type">완료 메시지 유형</label>
                            <select class="form-control" id="complete_type" name="complete_type">
                                <option value="text" <?php echo ($form['complete_type'] == 'text') ? 'selected' : ''; ?>>텍스트</option>
                                <option value="image" <?php echo ($form['complete_type'] == 'image') ? 'selected' : ''; ?>>이미지</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- 텍스트 설정 영역 - 첫 번째 행 -->
                    <div class="col-md-8" id="complete_text_settings_row1" <?php echo ($form['complete_type'] == 'image') ? 'style="display: none;"' : ''; ?>>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="complete_text_bg_color">배경색</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control" id="complete_text_bg_color" name="complete_text_bg_color" value="<?php echo $form['complete_text_bg_color']; ?>" style="width: 50px;">
                                        <input type="text" class="form-control" id="complete_text_bg_color_text" value="<?php echo $form['complete_text_bg_color']; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="complete_text_color">글자색</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control" id="complete_text_color" name="complete_text_color" value="<?php echo $form['complete_text_color']; ?>" style="width: 50px;">
                                        <input type="text" class="form-control" id="complete_text_color_text" value="<?php echo $form['complete_text_color']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 텍스트 설정 영역 - 두 번째 행 -->
                <div class="row" id="complete_text_settings_row2" <?php echo ($form['complete_type'] == 'image') ? 'style="display: none;"' : ''; ?>>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="complete_text_size">글자 크기</label>
                            <select class="form-control" id="complete_text_size" name="complete_text_size">
                                <option value="small" <?php echo ($form['complete_text_size'] == 'small') ? 'selected' : ''; ?>>작게</option>
                                <option value="medium" <?php echo ($form['complete_text_size'] == 'medium') ? 'selected' : ''; ?>>중간</option>
                                <option value="large" <?php echo ($form['complete_text_size'] == 'large') ? 'selected' : ''; ?>>크게</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="success_message">완료 메시지 내용</label>
                            <textarea class="form-control" id="success_message" name="success_message" rows="2"><?php echo htmlspecialchars($form['success_message']); ?></textarea>
                            <small class="form-text text-muted">폼 제출 성공 시 표시될 메시지입니다. HTML을 사용할 수 있습니다.</small>
                        </div>
                    </div>
                </div>
                
                <!-- 이미지 설정 영역 -->
                <div class="row" id="complete_image_settings" <?php echo ($form['complete_type'] == 'text') ? 'style="display: none;"' : ''; ?>>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="complete_image">완료 메시지 이미지 업로드</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="complete_image" name="complete_image" accept="image/*">
                                <label class="custom-file-label" for="complete_image">이미지 선택...</label>
                            </div>
                            <small class="form-text text-muted">권장 크기: 500px × 300px, 최대 파일 크기: 2MB</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>미리보기</label>
                            <div class="complete-image-preview mt-2 text-center border rounded p-2">
                                <?php if (!empty($form['complete_image_path'])): ?>
                                    <img src="<?php echo $form['complete_image_path']; ?>" class="img-fluid" style="max-height: 100px;" alt="완료 메시지 이미지">
                                <?php else: ?>
                                    <p class="text-muted small mb-0">이미지를 업로드하면 여기에 미리보기가 표시됩니다.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 완료 메시지 미리보기 -->
                <div class="row" id="complete_text_preview_container" <?php echo ($form['complete_type'] == 'image') ? 'style="display: none;"' : ''; ?>>
                    <div class="col-12">
                        <div class="form-group">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-3">완료 메시지 미리보기</h6>
                                    <div id="complete_text_preview" class="alert" style="background-color: <?php echo $form['complete_text_bg_color']; ?>; color: <?php echo $form['complete_text_color']; ?>;">
                                        <?php echo $form['success_message']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="form-group text-center">
                    <a href="form_list.php" class="btn btn-secondary">취소</a>
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 저장
                    </button>
                    <a href="form_fields.php?form_id=<?php echo $form_id; ?>" class="btn btn-info">
                        <i class="fas fa-list"></i> 필드 관리
                    </a>
                    <a href="form_view.php?id=<?php echo $form_id; ?>" class="btn btn-success" target="_blank">
                        <i class="fas fa-eye"></i> 폼 보기
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 폼 통계 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">폼 통계</h6>
        </div>
        <div class="card-body">
            <?php
            // 리드 수 조회
            $lead_count = $form_model->count_leads($form_id);
            
            // 필드 수 조회
            $field_sql = "SELECT COUNT(*) as cnt FROM {$cf_table_prefix}form_fields WHERE form_id = " . $form_id;
            $field_row = sql_fetch($field_sql);
            $field_count = $field_row['cnt'];
            ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-body text-center">
                            <h3><?php echo number_format($lead_count); ?></h3>
                            <p class="text-muted">수집된 리드 수</p>
                            <a href="form_leads.php?form_id=<?php echo $form_id; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-database"></i> 리드 데이터 보기
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-body text-center">
                            <h3><?php echo number_format($field_count); ?></h3>
                            <p class="text-muted">폼 필드 수</p>
                            <a href="form_fields.php?form_id=<?php echo $form_id; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-list"></i> 필드 관리
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle"></i> 상세 통계 및 리드 데이터는 리드 데이터 페이지에서 확인할 수 있습니다.
            </div>
        </div>
    </div>
    
    <!-- 폼 임베드 코드 카드 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">폼 임베드 코드</h6>
        </div>
        <div class="card-body">
            <p>다음 코드를 사용하여 외부 웹사이트에 이 폼을 삽입할 수 있습니다.</p>
            
            <?php $embed_code = $form_model->get_embed_code($form_id); ?>
            
            <div class="form-group">
                <label for="iframe-code">iframe 방식</label>
                <textarea class="form-control" id="iframe-code" rows="3" readonly><?php echo htmlspecialchars($embed_code['iframe']); ?></textarea>
                <small class="form-text text-muted">가장 간단한 방식이지만, 스타일 통합이 어려울 수 있습니다.</small>
            </div>
            
            <div class="form-group">
                <label for="script-code">자바스크립트 방식 (권장)</label>
                <textarea class="form-control" id="script-code" rows="3" readonly><?php echo htmlspecialchars($embed_code['script']); ?></textarea>
                <small class="form-text text-muted">더 나은 통합 경험을 제공하며, 페이지 스타일과 자연스럽게 어울립니다.</small>
            </div>
            
            <div class="text-center">
                <button type="button" class="btn btn-sm btn-primary" onclick="copyToClipboard('iframe-code')">
                    <i class="fas fa-copy"></i> iframe 코드 복사
                </button>
                <button type="button" class="btn btn-sm btn-primary ml-2" onclick="copyToClipboard('script-code')">
                    <i class="fas fa-copy"></i> 스크립트 코드 복사
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 클립보드 복사 함수
    function copyToClipboard(elementId) {
        var el = document.getElementById(elementId);
        el.select();
        document.execCommand('copy');
        
        // 복사 성공 메시지
        alert('코드가 클립보드에 복사되었습니다.');
    }
    
    // CTA 유형 변경 시 설정 영역 전환
    $('#cta_type').change(function() {
        var ctaType = $(this).val();
        
        if (ctaType === 'button') {
            $('#button_settings_row1, #button_settings_row2, #button_preview_container').show();
            $('#image_settings').hide();
        } else {
            $('#button_settings_row1, #button_settings_row2, #button_preview_container').hide();
            $('#image_settings').show();
        }
    });
    
    // 완료 메시지 유형 변경 시 설정 영역 전환
    $('#complete_type').change(function() {
        var completeType = $(this).val();
        
        if (completeType === 'text') {
            $('#complete_text_settings_row1, #complete_text_settings_row2, #complete_text_preview_container').show();
            $('#complete_image_settings').hide();
        } else {
            $('#complete_text_settings_row1, #complete_text_settings_row2, #complete_text_preview_container').hide();
            $('#complete_image_settings').show();
        }
    });
    
    // 색상 변경 시 텍스트 필드에 반영
    $('#cta_button_bg_color').change(function() {
        var color = $(this).val();
        $('#cta_button_bg_color_text').val(color);
        updateButtonPreview();
    });
    
    $('#cta_button_text_color').change(function() {
        var color = $(this).val();
        $('#cta_button_text_color_text').val(color);
        updateButtonPreview();
    });
    
    $('#cta_button_border_color').change(function() {
        var color = $(this).val();
        $('#cta_button_border_color_text').val(color);
        updateButtonPreview();
    });
    
    // 텍스트 필드 변경 시 색상 필드 반영
    $('#cta_button_bg_color_text').change(function() {
        var color = $(this).val();
        $('#cta_button_bg_color').val(color);
        updateButtonPreview();
    });
    
    $('#cta_button_text_color_text').change(function() {
        var color = $(this).val();
        $('#cta_button_text_color').val(color);
        updateButtonPreview();
    });
    
    $('#cta_button_border_color_text').change(function() {
        var color = $(this).val();
        $('#cta_button_border_color').val(color);
        updateButtonPreview();
    });
    
    // 완료 메시지 색상 설정
    $('#complete_text_bg_color').change(function() {
        var color = $(this).val();
        $('#complete_text_bg_color_text').val(color);
        updateCompletePreview();
    });
    
    $('#complete_text_color').change(function() {
        var color = $(this).val();
        $('#complete_text_color_text').val(color);
        updateCompletePreview();
    });
    
    $('#complete_text_bg_color_text').change(function() {
        var color = $(this).val();
        $('#complete_text_bg_color').val(color);
        updateCompletePreview();
    });
    
    $('#complete_text_color_text').change(function() {
        var color = $(this).val();
        $('#complete_text_color').val(color);
        updateCompletePreview();
    });
    
    // 라운드 슬라이더 변경 시 숫자 필드 반영
    $('#cta_button_radius_range').on('input', function() {
        var radius = $(this).val();
        $('#cta_button_radius').val(radius);
        updateButtonPreview();
    });
    
    // 숫자 필드 변경 시 슬라이더 반영
    $('#cta_button_radius').change(function() {
        var radius = $(this).val();
        $('#cta_button_radius_range').val(radius);
        updateButtonPreview();
    });
    
    // 버튼 크기 변경 시 미리보기 업데이트
    $('#cta_button_size').change(function() {
        updateButtonPreview();
    });
    
    // 완료 메시지 크기 변경 시 미리보기 업데이트
    $('#complete_text_size').change(function() {
        updateCompletePreview();
    });
    
    // 제출 버튼 텍스트 변경 시 미리보기 업데이트
    $('#submit_button_text').keyup(function() {
        $('#button_preview').text($(this).val());
    });
    
    // 성공 메시지 변경 시 미리보기 업데이트
    $('#success_message').keyup(function() {
        $('#complete_text_preview').html($(this).val());
    });
    
    // CTA 이미지 업로드 시 미리보기
    $('#cta_image').change(function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('.image-preview').html('<img src="' + e.target.result + '" class="img-fluid" style="max-height: 50px;">');
            }
            reader.readAsDataURL(file);
            
            // 파일 이름 표시
            $(this).next('.custom-file-label').html(file.name);
        }
    });
    
    // 완료 이미지 업로드 시 미리보기
    $('#complete_image').change(function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('.complete-image-preview').html('<img src="' + e.target.result + '" class="img-fluid" style="max-height: 100px;">');
            }
            reader.readAsDataURL(file);
            
            // 파일 이름 표시
            $(this).next('.custom-file-label').html(file.name);
        }
    });
    
    // 버튼 미리보기 업데이트 함수
    function updateButtonPreview() {
        var bgColor = $('#cta_button_bg_color').val();
        var textColor = $('#cta_button_text_color').val();
        var borderColor = $('#cta_button_border_color').val();
        var radius = $('#cta_button_radius').val();
        var buttonSize = $('#cta_button_size').val();
        var buttonText = $('#submit_button_text').val() || '제출하기';
        
        var btnSizeClass = '';
        switch (buttonSize) {
            case 'small':
                btnSizeClass = 'btn-sm';
                break;
            case 'large':
                btnSizeClass = 'btn-lg';
                break;
            default:
                btnSizeClass = '';
        }
        
        $('#button_preview')
            .css('background-color', bgColor)
            .css('color', textColor)
            .css('border-color', borderColor)
            .css('border-radius', radius + 'px')
            .removeClass('btn-sm btn-lg')
            .addClass(btnSizeClass)
            .text(buttonText);
    }
    
    // 완료 메시지 미리보기 업데이트 함수
    function updateCompletePreview() {
        var bgColor = $('#complete_text_bg_color').val();
        var textColor = $('#complete_text_color').val();
        var textSize = $('#complete_text_size').val();
        var message = $('#success_message').val() || '폼이 성공적으로 제출되었습니다.';
        
        var textSizeClass = '';
        switch (textSize) {
            case 'small':
                textSizeClass = 'small';
                break;
            case 'large':
                textSizeClass = 'lead';
                break;
            default:
                textSizeClass = '';
        }
        
        $('#complete_text_preview')
            .css('background-color', bgColor)
            .css('color', textColor)
            .removeClass('small lead')
            .addClass(textSizeClass)
            .html(message);
    }
    
    // 초기 미리보기 업데이트
    updateButtonPreview();
    updateCompletePreview();
});

// 클립보드 복사 함수
function copyToClipboard(elementId) {
    var el = document.getElementById(elementId);
    el.select();
    document.execCommand('copy');
    
    // 복사 성공 메시지
    alert('코드가 클립보드에 복사되었습니다.');
}
</script>

<?php
// 푸터 포함
include_once CF_PATH . '/footer.php';
?>