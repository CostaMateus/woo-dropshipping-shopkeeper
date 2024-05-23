<?php
/**
 * Singleton class trait.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\RestApi
 * @version 	1.0.0
 */

namespace Dropshipping_Shopkeeper\RestApi\Utilities;

/**
 * Singleton trait.
 */
trait SingletonTrait
{
	/**
	 * The single instance of the class.
	 *
	 * @var 	object
	 */
	protected static $instance = null;

	/**
	 * Constructor
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	protected function __construct() {}

	/**
	 * Get class instance.
	 *
	 * @since 	1.0.0
	 * @return 	object Instance.
	 */
	final public static function instance() : object
	{
		if ( null === static::$instance )
			static::$instance = new static();

		return static::$instance;
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	final public function __wakeup() : void
	{
		wc_doing_it_wrong( __FUNCTION__, __( "Unserializing instances of this class is forbidden.", "" ), "1.0.0" );
		die();
	}
}
