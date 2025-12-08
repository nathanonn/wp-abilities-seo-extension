<?php
/**
 * Update SEO Meta ability.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Abilities;

use SeoAbilities\Errors\ErrorFactory;
use WP_Error;

/**
 * Ability to update SEO meta data for a post.
 */
class UpdateSeoMetaAbility extends AbstractAbility {

	/**
	 * Valid fields that can be updated.
	 *
	 * @var array
	 */
	private const VALID_FIELDS = array( 'seo_title', 'seo_description', 'focus_keyword' );

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

		// Update SEO meta via provider.
		$updated_fields = $this->provider->update_seo_meta( $post_id, $update_data );

		// Get current values after update.
		$seo_meta = $this->provider->get_seo_meta( $post_id );

		return array(
			'success'        => true,
			'post_id'        => $post_id,
			'updated_fields' => $updated_fields,
			'current_values' => array(
				'seo_title'       => $seo_meta['seo_title'],
				'seo_description' => $seo_meta['seo_description'],
				'focus_keyword'   => $seo_meta['focus_keyword'],
			),
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
				'post_id'         => array(
					'type'        => 'integer',
					'description' => __( 'The ID of the post to update.', 'wp-abilities-seo-extension' ),
					'minimum'     => 1,
				),
				'seo_title'       => array(
					'type'        => 'string',
					'description' => __( 'New SEO meta title. Recommended max 60 characters for optimal display in search results. Provide empty string to clear.', 'wp-abilities-seo-extension' ),
				),
				'seo_description' => array(
					'type'        => 'string',
					'description' => __( 'New SEO meta description. Recommended max 160 characters for optimal display in search results. Provide empty string to clear.', 'wp-abilities-seo-extension' ),
				),
				'focus_keyword'   => array(
					'type'        => 'string',
					'description' => __( 'New focus keyword for SEO analysis. Provide empty string to clear.', 'wp-abilities-seo-extension' ),
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
					'description' => __( 'Object containing current values after update.', 'wp-abilities-seo-extension' ),
					'properties'  => array(
						'seo_title'       => array(
							'type' => array( 'string', 'null' ),
						),
						'seo_description' => array(
							'type' => array( 'string', 'null' ),
						),
						'focus_keyword'   => array(
							'type' => array( 'string', 'null' ),
						),
					),
				),
			),
		);
	}
}
