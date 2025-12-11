<?php
/**
 * Image service for parsing and validating images.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Services;

use WP_Post;

/**
 * Service for image-related utilities.
 */
class ImageService {

	/**
	 * Get all images from a post (featured image + content images).
	 *
	 * @param int $post_id Post ID.
	 * @return array{
	 *     featured_image: array|null,
	 *     content_images: array,
	 *     total_images: int,
	 *     images_with_alt: int,
	 *     images_without_alt: int,
	 *     images_orphaned: int
	 * }
	 */
	public function get_post_images( int $post_id ): array {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return array(
				'featured_image'     => null,
				'content_images'     => array(),
				'total_images'       => 0,
				'images_with_alt'    => 0,
				'images_without_alt' => 0,
				'images_orphaned'    => 0,
			);
		}

		$featured_image  = $this->get_featured_image( $post_id );
		$content_images  = $this->parse_content_images( $post->post_content );

		// Calculate totals.
		$total_images       = count( $content_images ) + ( $featured_image ? 1 : 0 );
		$images_with_alt    = 0;
		$images_without_alt = 0;
		$images_orphaned    = 0;

		// Count featured image.
		if ( $featured_image ) {
			if ( ! empty( $featured_image['alt_text'] ) ) {
				++$images_with_alt;
			} else {
				++$images_without_alt;
			}
		}

		// Count content images.
		foreach ( $content_images as $image ) {
			// Count orphaned images separately.
			if ( ! empty( $image['is_orphaned'] ) ) {
				++$images_orphaned;
				continue;
			}
			if ( ! empty( $image['alt_text'] ) ) {
				++$images_with_alt;
			} else {
				++$images_without_alt;
			}
		}

		return array(
			'featured_image'     => $featured_image,
			'content_images'     => $content_images,
			'total_images'       => $total_images,
			'images_with_alt'    => $images_with_alt,
			'images_without_alt' => $images_without_alt,
			'images_orphaned'    => $images_orphaned,
		);
	}

	/**
	 * Get featured image data.
	 *
	 * @param int $post_id Post ID.
	 * @return array|null Featured image data or null if not set.
	 */
	public function get_featured_image( int $post_id ): ?array {
		$thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( ! $thumbnail_id ) {
			return null;
		}

		return $this->get_attachment_data( (int) $thumbnail_id );
	}

	/**
	 * Parse images from post content.
	 *
	 * @param string $content Post content HTML.
	 * @return array Array of image data.
	 */
	public function parse_content_images( string $content ): array {
		if ( empty( $content ) ) {
			return array();
		}

		$images = array();

		// Match all img tags.
		preg_match_all( '/<img[^>]+>/i', $content, $matches );

		if ( empty( $matches[0] ) ) {
			return array();
		}

		foreach ( $matches[0] as $img_tag ) {
			$image_data = $this->parse_img_tag( $img_tag );
			if ( $image_data ) {
				$images[] = $image_data;
			}
		}

		return $images;
	}

	/**
	 * Parse a single img tag.
	 *
	 * @param string $img_tag HTML img tag.
	 * @return array|null Image data or null if invalid.
	 */
	private function parse_img_tag( string $img_tag ): ?array {
		// Extract src.
		if ( ! preg_match( '/src=["\']([^"\']+)["\']/i', $img_tag, $src_match ) ) {
			return null;
		}

		$url = $src_match[1];

		// Extract alt.
		$alt_text = '';
		if ( preg_match( '/alt=["\']([^"\']*)["\']/', $img_tag, $alt_match ) ) {
			$alt_text = $alt_match[1];
		}

		// Try to get attachment ID from class or data attribute.
		$referenced_id = null;

		// Check for wp-image-{id} class.
		if ( preg_match( '/wp-image-(\d+)/i', $img_tag, $class_match ) ) {
			$referenced_id = (int) $class_match[1];
		}

		// Check for data-id attribute.
		if ( ! $referenced_id && preg_match( '/data-id=["\'](\d+)["\']/i', $img_tag, $data_match ) ) {
			$referenced_id = (int) $data_match[1];
		}

		// Try to get attachment ID from URL if still not found.
		if ( ! $referenced_id ) {
			$referenced_id = $this->get_attachment_id_from_url( $url );
		}

		// Validate that the attachment actually exists in the Media Library.
		$attachment_id = null;
		$is_orphaned   = false;

		if ( $referenced_id ) {
			$attachment = get_post( $referenced_id );
			if ( $attachment && 'attachment' === $attachment->post_type ) {
				$attachment_id = $referenced_id;
			} else {
				// The HTML references an attachment that no longer exists.
				$is_orphaned = true;
			}
		}

		// Determine if external (not local URL and no valid attachment).
		$is_external = ! $attachment_id && ! $is_orphaned && ! $this->is_local_url( $url );

		// Get filename from URL.
		$filename = basename( wp_parse_url( $url, PHP_URL_PATH ) );

		// If we have a valid attachment ID, get the stored alt text (may differ from inline alt).
		$stored_alt_text = $alt_text;
		if ( $attachment_id ) {
			$stored_alt_text = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			// Use inline alt if stored is empty.
			if ( empty( $stored_alt_text ) ) {
				$stored_alt_text = $alt_text;
			}
		}

		return array(
			'attachment_id' => $attachment_id,
			'url'           => $url,
			'alt_text'      => $stored_alt_text ?: null,
			'is_external'   => $is_external,
			'is_orphaned'   => $is_orphaned,
			'referenced_id' => $is_orphaned ? $referenced_id : null,
			'filename'      => $filename,
		);
	}

	/**
	 * Get attachment data by ID.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array|null Attachment data or null if not found.
	 */
	public function get_attachment_data( int $attachment_id ): ?array {
		$attachment = get_post( $attachment_id );

		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return null;
		}

		$url      = wp_get_attachment_url( $attachment_id );
		$alt_text = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		$filename = basename( get_attached_file( $attachment_id ) );

		return array(
			'attachment_id' => $attachment_id,
			'url'           => $url,
			'alt_text'      => $alt_text ?: null,
			'filename'      => $filename,
		);
	}

	/**
	 * Check if an attachment is an image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool True if image, false otherwise.
	 */
	public function is_image_attachment( int $attachment_id ): bool {
		$mime_type = get_post_mime_type( $attachment_id );
		return $mime_type && strpos( $mime_type, 'image/' ) === 0;
	}

	/**
	 * Get MIME type of an attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string MIME type or empty string.
	 */
	public function get_attachment_mime_type( int $attachment_id ): string {
		return get_post_mime_type( $attachment_id ) ?: '';
	}

	/**
	 * Update alt text for an attachment.
	 *
	 * Note: This method returns true when the meta value is successfully stored,
	 * including when the new value is the same as the existing value.
	 * WordPress's update_post_meta returns true for new inserts, the meta_id
	 * for updates, and false only on failure. We normalize this to boolean.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $alt_text      New alt text.
	 * @return bool True on success (including when value unchanged), false on failure.
	 */
	public function update_alt_text( int $attachment_id, string $alt_text ): bool {
		$result = update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
		// update_post_meta returns false on failure, meta_id (int) on update, true on insert.
		return false !== $result;
	}

	/**
	 * Get alt text for an attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string|null Alt text or null if not set.
	 */
	public function get_alt_text( int $attachment_id ): ?string {
		$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		return $alt ?: null;
	}

	/**
	 * Find all posts that use a specific attachment in their content.
	 *
	 * Searches for posts containing images that reference the attachment ID
	 * via wp-image-{id} class or data-id attribute.
	 *
	 * @param int $attachment_id Attachment ID to search for.
	 * @return array Array of post IDs.
	 */
	public function find_posts_using_attachment( int $attachment_id ): array {
		global $wpdb;

		// Search for wp-image-{id} class pattern in post content.
		$query = $wpdb->prepare(
			"SELECT DISTINCT ID FROM {$wpdb->posts}
			WHERE post_status = 'publish'
			AND post_type NOT IN ('attachment', 'revision', 'nav_menu_item')
			AND (
				post_content LIKE %s
				OR post_content LIKE %s
			)",
			'%wp-image-' . $attachment_id . '%',
			'%data-id="' . $attachment_id . '"%'
		);

		$post_ids = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return array_map( 'intval', $post_ids );
	}

	/**
	 * Update alt text in a specific post's content for a given attachment.
	 *
	 * Finds all img tags in the post content that reference the attachment
	 * and updates their alt attribute.
	 *
	 * @param int    $post_id       Post ID to update.
	 * @param int    $attachment_id Attachment ID to match.
	 * @param string $alt_text      New alt text value.
	 * @return bool True if post was updated, false otherwise.
	 */
	public function update_alt_text_in_post_content( int $post_id, int $attachment_id, string $alt_text ): bool {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		$content     = $post->post_content;
		$new_content = $this->replace_alt_text_in_content( $content, $attachment_id, $alt_text );

		// If content didn't change, no need to update.
		if ( $content === $new_content ) {
			return false;
		}

		// Update the post.
		$result = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $new_content,
			),
			true
		);

		return ! is_wp_error( $result );
	}

	/**
	 * Replace alt text in content for img tags referencing a specific attachment.
	 *
	 * @param string $content       Post content.
	 * @param int    $attachment_id Attachment ID to match.
	 * @param string $alt_text      New alt text value.
	 * @return string Modified content.
	 */
	private function replace_alt_text_in_content( string $content, int $attachment_id, string $alt_text ): string {
		// Escape alt text for use in HTML attribute.
		$escaped_alt = esc_attr( $alt_text );

		// Pattern to match img tags with wp-image-{id} class or data-id="{id}".
		$pattern = '/(<img\s[^>]*(?:class="[^"]*wp-image-' . $attachment_id . '[^"]*"|data-id="' . $attachment_id . '")[^>]*)(\/?>)/i';

		return preg_replace_callback(
			$pattern,
			function ( $matches ) use ( $escaped_alt ) {
				$img_tag = $matches[1];
				$closing = $matches[2];

				// Check if alt attribute already exists.
				if ( preg_match( '/\salt=["\'][^"\']*["\']/', $img_tag ) ) {
					// Replace existing alt attribute.
					$img_tag = preg_replace( '/\salt=["\'][^"\']*["\']/', ' alt="' . $escaped_alt . '"', $img_tag );
				} else {
					// Add alt attribute before closing.
					$img_tag .= ' alt="' . $escaped_alt . '"';
				}

				return $img_tag . $closing;
			},
			$content
		);
	}

	/**
	 * Sync alt text to all posts that use the attachment.
	 *
	 * Finds all posts containing the attachment and updates the alt text
	 * in the inline img tags to match the media library.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $alt_text      New alt text value.
	 * @return array Array of post IDs that were updated.
	 */
	public function sync_alt_text_to_posts( int $attachment_id, string $alt_text ): array {
		$post_ids     = $this->find_posts_using_attachment( $attachment_id );
		$updated_posts = array();

		foreach ( $post_ids as $post_id ) {
			if ( $this->update_alt_text_in_post_content( $post_id, $attachment_id, $alt_text ) ) {
				$updated_posts[] = $post_id;
			}
		}

		return $updated_posts;
	}

	/**
	 * Try to get attachment ID from URL.
	 *
	 * @param string $url Image URL.
	 * @return int|null Attachment ID or null if not found.
	 */
	private function get_attachment_id_from_url( string $url ): ?int {
		$attachment_id = attachment_url_to_postid( $url );

		if ( $attachment_id ) {
			return $attachment_id;
		}

		// Try without size suffix (e.g., -300x200).
		$url_without_size = preg_replace( '/-\d+x\d+(?=\.[a-z]+$)/i', '', $url );
		if ( $url_without_size !== $url ) {
			$attachment_id = attachment_url_to_postid( $url_without_size );
			if ( $attachment_id ) {
				return $attachment_id;
			}
		}

		return null;
	}

	/**
	 * Check if a URL is local to this WordPress installation.
	 *
	 * @param string $url URL to check.
	 * @return bool True if local, false if external.
	 */
	private function is_local_url( string $url ): bool {
		$site_url  = wp_parse_url( home_url(), PHP_URL_HOST );
		$image_url = wp_parse_url( $url, PHP_URL_HOST );

		return $site_url === $image_url;
	}

	/**
	 * Get posts with images missing alt text.
	 *
	 * This method uses a two-phase approach for efficiency:
	 * 1. First, identify posts that have featured images without alt text using a direct DB query
	 * 2. Then, paginate through posts that contain <img> tags in content and check for alt text issues
	 *
	 * Note: For sites with many posts, this may still require iterating through content.
	 * Consider adding caching or background processing for very large sites.
	 *
	 * @param array $post_types Post types to search.
	 * @param int   $limit      Maximum results.
	 * @param int   $offset     Results offset.
	 * @return array{posts: array, total: int} Posts and total count.
	 */
	public function get_posts_with_missing_alt_text(
		array $post_types,
		int $limit = 20,
		int $offset = 0
	): array {
		global $wpdb;

		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

		// Phase 1: Get posts with featured images missing alt text (efficient DB query).
		$featured_query = $wpdb->prepare(
			"SELECT DISTINCT p.ID
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm_thumb ON p.ID = pm_thumb.post_id AND pm_thumb.meta_key = '_thumbnail_id'
			LEFT JOIN {$wpdb->postmeta} pm_alt ON pm_thumb.meta_value = pm_alt.post_id AND pm_alt.meta_key = '_wp_attachment_image_alt'
			WHERE p.post_type IN ({$placeholders})
			AND p.post_status = 'publish'
			AND (pm_alt.meta_value IS NULL OR pm_alt.meta_value = '')",
			$post_types
		);

		$posts_with_featured_issues = $wpdb->get_col( $featured_query ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$posts_with_featured_issues = array_map( 'intval', $posts_with_featured_issues );

		// Phase 2: Get posts that contain images in content (use LIKE for efficiency).
		$content_query = $wpdb->prepare(
			"SELECT p.ID
			FROM {$wpdb->posts} p
			WHERE p.post_type IN ({$placeholders})
			AND p.post_status = 'publish'
			AND p.post_content LIKE %s
			ORDER BY p.post_date DESC",
			array_merge( $post_types, array( '%<img%' ) )
		);

		$posts_with_images = $wpdb->get_col( $content_query ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Combine: start with featured image issues, then check content images.
		$posts_with_issues = $posts_with_featured_issues;

		foreach ( $posts_with_images as $post_id ) {
			$post_id = (int) $post_id;
			// Skip if already in the list from featured image check.
			if ( in_array( $post_id, $posts_with_issues, true ) ) {
				continue;
			}
			// Check if content images have alt text issues.
			if ( $this->post_has_content_images_without_alt( $post_id ) ) {
				$posts_with_issues[] = $post_id;
			}
		}

		// Sort by post date (most recent first) - get post dates for sorting.
		usort(
			$posts_with_issues,
			function ( $a, $b ) {
				$post_a = get_post( $a );
				$post_b = get_post( $b );
				if ( ! $post_a || ! $post_b ) {
					return 0;
				}
				return strtotime( $post_b->post_date ) - strtotime( $post_a->post_date );
			}
		);

		$total = count( $posts_with_issues );

		// Apply pagination.
		$posts_with_issues = array_slice( $posts_with_issues, $offset, $limit );

		return array(
			'posts' => $posts_with_issues,
			'total' => $total,
		);
	}

	/**
	 * Check if a post has content images without alt text.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True if has content images without alt text.
	 */
	private function post_has_content_images_without_alt( int $post_id ): bool {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		$content_images = $this->parse_content_images( $post->post_content );
		foreach ( $content_images as $image ) {
			if ( empty( $image['alt_text'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if a post has any images without alt text.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True if has images without alt text.
	 */
	private function post_has_images_without_alt( int $post_id ): bool {
		$images_data = $this->get_post_images( $post_id );
		return $images_data['images_without_alt'] > 0;
	}
}
