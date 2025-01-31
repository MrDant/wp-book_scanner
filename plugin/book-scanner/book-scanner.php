<?php
/*
Plugin Name: Book Scanner for WooCommerce
Description: Scanne un livre et ajoute les informations à WooCommerce.
Version: 1.0
Author: Maël D'ANTUONO
*/

if (!defined('ABSPATH')) exit;
include(plugin_dir_path(__FILE__) . 'custom-backoffice.php');
include(plugin_dir_path(__FILE__) . 'setting.php');

class BookScannerPlugin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', function ($hook) {
            if ('toplevel_page_book-scanner' === $hook) { 
                wp_enqueue_style('vue-custom-css',  plugins_url('index.css', __FILE__), [], '1.0', 'all');
                wp_enqueue_script('my-vue-app', plugins_url('index.js', __FILE__), [], '1.0', true);
                wp_localize_script('my-vue-app', 'wp_data', 
                    [
                        "consumer_key" => get_option('woocommerce_consumer_key', ''),
                        "consumer_secret" => get_option('woocommerce_consumer_secret', ''),
                        'ajax_url' => admin_url('admin-ajax.php')
                    ]);
            }
        });
        add_action('wp_ajax_scan_book', [$this, 'goodreads_retrieve']);
        add_action('wp_ajax_save_product', [$this, 'saveProduct']);
    }

    public function add_admin_menu() {
        add_menu_page('Book Scanner', 'Book Scanner', 'manage_options', 'book-scanner', [$this, 'scanner_page'], 'dashicons-book', 6);
        add_submenu_page(
            'book-scanner',                // Slug du parent (Book Scanner)
            'Réglages WooCommerce API',    // Titre de la page
            'Réglages API',                // Titre du sous-menu
            'manage_options',              // Capacité requise
            'bookscanner-api-settings', // Fonction de rendu pour le sous-menu
            'render_woocommerce_api_settings' // Slug unique du sous-menu
        );
    }

    public function scanner_page() {
        ?>
            <div id="app"></div>
        <?php
    }

    public function saveProduct() {
        if(!current_user_can('manage_options') && !current_user_can('edit_products')) {return wp_send_json_error(['message' => 'Non autorisé']);}
        $product = null;
        if(!isset($_POST["id"])) {
            $product = new WC_Product_Simple();
            foreach(["images", "name", "description", "regular_price", "stock_quantity", "category_ids", "isbn"] as $key ) {
                if(!isset($_POST[$key])) {
                    wp_send_json_error(['message' => "Données '$key' manquantes."]);
                    return wp_die();
                }
            }
        } else {
            $product = wc_get_product($_POST["id"]);
        }
        
        foreach(["name", "description", "regular_price", "stock_quantity", "category_ids"] as $key ) {
            if (isset($_POST[$key])) {
                $method = 'set_' . $key; // Construit le nom de la méthode dynamiquement
                if (method_exists($product, $method)) { // Vérifie si la méthode existe
                    $product->$method($_POST[$key]); // Appelle la méthode dynamiquement
                } 
            }
        }
        
        if($_POST['images']) {
            $images = [];
            foreach ($_POST['images'] as $img) {
                if(strlen($img) > 5) {
                    $img = $this-> download_image_to_media_library($img, sanitize_text_field($product->get_name()));
                }
                if (is_wp_error($img)) {
                    return wp_send_json_error($img); // Retourne l'erreur si échec
                }
                array_push($images,$img);
            }
            $product->set_image_id($images[0]);
            $product->set_gallery_image_ids(array_slice($images, 1));
        }

        if($_POST['isbn']) {
            $product->update_meta_data('isbn', $_POST['isbn']);
        }

        if($_POST['author']) {
            $attribute_name = "Autheur";

            // Récupérer le slug de l'attribut (WooCommerce préfixe les attributs globaux avec "pa_")
            $attribute_slug = 'pa_' . sanitize_title($attribute_name);

            // Assurez-vous que l'attribut global existe
            if (!taxonomy_exists($attribute_slug)) {
                $attribute_id = wc_create_attribute([
                    'name'         => $attribute_name,
                    'slug'         => sanitize_title($attribute_name),
                    'type'         => 'select',
                    'order_by'     => 'menu_order',
                    'has_archives' => false,
                ]);
                if (is_wp_error($attribute_id)) {
                    return wp_send_json_error($attribute_id); // Retourne l'erreur si échec
                }
                register_taxonomy(
                    $attribute_slug,
                    'product',
                    ['label' => $attribute_name, 'public' => false, 'hierarchical' => false]
                );

            }

            // Récupérer ou créer les termes pour l'attribut global
            $term_ids = [];
            foreach ($_POST['author'] as $term_name) {
                $term = term_exists($term_name, $attribute_slug);
                if (!$term) {
                    $term = wp_insert_term($term_name, $attribute_slug);
                }
                if (!is_wp_error($term)) {
                    $term_ids[] = intval($term['term_id']);
                }
            }
            $attrValue = $_POST['author'];

            $attr = new WC_Product_Attribute();
            $attr->set_id(wc_attribute_taxonomy_id_by_name($attribute_name));
            $attr->set_name( $attribute_slug );
            $attr->set_options($term_ids);
            $attr->set_visible(1);
            $attr->set_variation(1);
            $attr->set_position(0);

            $attrs[$attribute_slug] = $attr;
            $product->set_attributes($attrs);
        }
        $product->set_manage_stock(true);
        $product_id = $product->save();

        if ($product_id) {
            wp_send_json_success(['message' => 'Produit créé avec succès.', 'product_id' => $product_id]);
        } else {
            wp_send_json_error(['message' => 'Erreur lors de la création du produit.']);
        }
        wp_die();
    }

    function save_base64_image_to_media_library($base64_string, $title) {
        // Vérifier si la chaîne est bien du base64
        if (preg_match('/^data:image\/(png|jpg|jpeg|gif|webp);base64,/', $base64_string, $matches)) {
            $image_type = $matches[1]; // Type de l'image
            $base64_string = preg_replace('/^data:image\/(png|jpg|jpeg|gif|webp);base64,/', '', $base64_string);
            $decoded_image = base64_decode($base64_string);
    
            if ($decoded_image === false) {
                return new WP_Error('decode_error', 'Erreur lors du décodage de l’image.');
            }
    
            // Définir un nom de fichier propre
            $title = preg_replace('/\.+/', '-', $title);
            $filename = sanitize_file_name($title) . '.' . $image_type;
    
            // Sauvegarder l’image dans le dossier des uploads
            $upload_dir = wp_upload_dir();
            $upload_path = $upload_dir['path'] . '/' . $filename;
    
            file_put_contents($upload_path, $decoded_image);
    
            // Ajouter à la bibliothèque des médias
            $attachment = [
                'guid'           => $upload_dir['url'] . '/' . $filename,
                'post_mime_type' => 'image/' . $image_type,
                'post_title'     => sanitize_text_field($title),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ];
    
            $attachment_id = wp_insert_attachment($attachment, $upload_path);
    
            if (is_wp_error($attachment_id)) {
                return $attachment_id;
            }
    
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attach_data = wp_generate_attachment_metadata($attachment_id, $upload_path);
            wp_update_attachment_metadata($attachment_id, $attach_data);
    
            return $attachment_id;
        }
    
        return new WP_Error('invalid_base64', 'Format de base64 invalide.');
    }
    

    function download_image_to_media_library($image_source, $title) {
        if (strpos($image_source, 'data:image') === 0) {
            return $this->save_base64_image_to_media_library($image_source, $title);
        } 
        // Obtiens le contenu de l'image
        $response = wp_remote_get($image_source);
        if (is_wp_error($response)) {
            return new WP_Error('image_download_error', 'Erreur lors du téléchargement de l’image.');
        }
    
        // Vérifie que le contenu est valide
        $body = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');
    
        // Définis une extension basée sur le type MIME
        $extension = '';
        switch ($content_type) {
            case 'image/webp':
                $extension = 'webp';
                break;
            case 'image/png':
                $extension = 'png';
                break;
            case 'image/gif':
                $extension = 'gif';
                break;
            default:
                $extension = 'jpg';
                break;
        }
    
        // Sauvegarde l'image dans le dossier des uploads
        $filename =  sanitize_file_name(preg_replace('/\.+/', '-', $title)) . '.' . $extension;
        $upload = wp_upload_bits($filename, null, $body);
    
        if ($upload['error']) {
            return new WP_Error('upload_error', 'Erreur lors de l’enregistrement de l’image.'. $upload['error']);
        }
    
        // Ajoute l'image à la bibliothèque des médias
        $attachment = [
            'guid'           => $upload['url'],
            'post_mime_type' => $content_type,
            'post_title'     => sanitize_text_field($title),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];
    
        $attachment_id = wp_insert_attachment($attachment, $upload['file']);
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }
    
        // Génère les métadonnées de l'image
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attach_data);
    
        return $attachment_id;
    }

    
    public function goodreads_retrieve() {
        if (!current_user_can('manage_options') || !isset($_POST['isbn'])) {
            return wp_send_json_error(['message' => 'Non autorisé']);
        }

        $isbn = sanitize_text_field($_POST['isbn']);
        $body = wp_kses_post(wp_remote_retrieve_body(wp_remote_get("https://www.goodreads.com/book/isbn/$isbn")));
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        $dom->loadHTML(mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8')); 
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);
        wp_send_json_success([
            'name' => $xpath->query("//h1[contains(@class, 'Text__title1')]")[0]->textContent,
            'author' => $xpath->query("//span[contains(@class, 'ContributorLink__name')]")[0]->textContent,
            'description' => $xpath->query("//span[contains(@class, 'Formatted')]")[0]->textContent,
            'images' => $xpath->query("//img")[1]->getAttribute('src'),
        ]);
    }

}

new BookScannerPlugin();
?>
