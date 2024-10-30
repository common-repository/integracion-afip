=== AFIP para WooCommerce ===
Contributors: CRPlugins
Tags: afip, afip argentina, facturacion afip, afip woocommerce, afip para woocommerce, facturas afip, afip wordpress
Requires at least: 4.8
Tested up to: 6.6.2
Requires PHP: 7.1
Stable tag: 3.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
Conectá tu tienda con AFIP y facturá tus pedidos, podrás ver, descargar las facturas y más!

== Description ==

Con este plugin podrás conectar tu tienda con los servicios de AFIP.

Podrás facturar para distintos tipos de cliente, generar distintos tipos de facturas, ver las facturas generadas, descargarlas y más.

Este plugin es compatible con MercadoPago, Payway, Mobbex, Pay for Payment for WooCommerce y muchos más.

Este plugin es pago y se maneja bajo una modalidad de subscripción mensual, conectandose a un servicio externo (3rd party) de crplugins.com.ar, no tomamos ni almacenamos ninguna información privada de nuestros usuarios. Mas información en nuestro sitio https://crplugins.com.ar/

== Installation ==

1. Instalá el plugin desde el repositorio de plugins de WordPress
2. Activa el plugin en la pantalla 'Plugins' de tu sitio WordPress

== Frequently Asked Questions ==

= ¿Que tipo de facturas puedo hacer con este plugin? =

Facturas A, B y C

= ¿Se puede ver la factura sin tener que descargarla? =

Si! Podés ver e imprimir la factura sin tener que descargarla antes.

= ¿Este plugin ofrece notas de crédito? =

Si! podes hacer notas de crédito para las facturas A, B y C.

= ¿Por qué usaría este plugin en lugar de otro? =

Ofrecemos seguridad, confiabilidad, un desarrollo constante y sobre todo, un soporte que escucha las necesidades de los vendedores como vos!

= ¿Donde puedo ver los ToS? =

Acá https://crplugins.com.ar/terms-of-use/

== Screenshots ==

1. Visualización de facturas desde los detalles del pedido
2. Editá los detalles de las facturas antes de realizarlas
3. Acciones masivas para todas las órdenes
4. Configuración completa

== Changelog ==

= 3.1.0 =
* Added option for automatic invoice type detection
* Fixed a bug with document detection on blocks checkout

= 3.0.8 =
* Increased timeout for requests from 10 to 15 seconds
* Fixed bug when setting up the plugin for the first time

= 3.0.7 =
* Improved order address detection
* Improved order data UX

= 3.0.6 =
* Improved settings UX
* Fixed error with complex domain sites
* Added more logs when processing orders

= 3.0.5 =
* Improved settings UX
* Fixed error when saving settings

= 3.0.4 =
* Fixed error when saving settings
* Improved license status message
* Added possibility to add a note at the end of an invoice

= 3.0.3 =
* Fixed error on old WooCommerce versions
* Fixed error with virtual products
* Improved errors description

= 3.0.2 =
* Vastly improved pdf generation times
* Added pdf watermark for testing environment
* Fixed customer address not being properly formatted.
* Fixed possible php errors

= 3.0.1 =
* Fixed php error on PHP <8

= 3.0.0 =
* New settings form
* New documentation available
* Improved loading speed
* Added option for customizing tracking mail template
* Added compatibility with checkout blocks
* Added option for adding a custom logo
* Added option for creating invoices for only one item
* Added ability to resend mails
* Use WP Rest API for improving performance
* Added new hooks for developers

= 2.2.1 =
* Store invoice type, invoice number and credit note number into the order
* Fixed typo in translation

= 2.2.0 =
* Added option for adding a company name in the invoice
* Fixed reports unable to be sent.

= 2.1.0 =
* Added email for credit notes
* Added option for customizing email subject and body
* The document number checkout field is now of type number instead of text
* The document number gets saved in the user profile when an order is placed

= 2.0.0 =
* Added credit notes
* Added option to modify the invoice mail subject
* Fixed multiple bugs and errors

= 1.2.0 =
* Added support for WooCommerce Order Fees

= 1.1.0 =
* Fixed translations
* Added option to configure multiple tax conditions

= 1.0.1 =
* Added shipping prices to invoices

= 1.0 =
* First release