
$(document).ready(function() {

    const csrfToken      = $('meta[name="csrf-token"]').attr('content');
    const $table         = $('#basic-datatable');
    const dataUrl        = $table.data('url');
    const createUrl      = $table.data('create-url');
    const editUrl        = $table.data('edit-url').replace('REPLACE_WITH_ID', '');
    const updateUrl      = $table.data('update-url').replace('REPLACE_WITH_ID', '');
    const deleteUrl      = $table.data('delete-url').replace('REPLACE_WITH_ID', '');

    initDataTable();
    
    // ===== DATATABLE В РЕЖИМЕ SERVER-SIDE =====
    function initDataTable() {
        table = $('#basic-datatable').DataTable({
            scrollX: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: dataUrl,
                type: 'GET',
                data: function(d) {},
                error: (xhr, error, thrown) => {
                    console.error('DataTable error:', error, thrown);
                }
            },
            columns: [
                {
                    data:       'countryName',
                    name:       'countryName',
                    orderable:  true,
                    searchable: true,
                },
                { 
                    data:       'manufCount', 
                    name:       'manufs',
                    className:  'text-center',
                    orderable:  false,
                    searchable: false,
                    render:    (data) => data || '<span class="text-muted">-</span>' 
                },
                { 
                    data:       'fileCount',  
                    name:       'files',
                    className:  'text-center',
                    orderable:  false,
                    searchable: false,
                    render:    (data) => data || '<span class="text-muted">-</span>' 
                },
                {
                    data:       'countryId',
                    name:       'actions',
                    orderable:  false,
                    searchable: false,
                    render:     (data, type, row) => `
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-danger btn-sm btn-rounded edit-btn" 
                                    title="Редактировать" data-id="${data}" 
                                    data-name="${(row.countryName || '').replace(/'/g, "\\'")}">
                                <i class="mdi mdi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-primary btn-sm btn-rounded delete-btn" 
                                    title="Удалить" data-id="${data}" 
                                    data-name="${(row.countryName || '').replace(/'/g, "\\'")}">
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

    // ===== ЗАГРУЗКА ДАННЫХ ДЛЯ РЕДАКТИРОВАНИЯ =====
    function loadEditData(id, editUrl) {
        Swal.fire({ 
            title: 'Загрузка...', 
            html: 'Загружаем данные параметра', 
            allowOutsideClick: false, 
            didOpen: () => Swal.showLoading() 
        });

        // Меняем заголовок
        $('#modalTitleText').text('Редактирование');
        $('#editCountryModalLabel i').attr('class', 'mdi mdi-pencil-circle');
        
        $.ajax({
            url: editUrl + id,
            type: 'GET',
            success: (response) => {
                Swal.close();
                if (response.success) {
                    fillEditModal(response.data);
                    $('#editCountryModal').modal('show');
                } else {
                    Swal.fire({ title: 'Ошибка!', text: response.message, icon: 'error' });
                }
            },
            error: (xhr) => {
                Swal.close();
                let errorMessage = 'Произошла ошибка при получении данных';
                    
                // Пытаемся получить сообщение из ответа сервера
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMessage = response.message;
                        }
                    } catch(e) {
                        errorMessage = xhr.responseText;
                    }
                }
                
                Swal.fire({ 
                    title: 'Ошибка!', 
                    text: errorMessage, 
                    icon: 'error' 
                });
            }
        });
    }

    // ===== ЗАПОЛНЕНИЕ МОДАЛЬНОГО ОКНА =====
    function fillEditModal(data) {
        $('#edit_country_id').val(data.id);
        $('#edit_country_name').val(data.name);
    }

    // ===== СОЗДАНИЕ НОВОГО ПАРАМЕТРА =====
    $('#btnCreate').on('click', function(e) {
        e.preventDefault();
        
        // Меняем заголовок
        $('#modalTitleText').text('Создание');
        $('#editCountryModalLabel i').attr('class', 'mdi mdi-plus-circle');

        fillEditModal([id => 0, name => '']);
        
        // Используем специальный URL для создания с параметром new=true
        $('#editCountryModal').modal('show');
    });

    // ===== РЕДАКТИРОВАНИЕ =====
    $('#basic-datatable').on('click', '.edit-btn', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        loadEditData(id, editUrl);
    });

    // ===== СОХРАНЕНИЕ ИЗМЕНЕНИЙ =====
    $('#saveCountryBtn').on('click', function() {
        const formData = {
            '_token': csrfToken,
            'name': $('#edit_country_name').val()
        };
        
        if (!formData.name) {
            return Swal.fire({ 
                title: 'Ошибка!',
                text: 'Название параметра обязательно', 
                icon: 'error' 
            });
        }

        const id = $('#edit_country_id').val();

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
                    $('#editCountryModal').modal('hide');
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
                    console.log(response);
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
                        
                        Swal.fire({ 
                            title: 'Ошибка!', 
                            text:  response.message,
                            icon:  'error' 
                        });
                    }
                },
                error: (xhr) => {
                    $btn.html('<i class="mdi mdi-delete"></i>').prop('disabled', false);
                    
                    let errorMessage = 'Произошла ошибка при удалении';
                    
                    // Пытаемся получить сообщение из ответа сервера
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMessage = response.message;
                            }
                        } catch(e) {
                            errorMessage = xhr.responseText;
                        }
                    }
                    
                    Swal.fire({ 
                        title: 'Ошибка!', 
                        text: errorMessage, 
                        icon: 'error' 
                    });
                }
            });
        });
    });

});
