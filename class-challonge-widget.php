<?php
/**
 * @package Challonge
 */

// Exit on direct request.
defined( 'ABSPATH' ) OR exit;

class Challonge_Widget extends WP_Widget
{
	protected $oCP;
	protected $oApi;

	public function __construct() {
		parent::__construct(
			'challonge',
			__( Challonge_Plugin::TITLE, Challonge_Plugin::TEXT_DOMAIN ),
			array(
				'classname' => 'widget_challonge',
				'description' => __( 'List in-progress tournaments and signups for unstarted tournaments.', Challonge_Plugin::TEXT_DOMAIN )
			),
			array(
				'id_base' => 'challonge'
			)
		);
	}

	public function form($instance) {
		$title = '';
		$subdomain = '';
		$name_filter = '';
		$limit = '';
		if ( $instance ) {
			if ( isset( $instance['title'] ) ) {
				$title = esc_attr( $instance['title'] );
			}
			if ( isset( $instance['subdomain'] ) ) {
				$subdomain = esc_attr( $instance['subdomain'] );
			}
			if ( isset( $instance['name_filter'] ) ) {
				$name_filter = esc_attr( $instance['name_filter'] );
			}
			if ( isset( $instance['limit'] ) ) {
				$limit = esc_attr( $instance['limit'] );
			}
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', Challonge_Plugin::TEXT_DOMAIN ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" placeholder="<?php _e( Challonge_Plugin::TITLE, Challonge_Plugin::TEXT_DOMAIN ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'subdomain' ); ?>"><?php _e( 'Subdomain:', Challonge_Plugin::TEXT_DOMAIN ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'subdomain' ); ?>" name="<?php echo $this->get_field_name( 'subdomain' ); ?>" type="text" value="<?php echo $subdomain; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'name_filter' ); ?>"><?php _e( 'Tournament Filter:', Challonge_Plugin::TEXT_DOMAIN ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'name_filter' ); ?>" name="<?php echo $this->get_field_name( 'name_filter' ); ?>" type="text" value="<?php echo $name_filter; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Max tournaments listed:', Challonge_Plugin::TEXT_DOMAIN ); ?></label>
			<input id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="text" value="<?php echo $limit; ?>" size="3" placeholder="10" />
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance['title']       = strip_tags( $new_instance['title']       );
		$instance['subdomain']   = strip_tags( $new_instance['subdomain']   );
		$instance['name_filter'] = strip_tags( $new_instance['name_filter'] );
		if ( 0 < $instance['limit'] )
			$instance['limit']       = (int) $new_instance['limit'];
		else
			$instance['limit']       = '';
		return $instance;
	}

	public function widget( $args, $instance ) {
		// Init
		$this->oCP = Challonge_Plugin::getInstance();
		$usr = wp_get_current_user();
		$options = $this->oCP->getOptions();

		// Visitor can not see widget?
		if ( empty( $usr ) && empty( $options['public_widget'] ) ) {
			return;
		}

		// Widget output
		echo $args['before_widget'];
		echo $args['before_title'];
		if ( ! empty( $instance['title'] ) ) {
			echo esc_html( $instance['title'] );
		} else {
			echo __( Challonge_Plugin::TITLE, Challonge_Plugin::TEXT_DOMAIN );
		}
		echo $args['after_title'];
		echo '<div class="challonge-widget-content">';
		echo $this->content( $instance );
		echo '</div>';
		echo $args['after_widget'];
	}

	public function content( $instance ) {
		// Init
		$this->oCP = Challonge_Plugin::getInstance();
		$this->oApi = $this->oCP->getApi();
		$usr = wp_get_current_user();
		$ajaxurl = admin_url( 'admin-ajax.php' );
		if ( isset( $instance['subdomain'] ) )
			$subdomain = $instance['subdomain'];
		else
			$subdomain = null;
		if ( isset( $instance['name_filter'] ) )
			$filter = $instance['name_filter'];
		else
			$filter = null;
		if ( !empty( $instance['limit'] ) )
			$limit = $instance['limit'];
		else
			$limit = 10;

		// No API Key?
		if ( ! $this->oCP->hasApiKey() ) {
			if ( current_user_can( 'manage_options' ) ) {
				return '<p class="challonge-error">' . __( 'No API Key!', Challonge_Plugin::TEXT_DOMAIN ) . ' <a href="'
					. admin_url( 'options-general.php?page=challonge-settings' ) . '">'
					. __( 'Set one.', Challonge_Plugin::TEXT_DOMAIN ) . '</a></p>';
			}
			return '<p class="challonge-error">' . __( 'No API Key!', Challonge_Plugin::TEXT_DOMAIN ) . '</p>';
		}

		// Validate filter
		if ( empty( $filter ) || ! is_string( $filter ) ) // Empty or invalid filter
			$filter = null;
		else if ( 0 !== strpos( $filter, '/' ) ) // Astrisk "*" Wildcard to RegEx
			$filter = '/' . str_replace( '\*', '.*', preg_quote( $filter, '/' ) ) . '/i';
		else if ( false === @preg_match( $filter, null ) ) // Validate RegEx - KLUDGE: Can haz alternate that doesn't use "@"?
			$filter = false;

		// Get tournament listing
		$ret = '';
		if ( empty( $subdomain ) ) {
			$tournys = $this->oApi->getTournaments();
		} else {
			$tournys = $this->oApi->getTournaments( array( 'subdomain' => $subdomain ) );
		}
		if ( count( $tournys->tournament ) ) {
			foreach ( $tournys->tournament AS $tourny ) {
				if ( 'false' == $tourny->private && false !== $filter && ( null === $filter || preg_match( $filter, $tourny->name ) ) && ( $limit-- ) > 0 ) {
					$ret .= '<li>';
					if ( strlen( $tourny->subdomain ) )
						$tname = (string) $tourny->subdomain . '-' . $tourny->url;
					else
						$tname = (string) $tourny->url;
					$lnk = $this->oCP->widgetTournyLink( $tname );
					if ( ! empty( $lnk['name'] ) ) {
						add_thickbox();
						$ret .= $lnk['button_html'];
					}
					$ret .= '<a href="' . $tourny->{ 'full-challonge-url' } . '">'
							. esc_html( $tourny->name )
						. '</a><br /><span class="challonge-info">'
							. esc_html( $lnk['participants'] ) . '/' . $lnk['signup_cap']
							. ' | ' . esc_html( ucfirst( $tourny->state ) )
						. '</span>';
					$ret .= '</li>';
				}
			}
			if ( empty( $ret ) )
				$ret = '<p><em>(' . __( 'no tournaments', Challonge_Plugin::TEXT_DOMAIN ) . ')<!-- by filter --></em></p>';
			else
				$ret = '<ul class="challonge-widget-tournaments">' . $ret . '</ul>';
		} else {
			$ret = '<p><em>(' . __( 'no tournaments', Challonge_Plugin::TEXT_DOMAIN ) . ')</em></p>';
		}
		return $ret;
	}
}
