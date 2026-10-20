<?php

function log_intcomex_page() {

	add_menu_page(
		__( 'Intcomex Log', 'my-textdomain' ),
		__( 'Intcomex Log', 'my-textdomain' ),
		'manage_options',
		'page-intcomex',
		'log_intcomex_page_contents', 
		'dashicons-welcome-view-site', 
		3
	);
}


add_action( 'admin_menu', 'log_intcomex_page' );


function log_intcomex_page_contents() {
	global $wpdb;
	?> 
		<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
		<h1>LOG INTCOMEX <button class="button button-primary" id="clear_log" onclick="clear_log()">Limpiar Consola</button> &nbsp; <button class="button button-primary" id="verificar_licencia" onclick="verificar_license_intcomex()">Verificar Licencia</button></h1> 
		<div style="display: flex; align-items: center; justify-content: space-between;">
			<h2 style="display: flex; align-items: center;">
				Acciones en cola: 
				<?php 
					$table_name = $wpdb->prefix . 'queue_jobs'; 
					$count_query = "select count(*) from $table_name";
					$num = $wpdb->get_var($count_query);
					echo $num;
				?>
				&nbsp;
				<button class="button button-danger" onclick="clear_queue()">Limpiar Cola</button>
			</h2>
			<div style="display: flex; align-items: center;">
					powered by <a href="https://techwebgt.com/" target="_blank">
							<img style="height: 30px;" src="https://techwebgt.com/wp-content/uploads/2021/04/full-logo.png" alt="Techweb">
									</a>
			</div>
		</div>
		<div class="box" style="margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">	
			<div>
				Filtrar por Error
				<select onchange="filterLog(this)" id="select_filter">
					<option value="" >TODOS</option>
					<option value="INFO">Informativos</option>
					<option value="PROBLEMA">Problemas</option>
					<option value="BORRADO">Borrados</option>
					<option value="ERROR">Errores</option>
				</select>
			</div>
			<div>
				Descargar ultimo: 
				<select onchange="downloadLog()" id="select_log">
					<option disabled selected value>Selecciona una opcion</option>
					<option value="catalog">Catalogo</option>
					<option value="prices">Precios</option>
					<option value="inventory">Inventario</option>
					<option value="catalogExtend">Extendido</option>
					
				</select>
			</div>
		</div>
        <div id="console" class="console" style="position: relative;">
			<div class="loader_console" id="loader_console">
				<div class="loaderRectangle">
					<div></div>
					<div></div>
					<div></div>
					<div></div>
					<div></div>
				</div>
				<div style="margin-top: 10px;">
				<h4 style="color:white;">Esto puede llevar unos minutos</h4>
				</div>
			</div>
				<ul id="messages">
					<?php 
						$content = get_option('log_intcomex');
						$arrayLog = explode("\n", $content);
						foreach ($arrayLog as $key => $value) {
							$class = strrpos($value, "ERROR") === false ? 'lineConsole' : 'lineConsole erroLine';
							$class = strrpos($value, "INFO") === false ? $class : 'lineConsole infoLine';
							$class = strrpos($value, "PROBLEMA") === false ? $class : 'lineConsole warningLine';
							$class = strrpos($value, "BORRADO") === false ? $class : 'lineConsole deleteLine';
							//$class = (strrpos($value, "ERROR") === false and strrpos($value, "INFO") === false)? 'lineConsole' : (strrpos($value, "INFO") === false? 'lineConsole erroLine' : 'lineConsole infoLine');
							?>		
								<li class="<?php echo $class; ?>"><?php echo $value; ?></li>
							<?php
						}
					?>
			 	</ul>
        </div>
		<h1>Configuraciones</h1>		
		<table class="form-table">
		  <tbody>
			<tr>
			  <th class="titledesc">
				<label>Crear Categorias Automaticamente y asignarla a los productos</label>
			  </th>
			  <td class="forminp">
				<input type="checkbox" class="input-text regular-input" id="create_category_input"
					<?php 
	 					if(get_option('create_category') == 'true') echo "checked='checked'"
					?>
			    >
			  </td>
			</tr>
			<tr>
			  <th class="titledesc">
				<label>Descargar solo los productos de las categorias definidas en los precios</label>
			  </th>
			  <td class="forminp">
				<input type="checkbox" class="input-text regular-input" id="download_categories_saved_prices"
					<?php 
	 					if(get_option('download_categories_saved_prices') == 'true') echo "checked='checked'"
					?>
			    >
			  </td>
			</tr>
		  </tbody>
		</table>
		<section style="position: relative;">
			<div class="loader_console" id="loader_categories">
					<div class="loaderRectangle">
						<div></div>
						<div></div>
						<div></div>
						<div></div>
						<div></div>
					</div>
					<h3 style="margin-top: 10px; color: white;">
						Esto puede llevar unos minutos...
					</h3>
			</div>
			
			<?php 
				if( ! get_option( 'categories_intcomex_values')){
					?>		
						<div class="loader_console" id="load_categorias" style="display:flex;">
							<h3 style="color:white;">Se necesita descargar las categorias</h3>
							<button class="button button-primary" onclick="get_categories_intcomex()">Descargar Categorias</button>
						</div>
					<?php 
				}
			?>

			<h2>
				Establecer porcentajes de ganacia por rango de precios
				<button class="button button-primary" onclick="create_category()">Agregar Categoria</button>
				<button class="button button-danger" onclick="delete_category()">Borrar Categoria</button>
				<button class="button button-primary" id="saveRanges" onclick="save_ranges()">Guardar</button>
			</h2>
			<div style="display: flex;">
				<div style="display: flex; flex-direction: column; width: 200px;">
					<!-- <div class="loader_console" id="save_ranges_panel">
						<button class="button button-primary" id="saveRanges" onclick="save_ranges()">Guardar</button>
					</div> -->
					<h3>Categoria:</h3>
					<select name="enviar mensaje" size="10" id="select_categorie" onchange="select_categorie_price(this)">
						<option value="default" selected>General</option>
						<?php
							$valores = get_option('ranges_prices_categories_intcomex');
							array_shift($valores);
							foreach ($valores as $key => $value) {
								?>
									<option value="<?php echo $value['id'] ?>"><?php echo $value['name'] ?></option>
								<?php
							}
						?>
						
					</select>
				</div>
				<div>
					<br>
					<table class="table-ranges" id="table_ranges">
						<thead>
							<th>No.</th>
							<th>De</th>
							<th>Hasta</th>
							<th> &nbsp; </th>
							<th>Cantidad (Porcentaje %)</th>
							<th>Accion</th>
						</thead>
						<tbody>
							<?php
								$valores = get_option('ranges_prices_categories_intcomex')[0]['values'];
								foreach ($valores as $key => $value) {
									?>
										<tr>
											<td><?php echo 0 ?></td>
											<td><input type="number" value="<?php echo $value[0] ?>"></td>
											<td><input type="number" value="<?php echo $value[1] ?>"></td>
											<td>&nbsp; &nbsp;</td>
											<td><input type="number" value="<?php echo $value[2] ?>"></td>
											<td>
												<button class="button oculto" onclick="delete_row(<?php echo $key ?>)">Remover</button> 
											</td>
										</tr>
									<?php
								}
								
							?>
							<tr>
								<td colspan="5">
									<div class="flex-center">
										<button class="button button-primary" id="saveRanges" onclick="agregaRange()">Agrear rango</button>
									</div>
									
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<button class="button button-danger" id="deleteAllCategories" onclick="deleteAllCategories()">Reiniciar categorias</button>
			<button class="button button-danger" id="update_brands" onclick="update_brands()">Actualizar Marcas</button>
			<br>
			
		</section>
		<h1>
			Acciones
		</h1>
		<section> 
			<h2>Actualizar precios e inventario (se actualiza cada hora)</h2>
			Encargado de actualizar los precios segun el rango de precios y actualiza el inventario 
			<button class="button button-primary" onclick="update_hourly_event('precios_inventario_intcomex')" id="btn_PriInv">Actualizar inventario</button>
			<button class="button button-primary" onclick="update_hourly_event('precios_intcomex')" id="btn_Pri">Actualizar precios</button>
		</section>
		<section>
			<h2> Actualizar Catalogo (se actualiza cada día a media noche)</h2>
			
			Encargado de crear los productos y actualizar sus propiedades como las categorías
			<button class="button button-primary" onclick="update_daily_event()" id="btn_Cata">Actualizar Catalogo</button>
		</section>
		<section>
			<h2>Actualizar Catalogo Extendido (se actualiza cada mes a la 1:00 am)</h2>
			
			Encargado de actualizar la descripción detallada del producto e imagenes
			<button class="button button-primary" onclick="update_monthly_event()" id="btn_Cataext">Actualizar Catalogo Extendido</button>
		</section>
		<section>
			<h2>Otras opciones</h2>
			<button class="button button-primary" onclick="delete_products_out_stock_inc()" id="btn_delete_products">
				Eliminar Productos con inventario 0
			</button>
		</section>
		
        <script>
            var messageBody = document.querySelector('#console');
            messageBody.scrollTop = messageBody.scrollHeight - messageBody.clientHeight;
        </script>
	<?php

}


//Cargar SCRIPTS
function log_intcomex_scripts($hook) {

	if( $hook == 'toplevel_page_page-intcomex') {
		wp_enqueue_style( 'intcomex-plugin', plugins_url( 'page/css/log.css', dirname( __FILE__ ) ) );
    	wp_register_script( 'intcomex-plugin', plugins_url( 'page/js/script.js', dirname( __FILE__ ) ) );
	}
	
    //wp_register_script( 'intcomex-settings', plugins_url( 'page/js/settings.js', dirname( __FILE__ ) ) );
}
add_action( 'admin_enqueue_scripts', 'log_intcomex_scripts' );    
    

function load_intcomex_scripts( $hook ) {
    // Load only on ?page=page-log
    if( $hook == 'toplevel_page_page-intcomex' ) {
			wp_enqueue_script("jquery");
            wp_enqueue_style( 'intcomex-plugin' );
    		wp_enqueue_script( 'intcomex-plugin' );
    }
    // Load style & scripts.

}

add_action( 'admin_enqueue_scripts', 'load_intcomex_scripts' );

///actions SCRIPT


add_action( 'wp_ajax_my_action', 'my_action' );

function my_action() {
	global $wpdb; // this is how you get access to the database
	
	//Obtener valores de variables
	//$whatever = intval( $_POST['whatever'] );

	//$whatever += 10;
    //echo $whatever;

	wp_die(); // this is required to terminate immediately and return a proper response
}

add_action( 'wp_ajax_saveRanges', 'saveRanges' );
function saveRanges() {
	global $wpdb; // this is how you get access to the database
	
	//Obtener valores de variables
	$dataRanges = $_POST['dataRanges'];
	update_option( 'ranges_prices_categories_intcomex' ,  $dataRanges);
	
	
	$create_category = $_POST['create_category'];
	update_option( 'create_category' ,  $create_category);

	$download_categories_saved_prices = $_POST['download_categories_saved_prices'];
	update_option( 'download_categories_saved_prices', $download_categories_saved_prices);

	if( isset( $_POST['bodega_seleccionada'] ) ){
		update_option( 'selected_location_intcomex', $_POST['bodega_seleccionada']);
	}
	
	echo json_encode($dataRanges);
	wp_die(); // this is required to terminate immediately and return a proper response
}


add_action( 'wp_ajax_clear_log', 'clear_log' );
function clear_log() {
	global $wpdb; // this is how you get access to the database

	update_option( 'log_intcomex' ,  '');
	
	wp_die(); // this is required to terminate immediately and return a proper response
}

add_action( 'wp_ajax_do_hourly_event', 'do_hourly_event' );
function do_hourly_event(){
	$event = $_POST['event'];
	do_action($event);
	wp_die();
}
add_action( 'wp_ajax_do_daily_event', 'do_daily_event' );
function do_daily_event(){
	do_action('catalogo_event');
	wp_die();
}

add_action( 'wp_ajax_do_monthly_event', 'do_monthly_event' );
function do_monthly_event(){
	do_action('catalogo_extendido_event');
	wp_die();
}


add_action( 'wp_ajax_do_license_verify' , 'do_license_verify');
function do_license_verify(){
	validateLicense(true);
	wp_die();
}


add_action( 'wp_ajax_clear_queue_intcomex' , 'clear_queue_intcomex');
function clear_queue_intcomex(){
	global $wpdb;
	$table = $wpdb->prefix . 'queue_jobs';
	$delete = $wpdb->query("TRUNCATE TABLE $table");
	add_to_info_log("Se ha vaciado los procesos en cola correctamente");
	wp_die();
}

add_action( 'wp_ajax_get_categories_intcomex' , 'get_categories_intcomex');
function get_categories_intcomex(){
	$intcomex_Integration = new WC_IntcomexWebService_Integration();
	$categories = $intcomex_Integration->get_categories_api();
	update_option( 'categories_intcomex_values', $categories);
	echo json_encode($categories);
	wp_die();
}



add_action( 'wp_ajax_get_values_categories' , 'get_values_categories');
function get_values_categories(){

	$objectToSend = new stdClass();
	
	$categories = get_option( 'categories_intcomex_values', null);
	$objectToSend->categories = $categories;

	$prices = get_option( 'ranges_prices_categories_intcomex', null);
	$objectToSend->prices = $prices;

	echo json_encode($objectToSend);
	wp_die();
}

add_action( 'wp_ajax_deleteAllCategories' , 'deleteAllCategories');
function deleteAllCategories(){

	// $prices = array(
	// 	array(
	// 		'id'=>'default',
	// 		'name' => 'General',
	// 		'values' => array(
	// 			0 => array(
	// 				0 => 0,
	// 				1 => 99999,
	// 				2 => 25
	// 			)
	// 		)
	// 	)	
	// );
	// update_option( 'ranges_prices_categories_intcomex' , $prices);
	// delete_option( 'categories_intcomex_values' );
	// wp_die();

	get_tipo_cambio();
	wp_die();
}

add_action( 'wp_ajax_update_brands' , 'update_brands');
function update_brands(){
	update_option( 'intcomex_last_value_brands', 0);
	wp_queue()->push( new load_brands() );
}

add_action( 'wp_ajax_get_path_intcomex_plugin', 'get_path_intcomex_plugin' );
function get_path_intcomex_plugin() {
	$site_url = get_option('siteurl');
	$plugin_dir_absolute = dirname(plugin_dir_path(__FILE__), 1);
	$plugin_dir_relative = str_replace(ABSPATH, '', $plugin_dir_absolute); // Quita la parte absoluta
	
	echo json_encode(
		[
			'path' => $site_url . '/' . $plugin_dir_relative
		]
	);
	wp_die();
}	

add_action( 'wp_ajax_delete_products_out_stock_inc', 'delete_products_out_stock_inc' );
function delete_products_out_stock_inc() {
	$id_products = getAllProductsIntcomex();
	$contador = 0;
	$sn_borrar = $_POST['sn_borrar'];

	foreach ($id_products as $key => $value) {

		if($value->meta_value != 1 ){ continue; }

		$product = new WC_Product($value->post_id);

		if ($product->get_stock_quantity() == 0) {
			if ($sn_borrar == 1) {
				$product->delete(true);
			}

			$contador++;
		}
	}
	
	echo json_encode(
		['message' => "Se van a borrar $contador productos", 'count'=> $contador]
	);
	wp_die();
}	
