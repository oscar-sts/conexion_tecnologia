<?php

use WP_Queue\Job;


class ProductPriceInventory extends Job {

	/**
	 * @var int
	 */
	public $productAPI;
	public $sku_termination;
	public $no_product;
	public $impuesto;
	public $tipo_cambio;

	/**
	 * Subscribe_User_Job constructor.
	 *
	 * @param int $user_id
	 */
	public function __construct( $productAPI, $sku_termination = "", $no_product = 0, $impuesto = 0, $tipo_cambio = 0 ) {
		$this->productAPI = $productAPI;
		$this->sku_termination = $sku_termination;
		$this->no_product = $no_product;
		$this->impuesto = $impuesto;
		$this->tipo_cambio = $tipo_cambio;
	}

	/**
	 * Handle job logic.
	 */
	public function handle() {
		try {
			$productAPI = $this->productAPI;

			if(empty($productAPI['Sku'])){
				add_to_log("se dectecto un producto sin SKU");
				return null;
			}

			$sku = $productAPI['Sku'] . "-$this->sku_termination";
			$product = new WC_Product( wc_get_product_id_by_sku($productAPI['Sku'] . "-$this->sku_termination") );
				
			$product->set_sold_individually( false );
			if(isset($productAPI['InStock'])){

				$product->update_meta_data( 'inc_fec_hora_actualizacion_stock' , get_time_formated());
				
				if(!$product->exists()){ return 0; }

				//si no tiene stock Y no es virtual
				if(	! $product->is_virtual() and ($productAPI['InStock']==0 or is_null( $productAPI['InStock'] ) ) ){
					$stock = $productAPI['InStock'] ?? 0;

					add_to_log("El producto " . $productAPI['Sku'] . "-$this->sku_termination tiene stock: $stock");
					//return $product->delete(true);
				}
				
				if( $product->is_virtual() ) {
					$product->set_stock_status( 'instock' );
					$product->set_manage_stock( false );
				}else{
					$product->set_stock_status($productAPI['InStock']>5? 'instock' :'outofstock');
					$product->set_manage_stock( true );
					
					$product->set_stock_quantity($productAPI['InStock']);
				}
				
			}

			
			if( isset($productAPI['Price']) ){

				if(!$product->exists()){ return 0; }
				
				if($product->get_meta('no_actualizar_precio_icx')){ return 0; }

				$product->update_meta_data( 'inc_fec_hora_actualizacion_precio' , get_time_formated());
				$price = $productAPI['Price']['UnitPrice'];
				$price = round($price, 5);
				if(is_null($price) or  ! isset($price) or $price == 0 ){
					add_to_problem_log( "El producto con SKU " . $productAPI['Sku'] . " tiene precio $price, se coloco con stock 0." );
					$product->set_stock_quantity(0);
					//return $product->delete(true);
				}
				
				list($result, $columnas) = getPorcentajeSwitchPrice($price, $this->impuesto, $product->get_meta('intcomex_product_category_id'),  $this->tipo_cambio);
				$result = round($result, 5);
				if($result < $price){
					add_to_error_log("El producto " . $productAPI['Sku'] . "-$this->sku_termination es menor al anterio [precio nuevo: Q$result, precio anterior: $price]");
					$product->update_meta_data( '_alg_wc_cog_cost' , $price*$this->tipo_cambio);
					$product->set_regular_price($result);
				}else{
					$product->update_meta_data( '_alg_wc_cog_cost' , $price*$this->tipo_cambio);
					$product->set_regular_price($result);
				}
				//guardamos el precio original para mostrarlo en la vista del producto
				$product->update_meta_data( 'inc_precio_original_intcomex' , $productAPI['Price']['UnitPrice']);
				
			}
			
			$product->save();
		} catch (\Throwable $th) {
			$mensaje = $e->getMessage();
			add_to_problem_log( $mensaje );
			add_to_problem_log( print_r($productAPI, true) );
		}
		
		return 1;
	}

}