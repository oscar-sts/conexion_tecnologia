<?php

use WP_Queue\Job;


class load_ProductPrices extends Job {

	public function __construct() {
	}

	/**
	 * Handle job logic.
	 */
	public function handle() {
        $options = get_option('woocommerce_intcomex-plugin_settings', null);
        $sku_termination = $options['sku_termination'];
        $impuesto = $options['impuesto'];
        $tipo_cambio = $options['tipo_cambio'];
		try {
            $products = obtenerDatosDesdeJSON('prices');
            $contadorGlobal = 0;
            $contador = get_option( 'intcomex_last_value_precios', 0 );
            $salir =  false;
            $longitud = count($products);
            foreach ($products as $key => $productAPI) {

                $contadorGlobal = $contadorGlobal + 1;
                if($contadorGlobal <= $contador) continue;

                $product = new WC_Product( wc_get_product_id_by_sku( $productAPI['Sku'] . "-$sku_termination") );

                if( $product->exists() ) 
                { 
                    wp_queue()->push( new ProductPriceInventory($productAPI, $sku_termination, $key, $impuesto, $tipo_cambio ) );
                }

                if( $contadorGlobal % LOTES_PRODUCTS == 0) break;
            }
            
            if ($contadorGlobal < $longitud ) {
                update_option( 'intcomex_last_value_precios', $contadorGlobal );
                add_to_info_log("[Precios] Van $contadorGlobal de $longitud");
                wp_queue()->push( new load_ProductPrices() );
            }else{
                update_option( 'intcomex_last_value_precios', 0);
                add_to_info_log("Van $contadorGlobal de $longitud");
                wp_queue()->push( new PrintProgress("Se ha actualizado los precios", $contadorGlobal) );
            }
			
		} catch (\Throwable $e) {
			$mensaje = $e->getMessage();
			add_to_problem_log( $mensaje );
			add_to_problem_log( print_r($productAPI, true) );
		}
		
	}

}