<?php

namespace WPDiscourse\Shared;

trait PluginOptions {
	/**
	 * Returns a single array of options from a given array of arrays.
	 *
	 * @return array
	 */
	protected function get_options() {
		static $options = array();

		if ( empty( $options ) ) {
			$discourse_option_groups = get_option( 'discourse_option_groups' );
			if ( $discourse_option_groups ) {
				foreach ( $discourse_option_groups as $option_name ) {
					if ( get_option( $option_name ) ) {
						$option  = get_option( $option_name );
						$options = array_merge( $options, $option );
					}
				}

				$multisite_configuration_enabled = get_site_option( 'wpdc_multisite_configuration' );
				if ( 1 === intval( $multisite_configuration_enabled ) ) {
					$site_options = get_site_option( 'wpdc_site_options' );
					foreach ( $site_options as $key => $value ) {
						$options[ $key ] = $value;
					}
				}
			}
		}

		return apply_filters( 'wpdc_utilities_options_array', $options );
	}
}