<?php

add_action('wp_footer', 'payment_methods_footer', 100);

wp_enqueue_style('dpago_footer', plugins_url('/../assets/styles/footer.css', __FILE__), false, '1.0', 'all');

function payment_methods_footer()
{
    $available_gateways = WC()->payment_gateways()->payment_gateways();
    $dpago_gateway = $available_gateways["woocommerce_dpago"];

    if (!isset($available_gateways['woocommerce_dpago']) && $available_gateways['woocommerce_dpago']->enabled == 'no') {
        return;
    }

    if ($dpago_gateway->show_footer == 'no' || !$available_gateways['woocommerce_dpago']->commerce_id) {
        return;
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

    $logos_html = array_map(
        function ($item) {
            return '<div class="pd-payment-methods-footer-styles"><img style="max-height: 25px;" src="' . $item->logo . '" alt="Formas de pago" /></div>';
        },
        $platforms
    );

    $images = '';
    foreach ($logos_html as $logo) {
        $images .= $logo;
    }

    echo '<div class="pd-footer-container">
        <img style="max-height: 20px;" src="' . plugins_url('../assets/dpago-logo.svg', __FILE__) . '" alt="logo" />

        <div class="pd-images-footer">
            ' . $images . '
        </div>
    </div>';
}
