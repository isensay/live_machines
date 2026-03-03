<!-- Topbar Start -->
<div class="navbar-custom">
    <div class="container-fluid">
        
        <ul class="list-unstyled topnav-menu float-end mb-0">

            <li class="d-none d-md-inline-block">
                <a title="{{ __('topbar.hints.color') }}" class="nav-link dropdown-toggle arrow-none waves-effect waves-light" id="light-dark-mode" href="#">
                    <i class="fe-moon noti-icon"></i>
                </a>
            </li>

            <li class="dropdown d-none d-lg-inline-block">
                <a title="{{ __('topbar.hints.full_screen') }}" class="nav-link dropdown-toggle arrow-none waves-effect waves-light" data-toggle="fullscreen" href="#">
                    <i class="fe-maximize noti-icon"></i>
                </a>
            </li>

            {{-- чтобы не отображать добавить класс "d-none" --}}
            <li class="dropdown d-lg-inline-block topbar-dropdown">
                <a title="{{ __('topbar.hints.set_language') }}" class="nav-link dropdown-toggle arrow-none waves-effect waves-light" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                    <img src="/source/base/images/flags/{{ str_replace('_', '-', app()->getLocale()) }}.jpg" alt="user-image" height="14">
                </a>
                <div class="dropdown-menu dropdown-menu-end">

                    @if (str_replace('_', '-', app()->getLocale()) <> 'ru')
                    <!-- item-->
                    <a href="/locale/ru" class="dropdown-item">
                        <img src="/source/base/images/flags/ru.jpg" alt="user-image" class="me-1" height="12"> <span class="align-middle">Русский</span>
                    </a>
                    @endif

                    @if (str_replace('_', '-', app()->getLocale()) <> 'en')
                    <!-- item-->
                    <a href="/locale/en" class="dropdown-item">
                        <img src="/source/base/images/flags/en.jpg" alt="user-image" class="me-1" height="12"> <span class="align-middle">English</span>
                    </a>
                    @endif

                     <!-- item-->
                    <a href="{{ route('locale.reset') }}" class="dropdown-item">
                        <span class="align-middle">Auto</span>
                    </a>

                </div>
            </li>

            <li class="dropdown notification-list topbar-dropdown">
                <a title="{{ __('topbar.hints.show_notify') }}" class="nav-link dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                    <i class="fe-bell noti-icon"></i>
                    <span class="badge bg-danger rounded-circle noti-icon-badge">5</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-lg">

                    <!-- item-->
                    <div class="dropdown-item noti-title">
                        <h5 class="m-0">
                            <span class="float-end">
                                <a href="" class="text-dark">
                                    <small>{{ __('topbar.notify.clear_all') }}</small>
                                </a>
                            </span>{{ __('topbar.notify.title') }}
                        </h5>
                    </div>

                    <div class="noti-scroll" data-simplebar>

                        <!-- item-->
                        <a href="javascript:void(0);" class="dropdown-item notify-item active">
                            <div class="notify-icon bg-soft-primary text-primary">
                                <i class="mdi mdi-comment-account-outline"></i>
                            </div>
                            <p class="notify-details">Doug Dukes commented on Admin Dashboard
                                <small class="text-muted">1 min ago</small>
                            </p>
                        </a>

                        <!-- item-->
                        <a href="javascript:void(0);" class="dropdown-item notify-item">
                            <div class="notify-icon">
                                <img src="/source/base/images/users/avatar-2.jpg" class="img-fluid rounded-circle" alt="" /> </div>
                            <p class="notify-details">Mario Drummond</p>
                            <p class="text-muted mb-0 user-msg">
                                <small>Hi, How are you? What about our next meeting</small>
                            </p>
                        </a>

                        <!-- item-->
                        <a href="javascript:void(0);" class="dropdown-item notify-item">
                            <div class="notify-icon">
                                <img src="/source/base/images/users/avatar-4.jpg" class="img-fluid rounded-circle" alt="" /> </div>
                            <p class="notify-details">Karen Robinson</p>
                            <p class="text-muted mb-0 user-msg">
                                <small>Wow ! this admin looks good and awesome design</small>
                            </p>
                        </a>

                        <!-- item-->
                        <a href="javascript:void(0);" class="dropdown-item notify-item">
                            <div class="notify-icon bg-soft-warning text-warning">
                                <i class="mdi mdi-account-plus"></i>
                            </div>
                            <p class="notify-details">New user registered.
                                <small class="text-muted">5 hours ago</small>
                            </p>
                        </a>

                        <!-- item-->
                        <a href="javascript:void(0);" class="dropdown-item notify-item">
                            <div class="notify-icon bg-info">
                                <i class="mdi mdi-comment-account-outline"></i>
                            </div>
                            <p class="notify-details">Caleb Flakelar commented on Admin
                                <small class="text-muted">4 days ago</small>
                            </p>
                        </a>

                        <!-- item-->
                        <a href="javascript:void(0);" class="dropdown-item notify-item">
                            <div class="notify-icon bg-secondary">
                                <i class="mdi mdi-heart"></i>
                            </div>
                            <p class="notify-details">Carlos Crouch liked
                                <b>Admin</b>
                                <small class="text-muted">13 days ago</small>
                            </p>
                        </a>
                    </div>

                    <!-- All-->
                    <a href="javascript:void(0);" class="dropdown-item text-center text-primary notify-item notify-all">
                        {{ __('topbar.notify.view_all') }}
                        <i class="fe-arrow-right"></i>
                    </a>

                </div>
            </li>

            <li class="dropdown notification-list topbar-dropdown">
                <a class="nav-link dropdown-toggle nav-user me-0 waves-effect waves-light" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                    @guest
                    <img src="/source/base/images/nologin-light.svg" alt="user-image" class="rounded-circle user-image-dark">
                    <img src="/source/base/images/nologin-dark.svg"  alt="user-image" class="rounded-circle user-image-light">
                    <span class="pro-user-name ms-1">
                        <i class="mdi mdi-chevron-down"></i>
                    </span>
                    @else
                    <img src="/source/base/images/users/avatar-{{ auth()->id() }}.jpg" alt="user-image" class="rounded-circle">
                    <span class="pro-user-name ms-1">
                        {{ Auth::user()->name }} {{ Auth::user()->surname }} <i class="mdi mdi-chevron-down"></i>
                    </span>
                    @endguest
                </a>
                <div class="dropdown-menu dropdown-menu-end profile-dropdown ">
                    <!-- item-->
                    <!--
                    <div class="dropdown-header noti-title">
                        <h6 class="text-overflow m-0">Welcome !</h6>
                    </div>
                    -->

                    <!-- item-->
                    <a href="{{ route('profile') }}" class="dropdown-item notify-item">
                        <i class="ri-account-circle-line"></i>
                        <span>{{ __('topbar.user_menu.profile') }}</span>
                    </a>

                    <!-- item-->
                    <a href="#theme-settings-offcanvas" class="dropdown-item notify-item" data-bs-toggle="offcanvas" href="#theme-settings-offcanvas">
                        <i class="ri-settings-3-line"></i>
                        <span> {{ __('topbar.user_menu.settings') }} </span>
                    </a>

                    <div class="dropdown-divider"></div>

                    <a class="dropdown-item notify-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="ri-logout-circle-line"></i>
                        <span> {{ __('topbar.user_menu.logout') }} </span>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST">
                        @csrf
                    </form>

                </div>
            </li>

        </ul>

        <ul class="list-unstyled topnav-menu topnav-menu-left m-0">
            <li>
                <button class="button-menu-mobile waves-effect waves-light">
                    <i class="fe-menu"></i>
                </button>
            </li>

            <li>
                <!-- Mobile menu toggle (Horizontal Layout)-->
                <a class="navbar-toggle nav-link" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                    <div class="lines">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </a>
                <!-- End mobile menu toggle-->
            </li>   

        </ul>

        <div class="clearfix"></div>

    </div>
</div>
<!-- Topbar End -->