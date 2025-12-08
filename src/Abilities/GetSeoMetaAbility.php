<?php
/**
 * Get SEO Meta ability.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Abilities;

use WP_Error;

/**
 * Ability to retrieve SEO meta data for a post.
 */
class GetSeoMetaAbility extends AbstractAbility {

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

		// Get SEO meta from provider.
		$seo_meta = $this->provider->get_seo_meta( $post_id );

		// Build and return response.
		return array_merge(
			$this->get_post_data( $post_id ),
			$seo_meta
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
					'description' => __( 'The ID of the post to retrieve SEO meta for. Must be a valid post ID for an enabled post type.', 'wp-abilities-seo-extension' ),
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
				'post_id'                    => array(
					'type'        => 'integer',
					'description' => __( 'The post ID.', 'wp-abilities-seo-extension' ),
				),
				'post_title'                 => array(
					'type'        => 'string',
					'description' => __( 'The post\'s WordPress title (for reference).', 'wp-abilities-seo-extension' ),
				),
				'post_type'                  => array(
					'type'        => 'string',
					'description' => __( 'The post type (post, page, or CPT slug).', 'wp-abilities-seo-extension' ),
				),
				'post_url'                   => array(
					'type'        => 'string',
					'format'      => 'uri',
					'description' => __( 'The permalink of the post.', 'wp-abilities-seo-extension' ),
				),
				'seo_title'                  => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'The SEO meta title, or null if not set.', 'wp-abilities-seo-extension' ),
				),
				'seo_description'            => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'The SEO meta description, or null if not set.', 'wp-abilities-seo-extension' ),
				),
				'focus_keyword'              => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'The focus keyword, or null if not set.', 'wp-abilities-seo-extension' ),
				),
				'is_seo_title_default'       => array(
					'type'        => 'boolean',
					'description' => __( 'True if using the SEO plugin\'s default title template.', 'wp-abilities-seo-extension' ),
				),
				'is_seo_description_default' => array(
					'type'        => 'boolean',
					'description' => __( 'True if using the SEO plugin\'s default description.', 'wp-abilities-seo-extension' ),
				),
			),
		);
	}
}
