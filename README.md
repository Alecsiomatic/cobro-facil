# Cobro Fácil

Sistema de acceso seguro a entradas con código de 6 dígitos y envío por WhatsApp para WooCommerce.

## Características

- ✅ Genera código de acceso único de 6 dígitos por pedido
- ✅ Botón para enviar código a WhatsApp del cliente
- ✅ Página de acceso segura con validación de código
- ✅ Compatible con QRCompleto para mostrar QR de entrada
- ✅ Diseño moderno y responsive
- ✅ Integración con Git Updater para actualizaciones automáticas

## Flujo de uso

1. **Cliente compra** (puede ser guest checkout)
2. **Se genera código** de 6 dígitos automáticamente
3. **Página "Gracias"** muestra código + botón WhatsApp
4. **Cliente guarda** el código en su WhatsApp
5. **Para ver entrada** → va a `/mi-entrada` e ingresa código
6. **Ve su QR** y detalles del pedido

## Requisitos

- WordPress 5.0+
- WooCommerce 5.0+
- PHP 7.4+
- QRCompleto (opcional, para mostrar QR)

## Instalación

### Con Git Updater
1. Instala y activa [Git Updater](https://git-updater.com/)
2. Ve a Settings > Git Updater > Install Plugin
3. Ingresa: `Alecsiomatic/cobro-facil`
4. Haz clic en "Install Plugin"

### Manual
1. Descarga el plugin como ZIP
2. WordPress Admin > Plugins > Añadir nuevo > Subir plugin
3. Activa el plugin

## Configuración

El plugin crea automáticamente la página `/mi-entrada` al activarse.

Si necesitas crearla manualmente:
1. Crea una página con slug `mi-entrada`
2. Agrega el shortcode: `[mi_entrada]`

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[mi_entrada]` | Formulario de acceso con código de 6 dígitos |

## Git Updater

### Headers incluidos:
```php
GitHub Plugin URI: Alecsiomatic/cobro-facil
Primary Branch:    main
Release Asset:     true
```

## Licencia

GPL-2.0+

## Autor

Alecsiomatic - [GitHub](https://github.com/Alecsiomatic)
