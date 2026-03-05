@extends('layouts.base')

{{-- Page Content --}}
@section('page_content')
    <style>
        /* ===== ОБЩИЕ СТИЛИ ===== */
        #basic-datatable tbody tr { height: 60px; }
        #basic-datatable tbody td { vertical-align: middle !important; line-height: 1.2; }
        #basic-datatable .btn-sm {
            height: 32px; width: 32px;
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0 !important;
        }
        #basic-datatable td:nth-child(1) {
            white-space: normal; word-break: break-word; word-wrap: break-word;
            overflow-wrap: break-word; max-width: 200px;
        }

        /* ===== SELECT2 ===== 
        .select2-container--default .select2-selection--single {
            background: none; border: 1px solid #dee2e6; border-radius: 0.25rem; height: 38px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px; color: #6c757d; padding-left: 12px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
        .select2-dropdown { background-color: #f2f5f7; border: 1px solid #dee2e6; }
        .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #28a745; }
        .select2-container--default .select2-selection--single .select2-selection__placeholder { color: #98a6ad; }
        .select2-container--default .select2-selection--single:hover { border-color: #28a745; }
        */

        /* ===== МОДАЛЬНОЕ ОКНО ===== 
        .modal-xl { max-width: 1300px; }
        .modal-content {
            border: none; border-radius: 0.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15); background-color: #ffffff;
        }
        .modal-header {
            background: linear-gradient(135deg, #2d7a4b 0%, #28a745 100%);
            border-bottom: none; padding: 1.2rem 1.5rem; border-radius: 0.5rem 0.5rem 0 0;
        }
        .modal-header .modal-title {
            font-weight: 500; font-size: 1.2rem; letter-spacing: 0.5px;
            display: flex; align-items: center;
        }
        .modal-header .modal-title i { font-size: 1.4rem; margin-right: 10px; opacity: 0.9; }
        .modal-header .btn-close {
            background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
            opacity: 0.8; transition: opacity 0.2s;
        }
        .modal-header .btn-close:hover { opacity: 1; }
        .modal-body { padding: 1.8rem; background-color: #f8fafc; }
        */

        /* ===== КАРТОЧКИ В МОДАЛКЕ ===== 
        .modal-body .card {
            border: none; border-radius: 0.5rem; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
            margin-bottom: 1.5rem; background-color: #ffffff; transition: box-shadow 0.2s;
        }
        .modal-body .card:hover { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); }
        .modal-body .card-header {
            background-color: #f5f7f9 !important; border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.25rem; border-radius: 0.5rem 0.5rem 0 0 !important;
        }
        .modal-body .card-header h5 {
            color: #2d3b48; font-weight: 500; font-size: 1rem;
            text-transform: uppercase; letter-spacing: 0.3px; display: flex; align-items: center;
        }
        .modal-body .card-header h5 i { color: #28a745; font-size: 1.2rem; margin-right: 8px; }
        .modal-body .card-body { padding: 1.5rem; background-color: #ffffff; }
        .modal-body .card:nth-child(even) .card-header { background-color: #f0f3f7 !important; }
        */

        /* ===== ФОРМЫ ===== 
        .modal-body .form-label {
            font-weight: 500; color: #495057; font-size: 0.9rem; margin-bottom: 0.4rem;
        }
        .modal-body .form-control {
            border: 1px solid #dee2e6; border-radius: 0.3rem; padding: 0.55rem 0.9rem;
            font-size: 0.9rem; transition: border-color 0.15s, box-shadow 0.15s; background-color: #ffffff;
        }
        .modal-body .form-control:focus {
            border-color: #28a745; box-shadow: 0 0 0 0.15rem rgba(40, 167, 69, 0.15);
        }
        .modal-body .form-control[readonly] {
            background-color: #f8f9fa; border-color: #e9ecef; color: #6c757d;
        }

        .select2-container .select2-selection--multiple .select2-selection__choice {line-height: 200% !important;}
        .select2-container--default .select2-selection--multiple .select2-selection__clear {
            margin-top: 9px;
        }
        */

        /* ===== SELECT2 В МОДАЛКЕ ===== 
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #dee2e6 !important; border-radius: 0.3rem !important;
            min-height: 40px; background-color: #ffffff;
            zline-height: 25px;
        }
        
        .select2-container .select2-selection--multiple .select2-selection__choice {line-height: 200% !important;}
        .select2-container .select2-search__field { line-height: 200% !important; }
        
        .select2-container--default .select2-selection--multiple .select2-selection__clear {
            cursor: pointer;
            float: right;
            font-weight: bold;
            margin-top: 9px;
            margin-right: 10px;
            padding: 1px;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #e8f5e9 !important; border: 1px solid #c8e6c9 !important;
            color: #1e7e34 !important; border-radius: 0.2rem; zpadding: 0.2rem 0.5rem;
            zmargin: 0.25rem; zfont-size: 0.85rem;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #1e7e34 !important; margin-right: 0.25rem; font-weight: bold;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #dc3545 !important;
        }
        .select2-container--default .select2-selection--single {
            border: 1px solid #dee2e6 !important; border-radius: 0.3rem !important;
            height: 42px !important; background-color: #ffffff;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px !important; padding-left: 12px; color: #495057;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
        }
        */
        

        /* ===== КНОПКИ ===== 
        .modal-body .btn-outline-success {
            border: 1px dashed #28a745; color: #28a745; background: transparent;
            padding: 0.55rem 1.2rem; font-size: 0.9rem; transition: all 0.2s;
        }
        .modal-body .btn-outline-success:hover {
            background: #28a745; color: white; border: 1px solid #28a745;
        }
        #addValueRow {
            background-color: transparent; border: 1px dashed #28a745; color: #28a745;
            padding: 0.5rem 1.2rem; font-size: 0.9rem; transition: all 0.2s;
        }
        #addValueRow:hover {
            background-color: #28a745; color: white; border: 1px solid #28a745;
        }
        .remove-value-row {
            background-color: transparent; border: 1px solid #dc3545; color: #dc3545;
            width: 32px; height: 32px; padding: 0; display: inline-flex;
            align-items: center; justify-content: center; border-radius: 0.2rem;
            transition: all 0.2s;
        }
        .remove-value-row:hover { background-color: #dc3545; color: white; }
        */

        /* ===== ТАБЛИЦА ЗНАЧЕНИЙ ===== 
        #values-table {
            border: 1px solid #e9ecef; border-radius: 0.4rem; overflow: hidden;
        }
        #values-table thead th {
            background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; color: #495057;
            font-weight: 600; font-size: 0.85rem; text-transform: uppercase;
            letter-spacing: 0.3px; padding: 0.9rem 0.75rem;
        }
        #values-table tbody td {
            padding: 0.75rem; vertical-align: middle; border-bottom: 1px solid #e9ecef;
            background-color: #ffffff;
        }
        #values-table tbody tr:hover td { background-color: #f8fbfe; }
        #values-table tfoot td {
            background-color: #f8f9fa; padding: 0.75rem; border-top: 2px solid #dee2e6;
        }
            */

        /* ===== ФУТЕР МОДАЛКИ ===== 
        .modal-footer {
            border-top: 1px solid #e9ecef; padding: 1.2rem 1.5rem;
            background-color: #f8fafc; border-radius: 0 0 0.5rem 0.5rem;
        }
        .modal-footer .btn {
            padding: 0.6rem 1.5rem; font-size: 0.95rem; border-radius: 0.3rem;
            font-weight: 500; transition: all 0.2s;
        }
        .modal-footer .btn-light {
            background-color: #e9ecef; border-color: #e9ecef; color: #495057;
        }
        .modal-footer .btn-light:hover {
            background-color: #dee2e6; border-color: #dee2e6;
        }
        .modal-footer .btn-success {
            background-color: #28a745; border-color: #28a745;
        }
        .modal-footer .btn-success:hover {
            background-color: #218838; border-color: #1e7e34;
        }
        */

        /* ===== АДАПТАЦИЯ ===== */
        @media (max-width: 768px) {
            .modal-body { padding: 1rem; }
            #values-table { font-size: 0.85rem; }
        }

        /* ===== БЛОК ГРУПП ===== */
        .groups-container {
            display: flex; gap: 20px; align-items: flex-end;
        }
        .groups-select-wrapper {
            flex: 1; min-width: 0;
        }
        .groups-btn-wrapper {
            width: 160px; flex-shrink: 0;
        }
        .groups-btn-wrapper .btn {
            width: 100%; height: 42px; display: flex;
            align-items: center; justify-content: center;
        }
    </style>

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Справочник технических характеристик</h4>
                <div class="page-title-right">
                    <a href="#" class="btn btn-success btn-rounded">
                        <i class="mdi mdi-plus me-1"></i> Создать
                    </a>
                </div>
            </div>
        </div>
    </div>     
    <!-- end page title --> 

    <!-- Фильтр по группам -->
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
                            <select id="group-select2" class="form-control" data-toggle="select2" style="width: 100%;">
                                <option value="all">Все параметры</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <span class="text-muted small">
                                <i class="mdi mdi-magnify"></i> Можно искать по названию группы
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
                    <table id="basic-datatable" class="table dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>Название</th>
                                <th width="10%">Группа</th>
                                <th width="1%">Файл</th>
                                <th width="1%"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования -->
    <div class="modal fade" id="editParamModal" tabindex="-1" role="dialog" aria-labelledby="editParamModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="editParamModalLabel">
                        <i class="mdi mdi-pencil-circle"></i> Редактирование параметра
                    </h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <form id="editParamForm">
                        @csrf
                        <input type="hidden" id="edit_param_id" name="param_id">
                        
                        <!-- Основная информация -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="mdi mdi-information-outline"></i> Основная информация</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="edit_param_name" class="form-label">
                                                Название параметра <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="edit_param_name" 
                                                   name="name" required placeholder="Введите название параметра">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="edit_param_type" class="form-label">Тип параметра</label>
                                            <input type="text" class="form-control" id="edit_param_type" 
                                                   readonly value="Техническая характеристика">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Блок групп -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="mdi mdi-folder-multiple"></i> Принадлежность к группе параметров</h5>
                            </div>
                            <div class="card-body">
                                <div class="groups-container">
                                    <div class="groups-select-wrapper">
                                        <label for="edit_groups_select" class="form-label mb-1">Выберите группы</label>
                                        <select id="edit_groups_select" class="form-control" multiple="multiple" style="width: 100%;"></select>
                                    </div>
                                    <div class="groups-btn-wrapper">
                                        <label class="form-label mb-1 opacity-0">Скрытый</label>
                                        <button type="button" class="btn btn-outline-success" id="createNewGroupBtn">
                                            <i class="mdi mdi-plus-circle me-1"></i> Создать
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted mt-2 d-block">
                                    <i class="mdi mdi-information-outline me-1"></i>
                                    Можно выбрать несколько групп. Начните вводить текст для поиска.
                                </small>
                            </div>
                        </div>
                        
                        <!-- Единицы измерения и значения -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="mdi mdi-ruler"></i> Единицы измерения и значения</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table" id="values-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 50%">Единица измерения</th>
                                                <th style="width: 40%">Значение</th>
                                                <th style="width: 10%"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="values-container"></tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4">
                                                    <button type="button" class="btn" id="addValueRow">
                                                        <i class="mdi mdi-plus-circle me-1"></i> Добавить значение
                                                    </button>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <small class="text-muted">
                                    <i class="mdi mdi-lightbulb-on-outline me-1"></i>
                                    Заполните единицу измерения и значение
                                </small>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="saveParamBtn">
                        <i class="mdi mdi-content-save me-1"></i> Сохранить изменения
                    </button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="mdi mdi-close-circle me-1"></i> Отмена
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно создания группы -->
    <div class="modal fade" id="createGroupModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-white"><i class="mdi mdi-folder-plus"></i> Создание группы</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_group_name" class="form-label">Название группы</label>
                        <input type="text" class="form-control" id="new_group_name" placeholder="Введите название новой группы">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-success" id="saveNewGroupBtn">Создать</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно создания единицы измерения -->
    <div class="modal fade" id="createUnitModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-white"><i class="mdi mdi-ruler-plus"></i> Создание единицы измерения</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_unit_name" class="form-label">Название</label>
                        <input type="text" class="form-control" id="new_unit_name" placeholder="Например: кг, м, шт, кВт">
                    </div>
                    <div class="mb-3">
                        <label for="new_unit_type" class="form-label">Тип значения</label>
                        <select class="form-control" id="new_unit_type">
                            <option value="integer">Целое число</option>
                            <option value="float">Дробное число</option>
                            <option value="text">Текст</option>
                            <option value="boolean">Да/Нет (булево)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-success" id="saveNewUnitBtn">Создать</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // ===== ИНИЦИАЛИЗАЦИЯ =====
    let table;
    const references = { groups: [], units: [] };
    let referencesLoaded = false; // Флаг загрузки справочников
    
    initSelect2();
    initDataTable();
    loadReferences(); // Загружаем справочники сразу
    
    // ===== SELECT2 ДЛЯ ФИЛЬТРА =====
    function initSelect2() {
        $('#group-select2').select2({ minimumInputLength: 0, language: 'ru' });
    }
    
    // ===== DATATABLE =====
    function initDataTable() {
        table = $('#basic-datatable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '{{ route("lm_tech.data") }}',
                type: 'GET',
                data: (d) => { d.group_id = $('#group-select2').val(); },
                dataSrc: 'data',
                error: (xhr, error, thrown) => {
                    console.error('DataTable error:', error);
                }
            },
            columns: [
                { data: 'paramName' },
                { data: 'groups', render: (data) => data || '<span class="text-muted">-</span>' },
                { data: 'files',  render: (data) => data || '<span class="text-muted">-</span>' },
                //{ data: 'fileCount', render: (data) => data || '<span class="text-muted">-</span>' },
                {
                    data: 'paramNameId',
                    render: (data, type, row) => `
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-danger btn-sm btn-rounded edit-btn" 
                                    title="Редактировать" data-id="${data}" 
                                    data-name="${(row.paramName || '').replace(/'/g, "\\'")}">
                                <i class="mdi mdi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-primary btn-sm btn-rounded delete-btn" 
                                    title="Удалить" data-id="${data}" 
                                    data-name="${(row.paramName || '').replace(/'/g, "\\'")}">
                                <i class="mdi mdi-delete"></i>
                            </button>
                        </div>
                        <form id="delete-form-${data}" action="/livemachines/sprav/${data}" 
                              method="POST" style="display: none;">
                            @csrf @method('DELETE')
                        </form>
                    `
                }
            ],
            language: {
                processing: '<div style="margin:20px;" class="spinner-border text-success" role="status"><span class="visually-hidden">Загрузка...</span></div>',
                search: "Поиск:", lengthMenu: "Показать _MENU_ записей",
                info: "Показаны с _START_ по _END_ из _TOTAL_ записей",
                infoEmpty: "Показаны с 0 по 0 из 0 записей",
                infoFiltered: "(отфильтровано из _MAX_ записей)",
                zeroRecords: "Записи отсутствуют", emptyTable: "В таблице отсутствуют данные",
                paginate: { first: "Первая", previous: "<i class='mdi mdi-chevron-left'>", 
                           next: "<i class='mdi mdi-chevron-right'>", last: "Последняя" }
            },
            drawCallback: () => { $(".dataTables_paginate > .pagination").addClass("pagination-rounded"); },
            columnDefs: [{ orderable: false, targets: -1 }],
            stateSave: true, stateDuration: 0
        });
    }
    
    // ===== ЗАГРУЗКА СПРАВОЧНИКОВ =====
    function loadReferences() {
        $.ajax({
            url: '{{ route("lm_tech.references") }}',
            type: 'GET',
            success: (response) => {
                if (response.success) {
                    references.groups = response.data.groups;
                    references.units = response.data.units;
                    referencesLoaded = true;
                    console.log('References loaded:', references); // Для отладки
                    
                    // Инициализируем Select2 для групп пустыми данными
                    initGroupSelect();
                }
            },
            error: (xhr) => console.error('Error loading references:', xhr)
        });
    }
    
    // ===== ИНИЦИАЛИЗАЦИЯ SELECT2 ДЛЯ ГРУПП (ПУСТАЯ) =====
    function initGroupSelect() {
        const $groupSelect = $('#edit_groups_select');
        
        if ($groupSelect.data('select2')) {
            $groupSelect.select2('destroy');
        }
        
        $groupSelect.empty();
        
        // Добавляем все группы из справочника
        references.groups.forEach(group => {
            $groupSelect.append(new Option(group.name, group.id, false, false));
        });
        
        $groupSelect.select2({
            dropdownParent: $('#editParamModal'),
            placeholder: 'Выберите группы',
            allowClear: true,
            language: 'ru',
            multiple: true,
            width: '100%',
            templateResult: (data) => {
                if (data.loading || !data.id) return data.text;
                return $('<span><i class="mdi mdi-folder-outline text-success me-1"></i>' + data.text + '</span>');
            }
        });
        
        console.log('Group select initialized with', references.groups.length, 'groups');
    }
    
    // ===== ОБРАБОТЧИКИ СОБЫТИЙ =====
    $('#group-select2').on('change', () => {
        table.state.clear();
        table.ajax.reload();
    });
    
    // ===== УДАЛЕНИЕ =====
    $('#basic-datatable').on('click', '.delete-btn', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const $row = $btn.closest('tr');
        const id = $btn.data('id');
        const name = $btn.data('name');
        
        Swal.fire({
            title: 'Подтверждение удаления',
            html: `Вы действительно хотите удалить характеристику<br><strong>"${name}"</strong>?`,
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#1abc9c', cancelButtonColor: '#f1556c',
            confirmButtonText: 'Да, удалить!', cancelButtonText: 'Отмена',
            reverseButtons: false
        }).then((result) => {
            if (!result.value) return;
            
            $btn.html('<span class="spinner-border spinner-border-sm"></span>').prop('disabled', true);
            
            $.ajax({
                url: '/livemachines/sprav/' + id,
                type: 'POST',
                data: { '_token': '{{ csrf_token() }}', '_method': 'DELETE' },
                success: (response) => {
                    if (response.success) {
                        $row.fadeOut(400, () => {
                            table.row($row).remove().draw(false);
                            if (table.page.info().recordsDisplay > 0 && 
                                table.page.info().recordsDisplay <= table.page.info().start) {
                                table.page('previous').draw(false);
                            }
                        });
                        Swal.fire({ title: 'Удалено!', text: response.message, icon: 'success', 
                                   timer: 1500, showConfirmButton: false });
                    } else {
                        $btn.html('<i class="mdi mdi-delete"></i>').prop('disabled', false);
                        Swal.fire({ title: 'Ошибка!', text: response.message, icon: 'error' });
                    }
                },
                error: (xhr) => {
                    $btn.html('<i class="mdi mdi-delete"></i>').prop('disabled', false);
                    Swal.fire({ title: 'Ошибка!', text: 'Произошла ошибка при удалении', icon: 'error' });
                }
            });
        });
    });
    
    // ===== РЕДАКТИРОВАНИЕ =====
    $('#basic-datatable').on('click', '.edit-btn', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        
        // Проверяем, загружены ли справочники
        if (!referencesLoaded) {
            Swal.fire({
                title: 'Загрузка...',
                html: 'Загружаем справочники, пожалуйста подождите',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            
            // Пробуем загрузить справочники еще раз
            loadReferences();
            
            // Ждем загрузки и потом открываем
            const checkInterval = setInterval(() => {
                if (referencesLoaded) {
                    clearInterval(checkInterval);
                    Swal.close();
                    loadEditData(id);
                }
            }, 100);
        } else {
            loadEditData(id);
        }
    });
    
    // ===== ЗАГРУЗКА ДАННЫХ ДЛЯ РЕДАКТИРОВАНИЯ =====
    function loadEditData(id) {
        Swal.fire({ 
            title: 'Загрузка...', 
            html: 'Загружаем данные параметра', 
            allowOutsideClick: false, 
            didOpen: () => Swal.showLoading() 
        });
        
        $.ajax({
            url: '/livemachines/sprav/tech/edit/' + id,
            type: 'GET',
            success: (response) => {
                Swal.close();
                if (response.success) {
                    fillEditModal(response.data);
                    $('#editParamModal').modal('show');
                } else {
                    Swal.fire({ title: 'Ошибка!', text: response.message, icon: 'error' });
                }
            },
            error: () => {
                Swal.close();
                Swal.fire({ title: 'Ошибка!', text: 'Не удалось загрузить данные', icon: 'error' });
            }
        });
    }
    
    // ===== ЗАПОЛНЕНИЕ МОДАЛЬНОГО ОКНА =====
    function fillEditModal(data) {
        console.log('Filling modal with data:', data);
        
        $('#edit_param_id').val(data.param.id);
        $('#edit_param_name').val(data.param.name);
        
        // Пересоздаем Select2 для групп с правильными данными
        const $groupSelect = $('#edit_groups_select');
        
        if ($groupSelect.data('select2')) {
            $groupSelect.select2('destroy');
        }
        
        $groupSelect.empty();
        
        // Добавляем все группы из справочника
        references.groups.forEach(group => {
            $groupSelect.append(new Option(group.name, group.id, false, false));
        });
        
        $groupSelect.select2({
            dropdownParent: $('#editParamModal'),
            placeholder: 'Выберите группы',
            allowClear: true,
            language: 'ru',
            multiple: true,
            width: '100%',
            templateResult: (data) => {
                if (data.loading || !data.id) return data.text;
                return $('<span><i class="mdi mdi-folder-outline text-success me-1"></i>' + data.text + '</span>');
            }
        });
        
        // Устанавливаем выбранные группы
        if (data.groups && data.groups.length > 0) {
            const groupIds = data.groups.map(g => g.id);
            console.log('Setting groups:', groupIds);
            $groupSelect.val(groupIds).trigger('change');
        }
        
        // Заполняем значения
        $('#values-container').empty();
        if (data.values && data.values.length > 0) {
            data.values.forEach(value => addValueRow(value));
        } else {
            addValueRow();
        }
    }
    
    // ===== ДОБАВЛЕНИЕ СТРОКИ ЗНАЧЕНИЯ =====
    function addValueRow(value = {}) {
        const rowHtml = `
            <tr class="value-row">
                <td><select class="form-control unit-select" style="width: 100%;" name="units[]"></select></td>
                <td><input type="text" class="form-control value-input" name="values[]" value="${value.value || ''}" placeholder="Значение"></td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-value-row" title="Удалить"><i class="mdi mdi-delete"></i></button></td>
            </tr>
        `;
        
        $('#values-container').append(rowHtml);
        const $newRow = $('#values-container tr:last');
        const $unitSelect = $newRow.find('.unit-select');
        
        references.units.forEach(unit => {
            const selected = (value.unit_id == unit.id) ? 'selected' : '';
            $unitSelect.append(`<option value="${unit.id}" data-type="${unit.type}" ${selected}>${unit.name}</option>`);
        });
        
        $unitSelect.select2({
            dropdownParent: $('#editParamModal'),
            placeholder: 'Выберите единицу', 
            language: 'ru', 
            width: '100%', 
            minimumResultsForSearch: 5,
            templateResult: (data) => {
                if (data.loading || !data.id) return data.text;
                return $('<span><i class="mdi mdi-ruler text-success me-1"></i>' + data.text + '</span>');
            }
        });
        
        $unitSelect.on('change', function() {
            const type = $(this).find('option:selected').data('type');
            const $input = $(this).closest('tr').find('.value-input');
            $input.removeAttr('type step min max');
            
            if (type === 'integer') {
                $input.attr({ type: 'number', step: '1', placeholder: 'Целое число' });
            } else if (type === 'float') {
                $input.attr({ type: 'number', step: '0.01', placeholder: 'Дробное число' });
            } else if (type === 'boolean') {
                $input.attr({ type: 'number', min: '0', max: '1', placeholder: '0 или 1' });
            } else {
                $input.attr('type', 'text').attr('placeholder', 'Значение');
            }
        });
        
        if (value.unit_id) $unitSelect.trigger('change');
    }
    
    // ===== СОБЫТИЯ ДЛЯ ЗНАЧЕНИЙ =====
    $('#addValueRow').on('click', () => addValueRow());
    $('#values-container').on('click', '.remove-value-row', function() {
        $(this).closest('tr').remove();
    });
    
    // ===== СОЗДАНИЕ ГРУППЫ =====
    $('#createNewGroupBtn').on('click', () => {
        $('#new_group_name').val('');
        $('#createGroupModal').modal('show');
    });
    
    $('#saveNewGroupBtn').on('click', function() {
        const groupName = $('#new_group_name').val().trim();
        if (!groupName) {
            return Swal.fire({ title: 'Ошибка!', text: 'Введите название группы', icon: 'error' });
        }
        
        const newId = references.groups.length + 1;
        references.groups.push({ id: newId, name: groupName });
        
        const $groupSelect = $('#edit_groups_select');
        const currentValues = $groupSelect.val() || [];
        
        if ($groupSelect.data('select2')) $groupSelect.select2('destroy');
        
        $groupSelect.empty();
        references.groups.forEach(group => {
            $groupSelect.append(new Option(group.name, group.id, false, currentValues.includes(group.id)));
        });
        
        $groupSelect.select2({
            dropdownParent: $('#editParamModal'),
            placeholder: 'Выберите группы', 
            allowClear: true, 
            language: 'ru', 
            multiple: true, 
            width: '100%'
        });
        
        $groupSelect.val(currentValues).trigger('change');
        $('#createGroupModal').modal('hide');
        
        Swal.fire({ title: 'Создано!', text: `Группа "${groupName}" создана`, icon: 'success', timer: 1500 });
    });
    
    // ===== СОЗДАНИЕ ЕДИНИЦЫ ИЗМЕРЕНИЯ =====
    $('#saveNewUnitBtn').on('click', function() {
        const unitName = $('#new_unit_name').val().trim();
        const unitType = $('#new_unit_type').val();
        
        if (!unitName) {
            return Swal.fire({ title: 'Ошибка!', text: 'Введите название единицы измерения', icon: 'error' });
        }
        
        const newId = references.units.length + 1;
        references.units.push({ id: newId, name: unitName, type: unitType });
        
        $('.unit-select').each(function() {
            const $this = $(this);
            const currentVal = $this.val();
            
            if ($this.data('select2')) $this.select2('destroy');
            
            $this.empty();
            references.units.forEach(unit => {
                $this.append(`<option value="${unit.id}" data-type="${unit.type}">${unit.name} (${unit.type})</option>`);
            });
            
            $this.select2({
                dropdownParent: $('#editParamModal'),
                placeholder: 'Выберите единицу', 
                language: 'ru', 
                width: '100%', 
                minimumResultsForSearch: 5
            });
            
            if (currentVal) $this.val(currentVal).trigger('change');
        });
        
        $('#createUnitModal').modal('hide');
        Swal.fire({ title: 'Создано!', text: `Единица "${unitName}" создана`, icon: 'success', timer: 1500 });
    });
    
    // ===== СОХРАНЕНИЕ ИЗМЕНЕНИЙ =====
    $('#saveParamBtn').on('click', function() {
        const formData = {
            '_token': '{{ csrf_token() }}',
            'name': $('#edit_param_name').val(),
            'groups': $('#edit_groups_select').val() || [],
            'values': []
        };
        
        if (!formData.name) {
            return Swal.fire({ title: 'Ошибка!', text: 'Название параметра обязательно', icon: 'error' });
        }
        
        $('.value-row').each(function() {
            const $row = $(this);
            const unitId = $row.find('.unit-select').val();
            const value = $row.find('.value-input').val();
            const textValue = $row.find('input[name="text_values[]"]').val();
            
            if (unitId || value || textValue) {
                formData.values.push({ 
                    unit_id: unitId, 
                    value: value, 
                    text_value: textValue 
                });
            }
        });
        
        const id = $('#edit_param_id').val();
        
        Swal.fire({ 
            title: 'Сохранение...', 
            allowOutsideClick: false, 
            didOpen: () => Swal.showLoading() 
        });
        
        $.ajax({
            url: '/livemachines/sprav/tech/update/' + id,
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: (response) => {
                Swal.close();
                if (response.success) {
                    $('#editParamModal').modal('hide');
                    Swal.fire({ 
                        title: 'Сохранено!', 
                        text: response.message, 
                        icon: 'success', 
                        timer: 1500,
                        showConfirmButton: false
                    });
                    table.ajax.reload(null, false);
                } else {
                    Swal.fire({ title: 'Ошибка!', text: response.message, icon: 'error' });
                }
            },
            error: (xhr) => {
                Swal.close();
                let errorMsg = 'Произошла ошибка при сохранении';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                Swal.fire({ title: 'Ошибка!', text: errorMsg, icon: 'error' });
            }
        });
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

@section('page_more_java_script')
    <script src="/source/base/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="/source/base/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="/source/base/libs/select2/js/select2.min.js"></script>
    <script src="/source/base/libs/select2/js/i18n/ru.js"></script>
    <script src="/source/base/libs/sweetalert2/sweetalert2.min.js"></script>
@endsection