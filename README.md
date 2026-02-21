# Cobro Fácil

Plugin de WordPress para personalizar y mejorar el checkout de WooCommerce.

## Características

- ✅ Personalización de campos del checkout
- ✅ Reordenamiento de campos de facturación
- ✅ Campos personalizados adicionales
- ✅ Estilos modernos y responsivos
- ✅ Integración con Git Updater para actualizaciones automáticas

## Requisitos

- WordPress 5.0+
- WooCommerce 5.0+
- PHP 7.4+

## Instalación

### Método 1: Instalación manual
1. Descarga el plugin como ZIP
2. Ve a WordPress Admin > Plugins > Añadir nuevo > Subir plugin
3. Selecciona el archivo ZIP y haz clic en "Instalar ahora"
4. Activa el plugin

### Método 2: Con Git Updater
1. Instala y activa [Git Updater](https://git-updater.com/)
2. Ve a Settings > Git Updater > Install Plugin
3. Ingresa: `longevai/cobro-facil`
4. Haz clic en "Install Plugin"

## Configuración de Git Updater

Este plugin está preparado para actualizaciones automáticas mediante Git Updater.

### Headers incluidos:
```php
GitHub Plugin URI: longevai/cobro-facil
Primary Branch:    main
Release Asset:     true
```

### Webhook para actualizaciones automáticas

En tu repositorio de GitHub:
1. Ve a Settings > Webhooks > Add webhook
2. Payload URL: `https://ticketoride.com/wp-json/git-updater/v1/update/?key=974bff961fb4de8fdeb189dcf3828f36`
3. Content type: `application/json`
4. Selecciona: "Just the push event"
5. Guarda el webhook

### Endpoints disponibles:

**Actualizar plugin:**
```
POST https://ticketoride.com/wp-json/git-updater/v1/update/?key=974bff961fb4de8fdeb189dcf3828f36
```

**Resetear a branch:**
```
POST https://ticketoride.com/wp-json/git-updater/v1/reset-branch/?key=974bff961fb4de8fdeb189dcf3828f36
```

## Desarrollo

### Flujo de trabajo

1. Haz cambios localmente
2. Commit y push a GitHub:
   ```bash
   git add .
   git commit -m "Descripción del cambio"
   git push origin main
   ```
3. El webhook notificará automáticamente a tu sitio WordPress
4. Git Updater descargará e instalará la actualización

### Actualizar versión

Para una nueva versión:
1. Actualiza `Version: X.X.X` en `cobro-facil.php`
2. Actualiza `COBRO_FACIL_VERSION` en `cobro-facil.php`
3. Commit y push
4. Opcionalmente, crea un Release en GitHub

## Estructura del plugin

```
cobro-facil/
├── cobro-facil.php          # Archivo principal del plugin
├── includes/
│   └── class-cobro-facil-checkout.php  # Clase de checkout
├── assets/
│   └── css/
│       └── checkout.css     # Estilos del checkout
└── README.md
```

## Hooks disponibles

### Filtros
- `woocommerce_checkout_fields` - Personalizar campos
- `woocommerce_billing_fields` - Reordenar campos de facturación

### Acciones
- `woocommerce_after_checkout_billing_form` - Agregar campos personalizados
- `woocommerce_checkout_process` - Validar campos
- `woocommerce_checkout_update_order_meta` - Guardar datos del pedido

## Licencia

GPL-2.0+

## Autor

Longevai - [GitHub](https://github.com/longevai)
