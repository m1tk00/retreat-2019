<?php

namespace bjsmasth\Salesforce;

use AutomateWoo\Exception;
use GuzzleHttp\Client;

class CRUD {

	protected $instance_url;
	protected $access_token;

	public function __construct( $token = '', $instance_url = '' ) {
		if ( empty( $token ) && empty( $instance_url ) ) {
			$this->prepare_sf_data();
		} else {
			$this->instance_url = $instance_url;
			$this->access_token = $token;
		}
	}
	protected function prepare_sf_data() {
		$salesforce_data = get_option( 'salesforce_data' );
			$options     = [
				'grant_type'    => 'password',
				'client_id'     => '3MVG9oNqAtcJCF.GNHANRKVYTPNr1MjwokBIP0aMMDRR5XTRSRXX4mKUxQwIURY_29OPfXYYqH6zfOOqgbvmB',
				'client_secret' => '7B28487CF87494368EC34FBF77E9BCB0448FB7578066D646F06D70902BE00AC9',
				'username'      => $salesforce_data['sf_username'],
				'password'      => $salesforce_data['sf_password'] . $salesforce_data['sf_token'], // password+security token.
			];

			$this->_salesforce = new Authentication\PasswordAuthentication( $options );
			$this->_salesforce->authenticate();
			$access_token = $this->_salesforce->getAccessToken();
			$instance_url = $this->_salesforce->getInstanceUrl();

			$this->_salesforce = new Authentication\PasswordAuthentication( $options );
			$this->_salesforce->setEndpoint( $instance_url . '/' );
			$this->_salesforce->authenticate();

			$token        = $this->_salesforce->getAccessToken();
			$instance_url = $this->_salesforce->getInstanceUrl();
			update_option( 'sf_instance_url', $instance_url );
			update_option( 'sf_token', $token );
			$this->instance_url = $instance_url;
			$this->access_token = $token;
	}
	public function query( $query ) {
		try {
			$url = "$this->instance_url/services/data/v39.0/query";

			$client  = new Client();
			$request = $client->request(
				'GET',
				$url,
				[
					'headers' => [
						'Authorization' => "OAuth $this->access_token",
					],
					'query'   => [
						'q' => $query,
					],
				]
			);
			return json_decode( $request->getBody(), true );
		} catch ( \Exception $e ) {
			if ( false !== strpos( $e->getMessage(), '401 Unauthorized') ) {
				$this->prepare_sf_data();
				$this->query( $query );
			} else {
				throw $e;
			}
		}
	}

	public function view( $endpoint ) {
		try {
			$url     = "$this->instance_url{$endpoint}";
			$client  = new Client();
			$request = $client->request(
				'GET',
				$url,
				[
					'headers' => [
						'Authorization' => "OAuth $this->access_token",
					],
				]
			);

			return json_decode( $request->getBody(), true );
		} catch ( \Exception $e ) {
			if ( false !== strpos( $e->getMessage(), '401 Unauthorized') ) {
				$this->prepare_sf_data();
				$this->view( $endpoint );
			} else {
				throw $e;
			}
		}
	}

	public function create( $object, array $data ) {
		try {

			$url = "$this->instance_url/services/data/v39.0/sobjects/$object/";

			$client = new Client();

			$request = $client->request(
				'POST',
				$url,
				[
					'headers' => [
						'Authorization' => "OAuth $this->access_token",
						'Content-type'  => 'application/json',
					],
					'json'    => $data,
				]
			);
			$status  = $request->getStatusCode();

			if ( $status != 201 ) {
				die( "Error: call to URL $url failed with status $status, response: " . $request->getReasonPhrase() );
			}

			$response = json_decode( $request->getBody(), true );
			$id       = $response['id'];

			return $id;
		} catch ( \Exception $e ) {
			if ( false !== strpos( $e->getMessage(), '401 Unauthorized') ) {
				$this->prepare_sf_data();
				$this->create( $object, $data );
			} else {
				throw $e;
			}
		}

	}

	public function update( $object, $id, array $data ) {
		try {

			$url = "$this->instance_url/services/data/v39.0/sobjects/$object/$id";


			$client = new Client();

			$request = $client->request(
				'PATCH',
				$url,
				[
					'headers' => [
						'Authorization' => "OAuth $this->access_token",
						'Content-type'  => 'application/json',
					],
					'json'    => $data,
				]
			);

			$status = $request->getStatusCode();

			if ( $status != 204 ) {
				die( "Error: call to URL $url failed with status $status, response: " . $request->getReasonPhrase() );
			}

			return $status;
		} catch ( \Exception $e ) {
			if ( false !== strpos( $e->getMessage(), '401 Unauthorized') ) {
				$this->prepare_sf_data();
				$this->update( $object, $id, $data );
			} else {
				throw $e;
			}
		}
	}

	public function delete( $object, $id ) {
		try {

			$url = "$this->instance_url/services/data/v39.0/sobjects/$object/$id";

			$client  = new Client();
			$request = $client->request(
				'DELETE',
				$url,
				[
					'headers' => [
						'Authorization' => "OAuth $this->access_token",
					],
				]
			);

			$status = $request->getStatusCode();

			if ( $status != 204 ) {
				die( "Error: call to URL $url failed with status $status, response: " . $request->getReasonPhrase() );
			}

			return true;
		} catch ( \Exception $e ) {
			if ( false !== strpos( $e->getMessage(), '401 Unauthorized') ) {
				$this->prepare_sf_data();
				$this->delete( $object, $id );
			} else {
				throw $e;
			}
		}
	}
}
