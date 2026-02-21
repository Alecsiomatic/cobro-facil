<?php
/**
 * Clase principal para personalizar el checkout de WooCommerce.
 *
 * @package Cobro_Facil
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase Cobro_Facil_Checkout.
 */
class Cobro_Facil_Checkout {

    /**
     * Inicializar hooks y filtros.
     */
    public function init() {
        // Personalizar campos del checkout
        add_filter( 'woocommerce_checkout_fields', array( $this, 'customize_checkout_fields' ) );

        // Agregar estilos personalizados
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );

        // Personalizar el orden de los campos
        add_filter( 'woocommerce_billing_fields', array( $this, 'reorder_billing_fields' ), 20 );

        // Agregar campos personalizados después del formulario de pago
        add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'add_custom_checkout_fields' ) );

        // Validar campos personalizados
        add_action( 'woocommerce_checkout_process', array( $this, 'validate_custom_fields' ) );

        // Guardar campos personalizados
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_custom_fields' ) );

        // Simplificar el proceso de checkout
        add_filter( 'woocommerce_checkout_fields', array( $this, 'simplify_checkout' ) );
    }

    /**
     * Personalizar campos del checkout.
     *
     * @param array $fields Campos del checkout.
     * @return array
     */
    public function customize_checkout_fields( $fields ) {
        // Hacer campos opcionales
        if ( isset( $fields['billing']['billing_company'] ) ) {
            $fields['billing']['billing_company']['required'] = false;
        }

        // Personalizar placeholders
        if ( isset( $fields['billing']['billing_phone'] ) ) {
            $fields['billing']['billing_phone']['placeholder'] = __( 'Tu número de teléfono', 'cobro-facil' );
        }

        if ( isset( $fields['billing']['billing_email'] ) ) {
            $fields['billing']['billing_email']['placeholder'] = __( 'tu@email.com', 'cobro-facil' );
        }

        return $fields;
    }

    /**
     * Simplificar el proceso de checkout.
     *
     * @param array $fields Campos del checkout.
     * @return array
     */
    public function simplify_checkout( $fields ) {
        // Remover campos de dirección 2 si no son necesarios
        // unset( $fields['billing']['billing_address_2'] );
        
        // Remover campo de compañía si no es necesario
        // unset( $fields['billing']['billing_company'] );

        return $fields;
    }

    /**
     * Reordenar campos de facturación.
     *
     * @param array $fields Campos de facturación.
     * @return array
     */
    public function reorder_billing_fields( $fields ) {
        // Definir el nuevo orden de los campos
        $order = array(
            'billing_first_name',
            'billing_last_name',
            'billing_email',
            'billing_phone',
            'billing_country',
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_state',
            'billing_postcode',
            'billing_company',
        );

        $ordered_fields = array();
        $priority = 10;

        foreach ( $order as $field_key ) {
            if ( isset( $fields[ $field_key ] ) ) {
                $ordered_fields[ $field_key ] = $fields[ $field_key ];
                $ordered_fields[ $field_key ]['priority'] = $priority;
                $priority += 10;
            }
        }

        // Agregar campos restantes que no estaban en el orden
        foreach ( $fields as $field_key => $field ) {
            if ( ! isset( $ordered_fields[ $field_key ] ) ) {
                $ordered_fields[ $field_key ] = $field;
                $ordered_fields[ $field_key ]['priority'] = $priority;
                $priority += 10;
            }
        }

        return $ordered_fields;
    }

    /**
     * Agregar campos personalizados al checkout.
     *
     * @param WC_Checkout $checkout Objeto checkout.
     */
    public function add_custom_checkout_fields( $checkout ) {
        echo '<div class="cobro-facil-custom-fields">';
        echo '<h3>' . esc_html__( 'Información Adicional', 'cobro-facil' ) . '</h3>';

        // Campo de notas especiales
        woocommerce_form_field(
            'cobro_facil_notes',
            array(
                'type'        => 'textarea',
                'class'       => array( 'form-row-wide' ),
                'label'       => __( 'Notas especiales para tu pedido', 'cobro-facil' ),
                'placeholder' => __( 'Instrucciones de entrega, preferencias, etc.', 'cobro-facil' ),
                'required'    => false,
            ),
            $checkout->get_value( 'cobro_facil_notes' )
        );

        echo '</div>';
    }

    /**
     * Validar campos personalizados.
     */
    public function validate_custom_fields() {
        // Agregar validaciones personalizadas aquí
        // Ejemplo:
        // if ( empty( $_POST['cobro_facil_custom_field'] ) ) {
        //     wc_add_notice( __( 'Por favor completa el campo personalizado.', 'cobro-facil' ), 'error' );
        // }
    }

    /**
     * Guardar campos personalizados en el pedido.
     *
     * @param int $order_id ID del pedido.
     */
    public function save_custom_fields( $order_id ) {
        if ( ! empty( $_POST['cobro_facil_notes'] ) ) {
            update_post_meta( $order_id, '_cobro_facil_notes', sanitize_textarea_field( wp_unslash( $_POST['cobro_facil_notes'] ) ) );
        }
    }

    /**
     * Cargar estilos del plugin.
     */
    public function enqueue_styles() {
        if ( is_checkout() ) {
            wp_enqueue_style(
                'cobro-facil-checkout',
                COBRO_FACIL_PLUGIN_URL . 'assets/css/checkout.css',
                array(),
                COBRO_FACIL_VERSION
            );
        }
    }
}
