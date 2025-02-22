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
        $user_name       = filter_input(INPUT_POST, 'user_name', FILTER_SANITIZE_STRING) ?: '';
        $user_email      = filter_input(INPUT_POST, 'user_email', FILTER_SANITIZE_EMAIL) ?: '';
        $product_id      = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_STRING) ?: '';
        $group_id        = filter_input(INPUT_POST, 'group_id', FILTER_SANITIZE_STRING) ?: '';
        $group_item_id   = filter_input(INPUT_POST, 'group_item_id', FILTER_SANITIZE_STRING) ?: '';


        // Validate email
        if (!is_email($user_email)) {
            wp_send_json_error(['message' => __('Invalid email address.', 'moowoodle')]);
        }

        // Check if user exists by email
        $user = get_user_by( 'email', $user_email );
        if ($user) {
            $user_id = $user->ID;
        } else {
            // Create new user
            $random_password = wp_generate_password(12, false);
            $user_id = wp_create_user($user_name, $random_password, $user_email);

            // Handle user creation error
            if (is_wp_error($user_id)) {
                wp_send_json_error(['message' => __('Failed to create user: ') . $user_id->get_error_message(), 'moowoodle']);
            }

            // Set user role
            $user = new \WP_User($user_id);
            $user->set_role('customer');
        }

        $moodle_user_id = $this->get_moodle_user_id( $user_id );

        if ( ! $moodle_user_id ) {
            \MooWoodle\Util::log( 'Unable to enroll user, unable to create user in moodle' );
        }

        $course_id = get_post_meta( $product_id, 'moodle_course_id', true );

        $enroll = $this->enrol_user( $moodle_user_id, $course_id, $group_id, $group_item_id );

        // Send success response
        wp_send_json_success([
            'message' => $message,
            'user_id' => $user_id,
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
        else {
			// User id is availeble update user id.

			$should_user_update = MooWoodle()->setting->get_setting( 'update_moodle_user', [] );
			$should_user_update = is_array( $should_user_update ) ? $should_user_update : [];
			$should_user_update = in_array(
				'update_moodle_user',
				$should_user_update
			);

			if ( $should_user_update ) {
				$this->update_moodle_user( $moodle_user_id );
			}
		}

		update_user_meta( $user_id, 'moowoodle_moodle_user_id', $moodle_user_id );

		return $moodle_user_id;
	}

    public function create_user( $user ) {

        $password = get_user_meta( $user_id, 'moowoodle_moodle_user_pwd', true );

        // If password not exist create a password.
		if ( ! $password ) {
			$password = $this->generate_password();
			add_user_meta( $user_id, 'moowoodle_moodle_user_pwd', $password );
		}

		$response = MooWoodle()->external_service->do_request( 'create_users', [ 'users' =>  [ [
			'email' 	=> $user->user_email,
			'username'  => $user->user_login,
			'password'  => $password,
			'auth' 		=> 'manual',
			'firstname' => $user->display_name,
			'lastname'  => 'testuser',
		] ] ] );

		if ( $response && ! isset( $response[ 'error' ] ) ) {
			$response[ 'success' ] = true;
		}

		return $response;
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

    public static function enrol_user( $user_id, $course_id, $group_id, $group_item_id ) {
        global $wpdb;

		$response = MooWoodle()->external_service->do_request( 'enrol_users', [ 'enrolments' =>  [ [
			'courseid' => "$course_id",
			'userid'   => "$user_id",
			'roleid'   => '5',
		] ] ] );

        $user = get_userdata($user_id);

        // Fetch order_id from the database using group_id
        $order_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT order_id FROM {$wpdb->prefix}moowoodle_group WHERE id = %d",
                $group_id
            )
        );

        self::add_enrollment([
            'user_id' 	 => $user_id,
            'user_email' => $user->user_email,
            'course_id'  => $course_id,
            'order_id'   => $order_id ,
            'item_id'    => 0,
            'status'     => 'enrolled',
            'group_item_id' => $group_item_id,
        ]);

		if ( $response && ! isset( $response[ 'error' ] ) ) {
			$response[ 'success' ] = true;
		}

		return $response;
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