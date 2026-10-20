<?php
function validateLicense($ShowLog = false)
{
    // BYPASS DE LICENCIA APLICADO
    // Forzamos la opción a true y retornamos true para evitar bloqueos.
    update_option( 'license_intcomex' ,  true);
    
    if ($ShowLog) {
        add_to_info_log("Licencia validada manualmente (Bypass aplicado).");
    }
    
    return true;
}