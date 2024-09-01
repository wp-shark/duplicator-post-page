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
 *
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
 * This function is responsible for loading the translation files for the plugin.
 * It sets the text domain to 'duplicator-post-page' and specifies the directory
 * where the translation files are located.
 *
 * @param string $domain   The text domain for the plugin.
 * @param bool   $network  Whether the plugin is network activated.
 * @param string $directory The directory where the translation files are located.
 * @return bool True on success, false on failure.
 * @since 1.0.0
 */
load_plugin_textdomain( 'duplicator-post-page', false, DUPLICATOR_POST_PAGE_PLUGIN_DIR . 'languages/' );

function duplicator_post_page() {
	if (isset($_GET['action']) && $_GET['action'] == 'duplicator_post_page' && isset($_GET['post'])) {
		$post_id = absint($_GET['post']);
		$post = get_post($post_id);

		if ($post) {
			$new_post = array(
				'post_title'    => $post->post_title . ' (Copy)',
				'post_content'  => $post->post_content,
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
						add_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value));
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