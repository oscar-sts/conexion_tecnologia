<?php



function validateLicense($ShowLog = false)
{
	
	$license = get_option('woocommerce_intcomex-plugin_settings', null);
	
	if(!isset($license)){
		add_to_error_log("No hay licencia registrada");
		return false;
	}
	$license = $license['license_key'];
	
	if(!isset($license)){
		add_to_error_log("No hay licencia registrada");
		return false;
	}
	
	$priv = "-----BEGIN PRIVATE KEY-----
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
-----END PRIVATE KEY-----";
	
	
	openssl_private_decrypt(hex2bin($license), $decrypted, $priv);
	$license_formates = json_decode($decrypted, true);


	if(!isset($license_formates)){
		add_to_error_log("Licencia no valida");
		return false;
	}
	
	if(!isset($license_formates['host'])){
		add_to_error_log("La licencia no corresponde a esta pagina " . $_SERVER['SERVER_NAME'] );
		return false;
	}
	
	if (empty($_SERVER['SERVER_NAME'])) {
		add_to_error_log("Error al obtener el nombre del servidor.");
		return false;
	}

	if($license_formates['host'] != $_SERVER['SERVER_NAME']){
		add_to_error_log("La licencia no corresponde a esta pagina " . $_SERVER['SERVER_NAME'] );
		return false;
	}
	
	$date = time();
	if($date > $license_formates['expiration']){
		$tiempo = date('d/m/Y H:i:s', $license_formates['expiration']);
		add_to_error_log("Licencia Caduco en $tiempo");
		return false;
	}
	update_option( 'license_intcomex' ,  true);
	$tiempo = date('d/m/Y', $license_formates['expiration']);
	if ($ShowLog) {
		add_to_info_log("Licencia Valida hasta $tiempo");
	}
	return true;
}
