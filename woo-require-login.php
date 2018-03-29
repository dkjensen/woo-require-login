<?php
/**
 * Plugin Name: WooCommerce Require Login
 * Description: Restrict products and categories to logged in users only
 * Version: 1.0.0
 * Author: David Jensen
 * Author URI: http://dkjensen.com
 * License: GPL2
 */



if( ! defined( 'ABSPATH' ) )
    exit;

/**
 * Notice add to cart require logged in user
 *
 * @param [type] $cart_item_key
 * @param [type] $product_id
 * @param [type] $quantity
 * @param [type] $variation_id
 * @param [type] $variation
 * @param [type] $cart_item_data
 * @return void
 */
function woo_require_login_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
    $product_data = wc_get_product( $variation_id ? $variation_id : $product_id );

    $require_login = get_post_meta( $product_data->get_id(), '_require_login', true );

    if( $require_login && ! is_user_logged_in() ) {
        throw new Exception( apply_filters( 'woocommerce_require_login_add_to_cart_message', __( 'You must be logged in to purchase this product.', 'woocommerce' ), $product_data ) );
    }
}
add_action( 'woocommerce_add_to_cart', 'woo_require_login_add_to_cart', 10, 6 );


/**
 * Simple product field
 *
 * @return void
 */
function woo_require_login_options() {
    global $product_object;

    $require_login = get_post_meta( $product_object->get_id(), '_require_login', true );

    woocommerce_wp_checkbox( array(
        'id'            => '_require_login',
        'value'         => $require_login ? 'yes' : 'no',
        'wrapper_class' => 'show_if_simple',
        'label'         => __( 'Require login?', 'woocommerce' ),
        'description'   => __( 'Require user to be logged in to purchase', 'woocommerce' ),
    ) );
}
add_action( 'woocommerce_product_options_pricing', 'woo_require_login_options' );


/**
 * Variation field
 *
 * @param integer $loop
 * @param array $variation_data
 * @param object $variation
 * @return void
 */
function woo_require_login_options_variation( $loop, $variation_data, $variation ) {
    $require_login = get_post_meta( $variation->ID, '_require_login', true );
    ?>

    <label class="tips" data-tip="<?php _e( 'Enable this option if users are required to be logged in to purchase this product', 'woocommerce' ); ?>">
        <?php _e( 'Require login?', 'woocommerce' ); ?>
        <input type="checkbox" class="checkbox variable_is_require_login" name="_require_login[<?php echo $loop; ?>]" <?php checked( $require_login, 'yes' ); ?> value="yes" />
    </label>

    <?php
}
add_action( 'woocommerce_variation_options', 'woo_require_login_options_variation', 10, 3 );


/**
 * Save product data
 *
 * @param mixed $product
 * @param mixed $i
 * @return void
 */
function woo_require_login_save_product( $product, $i = null ) {
    if( is_int( $product ) ) {
        $require_login = isset( $_POST['_require_login'][$i] ) ? $_POST['_require_login'][$i] : '';

        update_post_meta( $product, '_require_login', $require_login );
    }elseif( is_object( $product ) ) {
        update_post_meta( $product->get_id(), '_require_login', $_POST['_require_login'] );
    }
    
}
add_action( 'woocommerce_save_product_variation', 'woo_require_login_save_product', 10, 2 );
add_action( 'woocommerce_admin_process_product_object', 'woo_require_login_save_product', 10, 2 );


/**
 * Check if item is in cart that required user to be logged in
 *
 * @return boolean
 */
function woo_require_login_check_cart() {
    $error = new WP_Error();

    foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
        $product = $values['data'];

        $require_login = get_post_meta( $product->get_id(), '_require_login', true );

        if ( $require_login && ! is_user_logged_in() ) {
            $error->add( 'require-login', apply_filters( 'woocommerce_require_login_cart_message', sprintf( 
                    __( 'Sorry, you must be logged in to purchase "%s". Please login or edit your cart and try again. We apologize for any inconvenience caused.', 'woocommerce' ), 
                    $product->get_name() 
            ) ) );
            
            wc_add_notice( $error->get_error_message(), 'error' );

            return false;
        }
    }

    return true;
}
add_action( 'woocommerce_check_cart_items', 'woo_require_login_check_cart' );