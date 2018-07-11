<?php

function podcasting_xmlns() {
	echo "\n\t" . 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"' . "\n";
}

add_action( 'rss2_ns', 'podcasting_xmlns' );

function podcasting_bloginfo_rss_name( $output ) {
	$title = get_option( 'podcasting_title' );
	if ( empty( $title ) ) {
		$title = get_bloginfo( 'name' );
		$category = get_category( Automattic_Podcasting::podcasting_get_podcasting_category_id() );
		$output = "$title &#187; {$category->name}";
	} else {
		$output = $title;
	}

	return $output;
}

add_filter( 'wp_title_rss', 'podcasting_bloginfo_rss_name' );

function podcasting_modify_default_feed_description( $value, $field ) {
	if ( $field === 'description' ) {
		return get_option( 'podcasting_summary', '' );
	}
	return $value;
}

add_filter( 'bloginfo_rss', 'podcasting_modify_default_feed_description', 10, 2 );

function podcasting_feed_head() {
	$subtitle = get_option( 'podcasting_subtitle' );

	if ( empty( $subtitle ) ) {
		$subtitle = get_bloginfo( 'description' );
	}

	if ( ! empty( $subtitle ) ) {
		echo '<itunes:subtitle>' . esc_html( strip_tags( $subtitle ) ) . "</itunes:subtitle>\n";
	}

	$summary = get_option( 'podcasting_summary' );

	if ( ! empty( $summary ) ) {
		echo '<itunes:summary>' . esc_html( strip_tags( $summary ) ) . "</itunes:summary>\n";
	}

	$author = get_option( 'podcasting_talent_name' );

	if ( ! empty( $author ) ) {
		echo '<itunes:author>' . esc_html( strip_tags( $author ) ) . "</itunes:author>\n";
	}

	$email = get_option( 'podcasting_email' );

	if ( ! empty( $email ) ) {
		echo '<itunes:owner>';
		echo '<itunes:email>' . esc_html( strip_tags ( $email ) ) . "</itunes:email>\n";
		echo '</itunes:owner>';
	}

	$copyright = get_option( 'podcasting_copyright' );

	if ( !empty( $copyright ) ) {
		echo '<copyright>' . esc_html( strip_tags( $copyright ) ) . "</copyright>\n";
	}

	$explicit = get_option( 'podcasting_explicit' );

	echo '<itunes:explicit>';

	if ( empty( $explicit ) ) {
		echo 'no';
	} else {
		echo esc_html( $explicit );
	}

	echo "</itunes:explicit>\n";

	$image = Automattic_Podcasting::podcasting_get_image_url();

	if ( ! empty( $image ) ) {
		if ( function_exists( 'jetpack_photon_url' ) ) {
			$image = jetpack_photon_url( $image, array( 'fit' => '3000,3000' ), 'https' );
		}

		echo "<itunes:image href='" . esc_url( $image ) . "' />\n";
	}

	$keywords = get_option( 'podcasting_keywords' );

	if ( ! empty( $keywords ) ) {
		echo '<itunes:keywords>' . esc_html( $keywords ) . "</itunes:keywords>\n";
	}

	$category_1 = podcasting_generate_category( 'podcasting_category_1' );

	if ( ! empty( $category_1 ) ) {
		echo $category_1;
	}

	$category_2 = podcasting_generate_category( 'podcasting_category_2' );

	if ( ! empty( $category_2 ) ) {
		echo $category_2;
	}

	$category_3 = podcasting_generate_category( 'podcasting_category_3' );

	if ( ! empty( $category_3 ) ) {
		echo $category_3;
	}
}

add_action( 'rss2_head', 'podcasting_feed_head' );

function podcasting_feed_item() {
	global $post;

	$post_meta = get_post_meta( $post->ID, 'podcast_episode', true );

	$author = get_the_author();
	if ( empty( $author ) ) {
		$author = get_option( 'podcasting_talent_name' );
	}

	echo "<itunes:author>" . esc_html( $author ) . "</itunes:author>\n";

	$explicit = get_option( 'podcasting_explicit' );

	echo "<itunes:explicit>";

	if ( empty( $explicit ) ) {
		echo 'no';
	} else {
		echo esc_html( $explicit );
	}

	echo "</itunes:explicit>\n";

	if ( has_post_thumbnail( $post->ID ) ) {
		$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'post-thumbnail' );
		if ( ! empty( $image ) ) {
			if ( is_array( $image ) ) {
				$image = $image[0];
			}
			echo "<itunes:image href='" . esc_url( $image ) . "' />\n";
		}
	}

	$keywords = '';
	if ( ! empty( $keywords ) ) {
		echo '<itunes:keywords>' . esc_html( $keywords ) . "</itunes:keywords>\n";
	}

	// Summary fallback order: custom excerpt > auto-generated post excerpt > empty string.
	$excerpt = apply_filters( 'the_excerpt_rss', get_the_excerpt() );

	echo "<itunes:summary>" . esc_html( strip_tags( $excerpt ) ) . "</itunes:summary>\n";

	$subtitle = wp_trim_words( $excerpt, 10, '&#8230;' );

	echo "<itunes:subtitle>" . esc_html( $subtitle ) . "</itunes:subtitle>\n";

	if ( ! empty( $post_meta['enclosure'] ) ) {
		echo "<enclosure url='" . esc_url( $post_meta['enclosure']['url'] ) . "' length='" . esc_html( $post_meta['enclosure']['length'] ) . "' type='" . esc_html( $post_meta['enclosure']['mime'] ) . "' />\n";
	}

	// TODO <itunes:duration>7:10</itunes:duration>; iTunes seems to figure this out on it's own. Would be nice to have in the future
}

add_action( 'rss2_item', 'podcasting_feed_item' );

function podcasting_rss_enclosure( $enclosure ) {
	global $post;

	$post_meta = get_post_meta( $post->ID, 'podcast_episode', true );

	if ( empty( $post_meta['enclosure'] ) ) {
		return $enclosure;
	}

	return '';
}

add_filter( 'rss_enclosure', 'podcasting_rss_enclosure' );

/**
 * Generate the category elements from the given option (e.g. podcasting_category_1)
 * @param  string $option option to retrieve via get_option
 * @return string The category tag that can be echoed into the feed
 */
function podcasting_generate_category( $option ) {
	$category = get_option( $option );
	$strcat = '';
	switch ( $category ) {
		case 'Education,Education' :
			$category = 'Education';
			break;
		case 'Education,Education Technology' :
			$category = 'Education, Educational Technology';
			break;
		case 'Tech News' :
			$category = 'Technology,Tech News';
			break;
		case 'Sports &amp; Recreation,Technology' :
			$category = 'Technology';
			break;
		case 'Sports &amp; Recreation,Gadgets' :
			$category = 'Technology,Gadgets';
			break;
	}

	if ( ! empty( $category ) ) {
		$splits = explode( ',', $category );
		if ( 2 == count( $splits ) ) {
			$strcat .= "<itunes:category text='" . esc_html( $splits[0] ) . "'>\n";
			$strcat .= "\t<itunes:category text='" . esc_html( $splits[1] ) . "' />\n";
			$strcat .= "</itunes:category>\n";
		} else {
			$strcat .= "<itunes:category text='" . esc_html( $category ) . "' />\n";
		}
	}

	return $strcat;
}

function podcasting_empty_rss_excerpt( $output ) {
	$excerpt = get_the_excerpt();

	if ( empty( $excerpt ) ) {
		return '';
	}

	return $output;
}
add_filter( 'the_excerpt_rss', 'podcasting_empty_rss_excerpt', 1000 ); // Run it super late after any other filters may have inserted something
