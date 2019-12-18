<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.webpigment.com
 * @since      1.0.0
 *
 * @package    Localhealth_Salesforce_Sync
 * @subpackage Localhealth_Salesforce_Sync/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Localhealth_Salesforce_Sync
 * @subpackage Localhealth_Salesforce_Sync/includes
 * @author     webpigment <mitko@webpigment.com>
 */
class Localhealth_Salesforce_Sync_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'localhealth-salesforce-sync',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
