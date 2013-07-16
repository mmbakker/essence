<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence;

use Essence\Configurable;
use Essence\Di\Container;



/**
 *	A collection of providers which can find the provider of an url.
 *
 *	@package fg.Essence
 */

class ProviderCollection {

	use Configurable;



	/**
	 *	Dependency injection container.
	 *
	 *	@var Essence\Di\Container
	 */

	protected $_Container = null;



	/**
	 *	A list of provider configurations.
	 *
	 *	### Options
	 *
	 *	- 'name' string Name of the provider.
	 *		- 'class' string The provider class.
	 *		- 'filter' string|callable A regex or callback to filter URLs
	 *			that will be processed by the provider.
	 *		- ... mixed Provider specific options.
	 *
	 *	@var array
	 */

	protected $_properties = array( );



	/**
	 *	A list of providers.
	 *
	 *	@var array
	 */

	protected $_providers = array( );



	/**
	 *	Constructor.
	 *
	 *	@param Essence\Di\Container $Container Dependency injection container
	 *		used to build providers.
	 */

	public function __construct( Container $Container ) {

		$this->_Container = $Container;
	}



	/**
	 *	Tells if a provider was found for the given url.
	 *
	 *	@param string $url An url which may be embedded.
	 *	@return mixed The url provider if any, otherwise null.
	 */

	public function hasProvider( $url ) {

		foreach ( $this->_properties as $config ) {
			if ( $this->_filter( $config['filter'], $url )) {
				return true;
			}
		}

		return false;
	}



	/**
	 *	Finds providers of the given url.
	 *
	 *	@todo Use PHP generators to yield providers.
	 *	@param string $url An url which may be embedded.
	 *	@return array An array of Essence\Provider.
	 */

	public function providers( $url ) {

		$providers = array( );

		foreach ( $this->_properties as $name => $config ) {
			if ( $this->_filter( $config['filter'], $url )) {
				$providers[ ] = $this->_provider( $name, $config );
			}
		}

		return $providers;
	}



	/**
	 *	Filters the URL with the given filter.
	 *
	 *	@param string|callable $filter Regex or callback to filter URL.
	 *	@param string $url URL to filter.
	 *	@return Whether the URL passes the filter or not.
	 */

	protected function _filter( $filter, $url ) {

		return is_callable( $filter )
			? call_user_func( $filter, $url )
			: preg_match( $filter, $url );
	}



	/**
	 *	Lazy loads a provider given its name and configuration.
	 *
	 *	@param string $name Name.
	 *	@param string $config Configuration.
	 *	@return Provider Instance.
	 */

	protected function _provider( $name, $config ) {

		if ( !isset( $this->_providers[ $name ])) {
			$class = $config['class'];
			$Provider = null;

			if ( $this->_Container->has( $class )) {
				$Provider = $this->_Container->get( $class );
			} else {
				if ( $class[ 0 ] !== '\\' ) {
					$class = "\\Essence\\Provider\\$class";
				}

				$Provider = new $class( );
			}

			$Provider->mergeProperties( $config );
			$this->_providers[ $name ] = $Provider;
		}

		return $this->_providers[ $name ];
	}
}
