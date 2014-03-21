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
		$status_filter_pending         = false;
		$status_filter_underway        = false;
		$status_filter_awaiting_review = false;
		$status_filter_complete        = false;
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
			if ( isset( $instance['status_filter_pending'] ) ) {
				$status_filter_pending = (bool) $instance['status_filter_pending'];
			}
			if ( isset( $instance['status_filter_underway'] ) ) {
				$status_filter_underway = (bool) $instance['status_filter_underway'];
			}
			if ( isset( $instance['status_filter_awaiting_review'] ) ) {
				$status_filter_awaiting_review = (bool) $instance['status_filter_awaiting_review'];
			}
			if ( isset( $instance['status_filter_complete'] ) ) {
				$status_filter_complete = (bool) $instance['status_filter_complete'];
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
		<p class="challonge-widget-status_filter">
			<?php _e( 'Status Filter:', Challonge_Plugin::TEXT_DOMAIN ); ?><br />
			<label for="<?php echo $this->get_field_id( 'status_filter_pending' ); ?>">
				<input id="<?php echo $this->get_field_id( 'status_filter_pending' ); ?>" name="<?php echo $this->get_field_name( 'status_filter_pending' ); ?>" type="checkbox" <?php checked($status_filter_pending, true) ?>/> Pending
			</label>
			<label for="<?php echo $this->get_field_id( 'status_filter_underway' ); ?>">
				<input id="<?php echo $this->get_field_id( 'status_filter_underway' ); ?>" name="<?php echo $this->get_field_name( 'status_filter_underway' ); ?>" type="checkbox" <?php checked($status_filter_underway, true) ?>/> Underway
			</label>
			<label for="<?php echo $this->get_field_id( 'status_filter_complete' ); ?>">
				<input id="<?php echo $this->get_field_id( 'status_filter_complete' ); ?>" name="<?php echo $this->get_field_name( 'status_filter_complete' ); ?>" type="checkbox" <?php checked($status_filter_complete, true) ?>/> Complete
			</label>
			<label for="<?php echo $this->get_field_id( 'status_filter_awaiting_review' ); ?>" class="challonge-status_filter_awaiting_review">
				<input id="<?php echo $this->get_field_id( 'status_filter_awaiting_review' ); ?>" name="<?php echo $this->get_field_name( 'status_filter_awaiting_review' ); ?>" type="checkbox" <?php checked($status_filter_awaiting_review, true) ?>/> Awaiting Review
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Max tournaments listed:', Challonge_Plugin::TEXT_DOMAIN ); ?></label>
			<input id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="text" value="<?php echo $limit; ?>" size="3" placeholder="10" />
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance['title']                         = strip_tags( $new_instance['title']       );
		$instance['subdomain']                     = strip_tags( $new_instance['subdomain']   );
		$instance['name_filter']                   = strip_tags( $new_instance['name_filter'] );
		$instance['status_filter_pending']         = (bool) $new_instance['status_filter_pending'];
		$instance['status_filter_underway']        = (bool) $new_instance['status_filter_underway'];
		$instance['status_filter_awaiting_review'] = (bool) $new_instance['status_filter_awaiting_review'];
		$instance['status_filter_complete']        = (bool) $new_instance['status_filter_complete'];
		if ( 0 < $new_instance['limit'] )
			$instance['limit']                     = (int) $new_instance['limit'];
		else
			$instance['limit']                     = '';
		return $instance;
	}

	public function widget( $args, $instance ) {
		// Init
		$this->oCP = Challonge_Plugin::getInstance();
		$usr = wp_get_current_user();
		$options = $this->oCP->getOptions();

		// Visitor can not see widget?
		if ( empty( $usr->ID ) && empty( $options['public_widget'] ) ) {
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
			$name_filter = $instance['name_filter'];
		else
			$name_filter = null;
		$status_filter = array();
		if ( isset( $instance['status_filter_pending']         ) && $instance['status_filter_pending']         )
			$status_filter[] = 'pending';
		if ( isset( $instance['status_filter_underway']        ) && $instance['status_filter_underway']        )
			$status_filter[] = 'underway';
		if ( isset( $instance['status_filter_awaiting_review'] ) && $instance['status_filter_awaiting_review'] )
			$status_filter[] = 'awaiting_review';
		if ( isset( $instance['status_filter_complete']        ) && $instance['status_filter_complete']        )
			$status_filter[] = 'complete';
		if ( ! empty( $instance['limit'] ) )
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

		// Validate name filter
		if ( empty( $name_filter ) || ! is_string( $name_filter ) ) // Empty or invalid filter
			$name_filter = null;
		else if ( 0 !== strpos( $name_filter, '/' ) ) // Astrisk "*" Wildcard to RegEx
			$name_filter = '/' . str_replace( '\*', '.*', preg_quote( $name_filter, '/' ) ) . '/i';
		else if ( false === @preg_match( $name_filter, null ) ) // Validate RegEx - KLUDGE: Can haz alternate that doesn't use "@"?
			$name_filter = false;

		// Get tournament listing
		if ( empty( $subdomain ) ) {
			$t = $this->oApi->getTournaments();
		} else {
			$t = $this->oApi->getTournaments( array( 'subdomain' => $subdomain ) );
		}
		$tournys = array();
		if ( count( $t->tournament ) ) {
			foreach ( $t->tournament AS $tourny ) {
				if ( 'false' == $tourny->private && false !== $name_filter && ( null === $name_filter || preg_match( $name_filter, $tourny->name ) ) && ( empty( $status_filter ) || in_array( strtolower( $tourny->state ), $status_filter ) ) && ( $limit-- ) > 0 ) {
					$ret = '<li>';
					if ( strlen( $tourny->subdomain ) )
						$tname = (string) $tourny->subdomain . '-' . $tourny->url;
					else
						$tname = (string) $tourny->url;
					$lnk = $this->oCP->widgetTournyLink( $tname );
					if ( ! empty( $lnk['name'] ) ) {
						$ret .= $lnk['button_html'] . $lnk['title_html'];
					} else {
						$ret .= $lnk['title_html']; //esc_html( $tourny->name );
					}
					$ret .= '<br /><span class="challonge-info">'
							. esc_html( $lnk['participants'] ) . '/' . $lnk['signup_cap']
							. ' | ' . esc_html( ucwords( str_replace( '_', ' ', $tourny->state ) ) )
						. '</span>';
					$ret .= '</li>';
					$tournys[ $tourny->{ 'created-at' } . $tourny->id ] = $ret;
				}
			}
		}
		if ( empty( $tournys ) ) {
			$ret = '<p><em>(' . __( 'no tournaments', Challonge_Plugin::TEXT_DOMAIN ) . ')</em></p>';
		} else {
			add_thickbox();
			ksort( $tournys );
			$ret = '<ul class="challonge-widget-tournaments">' . implode( '', array_reverse( $tournys ) ) . '</ul>';
		}
		return $ret;
	}
}
