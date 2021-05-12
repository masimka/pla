<?php
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

	$result = array();
	foreach ($taxonomy as $t) {
		$taxonomies = array();

		//Find parent of category found
		$parents_ids = get_ancestors( $t->term_id, $type );

		//Main category validation (search without parent)
		if (empty($parent)) {
			if (!empty($parents_ids))
				break;
			else
				$taxonomies[] = (object) [
				    'id' => $t->term_id,
				    'name' => $t->name,
				    'parent' => 0,
				    'nameparent' => ''
				];
		}

		//Find category parent`s tree
		foreach ( $parents_ids as $key => $term_id ) {
			$parent_data = get_term( $term_id, $type );

			//Validate the nearest parent
			if ($key == 0 && $parent_data->name != $parent) break;

			//Save category parent in categories array
			$taxonomies[] = (object) [
			    'id' => $t->term_id,
			    'name' => $t->name,
			    'parent' => $parent_data->term_id,
			    'nameparent' => $parent_data->name
			];

			//Clone parent data for create searchable category
			$t = $parent_data;			
	    }

	    //Save result if not null
		if (!empty($taxonomies)) {
			if (!empty($parent)) {
				$taxonomies[] = (object) [
				    'id' => $parent_data->term_id,
				    'name' => $parent_data->name,
				    'parent' => 0,
				    'nameparent' => ''
				];
			}
			$result[] = $taxonomies;
		}
	}

	//if null result return 401 status
	if (empty($result)) {
		$error = "The parent '{$parent}' of taxonomy '{$tax_name}' is not found";
		$code = 2;
		return api_error_404($error, $code);
	}
	
	return $result;
}

/**
 * API End Point Search attributes by Name
 *
 * @param object $data Name of Attribute and value of attribute.
 * @return array of attribute data with value data
 */
function search_attributes_by_name_value($data) {
	$name = urldecode($data->get_param('name'));
	$value = urldecode($data->get_param('value'));

	//Get taxonomy id
	$taxonomy_id = wc_attribute_taxonomy_id_by_name($name);

	if (empty($taxonomy_id)) {
		$error = "The attribute '{$name}' is not found";
		$code = 1;
		return api_error_404($error, $code);
	}

	//Get taxonomy slag for attribute searching
	$taxonomy_slag = wc_attribute_taxonomy_name($name);
	$attribute = get_term_by('name', $value, $taxonomy_slag);

	//if null result
	if (empty($attribute)) {
		$error = "The value '{$value}' is not found";
		$code = 2;
		return api_error_404($error, $code);
	}
	
	return (object) [
	    'attributeid' => $taxonomy_id,
	    'attributename' => $name,
	    'valueid' => $attribute->term_id,
	    'valuename' => $value
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

//API Hook of custom Endpoint for product categories
add_action('rest_api_init', function () {
	register_rest_route('wc/v3/products', '/taxonomy_by_name_type/(?P<type>.+)/(?P<name>.+)', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'search_taxonomy_by_name_type'
	));
});

add_action('rest_api_init', function () {
	register_rest_route('wc/v3/products', '/attribute_by_name_value/(?P<name>.+)/(?P<value>.+)', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'search_attributes_by_name_value'
	));
});