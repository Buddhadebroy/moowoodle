<?php

namespace MooWoodle;

class Ajax {
    
    public function __construct() {
        // Register the AJAX action for emroll moodle user
        add_action('wp_ajax_moowoodle_add_user', [$this, 'moowoodle_add_user']);
    }

    /**
     * Handle AJAX request to add or process a user for MooWoodle.
     * @return void
     */
	public function moowoodle_add_user() {
		// Sanitize input data
		$user_name     = filter_input(INPUT_POST, 'user_name', FILTER_SANITIZE_STRING) ?: '';
		$user_email    = filter_input(INPUT_POST, 'user_email', FILTER_SANITIZE_EMAIL) ?: '';
		$product_id    = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_STRING) ?: '';
		$group_id      = filter_input(INPUT_POST, 'group_id', FILTER_SANITIZE_STRING) ?: '';
		$group_item_id = filter_input(INPUT_POST, 'group_item_id', FILTER_SANITIZE_STRING) ?: '';
	
		// Validate email
		if ( !is_email( $user_email ) ) {
			wp_send_json([
				'success' => false,
				'message' => __('Invalid email address.', 'moowoodle')
			]);
		}
		// Check if user exists; otherwise, create a new user
		$user = get_user_by( 'email', $user_email ) ;
		if (!$user) {
			$user_id = wp_create_user( $user_name, wp_generate_password(12, false), $user_email );
			// Handle user creation error
			if ( is_wp_error( $user_id ) ) {
				wp_send_json([
					'success' => false,
					'message' => __('Failed to create user: ', 'moowoodle') . $user_id->get_error_message()
				]);
			}
			(new \WP_User($user_id))->set_role('customer');
		} else {
			$user_id = $user->ID;
		}
		// Get Moodle user ID
		$moodle_user_id = $this->get_moodle_user_id( $user_id );

		if ( ! $moodle_user_id ) {
			\MooWoodle\Util::log('Unable to enroll user, Moodle user creation failed.');
			wp_send_json([
				'success' => false,
				'message' => __('Failed to retrieve Moodle user ID.', 'moowoodle')
			]);
		}
		// Get course ID from product meta
		$course_id = get_post_meta( $product_id, 'moodle_course_id', true );
		if (!$course_id) {
			wp_send_json([
				'success' => false,
				'message' => __('Invalid course ID.', 'moowoodle')
			]);
		}
		// Enroll user
		$enroll_response = $this->enrol_user([
			'user_id'        => $user_id,
			'moodle_user_id' => $moodle_user_id,
			'course_id'      => $course_id,
			'group_id'       => $group_id,
			'group_item_id'  => $group_item_id,
		]);
	
		// Check enrollment success
		if ( ! $enroll_response['success'] ) {
			wp_send_json([
				'success' => false,
				'message' => __('Enrollment failed: ', 'moowoodle') . ($enroll_response['message'] ?? __('Unknown error', 'moowoodle'))
			]);
		}
	
		// Send success response
		wp_send_json([
			'success' => true,
			'message' => __('User successfully enrolled in the course.', 'moowoodle')
		]);
	}

    public function get_moodle_user_id($user_id) {
		// if user is a guest user.
		if ( ! $user_id ) return $user_id;

		$moodle_user_id = get_user_meta( $user_id, 'moowoodle_moodle_user_id', true );
		/**
		 * Filter before moodle user create or update.
		 * @var int $moodle_user_id
		 * @var int user_id
		 */
		$moodle_user_id = apply_filters( 'moowoodle_get_moodle_user_id_before_enrollment', $moodle_user_id, $user_id );
		// If moodle user id exist then return it.
		if ( $moodle_user_id ) return $moodle_user_id;

		$user 	  = ( $user_id ) ? get_userdata( $user_id ) : false;

		// Get user id from moodle database.
		$moodle_user_id = $this->search_for_moodle_user( 'email', $user->user_email );
		if ( ! $moodle_user_id ) {
			$moodle_user_id = $this->create_user( $user );
		} 
        // else {
		// 	// User id is availeble update user id.

		// 	$should_user_update = MooWoodle()->setting->get_setting( 'update_moodle_user', [] );
		// 	$should_user_update = is_array( $should_user_update ) ? $should_user_update : [];
		// 	$should_user_update = in_array(
		// 		'update_moodle_user',
		// 		$should_user_update
		// 	);
		// 	file_put_contents( WP_CONTENT_DIR . '/mo_file_log.txt', 'response:get moodle user id else call'. var_export($moodle_user_id, true) . "\n", FILE_APPEND );

		// 	if ( $should_user_update ) {
		// 		$this->update_moodle_user( $moodle_user_id );
		// 	}
		// }

		update_user_meta( $user_id, 'moowoodle_moodle_user_id', $moodle_user_id );
		return $moodle_user_id;
	}
	private function search_for_moodle_user( $key, $value ) {
		// find user on moodle with moodle externel function.
		$response = MooWoodle()->external_service->do_request(
			'get_moodle_users',
			[ 
				'criteria' => [
					[
						'key' 	=> $key,
						'value' => $value
					]
				]
			]
		);

		if ( ! empty( $response[ 'data' ][ 'users' ]) ) {
			$user = reset( $response[ 'data' ][ 'users' ] );
			return $user[ 'id' ];
		}

		return 0;
	}

    public function create_user( $user ) {
		try {

			$password = get_user_meta( $user->get('ID'), 'moowoodle_moodle_user_pwd', true );

			// If password not exist create a password.
			if ( ! $password ) {
				$password = $this->generate_password();
				add_user_meta( $user->get('ID'), 'moowoodle_moodle_user_pwd', $password );
			}
	
			$response = MooWoodle()->external_service->do_request( 'create_users', [ 'users' =>  [ [
				'email' 	=> $user->user_email,
				'username'  => $user->user_login,
				'password'  => $password,
				'auth' 		=> 'manual',
				'firstname' => $user->display_name,
				'lastname'  => 'testuser',
				'preferences' => [
					[
						'type'  => "auth_forcepasswordchange",
						'value' => 1
					]
				]
			] ] ] );
			// Not a valid response.
			if ( ! $response[ 'data' ] ) return 0;

			$moodle_users = $response[ 'data' ];
			$moodle_users = reset( $moodle_users );

			if ( is_array( $moodle_users ) && isset( $moodle_users[ 'id' ] ) ) {
				$user_id = $moodle_users[ 'id' ];

				/**
				 * Action hook after moodle user creation.
				 * @var array $user_data data for creating user in moodle
				 * @var int $user_id newly created user id
				 */
				//do_action( 'moowoodle_after_create_moodle_user', $user_data, $user_id );
                update_user_meta( $user->get('ID'), 'moowoodle_moodle_new_user_created', 'created' );
				return $user_id;
			} else {
				throw new \Exception( "Unable to create user." );
			}
		} catch ( \Exception $e ) {
			Util::log( $e->getMessage() );
		}

		return 0;
	}

    /**
	 * Generate random password.
	 * @param int $length default length is 12.
	 * @return string generated password.
	 */
	private function generate_password( $length = 12 ) {
		$sets 	= [];
		$sets[] = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
		$sets[] = 'abcdefghjkmnpqrstuvwxyz';
		$sets[] = '23456789';
		$sets[] = '~!@#$%^&*(){}[],./?';

		$password = '';

		// Append a character from each set - gets first 4 characters
		foreach ( $sets as $set ) {
			$password .= $set[ array_rand( str_split( $set ) ) ];
		}

		//use all characters to fill up to $length
		while ( strlen( $password ) < $length ) {
			//get a random set
			$randomSet = $sets[ array_rand( $sets ) ];

			//add a random char from the random set
			$password .= $randomSet[ array_rand( str_split( $randomSet ) ) ];
		}

		//shuffle the password string before returning!
		return str_shuffle( $password );
	}

	public static function enrol_user( $enroll_data ) {
		global $wpdb;
	
		// Fetch previously enrolled courses
		$previous_enrolled_courses = get_user_meta( $enroll_data['user_id'], 'moowoodle_moodle_course_enroll', true );
		$previous_enrolled_courses = is_array( $previous_enrolled_courses ) ? $previous_enrolled_courses : [];
	
		// Exit if user is already enrolled
		if ( in_array( $enroll_data[ 'course_id' ], $previous_enrolled_courses ) ) {
			return [
				'success' => false,
				'message' => __('User is already enrolled in this course.', 'moowoodle')
			];
		}
	
		// Fetch group item data in a single query
		$group_item_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT product_id, available_quantity FROM {$wpdb->prefix}moowoodle_group_items WHERE id = %d",
				$enroll_data['group_item_id']
			),
			ARRAY_A
		);
	
		// Validate group item existence and availability
		if ( ! $group_item_data || (int)$group_item_data[ 'available_quantity' ] <= 0 ) {
			return [
				'success' => false,
				'message' => $group_item_data ? __('No available seats for this course.', 'moowoodle') : __('Group item not found.', 'moowoodle')
			];
		}
	
		// Prepare enrollment request
		$enrolments = [[
			'courseid' => (int) $enroll_data['course_id'],
			'userid'   => $enroll_data['moodle_user_id'],
			'roleid'   => 5,
		]];
	
		// Call Moodle API for user enrollment
		$response = MooWoodle()->external_service->do_request('enrol_users', ['enrolments' => $enrolments]);
	
		// Handle Moodle API failure
		if (!$response || isset($response['error'])) {
			return [
				'success' => false,
				'message' => __('Moodle enrollment failed.', 'moowoodle')
			];
		}
	
		// Fetch order ID in a single query
		$order_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT order_id FROM {$wpdb->prefix}moowoodle_group WHERE id = %d",
				$enroll_data['group_id']
			)
		);
	
		// Add user enrollment record
		$user = get_userdata( $enroll_data[ 'user_id' ] );
		self::add_enrollment([
			'user_id'       => $enroll_data['user_id'],
			'user_email'    => $user->user_email,
			'course_id'     => $enroll_data['course_id'],
			'order_id'      => $order_id,
			'item_id'       => 0,
			'status'        => 'enrolled',
			'group_item_id' => $enroll_data['group_item_id'],
		]);
	
		// Reduce available quantity
		$wpdb->update(
			"{$wpdb->prefix}moowoodle_group_items",
			[ 'available_quantity' => (int) $group_item_data[ 'available_quantity' ] - 1],
			['id' => $enroll_data[ 'group_item_id' ]], 
			['%d'],
			['%d']
		);
	
		// Update user meta with the newly enrolled course
		$previous_enrolled_courses[] = $enroll_data[ 'course_id' ];
		update_user_meta( $enroll_data[ 'user_id' ], 'moowoodle_moodle_course_enroll', $previous_enrolled_courses );
	
		// Fire action hook for post-enrollment events
		do_action( 'moowoodle_after_enrol_moodle_user', $enrolments, $enroll_data[ 'user_id' ] );
	
		return [
			'success' => true,
			'message' => __('User successfully enrolled.', 'moowoodle')
		];
	}
	
    /**
	 * Add new enrollment
	 * @param mixed $args
	 * @return bool|int|null
	 */
	public static function add_enrollment( $args ) {
		global $wpdb;

		try {
			// insert data 
			return $wpdb->insert( "{$wpdb->prefix}moowoodle_enrollment", $args );
		} catch ( \Exception $error ) {
			return null;
		}
	}
}