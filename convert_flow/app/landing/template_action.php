<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/convert_flow/includes/_common.php");
include_once("./template_config.php");

// 사용자 권한 체크
if (!$is_member) {
    alert("로그인이 필요합니다.");
    exit;
}

$action = isset($_POST['action']) ? trim($_POST['action']) : '';
if (!$action) {
    alert("잘못된 접근입니다.");
    exit;
}

// 관리자가 아닌 경우 회원 ID 추가
if ($is_admin != "super") {
    $sql_mb_id = " and mb_id = '{$member['mb_id']}'";
} else {
    $sql_mb_id = "";
}

// 템플릿 생성 처리
if ($action == "create_template") {
    $template_name = isset($_POST['template_name']) ? trim($_POST['template_name']) : '';
    $template_type = isset($_POST['template_type']) ? trim($_POST['template_type']) : '';
    $template_content = isset($_POST['template_content']) ? $_POST['template_content'] : '';
    $template_description = isset($_POST['template_description']) ? trim($_POST['template_description']) : '';
    $template_industry = isset($_POST['template_industry']) ? trim($_POST['template_industry']) : '';
    
    if (empty($template_name)) {
        echo json_encode(array('status' => '-100', 'result' => '템플릿 이름을 입력해 주세요.'));
        exit;
    }
    
    if (empty($template_type)) {
        echo json_encode(array('status' => '-100', 'result' => '템플릿 유형을 선택해 주세요.'));
        exit;
    }
    
    if (empty($template_content)) {
        echo json_encode(array('status' => '-100', 'result' => '템플릿 내용을 입력해 주세요.'));
        exit;
    }
    
    // 중복 템플릿 이름 체크
    $sql = "select count(*) as cnt 
            from campaign_templates 
            where user_id = '{$member['mb_id']}' 
            and name = '{$template_name}'";
    $row = sql_fetch($sql);
    
    if ($row['cnt'] > 0) {
        echo json_encode(array('status' => '-100', 'result' => '이미 사용 중인 템플릿 이름입니다.'));
        exit;
    }
    
    // JSON 데이터 준비
    $template_data = array(
        'type' => $template_type,
        'content' => $template_content,
        'industry' => $template_industry
    );
    
    $json_data = json_encode($template_data);
    
    // 템플릿 저장
    $sql = "insert into campaign_templates
            set
                user_id             = '{$member['mb_id']}',
                name                = '{$template_name}',
                target_region       = '{$_POST['target_region']}',
                daily_budget        = '{$_POST['daily_budget']}',
                cpa_goal            = '{$_POST['cpa_goal']}',
                template_data       = '{$json_data}',
                created_at          = NOW(),
                updated_at          = NOW()";
    
    $result = sql_query($sql);
    
    if ($result) {
        $template_id = sql_insert_id();
        echo json_encode(array('status' => '100', 'result' => '템플릿이 성공적으로 저장되었습니다.', 'template_id' => $template_id));
    } else {
        echo json_encode(array('status' => '-100', 'result' => '템플릿 저장 중 오류가 발생했습니다.'));
    }
}
// 템플릿 수정 처리
else if ($action == "update_template") {
    $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
    $template_name = isset($_POST['template_name']) ? trim($_POST['template_name']) : '';
    $template_type = isset($_POST['template_type']) ? trim($_POST['template_type']) : '';
    $template_content = isset($_POST['template_content']) ? $_POST['template_content'] : '';
    $template_description = isset($_POST['template_description']) ? trim($_POST['template_description']) : '';
    $template_industry = isset($_POST['template_industry']) ? trim($_POST['template_industry']) : '';
    
    if (empty($template_id)) {
        echo json_encode(array('status' => '-100', 'result' => '템플릿 ID가 필요합니다.'));
        exit;
    }
    
    if (empty($template_name)) {
        echo json_encode(array('status' => '-100', 'result' => '템플릿 이름을 입력해 주세요.'));
        exit;
    }
    
    if (empty($template_type)) {
        echo json_encode(array('status' => '-100', 'result' => '템플릿 유형을 선택해 주세요.'));
        exit;
    }
    
    if (empty($template_content)) {
        echo json_encode(array('status' => '-100', 'result' => '템플릿 내용을 입력해 주세요.'));
        exit;
    }
    
    // 템플릿 소유권 확인
    $sql = "select * from campaign_templates 
            where id = '{$template_id}' 
            {$sql_mb_id}";
    $template = sql_fetch($sql);
    
    if (!$template) {
        echo json_encode(array('status' => '-100', 'result' => '템플릿을 찾을 수 없거나 접근 권한이 없습니다.'));
        exit;
    }
    
    // 다른 템플릿과 이름 중복 확인 (단, 자기 자신은 제외)
    $sql = "select count(*) as cnt 
            from campaign_templates 
            where user_id = '{$member['mb_id']}' 
            and name = '{$template_name}' 
            and id != '{$template_id}'";
    $row = sql_fetch($sql);
    
    if ($row['cnt'] > 0) {
        echo json_encode(array('status' => '-100', 'result' => '이미 사용 중인 템플릿 이름입니다.'));
        exit;
    }
    
    // JSON 데이터 준비
    $template_data = array(
        'type' => $template_type,
        'content' => $template_content,
        'industry' => $template_industry
    );
    
    $json_data = json_encode($template_data);
    
    // 템플릿 업데이트
    $sql = "update campaign_templates
            set
                name                = '{$template_name}',
                target_region       = '{$_POST['target_region']}',
                daily_budget        = '{$_POST['daily_budget']}',
                cpa_goal            = '{$_POST['cpa_goal']}',
                template_data       = '{$json_data}',
                updated_at          = NOW()
            where id = '{$template_id}'
            {$sql_mb_id}";
    
    $result = sql_query($sql);
    
    if ($result) {
        echo json_encode(array('status' => '100', 'result' => '템플릿이 성공적으로 업데이트되었습니다.'));
    } else {
        echo json_encode(array('status' => '-100', 'result' => '템플릿 업데이트 중 오류가 발생했습니다.'));
    }
}
// 템플릿 삭제 처리
else if ($action == "delete_template") {
    $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
    
    if (empty($template_id)) {
        echo json_encode(array('status' => '-100', 'result' => '템플릿 ID가 필요합니다.'));
        exit;
    }
    
    // 템플릿 소유권 확인
    $sql = "select * from campaign_templates 
            where id = '{$template_id}' 
            {$sql_mb_id}";
    $template = sql_fetch($sql);
    
    if (!$template) {
        echo json_encode(array('status' => '-100', 'result' => '템플릿을 찾을 수 없거나 접근 권한이 없습니다.'));
        exit;
    }
    
    // 템플릿 삭제
    $sql = "delete from campaign_templates 
            where id = '{$template_id}' 
            {$sql_mb_id}";
    
    $result = sql_query($sql);
    
    if ($result) {
        echo json_encode(array('status' => '100', 'result' => '템플릿이 성공적으로 삭제되었습니다.'));
    } else {
        echo json_encode(array('status' => '-100', 'result' => '템플릿 삭제 중 오류가 발생했습니다.'));
    }
}
// 템플릿 목록 조회
else if ($action == "get_template_list") {
    $template_type = isset($_POST['template_type']) ? trim($_POST['template_type']) : '';
    
    $sql_type = "";
    if (!empty($template_type)) {
        $sql_type = " AND JSON_EXTRACT(template_data, '$.type') = '{$template_type}'";
    }
    
    $sql = "select * 
            from campaign_templates 
            where user_id = '{$member['mb_id']}' 
            {$sql_type} 
            order by name asc";
    
    $result = sql_query($sql);
    $templates = array();
    
    for ($i = 0; $row = sql_fetch_array($result); $i++) {
        $templates[] = array(
            'id' => $row['id'],
            'name' => $row['name'],
            'target_region' => $row['target_region'],
            'daily_budget' => $row['daily_budget'],
            'cpa_goal' => $row['cpa_goal'],
            'template_data' => json_decode($row['template_data'], true),
            'created_at' => $row['created_at']
        );
    }
    
    echo json_encode(array('status' => '100', 'result' => $templates));
}
// 템플릿 상세 조회
else if ($action == "get_template_detail") {
    $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
    
    if (empty($template_id)) {
        echo json_encode(array('status' => '-100', 'result' => '템플릿 ID가 필요합니다.'));
        exit;
    }
    
    $sql = "select * 
            from campaign_templates 
            where id = '{$template_id}' 
            {$sql_mb_id}";
    
    $template = sql_fetch($sql);
    
    if (!$template) {
        echo json_encode(array('status' => '-100', 'result' => '템플릿을 찾을 수 없거나 접근 권한이 없습니다.'));
        exit;
    }
    
    $template_data = json_decode($template['template_data'], true);
    
    $result = array(
        'id' => $template['id'],
        'name' => $template['name'],
        'target_region' => $template['target_region'],
        'daily_budget' => $template['daily_budget'],
        'cpa_goal' => $template['cpa_goal'],
        'type' => $template_data['type'],
        'content' => $template_data['content'],
        'industry' => $template_data['industry'],
        'created_at' => $template['created_at'],
        'updated_at' => $template['updated_at']
    );
    
    echo json_encode(array('status' => '100', 'result' => $result));
}
// 템플릿 복제 처리
else if ($action == "clone_template") {
    $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
    
    if (empty($template_id)) {
        echo json_encode(array('status' => '-100', 'result' => '템플릿 ID가 필요합니다.'));
        exit;
    }
    
    // 원본 템플릿 조회
    $sql = "select * 
            from campaign_templates 
            where id = '{$template_id}' 
            {$sql_mb_id}";
    
    $template = sql_fetch($sql);
    
    if (!$template) {
        echo json_encode(array('status' => '-100', 'result' => '템플릿을 찾을 수 없거나 접근 권한이 없습니다.'));
        exit;
    }
    
    // 복제된 이름 생성 (중복 방지)
    $new_name = $template['name'] . ' (복사본)';
    $count = 1;
    
    while (true) {
        $sql = "select count(*) as cnt 
                from campaign_templates 
                where user_id = '{$member['mb_id']}' 
                and name = '{$new_name}'";
        $row = sql_fetch($sql);
        
        if ($row['cnt'] == 0) {
            break;
        }
        
        $count++;
        $new_name = $template['name'] . ' (복사본 ' . $count . ')';
    }
    
    // 템플릿 복제
    $sql = "insert into campaign_templates
            set
                user_id             = '{$member['mb_id']}',
                name                = '{$new_name}',
                target_region       = '{$template['target_region']}',
                daily_budget        = '{$template['daily_budget']}',
                cpa_goal            = '{$template['cpa_goal']}',
                template_data       = '{$template['template_data']}',
                created_at          = NOW(),
                updated_at          = NOW()";
    
    $result = sql_query($sql);
    
    if ($result) {
        $new_template_id = sql_insert_id();
        echo json_encode(array('status' => '100', 'result' => '템플릿이 성공적으로 복제되었습니다.', 'template_id' => $new_template_id, 'template_name' => $new_name));
    } else {
        echo json_encode(array('status' => '-100', 'result' => '템플릿 복제 중 오류가 발생했습니다.'));
    }
}
// 잘못된 액션
else {
    echo json_encode(array('status' => '-100', 'result' => '잘못된 액션입니다.'));
}
