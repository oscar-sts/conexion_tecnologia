<?php

use WP_Queue\Job;


class ProductCatalogExtended extends Job {

	/**
	 * @var int
	 */
	public $productAPI;
	public $sku_termination;
	public $no_product;
    public $descargar_imagen_principal;
	/**
	 * Subscribe_User_Job constructor.
	 *
	 * @param int $user_id
	 */
	public function __construct( $productAPI, $sku_termination = "", $no_product = 0, $descargar_imagen_principal = false) {
		$this->productAPI = $productAPI;
		$this->sku_termination = $sku_termination;
		$this->no_product = $no_product;
        $this->descargar_imagen_principal = $descargar_imagen_principal;
	}

	/**
	 * Handle job logic.
	 */
	public function handle() {
		$productAPI = $this->productAPI;
        
        if(empty($productAPI['localSku'])){
            add_to_log("se dectecto un producto sin SKU");
            return null;
        }
		
        $product = new WC_Product( wc_get_product_id_by_sku( $productAPI['localSku'] . "-$this->sku_termination") );

        if( $product->get_image_id() ){ return; }
        if( $product->get_meta("_uyond_product_img_url") ){ return; }

        $product->update_meta_data( 'inc_fec_hora_actualizacion_extended' , get_time_formated());
        
        $descriptionProduct = arrayToString($productAPI);
        $product->set_description($descriptionProduct);
        $product->set_catalog_visibility( 'visible' );
        $product->set_sold_individually( false );
        
        //buscando imagen principal
	try{
        	$mainImage = searchElementForProperty($productAPI['Imagenes'], 'isMainImage', true);
	} catch (Throwable $e) {
		$mensaje = $e->getMessage();
		add_to_problem_log( $mensaje );
		add_to_problem_log( print_r($productAPI, true) );
	}
        if(isset($mainImage)){
            if($this->descargar_imagen_principal == 'yes'){
               $imagePrincipal = ksa_upload_from_url($mainImage['url'], $productAPI['localSku'] . "-ICX" . ' - imagen principal');	
               $product->set_image_id($imagePrincipal['attachment_id']);
            }else{
                $product->update_meta_data("_uyond_product_img_url", $mainImage['url']);	
            }
            
        }else{
            add_to_error_log('producto con sku ' . $productAPI['localSku']. "-$this->sku_termination no tiene imagen principal");
        }
        
        //Agregando imagenes a la galeria
        $groupIds = array();
        $contadorDeImagenes = 0;
        foreach ($productAPI['Imagenes'] as $key => $value) {
            if( ! $value['isMainImage'] ){
                array_push($groupIds, $value['url']);
                $contadorDeImagenes++;
                if( $contadorDeImagenes == 4 ){ break; }
            }
        }
        
        if(!empty($groupIds)){
            $product->update_meta_data('_uyond_product_gallery_url', $groupIds);
        }else{
            add_to_error_log('producto con sku ' . $productAPI['localSku']. "-$this->sku_termination" . ' No tiene galeria de imagenes');
        }
        $product->set_sku($productAPI['localSku']. "-$this->sku_termination");
        $product->update_meta_data( 'set_extend_product_intcomex',  true );
        $product->save();
	}

}