<?php
/**
 * Plugin Name: WPGraphQL
 * Plugin URI: https://github.com/wp-graphql/wp-graphql
 * GitHub Plugin URI: https://github.com/wp-graphql/wp-graphql
 * Description: GraphQL API for WordPress
 * Author: WPGraphQL
 * Author URI: http://www.wpgraphql.com
 * Version: 1.16.0
 * Text Domain: wp-graphql
 * Domain Path: /languages/
 * Requires at least: 5.0
 * Tested up to: 6.2
 * Requires PHP: 7.1
 * License: GPL-3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package  WPGraphQL
 * @category Core
 * @author   WPGraphQL
 * @version  1.16.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// If the codeception remote coverage file exists, require it.
// This file should only exist locally or when CI bootstraps the environment for testing
if ( file_exists( __DIR__ . '/c3.php' ) ) {
	require_once __DIR__ . '/c3.php';
}

// Whether to autoload the files or not.
// This must be defined here and not within the WPGraphQL.php because this constant
// determines whether to autoload classes or not
if ( ! defined( 'WPGRAPHQL_AUTOLOAD' ) ) {
	define( 'WPGRAPHQL_AUTOLOAD', true );
}

// Run this function when WPGraphQL is de-activated
register_deactivation_hook( __FILE__, 'graphql_deactivation_callback' );
register_activation_hook( __FILE__, 'graphql_activation_callback' );

// Bootstrap the plugin
if ( ! class_exists( 'WPGraphQL' ) ) {
	require_once __DIR__ . '/src/WPGraphQL.php';
}

/**
 * @return bool
 */
function graphql_can_load_plugin(): bool {

	/**
	 * WPGRAPHQL_AUTOLOAD can be set to "false" to prevent the autoloader from running.
	 * In most cases, this is not something that should be disabled, but some environments
	 * may bootstrap their dependencies in a global autoloader that will autoload files
	 * before we get to this point, and requiring the autoloader again can trigger fatal errors.
	 *
	 * The codeception tests are an example of an environment where adding the autoloader again causes issues
	 * so this is set to false for tests.
	 */
	// @phpstan-ignore-next-line: this is ignored as the constant could be defined in wp-config, prior to being defined above
	if ( defined( 'WPGRAPHQL_AUTOLOAD' ) && false === WPGRAPHQL_AUTOLOAD ) {

		// IF WPGRAPHQL_AUTOLOAD is defined as false,
		// but the WPGraphQL Class exists, we can assume the dependencies
		// are loaded from the parent project.
		if ( class_exists( '\WPGraphQL' ) ) {
			return true;
		}
	}

	// If the autoload file exists, load it
	if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
		// Autoload Required Classes.
		require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
		return true;
		// If the autoload file doesn't exist
		// manually load the individual files defined
		// in the composer.json
	}

	add_action(
		'admin_notices',
		static function () {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			printf(
				'<div class="notice notice-error">' .
				'<p>%s</p>' .
				'</div>',
				esc_html__( 'WPGraphQL appears to have been installed without it\'s dependencies. It will not work properly until dependencies are installed. This likely means you have cloned WPGraphQL from Github and need to run the command `composer install`.', 'wp-graphql' )
			);
		}
	);

	return false;
}

if ( ! function_exists( 'graphql_init' ) ) {
	/**
	 * Function that instantiates the plugins main class
	 *
	 * @return object|null
	 */
	function graphql_init() {

		// if the plugin can't be loaded, bail
		if ( false === graphql_can_load_plugin() ) {
			return null;
		}

		/**
		 * Return an instance of the action
		 */
		return \WPGraphQL::instance();
	}
}
graphql_init();

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once plugin_dir_path( __FILE__ ) . 'cli/wp-cli.php';
}

/**
 * Initialize the plugin tracker
 *
 * @return void
 */
function graphql_init_appsero_telemetry() {
	// If the class doesn't exist, or code is being scanned by PHPSTAN, move on.
	if ( ! class_exists( 'Appsero\Client' ) || defined( 'PHPSTAN' ) ) {
		return;
	}

	$client   = new Appsero\Client( 'cd0d1172-95a0-4460-a36a-2c303807c9ef', 'WPGraphQL', __FILE__ );
	$insights = $client->insights();

	// If the Appsero client has the add_plugin_data method, use it
	if ( method_exists( $insights, 'add_plugin_data' ) ) {
		// @phpstan-ignore-next-line
		$insights->add_plugin_data();
	}

	// @phpstan-ignore-next-line
	$insights->init();
}

graphql_init_appsero_telemetry();
