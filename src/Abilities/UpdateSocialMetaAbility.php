<?php
/**
 * Update Social Meta ability.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Abilities;

use SeoAbilities\Errors\ErrorFactory;
use WP_Error;

/**
 * Ability to update social media meta data for a post.
 */
class UpdateSocialMetaAbility extends AbstractAbility {

	/**
	 * Valid fields that can be updated.
	 *
	 * @var array
	 */
	private const VALID_FIELDS = array(
		'facebook_title',
		'facebook_description',
		'facebook_image_id',
		'twitter_use_facebook',
		'twitter_title',
		'twitter_description',
		'twitter_image_id',
	);

	/**
	 * {@inheritdoc}
	 */
	public function execute( array $input ) {
		$post_id = (int) $input['post_id'];

		// Validate post and permission.
		$error = $this->validate_post_and_permission( $post_id, 'update' );
		if ( $error ) {
			return $error;
		}

		// Extract update data.
		$update_data = array();
		foreach ( self::VALID_FIELDS as $field ) {
			if ( array_key_exists( $field, $input ) ) {
				$update_data[ $field ] = $input[ $field ];
			}
		}

		// Check that at least one field is provided.
		if ( empty( $update_data ) ) {
			return ErrorFactory::no_fields_to_update( self::VALID_FIELDS );
		}

		// Validate image attachments if provided.
		if ( isset( $update_data['facebook_image_id'] ) && $update_data['facebook_image_id'] > 0 ) {
			$error = $this->validate_image_attachment( (int) $update_data['facebook_image_id'] );
			if ( $error ) {
				return $error;
			}
		}

		if ( isset( $update_data['twitter_image_id'] ) && $update_data['twitter_image_id'] > 0 ) {
			$error = $this->validate_image_attachment( (int) $update_data['twitter_image_id'] );
			if ( $error ) {
				return $error;
			}
		}

		// Update social meta via provider.
		$updated_fields = $this->provider->update_social_meta( $post_id, $update_data );

		// Get current values after update.
		$social_meta = $this->provider->get_social_meta( $post_id );

		return array(
			'success'        => true,
			'post_id'        => $post_id,
			'updated_fields' => $updated_fields,
			'current_values' => $social_meta,
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
				'post_id'              => array(
					'type'        => 'integer',
					'description' => __( 'The ID of the post to update.', 'wp-abilities-seo-extension' ),
					'minimum'     => 1,
				),
				'facebook_title'       => array(
					'type'        => 'string',
					'description' => __( 'Facebook OG title.', 'wp-abilities-seo-extension' ),
				),
				'facebook_description' => array(
					'type'        => 'string',
					'description' => __( 'Facebook OG description.', 'wp-abilities-seo-extension' ),
				),
				'facebook_image_id'    => array(
					'type'        => 'integer',
					'description' => __( 'Attachment ID for Facebook image (must be a valid image in Media Library). Use 0 to clear.', 'wp-abilities-seo-extension' ),
					'minimum'     => 0,
				),
				'twitter_use_facebook' => array(
					'type'        => 'boolean',
					'description' => __( 'Set to true to use Facebook data for Twitter.', 'wp-abilities-seo-extension' ),
				),
				'twitter_title'        => array(
					'type'        => 'string',
					'description' => __( 'Twitter-specific title (ignored if twitter_use_facebook is true).', 'wp-abilities-seo-extension' ),
				),
				'twitter_description'  => array(
					'type'        => 'string',
					'description' => __( 'Twitter-specific description (ignored if twitter_use_facebook is true).', 'wp-abilities-seo-extension' ),
				),
				'twitter_image_id'     => array(
					'type'        => 'integer',
					'description' => __( 'Attachment ID for Twitter image (must be a valid image in Media Library). Use 0 to clear.', 'wp-abilities-seo-extension' ),
					'minimum'     => 0,
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
				'success'        => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the update was successful.', 'wp-abilities-seo-extension' ),
				),
				'post_id'        => array(
					'type'        => 'integer',
					'description' => __( 'The post ID that was updated.', 'wp-abilities-seo-extension' ),
				),
				'updated_fields' => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => __( 'List of field names that were updated.', 'wp-abilities-seo-extension' ),
				),
				'current_values' => array(
					'type'        => 'object',
					'description' => __( 'Current social meta values after update.', 'wp-abilities-seo-extension' ),
				),
			),
		);
	}
}
