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

//API Hook of custom Endpoint for product categories
add_action('rest_api_init', function () {
	register_rest_route('wc/v3/gp_products', '/taxonomy_by_name_type/(?P<type>.+)/(?P<name>.+)', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'search_taxonomy_by_name_type'
	));
});

add_action('rest_api_init', function () {
	register_rest_route('wc/v3/gp_products', '/attribute_by_name/(?P<name>.+)', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'search_attributes_by_name'
	));
});

add_action('rest_api_init', function () {
	register_rest_route('wc/v3/gp_products', '/attribute_by_id_value/(?P<id>\d+)/(?P<value>.+)', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'search_attributes_by_id_value'
	));
});
//END API`s HOOK