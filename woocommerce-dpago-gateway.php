<?php

class WC_dpago_Gateway extends WC_Payment_Gateway
{

	//Se setea el gateway id, la descripcion y otros valores
	function __construct()
	{
		$this->id = "woocommerce_dpago";
		//El titulo mostrado en la parte superior de los gateways de pago
		$this->method_title = __("Dpago Gateway", "woocommerce-dpago");
		//La descripcion en las formas de pago
		$this->method_description = __("Plugin del Gateway de Pago de Dpago");
		//El titulo usado en los tabs verticales
		$this->title = __("Dpago Gateway", 'woocommerce-dpago');
		//Si contiene un icono representativo
		$this->icon = null;
		$this->supports = array();
		//Si posee un form para datos de la tarjeta
		$this->has_fields = false;
		//define las configuraciones que seran cargadas con init_settings
		$this->init_form_fields();
		//despues que init_settings() sea llamado, puede obtener las configuraciones y cargarlas
		//en variables ej: $this->title=$this->get_option('title');
		$this->init_settings();

		add_action('woocommerce_api_dpago',  array($this, 'return_handler'));

		//cada una de las variables se harán en variables que se podra usar luego
		foreach ($this->settings as $setting_key => $value) {
			$this->$setting_key = $value;
		}


		// Guardar configuraciones
		if (is_admin()) {
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		}
	}

	public function return_handler()
	{
		$order = new WC_Order(sanitize_text_field($_GET['reference']));
		wp_redirect($order->get_checkout_order_received_url());
	}

	// Se cancela todas las ordenes que registran el timeout y se llama el rollback en dpago
	public function process_refund($order_id, $amount = null, $reason = "")
	{
		throw new Exception(__('No existen reembolsos por dpago.', 'woocommerce-dpago'));
		return FALSE;
	}

	// Se construyen los campos correspondientes al administrador del gateway
	public function init_form_fields()
	{
		$this->form_fields = array(
			// config
			'enabled' => array(
				'title'		=> __('Habilitar / Deshabilitar Pagos con Dpago', 'woocommerce-dpago'),
				'label'		=> __('Habilitar este método de pago', 'woocommerce-dpago'),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			'title' => array(
				'title'		=> __('Título', 'woocommerce-dpago'),
				'type'		=> 'text',
				'desc_tip'	=> __('Los clientes verán esto durante el proceso de checkout.', 'woocommerce-dpago'),
				'default'	=> __('Dpago', 'woocommerce-dpago'),
			),
			'description' => array(
				'title'		=> __('Descripción', 'woocommerce-dpago'),
				'type'		=> 'textarea',
				'desc_tip'	=> __('La descripción que los clientes verán en el proceso de pago.', 'woocommerce-dpago'),
				'default'	=> __('Pago seguro utilizando Dpago.', 'woocommerce-dpago'),
				'css'		=> 'max-width:350px;'
			),
			'private_key' => array(
				'title'		=> __('Dpago llave privada', 'woocommerce-dpago'),
				'type'		=> 'text',
				'desc_tip'	=> __('Esta es la clave privada de su comercio en Dpago.', 'woocommerce-dpago'),
			),
			'public_key' => array(
				'title'		=> __('Dpago llave publica', 'woocommerce-dpago'),
				'type'		=> 'text',
				'desc_tip'	=> __('Esta es la clave pública de su comercio en Dpago.', 'woocommerce-dpago'),
			),
			'commerce_id' => array(
				'title'		=> __('Identificador del Comercio', 'woocommerce-dpago'),
				'type'		=> 'text',
				'desc_tip'	=> __('El ID del comercio asociado a tu cuenta.', 'woocommerce-dpago'),
			),
			'payment_form' => array(
				'title' => __('Formulario de pago', 'woocommerce-dpago'),
				'type' => 'title',
				'description' => __('', 'woocommerce-dpago'),
			),
			'document_field_name' => array(
				'title' => __('Nombre del campo Documento', 'woocommerce-dpago'),
				'type' => 'text',
				'default'	=> __('billing_document', 'woocommerce-dpago'),
				'desc_tip'		=> __('No es tu documento de identidad, solo es el nombre del campo del documento de identidad.', 'woocommerce-dpago'),
				'description' => __('Si tienes un campo personalizado de documento de identidad (CI) en tu formulario de pago, puedes colocar aquí su nombre para enviarlo al formulario de Dpago.', 'woocommerce-dpago'),
			),
			'url_details' => array(
				'title' => __('Información de URL', 'woocommerce-dpago'),
				'type' => 'title',
				'description' => __('Colocar esta información en la sección de "Desarrollo" de tu comercio en Dpago.', 'woocommerce-dpago'),
			),
			'url_thankyou' => array(
				'title' => __('URL de respuesta', 'woocommerce-dpago'),
				'type' => 'paragraph',
				'description' => site_url() . '/?confirmar=true',
				'desc_tip' => false,
				'class' => 'dpago_hidden_input'
			),
			'url_redirect' => array(
				'title' => __('URL de redireccionamiento', 'woocommerce-dpago'),
				'type' => 'paragraph',
				'description' => $this->obtenerURLPaginaRedireccionamiento(),
				'desc_tip' => false,
				'class' => 'dpago_hidden_input'
			),
			'estados_pedidos' => array(
				'title' => __('Manejo de pedidos', 'woocommerce-dpago'),
				'type' => 'title',
				'description' => __('', 'woocommerce-dpago'),
			),
			'delete_orders' => array(
				'title'		=> __('Eliminar los pedidos', 'woocommerce-dpago'),
				'label'		=> __('Eliminar los pedidos si son fallidos', 'woocommerce-dpago'),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			'time_to_delete_pending_orders' => array(
				'title'		=> __('Eliminar los pedidos pendientes (Beta)', 'woocommerce-dpago'),
				'description'		=> __('Los pedidos pendientes se eliminarán si no se han pagado en el tiempo asignado (Días), Colocar 0 o vacío para no hacer nada.', 'woocommerce-dpago'),
				'type'		=> 'number',
				'default'	=> 0,
			),
			'order_state_pd' => array(
				'title' => __('Estado cuando el pedido es pagado', 'woocommerce-dpago'),
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'options' => wc_get_order_statuses(),
				'default' => 'wc-processing'
			),
		);
	}

	/**
	 * Obtiene la url de redireccionamiento
	 */
	function obtenerURLPaginaRedireccionamiento()
	{
		$page_dpago_response = get_option('page_dpago_response');
		$page_dpago_response = get_permalink($page_dpago_response);
		return $page_dpago_response;
	}


	// Se envia el pago y se maneja la respuesta
	public function process_payment($order_id)
	{
		try {
			global $woocommerce;

			if (!$this->commerce_id)
				throw new Exception(__('ID de comercio no asignado.', 'woocommerce-dpago'));

			if (!$this->private_key)
				throw new Exception(__('Llave privada no asignada.', 'woocommerce-dpago'));

			if (!$this->public_key)
				throw new Exception(__('Llave pública no asignada.', 'woocommerce-dpago'));

			$available_gateways = WC()->payment_gateways()->payment_gateways();
			$dpago_gateway = $available_gateways["woocommerce_dpago"];

			$customer_order = new WC_Order($order_id);
			$form_name = sanitize_text_field($_POST['billing_first_name'] . " " . $_POST['billing_last_name']);
			$form_email = sanitize_text_field($_POST['billing_email']);
			$form_phone = sanitize_text_field($_POST['billing_phone']);
			$form_document = sanitize_text_field($_POST[$dpago_gateway->document_field_name] ?? '');

			$payment_gateway_url_link = 'https://pago.dpago.com/link/';
			$environment_url_link = 'https://api.dpago.com/links';

			$items = $customer_order->get_items();
			$productos = '';
			$product_amount = $customer_order->get_total();

			$currency_code = get_post_meta($customer_order->get_id(), '_order_currency', true);

			if (!$currency_code) {
				$currency_code = get_woocommerce_currency();
			}

			//Generacion de la descripcion de lo que se va a pagar
			foreach ($items as $item_id => $item_data) {
				$productos .=  $item_data['name'] . ' x ';
				if ($item_id === array_key_last($items))
					$productos .= wc_get_order_item_meta($item_id, '_qty', true) . '';
				else
					$productos .= wc_get_order_item_meta($item_id, '_qty', true) . ' + ';
			}

			$response = null;

			$request_transaction_link = [
				"amount" => floatval($product_amount),
				"commerceId" => $this->commerce_id,
				"description" => $productos,
				"currency" => $currency_code == "USD" ? "USD" : "PYG",
				"type" => "link",
				"reference" => (string)$order_id
			];

			// Realiza la creacion de link
			$response = wp_remote_post($environment_url_link, array(
				'method'    => 'POST',
				'body'      => json_encode($request_transaction_link),
				'timeout'   => 90,
				'sslverify' => true,
				'headers' => array(
					'Content-Type' => 'application/json',
					'REFERER' => ""
				)
			));

			if (is_wp_error($response))
				throw new Exception(__('Estamos experimentando un problema de conexión al tratar de conectarnos con la plataforma de pagos. Perdon por los inconvenientes.', 'woocommerce-dpago'));

			if (empty($response['body'])) {
				throw new Exception(__('La respuesta de nuestro proveedor de pago posee un inconveniente, pruebe mas tarde por favor.', 'woocommerce-dpago'));
			}

			// Conseguir el cuerpo de la respuesta
			$response_body = wp_remote_retrieve_body($response);
			$json_devuelto = json_decode($response_body);

			//Se guarda el id devuelto para utilizar en el callback
			if ($json_devuelto->id) {
				$current_custom_postmeta = get_post_meta($order_id, 'commerce_reference', true);
				if ($current_custom_postmeta) {
					delete_post_meta($order_id, 'commerce_reference');
				}
				add_post_meta($order_id, 'commerce_reference', $json_devuelto->reference);
			}
			$customer_order->save();

			$woocommerce->cart->empty_cart();

			return array(
				'result' => 'success',
				'redirect' => $payment_gateway_url_link . $json_devuelto->reference . "?name=" . urlencode($form_name) . "&email=" . urlencode($form_email) . "&phone=" . urlencode($form_phone) . "&document=" . urlencode($form_document) . ""
			);
		} catch (Exception $e) {
			throw new Exception(__($e->getMessage(), 'woocommerce-dpago'));
		}
	}
}
