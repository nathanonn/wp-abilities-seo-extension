<?php
/**
 * Get Social Meta ability.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Abilities;

use WP_Error;

/**
 * Ability to retrieve social media meta data for a post.
 */
class GetSocialMetaAbility extends AbstractAbility {

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

		// Get social meta from provider.
		$social_meta = $this->provider->get_social_meta( $post_id );

		return array(
			'post_id'  => $post_id,
			'facebook' => $social_meta['facebook'],
			'twitter'  => $social_meta['twitter'],
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
					'description' => __( 'The ID of the post to retrieve social meta for.', 'wp-abilities-seo-extension' ),
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
				'post_id'  => array(
					'type'        => 'integer',
					'description' => __( 'The post ID.', 'wp-abilities-seo-extension' ),
				),
				'facebook' => array(
					'type'        => 'object',
					'description' => __( 'Facebook Open Graph data.', 'wp-abilities-seo-extension' ),
					'properties'  => array(
						'title'       => array(
							'type'        => array( 'string', 'null' ),
							'description' => __( 'OG title.', 'wp-abilities-seo-extension' ),
						),
						'description' => array(
							'type'        => array( 'string', 'null' ),
							'description' => __( 'OG description.', 'wp-abilities-seo-extension' ),
						),
						'image'       => array(
							'type'        => array( 'string', 'null' ),
							'description' => __( 'OG image URL.', 'wp-abilities-seo-extension' ),
						),
						'image_id'    => array(
							'type'        => array( 'integer', 'null' ),
							'description' => __( 'OG image attachment ID.', 'wp-abilities-seo-extension' ),
						),
					),
				),
				'twitter'  => array(
					'type'        => 'object',
					'description' => __( 'Twitter Card data.', 'wp-abilities-seo-extension' ),
					'properties'  => array(
						'use_facebook' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether Twitter uses Facebook data as fallback.', 'wp-abilities-seo-extension' ),
						),
						'title'        => array(
							'type'        => array( 'string', 'null' ),
							'description' => __( 'Twitter title (if different from Facebook).', 'wp-abilities-seo-extension' ),
						),
						'description'  => array(
							'type'        => array( 'string', 'null' ),
							'description' => __( 'Twitter description.', 'wp-abilities-seo-extension' ),
						),
						'image'        => array(
							'type'        => array( 'string', 'null' ),
							'description' => __( 'Twitter image URL.', 'wp-abilities-seo-extension' ),
						),
						'image_id'     => array(
							'type'        => array( 'integer', 'null' ),
							'description' => __( 'Twitter image attachment ID.', 'wp-abilities-seo-extension' ),
						),
					),
				),
			),
		);
	}
}
