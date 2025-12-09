<?php
/**
 * Post service for post validation and utilities.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Services;

use WP_Post;

/**
 * Service for post-related utilities.
 */
class PostService {

	/**
	 * Get a post by ID.
	 *
	 * @param int $post_id Post ID.
	 * @return WP_Post|null Post object or null if not found.
	 */
	public function get_post( int $post_id ): ?WP_Post {
		$post = get_post( $post_id );
		return $post instanceof WP_Post ? $post : null;
	}

	/**
	 * Check if a post exists.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True if exists, false otherwise.
	 */
	public function post_exists( int $post_id ): bool {
		return null !== $this->get_post( $post_id );
	}

	/**
	 * Get basic post data for response.
	 *
	 * @param int $post_id Post ID.
	 * @return array Post data array.
	 */
	public function get_post_data( int $post_id ): array {
		$post = $this->get_post( $post_id );

		if ( ! $post ) {
			return array();
		}

		return array(
			'post_id'    => $post_id,
			'post_title' => $post->post_title,
			'post_type'  => $post->post_type,
			'post_url'   => get_permalink( $post_id ),
		);
	}

	/**
	 * Get extended post data including edit URL.
	 *
	 * @param int $post_id Post ID.
	 * @return array Extended post data array.
	 */
	public function get_extended_post_data( int $post_id ): array {
		$data = $this->get_post_data( $post_id );

		if ( empty( $data ) ) {
			return array();
		}

		$data['edit_url'] = get_edit_post_link( $post_id, 'raw' );

		return $data;
	}

	/**
	 * Check if user can edit a post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True if user can edit, false otherwise.
	 */
	public function can_edit_post( int $post_id ): bool {
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Get posts with missing meta field.
	 *
	 * @param string $meta_key   Meta key to check.
	 * @param array  $post_types Post types to search.
	 * @param int    $limit      Maximum results.
	 * @param int    $offset     Results offset.
	 * @return array{posts: array, total: int} Posts and total count.
	 */
	public function get_posts_with_missing_meta(
		string $meta_key,
		array $post_types,
		int $limit = 20,
		int $offset = 0
	): array {
		global $wpdb;

		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

		// Count total.
		$count_query = $wpdb->prepare(
			"SELECT COUNT(DISTINCT p.ID)
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
			WHERE p.post_type IN ({$placeholders})
			AND p.post_status = 'publish'
			AND (pm.meta_value IS NULL OR pm.meta_value = '')",
			array_merge( array( $meta_key ), $post_types )
		);

		$total = (int) $wpdb->get_var( $count_query ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Get posts.
		$posts_query = $wpdb->prepare(
			"SELECT DISTINCT p.ID
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
			WHERE p.post_type IN ({$placeholders})
			AND p.post_status = 'publish'
			AND (pm.meta_value IS NULL OR pm.meta_value = '')
			ORDER BY p.post_date DESC
			LIMIT %d OFFSET %d",
			array_merge( array( $meta_key ), $post_types, array( $limit, $offset ) )
		);

		$post_ids = $wpdb->get_col( $posts_query ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array(
			'posts' => array_map( 'intval', $post_ids ),
			'total' => $total,
		);
	}

	/**
	 * Get posts with low SEO score.
	 *
	 * @param string $meta_key        SEO score meta key.
	 * @param int    $score_threshold Score threshold (posts below this are returned).
	 * @param array  $post_types      Post types to search.
	 * @param int    $limit           Maximum results.
	 * @param int    $offset          Results offset.
	 * @return array{posts: array, total: int} Posts and total count.
	 */
	public function get_posts_with_low_score(
		string $meta_key,
		int $score_threshold,
		array $post_types,
		int $limit = 20,
		int $offset = 0
	): array {
		global $wpdb;

		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

		// Count total.
		$count_query = $wpdb->prepare(
			"SELECT COUNT(DISTINCT p.ID)
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
			WHERE p.post_type IN ({$placeholders})
			AND p.post_status = 'publish'
			AND CAST(pm.meta_value AS UNSIGNED) < %d
			AND CAST(pm.meta_value AS UNSIGNED) > 0",
			array_merge( array( $meta_key ), $post_types, array( $score_threshold ) )
		);

		$total = (int) $wpdb->get_var( $count_query ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Get posts ordered by score (lowest first).
		$posts_query = $wpdb->prepare(
			"SELECT DISTINCT p.ID, CAST(pm.meta_value AS UNSIGNED) as score
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
			WHERE p.post_type IN ({$placeholders})
			AND p.post_status = 'publish'
			AND CAST(pm.meta_value AS UNSIGNED) < %d
			AND CAST(pm.meta_value AS UNSIGNED) > 0
			ORDER BY score ASC, p.post_date DESC
			LIMIT %d OFFSET %d",
			array_merge( array( $meta_key ), $post_types, array( $score_threshold, $limit, $offset ) )
		);

		$results  = $wpdb->get_results( $posts_query ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$post_ids = array_map(
			function ( $row ) {
				return (int) $row->ID;
			},
			$results
		);

		return array(
			'posts' => $post_ids,
			'total' => $total,
		);
	}
}
