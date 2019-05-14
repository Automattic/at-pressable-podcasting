<?php
/**
 * Register the widget for use in Appearance -> Widgets
 */
add_action( 'widgets_init', 'podcasting_widget_init' );

function podcasting_widget_init() {
	register_widget( 'Podcast_Widget' );
}

class Podcast_Widget extends WP_Widget {
	function __construct() {
		parent::__construct( 'podcast', __( 'Podcast' ), array(
			'classname'   => 'widget-podcast',
			'description' => esc_html__( 'Display information about your Podcast and an allow visitors to subscribe via iTunes' ),
		) );
	}

	function widget( $args, $instance ) {
		if ( ! Automattic_Podcasting::podcasting_is_enabled() ) {
			return;
		}

		$podcast_category = get_category( Automattic_Podcasting::podcasting_get_podcasting_category_id() );

		if ( empty( $podcast_category ) || empty( $podcast_category->slug ) ) {
			return;
		}

		echo $args['before_widget'];

		$title = apply_filters( 'widget_title', $instance['title'] );
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];

		$podcast_title     = get_option( 'podcasting_title'     );
		$podcast_subtitle  = get_option( 'podcasting_subtitle'  );
		$podcast_summary   = get_option( 'podcasting_summary'   );
		$podcast_copyright = get_option( 'podcasting_copyright' );
		$podcast_image     = get_option( 'podcasting_image'     );

		if ( ! empty( $instance['itunes_feed_id'] ) ) {
			$subscribe_url = 'http://www.itunes.com/podcast?id=' . urlencode( $instance['itunes_feed_id'] );
		} else {
			$subscribe_url = 'itpc://' . str_replace( 'http://', '', site_url( '/category/' . esc_attr( $podcast_category->slug ) . '/feed/', 'http' ) );
		}

		if ( ! empty( $podcast_title ) ) {
			echo '<h3 class="podcast_title">' . esc_html( $podcast_title ) . '</h3>';
		}

		if ( ! empty( $podcast_image ) ) {
			$podcast_srcset = $podcast_image;
			if ( function_exists( 'jetpack_photon_url' ) ) {
				$podcast_image  = jetpack_photon_url( $podcast_image, array( 'fit' => '300,300' ), 'https' );
				$podcast_srcset =
					esc_url( jetpack_photon_url( $podcast_image, array( 'fit' => '150,150' ), 'https' ) ) . ', ' .
					esc_url( jetpack_photon_url( $podcast_image, array( 'fit' => '300,300' ), 'https' ) ) . ' 2x, ' .
					esc_url( jetpack_photon_url( $podcast_image, array( 'fit' => '450,450' ), 'https' ) ) . ' 3x';
			}
			echo '<a href="' . $subscribe_url . '"><img src="' . esc_url( $podcast_image ) . '" srcset="' . $podcast_srcset . '" /></a>';
		}

		if ( ! empty( $podcast_subtitle ) ) {
			echo '<h4 class="podcast_subtitle">' . esc_html( $podcast_subtitle ) . '</h4>';
		}

		if ( ! empty( $podcast_summary ) ) {
			echo '<p class="podcast_summary">' . esc_html( $podcast_summary ) . '</p>';
		}

		if ( ! empty( $podcast_copyright ) ) {
			echo '<p class="podcast_copyright">' . esc_html( $podcast_copyright ) . '</p>';
		}
		
		// TODO use a fancy image?
		echo '<p><a href="' . $subscribe_url . '">Subscribe via iTunes</a></p>';

		echo $args['after_widget'];
		do_action( 'jetpack_stats_extra', 'widget_view', 'podcasting' );
	}

	function form( $instance ) {
		$title          = isset( $instance['title'] )          ? $instance['title']          : '';
		$itunes_feed_id = isset( $instance['itunes_feed_id'] ) ? $instance['itunes_feed_id'] : '';
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
				<?php esc_html_e( 'Title' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'itunes_feed_id' ); ?>">
				<?php esc_html_e( 'iTunes Feed ID' ); ?> (<a href="http://www.apple.com/itunes/podcasts/specs.html#submitting">?</a>) <input class="widefat" id="<?php echo $this->get_field_id( 'itunes_feed_id' ); ?>" name="<?php echo $this->get_field_name( 'itunes_feed_id' ); ?>" type="text" value="<?php echo esc_attr( $itunes_feed_id ); ?>" />
			</label>
		</p>

		<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance                   = array();
		$instance['title']          = isset( $new_instance['title'] )          ? wp_kses( $new_instance['title'], array() ) : '';
		$instance['itunes_feed_id'] = isset( $new_instance['itunes_feed_id'] ) ? $new_instance['itunes_feed_id']            : '';

		return $instance;
	}
}
