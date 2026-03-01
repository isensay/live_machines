@extends('layouts.base')

{{-- Page Content --}}
@section('page_content')
    
    {{-- Page Title --}}
    {{-- @include('includes.title') --}}

    <!-- start page title -->
    <div class="row" style1="position: static; top: 70px; z-index: 99;">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Справочник стран</h4>
                <div class="page-title-right">
                    <div class="page-title-right">
                        <a href="{{-- route('livemachines.sprav.create') --}}" class="btn btn-success btn-rounded">
                            <i class="mdi mdi-plus me-1"></i> Создать
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>     
    <!-- end page title --> 

    <div class="row">

        <style>
            #basic-datatable tbody tr {
                height: 60px; /* фиксированная высота строки */
            }
            #basic-datatable thead th {
                text-align: center !important;
            }
            #basic-datatable tbody td {
                vertical-align: middle !important;
                line-height: 1.2;
                text-align: center;
            }
            #basic-datatable .btn-sm {
                height: 32px;
                width: 32px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0 !important;
            }
        </style>
        
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="basic-datatable" class="table dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>Название</th>
                                <th>Производителей</th>
                                <th>Файлов</th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $item)
                            <tr>
                                <td style="text-align:left;">{{ $item->countryName }}</td>
                                <td>{{ $item->manufCount }}</td>
                                <td>{{ $item->fileCount }}</td>
                                <td width="25">
                                    <a href="#" class="btn btn-success btn-sm btn-rounded" title="Редактировать">
                                        <i class="mdi mdi-pencil font-16"></i>
                                    </a>
                                </td>
                                <td width="25">
                                    <a href="#" class="btn btn-primary btn-sm btn-rounded" title="Удалить">
                                        <i class="mdi mdi-delete font-16"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div> <!-- end card body-->
            </div> <!-- end card -->
        </div><!-- end col-->

    </div>
    <!-- end row-->






@endsection


@section('head_other')
        <link href="/source/base/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
        <link href="/source/base/libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />
        <link href="/source/base/libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css" rel="stylesheet" type="text/css" />
        <link href="/source/base/libs/datatables.net-select-bs5/css/select.bootstrap5.min.css" rel="stylesheet" type="text/css" />
@endsection


{{-- Page Java Script --}}
@section('page_java_script')
@endsection


{{-- Page More Java Script --}}
@section('page_more_java_script')
        <script src="/source/base/libs/datatables.net/js/jquery.dataTables.min.js"></script>
        
        <script src="/source/base/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>

        <!--
        <script src="/source/base/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
        <script src="/source/base/libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
        <script src="/source/base/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
        <script src="/source/base/libs/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js"></script>
        
        <script src="/source/base/libs/datatables.net-buttons/js/buttons.html5.min.js"></script>
        <script src="/source/base/libs/datatables.net-buttons/js/buttons.flash.min.js"></script>
        <script src="/source/base/libs/datatables.net-buttons/js/buttons.print.min.js"></script>
        
        <script src="/source/base/libs/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
        
        <script src="/source/base/libs/datatables.net-select/js/dataTables.select.min.js"></script>
        
        <script src="/source/base/libs/pdfmake/build/pdfmake.min.js"></script>
        <script src="/source/base/libs/pdfmake/build/vfs_fonts.js"></script>
        
        <script src="/source/base/js/pages/datatables.init.js"></script>
        -->

        <script>
            $(document).ready(function () {
                $("#basic-datatable").DataTable({
                    paging: false,           // отключает пагинацию
                    //info: false,             // отключает текст "Showing 1 to X of Y entries"
                    searching: true,         // поиск оставляем (можно отключить если нужно)
                    language: {
                        processing: "Подождите...",
                        search: "Поиск:",
                        lengthMenu: "Показать _MENU_ записей",
                        info: "Показаны с _START_ по _END_ из _TOTAL_ записей",
                        infoEmpty: "Показаны с 0 по 0 из 0 записей",
                        infoFiltered: "(отфильтровано из _MAX_ записей)",
                        infoPostFix: "",
                        loadingRecords: "Загрузка записей...",
                        zeroRecords: "Записи отсутствуют.",
                        emptyTable: "В таблице отсутствуют данные",
                        paginate: {
                            first: "Первая",
                            previous: "<i class='mdi mdi-chevron-left'>",
                            next: "<i class='mdi mdi-chevron-right'>",
                            last: "Последняя"
                        },
                        aria: {
                            sortAscending: ": активировать для сортировки столбца по возрастанию",
                            sortDescending: ": активировать для сортировки столбца по убыванию"
                        }
                    },
                    drawCallback: function () {
                        $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                    },
                    columnDefs: [
                        { orderable: false, targets: [-2, -1] } // -1 означает последний столбец
                    ],
                });


                /*
                $("#basic-datatable").DataTable({
                    language: {
                        paginate: { previous: "<i class='mdi mdi-chevron-left'>", next: "<i class='mdi mdi-chevron-right'>" },
                    },
                    drawCallback: function () {
                        $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                    },
                });
                var a = $("#datatable-buttons").DataTable({
                    lengthChange: !1,
                    buttons: [
                        { extend: "copy", className: "btn-light" },
                        { extend: "print", className: "btn-light" },
                        { extend: "pdf", className: "btn-light" },
                    ],
                    language: {
                        paginate: { previous: "<i class='mdi mdi-chevron-left'>", next: "<i class='mdi mdi-chevron-right'>" },
                    },
                    drawCallback: function () {
                        $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                    },
                });
                $("#selection-datatable").DataTable({
                    select: { style: "multi" },
                    language: {
                        paginate: { previous: "<i class='mdi mdi-chevron-left'>", next: "<i class='mdi mdi-chevron-right'>" },
                    },
                    drawCallback: function () {
                        $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                    },
                }),
                    $("#key-datatable").DataTable({
                        keys: !0,
                        language: {
                            paginate: { previous: "<i class='mdi mdi-chevron-left'>", next: "<i class='mdi mdi-chevron-right'>" },
                        },
                        drawCallback: function () {
                            $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                        },
                    }),
                    a.buttons().container().appendTo("#datatable-buttons_wrapper .col-md-6:eq(0)"),
                    $("#alternative-page-datatable").DataTable({
                        pagingType: "full_numbers",
                        drawCallback: function () {
                            $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                        },
                    }),
                    $("#scroll-vertical-datatable").DataTable({
                        scrollY: "350px",
                        scrollCollapse: !0,
                        paging: !1,
                        language: {
                            paginate: { previous: "<i class='mdi mdi-chevron-left'>", next: "<i class='mdi mdi-chevron-right'>" },
                        },
                        drawCallback: function () {
                            $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                        },
                    }),
                    $("#scroll-horizontal-datatable").DataTable({
                        scrollX: !0,
                        language: {
                            paginate: { previous: "<i class='mdi mdi-chevron-left'>", next: "<i class='mdi mdi-chevron-right'>" },
                        },
                        drawCallback: function () {
                            $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                        },
                    }),
                    $("#complex-header-datatable").DataTable({
                        language: {
                            paginate: { previous: "<i class='mdi mdi-chevron-left'>", next: "<i class='mdi mdi-chevron-right'>" },
                        },
                        drawCallback: function () {
                            $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                        },
                        columnDefs: [{ visible: !1, targets: -1 }],
                    }),
                    $("#row-callback-datatable").DataTable({
                        language: {
                            paginate: { previous: "<i class='mdi mdi-chevron-left'>", next: "<i class='mdi mdi-chevron-right'>" },
                        },
                        drawCallback: function () {
                            $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                        },
                        createdRow: function (a, e, t) {
                            15e4 < +e[5].replace(/[\$,]/g, "") && $("td", a).eq(5).addClass("text-danger");
                        },
                    }),
                    $("#state-saving-datatable").DataTable({
                        stateSave: !0,
                        language: {
                            paginate: { previous: "<i class='mdi mdi-chevron-left'>", next: "<i class='mdi mdi-chevron-right'>" },
                        },
                        drawCallback: function () {
                            $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                        },
                    }),
                    $(".dataTables_length select").addClass("form-select form-select-sm");
                */
            });
        </script>


@endsection

