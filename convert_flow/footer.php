
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white p-4 mb-4">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; ConvertFlow 2023</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">로그아웃 하시겠습니까?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">현재 세션을 종료하려면 아래 "로그아웃" 버튼을 클릭하세요.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">취소</button>
                    <a class="btn btn-primary" href="logout.php">로그아웃</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast 알림 설정 -->
    <script>
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    // 알림 표시 함수
    function showToast(message, type = 'success', duration = 3000) {
        toastr.options.timeOut = duration;
        
        switch(type) {
            case 'success':
                toastr.success(message, '성공');
                break;
            case 'info':
                toastr.info(message, '안내');
                break;
            case 'warning':
                toastr.warning(message, '경고');
                break;
            case 'error':
                toastr.error(message, '오류');
                break;
            default:
                toastr.success(message, '성공');
        }
    }

    // URL 파라미터 확인하여 메시지 표시
    $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const msg = urlParams.get('msg');
        const msgType = urlParams.get('msg_type') || 'success';
        
        if (msg) {
            showToast(decodeURIComponent(msg), msgType);
        }
    });
    </script>

</body>
</html>
