<?php
/*
Plugin Name: Delivery zones with Yandex
Description: Автоматически расчитывает зону доставки. Яндекс. Woocommerce.
Version: 1.0.0
Author: Vladimir U.
*/

/*  Copyright 2021  Vladimir

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
define('DELIVERYZONES_DIR', plugin_dir_path(__FILE__));
wp_enqueue_script('jquery');
wp_enqueue_script('deliveryzzones_main_js', plugins_url( 'assets/js/main.js', __FILE__ )); 

    add_filter( 'woocommerce_get_sections_shipping', 'delivery_zones_add_section' );
    function delivery_zones_add_section( $sections ) {
    
        $sections[ 'deliveryzones' ] = 'Определение зоны доставки';
        return $sections;
    
    }
    add_filter( 'woocommerce_default_address_fields' , 'DZ_optional_postcode_checkout' );
    function DZ_optional_postcode_checkout( $p_fields ) {
    $p_fields['postcode']['required'] = false;
    $p_fields['postcode']['disabled'] = true;
    return $p_fields;
    }
add_filter( 'woocommerce_get_settings_shipping', 'delivery_zones_settings', 25, 2 );
 
function delivery_zones_settings( $settings, $current_section ) {
 
	if ( 'deliveryzones' == $current_section ) {
 
		$new_settings = array();
 
		// Добавляем заголовок для секции
		$new_settings[] = array(
			'name' => 'Настройки автоматического определения зоны доставки',
			'type' => 'title',
			'desc' => 'Настройки автоматического определения зоны и стоимости доставки на странице оплаты заказа',
			'id' => 'deliveryzones'
		);
		$new_settings[] = array(
			'name'     => 'API ключ Яндекса',
			'desc_tip' => 'Ключ генерируется в Яндексе',
			'id'       => 'deliveryzones_api_key',
			'type'     => 'text',
			'desc'     => '',
		);
        $geozone_file = get_option( 'deliveryzones_file' );
        $curl = curl_init($geozone_file);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        $curl_response = curl_exec($curl);
        curl_close($curl);        
        $curl_json = json_decode($curl_response, true);
        if(json_last_error() === JSON_ERROR_NONE){
            $new_settings[] = array(
                'name' => 'Файл зон доставки',
                'type' => 'text',
                'desc' => 'Файл зон доставки найден.',
                'id' => 'deliveryzones_file',
                'value' => $geozone_file,
                'enabled' => false
            );
        } else {
            $new_settings[] = array(
                'name' => 'Файл зон доставки',
                'type' => 'text',
                'desc_tip' => 'Указывайте полный путь с протоколом, например: https://example.com',
                'desc' => 'Файл зон доставки не найден. Укажите ссылку на ресурс или файл в формате json. Зоны доставки экспортируются из <a href="https://yandex.ru/map-constructor/">Конструктора Карт.</a>. В файле зоны доставки для каждого полигона в секции properties необходимо указать параметр "zoneCode", который будет подставляться как в почтовый индекс на странице оплаты.',
                'id' => 'deliveryzones_file',
                'value' => $geozone_file,
                'enabled' => false
            );
        }
 
		// Элемент, завершающий секцию
		$new_settings[] = array(
			'type' => 'sectionend',
			'id' => 'deliveryzones'
		);
		return $new_settings;
 
	} else {
		return $settings;
	}
}
add_action( 'woocommerce_after_order_notes', 'deliveryzones_process' );

function deliveryzones_process( $checkout ) {
    $geozone_file = get_option( 'deliveryzones_file' );
    $deliveryzones_api_key = get_option( 'deliveryzones_api_key' );    
    echo '<div class="checkout_map" style="width:100%; height:500px;">
          <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU&amp;coordorder=longlat&amp;apikey=' . $deliveryzones_api_key  . '" type="text/javascript"></script>
          <div id="map" style="width:100%; height:500px;"></div>
          </div>
    ';
    echo '<input type="hidden" id="zonesDataUrl" style="display: none;position: absolute; top: -99999px; overflow: hidden; height: 0;" value="' . $geozone_file . '">';
}
    
?>