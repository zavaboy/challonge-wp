<?php
/**
 * @package Challonge
 */

// Exit on direct request.
defined( 'ABSPATH' ) OR exit;

// This class is used for plugin development purposes
class Challonge_Plugin_Dev
{
	public static function adminNotices()
	{
		$oCP = Challonge_Plugin::getInstance();
		// Find TODO items
		$todos = array();
		$ver_1 = $ver_2 = $ver_3 = $ver_4 = array('','...');
		$dir = dirname( __FILE__ );
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if ( '.' != $file[0] && false !== strpos( $file, '.' ) && false === strpos( $file, '~' ) ) {
						if ( ! is_readable( $dir . DIRECTORY_SEPARATOR . $file ) ) {
							$oCP->addNotice( 'File unreadable: ' . $file, 'error' );
							continue;
						}
						$content = file( $dir . DIRECTORY_SEPARATOR . $file );
						foreach ( $content AS $k => $v ) {
							if ( strpos( $v, '// ' . 'TODO' ) !== false )
								$todos[] = $file . ' at line ' . ( $k + 1 ) . ': <tt>' . htmlentities( trim( $v ) ) . '</tt>';
						}
						if ( 'class-challonge-plugin.php' == $file ) {
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
			$oCP->addNotice( '<strong style="color:red">VERSION MISMATCH!</strong>'
				. '<br />' . $ver_1[1]
				. '<br />' . $ver_2[1]
				. '<br />' . $ver_3[1]
				. '<br />' . $ver_4[1]
				, 'error', 'version-info' );
		} else {
			$oCP->addNotice( '<strong style="color:green">VERSION: ' . $ver_1[0] . '</strong>', 'updated', 'version-info' );
		}
		// Send admin notice if there are TODO items found
		// If the plugin is released with TODO items, I have determined they should be completed in a later release.
		if ( ! empty( $todos ) )
			$oCP->addNotice('Found ' . count( $todos ) . ' TODO items:<br />' . implode( '<br />', $todos ), 'updated', 'todo-items-found' );
		// Send admin notice if UseMinJS is OFF
		if ( ! Challonge_Plugin::USE_MIN_JS )
			$oCP->addNotice( Challonge_Plugin::NAME . ': USE_MIN_JS is OFF!', 'error', 'minjs-is-off' );
	}
}
