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
	protected $aAttsUser;
	protected $aAttsDefault = array(
		'url'                    => ''   ,
		'subdomain'              => ''   ,
		'theme'                  => '1'  ,
		'multiplier'             => '1.0',
		'match_width_multiplier' => '1.0',
		'show_final_results'     => '0'  ,
		'show_standings'         => '0'  ,
		'width'                  => ''   ,
		'height'                 => ''   ,
		'limit'                  => '5'  ,
		'allowusers'             => ''   ,
		'denyusers'              => ''   ,
		'allowroles'             => ''   ,
		'denyroles'              => ''   ,
		'statuses'               => ''   ,
		'excludestatuses'        => ''   ,
		'listparticipants'       => ''   ,
	);
	protected $sCached;
	protected $sExpires;

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
					if ( preg_match( '/^(?:(?:https?\:\/\/)?(?:www\.|([\w\-]+)\.)?challonge\.com\/?)?((?<=\/)\w*|^\w+$)/i', $v, $m ) ) {
						if ( ! empty( $m[1] ) && empty( $atts['subdomain'] ) ) {
							$atts['subdomain'] = $m[1];
						}
						$atts['url'] = $m[2];
					}
					break;
				case 'subdomain' :
					if ( ! empty( $v ) && preg_match( '/^(?:https?\:\/\/)?([\w\-]+)/i', $v, $m ) )
						$atts['subdomain'] = $m[1];
					break;
				case 'theme' :
					if ( $v ) {
						$atts['theme'] = (int) $v;
					} else {
						$atts['theme'] = $this->aAttsDefault['theme'];
					}
					break;
				case 'multiplier' :
					if ( 0.5 <= $v && 1.4 >= $v ) {
						$atts['multiplier'] = (string) round( $v, 1 );
					} else {
						$atts['multiplier'] = $this->aAttsDefault['multiplier'];
					}
					break;
				case 'match_width_multiplier' :
					if ( 0.8 <= $v && 2 >= $v ) {
						$atts['match_width_multiplier'] = (string) round( $v, 1 );
					} else {
						$atts['match_width_multiplier'] = $this->aAttsDefault['match_width_multiplier'];
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
				case 'limit' :
					if ( 0 < $v ) {
						$atts['limit'] = (int) $v;
					} else {
						$atts['limit'] = $this->aAttsDefault['limit'];
					}
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
				case 'statuses' :
					$statuses = explode( ',', $v );
					$atts['statuses'] = array();
					foreach ( $statuses AS $status ) {
						$status = trim($status);
						if ( ! empty( $status ) ) {
							$atts['statuses'][] = str_replace(' ','_',strtolower($status));
						}
					}
					break;
				case 'excludestatuses' :
					$statuses = explode( ',', $v );
					$atts['excludestatuses'] = array();
					foreach ( $statuses AS $status ) {
						$status = trim($status);
						if ( ! empty( $status ) ) {
							$atts['excludestatuses'][] = str_replace(' ','_',strtolower($status));
						}
					}
					break;
				case 'listparticipants' :
					$atts['listparticipants'] = $this->isTrue( $v );
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

		// Current user and plugin options
		$usr = wp_get_current_user();
		$options = $this->oCP->getOptions();

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
		if ( ( is_user_logged_in() && ! current_user_can( 'challonge_view' ) ) || ( ! is_user_logged_in() && empty( $options['public_shortcode'] ) ) ) {
			if ( ! is_user_logged_in() ) {
				$loginmessage = '<br />' . __( 'Please login to view this tournament.', Challonge_Plugin::TEXT_DOMAIN );
			} else {
				$loginmessage = '';
			}
			return '<div class="challonge-embed challonge-denied"'
				. $css . '>'
					. '<div class="challonge-denied-message">'
						. '<div class="challonge-denied-message-inner">'
							. '<div class="challonge-denied-message-title">'
								/* translators:
									The phrase "sorry bro" should not be translated literally.
								*/
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
			. '<div class="challonge-loading" title="' . sprintf(
				/* translators:
					%s is the name of the third-party website this plugin interfaces with (hint: it will always be "Challonge.com")
				*/
				esc_attr__( 'Loading %s tournament...', Challonge_Plugin::TEXT_DOMAIN ),
				Challonge_Plugin::THIRD_PARTY
			) . '"></div>'
			. '</div>'
			. '<script>Challonge_jQuery(document).ready(function(){'
			. 'Challonge_jQuery(\'#challonge_embed_' . $id . '\').challonge(\'' . $url . '\',' . $jsobj . ');'
			. '});</script>';
	}

	public function listTournaments()
	{
		// Current user and plugin options
		$usr = wp_get_current_user();
		$options = $this->oCP->getOptions();

		// Denied?
		if ( ( ! empty( $usr->ID ) && ! current_user_can( 'challonge_view' ) ) || ( empty( $usr->ID ) && empty( $options['public_shortcode'] ) ) ) {
			return '<p><em>' . __( '(no tournaments)', Challonge_Plugin::TEXT_DOMAIN ) . '</em></p>';
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
			$t = $this->oApi->fromCache()->getTournaments();
		} else {
			$t = $this->oApi->fromCache()->getTournaments( array( 'subdomain' => $atts[ 'subdomain' ] ) );
		}
		$this->sCached = $this->oApi->getCacheDate();
		$this->sExpires = $this->oApi->getCacheExpireDate();
		$tournys = array();
		if ( ! empty( $t ) ) {
			if ( count( $t->tournament ) ) {
				$ajaxurl = admin_url( 'admin-ajax.php' );
				$tbw = 750; // ThinkBox Width
				$tbh = 550; // ThinkBox Height
				$lnk_url = $ajaxurl . '?action=challonge_widget&amp;width=' . $tbw . '&amp;height=' . $tbh;
				foreach ( $t->tournament AS $tourny ) {
					if (
						( 'false' == $tourny->private || $options[ 'public_ignore_exclusion' ] )
						&& ( empty( $atts[ 'statuses' ] )
							|| in_array( strtolower( $tourny->state ), $atts[ 'statuses' ] ) )
						&& ( empty( $atts[ 'excludestatuses' ] )
							|| ! in_array( strtolower( $tourny->state ), $atts[ 'excludestatuses' ] ) )
					) {
						$cells = array();
						foreach ( $options['headers_shortcode'] AS $v ) {
							if ( ! $v['show'] ) {
								continue;
							}
							$cell = null;
							switch ( $v['prop'] ) {
								case 'name' :
									$cell = esc_html( $tourny->name );
									if ( 'text' != $v['format'] ) {
										if ( strlen( $tourny->subdomain ) ) {
											$lnk_tourny = $tourny->subdomain . '-' . $tourny->url;
											$ext_url = 'http://' . $tourny->subdomain . '.challonge.com/' . $tourny->url;
										} else {
											$lnk_tourny = $tourny->url;
											$ext_url = 'http://challonge.com/' . $tourny->url;
										}
										switch ( $v['format'] ) {
											case 'link'           :
												$cell = '<a href="' . $ext_url . '" class="challonge-tournyid-'
													. esc_attr( $lnk_tourny ) . '">' . $cell . '</a>';
												break;
											case 'link_new'       :
												$cell = '<a href="' . $ext_url . '" class="challonge-tournyid-'
													. esc_attr( $lnk_tourny ) . '" target="_blank">' . $cell . '</a>';
												break;
											case 'link_modal'     :
												$cell = '<a href="'
													. $lnk_url . '&amp;lnk_tourny=' . esc_attr( $lnk_tourny )
													. '&amp;lnk_action=view&amp;n=1" class="challonge-tournyid-'
													. esc_attr( $lnk_tourny ) . ' thickbox" title="'
													. esc_attr( $tourny->name ) . '">'
													. $cell . '</a>';
												break;
											case 'link_modal_full':
											default:
												$cell = '<a href="'
													. $lnk_url . '&amp;lnk_tourny=' . esc_attr( $lnk_tourny )
													. '&amp;lnk_action=view" class="challonge-tournyid-'
													. esc_attr( $lnk_tourny ) . ' thickbox" title="'
													. esc_attr( $tourny->name ) . '">'
													. $cell . '</a>';
												break;
										}
									}
									break;
								case 'type' :
									if ( 'full' == $v['format'] ) {
										$cell = esc_html(
											ucwords( $tourny->{ 'tournament-type' } )
										);
									} else {
										$cell = esc_html(
											preg_replace( // eg. Swiss --> Sw, Single Elimination --> SE
												'/^(?:([A-Z]).*([A-Z]).*|([A-Z][a-z]).*)$/',
												'\1\2\3',
												ucwords( $tourny->{ 'tournament-type' } )
											)
										);
									}
									break;
								case 'participants' :
									$count = (int) $tourny->{ 'participants-count' };
									if ( 0 < $tourny->{ 'signup-cap' } ) {
										$cap = (int) $tourny->{ 'signup-cap' };
									} else {
										$cap = '&infin;';
									}
									switch ( $v['format'] ) {
										case 'p_of_t'     :
											$cell = $count . ' of ' . $cap;
											break;
										case 'p_slash_t'  :
											$cell = $count . '/' . $cap;
											break;
										case 'p'          :
										default           :
											$cell = $count;
											break;
									}
									break;
								case 'created' :
									switch ( $v['format'] ) {
										case 'date_time':
											$cell = date_i18n(
												get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
												strtotime( $tourny->{ 'created-at' } )
													+ ( get_option( 'gmt_offset' ) * 3600 )
											);
											break;
										case 'time_diff':
											$cell = sprintf(
												__( '%s ago', Challonge_Plugin::TEXT_DOMAIN ),
												human_time_diff(
													strtotime( $tourny->{ 'created-at' } )
													+ ( get_option( 'gmt_offset' ) * 3600 )
												)
											);
											break;
										case 'date':
										default:
											$cell = date_i18n(
												get_option( 'date_format' ),
												strtotime( $tourny->{ 'created-at' } )
													+ ( get_option( 'gmt_offset' ) * 3600 )
											);
											break;
									}
									break;
								case 'progress' :
									switch ( $v['format'] ) {
										case 'text':
											$cell = esc_html( $tourny->{ 'progress-meter' } ) . '%';
											break;
										case 'bar':
										default:
											if ( 100 == $tourny->{ 'progress-meter' } ) {
												$cell = __( 'Done', Challonge_Plugin::TEXT_DOMAIN );
											} else {
												$cell = '<progress value="'
													. esc_attr( $tourny->{ 'progress-meter' } )
													. '" max="100"></progress>';
											}
											break;
									}
									break;
								case 'checkin' :
									$cell = human_time_diff(
										time() + ( $tourny->{ 'check-in-duration' } * 60 )
									);
									break;
								case 'description':
									switch ( $v['format'] ) {
										case 'full':
											$cell = esc_html( strip_tags( $tourny->description ) );
											break;
										case 'full_html':
											$cell = $tourny->description;
											break;
										case 'line':
										default:
											$cell = explode( "</p>", $tourny->description, 2 );
											$cell = esc_html( strip_tags( $cell[0] ) );
											break;
									}
									break;
								case 'game'       :
									$cell = esc_html( $tourny->{ 'game-name' } );
									break;
								case 'quick'      :
									$cell = $tourny->{ 'quick-advance' };
									switch ( $v['format'] ) {
										case 'yes_no':
											if ( 'false' == $tourny->{ 'quick-advance' } ) {
												$cell = 'Yes';
											} else {
												$cell = 'No';
											}
											break;
										case 'on_off':
											if ( 'false' == $tourny->{ 'quick-advance' } ) {
												$cell = 'On';
											} else {
												$cell = 'Off';
											}
											break;
										case 'check':
										default:
											if ( 'false' == $tourny->{ 'quick-advance' } ) {
												$cell = '&nbsp;';
											} else {
												$cell = '<span class="dashicons dashicons-yes"></span>';
											}
											break;
									}
									break;
								case 'start'      :
									if ( ! empty( $tourny->{ 'start-at' } ) ) {
										switch ( $v['format'] ) {
											case 'date':
												$cell = date_i18n(
													get_option( 'date_format' ),
													strtotime( $tourny->{ 'start-at' } )
														+ ( get_option( 'gmt_offset' ) * 3600 )
												);
												break;
											case 'time_diff':
												$cell = sprintf(
													__( '%s ago', Challonge_Plugin::TEXT_DOMAIN ),
													human_time_diff(
														strtotime( $tourny->{ 'start-at' } )
														+ ( get_option( 'gmt_offset' ) * 3600 )
													)
												);
												break;
											case 'date_time':
											default:
												$cell = date_i18n(
													get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
													strtotime( $tourny->{ 'start-at' } )
														+ ( get_option( 'gmt_offset' ) * 3600 )
												);
												break;
										}
									} else {
										$cell = '&nbsp;';
									}
									break;
								case 'started'    :
									if ( ! empty( $tourny->{ 'started-at' } ) ) {
										switch ( $v['format'] ) {
											case 'date':
												$cell = date_i18n(
													get_option( 'date_format' ),
													strtotime( $tourny->{ 'started-at' } )
														+ ( get_option( 'gmt_offset' ) * 3600 )
												);
												break;
											case 'date_time':
												$cell = date_i18n(
													get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
													strtotime( $tourny->{ 'started-at' } )
														+ ( get_option( 'gmt_offset' ) * 3600 )
												);
												break;
											case 'time_diff':
											default:
												$cell = sprintf(
													__( '%s ago', Challonge_Plugin::TEXT_DOMAIN ),
													human_time_diff(
														strtotime( $tourny->{ 'started-at' } )
														+ ( get_option( 'gmt_offset' ) * 3600 )
													)
												);
												break;
										}
									} else {
										$cell = '&nbsp;';
									}
									break;
								case 'state'      :
									$cell = esc_html( ucwords( str_replace( '_', ' ', $tourny->state ) ) );
									break;
								case 'signup'      :
									$cell = '';
									if ( strlen( $tourny->subdomain ) )
										$tname = (string) $tourny->subdomain . '-' . $tourny->url;
									else
										$tname = (string) $tourny->url;
									$lnk = $this->oCP->widgetTournyLink( $tname );
									if ( ! empty( $lnk->name ) ) {
										$cell = $lnk->button_html;
									}
									break;
								default:
									throw new Exception('Unexpected or missing property name in headers_shortcode option list.');
									break;
							}
							if ( null !== $cell ) {
								$cells[] = '<td class="challonge-' . $v['prop'] . '">' . $cell . '</td>';
							}
						}
						$tournys[ $tourny->{ 'created-at' } . $tourny->id ] = '<tr>' . implode( '', $cells ) . '</tr>';
					}
				}
			}
		} else {
			return '<p><em>' . __( 'Sorry, the tournament listing is unavailable. Please try again later.', Challonge_Plugin::TEXT_DOMAIN ) . '</em></p>';
		}
		if ( empty( $tournys ) ) {
			return '<p><em>' . __( '(no tournaments)', Challonge_Plugin::TEXT_DOMAIN ) . '</em></p>';
		} else {
			add_thickbox();
			ksort( $tournys );
			$cells = array();
			foreach ( $options['headers_shortcode'] AS $v ) {
				if ( $v['show'] ) {
					$cells[] = '<th class="challonge-' . $v['prop'] . '">' . esc_html( $v['alias'] ?: $v['name'] ) . '</th>';
				}
			}
			$atts_val = base64_encode( gzcompress( json_encode( $this->aAttsUser ) ) );
			return '<table class="challonge-table"><thead><tr>'
					. implode( '', $cells )
				. '</tr></thead><tbody>'
				    . implode( '', array_slice( array_reverse( $tournys ), 0, $atts['limit'] ) )
				. '</tbody>'
				. '<tfoot' . ( $options['caching_freshness'] ? '' : ' class="challonge-hide-freshness"' ) . '><tr><td colspan="' . count( $cells ) . '">'
					. '<time datetime="' . $this->sCached . '" data-expires="' . $this->sExpires . '"'
						. ' data-atts="' . $atts_val . '" class="challonge-freshness' . ( $options['caching_freshness'] ? '' : ' challonge-hide-freshness' ) . ' dashicons-before dashicons-update">'
						. 'about '
						. human_time_diff( (new DateTime( $this->sCached ))->getTimestamp(), (new DateTime)->getTimestamp())
						. ' ago'
					. '</time>'
				. '</td></tr></tfoot>'
				. '</table>';
		}
	}

	public function listParticipants()
	{
		// Current user and plugin options
		$usr = $this->oCP->getUser();
		$options = $this->oCP->getOptions();

		// Denied?
		if ( ( ! empty( $usr->ID ) && ! current_user_can( 'challonge_view' ) ) || ( empty( $usr->ID ) && empty( $options['public_shortcode'] ) ) ) {
			return '<p><em>' . __( '(no participants)', Challonge_Plugin::TEXT_DOMAIN ) . '</em></p>';
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
			$tournyId = $atts[ 'url' ];
		} else {
			$tournyId = $atts[ 'subdomain' ] . '-' . $atts[ 'url' ];
		}
		$tourny = $this->oApi->fromCache()->getTournament( $tournyId, array(
			'include_participants' => 1,
			'include_matches'      => 1,
		) );
		$this->sCached = $this->oApi->getCacheDate();
		$this->sExpires = $this->oApi->getCacheExpireDate();
		// echo'<pre>';print_r($tourny);echo'</pre>';
		if ( empty( $tourny ) ) {
			return '<p><em>' . __( '(no participants)', Challonge_Plugin::TEXT_DOMAIN ) . '</em></p>';
		}

		// User key hash
		if ( is_user_logged_in() && current_user_can( 'challonge_signup' ) )
			$usrkey = md5( $tourny->url . ' ' . $usr->user_login . ' <' . $usr->user_email . '>' ); // Highlight self
		else
			$usrkey = false; // Highlight none

		$users = array();
		foreach ( $tourny->participants->participant AS $v ) {
			// echo'<pre>';print_r($v);echo'</pre>';
			$user = array();
			$user['id'] = $v->id;
			$user['seed'] = $v->seed;
			$user['name'] = esc_html( $v->name );
			if ( ! empty( $v->{'final-rank'} ) ) {
				$user['rank'] = $v->{'final-rank'};
			}
			$user['score'] = 0; // next foreach loop adds to this
			$user['misc'] = $v->misc;
			$users[(string) $v->id] = $user;
		}

		foreach ( $tourny->matches->match AS $v ) {
			// echo'<pre>';print_r($v);echo'</pre>';
			if ( 'complete' == $v->state ) {
				$score = explode( '-', $v->{'scores-csv'} );
				$users[(string) $v->{'player1-id'}]['score'] += $score[0];
				$users[(string) $v->{'player2-id'}]['score'] += $score[1];
			}
		}

		$list = array();
		foreach ( $users AS $v ) {
			// echo'<pre>';print_r($v);echo'</pre>';
			if ( $usrkey && strpos( $v['misc'], $usrkey ) === 0 ) {
				$v['name'] = '<strong>' . $v['name'] . '</strong>';
			}
			$num = isset( $v['rank'] ) ? $v['rank'] : $v['seed'];
			$list[ $num . '__' . $v['id'] ] = '<tr>'
					. '<td class="challonge-rank">' . $num . '</td>'
					. '<td class="challonge-name">' . $v['name'] . '</td>'
					. '<td class="challonge-points">' . $v['score'] . '</td>'
				. '</tr>';
		}
		ksort($list);
		$atts_val = base64_encode( gzcompress( json_encode( $this->aAttsUser ) ) );
		return '<table class="challonge-table">'
				. '<thead><tr>'
					. '<th class="challonge-rank">' . __( 'Rank', Challonge_Plugin::TEXT_DOMAIN ) . '</th>'
					. '<th class="challonge-name">' . __( 'Participant', Challonge_Plugin::TEXT_DOMAIN ) . '</th>'
					. '<th class="challonge-points">' . __( 'Pts', Challonge_Plugin::TEXT_DOMAIN ) . '</th>'
				. '</tr></thead>'
				. '<tbody>' . implode( '', $list ) . '</tbody>'
				. '<tfoot' . ( $options['caching_freshness'] ? '' : ' class="challonge-hide-freshness"' ) . '><tr><td colspan="3">'
					. '<time datetime="' . $this->sCached . '" data-expires="' . $this->sExpires . '"'
						. ' data-atts="' . $atts_val . '" class="challonge-freshness' . ( $options['caching_freshness'] ? '' : ' challonge-hide-freshness' ) . ' dashicons-before dashicons-update">'
						. 'about '
						. human_time_diff( (new DateTime( $this->sCached ))->getTimestamp(), (new DateTime)->getTimestamp())
						. ' ago'
					. '</time>'
				. '</td></tr></tfoot>'
			. '</table>';
	}

	public function shortCode( $atts )
	{
		// Attribute filtering and validation...
		$this->aAttsUser = $atts;
		$this->aAtts = shortcode_atts( $this->aAttsDefault, $this->aAttsUser );
		$this->validateAtts();

		if ( ! empty( $this->aAtts['url'] ) ) {
			if ( empty( $this->aAtts['listparticipants'] ) ) {
				// Display a tournament
				$html = $this->embedModule();
			} else {
				// Display tournament participants
				$html = $this->listParticipants();
			}
		} else {
			// Tournament listing
			$html = $this->listTournaments();
		}
		if ( null !== $this->oApi ) {
			return '<div class="challonge-shortcode-content">'
					. $html
				. '</div>';
		}
		return $html;
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
