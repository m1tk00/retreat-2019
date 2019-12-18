<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.webpigment.com
 * @since      1.0.0
 *
 * @package    Localhealth_Salesforce_Sync
 * @subpackage Localhealth_Salesforce_Sync/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Localhealth_Salesforce_Sync
 * @subpackage Localhealth_Salesforce_Sync/admin
 * @author     webpigment <mitko@webpigment.com>
 */
class Localhealth_Salesforce_Sync_Admin {

	private $options;

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/localhealth-salesforce-sync-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/localhealth-salesforce-sync-admin.js', array( 'jquery' ), $this->version, false );

	}

	public function add_plugin_page() {
		 // This page will be under "Settings"
		add_options_page(
			'Settings SalesForce',
			'SalesForce Connector',
			'manage_options',
			'sf-settings-page',
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page() {
		// Set class property
		$this->options = get_option( 'salesforce_data' );
		?>
		<div class="wrap">
			<h1>SalesForce Settings</h1>
			<form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields
				settings_fields( 'my_option_group' );
				do_settings_sections( 'sf-settings-page' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init() {

		register_setting(
			'my_option_group', // Option group
			'salesforce_data', // Option name
			array( $this, 'sanitize' ) // Sanitize
		);

		add_settings_section(
			'setting_section_id', // ID
			'My Custom Settings', // Title
			array( $this, 'print_section_info' ), // Callback
			'sf-settings-page' // Page
		);

		add_settings_field(
			'sf_username', // ID
			'SalesForce Username', // Title
			array( $this, 'do_email_callback' ), // Callback
			'sf-settings-page', // Page
			'setting_section_id' // Section
		);

		add_settings_field(
			'sf_password',
			'SalesForce Password',
			array( $this, 'sf_password_callback' ),
			'sf-settings-page',
			'setting_section_id'
		);
		add_settings_field(
			'sf_token',
			'SalesForce Token',
			array( $this, 'sf_token_callback' ),
			'sf-settings-page',
			'setting_section_id'
		);

		add_settings_field(
			'sf_store_mapper',
			'SalesForce Store Mapper',
			array( $this, 'sf_store_mapper_callback' ),
			'sf-settings-page',
			'setting_section_id'
		);
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 */
	public function sanitize( $input ) {
		$new_input = array();
		if ( isset( $input['sf_username'] ) ) {
			$new_input['sf_username'] = sanitize_text_field( $input['sf_username'] );
		}

		if ( isset( $input['sf_password'] ) ) {
			$new_input['sf_password'] = sanitize_text_field( $input['sf_password'] );
		}
		if ( isset( $input['sf_token'] ) ) {
			$new_input['sf_token'] = sanitize_text_field( $input['sf_token'] );
		}
		$sf_stores = array();

		if ( isset( $input['sf_stores'] ) ) {
			foreach( $input['sf_stores'] as $key => $data ) {
				$sf_stores[] = array(
					sanitize_text_field( $data[0] ),
					sanitize_text_field( $data[1] ),
					sanitize_text_field( $data[2] ),
					sanitize_text_field( $data[3] ),
				);
			}
		}
		$new_input['sf_stores'] = $sf_stores;

		return $new_input;
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info() {
		print 'Enter your settings below:';
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function do_email_callback() {
		printf(
			'<input type="email" id="sf_username" name="salesforce_data[sf_username]" value="%s" />',
			isset( $this->options['sf_username'] ) ? esc_attr( $this->options['sf_username'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function sf_password_callback() {
		printf(
			'<input type="password" id="sf_password" name="salesforce_data[sf_password]" value="%s" />',
			isset( $this->options['sf_password'] ) ? esc_attr( $this->options['sf_password'] ) : ''
		);
	}
	/**
	 * Get the settings option array and print one of its values
	 */
	public function sf_token_callback() {
		printf(
			'<input type="password" id="sf_token" name="salesforce_data[sf_token]" value="%s" />',
			isset( $this->options['sf_token'] ) ? esc_attr( $this->options['sf_token'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function sf_text_callback( $name, $value ) {
		printf(
			'<input type="text" id=" ' . $name . ' " name="' . esc_attr( $name ) . '" value="%s" />',
			isset( $value ) ? esc_attr( $value ) : ''
		);
	}
	public function sf_store_mapper_callback() {
		?>
		<table>
			<thead>
				<tr>
					<th>Store Name</th>
					<th>Store APIKey</th>
					<th>Store NPI</th>
					<th>Store SalesForce Name</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$i = 0;
				if ( is_array( $this->options['sf_stores'] ) ) {
					foreach ( $this->options['sf_stores'] as $key => $data ) {
						if ( empty( $data[0] ) || empty( $data[1] ) || empty( $data[2] ) || empty( $data[3] ) ) {
							continue;
						}
						echo '<tr>';
						echo '<td>';
						$this->sf_text_callback( 'salesforce_data[sf_stores][' . $i . '][0]', $data[0] );
						echo '</td>';
						echo '<td>';
						$this->sf_text_callback( 'salesforce_data[sf_stores][' . $i . '][1]', $data[1] );
						echo '</td>';
						echo '<td>';
						$this->sf_text_callback( 'salesforce_data[sf_stores][' . $i . '][2]', $data[2] );
						echo '</td>';
						echo '<td>';
						$this->sf_text_callback( 'salesforce_data[sf_stores][' . $i . '][3]', $data[3] );
						echo '</td>';
						echo '</tr>';
						$i++;
					}
				}
				?>
				<tr>
					<td>
						<?php $this->sf_text_callback( 'salesforce_data[sf_stores][' . $i . '][0]', '' ); ?>
					</td>
					<td>
					     <?php $this->sf_text_callback( 'salesforce_data[sf_stores][' . $i . '][1]', '' ); ?>
					</td>
					<td>
					     <?php $this->sf_text_callback( 'salesforce_data[sf_stores][' . $i . '][2]', '' ); ?>
					</td>
					<td>
						 <?php $this->sf_text_callback( 'salesforce_data[sf_stores][' . $i . '][3]', '' ); ?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

}
