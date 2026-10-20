<?php

use WP_Queue\Job;


class ProductCatalog extends Job {

	/**
	 * @var int
	 */
	public $productAPI;
	public $sku_termination;
	public $no_product;

	/**
	 * Subscribe_User_Job constructor.
	 *
	 * @param int $user_id
	 */
	public function __construct( $productAPI, $sku_termination = "", $no_product = 0 ) {
		$this->productAPI = $productAPI;
		$this->sku_termination = $sku_termination;
		$this->no_product = $no_product;
	}

	/**
	 * Handle job logic.
	 */
	public function handle() {
		$productAPI = $this->productAPI;
		$permite_digitales = get_option_license('digitales');
		try {
			$sku = $productAPI['Sku'] . "-$this->sku_termination";
			$product = new WC_Product( wc_get_product_id_by_sku( $productAPI['Sku'] . "-$this->sku_termination" ) );

			if( 
				$productAPI['Type'] != 'Physical'  //si es digital
				 and //y
				 ( is_null($permite_digitales) or $permite_digitales === 0 ) //no tiene productos digitales habiltiado
			){ 
				return $product->delete(true); //se borrara
			}

			$product->update_meta_data( 'inc_fec_hora_actualizacion_product' , get_time_formated());

			$product->update_meta_data( 'intcomex_product' , true);
			$product->set_sku($productAPI['Sku'] . "-$this->sku_termination");

			$name = $productAPI['Description'];
			$posicion_guion = 0;
			try {

				if(is_null($name) or ! isset($name) or empty($name)){
					add_to_problem_log( "El producto con SKU " . $productAPI['Sku'] . " no obtiene el nombre." );
					return;
				}
				do {
					$posicion_guion = strpos($name, "-",$posicion_guion+2);
					if(!$posicion_guion){
						$posicion_guion = strlen($name);
						break;
					}
				} while ($posicion_guion<20);

				$name = substr($name, 0, $posicion_guion);
			} catch (\Throwable $th) {
				add_to_problem_log( "Hubo un error con el nombre del producto " . $productAPI['Sku'] . " se utilizo el nombre original." );
				$name = $productAPI['Description'];
			}
			
			
			$product->set_name($name);
			
			$product->set_short_description( $productAPI['Description'] . " \nMPN: " . $productAPI['Mpn'] );

			if(!$product->exists()){
				$product->set_price( 0 );
				//$product->set_status( 'pending' );
				$product->update_meta_data( 'inc_fec_hora_creacion_product' , get_time_formated());
				add_to_log("El producto con SKU " . $sku . " se acaba de crear" );
			}
			
			if(get_option('create_category') == 'true'){
				$groudIds = $this->getOrCreateCategoryId($productAPI['Category']);
				$product->set_category_ids( $groudIds );	
			}
			if( isset( $productAPI['Category']['Subcategories'][0]['CategoryId'] )){
				$categorie = $productAPI['Category']['Subcategories'][0]['CategoryId'];
				$product->update_meta_data( 'intcomex_product_category_id' , $categorie);
			}else{
				$product->update_meta_data( 'intcomex_product_category_id' , 'default');
			}

			if( isset( $productAPI['Freight'] ) and isset( $productAPI['Freight']['Package'] ) ){
				$packageInfo = $productAPI['Freight']['Package'];

				$CM_CONVERT = 2.54;
				$KG_CONVERT = 0.453592;
				
				
				if( isset( $packageInfo['Width']  ) ){
					$result = round( $packageInfo['Width'] * $CM_CONVERT, 5);
					$product->set_width( $result );
				}
					
				if( isset( $packageInfo['Height'] ) ){
					$result = round( $packageInfo['Height'] * $CM_CONVERT, 5);
					$product->set_height( $result );
				}
				if( isset( $packageInfo['Length'] ) ) {
					$result = round( $packageInfo['Length'] * $CM_CONVERT, 5);
					$product->set_length( $result );
				}
				
				if( isset( $packageInfo['Weight'] ) ){ 
					$result = round( $packageInfo['Weight'] * $KG_CONVERT, 5);
					$product->set_weight( $result );
				}

			}else{
				add_to_problem_log( "El producto $sku no tiene dimensiones." );
			}


			//Si es virtual
			$product->set_virtual($productAPI['Type'] != 'Physical');
			
			$product->update_meta_data( 'MPN_product_intcomex',  $productAPI['Mpn'] );
			$product->update_meta_data( 'set_info_product_intcomex',  true );
			
			$product->save();
			if( isset($productAPI['Brand']) ){
				$new_brand = array( 'name' => $productAPI['Brand']['Description'], 'slug' => str_replace(" ", "-", $productAPI['Brand']['BrandId']) );
				$brand = create_brand($new_brand);
				add_brands($brand, $product);
				add_to_info_log("se relaciono la marca " . $productAPI['Brand']['Description'] . " al producto " . $productAPI['Sku'] . "-$this->sku_termination");
			}
			
		} catch (\Throwable $th) {
			$mensaje = $th->getMessage();
			add_to_problem_log( $mensaje );
			add_to_problem_log( print_r($productAPI, true) );
		}
		
	}

	function getOrCreateCategoryId($categoryApi, $parentId = 0){
		//se registra la taxonomia por si hay un error
		register_taxonomy( 'product_cat', array('product'), array() );
		
		if(!isset($categoryApi['CategoryId'])){
			return array($parentId);
		}
		
		$slug = str_replace('.', '-', $categoryApi['CategoryId']);
		//product_cat se llama la taxonomia que alberga las categorias de los productos
		$term = term_exists($slug, 'product_cat');
		if(is_null($term)){
			$term = wp_insert_term( $categoryApi['Description'], 'product_cat', array(
					'description' => $categoryApi['Description'], 
					'parent' => $parentId, 
					'slug' => $slug 
			));
		}
		$parentId = $term['term_id'];
		$groudIds = array($parentId);
		foreach ($categoryApi['Subcategories'] as $key => $value) {
			$groudIds = array_merge($groudIds, $this->getOrCreateCategoryId($value, $parentId));
			
		}
		return $groudIds;
	}

	

}