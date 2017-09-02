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
	protected $aStatuses;
	protected $sCached;
	protected $sExpires;

	public function __construct() {
		$this->aStatuses = Challonge_Plugin::$aStatuses;
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
		foreach ($this->aStatuses AS $status)
			$status_filter_{$status} = false;
		$status_filter_unknown = false;
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
			foreach ($this->aStatuses AS $status)
			{
				if ( isset( $instance['status_filter_' . $status] ) ) {
					$status_filter_{$status} = (bool) $instance['status_filter_' . $status];
				}
			}
			if ( isset( $instance['status_filter_unknown'] ) ) {
				$status_filter_unknown = (bool) $instance['status_filter_unknown'];
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
			<?php foreach ($this->aStatuses AS $status) { ?>
				<label for="<?php echo $this->get_field_id( 'status_filter_' . $status ); ?>">
					<input id="<?php echo $this->get_field_id( 'status_filter_' . $status ); ?>" name="<?php echo $this->get_field_name( 'status_filter_' . $status ); ?>" type="checkbox" <?php checked($status_filter_{$status}, true) ?>/> <?php echo ucwords( str_replace( '_', ' ', $status ) ); ?>
				</label>
			<?php } ?>
			<label for="<?php echo $this->get_field_id( 'status_filter_unknown' ); ?>">
				<input id="<?php echo $this->get_field_id( 'status_filter_unknown' ); ?>" name="<?php echo $this->get_field_name( 'status_filter_unknown' ); ?>" type="checkbox" <?php checked($status_filter_unknown, true) ?>/> <em>Unknown/Other</em>
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
		foreach ($this->aStatuses AS $status)
			$instance['status_filter_' . $status]  = (bool) $new_instance['status_filter_' . $status];
		$instance['status_filter_unknown']         = (bool) $new_instance['status_filter_unknown'];
		if ( 0 < $new_instance['limit'] )
			$instance['limit']                     = (int) $new_instance['limit'];
		else
			$instance['limit']                     = '';
		return $instance;
	}

	public function widget( $args, $instance ) {
		// Init
		$this->oCP = Challonge_Plugin::getInstance();
		$this->oApi = $this->oCP->getApi();
		$usr = wp_get_current_user();
		$options = $this->oCP->getOptions();

		// Visitor can not see widget?
		if ( empty( $usr->ID ) && empty( $options['public_widget'] ) ) {
			return;
		}

		// Widget output
		if ( empty( $args['ajax_content_only'] ) ) {
			echo $args['before_widget'];
			echo $args['before_title'];
			if ( ! empty( $instance['title'] ) ) {
				echo esc_html( $instance['title'] );
			} else {
				echo __( Challonge_Plugin::TITLE, Challonge_Plugin::TEXT_DOMAIN );
			}
			echo $args['after_title'];
		}
		$content = $this->content( $instance );
		echo '<div class="challonge-widget-content">';
		echo $content;
		echo '<time datetime="' . $this->sCached . '" data-expires="' . $this->sExpires . '"'
				. ' data-widgetid="' . $args['widget_id'] . '" class="challonge-freshness' . ( $options['caching_freshness'] ? '' : ' challonge-hide-freshness' ) . ' dashicons-before dashicons-update">'
				. 'about '
				. human_time_diff( (new DateTime( $this->sCached ))->getTimestamp(), (new DateTime)->getTimestamp())
				. ' ago'
			. '</time>';
		echo '</div>';
		if ( empty( $args['ajax_content_only'] ) ) {
			echo $args['after_widget'];
		}
	}

	public function content( $instance ) {
		// Init
		$this->oCP = Challonge_Plugin::getInstance();
		$options = $this->oCP->getOptions();
		$this->oApi = $this->oCP->getApi();
		$usr = wp_get_current_user();
		$ajaxurl = admin_url( 'admin-ajax.php' );
		if ( isset( $instance['subdomain'] ) && preg_match( '/^(?:https?\:\/\/)?([\w\-]+)/i', $instance['subdomain'], $m ) )
			$subdomain = $m[1];
		else
			$subdomain = null;
		if ( isset( $instance['name_filter'] ) )
			$name_filter = $instance['name_filter'];
		else
			$name_filter = null;
		$status_filter = array();
		foreach ($this->aStatuses AS $status)
			if ( isset( $instance['status_filter_' . $status] ) && $instance['status_filter_' . $status] )
				$status_filter[] = $status;
		if ( isset( $instance['status_filter_unknown']        ) && $instance['status_filter_unknown']    )
			$status_filter_unknown = true;
		else
			$status_filter_unknown = false;
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
		elseif ( 0 !== strpos( $name_filter, '/' ) ) // Astrisk "*" Wildcard to RegEx
			$name_filter = '/' . str_replace( '\*', '.*', preg_quote( $name_filter, '/' ) ) . '/i';
		elseif ( false === @preg_match( $name_filter, null ) ) // Validate RegEx - KLUDGE: Can haz alternate that doesn't use "@"?
			$name_filter = false;

		// Get tournament listing
		if ( empty( $subdomain ) ) {
			$t = $this->oApi->fromCache()->getTournaments();
		} else {
			$t = $this->oApi->fromCache()->getTournaments( array( 'subdomain' => $subdomain ) );
		}
		$this->sCached = $this->oApi->getCacheDate();
		$this->sExpires = $this->oApi->getCacheExpireDate();
		$tournys = array();
		if ( ! empty( $t->tournament ) ) {
			foreach ( $t->tournament AS $tourny ) {
				if (
					( 'false' == $tourny->private || $options['public_ignore_exclusion'] )
					&& false !== $name_filter
					&& ( null === $name_filter || preg_match( $name_filter, $tourny->name ) )
					&& (
						( empty( $status_filter ) && ! $status_filter_unknown )
						|| in_array( strtolower( $tourny->state ), $status_filter )
						|| (
							! in_array( strtolower( $tourny->state ), $this->aStatuses )
							&& $status_filter_unknown
						)
					)
				) {
					$ret = '<li>';
					if ( strlen( $tourny->subdomain ) )
						$tname = (string) $tourny->subdomain . '-' . $tourny->url;
					else
						$tname = (string) $tourny->url;
					$lnk = $this->oCP->widgetTournyLink( $tname );
					if ( ! empty( $lnk->name ) ) {
						$ret .= $lnk->button_html;
					}
					$ret .= $lnk->title_html;
					$ret .= '<br /><span class="challonge-info">'
							. esc_html( $lnk->participants_count ) . '/' . $lnk->signup_cap
							. ' | ' . esc_html( ucwords( str_replace( '_', ' ', $tourny->state ) ) )
						. '</span>';
					$ret .= '</li>';
					$tournys[ $tourny->{ 'created-at' } . $tourny->id ] = $ret;
				}
			}
		}
		if ( empty( $t ) ) {
			$ret = '<p><em>' . __( 'Sorry, the tournament listing is unavailable. Please try again later.', Challonge_Plugin::TEXT_DOMAIN ) . '</em></p>';
		} elseif ( empty( $tournys ) ) {
			$ret = '<p><em>' . __( '(no tournaments)', Challonge_Plugin::TEXT_DOMAIN ) . '</em></p>';
		} else {
			add_thickbox();
			ksort( $tournys );
			$ret = '<ul class="challonge-widget-tournaments">' . implode( '', array_slice( array_reverse( $tournys ), 0, $limit ) ) . '</ul>';
		}
		return $ret;
	}
}
