$(document).ready(function() {
    // Счетчик для новых строк
    let newRowCounter = 0;

    // ===== СОЗДАНИЕ НОВОГО ПАРАМЕТРА =====
    $('.page-title-right .btn-success').on('click', function(e) {
        e.preventDefault();
        
        const additionalValue = $('#additional-select2').val();
        
        if (!referencesLoaded) {
            Swal.fire({
                title: 'Загрузка...',
                html: 'Загружаем справочники, пожалуйста подождите',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            
            loadReferences();
            
            const checkInterval = setInterval(() => {
                if (referencesLoaded) {
                    clearInterval(checkInterval);
                    Swal.close();
                    loadCreateData(additionalValue);
                }
            }, 100);
        } else {
            loadCreateData(additionalValue);
        }
    });

    // ===== ЗАГРУЗКА ДАННЫХ ДЛЯ СОЗДАНИЯ =====
    function loadCreateData(additionalValue) {
        Swal.fire({ 
            title: 'Загрузка...', 
            html: 'Подготовка формы создания', 
            allowOutsideClick: false, 
            didOpen: () => Swal.showLoading() 
        });

        // Меняем заголовок
        $('#modalTitleText').text('Создание нового параметра');
        $('#editParamModalLabel i').attr('class', 'mdi mdi-plus-circle');
        
        // Используем специальный URL для создания с параметром new=true
        $.ajax({
            url: createUrl + '?additional=' + additionalValue + '&new=true',
            type: 'GET',
            success: (response) => {
                Swal.close();
                if (response.success) {
                    fillCreateModal(response.data);
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

    // ===== ЗАПОЛНЕНИЕ МОДАЛЬНОГО ОКНА ДЛЯ СОЗДАНИЯ =====
    function fillCreateModal(data) {
        // Очищаем ID параметра (будет создан новый)
        $('#edit_param_id').val('');
        $('#edit_param_name').val('');
        
        // Устанавливаем чекбоксы по умолчанию
        $('#edit_param_additional').prop('checked', data.additional == 1);
        $('#edit_param_checked').prop('checked', false);
        
        // Сбрасываем счетчик новых строк
        newRowCounter = 0;
        
        // Очищаем контейнеры
        $('#group-links-container').empty();
        $('#values-container').empty();
        
        // Добавляем одну пустую строку для значения (она создаст и строку группы)
        addValueRow({});
    }

    // ===== КНОПКИ КОПИРОВАНИЯ В ТЕКСТОВЫХ ОПИСАНИЯХ =====
    $(document).on('click', '.copy-group-icon', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        $('.btn-close').blur();
        
        if (document.activeElement && document.activeElement.blur) {
            document.activeElement.blur();
        }
        
        setTimeout(function() {
            applyToAllGroups();
        }, 20);
        
        return false;
    });

    $(document).on('click', '.copy-value-icon', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        $('.btn-close').blur();
        
        if (document.activeElement && document.activeElement.blur) {
            document.activeElement.blur();
        }
        
        setTimeout(function() {
            applyToAllValues();
        }, 20);
        
        return false;
    });

    // ===== ИНИЦИАЛИЗАЦИЯ =====
    let table;
    const references     = { groups: [], units: [], files: [] };
    let referencesLoaded = false;
    
    const csrfToken      = $('meta[name="csrf-token"]').attr('content');
    
    const $table         = $('#basic-datatable');
    const dataUrl        = $table.data('url');

    const referencesUrl  = $table.data('references-url');
    const createUrl      = $table.data('create-url');
    const editUrl        = $table.data('edit-url');
    const updateUrl      = $table.data('update-url').replace('REPLACE_WITH_ID', '');
    const deleteUrl      = $table.data('delete-url').replace('REPLACE_WITH_ID', '');

    const groupCreateUrl = $table.data('group-create-url');

    //replace('REPLACE_WITH_ID', id)
    initSelect2();
    initDataTable();
    loadReferences();
    
    // ===== SELECT2 ДЛЯ ФИЛЬТРОВ =====
    function initSelect2() {
        $('#group-select2').select2({minimumInputLength: 0, language: 'ru'});
        $('#additional-select2').select2({ minimumInputLength: 0, language: 'ru' });
        $('#group-select2, #additional-select2').on('select2:open', function() {
            $('.select2-dropdown').css('z-index', '10');
        });
    }
    
    // ===== DATATABLE В РЕЖИМЕ SERVER-SIDE =====
    function initDataTable() {
        table = $('#basic-datatable').DataTable({
            scrollX: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: dataUrl,
                type: 'GET',
                data: function(d) {
                    d.group_id = $('#group-select2').val();
                    d.additional = $('#additional-select2').val();
                },
                error: (xhr, error, thrown) => {
                    console.error('DataTable error:', error, thrown);
                }
            },
            columns: [
                { data: 'paramName', name: 'paramName' },
                { 
                    data: 'groups', 
                    name: 'groups',
                    render: (data) => data || '<span class="text-muted">-</span>' 
                },
                { 
                    data: 'files',  
                    name: 'files',
                    render: (data) => data || '<span class="text-muted">-</span>' 
                },
                {
                    data: 'paramNameId',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
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
                processing: '<div style="margin:20px;" class="spinner-border text-success" role="status"></div>',
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
            url: referencesUrl,
            type: 'GET',
            success: (response) => {
                if (response.success) {
                    references.groups = response.data.groups || [];
                    references.units = response.data.units || [];
                    references.files = response.data.files || [];
                    referencesLoaded = true;
                }
            },
            error: (xhr) => console.error('Error loading references:', xhr)
        });
    }
    
    // ===== ОБРАБОТЧИКИ СОБЫТИЙ =====
    $('#group-select2').on('change', function() {
        table.search('').draw();
        $('div.dataTables_filter input').val('');
        table.ajax.reload(null, true);
    });
    
    $('#additional-select2').on('change', function() {
        table.search('').draw();
        $('div.dataTables_filter input').val('');
        table.ajax.reload(null, true);
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
                        table.row($row).remove().draw(false);
                        
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
        
        // Формируем URL здесь, где id определен
        const editUrl = $table.data('edit-url').replace('REPLACE_WITH_ID', id);
        
        const additionalValue = $('#additional-select2').val();
        
        if (!referencesLoaded) {
            Swal.fire({
                title: 'Загрузка...',
                html: 'Загружаем справочники, пожалуйста подождите',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            
            loadReferences();
            
            const checkInterval = setInterval(() => {
                if (referencesLoaded) {
                    clearInterval(checkInterval);
                    Swal.close();
                    loadEditData(id, additionalValue, editUrl); // Передаем editUrl
                }
            }, 100);
        } else {
            loadEditData(id, additionalValue, editUrl); // Передаем editUrl
        }
    });

    // ===== ЗАГРУЗКА ДАННЫХ ДЛЯ РЕДАКТИРОВАНИЯ =====
    function loadEditData(id, additionalValue, editUrl) {
        Swal.fire({ 
            title: 'Загрузка...', 
            html: 'Загружаем данные параметра', 
            allowOutsideClick: false, 
            didOpen: () => Swal.showLoading() 
        });

        // Меняем заголовок
        $('#modalTitleText').text('Редактирование параметра');
        $('#editParamModalLabel i').attr('class', 'mdi mdi-pencil-circle');
        
        $.ajax({
            url: editUrl + '?additional=' + additionalValue, // Используем переданный editUrl
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
        
        $('#edit_param_additional').prop('checked', data.additional == 1);
        $('#edit_param_checked').prop('checked', data.checked == 1);
        
        // Для новых записей используем все файлы, для существующих - все файлы
        // window.currentParamFiles = data.param_files || []; // Больше не используем фильтрацию
        
        // Сбрасываем счетчик новых строк
        newRowCounter = 0;
        
        // Заполняем привязки к группам
        $('#group-links-container').empty();
        if (data.group_links && data.group_links.length > 0) {
            data.group_links.forEach((link, index) => {
                // Для существующих записей используем реальный ID из БД (dirty_param_id)
                const rowId = 'param_' + link.param_id; // Используем param_id из ответа
                addGroupLinkRow(link, index, rowId, true);
            });
        }

        // Заполняем значения
        $('#values-container').empty();
        if (data.values && data.values.length > 0) {
            data.values.forEach((value, index) => {
                // Для существующих записей используем реальный ID из БД (dirty_param_id)
                const rowId = 'param_' + value.param_id; // Используем param_id из ответа
                addValueRow(value, index, rowId, true);
            });
        }
    }

    // ===== ДОБАВЛЕНИЕ СТРОКИ С ГРУППОЙ =====
    function addGroupLinkRow(link = {}, index, rowId = null, fromExisting = false) {
        // Если rowId не передан, генерируем новый для связи
        if (!rowId) {
            newRowCounter++;
            rowId = 'new_param_' + newRowCounter;
        }
        
        const isExisting = link.file_id ? true : false;
        const groupIdValue = link.group_id || '0';
        
        let fileFieldHtml = '';
        if (isExisting) {
            fileFieldHtml = `
                <div class="form-control-plaintext file-name-display">
                    ${link.file_name ? '<i class="mdi mdi-file-outline text-info me-1"></i>' + link.file_name : '—'}
                </div>
                <input type="hidden" class="file-id-input" name="group_links[][file_id]" value="${link.file_id || ''}">
                <input type="hidden" class="group-id-input" name="group_links[][group_id]" value="${groupIdValue}">
            `;
        } else {
            fileFieldHtml = `
                <select class="form-control group-file-select" style="width: 100%;" name="group_links[][file_id]">
                    <option value="0">Без файла</option>
                </select>
            `;
        }
        
        const rowHtml = `
        <div class="row mb-2 group-link-row align-items-center" data-row-index="${index}" 
             data-existing="${isExisting}" data-row-id="${rowId}" data-type="group">
            <div class="col-md-6">
                <select class="form-control group-select" style="width: 100%;" name="group_links[][group_id]">
                    <option value="">Выберите группу</option>
                </select>
            </div>
            <div class="col-md-4">
                ${fileFieldHtml}
            </div>
            <div class="col-md-2 text-end">
                <div class="d-flex justify-content-end gap-1">
                    <button type="button" class="btn btn-sm btn-primary remove-group-link" title="Удалить" ${fromExisting ? 'disabled' : ''}>
                        <i class="mdi mdi-delete"></i>
                    </button>
                </div>
            </div>
        </div>
        `;
        
        $('#group-links-container').append(rowHtml);
        const $newRow = $('#group-links-container .group-link-row:last');
        
        // Инициализируем Select2 для группы
        const $groupSelect = $newRow.find('.group-select');
        references.groups.forEach(group => {
            $groupSelect.append(new Option(group.name, group.id, false, link.group_id == group.id));
        });
        
        $groupSelect.select2({
            dropdownParent: $('#editParamModal'),
            placeholder: 'Выберите группу',
            language: 'ru',
            width: '100%',
            minimumResultsForSearch: 0,
            templateResult: (data) => {
                if (data.loading || !data.id) return data.text;
                return $('<span><i class="mdi mdi-folder-outline text-success me-1"></i>' + data.text + '</span>');
            }
        });
        
        if (link.group_id && link.group_id != '0' && link.group_id != '') {
            $groupSelect.val(link.group_id).trigger('change');
        }
        
        // Если это новая строка, инициализируем select для файла
        if (!isExisting) {
            const $fileSelect = $newRow.find('.group-file-select');
            
            // Добавляем все файлы из справочника
            references.files.forEach(file => {
                const selected = (link.file_id == file.id) ? 'selected' : '';
                $fileSelect.append(`<option value="${file.id}" ${selected}>${file.name}</option>`);
            });
            
            $fileSelect.select2({
                dropdownParent: $('#editParamModal'),
                placeholder: 'Выберите файл',
                language: 'ru',
                width: '100%',
                minimumResultsForSearch: 0,
                templateResult: (data) => {
                    if (data.loading || !data.id) return data.text;
                    if (data.id == '0') {
                        return $('<span><i class="mdi mdi-file-remove-outline text-secondary me-1"></i>' + data.text + '</span>');
                    }
                    return $('<span><i class="mdi mdi-file-outline text-info me-1"></i>' + data.text + '</span>');
                }
            });
            
            // При изменении файла обновляем связанную строку в значениях
            $fileSelect.on('change', function() {
                const selectedFileId = $(this).val();
                const selectedFileName = $(this).find('option:selected').text();
                
                // Ищем связанную строку в значениях по rowId
                const $linkedValueRow = $(`#values-container .value-row[data-row-id="${rowId}"]`);
                
                if ($linkedValueRow.length) {
                    // Обновляем файл в связанной строке
                    const $valueFileSelect = $linkedValueRow.find('.file-select');
                    if ($valueFileSelect.length) {
                        $valueFileSelect.val(selectedFileId).trigger('change');
                    } else {
                        // Для существующих записей обновляем скрытое поле
                        $linkedValueRow.find('.file-id-input').val(selectedFileId);
                        $linkedValueRow.find('.file-name-display').html(
                            `<i class="mdi mdi-file-outline text-info me-1"></i>${selectedFileName}`
                        );
                    }
                } else if (selectedFileId && selectedFileId != '0') {
                    // Если нет связанной строки, создаем новую в значениях
                    addValueRow({ file_id: selectedFileId, file_name: selectedFileName }, 
                               $('#values-container .value-row').length, 
                               rowId, 
                               false);
                }
            });
        }
        
        return $newRow;
    }

    // ===== ДОБАВЛЕНИЕ СТРОКИ ЗНАЧЕНИЯ =====
    function addValueRow(value = {}, index, rowId = null, fromExisting = false) {
        // Если rowId не передан, генерируем новый для связи
        if (!rowId) {
            newRowCounter++;
            rowId = 'new_param_' + newRowCounter;
        }
        
        const isExisting = (value.value_id || value.file_id) ? true : false;
        const valueIdAttr = value.value_id ? `data-value-id="${value.value_id}"` : '';
        
        let fileFieldHtml = '';
        if (isExisting) {
            fileFieldHtml = `
                <div class="form-control-plaintext file-name-display" style="padding-top: 7px;">
                    ${value.file_name ? '<i class="mdi mdi-file-outline text-info me-1"></i>' + value.file_name : '—'}
                </div>
                <input type="hidden" class="file-id-input" name="values[][file_id]" value="${value.file_id || ''}">
            `;
        } else {
            fileFieldHtml = `
                <select class="form-control file-select" style="width: 100%;" name="values[][file_id]">
                    <option value="0">Без файла</option>
                </select>
            `;
        }
        
        const rowHtml = `
        <tr class="value-row" data-existing="${isExisting}" ${valueIdAttr} 
            data-row-id="${rowId}" data-type="value">
            <td style="width: 30%;">
                <select class="form-control unit-select" style="width: 100%;" name="values[][unit_id]"></select>
            </td>
            <td style="width: 30%;">
                <input type="text" class="form-control value-input" name="values[][value]" 
                    value="${value.value || ''}" placeholder="Значение">
            </td>
            <td style="width: 30%;">
                ${fileFieldHtml}
            </td>
            <td style="width: 10%;" class="text-end pe-3">
                <button type="button" class="btn btn-sm btn-primary remove-value-row" title="Удалить" ${fromExisting ? '' : ''}>
                    <i class="mdi mdi-delete"></i>
                </button>
            </td>
        </tr>
        `;
        
        $('#values-container').append(rowHtml);
        const $newRow = $('#values-container tr:last');
        
        // Инициализируем Select2 для единицы измерения
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
        
        if (value.unit_id) {
            $unitSelect.val(value.unit_id).trigger('change');
        }
        
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
        
        // Если это новая строка, инициализируем select для файла
        if (!isExisting) {
            const $fileSelect = $newRow.find('.file-select');
            
            $fileSelect.append('<option value="0">Без файла</option>');
            
            // Добавляем все файлы из справочника
            references.files.forEach(file => {
                const selected = (value.file_id == file.id) ? 'selected' : '';
                $fileSelect.append(`<option value="${file.id}" ${selected}>${file.name}</option>`);
            });
            
            $fileSelect.select2({
                dropdownParent: $('#editParamModal'),
                placeholder: 'Выберите файл',
                language: 'ru',
                width: '100%',
                minimumResultsForSearch: 0,
                templateResult: (data) => {
                    if (data.loading || !data.id) return data.text;
                    if (data.id == '0') {
                        return $('<span><i class="mdi mdi-file-remove-outline text-secondary me-1"></i>' + data.text + '</span>');
                    }
                    return $('<span><i class="mdi mdi-file-outline text-info me-1"></i>' + data.text + '</span>');
                }
            });
            
            // При выборе файла создаем или обновляем связанную строку в группах
            $fileSelect.on('change', function() {
                const selectedFileId = $(this).val();
                const selectedFileName = $(this).find('option:selected').text();
                
                if (selectedFileId && selectedFileId != '0') {
                    // Ищем связанную строку в группах по rowId
                    let $linkedGroupRow = $(`#group-links-container .group-link-row[data-row-id="${rowId}"]`);
                    
                    if (!$linkedGroupRow.length) {
                        // Создаем новую строку в группах с тем же rowId
                        $linkedGroupRow = addGroupLinkRow(
                            { file_id: selectedFileId, file_name: selectedFileName }, 
                            $('#group-links-container .group-link-row').length, 
                            rowId, 
                            false
                        );
                    } else {
                        // Обновляем существующую строку
                        const $groupFileSelect = $linkedGroupRow.find('.group-file-select');
                        if ($groupFileSelect.length) {
                            $groupFileSelect.val(selectedFileId).trigger('change');
                        } else {
                            // Для существующих записей обновляем скрытое поле
                            $linkedGroupRow.find('.file-id-input').val(selectedFileId);
                            $linkedGroupRow.find('.file-name-display').html(
                                `<i class="mdi mdi-file-outline text-info me-1"></i>${selectedFileName}`
                            );
                        }
                    }
                }
            });
            
            if (value.file_id) {
                $fileSelect.val(value.file_id).trigger('change');
            }
        }
        
        return $newRow;
    }
    
    // ===== ОБРАБОТЧИК УДАЛЕНИЯ СТРОК ЗНАЧЕНИЙ =====
    $('#values-container').on('click', '.remove-value-row:not(:disabled)', function() {
        const $row = $(this).closest('tr');
        const rowId = $row.data('row-id');
        
        // Удаляем связанную строку в группах
        if (rowId) {
            $(`#group-links-container .group-link-row[data-row-id="${rowId}"]`).remove();
        }
        
        $row.remove();
    });
    
    // ===== ОБРАБОТЧИК УДАЛЕНИЯ СТРОК ГРУПП =====
    $('#group-links-container').on('click', '.remove-group-link:not(:disabled)', function() {
        const $row = $(this).closest('.group-link-row');
        const rowId = $row.data('row-id');
        
        // Удаляем связанную строку в значениях
        if (rowId) {
            $(`#values-container .value-row[data-row-id="${rowId}"]`).remove();
        }
        
        $row.remove();
    });
    
    // ===== ПРИМЕНИТЬ КО ВСЕМ ГРУППАМ =====
    function applyToAllGroups() {
        const $firstRow = $('#group-links-container .group-link-row:first');
        if ($firstRow.length === 0) return;
        
        const firstGroupValue = $firstRow.find('.group-select').val();
        
        //if (!firstGroupValue) {
        //    Swal.fire({
        //        title: 'Внимание!',
        //        text: 'В первой строке не выбрана группа',
        //        icon: 'warning',
        //        timer: 1500
        //    });
        //    return;
        //}
        
        let appliedCount = 0;
        $('#group-links-container .group-link-row:not(:first)').each(function() {
            const $row = $(this);
            const $groupSelect = $row.find('.group-select');
            
            $groupSelect.val(firstGroupValue).trigger('change');
            appliedCount++;
        });
        
        //Swal.fire({
        //    title: 'Готово!',
        //    text: `Значения применены к ${appliedCount} группам`,
        //    icon: 'success',
        //    timer: 1500,
        //    showConfirmButton: false
        //});
    }
    
    // ===== ПРИМЕНИТЬ КО ВСЕМ ЗНАЧЕНИЯМ =====
    function applyToAllValues() {
        const $firstRow = $('#values-container .value-row:first');
        if ($firstRow.length === 0) return;
        
        const firstUnitValue = $firstRow.find('.unit-select').val();
        const firstValueText = $firstRow.find('.value-input').val();
        
        //if (!firstUnitValue) {
        //    Swal.fire({
        //        title: 'Внимание!',
        //        text: 'В первой строке не выбрана единица измерения',
        //        icon: 'warning',
        //        timer: 1500
        //    });
        //    return;
        //}
        
        //if (!firstValueText) {
        //    Swal.fire({
        //        title: 'Внимание!',
        //        text: 'В первой строке не заполнено значение',
        //        icon: 'warning',
        //        timer: 1500
        //    });
        //    return;
        //}
        
        let appliedCount = 0;
        $('#values-container .value-row:not(:first)').each(function() {
            const $row = $(this);
            const $unitSelect = $row.find('.unit-select');
            const $valueInput = $row.find('.value-input');
            
            $unitSelect.val(firstUnitValue).trigger('change');
            $valueInput.val(firstValueText);
            
            appliedCount++;
        });
        
        //Swal.fire({
        //    title: 'Готово!',
        //    text: `Значения применены к ${appliedCount} строкам`,
        //    icon: 'success',
        //    timer: 1500,
        //    showConfirmButton: false
        //});
    }
    
    // ===== ОБНОВЛЕНИЕ ВСЕХ SELECT2 ДЛЯ ГРУПП =====
    function refreshAllGroupSelects() {
        $('.group-select').each(function() {
            const $this = $(this);
            const currentVal = $this.val();
            
            if ($this.data('select2')) {
                $this.select2('destroy');
            }
            
            $this.empty().append('<option value="">Выберите группу</option>');
            references.groups.forEach(group => {
                $this.append(new Option(group.name, group.id, false, currentVal == group.id));
            });
            
            $this.select2({
                dropdownParent: $('#editParamModal'),
                placeholder: 'Выберите группу',
                language: 'ru',
                width: '100%',
                templateResult: (data) => {
                    if (data.loading || !data.id) return data.text;
                    return $('<span><i class="mdi mdi-folder-outline text-success me-1"></i>' + data.text + '</span>');
                }
            });
        });
    }
    
    // ===== ОБНОВЛЕНИЕ ВСЕХ SELECT2 ДЛЯ ФАЙЛОВ В ГРУППАХ =====
    function refreshAllGroupFileSelects() {
        $('.group-file-select').each(function() {
            const $this = $(this);
            const currentVal = $this.val();
            
            if ($this.data('select2')) {
                $this.select2('destroy');
            }
            
            $this.empty().append('<option value="0">Без файла</option>');
            references.files.forEach(file => {
                $this.append(`<option value="${file.id}">${file.name}</option>`);
            });
            
            $this.select2({
                dropdownParent: $('#editParamModal'),
                placeholder: 'Выберите файл',
                language: 'ru',
                width: '100%',
                templateResult: (data) => {
                    if (data.loading || !data.id) return data.text;
                    if (data.id == '0') {
                        return $('<span><i class="mdi mdi-file-remove-outline text-secondary me-1"></i>' + data.text + '</span>');
                    }
                    return $('<span><i class="mdi mdi-file-outline text-info me-1"></i>' + data.text + '</span>');
                }
            });
            
            if (currentVal) $this.val(currentVal).trigger('change');
        });
    }
    
    // ===== ОБНОВЛЕНИЕ ВСЕХ SELECT2 ДЛЯ ФАЙЛОВ В ЗНАЧЕНИЯХ =====
    function refreshAllFileSelects() {
        $('.file-select').each(function() {
            const $this = $(this);
            
            if ($this.prop('disabled')) {
                return;
            }
            
            const currentVal = $this.val();
            
            if ($this.data('select2')) {
                $this.select2('destroy');
            }
            
            $this.empty().append('<option value="0">Без файла</option>');
            references.files.forEach(file => {
                $this.append(`<option value="${file.id}">${file.name}</option>`);
            });
            
            $this.select2({
                dropdownParent: $('#editParamModal'),
                placeholder: 'Выберите файл',
                language: 'ru',
                width: '100%',
                templateResult: (data) => {
                    if (data.loading || !data.id) return data.text;
                    if (data.id == '0') {
                        return $('<span><i class="mdi mdi-file-remove-outline text-secondary me-1"></i>' + data.text + '</span>');
                    }
                    return $('<span><i class="mdi mdi-file-outline text-info me-1"></i>' + data.text + '</span>');
                }
            });
            
            if (currentVal) $this.val(currentVal).trigger('change');
        });
    }
    
    // ===== ОБНОВЛЕНИЕ ВСЕХ SELECT2 ДЛЯ ЕДИНИЦ ИЗМЕРЕНИЯ =====
    function refreshAllUnitSelects() {
        $('.unit-select').each(function() {
            const $this = $(this);
            const currentVal = $this.val();
            
            if ($this.data('select2')) {
                $this.select2('destroy');
            }
            
            $this.empty();
            references.units.forEach(unit => {
                $this.append(`<option value="${unit.id}" data-type="${unit.type}">${unit.name}</option>`);
            });
            
            $this.select2({
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
            
            if (currentVal) $this.val(currentVal).trigger('change');
        });
    }
    
    // ===== СОБЫТИЯ ДЛЯ ГРУПП =====
    $('#addGroupLinkBtn').on('click', function() {
        Swal.fire({
            title: 'Внимание!',
            text: 'Строки групп создаются автоматически при выборе файла в блоке значений',
            icon: 'info',
            timer: 2000
        });
    });
    
    // ===== СОБЫТИЯ ДЛЯ ЗНАЧЕНИЙ =====
    $('#addValueRow').on('click', function() {
        addValueRow({});
    });
    
    // ===== КНОПКИ КОПИРОВАНИЯ =====
    $(document).on('click', '.copy-group-icon', function(e) {
        e.preventDefault();
        e.stopPropagation();
        applyToAllGroups();
        return false;
    });
    
    $(document).on('click', '.copy-value-icon', function(e) {
        e.preventDefault();
        e.stopPropagation();
        applyToAllValues();
        return false;
    });
    
    // ===== СОЗДАНИЕ ГРУППЫ =====
    $('#createNewGroupBtn').on('click', function() {
        $('#new_group_name').val('');
        $('#createGroupModal').modal('show');
    });

    $('#saveNewGroupBtn').on('click', function() {
        const groupName = $('#new_group_name').val().trim();
        if (!groupName) {
            return Swal.fire({ 
                title: 'Ошибка!', 
                text: 'Введите название группы', 
                icon: 'error' 
            });
        }
        
        const $btn = $(this);
        const originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm"></span>').prop('disabled', true);
        
        $.ajax({
            url: groupCreateUrl,
            type: 'POST',
            data: {
                '_token': csrfToken,
                'name': groupName
            },
            success: (response) => {
                if (response.success) {
                    references.groups.push(response.group);
                    
                    refreshAllGroupSelects();
                    refreshAllGroupFileSelects();
                    
                    $('#createGroupModal').modal('hide');
                    Swal.fire({
                        title: 'Создано!',
                        text: `Группа "${response.group.name}" создана и добавлена в справочник`,
                        icon: 'success',
                        timer: 1500
                    });
                } else {
                    Swal.fire({
                        title: 'Ошибка!',
                        text: response.message,
                        icon: 'error'
                    });
                }
            },
            error: (xhr) => {
                let errorMsg = 'Ошибка при создании группы';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                Swal.fire({
                    title: 'Ошибка!',
                    text: errorMsg,
                    icon: 'error'
                });
            },
            complete: () => {
                $btn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // ===== СОЗДАНИЕ ЕДИНИЦЫ ИЗМЕРЕНИЯ =====
    $('#saveNewUnitBtn').on('click', function() {
        const unitName = $('#new_unit_name').val().trim();
        const unitType = $('#new_unit_type').val();
        
        if (!unitName) {
            return Swal.fire({ title: 'Ошибка!', text: 'Введите название единицы измерения', icon: 'error' });
        }
        
        const $btn = $(this);
        const originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm"></span>').prop('disabled', true);
        
        setTimeout(() => {
            const newId = references.units.length + 1;
            references.units.push({ id: newId, name: unitName, type: unitType });
            
            refreshAllUnitSelects();
            
            $('#createUnitModal').modal('hide');
            Swal.fire({ 
                title: 'Создано!', 
                text: `Единица "${unitName}" создана`, 
                icon: 'success', 
                timer: 1500 
            });
            
            $btn.html(originalText).prop('disabled', false);
        }, 500);
    });

    // ===== СОХРАНЕНИЕ ИЗМЕНЕНИЙ =====
    $('#saveParamBtn').on('click', function() {
        const formData = {
            '_token': csrfToken,
            'name': $('#edit_param_name').val(),
            'additional': $('#edit_param_additional').is(':checked') ? 1 : 0,
            'checked': $('#edit_param_checked').is(':checked') ? 1 : 0,
            'additional_filter': $('#additional-select2').val(),
            'group_links': [],
            'values': []
        };
        
        if (!formData.name) {
            return Swal.fire({ 
                title: 'Ошибка!', 
                text: 'Название параметра обязательно', 
                icon: 'error' 
            });
        }

        //console.log('=== НАЧАЛО СБОРА ДАННЫХ ===');
        //console.log('Всего строк групп:', $('.group-link-row').length);

        // Собираем все привязки к группам с param_id
        $('.group-link-row').each(function(index) {
            const $row = $(this);
            
            let groupId, fileId, paramId;
            
            // Получаем rowId для определения типа записи
            const rowId = $row.data('row-id');
            
            // Определяем param_id
            if (rowId && rowId.startsWith('new_param_')) {
                // Новая запись - используем new_param_X
                paramId = rowId;
                //console.log(`Строка групп ${index}: НОВАЯ запись, param_id =`, paramId);
            } else if (rowId && rowId.startsWith('param_')) {
                // Существующая запись - используем param_X (где X - dirty_param_id)
                paramId = rowId;
                //console.log(`Строка групп ${index}: СУЩЕСТВУЮЩАЯ запись, param_id =`, paramId);
            } else {
                // Fallback - используем ID параметра
                paramId = 'param_' + $('#edit_param_id').val();
                //console.log(`Строка групп ${index}: FALLBACK, param_id =`, paramId);
            }
            
            // Определяем group_id - ТОЛЬКО из select (актуальное значение)
            const $groupSelect = $row.find('.group-select');
            if ($groupSelect.length) {
                groupId = $groupSelect.val();
                if (groupId === null || groupId === '') {
                    groupId = '0';
                }
            } else {
                groupId = $row.find('.group-id-input').val() || '0';
            }
            
            // Определяем file_id
            const $fileSelect = $row.find('.group-file-select');
            if ($fileSelect.length) {
                fileId = $fileSelect.val();
                if (fileId === null) fileId = '0';
            } else {
                fileId = $row.find('.file-id-input').val();
            }
            
            // Добавляем в массив только если fileId определен и не равен '0'
            if (fileId !== undefined && fileId !== '0') {
                formData.group_links.push({ 
                    param_id: paramId,
                    group_id: groupId,
                    file_id: fileId
                });
                //console.log(`Строка групп ${index}: ДОБАВЛЕНО →`, { param_id: paramId, group_id: groupId, file_id: fileId });
            }
        });

        //('ИТОГОВЫЙ group_links:', formData.group_links);
        
        // Собираем ВСЕ значения с param_id
        $('.value-row').each(function(index) {
            const $row = $(this);
            const isExisting = $row.data('existing') === true;
            
            let unitId, fileId, value, paramId, valueId;
            
            // Получаем rowId для определения типа записи
            const rowId = $row.data('row-id');
            
            // Определяем param_id
            if (rowId && rowId.startsWith('new_param_')) {
                // Новая запись - используем new_param_X
                paramId = rowId;
            } else if (rowId && rowId.startsWith('param_')) {
                // Существующая запись - используем param_X (где X - dirty_param_id)
                paramId = rowId;
            } else {
                // Fallback - используем ID параметра
                paramId = 'param_' + $('#edit_param_id').val();
            }
            
            // Получаем value_id если есть
            valueId = $row.data('value-id');
            
            if (isExisting) {
                unitId = $row.find('.unit-select').val();
                fileId = $row.find('.file-id-input').val();
                value = $row.find('.value-input').val().trim();
                
                formData.values.push({
                    param_id: paramId,
                    unit_id: unitId || 0,
                    file_id: fileId || 0,
                    value: value,
                    is_existing: true,
                    value_id: valueId
                });
                //console.log(`Строка значений ${index}: ДОБАВЛЕНО существующая →`, { 
                //    param_id: paramId, 
                //    unit_id: unitId || 0, 
                //    file_id: fileId || 0, 
                //    value: value,
                //    value_id: valueId
                //});
            } else {
                unitId = $row.find('.unit-select').val();
                fileId = $row.find('.file-select').val();
                value = $row.find('.value-input').val().trim();
                
                if (value) {
                    formData.values.push({
                        param_id: paramId,
                        unit_id: unitId || 0,
                        file_id: fileId || 0,
                        value: value,
                        is_existing: false
                    });
                    //console.log(`Строка значений ${index}: ДОБАВЛЕНО новая →`, { 
                    //    param_id: paramId, 
                    //    unit_id: unitId || 0, 
                    //    file_id: fileId || 0, 
                    //    value: value 
                    //});
                }
            }
        });
        
        //console.log('ИТОГОВЫЙ values:', formData.values);
        
        const id = $('#edit_param_id').val();

        // Определяем URL для сохранения (создание или обновление)
        let saveUrl;
        if (!id) {
            // Новый параметр
            saveUrl = createUrl;
        } else {
            // Существующий параметр
            saveUrl = updateUrl + id;
        }

        Swal.fire({ 
            title: 'Сохранение...', 
            allowOutsideClick: false, 
            didOpen: () => Swal.showLoading() 
        });
        
        $.ajax({
            url: saveUrl,
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

    // ===== ВСПОМОГАТЕЛЬНАЯ ФУНКЦИЯ ДЛЯ КОПИРОВАНИЯ =====
    function copyToClipboard(text, message) {
        // Используем современный API clipboard
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                // Показываем всплывающее уведомление (опционально)
                showCopyNotification(message);
            }).catch(err => {
                console.error('Ошибка копирования: ', err);
                fallbackCopyToClipboard(text, message);
            });
        } else {
            fallbackCopyToClipboard(text, message);
        }
    }

    // ===== ЗАПАСНОЙ МЕТОД ДЛЯ СТАРЫХ БРАУЗЕРОВ =====
    function fallbackCopyToClipboard(text, message) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        
        try {
            document.execCommand('copy');
            showCopyNotification(message);
        } catch (err) {
            console.error('Ошибка копирования (fallback): ', err);
            // Если даже fallback не сработал, показываем сообщение с текстом для ручного копирования
            Swal.fire({
                title: 'Не удалось скопировать',
                text: text,
                icon: 'info',
                timer: 3000,
                showConfirmButton: true
            });
        }
        
        document.body.removeChild(textarea);
    }

    // ===== УВЕДОМЛЕНИЕ О КОПИРОВАНИИ =====
    function showCopyNotification(message) {
        // Проверяем, определен ли Toast (из SweetAlert2)
        if (typeof Toast !== 'undefined') {
            Toast.fire({
                icon: 'success',
                title: message,
                timer: 1500
            });
        } else {
            // Если Toast не определен, используем стандартный Swal
            Swal.fire({
                title: message,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }
    }

    // ===== УЛУЧШЕННЫЙ TOAST С ПЛАВНЫМИ АНИМАЦИЯМИ =====
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1200, // Немного уменьшил время
        timerProgressBar: false, // Убрал прогресс-бар для минимализма
        customClass: {
            popup: 'animated-toast compact-toast'
        },
        didOpen: (toast) => {
            // Добавляем плавное появление
            toast.style.animation = 'slideInRight 0.3s ease';
            
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        },
        willClose: (toast) => {
            // Добавляем плавное исчезновение
            toast.style.animation = 'fadeOut 0.2s ease';
        }
    });

    // ===== КОПИРОВАНИЕ ЗНАЧЕНИЯ ГРУППЫ ПРИ ДВОЙНОМ КЛИКЕ =====
    $(document).on('dblclick', '.group-select + .select2-container .select2-selection__rendered', function() {
        // Получаем текст из элемента
        const groupText = $(this).text().trim();
        
        // Игнорируем плейсхолдер
        if (groupText === 'Выберите группу' || groupText === '') {
            return;
        }
        
        // Копируем в буфер обмена
        copyToClipboard(groupText, 'Группа скопирована в буфер обмена');
    });

    
});