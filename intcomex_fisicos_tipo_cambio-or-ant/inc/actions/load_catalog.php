<?php

use WP_Queue\Job;


class load_ProductCatalog extends Job {

	public function __construct() {
	}

	/**
	 * Handle job logic.
	 */
	public function handle() {
        $options = get_option('woocommerce_intcomex-plugin_settings', null);
        $testmode = $options['test_mode'];
        $sku_termination = $options['sku_termination'];
        $tipo_cambio = $options['tipo_cambio'];
        
		try {
            $products = obtenerDatosDesdeJSON('catalog');
            $contadorGlobal = 0;
            $contador = get_option( 'intcomex_last_value_catalog', 0 );
            $longitud = count($products);

            foreach ($products as $key => $productAPI) {

                $contadorGlobal = $contadorGlobal + 1;
                if($contadorGlobal <= $contador) continue;
                
                $product = new WC_Product( wc_get_product_id_by_sku( $productAPI['Sku'] . "-$sku_termination") );

                //si el producto NO existe o es de tipo descargable ingresan
                //y no se haya establecido las propiedades del producto
                if(
                    ( ! $product->exists() and ! $product->get_meta('set_info_product_intcomex') )
                        //"set_info_product_intcomex" se coloca cuando el producto se completa la información del catalogo
                ){
                    wp_queue()->push( new ProductCatalog($productAPI, $sku_termination, $key ) );
                }

                if($contadorGlobal % LOTES_PRODUCTS == 0) break; 
            }
            
            if ($contadorGlobal < $longitud ) {
                update_option( 'intcomex_last_value_catalog', $contadorGlobal );
                add_to_info_log("Van $contadorGlobal de $longitud");
                wp_queue()->push( new load_ProductCatalog() );
            }else{
                update_option( 'intcomex_last_value_catalog', 0);
                add_to_info_log("Van $contadorGlobal de $longitud");
                wp_queue()->push( new PrintProgress("Se ha actualizado el catalogo", $contador) );
            }
                        
			
		} catch (\Throwable $e) {
			$mensaje = $e->getMessage();
			add_to_problem_log( $mensaje );
			add_to_problem_log( print_r($productAPI, true) );
		}
		
	}

}