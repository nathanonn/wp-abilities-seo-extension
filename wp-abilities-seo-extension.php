<?php
/**
 * Plugin Name: Abilities API Extension for Rank Math SEO
 * Plugin URI: https://github.com/nathanonn/wp-abilities-seo-extension
 * Description: Exposes Rank Math SEO functionality through the WordPress Abilities API, enabling AI agents to discover, read, and manage SEO settings programmatically.
 * Version: 1.0.0
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Author: Nathan Onn
 * Author URI: https://github.com/nathanonn
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-abilities-seo-extension
 * Domain Path: /languages
 *
 * @package SeoAbilities
 */

namespace SeoAbilities;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'SEO_ABILITIES_VERSION', '1.0.0' );
define( 'SEO_ABILITIES_FILE', __FILE__ );
define( 'SEO_ABILITIES_PATH', plugin_dir_path( __FILE__ ) );
define( 'SEO_ABILITIES_URL', plugin_dir_url( __FILE__ ) );
define( 'SEO_ABILITIES_BASENAME', plugin_basename( __FILE__ ) );

// Load Composer autoloader.
if ( file_exists( SEO_ABILITIES_PATH . 'vendor/autoload.php' ) ) {
	require_once SEO_ABILITIES_PATH . 'vendor/autoload.php';
}

/**
 * Display admin notice when WordPress Abilities API is not available.
 *
 * @return void
 */
function seo_abilities_missing_api_notice(): void {
	?>
	<div class="notice notice-error">
		<p>
			<strong><?php esc_html_e( 'Abilities API Extension for Rank Math SEO', 'wp-abilities-seo-extension' ); ?></strong>:
			<?php esc_html_e( 'This plugin requires the WordPress Abilities API to function. Please ensure you are running WordPress 6.9 or later with the Abilities API enabled.', 'wp-abilities-seo-extension' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Display admin notice when required SEO plugin is not active.
 *
 * @return void
 */
function seo_abilities_missing_seo_plugin_notice(): void {
	?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<strong><?php esc_html_e( 'Abilities API Extension for Rank Math SEO', 'wp-abilities-seo-extension' ); ?></strong>:
			<?php esc_html_e( 'No supported SEO plugin is active. Please install and activate Rank Math SEO for full functionality.', 'wp-abilities-seo-extension' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Initialize the plugin.
 *
 * @return void
 */
function seo_abilities_init(): void {
	// Check for Abilities API availability.
	if ( ! class_exists( 'WP_Abilities_Registry' ) ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\\seo_abilities_missing_api_notice' );
		return;
	}

	// Initialize the main plugin class.
	Plugin::get_instance();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\seo_abilities_init' );

/**
 * Plugin activation hook.
 * Sets default options on activation.
 *
 * @return void
 */
function seo_abilities_activate(): void {
	// Set default settings if they don't exist.
	if ( false === get_option( 'seo_abilities_settings' ) ) {
		add_option(
			'seo_abilities_settings',
			array(
				'post_types' => array( 'post', 'page' ),
				'bulk_limit' => 10,
			)
		);
	}
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\\seo_abilities_activate' );

/**
 * Plugin deactivation hook.
 *
 * @return void
 */
function seo_abilities_deactivate(): void {
	// Clean up transients if any.
	delete_transient( 'seo_abilities_provider_check' );
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\seo_abilities_deactivate' );
