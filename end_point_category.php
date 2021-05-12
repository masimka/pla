<?php
/**
 * API End Point Search Category by Name
 *
 * @param object $data Name of Category for searching and Parent Category name.
 * @return array of category tree and all categories data in hierarchy
 */
function search_category_by_name($data) {
	$cat_name = urldecode($data->get_param('name'));
	$parent = urldecode($data->get_param('parent'));

	//Strict search type with 100% of coincidence
	$category = get_terms(array(
		'get' => 'all',
	    'taxonomy' => 'product_cat',
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
		$categories = array();

		//Find parent of category found
		$parents_ids = get_ancestors( $cat->term_id, 'product_cat' );

		//Main category validation (search without parent)
		if (empty($parent)) {
			if (!empty($parents_ids))
				break;
			else
				$categories[] = (object) [
				    'id' => $cat->term_id,
				    'name' => $cat->name,
				    'parent' => 0,
				    'nameparent' => ''
				];
		}

		//Find category parent`s tree
		foreach ( $parents_ids as $key => $term_id ) {
			$parent_data = get_term( $term_id, 'product_cat' );

			//Validate the nearest parent
			if ($key == 0 && $parent_data->name != $parent) break;

			//Save category parent in categories array
			$categories[] = (object) [
			    'id' => $cat->term_id,
			    'name' => $cat->name,
			    'parent' => $parent_data->term_id,
			    'nameparent' => $parent_data->name
			];

			//Clone parent data for create searchable category
			$cat = $parent_data;			
	    }

	    //Save result if not null
		if (!empty($categories)) {
			if (!empty($parent)) {
				$categories[] = (object) [
				    'id' => $parent_data->term_id,
				    'name' => $parent_data->name,
				    'parent' => 0,
				    'nameparent' => ''
				];
			}
			$result[] = $categories;
		}
	}

	//if null result return 401 status
	if (empty($result)) {
		return array(
			"code" => "Category not found",
		    "message" => "Categoría no encontrada.",
		    "data" => array(
		        "status" => 401
		    )
		);
	}

	return $result;
}

//API Hook of custom Endpoint
add_action('rest_api_init', function () {
	register_rest_route('wc/v3/products', '/categories_by_name/(?P<name>.+)', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'search_category_by_name'
	));
});