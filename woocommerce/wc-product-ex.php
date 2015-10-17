<?php
/**
 * @author Piotr Boguslawski
 */

/**
 * Class WC_Product_Ex extends WC_Product class ;)
 */
class WC_Product_Ex extends WC_Product {
	/**
	 * Some kind of copy constructor with type casting ;)
	 * Use this if you want to cast object created as WC_Product to WC_Product_Ex.
	 *
	 * @since 1.0.0
	 * @see dynamic_cast
	 *
	 * @param WC_Product $obj
	 */
	public function __construct( WC_Product $obj ) {
		foreach ( $obj as $property => $value ) {
			$this->$property = $value;
		}
	}

	/**
	 * Returns the product categories as HTML links with adjustable sorting.
	 *
	 * @since 1.0.0
	 *
	 * @param string $sep
	 * @param string $before
	 * @param string $after
	 * @param string $orderby
	 * @param string $order
	 *
	 * @return string
	 */
	public function get_categories( $sep = ', ', $before = '', $after = '', $orderby = 'name', $order = 'ASC' ) {
		$cats  = wc_get_product_terms( $this->id, 'product_cat', [
			'orderby' => $orderby,
			'order'   => $order,
			'fields'  => 'ids'
		] );
		$links = [ ];
		foreach ( $cats as $k => $v ) {
			if ( ( $cat = get_term( (int) $v, 'product_cat' ) )
			     && is_object( $cat )
			) {
				$link    = get_term_link( $cat );
				$links[] = '<a href="' . esc_url( $link ) . '" rel="tag">' . esc_html( $cat->name ) . '</a>';
			}
		}
		$links = apply_filters( "term_links-product_cat", $links );

		return $before . join( $sep, $links ) . $after;
	}
}

