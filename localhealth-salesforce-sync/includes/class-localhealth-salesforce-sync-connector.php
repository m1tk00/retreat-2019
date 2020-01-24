<?php
class Localhealth_Salesforce_Sync_Connector {

	var $sf;

	/**
	 * Prepare SalesForce API object.
	 * @throws \bjsmasth\Salesforce\Exception\SalesforceAuthentication
	 */
	protected function prepare_sf() {
		if ( empty( $this->sf ) ) {
			$instance_url = get_option( 'sf_instance_url', '' );
			$token        = get_option( 'sf_token', '' );
			$this->sf     = new Localhealth_Salesforce_Connector( $token, $instance_url );
		}
	}

	/**
	 * Deprecated function, used to pull data from BestRX for each patient.
	 *
	 * @param array $data
	 *
	 * @return array|mixed|object
	 */
	protected function make_requset_to_bestrx( $data = array() ) {
		$url = 'https://webservice.bcsbestrx.com:5000/BCSWebService/WebRefillService/GetPatientProfile';
		if ( empty( $data ) ) {
			$data = array(
				'PharmacyNumber' => $data['PharmacyNumber'],
				'APIKey'         => $data['APIKey'],
				'LastName'       => $data['last_name'],
				'DOB'            => date( 'c', strtotime( $data['date_of_birth'] ) ),
				'RxNumber'       => 8880708,

			);
		}
		$response = wp_remote_post(
			$url,
			array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'        => json_encode( $data ),
				'cookies'     => array(),
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo "Something went wrong: $error_message";
		} else {
			return json_decode( $response['body'], true );
		}
	}

	/**
	 * Check if contact object exist and if it doesn't create new one. Called when patient is updated.
	 * @param $api_data
	 *
	 * @return string The contact ID.
	 */
	protected function prepare_contact( $api_data ) {
		$dob   = date( 'Y-m-d', strtotime( $api_data['dob'] ) );
		$fname = strtolower( $api_data['first_name'] );
		$lname = strtolower( $api_data['last_name'] );
		$query = "SELECT Id FROM Contact WHERE Birthdate = {$dob} and FirstName = '{$fname}' and LastName = '{$lname}'";
		// $contact_id = get_option( 'contact_' . md5( $query ), 0 );
		if ( isset( $_GET['test5123'] ) ) {
			echo $query;
		}
		$contact_id = 0;
		$contact    = array(
			'Birthdate'                 => $api_data['dob'],
			'Email'                     => $api_data['patient_email'],
			'Family_Email__c'           => $api_data['family_email'],
			'Family_Remark__c'          => $api_data['family_remark'],
			'Gender__c'                 => $api_data['gender'],
			'Phone'                     => $api_data['home_phone'],
			'Is_Active__c'              => $api_data['is_active'],
			'Is_Deceased__c'            => $api_data['is_deceased'],
			'Is_Pet__c'                 => $api_data['is_pet'],
			'Language__c'               => $api_data['language'],
			'MobilePhone'               => $api_data['cell_phone'],
			'Nursing_Home__c'           => $api_data['nursing_home'],
			'Patient_Group__c'          => $api_data['patient_group'],
			'Patient_Remark__c'         => $api_data['patient_remark'],
			'Patient_Short_Remark__c'   => $api_data['patient_short_remark'],
			'Pharmacy_Number__c'        => $api_data['PharmacyNumber'],
			'Social_Security_Number__c' => $api_data['social_security_number'],
			'Transaction_Action__c'     => $api_data['transaction_action'],
			'Transaction_Date__c'       => $api_data['transaction_date'],
			'Transaction_Time__c'       => $api_data['transaction_time'],
			'Unique_Patient_ID__c'      => $api_data['unique_patient_id'],
			'LastName'                  => $api_data['last_name'],
			'MiddleName'                => $api_data['middle_name'],
			'FirstName'                 => $api_data['first_name'],
			'Salutation'                => $api_data['name_prefix'],
			'Suffix'                    => $api_data['name_suffix'],
			'MailingStreet'             => $api_data['address1'],
			'Mailing_Apartment_Unit__c' => $api_data['address2'],
			'MailingCity'               => $api_data['city'],
			'State__c'                  => $api_data['state'],
			'MailingState'              => $api_data['state'],
			'MailingPostalCode'         => $api_data['zipcode'],
			'Work_Phone__c'             => $api_data['work_phone'],
		);
		if ( ! $contact_id ) {
			$results = $this->sf->get_query( $query );
			if ( $results['totalSize'] ) {
				$contact_id = $results['records'][0]['Id'];
				$this->sf->update_lead( 'Contact', $contact_id, $contact );
			} else {
				$contact_id = $this->sf->create_record( 'Contact', $contact );
			}
			// update_option( 'contact_' . md5( $query ), $contact_id, 0 );
		}
		return $contact_id;
	}

	/**
	 * Check if insurance object exist and if it doesn't create new one. Called when patient is updated.
	 * @param string $contact_id The contact ID.
	 * @param array  $api_data   The input data.
	 */
	protected function prepare_insurance( $contact_id, $api_data ) {
		$object = 'InsurancePlans__c';
		foreach ( $api_data['insurance_plans'] as $key => $data ) {
			$query = "SELECT Id FROM {$object} WHERE Contact__c = '{$contact_id}' and Insurance_BIN__c = '{$data['ins_bin']}' ";
			// $plan  = get_option( 'insurance_' . md5( $query ), 0 );
			$plan = 0;
			if ( ! $plan ) {
				$results = $this->sf->get_query( $query );
				if ( $results['totalSize'] ) {
					$plan = $results['records'][0]['Id'];
				} else {
					$plan_data = array(
						'Insurance_BIN__c'             => $data['ins_bin'],
						'Insurance_Cardholder_First_Name__c' => $data['ins_cardholder_first_name'],
						'Insurance_Cardholder_Last_Name__c' => $data['ins_cardholder_last_name'],
						'Insurance_Cardholder_ID__c'   => $data['ins_cardholder_id'],
						'Insurance_Code__c'            => $data['ins_code'],
						'Insurance_Group__c'           => $data['ins_group'],
						'Insurance_is_Primary__c'      => $data['ins_is_primary'],
						'Insurance_Name__c'            => $data['ins_name'],
						'Insurance_PCN__c'             => $data['ins_pcn'],
						'Name'                         => $data['ins_name'],
						'Insurance_Sequence_Number__c' => $data['ins_seq_no'],
						// 'Insurance_Person_Code__c' => $data[''],
						// 'Insurance_Relation_Code__c' => $data[''],
						'Contact__c'                   => $contact_id,
					);
					$plan      = $this->sf->create_record( $object, $plan_data );
				}
				// update_option( 'insurance_' . md5( $query ), $plan, 0 );
			}
		}
	}

	/**
	 * Updates SalesForce data when BestRX patient is updated.
	 * @param array $api_data The patient data.
	 *
	 * @return string
	 * @throws \bjsmasth\Salesforce\Exception\SalesforceAuthentication
	 */
	public function process_patient( $api_data, $time = 0 ) {

		$store_data = $this->validate_store( $api_data );
		if ( 0 === $store_data ) {
			return '';
		}
		$this->prepare_sf();
		try {
			$contact_id = $this->prepare_contact( $api_data );
			$this->prepare_insurance( $contact_id, $api_data );
			return $contact_id;
		} catch ( Exception $e ) {
			if ( false !== strpos( $e->getMessage(), 'entity is deleted' ) && $time === 0 ) {
				$dob   = date( 'Y-m-d', strtotime( $api_data['dob'] ) );
				$fname = strtolower( $api_data['first_name'] );
				$lname = strtolower( $api_data['last_name'] );
				$query = "SELECT Id FROM Contact WHERE Birthdate = {$dob} and FirstName = '{$fname}' and LastName = '{$lname}'";
				// update_option( 'contact_' . md5( $query ), 0, 0 );
				return $this->process_patient( $api_data, 1 );
			}
			return $e->getMessage();
		}

	}

	/**
	 * Update patient prescriptions. Called when BestRX update prescription happens.
	 * @param array $api_data The prescription data.
	 *
	 * @return string
	 * @throws \bjsmasth\Salesforce\Exception\SalesforceAuthentication
	 */
	public function process_prescription( $api_data, $time = 0 ) {

		$store_data = $this->validate_store( $api_data );
		if ( 0 === $store_data ) {
			return '';
		}
		try {
			$this->prepare_sf();
			$contact_id      = $this->get_patient_contact( $api_data );
			$prescription_id = $this->insert_prescription( $api_data, $contact_id );
			if ( $prescription_id ) {
				$this->insert_payers( $prescription_id, $api_data );
				$this->insert_dosage( $prescription_id, $api_data );
				$this->insert_diagnosis( $prescription_id, $api_data );
			}
			return $prescription_id;
		} catch ( Exception $e ) {
			if ( false !== strpos( $e->getMessage(), 'entity is deleted' ) && $time === 0 ) {
				$dob   = date( 'Y-m-d', strtotime( $api_data['patient_dob'] ) );
				$fname = strtolower( $api_data['patient_first_name'] );
				$lname = strtolower( $api_data['patient_last_name'] );
				$query = "SELECT Id FROM Contact WHERE Birthdate = {$dob} and FirstName = '{$fname}' and LastName = '{$lname}'";
				// update_option( 'contact_' . md5( $query ), 0, 0 );
				return $this->process_prescription( $api_data, 1 );
			}
			return $e->getMessage();
		}
	}

	/**
	 * Create prescription.
	 * @param array $api_data The input data.
	 *
	 * @return string The prescription ID.
	 */
	protected function insert_prescription( $api_data, $contact_id ) {
		$sf_data      = array(
			'API_Key__c'                       => $api_data['APIKey'],
			'Bill_Status__c'                   => $api_data['bill_status'],
			'Bill_Status_Text__c'              => $api_data['bill_status_text'],
			'Billed__c'                        => $api_data['billed'],
			'Cost__c'                          => $api_data['cost'],
			'Date_Rx_Written__c'               => $api_data['date_rx_written'],
			'Days_Supply__c'                   => $api_data['days_supply'],
			'Delivered__c'                     => $api_data['delivered'],
			'Dispense_as_Written__c'           => $api_data['dispense_as_written'],
			'Doctor_Address_1__c'              => $api_data['doctor_address1'],
			'Doctor_Address_2__c'              => $api_data['doctor_address2'],
			'Doctor_City__c'                   => $api_data['doctor_city'],
			'Doctor_DEA__c'                    => $api_data['doctor_dea'],
			'Doctor_Email__c'                  => $api_data['doctor_email'],
			'Doctor_Fax__c'                    => $api_data['doctor_fax'],
			'Doctor_First_Name__c'             => $api_data['doctor_first_name'],
			'Doctor_ID__c'                     => $api_data['doctor_id'],
			'Doctor_Last_Name__c'              => $api_data['doctor_last_name'],
			'Doctor_Middle_Name__c'            => $api_data['doctor_middle_name'],
			'Doctor_NPI__c'                    => $api_data['doctor_npi'],
			'Doctor_Phone__c'                  => $api_data['doctor_phone'],
			'Doctor_Prefix__c'                 => $api_data['doctor_prefix'],
			'Doctor_Remark__c'                 => $api_data['doctor_remark'],
			'Doctor_State__c'                  => $api_data['doctor_state'],
			'Doctor_Suffix__c'                 => $api_data['doctor_suffix'],
			'Doctor_Zip__c'                    => $api_data['doctor_zip'],
			'Drug_Back_Imprint__c'             => $api_data['drug_back_imprint'],
			'Drug_Brand_Name__c'               => $api_data['drug_brand_name'],
			'Drug_Color__c'                    => $api_data['drug_color'],
			'Drug_Front_Imprint__c'            => $api_data['drug_front_imprint'],
			'Drug_GPI__c'                      => $api_data['drug_gpi'],
			'Drug_is_Generic__c'               => $api_data['drug_is_generic'],
			'Drug_Miscellaneous_Info__c'       => $api_data['drug_misc_info'],
			'Drug_Name__c'                     => $api_data['drug_name'],
			'Drug_NDC__c'                      => $api_data['drug_ndc'],
			'Drug_NDC_10_Digit__c'             => $api_data['drug_ndc_10digit'],
			'Drug_NDC_10_Digit_Formatted__c'   => $api_data['drug_ndc_10digit_formatted'],
			'Drug_Package_Size__c'             => $api_data['drug_package_size'],
			'Drug_Schedule__c'                 => $api_data['drug_schedule'],
			'Drug_Shape__c'                    => $api_data['drug_shape'],
			'Drug_Strength__c'                 => $api_data['drug_strength'],
			'Drug_Therapeutic_Class__c'        => $api_data['drug_therapeutic_class'],
			'eRx_Message_ID__c'                => $api_data['erx_message_id'],
			'eRx_Pharmacy_Remark__c'           => $api_data['erx_pharmacy_remark'],
			'eRx_Prescriber_Order_Number__c'   => $api_data['erx_prescriber_order_no'],
			'eRx_Prescriber_Remark__c'         => $api_data['erx_prescriber_remark'],
			'Family_Email__c'                  => $api_data['family_email'],
			'Family_Remark__c'                 => $api_data['patient_short_remark'],
			// 'Field_39__c'                      => $api_data[''],
			'Fill_Date__c'                     => $api_data['fill_date'],
			'Fill_Number__c'                   => $api_data['fill_number'],
			'Fill_Time__c'                     => $api_data['fill_time'],
			'Filled_by__c'                     => $api_data['filled_by'],
			'Horizon_Graveyard_Code__c'        => $api_data['horizon_graveyard_code'],
			'Inactivate_Rx_Remark__c'          => $api_data['inactivate_rx_remark'],
			'is_340b__c'                       => $api_data['is340b'],
			'Is_Deactivated__c'                => $api_data['is_deactivated'],
			'Item_UPC_Code__c'                 => $api_data['item_upc_code'],
			'Lot_Expiration_Date__c'           => $api_data['lot_exp_date'],
			'Lot_Number__c'                    => $api_data['lot_number'],
			'Margin__c'                        => $api_data['margin'],
			'Margin_Percent__c'                => $api_data['margin_percent'],
			'Next_Refill_Date__c'              => $api_data['next_refill_date'],
			'Other_Coverage_Code__c'           => $api_data['other_coverage_code'],
			'Patient_Address_1__c'             => $api_data['patient_address1'],
			'Patient_Address_2__c'             => $api_data['patient_address2'],
			'Patient_Amount_Due__c'            => $api_data['patient_amount_due'],
			'Patient_Cell_Phone__c'            => $api_data['patient_cell_phone'],
			'Patient_City__c'                  => $api_data['patient_city'],
			'Patient_Date_of_Birth__c'         => $api_data['patient_dob'],
			'Patient_Email__c'                 => $api_data['patient_email'],
			'Patient_Gender__c'                => $api_data['patient_gender'],
			'Patient_Group__c'                 => $api_data['patient_group'],
			'Patient_Home_Phone__c'            => $api_data['patient_home_phone'],
			'Patient_ID__c'                    => $api_data['patient_id'],
			'Patient_First_Name__c'            => $api_data['patient_first_name'],
			'Patient_Last_Name__c'             => $api_data['patient_last_name'],
			'Patient_Middle_Name__c'           => $api_data['patient_middle_name'],
			'Patient_Prefix__c'                => $api_data['patient_prefix'],
			'Patient_Remark__c'                => $api_data['patient_remark'],
			'Patient_Ship_to_Address_1__c'     => $api_data['patient_shipto_address1'],
			'Patient_Ship_to_Address_2__c'     => $api_data['patient_shipto_address2'],
			'Patient_Ship_to_Address_Phone__c' => $api_data['patient_shipto_phone'],
			'Patient_Ship_to_City__c'          => $api_data['patient_shipto_city'],
			'Patient_Ship_to_First_Name__c'    => $api_data['patient_shipto_first_name'],
			'Patient_Ship_to_Last_Name__c'     => $api_data['patient_shipto_last_name'],
			'Patient_Ship_to_Organization__c'  => $api_data['patient_shipto_organization'],
			'Patient_Ship_to_State__c'         => $api_data['patient_shipto_state'],
			'Patient_Ship_to_Zip__c'           => $api_data['patient_shipto_zip'],
			'Patient_Short_Remark__c'          => $api_data['patient_short_remark'],
			'Patient_State__c'                 => $api_data['patient_state'],
			'Patient_Suffix__c'                => $api_data['patient_suffix'],
			'Patient_Work_Phone__c'            => $api_data['patient_work_phone'],
			'Patient_Zip__c'                   => $api_data['patient_zip'],
			'Pharmacy_Number__c'               => $api_data['PharmacyNumber'],
			'Picked_Up__c'                     => $api_data['picked_up'],
			'Prescribed_Drug__c'               => $api_data['prescribed_drug'],
			// 'Name' => $api_data[''],
			'Quantity_Filled__c'               => $api_data['qty_filled'],
			'Quantity_Ordered__c'              => $api_data['qty_ordered'],
			'Refill_Expiration_Date__c'        => $api_data['refill_exp_date'],
			'Refill_Number__c'                 => $api_data['refill_number'],
			'Refills_Authorized__c'            => $api_data['refills_authorized'],
			'Refills_Remaining__c'             => $api_data['refills_remaining'],
			'Retail__c'                        => $api_data['retail'],
			'RPH_Initials__c'                  => $api_data['rph_initials'],
			'Rx_Number__c'                     => $api_data['rx_number'],
			'Rx_Remark__c'                     => $api_data['rx_remark'],
			'Rx_Serial_Number__c'              => $api_data['rx_serial_number'],
			'Rx_Source__c'                     => $api_data['rx_source'],
			'Shipping_Provider__c'             => $api_data['shipping_provider'],
			'Shipping_Tracking_Number__c'      => $api_data['shipping_tracking_number'],
			'Signature__c'                     => $api_data['signature'],
			'Submission_Clarification_Code__c' => $api_data['submission_clar_code'],
			'Transaction_Date__c'              => $api_data['transaction_date'],
			'Tech_Initials__c'                 => $api_data['tech_initials'],
			'Total_Paid__c'                    => $api_data['total_paid'],
			'Transaction_ID__c'                => $api_data['transaction_id'],
			'Transaction_Time__c'              => $api_data['transaction_time'],
			'Transferred_Out__c'               => $api_data['transferred_out'],
			'Workflow_Status__c'               => $api_data['workflow_status'],
			'Workflow_Status_Text__c'          => $api_data['workflow_status_text'],
			'Written_Date_Remark__c'           => $api_data['written_date_remark'],
			'Patient_Ship_to_Care_Of__c'       => $api_data['patient_shipto_careof'],
			'Due_From_Insurance__c'            => $api_data['due_from_insurance'],
			'Patient__c'                       => $contact_id,
		);
		$query_doctor = "SELECT Id FROM Contact WHERE RecordTypeId = '012f2000001262GAAQ' and Prescriber_NPI__c = '{$api_data['doctor_npi']}'";
		$doctor       = $this->sf->get_query( $query_doctor );
		if ( ! $doctor['totalSize'] ) {
			$query_doctor = "SELECT Id FROM Contact WHERE RecordTypeId = '012f2000001262GAAQ' and FirstName = '{$api_data['doctor_first_name']}' and LastName = '{$api_data['doctor_last_name']}'";
			$doctor       = $this->sf->get_query( $query_doctor );
			if ( ! $doctor['totalSize'] ) {
				$prescriber_id = $this->sf->create_record(
					'Contact',
					array(
						'RecordTypeId'         => '012f2000001262GAAQ',
						'FirstName'            => $api_data['doctor_first_name'],
						'LastName'             => $api_data['doctor_last_name'],
						'Prescriber_NPI__c'    => $api_data['doctor_npi'],
						'MailingAddress'       => $api_data['doctor_address1'],
						'Mailing_Address_2__c' => $api_data['doctor_address2'],
						'MailingCity'          => $api_data['doctor_city'],
						'MailingState'         => $api_data['doctor_state'],
						'State__c'             => $api_data['doctor_state'],
						'Mailing_Zip__c'       => $api_data['doctor_zip'],
						'Email'                => $api_data['doctor_email'],
						'Fax'                  => $api_data['doctor_fax'],
						'MiddleName'           => $api_data['doctor_middle_name'],
						'MobilePhone'          => $api_data['doctor_phone'],
						'Title'                => $api_data['doctor_remark'],
						'Suffix'               => $api_data['doctor_suffix'],
					)
				);
			} else {
				$prescriber_id = $doctor['records'][0]['Id'];
				$this->sf->update_lead( 'Contact', $prescriber_id, array( 'Prescriber_NPI__c' => $api_data['doctor_npi'] , 'State__c' => $api_data['doctor_state'] ) );
			}
		} else {
			$prescriber_id = $doctor['records'][0]['Id'];
		}
		$sf_data['Prescriber__c'] = $prescriber_id;
		$query                    = "SELECT Id, Transaction_Time__c, Transaction_Date__c FROM Prescription__c WHERE Patient__c = '{$contact_id}' and Rx_Number__c = '{$api_data['rx_number']}' and Fill_Number__c = '{$api_data['fill_number']}'";
		$prescription_id          = 0;
		if ( $prescription_id ) {
			$this->sf->update_lead( 'Prescription__c', $prescription_id, $sf_data );
		} else {
			$results  = $this->sf->get_query( $query );
			$message  = $query . "\n";
			$message .= '```' . print_r( $results, true ) . '```' . "\n";

			if ( $results['totalSize'] ) {
				$prescription_id = 0;
				$current         = strtotime( $api_data['transaction_date'] . ' ' . $api_data['transaction_time'] );
				$sf_record_time  = strtotime( $results['records'][0]['Transaction_Date__c'] . ' ' . $results['records'][0]['Transaction_Time__c'] );
				if ( $current > $sf_record_time ) {
					$prescription_id = $results['records'][0]['Id'];
					$this->sf->update_lead( 'Prescription__c', $prescription_id, $sf_data );
					$message .= "Updated existing record {$prescription_id}\n";
				} else {
					$message .= "Skip existing record {$results['records'][0]['Id']}\n";
				}
			} else {

				$prescription_id = $this->sf->create_record( 'Prescription__c', $sf_data );
				$message        .= "Created new record {$prescription_id}\n";
			}
			$this->send_notification_to_slack( $message );
			// update_option( 'prescription_' . md5( $query ), $prescription_id, 0 );
		}
		return $prescription_id;
	}

	/**
	 * Insert Dosage information for Prescription.
	 *
	 * @param string $prescription_id The prescription ID.
	 * @param array $api_data The input data.
	 */
	protected function insert_dosage( $prescription_id, $api_data ) {
		$object = 'Dosage__C';
		foreach ( $api_data['dosage_info'] as $data ) {
			$query  = "SELECT Id FROM Dosage__C WHERE Dosage_Days__c = '{$data['dose_days']}' AND Prescription__c = '{$prescription_id}'";
			$dosage = $this->sf->get_query( $query );
			$obj    = array(
				'Dosage_Days__c'     => $data['dose_days'],
				'Dosage_Quantity__c' => $data['dose_qty'],
				'Dosage_Slot__c'     => $data['dose_slot'],
				'Dosage_Time__c'     => $data['dose_time'],
				'Prescription__c'    => $prescription_id,
			);
			if ( $dosage['totalSize'] ) {
				unset( $obj['Prescription__c'] );
				unset( $obj['Dosage_Days__c'] );
				$this->sf->update_lead( $object, $dosage['records'][0]['Id'], $obj );
			} else {
				$this->sf->create_record( $object, $obj );
			}
		}
	}

	/**
	 * Insert diagnosis for prescription.
	 *
	 * @param string $prescription_id The prescription ID.
	 * @param array  $api_data The input data.
	 */
	protected function insert_diagnosis( $prescription_id, $api_data ) {
		$object = 'Diagnosis__C';
		foreach ( $api_data['dosage_info'] as $data ) {
			$query      = "SELECT Id FROM Diagnosis__C WHERE Code__c = '{$data['code']}' AND Prescription__c = '{$prescription_id}'";
			$diagnostic = $this->sf->get_query( $query );
			$obj        = array(
				'Code__c'         => $data['code'],
				'Description__c'  => $data['description'],
				'Qualifier__c'    => $data['qualifier'],
				'Prescription__c' => $prescription_id,
			);
			if ( $diagnostic['totalSize'] ) {
				unset( $obj['Prescription__c'] );
				unset( $obj['code'] );
				$this->sf->update_lead( $object, $diagnostic['records'][0]['Id'], $obj );
			} else {
				$this->sf->create_record( $object, $obj );
			}
		}
	}

	/**
	 * Insert payers for prescription.
	 *
	 * @param string $prescription_id The prescription ID.
	 * @param array $api_data The input data.
	 */
	protected function insert_payers( $prescription_id, $api_data ) {
		$object = 'Payer__c';
		foreach ( $api_data['payers'] as $data ) {
			$query = "select Id from Plan_BIN__c WHERE Name = '{$data['plan_bin']}'";
			// $plan_id = get_option( 'new_plan_' . md5( $query ), 0 );
			$plan_id = 0;
			if ( 0 === $plan_id ) {
				$results = $this->sf->get_query( $query );
				if ( $results['totalSize'] ) {
					$plan_id = $results['records'][0]['Id'];
				} else {
					$plan_id = $this->sf->create_record( 'Plan_BIN__c', array( 'Name' => $data['plan_bin'] ) );
				}
				// update_option( 'new_plan_' . md5( $query ), $plan_id, 0 );
			}
			$query = "SELECT Id FROM Payer__c WHERE Plan_Bin_Record__c = '{$plan_id}' AND Prescription__c = '{$prescription_id}'";
			if ( isset( $_GET['test5123'] ) ) {
				echo $query;
			}
			$payer = $this->sf->get_query( $query );
			$obj   = array(
				'Card_ID__c'                  => $data['card_id'],
				'Cost_Paid__c'                => $data['cost_paid'],
				'Discount__c'                 => $data['discount'],
				'Dispensing_Fee_Paid__c'      => $data['disp_fee_paid'],
				'Due_From_Payer__c'           => $data['due_from_payer'],
				'Group__c'                    => $data['group'],
				'Patient_Pay_Amount__c'       => $data['pat_pay_amount'],
				'Person_Code__c'              => $data['person_code'],
				'Plan_Billing_Status__c'      => $data['plan_bill_status'],
				'Plan_Billing_Status_Text__c' => $data['plan_bill_status_text'],
				'Plan_BIN__c'                 => $data['plan_bin'],
				'Plan_Bin_Record__c'          => $plan_id,
				'Plan_Code__c'                => $data['plan_code'],
				'Plan_Name__c'                => $data['plan_name'],
				'Plan_PCN__c'                 => $data['plan_pcn'],
				'Plan_Sequence_Number__c'     => $data['plan_seq_no'],
				'Relationship_Code__c'        => $data['relation_code'],
				'Tax_Paid__c'                 => $data['tax_paid'],
				'Prescription__c'             => $prescription_id,
			);
			if ( $payer['totalSize'] ) {
				unset( $obj['Prescription__c'] );
				unset( $obj['Plan_Bin_Record__c'] );
				$this->sf->update_lead( $object, $payer['records'][0]['Id'], $obj );
			} else {
				$this->sf->create_record( $object, $obj );
			}
		}
	}

	/**
	 * Get contact Id from Prescription update.
	 * @param array $api_data The input data.
	 *
	 * @return mixed|void
	 */
	protected function get_patient_contact( $api_data ) {
		$dob   = date( 'Y-m-d', strtotime( $api_data['patient_dob'] ) );
		$fname = strtolower( $api_data['patient_first_name'] );
		$lname = strtolower( $api_data['patient_last_name'] );
		$query = "SELECT Id FROM Contact WHERE Birthdate = {$dob} and FirstName = '{$fname}' and LastName = '{$lname}'";
		if ( isset( $_GET['test5123'] ) ) {
			echo $query;
		}
		// $contact_id = get_option( 'contact_' . md5( $query ), 0 );
		$contact_id = 0;
		if ( ! $contact_id ) {
			$results = $this->sf->get_query( $query );
			if ( $results['totalSize'] ) {
				$contact_id = $results['records'][0]['Id'];
			} else {
				$temp_data  = array(
					'Birthdate' => $api_data['patient_dob'],
					'FirstName' => $api_data['patient_first_name'],
					'LastName'  => $api_data['patient_last_name'],
					'State__c'  => $api_data['patient_state'],
				);
				$contact_id = $this->sf->create_record( 'Contact', $temp_data );
			}
			// update_option( 'contact_' . md5( $query ), $contact_id, 0 );
		}
		return $contact_id;
	}

	/**
	 * Validate if input comes from our stores.
	 * @param array $api_data Input data.
	 *
	 * @return string
	 */
	protected function validate_store( $api_data ) {
		$auth = 0;

		if ( empty( $api_data ) ) {
			return $auth;
		}

		$salesforce_data = get_option( 'salesforce_data' );

		foreach ( $salesforce_data['sf_stores'] as $key => $store ) {

			if ( $api_data['APIKey'] === $store[1] && $api_data['PharmacyNumber'] === $store[2] ) {

				$auth = $store[3];
			}
		}

		return $auth;
	}

	protected function send_notification_to_slack( $message ) {

		$message .= "\n" . '=================' . "\n";
		// wp_remote_post( 'https://hooks.slack.com/services/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', array(
		// 	'body' => json_encode( array( 'text' => $message ) ),
		// ) );
	}
}

return new Localhealth_Salesforce_Sync_Connector();
