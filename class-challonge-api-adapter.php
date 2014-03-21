<?php
/**
 * @package Challonge
 */

// Exit on direct request.
defined( 'ABSPATH' ) OR exit;

require_once( 'class-challonge-api.php' ); // We need this, it's what we're adapting to!

/**
 * This adapter class wraps the Challonge API class to allow caching.
 */
class Challonge_Api_Adapter extends Challonge_Api {
	protected $oCP;
	protected $aOptions;
	public function __construct( $api_key = '' ) {
		$this->oCP = Challonge_Plugin::getInstance();
		$this->aOptions = $this->oCP->getOptions();
		parent::__construct( $api_key );
	}
	public function makeCall( $path = '', $params = array(), $method = 'get' ) {
		if ( 'get' == $method && 0 < $this->aOptions['caching'] ) {
			$transient = 'challongeapi-' . md5( serialize( func_get_args() ) ); // Exactly 45 characters (max for transients)
			if ( $this->oCP->isCacheIgnored() || false === ( $transient_data = get_transient( $transient ) ) ) {
				$response = parent::makeCall( $path, $params, $method );
				$transient_data = $response->asXML();
				$transient_set = set_transient( $transient, $transient_data, $this->aOptions['caching'] );
				if ( $transient_set )
					$this->oCP->logCache( $transient );
				//echo $transient_set ? '[OK:TransSet]' : '[ERROR:TransNotSet]';
			} else {
				$response = simplexml_load_string( $transient_data );
				//echo '[OK:TransFound]';
			}
			return $response;
		}
		//echo '[OK:TransSkipped]';
		return parent::makeCall( $path, $params, $method );
	}
}
