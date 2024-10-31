<?php

class WC_Pagodigital_Gateway extends WC_Payment_Gateway
{

	//Se setea el gateway id, la descripcion y otros valores
	function __construct()
	{
		$this->id = "woocommerce_pagodigital";
		//El titulo mostrado en la parte superior de los gateways de pago
		$this->method_title = __("PagoDigital Gateway", "woocommerce-pagodigital");
		//La descripcion en las formas de pago
		$this->method_description = __("Plugin del Gateway de Pago de PagoDigital");
		//El titulo usado en los tabs verticales
		$this->title = __("PagoDigital Gateway", 'woocommerce-pagodigital');
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

		add_action('woocommerce_api_pagodigital',  array($this, 'return_handler'));

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

	// Se cancela todas las ordenes que registran el timeout y se llama el rollback en pagodigital
	public function process_refund($order_id, $amount = null, $reason = "")
	{
		throw new Exception(__('No existen reembolsos por pagodigital.', 'woocommerce-pagodigital'));
		return FALSE;
	}



	// Se construyen los campos correspondientes al administrador del gateway
	public function init_form_fields()
	{
		$this->form_fields = array(
			// config
			'enabled' => array(
				'title'		=> __('Habilitar / Deshabilitar Pagos con PagoDigital', 'woocommerce-pagodigital'),
				'label'		=> __('Habilitar este metodo de pago', 'woocommerce-pagodigital'),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			'show_footer' => array(
				'title'		=> __('Mostrar el pie de página(footer) de PagoDigital', 'woocommerce-pagodigital'),
				'label'		=> __('Mostrar el pie de página(footer) personalizado de PagoDigital', 'woocommerce-pagodigital'),
				'type'		=> 'checkbox',
				'default'	=> 'yes',
			),
			// ----------------
			'currency' => array(
				'title'		=> __('Tipo de moneda', 'woocommerce-pagodigital'),
				'desc_tip'		=> __('La moneda que mostrara PagoDigital al procesar el pago', 'woocommerce-pagodigital'),
				'type'      => 'select',
				'options'   => array(
					'PYG' => __('PYG', 'woocommerce-pagodigital'),
					'USD' => __('USD', 'woocommerce-pagodigital')
				),
				'default'	=> 'PYG',
			),
			'title' => array(
				'title'		=> __('Titulo', 'woocommerce-pagodigital'),
				'type'		=> 'text',
				'desc_tip'	=> __('Los clientes del pago veran esto durante el proceso de checkout.', 'woocommerce-pagodigital'),
				'default'	=> __('PagoDigital', 'woocommerce-pagodigital'),
			),
			'description' => array(
				'title'		=> __('Descripcion', 'woocommerce-pagodigital'),
				'type'		=> 'textarea',
				'desc_tip'	=> __('La descripcion que los clientes veran en el proceso de pago.', 'woocommerce-pagodigital'),
				'default'	=> __('Pago seguro utilizando PagoDigital.', 'woocommerce-pagodigital'),
				'css'		=> 'max-width:350px;'
			),
			'private_key' => array(
				'title'		=> __('PagoDigital Private Key', 'woocommerce-pagodigital'),
				'type'		=> 'text',
				'desc_tip'	=> __('Esta es la clave privada de su comercio en PagoDigital', 'woocommerce-pagodigital'),
			),
			'public_key' => array(
				'title'		=> __('PagoDigital Public Key', 'woocommerce-pagodigital'),
				'type'		=> 'text',
				'desc_tip'	=> __('Esta es la clave publica de su comercio en PagoDigital.', 'woocommerce-pagodigital'),
			),
			'commerce_id' => array(
				'title'		=> __('Id de Comerio', 'woocommerce-pagodigital'),
				'type'		=> 'text',
				'desc_tip'	=> __('El id de comercio asociado a tu cuenta.', 'woocommerce-pagodigital'),
			),
			'payment_form' => array(
				'title' => __('Formulario de pago', 'woocommerce-pagodigital'),
				'type' => 'title',
				'description' => __('', 'woocommerce-pagodigital'),
			),
			'document_field_name' => array(
				'title' => __('nombre del campo Documento', 'woocommerce-pagodigital'),
				'type' => 'text',
				'default'	=> __('billing_document', 'woocommerce-pagodigital'),
				'desc_tip'		=> __('No es tu documento de identidad, solo es el nombre del campo del documento de identidad.', 'woocommerce-pagodigital'),
				'description' => __('Si tienes un campo personalizado de documento de identidad (CI) en tu formulario de pago, puedes colocar aquí su nombre para enviarlo al formulario de PagoDigital.', 'woocommerce-pagodigital'),
			),
			'url_details' => array(
				'title' => __('Información de URL', 'woocommerce-pagodigital'),
				'type' => 'title',
				'description' => __('Colocar esta información en la sección de "desarrollo" de PagoDigital.', 'woocommerce-pagodigital'),
			),
			'url_thankyou' => array(
				'title' => __('URL de respuesta', 'woocommerce-pagodigital'),
				'type' => 'paragraph',
				'description' => site_url() . '/?confirmar=true',
				'desc_tip' => false,
			),
			'url_redirect' => array(
				'title' => __('URL de redireccionamiento', 'woocommerce-pagodigital'),
				'type' => 'paragraph',
				'description' => $this->obtenerURLPaginaRedireccionamiento(),
				'desc_tip' => false,
			),
			'payment_methods_details' => array(
				'title' => __('Métodos de pago disponibles en tu tienda', 'woocommerce-pagodigital'),
				'type' => 'title',
				'description' => __('', 'woocommerce-pagodigital'),
			),
			// payment methods
			'bancard' => array(
				'title'		=> __('Tarjeta de crédito', 'woocommerce-pagodigital'),
				'label'		=> __('Tarjeta de crédito (Solo Paraguay)', 'woocommerce-pagodigital'),
				'type'		=> 'checkbox',
				'default'	=> 'yes',
			),
			'tigo' => array(
				'title'		=> __('Tigo money', 'woocommerce-pagodigital'),
				'label'		=> __('Tigo money', 'woocommerce-pagodigital'),
				'type'		=> 'checkbox',
				'default'	=> 'yes',
			),
			'personal' => array(
				'title'		=> __('Billetera personal', 'woocommerce-pagodigital'),
				'label'		=> __('Billetera personal', 'woocommerce-pagodigital'),
				'type'		=> 'checkbox',
				'default'	=> 'yes',
			),
			'wally' => array(
				'title'		=> __('Wally', 'woocommerce-pagodigital'),
				'label'		=> __('Wally', 'woocommerce-pagodigital'),
				'type'		=> 'checkbox',
				'default'	=> 'yes',
			),
			'zimple' => array(
				'title'		=> __('Zimple', 'woocommerce-pagodigital'),
				'label'		=> __('Zimple', 'woocommerce-pagodigital'),
				'type'		=> 'checkbox',
				'default'	=> 'yes',
			),
			'paypal' => array(
				'title'		=> __('Paypal', 'woocommerce-pagodigital'),
				'label'		=> __('Paypal', 'woocommerce-pagodigital'),
				'type'		=> 'checkbox',
				'default'	=> 'yes',
			),
			'stripe' => array(
				'title'		=> __('Tarjeta de crédito/débito internacional', 'woocommerce-pagodigital'),
				'label'		=> __('Tarjeta de crédito/débito internacional (Fuera de Paraguay)', 'woocommerce-pagodigital'),
				'type'		=> 'checkbox',
				'default'	=> 'yes',
			),
			'infonet' => array(
				'title'		=> __('Infonet', 'woocommerce-pagodigital'),
				'label'		=> __('Infonet', 'woocommerce-pagodigital'),
				'type'		=> 'checkbox',
				'default'	=> 'yes',
			),
			'wepa' => array(
				'title'		=> __('Wepa', 'woocommerce-pagodigital'),
				'label'		=> __('Wepa', 'woocommerce-pagodigital'),
				'type'		=> 'checkbox',
				'default'	=> 'yes',
			),
			'pago_express' => array(
				'title'		=> __('Pago express', 'woocommerce-pagodigital'),
				'label'		=> __('Pago express', 'woocommerce-pagodigital'),
				'type'		=> 'checkbox',
				'default'	=> 'yes',
			),
			'bancard_qr' => array(
				'title'		=> __('Pago por QR (Home Banking)', 'woocommerce-pagodigital'),
				'label'		=> __('Pago por QR (Home Banking)', 'woocommerce-pagodigital'),
				'type'		=> 'checkbox',
				'default'	=> 'yes',
			),
			'estados_pedidos' => array(
				'title' => __('Manejo de pedidos', 'woocommerce-pagodigital'),
				'type' => 'title',
				'description' => __('', 'woocommerce-pagodigital'),
			),
			'delete_orders' => array(
				'title'		=> __('Eliminar los pedidos', 'woocommerce-pagodigital'),
				'label'		=> __('Eliminar los pedidos si son fallidos', 'woocommerce-pagodigital'),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			'time_to_delete_pending_orders' => array(
				'title'		=> __('Eliminar los pedidos pendientes (Beta)', 'woocommerce-pagodigital'),
				'description'		=> __('Los pedidos pendientes se eliminarán si no se han pagado en el tiempo asignado (Días), Colocar 0 o vacío para no hacer nada.', 'woocommerce-pagodigital'),
				'type'		=> 'number',
				'default'	=> 0,
			),
			'order_state_pd' => array(
				'title' => __('Estado cuando el pedido es pagado', 'woocommerce-pagodigital'),
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'options' => wc_get_order_statuses(),
				'default' => 'wc-processing'
			),
		);
		wp_enqueue_style('pagodigital_hidde', plugins_url('/assets/styles/style.css', __FILE__));
	}

	/**
	 * Obtiene la url de redireccionamiento
	 */
	function obtenerURLPaginaRedireccionamiento()
	{
		$page_pagodigital_response = get_option('page_pagodigital_response');
		$page_pagodigital_response = get_permalink($page_pagodigital_response);
		return $page_pagodigital_response;
	}


	// Se envia el pago y se maneja la respuesta
	public function process_payment($order_id)
	{
		global $woocommerce;

		$available_gateways = WC()->payment_gateways()->payment_gateways();
		$pagodigital_gateway = $available_gateways["woocommerce_pagodigital"];

		$current_user = wp_get_current_user();
		$customer_order = new WC_Order($order_id);
		$payment_method = sanitize_text_field($_POST['pd_radio_choice']);
		$form_name = sanitize_text_field($_POST['billing_first_name'] . " " . $_POST['billing_last_name']);
		$form_email = sanitize_text_field($_POST['billing_email']);
		$form_phone = sanitize_text_field($_POST['billing_phone']);
		$form_document = sanitize_text_field($_POST[$pagodigital_gateway->document_field_name] ?? '');

		$environment_url_individual = 'https://backend.pagodigital.com.py/transaction';
		$environment_url_link = 'https://backend.pagodigital.com.py/transaction/link';
		$environment_url_dollar = 'https://backend.pagodigital.com.py/currency/last-dollar';

		$items = $customer_order->get_items();
		$productos = '';
		$product_amount = $customer_order->get_total();

		// Verifica si la moneda usada es dolar para realizar el cambio
		if ($this->currency == "USD") {
			$response_dollar = wp_remote_get($environment_url_dollar);

			if (is_wp_error($response_dollar))
				throw new Exception(__('Estamos experimentando un problema de conexión al tratar de conectarnos con la plataforma de pagos. Perdon por los inconvenientes.', 'woocommerce-pagodigital'));

			if (empty($response_dollar['body']))
				throw new Exception(__('La respuesta de nuestro proveedor de pago posee un inconveniente.', 'woocommerce-pagodigital'));

			$response_body = wp_remote_retrieve_body($response_dollar);
			$json_devuelto_dollar = json_decode($response_body);

			$dollar_value = floatval($json_devuelto_dollar->data->value);
			$product_amount = $product_amount * $dollar_value;
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
			"commerceId" => $this->commerce_id,
			"token" => $this->public_key,
			"amount" => (int)$product_amount,
			"description" => $productos,
			"reference" => strval($order_id),
			"currency" =>	$this->currency
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
			throw new Exception(__('Estamos experimentando un problema de conexión al tratar de conectarnos con la plataforma de pagos. Perdon por los inconvenientes.', 'woocommerce-pagodigital'));

		if (empty($response['body'])) {
			throw new Exception(__('La respuesta de nuestro proveedor de pago posee un inconveniente, pruebe mas tarde por favor.', 'woocommerce-pagodigital'));
		}

		// Conseguir el cuerpo de la respuesta
		$response_body = wp_remote_retrieve_body($response);
		$json_devuelto = json_decode($response_body);

		//Se guarda el id devuelto para utilizar en el callback
		if ($json_devuelto->data->merchantTransactionId) {
			$current_custom_postmeta = get_post_meta($order_id, 'transactionid', true);
			if ($current_custom_postmeta) {
				delete_post_meta($order_id, 'transactionid');
			}
			add_post_meta($order_id, 'transactionid', $json_devuelto->data->merchantTransactionId);
		}
		$customer_order->save();

		if ($json_devuelto->error == "COMMERCE_TRANSACTION_ID_EXISTS") {
			$woocommerce->cart->empty_cart();
			wp_mail(get_option('admin_email'), "Ya existe una transacción registrada de este pedido " . get_option('blogname'), print_r($json_devuelto, true));
			throw new Exception(__('Ya existe una transacción registrada de este pedido.', 'woocommerce-pagodigital'));
		}

		if ($json_devuelto->errorCode === 3) {
			$woocommerce->cart->empty_cart();
			wp_mail(get_option('admin_email'), "Problema con pago en " . get_option('blogname'), print_r($json_devuelto, true));
			if ($payment_method == 'paypal') {
				throw new Exception(__('El monto minimo para realizar un pago con Paypal es de $ 10 al cambio.', 'woocommerce-pagodigital'));
			} else if ($payment_method == 'stripe') {
				throw new Exception(__('El monto minimo para realizar un pago internacional es de $ 10 al cambio.', 'woocommerce-pagodigital'));
			}
			throw new Exception(__('La respuesta de nuestro proveedor de pago posee un inconveniente, pruebe mas tarde por favor.', 'woocommerce-pagodigital'));
		}

		if (!$json_devuelto->success) {
			$woocommerce->cart->empty_cart();
			wp_mail(get_option('admin_email'), "Problema con pago en " . get_option('blogname'), print_r($json_devuelto, true));
			throw new Exception(__('La respuesta de nuestro proveedor de pago posee un inconveniente, pruebe mas tarde por favor.', 'woocommerce-pagodigital'));
		}

		$woocommerce->cart->empty_cart();

		return array(
			'result' => 'success',
			'redirect' => $json_devuelto->data->link . "?name=" . urlencode($form_name) . "&email=" . urlencode($form_email) . "&phone=" . urlencode($form_phone) . "&document=" . urlencode($form_document) . ""
		);
	}
}
