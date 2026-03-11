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
        if (preloader && status) {
            preloader.style.display = 'none';
            status.style.display = 'none';
        }
        
        if (data.success) {
            // Успех - показываем сообщение и перезагружаем страницу
            alert('✅ База данных успешно сброшена!');
            location.reload(); // или window.location.href = data.redirect;
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

$(document).ready(function() {
    setTimeout(createStickyHeader, 100);
});