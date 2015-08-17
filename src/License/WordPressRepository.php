<?php

namespace Never5\LicenseWP\License;

class WordPressRepository implements Repository {
	/**
	 * @param string $key
	 *
	 * @return \stdClass
	 */
	public function retrieve( $key ) {
		global $wpdb;

		$data = new \stdClass();

		$row = $wpdb->get_row( $wpdb->prepare( "
		SELECT * FROM {$wpdb->prefix}license_wp_licenses
		WHERE license_key = %s
	", $key ) );

		/**
		 * AND (
		 * date_expires IS NULL
		 * OR date_expires = '0000-00-00 00:00:00'
		 * OR date_expires > NOW()
		 * )
		 */

		// set data if row found
		if ( null !== $row ) {
			$data->key              = $row->license_key;
			$data->order_id         = $row->order_id;
			$data->user_id          = $row->user_id;
			$data->activation_email = $row->activation_email;
			$data->product_id       = $row->product_id;
			$data->activation_limit = $row->activation_limit;
			$data->date_created     = new \DateTime( $row->date_created );
			$data->date_expires     = new \DateTime( $row->date_expires );
		}

		return $data;
	}

	/**
	 * @param License $license
	 *
	 * @return bool
	 */
	public function persist( $license ) {
		global $wpdb;

		// dem defaults
		$defaults = array(
			'order_id'         => '',
			'activation_email' => '',
			'user_id'          => '',
			'license_key'      => '',
			'product_id'       => '',
			'activation_limit' => '',
			'date_expires'     => '',
			'date_created'     => current_time( 'mysql' )
		);

		// setup array with data
		$data = wp_parse_args( array(
			'license_key'      => $license->get_key(),
			'order_id'         => $license->get_order_id(),
			'user_id'          => $license->get_user_id(),
			'activation_email' => $license->get_activation_email(),
			'product_id'       => $license->get_product_id(),
			'activation_limit' => $license->get_activation_limit(),
			'date_created'     => $license->get_date_created()->format( 'Y-m-d' ),
			'date_expires'     => $license->get_date_expires()->format( 'Y-m-d' )
		), $defaults );

		// check if new license or existing
		if ( '' == $license->get_key() ) { // insert

			// generate new license
			$license->set_key( license_wp()->service( 'license_manager' )->generate_license_key() );

			// insert into WordPress database
			$wpdb->insert( $wpdb->prefix . 'license_wp_licenses', $data );
		} else { // update

			// unset license from data
			unset( $data['license_key'] );

			// update database
			$wpdb->update( $wpdb->prefix . 'license_wp_licenses',
				$data,
				array(
					'license_key' => $license->get_key()
				)
			);

		}

	}

}