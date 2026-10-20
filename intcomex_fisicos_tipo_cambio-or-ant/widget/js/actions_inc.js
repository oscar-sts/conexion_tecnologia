$ = jQuery;


$("#validate_exist_info_product").click(function(event) {
    event.preventDefault();
    const sku = $(this).data('sku');
    var data = {
        'action': 'get_info_product_in_api',
        'sku': sku
    };
    $.post(ajaxurl, data, (response) => {
        const object = JSON.parse(response);
        console.log(object);
        
        $("#exist_inventary").text( object.inventary ? 'Disponible' : 'No Disponible' );
        $("#exist_price").text( object.price ? 'Disponible' : 'No Disponible');
        $("#exist_catalog").text( object.catalog ? 'Disponible' : 'No Disponible' );
        $("#exist_extended").text( object.extended ? 'Disponible' : 'No Disponible' );
    });

});

