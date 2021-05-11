<?php
/**
 * API End Point Search Category by Name
 *
 * @param object $data Name of Category for searching.
 * @return array of category tree and all categories data in hierarchy
 */
function search_category_by_name( $data ) {
	global $wpdb;
	$categories = array();
	$category_tree = array();
	$cat_name = urldecode($data->get_param( 'name' ));

	//Strict search type with 100% of coincidence
	$category = get_term_by( 'name', urldecode($cat_name), 'product_cat' );

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
	
	//Save category found in categories array
	$categories[$category->term_id] = (object) [
	    'term_id' => $category->term_id,
	    'name' => $category->name
	];

	//Find parent of category found
	$parents_ids = get_ancestors( $category->term_id, 'product_cat' );
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
    $category_tree[] = $category->term_id;

	return array(
		'tree' => implode (",", $category_tree),
		'categories' => $categories
	);
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'wc/v3/products', '/categories_by_name/(?P<name>.+)', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'search_category_by_name'
	) );
} );