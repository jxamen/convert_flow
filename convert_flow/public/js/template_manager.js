/**
 * 랜딩페이지 템플릿 관리 스크립트
 */

$(document).ready(function() {
    /**
     * 템플릿 삭제 기능
     */
    $('.btn-template-delete').click(function(e) {
        e.preventDefault();
        
        var templateId = $(this).data('id');
        
        if (confirm('이 템플릿을 삭제하시겠습니까?')) {
            $.ajax({
                url: 'template_delete.php',
                type: 'POST',
                data: {
                    'id': templateId
                },
                dataType: 'json',
                success: function(data) {
                    if (data.status == 'success') {
                        alert('템플릿이 삭제되었습니다.');
                        location.reload();
                    } else {
                        alert('삭제 실패: ' + data.message);
                    }
                },
                error: function() {
                    alert('서버 통신 오류가 발생했습니다.');
                }
            });
        }
    });
    
    /**
     * 코드 에디터 기능
     */
    $('.code-editor').each(function() {
        $(this).on('keydown', function(e) {
            if (e.keyCode === 9) { // 탭 키
                e.preventDefault();
                
                var start = this.selectionStart;
                var end = this.selectionEnd;
                
                // 탭 문자를 삽입
                this.value = this.value.substring(0, start) + "    " + this.value.substring(end);
                
                // 커서 위치 조정
                this.selectionStart = this.selectionEnd = start + 4;
            }
        });
    });
    
    /**
     * 미리보기 기능
     */
    $('#btn-preview').click(function() {
        // 임시 폼 생성
        var $form = $('<form>', {
            action: 'template_preview.php',
            method: 'post',
            target: '_blank'
        });
        
        // 필요한 데이터 추가
        $form.append($('<input>', {
            type: 'hidden',
            name: 'html_template',
            value: $('#html_template').val()
        }));
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'css_template',
            value: $('#css_template').val()
        }));
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'js_template',
            value: $('#js_template').val()
        }));
        
        // 폼 제출
        $form.appendTo('body').submit().remove();
    });
    
    /**
     * 미리보기 화면 반응형 전환
     */
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
            'border': width === '100%' ? 'none' : '1px solid #dee2e6'
        });
    });
    
    /**
     * 폼 유효성 검사
     */
    $('#ftemplateform').on('submit', function(e) {
        if ($('#name').val().trim() === '') {
            alert('템플릿 이름을 입력해주세요.');
            $('#name').focus();
            e.preventDefault();
            return false;
        }
        
        if ($('#industry').val() === '') {
            alert('산업 분류를 선택해주세요.');
            $('#industry').focus();
            e.preventDefault();
            return false;
        }
        
        if ($('#html_template').val().trim() === '') {
            alert('HTML 코드를 입력해주세요.');
            $('#html_template').focus();
            e.preventDefault();
            return false;
        }
        
        return true;
    });
});
