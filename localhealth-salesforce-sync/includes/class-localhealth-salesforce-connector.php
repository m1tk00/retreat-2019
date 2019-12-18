<?php
/**
 * Plugin Name: myDevices Salesforce Connector.
 * Plugin URI: https://sprintiot.com
 * Description: This plugin contains the main class for the salesforce connection. Used by the rest of the plugins.
 * Version: 1.0
 *
 * @package WordPress
 */

if ( ! class_exists( 'Localhealth_Salesforce_Connector' ) ) {
	/**
	 * Class Localhealth_Salesforce_Connector
	 *
	 * The main class for making requests to SalesForce.
	 */
	class Localhealth_Salesforce_Connector {

		/**
		 * @var \bjsmasth\Salesforce\Authentication\PasswordAuthentication Makes the connection.
		 */
		private $_salesforce;

		/**
		 * @var \bjsmasth\Salesforce\CRUD The CURD object.
		 */
		private $_crud;

		/**
		 * Localhealth_Salesforce_Connector constructor.
		 *
		 * @throws \bjsmasth\Salesforce\Exception\SalesforceAuthentication Error.
		 */
		public function __construct( $token, $url ) {
			require_once( dirname( __FILE__ ) ) . '/../composer/vendor/autoload.php';

			$this->_crud = new \bjsmasth\Salesforce\CRUD( $token, $url );

		}

		public function view( $query ) {
			return $this->_crud->view( $query );
		}

		public function get_header() {

		}

		public function create_record( $object, $data ) {
			return $this->_crud->create( $object, $data );
		}

		public function create_user( $data ) {
			return $this->_crud->create( 'Account', $data );
		}
		public function create_contact( $data ) {
			return $this->_crud->create( 'Contact', $data );
		}
		public function create_lead( $data ) {
			return $this->_crud->create( 'Lead', $data );
		}
		public function create_note( $data ) {
			return $this->_crud->create( 'ContentNote', $data );
		}
		public function create_note_link( $data ) {
			return $this->_crud->create( 'ContentDocumentLink', $data );
		}
		public function update_lead( $object, $id, $data ) {
			return $this->_crud->update( $object, $id, $data );
		}
		public function create_reseller_user( $username, $email, $bpm = '' ) {
			$data = array(
				'Name'         => $username,
				// 'Primary_Contact_Email__c' => $email,
				// Record type for Reseller.
				'RecordTypeId' => '0120b0000003OoiAAE',
			);
			if ( ! empty( $bpm ) ) {
				// If BPM was setup.
				$data['OwnerId'] = $bpm;
			}

			return $this->create_user( $data );
		}
		public function get_query( $query ) {
			return $this->_crud->query( $query );
		}
		public function generate_user_table( $query ) {
			return $this->_crud->query( $query );
		}

		public function maybe_get_query( $query ) {

			if ( false === ( $results = get_transient( 'reseller_' . md5( $query ) ) ) || defined( 'RESELLER_IGNORE_CACHED' ) ) {
				// It wasn't there, so regenerate the data and save the transient
				$results = $this->get_query( $query );
				set_transient( 'reseller_' . md5( $query ), $results, 24 * HOUR_IN_SECONDS );
			}
			return $results;
		}

		public function delete( $object, $id ) {
			$this->_crud->delete( $object, $id );
		}
	}
}
