$(document).ready(function() {

    // ===== ИНИЦИАЛИЗАЦИЯ =====
    let table;
    const references = { groups: [], units: [] };
    let referencesLoaded = false; // Флаг загрузки справочников
    
    // Получаем CSRF-токен из мета-тега
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    
    // Получаем таблицу и ее data-атрибуты
    const $table = $('#basic-datatable');
    const dataUrl = $table.data('url');
    const referencesUrl = $table.data('references-url');
    const updateUrl = $table.data('update-url');
    const deleteUrl = $table.data('delete-url');
    const editUrl = $table.data('edit-url');
    
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
            scrollX: true,
            processing: true,
            serverSide: false,
            ajax: {
                url: dataUrl, // Используем data-атрибут
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
                        <form id="delete-form-${data}" action="${deleteUrl}${data}" 
                              method="POST" style="display: none;">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <input type="hidden" name="_method" value="DELETE">
                        </form>
                    `
                }
            ],
            language: {
                processing: '<div style="zmargin:20px;" class="spinner-border text-success" role="status"></div>',
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
            drawCallback: () => { 
                $(".dataTables_paginate > .pagination").addClass("pagination-rounded"); 
            },
            columnDefs: [{ orderable: false, targets: -1 }],
            stateSave: true, 
            stateDuration: 0
        });
    }
    
    // ===== ЗАГРУЗКА СПРАВОЧНИКОВ =====
    function loadReferences() {
        $.ajax({
            url: referencesUrl, // Используем data-атрибут
            type: 'GET',
            success: (response) => {
                if (response.success) {
                    references.groups = response.data.groups;
                    references.units = response.data.units;
                    referencesLoaded = true;
                    
                    // Инициализируем Select2 для групп
                    initGroupSelect();
                }
            },
            error: (xhr) => console.error('Error loading references:', xhr)
        });
    }
    
    // ===== ИНИЦИАЛИЗАЦИЯ SELECT2 ДЛЯ ГРУПП =====
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
            icon: 'warning', 
            showCancelButton: true,
            confirmButtonColor: '#1abc9c', 
            cancelButtonColor: '#f1556c',
            confirmButtonText: 'Да, удалить!', 
            cancelButtonText: 'Отмена',
            reverseButtons: false
        }).then((result) => {
            if (!result.value) return;
            
            $btn.html('<span class="spinner-border spinner-border-sm"></span>').prop('disabled', true);
            
            $.ajax({
                url: deleteUrl + id,
                type: 'POST',
                data: { 
                    '_token': csrfToken, 
                    '_method': 'DELETE' 
                },
                success: (response) => {
                    if (response.success) {
                        $row.fadeOut(400, () => {
                            table.row($row).remove().draw(false);
                            if (table.page.info().recordsDisplay > 0 && 
                                table.page.info().recordsDisplay <= table.page.info().start) {
                                table.page('previous').draw(false);
                            }
                        });
                        Swal.fire({ 
                            title: 'Удалено!', 
                            text: response.message, 
                            icon: 'success', 
                            timer: 1500, 
                            showConfirmButton: false 
                        });
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
            url: editUrl + id,
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
                <td class="text-center"><button type="button" class="btn btn-sm btn-primary remove-value-row" title="Удалить"><i class="mdi mdi-delete"></i></button></td>
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
            '_token': csrfToken,
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
            
            if (unitId || value) {
                formData.values.push({ 
                    unit_id: unitId, 
                    value: value
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
            url: updateUrl + id,
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrfToken },
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