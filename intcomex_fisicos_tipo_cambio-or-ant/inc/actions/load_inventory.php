<?php

use WP_Queue\Job;


class load_ProductInventory extends Job {

	public function __construct() {
	}

	/**
	 * Handle job logic.
	 */
	public function handle() {
        $options = get_option('woocommerce_intcomex-plugin_settings', null);
        $sku_termination = $options['sku_termination'];
        $testmode = $options['test_mode'];
        $impuesto = $options['impuesto'];
        $tipo_cambio = $options['tipo_cambio'];
		try {
            $products = obtenerDatosDesdeJSON('inventory');
            $contadorGlobal = 0;
            $contador = get_option( 'intcomex_last_value_inventory', 0 );
            $longitud = count($products);

            foreach ($products as $key => $productAPI) {
                
                $contadorGlobal = $contadorGlobal + 1;
                if($contadorGlobal <= $contador) continue;
                
                $sku = $productAPI['Sku'] . "-$sku_termination";
                $product = new WC_Product( wc_get_product_id_by_sku( $sku ) );

                if ( $product->exists() ) { 
                    wp_queue()->push( new ProductPriceInventory($productAPI, $sku_termination, $key, $impuesto, $tipo_cambio ) );
                }
                
              
                if($contadorGlobal % LOTES_PRODUCTS == 0) break; 
            }

            if ($contadorGlobal < $longitud ) {
                update_option( 'intcomex_last_value_inventory', $contadorGlobal );
                add_to_info_log("[Inventory] Van $contadorGlobal de $longitud");
                wp_queue()->push( new load_ProductInventory() );
            }else{
                update_option( 'intcomex_last_value_inventory', 0);
                add_to_info_log("Van $contadorGlobal de $longitud");
                wp_queue()->push( new PrintProgress("Se ha actualizado el inventario", $contadorGlobal) );
            }

            			
		} catch (\Throwable $e) {
			$mensaje = $e->getMessage();
			add_to_problem_log( $mensaje );
			add_to_problem_log( print_r($productAPI, true) );
		}
		
	}

}