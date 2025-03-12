<?php

class Album_Reviews {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	protected $version = '2.0.0';

	/**
	 * Unique identifier for your plugin.
	 *
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'album-reviews';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		// all the actions go here

		add_action( 'init', array( $this, 'post_type_reviews' ), 0 );

		add_action( 'init', array( $this, 'review_taxonomies' ), 0 ); // taxonomy for genre

		add_action( 'save_post', array( $this, 'reviews_save_product_postdata' ), 1, 2); // save the custom fields
		//add_action( 'admin_head', array( $this, 'reviews_icon' ) );
		//add_action( 'admin_head', array( $this, 'reviews_header' ) );
		add_action( 'admin_menu', array( $this, 'custom_meta_boxes_reviews' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles') );
		add_action( 'wp_enqueue_scripts', array( $this, 'public_styles' ) );
		add_filter( 'post_type_link', array( $this, 'filter_post_type_link' ), 10, 2 );
		add_filter( 'the_content', array( $this, 'filter_review_content' ), 1 );
		// Rename "featured image"
		add_action('admin_head-post-new.php', array($this, 'change_thumbnail_html'));
		add_action('admin_head-post.php', array($this, 'change_thumbnail_html'));
		add_action( 'add_meta_boxes', array( $this, 'rebuild_thumbnail_metabox' ) );
		add_filter( 'pre_get_posts', array( $this, 'modify_pre_get_posts' ) );
		add_filter( 'the_title', array( $this, 'the_review_title' ), 20 );
		// new custom columns
		add_filter( 'manage_edit-album-review_columns', array( $this, 'edit_album_review_columns' ) );
		add_action( 'manage_album-review_posts_custom_column', array( $this, 'manage_album_review_columns' ), 10, 2 );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/* create the custom post type */
	public function post_type_reviews() {
	    $labels = array(
			'name' => _x('Album Reviews', 'post type general name', 'plague-reviews'),
			'singular_name' => _x('Review', 'post type singular name', 'plague-reviews'),
			'add_new' => __('Add New', 'plague-reviews'),
			'add_new_item' => __('Add New Album Review', 'plague-reviews'),
			'edit_item' => __('Edit Review', 'plague-reviews'),
			'edit' => __('Edit', 'plague-reviews'),
			'new_item' => __('New Album Review', 'plague-reviews'),
			'view_item' => __('View Album Review', 'plague-reviews'),
			'search_items' => __('Search Album Reviews', 'plague-reviews'),
			'not_found' =>  __('No reviews found', 'plague-reviews'),
			'not_found_in_trash' => __('No reviews found in Trash', 'plague-reviews'),
			'view' =>  __('View Album Review', 'plague-reviews'),
			'parent_item_colon' => ''
	  );
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array("slug" => "review/%artist%"),
			'capability_type' => 'post',
			'hierarchical' => false,
			'menu_position' => null,
			'has_archive' => true,
			'supports' => array( 'title','editor','thumbnail' ),
			'exclude_from_search' => false
	  );

	  register_post_type( 'album-review', $args );
	  // flush rewrite rules when post type is registered
	  flush_rewrite_rules();
	}

	public function review_taxonomies() {

		$genre_labels = array(
			'name' => __( 'Genres', 'plague-reviews' ),
			'singular_name' => __( 'Genre', 'plague-reviews' ),
			'search_items' => __( 'Search Genres', 'plague-reviews' ),
			'all_items' => __( 'All Genres', 'plague-reviews' ),
			'parent_item' => __( 'Parent Genre', 'plague-reviews' ),
			'parent_item_colon' => __( 'Parent Genre:', 'plague-reviews' ),
			'edit_item' => __( 'Edit Genre', 'plague-reviews' ),
			'update_item' => __( 'Update Genre', 'plague-reviews' ),
			'add_new_item' => __( 'Add New Genre', 'plague-reviews' ),
			'new_item_name' => __( 'New Genre Name', 'plague-reviews' ),
		);
		$label_uh_labels = array(
			'name' => __( 'Labels', 'plague-reviews' ),
			'singular_name' => __( 'Label', 'plague-reviews' ),
			'search_items' => __( 'Search Labels', 'plague-reviews' ),
			'all_items' => __( 'All Labels', 'plague-reviews' ),
			'parent_item' => __( 'Parent Label', 'plague-reviews' ),
			'parent_item_colon' => __( 'Parent Label:', 'plague-reviews' ),
			'edit_item' => __( 'Edit Label', 'plague-reviews' ),
			'update_item' => __( 'Update Label', 'plague-reviews' ),
			'add_new_item' => __( 'Add New Label', 'plague-reviews' ),
			'new_item_name' => __( 'New Label Name', 'plague-reviews' ),
		);
		$artist_labels = array(
			'name' => __( 'Artists', 'plague-reviews' ),
			'singular_name' => __( 'Artists', 'plague-reviews' ),
			'search_items' => __( 'Search Artists', 'plague-reviews' ),
			'all_items' => __( 'All Artists', 'plague-reviews' ),
			'edit_item' => __( 'Edit Artist', 'plague-reviews' ),
			'update_item' => __( 'Update Artist', 'plague-reviews' ),
			'add_new_item' => __( 'Add New Artist', 'plague-reviews' ),
			'new_item_name' => __( 'New Artist Name', 'plague-reviews' ),
		);
		// check for the genre taxonomy -- this is used in both releases and reviews
		if ( !taxonomy_exists( 'genre' ) ) {
			register_taxonomy( 'genre', array('album-review','plague-release', 'releases'), array( 'hierarchical' => true, 'labels' => $genre_labels, 'query_var' => 'genre', 'rewrite' => array( 'slug' => 'genre' ) ) ); // this is the genre taxonomy for album reviews
		}
		// check for the artist taxonomy -- this is used in both releases and reviews
		if ( !taxonomy_exists( 'artist' ) ) {
			register_taxonomy( 'artist', array('album-review', 'plague-release', 'releases'), array( 'hierarchical' => true, 'labels' => $artist_labels, 'query_var' => 'artist', 'rewrite' => array( 'slug' => 'artist' ) ) ); // this is the artist taxonomy for album reviews
			if ( class_exists('Add_Taxonomy_To_Post_Permalinks') ) {
				$album_taxonomy = new Add_Taxonomy_To_Post_Permalinks( 'artist' );
			}
		}
		// the label taxonomy is only used in reviews, so we don't need to check if it exists
		register_taxonomy( 'label', 'album-review', array( 'hierarchical' => true, 'labels' => $label_uh_labels, 'query_var' => 'label', 'rewrite' => array( 'slug' => 'label' ) ) ); // this is the label taxonomy for album reviews
	}

	public function filter_post_type_link($link, $post) {
	    if ($post->post_type != 'album-review')
	        return $link;

	    if ($cats = get_the_terms($post->ID, 'artist'))
	        $link = str_replace('%artist%', array_pop($cats)->slug, $link);
	    return $link;
	}

	public function ratings() {
		$ratings = array(
			0 => array(
				'value' => 0,
				'label' => __( 'Zero stars', 'plague-reviews' ),
				'html' => ''
			),
			1 => array(
				'value' => 1,
				'label' => __( 'One star', 'plague-reviews' ),
				'html' => '<i class="icon-star"></i>'
			),
			2 => array(
				'value' => 2,
				'label' => __( 'Two stars', 'plague-reviews' ),
				'html' => '<i class="icon-star"></i><i class="icon-star"></i>'
			),
			3 => array(
				'value' => 3,
				'label' => __( 'Three stars', 'plague-reviews' ),
				'html' => '<i class="icon-star"></i><i class="icon-star"></i><i class="icon-star"></i>'
			),
			4 => array(
				'value' => 4,
				'label' => __( 'Four stars', 'plague-reviews' ),
				'html' => '<i class="icon-star"></i><i class="icon-star"></i><i class="icon-star"></i><i class="icon-star"></i>'
			),
			5 => array(
				'value' => 5,
				'label' => __( 'Five stars', 'plague-reviews' ),
				'html' => '<i class="icon-star"></i><i class="icon-star"></i><i class="icon-star"></i><i class="icon-star"></i><i class="icon-star"></i>'
			)
		);
		return $ratings;
	}

	/* create custom meta boxes */

	public function custom_meta_boxes_reviews() {
	    add_meta_box("reviews-details", "Album Details", array( $this, "meta_cpt_reviews"), "album-review", "normal", "low");
	}

	public function meta_cpt_reviews() {
	    global $post;

		echo '<input type="hidden" name="reviews_noncename" id="reviews_noncename" value="' .
		wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

		echo '<p><label for="review_rating">' . __( 'Album Rating', 'plague-reviews' ) . '</label><br />';
		echo '<select name="review_rating">';
		$selected = get_post_meta( $post->ID, 'review_rating', true );
		foreach ( $this->ratings() as $rating ) {
			$value = $rating['value'];
			$label = $rating['label'];
			echo '<option value="' . $value . '" ' . selected( $value, $selected ) . '>' . esc_attr( $label ) . '</option>';
		}
		echo '</select>';

		echo '<p><label for="release_date">' . __( 'Album Release Date', 'plague-reviews' ) . '</label><br />';
		echo '<input class="widefat" type="text" name="release_date" id="datepicker" value="' . wp_kses( get_post_meta( $post->ID, 'release_date', true ), array() ) . '" /></p>';

		echo '<p><label for="url_to_buy">' . __( 'URL to purchase album', 'plague-reviews' ) . '</label><br />';
		echo '<input class="widefat" type="text" name="url_to_buy" value="'.mysql_real_escape_string(get_post_meta($post->ID, 'url_to_buy', true)).'" /></p>';

		echo '<p><label for="tracklist">Track List</label><br />';
		echo wpautop(wp_kses_post(wp_editor( get_post_meta($post->ID, 'tracklist', true), 'tracklist', array('media_buttons' => false, 'teeny' => true, 'quicktags' => false, 'textarea_rows' => 5 ) ), array())).'</p>';

		$kses_allowed = array_merge(wp_kses_allowed_html( 'post' ), array(
			'iframe' => array(
				'src' => array(),
				'style' => array(),
				'width' => array(),
				'height' => array(),
				'scrolling' => array(),
				'frameborder' => array()
			)));
		echo '<p><label for="embed_code">Player Embed Code</label><br />';
		echo '<textarea class="embed_code widefat" rows="5" name="embed_code" />'.wp_kses(get_post_meta($post->ID, 'embed_code', true), $kses_allowed).'</textarea></p>';
	}

	public function rebuild_thumbnail_metabox() {
		remove_meta_box( 'postimagediv', 'album-review', 'side' );
    	add_meta_box('postimagediv', __('Album Cover', 'plague-reviews'), 'post_thumbnail_meta_box', 'album-review', 'side', 'default');
	}

	/**
	 * Filter for the featured image post box
	 *
	 * @since 	2.0.0
	 */
	public function change_thumbnail_html( $content ) {
	    if ( 'album-review' == get_post_type() )
	      add_filter('admin_post_thumbnail_html', array($this,'do_thumb'));
	}

	/**
	 * Replaces "Set featured image" with "Album Cover"
	 *
	 * @since 	2.0.0
	 *
	 * @return 	string 	returns the modified text
	 */
	public function do_thumb($content){
		 return str_replace(__('Set featured image'), __('Select Album Cover', 'plague-reviews'),$content);
	}

	/**
	 * Add reviews to the home page
	 *
	 * @since 2.0.0
	 * @author Justin Tadlock
	 * @link http://justintadlock.com/archives/2010/02/02/showing-custom-post-types-on-your-home-blog-page
	 * @return 	mixed 	returns modification to main query to include new post type
	 */
	public function modify_pre_get_posts( $query ) {
		if ( is_home() && $query->is_main_query() )
			$query->set( 'post_type', array( 'post', 'album-review' ) );

		return $query;
	}

	/**
	 * New columns for album reviews
	 *
	 * @since 2.0.0
	 * @author Chris Reynolds
	 * @return 	$columns 	an array of column header values
	 */
	public function edit_album_review_columns( $columns ) {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => __( 'Album Title', 'plague-reviews' ),
			'artist' => __( 'Artist', 'plague-reviews' ),
			'genre' => __( 'Genres', 'plague-reviews' ),
			'label' => __( 'Label', 'plague-reviews' )
		);
		return $columns;
	}

	/**
	 * Data for new custom columsn
	 *
	 * @since 2.0.0
	 * @author Chris Reynolds
	 */
	public function manage_album_review_columns( $column, $post_id ) {
		global $post;

		switch( $column ) {
			// artist column
			case 'artist' :
				$terms = get_the_terms( $post_id, 'artist' );

				// if artists were found
				if ( !empty( $terms ) ) {
					$out = array();

					// now loop through the artist list and display each on
					foreach ( $terms as $term ) {
						$out[] = sprintf( '<a href="%s">%s</a>',
							esc_url( add_query_arg( array( 'post_type' => $post->post_type, 'artist' => $term->slug ), 'edit.php' ) ),
							esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'artist', 'display' ) )
						);
					}

					// join the terms and separate with a comma
					echo join( ', ', $out );
				} else {
					_e( 'No artists found', 'plague-reviews' );
				}
				break;

			case 'genre' :
				$terms = get_the_terms( $post_id, 'genre' );

				// if genres were found
				if ( !empty( $terms ) ) {
					$out = array();

					// now loop through the genre list and display each on
					foreach ( $terms as $term ) {
						$out[] = sprintf( '<a href="%s">%s</a>',
							esc_url( add_query_arg( array( 'post_type' => $post->post_type, 'genre' => $term->slug ), 'edit.php' ) ),
							esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'genre', 'display' ) )
						);
					}

					// join the terms and separate with a comma
					echo join( ', ', $out );
				} else {
					_e( 'No genres found', 'plague-reviews' );
				}
				break;

			case 'label' :
				$terms = get_the_terms( $post_id, 'label' );

				// if labels were found
				if ( !empty( $terms ) ) {
					$out = array();

					// now loop through the label list and display each on
					foreach ( $terms as $term ) {
						$out[] = sprintf( '<a href="%s">%s</a>',
							esc_url( add_query_arg( array( 'post_type' => $post->post_type, 'label' => $term->slug ), 'edit.php' ) ),
							esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'label', 'display' ) )
						);
					}

					// join the terms and separate with a comma
					echo join( ', ', $out );
				} else {
					_e( 'No labels found', 'plague-reviews' );
				}
				break;
		}
	}

	/**
	 * Alter the_title for review posts on the home page
	 *
	 * @since 2.0.0
	 * @author Chris Reynolds
	 * @return 	string 	returns a string to display before the title (e.g. Review: Artistname - Albumname)
	 */
	public function the_review_title( $title ) {
		global $post;

		if ( get_the_artist_list() && 'album-review' == get_post_type() && in_the_loop() && is_home() ) {
			$the_artist = null;
			if ( get_the_artist_list() ) {
				$the_artist = get_the_artist_list();
			}
			if ( $the_artist ) {
				$new_title = sprintf( __( 'Review: %s - %s', 'plague-reviews' ), $the_artist, $title );
			} else {
				$new_title = sprintf( __( 'Review: %s', 'plague-reviews' ), $title );
			}
			return $new_title;
		} else {
			return $title;
		}
	}

	/* When the post is saved, saves our product data */
	public function reviews_save_product_postdata($post_id, $post) {
		$nonce = isset( $_POST['reviews_noncename'] ) ? $_POST['reviews_noncename'] : 'all the pigs, all lined up';
		if ( !wp_verify_nonce( $nonce, plugin_basename(__FILE__) )) {
			return $post->ID;
		}

		/* confirm user is allowed to save page/post */
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post->ID ))
			return $post->ID;
		} else {
			if ( !current_user_can( 'edit_post', $post->ID ))
			return $post->ID;
		}

		/* ready our data for storage */
		$meta_keys = array(
			'tracklist' => 'text',
			'embed_code' => 'embed',
			'url_to_buy' => 'text',
			'review_rating' => 'numeric',
			'release_date' => 'date'
		);

		/* Add values of $mydata as custom fields */
		foreach ($meta_keys as $meta_key => $type) {
			if( $post->post_type == 'revision' )
				return;
			if ( isset( $_POST[ $meta_key ] ) ) {
				if ( $type == 'text' ) {
					$value = wp_kses_post( $_POST[ $meta_key ] );
				}
				if ( $type == 'embed' ) {
					$kses_allowed = array_merge(wp_kses_allowed_html( 'post' ), array('iframe' => array(
						'src' => array(),
						'style' => array(),
						'width' => array(),
						'height' => array(),
						'scrolling' => array(),
						'frameborder' => array()
						)));
					$value = wp_kses( $_POST[ $meta_key ], $kses_allowed );
				}
				if ( $type == 'numeric' ) {
					if ( is_numeric( $_POST[ $meta_key ] ) ) {
						$value = wp_kses( $_POST[ $meta_key ], array() );
					}
				}
				if ( $type == 'date' ) {
					$value = date_i18n( get_option( 'date_format' ), strtotime( strip_tags( $_POST[ $meta_key ] ) ) );
				}

				update_post_meta( $post->ID, $meta_key, $value );
			} else {
				delete_post_meta( $post->ID, $meta_key );
			}
		}
	}

	public function public_styles() {
		wp_enqueue_style( 'plague-fonts', plugins_url( 'css/plague-fonts.css', __FILE__ ), array(), $this->version );
	}

	public function admin_styles() {
		wp_enqueue_style( 'plague-fonts', plugins_url( 'css/plague-fonts.css', __FILE__ ), array(), $this->version );
		wp_enqueue_style( 'reviews-admin-css', plugins_url( 'css/reviews-admin.css', __FILE__ ), array(), $this->version );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'plague-reviews-js', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery-ui-datepicker' ), '1.0' );
	}

	public function filter_review_content( $content ) {
		global $post;

		// get the artist(s)
		if ( get_the_artist_list() ) {
			$artist_list = get_the_artist_list();
		} else {
			$artist_list = null;
		}

		// get the genres
		if ( get_the_genres() ) {
			$genre_list = get_the_genres();
		} else {
			$genre_list = null;
		}

		// get the label(s)
		if ( get_the_labels() ) {
			$label_list = get_the_labels();
		} else {
			$label_list = null;
		}

		$entry_open = '<div class="alignleft entry-content">';
		$entry_close = '</div>';

		// the artist for output
		$the_artist = null;
		if ( $artist_list ) {
			$the_artist = '<div class="the_artist">';
			$the_artist .= $artist_list;
			$the_artist .= '</div>';
		}

		// get the rating
		$the_rating = null;
		if ( get_post_meta( $post->ID, 'review_rating', true ) ) {
			$rating = get_post_meta( $post->ID, 'review_rating', true );
			$ratings = $this->ratings();
			$the_rating = '<div class="rating">';
			$the_rating .= $ratings[$rating]['html'];
			$the_rating .= '</div>';
		}

		$the_date = null;
		if ( get_post_meta( $post->ID, 'release_date', true ) ) {
			$release_date = get_post_meta( $post->ID, 'release_date', true );
			$the_date = '<div class="release-date">';
			$the_date .= sprintf( '%1$s' . __( 'Release Date:', 'plague-reviews' ) . '%2$s %3$s', '<label for="release-date">', '</label>', strip_tags( $release_date ) );
			$the_date .= '</div>';
		}

		// get the thumbnail
		$thumbnail = null;
		if ( has_post_thumbnail( $post->ID ) ) {
			$the_thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'thumbnail' );
			$the_full_thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'full' );
			$thumbnail_url = $the_thumbnail['0'];
			$thumbnail_full_url = $the_full_thumbnail['0'];

			$thumbnail = '<div class="thumbnail alignleft">';
			$thumbnail .= '<a href="'. htmlspecialchars( $thumbnail_full_url ) . '"><img src="' . $thumbnail_url . '" alt="' . $artist_list . ' - ' . get_the_title( $post->ID ) . '" /></a>';
			$thumbnail .= '</div>';
		}

		// get the purchase link
		$purchase_url = null;
		if ( get_post_meta( $post->ID, 'url_to_buy', true ) ) {
			$url_to_buy = get_post_meta( $post->ID, 'url_to_buy', true );
			$purchase_url = '<div class="purchase-link">' . __( 'Purchase this album:' ) . ' ';
			$purchase_url .= '<a href="' . htmlspecialchars( $url_to_buy ) . '" target="_blank">';
			$purchase_url .= '<i class="icon-cart"></i>';
			$purchase_url .= '</a></div>';
		}

		// get the embed code
		$embed_code = null;
		if ( get_post_meta( $post->ID, 'embed_code', true ) ) {
			$embed = get_post_meta( $post->ID, 'embed_code', true );
			$embed_code = '<div class="clear clearfix embed-code">';
			$embed_code .= $embed;
			$embed_code .= '</div>';
		}

		// get the review meta
		$review_meta = null;
		if ( $genre_list || $label_list ) {
			$review_meta = '<div class="review-meta">';
			if ( $label_list ) {
				$review_meta .= '<span class="label">';
				$review_meta .= $label_list;
				$review_meta .= '</span><br />';
			}
			if ( $genre_list ) {
				$review_meta .= '<span class="genres">';
				$review_meta .= $genre_list;
				$review_meta .= '</span>';
			}
			$review_meta .= '</div>';
		}

		// get the track list
		$the_tracklist = null;
		if ( get_post_meta( $post->ID, 'tracklist', true ) ) {
			$tracklist = get_post_meta( $post->ID, 'tracklist', true );
			$the_tracklist = '<div class="tracklist">';
			$the_tracklist .= wp_kses_post( $tracklist );
			$the_tracklist .= '</div>';
		}

		$before_content = '<div class="review-entry">';
		$after_content = '</div>';

		if ( 'album-review' == get_post_type() && in_the_loop() && is_singular() ) {
			return $thumbnail . $entry_open . $the_artist . $the_rating . $the_date . $before_content . $content . $after_content . $purchase_url . $the_tracklist . $entry_close . $embed_code . $review_meta;
		} else {
			return $content;
		}
	}
}