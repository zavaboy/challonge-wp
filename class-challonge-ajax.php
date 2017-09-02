<?php
/**
 * @package Challonge
 */

// TODO: Cleanup this class

// Exit on direct request.
defined( 'ABSPATH' ) OR exit;

class Challonge_Ajax
{
	protected $oCP;
	protected $oApi;
	protected $oUsr;
	protected $aOptions;

	protected $oLnk;

	public function __construct() {
		$this->oCP = Challonge_Plugin::getInstance();
	}

	public function widgetTournyLink( $tournyId )
	{
		// Init
		if ( ! $this->oCP->hasApiKey() || empty( $tournyId ) || ! is_string( $tournyId ) ) {
			return false;
		}
		$this->oApi     = $this->oCP->getApi();
		$this->oUsr     = $this->oCP->getUser();
		$this->aOptions = $this->oCP->getOptions();
		$this->oLnk     = new stdClass();
		$tourny = $this->oApi->getTournament( $tournyId, array(
			'include_participants' => 1,
			'include_matches'      => 1,
		) );
		if ( empty( $tourny ) ) {
			return false;
		}
		$this->oLnk->tourny = $tourny;

		// Vars for participants, matches, and our ajax url
		$this->oLnk->participants = $tourny->participants->participant;
		$this->oLnk->participants_count = (int) $tourny->{'participants-count'};
		$this->oLnk->matches = $tourny->matches->match;
		$this->oLnk->ajaxurl = admin_url( 'admin-ajax.php' );
		if ( strlen( $tourny->subdomain ) ) {
			$this->oLnk->lnk_tourny = $tourny->subdomain . '-' . $tourny->url;
		} else {
			$this->oLnk->lnk_tourny = $tourny->url;
		}

		// User key hash
		if ( is_user_logged_in() && current_user_can( 'challonge_signup' ) )
			$this->oLnk->usrkey = md5( $tourny->url . ' ' . $this->oUsr->user_login . ' <' . $this->oUsr->user_email . '>' ); // Shows signup
		elseif ( ! is_user_logged_in() && $this->aOptions['public_widget_signup'] )
			$this->oLnk->usrkey = true; // Shows signup to login
		else
			$this->oLnk->usrkey = false; // Shows nothing

		// Is the user signed up?
		// We will also cheack if all participants have signed up through the plugin while we're at it. :)
		$this->oLnk->all_have_misc = true;
		$this->oLnk->misc = array();
		$this->oLnk->signed_up = $reported_scores = false;
		$this->oLnk->participant_id = -1;
		$this->oLnk->participants_by_id = array();
		$this->oLnk->participant_names = array();
		foreach ( $this->oLnk->participants AS $participant ) {
			$this->oLnk->participants_by_id[ (int) $participant->id ] = $participant;
			$this->oLnk->participant_names[] = $participant->name;
			$pmisc = $this->parseParticipantMisc( $participant->misc );
			if ( empty( $pmisc[0] ) ) {
				$this->oLnk->all_have_misc = false;
			} elseif ( $pmisc[0] === $this->oLnk->usrkey ) { // Fix by sagund07 ~ https://wordpress.org/support/topic/widget-signup-button
				$this->oLnk->signed_up = true;
				$this->oLnk->participant_id = (int) $participant->id;
				$this->oLnk->misc = $pmisc;
				$this->oLnk->reported_scores = ( ! empty( $pmisc[1] ) && in_array( $pmisc[1], array( 'w', 'l', 't' ) ) );
			}
		}

		// Find current match
		$this->oLnk->has_match = false;
		$this->oLnk->opponent_id = -1;
		$this->oLnk->opponent = null;
		if ( $this->oLnk->signed_up && ! $this->oLnk->reported_scores && 'underway' == $tourny->state ) {
			foreach ( $this->oLnk->matches AS $match ) {
				if ( 'open' == $match->state && (
					(int) $match->{'player1-id'} == $this->oLnk->participant_id ||
					(int) $match->{'player2-id'} == $this->oLnk->participant_id
				  ) ) {
					$this->oLnk->has_match = true;
					if ( (int) $match->{'player1-id'} == $this->oLnk->participant_id )
						$opplr = 2;
					else
						$opplr = 1;
					$this->oLnk->opponent_id = (int) $match->{ 'player' . $opplr . '-id' };
					$this->oLnk->opponent = $this->oLnk->participants_by_id[ $this->oLnk->opponent_id ];
					break; // Just break the loop, $match is the correct match we want
				}
			}
		}
		if ( $this->oLnk->has_match )
			$this->oLnk->match = $match;

		// Determine link
		if ( 0 < $tourny->{'signup-cap'} ) {
			$this->oLnk->signup_cap = (int) $tourny->{'signup-cap'};
		} else {
			$this->oLnk->signup_cap = '&infin;';
		}
		$this->oLnk->name = false;
		$this->oLnk->hide_button = false;
		$this->oLnk->tbw = 750; // ThinkBox Width
		$this->oLnk->tbh = 550; // ThinkBox Height
		$this->oLnk->username = $this->getUsernameHtml();
		
		// Build tournament info HTML
		$tournyinfo = array();
		// Tournyinfo: CREATED
		$created = date_i18n( get_option( 'date_format' ), strtotime( $tourny->{'created-at'} ) + ( get_option( 'gmt_offset' ) * 3600 ) );
		$tournyinfo['created'] = '<span title="' . esc_attr( sprintf(
				/* translators:
					%s is the created date in the date format from general settings
				*/
				__( 'Created on %s', Challonge_Plugin::TEXT_DOMAIN ),
				$created
			) ) . '">' . $created . '</span>';
		// Tournyinfo: START
		if ( ! empty( $tourny->{'start-at'} ) ) {
			$start_at = sprintf(
					/* translators:
						%s is a relative amount of time with past and future context (eg. "in 3 weeks" or "34 minutes ago")
					*/
					__( 'Starts %s', Challonge_Plugin::TEXT_DOMAIN ),
					$this->oCP->timeDiff( $tourny->{'start-at'} )
				);
			$tournyinfo['start'] = '<span title="' . esc_attr( $start_at ) . '">'
				. esc_html( $start_at ) . '</span>';
		}
		// Tournyinfo: TYPE
		$tournyinfo['type'] = '<span>' . esc_html( ucwords( $tourny->{'tournament-type'} ) ) . '</span>';
		// Tournyinfo: PARTICIPANTS
		$tournyinfo['participants'] = '<span title="' . esc_attr( sprintf(
				/* translators:
					%1$d is the number of signed uo participants
					%2$s is the participant cap (may be infinity)
				*/
				__( '%1$d participants of %2$s', Challonge_Plugin::TEXT_DOMAIN ),
				$tourny->{'participants-count'},
				$this->oLnk->signup_cap
			) ) . '">' . $this->oLnk->participants_count . '/' . $this->oLnk->signup_cap . '</span>';
		// Tournyinfo together
		$this->oLnk->tournyinfo = '<span class="challonge-tournyname">' . esc_html( $tourny->name ) . '</span><br />'
			. '<span class="challonge-tournyinfo">' . implode( ' &nbsp;&middot;&nbsp; ', $tournyinfo ) . '</span>';

		// Conditions
			//print_r(array($this->oLnk->usrkey,!$this->oLnk->signed_up,'pending' == $tourny->state,'true' == $tourny->{'open-signup'}));exit;
		if ( $this->oLnk->usrkey
			&& ! $this->oLnk->signed_up
			&& 'pending' == $tourny->state
			&& 'true' == $tourny->{'open-signup'}
			)
			$this->getSignup();
		elseif ( $this->oLnk->signed_up
			&& current_user_can( 'challonge_signup' )
			&& ( 'pending' == $tourny->state
				|| ( 'checking_in' == $tourny->state
					&& !empty( $this->oLnk->participants_by_id[ $this->oLnk->participant_id ]->{'checked-in-at'} )
					)
				)
			)
			$this->getForfeit();
		elseif ( $this->oLnk->signed_up
			&& current_user_can( 'challonge_signup' )
			&& 'checking_in' == $tourny->state
			&& empty( $this->oLnk->participants_by_id[ $this->oLnk->participant_id ]->{'checked-in-at'} )
			)
			$this->getCheckIn();
		elseif ( $this->oLnk->signed_up
			&& current_user_can( 'challonge_report_own' )
			&& 'underway' == $tourny->state
			&& 'true' == $tourny->{'allow-participant-match-reporting'}
			&& 'none' != $this->aOptions['scoring']
			)
			$this->getReport();

		$this->oLnk->lnk_url = $this->oLnk->ajaxurl . '?action=challonge_widget&amp;width=%d&amp;height=%d';
		$this->oLnk->title_html = '<a href="' . sprintf( $this->oLnk->lnk_url, 750, 550 ) . '&amp;lnk_tourny=' . esc_attr( $this->oLnk->lnk_tourny )
			. '&amp;lnk_action=view" class="challonge-tournyid-' . esc_attr( $this->oLnk->lnk_tourny ) . ' thickbox" title="' . esc_html( $tourny->name ) . '">'
			. esc_html( $tourny->name ) . '</a>';
		// Content
		if ( $this->oLnk->name ) {
			$this->oLnk->html .= '<p class="challonge-lnkconfirm">';
			if ( $this->oLnk->hide_button )
				$this->oLnk->html .= '<a href="#close" onclick="tb_remove();return false;" class="challonge-cancel">' . __( 'Close', Challonge_Plugin::TEXT_DOMAIN ) . '</a>';
			else
				$this->oLnk->html .= '<a href="#' . $this->oLnk->name . '" data-lnkaction="' . $this->oLnk->name . '" data-lnktourny="' . esc_attr( $this->oLnk->lnk_tourny )
					. '" class="challonge-button challonge-bigbutton challonge-button-' . $this->oLnk->name . '">' . $this->oLnk->button . '</a>'
					. ' &nbsp; '
					. '<a href="#cancel" onclick="tb_remove();return false;" class="challonge-cancel">' . __( 'Cancel', Challonge_Plugin::TEXT_DOMAIN ) . '</a>';
			$this->oLnk->html .= '</p>';
			$this->oLnk->button_html = '<a href="' . sprintf( $this->oLnk->lnk_url, $this->oLnk->tbw, $this->oLnk->tbh ) . '&amp;lnk_tourny=' . esc_attr( $this->oLnk->lnk_tourny )
				. '" class="challonge-button challonge-button-' . $this->oLnk->name . ' challonge-tournyid-' . esc_attr( $this->oLnk->lnk_tourny ) . ' thickbox" title="' . esc_html( $tourny->name ) . '">'
				. $this->oLnk->button . '</a>';
		} else {
			$this->oLnk->html = '<p class="challonge-error">' . __( 'ERROR: No tournament actions available.', Challonge_Plugin::TEXT_DOMAIN ) . '</p>';
			$this->oLnk->button = $button_html = '';
		}
		$this->oLnk->html = '<div class="challonge-container-tb">' . $this->oLnk->html . '</div>';

		return $this->oLnk;

		// return array(
		// 	'tourny'         => $tourny,
		// 	'participants_count'   => $tourny->{'participants-count'},
		// 	'signup_cap'     => $signup_cap,
		// 	'name'           => $name,
		// 	'button'         => $button,
		// 	'html'           => $html,
		// 	'button_html'    => $button_html,
		// 	'title_html'     => $title_html,
		// 	'usrkey'         => $usrkey,
		// 	'misc'           => $misc,
		// 	'participant_id' => $participant_id,
		// 	'match'          => $match,
		// 	'opponent'       => $opponent,
		// 	'username'       => $username,
		// );
	}

	public function getSignup()
	{
		$this->oLnk->name = 'join';
		$this->oLnk->button = __( 'Signup', Challonge_Plugin::TEXT_DOMAIN );
		$this->oLnk->hide_button = true;
		if ( ! is_user_logged_in() ) {
			$this->oLnk->tbw = 300; // ThinkBox Width
			$this->oLnk->tbh = 350; // ThinkBox Height
			$this->oLnk->html = '<p>' . __( 'You must be logged in to sign up to tournaments.', Challonge_Plugin::TEXT_DOMAIN ) . '</p>'
				. wp_login_form(
					array(
						'echo'     => false,
						'redirect' => 'challonge_signup=' . urlencode( $this->oLnk->lnk_tourny ),
						'form_id'  => 'challonge-loginform',
					)
				);
		} elseif ( '&infin;' == $this->oLnk->signup_cap || (int) $this->oLnk->tourny->{'participants-count'} < $this->oLnk->signup_cap ) {
			if ( empty( $this->oLnk->tourny->description ) )
				$this->oLnk->tbh = 300; // ThinkBox Height
			$this->oLnk->html = '<p>' . __( 'Signup to the following tournament?', Challonge_Plugin::TEXT_DOMAIN ) . '</p>'
				. '<div>'
					. $this->oLnk->tournyinfo
				. '</div>'
				. '<div class="challonge-tournydesc">' . $this->oLnk->tourny->description . '</div>'
				. '<p>' . __( 'You will join as:', Challonge_Plugin::TEXT_DOMAIN )
				. '<br /><span class="challonge-playername">' . $this->oLnk->username . '</span></p>';
			if ( ! $this->oLnk->all_have_misc && 'both' == $this->aOptions['scoring'] )
				/* translators:
					%s is the name of the third-party website this plugin interfaces with (hint: it will always be "Challonge.com")
				*/
				$this->oLnk->html .= '<p class="challonge-error">' . sprintf( __( 'Warning: One or more participants in this tournament have not'
					. ' signed up through this website. The host or an authorized %s user must report'
					. ' the score for their matches.', Challonge_Plugin::TEXT_DOMAIN ), Challonge_Plugin::THIRD_PARTY ) . '</p>';
			$this->oLnk->hide_button = false;
		} else {
			$this->oLnk->html = '<p>' . __( 'This tournament is full.', Challonge_Plugin::TEXT_DOMAIN ) . '</p>';
		}
		return true;
	}

	public function getForfeit()
	{
		$this->oLnk->name = 'leave';
		$this->oLnk->button = __( 'Forfeit', Challonge_Plugin::TEXT_DOMAIN );
		$this->oLnk->tbw = 600; // ThinkBox Width
		$this->oLnk->tbh = 200; // ThinkBox Height
		$this->oLnk->html = '<p>' . __( 'Forfeit the following tournament?', Challonge_Plugin::TEXT_DOMAIN ) . '</p>'
			. '<div>'
				. $this->oLnk->tournyinfo
			. '</div>';
		return true;
	}

	public function getCheckIn()
	{
		$this->oLnk->name = 'checkin';
		$this->oLnk->button = __( 'Check In', Challonge_Plugin::TEXT_DOMAIN );
		$this->oLnk->tbw = 600; // ThinkBox Width
		$this->oLnk->tbh = 200; // ThinkBox Height
		$this->oLnk->html = '<p>' . __( 'Check in on the following tournament?', Challonge_Plugin::TEXT_DOMAIN ) . '</p>'
			. '<div>'
				. $this->oLnk->tournyinfo
			. '</div>';
		return true;
	}

	public function getReport()
	{
		$this->oLnk->name = 'report';
		$this->oLnk->button = __( 'Report', Challonge_Plugin::TEXT_DOMAIN );
		$this->oLnk->tbw = 600; // ThinkBox Width
		$this->oLnk->tbh = 250; // ThinkBox Height
		$this->oLnk->hide_button = true;
		// Find opponent
		if ( $this->oLnk->has_match ) {
			$omisc = $this->parseParticipantMisc( $this->oLnk->opponent->misc );
			if ( ! empty( $omisc[0] ) || 'both' != $this->aOptions['scoring'] ) {
				// TODO: Use round labels when available
				$round = (int) $this->oLnk->match->round;
				if ( 'true' != $this->oLnk->tourny->{'quick-advance'} ) {
					$this->oLnk->tbh = 280; // ThinkBox Height
					$scoring_row = '<label for="challonge-report-score">' . __( 'What was your score?', Challonge_Plugin::TEXT_DOMAIN ) . '</label><br />'
						. '<input type="text" id="challonge-report-score" name="challonge_report_score" placeholder="0" />';
					if ( $this->aOptions['scoring_opponent'] || 'any' == $this->aOptions['scoring'] || ( empty( $omisc[0] ) && 'one' == $this->aOptions['scoring'] ) ) {
						$scoring_row = '<tr class="challonge-score"><td colspan="2">'
								. $scoring_row
							. '</td><td colspan="2">'
								. '<label for="challonge-report-opponent-score">' . __( 'What was your opponent\'s score?', Challonge_Plugin::TEXT_DOMAIN ) . '</label><br />'
								. '<input type="text" id="challonge-report-opponent-score" name="challonge_report_opponent_score" placeholder="0" />'
							. '</td></tr>';
					} else {
						$scoring_row = '<tr class="challonge-score"><td colspan="4">'
								. $scoring_row
							. '</td></tr>';
					}
				} else {
					$scoring_row = '';
				}
				$this->oLnk->html = '<p>'
					. sprintf(
						/* translators:
							%1$s is the name of the participant's opponent
							%2$s is the name of the current round (see: "Round %d")
							%3$s is the name of the tournament
						*/
						__( 'Did you win against %1$s in %2$s of %3$s?', Challonge_Plugin::TEXT_DOMAIN ),
							'<strong>' . $this->oLnk->opponent->name . '</strong>',
							'<strong>' . sprintf(
								/* translators:
									%d is a number
								*/
								__( 'Round %d', Challonge_Plugin::TEXT_DOMAIN ),
								$round
							) . '</strong>',
							'<strong>' . $this->oLnk->tourny->name . '</strong>'
						) . '</p>'
					. '<form id="challonge-report">'
						. '<table id="challonge-report-table"><tr class="challonge-wlt">'
							. '<td><label for="challonge-report-win" class="challonge-button challonge-bigbutton challonge-button-win">'
								. '<input type="radio" id="challonge-report-win" name="challonge_report_wl" value="w" /> '
							. __( 'Won', Challonge_Plugin::TEXT_DOMAIN ) . '</label></td>'
							. '<td colspan="2"><label for="challonge-report-lose" class="challonge-button challonge-bigbutton challonge-button-lose">'
								. '<input type="radio" id="challonge-report-lose" name="challonge_report_wl" value="l" /> '
							. __( 'Lost', Challonge_Plugin::TEXT_DOMAIN ) . '</label></td>'
							. ( 'true' != $this->oLnk->tourny->{'quick-advance'} ?
									'<td><label for="challonge-report-tie" class="challonge-button challonge-bigbutton challonge-button-tie">'
									. '<input type="radio" id="challonge-report-tie" name="challonge_report_wl" value="t" /> '
								. __( 'Tied', Challonge_Plugin::TEXT_DOMAIN ) . '</label></td>'
								: '' )
						. '</tr>' . $scoring_row . '</table>'
					. '</form>';
				$this->oLnk->hide_button = false;
			} else {
				/* translators:
					$1$s is the name of the participant's opponent
					%2$s is the name of the third-party website this plugin interfaces with (hint: it will always be "Challonge.com")
				*/
				$this->oLnk->html = '<p>' . sprintf( __( 'Your opponent, %1$s, did not sign up through this website. The host or an authorized %2$s user must report the score.', Challonge_Plugin::TEXT_DOMAIN ), '<strong>' . $this->oLnk->opponent->name . '</strong>', Challonge_Plugin::THIRD_PARTY ) . '</p>';
			}
		} elseif ( $this->oLnk->reported_scores ) {
			$this->oLnk->html = '<p>' . __( 'You have already reported your score for this round. Please wait for the round to end.', Challonge_Plugin::TEXT_DOMAIN ) . '</p>';
		} else {
			$this->oLnk->html = '<p>' . __( 'You are not included in this round.', Challonge_Plugin::TEXT_DOMAIN ) . '</p>';
		}
		return true;
	}

	public function widgetReply()
	{
		$this->oCP->setCacheIgnore( true ); // AJAX requests never should use cached API data

		if ( ! empty( $_REQUEST['type'] ) && ! empty( $_REQUEST['refresh'] ) ) {
			switch ( $_REQUEST['type'] ) {
				case 'widget':
					$this->getWidgetContent( $_REQUEST['refresh'] );
					break;
				case 'shortcode':
					$this->getShortcodeContent( $_REQUEST['refresh'] );
					break;
				default:
					echo 'Unexpected refresh type.';
					break;
			}
			exit;
		}

		// No API Key?
		if ( ! $this->oCP->hasApiKey() ) {
			if ( current_user_can( 'manage_options' ) ) {
				die( '<p class="challonge-error">' . __( 'No API Key!', Challonge_Plugin::TEXT_DOMAIN ) . ' <a href="'
					. admin_url( 'options-general.php?page=challonge-settings' ) . '">'
					. __( 'Set one.', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>' );
			}
			die( '<p class="challonge-error">' . __( 'No API Key!', Challonge_Plugin::TEXT_DOMAIN ) . '</p>' );
		}

		// Variables
		$action = isset( $_REQUEST['lnk_action'] ) ? $_REQUEST['lnk_action'] : '';
		$tournyId = isset( $_REQUEST['lnk_tourny'] ) ? $_REQUEST['lnk_tourny'] : '';

		// Validate and verify tournament
		if ( empty( $tournyId ) )
			die( '<p class="challonge-error">' . __( 'ERROR: No tournament defined.', Challonge_Plugin::TEXT_DOMAIN ) . '</p>' );
		$lnk = $this->widgetTournyLink( $tournyId );
		$tourny = $lnk->tourny;
		if ( empty( $tourny ) )
			die ( '<p class="challonge-error">' . __( 'ERROR: Invalid tournament.', Challonge_Plugin::TEXT_DOMAIN ) . '</p>' );

		// Actions
		switch ( $action ) {
			case 'view':
				if ( is_user_logged_in() || $this->aOptions['public_shortcode'] ) {
					die( 
						'<div id="challonge_embed_tb" class="challonge-embed-tb">'
						/* translators:
							%s is the name of the third-party website this plugin interfaces with (hint: it will always be "Challonge.com")
						*/
						. '<div class="challonge-loading" title="' . sprintf( esc_attr__( 'Loading %s tournament...', Challonge_Plugin::TEXT_DOMAIN ), Challonge_Plugin::THIRD_PARTY ) . '"></div>'
						. '</div>'
						. '<script>Challonge_jQuery(document).ready(function(){'
						. 'var a=Challonge_jQuery(\'#challonge_embed_tb\');'
						. 'a.parent().css({overflow:\'hidden\',padding:1,width:\'auto\'});'
						. 'a.challonge(\'' . $tourny->url . '\',{subdomain:\'' . $tourny->subdomain . '\'});'
						. '});</script>'
					);
				} else {
					die( '<div id="challonge_embed_tb" class="challonge-embed-tb challonge-denied">'
							. '<div class="challonge-denied-message">'
								. '<div class="challonge-denied-message-inner">'
									. '<div class="challonge-denied-message-title">'
										. __( 'Sorry bro...', Challonge_Plugin::TEXT_DOMAIN )
									. '</div>'
									. '<div class="challonge-denied-message-description">'
										. __( 'You do not have permission to view this tournament.', Challonge_Plugin::TEXT_DOMAIN )
										. '<br />' . __( 'Please login to view this tournament.', Challonge_Plugin::TEXT_DOMAIN )
									. '</div>'
								. '</div>'
							. '</div>'
						. '</div>');
				}
			case 'join':
				$joined = false;
				if ( ! empty( $_REQUEST['playername_input'] ) ) {
					$input = stripslashes_deep( $_REQUEST['playername_input'] );
				} else {
					$input = array();
				}
				$username = $this->getUsernameText( $lnk->username, $input );
				if ( ! empty( $username ) ) 
					$joined = $this->oApi->createParticipant( $lnk->tourny->id, array(
						'participant[name]' => $username,
						'participant[misc]' => $lnk->usrkey,
					) );
				if ( $joined ) {
					// Refresh lnk
					$lnk = $this->widgetTournyLink( $tournyId );
					die( '<p class="challonge-ok">'
						/* translators:
							%s is the name of the tournament
						*/
						. sprintf( __( 'You have joined %s.', Challonge_Plugin::TEXT_DOMAIN ), '<strong>' . $lnk->tourny->name . '</strong>' )
						. ' -- <a href="#done" onclick="tb_remove();return false;">' . __( 'Done', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>'
						. '<div class="challonge-metahtml">' . $lnk->button_html . '</div>' );
				} else {
					die( '<p class="challonge-error">'
						/* translators:
							%s is the name of the tournament
						*/
						. sprintf( __( 'ERROR: You did not join %s.', Challonge_Plugin::TEXT_DOMAIN ), '<strong>' . $lnk->tourny->name . '</strong>' )
						. ' -- <a href="#close" onclick="tb_remove();return false;">' . __( 'Close', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>' );
				}
				break;
			case 'leave':
				$deleted = $this->oApi->deleteParticipant( $lnk->tourny->id, $lnk->participant_id );
				if ( $deleted ) {
					// Refresh lnk
					$lnk = $this->widgetTournyLink( $tournyId );
					die( '<p class="challonge-ok">'
						/* translators:
							%s is the name of the tournament
						*/
						. sprintf( __( 'You have forfeited %s.', Challonge_Plugin::TEXT_DOMAIN ), '<strong>' . $lnk->tourny->name . '</strong>' )
						. ' -- <a href="#done" onclick="tb_remove();return false;">' . __( 'Done', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>'
						. '<div class="challonge-metahtml">' . $lnk->button_html . '</div>' );
				} else {
					die( '<p class="challonge-error">'
						/* translators:
							%s is the name of the tournament
						*/
						. sprintf( __( 'ERROR: You did not forfeit %s.', Challonge_Plugin::TEXT_DOMAIN ), '<strong>' . $lnk->tourny->name . '</strong>' )
						. ' -- <a href="#close" onclick="tb_remove();return false;">' . __( 'Close', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>' );
				}
				break;
			case 'checkin':
				$checkedin = $this->oApi->checkInParticipant( $lnk->tourny->id, $lnk->participant_id );
				if ( $checkedin ) {
					// Refresh lnk
					$lnk = $this->widgetTournyLink( $tournyId );
					die( '<p class="challonge-ok">'
						/* translators:
							%s is the name of the tournament
						*/
						. sprintf( __( 'You have been checked into %s.', Challonge_Plugin::TEXT_DOMAIN ), '<strong>' . $lnk->tourny->name . '</strong>' )
						. ' -- <a href="#done" onclick="tb_remove();return false;">' . __( 'Done', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>'
						. '<div class="challonge-metahtml">' . $lnk->button_html . '</div>' );
				} else {
					die( '<p class="challonge-error">'
						/* translators:
							%s is the name of the tournament
						*/
						. sprintf( __( 'ERROR: You were not checked into %s.', Challonge_Plugin::TEXT_DOMAIN ), '<strong>' . $lnk->tourny->name . '</strong>' )
						. ' -- <a href="#close" onclick="tb_remove();return false;">' . __( 'Close', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>' );
				}
				break;
			case 'report':
				$score          = isset( $_REQUEST['report_score']          ) ? (int) $_REQUEST['report_score']          : null;
				$opponent_score = isset( $_REQUEST['report_opponent_score'] ) ? (int) $_REQUEST['report_opponent_score'] : null;
				if ( ! empty( $_REQUEST['report_wl'] ) ) {
					$reported = $this->reportScore( $lnk, $_REQUEST['report_wl'], $score, $opponent_score );
				} else {
					$reported = __( 'Invalid request', Challonge_Plugin::TEXT_DOMAIN );
				}
				if ( 'Reported' == $reported ) {
					// Refresh lnk
					$lnk = $this->widgetTournyLink( $tournyId );
					die( '<p class="challonge-ok">'
						. __( 'You have reported your score.', Challonge_Plugin::TEXT_DOMAIN )
						. ' -- <a href="#done" onclick="tb_remove();return false;">' . __( 'Done', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>'
						. '<div class="challonge-metahtml">' . $lnk->button_html . '</div>' );
				} else {
					die( '<p class="challonge-error">'
						. sprintf( __( 'Your score was not reported. Reason: %s', Challonge_Plugin::TEXT_DOMAIN ), $reported )
						. ' -- <a href="#close" onclick="tb_remove();return false;">' . __( 'Close', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>' );
				}
				break;
			default:
				die( $lnk->html );
		}
	}

	public function reportScore( $lnk, $wl, $score, $opponent_score )
	{
		// Check if scoring is disabled
		if ( 'none' == $this->aOptions['scoring'] )
			return __( 'Scoring disabled', Challonge_Plugin::TEXT_DOMAIN );

		// Validate W/L status
		$wl = strtolower( $wl );
		if ( ! in_array( $wl, array( 'w', 'l', 't' ), true ) ) // is strict
			return __( 'Invalid W/L status', Challonge_Plugin::TEXT_DOMAIN );

		// Type-cast scores to be safe
		$score          = (int) $score;
		$opponent_score = (int) $opponent_score;

		// Validate opponent
		if ( ! empty( $lnk->opponent ) && 'any' != $this->aOptions['scoring'] )
			$omisc = $this->parseParticipantMisc( $lnk->opponent->misc );
		elseif ( 'both' != $this->aOptions['scoring'] )
			$omisc = false;
		else
			return __( 'Invalid opponent', Challonge_Plugin::TEXT_DOMAIN );

		// Validate score agreement is applicable
		if ( false !== $omisc ) {
			if ( ! empty( $omisc[1] ) ) {
				// Opponent W/L disagreement?
				$wlwl = $wl . $omisc[1];
				if ( 'wl' != $wlwl && 'lw' != $wlwl && 'tt' != $wlwl )
					return __( 'Opponent W/L/T disagreement', Challonge_Plugin::TEXT_DOMAIN ); // eg: Oppenent says he/she won and you say you won.
				// Opponent score disagreement?
				if ( ( $this->aOptions['scoring_opponent'] || 'any' == $this->aOptions['scoring'] ) && ( $score != $omisc[3] || $opponent_score != $omisc[2] ) )
					return __( 'Opponent score disagreement', Challonge_Plugin::TEXT_DOMAIN );
				$opponent_score = $omisc[2];
				// Good to go, let's first reset opponent's misc to just his/her usrkey
				$oupdated = $this->oApi->updateParticipant( $lnk->tourny->id, (int) $lnk->opponent->id, array(
					'participant[misc]' => $omisc[0],
				) );
				if ( ! $oupdated )
					return __( 'Unable to clear opponent report', Challonge_Plugin::TEXT_DOMAIN );
			} else {
				// Set participant score, opponent hasn't submitted his/her score yet
				$pupdated = $this->oApi->updateParticipant( $lnk->tourny->id, $lnk->participant_id, array(
					'participant[misc]' => $lnk->usrkey . ',' . $wl . ',' . $score . ',' . $opponent_score,
				) );
				if ( ! $pupdated )
					return __( 'Unable to set your report', Challonge_Plugin::TEXT_DOMAIN );
				return 'Reported';
			}
		}

		// Determine winner participant ID
		if ( 'w' == $wl ) {
			$winner_id = $lnk->participant_id;
		} elseif ( 'l' == $wl ) {
			$winner_id = (int) $lnk->opponent->id;
		} else {
			$winner_id = 'tie';
		}
		// Determine score order
		if ( (int) $lnk->match->{ 'player1-id' } == $lnk->participant_id ) {
			$scores_csv = sprintf( '%d-%d', $score, $opponent_score );
		} else {
			$scores_csv = sprintf( '%d-%d', $opponent_score, $score );
		}
		// Now, let's set the match score
		$mupdated = $this->oApi->updateMatch( $lnk->tourny->id, (int) $lnk->match->id, array(
			'match[scores_csv]' => $scores_csv,
			'match[winner_id]' => $winner_id,
		) );
		if ( ! $mupdated )
			return __( 'Unable to update match', Challonge_Plugin::TEXT_DOMAIN );

		return 'Reported';
	}

	public function parseParticipantMisc( $misc )
	{
		// If $misc is defined:
		//   $misc format is '{usrkey},{w|l|t},{score},{opponent_score}' eg: b9c3cf4e9491aaf72548408eac387e3c,w,12,3
		$default = array( null, null, null, null );
		// Validate
		if ( empty( $misc ) )
			return $default;
		if ( is_array( $misc ) )
			$misc = implode( ',', $misc );
		elseif ( ! is_string( $misc ) )
			$misc = (string) $misc;

		// Parse
		$misc = explode( ',', $misc ); // CSV to array
		// $misc[0] is $usrkey of the participant (always there if participant signed up via widget)
		// $misc[1] is the win/loss/tie reported by the participant, if any
		// $misc[2] is the score reported by the participant, if any
		// $misc[3] is the opponent score reported by the participant, if any

		// Validate array, must be at most 4 values
		if ( count( $misc ) > 4 ) {
			return $default;
		}

		// Validate usrkey (is MD5 hash)
		if ( ! preg_match( '/^[0-9a-f]{32}$/', $misc[0] ) ) // is case-sensitive
			return $default;
		// Validate Win/Loss/Tie status
		if ( empty( $misc[1] ) || ! in_array( $misc[1], array( 'w', 'l', 't' ), true ) ) // is strict
			$misc[1] = null;
		// Validate score
		if ( empty( $misc[2] ) )
			$misc[2] = null;
		else
			$misc[2] = (int) $misc[2]; // Cast to integer
		// Validate opponent score
		if ( empty( $misc[3] ) )
			$misc[3] = null;
		else
			$misc[3] = (int) $misc[3]; // Cast to integer

		return $misc;
	}

	public function getUsernameHtml()
	{
		$username = htmlspecialchars( $this->aOptions['participant_name'] );
		if ( empty( $username ) )
			return $this->oUsr->display_name;
		$username = explode( '%', $username );
		$last = count( $username ) - 1;
		$lastWasToken = true; // Starting this true will skip the first element, which can't be a token
		foreach ( $username AS $k => $v ) {
			if ( $lastWasToken ) {
				$lastWasToken = false;
				continue;
			} elseif ( $last == $k ) {
				$username[$k] = '%' . $v;
				break;
			}
			$lastWasToken = true;
			switch ( $v ) {
				case ''       : $v =                   '%'                            ; break;
				case 'uid'    : $v =                   $this->oUsr->ID                ; break;
				case 'login'  : $v = htmlspecialchars( $this->oUsr->user_login       ); break;
				case 'nice'   : $v =                   $this->oUsr->user_nicename     ; break;
				case 'first'  : $v = htmlspecialchars( $this->oUsr->user_firstname   ); break;
				case 'last'   : $v = htmlspecialchars( $this->oUsr->user_lastname    ); break;
				case 'nick'   : $v = htmlspecialchars( $this->oUsr->nickname         ); break;
				case 'display': $v = htmlspecialchars( $this->oUsr->display_name     ); break;
				case 'role'   : $v = htmlspecialchars( current( $this->oUsr->roles ) ); break;
				default:
					if ( preg_match( '/^whatev:?(\d*)$/', $v, $m ) ) {
						$v = '<input type="text" style="width:' . ( $m[1] > 0 ? (int) $m[1] : 12 ) . 'em" value="" placeholder="' . __( 'type here', Challonge_Plugin::TEXT_DOMAIN ) . '" />';
					} else if ( preg_match( '/^meta:(\w+)$/', $v, $m ) ) {
						$v = esc_html( get_user_meta( $this->oUsr->ID, $m[1], true ) );
					} else {
						$v = '%' . $v;
						$lastWasToken = false;
					}
			}
			$username[$k] = $v;
		}
		return implode( '', $username );
	}

	public function getUsernameText( $htmlUsername, $inputs )
	{
		if ( ! is_array( $inputs ) )
			return false;
		$username = preg_replace(
			'/<input[^>]+>/i',
			'%s',
			str_replace(
				'%',
				'&#37;',
				$htmlUsername
			),
			-1, // no limit
			$count
		);
		if ( count( $inputs ) == $count ) {
			foreach ( $inputs AS &$input ) {
				$input = htmlspecialchars( $input );
			}
			return html_entity_decode( vsprintf( $username, $inputs ) );
		}
		return false;
	}

	public function getWidgetContent( $wId )
	{
		global $wp_registered_widgets, $wp_registered_sidebars;
		$sidebars_widgets = get_option( 'sidebars_widgets' );
		foreach ( $sidebars_widgets as $sidebarId => $widgets ) {
			foreach ( $widgets as $k => $widgetId ) {
				if ( $widgetId === $wId && isset( $wp_registered_sidebars[ $sidebarId ] ) && isset( $wp_registered_widgets[ $widgetId ] ) ) {
					$num = $wp_registered_widgets[ $widgetId ]['params'][0]['number'];
					$pluginWidgets = $wp_registered_widgets[ $widgetId ]['callback'][0]->get_settings();
					the_widget( 'Challonge_Widget', $pluginWidgets[ $num ], array( 'ajax_content_only' => true, 'widget_id' => $widgetId ) );
					exit;
				}
			}
		}
		die( __( 'Widget not found' ) );
	}

	public function getShortcodeContent( $data )
	{
		$atts = json_decode( gzuncompress( base64_decode( $data ) ), true );
		if ( is_array( $atts ) ) {
			$oShortcode = new Challonge_Shortcode;
			echo $oShortcode->shortCode( $atts );
			exit;
		}
		die( __( 'Invalid shortcode data' ) );
	}

}
