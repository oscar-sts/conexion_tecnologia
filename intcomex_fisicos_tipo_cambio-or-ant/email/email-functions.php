<?php


function email_template_intcomex( $order, $sent_to_admin, $plain_text, $email ) {
		
    if( 'customer_completed_order' == $email->id ){
        // Set here as you want your custom content (for customers and email notification related to processing orders only)
        
        $res = get_post_meta($order->get_id(), 'TokensESD', true);
        $tokensInfo = json_decode($res, true);
        foreach ($tokensInfo as $key => $item) {
            // obtengo el producto segun el SKU
            $product = new WC_Product( wc_get_product_id_by_sku( $item['Sku'] .  get_termination_sku_intcomex()  ) );

            //se obtiene el link de la imagen
            $product_image = wp_get_attachment_image_src( $product->get_image_id() )[0];
            
            $name = $product->get_name();
            
            ?>
                <table width="100%">
                    <thead>
                        <tr>
                            <th style="text-align: center;"><?php echo $name ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="text-align: center;">
                            <img src='<?php echo $product_image ?>' class='img_intcomex'>
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: center;">CODIGOS PARA CANJEAR</td>
                        </tr>
                        <?php
                            foreach ($item['Tokens'] as $key => $token) {
                                $product_key = $token['ProductKey'];
                            ?>
                            <tr>
                                <td style="text-align: center;">
                                    <span class='codigo_intcomex' >
                                        <?php echo $product_key ?>
                                    </span>
                                </td>
                            </tr>
                            <?php
                            
                            if(isset($token['LinkUrl'])){
                                $link = $token['LinkUrl']; ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <a class='a_intcomex' href='<?php echo $link ?>'>Enlace</a>
                                    </td>
                                </tr>
                            <?php
                                }
                            ?>
                            
                        <?php 
                            }  
                        ?>
                    </tbody>
                </table>

            <?php
        }

    }

}

function css_intcomex_email( $css, $email ) { 
    if ( $email->id == 'customer_completed_order' ) {
        
        $css .= '
        .img_intcomex{
            height: 200px;
        }
        
        .codigo_intcomex{
            padding: 10px;
            text-align: center;
            background-color: #D6E4E5;
            border-radius: 50px;
        }
        
        .a_intcomex{
            text-decoration: none; 
        }  
        ';
    }
    return $css;
}