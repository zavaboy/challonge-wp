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
	const VERSION     = '1.1.6';
	const TEXT_DOMAIN = 'challonge';
	const THIRD_PARTY = 'Challonge.com'; // The name of the website this plugin interfaces with.
	const CACHE_NAME  = 'challonge_cache_log'; // The name of the website this plugin interfaces with.
	static $aStatuses = array(
		'pending',
		'underway',
		'awaiting_review',
		'complete',
		'checking_in',
		'checked_in',
		//'unknown',
	);

	// TODO: Before release, minify JS and turn USE_MIN_JS on.
	const USE_MIN_JS  = true; // Use minified/compressed (.min.js) JavaScript files?
	const DEV_MODE    = false; // Development mode? (Use 'FORCE' instead of true to ignore hostname.)

	protected $sPluginUrl;
	protected $oUsr;
	protected $oApi;
	protected $oAjax;
	protected $sApiKey;
	protected $aOptions;
	protected $aOptionsDefault = array(
		'api_key'                 => '', // API key used - always valid or empty
		'api_key_input'           => '', // API key input value as submitted
		'public_shortcode'        => false,
		'public_widget'           => false,
		'public_widget_signup'    => false,
		'public_ignore_exclusion' => false,
		'headers_shortcode'       => array( /* initialized in construct */ ),
		'enable_teams'            => false, // NOT USED
		'participant_name'        => '%whatev% (%login%)',
		'scoring'                 => 'one',
		'scoring_opponent'        => false,
		'caching'                 => 0,
		'caching_adaptive'        => true,
		'caching_freshness'       => true,
		'no_ssl_verify'           => false,
		// TODO: Safely remove 'no_ssl_verify' in version 1.2
		'VERSION'                 => null,
		'LAST_UPDATED_VER'        => null,
		'LAST_UPDATED'            => null,
	);
	protected $bIgnoreCached = false;
	protected $sAdminPage; // Administration page name

	static $oInstance;

	public function __construct()
	{
		// Set instance
		self::$oInstance = $this;

		$this->initShortcodeHeaders();

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

		// Ajax (Widgets)
		require_once( 'class-challonge-ajax.php' );
		$this->oAjax = new Challonge_Ajax();
		add_action( 'wp_ajax_challonge_widget', array( $this->oAjax, 'widgetReply' ) );
		add_action( 'wp_ajax_nopriv_challonge_widget', array( $this->oAjax, 'widgetReply' ) );

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
		// NOTE: load_plugin_textdomain() is no longer required as of WordPress version 4.6
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
		// NOTE: register_widget() also accepts object instance since WordPress 4.6
		register_widget( 'Challonge_Widget' );
	}

	public function widgetTournyLink( $tournyId ) // Alias function
	{
		return $this->oAjax->widgetTournyLink( $tournyId );
	}

	public function loadAssets()
	{
		if ( self::USE_MIN_JS )
			$min = '.min';
		else
			$min = '';
		wp_register_style( 'challonge.css', $this->sPluginUrl . 'challonge.css', array( 'thickbox' ), self::VERSION );
		wp_enqueue_style( 'challonge.css' );
		wp_register_script( 'moment-with-locales.js', $this->sPluginUrl . 'moment-with-locales' . $min . '.js', array(), self::VERSION );
		wp_enqueue_script( 'moment-with-locales.js' );
		wp_register_script( 'challonge.js', $this->sPluginUrl . 'challonge' . $min . '.js', array( 'jquery', 'moment-with-locales.js' ), self::VERSION );
		wp_localize_script( 'challonge.js', 'challongeVar', array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'spinUrl'  => includes_url( 'images/wpspin.gif' ),
			'wltMsg'   => __( 'Please select if you Won, Lost, or Tied.', Challonge_Plugin::TEXT_DOMAIN ),
			'errorMsg' => __( 'Sorry, an error occurred.', Challonge_Plugin::TEXT_DOMAIN ),
			'closeMsg' => __( 'Close', Challonge_Plugin::TEXT_DOMAIN ),
			'wpLocale' => get_locale(), // used with moment.js
			'rfNowMsg' => __( 'refresh now' ),
			'rfingMsg' => __( 'refreshing...' ),
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
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_register_script( 'challonge.js', $this->sPluginUrl . 'challonge-admin' . $min . '.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-dialog' ), self::VERSION );
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
			require_once( 'class-challonge-plugin-dev.php' );
			Challonge_Plugin_Dev::adminNotices();
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
			$this->bIgnoreCached = true;
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
		$options['public_ignore_exclusion'] = ! empty( $input['public_ignore_exclusion'] );

		// Display
		$options['headers_shortcode'] = array();
		foreach ( $input['headers_shortcode'] AS $v ) {
			$default = null;
			$new = json_decode( $v, true );
			foreach ( $this->aOptionsDefault['headers_shortcode'] AS $vv ) {
				if ( $vv['prop'] == $new['prop'] ) {
					$default = $vv;
					break; // no need to look at the rest
				}
			}
			if ( null !== $default ) {
				$options['headers_shortcode'][] = array_merge( $default, $new );
			}
		}
		// $options['headers_shortcode'] = $this->aOptionsDefault['headers_shortcode']; // reset

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
		$options['caching_adaptive'] = ! empty( $input['caching_adaptive'] );
		$options['caching_freshness'] = ! empty( $input['caching_freshness'] );

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

	public function getCache() {
		if ( false === ( $cache = get_transient( self::CACHE_NAME ) ) ) {
			$cache = array();
		}
		return $cache;
	}

	public function clearCache() {
		$cache = $this->getCache();
		if ( ! empty( $cache ) ) {
			foreach ( $cache AS $k => $v) {
				delete_transient( $k );
			}
			delete_transient( self::CACHE_NAME );
		}
	}

	public function logCache( $transient, DateTime $time = null ) {
		$cache = $this->getCache();
		if ( null === $time ) {
			$time = new DateTime;
		}
		$cache[$transient] = $time->format( DateTime::ATOM );
		$transient_set = set_transient( self::CACHE_NAME, $cache, MONTH_IN_SECONDS );
	}

	public function isCacheIgnored() {
		return $this->bIgnoreCached;
	}

	public function updateVersion() {
		$aOptions = get_option( 'challonge_options' );
		if ( empty( $aOptions ) || ! is_array( $aOptions ) ) {
			// Probably a new install
			$aOptions = array();
		} else {
			if ( ! isset( $aOptions['VERSION'] ) ) {
				// Probably from a version prior to 1.1.3
				if ( isset( $aOptions['no_ssl_verify'] ) && $aOptions['no_ssl_verify'] ) {
					// SSL verification was finally fixed in version 1.1.3.
					// Let's turn SSL verification ON for the user.
					// They can always turn it back off if they need to. (for now)
					$aOptions['no_ssl_verify'] = false; // Turn SSL verification ON
				}
			}
			if ( $aOptions['VERSION'] < '1.1.6' ) {
				// Caching format changed in version 1.1.6
				// Old cached data will no longer work, so we need to clear it.
				$this->clearCache();
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

	public function getUser() {
		return $this->oUsr;
	}

	public function setCacheIgnore( $bIgnoreCached ) {
		$this->bIgnoreCached = (bool) $bIgnoreCached;
		return true;
	}
	
	public function timeDiff( $time, $now = null ) {
		if ( ! is_int( $time ) )
			$time = strtotime( (string) $time ) + ( get_option( 'gmt_offset' ) * 3600 );
		if ( is_null( $now ) )
			$now = time();
		if ( $now == $time )
			return __( 'right now', Challonge_Plugin::TEXT_DOMAIN );
		if ( $now < $time )
			/* translators:
				%s is an approximate relative amount of time (eg. "2 days")
			*/
			return sprintf( __( 'in %s', Challonge_Plugin::TEXT_DOMAIN ), human_time_diff( $time, $now ) );
		/* translators:
			%s is an approximate relative amount of time (eg. "2 days")
		*/
		return sprintf( __( '%s ago', Challonge_Plugin::TEXT_DOMAIN ), human_time_diff( $time, $now ) );
	}

	private function initShortcodeHeaders() {
		/*
		Reason for this method:
		- I didn't want to clutter the top
		- The values should be translatable
		*/
		$this->aOptionsDefault['headers_shortcode'] = array(
			/*
			Ref:
				prop     - API XML property name (this must be unique)
				name     - Default table header text (used when alias is empty)
				_formats - List of available formats
				alias    - User-defined table header text
				show     - The column is displayed if this is true
				format   - The selected format
			Note:
				Items beginning with an underscore will not be included in the data hidden input on the options page.
			*/
			array('prop' => 'name'        , 'name' => __( 'Name'             , Challonge_Plugin::TEXT_DOMAIN ), '_formats' => array(
				'text'            => __( 'Plain Text'                                     , Challonge_Plugin::TEXT_DOMAIN ),
				'link'            => __( 'Link to Challonge.com'                          , Challonge_Plugin::TEXT_DOMAIN ),
				'link_new'        => __( 'Link to Challonge.com in new browser window/tab', Challonge_Plugin::TEXT_DOMAIN ),
				'link_modal'      => __( 'Link to open in dialog'                          , Challonge_Plugin::TEXT_DOMAIN ),
				'link_modal_full' => __( 'Link to open in full dialog'                     , Challonge_Plugin::TEXT_DOMAIN ),
				), 'alias' => '', 'show' => true , 'format' => 'link_modal_full'),
			array('prop' => 'type'        , 'name' => __( 'Type'             , Challonge_Plugin::TEXT_DOMAIN ), '_formats' => array(
				'full'            => __( 'Full (eg. Single Elimination, Swiss)'           , Challonge_Plugin::TEXT_DOMAIN ),
				'short'           => __( 'Short (eg. SE, Sw)'                             , Challonge_Plugin::TEXT_DOMAIN ),
				), 'alias' => '', 'show' => true , 'format' => 'short'),
			array('prop' => 'participants', 'name' => __( 'Participants'     , Challonge_Plugin::TEXT_DOMAIN ), '_formats' => array(
				'p'               => __( 'Number of participants (eg. 5)'                 , Challonge_Plugin::TEXT_DOMAIN ),
				'p_of_t'          => __( 'Num. of participants of total (eg. 5 of 12)'    , Challonge_Plugin::TEXT_DOMAIN ),
				'p_slash_t'       => __( 'Num. of participants slash total (eg. 5/12)'     , Challonge_Plugin::TEXT_DOMAIN ),
				), 'alias' => '', 'show' => true , 'format' => 'p_slash_t'),
			array('prop' => 'created'     , 'name' => __( 'Created On'       , Challonge_Plugin::TEXT_DOMAIN ), '_formats' => array(
				'date'            => __( 'Date'                                           , Challonge_Plugin::TEXT_DOMAIN ),
				'date_time'       => __( 'Date & Time'                                    , Challonge_Plugin::TEXT_DOMAIN ),
				'time_diff'       => __( 'Time ago (eg. 2 days ago)'                      , Challonge_Plugin::TEXT_DOMAIN ),
				), 'alias' => '', 'show' => true , 'format' => 'date'),
			array('prop' => 'progress'    , 'name' => __( 'Progress'         , Challonge_Plugin::TEXT_DOMAIN ), '_formats' => array(
				'text'            => __( 'Percentage (eg. 27%)'                           , Challonge_Plugin::TEXT_DOMAIN ),
				'bar'             => __( 'Progress bar'                                   , Challonge_Plugin::TEXT_DOMAIN ),
				), 'alias' => '', 'show' => true , 'format' => 'bar'),
			array('prop' => 'checkin'     , 'name' => __( 'Check-In Duration', Challonge_Plugin::TEXT_DOMAIN ), '_formats' => array(
				'default'         => __( 'Default'                                        , Challonge_Plugin::TEXT_DOMAIN ),
				), 'alias' => '', 'show' => false, 'format' => 'default'),
			array('prop' => 'description' , 'name' => __( 'Description'      , Challonge_Plugin::TEXT_DOMAIN ), '_formats' => array(
				'full_html'       => __( 'Full HTML'                                      , Challonge_Plugin::TEXT_DOMAIN ),
				'full'            => __( 'Full Text'                                      , Challonge_Plugin::TEXT_DOMAIN ),
				'line'            => __( 'First Line (without formatting)'                , Challonge_Plugin::TEXT_DOMAIN ),
				), 'alias' => '', 'show' => false, 'format' => 'line'),
			array('prop' => 'game'        , 'name' => __( 'Game'             , Challonge_Plugin::TEXT_DOMAIN ), '_formats' => array(
				'default'         => __( 'Default'                                        , Challonge_Plugin::TEXT_DOMAIN ),
				), 'alias' => '', 'show' => false, 'format' => 'default'),
			array('prop' => 'quick'       , 'name' => __( 'Quick Advance'    , Challonge_Plugin::TEXT_DOMAIN ), '_formats' => array(
				'yes_no'          => __( 'Yes/No'                                         , Challonge_Plugin::TEXT_DOMAIN ),
				'on_off'          => __( 'On/Off'                                         , Challonge_Plugin::TEXT_DOMAIN ),
				'check'           => __( 'Checkmark'                                      , Challonge_Plugin::TEXT_DOMAIN ),
				), 'alias' => '', 'show' => false, 'format' => 'check'),
			array('prop' => 'signup'      , 'name' => __( 'Signup'           , Challonge_Plugin::TEXT_DOMAIN ), '_formats' => array(
				'default'         => __( 'Default'                                        , Challonge_Plugin::TEXT_DOMAIN ),
				), 'alias' => '', 'show' => false, 'format' => 'default'),
			array('prop' => 'start'       , 'name' => __( 'Start At'         , Challonge_Plugin::TEXT_DOMAIN ), '_formats' => array(
				'date'            => __( 'Date'                                           , Challonge_Plugin::TEXT_DOMAIN ),
				'date_time'       => __( 'Date & Time'                                    , Challonge_Plugin::TEXT_DOMAIN ),
				'time_diff'       => __( 'Time ago (eg. 2 days ago)'                      , Challonge_Plugin::TEXT_DOMAIN ),
				), 'alias' => '', 'show' => false, 'format' => 'date_time'),
			array('prop' => 'started'     , 'name' => __( 'Started At'       , Challonge_Plugin::TEXT_DOMAIN ), '_formats' => array(
				'date'            => __( 'Date'                                           , Challonge_Plugin::TEXT_DOMAIN ),
				'date_time'       => __( 'Date & Time'                                    , Challonge_Plugin::TEXT_DOMAIN ),
				'time_diff'       => __( 'Time ago (eg. 2 days ago)'                      , Challonge_Plugin::TEXT_DOMAIN ),
				), 'alias' => '', 'show' => false, 'format' => 'time_diff'),
			array('prop' => 'state'       , 'name' => __( 'State'            , Challonge_Plugin::TEXT_DOMAIN ), '_formats' => array(
				'default'         => __( 'Default'                                        , Challonge_Plugin::TEXT_DOMAIN ),
				), 'alias' => '', 'show' => false, 'format' => 'default'),
			// array('prop' => 'foo'      , 'name' => 'Foo'              , '_formats' => array(
			// 	'default'         => 'Default',
			// 	), 'alias' => '', 'show' => false, 'format' => 'default'),
		);
	}
}
