/**
 * Cobro Fácil - Acceso con código
 */
jQuery(function($) {
    'use strict';

    var $form = $('#cobro-facil-access-form');
    var $input = $('#cobro-facil-code-input');
    var $error = $('#cobro-facil-error');
    var $formSection = $('#cobro-facil-form-section');
    var $resultSection = $('#cobro-facil-result-section');

    // Auto-focus en el input
    $input.focus();

    // Solo permitir números
    $input.on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Enviar formulario
    $form.on('submit', function(e) {
        e.preventDefault();

        var code = $input.val().trim();

        if (code.length !== 6) {
            showError('Por favor ingresa un código de 6 dígitos.');
            return;
        }

        // Deshabilitar botón mientras carga
        var $btn = $form.find('button[type="submit"]');
        var originalText = $btn.text();
        $btn.prop('disabled', true).text('Verificando...');
        $error.hide();

        $.ajax({
            url: cobroFacil.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cobro_facil_validate_code',
                code: code,
                nonce: cobroFacil.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Mostrar resultado
                    $formSection.hide();
                    $resultSection.html(response.data.html).show();
                } else {
                    showError(response.data.message || 'Código no válido.');
                    $btn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                showError('Error de conexión. Intenta de nuevo.');
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });

    function showError(message) {
        $error.text(message).show();
        $input.focus().select();
    }
});
