<?php

/**
 * Retrieves an image from a URL and uploads it using ld_handle_upload_from_path. See that function for more details.
 *
 * Note: This function should also work for local file paths as well, but the implementation is slightly different than ld_handle_upload_from_path.
 *
 * @param $image_url
 * @param int $attach_to_post
 * @param bool|true $add_to_media
 * @return array|bool
 */
function ksa_upload_from_url( $image_url, $filenameToSave =  null ,$attach_to_post = 0, $add_to_media = true ) {
	
	$remote_image = fopen($image_url, 'r');
	
	if ( !$remote_image ) return false;
	
	$meta = stream_get_meta_data( $remote_image );
	
	$image_meta = false;
	$image_filetype = false;
	
	if ( $meta && !empty($meta['wrapper_data']) ) {
		foreach( $meta['wrapper_data'] as $v ) {
			if ( preg_match('/Content\-Type: ?((image)\/?(jpe?g|png|gif|bmp))/i', $v, $matches ) ) {
				$image_meta = $matches[1];
				$image_filetype = $matches[3];
			}
		}
	}
	
	// Resource did not provide an image.
	if ( !$image_meta ) return false;
	
	$v = basename($image_url);
	if ( $v && strlen($v) > 6 ) {
		// Create a filename from the URL's file, if it is long enough
		$path = $v;
	}else{
		// Short filenames should use the path from the URL (not domain)
		$url_parsed = parse_url( $image_url );
		$path = isset($url_parsed['path']) ? $url_parsed['path'] : $image_url;
	}
	
	$path = preg_replace('/(https?:|\/|www\.|\.[a-zA-Z]{2,4}$)/i', '', $path );
	$filename_no_ext = sanitize_title_with_dashes( $path, '', 'save' );
	
	$extension = $image_filetype;
	$filename = $filename_no_ext . "." . $extension;
	
	// Simulate uploading a file through $_FILES. We need a temporary file for this.
	$stream_content = stream_get_contents( $remote_image );
	
	$tmp = tmpfile();
	$tmp_path = stream_get_meta_data( $tmp )['uri'];
	fwrite( $tmp, $stream_content );
	fseek( $tmp, 0 ); // If we don't do this, WordPress thinks the file is empty
	$fake_FILE = array(
		'name'     => $filename,
		'type'     => 'image/' . $extension,
		'tmp_name' => $tmp_path,
		'error'    => UPLOAD_ERR_OK,
		'size'     => strlen( $stream_content ),
	);
	
	// Trick is_uploaded_file() by adding it to the superglobal
	$_FILES[basename( $tmp_path )] = $fake_FILE;
	
	// For wp_handle_upload to work:
	include_once ABSPATH . 'wp-admin/includes/media.php';
	include_once ABSPATH . 'wp-admin/includes/file.php';
	include_once ABSPATH . 'wp-admin/includes/image.php';
	
	$result = wp_handle_upload( $fake_FILE, array(
		'test_form' => false,
		'action'    => 'local',
	) );
	
	fclose( $tmp ); // Close tmp file
	@unlink( $tmp_path ); // Delete the tmp file. Closing it should also delete it, so hide any warnings with @
	unset( $_FILES[basename( $tmp_path )] ); // Clean up our $_FILES mess.
	
	fclose( $remote_image ); // Close the opened image resource
	
	$result['attachment_id'] = 0;
	if ( empty( $result['error'] ) && $add_to_media ) {
		$args = array(
			'post_title'     => is_null($filenameToSave)? $filename_no_ext : $filenameToSave,
			'post_content'   => '',
			'post_status'    => 'publish',
			'post_mime_type' => $result['type'],
		);
		
		$result['attachment_id'] = wp_insert_attachment( $args, $result['file'], $attach_to_post );
		
		$attach_data = wp_generate_attachment_metadata( $result['attachment_id'], $result['file'] );
		wp_update_attachment_metadata( $result['attachment_id'], $attach_data );
		
		if ( is_wp_error( $result['attachment_id'] ) ) {
			$result['attachment_id'] = 0;
		}
	}
	
	
	return $result;
}

function searchElementForProperty($array, $property, $value){
		$found_key = array_search($value, array_column($array, $property));
		if( ! is_numeric($found_key) ) return null;
		return $array[array_keys($array)[$found_key]];
}

function add_to_log($message){
	$log_intcomex = get_option('log_intcomex');
	
	$log_intcomex .= '[' . get_time_formated() .  '] ' . $message . "\n";
	
	if(strlen($log_intcomex)>pow(2,20)){
		$log_intcomex = substr($log_intcomex, -100000, -1) . "\n";
	}
	
	update_option( 'log_intcomex' ,  $log_intcomex);
}

function add_to_error_log($message){
	$log_intcomex = get_option('log_intcomex');
	
	$log_intcomex .= "ERROR [" . get_time_formated() .  "] " . $message . "\n";
	if(strlen($log_intcomex)>pow(2,20)){
		$log_intcomex = substr($log_intcomex, -100000, -1) . "\n";
	}
	update_option( 'log_intcomex' ,  $log_intcomex);
}

function add_to_problem_log($message){
	$log_intcomex = get_option('log_intcomex');
	
	$log_intcomex .= "PROBLEMA [" . get_time_formated() .  "] " . $message . "\n";
	if(strlen($log_intcomex)>pow(2,20)){
		$log_intcomex = substr($log_intcomex, -100000, -1) . "\n";
	}
	update_option( 'log_intcomex' ,  $log_intcomex);
}

function add_to_delete_log($message){
	$log_intcomex = get_option('log_intcomex');
	
	$log_intcomex .= "BORRADO [" . get_time_formated() .  "] " . $message . "\n";
	if(strlen($log_intcomex)>pow(2,20)){
		$log_intcomex = substr($log_intcomex, -100000, -1) . "\n";
	}
	update_option( 'log_intcomex' ,  $log_intcomex);
}

function add_to_info_log($message){
	$log_intcomex = get_option('log_intcomex');
	
	$log_intcomex .= "INFO [" . get_time_formated() .  "] " . $message . "\n";
	if(strlen($log_intcomex)>pow(2,20)){
		$log_intcomex = substr($log_intcomex, -100000, -1) . "\n";
	}
	update_option( 'log_intcomex' ,  $log_intcomex);
}

function getPorcentajeSwitchPrice($price, $iva = 0, $categoriaid, $tipo_cambio){	
	$index_default = 0;
	$prices_saved = get_option('ranges_prices_categories_intcomex');
	//se busca por el id
	$prices = searchElementForProperty($prices_saved , 'id', $categoriaid);
	if( ! $prices ) $prices = $prices_saved[0];
	$ranges = $prices['values'];
	$columnas =  array();
	foreach ($ranges as $key => $value) {
		if ($price >= floatval($value[0]) and $price <= floatval($value[1])) {
			$precioBase = $price*$tipo_cambio;
			$ganancia = $precioBase*((double)$value[2]/100);
			$impuesto = $ganancia*((double)($iva ?? 0)/100);
			array_push($columnas, strval($prices['name'])	);
			array_push($columnas, strval($price)			);
			array_push($columnas, strval($value[2]) . "%"	);
			array_push($columnas, strval($impuesto ?? 0)	);
			array_push($columnas, strval($value[0])			);
			array_push($columnas, strval($value[1])			);

			$price = $precioBase +  $ganancia + $impuesto;
			array_push($columnas, strval($price)			);
			break;
		}
	}
	$price = round($price, 5);
	return [$price, $columnas];
}


add_filter( 'cron_schedules', 'cron_add_Monthly_intcomex' );
function cron_add_Monthly_intcomex( $schedules ) {
   // Adds once weekly to the existing schedules.
   $schedules['cron_catalogo_extendido'] = array(
       'interval' => DIAS_CATALOGO_EXTENDIDO * DAY_IN_SECONDS,
       'display' => __( 'Cada ' . DIAS_CATALOGO_EXTENDIDO . ' Dias' )
   );
   return $schedules;
}

add_filter( 'cron_schedules', 'cron_add_catalogo_intcomex' );
function cron_add_catalogo_intcomex( $schedules ) {
   // Adds once weekly to the existing schedules.
   $schedules['cron_catalogo'] = array(
       'interval' => HORAS_INVENTARIO_PRECIO * HOUR_IN_SECONDS,
       'display' => __( 'Cada ' . HORAS_INVENTARIO_PRECIO . ' horas' )
   );
   return $schedules;
}

add_filter( 'cron_schedules', 'cron_catalogo_intcomex' );
function cron_catalogo_intcomex( $schedules ) {
    $schedules['cron_catalogo_intcomex'] = array(
        'interval' => HORAS_INVENTARIO_PRECIO * HOUR_IN_SECONDS,
        'display' => __('Cada ' . HORAS_INVENTARIO_PRECIO . ' horas')
    );
    return $schedules;
}

function activateTimers(){
	if (! wp_next_scheduled ( 'precios_inventario_intcomex')) {
		$dtz = new DateTimeZone("America/Guatemala");
		$dt = new DateTime("tomorrow 01:00 am", $dtz);
		wp_schedule_event( $dt->getTimestamp(), 'cron_catalogo_intcomex', 'precios_inventario_intcomex');
		add_to_info_log("Timers [Inventario] Activado ");
	}
	
	if (! wp_next_scheduled ( 'precios_intcomex')) {
		$dtz = new DateTimeZone("America/Guatemala");
		$dt = new DateTime("tomorrow 03:00 am", $dtz);
		wp_schedule_event( $dt->getTimestamp(), 'cron_catalogo_intcomex', 'precios_intcomex');
		add_to_info_log("Timers [Precios] Activado ");
	}

	if (! wp_next_scheduled ( 'catalogo_event')) {
		$dtz = new DateTimeZone("America/Guatemala");
		$dt = new DateTime("tomorrow 02:00 am", $dtz);
		wp_schedule_event( $dt->getTimestamp() , 'cron_catalogo', 'catalogo_event');
		add_to_info_log("Timers [Catalogo] Activado");
	}

	if (! wp_next_scheduled ( 'catalogo_extendido_event')) {
		$dtz = new DateTimeZone("America/Guatemala");
		$dt = new DateTime("tomorrow 06:00 am", $dtz);
		wp_schedule_event( $dt->getTimestamp(), 'cron_catalogo_extendido', 'catalogo_extendido_event');
		add_to_info_log("Timers [Catalogo Extendido] Activado ");
	}
}


function get_time_formated(){
	$dtz = new DateTimeZone("America/Guatemala");
	$dt = new DateTime("now", $dtz);
	$currentTime = $dt->format("d/m") . " " . $dt->format("H:i");
	return $currentTime;
}

function arrayToString($valores){
	$ignoreProperties = array('Descripcion', 'mpn', 'localSku', 'DescripcionFabrica', 'DescripcionMarca', 'CategoriaCompleta', 'Imagenes', 'centralRecno');
    $descriptionProduct = "";
    foreach ($valores as $key => $value) {
		if(!in_array($key, $ignoreProperties)){
		    
		    if (is_array($value)) {
		        $descriptionProduct .= $key .  ": \n" .arrayToString($value);
		    }else{
		           
	            if( str_contains( $key , '/') ){ 
                    $separado = explode('/', $key, 2); // Divide la cadena en dos partes usando el primer "/"
                    $resultado = trim($separado[1]); // Elimina los espacios en blanco alrededor de la segunda parte
                    $key = $resultado;
            	}
		        
		        $descriptionProduct .= $key . ': ' . $value . "\n";    
		    }
			
		}
	}
	return $descriptionProduct;
};

use WP_Queue\Queue;
use WP_Queue\QueueManager;

if ( ! function_exists( 'wp_queue' ) ) {
	/**
	 * Return Queue instance.
	 *
	 * @param string $connection
	 *
	 * @return Queue
	 */
	function wp_queue( $connection = '' ) {
		if( empty( $connection ) ) {
			$connection = apply_filters( 'wp_queue_default_connection', 'database' );
		}
		
		return QueueManager::resolve( $connection );
	}
}

if ( ! function_exists( 'wp_queue_install_tables' ) ) {
	/**
	 * Install database tables
	 */
	function wp_queue_install_tables() {
		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$wpdb->hide_errors();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}queue_jobs (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				job longtext NOT NULL,
				attempts tinyint(3) NOT NULL DEFAULT 0,
				reserved_at datetime DEFAULT NULL,
				available_at datetime NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id)
				) $charset_collate;";

		dbDelta( $sql );

		$sql = "CREATE TABLE {$wpdb->prefix}queue_failures (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				job longtext NOT NULL,
				error text DEFAULT NULL,
				failed_at datetime NOT NULL,
				PRIMARY KEY  (id)
				) $charset_collate;";

		dbDelta( $sql );
	}
}



/** Se utiliza para obtener las configuraciones fuera de la clase de WC_IntcomexWebService_Integration */
if ( ! function_exists( 'get_option_db_intcomex' ) ) {
	function get_option_db_intcomex($id) {
		$data = get_option('woocommerce_intcomex-plugin_settings', null);	
		return $data[$id];
	}
	
}
 
// Obtiene la terminacion del sku
if ( ! function_exists( 'get_termination_sku_intcomex' ) ) {
	function get_termination_sku_intcomex() {
		$termination = get_option_db_intcomex('sku_termination');
		return '-' . $termination;
	}
	
}

// regitra u obtiene las DB
if ( ! function_exists( 'set_news_locations_intcomex' ) ) {
	function set_news_locations_intcomex($locations) {

		$locations_inc = get_option('locations_intcomex_globals', array() );

		foreach ($locations as $key => $value) {

			if( isset($value['Id']) and ! in_array( $value['Id'], $locations_inc) ){
				array_push($locations_inc, $value['Id']);
			}
			
		}

		update_option( 'locations_intcomex_globals', $locations_inc );
		
		return $locations_inc ?? array();
	}
	
}

if ( ! function_exists( 'get_locations_intcomex' ) ) {
	function get_locations_intcomex() {
		$locations_inc = get_option('locations_intcomex_globals', array() );
		return $locations_inc ?? array();
	}
	
}


if ( ! function_exists( 'exportJsonToCsv' ) ) {

	//funcion encargada de exportar un archivo JSON a CSV para analisis
	//						el json ya debe de estar decoded
	function exportJsonToCsv($name, $jsondata, $columns, $columnsTranformToString)
	{
		
		// se arma el nombre del archivo
		$csv = "$name.csv";
		
		// se abre el archivo
		$file_pointer = fopen($csv, 'w');

		//se guardan las columnas
		fputcsv($file_pointer, $columns); 

		foreach($jsondata as $i){	
			// se escribe el archivo en formato csv

			foreach ($i as $key => $value) {
				$i[$key] = json_encode($value);
			}
			
			fputcsv($file_pointer, $i);
		}
		
		// se cierra el archivo
		fclose($file_pointer);
	}	
}

// Agrega el widget en la pantalla de edición de productos
function agregar_widget_edicion_producto() {
	global $post;

	$product = new WC_Product( $post->ID );	
	$locations = $product->get_meta( 'Locations_stock_intcomex' );
	
	if( isset($locations) && is_array( $locations ) && count($locations) > 0 ){
		?> 
		<?php foreach ($locations as $key => $value) { ?> 
			<div class="inventory_sold_individually options_group show_if_simple show_if_variable" style="">
				<p class="form-field _sold_individually_field show_if_simple show_if_variable" style="">
					<label for="_sold_individually">Intcomex bodega -  <?php echo $value["Id"]; ?></label>
						<input type="number" class="checkbox" disabled value="<?php echo $value["Stock"]; ?>">

				</p>
				
			</div>
			<?php } ?>
		<?php

		// '<div><p>' . $locations[0]['Id'] . '</p></div>';
	}
}
add_action('woocommerce_product_options_inventory_product_data', 'agregar_widget_edicion_producto');


function obtenerPedidoPorBodega($product, $SKU, $quantity){
	$locations = $product->get_meta('Locations_stock_intcomex');
	$selectedLocation = get_option( 'selected_location_intcomex' );

	$body = array();

	//primero se obtiene se realiza con la bodega principal
	if (($index = array_search($selectedLocation , array_column($locations, "Id") )) !== FALSE){
		$item = new stdClass();
		$bodega = $locations[$index];
		
		$item->LocationId = $bodega['Id'];
		$item->Sku = $SKU;
		
		if($quantity <= $bodega['Stock'] ){
			$item->Quantity = $quantity;
			
		}else{
			$item->Quantity = $bodega['Stock'];
		}
		
		$quantity =  $quantity - $bodega['Stock'];
		array_push( $body,  $item);
	}

	//si la cantidad aun no se ha abastecido recorre todas las bodegas
	if($quantity > 0){
		foreach($locations as $key => $valueLocation){

			//si el indice concide con el de la bodega principal no lo usa o
			//la cantidad es menor o igual a 0
			if($key === $index or $quantity <= 0  ){  continue; };
			
			$item = new stdClass();
			$item->LocationId = $valueLocation['Id'];
			$item->Sku = $SKU;
			
			if($quantity <= $valueLocation['Stock'] ){
				$item->Quantity = $quantity;
			}else{
				$item->Quantity = $valueLocation['Stock'];
			}
			
			$quantity =  $quantity - $valueLocation['Stock'];
			array_push( $body,  $item);
		}    
	}

	return $body;
}

function saveResponse_inc($type, $data) {
	$json_file_path = __DIR__ . "/$type.json";

	$json_data = json_encode($data);

	if (file_put_contents($json_file_path, $json_data)) {
		add_to_log( "Archivo $type creado correctamente");
	} else {
		$last_error = error_get_last();
		if ($last_error) {
			add_to_error_log( 'Hubo un error al guardar el archivo: ' . $last_error['message'] );
		} else {
			add_to_error_log('Hubo un error al guardar el archivo JSON.');
		}
		return false;
	}
	return true;
}

function obtenerDatosDesdeJSON($type) {
	$archivo = __DIR__ . "/$type.json";
    $json_content = file_get_contents($archivo);
    
    if ($json_content !== false) {
        $decoded_data = json_decode($json_content, true);
        
        if ($decoded_data !== null) {
            return $decoded_data;
        } else {
            return false; // Error al decodificar el JSON
        }
    } else {
        $last_error = error_get_last();
		add_to_error_log( $last_error['message'] );
        return false; 
    }
}


function get_tipo_cambio() {
	$options = get_option('woocommerce_intcomex-plugin_settings', null);
	
	$moneda = $options['moneda'];
	$puntos_aumentar = $options['puntos_aumentar'];
	$api_key_exchange = $options['api_key_exchange'];

	$urlToRequest = "https://v6.exchangerate-api.com/v6/$api_key_exchange/latest/USD";

	$response = wp_remote_get( $urlToRequest, array(
			'method'      => 'GET',
			'timeout'     => 1000
		)
	);


	$codeResponse = wp_remote_retrieve_response_code($response);
	$body = json_decode($response['body'], true);

	if(!$body){ return 0;}

	if( isset($body['conversion_rates']) and ! isset($body['conversion_rates'][$moneda]) ){
		add_to_problem_log("Error no existe esa moneda en la API, monedas disponibles");
		add_to_log(print_r( $body['conversion_rates'] , true));
	}

	$options['tipo_cambio'] = $body['conversion_rates'][$moneda] + $puntos_aumentar; 
	update_option('woocommerce_intcomex-plugin_settings', $options);
	return $options['tipo_cambio'];
}

function create_brand( $brand ) {
	$term = term_exists($brand['slug'], 'pwb-brand');
	if(is_null($term)){
		$term = wp_insert_term(
		$brand['name'],
		'pwb-brand',
		array(
			'slug'        => $brand['slug'],
			'description' => '',
			)
		);	
	}
	
	return $term;
	
}


function add_brands( $brands, $product ) {
	
	if(! is_null($product->get_id())){
		$result = wp_set_post_terms( $product->get_id(), $brands, 'pwb-brand' );	
	}
	
	
}

/*Funcion que obtiene todos los productos de intcomex */
function getAllProductsIntcomex(){
	global $wpdb;
	$table_name = $wpdb->prefix . 'postmeta'; 
	$count_query = "select * from $table_name where meta_key = 'intcomex_product' ";
	$num = $wpdb->get_results($count_query);
	return $num;
}