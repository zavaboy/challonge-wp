<?php
/**
 * @package Challonge
 */

// Exit on direct request.
defined( 'ABSPATH' ) OR exit;

class Challonge_Plugin_Setup
{
	public static function on_activation()
	{
		if ( ! current_user_can( 'activate_plugins' ) )
			return;
		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		check_admin_referer( 'activate-plugin_' . $plugin );

		// Get roles
		$wp_roles = new WP_Roles();

		// Determine if Challonge role capabilities exist
		$has_roles = false;
		foreach ( $wp_roles->roles AS $role_name => $role ) {
			foreach ( $role['capabilities'] AS $cap => $cap_allow ) {
				if ( 'challonge_' == substr( $cap, 0, 10 ) ) {
					$has_roles = true;
					break 2;
				}
			}
		}

		// Add default role capabilities if no Challonge role capabilities exist
		if ( ! $has_roles ) {

			// Default role capabilities
			$dcaps = array(
				0 => array( // Default
					'challonge_view'        => true,
					'challonge_signup'      => true,
					'challonge_report_own'  => true,
					'challonge_report_all'  => false,
					'challonge_view_log'    => false,
					'challonge_add_log'     => false,
				),
				'administrator' => array(
					'challonge_view'        => true,
					'challonge_signup'      => true,
					'challonge_report_own'  => true,
					'challonge_report_all'  => true,
					'challonge_view_log'    => true,
					'challonge_add_log'     => true,
				),
				'editor' => array(
					'challonge_view'        => true,
					'challonge_signup'      => true,
					'challonge_report_own'  => true,
					'challonge_report_all'  => true,
					'challonge_view_log'    => true,
					'challonge_add_log'     => true,
				),
			);

			// Setup default role capabilities
			foreach ( $wp_roles->roles AS $role_name => $role ) {
				if ( isset( $caps[ $role_name ] ) ) {
					$caps = $dcaps[ $role_name ];
				} else {
					$caps = $dcaps[0];
				}
				foreach ( $caps AS $cap => $cap_allow ) {
					if ( ! isset( $role['capabilities'][ $cap ] ) && $cap_allow ) {
						$wp_roles->add_cap( $role_name, $cap );
					}
				}
			}

		}
	}

	/*
	// NOT USED
	public static function on_deactivation()
	{
		if ( ! current_user_can( 'activate_plugins' ) )
			return;
		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		check_admin_referer( 'deactivate-plugin_' . $plugin );

		// ...
	}
	*/

	public static function on_uninstall()
	{
		if ( ! current_user_can( 'activate_plugins' ) )
			return;
		check_admin_referer( 'bulk-plugins' );

		// Important: Check if the file is the one
		// that was registered during the uninstall hook.
		if ( __FILE__ != WP_UNINSTALL_PLUGIN )
			return;

		// Remove Challonge options
		$options = get_option( 'challonge_options' );
		if ( false !== $options ) {
			delete_option( 'challonge_options' );
		}

		// Get roles
		$wp_roles = new WP_Roles();

		// Remove all Challonge role capabilities
		foreach ( $wp_roles->roles AS $role_name => $role ) {
			foreach ( $role['capabilities'] AS $cap => $cap_allow ) {
				if ( 'challonge_' == substr( $cap, 0, 10 ) ) {
					$wp_roles->remove_cap( $role_name, $cap );
				}
			}
		}
	}
}
