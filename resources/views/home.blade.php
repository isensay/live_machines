@extends('layouts.base')

@section('head_title', __('home.page_title'))
@section('head_description', '')

{{-- Page Content --}}
@section('page_content')
    <!-- Заголовок страницы -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box sticky">
                <h4 class="page-title">
                    @yield('head_title')
                </h4>
            </div>
        </div>
    </div>

    <!-- Статистика системы -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="knob-chart invisible" dir="ltr" title="Использовано {{ $system['diskUsedPer'] }}%">
                            <input data-plugin="knob" data-width="70" data-height="70" data-fgColor="#f06b78"
                                data-bgColor="#fbdee1" value="{{ $system['diskUsedPer'] }}"
                                data-skin="tron" data-angleOffset="0" data-readOnly=true
                                data-thickness=".15"/>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-1 mt-0"> <span data-plugin="counterup">{{ $system['diskUsed'] }} </span> из <span data-plugin="counterup">{{ $system['diskTotal'] }}</span> Гб </h3>
                            <p class="text-muted mb-0">Размер диска</p>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- end col -->

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="knob-chart invisible" dir="ltr" title="Использовано {{ $system['memoryUsedPer'] }}%">
                            <input data-plugin="knob" data-width="70" data-height="70" data-fgColor="#f7b84b"
                                data-bgColor="#ebeff2" value="{{ $system['memoryUsedPer'] }}"
                                data-skin="tron" data-angleOffset="0" data-readOnly=true
                                data-thickness=".15"/>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-1 mt-0"> <span data-plugin="counterup">{{ $system['memoryUsed'] }}</span> из <span data-plugin="counterup">{{ $system['memoryTotal'] }}</span> Гб </h3>
                            <p class="text-muted mb-0">Оперативная память</p>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- end col -->

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="knob-chart invisible" dir="ltr" title="Использовано {{ $system['redisUsedPer'] }}%">
                            <input data-plugin="knob" data-width="70" data-height="70" data-fgColor="#1abc9c"
                                data-bgColor="#fbdee1" value="{{ $system['redisUsedPer'] }}"
                                data-skin="tron" data-angleOffset="0" data-readOnly=true
                                data-thickness=".15"/>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-1 mt-0"> <span data-plugin="counterup">{{ $system['redisUsed'] }}</span> из <span data-plugin="counterup">{{ $system['redisTotal'] }}</span> Мб </h3>
                            <p class="text-muted mb-0">База данных Redis</p>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- end col -->

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="knob-chart invisible" dir="ltr" title="Использовано {{ $system['mysqlUsedPer'] }}%">
                            <input data-plugin="knob" data-width="70" data-height="70" data-fgColor="#6559cc"
                                data-bgColor="#ebeff2" value="{{ $system['mysqlUsedPer'] }}"
                                data-skin="tron" data-angleOffset="0" data-readOnly=true
                                data-thickness=".15"/>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-1 mt-0"> <span data-plugin="counterup">{{ $system['mysqlUsed'] }}</span> Мб из <span data-plugin="counterup">{{ $system['mysqlTotal'] }}</span> Гб </h3>
                            <p class="text-muted mb-0">Базы данных MySQL</p>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- end col -->

    </div>

    <!-- Карта со странами производителей и список производителей -->
    <div class="row">
        <!-- Карта -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <div class="card-widgets">
                        {{--<a href="javascript:;" data-toggle="reload"><i class="mdi mdi-refresh"></i></a>--}}
                        <a data-bs-toggle="collapse" href="#cardCollpase4" role="button" aria-expanded="false" aria-controls="cardCollpase4"><i class="mdi mdi-minus"></i></a>
                        {{--<a href="javascript:;" data-toggle="remove"><i class="mdi mdi-close"></i></a>--}}
                    </div>
                    <h4 class="header-title mb-0">Страны производителей</h4>

                    <div id="cardCollpase4" class="collapse pt-3 show">
                        <div id="world-map-markers" style="height: 390px; overflow: hidden;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Производители -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <div class="card-widgets">
                        {{--<a href="javascript:;" data-toggle="reload"><i class="mdi mdi-refresh"></i></a>--}}
                        <a data-bs-toggle="collapse" href="#cardCollpase5" role="button" aria-expanded="false" aria-controls="cardCollpase5"><i class="mdi mdi-minus"></i></a>
                        {{--<a href="javascript:;" data-toggle="remove"><i class="mdi mdi-close"></i></a>--}}
                    </div>
                    <h4 class="header-title mb-0">Производители</h4>

                    <style>

                    </style>

                    <div id="cardCollpase5" class="collapse pt-3 show">
                        <div class="table-responsive" style="max-height: 390px; overflow-y: auto;">
                            <table class="table table-hover table-centered mb-0">
                                <thead class="sticky-top bg-body" style="z-index: 1;">
                                    <tr>
                                        <th>Наименование</th>
                                        <th style="text-align: center;">Страна</th>
                                        <th style="text-align: center;">Моделей</th>
                                        <th style="text-align: center;">Файлов</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($manufs as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td style="text-align: center;">{!! $item->country !!}</td>
                                        <td style="text-align: center;">{{ $item->models }}</td>
                                        <td style="text-align: center;">{{ $item->files }}</td>
                                    </tr>
                                     @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Статистика по справочникам -->
    <div class="row">
        <div class="col-xl-2 col-md-6">
            <div class="widget-simple text-center card">
                <div class="card-body">
                    <h3 class="text-warning mt-0"><span data-plugin="counterup">{{ $stat['file'] }}</span></h3>
                    <p class="text-muted mb-0">Файлов КП</p>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="widget-simple text-center card">
                <div class="card-body">
                    <h3 class="text-info mt-0"><span data-plugin="counterup">{{ $stat['group'] }}</span></h3>
                    <p class="text-muted mb-0">Гпупп</p>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="widget-simple text-center card">
                <div class="card-body">
                    <h3 class="text-success mt-0" style="color:#37cde6;"><span data-plugin="counterup">{{ $stat['tech'] }}</span></h3>
                    <p class="text-muted mb-0">Тех. характеристик</p>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="widget-simple text-center card">
                <div class="card-body">
                    <h3 class="text-primary mt-0"><span data-plugin="counterup">{{ $stat['comp'] }}</span></h3>
                    <p class="text-muted mb-0">Комплектаций</p>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="widget-simple text-center card">
                <div class="card-body">
                    <h3 class="text-pink mt-0"><span data-plugin="counterup">{{ $stat['model'] }}</span></h3>
                    <p class="text-muted mb-0">Моделей</p>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="widget-simple text-center card">
                <div class="card-body">
                    <h3 class="text-purple mt-0"><span data-plugin="counterup">{{ $stat['manuf'] }}</span></h3>
                    <p class="text-muted mb-0">Производителей</p>
                </div>
            </div>
        </div>
    </div>

    {{--
    <div class="row" style="display:none;">

        <div class="col-xl-4 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="dropdown float-end">
                        <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="mdi mdi-dots-horizontal"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Settings</a>
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Download</a>
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Upload</a>
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Action</a>
                        </div>
                    </div>

                    <h4 class="header-title">Revenue Report</h4>

                    <div class="mt-3 text-center">

                        <div class="row pt-2">
                            <div class="col-4">
                                <p class="text-muted font-15 mb-1 text-truncate">Target</p>
                                <h4> $12,365</h4>
                            </div>
                            <div class="col-4">
                                <p class="text-muted font-15 mb-1 text-truncate">Last week</p>
                                <h4><i class="fe-arrow-down text-primary"></i> $365</h4>
                            </div>
                            <div class="col-4">
                                <p class="text-muted font-15 mb-1 text-truncate">Last Month</p>
                                <h4><i class="fe-arrow-up text-success"></i> $8,501</h4>
                            </div>
                        </div>
                        
                        <div dir="ltr">
                            <div id="revenue-report" class="apex-charts" data-colors="#f06b78,#e3eaef"></div>
                        </div>

                    </div>
                </div>
            </div> <!-- end card-box -->
        </div> <!-- end col -->

        <div class="col-xl-4 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="dropdown float-end">
                        <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="mdi mdi-dots-horizontal"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Settings</a>
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Download</a>
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Upload</a>
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Action</a>
                        </div>
                    </div>

                    <h4 class="header-title">Products Sales</h4>

                    <div class="mt-3 text-center">

                        <div class="row pt-2">
                            <div class="col-4">
                                <p class="text-muted font-15 mb-1 text-truncate">Target</p>
                                <h4> $56,214</h4>
                            </div>
                            <div class="col-4">
                                <p class="text-muted font-15 mb-1 text-truncate">Last week</p>
                                <h4><i class="fe-arrow-up text-success"></i> $840</h4>
                            </div>
                            <div class="col-4">
                                <p class="text-muted font-15 mb-1 text-truncate">Last Month</p>
                                <h4><i class="fe-arrow-down text-primary"></i> $7,845</h4>
                            </div>
                        </div>
                        <div dir="ltr">
                            <div id="products-sales" class="apex-charts" data-colors="#f06b78,#6c757d"></div>
                        </div>

                    </div>
                </div>
            </div> <!-- end card -->
        </div> <!-- end col -->

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="dropdown float-end">
                        <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="mdi mdi-dots-horizontal"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Settings</a>
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Download</a>
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Upload</a>
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Action</a>
                        </div>
                    </div>
                    <h4 class="header-title">Marketing Reports</h4>
                    <p class="text-muted mb-0">1 Mar - 31 Mar Showing Data</p>
                    
                    <div dir="ltr">
                        <div id="marketing-reports" class="apex-charts" data-colors="#f06b78,#6c757d,#f7b84b"></div>
                    </div>

                    <div class="row text-center">
                        <div class="col-6">
                            <p class="text-muted mb-1">This Month</p>
                            <h3 class="mt-0 font-20"><span class="align-middle">$120,254</span> <small class="badge badge-soft-success font-12">+15%</small></h3>
                        </div>

                        <div class="col-6">
                            <p class="text-muted mb-1">Last Month</p>
                            <h3 class="mt-0 font-20"><span class="align-middle">$98,741</span> <small class="badge badge-soft-danger font-12">-5%</small></h3>
                        </div>
                    </div>

                </div>
            </div> <!-- end card -->
        </div> <!-- end col -->

    </div>

    <div style="display:none;" class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="dropdown float-end">
                        <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="mdi mdi-dots-horizontal"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Settings</a>
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Download</a>
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Upload</a>
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Action</a>
                        </div>
                    </div>
                    <h4 class="header-title mb-3">Revenue History</h4>

                    <div class="table-responsive">
                        <table class="table table-borderless table-hover table-centered m-0">

                            <thead class="table-light">
                                <tr>
                                    <th>Marketplaces</th>
                                    <th>Date</th>
                                    <th>US Tax Hold</th>
                                    <th>Payouts</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <h5 class="m-0 fw-normal">Themes Market</h5>
                                    </td>

                                    <td>
                                        Oct 15, 2020
                                    </td>
                                    
                                    <td>
                                        $125.23
                                    </td>

                                    <td>
                                        $5848.68
                                    </td>

                                    <td>
                                        <span class="badge badge-soft-warning">Upcoming</span>
                                    </td>

                                    <td>
                                        <a href="javascript: void(0);" class="btn btn-xs btn-secondary"><i class="mdi mdi-pencil"></i></a>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <h5 class="m-0 fw-normal">Freelance</h5>
                                    </td>

                                    <td>
                                        Oct 12, 2020
                                    </td>

                                    <td>
                                        $78.03
                                    </td>

                                    <td>
                                        $1247.25
                                    </td>

                                    <td>
                                        <span class="badge badge-soft-success">Paid</span>
                                    </td>

                                    <td>
                                        <a href="javascript: void(0);" class="btn btn-xs btn-secondary"><i class="mdi mdi-pencil"></i></a>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <h5 class="m-0 fw-normal">Share Holding</h5>
                                    </td>

                                    <td>
                                        Oct 10, 2020
                                    </td>

                                    <td>
                                        $358.24
                                    </td>

                                    <td>
                                        $815.89
                                    </td>

                                    <td>
                                        <span class="badge badge-soft-success">Paid</span>
                                    </td>

                                    <td>
                                        <a href="javascript: void(0);" class="btn btn-xs btn-secondary"><i class="mdi mdi-pencil"></i></a>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <h5 class="m-0 fw-normal">Wrap's Affiliates</h5>
                                    </td>

                                    <td>
                                        Oct 03, 2020
                                    </td>

                                    <td>
                                        $18.78
                                    </td>

                                    <td>
                                        $248.75
                                    </td>

                                    <td>
                                        <span class="badge badge-soft-danger">Overdue</span>
                                    </td>

                                    <td>
                                        <a href="javascript: void(0);" class="btn btn-xs btn-secondary"><i class="mdi mdi-pencil"></i></a>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <h5 class="m-0 fw-normal">Marketing Revenue</h5>
                                    </td>

                                    <td>
                                        Sep 21, 2020
                                    </td>

                                    <td>
                                        $185.36
                                    </td>

                                    <td>
                                        $978.21
                                    </td>

                                    <td>
                                        <span class="badge badge-soft-warning">Upcoming</span>
                                    </td>

                                    <td>
                                        <a href="javascript: void(0);" class="btn btn-xs btn-secondary"><i class="mdi mdi-pencil"></i></a>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <h5 class="m-0 fw-normal">Advertise Revenue</h5>
                                    </td>

                                    <td>
                                        Sep 15, 2020
                                    </td>

                                    <td>
                                        $29.56
                                    </td>

                                    <td>
                                        $358.10
                                    </td>

                                    <td>
                                        <span class="badge badge-soft-success">Paid</span>
                                    </td>

                                    <td>
                                        <a href="javascript: void(0);" class="btn btn-xs btn-secondary"><i class="mdi mdi-pencil"></i></a>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div> <!-- end .table-responsive-->
                </div>
            </div> <!-- end card-->
        </div> <!-- end col -->

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="dropdown float-end">
                        <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="mdi mdi-dots-horizontal"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Settings</a>
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Download</a>
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Upload</a>
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item">Action</a>
                        </div>
                    </div>

                    <h4 class="header-title">Projections Vs Actuals</h4>

                    <div class="mt-3 text-center" dir="ltr">

                        <div id="projections-actuals" class="apex-charts" data-colors="#f06b78,#e3eaef,#f7b84b,#6c757d"></div>

                        <div class="row mt-3">
                            <div class="col-4">
                                <p class="text-muted font-15 mb-1 text-truncate">Target</p>
                                <h4>$8712</h4>
                            </div>
                            <div class="col-4">
                                <p class="text-muted font-15 mb-1 text-truncate">Last week</p>
                                <h4><i class="fe-arrow-up text-success"></i> $523</h4>
                            </div>
                            <div class="col-4">
                                <p class="text-muted font-15 mb-1 text-truncate">Last Month</p>
                                <h4><i class="fe-arrow-down text-danger"></i> $965</h4>
                            </div>
                        </div>

                    </div>
                </div>
            </div> <!-- end card-box -->
        </div> <!-- end col -->
    </div>
    --}}
@endsection


{{-- Page Java Script --}}
@section('page_java_script')
    <!-- KNOB JS -->
    <script src="/source/base/libs/jquery-knob/jquery.knob.min.js"></script>
    <!-- Apex js-->
    <script src="/source/base/libs/apexcharts/apexcharts.min.js"></script>

    <!-- Plugins js-->
    <script src="/source/base/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js"></script>
    <script src="/source/base/libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-world-mill-en.js"></script>

    {{--
    <!-- Dashboard init-->
    <script src="/source/base/js/pages/dashboard-sales.init.js"></script>
    --}}
@endsection


{{-- Page More Java Script --}}
@section('page_more_java_script')
    <script src="/source/base/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="/source/base/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="/source/base/libs/select2/js/select2.min.js"></script>
    <script src="/source/base/libs/select2/js/i18n/ru.js"></script>
    <script src="/source/base/libs/sweetalert2/sweetalert2.min.js"></script>

    <script>
        const countriesFromDb = @json($countries);
        console.log(countriesFromDb);
    </script>

    <script src="/source/base/home/script.js?<?=time()?>"></script>
@endsection


