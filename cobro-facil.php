<?php
/**
 * Plugin Name:       Cobro Fácil
 * Plugin URI:        https://github.com/Alecsiomatic/cobro-facil
 * Description:       Personaliza y mejora el checkout de WooCommerce para una experiencia de pago más sencilla.
 * Version:           1.0.1
 * Author:            Alecsiomatic
 * Author URI:        https://github.com/Alecsiomatic
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cobro-facil
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Requires PHP:      7.4
 *
 * GitHub Plugin URI: Alecsiomatic/cobro-facil
 * GitHub Plugin URI: https://github.com/Alecsiomatic/cobro-facil
 * Primary Branch:    main
 * Release Asset:     true
 */

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'COBRO_FACIL_VERSION', '1.0.1' );

/**
 * Agregar menú en el admin.
 */
function cobro_facil_admin_menu() {
    add_menu_page(
        'Cobro Fácil',           // Título de la página
        'Cobro Fácil',           // Título del menú
        'manage_options',        // Capacidad requerida
        'cobro-facil',           // Slug del menú
        'cobro_facil_admin_page', // Función callback
        'dashicons-cart',        // Icono
        30                       // Posición
    );
}
add_action( 'admin_menu', 'cobro_facil_admin_menu' );

/**
 * Página del admin.
 */
function cobro_facil_admin_page() {
    ?>
    <div class="wrap">
        <h1>🎉 Cobro Fácil</h1>
        <div class="notice notice-success" style="padding: 20px; margin-top: 20px;">
            <h2 style="margin-top: 0;">✅ Plugin instalado exitosamente</h2>
            <p>Versión: <?php echo COBRO_FACIL_VERSION; ?></p>
            <p>El plugin está activo y funcionando correctamente.</p>
        </div>
    </div>
    <?php
}

/**
 * Mostrar aviso de activación.
 */
function cobro_facil_activation_notice() {
    if ( get_transient( 'cobro_facil_activated' ) ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Cobro Fácil</strong> ha sido instalado exitosamente. <a href="<?php echo admin_url( 'admin.php?page=cobro-facil' ); ?>">Ir a la configuración</a></p>
        </div>
        <?php
        delete_transient( 'cobro_facil_activated' );
    }
}
add_action( 'admin_notices', 'cobro_facil_activation_notice' );

/**
 * Activación del plugin.
 */
function cobro_facil_activate() {
    set_transient( 'cobro_facil_activated', true, 30 );
}
register_activation_hook( __FILE__, 'cobro_facil_activate' );
