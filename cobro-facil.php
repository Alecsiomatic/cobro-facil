<?php
/**
 * Plugin Name:       Cobro Fácil
 * Plugin URI:        https://github.com/Alecsiomatic/cobro-facil
 * Description:       Sistema de acceso seguro a entradas con código de 6 dígitos y envío por WhatsApp.
 * Version:           2.8.0
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

define( 'COBRO_FACIL_VERSION', '2.8.0' );
define( 'COBRO_FACIL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'COBRO_FACIL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// =============================================================================
// CHECKOUT SIMPLIFICADO: Solo nombre y teléfono
// =============================================================================

/**
 * Simplificar campos del checkout - solo nombre y teléfono.
 */
function cobro_facil_simplify_checkout_fields( $fields ) {
    // Campos a mantener
    $keep_fields = array( 'billing_first_name', 'billing_phone' );
    
    // Remover todos los campos de billing excepto los que queremos
    foreach ( $fields['billing'] as $key => $field ) {
        if ( ! in_array( $key, $keep_fields ) ) {
            unset( $fields['billing'][ $key ] );
        }
    }
    
    // Remover shipping y order comments
    unset( $fields['shipping'] );
    unset( $fields['order']['order_comments'] );
    
    // Asegurar que nombre y teléfono son obligatorios
    if ( isset( $fields['billing']['billing_first_name'] ) ) {
        $fields['billing']['billing_first_name']['required'] = true;
        $fields['billing']['billing_first_name']['label'] = 'Nombre';
        $fields['billing']['billing_first_name']['placeholder'] = 'Tu nombre';
        $fields['billing']['billing_first_name']['class'] = array( 'form-row-wide' );
        $fields['billing']['billing_first_name']['priority'] = 10;
    }
    
    if ( isset( $fields['billing']['billing_phone'] ) ) {
        $fields['billing']['billing_phone']['required'] = true;
        $fields['billing']['billing_phone']['label'] = 'WhatsApp / Teléfono';
        $fields['billing']['billing_phone']['placeholder'] = 'Ej: 55 1234 5678';
        $fields['billing']['billing_phone']['class'] = array( 'form-row-wide' );
        $fields['billing']['billing_phone']['priority'] = 20;
    }
    
    return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'cobro_facil_simplify_checkout_fields', 9999 );

/**
 * Remover campos de dirección requeridos por defecto.
 */
function cobro_facil_remove_default_required_fields( $fields ) {
    // Lista de campos que WooCommerce marca como requeridos por defecto
    $not_required = array(
        'billing_last_name',
        'billing_company',
        'billing_address_1',
        'billing_address_2',
        'billing_city',
        'billing_postcode',
        'billing_country',
        'billing_state',
        'billing_email',
    );
    
    foreach ( $not_required as $field ) {
        if ( isset( $fields[ $field ] ) ) {
            $fields[ $field ]['required'] = false;
        }
    }
    
    return $fields;
}
add_filter( 'woocommerce_billing_fields', 'cobro_facil_remove_default_required_fields', 9999 );

/**
 * No requerir email ni dirección para checkout virtual.
 */
add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );
add_filter( 'woocommerce_cart_needs_billing_address', '__return_false' );

/**
 * Para WooCommerce Blocks: Establecer valores por defecto antes de validar.
 */
function cobro_facil_set_default_checkout_data( $data ) {
    // Valores por defecto para campos ocultos
    $defaults = array(
        'billing_last_name'  => '-',
        'billing_company'    => '',
        'billing_address_1'  => 'N/A',
        'billing_address_2'  => '',
        'billing_city'       => 'Ciudad',
        'billing_state'      => '',
        'billing_postcode'   => '00000',
        'billing_country'    => 'MX',
    );
    
    foreach ( $defaults as $key => $value ) {
        if ( empty( $data[ $key ] ) ) {
            $data[ $key ] = $value;
        }
    }
    
    // Email temporal basado en teléfono
    if ( empty( $data['billing_email'] ) ) {
        $phone = isset( $data['billing_phone'] ) ? preg_replace( '/[^0-9]/', '', $data['billing_phone'] ) : time();
        $data['billing_email'] = 'guest_' . $phone . '@ticketoride.com';
    }
    
    return $data;
}
add_filter( 'woocommerce_checkout_posted_data', 'cobro_facil_set_default_checkout_data', 5 );

/**
 * Para Store API (Blocks): Modificar datos antes de crear orden.
 */
function cobro_facil_modify_store_api_data( $request ) {
    $body = $request->get_json_params();
    
    if ( isset( $body['billing_address'] ) ) {
        $defaults = array(
            'last_name'  => '-',
            'company'    => '',
            'address_1'  => 'N/A',
            'address_2'  => '',
            'city'       => 'Ciudad',
            'state'      => '',
            'postcode'   => '00000',
            'country'    => 'MX',
        );
        
        foreach ( $defaults as $key => $value ) {
            if ( empty( $body['billing_address'][ $key ] ) ) {
                $body['billing_address'][ $key ] = $value;
            }
        }
        
        // Email temporal
        if ( empty( $body['billing_address']['email'] ) ) {
            $phone = isset( $body['billing_address']['phone'] ) ? preg_replace( '/[^0-9]/', '', $body['billing_address']['phone'] ) : time();
            $body['billing_address']['email'] = 'guest_' . $phone . '@ticketoride.com';
        }
        
        $request->set_body( wp_json_encode( $body ) );
    }
    
    return $request;
}

/**
 * Hook para modificar validación de Store API.
 */
function cobro_facil_store_api_checkout_update_customer( $customer, $request ) {
    // Establecer valores por defecto en el customer
    if ( ! $customer->get_billing_last_name() ) {
        $customer->set_billing_last_name( '-' );
    }
    if ( ! $customer->get_billing_address_1() ) {
        $customer->set_billing_address_1( 'N/A' );
    }
    if ( ! $customer->get_billing_city() ) {
        $customer->set_billing_city( 'Ciudad' );
    }
    if ( ! $customer->get_billing_postcode() ) {
        $customer->set_billing_postcode( '00000' );
    }
    if ( ! $customer->get_billing_country() ) {
        $customer->set_billing_country( 'MX' );
    }
    if ( ! $customer->get_billing_email() ) {
        $phone = $customer->get_billing_phone() ?: time();
        $customer->set_billing_email( 'guest_' . preg_replace( '/[^0-9]/', '', $phone ) . '@ticketoride.com' );
    }
    
    return $customer;
}
add_filter( 'woocommerce_store_api_checkout_update_customer_from_request', 'cobro_facil_store_api_checkout_update_customer', 10, 2 );

/**
 * Remover validaciones de campos para checkout de bloques.
 */
function cobro_facil_remove_fields_validation( $fields ) {
    $optional_fields = array(
        'billing_last_name', 'billing_company', 'billing_address_1', 
        'billing_address_2', 'billing_city', 'billing_state', 
        'billing_postcode', 'billing_country', 'billing_email'
    );
    
    foreach ( $optional_fields as $field ) {
        if ( isset( $fields[ $field ] ) ) {
            $fields[ $field ]['required'] = false;
        }
    }
    
    return $fields;
}
add_filter( 'woocommerce_default_address_fields', 'cobro_facil_remove_fields_validation', 9999 );

/**
 * Generar email temporal si no se proporciona (WooCommerce lo requiere).
 */
function cobro_facil_generate_guest_email( $data, $errors ) {
    if ( empty( $data['billing_email'] ) ) {
        // Generar email temporal basado en teléfono
        $phone = isset( $data['billing_phone'] ) ? preg_replace( '/[^0-9]/', '', $data['billing_phone'] ) : time();
        $data['billing_email'] = 'guest_' . $phone . '@ticketoride.com';
        $_POST['billing_email'] = $data['billing_email'];
    }
}
add_action( 'woocommerce_after_checkout_validation', 'cobro_facil_generate_guest_email', 10, 2 );

/**
 * Estilos y JS para checkout simplificado.
 * Funciona tanto en checkout clásico como en checkout de bloques.
 */
function cobro_facil_checkout_styles() {
    if ( is_checkout() ) {
        ?>
        <style>
            /* =============================================
               CHECKOUT DE BLOQUES - Ocultar campos específicos
               ============================================= */
            
            /* Ocultar sección de contacto/email */
            .wp-block-woocommerce-checkout-contact-information-block,
            .wc-block-checkout__contact-fields {
                display: none !important;
            }
            
            /* Ocultar campos específicos en billing */
            .wc-block-components-address-form__last_name,
            .wc-block-components-address-form__company,
            .wc-block-components-address-form__address_1,
            .wc-block-components-address-form__address_2,
            .wc-block-components-address-form__city,
            .wc-block-components-address-form__state,
            .wc-block-components-address-form__postcode,
            .wc-block-components-address-form__country {
                display: none !important;
            }
            
            /* Ocultar shipping completo */
            .wp-block-woocommerce-checkout-shipping-address-block,
            .wc-block-checkout__shipping-fields,
            .wc-block-checkout__shipping-option,
            .wp-block-woocommerce-checkout-shipping-method-block,
            .wp-block-woocommerce-checkout-shipping-methods-block {
                display: none !important;
            }
            
            /* Ocultar notas */
            .wp-block-woocommerce-checkout-order-note-block,
            .wc-block-checkout__add-note {
                display: none !important;
            }
            
            /* MOSTRAR nombre y teléfono con alta prioridad */
            .wc-block-components-address-form__first_name,
            .wc-block-components-address-form__phone {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            /* =============================================
               CHECKOUT CLÁSICO - Ocultar campos específicos
               ============================================= */
            
            #billing_email_field,
            #billing_last_name_field,
            #billing_company_field,
            #billing_country_field,
            #billing_address_1_field,
            #billing_address_2_field,
            #billing_city_field,
            #billing_state_field,
            #billing_postcode_field,
            #order_comments_field,
            .woocommerce-shipping-fields,
            #ship-to-different-address,
            .woocommerce-additional-fields {
                display: none !important;
            }
            
            /* MOSTRAR nombre y teléfono en clásico */
            #billing_first_name_field,
            #billing_phone_field {
                display: block !important;
            }
            
            /* Estilos bonitos para campos */
            .wc-block-components-text-input input,
            .woocommerce-checkout #billing_first_name,
            .woocommerce-checkout #billing_phone {
                padding: 15px !important;
                font-size: 16px !important;
                border-radius: 10px !important;
                border: 2px solid #e0e0e0 !important;
            }
            
            .wc-block-components-text-input input:focus,
            .woocommerce-checkout #billing_first_name:focus,
            .woocommerce-checkout #billing_phone:focus {
                border-color: #667eea !important;
                outline: none !important;
            }
        </style>
        
        <script>
        (function() {
            // Valores por defecto para campos ocultos
            var defaultValues = {
                'last_name': '-',
                'last-name': '-',
                'company': '',
                'address_1': 'N/A',
                'address-1': 'N/A',
                'address_2': '',
                'address-2': '',
                'city': 'Ciudad',
                'state': '',
                'postcode': '00000',
                'postal': '00000',
                'country': 'MX',
                'email': 'guest_' + Date.now() + '@ticketoride.com'
            };
            
            function setupCheckoutFields() {
                // Lista de campos a OCULTAR
                var fieldsToHide = [
                    'email', 'last_name', 'last-name', 'company', 
                    'address_1', 'address_2', 'address-1', 'address-2',
                    'city', 'state', 'postcode', 'country', 'postal'
                ];
                
                // Lista de campos a MOSTRAR (nunca ocultar estos)
                var fieldsToShow = ['first_name', 'first-name', 'phone'];
                
                // Función para verificar si un elemento debe mostrarse
                function shouldShow(el) {
                    var id = (el.id || '').toLowerCase();
                    var className = (el.className || '').toLowerCase();
                    var text = id + ' ' + className;
                    
                    for (var i = 0; i < fieldsToShow.length; i++) {
                        if (text.indexOf(fieldsToShow[i]) !== -1) {
                            return true;
                        }
                    }
                    return false;
                }
                
                // Función para verificar si debe ocultarse
                function shouldHide(el) {
                    var id = (el.id || '').toLowerCase();
                    var className = (el.className || '').toLowerCase();
                    var text = id + ' ' + className;
                    
                    for (var i = 0; i < fieldsToHide.length; i++) {
                        if (text.indexOf(fieldsToHide[i]) !== -1) {
                            return true;
                        }
                    }
                    return false;
                }
                
                // Encontrar y llenar campos con valores por defecto
                function fillHiddenFields() {
                    var allInputs = document.querySelectorAll('input, select');
                    allInputs.forEach(function(input) {
                        var id = (input.id || '').toLowerCase();
                        var name = (input.name || '').toLowerCase();
                        var text = id + ' ' + name;
                        
                        // No modificar nombre o teléfono
                        for (var i = 0; i < fieldsToShow.length; i++) {
                            if (text.indexOf(fieldsToShow[i]) !== -1) {
                                return;
                            }
                        }
                        
                        // Llenar con valores por defecto si está vacío
                        for (var key in defaultValues) {
                            if (text.indexOf(key) !== -1 && !input.value) {
                                // Disparar evento de cambio para que React lo detecte
                                var nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
                                if (input.tagName === 'INPUT' && nativeInputValueSetter) {
                                    nativeInputValueSetter.call(input, defaultValues[key]);
                                } else {
                                    input.value = defaultValues[key];
                                }
                                input.dispatchEvent(new Event('input', { bubbles: true }));
                                input.dispatchEvent(new Event('change', { bubbles: true }));
                                break;
                            }
                        }
                    });
                    
                    // Para select de país
                    var countrySelects = document.querySelectorAll('select[id*="country"], select[name*="country"]');
                    countrySelects.forEach(function(select) {
                        if (!select.value || select.value === '') {
                            var mxOption = select.querySelector('option[value="MX"]');
                            if (mxOption) {
                                select.value = 'MX';
                                select.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        }
                    });
                }
                
                // Procesar todos los inputs y sus contenedores
                var allContainers = document.querySelectorAll('.wc-block-components-text-input, .form-row, .wc-block-components-country-input, .wc-block-components-state-input');
                
                allContainers.forEach(function(container) {
                    if (shouldShow(container)) {
                        container.style.display = 'block';
                        container.style.visibility = 'visible';
                    } else if (shouldHide(container)) {
                        container.style.display = 'none';
                    }
                });
                
                // Ocultar secciones completas
                var sectionsToHide = [
                    '.wp-block-woocommerce-checkout-contact-information-block',
                    '.wc-block-checkout__contact-fields',
                    '.wp-block-woocommerce-checkout-shipping-address-block',
                    '.wc-block-checkout__shipping-fields',
                    '.wp-block-woocommerce-checkout-order-note-block',
                    '.wc-block-checkout__add-note'
                ];
                
                sectionsToHide.forEach(function(selector) {
                    var elements = document.querySelectorAll(selector);
                    elements.forEach(function(el) {
                        el.style.display = 'none';
                    });
                });
                
                // Llenar campos ocultos con valores por defecto
                fillHiddenFields();
            }
            
            // Ejecutar cuando el DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', setupCheckoutFields);
            } else {
                setupCheckoutFields();
            }
            
            // Ejecutar periódicamente para React/bloques
            setInterval(setupCheckoutFields, 500);
            
            // Observer para cambios dinámicos
            var observer = new MutationObserver(function() {
                setTimeout(setupCheckoutFields, 100);
            });
            
            setTimeout(function() {
                var checkout = document.querySelector('.wc-block-checkout, .woocommerce-checkout');
                if (checkout) {
                    observer.observe(checkout, { childList: true, subtree: true });
                }
            }, 1000);
        })();
        </script>
        <?php
    }
}
add_action( 'wp_head', 'cobro_facil_checkout_styles', 999 );
add_action( 'wp_footer', 'cobro_facil_checkout_styles', 999 );

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
    
    // Mensaje para WhatsApp - Construir URL manualmente para asegurar saltos de línea
    $nl = '%0A'; // Salto de línea para WhatsApp
    $whatsapp_text = "🎟️ *Ticket to Ride - Mi Entrada*{$nl}{$nl}";
    $whatsapp_text .= "Tu código de acceso: *{$code}*{$nl}{$nl}";
    $whatsapp_text .= "📱 Accede a tu entrada aquí:{$nl}";
    $whatsapp_text .= rawurlencode( $access_url ) . "{$nl}{$nl}";
    $whatsapp_text .= rawurlencode( "Guarda este mensaje para acceder a tu QR cuando lo necesites." );
    
    // Formatear número para wa.me (quitar espacios, guiones, etc)
    $phone_clean = preg_replace( '/[^0-9]/', '', $phone );
    
    // Si no tiene código de país, agregar +52 (México) por defecto
    if ( strlen( $phone_clean ) === 10 ) {
        $phone_clean = '52' . $phone_clean;
    }
    
    $whatsapp_url = 'https://wa.me/' . $phone_clean . '?text=' . $whatsapp_text;
    
    // Cargar estilos
    wp_enqueue_style( 'cobro-facil-styles', COBRO_FACIL_PLUGIN_URL . 'assets/css/cobro-facil.css', array(), COBRO_FACIL_VERSION );
    
    ?>
    <div class="cobro-facil-thankyou-box">
        <div class="cobro-facil-icon">✓</div>
        <h2>¡Compra exitosa!</h2>
        <p class="cobro-facil-subtitle">Ahora envía tu entrada a WhatsApp</p>
        
        <div class="cobro-facil-steps">
            <div class="cobro-facil-step">
                <span class="step-number">1</span>
                <span class="step-text">Presiona el botón verde para enviarte el código a tu WhatsApp</span>
            </div>
            <div class="cobro-facil-step">
                <span class="step-number">2</span>
                <span class="step-text">Abre el link que recibirás en el mensaje</span>
            </div>
            <div class="cobro-facil-step">
                <span class="step-number">3</span>
                <span class="step-text">Ingresa tu código de 6 dígitos para ver tu QR</span>
            </div>
        </div>
        
        <div class="cobro-facil-code-section">
            <p class="cobro-facil-label">Tu código:</p>
            <div class="cobro-facil-code"><?php echo esc_html( $code ); ?></div>
            <p class="cobro-facil-code-note">Este código NO es tu entrada. Úsalo para acceder a tu QR.</p>
        </div>
        
        <a href="<?php echo esc_attr( $whatsapp_url ); ?>" target="_blank" class="cobro-facil-whatsapp-btn">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            Enviar a mi WhatsApp
        </a>
    </div>
    <?php
}
// Prioridad 1 para mostrar ANTES del QR de QRCompleto (que usa prioridad 10)
add_action( 'woocommerce_thankyou', 'cobro_facil_thankyou_content', 1 );

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
    
    $html .= '<p class="cobro-facil-screenshot-note">📸 Toma una captura de pantalla de tu QR y muéstrala en el acceso del evento</p>';
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
