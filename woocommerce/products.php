<?php
/**
 * @author Piotr Boguslawski
 */


/**
 * @param $short_code
 * @param $caption
 * @param $tag_class can be an array(tag, class)...
 * @param $max_rows
 * @param $max_colums
 */
function pbo_run_woocommerce_products_shortcode( $short_code, $caption, $tag_class, $max_rows, $max_colums ) {
	if ( ! is_array( $tag_class ) ) {
		$tag_class = $tag_class !== '' ? explode( ' ', $tag_class ) : [ 'h3' ];
	}
	echo '<' . esc_html( $tag_class[0] );
	if ( isset( $tag_class[1] ) ) {
		echo " class='$tag_class[1]'";
	}
	echo '>';
	echo wp_kses_post( $caption );
	echo '</' . esc_html( $tag_class[0] ) . '>';
	echo execute_shortcode( $short_code,
		array(
			'per_page' => (int) $max_rows * $max_colums,
			'columns'  => $max_colums,
		) );
}


function pbo_featured_products( $tag_class, $max_rows, $max_colums, $caption = '' ) {
	if ( wc_get_featured_product_ids() ) {
		pbo_run_woocommerce_products_shortcode( 'featured_products', esc_html( $caption === '' ? __( 'Featured products', 'pbo-tools' ) : $caption ), $tag_class, $max_rows, $max_colums );
	}
}


function pbo_sale_products( $tag_class, $max_rows, $max_colums, $caption = '' ) {
	if ( wc_get_product_ids_on_sale() ) {
		pbo_run_woocommerce_products_shortcode( 'sale_products', esc_html( $caption === '' ? __( 'Products on sale', 'pbo-tools' ) : $caption ), $tag_class, $max_rows, $max_colums );
	}
}


function pbo_recent_products( $tag_class, $max_rows, $max_colums, $caption = '' ) {
	pbo_run_woocommerce_products_shortcode( 'recent_products', esc_html( $caption === '' ? __( 'Recent products', 'pbo-tools' ) : $caption ), $tag_class, $max_rows, $max_colums );
}


function pbo_best_selling_products( $tag_class, $max_rows, $max_colums, $caption = '' ) {
	pbo_run_woocommerce_products_shortcode( 'best_selling_products', esc_html( $caption === '' ? __( 'Best selling products', 'pbo-tools' ) : $caption ), $tag_class, $max_rows, $max_colums );
}


function pbo_top_rated_products( $tag_class, $max_rows, $max_colums, $caption = '' ) {
	pbo_run_woocommerce_products_shortcode( 'top_rated_products', esc_html( $caption === '' ? __( 'Top rated products', 'pbo-tools' ) : $caption ), $tag_class, $max_rows, $max_colums );
}
