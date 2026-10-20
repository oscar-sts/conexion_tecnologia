<?php

use WP_Queue\Job;


class load_ProductCatalogExtend extends Job {

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
        $descargar_imagen_principal = $options['descargar_imagen'];
        
		try {
            $products = obtenerDatosDesdeJSON('catalogExtend');
            
            $contadorGlobal = 0;
            $contador = get_option( 'intcomex_last_value_catalog_extend', 0 );
            $longitud = count($products);

            foreach ($products as $key => $productAPI) {

                $contadorGlobal = $contadorGlobal + 1;
                if($contadorGlobal <= $contador) continue;
                
                
                $product = new WC_Product( wc_get_product_id_by_sku( $productAPI['localSku'] . "-$sku_termination") );

                if( $product->exists()
                    and $product->get_meta('intcomex_product')
                    and ! $product->get_image_id()
                    and ! $product->get_meta("_uyond_product_img_url")
                    and ! $product->get_meta('set_extend_product_intcomex')
                ){
                    wp_queue()->push( new ProductCatalogExtended($productAPI, $sku_termination, $key, $descargar_imagen_principal ) );
                }
                                
                if($contadorGlobal % LOTES_PRODUCTS == 0) break; 
                
            }


            if ($contadorGlobal < $longitud ) {
                update_option( 'intcomex_last_value_catalog_extend', $contadorGlobal );
                add_to_info_log("Van $contadorGlobal de $longitud");
                wp_queue()->push( new load_ProductCatalogExtend() );
            }else{
                update_option( 'intcomex_last_value_catalog_extend', 0);
                add_to_info_log("Van $contadorGlobal de $longitud");
                wp_queue()->push( new PrintProgress("Se ha actualizado el catalogo", $contadorGlobal) );
            }
            // exportJsonToCsv(
            //     "Catalogo",  //nombre del archivo 
            //     $products,	 //array de objetos
            //     array('SKU', 'MPN', 'Descripcion', 'Tipo', 'Marca', 'Categorias'), //columnas que iran en la cabecera
            //     array('Brand', 'Category') //Propiedades a transformar en string
            // ); 
            
			
		} catch (Throwable $e) {
			$mensaje = $e->getMessage();
			add_to_problem_log( $mensaje );
			add_to_problem_log( print_r($productAPI, true) );
		}
		
	}

}