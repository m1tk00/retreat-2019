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

	public function sync_sf_data() {
		global $wpdb;
		$enc = new Localhealth_Salesforce_Encryptor();
		$results = $wpdb->get_results( 'SELECT * FROM wp_localhealt_info order by id asc', ARRAY_A );
		if( isset( $_GET['mitko_test_site_again_please2232'])) {
			echo 'SELECT * FROM wp_localhealt_info order by id asc';
			print_r( $results );
		}
		foreach ( $results as $entry ) {
			$dec_key = $this->get_ecnryption_key( $entry['api_key'] );
			$remove_id = $entry['ID'];
			$decrypted = json_decode( $enc->decrypt( base64_decode( $entry['data'] ), $dec_key ), true );
			$api    = new Localhealth_Salesforce_Sync_Connector();
			try {
				$id = $api->process_prescription( $decrypted );
				$this->send_slack_notification(0, $decrypted['rx_number'] );
			} catch ( Exception $e ) {
			}
			$wpdb->delete( 'wp_localhealt_info', array( 'ID' => $remove_id ) );
		}
		if ( isset( $_GET['mitko_test_site_again_please2232'])) {
			exit();
		}
	}

	public function add_site_endpoint() {
		add_action(
			'rest_api_init',
			function () {
				register_rest_route(
					'wp/v2',
					'/bestrx/prescription_update',
					array(
						'methods'  => array( 'POST' ),
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
		$params = $request->get_json_params();
		$enc = new Localhealth_Salesforce_Encryptor();
		$key = $this->get_ecnryption_key( $params['APIKey'] );
		if( ! $key ) {
			return array(
				'status'  => 'Success',
				'Message' => 'Invalid API key.' . $params['APIKey'],
			);
		}
		$encrypted = $enc->encrypt( json_encode( $params ), $key );

		global $wpdb;

		$insert_id = $wpdb->insert(
			'wp_localhealt_info',
			array(
				'api_key' => $params['APIKey'],
				'data' => base64_encode( $encrypted ),
			)
		);
		$last_insert = $wpdb->insert_id;
		return array(
			'status'  => 'Success',
			'Message' => null,
			'test'    => $last_insert,
		);

		/**$entry = $wpdb->get_row( 'SELECT * FROM wp_localhealt_info WHERE id != ' . $last_insert . ' order by id asc limit 1', ARRAY_A );

		if ( empty( $entry) ) {

		}
		$dec_key = $this->get_ecnryption_key( $entry['api_key'] );
		$remove_id = $entry['ID'];
		$decrypted = json_decode( $enc->decrypt( base64_decode( $entry['data'] ), $dec_key ), true );
		$api    = new Localhealth_Salesforce_Sync_Connector();
		$this->send_slack_notification( 0, 'SELECT * FROM wp_localhealt_info WHERE id != ' . $last_insert . ' order by id asc limit 1,1 and remove' . $remove_id  );
		// $this->send_slack_notification( 0, $decrypted['rx_number'] );
		try {

			$id = $api->process_prescription( $decrypted );

			if ( $wpdb->get_col( 'SELECT ID FROM wp_localhealt_info WHERE ID = ' . $remove_id ) ) {
				$wpdb->delete( 'wp_localhealt_info', array( 'ID' => $remove_id ) );
				return array(
					'status'  => 'Success',
					'Message' => null,
					'test'    => $id,
				);
			} else {
				$entry_1 = $wpdb->get_row( 'SELECT * FROM wp_localhealt_info WHERE id != ' . $last_insert . ' order by id asc limit 1', ARRAY_A );
				if ( empty( $entry_1 ) ) {
					return array(
						'status'  => 'Success',
						'Message' => null,
						'test'    => $last_insert,
					);
				}
				$dec_key = $this->get_ecnryption_key( $entry_1['api_key'] );
				$remove_id = $entry_1['ID'];
				$decrypted = json_decode( $enc->decrypt( base64_decode( $entry_1['data'] ), $dec_key ), true );
				$id = $api->process_prescription( $decrypted );
				$wpdb->delete( 'wp_localhealt_info', array( 'ID' => $remove_id ) );
				return array(
					'status'  => 'Success',
					'Message' => null,
					'test'    => $id,
				);
			}
		} catch ( Exception $e ) {
			$wpdb->delete( 'wp_localhealt_info', array( 'ID' => $remove_id ) );
			// $this->send_slack_notification( 0 );
			if ( false === ( $special_prescription_update = get_transient( 'special_prescription_update' ) ) ) {
				$special_prescription_update = 'test';
				set_transient( 'special_prescription_update', $special_prescription_update, 2 * HOUR_IN_SECONDS );
				wp_mail( 'mitko.kockovski@gmail.com', 'BestRX Prescription record', print_r( $request->get_json_params(), true ) );
			}
			return array(
				'status'  => 'Success',
				'Message' => $e->getMessage(),
			);
		}*/
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

	protected function send_slack_notification( $type = 0, $pharmacy = '' ){
		if ( $type ) {
			$json_data = strtotime('now') . "\n";
			$json_data .= 'Request ' . $pharmacy .  "\n";
			$json_data .= '=======' . "\n";
		} else {
			$json_data = strtotime('now') . "\n";
			$json_data .= 'Duplicate waiting ' . $pharmacy .  "\n";
			$json_data .= '=======' . "\n";
		}
		// wp_remote_post( 'https://hooks.slack.com/services/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', array(
		// 	'body' => json_encode( array( 'text' => $json_data ) ),
		// ) );
	}

	public function get_ecnryption_key( $api_key ) {
		$salesforce_data = get_option( 'salesforce_data' );

		foreach ( $salesforce_data['sf_stores'] as $key => $store ) {
			if ( $api_key === $store[1] ) {
				return $store[2];
			}
		}
		return false;
	}
}
/*
 * CREATE TABLE `wp_localhealt_info` (
  `ID` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `api_key` text NOT NULL,
  `data` longtext NOT NULL
);
 */
