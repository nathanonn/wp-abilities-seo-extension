<?php
/**
 * Find Posts with SEO Issues ability.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Abilities;

use SeoAbilities\Errors\ErrorFactory;
use WP_Error;

/**
 * Ability to find posts with specific SEO issues.
 */
class FindPostsWithSeoIssuesAbility extends AbstractAbility {

	/**
	 * Valid issue types.
	 *
	 * @var array
	 */
	private const VALID_ISSUE_TYPES = array(
		'missing_title',
		'missing_description',
		'missing_focus_keyword',
		'missing_alt_text',
		'low_seo_score',
	);

	/**
	 * Default score threshold.
	 *
	 * @var int
	 */
	private const DEFAULT_SCORE_THRESHOLD = 50;

	/**
	 * Default limit.
	 *
	 * @var int
	 */
	private const DEFAULT_LIMIT = 20;

	/**
	 * Maximum limit.
	 *
	 * @var int
	 */
	private const MAX_LIMIT = 100;

	/**
	 * {@inheritdoc}
	 */
	public function execute( array $input ) {
		$issue_type      = $input['issue_type'];
		$score_threshold = $input['score_threshold'] ?? self::DEFAULT_SCORE_THRESHOLD;
		$post_type       = $input['post_type'] ?? null;
		$limit           = min( $input['limit'] ?? self::DEFAULT_LIMIT, self::MAX_LIMIT );
		$offset          = $input['offset'] ?? 0;

		// Validate issue type.
		if ( ! in_array( $issue_type, self::VALID_ISSUE_TYPES, true ) ) {
			return ErrorFactory::invalid_issue_type( $issue_type, self::VALID_ISSUE_TYPES );
		}

		// Determine post types to search.
		$post_types = $post_type
			? array( $post_type )
			: $this->settings->get_supported_post_types();

		// Validate post type if specified.
		if ( $post_type && ! $this->settings->is_supported_post_type( $post_type ) ) {
			return ErrorFactory::post_type_not_enabled( $post_type );
		}

		// Get results based on issue type.
		$result = $this->get_posts_with_issue(
			$issue_type,
			$post_types,
			$score_threshold,
			$limit,
			$offset
		);

		// Build response with post details.
		$posts = array();
		foreach ( $result['posts'] as $post_id ) {
			$post_data = $this->get_extended_post_data( $post_id );
			if ( empty( $post_data ) ) {
				continue;
			}

			// Get SEO score if available.
			$score_data = $this->provider->get_seo_score( $post_id );

			$posts[] = array_merge(
				$post_data,
				array(
					'seo_score'     => $score_data['seo_score'] ?? null,
					'issue_details' => $this->get_issue_details( $issue_type, $post_id, $score_threshold ),
				)
			);
		}

		return array(
			'issue_type'       => $issue_type,
			'score_threshold'  => 'low_seo_score' === $issue_type ? $score_threshold : null,
			'post_type_filter' => $post_type,
			'total_found'      => $result['total'],
			'returned_count'   => count( $posts ),
			'has_more'         => ( $offset + count( $posts ) ) < $result['total'],
			'posts'            => $posts,
		);
	}

	/**
	 * Get posts with a specific issue.
	 *
	 * @param string $issue_type      Issue type.
	 * @param array  $post_types      Post types to search.
	 * @param int    $score_threshold Score threshold (for low_seo_score).
	 * @param int    $limit           Maximum results.
	 * @param int    $offset          Results offset.
	 * @return array{posts: array, total: int}
	 */
	private function get_posts_with_issue(
		string $issue_type,
		array $post_types,
		int $score_threshold,
		int $limit,
		int $offset
	): array {
		switch ( $issue_type ) {
			case 'missing_title':
				return $this->post_service->get_posts_with_missing_meta(
					$this->provider->get_title_meta_key(),
					$post_types,
					$limit,
					$offset
				);

			case 'missing_description':
				return $this->post_service->get_posts_with_missing_meta(
					$this->provider->get_description_meta_key(),
					$post_types,
					$limit,
					$offset
				);

			case 'missing_focus_keyword':
				return $this->post_service->get_posts_with_missing_meta(
					$this->provider->get_focus_keyword_meta_key(),
					$post_types,
					$limit,
					$offset
				);

			case 'missing_alt_text':
				return $this->image_service->get_posts_with_missing_alt_text(
					$post_types,
					$limit,
					$offset
				);

			case 'low_seo_score':
				return $this->post_service->get_posts_with_low_score(
					$this->provider->get_seo_score_meta_key(),
					$score_threshold,
					$post_types,
					$limit,
					$offset
				);

			default:
				return array(
					'posts' => array(),
					'total' => 0,
				);
		}
	}

	/**
	 * Get human-readable issue details for a post.
	 *
	 * @param string $issue_type      Issue type.
	 * @param int    $post_id         Post ID.
	 * @param int    $score_threshold Score threshold.
	 * @return string Issue details.
	 */
	private function get_issue_details( string $issue_type, int $post_id, int $score_threshold ): string {
		switch ( $issue_type ) {
			case 'missing_title':
				return __( 'No custom SEO title set. Using default title template.', 'wp-abilities-seo-extension' );

			case 'missing_description':
				return __( 'No meta description set for this post.', 'wp-abilities-seo-extension' );

			case 'missing_focus_keyword':
				return __( 'No focus keyword set. SEO analysis is limited without a focus keyword.', 'wp-abilities-seo-extension' );

			case 'missing_alt_text':
				$images_data = $this->image_service->get_post_images( $post_id );
				return sprintf(
					/* translators: %d: number of images without alt text */
					_n(
						'%d image is missing alt text.',
						'%d images are missing alt text.',
						$images_data['images_without_alt'],
						'wp-abilities-seo-extension'
					),
					$images_data['images_without_alt']
				);

			case 'low_seo_score':
				$score_data = $this->provider->get_seo_score( $post_id );
				return sprintf(
					/* translators: 1: current score, 2: threshold score */
					__( 'SEO score is %1$d, which is below the threshold of %2$d.', 'wp-abilities-seo-extension' ),
					$score_data['seo_score'],
					$score_threshold
				);

			default:
				return '';
		}
	}

	/**
	 * Get the input schema for this ability.
	 *
	 * @return array JSON Schema.
	 */
	public static function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'issue_type'      => array(
					'type'        => 'string',
					'description' => __( 'Type of issue to find.', 'wp-abilities-seo-extension' ),
					'enum'        => self::VALID_ISSUE_TYPES,
				),
				'score_threshold' => array(
					'type'        => 'integer',
					'description' => __( 'For low_seo_score only: posts with scores below this value are returned. Default: 50. Range: 1-100.', 'wp-abilities-seo-extension' ),
					'minimum'     => 1,
					'maximum'     => 100,
					'default'     => self::DEFAULT_SCORE_THRESHOLD,
				),
				'post_type'       => array(
					'type'        => 'string',
					'description' => __( 'Filter by specific post type slug. Default: all enabled post types.', 'wp-abilities-seo-extension' ),
				),
				'limit'           => array(
					'type'        => 'integer',
					'description' => __( 'Maximum results to return. Default: 20. Maximum: 100.', 'wp-abilities-seo-extension' ),
					'minimum'     => 1,
					'maximum'     => self::MAX_LIMIT,
					'default'     => self::DEFAULT_LIMIT,
				),
				'offset'          => array(
					'type'        => 'integer',
					'description' => __( 'Number of results to skip for pagination. Default: 0.', 'wp-abilities-seo-extension' ),
					'minimum'     => 0,
					'default'     => 0,
				),
			),
			'required'             => array( 'issue_type' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the output schema for this ability.
	 *
	 * @return array JSON Schema.
	 */
	public static function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'issue_type'       => array(
					'type'        => 'string',
					'description' => __( 'The issue type that was searched.', 'wp-abilities-seo-extension' ),
				),
				'score_threshold'  => array(
					'type'        => array( 'integer', 'null' ),
					'description' => __( 'The score threshold used (for low_seo_score only).', 'wp-abilities-seo-extension' ),
				),
				'post_type_filter' => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'The post type filter applied (null if all types).', 'wp-abilities-seo-extension' ),
				),
				'total_found'      => array(
					'type'        => 'integer',
					'description' => __( 'Total posts matching criteria (for pagination).', 'wp-abilities-seo-extension' ),
				),
				'returned_count'   => array(
					'type'        => 'integer',
					'description' => __( 'Number of posts in this response.', 'wp-abilities-seo-extension' ),
				),
				'has_more'         => array(
					'type'        => 'boolean',
					'description' => __( 'True if more results exist beyond this page.', 'wp-abilities-seo-extension' ),
				),
				'posts'            => array(
					'type'        => 'array',
					'description' => __( 'Array of posts with issues.', 'wp-abilities-seo-extension' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'post_id'       => array(
								'type'        => 'integer',
								'description' => __( 'Post ID.', 'wp-abilities-seo-extension' ),
							),
							'post_title'    => array(
								'type'        => 'string',
								'description' => __( 'Post title.', 'wp-abilities-seo-extension' ),
							),
							'post_type'     => array(
								'type'        => 'string',
								'description' => __( 'Post type slug.', 'wp-abilities-seo-extension' ),
							),
							'post_url'      => array(
								'type'        => 'string',
								'description' => __( 'Permalink.', 'wp-abilities-seo-extension' ),
							),
							'edit_url'      => array(
								'type'        => 'string',
								'description' => __( 'WordPress admin edit URL.', 'wp-abilities-seo-extension' ),
							),
							'seo_score'     => array(
								'type'        => array( 'integer', 'null' ),
								'description' => __( 'Current SEO score (if available).', 'wp-abilities-seo-extension' ),
							),
							'issue_details' => array(
								'type'        => 'string',
								'description' => __( 'Human-readable description of the specific issue.', 'wp-abilities-seo-extension' ),
							),
						),
					),
				),
			),
		);
	}
}
