<?php

add_filter( 'allow_subdirectory_install',
	create_function( '', 'return true;' )
);


add_shortcode( 'wc_sale_products', 'wc_sale_products' );
function wc_sale_products( $atts ) {
		global $woocommerce_loop, $product_Query;

		extract( shortcode_atts( array(
			'per_page'      => '12',
			'columns'       => '4',
			'orderby'       => 'title',
			'order'         => 'asc'
		), $atts ) );

		// Get products on sale
		$product_ids_on_sale = wc_get_product_ids_on_sale();

          
          $product_cat = ($_REQUEST['cat']) ? $_REQUEST['cat'] : '';
        
        
		$meta_query   = array();
		$meta_query[] = WC()->query->visibility_meta_query();
		$meta_query[] = WC()->query->stock_status_meta_query();
		$meta_query   = array_filter( $meta_query );
          $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;  
		$args = array(
			'posts_per_page'	=> $per_page,
			'orderby' 			=> $orderby,
			'order' 			=> $order,
			'post_status' 		=> 'publish',
			'post_type' 		=> 'product',
			'meta_query' 		=> $meta_query,
			'post__in'			=> array_merge( array( 0 ), $product_ids_on_sale ),
               'paged'                 => $paged,
               'product_cat'        => $product_cat,
		);
 
		ob_start();

		$product_Query = new WP_Query( apply_filters( 'woocommerce_shortcode_products_query', $args, $atts ) );
          // echo '<pre>'.print_r($product_Query,true).'</pre>';
		$woocommerce_loop['columns'] = $columns;

		if ( $product_Query->have_posts() ) : ?>

			<?php woocommerce_product_loop_start(); ?>

				<?php while ( $product_Query->have_posts() ) : $product_Query->the_post(); ?>

					<?php wc_get_template_part( 'content', 'product' ); ?>

				<?php endwhile; // end of the loop. ?>

			<?php woocommerce_product_loop_end(); ?>
          
          <?php
               else:
                    echo 'No Product Found';
          ?>
          
		<?php endif;

		wp_reset_postdata();
         
          wc_get_template( 'loop/pagination.php',$product_Query);
          
		return '<div class="woocommerce columns-' . $columns . '">' . ob_get_clean() . '</div>';
}


add_shortcode( 'wc_recent_products', 'wc_recent_products' );     
function wc_recent_products( $atts ) {
		global $woocommerce_loop,$product_Query;

		extract( shortcode_atts( array(
			'per_page' 	=> '12',
			'columns' 	=> '4',
			'orderby' 	=> 'date',
			'order' 	=> 'desc'
		), $atts ) );

		$meta_query = WC()->query->get_meta_query();
          $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;  
          
          $product_cat = ($_REQUEST['cat']) ? $_REQUEST['cat'] : '';
          
		$args = array(
			'post_type'				=> 'product',
			'post_status'			=> 'publish',
			'ignore_sticky_posts'	=> 1,
			'posts_per_page' 		=> $per_page,
			'orderby' 				=> $orderby,
			'order' 				=> $order,
			'meta_query' 			=> $meta_query,
               'paged'                 => $paged,
               'product_cat'        => $product_cat
		);

		ob_start();

		$product_Query = new WP_Query( apply_filters( 'woocommerce_shortcode_products_query', $args, $atts ) );

		$woocommerce_loop['columns'] = $columns;

		if ( $product_Query->have_posts() ) : ?>

			<?php woocommerce_product_loop_start(); ?>

				<?php while ( $product_Query->have_posts() ) : $product_Query->the_post(); ?>

					<?php wc_get_template_part( 'content', 'product' ); ?>

				<?php endwhile; // end of the loop. ?>

			<?php woocommerce_product_loop_end(); ?>

          <?php
               else:
                    echo 'No Product Found';
          ?>
		<?php endif;

		wp_reset_postdata();
          
          wc_get_template( 'loop/pagination.php',$product_Query);

		return '<div class="woocommerce columns-' . $columns . '">' . ob_get_clean() . '</div>';
}     


register_sidebar( array(
		'name' => __( 'Category Filter Sidebar', 'flatsome' ),
		'id' => 'category-filter-sidebar',
          'description' => __( 'Show on On Sale and New Products Page.', 'flatsome' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3><div class="tx-div small"></div>',
) );



function get_hierarchical_product_categories(){

     $taxonomy     = 'product_cat';
     $orderby      = 'name';  
     $show_count   = 0;      // 1 for yes, 0 for no
     $pad_counts   = 0;      // 1 for yes, 0 for no
     $hierarchical = 1;      // 1 for yes, 0 for no  
     $title        = '';  
     $empty        = 0;
     $args = array( 
          'taxonomy'     => $taxonomy,
          'orderby'      => $orderby,
          'show_count'   => $show_count,
          'pad_counts'   => $pad_counts,
          'hierarchical' => $hierarchical,
          'title_li'     => $title,
          'hide_empty'   => $empty
     );

     $all_categories = get_categories( $args );
     //print_r($all_categories);

     echo '<ul class="product-categories">';
          foreach ($all_categories as $cat) {
               //print_r($cat);
               if($cat->category_parent == 0) {
                    $cat_id = $cat->term_id;
                    $url = get_permalink().'?cat='.$cat->slug;
                    
                    $current_class_parent = current_category_parent_class($cat->slug,$taxonomy);
                    $current_class  = (!empty($_GET['cat']) && $_GET['cat'] == $cat->slug)? 'current-cat': '';
                    
                    echo '<li class="cat-item cat-item-'.$cat_id.' '.$current_class.$current_class_parent.' cat-parent"><a href="'. $url .'">'. $cat->name .'</a>'; 
                    if(has_hierarchical_product_categories_child($cat_id)){
                         get_hierarchical_product_categories_child($cat_id);
                    }
        
        echo '</li>'; 
        }     
}
echo '</ul></aside>';
}


function get_hierarchical_product_categories_child($parent_id){
     $taxonomy     = 'product_cat';
     $orderby      = 'name';  
     $show_count   = 0;      // 1 for yes, 0 for no
     $pad_counts   = 0;      // 1 for yes, 0 for no
     $hierarchical = 1;      // 1 for yes, 0 for no  
     $title        = '';  
     $empty        = 0;
     $args2 = array(
          'taxonomy'     => $taxonomy,
          'child_of'     => 0,
          'parent'       => $parent_id,
          'orderby'      => $orderby,
          'show_count'   => $show_count,
          'pad_counts'   => $pad_counts,
          'hierarchical' => $hierarchical,
          'title_li'     => $title,
          'hide_empty'   => $empty
     );
     $sub_cats = get_categories( $args2 );
     if($sub_cats) {
          echo '<ul class="children">';
               foreach($sub_cats as $sub_category) {
                    $sub_id = $sub_category->term_id;
                    $url = get_permalink().'?cat='.$sub_category->slug;
                    
                    $current_class_parent = current_category_parent_class($sub_category->slug,$taxonomy);
                    $current_class = (!empty($_GET['cat']) && $_GET['cat'] == $sub_category->slug)? 'current-cat': '';
                    echo  '<li class="cat-item cat-item-'.$sub_id.$current_class_parent.' '.$current_class.' ">
                                   <a href="'.$url.'">'.$sub_category->name.'</a>';
                                   if(has_hierarchical_product_categories_child($sub_id)){
                                        get_hierarchical_product_categories_child($sub_id);
                                   }
                    echo '</li>';
               }
          echo '</ul>';
     } 
}


function has_hierarchical_product_categories_child($parent_id){
     $taxonomy     = 'product_cat';
     $orderby      = 'name';  
     $show_count   = 0;      // 1 for yes, 0 for no
     $pad_counts   = 0;      // 1 for yes, 0 for no
     $hierarchical = 1;      // 1 for yes, 0 for no  
     $title        = '';  
     $empty        = 0;
     $args2 = array(
          'taxonomy'     => $taxonomy,
          'child_of'     => 0,
          'parent'       => $parent_id,
          'orderby'      => $orderby,
          'show_count'   => $show_count,
          'pad_counts'   => $pad_counts,
          'hierarchical' => $hierarchical,
          'title_li'     => $title,
          'hide_empty'   => $empty
     );
     $sub_cats = get_categories( $args2 );
     if($sub_cats){
          return true;
     }
     else{
          return false;
     }
}


function current_category_parent_class($curr_cat_parent,$taxonomy){
     if(!empty($_GET['cat'])){
          $idObj = get_term_by('slug', $_GET['cat'], $taxonomy);
          if(is_object($idObj)){
               $category_id = $idObj->term_id;
               $parents_of_child = get_custom_category_parents($category_id , $taxonomy);
               return $current_class_parent = (!empty($_GET['cat']) && in_array($curr_cat_parent,$parents_of_child))? ' current-cat-parent': '';
          }
     }
     return '';
}


function get_custom_category_parents( $id, $taxonomy = false) {

	if(!($taxonomy && is_taxonomy_hierarchical( $taxonomy )))
		return '';

	$chain = '';
	$parent = get_term( $id, $taxonomy);
	if ( is_wp_error( $parent ) )
		return $parent;

     $name = $parent->slug;

	if ( $parent->parent && ( $parent->parent != $parent->term_id )) {
		$visited[] = $parent->parent;
		$chain = get_custom_category_parents( $parent->parent, $taxonomy);
	}
     	$chain[] = $name;
	
	return $chain;
}
?>
