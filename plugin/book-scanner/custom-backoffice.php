<?php 
add_action('woocommerce_product_options_general_product_data', 'add_isbn_field_to_product');
add_filter('manage_edit-product_columns', function($columns) {
    $columns['isbn'] = 'ISBN';
    return $columns;
}, 20);

add_action('manage_product_posts_custom_column', function($column, $post_id) {
    if ($column === 'isbn') {
        $isbn = get_post_meta($post_id, 'isbn', true);
        echo $isbn ? esc_html($isbn) : '—';
    }
}, 10, 2);

add_filter('manage_edit-product_sortable_columns', function($columns) {
    $columns['isbn'] = 'isbn';
    return $columns;
});

// Modifier la requête pour gérer le tri par ISBN
add_action('pre_get_posts', function($query) {
    $orderby = $query->get('orderby');
    if ('isbn' === $orderby) {
        $query->set('meta_key', 'isbn');
        $query->set('orderby', 'meta_value');
    }
    // if ( !is_admin() && $query->is_main_query() && is_search() ) {
    //     // Ajouter un filtre pour rechercher dans le champ personnalisé ISBN
    //     $query->set('meta_query', array(
    //         'relation' => 'OR', 
    //         array(
    //             'key'     => 'isbn', // Le nom de votre champ ISBN personnalisé
    //             'value'   => $query->get('s'),
    //             'compare' => 'LIKE'
    //         ),
    //     ));
    // }
});

function add_isbn_field_to_product() {
    woocommerce_wp_text_input(array(
        'id' => 'isbn',
        'label' => __('ISBN', 'woocommerce'),
        'description' => __('Entrez l\'ISBN du produit.', 'woocommerce'),
        'desc_tip' => true,
    ));
}

add_filter('wc_rest_product_query', 'filter_products_by_isbn', 10, 2);

function filter_products_by_isbn($args, $request) {
    if (isset($request['isbn'])) {
        $args['meta_query'] = array(
            array(
                'key'     => 'isbn', // ou 'isbn' selon le nom de votre champ
                'value'   => $request['isbn'],
                'compare' => '='
            )
        );
    }
    return $args;
}

add_action( 'rest_api_init', 'register_isbn_filter' );

function register_isbn_filter() {
    register_rest_route('wc/v3', '/products/isbn/(?P<isbn>\d+)', array(
        'methods' => 'GET',
        'callback' => 'filter_products_by_isbn_api',
        'permission_callback' => '__return_true',
    ));
}

function filter_products_by_isbn_api(WP_REST_Request $request) {
    $isbn = sanitize_text_field($request['isbn']); // Sécurisation des données reçues

    // Rechercher les produits avec l'ISBN correspondant
    $args = array(
        'meta_key'   => 'isbn',
        'meta_value' => $isbn,
    );

    $products = wc_get_products($args); // Récupérer les produits WooCommerce
    $result = null;

    if (!empty($products)) {
        $product = $products[0];
        $images = array();
        // Image principale
        $main_image_id = $product->get_image_id();
        if ($main_image_id) {
            $images[] = ["id"=> $main_image_id, "src" => wp_get_attachment_url($main_image_id)];
        }
        // Images de galerie
        $gallery_image_ids = $product->get_gallery_image_ids();
        foreach ($gallery_image_ids as $gallery_image_id) {
            $images[] = ["id"=> $gallery_image_id, "src" => wp_get_attachment_url($gallery_image_id)];
        }

        // Obtenir les détails des attributs
        $attributes = $product->get_attributes();
        $attributes_details = array();

        foreach ($attributes as $attribute_name => $attribute) {
            if ($attribute->is_taxonomy()) {
                // Attribut basé sur une taxonomie (ex : global)
                $terms = wp_get_post_terms($product->get_id(), $attribute->get_name(), ['fields' => 'all']);
                $attributes_details[] = array(
                    'name'  => wc_attribute_label($attribute->get_name()),
                    'slug'  => $attribute->get_name(),
                    'terms' => array_map(function ($term) {
                        return [
                            'id'   => $term->term_id,
                            'name' => $term->name,
                            'slug' => $term->slug,
                        ];
                    }, $terms),
                );
            } else {
                // Attribut personnalisé (non basé sur une taxonomie)
                $attributes_details[] = array(
                    'name'  => wc_attribute_label($attribute_name),
                    'value' => $attribute->get_options(),
                );
            }
        }

        $result = array(
            'id'          => $product->get_id(),
            'name'        => $product->get_name(),
            'regular_price'       => $product->get_price(),
            'description' => $product->get_description(),
            'category_ids'  => $product->get_category_ids(),
            'attributes'  => $attributes_details,
            'stock_quantity'  => $product->get_stock_quantity(),
            'images'      => $images
        );
    }

    // Retourner les résultats sous forme de réponse REST
    return rest_ensure_response($result);
}

