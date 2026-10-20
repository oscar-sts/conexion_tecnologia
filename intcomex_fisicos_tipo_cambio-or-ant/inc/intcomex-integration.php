<?php

/**
 * Integration intcomex.
 *
 * @package   Woocommerce intcomex
 * @category Integration
 * @author   Techweb guatemala
 */

if (!class_exists( 'WC_IntcomexWebService_Integration' ) ) :
	
class WC_IntcomexWebService_Integration extends WC_Integration {
  /**
   * Init and hook in the integration.
   */
  public function __construct() {
    global $woocommerce;
    $this->id                 = 'intcomex-plugin';
    $this->method_title       = __( 'intcomex Integration');
    $this->method_description = __( 'Integración con intcomex web service');
    // Load the settings.
    $this->init_form_fields();
    $this->init_settings();
    // Define user set variables.
    $this->api_key          = $this->get_option('api_key');
    $this->user_access_key  = $this->get_option('user_access_key');
    $this->testmode         = $this->get_option('test_mode');
	$this->license_key         = $this->get_option('license_key');
	$this->sku_termination     = $this->get_option('sku_termination');
	$this->impuesto     = $this->get_option('impuesto');
	$this->descargar_imagen_principal     = $this->get_option('descargar_imagen');
	$this->tipo_cambio     = floatval($this->get_option('tipo_cambio'));
	$this->api_key_exchange = $this->get_option('api_key_exchange');
	$this->catalogo_pesado = $this->get_option('catalogo_pesado');
    // Actions.
    add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );
   	add_action('woocommerce_order_status_completed', array($this, 'liberateOrder'));
	add_action('woocommerce_order_status_processing', array($this, 'generateOrder'));
	add_action('woocommerce_order_status_cancelled', array($this, 'cancelateOrder'));

	//add_action( 'woocommerce_before_calculate_totals', array($this,'AlActualizarTotal') );
	add_action( 'woocommerce_add_to_cart', array($this,'add_to_cart'), 10, 6 );

	// TIMERS
	add_action( 'precios_inventario_intcomex',   array($this,'updateInventory'));
	add_action( 'precios_intcomex',   array($this,'updatePriceList'));
	add_action( 'catalogo_event',   array($this,'validate_license'));
	add_action( 'catalogo_event',   array($this,'updateCatalog'));
	add_action( 'catalogo_extendido_event',   array($this,'UpdateCatalogExtended'));


	//EMAIL
    add_action( 'woocommerce_email_after_order_table', 'email_template_intcomex', 10, 4 );
	add_filter( 'woocommerce_email_styles', 'css_intcomex_email', 9999, 2 );
	
    if ( $this->testmode == 'yes' ) {
      $this->url = 'https://intcomex-test.apigee.net/v1';
    } else {
      $this->url = 'https://intcomex-prod.apigee.net/v1';
    }
  }
	
	/**
	 * Initialize integration settings form fields.
	*/
	public function init_form_fields() {
		$this->form_fields = array(
		'api_key' => array(
			'title'             => __( 'API Key'),
			'type'              => 'text',
			'description'       => __( 'Public key '),
			'desc_tip'          => true,
			'default'           => '',
			'css'      => 'width:170px;',
		),
		'user_access_key' => array(
			'title'             => __( 'User access key'),
			'type'              => 'password',
			'description'       => __( 'Private key user'),
			'desc_tip'          => true,
			'default'           => '',
			'css'      => 'width:170px;',
		),
		'sku_termination' => array(
			'title'             => __( 'Terminación de sku'),
			'type'              => 'text',
			'description'       => __( 'Terminacion de SKU para productos de intcomex'),
			'desc_tip'          => true,
			'default'           => '',
			'css'      => 'width:170px;',
		),
		'title' => array(
			'title'             => __( 'License key'),
			'type'              => 'title',
			'description'       => __( 'License key of use'),
			'desc_tip'          => true,
			'default'           => '',
			'css'      => 'width:170px;',
		),
		'license_key' => array(
			'title'             => __( 'key'),
			'type'              => 'text',
			'description'       => __( 'key'),
			'desc_tip'          => true,
			'default'           => '',
			'css'      => 'width:170px;',
		),
		'impuesto' => array(
			'title'             => __( 'Iva a agregar'),
			'type'              => 'number',
			'description'       => __( 'Inpuesto a agregar a los productos'),
			'desc_tip'          => true,
			'default'           => '0',
			'css'      => 'width:170px;',
		),
		'api_key_exchange' => array(
			'title'             => __( 'Llave de acceso a la api exchange'),
			'type'              => 'text',
			'description'       => __( 'Valor de cambio'),
			'desc_tip'          => true,
			'default'           => '',
			'css'      => 'width:170px;',
		),
		'moneda' => array(
			'title'             => __( 'Moneda en tipo ISO 4217'),
			'type'              => 'text',
			'description'       => __( 'Moneda en tipo ISO 4217 '),
			'desc_tip'          => true,
			'default'           => 'GTQ',
			'css'      => 'width:170px;',
		),
		'tipo_cambio' => array(
			'title'             => __( 'Tipo de cambio'),
			'type'              => 'text',
			'description'       => __( 'Valor de cambio'),
			'desc_tip'          => true,
			'default'           => '0',
			'css'      => 'width:170px;',
		),
		'puntos_aumentar' => array(
			'title'             => __( 'Puntos a aumentar al valor de cambio'),
			'type'              => 'text',
			'description'       => __( 'La cantidad a sumar al valor de cambio'),
			'desc_tip'          => true,
			'default'           => '0',
			'css'      => 'width:170px;',
		),
		'title_configuracion' => array(
			'title'             => __( 'Configuración'),
			'type'              => 'title',
			'description'       => __( 'Aqui establece la configuracion del funcionamiento del plugin'),
			'desc_tip'          => true,
			'default'           => '',
			'css'      => 'width:170px;',
		),
		'catalogo_pesado' => array(
			'title'             => __( 'Catalogo pesado'),
			'type'              => 'checkbox',
			'description'       => __( 'Es para indicar que este usuario tiene la opcion de catalogo pesado.'),
			'desc_tip'          => true,
			'default'           => 'no',
		),
		// 'set_pending_products' => array(
		// 	'title'             => __( 'Al momento de crearse se coloquen en pendiente'),
		// 	'type'              => 'checkbox',
		// 	'description'       => __('Cuando se cree un producto este se colocará en pendiente'),
		// 	'desc_tip'          => true,
		// 	'default'           => 'no',
		// ),
		// 'set_public_products' => array(
		// 	'title'             => __( 'Colocar el producto en publico cuando tenga precio'),
		// 	'type'              => 'checkbox',
		// 	'description'       => __('El precio una vez tenga precio se colocará en publico'),
		// 	'desc_tip'          => true,
		// 	'default'           => 'no',
		// ),
		// 'set_delete_products' => array(
		// 	'title'             => __( 'Se elimina el producto una vez tenga stock 0'),
		// 	'type'              => 'checkbox',
		// 	'description'       => __('El producto se eliminará si el stock llega a hacer 0'),
		// 	'desc_tip'          => true,
		// 	'default'           => 'no',
		// ),
		'descargar_imagen' => array(
			'title'             => __( 'Descargar solo imagen principal'),
			'type'              => 'checkbox',
			'description'       => __('Util si tiene woocommerce enlazado con redes sociales'),
			'desc_tip'          => true,
			'default'           => 'yes',
		),
		'test_mode' => array(
			'title'             => __( 'Modo pruebas'),
			'type'              => 'checkbox',
			'description'       => __( ''),
			'desc_tip'          => true,
			'default'           => 'yes',
		),
		);
	}

	function get_api($baseUrl, $query = null){
		if(!validateLicense(false)){ return null; }
		$badRequestCodes = Array(400, 401, 403, 404, 429, 500, 501);
    	$urlToRequest = $this->url . $baseUrl;
    	if (!is_null($query)) {
      		$urlToRequest = $urlToRequest . '?' . http_build_query($query);
    	}
    	$token =  'Bearer ' . $this->createToken();

    	$result = wp_remote_get($urlToRequest, array(
			'timeout' => 600, 
			'headers' => array('Authorization' => $token))
		);
		$codeResponse = wp_remote_retrieve_response_code($result);
		
		$body = json_decode($result['body'], true);
		if ( in_array($codeResponse, $badRequestCodes) ) {
        	error_log(print_r($result, true));
			$message = '[' . $body['ErrorCode'] . '] ' .  $body['Message'] . ' (Referencia: ' . $body['Reference'] . ')';
        	add_to_error_log($message);
			return $message;
      	}
		return $body;
  	}
	
	function post_api($baseUrl, $body ,$query = null){
		if(!validateLicense(false)){ return null; }

		$badRequestCodes = Array(400, 401, 403, 404, 429, 500, 501);
    	$urlToRequest = $this->url . $baseUrl;
		if (!is_null($query)) {
		  $urlToRequest = $urlToRequest . '?' . http_build_query($query);
		}
    	$token =  'Bearer ' . $this->createToken();
		add_to_error_log(print_r( json_encode($body) , true));
		$response = wp_remote_post( $urlToRequest, array(
			'method'      => 'POST',
			'timeout'     => 100,
			'headers'     => array('Authorization' => $token,  'Content-Type' => 'application/json'),
			'body'        => json_encode($body)
			)
		);
		
	  	$codeResponse = wp_remote_retrieve_response_code($response);
	  	$body = json_decode($response['body'], true);
		add_to_error_log(print_r( $body , true));
		
		if ( in_array($codeResponse, $badRequestCodes) and isset($body['ErrorCode']) ) {
        	error_log(print_r($response, true));
			$message = '[' . $body['ErrorCode'] . '] ' .  $body['Message'] . ' (Referencia: ' . $body['Reference'] . ')';
        	add_to_error_log($message);
			return $message;
      	}
		if(isset($body['fault'])){
			add_to_error_log(print_r($response, true));
			
			add_to_error_log('Ha ocurrido un error critico');
		}
	  
		return $body;
  	}

  	function createToken(){
		$this->dateToken = gmdate("Y-m-d\TH:i:s");
		$signature = hash('sha256', $this->api_key . ',' . $this->user_access_key . ',' . $this->dateToken );
		$this->token = 'apiKey=' . $this->api_key .'&utcTimeStamp=' . $this->dateToken .'&signature=' . $signature;

		return $this->token;
	}

	// function AlActualizarTotal($cart_object){
	// 	foreach ($cart_object->cart_contents as $key => $value) {
	// 		$productSimple = $value['data'];
	// 		$sku =  str_replace("-$this->sku_termination", '', $productSimple->get_sku());
			
	// 		$product = new WC_Product( wc_get_product_id_by_sku( $productSimple->get_sku() ));
	// 		if(!$product->get_meta('intcomex_product')){ continue; }
	// 		$productAPI = $this->get_api('/getproduct', array('sku'=> $sku));
	// 		if(!$productAPI) return null;
	// 		$this->updateProduct($productAPI);
	// 		add_to_log('Actualizado ' . $productSimple->get_sku());
	// 	}
	// 	return $cart_object;
	// }

	function add_to_cart($cart_id, $product_id, $request_quantity, $variation_id, $variation, $cart_item_data){

		
		$product = new WC_Product( $product_id );

		if( !$product->exists() ){ return; }
		if( !$product->get_meta('intcomex_product') ){ return; }
		$sku =  str_replace("-$this->sku_termination", '', $product->get_sku());
		
		$productAPI = $this->get_api('/getproduct', array('sku'=> $sku));
		
		if( is_string($productAPI) ){
			$this->removeProductoCart( $product->get_sku(), $product->get_name() );
			$product->set_stock_status( 'outofstock' );
			//$product->set_status( 'pending' );
			$product->set_stock_quantity(0);
			$product->save();
			add_to_problem_log("El producto " . $product->get_sku() . " fue colocado en con STOCK  '0' ya que presento un error");
			return false;
		}
		if( ! $productAPI ) return null;

		$this->updateProduct($productAPI);
		add_to_log('Actualizado ' . $product->get_sku());
		
		return;
	}

  	function updateProduct($productAPI){
		$instanceP = new ProductPriceInventory($productAPI, $this->sku_termination, 0, $this->impuesto, $this->tipo_cambio  );
		$instanceP->handle();
  	}

	public function removeProductoCart($sku, $name){
		
		foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ){
			$skuCart = $cart_item['data']->get_sku();
			if( $skuCart == $sku ){
				wc_add_notice( __("UPS! Hemos tenido un error con el producto $name, por favor intenta mas tarde", 'woocommerce'), 'error' );
				WC()->cart->remove_cart_item( $cart_item_key );
				break;
			}
		}
	}
  
	function updateInventory() {
		//se agrego el metodo de debugeo para ejecutar los archivos de inventario y no consultar la api
		$memory_total   = $this->get_memory_limit(); // 80% of max memory
		$memory_limit =  $memory_total * 0.80;
		$current_memory = round((memory_get_usage( true )/$memory_total)*100,0);
		add_to_info_log( "Memoria del servidor [Total: ".$memory_total."(100%) limite: 80%, actual " . $current_memory . "%]" );
			
		
		if (defined('DEBUG_INTCOMEX') and DEBUG_INTCOMEX == true) {
			update_option( 'intcomex_last_value_inventory', 0);
			wp_queue()->push( new load_ProductInventory() );
		}else{
			$products = $this->get_api('/getinventory', array('instock'=> 'true'));

			if(!$products) return null;
			if(is_string($products)){ return; }

			$res = saveResponse_inc('inventory', $products);

			if( defined('FORZAR_PRECIOS_INVENTARIO') ){
				add_to_log("FORZANDO ACTUALIZACION DE INVENTARIO");
			}else{
				add_to_log("Actualizando de inventario...");
			}

			if ($res) {
				update_option( 'intcomex_last_value_inventory', 0);
				wp_queue()->push( new load_ProductInventory() );
			}
		}

	}
	
	function updatePriceList() {
		$memory_total   = $this->get_memory_limit(); // 80% of max memory
		$memory_limit =  $memory_total * 0.80;
		$current_memory = round((memory_get_usage( true )/$memory_total)*100,0);
		add_to_info_log( "Memoria del servidor [Total: ".$memory_total."(100%) limite: 80%, actual " . $current_memory . "%]" );

		if (defined('DEBUG_INTCOMEX') and DEBUG_INTCOMEX == true) {
			update_option( 'intcomex_last_value_precios', 0);
			wp_queue()->push( new load_ProductPrices() );
		}else{
			$products = $this->get_api('/getpricelist', array('instock'=> 'true'));

			if(!$products) return null;
			if(is_string($products)){ return; }
			
			if( ! empty($this->api_key_exchange)){
				$total = get_tipo_cambio();
				add_to_info_log("Moneda de cambio establecida $total");
			}else{
				add_to_info_log("No tiene llave de exchange para tipo de cambio se toma el tipo de cambio: $this->tipo_cambio.");
			}

			

			$res = saveResponse_inc('prices', $products);

			if( defined('FORZAR_PRECIOS_INVENTARIO') ){
				add_to_log("FORZANDO ACTUALIZACION DE PRECIOS");
			}else{
				add_to_log("Actualizando de precios...");
			}

			if ($res) {
				update_option( 'intcomex_last_value_precios', 0);
				wp_queue()->push( new load_ProductPrices() );
			}
		}
	}
	
	function updateCatalog() {

		$memory_total   = $this->get_memory_limit(); // 80% of max memory
		$memory_limit =  $memory_total * 0.80;
		$current_memory = round((memory_get_usage( true )/$memory_total)*100,0);
		add_to_info_log( "Memoria del servidor [Total: ".$memory_total."(100%) limite: 80%, actual " . $current_memory . "%]" );

		if (defined('DEBUG_INTCOMEX') and DEBUG_INTCOMEX == true) {
			update_option( 'intcomex_last_value_catalog', 0);
			wp_queue()->push( new load_ProductCatalog() );
			add_to_info_log("[DEBUG] Actualizacion de catalogo en cola");
		}else{
			if( $this->testmode == 'yes' ){

				$products = $this->get_api('/getcatalog', array('locale'=> 'es','instock'=> 'true'));	

			}else if($this->catalogo_pesado == 'yes'){

				$products = $this->get_api('/downloadcatalog', array('locale'=> 'es','instock'=> 'true'));
				
			}else{
				$products = $this->get_api('/getcatalog', array('locale'=> 'es','instock'=> 'true'));	
			}
			
			if(!$products) return null;
			if(is_string($products)){ return; }

			$res = saveResponse_inc('catalog', $products);
			if( defined('FORZAR_CATALOGO_FISICO') ){
				add_to_log("FORZANDO ACTUALIZACION DE PRODUCTOS FISICOS");
			}

			if( defined('FORZAR_CATALOGO') ){
				add_to_log("FORZANDO CATALOGO COMPLETO");
			}

			if( ! defined('FORZAR_CATALOGO') and !defined('FORZAR_CATALOGO_FISICO') ){
				add_to_log("Actualizando de Catalogo...");
			}
			
			if ($res) {
				update_option( 'intcomex_last_value_catalog', 0);
				wp_queue()->push( new load_ProductCatalog() );
			}

			add_to_info_log("Actualizacion de catalogo en cola");
		}
		
		
	}
	
	function UpdateCatalogExtended(){

		$memory_total   = $this->get_memory_limit(); // 80% of max memory
		$memory_limit =  $memory_total * 0.80;
		$current_memory = round((memory_get_usage( true )/$memory_total)*100,0);
		add_to_info_log( "Memoria del servidor [Total: ".$memory_total."(100%) limite: 80%, actual " . $current_memory . "%]" );
		
		$products = $this->get_api('/downloadextendedcatalog');
		if(!$products) return null;
		if(is_string($products)){ return; }

		$res = saveResponse_inc('catalogExtend', $products);

		add_to_log("Actualizando de Catalogo extendido...");
		
		
		if ($res) {
			update_option( 'intcomex_last_value_catalog_extend', 0);
			wp_queue()->push( new load_ProductCatalogExtend() );
		}

		// $products = $this->get_api('/downloadextendedcatalog');
		// if(!$products) return null;
		// if(is_string($products)){ return; }

		// add_to_log("Actualizando de catalogo extendido...");
		// $longitud = count($products);
		// $contador = 0;
		// foreach ($products as $key => $productAPI) {
		// 	$product = new WC_Product( wc_get_product_id_by_sku( $productAPI['localSku'] . "-$this->sku_termination") );
		// 	if( ! $product->exists() ){ continue; }
		// 	if( ! $product->get_meta('intcomex_product') ){ continue; }
		// 	if( $this->descargar_imagen_principal and $product->get_image_id() ){ continue; }
		// 	if( ! $this->descargar_imagen_principal and $product->get_meta("_uyond_product_img_url") ){ continue; }
		// 	$contador++;
		// 	wp_queue()->push( new ProductCatalogExtended($productAPI, $this->sku_termination, $key, $this->descargar_imagen_principal ) );
		// }
		// wp_queue()->push( new PrintProgress("Se ha actualizado el catalogo extendido", $contador) );
		 
	}

	//Retorna un array de categorias
	function get_categories_api() {
		$products =  $this->get_api('/getcatalog', array('locale'=> 'es'));
		if(is_string($products)){ return; }
		$categories = array();
		foreach ($products as $key => $value) {
			$categoria = $value['Category'];

			//se verifica que tenga subcategoria
			if( ! isset( $categoria['Subcategories'] ) ) continue;

			//se obtiene la primer subcategoria
			$PRIMER_SUBCATEGORIA = 0;
			$subcategoria = $categoria['Subcategories'][$PRIMER_SUBCATEGORIA];

			if( ! isset( $subcategoria['CategoryId'] ) ) continue;
			
			$cat = array(
				'id' => $subcategoria['CategoryId'],
				'name' => $categoria['Description'] .  ' / ' .  $subcategoria['Description']
			);
			
			//Se verifica si ya existe en el array de lo contrario se agrega
			if( ! in_array($cat, $categories) ) array_push($categories, $cat);
		}
		return $categories;
 	}
	
	function generateOrder($order_id){
		$customer_order = new WC_Order($order_id);
		$objectToSend = new stdClass();
		$objectToSend->CustomerOrderNumber = $order_id;
		
		$items = array();
		$itemsString = "";
		$countProductESD = 0;
		foreach ($customer_order->get_items() as $key => $item) {
			$product   = $item->get_product();
			if(!$product->get_meta('intcomex_product')){ continue; }

			if( $product->is_virtual() ){ 
				$countProductESD++; 
			}
		
			$itemSave = new stdClass();
			$itemSave->sku = str_replace("-$this->sku_termination", '', $product->get_sku());
			$itemSave->Quantity = $item->get_quantity();
			$itemsString .= $product->get_sku() . "\n";
			array_push($items, $itemSave);
		}
		$objectToSend->Items = $items;
		if(count($items)>0){
			$result = $this->post_api('/placeorder', $objectToSend);
			if(is_array($result)){
				$customer_order->update_meta_data( 'OrderNumberIntcomex', $result['OrderNumber']);
				
				$customer_order->update_meta_data( 'countESDproductIntcomex', $countProductESD);
				
				$customer_order->save();
				$customer_order->add_order_note('Tienes un periodo de 48 horas para confirmar la orden de lo contrario se cancelará');
				$customer_order->add_order_note("Productos solicitados a intcomex: \n $itemsString");
				$customer_order->add_order_note('OrderNumber Intcomex: ' . $result['OrderNumber']);

			}else{
				$customer_order->add_order_note('Ha ocurrido un error, ' . $result);
			}
		}
		
		return $order_id;
	}

	function liberateOrder($order_id){
		$customer_order = new WC_Order($order_id);
		
		$count = get_post_meta($order_id, 'countESDproductIntcomex', true);
		if($count > 0){
			$objectToSend = new stdClass();
			$objectToSend->OrderNumber = get_post_meta($order_id, 'OrderNumberIntcomex', true);
			$result = $this->post_api('/generatetokens', $objectToSend);
			if(is_array($result)){
				$customer_order->update_meta_data( 'TokensESD', json_encode($result));
				$customer_order->save();
			}
		}

		if(get_post_meta($order_id, 'OrderNumberIntcomex', true) and $count != count($customer_order->get_items()) ){
			$objectToSend = new stdClass();
			$objectToSend->OrderNumber = get_post_meta($order_id, 'OrderNumberIntcomex', true);
			$result = $this->post_api('/releaseorder', $objectToSend);

			if(is_array($result) and isset($result['OrderNumber'])){
				$customer_order->add_order_note('Se ha confirmado la orden ' . $result['OrderNumber']);
			}else if(!is_array($result)){
				$customer_order->add_order_note('Ha ocurrido un error, ' . $result);
			}else{
				$customer_order->add_order_note('Ha ocurrido un error critico');
			}
		}
		
		return $order_id;
	}

	function cancelateOrder($order_id){

		if(get_post_meta($order_id, 'OrderNumberIntcomex', true)){
			$customer_order = new WC_Order($order_id);
			$objectToSend = new stdClass();
			$objectToSend->OrderNumber = get_post_meta($order_id, 'OrderNumberIntcomex', true);
			$result = $this->post_api('/cancelorder', $objectToSend);
			if(is_array($result)){
				$customer_order->add_order_note('Se ha cancelado la orden ' . $result['OrderNumber']);
			}else{
				$customer_order->add_order_note('Ha ocurrido un error, ' . $result);
			}
		}
		
		return $order_id;
	}
	
	function validate_license(){
		validateLicense(true);
	}

	protected function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			$memory_limit = '256M';
		}

		if ( ! $memory_limit || - 1 == $memory_limit ) {
			// Unlimited, set to 1GB
			$memory_limit = '1000M';
		}

		return intval( $memory_limit ) * 1024 * 1024;
	}

}
endif; 
