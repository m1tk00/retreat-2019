<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.webpigment.com
 * @since             1.0.0
 * @package           Localhealth_Salesforce_Sync
 *
 * @wordpress-plugin
 * Plugin Name:       localhealth salesforce sync
 * Plugin URI:        https://localhealth.io
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            webpigment
 * Author URI:        https://www.webpigment.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       localhealth-salesforce-sync
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'LOCALHEALTH_SALESFORCE_SYNC_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-localhealth-salesforce-sync-activator.php
 */
function activate_localhealth_salesforce_sync() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-localhealth-salesforce-sync-activator.php';
	Localhealth_Salesforce_Sync_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-localhealth-salesforce-sync-deactivator.php
 */
function deactivate_localhealth_salesforce_sync() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-localhealth-salesforce-sync-deactivator.php';
	Localhealth_Salesforce_Sync_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_localhealth_salesforce_sync' );
register_deactivation_hook( __FILE__, 'deactivate_localhealth_salesforce_sync' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-localhealth-salesforce-sync.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_localhealth_salesforce_sync() {

	$plugin = new Localhealth_Salesforce_Sync();
	$plugin->run();

}
run_localhealth_salesforce_sync();

function my_cron_schedules( $schedules ) {
	if ( ! isset( $schedules['1min'] ) ) {
		$schedules['1min'] = array(
			'interval' => 60,
			'display'  => __( 'Once every 1 minute' ),
		);
	}
	return $schedules;
}
add_filter( 'cron_schedules', 'my_cron_schedules' );
if( ! wp_next_scheduled('sync_sf_data_v2_v2' ) ) {
	wp_schedule_event( time(), '1min', 'sync_sf_data_v2_v2' );
}
wp_clear_scheduled_hook( 'sync_sf_data_v2' );
// wp_clear_scheduled_hook( 'sync_sf_data_v2_v2' );
add_action( 'sync_sf_data_v2_v2', 'localhealth_sync_sf_data', 10, 0 );

function localhealth_sync_sf_data() {
	// wp_mail('mitko.kockovski@gmail.com','testing cron', 'cron works');
	global $wpdb;
	$enc = new Localhealth_Salesforce_Encryptor();
	$results = $wpdb->get_results( 'SELECT * FROM wp_localhealt_info order by id asc', ARRAY_A );
	foreach ( $results as $entry ) {
		$dec_key = localhealt_get_encryption_key( $entry['api_key'] );
		$remove_id = $entry['ID'];
		$decrypted = json_decode( $enc->decrypt( base64_decode( $entry['data'] ), $dec_key ), true );
		$api    = new Localhealth_Salesforce_Sync_Connector();
		try {
			$id = $api->process_prescription( $decrypted );
		} catch ( Exception $e ) {
		}
		$wpdb->delete( 'wp_localhealt_info', array( 'ID' => $remove_id ) );
	}
}

function localhealt_get_encryption_key( $api_key ) {
	$salesforce_data = get_option( 'salesforce_data' );

	foreach ( $salesforce_data['sf_stores'] as $key => $store ) {
		if ( $api_key === $store[1] ) {
			return $store[2];
		}
	}
	return false;
}