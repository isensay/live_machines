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