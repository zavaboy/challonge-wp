<?php
/**
 * @package Challonge
 */

// Exit on direct request.
defined( 'ABSPATH' ) OR exit;

class Challonge_Shortcode
{
	protected $oCP;
	protected $oApi;
	protected $nShortCodeId = 0;
	protected $aAtts;
	protected $aAttsDefault = array(
		'url'					=> ''	,
		'subdomain'				=> ''	,
		'theme'					=> '1'	,
		'multiplier'			=> '1.0',
		'match_width_multiplier'=> '1.0',
		'show_final_results'	=> '0'	,
		'show_standings'		=> '0'	,
		'width'					=> ''	,
		'height'				=> ''	,
		'allowusers'			=> ''	,
		'denyusers'				=> ''	,
		'allowroles'			=> ''	,
		'denyroles'				=> ''	,
	);

	public function __construct()
	{
		$this->oCP = Challonge_Plugin::getInstance();
	}

	public function validateAtts()
	{
		$atts = $this->aAtts;
		foreach ( $atts AS $k => $v ) {
			switch ( $k ) {
				case 'url' :
					if ( preg_match( '/^(?:\w+\:)?(?:(\w+)\.)?challonge\.com\/(\w+)(?:\/.*)?$/i', strtolower( $v ), $m ) ) {
						if ( ! empty( $m[1] ) && empty( $atts['subdomain'] ) ) {
							$atts['subdomain'] = $m[1];
						}
						if ( ! empty( $m[2] ) ) {
							$atts['url'] = $m[2];
						}
					}
					break;
				case 'subdomain' :
					if ( ! empty( $v ) )
						$atts['subdomain'] = strtolower( preg_replace( array( '/^(?:.*\W)?(\w+)\.challonge\.com.*$/i', '/[\W]+/' ), array( '$1', '' ), $v ) );
					break;
				case 'theme' :
					if ( $v ) {
						$atts['theme'] = (int) $v;
					} else {
						$atts['theme'] = '1';
					}
					break;
				case 'multiplier' :
					if ( 0.5 <= $v && 1.4 >= $v ) {
						$atts['multiplier'] = (string) round( $v, 1 );
					} else {
						$atts['multiplier'] = '1.0';
					}
					break;
				case 'match_width_multiplier' :
					if ( 0.8 <= $v && 2 >= $v ) {
						$atts['match_width_multiplier'] = (string) round( $v, 1 );
					} else {
						$atts['match_width_multiplier'] = '1.0';
					}
					break;
				case 'show_final_results' :
					$atts['show_final_results'] = $this->isTrue( $v );
					break;
				case 'show_standings' :
					$atts['show_standings'] = $this->isTrue( $v );
					break;
				case 'width' :
					$atts['width'] = $this->toCssUnit( $v );
					break;
				case 'height' :
					$atts['height'] = $this->toCssUnit( $v );
					break;
				case 'allowusers' :
					$users = explode( ',', $v );
					$atts['allowusers'] = array();
					foreach ( $users AS $user ) {
						$user = trim( $user );
						if ( ! empty( $user ) ) {
							$atts['allowusers'][] = strtolower( $user );
						}
					}
					break;
				case 'denyusers' :
					$users = explode( ',', $v );
					$atts['denyusers'] = array();
					foreach ( $users AS $user ) {
						$user = trim( $user );
						if ( ! empty( $user ) ) {
							$atts['denyusers'][] = strtolower( $user );
						}
					}
					break;
				case 'allowroles' :
					$roles = explode( ',', $v );
					$atts['allowroles'] = array();
					foreach ( $roles AS $role ) {
						$role = trim( $role );
						if ( ! empty( $role ) ) {
							$atts['allowroles'][] = strtolower( $role );
						}
					}
					break;
				case 'denyroles' :
					$roles = explode( ',', $v );
					$atts['denyroles'] = array();
					foreach ( $roles AS $role ) {
						$role = trim( $role );
						if ( ! empty( $role ) ) {
							$atts['denyroles'][] = strtolower( $role );
						}
					}
					break;
				default:
					// This shouldn't happen! If it does, do nothing.
			}
		}
		$this->aAtts = $atts;
	}

	public function embedModule()
	{
		// Attributes
		$atts = $this->aAtts;

		// Current user
		$usr = wp_get_current_user();

		// Width/Height CSS
		$css = '';
		if ( ! empty( $atts['width'] ) ) {
			$css .= 'width:' . $atts['width'] . ';';
		}
		if ( ! empty( $atts['height'] ) ) {
			$css .= 'height:' . $atts['height'] . ';';
		}
		if ( ! empty( $css ) ) {
			$css = ' style="' . $css . '"';
		}

		// Denied?
		if ( ! current_user_can( 'challonge_view' ) || ( empty( $usr ) && empty( $this->oCP->aOptions['public_shortcode'] ) ) ) {
			if ( empty( $usr ) ) {
				$loginmessage = '<br />' . __( 'Please login to view this tournament.', Challonge_Plugin::TEXT_DOMAIN );
			} else {
				$loginmessage = '';
			}
			return '<div class="challonge-embed challonge-denied"'
				. $css . '>'
					. '<div class="challonge-denied-message">'
						. '<div class="challonge-denied-message-inner">'
							. '<div class="challonge-denied-message-title">'
								. __( 'Sorry bro...', Challonge_Plugin::TEXT_DOMAIN )
							. '</div>'
							. '<div class="challonge-denied-message-description">'
								. __( 'You do not have permission to view this tournament.', Challonge_Plugin::TEXT_DOMAIN )
								. $loginmessage
							. '</div>'
						. '</div>'
					. '</div>'
				. '</div>';
		}

		// Tournament URL (the bit after "challonge.com/")
		$url = $atts['url'];

		// Build Challonge Module Options JS object
		$jsobj = array();
		$mop = array( // Challonge Module Options
			//'url'					, // not to be included in jsobj
			'subdomain'				,
			'theme'					,
			'multiplier'			,
			'match_width_multiplier',
			'show_final_results'	,
			'show_standings'		,
		);
		foreach ( $mop AS $op ) {
			$jsobj[] = "$op:'$atts[$op]'";
		}
		$jsobj = '{' . implode(',', $jsobj) . '}';

		// Make a unique ID
		$id = ( ++ $this->nShortCodeId ) . '_' . substr( md5( 'lemons...' . microtime() . serialize( $atts ) ), 0, 6 );

		// The result
		return '<div id="challonge_embed_' . $id . '" class="challonge-embed"'
			. $css . '>'
			. '<div class="challonge-loading" title="' . __( 'Loading Challonge tournament...', Challonge_Plugin::TEXT_DOMAIN ) . '"></div>'
			. '</div>'
			. '<script>jQuery(document).ready(function(){'
			. 'jQuery(\'#challonge_embed_' . $id . '\').challonge(\'' . $url . '\',' . $jsobj . ');'
			. '});</script>';
	}

	public function listTournaments()
	{
		// Denied?
		if ( ! current_user_can( 'challonge_view' ) ) {
			return '<p><em>(' . __( 'no tournaments', Challonge_Plugin::TEXT_DOMAIN ) . ')</em></p>';
		}

		// No API Key?
		if ( ! $this->oCP->hasApiKey() ) {
			if ( current_user_can( 'manage_options' ) ) {
				return '<p class="challonge-error">' . __( 'No API Key!', Challonge_Plugin::TEXT_DOMAIN ) . ' <a href="'
					. admin_url( 'options-general.php?page=challonge-settings' ) . '">'
					. __( 'Set one.', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>';
			}
			return '<p class="challonge-error">' . __( 'No API Key!', Challonge_Plugin::TEXT_DOMAIN ) . '</p>';
		}

		// API and Attributes
		$this->oApi = $this->oCP->getApi();
		$atts = $this->aAtts;

		// Get all tournaments
		if ( empty( $atts[ 'subdomain' ] ) ) {
			$t = $this->oApi->getTournaments();
		} else {
			$t = $this->oApi->getTournaments( array( 'subdomain' => $atts[ 'subdomain' ] ) );
		}
		$html = '';
		if ( count( $t->tournament ) ) {
			$html .= '<table class="challonge-table"><thead><tr>'
				. '<th class="challonge-name">'         . __( 'Name'        , Challonge_Plugin::TEXT_DOMAIN ) . '</th>'
				. '<th class="challonge-type">'         . __( 'Type'        , Challonge_Plugin::TEXT_DOMAIN ) . '</th>'
				. '<th class="challonge-participants">' . __( 'Participants', Challonge_Plugin::TEXT_DOMAIN ) . '</th>'
				. '<th class="challonge-created">'      . __( 'Created On'  , Challonge_Plugin::TEXT_DOMAIN ) . '</th>'
				. '<th class="challonge-progress">'     . __( 'Progress'    , Challonge_Plugin::TEXT_DOMAIN ) . '</th>'
				. '</tr></thead><tbody>';
			$tournys = array();
			foreach ( $t->tournament AS $tourny ) {
				if ( 100 == $tourny->{ 'progress-meter' } ) {
					$progress = __( 'Done', Challonge_Plugin::TEXT_DOMAIN );
				} else {
					$progress = '<progress value="'
						. esc_attr( $tourny->{ 'progress-meter' } )
						. '" max="100"></progress>';
				}
				if ( 'false' == $tourny->{'private'} ) {
					$tournys[] = '<tr>'

						. '<td class="challonge-name">'
						. '<a href="' . $tourny->{ 'full-challonge-url' } . '">'
							. esc_html( $tourny->name )
						. '</a>'
						. '</td>'

						. '<td class="challonge-type">'
						. esc_html(
								preg_replace( // eg. Swiss --> Sw, Single Elimination --> SE
									'/^(?:([A-Z]).*([A-Z]).*|([A-Z][a-z]).*)$/',
									'\1\2\3',
									ucwords( $tourny->{ 'tournament-type' } )
								)
							)
						. '</td>'

						. '<td class="challonge-participants">'
						. esc_html(
								$tourny->{ 'participants-count' }
							)
						. '</td>'

						. '<td class="challonge-created">'
						. date_i18n(
								get_option( 'date_format' ),
								strtotime( $tourny->{ 'created-at' } )
							)
						. '</td>'

						. '<td class="challonge-progress">'
						. $progress
						. '</td>'

						. '</tr>';
				}
			}
			$html .= implode( '', array_slice( array_reverse( $tournys ), 0, 5 ) );
			$html .= '</tbody></table>';
		} else {
			$html .= '<p><em>(' . __( 'no tournaments', Challonge_Plugin::TEXT_DOMAIN ) . ')</em></p>';
		}
		return $html;
	}

	public function shortCode( $atts )
	{
		// Attribute filtering and validation...
		$this->aAtts = shortcode_atts( $this->aAttsDefault, $atts );
		$this->validateAtts();

		if ( ! empty( $this->aAtts['url'] ) ) {
			// Display a tournament
			return $this->embedModule();
		} else {
			// Tournament listing
			return $this->listTournaments();
		}
	}

	private function isTrue( $val )
	{
		$val = strtolower( trim( $val ) );
		if ( in_array( $val, array( 'yes', 'true', '1', 'y', 't' ) ) ) {
			return true;
		} elseif ( in_array( $val, array( 'no', 'false', '0', 'n', 'f' ) ) ) {
			return false;
		} else {
			return null;
		}
	}

	private function toCssUnit( $val )
	{
		$val = strtolower( trim( $val ) );
		if ( preg_match( '/^((\d*\.)?\d+)(%|in|cm|mm|em|ex|pt|pc|px)?$/i' , $val ) ) {
			if ( is_numeric( $val ) ) {
				return $val.'px';
			} else {
				return $val;
			}
		} else {
			return '';
		}
	}
}
