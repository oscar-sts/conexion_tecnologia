<?php

use WP_Queue\Job;


class load_brands extends Job {

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
            $products = obtenerDatosDesdeJSON('catalog');
            $contadorGlobal = 0;
            $contador = get_option( 'intcomex_last_value_brands', 0 );
            $longitud = count($products);

            foreach ($products as $key => $productAPI) {
            //    if ( $testmode == 'yes' and $contador >= 100) { break; }
                
                $contadorGlobal = $contadorGlobal + 1;
                if($contadorGlobal <= $contador) continue;
                 

                $product = new WC_Product( wc_get_product_id_by_sku( $productAPI['Sku'] . "-$sku_termination") );

                if( isset($productAPI['Brand']) and $product->exists() ){
                    $new_brand = array( 'name' => $productAPI['Brand']['Description'], 'slug' => str_replace(" ", "-", $productAPI['Brand']['BrandId']) );
                    $brand = create_brand($new_brand);
                    add_brands($brand, $product);
                    add_to_info_log("se relaciono la marca " . $productAPI['Brand']['Description'] . " al producto " . $productAPI['Sku'] . "-$this->sku_termination");
			    }
                if($contadorGlobal % LOTES_PRODUCTS == 0) break;
            }

            // if ($testmode == 'yes' and $contador >= 100) {
            //     update_option( 'intcomex_last_value_brands', $contador);
            //     wp_queue()->push( new PrintProgress("Se ha actualizado las marcas - pruebas", $contador) );
            //     return;
            // }
            if ($contadorGlobal < $longitud ) {
                update_option( 'intcomex_last_value_brands', $contadorGlobal );
                add_to_info_log("[BRANDS] Van $contadorGlobal de $longitud");
                wp_queue()->push( new load_brands() );
            }else{
                update_option( 'intcomex_last_value_brands', 0);
                add_to_info_log("Van $contadorGlobal de $longitud");
                wp_queue()->push( new PrintProgress("Se ha actualizado las marcas", $contadorGlobal) );
            }
			
		} catch (\Throwable $e) {
			$mensaje = $e->getMessage();
			add_to_problem_log( $mensaje );
			add_to_problem_log( print_r($productAPI, true) );
		}
		
	}

}