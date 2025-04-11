<?php
/**
 * 블록 에디터 AJAX 처리 파일
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/convert_flow/include/_common.php';

// 요청 파라미터 확인
$action = isset($_POST['action']) ? $_POST['action'] : '';

// 응답 데이터 초기화
$response = array(
    'success' => false,
    'message' => '알 수 없는 오류가 발생했습니다.'
);

// 액션 분기 처리
switch ($action) {
    // 블록 템플릿 저장
    case 'save_template':
        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        $html_content = isset($_POST['html_content']) ? $_POST['html_content'] : '';
        $css_content = isset($_POST['css_content']) ? $_POST['css_content'] : '';
        $js_content = isset($_POST['js_content']) ? $_POST['js_content'] : '';
        
        // 이스케이프된 따옴표 처리
        $css_content = str_replace('\\\'', '\'', $css_content);
        $css_content = str_replace('\\"', '"', $css_content);
        
        // SQL 쿼리 직접 작성
        $sql = "UPDATE landing_block_templates SET 
                html_content = '" . sql_real_escape_string($html_content) . "',
                css_content = '" . sql_real_escape_string($css_content) . "',
                js_content = '" . sql_real_escape_string($js_content) . "'
                WHERE id = " . intval($template_id);
        
        $result = sql_query($sql);
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = '템플릿이 성공적으로 저장되었습니다.';
        } else {
            $response['message'] = '템플릿 저장 중 오류가 발생했습니다. SQL: ' . $sql;
        }
        break;
        
    // 블록 인스턴스 저장
    case 'save_block':
        $block_id = isset($_POST['block_id']) ? intval($_POST['block_id']) : 0;
        $custom_html = isset($_POST['custom_html']) ? $_POST['custom_html'] : '';
        $custom_css = isset($_POST['custom_css']) ? $_POST['custom_css'] : '';
        
        // 이스케이프된 따옴표 처리
        $custom_css = str_replace('\\\'', '\'', $custom_css);
        $custom_css = str_replace('\\"', '"', $custom_css);
        
        // SQL 쿼리 직접 작성
        $sql = "UPDATE landing_page_block_instances SET 
                custom_html = '" . sql_real_escape_string($custom_html) . "',
                custom_css = '" . sql_real_escape_string($custom_css) . "',
                updated_at = NOW()
                WHERE id = " . intval($block_id);
        
        $result = sql_query($sql);
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = '블록이 성공적으로 저장되었습니다.';
        } else {
            $response['message'] = '블록 저장 중 오류가 발생했습니다. SQL: ' . $sql;
        }
        break;
        
    // 블록 데이터 가져오기
    case 'get_block_data':
        $block_id = isset($_POST['block_id']) ? intval($_POST['block_id']) : 0;
        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        
        if ($block_id > 0) {
            // 블록 정보 조회
            $sql = "SELECT * FROM landing_page_block_instances WHERE id = " . intval($block_id);
            $block = sql_fetch($sql);
            
            if ($block) {
                // 템플릿 정보 조회
                $sql = "SELECT * FROM landing_block_templates WHERE id = " . intval($block['block_template_id']);
                $template = sql_fetch($sql);
                
                $html_content = !empty($block['custom_html']) ? $block['custom_html'] : $template['html_content'];
                $css_content = !empty($block['custom_css']) ? $block['custom_css'] : $template['css_content'];
                
                // 이스케이프된 따옴표 처리
                $css_content = str_replace('\\\'', '\'', $css_content);
                $css_content = str_replace('\\"', '"', $css_content);
                
                $response['success'] = true;
                $response['block'] = $block;
                $response['template'] = $template;
                $response['html_content'] = $html_content;
                $response['css_content'] = $css_content;
                $response['message'] = '블록 데이터를 성공적으로 불러왔습니다.';
            } else {
                $response['message'] = '존재하지 않는 블록입니다.';
            }
        } else if ($template_id > 0) {
            // 템플릿 정보 조회
            $sql = "SELECT * FROM landing_block_templates WHERE id = " . intval($template_id);
            $template = sql_fetch($sql);
            
            if ($template) {
                // 이스케이프된 따옴표 처리
                $css_content = $template['css_content'];
                $css_content = str_replace('\\\'', '\'', $css_content);
                $css_content = str_replace('\\"', '"', $css_content);
                
                $response['success'] = true;
                $response['template'] = $template;
                $response['html_content'] = $template['html_content'];
                $response['css_content'] = $css_content;
                $response['js_content'] = $template['js_content'];
                $response['message'] = '템플릿 데이터를 성공적으로 불러왔습니다.';
            } else {
                $response['message'] = '존재하지 않는 템플릿입니다.';
            }
        } else {
            $response['message'] = '유효하지 않은 요청입니다.';
        }
        break;
        
    default:
        $response['message'] = '지원되지 않는 액션입니다.';
        break;
}

// JSON 응답 반환
header('Content-Type: application/json');
echo json_encode($response);
exit;