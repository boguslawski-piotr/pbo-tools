<?php
/**
 *
 * @source Search Everything 8.1.3, http://wordpress.org/plugins/search-everything/
 */

/**
 * Class SearchEverything
 */
class SearchEverything {
	var $options;
	var $ajax_request;
	private $query_instance;

	function SearchEverything( $ajax_query = false ) {
		$this->ajax_request = $ajax_query ? true : false;
		$this->options      = $this->se_get_default_options();

		if ( $this->ajax_request ) {
			$this->init_ajax( $ajax_query );
		} else {
			$this->init();
		}
	}

	function se_get_default_options() {
		$se_options = array(
			'se_exclude_categories'      => '',
			'se_exclude_categories_list' => '',
			'se_exclude_posts'           => '',
			'se_exclude_posts_list'      => '',
			'se_use_page_search'         => true,
			'se_use_comment_search'      => true,
			'se_use_tag_search'          => true,
			'se_use_tax_search'          => true,
			'se_use_category_search'     => true,
			'se_approved_comments_only'  => true,
			'se_approved_pages_only'     => true,
			'se_use_excerpt_search'      => true,
			'se_use_draft_search'        => false,
			'se_use_attachment_search'   => false,
			'se_use_authors'             => false,
			'se_use_cmt_authors'         => false,
			'se_use_metadata_search'     => true,
			'se_use_highlight'           => true,
			'se_highlight_color'         => '#eeeeee',
			'se_highlight_style'         => '',
		);

		return $se_options;
	}

	function init_ajax( $query ) {
		$this->search_hooks();
	}

	function init() {
		$this->search_hooks();

		// Highlight content
		if ( $this->options['se_use_highlight'] ) {
			add_filter( 'the_content', array( &$this, 'se_postfilter' ), 11 );
			add_filter( 'the_title', array( &$this, 'se_postfilter' ), 11 );
			add_filter( 'the_excerpt', array( &$this, 'se_postfilter' ), 11 );
		}
	}

	function search_hooks() {
		//add filters based upon option settings

		if ( $this->options['se_use_tag_search'] || $this->options['se_use_category_search'] || $this->options['se_use_tax_search'] ) {
			add_filter( 'posts_join', array( &$this, 'se_terms_join' ) );
		}

		if ( $this->options['se_use_page_search'] ) {
			add_filter( 'posts_where', array( &$this, 'se_search_pages' ) );
		}

		if ( $this->options['se_use_comment_search'] ) {
			add_filter( 'posts_join', array( &$this, 'se_comments_join' ) );
			// Highlight content
			if ( $this->options['se_use_highlight'] ) {
				add_filter( 'comment_text', array( &$this, 'se_postfilter' ) );
			}
		}

		if ( $this->options['se_use_draft_search'] ) {
			add_filter( 'posts_where', array( &$this, 'se_search_draft_posts' ) );
		}

		if ( $this->options['se_use_attachment_search'] ) {
			add_filter( 'posts_where', array( &$this, 'se_search_attachments' ) );
		}

		if ( $this->options['se_use_metadata_search'] ) {
			add_filter( 'posts_join', array( &$this, 'se_search_metadata_join' ) );
		}

		if ( $this->options['se_exclude_categories_list'] != '' ) {
			add_filter( 'posts_join', array( &$this, 'se_exclude_categories_join' ) );
		}

		if ( $this->options['se_use_authors'] ) {
			add_filter( 'posts_join', array( &$this, 'se_search_authors_join' ) );
		}

		add_filter( 'posts_search', array( &$this, 'se_search_where' ), 10, 2 );

		add_filter( 'posts_where', array( &$this, 'se_no_revisions' ) );

		add_filter( 'posts_request', array( &$this, 'se_distinct' ) );

		add_filter( 'posts_where', array( &$this, 'se_no_future' ) );
	}

	// creates the list of search keywords from the 's' parameters.
	function se_get_search_terms() {
		global $wpdb;
		$s            = isset( $this->query_instance->query_vars['s'] ) ? $this->query_instance->query_vars['s'] : '';
		$sentence     = isset( $this->query_instance->query_vars['sentence'] ) ? $this->query_instance->query_vars['sentence'] : false;
		$search_terms = array();

		if ( ! empty( $s ) ) {
			// added slashes screw with quote grouping when done early, so done later
			$s = stripslashes( $s );
			if ( $sentence ) {
				$search_terms = array( $s );
			} else {
				preg_match_all( '/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $s, $matches );
				$search_terms = array_map( create_function( '$a', 'return trim($a, "\\"\'\\n\\r ");' ), $matches[0] );
			}
		}

		return $search_terms;
	}

	// add where clause to the search query
	function se_search_where( $where, $wp_query ) {

		if ( ! $wp_query->is_search() && ! $this->ajax_request ) {
			return $where;
		}

		$this->query_instance = &$wp_query;
		global $wpdb;

		$searchQuery = $this->se_search_default();

		//add filters based upon option settings
		if ( $this->options['se_use_tag_search'] ) {
			$searchQuery .= $this->se_build_search_tag();
		}
		if ( $this->options['se_use_category_search'] || $this->options['se_use_tax_search'] ) {
			$searchQuery .= $this->se_build_search_categories();
		}
		if ( $this->options['se_use_metadata_search'] ) {
			$searchQuery .= $this->se_build_search_metadata();
		}
		if ( $this->options['se_use_excerpt_search'] ) {
			$searchQuery .= $this->se_build_search_excerpt();
		}
		if ( $this->options['se_use_comment_search'] ) {
			$searchQuery .= $this->se_build_search_comments();
		}
		if ( $this->options['se_use_authors'] ) {
			$searchQuery .= $this->se_search_authors();
		}
		if ( $searchQuery != '' ) {
			$where = preg_replace( '#\(\(\(.*?\)\)\)#', '((' . $searchQuery . '))', $where );

		}
		if ( $this->options['se_exclude_posts_list'] != '' ) {
			$where .= $this->se_build_exclude_posts();
		}
		if ( $this->options['se_exclude_categories_list'] != '' ) {
			$where .= $this->se_build_exclude_categories();

		}

		return $where;
	}
	// search for terms in default locations like title and content
	// replacing the old search terms seems to be the best way to
	// avoid issue with multiple terms
	function se_search_default() {
		global $wpdb;

		$not_exact        = empty( $this->query_instance->query_vars['exact'] );
		$search_sql_query = '';
		$seperator        = '';
		$terms            = $this->se_get_search_terms();

		// if it's not a sentance add other terms
		$search_sql_query .= '(';
		foreach ( $terms as $term ) {
			$search_sql_query .= $seperator;

			$esc_term = esc_sql( $term );
			if ( $not_exact ) {
				$esc_term = "%$esc_term%";
			}

			$like_title = "($wpdb->posts.post_title LIKE '$esc_term')";
			$like_post  = "($wpdb->posts.post_content LIKE '$esc_term')";

			$search_sql_query .= "($like_title OR $like_post)";

			$seperator = ' AND ';
		}

		$search_sql_query .= ')';

		return $search_sql_query;
	}

	// Exclude post revisions
	function se_no_revisions( $where ) {
		global $wpdb;
		if ( ! empty( $this->query_instance->query_vars['s'] ) ) {
			//if ( !$this->wp_ver28 ) {
			//	$where = 'AND (' . substr( $where, strpos( $where, 'AND' )+3 ) . ") AND $wpdb->posts.post_type != 'revision'";
			//}
			$where = ' AND (' . substr( $where, strpos( $where, 'AND' ) + 3 ) . ') AND post_type != \'revision\'';
		}

		return $where;
	}

	// Exclude future posts fix provided by Mx
	function se_no_future( $where ) {
		global $wpdb;
		if ( ! empty( $this->query_instance->query_vars['s'] ) ) {
			//if ( !$this->wp_ver28 ) {
			//	$where = 'AND (' . substr( $where, strpos( $where, 'AND' )+3 ) . ") AND $wpdb->posts.post_status != 'future'";
			//}
			$where = 'AND (' . substr( $where, strpos( $where, 'AND' ) + 3 ) . ') AND post_status != \'future\'';
		}

		return $where;
	}

	//Duplicate fix provided by Tiago.Pocinho
	function se_distinct( $query ) {
		global $wpdb;
		if ( ! empty( $this->query_instance->query_vars['s'] ) ) {
			if ( strstr( $query, 'DISTINCT' ) ) {
			} else {
				$query = str_replace( 'SELECT', 'SELECT DISTINCT', $query );
			}
		}

		return $query;
	}

	//search pages (except password protected pages provided by loops)
	function se_search_pages( $where ) {
		global $wpdb;
		if ( ! empty( $this->query_instance->query_vars['s'] ) ) {

			$where = str_replace( '"', '\'', $where );
			if ( $this->options['se_approved_pages_only'] ) {
				$where = str_replace( "post_type = 'post'", " AND 'post_password = '' AND ", $where );
			} else { // < v 2.1
				$where = str_replace( 'post_type = \'post\' AND ', '', $where );
			}
		}

		return $where;
	}

	// create the search excerpts query
	function se_build_search_excerpt() {
		global $wpdb;
		$vars = $this->query_instance->query_vars;

		$s            = $vars['s'];
		$search_terms = $this->se_get_search_terms();
		$exact        = isset( $vars['exact'] ) ? $vars['exact'] : '';
		$search       = '';

		if ( ! empty( $search_terms ) ) {
			// Building search query
			$n         = ( $exact ) ? '' : '%';
			$searchand = '';
			foreach ( $search_terms as $term ) {
				$term = addslashes_gpc( $term );
				$search .= "{$searchand}($wpdb->posts.post_excerpt LIKE '{$n}{$term}{$n}')";
				$searchand = ' AND ';
			}
			$sentence_term = esc_sql( $s );
			if ( count( $search_terms ) > 1 && $search_terms[0] != $sentence_term ) {
				$search = "($search) OR ($wpdb->posts.post_excerpt LIKE '{$n}{$sentence_term}{$n}')";
			}
			if ( ! empty( $search ) ) {
				$search = " OR ({$search}) ";
			}
		}

		return $search;
	}


	//search drafts
	function se_search_draft_posts( $where ) {
		global $wpdb;
		if ( ! empty( $this->query_instance->query_vars['s'] ) ) {
			$where = str_replace( '"', '\'', $where );
			//if ( !$this->wp_ver28 ) {
			//	$where = str_replace( " AND (post_status = 'publish'", " AND ((post_status = 'publish' OR post_status = 'draft')", $where );
			//}
			//else
			{
				$where = str_replace( " AND ($wpdb->posts.post_status = 'publish'", " AND ($wpdb->posts.post_status = 'publish' OR $wpdb->posts.post_status = 'draft'", $where );
			}
			$where = str_replace( " AND (post_status = 'publish'", " AND (post_status = 'publish' OR post_status = 'draft'", $where );
		}

		return $where;
	}

	//search attachments
	function se_search_attachments( $where ) {
		global $wpdb;
		if ( ! empty( $this->query_instance->query_vars['s'] ) ) {
			$where = str_replace( '"', '\'', $where );
			//if ( !$this->wp_ver28 ) {
			//	$where = str_replace( " AND (post_status = 'publish'", " AND (post_status = 'publish' OR post_type = 'attachment'", $where );
			//	$where = str_replace( "AND post_type != 'attachment'", "", $where );
			//}
			//else
			{
				$where = str_replace( " AND ($wpdb->posts.post_status = 'publish'", " AND ($wpdb->posts.post_status = 'publish' OR $wpdb->posts.post_type = 'attachment'", $where );
				$where = str_replace( "AND $wpdb->posts.post_type != 'attachment'", "", $where );
			}
		}

		return $where;
	}

	// create the comments data query
	function se_build_search_comments() {
		global $wpdb;
		$vars = $this->query_instance->query_vars;

		$s            = $vars['s'];
		$search_terms = $this->se_get_search_terms();
		$exact        = isset( $vars['exact'] ) ? $vars['exact'] : '';
		$search       = '';
		if ( ! empty( $search_terms ) ) {
			// Building search query on comments content
			$n             = ( $exact ) ? '' : '%';
			$searchand     = '';
			$searchContent = '';
			foreach ( $search_terms as $term ) {
				$term = addslashes_gpc( $term );
				$searchContent .= "{$searchand}(cmt.comment_content LIKE '{$n}{$term}{$n}')";
				$searchand = ' AND ';
			}
			$sentense_term = esc_sql( $s );
			if ( count( $search_terms ) > 1 && $search_terms[0] != $sentense_term ) {
				$searchContent = "($searchContent) OR (cmt.comment_content LIKE '{$n}{$sentense_term}{$n}')";
			}
			$search = $searchContent;
			// Building search query on comments author
			if ( $this->options['se_use_cmt_authors'] ) {
				$searchand      = '';
				$comment_author = '';
				foreach ( $search_terms as $term ) {
					$term = addslashes_gpc( $term );
					$comment_author .= "{$searchand}(cmt.comment_author LIKE '{$n}{$term}{$n}')";
					$searchand = ' AND ';
				}
				$sentence_term = esc_sql( $s );
				if ( count( $search_terms ) > 1 && $search_terms[0] != $sentence_term ) {
					$comment_author = "($comment_author) OR (cmt.comment_author LIKE '{$n}{$sentence_term}{$n}')";
				}
				$search = "($search) OR ($comment_author)";
			}
			if ( $this->options['se_approved_comments_only'] ) {
				$comment_approved = "AND cmt.comment_approved =  '1'";
				$search           = "($search) $comment_approved";
			}
			if ( ! empty( $search ) ) {
				$search = " OR ({$search}) ";
			}
		}

		return $search;
	}

	// Build the author search
	function se_search_authors() {
		global $wpdb;
		$s            = $this->query_instance->query_vars['s'];
		$search_terms = $this->se_get_search_terms();
		$n            = ( isset( $this->query_instance->query_vars['exact'] ) && $this->query_instance->query_vars['exact'] ) ? '' : '%';
		$search       = '';
		$searchand    = '';

		if ( ! empty( $search_terms ) ) {
			// Building search query
			foreach ( $search_terms as $term ) {
				$term = addslashes_gpc( $term );
				$search .= "{$searchand}(u.display_name LIKE '{$n}{$term}{$n}')";
				$searchand = ' OR ';
			}
			$sentence_term = esc_sql( $s );
			if ( count( $search_terms ) > 1 && $search_terms[0] != $sentence_term ) {
				$search .= " OR (u.display_name LIKE '{$n}{$sentence_term}{$n}')";
			}

			if ( ! empty( $search ) ) {
				$search = " OR ({$search}) ";
			}

		}

		return $search;
	}

	// create the search meta data query
	function se_build_search_metadata() {
		global $wpdb;
		$s            = $this->query_instance->query_vars['s'];
		$search_terms = $this->se_get_search_terms();
		$n            = ( isset( $this->query_instance->query_vars['exact'] ) && $this->query_instance->query_vars['exact'] ) ? '' : '%';
		$search       = '';

		if ( ! empty( $search_terms ) ) {
			// Building search query
			$searchand = '';
			foreach ( $search_terms as $term ) {
				$term = addslashes_gpc( $term );
				$search .= "{$searchand}(m.meta_value LIKE '{$n}{$term}{$n}')";
				$searchand = ' AND ';
			}
			$sentence_term = esc_sql( $s );
			if ( count( $search_terms ) > 1 && $search_terms[0] != $sentence_term ) {
				$search = "($search) OR (m.meta_value LIKE '{$n}{$sentence_term}{$n}')";
			}

			if ( ! empty( $search ) ) {
				$search = " OR ({$search}) ";
			}

		}

		return $search;
	}

	// create the search tag query
	function se_build_search_tag() {
		global $wpdb;
		$vars = $this->query_instance->query_vars;

		$s            = $vars['s'];
		$search_terms = $this->se_get_search_terms();
		$exact        = isset( $vars['exact'] ) ? $vars['exact'] : '';
		$search       = '';

		if ( ! empty( $search_terms ) ) {
			// Building search query
			$n         = ( $exact ) ? '' : '%';
			$searchand = '';
			foreach ( $search_terms as $term ) {
				$term = addslashes_gpc( $term );
				$search .= "{$searchand}(tter.name LIKE '{$n}{$term}{$n}')";
				$searchand = ' OR ';
			}
			$sentence_term = esc_sql( $s );
			if ( count( $search_terms ) > 1 && $search_terms[0] != $sentence_term ) {
				$search = "($search) OR (tter.name LIKE '{$n}{$sentence_term}{$n}')";
			}
			if ( ! empty( $search ) ) {
				$search = " OR ({$search}) ";
			}
		}

		return $search;
	}

	// create the search categories query
	function se_build_search_categories() {
		global $wpdb;
		$vars = $this->query_instance->query_vars;

		$s            = $vars['s'];
		$search_terms = $this->se_get_search_terms();
		$exact        = isset( $vars['exact'] ) ? $vars['exact'] : '';
		$search       = '';

		if ( ! empty( $search_terms ) ) {
			// Building search query for categories slug.
			$n          = ( $exact ) ? '' : '%';
			$searchand  = '';
			$searchSlug = '';
			foreach ( $search_terms as $term ) {
				$term = addslashes_gpc( $term );
				$searchSlug .= "{$searchand}(tter.slug LIKE '{$n}" . sanitize_title_with_dashes( $term ) . "{$n}')";
				$searchand = ' AND ';
			}
			if ( count( $search_terms ) > 1 && $search_terms[0] != $s ) {
				$searchSlug = "($searchSlug) OR (tter.slug LIKE '{$n}" . sanitize_title_with_dashes( $s ) . "{$n}')";
			}
			if ( ! empty( $searchSlug ) ) {
				$search = " OR ({$searchSlug}) ";
			}

			// Building search query for categories description.
			$searchand  = '';
			$searchDesc = '';
			foreach ( $search_terms as $term ) {
				$term = addslashes_gpc( $term );
				$searchDesc .= "{$searchand}(ttax.description LIKE '{$n}{$term}{$n}')";
				$searchand = ' AND ';
			}
			$sentence_term = esc_sql( $s );
			if ( count( $search_terms ) > 1 && $search_terms[0] != $sentence_term ) {
				$searchDesc = "($searchDesc) OR (ttax.description LIKE '{$n}{$sentence_term}{$n}')";
			}
			if ( ! empty( $searchDesc ) ) {
				$search = $search . " OR ({$searchDesc}) ";
			}
		}

		return $search;
	}

	// create the Posts exclusion query
	function se_build_exclude_posts() {
		global $wpdb;
		$excludeQuery = '';
		if ( ! empty( $this->query_instance->query_vars['s'] ) ) {
			$excludedPostList = trim( $this->options['se_exclude_posts_list'] );
			if ( $excludedPostList != '' ) {
				$excluded_post_list = array();
				foreach ( explode( ',', $excludedPostList ) as $post_id ) {
					$excluded_post_list[] = (int) $post_id;
				}
				$excl_list    = implode( ',', $excluded_post_list );
				$excludeQuery = ' AND (' . $wpdb->posts . '.ID NOT IN ( ' . $excl_list . ' ))';
			}
		}

		return $excludeQuery;
	}

	// create the Categories exclusion query
	function se_build_exclude_categories() {
		global $wpdb;
		$excludeQuery = '';
		if ( ! empty( $this->query_instance->query_vars['s'] ) ) {
			$excludedCatList = trim( $this->options['se_exclude_categories_list'] );
			if ( $excludedCatList != '' ) {
				$excluded_cat_list = array();
				foreach ( explode( ',', $excludedCatList ) as $cat_id ) {
					$excluded_cat_list[] = (int) $cat_id;
				}
				$excl_list    = implode( ',', $excluded_cat_list );
				$excludeQuery = " AND ( ctax.term_id NOT IN ( " . $excl_list . " ) OR (wp_posts.post_type IN ( 'page' )))";
			}
		}

		return $excludeQuery;
	}

	//join for excluding categories - Deprecated in 2.3
	function se_exclude_categories_join( $join ) {
		global $wpdb;

		if ( ! empty( $this->query_instance->query_vars['s'] ) ) {
			$join .= " LEFT JOIN $wpdb->term_relationships AS crel ON ($wpdb->posts.ID = crel.object_id) LEFT JOIN $wpdb->term_taxonomy AS ctax ON (ctax.taxonomy = 'category' AND crel.term_taxonomy_id = ctax.term_taxonomy_id) LEFT JOIN $wpdb->terms AS cter ON (ctax.term_id = cter.term_id) ";
		}

		return $join;
	}

	//join for searching comments
	function se_comments_join( $join ) {
		global $wpdb;

		if ( ! empty( $this->query_instance->query_vars['s'] ) ) {
			$join .= " LEFT JOIN $wpdb->comments AS cmt ON ( cmt.comment_post_ID = $wpdb->posts.ID ) ";
		}

		return $join;
	}

	//join for searching authors

	function se_search_authors_join( $join ) {
		global $wpdb;

		if ( ! empty( $this->query_instance->query_vars['s'] ) ) {
			$join .= " LEFT JOIN $wpdb->users AS u ON ($wpdb->posts.post_author = u.ID) ";
		}

		return $join;
	}

	//join for searching metadata
	function se_search_metadata_join( $join ) {
		global $wpdb;

		if ( ! empty( $this->query_instance->query_vars['s'] ) ) {
			$join .= " LEFT JOIN $wpdb->postmeta AS m ON ($wpdb->posts.ID = m.post_id) ";
		}

		return $join;
	}

	//join for searching tags
	function se_terms_join( $join ) {
		global $wpdb;

		if ( ! empty( $this->query_instance->query_vars['s'] ) ) {

			// if we're searching for categories
			if ( $this->options['se_use_category_search'] ) {
				$on[] = "ttax.taxonomy = 'category'";
			}

			// if we're searching for tags
			if ( $this->options['se_use_tag_search'] ) {
				$on[] = "ttax.taxonomy = 'post_tag'";
			}
			// if we're searching custom taxonomies
			if ( $this->options['se_use_tax_search'] ) {
				$all_taxonomies    = get_taxonomies();
				$filter_taxonomies = array( 'post_tag', 'category', 'nav_menu', 'link_category' );

				foreach ( $all_taxonomies as $taxonomy ) {
					if ( in_array( $taxonomy, $filter_taxonomies ) ) {
						continue;
					}
					$on[] = "ttax.taxonomy = '" . addslashes( $taxonomy ) . "'";
				}
			}
			// build our final string
			$on = ' ( ' . implode( ' OR ', $on ) . ' ) ';
			$join .= " LEFT JOIN $wpdb->term_relationships AS trel ON ($wpdb->posts.ID = trel.object_id) LEFT JOIN $wpdb->term_taxonomy AS ttax ON ( " . $on . " AND trel.term_taxonomy_id = ttax.term_taxonomy_id) LEFT JOIN $wpdb->terms AS tter ON (ttax.term_id = tter.term_id) ";
		}

		return $join;
	}

	// Highlight the searched terms into Title, excerpt and content
	// in the search result page.
	function se_postfilter( $postcontent ) {
		global $wpdb;
		$s = isset( $this->query_instance->query_vars['s'] ) ? $this->query_instance->query_vars['s'] : '';
		// highlighting
		if ( is_search() && $s != '' ) {
			$highlight_color = $this->options['se_highlight_color'];
			$highlight_style = $this->options['se_highlight_style'];
			$search_terms    = $this->se_get_search_terms();
			foreach ( $search_terms as $term ) {
				if ( preg_match( '/\>/', $term ) ) {
					continue;
				} //don't try to highlight this one
				$term = preg_quote( $term );

				if ( $highlight_color != '' ) {
					$postcontent = preg_replace(
						'"(?<!\<)(?<!\w)(\pL*' . $term . '\pL*)(?!\w|[^<>]*>)"iu'
						, '<span class="search-everything-highlight-color" style="background-color:' . $highlight_color . '">$1</span>'
						, $postcontent
					);
				} else {
					$postcontent = preg_replace(
						'"(?<!\<)(?<!\w)(\pL*' . $term . '\pL*)(?!\w|[^<>]*>)"iu'
						, '<span class="search-everything-highlight" style="' . $highlight_style . '">$1</span>'
						, $postcontent
					);
				}
			}
		}

		return $postcontent;
	}
} // END

/*
function se_enqueue_styles() {
	wp_enqueue_style('se-link-styles', SE_PLUGIN_URL . '/static/css/se-styles.css');
}
add_action('wp_enqueue_scripts', 'se_enqueue_styles');
*/

