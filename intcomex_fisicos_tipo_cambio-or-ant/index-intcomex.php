<?php
/*
    Plugin Name: intcomex Web Service con tipo de cambio 2
    Plugin URI: https://techwebgt.com
    Description: Integracion con intcomex con tipo de cambio con productos digitales parametrizados
    Author: Techweb
    Author URI: https://techwebgt.com
    Version: 2.5.2

	Copyright: © 2012 Techweb 
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/*
 Modificaciones:
		Se agrego actualizacion de brand 22-04-2024
 */

/**
 * VARIABLES PARA ADMINISTRADOR
*/

//Fuerza a que se actualicen todos los productos 
//define('FORZAR_CATALOGO', false);

//Fuerza a que se actualicen todos los PRECIOS E INVENTARIO
//define('FORZAR_PRECIOS_INVENTARIO', false);

define('DIAS_CATALOGO', 1);

define('HORAS_INVENTARIO_PRECIO', 12);

define('DIAS_CATALOGO_EXTENDIDO', 7);

define('LOTES_PRODUCTS', 150);

//solo activar cuando se necesite usar los archivos de carga de los productos localmente sin que pase por la consulta de la API
define('DEBUG_INTCOMEX', false);

require_once( 'inc/functions.php' );
require_once( 'inc/functions_license.php' );
require_once( 'external_image/external_image.php' );
require_once( 'email/email-functions.php' );
require_once( 'widget/product_widget.php' );

foreach (glob( dirname( __FILE__) . "/WP_Queue/Connections/*.php") as $filename) { require_once($filename); }
foreach (glob(dirname( __FILE__) . "/WP_Queue/Exceptions/*.php") as $filename) { require_once($filename); }
foreach (glob(dirname( __FILE__) . "/WP_Queue/*.php") as $filename) { require_once($filename); }
foreach (glob(dirname( __FILE__) . "/inc/actions/*.php") as $filename) { require_once($filename); }

foreach (glob(dirname( __FILE__) . "/inc/options/options_functios.php") as $filename) { require_once($filename); }

if (!class_exists('WC_IntcomexWebService')) :
	
	class WC_IntcomexWebService
	{
		/**
		 * Construct the plugin.
		 */
		public function __construct(){
			add_action('plugins_loaded', array($this, 'init'));
		}
		/**
		 * Initialize the plugin.
		 */
		public function init(){
			// Checks if WooCommerce is installed.
			if (class_exists('WC_Integration')) {
				// Include our integration class.
				include_once 'inc/intcomex-integration.php';
				require_once( 'page/page-log.php' );
				// Register the integration.
				add_filter('woocommerce_integrations', array($this, 'add_integration'));
			}
			// Set the plugin slug
			define( 'INTCOMEX_PLUGIN', 'wc-settings' );
			
			// Setting action for plugin
			add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'WC_IntcomexWebService_action_links');
		}

		/**
		 * Add a new integration to WooCommerce.
		 */
		public function add_integration($integrations)
		{
			$integrations[] = 'WC_IntcomexWebService_Integration';
			return $integrations;
		}
	}
	$WC_IntcomexWebService = new WC_IntcomexWebService(__FILE__);
	function WC_IntcomexWebService_action_links($links)
	{

		$links[] = '<a href="' . menu_page_url(INTCOMEX_PLUGIN, false) . '&tab=integration">Settings</a>';
				
		return $links;
	}

	register_deactivation_hook( __FILE__, 'my_deactivation' );
	function my_deactivation() {
	    add_to_info_log("Plugin desactivado");
		wp_clear_scheduled_hook( 'precios_inventario_intcomex' );
		wp_clear_scheduled_hook( 'precios_intcomex' );
		wp_clear_scheduled_hook( 'catalogo_event' );
		wp_clear_scheduled_hook( 'catalogo_extendido_event' );
	}
	

	register_activation_hook( __FILE__, 'my_activation' );
	function my_activation() {
		if(defined('DISABLE_WP_CRON') and DISABLE_WP_CRON){
			add_to_error_log("Estan desactivados los cron jobs, favor editar el archivo wp-config.php y desactivar DISABLE_WP_CRON");
			add_to_error_log("Luego desactive y active el plugin nuevamente");
			return;
		}

		add_to_info_log("Plugin Activado");
		wp_queue_install_tables();
		
		$comprobadorLog = get_option( 'log_intcomex', null);
		if(is_null($comprobadorLog)){
			add_to_info_log("Inicializada option log_intcomex");
			add_option( 'log_intcomex' , 'inicio log intcomex' , '' , 'no');
		}

		activateTimers();

		$comprobadorLog = get_option( 'create_category_intcomex', null);
		if(is_null($comprobadorLog)){
			add_to_info_log("Inicializada create_category_intcomex");
			add_option( 'create_category_intcomex' , true, '' , 'no');
		}

		$comprobadorLog = get_option( 'ranges_prices_categories_intcomex', null);
		if(is_null($comprobadorLog)){
			add_to_info_log("Inicializada ranges_prices_categories_intcomex");
			//se establece el rango de precios general
			
			$prices = array(
				array(
					'id'=>'default',
					'name' => 'General',
					'values' => array(
						0 => array(
							0 => 0,
							1 => 99999,
							2 => 25
						)
					)
				)	
			);
			add_option( 'ranges_prices_categories_intcomex' , $prices, '' , 'no');
		}

		$comprobadorLog = get_option( 'categories_intcomex_values', null);
		if(is_null($comprobadorLog)){
			add_to_info_log("Inicializada categories_intcomex_values");
			add_option( 'categories_intcomex_values' , nulL, '' , 'no');
		}

		$comprobadorLog = get_option( 'download_categories_saved_prices', null);
		if(is_null($comprobadorLog)){
			add_to_info_log("Inicializada download_categories_saved_prices");
			add_option( 'download_categories_saved_prices' , false, '' , 'no');
		}
	}
	
endif;

wp_queue()->cron(2);