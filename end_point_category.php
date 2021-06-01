<?php
//START API`s HOOK
/**
 * API End Point Search Category by Name
 *
 * @param object $data Name of Category for searching and Parent Category name.
 * @return array of category tree and all categories data in hierarchy
 */
function search_taxonomy_by_name_type($data) {
	$type = urldecode($data->get_param('type'));
	$tax_name = urldecode($data->get_param('name'));
	$parent = urldecode($data->get_param('parent'));

	if ($type != 'product_cat' && 
		$type != 'product_brand') {
		$error = "The type '{$type}' is not supported";
		return api_error_404($error, $code);
	}

	//Strict search type with 100% of coincidence
	$taxonomy = get_terms(array(
		'get' => 'all',
	    'taxonomy' => $type,
	    'name' => urldecode($tax_name)
	));

	//if null result
	if (empty($taxonomy)) {
		$error = "The name of taxonomy '{$tax_name}' is not found";
		$code = 1;
		return api_error_404($error, $code);
	}

	$taxonomies = array();
	foreach ($taxonomy as $t) {
		//Find parent of category found
		$parents_ids = get_ancestors( $t->term_id, $type );

		//Main category validation (search without parent)
		if (empty($parent)) {
			if (!empty($parents_ids))
				break;
			else
				$taxonomies[] = array (
				    'id' => $t->term_id,
				    'name' => $t->name,
				    'parent' => 0,
				    'nameparent' => ''
				);
		}

		//Find category parent`s tree
		foreach ( $parents_ids as $key => $term_id ) {
			$parent_data = get_term( $term_id, $type );

			//Validate the nearest parent
			if ($key == 0 && strtolower(htmlspecialchars_decode($parent_data->name)) != strtolower($parent)) break;

			//Save category parent in categories array
			$taxonomies[] = array (
			    'id' => $t->term_id,
			    'name' => $t->name,
			    'parent' => $parent_data->term_id,
			    'nameparent' => $parent_data->name
			);
			break;
			//Clone parent data for create searchable category
			$t = $parent_data;			
	    }

	    //Save result if not null
		if (!empty($taxonomies)) {
			// if (!empty($parent)) {
			// 	$taxonomies[] = array (
			// 	    'id' => $parent_data->term_id,
			// 	    'name' => $parent_data->name,
			// 	    'parent' => 0,
			// 	    'nameparent' => ''
			// 	);
			// }
		}
	}


	//if null result return 401 status
	if (empty($taxonomies)) {
		$error = "The parent '{$parent}' of taxonomy '{$tax_name}' is not found";
		$code = 2;
		return api_error_404($error, $code);
	}

	return (object)['item' => $taxonomies];
}

/**
 * API End Point Search attributes by Name
 *
 * @param object $data Name of Attribute.
 * @return id of attribute
 */
function search_attributes_by_name($data) {
	$name = urldecode($data->get_param('name'));

	$taxonomies = wc_get_attribute_taxonomies();

	if (empty($taxonomies)) {
		$error = "The attribute '{$name}' is not found";
		$code = 1;
		return api_error_404($error, $code);
	}

	$attrs = array();
	foreach ($taxonomies as $value) {
		if ($value->attribute_label == $name) {
			$attrs[] = (object) ["id" => $value->attribute_id];
		}
	}
	
	if (empty($attrs)) {
		$error = "The attribute '{$name}' is not found";
		$code = 1;
		return api_error_404($error, $code);
	}

	return $attrs;
}

/**
 * API End Point Search attributes by Slug
 *
 * @param object $data Slug of Attribute.
 * @return object data of attribute
 */
function search_attributes_by_slug($data) {
	$slug = urldecode($data->get_param('slug'));

	$taxonomies = wc_get_attribute_taxonomies();

	if (empty($taxonomies)) {
		$error = "The attribute '{$slug}' is not found";
		$code = 1;
		return api_error_404($error, $code);
	}

	$attrs = array();
	foreach ($taxonomies as $value) {
		if ($value->attribute_name == $slug) {
			return (object) [
				"id" => $value->attribute_id,
				"name" => $value->attribute_label,
				"slug" => $value->attribute_name
			];
		}
	}
	
	if (empty($attrs)) {
		$error = "The attribute '{$slug}' is not found";
		$code = 1;
		return api_error_404($error, $code);
	}

	return $attrs;
}

/**
 * API End Point Search Brands by Slug
 *
 * @param object $data Slug of Brand.
 * @return object data of attribute
 */
function search_brands_by_slug($data) {
	$slug = urldecode($data->get_param('slug'));

	$taxonomy = get_terms(array(
		'get' => 'all',
	    'taxonomy' => 'product_brand',
	    'slug' => urldecode($slug)
	));

	if (empty($taxonomy)) {
		$error = "The brand '{$slug}' is not found";
		$code = 1;
		return api_error_404($error, $code);
	}

	return (object) [
		"id" => $taxonomy[0]->term_id,
		"name" => $taxonomy[0]->name,
		"slug" => $taxonomy[0]->slug,
		"description" => $taxonomy[0]->description,
		"parent" => $taxonomy[0]->parent
	];
}


/**
 * API End Point Get all Brands
 *
 * @return object data of brand
 */
function get_brands() {
	$taxonomy = get_terms(array(
		'get' => 'all',
	    'taxonomy' => 'product_brand'
	));

	if (empty($taxonomy)) {
		$error = "No brands are found";
		$code = 1;
		return api_error_404($error, $code);
	}

	foreach ($taxonomy as $t) {
		$result[] = (object) [
			"id" => $t->term_id,
			"name" => $t->name,
			"slug" => $t->slug,
			"description" => $t->description,
			"parent" => $t->parent
		];
	}

	return $result;
}

/**
 * API End Point Search attributes by Name
 *
 * @param object $data Name of Attribute and value of attribute.
 * @return array of attribute data with value data
 */
function search_attributes_by_id_value($data) {
	$id = urldecode($data->get_param('id'));
	$value = urldecode($data->get_param('value'));

	if (empty($id)) {
		$error = "The attribute '{$id}' is not found";
		$code = 1;
		return api_error_404($error, $code);
	}

	//Get taxonomy data for attribute searching
	$term_object = wc_get_attribute( $id );

	if (empty($term_object)) {
		$error = "The attribute '{$id}' is not found";
		$code = 1;
		return api_error_404($error, $code);
	}

	//Get term data for attribute searching
	$args = array(
		'get'                    => 'all',
		'name'                   => $value,
		'taxonomy'               => $term_object->slug,
		'update_term_meta_cache' => false,
		'orderby'                => 'none',
		'suppress_filter'        => true,
	);
	$attributes = get_terms( $args );

	//if null result
	if (empty($attributes)) {
		$error = "The value '{$value}' is not found";
		$code = 2;
		return api_error_404($error, $code);
	}

	$result = array();
	foreach ($attributes as $v) {
		$result[] = (object) [
		    'id' => $v->term_id
		];
	}
	
	return $result;
}

/**
 * API End Point assign brands to product
 *
 * @param object $data Id of product and json of array of ids of brands.
 * @return array of inserted id`s data
 */
function assign_brand_to_product($data) {
	$id = urldecode($data->get_param('id'));
	$ids_brands = json_decode(urldecode($data->get_param('ids_brands')));

	$product = wc_get_product($id);

	if (empty($product)) {
		$error = "The product '{$id}' is not found";
		$code = 1;
		return api_error_404($error, $code);
	}

	if (empty($ids_brands)) {
		$error = "The brands '{$data->get_param('ids_brands')}' are not found";
		$code = 1;
		return api_error_404($error, $code);
	}

	//Get term data for brand
	$args = array(
		'get'                    => 'all',
		'taxonomy'               => 'product_brand',
		'update_term_meta_cache' => false,
		'orderby'                => 'none',
		'suppress_filter'        => true,
		'include' 				 => $ids_brands
	);
	$brands = get_terms( $args );

	if (empty($brands)) {
		$error = "The brand '{$data->get_param('ids_brands')}' are not found";
		$code = 1;
		return api_error_404($error, $code);
	}

    $inserted_ids = wp_set_object_terms($id, $ids_brands, 'product_brand');

	//if null result
	if (empty($inserted_ids)) {
		$error = "Error, the brands are not inserted";
		$code = 2;
		return api_error_404($error, $code);
	}

	return (object) [
	    'inserted_ids' => $inserted_ids
	];
}

/**
 * API End Point assign brands to product
 *
 * @param object $data data of brand to insert.
 * @return array of inserted id`s data
 */
function add_new_brand($data) {
	$brand = json_decode(urldecode($data->get_param('brand')));

	if (empty($brand->name)) {
		$error = "The brand data can`t be null";
		$code = 1;
		return api_error_404($error, $code);
	}

	$args = array(
		'name' => $brand->name
	);

	if (!empty($brand->description)) $args['description'] = $brand->description;
	if (!empty($brand->parent) && is_integer($brand->parent) && $brand->parent > 0) $args['parent'] = $brand->parent;
	if (!empty($brand->slug)) $args['slug'] = $brand->slug;

	$term = wp_insert_term($brand->name, 'product_brand', $args);

	if (!empty($term->error_data['term_exists'])) {
		$term =  get_term_by( 'id', $term->error_data['term_exists'], 'product_brand' );
		return (object) [
		    "id" => $term->term_id,
			"name" => $term->name,
			"slug" => $term->slug,
			"description" => $term->description,
			"parent" => $term->parent
		];
	}

	$term =  get_term_by( 'id', $term['term_id'], 'product_brand' );
	return (object) [
	    "id" => $term->term_id,
		"name" => $term->name,
		"slug" => $term->slug,
		"description" => $term->description,
		"parent" => $term->parent
	];
}

/**
 * API End Point assign brands to product
 *
 * @param object $data data of brand to insert.
 * @return array of inserted id`s data
 */
function add_new_brand_body($data) {
	$brands = $data->get_params('JSON');

	if (empty($brands)) {
		$error = "The brand data can`t be null";
		$code = 1;
		return api_error_404($error, $code);
	}

	$brands_created = array();
	foreach ($brands as $brand) {
		if ($brand['name'] != "") {
			$args = array(
				'name' => $brand['name']
			);

			if (!empty($brand['description'])) $args['description'] = $brand['description'];
			if (!empty($brand['parent']) && is_integer($brand['parent']) && $brand['parent'] > 0) $args['parent'] = $brand['parent'];
			if (!empty($brand['slug'])) $args['slug'] = $brand['slug'];

			$term = wp_insert_term($brand['name'], 'product_brand', $args);

			if (!empty($term->error_data['term_exists'])) {
				$term =  get_term_by( 'id', $term->error_data['term_exists'], 'product_brand' );
			} else {
				$term =  get_term_by( 'id', $term['term_id'], 'product_brand' );
			}

			$brands_created[] = (object) [
			    "id" => $term->term_id,
				"name" => $term->name,
				"slug" => $term->slug,
				"description" => $term->description,
				"parent" => $term->parent
			];
		}
	}

	return $brands_created;

}

/**
 * API End Point get product data by slugs
 *
 * @param object $data data of brand to insert.
 * @return array of inserted id`s data
 */
function get_products_by_slugs($data) {
	$slugs = $data->get_params('JSON');
	if (empty($slugs)) {
		$error = "The products are not found";
		$code = 1;
		return api_error_404($error, $code);
	}

	$products_data = array();
	$i = 0;
	foreach ($slugs as $s) {
		$prod = get_page_by_path( $s['slug'], OBJECT, 'product' );
		if (!empty($prod->ID)) {
			$product = new WC_product($prod->ID);
			$temp_data = (object) [
			    "id" => $prod->ID,
				"slug" => $s['slug'],
				"sku" => $product->get_sku()
			];

			$image_id = $product->get_image_id();

			if ($image_id) {
				$temp_data->images[] = (object) [
				    "id" => intval($image_id),
					"alt" =>  get_post_meta($image_id, '_wp_attachment_image_alt', TRUE ),
				];
			}
			
			$image_ids = $product->get_gallery_image_ids($prod->ID);

			if (!empty($image_ids)) {
				foreach($image_ids as $iid) {
		          	// Display the image URL
		        	$temp_data->images[] = (object) [
					    "id" => $iid,
						"alt" =>  get_post_meta($iid, '_wp_attachment_image_alt', TRUE ),
					];
		        }
			}

			$products_data[] = $temp_data;
		}
		$i++;
	}

	return $products_data;
}

/**
 * API End Point get variations data by parent id
 *
 * @param object $data data of parent ids.
 * @return array of product data
 */
function get_variations_by_parent_ids($data) {
	$id = $data->get_param('id');
	
	if (empty($id)) {
		$error = "The products are not found";
		$code = 1;
		return api_error_404($error, $code);
	}

	$variations_data = array();
	$args = array(
	    'post_type'     => 'product_variation',
	    'post_status'   => array( 'private', 'publish' ),
	    'numberposts'   => -1,
	    'orderby'       => 'menu_order',
	    'order'         => 'asc',
	    'post_parent'   => $id // get parent post-ID
	);
	$variations = get_posts( $args );

	$i = 0;
	foreach ( $variations as $variation ) {
	    // get variations meta
	    $product_variation = new WC_Product_Variation($variation->ID);

	    if (!empty($product_variation)) {
	    	$variation_data = $product_variation->get_data();
			$variations_data[$i] = (object) [
			    "id" => $variation->ID,
				"sku" => $variation_data['sku'],
				"description" => $variation_data['description']
			];

		    // get variation featured image
		    $image_id = $product_variation->get_image_id();

		    if (!empty($image_id)) {
			    $variations_data[$i]->images = (object) [
				    "id" => intval($image_id),
					"alt" =>  get_post_meta($image_id, '_wp_attachment_image_alt', TRUE ),
				];
		    } else {
		    	$variations_data[$i]->images = (object) [];
		    }
	    }
		$i++;
	}

	return $variations_data;
}

/**
 * API End Point set inventory data by slug
 *
 * @param object $data data of slugs.
 * @return bool true or false
 */
function set_inventory_by_slug($data) {
	$product_data = $data->get_params('JSON');
	
	if (empty($product_data)) {
		$error = "The products are not found";
		$code = 1;
		return api_error_404($error, $code);
	}

	$variations_data = array();

	foreach ($product_data as $pd) {
		$product = get_page_by_path( $pd['slug'], OBJECT, 'product' );
		if (!$product->ID) {
			return false;
		} 

		// Add product stock
		update_stock($product->ID, $pd['stock_quantity']);

		// Add product prices
		$product = wc_get_product($product->ID);
		update_prices($product, $pd['regular_price'], $pd['sale_price']);

		if (!empty($pd['variations'])) {
			foreach ($pd['variations'] as $pv) {
				$id = wc_get_product_id_by_sku( $pv['sku'] );
				if (!empty($id)) {

					// Add variation product stock
					update_stock($id, $pv['stock_quantity']);

					// Add product prices
					$product = wc_get_product($id);
					update_prices($product, $pv['regular_price'], $pv['sale_price']);
				} 
			}
		}
	}

	return true;
}

/**
 * update_stock function
 *
 * @param int $id product id.
 * @param int $stock_quantity cuantity in stock.
 */
function update_stock($id, $stock_quantity) {
	// Updating the stock quantity
	update_post_meta($id, '_stock', $stock_quantity);

	// Updating the stock quantity and Updating post term relationship
	if ($stock_quantity > 0) {
		update_post_meta($id, '_stock_status', 'instock');
		wp_set_post_terms($id, 'instock', 'product_visibility', true);
	} else {
		update_post_meta($id, '_stock_status', 'outofstock');
		wp_set_post_terms($id, 'outofstock', 'product_visibility', true);
	}

	// Clear/refresh the variation cache
	wc_delete_product_transients($id);
}

/**
 * update_stock function
 *
 * @param WC_Product $product product data.
 * @param number $regular_price regular price.
 * @param number $sale_price sale price.
 */
function update_prices($product, $regular_price, $sale_price) {
	if (empty($regular_price)) return false;
	
	$product->set_regular_price($regular_price);
	
	// If your product has a Sale price
	if (!empty($sale_price)) {
		$product->set_sale_price($sale_price);
		$new_price = $sale_price;
	} else {
		$new_price = $regular_price;
	}
	
	// Set new price
	$product->set_price($new_price);
	$product->save();

	// Clear/refresh the variation cache
	wc_delete_product_transients($product->ID);
}

/**
 * API End Point not found
 *
 * @param object $data Name of Category for searching and Parent Category name.
 * @return array of category tree and all categories data in hierarchy
 */
function api_error_404($error = '', $code = 0) {
	return array(
		"code" => $code,
	    "message" => $error,
	    "data" => array(
	        "status" => 404
	    )
	);
}

//Add filter for brands
add_filter( 'woocommerce_rest_prepare_product_object', 'add_brands_to_product', 10, 3 ); 

function add_brands_to_product($response, $object, $request) {

	//Add brands data if POST
	if ($request->get_method() == 'POST') {
		$brands = $request->get_params()['brands'];
		$ids_brands = array();
		
		if (!empty($brands)) {
			foreach ($brands as $b) {
				$ids_brands[] = $b['id'];
			}
		}

		if (!empty($ids_brands)) {
			wp_set_object_terms($response->data['id'], $ids_brands, 'product_brand');
		}
	}

	$terms = get_the_terms( $response->data['id'], 'product_brand' );

	if (!empty($terms)) {
		foreach ($terms as $t) {
			$response->data['brands'][] = (object) [
				"id" => $t->term_id,
				"name" => $t->name,
			];
		}
	} else {
		$response->data['brands'] = array();
	}

	return $response; 
}

add_filter( 'woocommerce_rest_check_permissions',
	function ( $permission, $context, $object_id, $post_type ) {
		if ($post_type == 'product_tag' ||
			$post_type == 'attributes' ||
			strpos($post_type, 'pa_') !== false ||
			($context == 'batch' && $post_type == 'product') ||
			($context == 'batch' && $post_type == 'product_variation')
		) {
		    return get_api_user();
		}
		return $permission;
	}, 10, 4
);

function get_user_data_by_consumer_key( $consumer_key ) {
	global $wpdb;
	$consumer_key = wc_api_hash( sanitize_text_field( $consumer_key ) );
	$user         = $wpdb->get_row($wpdb->prepare("
						SELECT key_id, user_id, permissions, consumer_key, consumer_secret, nonces
						FROM {$wpdb->prefix}woocommerce_api_keys
						WHERE consumer_key = %s", $consumer_key)
					);
	return $user;
}

function get_api_user() {
	$key = get_oauth_parameters();

	if (!empty($_SERVER['PHP_AUTH_USER'])) {
		$key = $_SERVER['PHP_AUTH_USER'];
	} 

	$consumer = get_user_data_by_consumer_key($key);
	$user = get_userdata( $consumer->user_id );

	if ( in_array( 'administrator', (array) $user->roles ) ||
		 in_array( 'seller', (array) $user->roles ) || 
		 in_array( 'vendor_catalog', (array) $user->roles ) ) {
	    return true;
	}
	return false;
}

function get_oauth_parameters() {
	$params = array_merge( $_GET, $_POST ); // WPCS: CSRF ok.
	$params = wp_unslash( $params );
	$header = get_authorization_header();

	if ( ! empty( $header ) ) {
		// Trim leading spaces.
		$header        = trim( $header );
		$header_params = parse_header( $header );

		if ( ! empty( $header_params ) ) {
			$params = array_merge( $params, $header_params );
		}
	}

	return $params['oauth_consumer_key'];
}

function get_authorization_header() {
	if ( ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
		return wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ); // WPCS: sanitization ok.
	}

	if ( function_exists( 'getallheaders' ) ) {
		$headers = getallheaders();
		// Check for the authoization header case-insensitively.
		foreach ( $headers as $key => $value ) {
			if ( 'authorization' === strtolower( $key ) ) {
				return $value;
			}
		}
	}

	return '';
}

function parse_header( $header ) {
	if ( 'OAuth ' !== substr( $header, 0, 6 ) ) {
		return array();
	}

	// From OAuth PHP library, used under MIT license.
	$params = array();
	if ( preg_match_all( '/(oauth_[a-z_-]*)=(:?"([^"]*)"|([^,]*))/', $header, $matches ) ) {
		foreach ( $matches[1] as $i => $h ) {
			$params[ $h ] = urldecode( empty( $matches[3][ $i ] ) ? $matches[4][ $i ] : $matches[3][ $i ] );
		}
		if ( isset( $params['realm'] ) ) {
			unset( $params['realm'] );
		}
	}

	return $params;
}

//API Hook of custom Endpoint for product categories
add_action('rest_api_init', function () {
	register_rest_route('wc/v3/gp_products', '/taxonomy_by_name_type/(?P<type>.+)/(?P<name>.+)', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'search_taxonomy_by_name_type',
		'permission_callback' => function () {return get_api_user();}
	));
});

add_action('rest_api_init', function () {
	register_rest_route('wc/v3/gp_products', '/attribute_by_name/(?P<name>.+)', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'search_attributes_by_name',
		'permission_callback' => function () {return get_api_user();}
	));
});

add_action('rest_api_init', function () {
	register_rest_route('wc/v3/gp_products', '/attribute_by_slug/(?P<slug>.+)', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'search_attributes_by_slug',
		'permission_callback' => function () {return get_api_user();}
	));
});

add_action('rest_api_init', function () {
	register_rest_route('wc/v3/gp_products', '/brand_by_slug/(?P<slug>.+)', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'search_brands_by_slug',
		'permission_callback' => function () {return get_api_user();}
	));
});

add_action('rest_api_init', function () {
	register_rest_route('wc/v3/gp_products', '/brands', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'get_brands',
		'permission_callback' => function () {return get_api_user();}
	));
});

add_action('rest_api_init', function () {
	register_rest_route('wc/v3/gp_products', '/attribute_by_id_value/(?P<id>\d+)/(?P<value>.+)', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'search_attributes_by_id_value',
		'permission_callback' => function () {return get_api_user();}
	));
});

add_action('rest_api_init', function () {
	register_rest_route('wc/v3/gp_products', '/assign_brand_to_product/(?P<id>\d+)/(?P<ids_brands>.+)', array(
		'methods'  => WP_REST_Server::EDITABLE,
		'callback' => 'assign_brand_to_product',
		'permission_callback' => function () {return get_api_user();}
	));
});

add_action('rest_api_init', function () {
	register_rest_route('wc/v3/gp_products', '/add_new_brand/(?P<brand>.+)', array(
		'methods'  => WP_REST_Server::EDITABLE,
		'callback' => 'add_new_brand',
		'permission_callback' => function () {return get_api_user();}
	));
});

add_action('rest_api_init', function () {
	register_rest_route('wc/v3/gp_products', '/add_new_brand_body', array(
		'methods'  => WP_REST_Server::EDITABLE,
		'callback' => 'add_new_brand_body',
		'permission_callback' => function () {return get_api_user();}
	));
});

add_action('rest_api_init', function () {
	register_rest_route('wc/v3/gp_products', '/get_products_by_slugs', array(
		'methods'  => WP_REST_Server::EDITABLE,
		'callback' => 'get_products_by_slugs',
		'permission_callback' => function () {return get_api_user();}
	));
});

add_action('rest_api_init', function () {
	register_rest_route('wc/v3/gp_products', '/get_variations_by_parent_ids/(?P<id>\d+)', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'get_variations_by_parent_ids',
		'permission_callback' => function () {return get_api_user();}
	));
});

add_action('rest_api_init', function () {
	register_rest_route('wc/v3/gp_products', '/set_inventory_by_slug', array(
		'methods'  => WP_REST_Server::EDITABLE,
		'callback' => 'set_inventory_by_slug',
		'permission_callback' => function () {return get_api_user();}
	));
});
//END API`s HOOK