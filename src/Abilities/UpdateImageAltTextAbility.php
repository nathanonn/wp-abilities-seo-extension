<?php
/**
 * Update Image Alt Text ability.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Abilities;

use WP_Error;

/**
 * Ability to update alt text for a specific image.
 */
class UpdateImageAltTextAbility extends AbstractAbility {

	/**
	 * {@inheritdoc}
	 */
	public function execute( array $input ) {
		$attachment_id = (int) $input['attachment_id'];
		$alt_text      = $input['alt_text'];

		// Validate image attachment.
		$error = $this->validate_image_attachment( $attachment_id );
		if ( $error ) {
			return $error;
		}

		// Get previous alt text.
		$previous_alt_text = $this->image_service->get_alt_text( $attachment_id );

		// Update alt text in media library.
		$this->image_service->update_alt_text( $attachment_id, $alt_text );

		// Sync alt text to all posts that use this image.
		$posts_updated = $this->image_service->sync_alt_text_to_posts( $attachment_id, $alt_text );

		// Get attachment data.
		$attachment_data = $this->image_service->get_attachment_data( $attachment_id );

		return array(
			'success'           => true,
			'attachment_id'     => $attachment_id,
			'previous_alt_text' => $previous_alt_text,
			'new_alt_text'      => $alt_text,
			'image_url'         => $attachment_data['url'],
			'image_filename'    => $attachment_data['filename'],
			'posts_updated'     => $posts_updated,
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
				'attachment_id' => array(
					'type'        => 'integer',
					'description' => __( 'The attachment ID of the image in the Media Library.', 'wp-abilities-seo-extension' ),
					'minimum'     => 1,
				),
				'alt_text'      => array(
					'type'        => 'string',
					'description' => __( 'New alt text for the image. Should be descriptive and relevant to the image content.', 'wp-abilities-seo-extension' ),
				),
			),
			'required'             => array( 'attachment_id', 'alt_text' ),
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
				'success'           => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the update was successful.', 'wp-abilities-seo-extension' ),
				),
				'attachment_id'     => array(
					'type'        => 'integer',
					'description' => __( 'The attachment ID.', 'wp-abilities-seo-extension' ),
				),
				'previous_alt_text' => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Previous alt text value (for verification/undo).', 'wp-abilities-seo-extension' ),
				),
				'new_alt_text'      => array(
					'type'        => 'string',
					'description' => __( 'New alt text value.', 'wp-abilities-seo-extension' ),
				),
				'image_url'         => array(
					'type'        => 'string',
					'format'      => 'uri',
					'description' => __( 'URL of the updated image.', 'wp-abilities-seo-extension' ),
				),
				'image_filename'    => array(
					'type'        => 'string',
					'description' => __( 'Filename of the image.', 'wp-abilities-seo-extension' ),
				),
				'posts_updated'     => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'integer' ),
					'description' => __( 'Array of post IDs where the alt text was synced in the content.', 'wp-abilities-seo-extension' ),
				),
			),
		);
	}
}
