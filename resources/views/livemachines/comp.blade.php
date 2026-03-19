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
                    <a href="#" class="btn btn-success btn-rounded">
                        <i class="mdi mdi-plus me-1"></i> Создать
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтр по группам и типу параметра -->
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
                        <div class="col-md-3">
                            <select id="group-select2" class="form-control" data-toggle="select2" style="width: 100%;z-index:10 !important;">
                                <option value="all" selected>- Все параметры -</option>
                                <option value="check">- Проверенные -</option>
                                <option value="nocheck">- Непроверенные -</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-auto ms-3">
                            <label for="type-select2" class="form-label mb-0 fw-bold">
                                <i class="mdi mdi-filter-outline text-info"></i> Тип:
                            </label>
                        </div>
                        <div class="col-md-2">
                            <select id="additional-select2" class="form-control" data-toggle="select2" style="width: 100%;">
                                <option value="0" selected>Основные параметры</option>
                                <option value="1">Дополнительные параметры</option>
                            </select>
                        </div>
                        
                        <div class="col-md-auto">
                            <span class="text-muted small">
                                <i class="mdi mdi-magnify"></i> Фильтр по группе и типу
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
                                        data-url="{{ route('lm_comp_data') }}"
                                        data-references-url="{{ route('lm_comp_references') }}"
                                        data-update-url={{ route('lm_comp_update', ['id' => 'REPLACE_WITH_ID']) }}
                                        data-delete-url={{ route('lm_comp_destroy', ['id' => 'REPLACE_WITH_ID']) }}
                                        data-create-url="{{ route('lm_comp_create_data') }}"
                                        data-group-create-url="{{ route('lm_comp_group_create') }}"
                                        data-edit-url="{{ route('lm_comp_edit_data', ['id' => 'REPLACE_WITH_ID']) }}"
                                    >
                                        <thead>
                                            <tr>
                                                <th>Название</th>
                                                <th width="10%">Группа</th>
                                                <th width="1%">Файл</th>
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
    <div class="modal fade" id="editParamModal" tabindex="-1" role="dialog" aria-labelledby="editParamModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="editParamModalLabel">
                        <i class="mdi mdi-pencil-circle"></i> <span id="modalTitleText">Редактирование параметра</span>
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
                                                   readonly value="Комплектация">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="edit_param_additional" name="additional" value="1">
                                                <label class="form-check-label" for="edit_param_additional">
                                                    Дополнительная опция
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Блок групп -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="mdi mdi-folder-multiple"></i> Принадлежность к группам параметров</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info alert-dismissible fade show" role="alert">
                                    Эта кнопка <span class="badge bg-info"><i class="mdi mdi-content-copy copy-group-icon" style="cursor: pointer;"></i></span> копирует выбранную группу из первой строки во все остальные.
                                </div>
                                
                                <div id="group-links-container">
                                    <!-- Сюда будут добавляться строки с группами -->
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    {{--
                                    <button type="button" class="btn btn-outline-success" id="addGroupLinkBtn">
                                        <i class="mdi mdi-plus-circle me-1"></i> Добавить привязку к группе
                                    </button>
                                    --}}
                                    <button type="button" class="btn btn-outline-success" id="createNewGroupBtn">
                                        <i class="mdi mdi-plus-circle me-1"></i> Создать новую группу в справочнике
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Единицы измерения и значения -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="mdi mdi-ruler"></i> Единицы измерения и значения</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info alert-dismissible fade show" role="alert">
                                    Эта кнопка <span class="badge bg-info"><i class="mdi mdi-content-copy copy-value-icon" style="cursor: pointer;"></i></span> копирует единицу измерения и значение из первой строки во все остальные строки.
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table" id="values-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 30%">Единица измерения</th>
                                                <th style="width: 30%">Значение</th>
                                                <th style="width: 25%">Файл</th>
                                                <th style="width: 15%"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="values-container"></tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4">
                                                    <button type="button" class="btn btn-outline-success" id="addValueRow">
                                                        <i class="mdi mdi-plus-circle me-1"></i> Добавить значение
                                                    </button>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
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
                        <div class="form-check mb-0">
                            <input type="checkbox" class="form-check-input" id="edit_param_checked" name="checked" value="1">
                            <label class="form-check-label fw-normal" for="edit_param_checked">
                                Проверено
                            </label>
                        </div>
                        <button type="button" class="btn btn-success" id="saveParamBtn">
                            <i class="mdi mdi-content-save me-1"></i> Сохранить
                        </button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            <i class="mdi mdi-close-circle me-1"></i> Отмена
                        </button>
                    </div>
                </div>
                
                {{--
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="saveParamBtn">
                        <i class="mdi mdi-content-save me-1"></i> Сохранить изменения
                    </button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="mdi mdi-close-circle me-1"></i> Отмена
                    </button>
                </div>
                --}}
            </div>
        </div>
    </div>

    <!-- Модальное окно создания группы -->
    <div class="modal fade" id="createGroupModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="mdi mdi-folder-plus"></i> Создание группы</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_group_name" class="form-label">Название группы</label>
                        <input type="text" class="form-control" id="new_group_name" placeholder="Введите название новой группы">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="saveNewGroupBtn">Создать</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно создания единицы измерения -->
    <div class="modal fade" id="createUnitModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="mdi mdi-ruler-plus"></i> Создание единицы измерения</h5>
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

@section('head_other')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="/source/base/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <link href="/source/base/libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <link href="/source/base/libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <link href="/source/base/libs/datatables.net-select-bs5/css/select.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <link href="/source/base/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="/source/base/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <link href="/source/base/livemachines/tech/style.css?<?=time()?>" rel="stylesheet" type="text/css" />
@endsection

@section('page_more_java_script')
    <script src="/source/base/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="/source/base/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="/source/base/libs/select2/js/select2.min.js"></script>
    <script src="/source/base/libs/select2/js/i18n/ru.js"></script>
    <script src="/source/base/libs/sweetalert2/sweetalert2.min.js"></script>
    <script src="/source/base/livemachines/tech/script.js?<?=time()?>"></script>
@endsection