function createStickyHeader111() {
    var $originalHeader = $('.page-title-box.sticky').first();
    var $window = $(window);
    var $contentPage = $('.content-page');
    var headerTop = $originalHeader.offset().top;
    
    $('.sticky-header-clone').remove();
    
    // Функция проверки режима
    function isFixedMode() {
        return $('body').attr('data-layout-position') !== 'scrollable' && 
               $('html').attr('data-layout-position') !== 'scrollable';
    }
    
    var $clone = $originalHeader.clone();
    $clone.addClass('sticky-header-clone');
    $originalHeader.before($clone);
    
    $clone.hide();
    
    var originalWidth = $originalHeader.outerWidth();
    var originalHeight = $originalHeader.outerHeight();
    
    $clone.css({
        'position': 'fixed',
        'top': '70px',
        'width': originalWidth + 'px',
        'height': originalHeight + 'px',
        'z-index': 10,
        'background-color': $originalHeader.css('background-color'),
        'margin': '0',
        'border': 'none',
    });
    
    function updateSticky() {
        // Если режим не фиксированный, ничего не делаем
        if (!isFixedMode()) {
            $clone.hide();
            $originalHeader.css('visibility', 'visible');
            return;
        }
        
        var scrollTop = $window.scrollTop();
        
        if (scrollTop > headerTop - 70) {
            var currentColor = $originalHeader.css('background-color');
            $clone.css({
                'left': $contentPage.offset().left + 'px',
                'background-color': currentColor
            }).show();
            $originalHeader.css('visibility', 'hidden');
        } else {
            $clone.hide();
            $originalHeader.css('visibility', 'visible');
        }
    }
    
    $window.on('scroll', updateSticky);
    $window.on('resize', function() {
        headerTop = $originalHeader.offset().top;
        updateSticky();
    });
    
    // Отслеживаем изменение режима
    var observer = new MutationObserver(function() {
        updateSticky();
    });
    
    observer.observe(document.body, { 
        attributes: true, 
        attributeFilter: ['data-layout-position'] 
    });
}

function createStickyHeader() {
    var $originalHeader = $('.page-title-box.sticky').first();
    var $window = $(window);
    var $contentPage = $('.content-page');
    var headerTop = $originalHeader.offset().top;
    
    $('.sticky-header-clone').remove();
    
    // Функция проверки режима
    function isFixedMode() {
        return $('body').attr('data-layout-position') !== 'scrollable' && 
               $('html').attr('data-layout-position') !== 'scrollable';
    }
    
    var $clone = $originalHeader.clone();
    $clone.addClass('sticky-header-clone');
    $originalHeader.before($clone);
    
    $clone.hide();
    
    var originalWidth = $originalHeader.outerWidth();
    var originalHeight = $originalHeader.outerHeight();
    
    $clone.css({
        'position': 'fixed',
        'top': '70px',
        'width': originalWidth + 'px',
        'height': originalHeight + 'px',
        'z-index': 100,
        'background-color': $originalHeader.css('background-color'),
        'margin': '0',
        'border': 'none',
    });
    
    function updateSticky() {
        // Если режим не фиксированный, ничего не делаем
        if (!isFixedMode()) {
            $clone.hide();
            $originalHeader.css('visibility', 'visible');
            return;
        }
        
        var scrollTop = $window.scrollTop();
        
        if (scrollTop > headerTop - 70) {
            var currentColor = $originalHeader.css('background-color');
            $clone.css({
                'left': $contentPage.offset().left + 'px',
                'background-color': currentColor
            }).show();
            $originalHeader.css('visibility', 'hidden');
            
            // Важно: не трогаем select2 внутри модального окна
            // Просто обновляем позицию, не вмешиваясь в DOM модалки
        } else {
            $clone.hide();
            $originalHeader.css('visibility', 'visible');
        }
    }
    
    $window.on('scroll', updateSticky);
    $window.on('resize', function() {
        headerTop = $originalHeader.offset().top;
        originalWidth = $originalHeader.outerWidth();
        $clone.css('width', originalWidth + 'px');
        updateSticky();
    });
    
    // Отслеживаем изменение режима
    var observer = new MutationObserver(function() {
        updateSticky();
    });
    
    observer.observe(document.body, { 
        attributes: true, 
        attributeFilter: ['data-layout-position'] 
    });
}

// Сброс базы данных "livemachines"
function resetDatabase(event) {
    event.preventDefault();
    
    // Подтверждение действия
    if (!confirm('Вы действительно хотите отменить все изменения в базе данных?')) {
        return false;
    }
    
    console.log('Начинаем сброс БД');
    
    // Показываем прелоадер
    var preloader = document.getElementById('preloader');
    var status = document.getElementById('status');
    
    if (preloader && status) {
        preloader.style.display = 'block';
        status.style.display = 'block';
    }

    $('#preloader .info').text('Восстановление базы данных');
    
    // Выполняем AJAX запрос
    fetch('/livemachines/reset-database?confirmed=yes', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Сетевая ошибка: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('Ответ получен:', data);
        
        // Скрываем прелоадер
        //if (preloader && status) {
            //preloader.style.display = 'none';
            //status.style.display = 'none';
        //}
        
        if (data.success) {
            // Успех - показываем сообщение и перезагружаем страницу
            $('#preloader .info').text('✅ База данных успешно восстановлена!');
            setTimeout(function(){
                location.reload(); // или window.location.href = data.redirect;
            }, 2000);
        } else {
            // Ошибка
            alert('❌ Ошибка: ' + (data.error || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        
        // Скрываем прелоадер
        if (preloader && status) {
            preloader.style.display = 'none';
            status.style.display = 'none';
        }
        
        alert('❌ Произошла ошибка при выполнении запроса: ' + error.message);
    });
    
    return false;
}

// ===== ГЛОБАЛЬНОЕ РЕШЕНИЕ ДЛЯ ПРОБЛЕМ С ARIA-HIDDEN =====
(function() {
    // 1. Подавление ошибок в консоли
    const originalConsoleError = console.error;
    console.error = function() {
        const args = Array.from(arguments);
        const errorString = args.join(' ');
        
        // Игнорируем ошибки связанные с aria-hidden
        if (errorString.includes('Blocked aria-hidden') || 
            errorString.includes('aria-hidden on an element') ||
            (errorString.includes('aria-hidden') && errorString.includes('focus'))) {
            return;
        }
        
        originalConsoleError.apply(console, arguments);
    };

    // 2. Глобальный обработчик для всех модальных окон Bootstrap
    $(document).ready(function() {
        // Флаг для отслеживания, нужно ли снимать фокус
        let shouldBlur = false;
        
        // Функция для безопасного снятия фокуса ТОЛЬКО с кнопок
        function safeBlurButtons() {
            // Убираем фокус только с кнопок, но не с полей ввода
            $('.btn-close').blur();
            $('button:focus').blur();
            $('a:focus').blur();
        }

        // Перехватываем все клики по кнопкам закрытия
        $(document).on('click', '.btn-close, .modal .btn-close, button[data-bs-dismiss="modal"]', function() {
            safeBlurButtons();
        });

        // Для всех модальных окон - событие перед закрытием
        $(document).on('hide.bs.modal', '.modal', function() {
            safeBlurButtons();
        });

        // После закрытия модального окна
        $(document).on('hidden.bs.modal', '.modal', function() {
            // Убираем aria-hidden с wrapper и body
            $('#wrapper, body').removeAttr('aria-hidden');
        });

        // Наблюдатель за изменениями атрибутов для всех модальных окон
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'aria-hidden') {
                    const $target = $(mutation.target);
                    
                    // Если aria-hidden добавляется к wrapper или body, а модальное окно еще видимо
                    if (($target.is('#wrapper') || $target.is('body')) && $('.modal.show').length) {
                        $target.removeAttr('aria-hidden');
                    }
                    
                    // Если aria-hidden добавляется к модальному окну
                    if ($target.hasClass('modal') && $target.attr('aria-hidden') === 'true') {
                        // Проверяем, есть ли фокус на элементе внутри, который не является полем ввода
                        setTimeout(function() {
                            const $focused = $target.find(':focus');
                            if ($focused.length && !$focused.is('input, select, textarea, [contenteditable="true"]')) {
                                $focused.blur();
                            }
                        }, 10);
                    }
                }
            });
        });

        // Наблюдаем за всеми модальными окнами и wrapper
        if (document.getElementById('wrapper')) {
            observer.observe(document.getElementById('wrapper'), { attributes: true });
        }
        observer.observe(document.body, { attributes: true });
        
        // Динамически наблюдаем за новыми модальными окнами
        const modalObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if ($(node).hasClass('modal')) {
                        observer.observe(node, { attributes: true });
                    }
                });
            });
        });
        
        modalObserver.observe(document.body, { childList: true, subtree: true });

        // Обработка клавиши Escape
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('.modal.show').length) {
                // При нажатии Escape снимаем фокус с кнопок, но не с полей ввода
                setTimeout(function() {
                    if ($('button:focus').length || $('.btn-close:focus').length) {
                        $('button:focus').blur();
                        $('.btn-close').blur();
                    }
                }, 10);
            }
        });

        // Специальная обработка для Select2 внутри модальных окон
        $(document).on('select2:open', function() {
            const $modal = $('.modal.show');
            if ($modal.length) {
                // Повышаем z-index Select2
                $('.select2-container').css('z-index', 10);
            }
        });

        $(document).on('select2:close', function() {
            // Не снимаем фокус при закрытии Select2
        });

        // Отслеживаем фокус на полях ввода и не мешаем им
        $(document).on('focus', 'input, select, textarea, [contenteditable="true"]', function() {
            // Пользователь хочет вводить текст - не будем мешать
            shouldBlur = false;
        });
    });

    // 3. Переопределение методов Bootstrap модальных окон (если используется Bootstrap 5)
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const originalHide = bootstrap.Modal.prototype.hide;
        
        bootstrap.Modal.prototype.hide = function() {
            // Убираем фокус только с кнопок перед скрытием
            if (document.activeElement && 
                document.activeElement.tagName === 'BUTTON' || 
                $(document.activeElement).hasClass('btn-close')) {
                document.activeElement.blur();
            }
            
            // Вызываем оригинальный метод
            return originalHide.call(this);
        };
    }
})();

$(document).ready(function() {
    setTimeout(createStickyHeader, 100);
});