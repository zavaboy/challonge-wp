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
	protected $bUseCached = null;
	protected $oCacheDate = null;
	protected $oCacheExpireDate = null;
	public function __construct( $api_key = '' ) {
		$this->oCP = Challonge_Plugin::getInstance();
		$this->aOptions = $this->oCP->getOptions();
		$this->api_key = $api_key;
	}

	// This is designed to be chained.
	//   e.g.
	//    $t = $oApi->fromCache()->getTournament(...);
	public function fromCache() {
		$this->bUseCached = true;
		return $this;
	}

	public function getCacheDate() {
		if ( null !== $this->oCacheDate )
			return $this->oCacheDate->format( DateTime::ATOM );
		else
			return null;
	}

	public function getCacheExpireDate( $response = null ) {
		// TODO: Improve adaptive caching by looking at other variables, like tournament start time
		if ( null === $this->oCacheExpireDate ) {
			if ( null === $this->oCacheDate ) {
				return null;
			}
			$options = $this->oCP->getOptions();
			if ( empty( $options['caching_adaptive'] ) ) {
				$cacheTime = $options['caching'] ? $options['caching'] : WEEK_IN_SECONDS;
			} else {
				$cacheTime = WEEK_IN_SECONDS;
				if ( null !== $response && $response instanceof SimpleXMLElement ) {
					$state_list = array();
					if ( isset( $response->state ) ) {
						$state_list[] = (string) $response->state;
					} else if ( isset( $response->tournament ) ) {
						foreach ( $response->tournament AS $tourny ) {
							$state_list[] = (string) $tourny->state;
						}
					}
					if ( count( $state_list ) ) {
						foreach ($state_list as $state) {
							switch ( (string) $state ) {
								case 'pending'         : $ct = MINUTE_IN_SECONDS * 5 ; break;
								case 'checking_in'     : $ct = MINUTE_IN_SECONDS * 2 ; break;
								case 'checked_in'      : $ct = MINUTE_IN_SECONDS * 2 ; break;
								case 'underway'        : $ct = MINUTE_IN_SECONDS / 2 ; break;
								case 'awaiting_review' : $ct = MINUTE_IN_SECONDS * 5 ; break;
								case 'complete'        : $ct =   HOUR_IN_SECONDS     ; break;
								default                : $ct =   WEEK_IN_SECONDS     ; break;
							}
							$cacheTime = min( $ct, $cacheTime );
						}
					}
				}
				if ( WEEK_IN_SECONDS == $cacheTime ) {
					$cacheTime = HOUR_IN_SECONDS / 4;
				}
			}
			$this->oCacheExpireDate = clone $this->oCacheDate;
			$this->oCacheExpireDate->add( new DateInterval( 'PT' . $cacheTime . 'S' ) );
		}
		return $this->oCacheExpireDate
			->format( DateTime::ATOM );
	}

	public function makeCall( $path = '', $params = array(), $method = 'get' ) {
		// Handle caching
		if ( 'get' == $method ) {
			$args = func_get_args();
			$transient = 'challongeapi-' . md5( serialize( $args ) ); // Exactly 45 characters (max for transients)
			$cache = $this->oCP->getCache();
			$this->oCacheDate = isset( $cache[$transient] ) ? new DateTime( $cache[$transient] ) : null;
			if ( ! $this->bUseCached  &&  0 < $this->aOptions['caching'] && ! $this->oCP->isCacheIgnored() && null !== $this->oCacheDate ) {
				$transient_data = get_transient( $transient );
				if ( is_array( $transient_data ) ) {
					$this->oCacheDate       = new DateTime( $transient_data['cache_time' ] );
					$this->oCacheExpireDate = new DateTime( $transient_data['expire_time'] );
					$this->bUseCached = 0 < $this->oCacheExpireDate->diff( new DateTime )->format( '%s' ); // use cache if not expired
				}
			}
			if ( $this->bUseCached && ! $this->oCP->isCacheIgnored() && false !== ( $transient_data = get_transient( $transient ) ) ) {
				if ( is_array( $transient_data ) ) {
					$transient_data = gzuncompress( base64_decode( $transient_data['response'] ) );
				}
				if ( is_string( $transient_data ) && false !== strpos( $transient_data, '<?xml' ) )
					$response = simplexml_load_string( $transient_data );
				else
					$response = $transient_data;
				//echo '[OK:TransFound]'; // For debugging
			} else {
				$transient_data = $response = $this->makeCallAlt( $path, $params, $method );
				if ( $response instanceof SimpleXMLElement ) {
					// Using DOMDocument to minify the XML
					$dom = new DOMDocument( '1.0' );
					$dom->preserveWhiteSpace = false;
					$dom->formatOutput = false;
					$dom->loadXML( $response->asXML() );
					$transient_data = $dom->saveXML();
				}
				$this->oCacheDate = new DateTime;
				$transient_data = array(
					'plugin_ver'  => Challonge_Plugin::VERSION,
					'cache_time'  => $this->getCacheDate(),
					'expire_time' => $this->getCacheExpireDate( $response ),
					'call_args'   => $args,
					'response'    => base64_encode( gzcompress( $transient_data ) ),
				);
				$transient_set = set_transient( $transient, $transient_data, MONTH_IN_SECONDS );
				if ( $transient_set ) {
					$this->oCP->logCache( $transient, $this->oCacheDate );
				} else {
					$this->oCacheDate = null;
					$this->oCacheExpireDate = null;
				}
				//echo $transient_set ? '[OK:TransSet]' : '[ERROR:TransNotSet]'; // For debugging
			}
			$this->bUseCached = false;
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
		$call_url = 'https://api.challonge.com/v1/' . $path . '.xml';

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
				$call_url .= '?' . http_build_query( $params, '', '&' );
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
