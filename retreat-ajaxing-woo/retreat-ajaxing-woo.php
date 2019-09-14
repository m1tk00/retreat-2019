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

		add_action( 'woocommerce_add_to_cart_fragments', array( $this, 'update_menu_item' ) );
		add_action( 'wp_footer', array( $this, 'display_cart_items' ) );
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

	public function update_menu_item( $fragments ) {
		$fragments['.refresh_this_item'] = $this->update_cart_items();
		return $fragments;
	}

	protected function update_cart_items() {
		ob_start();
		?>
		<div class="refresh_this_item">
			<?php if ( WC()->cart->get_cart_total() ) { ?>
				Cart Total:
				<strong><?php echo WC()->cart->get_cart_total(); ?></strong>
			<?php } ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public function display_cart_items() {
		echo $this->update_cart_items();
	}

}

return new Retreat_Ajaxing_Woo();
