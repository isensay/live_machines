@extends('layouts.base')

@section('head_title', 'Справочник технических характеристик')

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
                    <!-- Таблица -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <table id="basic-datatable" 
                                        class="table dt-responsive nowrap w-100"
                                        data-url="{{ route('lm_tech.data') }}"
                                        data-references-url="{{ route('lm_tech.references') }}"
                                        data-update-url="/livemachines/sprav/tech/update/"
                                        data-delete-url="/livemachines/sprav/"
                                        data-edit-url="/livemachines/sprav/tech/edit/">
                                        <thead>
                                            <tr>
                                                <th>Название</th>
                                                <th width="10%">Группа</th>
                                                <th width="1%">Файл</th>
                                                <th width="1%"></th>
                                            </tr>
                                        </thead>
                                        <tbody>{{-- Данные будут загражены через AJAX --}}</tbody>
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
                                        <select id="edit_groups_select" class="form-control" multiple="multiple" style="width: 100%;" placeholder="aaaaa"></select>
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