<?php
/**
 * Nonce generator & validator.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\SSOClient;

/**
 * Nonce generator
 */
class Nonce {

	/**
	 * Database Verson of nonce table
	 *
	 * @var string
	 */
	private $db_version = '1.0.1';

	/**
	 * Nonce Class Instance
	 *
	 * @var mixed
	 */
	private static $instance = null;

	/**
	 * Constructor
	 *
	 * @method __construct
	 */
	private function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		/**
		 * One can override the default nonce life.
		 *
		 * The default is set to 10 minutes, which is plenty for most of the cases
		 *
		 * @var int
		 */
		$this->nonce_life = apply_filters( 'wpdc_nonce_life', 600 );

		$this->maybe_create_db();
	}


	/**
	 * Singleton instance
	 *
	 * @method get_instance
	 *
	 * @return \WPDiscourse\SSOClient\Nonce       the instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the Database Name
	 *
	 * @method get_table_name
	 *
	 * @return string      the db name.
	 */
	private function get_table_name() {
		return "{$this->wpdb->prefix}discourse_nonce";
	}

	/**
	 * Db shouldn't be created/updated unless if it's an old version.
	 *
	 * @method maybe_create_db
	 */
	private function maybe_create_db() {
		if ( version_compare( get_option( 'wpdiscourse_nonce_db_version', -1 ), $this->db_version ) !== 1 ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$table_name = $this->get_table_name();
			$charset    = $this->wpdb->get_charset_collate();

			dbDelta(
				"CREATE TABLE {$table_name} (
					id mediumint(9) NOT NULL AUTO_INCREMENT,
					added_on datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
					nonce varchar(255) DEFAULT '' NOT NULL,
					action varchar(255) DEFAULT '' NOT NULL,
					PRIMARY KEY  (id)
				) $charset;"
			);

				update_option( 'wpdiscourse_nonce_db_version', $this->db_version );
		}

		$this->purge_expired_nonces();
	}

	/**
	 * Will purge expired nonces.
	 *
	 * @method purge_expired_nonces
	 */
	private function purge_expired_nonces() {
		$table_name = $this->get_table_name();

		$expired_nonces = $this->wpdb->get_results( "SELECT id FROM {$table_name} WHERE added_on < DATE_SUB(NOW(), INTERVAL {$this->nonce_life} SECOND)" );

		if ( count( $expired_nonces ) ) {
			$expired_nonces = wp_list_pluck( $expired_nonces, 'id' );
			$expired_nonces = implode( ',', $expired_nonces );
			$this->wpdb->get_results( "DELETE FROM {$table_name} WHERE id IN ({$expired_nonces})" );
		}
	}

	/**
	 * Creates a truly unique nonce based on the provided action
	 *
	 * @method create
	 *
	 * @param string|int $action Scalar value to add context to the nonce.
	 *
	 * @return string
	 */
	public function create( $action = -1 ) {
		$nonce = wp_hash( uniqid( $action, true ), 'nonce' );

		$this->wpdb->insert(
			$this->get_table_name(), array(
				'nonce'  => $nonce,
				'action' => $action,
			), array( '%s', '%s' )
		);

		return $nonce;
	}

	/**
	 * Verify a nonce if it's valid and it will invalidate it
	 *
	 * @method verify
	 *
	 * @param  string     $nonce  the nonce to be validated.
	 * @param string|int $action Scalar value to add context to the nonce.
	 *
	 * @return bool
	 */
	public function verify( $nonce, $action = -1 ) {
		$table_name = $this->get_table_name();

		$valid_nonce = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT id FROM {$table_name} WHERE nonce = %s AND action = %s", $nonce, $action ) );

		if ( ! empty( $valid_nonce ) ) {
			return (bool) $this->invalidate_nonce( $valid_nonce->id );
		}

		return false;
	}

	/**
	 * Delete the nonce from the DB once it is used
	 *
	 * @method invalidate_nonce
	 *
	 * @param  int $id the nonce ID that needs to be invalidated.
	 *
	 * @return boolean
	 */
	private function invalidate_nonce( $id ) {
		return $this->wpdb->delete(
			$this->get_table_name(), array(
				'id' => $id,
			), array( '%d' )
		);
	}
}
