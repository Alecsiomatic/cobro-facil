<?php
/**
 * Plugin Name:       Cobro Fácil
 * Plugin URI:        https://github.com/Alecsiomatic/cobro-facil
 * Description:       Sistema de acceso seguro a entradas con código de 6 dígitos y envío por WhatsApp.
 * Version:           2.0.0
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

if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'COBRO_FACIL_VERSION', '2.0.0' );
define( 'COBRO_FACIL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'COBRO_FACIL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// =============================================================================
// 1. GENERAR CÓDIGO DE 6 DÍGITOS AL COMPLETAR PEDIDO
// =============================================================================
function cobro_facil_generate_access_code( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    // Solo generar si no existe ya
    $existing_code = get_post_meta( $order_id, '_cobro_facil_access_code', true );
    if ( $existing_code ) return;

    // Generar código único de 6 dígitos
    $code = sprintf( '%06d', mt_rand( 0, 999999 ) );
    
    // Verificar que no exista (muy improbable pero por seguridad)
    global $wpdb;
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_cobro_facil_access_code' AND meta_value = %s",
        $code
    ) );
    
    if ( $exists ) {
        // Regenerar si existe
        $code = sprintf( '%06d', mt_rand( 0, 999999 ) );
    }

    update_post_meta( $order_id, '_cobro_facil_access_code', $code );
}
add_action( 'woocommerce_thankyou', 'cobro_facil_generate_access_code', 5 );
add_action( 'woocommerce_order_status_completed', 'cobro_facil_generate_access_code', 5 );
add_action( 'woocommerce_order_status_processing', 'cobro_facil_generate_access_code', 5 );

// =============================================================================
// 2. MOSTRAR CÓDIGO Y BOTÓN WHATSAPP EN PÁGINA "GRACIAS"
// =============================================================================
function cobro_facil_thankyou_content( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    $code = get_post_meta( $order_id, '_cobro_facil_access_code', true );
    if ( ! $code ) {
        // Generar si no existe
        cobro_facil_generate_access_code( $order_id );
        $code = get_post_meta( $order_id, '_cobro_facil_access_code', true );
    }

    $phone = $order->get_billing_phone();
    $access_url = site_url( '/mi-entrada' );
    
    // Mensaje para WhatsApp
    $whatsapp_message = "🎟️ *Ticket to Ride - Mi Entrada*\n\n";
    $whatsapp_message .= "Tu código de acceso: *{$code}*\n\n";
    $whatsapp_message .= "📱 Accede a tu entrada aquí:\n{$access_url}\n\n";
    $whatsapp_message .= "Guarda este mensaje para acceder a tu QR cuando lo necesites.";
    
    // Formatear número para wa.me (quitar espacios, guiones, etc)
    $phone_clean = preg_replace( '/[^0-9]/', '', $phone );
    
    // Si no tiene código de país, agregar +52 (México) por defecto
    if ( strlen( $phone_clean ) === 10 ) {
        $phone_clean = '52' . $phone_clean;
    }
    
    $whatsapp_url = 'https://wa.me/' . $phone_clean . '?text=' . rawurlencode( $whatsapp_message );
    
    // Cargar estilos
    wp_enqueue_style( 'cobro-facil-styles', COBRO_FACIL_PLUGIN_URL . 'assets/css/cobro-facil.css', array(), COBRO_FACIL_VERSION );
    
    ?>
    <div class="cobro-facil-thankyou-box">
        <div class="cobro-facil-icon">🎟️</div>
        <h2>¡Compra exitosa!</h2>
        
        <div class="cobro-facil-code-section">
            <p class="cobro-facil-label">Tu código de acceso:</p>
            <div class="cobro-facil-code"><?php echo esc_html( $code ); ?></div>
        </div>
        
        <a href="<?php echo esc_url( $whatsapp_url ); ?>" target="_blank" class="cobro-facil-whatsapp-btn">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            Enviar a mi WhatsApp
        </a>
        
        <p class="cobro-facil-note">
            Guarda este código para acceder a tu entrada cuando quieras en:<br>
            <a href="<?php echo esc_url( $access_url ); ?>"><?php echo esc_html( $access_url ); ?></a>
        </p>
    </div>
    <?php
}
add_action( 'woocommerce_thankyou', 'cobro_facil_thankyou_content', 15 );

// =============================================================================
// 3. SHORTCODE PARA PÁGINA DE ACCESO [mi_entrada]
// =============================================================================
function cobro_facil_access_page_shortcode() {
    wp_enqueue_style( 'cobro-facil-styles', COBRO_FACIL_PLUGIN_URL . 'assets/css/cobro-facil.css', array(), COBRO_FACIL_VERSION );
    wp_enqueue_script( 'cobro-facil-access', COBRO_FACIL_PLUGIN_URL . 'assets/js/access.js', array( 'jquery' ), COBRO_FACIL_VERSION, true );
    wp_localize_script( 'cobro-facil-access', 'cobroFacil', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'cobro_facil_access_nonce' ),
    ) );

    ob_start();
    ?>
    <div class="cobro-facil-access-container">
        <div id="cobro-facil-form-section">
            <div class="cobro-facil-icon">🎟️</div>
            <h2>Accede a tu entrada</h2>
            <p>Ingresa tu código de 6 dígitos</p>
            
            <form id="cobro-facil-access-form">
                <div class="cobro-facil-code-input-container">
                    <input type="text" id="cobro-facil-code-input" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="000000" autocomplete="off" required>
                </div>
                <button type="submit" class="cobro-facil-submit-btn">Ver mi entrada</button>
            </form>
            
            <div id="cobro-facil-error" class="cobro-facil-error" style="display:none;"></div>
        </div>
        
        <div id="cobro-facil-result-section" style="display:none;">
            <!-- Aquí se mostrará el QR y detalles del pedido -->
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'mi_entrada', 'cobro_facil_access_page_shortcode' );

// =============================================================================
// 4. AJAX: VALIDAR CÓDIGO Y DEVOLVER QR
// =============================================================================
function cobro_facil_validate_code() {
    check_ajax_referer( 'cobro_facil_access_nonce', 'nonce' );

    $code = isset( $_POST['code'] ) ? sanitize_text_field( $_POST['code'] ) : '';
    
    if ( strlen( $code ) !== 6 || ! ctype_digit( $code ) ) {
        wp_send_json_error( array( 'message' => 'El código debe tener 6 dígitos.' ) );
    }

    // Buscar pedido con ese código
    global $wpdb;
    $order_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_cobro_facil_access_code' AND meta_value = %s",
        $code
    ) );

    if ( ! $order_id ) {
        wp_send_json_error( array( 'message' => 'Código no encontrado. Verifica e intenta de nuevo.' ) );
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        wp_send_json_error( array( 'message' => 'Pedido no encontrado.' ) );
    }

    // Generar URL del QR (compatible con QRCompleto)
    $upload_dir = wp_upload_dir();
    $qr_url = $upload_dir['baseurl'] . '/qr_order_' . $order_id . '.png';
    $qr_path = $upload_dir['basedir'] . '/qr_order_' . $order_id . '.png';

    // Si no existe el QR, intentar generarlo (requiere QRCompleto)
    if ( ! file_exists( $qr_path ) && function_exists( 'qrcompleto_generate_qr' ) ) {
        $qr_url = qrcompleto_generate_qr( $order_id );
    }

    // Obtener detalles del pedido
    $items_html = '';
    foreach ( $order->get_items() as $item_id => $item ) {
        $product_name = $item->get_name();
        $section_name = wc_get_order_item_meta( $item_id, 'section_name', true );
        $seat_row     = wc_get_order_item_meta( $item_id, 'seat_row', true );
        $seat_number  = wc_get_order_item_meta( $item_id, 'seat_number', true );
        $event_time   = strip_tags( wc_get_order_item_meta( $item_id, 'event_time', true ) );
        $event_location = strip_tags( wc_get_order_item_meta( $item_id, 'event_location', true ) );

        $items_html .= '<div class="cobro-facil-ticket-item">';
        $items_html .= '<h4>' . esc_html( $product_name ) . '</h4>';
        if ( $section_name ) $items_html .= '<p><strong>Mesa:</strong> ' . esc_html( $section_name ) . '</p>';
        if ( $seat_row ) $items_html .= '<p><strong>Fila:</strong> ' . esc_html( $seat_row ) . '</p>';
        if ( $seat_number ) $items_html .= '<p><strong>Asiento:</strong> ' . esc_html( $seat_number ) . '</p>';
        if ( $event_time ) $items_html .= '<p><strong>Hora:</strong> ' . esc_html( $event_time ) . '</p>';
        if ( $event_location ) $items_html .= '<p><strong>Lugar:</strong> ' . esc_html( $event_location ) . '</p>';
        $items_html .= '</div>';
    }

    // Obtener imagen de portada del evento (compatible con QRCompleto)
    $cover_url = '';
    if ( function_exists( 'qrcompleto_get_event_cover_image_from_order' ) ) {
        $cover_url = qrcompleto_get_event_cover_image_from_order( $order );
    }

    $html = '<div class="cobro-facil-entry-result">';
    
    if ( $cover_url ) {
        $html .= '<div class="cobro-facil-cover"><img src="' . esc_url( $cover_url ) . '" alt="Evento"></div>';
    }
    
    $html .= '<h2>🎟️ Tu Entrada</h2>';
    $html .= '<p class="cobro-facil-order-number">Pedido #' . esc_html( $order_id ) . '</p>';
    
    if ( file_exists( $qr_path ) ) {
        $html .= '<div class="cobro-facil-qr"><img src="' . esc_url( $qr_url ) . '" alt="QR de acceso"></div>';
    }
    
    $html .= '<div class="cobro-facil-ticket-details">' . $items_html . '</div>';
    
    $html .= '<button onclick="window.print()" class="cobro-facil-print-btn">🖨️ Imprimir entrada</button>';
    $html .= '<button onclick="location.reload()" class="cobro-facil-back-btn">← Volver</button>';
    
    $html .= '</div>';

    wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_cobro_facil_validate_code', 'cobro_facil_validate_code' );
add_action( 'wp_ajax_nopriv_cobro_facil_validate_code', 'cobro_facil_validate_code' );

// =============================================================================
// 5. ADMIN: MOSTRAR CÓDIGO EN DETALLES DEL PEDIDO
// =============================================================================
function cobro_facil_admin_order_meta( $order ) {
    $code = get_post_meta( $order->get_id(), '_cobro_facil_access_code', true );
    if ( $code ) {
        echo '<p><strong>Código de Acceso (Cobro Fácil):</strong> <code style="font-size: 16px; padding: 5px 10px; background: #f0f0f0;">' . esc_html( $code ) . '</code></p>';
    }
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'cobro_facil_admin_order_meta' );

// =============================================================================
// 6. MENÚ DE ADMINISTRACIÓN
// =============================================================================
function cobro_facil_admin_menu() {
    add_menu_page(
        'Cobro Fácil',
        'Cobro Fácil',
        'manage_options',
        'cobro-facil',
        'cobro_facil_admin_page',
        'dashicons-tickets-alt',
        30
    );
}
add_action( 'admin_menu', 'cobro_facil_admin_menu' );

function cobro_facil_admin_page() {
    ?>
    <div class="wrap">
        <h1>🎟️ Cobro Fácil</h1>
        <div class="card" style="max-width: 600px; padding: 20px;">
            <h2>✅ Plugin activo</h2>
            <p><strong>Versión:</strong> <?php echo COBRO_FACIL_VERSION; ?></p>
            
            <hr>
            
            <h3>Configuración</h3>
            <p>1. Crea una página llamada <strong>"Mi Entrada"</strong> con el slug <code>/mi-entrada</code></p>
            <p>2. Agrega el shortcode: <code>[mi_entrada]</code></p>
            
            <hr>
            
            <h3>Cómo funciona</h3>
            <ol>
                <li>Cliente compra → Se genera código de 6 dígitos</li>
                <li>En página "Gracias" → Ve código + botón WhatsApp</li>
                <li>Cliente guarda código en su WhatsApp</li>
                <li>Para ver entrada → Va a /mi-entrada e ingresa código</li>
                <li>Ve su QR y detalles del pedido</li>
            </ol>
        </div>
    </div>
    <?php
}

// =============================================================================
// 7. ACTIVACIÓN DEL PLUGIN
// =============================================================================
function cobro_facil_activate() {
    // Crear página "Mi Entrada" si no existe
    $page = get_page_by_path( 'mi-entrada' );
    if ( ! $page ) {
        wp_insert_post( array(
            'post_title'   => 'Mi Entrada',
            'post_name'    => 'mi-entrada',
            'post_content' => '[mi_entrada]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ) );
    }
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'cobro_facil_activate' );
