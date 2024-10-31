<?php

/**
 * Plugin Name: PagoDigital
 * Description: Este plugin agrega la plataforma de pago de pagodigital al wordpress
 * Version: 2.3.6
 * Author: PagoDigital
 * License: GPL2
 */

add_action('plugins_loaded', 'woo_pagodigital_init_gateway_class', 0);
add_action('admin_init', 'autodelete_wc_orders');

add_filter('the_content', 'pagodigital_response_page');

register_activation_hook(__FILE__, 'woocommerce_plugin_activate_in_pagodigital');
register_deactivation_hook(__FILE__, 'deactivate_pagodigital_plugin');

function woo_pagodigital_init_gateway_class()
{
	//Si woocommerce no esta instalado
	if (!class_exists('WC_Payment_Gateway'))
		return;

	//incluir las funciones del gateway
	include_once('woocommerce-pagodigital-gateway.php');

	//incluir las funciones del gateway
	include_once('includes/payment-checkout-description.php');

	//incluir el footer
	include_once('includes/footer.php');

	add_filter('woocommerce_payment_gateways', 'woo_pagodigital_add_gateway_class');

	add_filter('woocommerce_cancel_unpaid_order', 'woo_pagodigital_consultar_ordenes', 10, 2);

	add_action('woocommerce_admin_order_data_after_order_details', 'woo_pagodigital_consultar_ordenes', 11);

	wp_enqueue_style('pagodigital', get_template_directory_uri() . '/assets/styles/style.css');

	add_filter('query_vars',  'add_query_vars', 0);
	add_action('parse_request', 'confirm_in_pago_digital_request', 0);
	add_action('init', 'woo_pagodigital_page_redirect');
}

function woo_pagodigital_add_gateway_class($methods)
{
	//nombre de la clase que implementara el gateway
	$methods[] = 'WC_Pagodigital_Gateway';
	return $methods;
}

function add_query_vars($vars)
{
	$vars[] = 'confirmar';
	return $vars;
}

/**
 * Crea la pagina de redireccionamiento de pago
 */
function create_response_pagodigital_page()
{
	$PageGuid = site_url() . "/pagodigital-buy-response";
	$my_post = array(
		'post_title' => 'Datos del pedido',
		'post_content' => '',
		'post_type' => 'page',
		'post_name' => 'pagodigital-buy-response',
		'post_status' => 'publish',
		'comment_status' => 'closed',
		'ping_status' => 'closed',
		'post_author' => 1,
		'menu_order' => 0,
		'guid' => $PageGuid
	);

	$pageIdentificator = wp_insert_post($my_post, false); // Get Post ID - FALSE to return 0 instead of wp_error.
	update_option('page_pagodigital_response', $pageIdentificator);
}

/**
 * Verifica que los plugin padres esten activos
 */
function woocommerce_plugin_activate_in_pagodigital()
{
	// Require parent plugin
	if (!is_plugin_active('woocommerce/woocommerce.php') and current_user_can('activate_plugins')) {
		// Stop activation redirect and show error
		wp_die('Lo sentimos, pero este plugin requiere <a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a> para ser activado. <br><a href="' . admin_url('plugins.php') . '">&laquo; Volver a Plugins</a>');
	} else {
		create_response_pagodigital_page();
	}
}

/**
 * Accion que se ejecuata cuando el plugin se desactiva
 */
function deactivate_pagodigital_plugin()
{
	$page_id = get_option('page_pagodigital_response');
	wp_delete_post($page_id);
}

/**
 * Obtiene la pagina de detalles de la respuesta de pagodigital
 * @param $content
 */
function getPagoDigitalResponsePage($content)
{
	$output = $content;
	try {

		$success = $_GET['messageError'] == 'null' || $_GET['messageError'] == 'undefined';
		$transaction_id = $_GET['transactionId'];
		$reference = $_GET['reference'];
		$commerce_id = $_GET['commerceId'];
		$message_error = $_GET['messageError'];
		$transaction_type = $_GET['transactionType'];

		if ($success) {
			$output .= "<h2>¡Gracias por su compra!</h2>";
			$output .= "<p>Hemos recibido su pago.</p>";
		} else {
			$output .= "<h2>Transacción incompleta</h2>";
			$output .= "<p>El pago no fue realizado.</p>";
			$output .= "<p>Mensaje de PagoDigital: " . $message_error . "</p>";
		}

		$output .= "<div class='cuadroResumen'>";
		$output .= "<h4 class='dataTitle'>Datos del pedido:</h4>";

		$output .= "<p><strong>Nro. de transferencia:</strong> " . $transaction_id . " " . "</p>";
		$output .= "<p><strong>Referencia del pago:</strong> " . $reference . " " . "</p>";
		$output .= "<p><strong>Estado del pago:</strong> " . ($success ? "Pagado" : "Rechazado") . "</p>";
		$output .= "<p><strong>Forma de pago:</strong> " . $transaction_type . "</p>";
		$output .= "</div>";


		$output = '<div>' . $output . '</div>';

		if ($success) {
			$customer_order = new WC_Order((int)$reference);
			$downloads = $customer_order->get_downloadable_items();

			if (!empty($downloads)) {
				$output .= '<section class="woocommerce-order-downloads">
				<table class="woocommerce-table woocommerce-table--order-downloads shop_table shop_table_responsive order_details">
				<thead>
				<tr>';
				foreach (wc_get_account_downloads_columns() as $column_id => $column_name) {
					$output .= '<th class="' . esc_attr($column_id) . '"><span class="nobr">' . esc_html($column_name) . '</span></th>';
				}
				$output .= '</tr>
				</thead>';
				foreach ($downloads as $download) {
					$output .= '<tr>';
					foreach (wc_get_account_downloads_columns() as $column_id => $column_name) {
						$output .= '<td class="' . esc_attr($column_id) . '" data-title="' . esc_attr($column_name) . '">';

						switch ($column_id) {
							case 'download-product':
								if ($download['product_url']) {
									$output .= '<a href="' . esc_url($download['product_url']) . '">' . esc_html($download['product_name']) . '</a>';
								} else {
									$output .= esc_html($download['product_name']);
								}
								break;
							case 'download-file':
								$output .= '<a href="' . esc_url($download['download_url']) . '" class="woocommerce-MyAccount-downloads-file button alt">' . esc_html($download['download_name']) . '</a>';
								break;
							case 'download-remaining':
								$output .= is_numeric($download['downloads_remaining']) ? esc_html($download['downloads_remaining']) : esc_html__('&infin;', 'woocommerce');
								break;
							case 'download-expires':
								if (!empty($download['access_expires'])) {
									$output .= '<time datetime="' . esc_attr(date('Y-m-d', strtotime($download['access_expires']))) . '" title="' . esc_attr(strtotime($download['access_expires'])) . '">' . esc_html(date_i18n(get_option('date_format'), strtotime($download['access_expires']))) . '</time>';
								} else {
									$output .= '<p>' . esc_html__('Never', 'woocommerce') . '</p>';
								}
								break;
						}
						$output .= '</td>';
					}
					$output .= '</tr>';
				}
				$output .= '</table>';
				$output .= '  </section>';
			}
		}

		$output .= '
<style>
.dataTitle {margin-top:0px;}
.cuadroResumen {background: #fcfcfc;
    border: solid 1px #eee;
    padding: 15px;
    line-height: 14px;
    font-size: 15px;margin-top:15px;}

.consultarBanco {font-size:14px;}

</style>

        ';

		return $output;
	} catch (\Throwable $th) {
		error_log($th);
		$output .= print_r($th, true);
		return $output;
	}
}

/**
 * Si la pagina ingresada es la de redireccionamientod e pagodigital muestra la informacion detallada del pedido
 * @param string $content
 */
function pagodigital_response_page($content)
{
	$page_id = get_option('page_pagodigital_response');
	if (is_page($page_id)) {
		$newContent = getPagoDigitalResponsePage($content);
		return $newContent;
	}

	return $content;
}

//Se consulta el estado de un orden para luego cambiar su estado
function woo_pagodigital_consultar_ordenes($orden)
{
	$available_gateways = WC()->payment_gateways()->payment_gateways();

	if (isset($available_gateways['woocommerce_pagodigital']) && $available_gateways['woocommerce_pagodigital']->enabled == 'yes') {

		// Conseguimos el gateway de pagodigital
		$gateway = $available_gateways['woocommerce_pagodigital'];

		// Decide a cual url de Pagodigital pedir la confirmacion de la transaccion
		$environment_url = 'https://backend.pagodigital.com.py/transaction/get';

		$url = $environment_url . '/' . get_post_meta($orden->get_id(), 'transactionid', true);


		// Enviar el payload a Pagodigital para que sea procesado
		$response = wp_remote_post($url, array(
			'method'    => 'GET',
			'timeout'   => 90,
			'sslverify' => true,
			'headers' => array(
				'Content-Type' => 'application/json',
				'REFERER' => ""
			)
		));

		if (is_wp_error($response))
			throw new Exception(__('No puede contactarse con PagoDigital.', 'woocommerce-pagodigital'));

		if (empty($response['body']))
			throw new Exception(__('La respuesta del proveedor de pago posee un inconveniente en la consulta, pruebe mas tarde por favor.', 'woocommerce-pagodigital'));

		$response_body = wp_remote_retrieve_body($response);
		$json_devuelto = json_decode($response_body);

		if ($json_devuelto->success) {
			if (isset($json_devuelto->data)) {
				$operacion = $json_devuelto->data;
				$id_devuelto = $operacion->id;

				if ($id_devuelto != '') {
					$codigo_respuesta_devuelto = $operacion->status;
					// Deshabilitado por inconcistencia al cambiar el estado de forma directa en woocommerce
					/* error_log($codigo_respuesta_devuelto);
					if ($codigo_respuesta_devuelto == 'APPROVED') {
						$orden->update_status(substr($gateway->order_state_pd, 3));
					} else {
						$orden->update_status('failed');
					} */
				}
			}
		} elseif ($json_devuelto->status == 'error') {

			$orden->update_status('cancelled');
		}
	}

	return;
}

function woo_pagodigital_page_redirect()
{
	global $wp_rewrite;
	//set up our query variable %test% which equates to index.php?test= 
	add_rewrite_tag('%confirmar%', '([^&]+)');
	//add rewrite rule that matches /confirmar
	add_rewrite_rule('^confirmar/?', 'index.php?confirmar=confirmar', 'top');
	//add endpoint, in this case 'confirmar' to satisfy our rewrite rule /confirmar
	add_rewrite_endpoint('confirmar', EP_PERMALINK | EP_PAGES);
	//flush rules to get this to work properly (do this once, then comment out)
	$wp_rewrite->flush_rules();
}


function confirm_in_pago_digital_request($query)
{
	global $wp;
	if (isset($wp->query_vars['confirmar'])) {
		handle_pago_digital_request($query);
		exit;
	}
}


function handle_pago_digital_request($query)
{
	global $wp, $woocommerce;
	try {
		header("Access-Control-Allow-Origin: *");
		header('Content-Type: application/json; charset=' . get_option('blog_charset'));

		if (!class_exists('WC_Payment_Gateways')) {
			return;
		}

		$available_gateways = WC()->payment_gateways()->payment_gateways();

		if (isset($available_gateways['woocommerce_pagodigital']) && $available_gateways['woocommerce_pagodigital']->enabled == 'yes') {

			$gateway = $available_gateways['woocommerce_pagodigital'];
			$cuerpo = file_get_contents('php://input');
			$json_devuelto = json_decode($cuerpo);

			if (isset($json_devuelto->commerce_transaction_id)) {
				$id_devuelto = $json_devuelto->commerce_transaction_id;

				if ($id_devuelto != '') {
					$token_devuelto = $json_devuelto->token;
					$cantidad_devuelto = $json_devuelto->amount;
					$codigo_respuesta_devuelto = $json_devuelto->status;

					$orden = new WC_Order($id_devuelto);

					$token_comprobacion = hash("sha256", get_post_meta($orden->get_id(), 'transactionid', true) . floatval($cantidad_devuelto) . $gateway->private_key);

					if ($token_comprobacion == $token_devuelto) {
						if ($codigo_respuesta_devuelto == 'APPROVED') {
							$msg['message'] = "Gracias por comprar con nosotros.";
							$msg['class'] = 'woocommerce_pagodigital';
							$orden->add_order_note('Pago exitoso con PagoDigital');
							$orden->add_order_note($msg['message']);
							$orden->update_status(substr($gateway->order_state_pd, 3));
						} elseif ($codigo_respuesta_devuelto == 'REFUSED') {
							$msg['message'] = "Hubo un problema al procesar el pago.";
							$msg['class'] = 'woocommerce_pagodigital';
							$orden->add_order_note('Pago fallido.');
							$orden->add_order_note($msg['message']);
							$orden->update_status('failed');

							if ($gateway->delete_orders === "yes") {
								wp_delete_post($id_devuelto, true);
							}
						} elseif ($codigo_respuesta_devuelto == 'PENDING') {

							$msg['class'] = 'woocommerce_pagodigital';
							$orden->add_order_note('El pago está a la espera de ser procesado');
							$orden->add_order_note($msg['message']);
							$orden->update_status('on-hold');
						} else {

							$msg['message'] = "Hubo un problema al realizar la transaccion. Espere un momento y pruebe nuevamente.";
							$msg['class'] = 'woocommerce_pagodigital';
							$orden->update_status('failed');
						}
					}
				}
			}

			wp_send_json(array(
				'success'	=> true,
				'msg'		=> 'Respuesta exitosa',
				'token_comprobacion' => $token_comprobacion
			));
		} else {
			wp_send_json(array(
				'success'	=> false,
				'msg'		=> 'No se encuentra habilitada la plataforma de pago',
				'available_gateways' => $available_gateways
			));
		}
	} catch (\Throwable $th) {
		wp_send_json(array(
			'success'	=> false,
			'msg'		=> 'Ocurrio un error inesperado',
			'available_gateways' => null,
			'error' => $th->getMessage()
		));
	}
}

/**
 * Elimina los pedidos pendientes en un tiempo asignado
 */
function autodelete_wc_orders()
{
	$available_gateways = WC()->payment_gateways()->payment_gateways();
	$gateway = $available_gateways['woocommerce_pagodigital'];

	if (!$gateway->time_to_delete_pending_orders || $gateway->time_to_delete_pending_orders == 0) {
		return;
	}

	$query = (array(
		'limit'   => 20,
		'orderby' => 'date',
		'order'   => 'DESC',
		'status'  => array('wc-pending', 'wc-ywraq-new', 'wc-ywraq- 
        pending')
	));
	$orders = wc_get_orders($query);
	foreach ($orders as $order) {
		$date     = new DateTime($order->get_date_created());
		$today    = new DateTime();
		$interval = $date->diff($today);

		$datediff = $interval->format('%a');

		if ($datediff > $gateway->time_to_delete_pending_orders) {
			wp_delete_post($order->id, true);
		}
	}
}

function woo_pagodigital_action_links($links)
{
	$plugin_links = array('<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout') . '">' . __('Ajustes', 'woo_pagodigital') . '</a>',);

	return array_merge($plugin_links, $links);
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'woo_pagodigital_action_links');
