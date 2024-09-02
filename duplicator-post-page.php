<?php
/**
 * Plugin Name:       Duplicator Post Page
 * Plugin URI:        duplicator-post-page
 * Description:       Duplicate post and page with a single click.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           1.0.0
 * Author:            @iqbal1hossain
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       duplicator-post-page
 * Domain Path:       /languages
 */

 if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Defining plugin constants.
 *
 * @since 1.0.0
 */
define('DUPLICATOR_POST_PAGE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DUPLICATOR_POST_PAGE_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Defining plugin version
 *
 * @since 1.0.0
 */
class Duplicator_Post_Page_Version {
	const PLUGIN_VERSION = '1.0.0';

	public static function get_plugin_version() {
		return self::PLUGIN_VERSION;
	}
}

/**
 * Loads the plugin text domain for the Gutenkit Blocks Addon.
 *
 * @param string $domain   The text domain for the plugin.
 * @param bool   $network  Whether the plugin is network activated.
 * @param string $directory The directory where the translation files are located.
 * @return bool True on success, false on failure.
 * @since 1.0.0
 */
load_plugin_textdomain( 'duplicator-post-page', false, DUPLICATOR_POST_PAGE_PLUGIN_DIR . 'languages/' );

/**
 * Custom slashing functions to prevent special characters from being converted.
 */
class Duplicator_Post_Page_Helper {
	/**
	 * Adds slashes only to strings.
	 *
	 * @param mixed $value Value to slash only if string.
	 * @return string|mixed
	 */
	public static function addslashes_to_strings_only( $value ) {
		return is_string( $value ) ? addslashes( $value ) : $value;
	}

	/**
	 * Replaces faulty core wp_slash().
	 *
	 * @param mixed $value What to add slashes to.
	 * @return mixed
	 */
	public static function recursively_slash_strings( $value ) {
		return map_deep( $value, [ self::class, 'addslashes_to_strings_only' ] );
	}
}

function duplicator_post_page() {
	if (isset($_GET['action']) && $_GET['action'] == 'duplicator_post_page' && isset($_GET['post'])) {
		$post_id = absint($_GET['post']);
		$post = get_post($post_id);

		if ($post) {
			$new_post = array(
				'post_title'    => Duplicator_Post_Page_Helper::recursively_slash_strings($post->post_title . ' (Copy)'),
				'post_content'  => Duplicator_Post_Page_Helper::recursively_slash_strings($post->post_content),
				'post_status'   => 'draft',
				'post_type'     => $post->post_type,
				'post_author'   => $post->post_author,
			);
			$new_post_id = wp_insert_post($new_post);

			if ($new_post_id) {
				// Copy metadata
				$post_meta = get_post_meta($post_id);
				foreach ($post_meta as $meta_key => $meta_values) {
					foreach ($meta_values as $meta_value) {
						add_post_meta($new_post_id, $meta_key, maybe_unserialize(Duplicator_Post_Page_Helper::recursively_slash_strings($meta_value)));
					}
				}

				// Copy taxonomies
				$taxonomies = get_object_taxonomies($post->post_type);
				foreach ($taxonomies as $taxonomy) {
					$terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'ids'));
					wp_set_object_terms($new_post_id, $terms, $taxonomy);
				}

				// Redirect to the edit screen for the new post/page
				wp_redirect(admin_url('edit.php?post_type=' . $post->post_type));
				exit;
			}
		}
	}
}
add_action('admin_action_duplicator_post_page', 'duplicator_post_page');

function duplicator_post_page_link($actions, $post) {
	if (in_array($post->post_type, array('post', 'page')) && current_user_can('edit_posts')) {
		$actions['duplicate'] = '<a href="' . wp_nonce_url('admin.php?action=duplicator_post_page&post=' . $post->ID, basename(__FILE__), 'duplicate_nonce') . '" title="' . __('Duplicate this', 'duplicator-post-page') . '" rel="permalink">' . __('Duplicate', 'duplicator-post-page') . '</a>';
	}
	return $actions;
}
add_filter('post_row_actions', 'duplicator_post_page_link', 10, 2);
add_filter('page_row_actions', 'duplicator_post_page_link', 10, 2);