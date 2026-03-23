@extends('layouts.base')

@section('head_title', $title)

{{-- Page Content --}}
@section('page_content')

    <!-- Заголовок страницы -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box sticky">
                <h4 class="page-title">
                    @yield('head_title')
                </h4>
                <div class="page-title-right">
                    <a href="#" id="btnCreate" class="btn btn-success btn-rounded">
                        <i class="mdi mdi-plus me-1"></i> Создать
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтр по типу -->
    <div class="row mb-1">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-auto ms-3">
                            <label for="type-select2" class="form-label mb-0 fw-bold">
                                <i class="mdi mdi-filter-outline text-info"></i> Тип:
                            </label>
                        </div>
                        <div class="col-md-2">
                            <select id="type-select2" class="form-control" data-toggle="select2" style="width: 100%;">
                                <option value="1" selected>Технические характеристики</option>
                                <option value="2">Комплектации</option>
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <span class="text-muted small">
                                <i class="mdi mdi-magnify"></i> Фильтр по типу
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Таблица -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!-- Таблица -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <table id="basic-datatable" 
                                        class="table dt-responsive nowrap w-100"
                                        data-url="{{ route('lm_group_data') }}"
                                        data-create-url="{{ route('lm_group_create') }}"
                                        data-edit-url="{{ route('lm_group_edit', ['id' => 'REPLACE_WITH_ID']) }}"
                                        data-update-url={{ route('lm_group_update', ['id' => 'REPLACE_WITH_ID']) }}
                                        data-delete-url={{ route('lm_group_remove', ['id' => 'REPLACE_WITH_ID']) }}
                                    >
                                        <thead>
                                            <tr>
                                                <th>Название</th>
                                                <th width="10%">Параметров</th>
                                                <th width="1%">Файлов</th>
                                                <th width="1%"></th>
                                            </tr>
                                        </thead>
                                        <tbody>{{-- Данные будут загружены через AJAX --}}</tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="editModalLabel">
                        <i class="mdi mdi-pencil-circle"></i> <span id="modalTitleText">Редактирование</span>
                    </h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <form id="editForm">
                        @csrf
                        <input type="hidden" id="edit_id" name="id">
                        
                        <!-- Основная информация -->
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                            <label for="edit_name" class="form-label">
                                                Название группы <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="edit_name" style="text-transform: uppercase;"
                                                   name="name" required placeholder="Введите название группы">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>

                <div class="modal-footer">
                    <div class="d-flex align-items-center me-auto">
                        <!-- Здесь ничего не добавляем, чекбокс будет справа -->
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <button type="button" class="btn btn-success" id="saveBtn">
                            <i class="mdi mdi-content-save me-1"></i> Сохранить
                        </button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            <i class="mdi mdi-close-circle me-1"></i> Отмена
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

@endsection

@section('head_other')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="/source/base/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <link href="/source/base/libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <link href="/source/base/libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <link href="/source/base/libs/datatables.net-select-bs5/css/select.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <link href="/source/base/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="/source/base/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <link href="/source/base/livemachines/group/style.css?<?=time()?>" rel="stylesheet" type="text/css" />
@endsection

@section('page_more_java_script')
    <script src="/source/base/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="/source/base/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="/source/base/libs/select2/js/select2.min.js"></script>
    <script src="/source/base/libs/select2/js/i18n/ru.js"></script>
    <script src="/source/base/libs/sweetalert2/sweetalert2.min.js"></script>
    <script src="/source/base/livemachines/group/script.js?<?=time()?>"></script>
@endsection