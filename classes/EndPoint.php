<?php

namespace MooWoodle;

class EndPoint {
    private $endpoint_slug_course = 'my-courses';
	private $endpoint_slug_group = 'my-groups';
	private $endpoint_slug_view_enroll = 'view-enroll';
	
	public function __construct() {

		// register enpoints.
		add_action( 'init', [ &$this, 'register_my_courses_endpoint' ] );
		add_action( 'init', [ &$this, 'register_my_groups_endpoint' ] );
		add_action( 'init', [ &$this, 'register_view_enroll_endpoint' ] );

		// Resister 'my_course','my-group' end point page in WooCommerce 'my_account'.
		add_filter( 'woocommerce_account_menu_items', [ &$this, 'add_my_courses_to_account_menu' ] );
		add_filter( 'woocommerce_account_menu_items', [ &$this, 'add_my_groups_to_account_menu' ] );
		
		

		// Put endpoint containt. 
		add_action('woocommerce_account_' . $this->endpoint_slug_course . '_endpoint', [ &$this, 'load_my_courses_account_endpoint' ] );
		add_action('woocommerce_account_' . $this->endpoint_slug_group . '_endpoint', [ &$this, 'load_my_groups_account_endpoint' ] );
		add_action('woocommerce_account_' . $this->endpoint_slug_view_enroll . '_endpoint', [ &$this, 'load_view_enroll_account_endpoint' ] );
		add_action('wp_enqueue_scripts', [ $this, 'frontend_scripts' ]);
    }

	/**
	 *Adds my-courses endpoints table heade
	 * @return void
	 */
	public function register_my_courses_endpoint() {
		add_rewrite_endpoint( $this->endpoint_slug_course, EP_ROOT | EP_PAGES );
		flush_rewrite_rules();
	}

	/**
	 *Adds my-groups endpoints table heade
	 * @return void
	 */
	public function register_my_groups_endpoint() {
		add_rewrite_endpoint( $this->endpoint_slug_group, EP_ROOT | EP_PAGES );
		flush_rewrite_rules();
	}
	/**
	 *Adds view-enroll endpoints table heade
	 * @return void
	 */
	public function register_view_enroll_endpoint() {
		add_rewrite_endpoint( $this->endpoint_slug_view_enroll, EP_ROOT | EP_PAGES );
		flush_rewrite_rules();
	}
	
	/**
	 * resister my course to my-account WooCommerce menu.
	 * @param array $menu_links 
	 * @return array
	 */
	public function add_my_courses_to_account_menu( $menu_links ) {

		$menu_name     = __( 'My Courses', 'moowoodle' );
		$menu_link     = [ $this->endpoint_slug_course => $menu_name ];
		$menu_priority = MooWoodle()->setting->get_setting( 'my_courses_priority' );

		if( ! $menu_priority ) {
			$menu_priority = 0;
		}

		// Merge mycourse menu in priotity position
		$menu_links = array_slice( $menu_links, 0, $menu_priority + 1, true )
					+ $menu_link
		 			+ array_slice( $menu_links, $menu_priority + 1, NULL, true );

		return $menu_links;
	}

	/**
	 * resister my groups to my-account WooCommerce menu.
	 * @param array $menu_links 
	 * @return array
	 */
	public function add_my_groups_to_account_menu( $menu_links ) {

		$menu_name     = __( 'My Groups', 'moowoodle' );
		$menu_link     = [ $this->endpoint_slug_group => $menu_name ];
		$menu_priority = MooWoodle()->setting->get_setting( 'my_groups_priority' );

		if( ! $menu_priority ) {
			$menu_priority = 1;
		}

		// Merge mycourse menu in priotity position
		$menu_links = array_slice( $menu_links, 0, $menu_priority + 1, true )
					+ $menu_link
		 			+ array_slice( $menu_links, $menu_priority + 1, NULL, true );

		return $menu_links;
	}


	/**
	 * Add meta box panal.
	 * @return void
	 */
	public function load_my_courses_account_endpoint() {
		// $customer = wp_get_current_user();

		// // Get all orders of customer
		// $customer_orders = wc_get_orders([
		// 	'numberposts' => -1,
		// 	'orderby' 	  => 'date',
		// 	'order'       => 'DESC',
		// 	'type'        => 'shop_order',
		// 	'status'      => 'wc-completed',
		// 	'customer_id' => $customer->ID,
		// ]);

		// // Define the columns for table
		// $table_heading = [
		// 	__( "Course Name", 'moowoodle' ),
		// 	__( "Moodle User Name", 'moowoodle' ),
		// 	__( "Enrolment Date", 'moowoodle' ),
		// 	__( "Course Link", 'moowoodle' ),
		// ];

		// $password = get_user_meta( $customer->ID, 'moowoodle_moodle_user_pwd', true );

		// // Add extra column for password.
		// if ( $password ) {
		// 	array_splice( $table_heading, 2, 0, __( "Password (First Time use Only)", 'moowoodle' ) );
		// }
		
		// Util::get_template( 'endpoints/my-course.php', [
		// 	'table_heading'   => $table_heading,
		// 	'customer_orders' => $customer_orders,
		// 	'customer' 		  => $customer,
		// 	'password' 		  => $password,
		// ]);
		
		// // load css for admin panel.
		// add_action( 'wp_enqueue_scripts', [ $this, 'frontend_styles' ] );



		wp_enqueue_script(
			'moowoodle-myaccount-mycourse-script',
			MOOWOODLE_PLUGIN_URL . 'build/blocks/MyCourses/index.js',
			['wp-element', 'wp-i18n', 'react-jsx-runtime'],
			time(),
			true
		);

		wp_localize_script(
			'moowoodle-myaccount-mycourse-script',
			'appLocalizer',
			[
				'apiUrl'          => untrailingslashit( get_rest_url() ),
				'restUrl'         => 'moowoodle/v1',
				'nonce'           => wp_create_nonce('wp_rest'),
				'moodle_site_url' => MooWoodle()->setting->get_setting( 'moodle_url' ),
			]
		);
		
		echo '<div id="moowoodle-my-course"></div>';
		
	}

	/**
	 * Load the "My Groups" endpoint template in WooCommerce My Account.
	 * @return void
	 */
	public function load_my_groups_account_endpoint() {
		Util::get_template( 'endpoints/my-groups.php', [] );
		// $this->frontend_scripts(); 
	}
	/**
	 * Load the "View enroll" endpoint template in WooCommerce My Account.
	 * @return void
	 */

	public function load_view_enroll_account_endpoint($product_id) {

		// Get group_id from query parameter using filter_input
		$group_id = filter_input(INPUT_GET, 'groupId', FILTER_SANITIZE_NUMBER_INT) ?: 0;
		$group_item_id = filter_input(INPUT_GET, 'groupItemId', FILTER_SANITIZE_NUMBER_INT) ?: 0;

		// Load template with both product_id and group_id
		Util::get_template('endpoints/view-enroll.php', [
			'product_id' => $product_id,
			'group_id' => $group_id,
			'group_item_id' => $group_item_id,
		]);
		// $this->frontend_scripts();
	}

	/**
	 * Add frontend style in mycourse page
	 * @access public
	 * @return void
	 */
	public function frontend_styles() {
		$suffix = defined( 'MOOWOODLE_SCRIPT_DEBUG' ) && MOOWOODLE_SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_style( 'frontend_css', MOOWOODLE_PLUGIN_URL . 'assets/frontend/css/frontend' . $suffix . '.css', array(), MOOWOODLE_PLUGIN_VERSION );
	}

	public function frontend_scripts() {
		if ( is_account_page() ) {

			// Check for 'my-groups' endpoint
			// if ( is_wc_endpoint_url( 'my-groups' ) ) {
				wp_enqueue_script( 'my_groups_js', MOOWOODLE_PLUGIN_URL . 'assets/js/add-groups.js', [], MOOWOODLE_PLUGIN_VERSION );
				wp_localize_script( 'my_groups_js', 'group_frontend_data', 
					[
						'ajax_url' => admin_url('admin-ajax.php'),
					]
				);
			// }
		}
	}
}
