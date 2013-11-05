<?php
/**
 * @package Challonge
 */

// Exit on direct request.
defined( 'ABSPATH' ) OR exit;

class Challonge_Plugin
{
	const NAME        = 'Challonge';
	const TITLE       = 'Challonge';
	const VERSION     = '1.0.2';
	const TEXT_DOMAIN = 'challonge';

	protected $sPluginUrl;
	protected $oUsr;
	protected $oApi;
	protected $sApiKey;
	protected $aOptions;
	protected $aOptionsDefault = array(
		'api_key'          => ''    ,
		'public_shortcode' => false ,
		'public_widget'    => false ,
	);
	protected $bUseMinJs = true;

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
		add_action( 'admin_enqueue_scripts', array( $this, 'loadAssetsAdmin' ) );
		add_action( 'wp_ajax_challonge_verify_apikey', array( $this, 'verifyApiKey' ) );

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
		$this->getOptions();
		$this->sApiKey = $this->aOptions['api_key'];
		require_once( 'class-challonge-api.php' );
		if ( $this->hasApiKey() ) {
			$this->oApi = new Challonge_Api( $this->sApiKey );
			$this->oApi->verify_ssl = ( '127.0.0.1' != $_SERVER['SERVER_ADDR'] ); // KLUDGE: Make this better.
		}
		$this->oUsr = wp_get_current_user();
	}

	public function getOptions()
	{
		return $this->aOptions = wp_parse_args( get_option( 'challonge_options' ), $this->aOptionsDefault );
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

		// User key hash
		$usrkey = md5( $tourny->url . ' ' . $this->oUsr->user_login . ' <' . $this->oUsr->user_email . '>' );

		// Is the user signed up?
		$misc = array();
		$signed_up = $reported_scores = false;
		$participant_id = -1;
		$participants_by_id = array();
		foreach ( $participants AS $participant ) {
			$participants_by_id[ (int) $participant->id ] = $participant;
			$pmisc = $this->parseParticipantMisc( $participant->misc );
			if ( $pmisc[0] == $usrkey ) {
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
		if ( $signed_up && 'underway' == $tourny->state ) {
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
		$tbw = 400; // ThinkBox Width
		$tbh = 250; // ThinkBox Height
		if ( ! $signed_up && current_user_can( 'challonge_signup' ) && 'pending' == $tourny->state && 'true' == $tourny->{'open-signup'} ) {
			$lnk = 'join';
			$lnk_button = __( 'Signup', Challonge_Plugin::TEXT_DOMAIN );
			if ( '&infin;' == $signup_cap || (int) $tourny->{'participants-count'} < $signup_cap ) {
				$lnk_html = '<p>' . __( 'Signup to the following tournament?', Challonge_Plugin::TEXT_DOMAIN ) . '</p>'
					. '<p>'
						. '<span class="challonge-tournyname">' . $tourny->name . '</span><br />'
						. '<span class="challonge-tournyinfo">'
							. date_i18n( get_option( 'date_format' ), strtotime( $tourny->{'created-at'} ) ) . ' | '
							. esc_html( ucwords( $tourny->{'tournament-type'} ) ) . ' | '
							. esc_html( $tourny->{'participants-count'} ) . '/' . $signup_cap
						. '</span>'
					. '</p>'
					. '<p>' . __( 'You will join as:', Challonge_Plugin::TEXT_DOMAIN )
					. '<br /><span class="challonge-playername">' . $this->oUsr->display_name . '</span></p>';
			} else {
				$lnk_html = '<p>' . __( 'This tournament is full.', Challonge_Plugin::TEXT_DOMAIN ) . '</p>';
				$hide_button = true;
			}
		} elseif ( $signed_up && current_user_can( 'challonge_signup' ) && 'pending' == $tourny->state ) {
			$lnk = 'leave';
			$lnk_button = __( 'Forfeit', Challonge_Plugin::TEXT_DOMAIN );
			$lnk_html = '<p>' . __( 'Forfeit the following tournament?', Challonge_Plugin::TEXT_DOMAIN ) . '</p>'
				. '<p>'
					. '<span class="challonge-tournyname">' . $tourny->name . '</span><br />'
					. '<span class="challonge-tournyinfo">'
						. date_i18n( get_option( 'date_format' ), strtotime( $tourny->{'created-at'} ) ) . ' | '
						. esc_html( ucwords( $tourny->{'tournament-type'} ) ) . ' | '
						. esc_html( $tourny->{'participants-count'} ) . '/' . $signup_cap
					. '</span>'
				. '</p>';
			$tbh = 200;
		} elseif ( $signed_up && current_user_can( 'challonge_report_own' ) && 'underway' == $tourny->state && 'true' == $tourny->{'allow-participant-match-reporting'} ) {
			$lnk = 'report';
			$lnk_button = __( 'Report', Challonge_Plugin::TEXT_DOMAIN );
			// Find opponent
			if ( $has_match ) {
				$omisc = $this->parseParticipantMisc( $opponent->misc );
				if ( ! empty( $omisc[0] ) ) {
					$round = (int) $match->round;
					$lnk_html = '<p>'
						. sprintf(
							__( 'Did you win against <strong>%s</strong> in <strong>Round %d</strong> of <strong>%s</strong>?', Challonge_Plugin::TEXT_DOMAIN ),
							$opponent->name, $round, $tourny->name
							) . '</p>'
						. '<form id="challonge-report">'
							. '<table id="challonge-report-table"><tr>'
								. '<td><label for="challonge-report-win" class="challonge-button challonge-bigbutton challonge-button-win">'
									. '<input type="radio" id="challonge-report-win" name="challonge_report_wl" value="w" /> '
								. __( 'Won', Challonge_Plugin::TEXT_DOMAIN ) . '</label></td>'
								. '<td><label for="challonge-report-lose" class="challonge-button challonge-bigbutton challonge-button-lose">'
									. '<input type="radio" id="challonge-report-lose" name="challonge_report_wl" value="l" /> '
								. __( 'Lost', Challonge_Plugin::TEXT_DOMAIN ) . '</label></td>'
								. '<td><label for="challonge-report-tie" class="challonge-button challonge-bigbutton challonge-button-tie">'
									. '<input type="radio" id="challonge-report-tie" name="challonge_report_wl" value="t" /> '
								. __( 'Tied', Challonge_Plugin::TEXT_DOMAIN ) . '</label></td>'
							. '</tr></table>'
							. '<p><label for="challonge-report-score">' . __( 'What was your score?', Challonge_Plugin::TEXT_DOMAIN ) . '</label><br />'
							. '<input type="text" id="challonge-report-score" name="challonge_report_score" placeholder="0" /></p>'
						. '</form>';
				} else {
					$lnk_html = '<p>' . sprintf( __( 'Your opponent, <strong>%s</strong>, did not sign up through this website. The host or an authorized Challonge user must report the score.', Challonge_Plugin::TEXT_DOMAIN ), $opponent->name ) . '</p>';
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

		// Content
		if ( $lnk ) {
			$lnk_url = $ajaxurl . '?action=challonge_widget&amp;width=' . $tbw . '&amp;height=' . $tbh;
			$lnk_html .= '<p class="challonge-lnkconfirm">';
			if ( $hide_button )
				$lnk_html .= '<a href="#close" onclick="tb_remove();return false;" class="challonge-cancel">' . __( 'Close', Challonge_Plugin::TEXT_DOMAIN ) . '</a>';
			else
				$lnk_html .= '<a href="#' . $lnk . '" data-lnkaction="' . $lnk . '" data-lnktourny="' . esc_attr( $tourny->url )
					. '" class="challonge-button challonge-bigbutton challonge-button-' . $lnk . '">' . $lnk_button . '</a>'
					. ' &nbsp; '
					. '<a href="#cancel" onclick="tb_remove();return false;" class="challonge-cancel">' . __( 'Cancel', Challonge_Plugin::TEXT_DOMAIN ) . '</a>';
			$lnk_html .= '</p>';
			$lnk_button_html = '<a href="' . $lnk_url . '&amp;lnk_tourny=' . esc_attr( $tourny->url )
				. '" class="challonge-button challonge-button-' . $lnk . ' challonge-tournyid-' . esc_attr( $tourny->url ) . ' thickbox">'
				. $lnk_button . '</a>';
		} else {
			$lnk_html = '<p class="challonge-error">' . __( 'ERROR: No tournament actions available.', Challonge_Plugin::TEXT_DOMAIN ) . '</p>';
			$lnk_button = $lnk_button_html = '';
		}

		return array(
			'tourny'         => $tourny,
			'participants'   => $tourny->{'participants-count'},
			'signup_cap'     => $signup_cap,
			'name'           => $lnk,
			'button'         => $lnk_button,
			'html'           => $lnk_html,
			'button_html'    => $lnk_button_html,
			'usrkey'         => $usrkey,
			'misc'           => $misc,
			'participant_id' => $participant_id,
			'match'          => $match,
			'opponent'       => $opponent,
		);
	}

	public function widgetReply()
	{
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
			case 'join':
				$joined = $this->oApi->createParticipant( $lnk['tourny']->id, array(
					'participant[name]' => (string) $this->oUsr->display_name,
					'participant[misc]' => $lnk['usrkey'],
				) );
				if ( $joined ) {
					// Refresh lnk
					$lnk = $this->widgetTournyLink( $tournyId );
					die( '<p class="challonge-ok">'
						. sprintf( __( 'You have joined <strong>%s</strong>.', Challonge_Plugin::TEXT_DOMAIN ), $lnk['tourny']->name )
						. ' -- <a href="#done" onclick="tb_remove();return false;">' . __( 'Done', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>'
						. '<div class="challonge-metahtml">' . $lnk['button_html'] . '</div>' );
				} else {
					die( '<p class="challonge-error">'
						. sprintf( __( 'ERROR: You did not join <strong>%s</strong>.', Challonge_Plugin::TEXT_DOMAIN ), $lnk['tourny']->name )
						. ' -- <a href="#close" onclick="tb_remove();return false;">' . __( 'Close', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>' );
				}
				break;
			case 'leave':
				$deleted = $this->oApi->deleteParticipant( $lnk['tourny']->id, $lnk['participant_id'] );
				if ( $deleted ) {
					// Refresh lnk
					$lnk = $this->widgetTournyLink( $tournyId );
					die( '<p class="challonge-ok">'
						. sprintf( __( 'You have forfeited <strong>%s</strong>.', Challonge_Plugin::TEXT_DOMAIN ), $lnk['tourny']->name )
						. ' -- <a href="#done" onclick="tb_remove();return false;">' . __( 'Done', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>'
						. '<div class="challonge-metahtml">' . $lnk['button_html'] . '</div>' );
				} else {
					die( '<p class="challonge-error">'
						. sprintf( __( 'ERROR: You did not forfeit <strong>%s</strong>.', Challonge_Plugin::TEXT_DOMAIN ), $lnk['tourny']->name )
						. ' -- <a href="#close" onclick="tb_remove();return false;">' . __( 'Close', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>' );
				}
				break;
			case 'report':
				if ( ! empty( $_REQUEST['report_wl'] ) && isset( $_REQUEST['report_score'] ) ) {
					$reported = $this->reportScore( $lnk, $_REQUEST['report_wl'], $_REQUEST['report_score'] );
				} else {
					$reported = __( 'Invalid request', Challonge_Plugin::TEXT_DOMAIN );
				}
				if ( 'Reported' == $reported ) {
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

	public function reportScore( $lnk, $wl, $score )
	{
		// Validate W/L status
		$wl = strtolower( $wl );
		if ( ! in_array( $wl, array( 'w', 'l', 't' ), true ) ) // is strict
			return __( 'Invalid W/L status', Challonge_Plugin::TEXT_DOMAIN );

		// Type-cast score to be safe
		$score = (int) $score;

		// Validate opponent
		if ( ! empty( $lnk['opponent'] ) )
			$omisc = $this->parseParticipantMisc( $lnk['opponent']->misc );
		else
			return __( 'Invalid opponent', Challonge_Plugin::TEXT_DOMAIN );

		// Opponent W/L disagreement?
		if ( ! empty( $omisc[1] ) ) {
			$wlwl = $wl . $omisc[1];
			if ( 'wl' != $wlwl && 'lw' != $wlwl && 'tt' != $wlwl )
				return __( 'Opponent disagreement', Challonge_Plugin::TEXT_DOMAIN ); // eg: Oppenent says he/she won and you say you won.

			// Good to go, let's first reset opponent's misc to just his/her usrkey
			$oupdated = $this->oApi->updateParticipant( $lnk['tourny']->id, (int) $lnk['opponent']->id, array(
				'participant[misc]' => $omisc[0],
			) );
			if ( ! $oupdated )
				return __( 'Unable to clear opponent report', Challonge_Plugin::TEXT_DOMAIN );

			// Determine winner participant ID
			if ( 'w' == $wl ) {
				$winner_id = $lnk['participant_id'];
			} else {
				$winner_id = (int) $lnk['opponent']->id;
			}
			// Determine score order
			if ( (int) $lnk['match']->{ 'player1-id' } == $lnk['participant_id'] ) {
				$scores_csv = sprintf( '%d-%d', $score, (int) $omisc[2] );
			} else {
				$scores_csv = sprintf( '%d-%d', (int) $omisc[2], $score );
			}
			// Now, let's set the match score
			$mupdated = $this->oApi->updateMatch( $lnk['tourny']->id, (int) $lnk['match']->id, array(
				'match[scores_csv]' => $scores_csv,
				'match[winner_id]' => $winner_id,
			) );
			if ( ! $mupdated )
				return __( 'Unable to update match', Challonge_Plugin::TEXT_DOMAIN );
		} else {
			// Set participant score, opponent hasn't submitted his/hers yet
			$pupdated = $this->oApi->updateParticipant( $lnk['tourny']->id, $lnk['participant_id'], array(
				'participant[misc]' => $lnk['usrkey'] . ',' . $wl . ',' . $score,
			) );
			if ( ! $pupdated )
				return __( 'Unable to set your report', Challonge_Plugin::TEXT_DOMAIN );
		}

		return 'Reported';
	}

	public function parseParticipantMisc( $misc )
	{
		// If $misc is defined:
		//   $misc format is '{usrkey},{w|l|t},{score}' eg: b9c3cf4e9491aaf72548408eac387e3c,w,12
		$default = array( null, null, null );
		// Validate
		if ( empty( $misc ) )
			return $default;
		if ( is_array( $misc ) )
			$misc = implode( ',', $misc );
		else if ( ! is_string( $misc ) )
			$misc = (string) $misc;

		// Parse
		$misc = explode( ',', $misc ); // CSV to array
		// $misc[0] is $usrkey of the participant (always there if participant signed up via widget)
		// $misc[1] is the win/loss/tie reported by the participant, if any
		// $misc[2] is the score reported by the participant, if any

		// Validate array, must be at most 3 values
		if ( count( $misc ) > 3 ) {
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

		return $misc;
	}

	public function loadAssets()
	{
		if ( $this->bUseMinJs )
			$min = '.min';
		else
			$min = '';
		wp_register_style( 'challonge.css', $this->sPluginUrl . 'challonge.css', array(), self::VERSION );
		wp_enqueue_style( 'challonge.css' );
		wp_register_script( 'challonge.js', $this->sPluginUrl . 'challonge' . $min . '.js', array( 'jquery' ), self::VERSION );
		wp_localize_script( 'challonge.js', 'challongeVar', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'spinUrl' => includes_url( 'images/wpspin.gif' ),
			'wltMsg' => __( 'Please select if you Won, Lost, or Tied.', Challonge_Plugin::TEXT_DOMAIN ),
		) );
		wp_enqueue_script( 'challonge.js' );
		wp_register_script( 'jquery.challonge.js', $this->sPluginUrl . 'jquery.challonge' . $min . '.js', array( 'jquery' ), self::VERSION );
		wp_enqueue_script( 'jquery.challonge.js' );
	}

	public function loadAssetsAdmin()
	{
		if ( $this->bUseMinJs )
			$min = '.min';
		else
			$min = '';
		wp_register_style( 'challonge.css', $this->sPluginUrl . 'challonge-admin.css', array(), self::VERSION );
		wp_enqueue_style( 'challonge.css' );
		wp_register_script( 'challonge.js', $this->sPluginUrl . 'challonge-admin' . $min . '.js', array( 'jquery' ), self::VERSION );
		wp_enqueue_script( 'challonge.js' );
	}

	public function hasApiKey()
	{
		return ! empty( $this->sApiKey );
	}

	public function verifyApiKey()
	{
		header( 'Content-Type: application/json; charset=utf-8' );
		if ( isset( $_GET['api_key'] ) && preg_match( '/^[a-z0-9]{40}$/i', $_GET['api_key'] ) && current_user_can( 'manage_options' ) ) {
			$apikey = $_GET['api_key'];

			$c = new Challonge_Api( $apikey );
			$c->verify_ssl = ( '127.0.0.1' != $_SERVER['SERVER_ADDR'] ); // KLUDGE: Make this better.
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
		add_options_page( 'Challonge', 'Challonge', 'manage_options', 'challonge-settings', array( $this, 'settings' ) );
	}

	public function initAdmin()
	{
		register_setting( 'challonge_options', 'challonge_options', array( $this, 'optionsValidate' ) );
	}

	public function optionsValidate( $input )
	{
		$options = $this->aOptions;

		// API Key
		$options['api_key_input'] = preg_replace( '/[\W_]+/', '', $input['api_key'] );
		if (40 == strlen( $options['api_key_input'] ) ) {
			$c = new Challonge_Api( $options['api_key_input'] );
			$c->verify_ssl = ( '127.0.0.1' != $_SERVER['SERVER_ADDR'] ); // KLUDGE: Make this better.
			$t = $c->getTournaments( array( 'created_after' => date( 'Y-m-d', time() + 86400 ) ) );
			if ( $c->errors && 'Result set empty' == $c->errors[0] )
				$options['api_key'] = $options['api_key_input'];
			else
				$options['api_key'] = '';
		} else {
			$options['api_key'] = '';
		}

		// Include Organizations
		$orgs = preg_split( '/[\s,]/' , $input['orgs'] );
		$filtered_orgs = array();
		foreach ( $orgs AS $org ) {
			$org = strtolower( preg_replace( array( '/^(?:.*\W)?(\w+)\.challonge\.com.*$/i', '/[\W]+/' ), array( '$1', '' ), $org ) );
			if ( $org ) {
				$filtered_orgs[] = $org;
			}
		}
		$options['orgs'] = $filtered_orgs;

		// Public
		$options['public'] = (bool) $input['public'];
		$options['public_widget'] = (bool) $input['public_widget'];

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
}
