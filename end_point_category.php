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
		if ('pa_' . $value->attribute_name == $slug) {
			return (object) [
				"id" => $value->attribute_id,
				"name" => $value->attribute_label,
				"slug" => 'pa_' . $value->attribute_name
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
		$error = "The brand data cant`t be null";
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

	if (empty($term)) {
		$error = "Error, the brand are not inserted";
		$code = 1;
		return api_error_404($error, $code);
	}

	return (object) [
	    'term' => $term
	];
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
			strpos($post_type, 'pa_') !== false
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
//END API`s HOOK