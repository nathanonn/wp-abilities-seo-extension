<?php
/**
 * Get SEO Score ability.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Abilities;

use WP_Error;

/**
 * Ability to retrieve SEO score and analysis for a post.
 */
class GetSeoScoreAbility extends AbstractAbility {

	/**
	 * {@inheritdoc}
	 */
	public function execute( array $input ) {
		$post_id = (int) $input['post_id'];

		// Validate post and permission.
		$error = $this->validate_post_and_permission( $post_id, 'view' );
		if ( $error ) {
			return $error;
		}

		// Get SEO score from provider.
		$score_data = $this->provider->get_seo_score( $post_id );

		return array(
			'post_id'       => $post_id,
			'seo_score'     => $score_data['seo_score'],
			'seo_rating'    => $score_data['seo_rating'],
			'focus_keyword' => $score_data['focus_keyword'],
			'tests_passed'  => $score_data['tests_passed'],
			'tests_warning' => $score_data['tests_warning'],
			'tests_failed'  => $score_data['tests_failed'],
		);
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
				'post_id' => array(
					'type'        => 'integer',
					'description' => __( 'The ID of the post to analyze.', 'wp-abilities-seo-extension' ),
					'minimum'     => 1,
				),
			),
			'required'             => array( 'post_id' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the output schema for this ability.
	 *
	 * @return array JSON Schema.
	 */
	public static function get_output_schema(): array {
		$test_result_schema = array(
			'type'       => 'object',
			'properties' => array(
				'test_id' => array(
					'type'        => 'string',
					'description' => __( 'Test identifier.', 'wp-abilities-seo-extension' ),
				),
				'label'   => array(
					'type'        => 'string',
					'description' => __( 'Human-readable test name.', 'wp-abilities-seo-extension' ),
				),
			),
		);

		$test_failure_schema = array(
			'type'       => 'object',
			'properties' => array(
				'test_id'    => array(
					'type'        => 'string',
					'description' => __( 'Test identifier.', 'wp-abilities-seo-extension' ),
				),
				'label'      => array(
					'type'        => 'string',
					'description' => __( 'Human-readable test name.', 'wp-abilities-seo-extension' ),
				),
				'message'    => array(
					'type'        => 'string',
					'description' => __( 'Message explaining the issue.', 'wp-abilities-seo-extension' ),
				),
				'suggestion' => array(
					'type'        => 'string',
					'description' => __( 'Actionable suggestion for improvement.', 'wp-abilities-seo-extension' ),
				),
			),
		);

		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id'       => array(
					'type'        => 'integer',
					'description' => __( 'The post ID.', 'wp-abilities-seo-extension' ),
				),
				'seo_score'     => array(
					'type'        => 'integer',
					'description' => __( 'Overall SEO score (0-100).', 'wp-abilities-seo-extension' ),
					'minimum'     => 0,
					'maximum'     => 100,
				),
				'seo_rating'    => array(
					'type'        => 'string',
					'description' => __( 'Rating label: "Good" (71-100), "OK" (51-70), or "Poor" (0-50).', 'wp-abilities-seo-extension' ),
					'enum'        => array( 'Good', 'OK', 'Poor' ),
				),
				'focus_keyword' => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'The focus keyword being analyzed (null if not set).', 'wp-abilities-seo-extension' ),
				),
				'tests_passed'  => array(
					'type'        => 'array',
					'description' => __( 'List of passed SEO tests.', 'wp-abilities-seo-extension' ),
					'items'       => $test_result_schema,
				),
				'tests_warning' => array(
					'type'        => 'array',
					'description' => __( 'List of tests with warnings.', 'wp-abilities-seo-extension' ),
					'items'       => $test_failure_schema,
				),
				'tests_failed'  => array(
					'type'        => 'array',
					'description' => __( 'List of failed tests.', 'wp-abilities-seo-extension' ),
					'items'       => $test_failure_schema,
				),
			),
		);
	}
}
