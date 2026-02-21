<?php
/**
 * Plugin Name:       Cobro Fácil
 * Plugin URI:        https://github.com/longevai/cobro-facil
 * Description:       Personaliza y mejora el checkout de WooCommerce para una experiencia de pago más sencilla.
 * Version:           1.0.0
 * Author:            Longevai
 * Author URI:        https://github.com/longevai
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cobro-facil
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * WC requires at least: 5.0
 * WC tested up to:   8.0
 *
 * GitHub Plugin URI: longevai/cobro-facil
 * GitHub Plugin URI: https://github.com/longevai/cobro-facil
 * Primary Branch:    main
 * Release Asset:     true
 */

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Versión actual del plugin.
 */
define( 'COBRO_FACIL_VERSION', '1.0.0' );
define( 'COBRO_FACIL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'COBRO_FACIL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Verificar que WooCommerce esté activo.
 */
function cobro_facil_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'cobro_facil_woocommerce_missing_notice' );
        return false;
    }
    return true;
}

/**
 * Aviso de WooCommerce faltante.
 */
function cobro_facil_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e( 'Cobro Fácil requiere WooCommerce para funcionar. Por favor, instala y activa WooCommerce.', 'cobro-facil' ); ?></p>
    </div>
    <?php
}

/**
 * Inicializar el plugin.
 */
function cobro_facil_init() {
    if ( ! cobro_facil_check_woocommerce() ) {
        return;
    }

    // Cargar archivos del plugin
    require_once COBRO_FACIL_PLUGIN_DIR . 'includes/class-cobro-facil-checkout.php';

    // Inicializar la clase principal
    $checkout = new Cobro_Facil_Checkout();
    $checkout->init();
}
add_action( 'plugins_loaded', 'cobro_facil_init' );

/**
 * Activación del plugin.
 */
function cobro_facil_activate() {
    // Código de activación aquí
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'cobro_facil_activate' );

/**
 * Desactivación del plugin.
 */
function cobro_facil_deactivate() {
    // Código de desactivación aquí
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'cobro_facil_deactivate' );
