<?php
/**
 * Bulk Update Image Alt Text ability.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Abilities;

use WP_Error;

/**
 * Ability to update alt text for multiple images.
 */
class BulkUpdateImageAltTextAbility extends AbstractAbility {

	/**
	 * {@inheritdoc}
	 */
	public function execute( array $input ) {
		$items = $input['items'] ?? array();

		// Validate bulk items count.
		$error = $this->validate_bulk_items( $items );
		if ( $error ) {
			return $error;
		}

		$results         = array();
		$total_requested = count( $items );
		$total_processed = 0;
		$all_success     = true;

		foreach ( $items as $item ) {
			$attachment_id = (int) ( $item['attachment_id'] ?? 0 );
			$alt_text      = $item['alt_text'] ?? '';

			if ( ! $attachment_id ) {
				$results[] = array(
					'attachment_id' => 0,
					'success'       => false,
					'error'         => __( 'Invalid attachment_id provided.', 'wp-abilities-seo-extension' ),
				);
				$all_success = false;
				continue;
			}

			// Validate image attachment.
			$validation_error = $this->validate_image_attachment( $attachment_id );
			if ( $validation_error ) {
				$results[] = array(
					'attachment_id' => $attachment_id,
					'success'       => false,
					'error'         => $validation_error->get_error_message(),
				);
				$all_success = false;
				continue;
			}

			// Get previous alt text.
			$previous_alt_text = $this->image_service->get_alt_text( $attachment_id );

			// Update alt text in media library.
			$this->image_service->update_alt_text( $attachment_id, $alt_text );

			// Sync alt text to all posts that use this image.
			$posts_updated = $this->image_service->sync_alt_text_to_posts( $attachment_id, $alt_text );

			$results[] = array(
				'attachment_id'     => $attachment_id,
				'success'           => true,
				'previous_alt_text' => $previous_alt_text,
				'new_alt_text'      => $alt_text,
				'posts_updated'     => $posts_updated,
				'error'             => null,
			);
			++$total_processed;
		}

		return array(
			'success'         => $all_success,
			'total_requested' => $total_requested,
			'total_processed' => $total_processed,
			'results'         => $results,
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
				'items' => array(
					'type'        => 'array',
					'description' => __( 'Array of image updates. Maximum items determined by admin settings.', 'wp-abilities-seo-extension' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'attachment_id' => array(
								'type'        => 'integer',
								'description' => __( 'Attachment ID of the image.', 'wp-abilities-seo-extension' ),
								'minimum'     => 1,
							),
							'alt_text'      => array(
								'type'        => 'string',
								'description' => __( 'New alt text for this image.', 'wp-abilities-seo-extension' ),
							),
						),
						'required' => array( 'attachment_id', 'alt_text' ),
					),
					'minItems' => 1,
				),
			),
			'required'             => array( 'items' ),
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
				'success'         => array(
					'type'        => 'boolean',
					'description' => __( 'True only if ALL updates succeeded.', 'wp-abilities-seo-extension' ),
				),
				'total_requested' => array(
					'type'        => 'integer',
					'description' => __( 'Number of items in the request.', 'wp-abilities-seo-extension' ),
				),
				'total_processed' => array(
					'type'        => 'integer',
					'description' => __( 'Number of items actually processed.', 'wp-abilities-seo-extension' ),
				),
				'results'         => array(
					'type'        => 'array',
					'description' => __( 'Individual results for each image.', 'wp-abilities-seo-extension' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'attachment_id'     => array(
								'type'        => 'integer',
								'description' => __( 'Attachment ID.', 'wp-abilities-seo-extension' ),
							),
							'success'           => array(
								'type'        => 'boolean',
								'description' => __( 'Whether this image was updated.', 'wp-abilities-seo-extension' ),
							),
							'previous_alt_text' => array(
								'type'        => array( 'string', 'null' ),
								'description' => __( 'Previous alt text (if successful).', 'wp-abilities-seo-extension' ),
							),
							'new_alt_text'      => array(
								'type'        => 'string',
								'description' => __( 'New alt text (if successful).', 'wp-abilities-seo-extension' ),
							),
							'posts_updated'     => array(
								'type'        => 'array',
								'items'       => array( 'type' => 'integer' ),
								'description' => __( 'Array of post IDs where the alt text was synced (if successful).', 'wp-abilities-seo-extension' ),
							),
							'error'             => array(
								'type'        => array( 'string', 'null' ),
								'description' => __( 'Error message (if failed).', 'wp-abilities-seo-extension' ),
							),
						),
					),
				),
			),
		);
	}
}
