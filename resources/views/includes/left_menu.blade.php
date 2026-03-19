<!-- ========== Left Sidebar Start ========== -->
<div class="left-side-menu">

    <!-- LOGO -->
    <div class="logo-box">
        <a href="/" class="logo logo-dark text-center">
            <span class="logo-sm">
                <img src="/source/base/images/adoxa_logo_ico.svg" alt="" height="24">
            </span>
            <span class="logo-lg">
                <img src="/source/base/images/adoxa_logo_dark.svg" alt="" height="20">
            </span>
        </a>

        <a href="/" class="logo logo-light text-center">
            <span class="logo-sm">
                <img src="/source/base/images/adoxa_logo_ico.svg" alt="" height="24">
            </span>
            <span class="logo-lg">
                <img src="/source/base/images/adoxa_logo_light.svg" alt="" height="20">
            </span>
        </a>
    </div>

    <div class="h-100" data-simplebar>

        <!--- Sidemenu -->
        <div id="sidebar-menu">

            <ul id="side-menu">
    
                <li>
                    <a href="{{ route('home') }}" aria-expanded="false" aria-controls="sidebarDashboards" class="waves-effect">
                        <i class="ri-home-3-line"></i>
                        <span>{{ __('left_menu.home') }}</span>
                    </a>
                </li>

                <li>
                    <a href="#sidebarMultilevel_lm" data-bs-toggle="collapse" aria-expanded="false" aria-controls="sidebarMultilevel_lm">
                        <i class="ri-error-warning-line"></i>
                        <span>Live Machines</span>
                        <span class="menu-arrow"></span>
                    </a>

                    <div class="collapse" id="sidebarMultilevel_lm">
                        <ul class="nav-second-level">
                            <li>
                                <a href="#">Группы параметров</a>
                            </li>
                            <li>
                                <a href="#">Единицы измерения</a>
                            </li>
                            <li>
                                <a href="#">Значения</a>
                            </li>
                            <li>
                                <a href="{{ route('lm_tech_list') }}">Тех. характеристики</a>
                            </li>
                            <li>
                                <a href="{{ route('lm_comp_list') }}">Комплектации</a>
                            </li>
                            <li>
                                <a href="{{ route('lm_model_list') }}">Модели</a>
                            </li>
                            <li>
                                <a href="{{ route('lm_manuf_list') }}">Производители</a>
                            </li>
                            <li>
                                <a href="{{ route('lm_country_list') }}">Страны</a>
                            </li>
                            <li>
                                <a href="#" onclick="return resetDatabase(event)">Откатить БД</a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{--
                <li>
                    <a href="{{ route('maintenance') }}" class="waves-effect">
                        <i class="ri-tools-line"></i>
                        <span>{{ __('left_menu.maintenance') }}</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('comming_soon') }}" class="waves-effect">
                        <i class="ri-time-line"></i>
                        <span>{{ __('left_menu.cooming_soon') }}</span>
                    </a>
                </li>
                
                <li>
                    <a href="#sidebarMultilevel1" data-bs-toggle="collapse" aria-expanded="false" aria-controls="sidebarMultilevel1">
                        <i class="ri-error-warning-line"></i>
                        <span>{{ __('left_menu.errors.title') }}</span>
                        <span class="menu-arrow"></span>
                    </a>

                    <div class="collapse" id="sidebarMultilevel1">
                        <ul class="nav-second-level">
                            <li>
                                <a href="/error401">{{ __('left_menu.errors.btn_prefix') }} - 401</a>
                            </li>
                            <li>
                                <a href="/error403">{{ __('left_menu.errors.btn_prefix') }} - 403</a>
                            </li>
                            <li>
                                <a href="/error404">{{ __('left_menu.errors.btn_prefix') }} - 404</a>
                            </li>
                            <li>
                                <a href="/error419">{{ __('left_menu.errors.btn_prefix') }} - 419</a>
                            </li>
                            <li>
                                <a href="/error429">{{ __('left_menu.errors.btn_prefix') }} - 429</a>
                            </li>
                            <li>
                                <a href="/error500">{{ __('left_menu.errors.btn_prefix') }} - 500</a>
                            </li>
                        </ul>
                    </div>
                </li>
                --}}

                {{--
                <li class="menu-title mt-2">Разное</li>

                <li>
                    <a href="#sidebarMultilevel1" data-bs-toggle="collapse" aria-expanded="false" aria-controls="sidebarMultilevel1">
                        <i class="ri-share-line"></i>
                        <span>Меню</span>
                        <span class="menu-arrow"></span>
                    </a>

                    <div class="collapse" id="sidebarMultilevel1">
                        <ul class="nav-second-level">
                            <li>
                                <a href="javascript: void(0);">Без подуровней</a>
                            </li>
                            
                            <li>
                                <a href="#sidebarMultilevel2" data-bs-toggle="collapse" aria-expanded="false" aria-controls="sidebarMultilevel2">
                                    2 подуровня
                                    <span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="sidebarMultilevel2">
                                    <ul class="nav-third-level">
                                        <li>
                                            <a href="javascript: void(0);">Подменю 1</a>
                                        </li>
                                        <li>
                                            <a href="javascript: void(0);">Подменю 2</a>
                                        </li>
                                    </ul>
                                </div>
                            </li>

                            <li>
                                <a href="#sidebarMultilevel3" data-bs-toggle="collapse" aria-expanded="false" aria-controls="sidebarMultilevel3">
                                    3 подуровня
                                    <span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="sidebarMultilevel3">
                                    <ul class="nav-third-level">
                                        <li>
                                            <a href="javascript: void(0);">Подменю 1</a>
                                        </li>
                                        <li>
                                            <a href="#sidebarMultilevel4" data-bs-toggle="collapse" aria-expanded="false" aria-controls="sidebarMultilevel4">
                                                Подменю 2
                                                <span class="menu-arrow"></span>
                                            </a>
                                            <div class="collapse" id="sidebarMultilevel4">
                                                <ul class="nav-fourth-level">
                                                    <li>
                                                        <a href="javascript: void(0);">Подменю 2.1</a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript: void(0);">Подменю 2.2</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        </ul>
                    </div>
                </li>
                --}}
            </ul>

        </div>
        <!-- End Sidebar -->

        <div class="clearfix"></div>

    </div>
    <!-- Sidebar -left -->

</div>
<!-- Left Sidebar End -->