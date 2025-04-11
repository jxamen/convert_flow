<?php
/**
 * 폼 복제 처리
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

// 폼 복제
$new_form_id = $form_model->duplicate_form($form_id, $member['id']);

if ($new_form_id) {
    alert('폼이 복제되었습니다.', 'form_edit.php?id=' . $new_form_id);
} else {
    alert('폼 복제 중 오류가 발생했습니다.', 'form_list.php');
}
?>