<?php
/**
 * Provider interface for SEO plugins.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Providers;

/**
 * Interface that all SEO plugin providers must implement.
 * This enables multi-plugin support in future versions.
 */
interface ProviderInterface {

	/**
	 * Get the provider name.
	 *
	 * @return string Provider name (e.g., "Rank Math", "Yoast SEO").
	 */
	public function get_name(): string;

	/**
	 * Check if the SEO plugin is active and usable.
	 *
	 * @return bool True if active, false otherwise.
	 */
	public function is_active(): bool;

	// =========================================================================
	// SEO Meta Methods
	// =========================================================================

	/**
	 * Get SEO meta data for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array{
	 *     seo_title: string|null,
	 *     seo_description: string|null,
	 *     focus_keyword: string|null,
	 *     is_seo_title_default: bool,
	 *     is_seo_description_default: bool
	 * }
	 */
	public function get_seo_meta( int $post_id ): array;

	/**
	 * Update SEO meta data for a post.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data    Data to update (seo_title, seo_description, focus_keyword).
	 * @return array List of updated field names.
	 */
	public function update_seo_meta( int $post_id, array $data ): array;

	// =========================================================================
	// Social Meta Methods
	// =========================================================================

	/**
	 * Get social meta data for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array{
	 *     facebook: array{
	 *         title: string|null,
	 *         description: string|null,
	 *         image: string|null,
	 *         image_id: int|null
	 *     },
	 *     twitter: array{
	 *         use_facebook: bool,
	 *         title: string|null,
	 *         description: string|null,
	 *         image: string|null,
	 *         image_id: int|null
	 *     }
	 * }
	 */
	public function get_social_meta( int $post_id ): array;

	/**
	 * Update social meta data for a post.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data    Data to update.
	 * @return array List of updated field names.
	 */
	public function update_social_meta( int $post_id, array $data ): array;

	// =========================================================================
	// SEO Score Methods
	// =========================================================================

	/**
	 * Get SEO score and analysis for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array{
	 *     seo_score: int,
	 *     seo_rating: string,
	 *     focus_keyword: string|null,
	 *     tests_passed: array,
	 *     tests_warning: array,
	 *     tests_failed: array
	 * }
	 */
	public function get_seo_score( int $post_id ): array;

	/**
	 * Check if the provider supports SEO score functionality.
	 *
	 * @return bool True if supported, false otherwise.
	 */
	public function has_score_support(): bool;

	// =========================================================================
	// Meta Key Accessors (for direct queries)
	// =========================================================================

	/**
	 * Get the meta key for SEO title.
	 *
	 * @return string Meta key.
	 */
	public function get_title_meta_key(): string;

	/**
	 * Get the meta key for SEO description.
	 *
	 * @return string Meta key.
	 */
	public function get_description_meta_key(): string;

	/**
	 * Get the meta key for focus keyword.
	 *
	 * @return string Meta key.
	 */
	public function get_focus_keyword_meta_key(): string;

	/**
	 * Get the meta key for SEO score.
	 *
	 * @return string Meta key.
	 */
	public function get_seo_score_meta_key(): string;
}
