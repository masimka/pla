<?php
/**
 * API End Point Search Category by Name
 *
 * @param object $data Name of Category for searching.
 * @return object category
 */
function search_category_by_name( $data ) {
	global $wpdb;
	$cat_name = urldecode($data->get_param( 'name' ));

	//Strict search type with 100% of coincidence
	$category = get_term_by( 'name', urldecode($cat_name), 'product_cat' );

	// //Search with like type
	// $category = $wpdb->get_results("
 //        SELECT DISTINCT t.*, tt.* 
 //        FROM {$wpdb->prefix}terms AS t 
 //        LEFT JOIN {$wpdb->prefix}termmeta ON ( t.term_id = {$wpdb->prefix}termmeta.term_id AND {$wpdb->prefix}termmeta.meta_key='order') 
 //        INNER JOIN {$wpdb->prefix}term_taxonomy AS tt ON t.term_id = tt.term_id 
 //        WHERE tt.taxonomy IN ('product_cat') AND t.name LIKE '%{$cat_name}%'
 //            AND ( ( {$wpdb->prefix}termmeta.meta_key = 'order' OR {$wpdb->prefix}termmeta.meta_key IS NULL ) ) 
 //        ORDER BY {$wpdb->prefix}termmeta.meta_value ASC, t.name ASC
 //    ");

	if (empty($category)) {
		return array(
			"code" => "Category not found",
		    "message" => "Categoría no encontrada.",
		    "data" => array(
		        "status" => 401
		    )
		);
	}

	return $category;
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'wc/v3/products', '/categories_by_name/(?P<name>.+)', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'search_category_by_name'
	) );
} );