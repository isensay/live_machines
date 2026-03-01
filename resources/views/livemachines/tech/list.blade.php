@extends('layouts.base')

{{-- Page Content --}}
@section('page_content')
    
    {{-- Page Title --}}
    {{-- @include('includes.title') --}}

    <style>
        /* Адаптация Select2 под фон Minton */
        .select2-container--default .select2-selection--single {
            background: none; /* цвет фона как в Minton */
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            height: 38px;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
            color: #6c757d;
            padding-left: 12px;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        
        /* Стили для выпадающего списка */
        .select2-dropdown {
            background-color: #f2f5f7;
            border: 1px solid #dee2e6;
        }
        
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #28a745; /* зеленый цвет Minton */
        }
        
        /* Для темной темы Minton */
        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #98a6ad;
        }
        
        /* Hover эффект */
        .select2-container--default .select2-selection--single:hover {
            zborder-color: #28a745;
        }
    </style>

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Справочник технических характеристик</h4>
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

    <div class="row mb-1">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-auto">
                            <label for="group-select2" class="form-label mb-0 fw-bold">
                                <i class="mdi mdi-filter-outline text-success"></i> Группа:
                            </label>
                        </div>
                        <div class="col-md-4">
                            <select id="group-select2" class="form-control" style="width: 100%;">
                                <option value="all">Все группы</option>
                                
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <span class="text-muted small">
                                <i class="mdi mdi-magnify"></i> Можно искать по названию
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        <style>
            #basic-datatable tbody tr {
                height: 60px; /* фиксированная высота строки */
            }
            #basic-datatable tbody td {
                vertical-align: middle !important;
                line-height: 1.2;
            }
            #basic-datatable .btn-sm {
                height: 32px;
                width: 32px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0 !important;
            }

            

            #basic-datatable td:nth-child(1) {
                white-space: normal;      /* разрешаем перенос */
    word-break: break-word;   /* переносим длинные слова */
    word-wrap: break-word;    /* для старых браузеров */
    overflow-wrap: break-word; /* современный аналог */
    max-width: 200px;         /* максимальная ширина */
            }
        </style>
        
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="basic-datatable" class="table dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>Название</th>
                                <th width="1%">Производителей</th>
                                <th width="1%">Файлов</th>
                                <th width="1%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Данные будут загружены через AJAX --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

@endsection



@push('scripts')
<script>

    $(document).ready(function() {
    
        // Обработчик удаления
        $('#basic-datatable').on('click', '.delete-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $row = $btn.closest('tr');
            var id = $btn.data('id');
            var name = $btn.data('name');
            
            // Сохраняем текущую страницу до удаления
            var currentPage = table.page();
            
            Swal.fire({
                title: 'Подтверждение удаления',
                html: `Вы действительно хотите удалить характеристику<br><strong>"${name}"</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#1abc9c',
                cancelButtonColor: '#f1556c',
                confirmButtonText: 'Да, удалить!',
                cancelButtonText: 'Отмена',
                reverseButtons: false
            }).then(function(result) {
                if (result.value) {
                    // Показываем загрузку на кнопке
                    $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>').prop('disabled', true);
                    
                    // Отправляем AJAX запрос
                    $.ajax({
                        url: '/livemachines/sprav/' + id,
                        type: 'POST',
                        data: {
                            '_token': '{{ csrf_token() }}',
                            '_method': 'DELETE'
                        },
                        success: function(response) {
                            if (response.success) {
                                // Плавно удаляем строку
                                $row.fadeOut(400, function() {
                                    // Удаляем строку из DataTable
                                    table.row($row).remove().draw(false); // false сохраняет текущую страницу
                                    
                                    // Проверяем, не осталась ли страница пустой
                                    if (table.page.info().recordsDisplay > 0) {
                                        // Если текущая страница стала пустой (последняя запись на странице удалена)
                                        if (table.page.info().recordsDisplay <= table.page.info().start) {
                                            // Переходим на предыдущую страницу
                                            table.page('previous').draw(false);
                                        }
                                    }
                                });
                                
                                // Показываем уведомление
                                Swal.fire({
                                    title: 'Удалено!',
                                    text: response.message || 'Запись успешно удалена',
                                    icon: 'success',
                                    confirmButtonColor: '#1abc9c',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                
                            } else {
                                $btn.html('<i class="mdi mdi-delete"></i>').prop('disabled', false);
                                
                                Swal.fire({
                                    title: 'Ошибка!',
                                    text: response.message || 'Не удалось удалить запись',
                                    icon: 'error',
                                    confirmButtonColor: '#1abc9c'
                                });
                            }
                        },
                        error: function(xhr) {
                            $btn.html('<i class="mdi mdi-delete"></i>').prop('disabled', false);
                            
                            let errorMessage = 'Произошла ошибка при удалении';
                            
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.status === 404) {
                                errorMessage = 'Запись не найдена';
                            } else if (xhr.status === 419) {
                                errorMessage = 'CSRF токен устарел. Обновите страницу';
                            } else if (xhr.status === 500) {
                                errorMessage = 'Внутренняя ошибка сервера';
                            }
                            
                            Swal.fire({
                                title: 'Ошибка!',
                                text: errorMessage,
                                icon: 'error',
                                confirmButtonColor: '#1abc9c'
                            });
                        }
                    });
                }
            });
        });

        // Инициализация Select2
        $('#group-select2').select2({
            minimumInputLength: 0,
            language: 'ru'
        });

        // Инициализация DataTable
        var table = $('#basic-datatable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '{{ route("lm_tech.data") }}',
                type: 'GET',
                data: function(d) {
                    d.group_id = $('#group-select2').val();
                },
                dataSrc: 'data',
                error: function(xhr, error, thrown) {
                    console.log('DataTable error:', error);
                    
                    $('#basic-datatable tbody').html(`
                        <tr>
                            <td colspan="4" class="text-center text-danger p-4">
                                <i class="mdi mdi-alert-circle" style="font-size: 2rem;"></i>
                                <p class="mt-2">Ошибка загрузки данных</p>
                                <small class="text-muted">${xhr.status} ${xhr.statusText}</small>
                            </td>
                        </tr>
                    `);
                }
            },
            columns: [
                { data: 'paramName' },
                { 
                    data: 'manufCount',
                    render: function(data) {
                        return data || '<span class="text-muted">-</span>';
                    }
                },
                { 
                    data: 'fileCount',
                    render: function(data) {
                        return data || '<span class="text-muted">-</span>';
                    }
                },
                {
                    data: 'paramNameId',
                    render: function(data, type, row) {
                        return `
                            <div class="btn-group" role="group">
                                <a href="/livemachines/sprav/${data}/edit" 
                                class="btn btn-danger btn-sm btn-rounded" 
                                title="Редактировать">
                                    <i class="mdi mdi-pencil"></i>
                                </a>
                                
                                <button type="button" 
                                        class="btn btn-primary btn-sm btn-rounded delete-btn" 
                                        title="Удалить"
                                        data-id="${data}"
                                        data-name="${(row.paramName || '').replace(/'/g, "\\'")}">
                                    <i class="mdi mdi-delete"></i>
                                </button>
                            </div>
                            
                            <form id="delete-form-${data}" 
                                action="/livemachines/sprav/${data}" 
                                method="POST" 
                                style="display: none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        `;
                    }
                }
            ],
            language: {
                processing: '<div style="margin:20px;" class="spinner-border text-success" role="status"><span class="visually-hidden">Загрузка...</span></div>',
                search: "Поиск:",
                lengthMenu: "Показать _MENU_ записей",
                info: "Показаны с _START_ по _END_ из _TOTAL_ записей",
                infoEmpty: "Показаны с 0 по 0 из 0 записей",
                infoFiltered: "(отфильтровано из _MAX_ записей)",
                zeroRecords: "Записи отсутствуют",
                emptyTable: "В таблице отсутствуют данные",
                paginate: {
                    first: "Первая",
                    previous: "<i class='mdi mdi-chevron-left'>",
                    next: "<i class='mdi mdi-chevron-right'>",
                    last: "Последняя"
                }
            },
            drawCallback: function() {
                $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
            },
            columnDefs: [
                { orderable: false, targets: -1 }
            ],
            // Сохраняем состояние таблицы
            stateSave: true,
            stateDuration: 0 // 0 = сохранять до закрытия страницы
        });

        // Обработчик изменения группы
        $('#group-select2').on('change', function() {
            // Сбрасываем сохраненное состояние при смене группы
            table.state.clear();
            table.ajax.reload();
        });

    });

</script>
@endpush




@section('head_other')
    <link href="/source/base/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <link href="/source/base/libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <link href="/source/base/libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <link href="/source/base/libs/datatables.net-select-bs5/css/select.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <link href="/source/base/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="/source/base/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
@endsection


{{-- Page Java Script --}}
@section('page_java_script')
@endsection


{{-- Page More Java Script --}}
@section('page_more_java_script')

    <script src="/source/base/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="/source/base/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>

    <script src="/source/base/libs/select2/js/select2.min.js"></script>
    <script src="/source/base/libs/select2/js/i18n/ru.js"></script>

    <script src="/source/base/libs/sweetalert2/sweetalert2.min.js"></script>

@endsection

