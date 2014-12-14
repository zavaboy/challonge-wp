<?php
/**
 * @package Challonge
 */

// Exit on direct request.
defined( 'ABSPATH' ) OR exit;

require_once( 't27duck-challonge-php/challonge.class.php' ); // We need this, it's what we're adapting to!

/**
 * This adapter class wraps the Challonge API class by Tony Drake to allow caching and a few minor modifications.
 */
class Challonge_Api_Adapter extends ChallongeAPI {
	private $api_key;
	protected $oCP;
	protected $aOptions;
	public function __construct( $api_key = '' ) {
		$this->oCP = Challonge_Plugin::getInstance();
		$this->aOptions = $this->oCP->getOptions();
		$this->api_key = $api_key;
	}

	public function makeCall( $path = '', $params = array(), $method = 'get' ) {
		// Handle caching
		if ( 'get' == $method && 0 < $this->aOptions['caching'] ) {
			$transient = 'challongeapi-' . md5( serialize( func_get_args() ) ); // Exactly 45 characters (max for transients)
			if ( $this->oCP->isCacheIgnored() || false === ( $transient_data = get_transient( $transient ) ) ) {
				$transient_data = $response = $this->makeCallAlt( $path, $params, $method );
				if ( $response instanceof SimpleXMLElement )
					$transient_data = $response->asXML();
				$transient_set = set_transient( $transient, $transient_data, $this->aOptions['caching'] );
				if ( $transient_set )
					$this->oCP->logCache( $transient );
				//echo $transient_set ? '[OK:TransSet]' : '[ERROR:TransNotSet]'; // For debugging
			} else {
				if ( is_string( $transient_data ) && false !== strpos( $transient_data, '<?xml' ) )
					$response = simplexml_load_string( $transient_data );
				else
					$response = $transient_data;
				//echo '[OK:TransFound]'; // For debugging
			}
			return $response;
		}
		//echo '[OK:TransSkipped]'; // For debugging
		return $this->makeCallAlt( $path, $params, $method );
	}

	// Replaces parent::makeCall()
	public function makeCallAlt( $path = '', $params = array(), $method = 'get' ) {
		// Clear the public vars
		$this->errors = array();
		$this->status_code = 0;
		$this->result = false;

		// Append the api_key to params so it'll get passed in with the call
		$params['api_key'] = $this->api_key;

		// Build the URL that'll be hit. If the request is GET, params will be appended later
		$call_url = 'https://api.challonge.com/v1/'.$path.'.xml';

		$curl_handle = curl_init();
		// Common settings
		curl_setopt( $curl_handle, CURLOPT_CONNECTTIMEOUT, 5 );
		curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, 1 );

		if ( ! $this->verify_ssl ) {
			// WARNING: this would prevent curl from detecting a 'man in the middle' attack
			curl_setopt( $curl_handle, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt( $curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
		} else {
			curl_setopt( $curl_handle, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt( $curl_handle, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt( $curl_handle, CURLOPT_CAINFO, plugin_dir_path( __FILE__ ) . 'cacert.pem');
		}

		$curlheaders = array(); //array('Content-Type: text/xml','Accept: text/xml');

		// Determine REST verb and set up params
		switch( strtolower( $method ) ) {
			case 'post':
				$fields = http_build_query( $params, '', '&' );
				$curlheaders[] = 'Content-Length: ' . strlen( $fields );
				curl_setopt( $curl_handle, CURLOPT_POST      , 1       );
				curl_setopt( $curl_handle, CURLOPT_POSTFIELDS, $fields );
			break;

			case 'put':
				$fields = http_build_query( $params, '', '&' );
				$curlheaders[] = 'Content-Length: ' . strlen( $fields );
				curl_setopt( $curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT'   );
				curl_setopt( $curl_handle, CURLOPT_POSTFIELDS   , $fields );
			break;

			case 'delete':
				$params["_method"] = 'delete';
				$fields = http_build_query( $params, '', '&' );
				$curlheaders[] = 'Content-Length: ' . strlen( $fields );
				curl_setopt( $curl_handle, CURLOPT_POST      , 1       );
				curl_setopt( $curl_handle, CURLOPT_POSTFIELDS, $fields );
				// curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, "DELETE");
			break;

			case 'get':
			default:
				$call_url .= '?'.http_build_query( $params, '', '&' );
		}

		curl_setopt( $curl_handle, CURLOPT_HTTPHEADER, $curlheaders ); 
		curl_setopt( $curl_handle, CURLOPT_URL       , $call_url    );

		$curl_result = curl_exec( $curl_handle );
		$info = curl_getinfo( $curl_handle );
		$this->status_code = (int) $info['http_code'];
		$return = false;
		if ( $curl_result === false ) { 
			// CURL Failed
			$this->errors[] = curl_error( $curl_handle );
		} else {
			switch ( $this->status_code ) {
				case 401: // Bad API Key
				case 422: // Validation errors
				case 404: // Not found/Not in scope of account
					$return = $this->result = new SimpleXMLElement( $curl_result );
					foreach ( $return->error as $error ) {
						$this->errors[] = $error;
					}
					$return = false;
				break;

				case 500: // Oh snap!
					$return = $this->result = false;
					$this->errors[] = 'Server returned HTTP 500';
				break;

				case 200:
					$return = $this->result = new SimpleXMLElement( $curl_result );
					// Check if the result set is nil/empty
					if ( count( $return ) == 0 ) {
						$this->errors[] = 'Result set empty';
						$return = false;
					}
				break;

				default:
					$this->errors[] = 'Server returned unexpected HTTP Code (' . $this->status_code . ')';
					$return = false;
			}
		}

		curl_close( $curl_handle );
		return $return;

	}

	public function checkInParticipant($tournament_id, $participant_id) {
		return $this->makeCall( 'tournaments/' . $tournament_id . '/participants/' . $participant_id . '/check_in', array(), 'post' );
	}
}
