
function deliveryZones() {
    let postCode = document.querySelector('#billing_postcode');	
    let inputAdressElem = document.querySelector('#billing_address_1');
    if(postCode !== undefined && postCode !== null && inputAdressElem !== undefined && inputAdressElem !== null){
        postCode.setAttribute('disabled', true);
        document.querySelector('#billing_postcode_field').style.display = "none";
        inputAdressElem.value='';
        ymaps.ready(init);
    }
}
var myMap;


document.addEventListener("DOMContentLoaded", deliveryZones);

function init() {
    var inputAdressElem, selectElem, addr, deliveryZones;
    var myMap = new ymaps.Map('map', {
            center: [36.587297, 50.595433],
            zoom: 10,
            controls: ['geolocationControl']
        }),
        deliveryPoint = new ymaps.GeoObject({
            geometry: {type: 'Point'},
            properties: {}
        }, {
            preset: 'islands#blackDotIconWithCaption',
            draggable: true,
            iconCaptionMaxWidth: '215'
        })
    myMap.geoObjects.add(deliveryPoint);

    function onZonesLoad(json) {
        inputAdressElem = document.querySelector('#billing_address_1');
    inputAdressElem.addEventListener("focusout", calculateDelivery);
    function calculateDelivery(){
        deliveryPoint.properties.set({iconCaption: '', balloonContent: ''});
        deliveryPoint.options.set('iconColor', 'black');
        let inputAdressVal = document.querySelector('#billing_address_1').value;
        let selectElemVal = document.querySelector('#billing_city').value;
        var postCode = jQuery('#billing_postcode');
        if(selectElemVal == 'Белгород'){
            addr = 'Белгород, ' + inputAdressVal;
            if(addr.length > 0 && localStorage && localStorage.getItem(addr)){
                
            }else{
                var myGeocoder = ymaps.geocode(addr);
                myGeocoder.then(
                    function (res) {
                        highlightResult(res.geoObjects.get(0));
                    },
                    function (err) {
                    } 
                ); 
            }
             
        } else {
            postCode.val('309070');
            postCode.trigger('keydown');
        }
    }
        deliveryZones = ymaps.geoQuery(json).addToMap(myMap);
        deliveryZones.each(function (obj) {
            obj.options.set({
                fillColor: obj.properties.get('fill'),
                fillOpacity: obj.properties.get('fill-opacity'),
                strokeColor: obj.properties.get('stroke'),
                strokeWidth: obj.properties.get('stroke-width'),
                strokeOpacity: obj.properties.get('stroke-opacity')
            });
            obj.properties.set('balloonContent', obj.properties.get('description'));            
        });

        function highlightResult(obj) {
            var coords = obj.geometry.getCoordinates(),
                polygon = deliveryZones.searchContaining(coords).get(0);
            if (polygon) {
                deliveryZones.setOptions('fillOpacity', 0.4);
                polygon.options.set('fillOpacity', 0.8);
                deliveryPoint.geometry.setCoordinates(coords);
                deliveryPoint.options.set('iconColor', polygon.properties.get('fill'));
                let postCode = jQuery('#billing_postcode');
                postCode.val(polygon.properties.get('zoneCode'));
                postCode.trigger('keydown');
            } else {
                deliveryZones.setOptions('fillOpacity', 0.4);
                deliveryPoint.geometry.setCoordinates(coords);
                deliveryPoint.properties.set({
                    iconCaption: 'За пределами доставки',
                    balloonContent: 'Cвяжитесь с оператором',
                    balloonContentHeader: ''
                });
                deliveryPoint.options.set('iconColor', 'black');
            }
        }
    }
    let zonesDataUrl = document.querySelector('#zonesDataUrl').value;
    if(zonesDataUrl !== null && zonesDataUrl!== undefined && zonesDataUrl.length > 5){
        $.ajax({
            url: zonesDataUrl,
            dataType: 'json',
            success: onZonesLoad
        });
    }    
}