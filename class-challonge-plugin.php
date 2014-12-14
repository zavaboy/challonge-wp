<?php
/**
 * @package Challonge
 */

// TODO: Move widget content methods to a separate class.

// Exit on direct request.
defined( 'ABSPATH' ) OR exit;

class Challonge_Plugin
{
	const NAME        = 'Challonge';
	const TITLE       = 'Challonge';
	const VERSION     = '1.1.4';
	const TEXT_DOMAIN = 'challonge';
	const THIRD_PARTY = 'Challonge.com'; // The name of the website this plugin interfaces with.

	// TODO: Before release, minify JS and turn USE_MIN_JS on.
	const USE_MIN_JS  = true; // Use minified/compressed (.min.js) JavaScript files?
	const DEV_MODE    = false; // Development mode? (Use 'FORCE' instead of true to ignore hostname.)

	protected $sPluginUrl;
	protected $oUsr;
	protected $oApi;
	protected $sApiKey;
	protected $aOptions;
	protected $aOptionsDefault = array(
		'api_key'              => '', // API key used - always valid or empty
		'api_key_input'        => '', // API key input value as submitted
		'public_shortcode'     => false,
		'public_widget'        => false,
		'public_widget_signup' => false,
		'enable_teams'         => false, // NOT USED
		'participant_name'     => '%whatev% (%login%)',
		'scoring'              => 'one',
		'scoring_opponent'     => false,
		'caching'              => 0,
		'no_ssl_verify'        => false,
		// TODO: Safely remove 'no_ssl_verify' in version 1.2
		'VERSION'              => null,
		'LAST_UPDATED_VER'     => null,
		'LAST_UPDATED'         => null,
	);
	protected $bIgnoreCached = false;
	protected $sAdminPage; // Administration page name

	static $oInstance;

	public function __construct()
	{
		// Set instance
		self::$oInstance = $this;

		$sPluginFile = plugin_dir_path( __FILE__ ) . 'challonge.php';
		$this->sPluginUrl = plugin_dir_url( $sPluginFile );

		// Activation/deactivation/uninstall hooks
		require_once( 'class-challonge-plugin-setup.php' );
		$sSetupClass = 'Challonge_Plugin_Setup';
		register_activation_hook( $sPluginFile, array( $sSetupClass, 'on_activation' ) );
		//register_deactivation_hook( $sPluginFile, array( $sSetupClass, 'on_deactivation' ) ); // NOT USED
		register_uninstall_hook(  $sPluginFile, array( $sSetupClass, 'on_uninstall'  ) );

		// Init
		add_action( 'init', array( $this, 'init' ) );

		// Widgets
		add_action( 'widgets_init', array( $this, 'registerWidgets' ) );
		add_action( 'wp_ajax_challonge_widget', array( $this, 'widgetReply' ) );
		add_action( 'wp_ajax_nopriv_challonge_widget', array( $this, 'widgetReply' ) );

		// General
		add_action( 'wp_enqueue_scripts', array( $this, 'loadAssets' ) );

		// Admin
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'initAdmin' ) );
		add_action( 'admin_head', array( $this, 'headAdmin' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'loadAssetsAdmin' ) );
		add_action( 'wp_ajax_challonge_verify_apikey', array( $this, 'verifyApiKey' ) );
		add_action( 'admin_notices', array( $this, 'adminNotice' ) );

		// Short Code
		require_once( 'class-challonge-shortcode.php' );
		$oShortCode = new Challonge_Shortcode();
		add_shortcode( 'challonge', array( $oShortCode, 'shortCode' ) );
	}

	static public function getInstance()
	{
		if ( ! is_object( self::$oInstance ) ) {
			$sClassName = __CLASS__;
			self::$oInstance = new $sClassName;
		}
		return self::$oInstance;
	}

	public function init()
	{
		// Localization!
		load_plugin_textdomain( Challonge_Plugin::TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		// Load Options!
		$this->getOptions();
		// Update!
		if ( Challonge_Plugin::VERSION != $this->aOptions['VERSION'] ) { // Needs updated?
			$aOptions = get_option( 'challonge_options' );
			if ( $this->updateVersion() && Challonge_Plugin::VERSION == $this->aOptions['VERSION'] ) // Got updated?
				if ( ! empty( $aOptions ) ) // Only show update notice on existing installs
					$this->addNotice( sprintf(
						/* translators:
							%s is the title of the plugin (hint: it will always be "Challonge")
						*/
						__( '%s has been updated.', Challonge_Plugin::TEXT_DOMAIN ),
						Challonge_Plugin::TITLE ),
					'updated', 'update-version' );
			else
				$this->addNotice( sprintf(
					/* translators:
						%s is the title of the plugin (hint: it will always be "Challonge")
					*/
					__( 'An error ocurred. %s could not update.', Challonge_Plugin::TEXT_DOMAIN ),
					Challonge_Plugin::TITLE ),
				'error', 'update-version');
		}
		// Get API Key!
		$this->sApiKey = $this->aOptions['api_key'];
		// Initiate the API!
		require_once( 'class-challonge-api-adapter.php' );
		if ( $this->hasApiKey() ) {
			$this->oApi = new Challonge_Api_Adapter( $this->sApiKey );
			$this->oApi->verify_ssl = ! $this->aOptions['no_ssl_verify'];
		}
		// Load the Current User!
		$this->oUsr = wp_get_current_user();
	}

	public function getOptions()
	{
		$aOptions = get_option( 'challonge_options' );
		if ( empty( $aOptions ) )
			$aOptions = array();
		return $this->aOptions = wp_parse_args( $aOptions, $this->aOptionsDefault );
	}

	public function registerWidgets()
	{
		require_once( 'class-challonge-widget.php' );
		register_widget( 'Challonge_Widget' );
	}

	public function widgetTournyLink( $tournyId )
	{
		// Init
		if ( ! $this->hasApiKey() || empty( $tournyId ) || ! is_string( $tournyId ) ) {
			return false;
		}
		$tourny = $this->oApi->getTournament( $tournyId, array(
			'include_participants' => 1,
			'include_matches'      => 1,
		) );
		if ( empty( $tourny ) ) {
			return false;
		}

		// Vars for participants, matches, and our ajax url
		$participants = $tourny->participants->participant;
		$matches = $tourny->matches->match;
		$ajaxurl = admin_url( 'admin-ajax.php' );
		if ( strlen( $tourny->subdomain ) ) {
			$lnk_tourny = $tourny->subdomain . '-' . $tourny->url;
		} else {
			$lnk_tourny = $tourny->url;
		}

		// User key hash
		if ( is_user_logged_in() && current_user_can( 'challonge_signup' ) )
			$usrkey = md5( $tourny->url . ' ' . $this->oUsr->user_login . ' <' . $this->oUsr->user_email . '>' ); // Shows signup
		elseif ( ! is_user_logged_in() && $this->aOptions['public_widget_signup'] )
			$usrkey = true; // Shows signup to login
		else
			$usrkey = false; // Shows nothing

		// Is the user signed up?
		// We will also cheack if all participants have signed up through the plugin while we're at it. :)
		$all_have_misc = true;
		$misc = array();
		$signed_up = $reported_scores = false;
		$participant_id = -1;
		$participants_by_id = array();
		$participant_names = array();
		foreach ( $participants AS $participant ) {
			$participants_by_id[ (int) $participant->id ] = $participant;
			$participant_names[] = $participant->name;
			$pmisc = $this->parseParticipantMisc( $participant->misc );
			if ( empty( $pmisc[0] ) ) {
				$all_have_misc = false;
			} elseif ( $pmisc[0] == $usrkey ) {
				$signed_up = true;
				$participant_id = (int) $participant->id;
				$misc = $pmisc;
				$reported_scores = ( ! empty( $misc[1] ) && in_array( $misc[1], array( 'w', 'l', 't' ) ) );
			}
		}

		// Find current match
		$has_match = false;
		$opponent_id = -1;
		$opponent = null;
		if ( $signed_up && ! $reported_scores && 'underway' == $tourny->state ) {
			foreach ( $matches AS $match ) {
				if ( 'open' == $match->state && (
					(int) $match->{'player1-id'} == $participant_id ||
					(int) $match->{'player2-id'} == $participant_id
				  ) ) {
					$has_match = true;
					if ( (int) $match->{'player1-id'} == $participant_id )
						$opplr = 2;
					else
						$opplr = 1;
					$opponent_id = (int) $match->{ 'player' . $opplr . '-id' };
					$opponent = $participants_by_id[ $opponent_id ];
					break; // Just break the loop, $match is the correct match we want
				}
			}
		}
		if ( ! $has_match )
			$match = null;

		// Determine link
		if ( $tourny->{'signup-cap'} > 0 ) {
			$signup_cap = (int) $tourny->{'signup-cap'};
		} else {
			$signup_cap = '&infin;';
		}
		$lnk = false;
		$hide_button = false;
		$tbw = 750; // ThinkBox Width
		$tbh = 550; // ThinkBox Height
		$username = $this->getUsernameHtml();
		
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
						%s is remaining time until starting time in human readable format (eg. "3 weeks" or "34 minutes")
					*/
					__( 'Starts in %s', Challonge_Plugin::TEXT_DOMAIN ),
					$this->timeDiff( $tourny->{'start-at'} )
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
				$signup_cap
			) ) . '">' . ( (int) $tourny->{'participants-count'} ) . '/' . $signup_cap . '</span>';
		// Tournyinfo together
		$tournyinfo = '<span class="challonge-tournyname">' . esc_html( $tourny->name ) . '</span><br />'
			. '<span class="challonge-tournyinfo">' . implode( ' &nbsp;&middot;&nbsp; ', $tournyinfo ) . '</span>';
		
		// TODO: Replace these conditionals with something better. (They're getting a little hard to work with.)
		if ( $usrkey && ! $signed_up && 'pending' == $tourny->state && 'true' == $tourny->{'open-signup'} ) {
			$lnk = 'join';
			$lnk_button = __( 'Signup', Challonge_Plugin::TEXT_DOMAIN );
			if ( ! is_user_logged_in() ) {
				$tbw = 300; // ThinkBox Width
				$tbh = 350; // ThinkBox Height
				$lnk_html = '<p>' . __( 'You must be logged in to sign up to tournaments.', Challonge_Plugin::TEXT_DOMAIN ) . '</p>'
					. wp_login_form(
						array(
							'echo'     => false,
							'redirect' => 'challonge_signup=' . urlencode( $lnk_tourny ),
							'form_id'  => 'challonge-loginform',
						)
					);
				$hide_button = true;
			} elseif ( '&infin;' == $signup_cap || (int) $tourny->{'participants-count'} < $signup_cap ) {
				if ( empty( $tourny->description ) )
					$tbh = 300; // ThinkBox Height
				$lnk_html = '<p>' . __( 'Signup to the following tournament?', Challonge_Plugin::TEXT_DOMAIN ) . '</p>'
					. '<div>'
						. $tournyinfo
					. '</div>'
					. '<div class="challonge-tournydesc">' . $tourny->description . '</div>'
					. '<p>' . __( 'You will join as:', Challonge_Plugin::TEXT_DOMAIN )
					. '<br /><span class="challonge-playername">' . $username . '</span></p>';
				if ( ! $all_have_misc && 'both' == $this->aOptions['scoring'] )
					/* translators:
						%s is the name of the third-party website this plugin interfaces with (hint: it will always be "Challonge.com")
					*/
					$lnk_html .= '<p class="challonge-error">' . sprintf( __( 'Warning: One or more participants in this tournament have not'
						. ' signed up through this website. The host or an authorized %s user must report'
						. ' the score for their matches.', Challonge_Plugin::TEXT_DOMAIN ), Challonge_Plugin::THIRD_PARTY ) . '</p>';
			} else {
				$lnk_html = '<p>' . __( 'This tournament is full.', Challonge_Plugin::TEXT_DOMAIN ) . '</p>';
				$hide_button = true;
			}
		} elseif ( $signed_up && current_user_can( 'challonge_signup' ) && ( 'pending' == $tourny->state || ( 'checking_in' == $tourny->state && !empty( $participants_by_id[ $participant_id ]->{'checked-in-at'} ) ) ) ) {
			$lnk = 'leave';
			$lnk_button = __( 'Forfeit', Challonge_Plugin::TEXT_DOMAIN );
			$tbw = 600; // ThinkBox Width
			$tbh = 200; // ThinkBox Height
			$lnk_html = '<p>' . __( 'Forfeit the following tournament?', Challonge_Plugin::TEXT_DOMAIN ) . '</p>'
				. '<div>'
					. $tournyinfo
				. '</div>';
		} elseif ( $signed_up && current_user_can( 'challonge_signup' ) && 'checking_in' == $tourny->state && empty( $participants_by_id[ $participant_id ]->{'checked-in-at'} ) ) {
			$lnk = 'checkin';
			$lnk_button = __( 'Check In', Challonge_Plugin::TEXT_DOMAIN );
			$tbw = 600; // ThinkBox Width
			$tbh = 200; // ThinkBox Height
			$lnk_html = '<p>' . __( 'Check in on the following tournament?', Challonge_Plugin::TEXT_DOMAIN ) . '</p>'
				. '<div>'
					. $tournyinfo
				. '</div>';
		} elseif ( $signed_up && current_user_can( 'challonge_report_own' ) && 'underway' == $tourny->state && 'true' == $tourny->{'allow-participant-match-reporting'} && 'none' != $this->aOptions['scoring'] ) {
			$lnk = 'report';
			$lnk_button = __( 'Report', Challonge_Plugin::TEXT_DOMAIN );
			$tbw = 600; // ThinkBox Width
			$tbh = 250; // ThinkBox Height
			// Find opponent
			if ( $has_match ) {
				$omisc = $this->parseParticipantMisc( $opponent->misc );
				if ( ! empty( $omisc[0] ) || 'both' != $this->aOptions['scoring'] ) {
					// TODO: Use round labels when available
					$round = (int) $match->round;
					if ( 'true' != $tourny->{'quick-advance'} ) {
						$tbh = 280; // ThinkBox Height
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
					$lnk_html = '<p>'
						. sprintf(
							/* translators:
								%1$s is the name of the participant's opponent
								%2$s is the name of the current round (see: "Round %d")
								%3$s is the name of the tournament
							*/
							__( 'Did you win against %1$s in %2$s of %3$s?', Challonge_Plugin::TEXT_DOMAIN ),
								'<strong>' . $opponent->name . '</strong>',
								'<strong>' . sprintf(
									/* translators:
										%d is a number
									*/
									__( 'Round %d', Challonge_Plugin::TEXT_DOMAIN ),
									$round
								) . '</strong>',
								'<strong>' . $tourny->name . '</strong>'
							) . '</p>'
						. '<form id="challonge-report">'
							. '<table id="challonge-report-table"><tr class="challonge-wlt">'
								. '<td><label for="challonge-report-win" class="challonge-button challonge-bigbutton challonge-button-win">'
									. '<input type="radio" id="challonge-report-win" name="challonge_report_wl" value="w" /> '
								. __( 'Won', Challonge_Plugin::TEXT_DOMAIN ) . '</label></td>'
								. '<td colspan="2"><label for="challonge-report-lose" class="challonge-button challonge-bigbutton challonge-button-lose">'
									. '<input type="radio" id="challonge-report-lose" name="challonge_report_wl" value="l" /> '
								. __( 'Lost', Challonge_Plugin::TEXT_DOMAIN ) . '</label></td>'
								. ( 'true' != $tourny->{'quick-advance'} ?
										'<td><label for="challonge-report-tie" class="challonge-button challonge-bigbutton challonge-button-tie">'
										. '<input type="radio" id="challonge-report-tie" name="challonge_report_wl" value="t" /> '
									. __( 'Tied', Challonge_Plugin::TEXT_DOMAIN ) . '</label></td>'
									: '' )
							. '</tr>' . $scoring_row . '</table>'
						. '</form>';
				} else {
					/* translators:
						$1$s is the name of the participant's opponent
						%2$s is the name of the third-party website this plugin interfaces with (hint: it will always be "Challonge.com")
					*/
					$lnk_html = '<p>' . sprintf( __( 'Your opponent, %1$s, did not sign up through this website. The host or an authorized %2$s user must report the score.', Challonge_Plugin::TEXT_DOMAIN ), '<strong>' . $opponent->name . '</strong>', Challonge_Plugin::THIRD_PARTY ) . '</p>';
					$hide_button = true;
				}
			} elseif ( $reported_scores ) {
				$lnk_html = '<p>' . __( 'You have already reported your score for this round. Please wait for the round to end.', Challonge_Plugin::TEXT_DOMAIN ) . '</p>';
				$hide_button = true;
			} else {
				$lnk_html = '<p>' . __( 'You are not included in this round.', Challonge_Plugin::TEXT_DOMAIN ) . '</p>';
				$hide_button = true;
			}
		}

		$lnk_url = $ajaxurl . '?action=challonge_widget&amp;width=%d&amp;height=%d';
		$lnk_title_html = '<a href="' . sprintf( $lnk_url, 750, 550 ) . '&amp;lnk_tourny=' . esc_attr( $lnk_tourny )
			. '&amp;lnk_action=view" class="challonge-tournyid-' . esc_attr( $lnk_tourny ) . ' thickbox" title="' . esc_html( $tourny->name ) . '">'
			. esc_html( $tourny->name ) . '</a>';
		// Content
		if ( $lnk ) {
			$lnk_html .= '<p class="challonge-lnkconfirm">';
			if ( $hide_button )
				$lnk_html .= '<a href="#close" onclick="tb_remove();return false;" class="challonge-cancel">' . __( 'Close', Challonge_Plugin::TEXT_DOMAIN ) . '</a>';
			else
				$lnk_html .= '<a href="#' . $lnk . '" data-lnkaction="' . $lnk . '" data-lnktourny="' . esc_attr( $lnk_tourny )
					. '" class="challonge-button challonge-bigbutton challonge-button-' . $lnk . '">' . $lnk_button . '</a>'
					. ' &nbsp; '
					. '<a href="#cancel" onclick="tb_remove();return false;" class="challonge-cancel">' . __( 'Cancel', Challonge_Plugin::TEXT_DOMAIN ) . '</a>';
			$lnk_html .= '</p>';
			$lnk_button_html = '<a href="' . sprintf( $lnk_url, $tbw, $tbh ) . '&amp;lnk_tourny=' . esc_attr( $lnk_tourny )
				. '" class="challonge-button challonge-button-' . $lnk . ' challonge-tournyid-' . esc_attr( $lnk_tourny ) . ' thickbox" title="' . esc_html( $tourny->name ) . '">'
				. $lnk_button . '</a>';
		} else {
			$lnk_html = '<p class="challonge-error">' . __( 'ERROR: No tournament actions available.', Challonge_Plugin::TEXT_DOMAIN ) . '</p>';
			$lnk_button = $lnk_button_html = '';
		}
		$lnk_html = '<div class="challonge-container-tb">' . $lnk_html . '</div>';

		return array(
			'tourny'         => $tourny,
			'participants'   => $tourny->{'participants-count'},
			'signup_cap'     => $signup_cap,
			'name'           => $lnk,
			'button'         => $lnk_button,
			'html'           => $lnk_html,
			'button_html'    => $lnk_button_html,
			'title_html'     => $lnk_title_html,
			'usrkey'         => $usrkey,
			'misc'           => $misc,
			'participant_id' => $participant_id,
			'match'          => $match,
			'opponent'       => $opponent,
			'username'       => $username,
		);
	}

	public function widgetReply()
	{
		$this->getOptions();
		$this->bIgnoreCached = true; // AJAX requests never should use cached API data

		// No API Key?
		if ( ! $this->hasApiKey() ) {
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
		$tourny = $lnk['tourny'];
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
				$username = $this->getUsernameText( $lnk['username'], $input );
				if ( ! empty( $username ) ) 
					$joined = $this->oApi->createParticipant( $lnk['tourny']->id, array(
						'participant[name]' => $username,
						'participant[misc]' => $lnk['usrkey'],
					) );
				if ( $joined ) {
					// Refresh lnk
					$lnk = $this->widgetTournyLink( $tournyId );
					die( '<p class="challonge-ok">'
						/* translators:
							%s is the name of the tournament
						*/
						. sprintf( __( 'You have joined %s.', Challonge_Plugin::TEXT_DOMAIN ), '<strong>' . $lnk['tourny']->name . '</strong>' )
						. ' -- <a href="#done" onclick="tb_remove();return false;">' . __( 'Done', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>'
						. '<div class="challonge-metahtml">' . $lnk['button_html'] . '</div>' );
				} else {
					die( '<p class="challonge-error">'
						/* translators:
							%s is the name of the tournament
						*/
						. sprintf( __( 'ERROR: You did not join %s.', Challonge_Plugin::TEXT_DOMAIN ), '<strong>' . $lnk['tourny']->name . '</strong>' )
						. ' -- <a href="#close" onclick="tb_remove();return false;">' . __( 'Close', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>' );
				}
				break;
			case 'leave':
				$deleted = $this->oApi->deleteParticipant( $lnk['tourny']->id, $lnk['participant_id'] );
				if ( $deleted ) {
					// Refresh lnk
					$lnk = $this->widgetTournyLink( $tournyId );
					die( '<p class="challonge-ok">'
						/* translators:
							%s is the name of the tournament
						*/
						. sprintf( __( 'You have forfeited %s.', Challonge_Plugin::TEXT_DOMAIN ), '<strong>' . $lnk['tourny']->name . '</strong>' )
						. ' -- <a href="#done" onclick="tb_remove();return false;">' . __( 'Done', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>'
						. '<div class="challonge-metahtml">' . $lnk['button_html'] . '</div>' );
				} else {
					die( '<p class="challonge-error">'
						/* translators:
							%s is the name of the tournament
						*/
						. sprintf( __( 'ERROR: You did not forfeit %s.', Challonge_Plugin::TEXT_DOMAIN ), '<strong>' . $lnk['tourny']->name . '</strong>' )
						. ' -- <a href="#close" onclick="tb_remove();return false;">' . __( 'Close', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>' );
				}
				break;
			case 'checkin':
				$checkedin = $this->oApi->checkInParticipant( $lnk['tourny']->id, $lnk['participant_id'] );
				if ( $checkedin ) {
					// Refresh lnk
					$lnk = $this->widgetTournyLink( $tournyId );
					die( '<p class="challonge-ok">'
						/* translators:
							%s is the name of the tournament
						*/
						. sprintf( __( 'You have been checked into %s.', Challonge_Plugin::TEXT_DOMAIN ), '<strong>' . $lnk['tourny']->name . '</strong>' )
						. ' -- <a href="#done" onclick="tb_remove();return false;">' . __( 'Done', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>'
						. '<div class="challonge-metahtml">' . $lnk['button_html'] . '</div>' );
				} else {
					die( '<p class="challonge-error">'
						/* translators:
							%s is the name of the tournament
						*/
						. sprintf( __( 'ERROR: You were not checked into %s.', Challonge_Plugin::TEXT_DOMAIN ), '<strong>' . $lnk['tourny']->name . '</strong>' )
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
						. '<div class="challonge-metahtml">' . $lnk['button_html'] . '</div>' );
				} else {
					die( '<p class="challonge-error">'
						. sprintf( __( 'Your score was not reported. Reason: %s', Challonge_Plugin::TEXT_DOMAIN ), $reported )
						. ' -- <a href="#close" onclick="tb_remove();return false;">' . __( 'Close', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>' );
				}
				break;
			default:
				die( $lnk['html'] );
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
		if ( ! empty( $lnk['opponent'] ) && 'any' != $this->aOptions['scoring'] )
			$omisc = $this->parseParticipantMisc( $lnk['opponent']->misc );
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
				$oupdated = $this->oApi->updateParticipant( $lnk['tourny']->id, (int) $lnk['opponent']->id, array(
					'participant[misc]' => $omisc[0],
				) );
				if ( ! $oupdated )
					return __( 'Unable to clear opponent report', Challonge_Plugin::TEXT_DOMAIN );
			} else {
				// Set participant score, opponent hasn't submitted his/her score yet
				$pupdated = $this->oApi->updateParticipant( $lnk['tourny']->id, $lnk['participant_id'], array(
					'participant[misc]' => $lnk['usrkey'] . ',' . $wl . ',' . $score . ',' . $opponent_score,
				) );
				if ( ! $pupdated )
					return __( 'Unable to set your report', Challonge_Plugin::TEXT_DOMAIN );
				return 'Reported';
			}
		}

		// Determine winner participant ID
		if ( 'w' == $wl ) {
			$winner_id = $lnk['participant_id'];
		} elseif ( 'l' == $wl ) {
			$winner_id = (int) $lnk['opponent']->id;
		} else {
			$winner_id = 'tie';
		}
		// Determine score order
		if ( (int) $lnk['match']->{ 'player1-id' } == $lnk['participant_id'] ) {
			$scores_csv = sprintf( '%d-%d', $score, $opponent_score );
		} else {
			$scores_csv = sprintf( '%d-%d', $opponent_score, $score );
		}
		// Now, let's set the match score
		$mupdated = $this->oApi->updateMatch( $lnk['tourny']->id, (int) $lnk['match']->id, array(
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

	public function loadAssets()
	{
		if ( self::USE_MIN_JS )
			$min = '.min';
		else
			$min = '';
		wp_register_style( 'challonge.css', $this->sPluginUrl . 'challonge.css', array( 'thickbox' ), self::VERSION );
		wp_enqueue_style( 'challonge.css' );
		wp_register_script( 'challonge.js', $this->sPluginUrl . 'challonge' . $min . '.js', array( 'jquery' ), self::VERSION );
		wp_localize_script( 'challonge.js', 'challongeVar', array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'spinUrl'  => includes_url( 'images/wpspin.gif' ),
			'wltMsg'   => __( 'Please select if you Won, Lost, or Tied.', Challonge_Plugin::TEXT_DOMAIN ),
			'errorMsg' => __( 'Sorry, an error occurred.', Challonge_Plugin::TEXT_DOMAIN ),
			'closeMsg' => __( 'Close', Challonge_Plugin::TEXT_DOMAIN ),
		) );
		wp_enqueue_script( 'challonge.js' );
		wp_register_script( 'jquery.challonge.js', $this->sPluginUrl . 'jquery.challonge' . $min . '.js', array( 'jquery' ), self::VERSION );
		wp_enqueue_script( 'jquery.challonge.js' );
	}

	public function loadAssetsAdmin()
	{
		if ( self::USE_MIN_JS )
			$min = '.min';
		else
			$min = '';
		wp_register_style( 'challonge.css', $this->sPluginUrl . 'challonge-admin.css', array(), self::VERSION );
		wp_enqueue_style( 'challonge.css' );
		wp_register_script( 'challonge.js', $this->sPluginUrl . 'challonge-admin' . $min . '.js', array( 'jquery' ), self::VERSION );
		wp_enqueue_script( 'challonge.js' );
		wp_localize_script( 'challonge.js', 'challongeVar', array(
			'errorMsg' => __( 'Sorry, an error occurred.', Challonge_Plugin::TEXT_DOMAIN ),
		) );
	}

	public function hasApiKey()
	{
		return ! empty( $this->sApiKey );
	}

	public function verifyApiKey()
	{
		$this->bIgnoreCached = true; // AJAX requests never should use cached API data

		header( 'Content-Type: application/json; charset=utf-8' );
		if ( ! extension_loaded( 'curl' ) ) {
			die( '{"errors":["API requests require cURL"]}' );
		}
		if ( isset( $_GET['api_key'] ) && preg_match( '/^[a-z0-9]{40}$/i', $_GET['api_key'] ) && current_user_can( 'manage_options' ) ) {
			$apikey = $_GET['api_key'];

			$c = new Challonge_Api_Adapter( $apikey );
			$c->verify_ssl = ! $this->aOptions['no_ssl_verify'];
			$t = $c->getTournaments( array( 'created_after' => date( 'Y-m-d', time() + 86400 ) ) );
			if ( $c->errors )
				die( json_encode( array( 'errors' => $c->errors ) ) );
			else
				die( json_encode( $t ) );
		}
		die( '{"errors":["X-Error"]}' );
	}

	public function menu()
	{
		$this->sAdminPage = add_options_page( Challonge_Plugin::TITLE, Challonge_Plugin::TITLE, 'manage_options', 'challonge-settings', array( $this, 'settings' ) );
	}

	public function initAdmin()
	{
		register_setting( 'challonge_options', 'challonge_options', array( $this, 'optionsValidate' ) );

		if ( ! extension_loaded( 'curl' ) ) {
			$this->addNotice( sprintf(
				/* translators:
					%1$s is the title of the plugin (hint: it will always be "Challonge")
					%2$s is the name of the third-party website this plugin interfaces with (hint: it will always be "Challonge.com")
				*/
				__( '%1$s requires cURL to be enabled on your webserver.'
					. ' Please ask your server administrator or hosting provider to install cURL so you may use the %2$s API.',
					Challonge_Plugin::TEXT_DOMAIN ),
				Challonge_Plugin::TITLE,
				Challonge_Plugin::THIRD_PARTY
			), 'error', 'no-curl' );
		}

		// BEGIN PLUGIN DEVELOPMENT STUFF
		// If in Dev Mode and on localhost (or dev version), throw some notices to help with development.
		// This is for myself while developing so I remember what I need to complete before release.
		// Yeah, I'm a lazy sod.
		if ( ( self::DEV_MODE && ( 'localhost' == $_SERVER['SERVER_NAME'] || false !== strpos( self::VERSION, 'dev' ) ) ) || 'FORCE' == self::DEV_MODE ) {
			// Find TODO items
			$todos = array();
			$ver_1 = $ver_2 = $ver_3 = $ver_4 = array('','...');
			$dir = dirname( __FILE__ );
			if (is_dir($dir)) {
				if ($dh = opendir($dir)) {
					while (($file = readdir($dh)) !== false) {
						if ( '.' != $file[0] && false !== strpos( $file, '.' ) && false === strpos( $file, '~' ) ) {
							if ( ! is_readable( $dir . '/' . $file ) ) {
								$this->addNotice( 'File unreadable: ' . $file, 'error' );
								continue;
							}
							$content = file( $dir . '/' . $file );
							foreach ( $content AS $k => $v ) {
								if ( strpos( $v, '// ' . 'TODO' ) !== false )
									$todos[] = $file . ' at line ' . ( $k + 1 ) . ': <tt>' . htmlentities( trim( $v ) ) . '</tt>';
							}
							if ( basename( __FILE__ ) == $file ) {
								foreach ( $content AS $k => $v ) {
									if ( preg_match( '/const\s+VERSION\s*=\s*[\'"](.*)[\'"]\s*;/i', $v, $m ) )
										$ver_2 = array(
											trim( $m[1] ),
											$file . ' at line ' . ( $k + 1 )
												. ': <strong style="color:red">Version: ' . $m[1] . '</strong>',
										);
								}
							} elseif ( 'challonge.php' == $file ) {
								foreach ( $content AS $k => $v ) {
									if ( preg_match( '/^Version:\s*(.*)$/i', $v, $m ) )
										$ver_1 = array(
											trim( $m[1] ),
											$file . ' at line ' . ( $k + 1 )
												. ': <strong style="color:red">Version: ' . $m[1] . '</strong>',
										);
								}
							} elseif ( 'readme.txt' == $file ) {
								$start1 = $start2 = false;
								foreach ( $content AS $k => $v ) {
									if ( strpos( $v, '== Changelog ==' ) !== false ) {
										$start1 = true;
									} elseif ( $start1 && preg_match( '/=\s*(.*)\s*=/i', $v, $m ) ) {
										$ver_3 = array(
											trim( $m[1] ),
											$file . ' at line ' . ( $k + 1 )
												. ': <strong style="color:red">(Changelog) Version: ' . $m[1] . '</strong>',
										);
										$start1 = false;
									} elseif ( strpos( $v, '== Upgrade Notice ==' ) !== false ) {
										$start2 = true;
									} elseif ( $start2 && preg_match( '/=\s*(.*)\s*=/i', $v, $m ) ) {
										$ver_4 = array(
											trim( $m[1] ),
											$file . ' at line ' . ( $k + 1 )
												. ': <strong style="color:red">(Upgrade Notice) Version: ' . $m[1] . '</strong>',
										);
										$start2 = false;
									}
								}
							}
						}
					}
					closedir($dh);
				}
			}
			if ( $ver_1[0] != $ver_2[0] || $ver_1[0] != $ver_3[0] || $ver_1[0] != $ver_4[0] ) {
				$this->addNotice( '<strong style="color:red">VERSION MISMATCH!</strong>'
					. '<br />' . $ver_1[1]
					. '<br />' . $ver_2[1]
					. '<br />' . $ver_3[1]
					. '<br />' . $ver_4[1]
					, 'error', 'version-info' );
			} else {
				$this->addNotice( '<strong style="color:green">VERSION: ' . $ver_1[0] . '</strong>', 'updated', 'version-info' );
			}
			// Send admin notice if there are TODO items found
			// If the plugin is released with TODO items, I have determined they should be completed in a later release.
			if ( ! empty( $todos ) )
				$this->addNotice('Found ' . count( $todos ) . ' TODO items:<br />' . implode( '<br />', $todos ), 'updated', 'todo-items-found' );
			// Send admin notice if UseMinJS is OFF
			if ( ! self::USE_MIN_JS )
				$this->addNotice( Challonge_Plugin::NAME . ': USE_MIN_JS is OFF!', 'error', 'minjs-is-off' );
		}
		// END PLUGIN DEVELOPMENT STUFF
	}

	public function headAdmin( $input )
	{
	    // check user permissions
	    if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) )
	        return;
	    // check if WYSIWYG is enabled
	    if ( 'true' == get_user_option( 'rich_editing' ) && version_compare( get_bloginfo('version'), '3.9', '>=' ) ) {
	        add_filter( 'mce_external_plugins', array( $this, 'addTinyMcePlugin' ) );
	        add_filter( 'mce_buttons', array( $this, 'registerTinyMceButton' ) );
	    }
	}

	public function addTinyMcePlugin( $plugin_array )
	{
		$plugin_array['challonge_mce_button'] = $this->sPluginUrl . 'challonge-mce-button.js';
	    return $plugin_array;
	}

	public function registerTinyMceButton( $buttons )
	{
		array_push( $buttons, 'challonge_mce_button' );
		return $buttons;
	}

	public function optionsValidate( $input )
	{
		$options = $this->aOptions;

		// API Key
		$options['api_key_input'] = preg_replace( '/[\W_]+/', '', $input['api_key'] );
		if (40 == strlen( $options['api_key_input'] ) && extension_loaded( 'curl' ) ) {
			$c = new Challonge_Api_Adapter( $options['api_key_input'] );
			$c->verify_ssl = ! $this->aOptions['no_ssl_verify'];
			$t = $c->getTournaments( array( 'created_after' => date( 'Y-m-d', time() + 86400 ) ) );
			if ( $c->errors && 'Result set empty' == $c->errors[0] )
				$options['api_key'] = $options['api_key_input'];
			else
				$options['api_key'] = '';
		} else {
			$options['api_key'] = '';
		}

		// Public
		$options['public_shortcode'] = ! empty( $input['public_shortcode'] );
		$options['public_widget'] = ! empty( $input['public_widget'] );
		$options['public_widget_signup'] = ! empty( $input['public_widget_signup'] );

		// Participant Name
		$options['participant_name'] = trim( $input['participant_name'] );

		// Scoring
		if ( in_array( $input['scoring'], array( 'both', 'one', 'any', 'none' ) ) )
			$options['scoring'] = $input['scoring'];
		$options['scoring_opponent'] = ! empty( $input['scoring_opponent'] );

		// Cache
		$options['caching'] = (int) $input['caching'];
		if ( $input['caching_clear'] ) {
			$this->clearCache();
			$this->addNotice( __( 'The Challonge API cache was cleared.', Challonge_Plugin::TEXT_DOMAIN ), 'updated', 'cache-cleared' );
		}

		// Disable SSL verification
		$options['no_ssl_verify'] = ! empty( $input['no_ssl_verify'] );

		$options['LAST_UPDATED'] = time();

		return $options;
	}

	public function settings()
	{
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', Challonge_Plugin::TEXT_DOMAIN ) );
		}
		include( 'settings.tpl.php' );
	}

	public function getApi()
	{
		if ( $this->hasApiKey() ) {
			return $this->oApi;
		}
		return null;
	}

	public function addNotice( $message, $type = 'updated', $id = null )
	{
		$notice_transient = 'challonge_notices';
		if ( false === ( $transient_data = get_transient( $notice_transient ) ) ) {
			$transient_data = array();
		}
		if ( $type != 'updated' && $type != 'error' && $type != 'updated-nag' )
			$type = 'updated';
		$notice = array( 'type' => $type, 'message' => $message );
		if ( empty( $id ) ) $id = md5( serialize( $notice ) );
		$transient_data[ $id ] = $notice;
		$transient_set = set_transient( $notice_transient, $transient_data, HOUR_IN_SECONDS );
	}

	public function adminNotice()
	{
		// Only show on the Challonge settings page
		$screen = get_current_screen();
		if ( $screen->id != $this->sAdminPage ) return;

		// Use transients in case the request is redirected
		$notice_transient = 'challonge_notices';
		if ( false !== ( $transient_data = get_transient( $notice_transient ) ) ) {
			foreach ( $transient_data AS $notice ) {
				echo '<div class="' . $notice['type'] . '"><p>' . $notice['message'] . '</p></div>';
			}
			delete_transient( $notice_transient );
		}
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
					if ( preg_match( '/^whatev:?(\d*)$/', $v, $m ) )
						$v = '<input type="text" style="width:' . ( $m[1] > 0 ? (int) $m[1] : 12 ) . 'em" value="" placeholder="' . __( 'type here', Challonge_Plugin::TEXT_DOMAIN ) . '" />';
					else {
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

	public function clearCache() {
		$log_transient = 'challonge_cache_log';
		if ( false !== ( $transient_data = get_transient( $log_transient ) ) ) {
			foreach ( $transient_data AS $k => $v) {
				delete_transient( $k );
			}
			delete_transient( $log_transient );
		}
	}

	public function logCache( $transient ) {
		$log_transient = 'challonge_cache_log';
		if ( false === ( $transient_data = get_transient( $log_transient ) ) ) {
			$transient_data = array();
		}
		$transient_data[$transient] = time();
		$transient_set = set_transient( $log_transient, $transient_data, WEEK_IN_SECONDS );
	}

	public function isCacheIgnored() {
		return $this->bIgnoreCached;
	}

	public function updateVersion() {
		$aOptions = get_option( 'challonge_options' );
		if ( empty( $aOptions ) || ! is_array( $aOptions ) ) {
			// Probably a new install
			$aOptions = array();
		} elseif ( ! isset( $aOptions['VERSION'] ) ) {
			// Probably from a version prior to 1.1.3
			if ( isset( $aOptions['no_ssl_verify'] ) && $aOptions['no_ssl_verify'] ) {
				// SSL verification was finally fixed in version 1.1.3.
				// Let's turn SSL verification ON for the user.
				// They can always turn it back off if they need to. (for now)
				$aOptions['no_ssl_verify'] = false; // Turn SSL verification ON
			}
		}
		$aOptions['VERSION'] = Challonge_Plugin::VERSION;
		$aOptions['LAST_UPDATED_VER'] = time();
		if ( update_option( 'challonge_options', $aOptions ) ) {
			$this->getOptions(); // Reload the options
			return true;
		}
		return false;
	}
	
	public function timeDiff( $time, $now = null ) {
		if ( ! is_int( $time ) )
			$time = strtotime( (string) $time ) + ( get_option( 'gmt_offset' ) * 3600 );
		if ( is_null( $now ) )
			$now = time();
		if ( $now == $time )
			return 'just now';
		return human_time_diff( $time, $now );
	}
}
