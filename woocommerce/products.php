<?php
/**
 * @author Piotr Boguslawski
 */


/**
 * @param $short_code
 * @param $caption
 * @param $tag_style can be an array(tag, style)...
 * @param $max_rows
 * @param $max_colums
 */
function pbo_xxx_products( $short_code, $caption, $tag_style, $max_rows, $max_colums ) {
	if ( ! is_array( $tag_style ) ) {
		$tag_style = [ (string) $tag_style !== '' ? (string) $tag_style : 'h3' ];
	}
	echo '<' . esc_html( $tag_style[0] );
	if ( isset( $tag_style[1] ) ) {
		echo " style='$tag_style[1]'";
	}
	echo '>';
	echo $caption;
	echo '</' . esc_html( $tag_style[0] ) . '>';
	echo execute_shortcode( $short_code,
		array(
			'per_page' => (int) $max_rows * $max_colums,
			'columns'  => $max_colums,
		) );
}


function pbo_featured_products( $tag_style, $max_rows, $max_colums, $caption = '' ) {
	if ( wc_get_featured_product_ids() ) {
		pbo_xxx_products( 'featured_products', esc_html( $caption === '' ? __( 'Featured products', 'pbo-tools' ) : $caption ), $tag_style, $max_rows, $max_colums );
	}
}


function pbo_sale_products( $tag_style, $max_rows, $max_colums ) {
	if ( wc_get_product_ids_on_sale() ) {
		pbo_xxx_products( 'sale_products', esc_html__( 'Products on sale', 'pbo-tools' ), $tag_style, $max_rows, $max_colums );
	}
}


function pbo_recent_products( $tag_style, $max_rows, $max_colums ) {
	pbo_xxx_products( 'recent_products', esc_html__( 'Recent products', 'pbo-tools' ), $tag_style, $max_rows, $max_colums );
}


function pbo_best_selling_products( $tag_style, $max_rows, $max_colums ) {
	pbo_xxx_products( 'best_selling_products', esc_html__( 'Best selling products', 'pbo-tools' ), $tag_style, $max_rows, $max_colums );
}


function pbo_top_rated_products( $tag_style, $max_rows, $max_colums ) {
	pbo_xxx_products( 'top_rated_products', esc_html__( 'Top rated products', 'pbo-tools' ), $tag_style, $max_rows, $max_colums );
}
