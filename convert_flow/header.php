<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="ConvertFlow CPA 마케팅 관리 시스템">
    <meta name="author" content="ConvertFlow">

    <title><?php echo isset($page_title) ? $page_title . " - " : ""; ?>ConvertFlow</title>

    <!-- Custom fonts -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom styles -->
    <link href="<?php echo CF_CSS_URL; ?>/dashboard.css" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
    
    <!-- Moment.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    
    <!-- Toastr -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper" class="d-flex">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion flex-shrink-0" id="accordionSidebar" style="width: 250px; min-height: 100vh;">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
                <div class="sidebar-brand-icon text-white">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="sidebar-brand-text text-white mx-3">ConvertFlow</div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo CF_URL; ?>">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>대시보드</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                마케팅
            </div>

            <!-- Nav Item - 캠페인 Collapse Menu -->
            <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'campaign') ? 'active' : ''; ?>">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseCampaign" aria-expanded="true" aria-controls="collapseCampaign">
                    <i class="fas fa-fw fa-flag"></i>
                    <span>캠페인</span>
                </a>
                <div id="collapseCampaign" class="collapse <?php echo strpos($_SERVER['REQUEST_URI'], '/campaign') ? 'show' : ''; ?>" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_list.php">캠페인 목록</a>
                        <a class="collapse-item" href="<?php echo CF_CAMPAIGN_URL; ?>/campaign_create.php">캠페인 생성</a>
                        <a class="collapse-item" href="<?php echo CF_CAMPAIGN_URL; ?>/ad_group_add.php">광고 그룹</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - 전환 스크립트 Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseConversion" aria-expanded="true" aria-controls="collapseConversion">
                    <i class="fas fa-fw fa-exchange-alt"></i>
                    <span>전환 스크립트</span>
                </a>
                <div id="collapseConversion" class="collapse <?php echo strpos($_SERVER['REQUEST_URI'], '/conversion') ? 'show' : ''; ?>" aria-labelledby="headingConversion" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo CF_CONVERSION_URL; ?>/conversion_script_list.php">스크립트 목록</a>
                        <a class="collapse-item" href="<?php echo CF_CONVERSION_URL; ?>/conversion_event_list.php">전환 이벤트</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - 랜딩페이지 Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLanding" aria-expanded="true" aria-controls="collapseLanding">
                    <i class="fas fa-fw fa-file-alt"></i>
                    <span>랜딩페이지</span>
                </a>
                <div id="collapseLanding" class="collapse <?php echo strpos($_SERVER['REQUEST_URI'], '/landing_page') ? 'show' : ''; ?>" aria-labelledby="headingLanding" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo CF_LANDING_URL; ?>/landing_page_list.php">페이지 목록</a>
                        <a class="collapse-item" href="<?php echo CF_LANDING_URL; ?>/landing_page_create.php">페이지 생성</a>
                        <a class="collapse-item" href="<?php echo CF_LANDING_URL; ?>/template_list.php">템플릿 관리</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - A/B 테스트 -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseABTest" aria-expanded="true" aria-controls="collapseABTest">
                    <i class="fas fa-fw fa-flask"></i>
                    <span>A/B 테스트</span>
                </a>
                <div id="collapseABTest" class="collapse <?php echo strpos($_SERVER['REQUEST_URI'], '/ab_test') ? 'show' : ''; ?>" aria-labelledby="headingABTest" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="ab_test_list.php">테스트 목록</a>
                        <a class="collapse-item" href="ab_test_create.php">테스트 생성</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                리드 관리
            </div>

            <!-- Nav Item - 폼 Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseForm" aria-expanded="true" aria-controls="collapseForm">
                    <i class="fas fa-fw fa-edit"></i>
                    <span>폼</span>
                </a>
                <div id="collapseForm" class="collapse <?php echo strpos($_SERVER['REQUEST_URI'], '/form') ? 'show' : ''; ?>" aria-labelledby="headingForm" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo CF_FORM_URL; ?>/form_list.php">폼 목록</a>
                        <a class="collapse-item" href="<?php echo CF_FORM_URL; ?>/form_create.php">폼 생성</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - 리드 Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLead" aria-expanded="true" aria-controls="collapseLead">
                    <i class="fas fa-fw fa-users"></i>
                    <span>리드</span>
                </a>
                <div id="collapseLead" class="collapse <?php echo strpos($_SERVER['REQUEST_URI'], '/lead') ? 'show' : ''; ?>" aria-labelledby="headingLead" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo CF_LEAD_URL; ?>/lead_list.php">리드 목록</a>
                        <a class="collapse-item" href="<?php echo CF_LEAD_URL; ?>/lead_export.php">리드 내보내기</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - API 연동 -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAPI" aria-expanded="true" aria-controls="collapseAPI">
                    <i class="fas fa-fw fa-link"></i>
                    <span>API 연동</span>
                </a>
                <div id="collapseAPI" class="collapse <?php echo strpos($_SERVER['REQUEST_URI'], 'api') ? 'show' : ''; ?>" aria-labelledby="headingAPI" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo CF_API_URL; ?>/api_endpoint_list.php">API 엔드포인트</a>
                        <a class="collapse-item" href="<?php echo CF_API_URL; ?>/api_transmission_list.php">데이터 전송 기록</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                도구
            </div>

            <!-- Nav Item - URL 관리 -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseURL" aria-expanded="true" aria-controls="collapseURL">
                    <i class="fas fa-fw fa-link"></i>
                    <span>URL 관리</span>
                </a>
                <div id="collapseURL" class="collapse <?php echo strpos($_SERVER['REQUEST_URI'], 'shorturl') ? 'show' : ''; ?>" aria-labelledby="headingURL" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo CF_SHORT_URL; ?>/shorturl_list.php">단축 URL</a>
                        <a class="collapse-item" href="<?php echo CF_SHORT_URL; ?>/shorturl_create.php">URL 생성</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - 푸시 알림 -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePush" aria-expanded="true" aria-controls="collapsePush">
                    <i class="fas fa-fw fa-bell"></i>
                    <span>푸시 알림</span>
                </a>
                <div id="collapsePush" class="collapse <?php echo strpos($_SERVER['REQUEST_URI'], 'push') ? 'show' : ''; ?>" aria-labelledby="headingPush" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo CF_PUSH_URL; ?>/push_campaign_list.php">푸시 캠페인</a>
                        <a class="collapse-item" href="<?php echo CF_PUSH_URL; ?>/push_campaign_create.php">캠페인 생성</a>
                        <a class="collapse-item" href="<?php echo CF_PUSH_URL; ?>/push_token_list.php">구독자 관리</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - 보고서 -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseReport" aria-expanded="true" aria-controls="collapseReport">
                    <i class="fas fa-fw fa-chart-bar"></i>
                    <span>보고서</span>
                </a>
                <div id="collapseReport" class="collapse <?php echo strpos($_SERVER['REQUEST_URI'], 'report') ? 'show' : ''; ?>" aria-labelledby="headingReport" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo CF_REPORT_URL; ?>/report_list.php">보고서 목록</a>
                        <a class="collapse-item" href="<?php echo CF_REPORT_URL; ?>/report_create.php">보고서 생성</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                설정
            </div>

            <!-- Nav Item - 광고 계정 -->
            <li class="nav-item">
                <a class="nav-link" href="<?php echo CF_URL; ?>/app/ad_account/ad_accounts.php">
                    <i class="fas fa-fw fa-ad"></i>
                    <span>광고 계정</span>
                </a>
            </li>

            <!-- Nav Item - 계정 설정 -->
            <li class="nav-item">
                <a class="nav-link" href="user_settings.php">
                    <i class="fas fa-fw fa-user-cog"></i>
                    <span>계정 설정</span>
                </a>
            </li>

            <?php if ($is_admin) { ?>
            <!-- Nav Item - 관리자 -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAdmin" aria-expanded="true" aria-controls="collapseAdmin">
                    <i class="fas fa-fw fa-user-shield"></i>
                    <span>관리자</span>
                </a>
                <div id="collapseAdmin" class="collapse <?php echo strpos($_SERVER['REQUEST_URI'], 'admin') ? 'show' : ''; ?>" aria-labelledby="headingAdmin" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?php echo CF_ADMIN_URL; ?>/users/admin_user_list.php">사용자 관리</a>
                        <a class="collapse-item" href="<?php echo CF_ADMIN_URL; ?>/settings/admin_settings.php">시스템 설정</a>
                        <a class="collapse-item" href="<?php echo CF_ADMIN_URL; ?>/settings/ad_platform_settings.php">광고 플랫폼 설정</a>
                        <a class="collapse-item" href="<?php echo CF_ADMIN_URL; ?>/stats/admin_logs.php">통계</a>

                        <div class=" mt-3 pt-3 border-top"></div>
                    
                        <a class="collapse-item" href="<?php echo CF_ADMIN_URL; ?>/landing/landing_block_template_list.php">블록 템플릿 관리</a>
                        <a class="collapse-item" href="<?php echo CF_ADMIN_URL; ?>/landing/landing_block_types_list.php">블록 타입 관리</a>
                    </div>
                </div>
            </li>
            <?php } ?>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column flex-grow-1">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Search -->
                    <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 small" placeholder="검색..." aria-label="Search" aria-describedby="basic-addon2">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        <!-- Nav Item - Search Dropdown (Visible Only XS) -->
                        <li class="nav-item dropdown no-arrow d-sm-none">
                            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-search fa-fw"></i>
                            </a>
                            <!-- Dropdown - Messages -->
                            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in" aria-labelledby="searchDropdown">
                                <form class="form-inline mr-auto w-100 navbar-search">
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-light border-0 small" placeholder="검색..." aria-label="Search" aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button">
                                                <i class="fas fa-search fa-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </li>

                        <!-- Nav Item - Alerts -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bell fa-fw"></i>
                                <!-- Counter - Alerts -->
                                <span class="badge badge-danger badge-counter">3+</span>
                            </a>
                            <!-- Dropdown - Alerts -->
                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="alertsDropdown">
                                <h6 class="dropdown-header">
                                    알림 센터
                                </h6>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="mr-3">
                                        <div class="icon-circle bg-primary">
                                            <i class="fas fa-file-alt text-white"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">2023년 10월 15일</div>
                                        <span class="font-weight-bold">새 보고서가 생성되었습니다!</span>
                                    </div>
                                </a>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="mr-3">
                                        <div class="icon-circle bg-success">
                                            <i class="fas fa-exchange-alt text-white"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">2023년 10월 14일</div>
                                        새로운 전환이 발생했습니다!
                                    </div>
                                </a>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="mr-3">
                                        <div class="icon-circle bg-warning">
                                            <i class="fas fa-exclamation-triangle text-white"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">2023년 10월 13일</div>
                                        전환율이 평소보다 낮습니다. 확인이 필요합니다.
                                    </div>
                                </a>
                                <a class="dropdown-item text-center small text-gray-500" href="#">모든 알림 보기</a>
                            </div>
                        </li>

                        <!-- Nav Item - Messages -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-envelope fa-fw"></i>
                                <!-- Counter - Messages -->
                                <span class="badge badge-danger badge-counter">7</span>
                            </a>
                            <!-- Dropdown - Messages -->
                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="messagesDropdown">
                                <h6 class="dropdown-header">
                                    메시지 센터
                                </h6>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="dropdown-list-image mr-3">
                                        <img class="rounded-circle" src="https://source.unsplash.com/fn_BT9fwg_E/60x60" alt="">
                                        <div class="status-indicator bg-success"></div>
                                    </div>
                                    <div class="font-weight-bold">
                                        <div class="text-truncate">안녕하세요! 캠페인 성과에 대해 문의드립니다.</div>
                                        <div class="small text-gray-500">홍길동 · 58분 전</div>
                                    </div>
                                </a>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="dropdown-list-image mr-3">
                                        <img class="rounded-circle" src="https://source.unsplash.com/AU4VPcFN4LE/60x60" alt="">
                                        <div class="status-indicator"></div>
                                    </div>
                                    <div>
                                        <div class="text-truncate">지난 달 보고서 받아보았습니다. 전환율이 많이 상승했네요!</div>
                                        <div class="small text-gray-500">김철수 · 1일 전</div>
                                    </div>
                                </a>
                                <a class="dropdown-item text-center small text-gray-500" href="#">모든 메시지 보기</a>
                            </div>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $member['full_name']; ?></span>
                                <img class="img-profile rounded-circle" src="https://source.unsplash.com/QAB-WJcbgJk/60x60">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="user_settings.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    프로필
                                </a>
                                <a class="dropdown-item" href="user_settings.php">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                    설정
                                </a>
                                <a class="dropdown-item" href="activity_log.php">
                                    <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                                    활동 로그
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    로그아웃
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
