<?php


// Fonction pour afficher le contenu de la page
function render_woocommerce_api_settings() {
    // Sauvegarde des données si le formulaire est soumis
    if (isset($_POST['woocommerce_api_settings_nonce']) && wp_verify_nonce($_POST['woocommerce_api_settings_nonce'], 'woocommerce_api_settings')) {
        update_option('woocommerce_consumer_key', sanitize_text_field($_POST['woocommerce_consumer_key']));
        update_option('woocommerce_consumer_secret', sanitize_text_field($_POST['woocommerce_consumer_secret']));
        echo '<div class="updated"><p>Les réglages ont été sauvegardés avec succès !</p></div>';
    }

    // Récupérer les valeurs actuelles
    $consumer_key = get_option('woocommerce_consumer_key', '');
    $consumer_secret = get_option('woocommerce_consumer_secret', '');

    // Formulaire HTML
    ?>
    <div class="wrap">
        <h1>Réglages WooCommerce API</h1>
        <form method="POST" action="">
            <?php wp_nonce_field('woocommerce_api_settings', 'woocommerce_api_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="woocommerce_consumer_key">Consumer Key</label></th>
                    <td><input name="woocommerce_consumer_key" id="woocommerce_consumer_key" type="text" value="<?php echo esc_attr($consumer_key); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="woocommerce_consumer_secret">Consumer Secret</label></th>
                    <td><input name="woocommerce_consumer_secret" id="woocommerce_consumer_secret" type="text" value="<?php echo esc_attr($consumer_secret); ?>" class="regular-text"></td>
                </tr>
            </table>
            <?php submit_button('Enregistrer les réglages'); ?>
        </form>
    </div>
    <?php
}