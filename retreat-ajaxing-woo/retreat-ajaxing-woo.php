<?php
/**
 * Plugin Name: Ajaxing your ( WooCommerce ) website.
 */
class Retreat_Ajaxing_Woo {
	public function __construct() {
		add_action( 'init', array( $this, 'register_my_script' ) );

		// Logged in users.
		add_action( 'wp_ajax_sample_ajax_call', array( $this, 'sample_ajax_call' ), 10 );
		add_action( 'wp_ajax_sample_ajax_call', 'wp_die', 20 );
		// Logged out users.
		add_action( 'wp_ajax_nopriv_sample_ajax_call', array( $this, 'nopriv_sample_ajax_call' ), 10 );
		add_action( 'wp_ajax_nopriv_sample_ajax_call', 'wp_die', 20 );
	}

	public function register_my_script() {
		wp_enqueue_script( 'my-ajax-script', plugins_url( '/js/my_query.js', __FILE__ ), array( 'jquery' ) );
		wp_localize_script(
			'my-ajax-script',
			'ajax_object',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'_wpnonce' => wp_create_nonce( 'my-nonce' ),
			)
		);
	}

	public function sample_ajax_call() {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-nonce' ) ) {
			// This nonce is not valid.
			die( 'Security check' );
		} else {
			echo 'logged in';
		}
	}

	public function nopriv_sample_ajax_call() {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-nonce' ) ) {
			// This nonce is not valid.
			die( 'Security check' );
		} else {
			echo 'not logged in';
		}
	}
}

return new Retreat_Ajaxing_Woo();
