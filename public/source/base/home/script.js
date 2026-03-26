$(document).ready(function(){
            
    // Fix bug on load page
    $('.knob-chart').removeClass('invisible');

    // Анимация для виджетов
    $(".knob-chart input").knob();
    $('.knob-chart input').each(function(){
        var obj = $(this);
        var graphicValue = parseInt(obj.val());
        $({animatedVal: 0}).animate({animatedVal: graphicValue}, {
            duration: 2000,
            easing: "swing",
            step: function() {
                obj.val(Math.ceil(this.animatedVal)).trigger("change");
            }
        });
    });

    // Карта стран производителей
    const markers1 = [
        { name: 'Германия', coords: [51.1657, 10.4515], city: 'Берлин', latLng: [52.5200, 13.4050] },
        { name: 'Китай', coords: [35.8617, 104.1954], city: 'Пекин', latLng: [39.9042, 116.4074] },
        { name: 'Россия', coords: [61.5240, 105.3188], city: 'Москва', latLng: [55.7558, 37.6173] },
        { name: 'США', coords: [37.0902, -95.7129], city: 'Вашингтон', latLng: [38.9072, -77.0369] },
        { name: 'Тайвань', coords: [23.6978, 120.9605], city: 'Тайбэй', latLng: [25.0330, 121.5654] },
        { name: 'Япония', coords: [36.2048, 138.2529], city: 'Токио', latLng: [35.6762, 139.6503] }
    ];

    const markers = countriesFromDb.map(country => ({
            name: country.name,
            city: country.name,
            latLng: [parseFloat(country.latitude), parseFloat(country.longitude)]
        }));
    
    $('#world-map-markers').vectorMap({
        map: 'world_mill_en',
        backgroundColor: 'transparent',
        regionStyle: {
            initial: { fill: '#e9ecef' },
            hover: { fill: '#1abc9c' },
            selected: { fill: '#1abc9c' }
        },
        markers: markers.map(marker => ({
            latLng: marker.latLng,
            name: marker.city
        })),
        markerStyle: {
            initial: { fill: '#f1556c', stroke: '#FFFFFF', r: 8 },
            hover: { fill: '#1abc9c', stroke: '#FFFFFF', r: 8 }
        },
        showTooltip: false,  // ← ОТКЛЮЧАЕМ ТУЛТИПЫ
        onRegionLabelShow: function() {
            return false; // Отключаем подписи регионов
        },
        onMarkerLabelShow: function() {
            return false; // Отключаем подписи маркеров
        },
        enableZoom: false,
        zoomOnScroll: false,
        zoomButtons: false
    });

    
});