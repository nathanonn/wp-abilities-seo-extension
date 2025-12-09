<?php
/**
 * Bulk Update SEO Meta ability.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Abilities;

use WP_Error;

/**
 * Ability to update SEO meta data for multiple posts.
 */
class BulkUpdateSeoMetaAbility extends AbstractAbility {

	/**
	 * Valid fields that can be updated per item.
	 *
	 * @var array
	 */
	private const VALID_FIELDS = array( 'seo_title', 'seo_description', 'focus_keyword' );

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
			$post_id = (int) ( $item['post_id'] ?? 0 );

			if ( ! $post_id ) {
				$results[] = array(
					'post_id' => 0,
					'success' => false,
					'error'   => __( 'Invalid post_id provided.', 'wp-abilities-seo-extension' ),
				);
				$all_success = false;
				continue;
			}

			// Validate post.
			$post_error = $this->validate_post( $post_id );
			if ( $post_error ) {
				$results[] = array(
					'post_id' => $post_id,
					'success' => false,
					'error'   => $post_error->get_error_message(),
				);
				$all_success = false;
				continue;
			}

			// Validate permission.
			$perm_error = $this->validate_permission( $post_id, 'update' );
			if ( $perm_error ) {
				$results[] = array(
					'post_id' => $post_id,
					'success' => false,
					'error'   => $perm_error->get_error_message(),
				);
				$all_success = false;
				continue;
			}

			// Extract update data.
			$update_data = array();
			foreach ( self::VALID_FIELDS as $field ) {
				if ( array_key_exists( $field, $item ) ) {
					$update_data[ $field ] = $item[ $field ];
				}
			}

			// Check that at least one field is provided.
			if ( empty( $update_data ) ) {
				$results[] = array(
					'post_id' => $post_id,
					'success' => false,
					'error'   => __( 'No fields to update for this item.', 'wp-abilities-seo-extension' ),
				);
				$all_success = false;
				continue;
			}

			// Update SEO meta.
			$updated_fields = $this->provider->update_seo_meta( $post_id, $update_data );

			$results[] = array(
				'post_id'        => $post_id,
				'success'        => true,
				'updated_fields' => $updated_fields,
				'error'          => null,
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
					'description' => __( 'Array of update objects. Maximum items determined by admin settings.', 'wp-abilities-seo-extension' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'post_id'         => array(
								'type'        => 'integer',
								'description' => __( 'Post ID to update.', 'wp-abilities-seo-extension' ),
								'minimum'     => 1,
							),
							'seo_title'       => array(
								'type'        => 'string',
								'description' => __( 'New SEO title.', 'wp-abilities-seo-extension' ),
							),
							'seo_description' => array(
								'type'        => 'string',
								'description' => __( 'New SEO description.', 'wp-abilities-seo-extension' ),
							),
							'focus_keyword'   => array(
								'type'        => 'string',
								'description' => __( 'New focus keyword.', 'wp-abilities-seo-extension' ),
							),
						),
						'required' => array( 'post_id' ),
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
					'description' => __( 'Array of individual results per post.', 'wp-abilities-seo-extension' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'post_id'        => array(
								'type'        => 'integer',
								'description' => __( 'Post ID.', 'wp-abilities-seo-extension' ),
							),
							'success'        => array(
								'type'        => 'boolean',
								'description' => __( 'Whether this specific post was updated.', 'wp-abilities-seo-extension' ),
							),
							'updated_fields' => array(
								'type'        => 'array',
								'items'       => array( 'type' => 'string' ),
								'description' => __( 'Fields updated for this post (if successful).', 'wp-abilities-seo-extension' ),
							),
							'error'          => array(
								'type'        => array( 'string', 'null' ),
								'description' => __( 'Error message if this post failed.', 'wp-abilities-seo-extension' ),
							),
						),
					),
				),
			),
		);
	}
}
