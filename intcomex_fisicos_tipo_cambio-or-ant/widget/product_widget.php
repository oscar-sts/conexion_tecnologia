<?php

defined( 'ABSPATH' ) || exit;
class product_widget {

    protected static $instance;

    public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

    protected function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );

		// // show the product image in shop list.
		// add_filter( 'woocommerce_product_get_image', array( $this, 'get_image' ), 10, 6 );

		// // show the gallery images in single product page.
		// add_filter( 'woocommerce_single_product_image_thumbnail_html', array( $this, 'thumbnail_html' ), 10, 2 );
		// add_filter( 'wc_get_template', array( $this, 'get_template' ), 10, 5 );
	}

    public function add_meta_boxes() {
		add_meta_box(
			'intcomex_widget_product_info',
			'Propiedades de intcomex',
			array( $this, 'echo_intcomex_display_info' ),
			'product',
			'side',
			'default'
		);

		
	}

    public function echo_intcomex_display_info( $post ) {
        $this->widget_script();
		wp_nonce_field( 'intcomex_info_metabox_nonce', 'intcomex_info_nonce' );

		$info = $this->get_product_info_intcomex( $post->ID );

        if($info['intcomex_product']){
            $options = options_intcomex_plugin();
            $tipo_cambio = $options['tipo_cambio'];
            $impuesto = $options['impuesto'];
            $tipo_cambio = $options['tipo_cambio'];

            $categoria_id = $info['intcomex_product_category_id'];

            if( ! empty($info['price'])){ 
                list($result, $columnas)  = getPorcentajeSwitchPrice($info['price'], $impuesto, $categoria_id, $tipo_cambio);
            }
            
            ?>
                <fieldset>
                    Id de la categoria:
                    <b><?php echo $info['intcomex_product_category_id']  ?></b>
                </fieldset>
                <hr> 
                Desgloce de formula del precio:
                
                <fieldset>
                    Categoria aplicada: <b><?php echo $columnas[0] ?? ''  ?></b> <br>
                    Porcentaje aplicado: <b><?php echo $columnas[2] ?? ''  ?></b> <br>
                    Precio (Moneda original):<b><?php echo $info['original_price'] ?? '<no obtenido, ejecute nuevamente la actualización del precio>'  ?></b> <br> 
                    Impuesto: <b><?php echo $columnas[3]  ?? '' ?></b> <br>
                    Tipo de cambio: <b><?php echo $tipo_cambio ?? ''  ?></b> <br>
                    Rango menor: <b><?php echo $columnas[4]  ?? '' ?></b> <br>
                    Rango mayor: <b><?php echo $columnas[5]  ?? '' ?></b> <br>
                </fieldset>
                <hr>
                Información de actualización del producto
                <fieldset>
                    Creado: <b><?php echo $info['creacion_product']  ?></b><br>
                    Stock: <b><?php echo $info['actualizacion_stock']  ?></b><br>
                    precio: <b><?php echo $info['actualizacion_precio']  ?></b><br>
                    Info Basica: <b><?php echo $info['actualizacion_product']  ?></b><br>
                    Info ext.: <b><?php echo $info['actualizacion_extended']  ?></b><br>
                </fieldset>
                <hr> 
                
                <fieldset style="display: flex; align-items: center">
                    Información de la api &nbsp;
                    <button class="button tagadd" id="validate_exist_info_product" data-sku="<?php echo $info['sku']?>">Validar</button>    
                </fieldset>
                <fieldset id="info_existencia_inc">
                    Inventario: <b id="exist_inventary"></b><br>
                    Precio:     <b id="exist_price"></b><br>
                    Catalogo:   <b id="exist_catalog"></b><br>
                    Extendido:  <b id="exist_extended"></b><br>
                </fieldset>
                <hr>
                <fieldset>
                    <input type="checkbox" name="no_actualizar_precio_icx" id="no_actualizar_precio_icx"
                        <?php echo $info['no_actualizar_precio_icx'] ? 'checked' : '' ?>
                    >
                    <label for="no_actualizar_precio_icx">No mantener el precio actualizado</label>      
                </fieldset>
                <fieldset>
                    
                </fieldset>
            <?php
        }else{
            ?>
            <h2>Este producto no forma parte de intcomex</h2>
            <?php
        }
        /*<input id="uyond_product_img_url" type="text" name="uyond_product_img_url" placeholder="Image URL" value="<?php echo esc_url( $img_url ); ?>" style="width:100%;font-size:13px;"> */
	}

    public function get_product_info_intcomex( $id ) {
        $product = new WC_Product( $id );

        $options = get_option('woocommerce_intcomex-plugin_settings', null);
        $sku_termination = $options['sku_termination'];
        $sku = str_replace("-$sku_termination", '', $product->get_sku());
        
		$info = array( 
            'sku' => $sku,
            'intcomex_product' => get_post_meta($id, 'intcomex_product', true) ?? false,
            'intcomex_product_category_id' => get_post_meta($id, 'intcomex_product_category_id', true) ?? 'default',
            'MPN_product_intcomex' => get_post_meta($id, 'MPN_product_intcomex', true) ?? '-',
            'no_actualizar_precio_icx' => get_post_meta($id, 'no_actualizar_precio_icx', true) ?? false,
            'price' => get_post_meta($id, '_alg_wc_cog_cost', true) ?? 0,
            'original_price' => get_post_meta($id, 'inc_precio_original_intcomex', true) ?? 0,

            'creacion_product' => get_post_meta($id, 'inc_fec_hora_creacion_product', true) != '' ? get_post_meta($id, 'inc_fec_hora_creacion_product', true) : 'No ha sido actualizado',
            'actualizacion_precio' => get_post_meta($id, 'inc_fec_hora_actualizacion_precio', true) != '' ? get_post_meta($id, 'inc_fec_hora_actualizacion_precio', true) : 'No ha sido actualizado',
            'actualizacion_stock' => get_post_meta($id, 'inc_fec_hora_actualizacion_stock', true) != '' ? get_post_meta($id, 'inc_fec_hora_actualizacion_stock', true) : 'No ha sido actualizado',
            'actualizacion_product' => get_post_meta($id, 'inc_fec_hora_actualizacion_product', true) != '' ? get_post_meta($id, 'inc_fec_hora_actualizacion_product', true) : 'No ha sido actualizado',
            'actualizacion_extended' => get_post_meta($id, 'inc_fec_hora_actualizacion_extended', true) != '' ? get_post_meta($id, 'inc_fec_hora_actualizacion_extended', true) : 'No ha sido actualizado',
        );

		return $info;
	}

    public function save_post( $post_id ) {

		if ( get_post_type( $post_id ) !== 'product' ) {
			return;
		}

        $data = ISSET($_POST['no_actualizar_precio_icx']) ? $_POST['no_actualizar_precio_icx'] == 'on' : false;

		if ( $data ) {
			update_post_meta( $post_id, 'no_actualizar_precio_icx', $data );
		} else {
			delete_post_meta( $post_id, 'no_actualizar_precio_icx' );
		}
	}

    //Cargar SCRIPTS
    function widget_script() {
        
        //wp_enqueue_style( 'intcomex-plugin', plugins_url( 'page/css/log.css', dirname( __FILE__ ) ) );
        wp_register_script( 'intcomex-widget', plugins_url( 'widget/js/actions_inc.js', dirname( __FILE__ ) ) );        
        wp_enqueue_script( 'intcomex-widget' );
        wp_enqueue_script("jquery");      
        //wp_register_script( 'intcomex-settings', plugins_url( 'page/js/settings.js', dirname( __FILE__ ) ) );
    }
        
}

product_widget::get_instance();

add_action( 'wp_ajax_get_info_product_in_api', 'get_info_product_in_api' );
function get_info_product_in_api() {

    $products = obtenerDatosDesdeJSON('inventory');
    $elemento = searchElementForProperty($products, 'Sku', $_POST['sku']);
    $existe_inventario = ! is_null( $elemento );

    $products = obtenerDatosDesdeJSON('catalog');
    $elemento = searchElementForProperty($products, 'Sku', $_POST['sku']);
    $existe_catalog = ! is_null( $elemento );

    $products = obtenerDatosDesdeJSON('prices');
    $elemento = searchElementForProperty($products, 'Sku', $_POST['sku']);
    $existe_precio = ! is_null( $elemento );

    $products = obtenerDatosDesdeJSON('catalogExtend');
    $elemento = searchElementForProperty($products, 'localSku', $_POST['sku']);
    $existe_extend = ! is_null( $elemento );


    echo json_encode(
        [
            'inventary' => $existe_inventario,
            'catalog' => $existe_catalog,
            'price' => $existe_precio,
            'extended' => $existe_extend,
        ]
    );

    wp_die(); 
}

?>