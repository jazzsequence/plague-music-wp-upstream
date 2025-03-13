<?php
/**
 * Plague Music functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Plague_Music
 * @since Plague Music 1.0
 */

// Adds theme support for post formats.
if ( ! function_exists( 'plaguemusic_post_format_setup' ) ) :
    /**
     * Adds theme support for post formats.
     *
     * @since Plague Music 1.0
     *
     * @return void
     */
    function plaguemusic_post_format_setup() {
        add_theme_support( 'post-formats', array( 'aside', 'audio', 'image', 'quote', 'video' ) );
    }
endif;
add_action( 'after_setup_theme', 'plaguemusic_post_format_setup' );

// Enqueues editor-style.css in the editors.
if ( ! function_exists( 'plaguemusic_editor_style' ) ) :
    /**
     * Enqueues editor-style.css in the editors.
     *
     * @since Plague Music 1.0
     *
     * @return void
     */
    function plaguemusic_editor_style() {
        add_editor_style( get_parent_theme_file_uri( 'assets/css/editor-style.css' ) );
    }
endif;
add_action( 'after_setup_theme', 'plaguemusic_editor_style' );

// Enqueue parent and child theme styles.
function plaguemusic_enqueue_styles() {
    wp_enqueue_style(
        'twentytwentyfive-style',
        get_template_directory_uri() . '/style.css'
    );
    wp_enqueue_style(
        'plaguemusic-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('twentytwentyfive-style')
    );
}
add_action('wp_enqueue_scripts', 'plaguemusic_enqueue_styles');

// Prevent WordPress from automatically loading missing patterns from the /patterns directory
function plaguemusic_unregister_theme_patterns() {
    remove_theme_support('core-block-patterns');
}
add_action('after_setup_theme', 'plaguemusic_unregister_theme_patterns', 9);


// Register child theme patterns
function plaguemusic_register_child_patterns() {
	unregister_block_pattern( 'twentytwentyfive/header' );
	unregister_block_pattern( 'twentytwentyfive/footer' );

    register_block_pattern(
        'twentytwentyfive/header',
        array(
            'title'       => __('Header', 'plaguemusic'),
            'description' => _x('Header with site title and navigation.', 'Block pattern description', 'plaguemusic'),
			'content'     => '<!-- wp:group {"align":"full"} -->
			<div class="wp-block-group alignfull">
				<!-- wp:group {"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
					<div class="wp-block-group alignwide">
						<!-- wp:group {"layout":{"type":"flex","justifyContent":"right"}} -->
						<figure class="wp-block-image plague-logo">
							<a href="' . get_home_url() . '"><img src="' . esc_url( get_stylesheet_directory_uri() . '/assets/images/plague.png' ) . '" alt="Plague Music"/></a>
						</figure>
						<div class="wp-block-group">
							<!-- wp:navigation {"overlayBackgroundColor":"base","overlayTextColor":"contrast","layout":{"type":"flex","justifyContent":"right"}} /-->
						</div>
						<!-- /wp:group -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->',
            'categories'  => array('header'),
            'blockTypes'  => array('core/template-part/header'),
        )
    );

    register_block_pattern(
        'twentytwentyfive/footer',
        array(
            'title'       => __('Footer', 'plaguemusic'),
            'description' => _x('Footer with site info and navigation.', 'Block pattern description', 'plaguemusic'),
            'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
            <div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--50)">
                <!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
                <div class="wp-block-group alignwide">
                    <!-- wp:group {"align":"full","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"top"}} -->
                    <div class="wp-block-group alignfull">
                        <!-- wp:columns -->
                        <div class="wp-block-columns">
                            <!-- wp:column {"width":"100%"} -->
                            <div class="wp-block-column" style="flex-basis:100%">
								<figure class="wp-block-image plague-pl">
									<img src="' . esc_url( get_stylesheet_directory_uri() . '/assets/images/pl.png' ) . '" alt="Plague Music"/>
								</figure>							
                                <!-- wp:site-title {"level":3} /-->
                                <!-- wp:site-tagline /-->
                            </div>
                            <!-- /wp:column -->
                        </div>
                        <!-- /wp:columns -->

                        <!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|80"}},"layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"top","justifyContent":"space-between"}} -->
                        <div class="wp-block-group">
                            <!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","orientation":"vertical"}} -->
                                <!-- wp:navigation-link {"label":"' . esc_html__( 'Blog', 'plaguemusic' ) . '","url":"#"} /-->
                                <!-- wp:navigation-link {"label":"' . esc_html__( 'About', 'plaguemusic' ) . '","url":"#"} /-->
                                <!-- wp:navigation-link {"label":"' . esc_html__( 'FAQs', 'plaguemusic' ) . '","url":"#"} /-->
                                <!-- wp:navigation-link {"label":"' . esc_html__( 'Authors', 'plaguemusic' ) . '","url":"#"} /-->
                            <!-- /wp:navigation -->
                        </div>
                        <!-- /wp:group -->
                    </div>
                    <!-- /wp:group -->
                </div>
                <!-- /wp:group -->
            </div>
            <!-- /wp:group -->',
            'categories'  => array('footer'),
            'blockTypes'  => array('core/template-part/footer'),
        )
    );
}
add_action('init', 'plaguemusic_register_child_patterns');

function plaguemusic_setup_default_pages() {
    // Check if 'Home' page exists using WP_Query
    $home_query = new WP_Query( array(
        'post_type'   => 'page',
        'title'       => 'Home',
        'post_status' => 'publish',
        'posts_per_page' => 1,
    ));

    if ( ! $home_query->have_posts() ) {
        // Create the Home page
        $home_page_id = wp_insert_post( array(
            'post_title'   => 'Home',
            'post_content' => 'Welcome to Plague Music!',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ));
    } else {
        $home_page_id = $home_query->posts[0]->ID;
    }

    // Check if 'About' page exists
    $about_query = new WP_Query( array(
        'post_type'   => 'page',
        'title'       => 'About',
        'post_status' => 'publish',
        'posts_per_page' => 1,
    ));

    if ( ! $about_query->have_posts() ) {
        wp_insert_post( array(
            'post_title'   => 'About',
            'post_content' => 'This is the default About page.',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ));
    }

    // Set the home page as the front page
    update_option( 'show_on_front', 'page' );
    update_option( 'page_on_front', $home_page_id );
}
add_action( 'after_switch_theme', 'plaguemusic_setup_default_pages' );
