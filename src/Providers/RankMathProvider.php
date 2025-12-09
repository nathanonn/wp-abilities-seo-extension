<?php
/**
 * Rank Math SEO provider implementation.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Providers;

/**
 * Rank Math SEO provider.
 * Implements ProviderInterface using Rank Math's meta keys and APIs.
 */
class RankMathProvider implements ProviderInterface {

	/**
	 * Meta keys used by Rank Math.
	 *
	 * @var array<string, string>
	 */
	private const META_KEYS = array(
		'title'            => 'rank_math_title',
		'description'      => 'rank_math_description',
		'focus_keyword'    => 'rank_math_focus_keyword',
		'seo_score'        => 'rank_math_seo_score',
		'facebook_title'   => 'rank_math_facebook_title',
		'facebook_desc'    => 'rank_math_facebook_description',
		'facebook_image'   => 'rank_math_facebook_image',
		'facebook_image_id' => 'rank_math_facebook_image_id',
		'twitter_title'    => 'rank_math_twitter_title',
		'twitter_desc'     => 'rank_math_twitter_description',
		'twitter_image'    => 'rank_math_twitter_image',
		'twitter_image_id' => 'rank_math_twitter_image_id',
		'twitter_use_fb'   => 'rank_math_twitter_use_facebook',
	);

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'Rank Math';
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_active(): bool {
		return class_exists( '\\RankMath\\Helper' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_seo_meta( int $post_id ): array {
		$title       = $this->get_meta( $post_id, 'title' );
		$description = $this->get_meta( $post_id, 'description' );
		$keyword     = $this->get_meta( $post_id, 'focus_keyword' );

		return array(
			'seo_title'                  => $title ?: null,
			'seo_description'            => $description ?: null,
			'focus_keyword'              => $keyword ?: null,
			'is_seo_title_default'       => empty( $title ),
			'is_seo_description_default' => empty( $description ),
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function update_seo_meta( int $post_id, array $data ): array {
		$updated_fields = array();

		if ( array_key_exists( 'seo_title', $data ) ) {
			$this->update_meta( $post_id, 'title', $data['seo_title'] );
			$updated_fields[] = 'seo_title';
		}

		if ( array_key_exists( 'seo_description', $data ) ) {
			$this->update_meta( $post_id, 'description', $data['seo_description'] );
			$updated_fields[] = 'seo_description';
		}

		if ( array_key_exists( 'focus_keyword', $data ) ) {
			$this->update_meta( $post_id, 'focus_keyword', $data['focus_keyword'] );
			$updated_fields[] = 'focus_keyword';
		}

		return $updated_fields;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_social_meta( int $post_id ): array {
		// Facebook / Open Graph.
		$fb_title    = $this->get_meta( $post_id, 'facebook_title' );
		$fb_desc     = $this->get_meta( $post_id, 'facebook_desc' );
		$fb_image    = $this->get_meta( $post_id, 'facebook_image' );
		$fb_image_id = $this->get_meta( $post_id, 'facebook_image_id' );

		// Twitter.
		$tw_use_fb   = $this->get_meta( $post_id, 'twitter_use_fb' );
		$tw_title    = $this->get_meta( $post_id, 'twitter_title' );
		$tw_desc     = $this->get_meta( $post_id, 'twitter_desc' );
		$tw_image    = $this->get_meta( $post_id, 'twitter_image' );
		$tw_image_id = $this->get_meta( $post_id, 'twitter_image_id' );

		return array(
			'facebook' => array(
				'title'       => $fb_title ?: null,
				'description' => $fb_desc ?: null,
				'image'       => $fb_image ?: null,
				'image_id'    => $fb_image_id ? (int) $fb_image_id : null,
			),
			'twitter'  => array(
				'use_facebook' => 'on' === $tw_use_fb || '1' === $tw_use_fb,
				'title'        => $tw_title ?: null,
				'description'  => $tw_desc ?: null,
				'image'        => $tw_image ?: null,
				'image_id'     => $tw_image_id ? (int) $tw_image_id : null,
			),
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function update_social_meta( int $post_id, array $data ): array {
		$updated_fields = array();

		// Facebook fields.
		if ( array_key_exists( 'facebook_title', $data ) ) {
			$this->update_meta( $post_id, 'facebook_title', $data['facebook_title'] );
			$updated_fields[] = 'facebook_title';
		}

		if ( array_key_exists( 'facebook_description', $data ) ) {
			$this->update_meta( $post_id, 'facebook_desc', $data['facebook_description'] );
			$updated_fields[] = 'facebook_description';
		}

		if ( array_key_exists( 'facebook_image_id', $data ) ) {
			$image_id = (int) $data['facebook_image_id'];
			$this->update_meta( $post_id, 'facebook_image_id', $image_id );
			// Also update the image URL.
			$image_url = $image_id ? wp_get_attachment_url( $image_id ) : '';
			$this->update_meta( $post_id, 'facebook_image', $image_url );
			$updated_fields[] = 'facebook_image_id';
		}

		// Twitter fields.
		if ( array_key_exists( 'twitter_use_facebook', $data ) ) {
			$value = $data['twitter_use_facebook'] ? 'on' : '';
			$this->update_meta( $post_id, 'twitter_use_fb', $value );
			$updated_fields[] = 'twitter_use_facebook';
		}

		if ( array_key_exists( 'twitter_title', $data ) ) {
			$this->update_meta( $post_id, 'twitter_title', $data['twitter_title'] );
			$updated_fields[] = 'twitter_title';
		}

		if ( array_key_exists( 'twitter_description', $data ) ) {
			$this->update_meta( $post_id, 'twitter_desc', $data['twitter_description'] );
			$updated_fields[] = 'twitter_description';
		}

		if ( array_key_exists( 'twitter_image_id', $data ) ) {
			$image_id = (int) $data['twitter_image_id'];
			$this->update_meta( $post_id, 'twitter_image_id', $image_id );
			// Also update the image URL.
			$image_url = $image_id ? wp_get_attachment_url( $image_id ) : '';
			$this->update_meta( $post_id, 'twitter_image', $image_url );
			$updated_fields[] = 'twitter_image_id';
		}

		return $updated_fields;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_seo_score( int $post_id ): array {
		$score   = (int) $this->get_meta( $post_id, 'seo_score' );
		$keyword = $this->get_meta( $post_id, 'focus_keyword' );

		// Determine rating based on score.
		$rating = 'Poor';
		if ( $score >= 71 ) {
			$rating = 'Good';
		} elseif ( $score >= 51 ) {
			$rating = 'OK';
		}

		// Get detailed test results if available.
		$tests_passed  = array();
		$tests_warning = array();
		$tests_failed  = array();

		// Try to get SEO analysis data from Rank Math if available.
		$analysis_data = $this->get_seo_analysis_data( $post_id );
		if ( $analysis_data ) {
			$tests_passed  = $analysis_data['passed'] ?? array();
			$tests_warning = $analysis_data['warning'] ?? array();
			$tests_failed  = $analysis_data['failed'] ?? array();
		}

		return array(
			'seo_score'     => $score,
			'seo_rating'    => $rating,
			'focus_keyword' => $keyword ?: null,
			'tests_passed'  => $tests_passed,
			'tests_warning' => $tests_warning,
			'tests_failed'  => $tests_failed,
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function has_score_support(): bool {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_title_meta_key(): string {
		return self::META_KEYS['title'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description_meta_key(): string {
		return self::META_KEYS['description'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_focus_keyword_meta_key(): string {
		return self::META_KEYS['focus_keyword'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_seo_score_meta_key(): string {
		return self::META_KEYS['seo_score'];
	}

	/**
	 * Get a meta value using the appropriate method.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key (without prefix).
	 * @return mixed Meta value.
	 */
	private function get_meta( int $post_id, string $key ) {
		$meta_key = self::META_KEYS[ $key ] ?? '';
		if ( empty( $meta_key ) ) {
			return '';
		}

		return get_post_meta( $post_id, $meta_key, true );
	}

	/**
	 * Update a meta value.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key (without prefix).
	 * @param mixed  $value   Value to set.
	 * @return bool True on success, false on failure.
	 */
	private function update_meta( int $post_id, string $key, $value ): bool {
		$meta_key = self::META_KEYS[ $key ] ?? '';
		if ( empty( $meta_key ) ) {
			return false;
		}

		// Delete meta if value is empty string.
		if ( '' === $value ) {
			return delete_post_meta( $post_id, $meta_key );
		}

		return (bool) update_post_meta( $post_id, $meta_key, $value );
	}

	/**
	 * Get SEO analysis data from Rank Math.
	 *
	 * @param int $post_id Post ID.
	 * @return array|null Analysis data or null if not available.
	 */
	private function get_seo_analysis_data( int $post_id ): ?array {
		// Rank Math stores detailed SEO analysis in a different way.
		// For now, we'll return basic structure. In a more complete implementation,
		// we could parse Rank Math's internal analysis data.

		$focus_keyword = $this->get_meta( $post_id, 'focus_keyword' );
		$post          = get_post( $post_id );

		if ( ! $post ) {
			return null;
		}

		$tests_passed  = array();
		$tests_warning = array();
		$tests_failed  = array();

		// Basic checks we can perform.
		$seo_title       = $this->get_meta( $post_id, 'title' );
		$seo_description = $this->get_meta( $post_id, 'description' );

		// Check: Focus keyword set.
		if ( ! empty( $focus_keyword ) ) {
			$tests_passed[] = array(
				'test_id' => 'focus_keyword_set',
				'label'   => __( 'Focus Keyword Set', 'wp-abilities-seo-extension' ),
			);

			// Check: Keyword in title.
			$title_to_check = $seo_title ?: $post->post_title;
			if ( stripos( $title_to_check, $focus_keyword ) !== false ) {
				$tests_passed[] = array(
					'test_id' => 'keyword_in_title',
					'label'   => __( 'Keyword in Title', 'wp-abilities-seo-extension' ),
				);
			} else {
				$tests_failed[] = array(
					'test_id'    => 'keyword_in_title',
					'label'      => __( 'Keyword in Title', 'wp-abilities-seo-extension' ),
					'message'    => __( 'Focus keyword does not appear in the SEO title.', 'wp-abilities-seo-extension' ),
					'suggestion' => __( 'Add the focus keyword to your SEO title to improve search visibility.', 'wp-abilities-seo-extension' ),
				);
			}

			// Check: Keyword in description.
			if ( ! empty( $seo_description ) && stripos( $seo_description, $focus_keyword ) !== false ) {
				$tests_passed[] = array(
					'test_id' => 'keyword_in_description',
					'label'   => __( 'Keyword in Meta Description', 'wp-abilities-seo-extension' ),
				);
			} elseif ( ! empty( $seo_description ) ) {
				$tests_warning[] = array(
					'test_id'    => 'keyword_in_description',
					'label'      => __( 'Keyword in Meta Description', 'wp-abilities-seo-extension' ),
					'message'    => __( 'Focus keyword does not appear in the meta description.', 'wp-abilities-seo-extension' ),
					'suggestion' => __( 'Consider adding the focus keyword to your meta description for better relevance.', 'wp-abilities-seo-extension' ),
				);
			}
		} else {
			$tests_failed[] = array(
				'test_id'    => 'focus_keyword_set',
				'label'      => __( 'Focus Keyword Set', 'wp-abilities-seo-extension' ),
				'message'    => __( 'No focus keyword is set for this post.', 'wp-abilities-seo-extension' ),
				'suggestion' => __( 'Set a focus keyword to enable full SEO analysis and optimization suggestions.', 'wp-abilities-seo-extension' ),
			);
		}

		// Check: SEO title set.
		if ( ! empty( $seo_title ) ) {
			$title_length = strlen( $seo_title );
			if ( $title_length <= 60 ) {
				$tests_passed[] = array(
					'test_id' => 'title_length',
					'label'   => __( 'SEO Title Length', 'wp-abilities-seo-extension' ),
				);
			} else {
				$tests_warning[] = array(
					'test_id'    => 'title_length',
					'label'      => __( 'SEO Title Length', 'wp-abilities-seo-extension' ),
					'message'    => sprintf(
						/* translators: %d: character count */
						__( 'SEO title is %d characters long, which may be truncated in search results.', 'wp-abilities-seo-extension' ),
						$title_length
					),
					'suggestion' => __( 'Keep your SEO title under 60 characters for optimal display in search results.', 'wp-abilities-seo-extension' ),
				);
			}
		} else {
			$tests_warning[] = array(
				'test_id'    => 'title_set',
				'label'      => __( 'Custom SEO Title', 'wp-abilities-seo-extension' ),
				'message'    => __( 'No custom SEO title set. Using default title template.', 'wp-abilities-seo-extension' ),
				'suggestion' => __( 'Set a custom SEO title for better control over search appearance.', 'wp-abilities-seo-extension' ),
			);
		}

		// Check: Meta description set.
		if ( ! empty( $seo_description ) ) {
			$desc_length = strlen( $seo_description );
			if ( $desc_length <= 160 ) {
				$tests_passed[] = array(
					'test_id' => 'description_length',
					'label'   => __( 'Meta Description Length', 'wp-abilities-seo-extension' ),
				);
			} else {
				$tests_warning[] = array(
					'test_id'    => 'description_length',
					'label'      => __( 'Meta Description Length', 'wp-abilities-seo-extension' ),
					'message'    => sprintf(
						/* translators: %d: character count */
						__( 'Meta description is %d characters long, which may be truncated in search results.', 'wp-abilities-seo-extension' ),
						$desc_length
					),
					'suggestion' => __( 'Keep your meta description under 160 characters for optimal display in search results.', 'wp-abilities-seo-extension' ),
				);
			}
		} else {
			$tests_failed[] = array(
				'test_id'    => 'description_set',
				'label'      => __( 'Meta Description Set', 'wp-abilities-seo-extension' ),
				'message'    => __( 'No meta description set for this post.', 'wp-abilities-seo-extension' ),
				'suggestion' => __( 'Add a compelling meta description to improve click-through rates from search results.', 'wp-abilities-seo-extension' ),
			);
		}

		return array(
			'passed'  => $tests_passed,
			'warning' => $tests_warning,
			'failed'  => $tests_failed,
		);
	}
}
