<?php
/**
 * Get Post Content ability.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Abilities;

use WP_Error;

/**
 * Ability to retrieve post content with metadata for context understanding.
 */
class GetPostContentAbility extends AbstractAbility {

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

		$post = $this->post_service->get_post( $post_id );

		// Calculate word count from content (strip HTML tags first).
		$content_text = wp_strip_all_tags( $post->post_content );
		$word_count   = str_word_count( $content_text );

		// Get post data.
		$post_data = $this->get_post_data( $post_id );

		return array(
			'post_id'       => $post_id,
			'post_title'    => $post_data['post_title'],
			'post_type'     => $post_data['post_type'],
			'post_url'      => $post_data['post_url'],
			'post_excerpt'  => $post->post_excerpt ?: null,
			'post_content'  => $post->post_content,
			'word_count'    => $word_count,
			'publish_date'  => $post->post_date ? gmdate( 'c', strtotime( $post->post_date ) ) : null,
			'modified_date' => $post->post_modified ? gmdate( 'c', strtotime( $post->post_modified ) ) : null,
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
					'description' => __( 'The ID of the post to retrieve content from.', 'wp-abilities-seo-extension' ),
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
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id'       => array(
					'type'        => 'integer',
					'description' => __( 'The post ID.', 'wp-abilities-seo-extension' ),
				),
				'post_title'    => array(
					'type'        => 'string',
					'description' => __( 'The post title.', 'wp-abilities-seo-extension' ),
				),
				'post_type'     => array(
					'type'        => 'string',
					'description' => __( 'The post type (post, page, etc.).', 'wp-abilities-seo-extension' ),
				),
				'post_url'      => array(
					'type'        => 'string',
					'format'      => 'uri',
					'description' => __( 'The public URL of the post.', 'wp-abilities-seo-extension' ),
				),
				'post_excerpt'  => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'The post excerpt (null if not set).', 'wp-abilities-seo-extension' ),
				),
				'post_content'  => array(
					'type'        => 'string',
					'description' => __( 'The raw post content (may contain HTML, shortcodes, or block markup).', 'wp-abilities-seo-extension' ),
				),
				'word_count'    => array(
					'type'        => 'integer',
					'description' => __( 'Approximate word count of the content (excluding HTML).', 'wp-abilities-seo-extension' ),
				),
				'publish_date'  => array(
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'description' => __( 'The publish date in ISO 8601 format.', 'wp-abilities-seo-extension' ),
				),
				'modified_date' => array(
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'description' => __( 'The last modified date in ISO 8601 format.', 'wp-abilities-seo-extension' ),
				),
			),
		);
	}
}
