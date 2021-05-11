<?php
/**
 * API End Point Search Category by Name
 *
 * @param object $data Name of Category for searching.
 * @return array of category tree and all categories data in hierarchy
 */
function search_category_by_name( $data ) {
	global $wpdb;
	$cat_name = urldecode($data->get_param( 'name' ));

	//Strict search type with 100% of coincidence
	// $category = get_term_by( 'name', urldecode($cat_name), 'product_cat' );
	$category = get_terms(array(
		'get' => 'all',
	    'taxonomy' => 'product_cat',
	    'suppress_filter' => 1,
	    'name' => urldecode($cat_name)
	));

	//if null result
	if (empty($category)) {
		return array(
			"code" => "Category not found",
		    "message" => "Categoría no encontrada.",
		    "data" => array(
		        "status" => 401
		    )
		);
	}

	$result = array();

	foreach ($category as $cat) {
		$category_tree = array();
		$categories = array();

		//Save category found in categories array
		$categories[$cat->term_id] = (object) [
		    'term_id' => $cat->term_id,
		    'name' => $cat->name
		];

		//Find parent of category found
		$parents_ids = get_ancestors( $cat->term_id, 'product_cat' );
		foreach ( array_reverse( $parents_ids ) as $term_id ) {
			$parent_data = get_term( $term_id, 'product_cat' );

			//Save parent category in category tree
			$category_tree[] = $parent_data->term_id;

			//Save category parent in categories array
			$categories[$parent_data->term_id] = (object) [
			    'term_id' => $parent_data->term_id,
			    'name' => $parent_data->name
			];
	    }

	    //Save category found in category tree
	    $category_tree[] = $cat->term_id;

	    $result[] = array(
			'tree' => implode (",", $category_tree),
			'categories' => $categories
		);
	}
	

	return $result;
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'wc/v3/products', '/categories_by_name/(?P<name>.+)', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'search_category_by_name'
	) );
} );