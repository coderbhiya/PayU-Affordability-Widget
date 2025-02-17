<?php
/*
Plugin Name: PayU Affordability Widget
Description: Integrates the PayU Affordability Widget into WooCommerce product and cart pages with admin settings.
Plugin url: https://senseforge.in
Version: 1.1
Author: Aakash Dave
Author Url: https://senseforge.in
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Add a settings page to the WordPress admin
function payu_affordability_add_settings_page() {
    add_menu_page(
        'PayU Affordability Widget',
        'PayU Widget',
        'manage_options',
        'payu-affordability-widget',
        'payu_affordability_render_settings_page',
        'dashicons-admin-tools',
        100
    );
}
add_action('admin_menu', 'payu_affordability_add_settings_page');

// Render the settings page
function payu_affordability_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['payu_affordability_save_settings'])) {
        check_admin_referer('payu_affordability_settings');
        update_option('payu_affordability_merchant_key', sanitize_text_field($_POST['payu_affordability_merchant_key']));
    }

    $merchant_key = get_option('payu_affordability_merchant_key', '');
    ?>
    <div class="wrap">
        <h1>PayU Affordability Widget Settings</h1>
        <form method="post" action="">
            <?php wp_nonce_field('payu_affordability_settings'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Merchant Key</th>
                    <td>
                        <input type="text" name="payu_affordability_merchant_key" value="<?php echo esc_attr($merchant_key); ?>" class="regular-text" />
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings', 'primary', 'payu_affordability_save_settings'); ?>
        </form>
    </div>
    <?php
}

// Enqueue the PayU Widget script
function payu_affordability_enqueue_scripts() {
    if (class_exists('WooCommerce')) {
        wp_enqueue_script(
            'payu-affordability-widget',
            'https://jssdk.payu.in/widget/affordability-widget.min.js',
            [],
            null,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'payu_affordability_enqueue_scripts');

// Add the widget to the product page before the Add to Cart button
function payu_affordability_product_widget() {
    if (is_product()) {
        global $product;
        $price = $product->get_price();
        $merchant_key = get_option('payu_affordability_merchant_key', '');

        if (!$merchant_key) {
            return; // Do not display the widget if the Merchant Key is not set
        }
        ?>
        <div id="payuWidget"></div>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                const widgetConfig = {
                    key: '<?php echo esc_js($merchant_key); ?>',
                    amount: <?php echo esc_js($price); ?>
                };
                payuAffordability.init(widgetConfig);
            });
        </script>
        <?php
    }
}
add_action('woocommerce_before_add_to_cart_button', 'payu_affordability_product_widget');

// Add the widget to the cart page
function payu_affordability_cart_widget() {
    if (is_cart()) {
        $cart_total = WC()->cart->get_cart_contents_total();
        $merchant_key = get_option('payu_affordability_merchant_key', '');

        if (!$merchant_key) {
            return; // Do not display the widget if the Merchant Key is not set
        }
        ?>
        <div id="payuWidget"></div>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                const widgetConfig = {
                    key: '<?php echo esc_js($merchant_key); ?>',
                    amount: <?php echo esc_js($cart_total); ?>
                };
                payuAffordability.init(widgetConfig);
            });
        </script>
        <?php
    }
}
add_action('woocommerce_cart_totals_after_order_total', 'payu_affordability_cart_widget');
