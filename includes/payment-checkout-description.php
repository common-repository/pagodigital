<?php

add_filter('woocommerce_gateway_description', 'custom_dpago_gateway', 20, 2);
add_filter('woocommerce_gateway_icon', 'dpago_icon', 10, 2);

add_action('wp_ajax_dpago_custom_form_ajax', 'dpago_custom_form_ajax');
add_action('wp_ajax_nopriv_dpago_custom_form_ajax', 'dpago_custom_form_ajax');

function dpago_icon($icon, $id)
{
    if ($id !== 'woocommerce_dpago') {
        return $icon;
    }

    $available_gateways = WC()->payment_gateways()->payment_gateways();

    if (!$available_gateways['woocommerce_dpago']->commerce_id) {
        return "";
    }

    $environment_url_platforms_link = "https://api.dpago.com/commerces/" . $available_gateways['woocommerce_dpago']->commerce_id . "/platforms";

    $response = wp_remote_post($environment_url_platforms_link, array(
        'method'    => 'GET',
        'timeout'   => 90,
        'sslverify' => true,
        'headers' => array(
            'Content-Type' => 'application/json',
            'REFERER' => ""
        )
    ));

    if (is_wp_error($response))
        throw new Exception(__('Ocurrió un error inesperado al obtener medios de pago.', 'woocommerce-dpago'));

    if (empty($response['body'])) {
        throw new Exception(__('La respuesta de nuestro proveedor de pago posee un inconveniente, pruebe más tarde por favor.', 'woocommerce-dpago'));
    }

    // Conseguir el cuerpo de la respuesta
    $response_body = wp_remote_retrieve_body($response);
    $platforms = json_decode($response_body);

    $logos_html = array_map(function ($item) {
        return '<div style="margin-right: 5px;margin-bottom: 10px;"><img style="max-height: 30px;" src="' . $item->logo . '" alt="Formas de pago" /></div>';
    }, $platforms);

    $images = '';
    foreach ($logos_html as $logo) {
        $images .= $logo;
    }
    return '<div style="width: 100%;display: flex;align-items: center;flex-wrap: wrap;margin-top: 15px;">' . $images . '</div>';
}

function custom_dpago_gateway($description, $gateway_id)
{
    if ($gateway_id !== 'woocommerce_dpago') {
        return $description;
    }

    return $description . ' <img style="max-height: 25px;position: absolute;right: 15px;" src="' . plugins_url('../assets/dpago-logo.svg', __FILE__) . '" alt="dpago_logo" />';
}

function custom_dpago_billing_fields($fields)
{
    return $fields;
}

function update_wc_dpago_billing($array)
{
    add_filter('woocommerce_billing_fields', 'custom_dpago_billing_fields', 10, 1);

    if (isset($_POST['fields'])) {
        $camposArray = $_POST['fields'];
        foreach ($camposArray as $key => $value) {
            $_POST[$key] = $value;
        }
    }

    WC()->checkout()->checkout_form_billing($array);
}

function dpago_custom_form_ajax()
{
    add_filter('woocommerce_update_order_review_fragments', 'update_wc_dpago_billing', 10, 1);
    do_action('woocommerce_update_order_review_fragments');

    wp_die();
}
