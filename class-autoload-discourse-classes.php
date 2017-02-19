<?php
/**
 * Autoload Discourse related classes
 *
 * @package WPDiscourse
 */

/**
 * SPL Autoloader
 */
class Autoload_Discourse_Classes {
	/**
	 * Current Dir path
	 *
	 * @var string
	 */
	public $dir = __DIR__;

	/**
	 * Constructor
	 *
	 * @method __construct
	 */
	public function __construct() {
		spl_autoload_register( array( $this, 'spl_autoload_register' ) );
	}

	/**
	 * Register autoload
	 *
	 * @method spl_autoload_register
	 *
	 * @param  string $class_name the class name to be loaded.
	 */
	public function spl_autoload_register( $class_name ) {
		$class_name = str_replace( 'WPDiscourse', 'lib', $class_name );
		$class_name = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name );
		$class_path = $this->dir . DIRECTORY_SEPARATOR . strtolower( str_replace( '_', '-', $class_name ) );

		$class_path = preg_replace( '~([^\\\\]+$)~', 'class-$1.php', $class_path );
		if ( file_exists( $class_path ) ) {
			include $class_path;
		}
	}
}

new Autoload_Discourse_Classes();
