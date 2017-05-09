<?php

function podcasting_add_meta_box() {
	add_meta_box( 'podcasting', __( 'Podcasting' ), 'podcasting_meta_box_html', 'post', 'advanced' );
}

add_action( 'add_meta_boxes', 'podcasting_add_meta_box' );

function podcasting_meta_box_html( $post ) {
	$options = wp_parse_args( 
						get_post_meta( $post->ID, 'podcast_episode', true ),
						array(
							'closed_captioned'  => 'no',
							'explicit_content'  => 'no',
							'podcast_enclosure' => '',
						) );

	wp_nonce_field( plugin_basename( __FILE__ ), 'podcasting' );

	$enclosure_url = ( isset( $options['enclosure']['url'] ) ) ? $options['enclosure']['url'] : '';
	?>

	<p>
		<label for="podcast_closed_captioned">
			<?php esc_html_e( 'Closed Captioned' ); ?>
			<input type="checkbox" id="podcast_closed_captioned" name="podcast_closed_captioned" <?php checked( $options['closed_captioned'], 'yes', false ); ?> />
		</label>
	</p>

	<p>
		<label for="podcast_explicit_content">
			<?php esc_html_e( 'Explicit Content' ); ?>
			<select id="podcast_explicit_content" name="podcast_explicit_content">
				<option value="no"<?php selected( $options['explicit_content'], 'no', false ); ?>><?php esc_html_e( 'No' ); ?></option>
				<option value="yes"<?php selected( $options['explicit_content'], 'yes', false ); ?>><?php esc_html_e( 'Yes' ); ?></option>
				<option value="clean"<?php selected( $options['explicit_content'], 'clean', false ); ?>><?php esc_html_e( 'Clean' ); ?></option>
			</select>
		</label>
	</p>

	<p>
		<label for="podcasting-enclosure-url"><?php esc_html_e( 'Enclosure' ); ?></label>
		<input type="text" id="podcasting-enclosure-url" name="podcast_enclosure_url" value="<?php echo esc_url( $enclosure_url ); ?>" size="35" />
		<input type="button" id="podcasting-enclosure-button" value="<?php echo esc_attr__( 'Choose File' ); ?>" class="button">
	</p>

	<p class="howto"><?php esc_html_e( 'Optional: Use this field if you have more than one audio/video file in your post.' ); ?></p>

	<p><a href="http://en.support.wordpress.com/audio/podcasting/" target="_blank"><?php esc_html_e( 'Need help? No problem! Check out our support documentation.' ); ?></a></p>

	<?php
}

function podcasting_save_meta_box( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return;

	if ( ! wp_verify_nonce( $_POST['podcasting'], plugin_basename( __FILE__ ) ) || 'post' != $_POST['post_type'] || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$podcast_options = array(
		'closed_captioned'  => 'no',
		'explicit_content'  => 'no',
		'podcast_enclosure' => '',
	);

	if ( isset( $_POST['podcast_closed_captioned'] ) && 'on' == $_POST['podcast_closed_captioned'] )
		$podcast_options['closed_captioned'] = 'yes';

	if ( isset( $_POST['podcast_explicit_content'] ) && in_array( $_POST['podcast_explicit_content'], array( 'yes', 'no', 'clean' ) ) )
		$podcast_options['explicit_content'] = $_POST['podcast_explicit_content'];

	if ( isset( $_POST['podcast_enclosure_url'] ) && ! empty( $_POST['podcast_enclosure_url'] ) ) {
		$url = $_POST['podcast_enclosure_url'];

		// Modeled after WordPress do_enclose()
		if ( $headers = wp_get_http_headers( $url ) ) {
			if ( ! empty( $headers['location'] ) ) {
				$headers = wp_get_http_headers( $headers['location'] );
			}

			$len           = isset( $headers['content-length'] ) ? (int) $headers['content-length'] : 0;
			$type          = isset( $headers['content-type'] )   ? $headers['content-type']         : '';
			$allowed_types = array( 'video', 'audio' );

			// Check to see if we can figure out the mime type from the extension
			$url_parts = @parse_url( $url );
			if ( false !== $url_parts ) {
				$extension = pathinfo( $url_parts['path'], PATHINFO_EXTENSION );
				if ( ! empty( $extension ) ) {
					foreach ( wp_get_mime_types() as $exts => $mime ) {
						if ( preg_match( '!^(' . $exts . ')$!i', $extension ) ) {
							$type = $mime;
							break;
						}
					}
				}
			}

			if ( in_array( substr( $type, 0, strpos( $type, '/' ) ), $allowed_types ) ) {
				$podcast_options['enclosure'] = array(
					'url'    => esc_url_raw( $url ),
					'length' => $len,
					'mime'   => $type,
				);
			}
		}
	}

	update_post_meta( $post_id, 'podcast_episode', $podcast_options );
}

add_action( 'save_post', 'podcasting_save_meta_box' );

function podcasting_edit_post_enqueues( $hook_suffix ) {
	$screens = array(
		'post.php',
		'post-new.php'
	);

	if ( ! in_array( $hook_suffix, $screens ) )
		return;

	wp_enqueue_script(
		'podcasting_edit_post_screen',
		plugin_dir_url( __FILE__ ) . 'podcasting-edit-post.js',
		array( 'jquery', 'media-upload' ),
		'20120911',
		true
	);

	wp_localize_script( 'podcasting_edit_post_screen', 'Podcasting', array(
		'postID'     => get_the_ID(),
		'modalUrl'   => podcasting_get_media_modal_url(),
	) );
}

add_action( 'admin_enqueue_scripts', 'podcasting_edit_post_enqueues' );

function podcasting_get_media_modal_url() {
	$post_id = get_the_ID();

	$url = 'media-upload.php';

	$query = array(
		'type'      => 'audio',
		'post_id'   => $post_id,
		'tab'       => 'library',
		'TB_iframe' => 'true',
	);

	$url = add_query_arg( $query, $url );

	return esc_url( $url );
}
