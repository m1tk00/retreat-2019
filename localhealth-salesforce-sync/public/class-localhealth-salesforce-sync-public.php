<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.webpigment.com
 * @since      1.0.0
 *
 * @package    Localhealth_Salesforce_Sync
 * @subpackage Localhealth_Salesforce_Sync/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Localhealth_Salesforce_Sync
 * @subpackage Localhealth_Salesforce_Sync/public
 * @author     webpigment <mitko@webpigment.com>
 */
class Localhealth_Salesforce_Sync_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Localhealth_Salesforce_Sync_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Localhealth_Salesforce_Sync_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/localhealth-salesforce-sync-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Localhealth_Salesforce_Sync_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Localhealth_Salesforce_Sync_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/localhealth-salesforce-sync-public.js', array( 'jquery' ), $this->version, false );

	}

	public function add_site_endpoint() {
		add_action(
			'rest_api_init',
			function () {
				register_rest_route(
					'wp/v2',
					'/bestrx/prescription_update',
					array(
						'methods'  => array( 'POST', 'GET' ),
						'callback' => array( $this, 'update_prescription' ),
					)
				);
				register_rest_route(
					'wp/v2',
					'/bestrx/patient_update',
					array(
						'methods'  => array( 'POST', 'GET' ),
						'callback' => array( $this, 'update_patient' ),
					)
				);
			}
		);
	}

	public function update_prescription( WP_REST_Request $request ) {
		$api    = new Localhealth_Salesforce_Sync_Connector();
		$params = $request->get_json_params();

		try {
			$id = $api->process_prescription( $params );
			return array(
				'status'  => 'Success',
				'Message' => null,
				'test'    => $id,
			);
		} catch ( Exception $e ) {
			if ( false === ( $special_prescription_update = get_transient( 'special_prescription_update' ) ) ) {
				$special_prescription_update = 'test';
				set_transient( 'special_prescription_update', $special_prescription_update, 2 * HOUR_IN_SECONDS );
				wp_mail( 'mitko.kockovski@gmail.com', 'BestRX Prescription record', print_r( $request->get_json_params(), true ) );
			}
			// wp_mail('mitko.kockovski@gmail.com','test issue', $e->getMessage() );
			return array(
				'status'  => 'Success',
				'Message' => $e->getMessage(),
			);
		}
	}

	public function update_patient( WP_REST_Request $request ) {
		$api    = new Localhealth_Salesforce_Sync_Connector();
		$params = $request->get_json_params();
		try {
			$response = $api->process_patient( $params );
			return array(
				'status'  => 'Success',
				'Message' => $response,
			);
		} catch ( Exception $e ) {
			if ( false === ( $special_patient_user = get_transient( 'special_patient_user' ) ) ) {
				$special_patient_user = 'test';
				set_transient( 'special_patient_user', $special_patient_user, 2 * HOUR_IN_SECONDS );
				wp_mail( 'mitko.kockovski@gmail.com', 'BestRX Patient record', print_r( $request->get_json_params(), true ) );
			}
			return array(
				'status'  => 'Success',
				'Message' => $e->getMessage(),
			);
		}
	}
}
