<?php
/**
 * Podcasting for WordPress
 *
 */

add_action( 'plugins_loaded', 'automattic_podcasting_init' );

function automattic_podcasting_init() {
	$automattic_podcasting_init = new Automattic_Podcasting();
}

class Automattic_Podcasting {
	function __construct() {
		if ( is_admin() ) {
			require_once plugin_dir_path( __FILE__ ) . 'podcasting/settings.php';
		}

		if ( self::podcasting_is_enabled() ) {
			add_action( 'after_setup_theme', array( 'Automattic_Podcasting', 'podcasting_add_post_thumbnail_support' ), 20 ); // Later then themes normally do.
			remove_action( 'rss2_head', 'rss2_blavatar' );
			remove_action( 'rss2_head', 'rss2_site_icon' );
			remove_filter( 'the_excerpt_rss', 'add_bug_to_feed', 100 );
			remove_action( 'rss2_head', 'rsscloud_add_rss_cloud_element' );

			if ( ! is_admin() ) {
				add_action( 'wp', array( 'Automattic_Podcasting', 'podcasting_custom_feed' ) );
			}

			require_once plugin_dir_path( __FILE__ ) . 'podcasting/widget.php';
		}
	}

	/**
	 * Load the code for the Podcasting meta box on Edit/New Post
	 *
	 */
	function podcasting_load_post_meta_box_code() {
		if ( self::podcasting_is_enabled() ) {
			require_once plugin_dir_path( __FILE__ ) . 'podcasting/post-meta-box.php';
		}
	}

	/**
	 * Load the file containing iTunes specific feed hooks.
	 *
	 * @uses podcasting/customize-feed.php
	 */
	function podcasting_custom_feed() {
		if ( is_feed() && is_category( get_option( 'podcasting_archive' ) ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'podcasting/customize-feed.php';
		}
	}

	/**
	 * Ensure that theme support for post thumbnails exists.
	 * We will be using these for episode-level feed images.
	 */
	function podcasting_add_post_thumbnail_support() {
		add_theme_support( 'post-thumbnails' );
	}

	/**
	 * Is podcasting enabled?
	 *
	 * If the user has chosen a category for their podcast feed
	 * this function will return true. If not, then false.
	 *
	 * @return bool
	 */
	static function podcasting_is_enabled() {
		return (bool) get_option( 'podcasting_archive', false );
	}
}
