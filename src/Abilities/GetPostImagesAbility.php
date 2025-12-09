<?php
/**
 * Get Post Images ability.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Abilities;

use WP_Error;

/**
 * Ability to retrieve all images in a post with their alt text status.
 */
class GetPostImagesAbility extends AbstractAbility {

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

		// Get images data from service.
		$images_data = $this->image_service->get_post_images( $post_id );
		$post_data   = $this->get_post_data( $post_id );

		return array(
			'post_id'            => $post_id,
			'post_title'         => $post_data['post_title'],
			'total_images'       => $images_data['total_images'],
			'images_with_alt'    => $images_data['images_with_alt'],
			'images_without_alt' => $images_data['images_without_alt'],
			'featured_image'     => $images_data['featured_image'],
			'content_images'     => $images_data['content_images'],
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
					'description' => __( 'The ID of the post to scan for images.', 'wp-abilities-seo-extension' ),
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
		$image_schema = array(
			'type'       => 'object',
			'properties' => array(
				'attachment_id' => array(
					'type'        => array( 'integer', 'null' ),
					'description' => __( 'Attachment ID (null if external image).', 'wp-abilities-seo-extension' ),
				),
				'url'           => array(
					'type'        => 'string',
					'format'      => 'uri',
					'description' => __( 'Image URL.', 'wp-abilities-seo-extension' ),
				),
				'alt_text'      => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Current alt text (null if not set).', 'wp-abilities-seo-extension' ),
				),
				'filename'      => array(
					'type'        => 'string',
					'description' => __( 'Filename.', 'wp-abilities-seo-extension' ),
				),
			),
		);

		$content_image_schema = array(
			'type'       => 'object',
			'properties' => array(
				'attachment_id' => array(
					'type'        => array( 'integer', 'null' ),
					'description' => __( 'Attachment ID (null if external image).', 'wp-abilities-seo-extension' ),
				),
				'url'           => array(
					'type'        => 'string',
					'format'      => 'uri',
					'description' => __( 'Image URL.', 'wp-abilities-seo-extension' ),
				),
				'alt_text'      => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Current alt text.', 'wp-abilities-seo-extension' ),
				),
				'is_external'   => array(
					'type'        => 'boolean',
					'description' => __( 'True if image is hosted externally (cannot be updated).', 'wp-abilities-seo-extension' ),
				),
				'filename'      => array(
					'type'        => 'string',
					'description' => __( 'Filename extracted from URL.', 'wp-abilities-seo-extension' ),
				),
			),
		);

		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id'            => array(
					'type'        => 'integer',
					'description' => __( 'The post ID.', 'wp-abilities-seo-extension' ),
				),
				'post_title'         => array(
					'type'        => 'string',
					'description' => __( 'The post title (for reference).', 'wp-abilities-seo-extension' ),
				),
				'total_images'       => array(
					'type'        => 'integer',
					'description' => __( 'Total number of images found.', 'wp-abilities-seo-extension' ),
				),
				'images_with_alt'    => array(
					'type'        => 'integer',
					'description' => __( 'Count of images that have alt text.', 'wp-abilities-seo-extension' ),
				),
				'images_without_alt' => array(
					'type'        => 'integer',
					'description' => __( 'Count of images missing alt text.', 'wp-abilities-seo-extension' ),
				),
				'featured_image'     => array(
					'oneOf'       => array(
						$image_schema,
						array( 'type' => 'null' ),
					),
					'description' => __( 'Featured image data (null if no featured image).', 'wp-abilities-seo-extension' ),
				),
				'content_images'     => array(
					'type'        => 'array',
					'description' => __( 'Images found in post content.', 'wp-abilities-seo-extension' ),
					'items'       => $content_image_schema,
				),
			),
		);
	}
}
