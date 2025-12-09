<?php
/**
 * Error factory for creating verbose WP_Error objects.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Errors;

use WP_Error;

/**
 * Factory for creating verbose, AI-agent-friendly WP_Error objects.
 */
class ErrorFactory {

	/**
	 * Create a WP_Error with HTTP status.
	 *
	 * @param string $code       Error code.
	 * @param string $message    Error message.
	 * @param array  $extra_data Additional error data.
	 * @return WP_Error
	 */
	public static function create( string $code, string $message, array $extra_data = array() ): WP_Error {
		return new WP_Error(
			$code,
			$message,
			array_merge( array( 'status' => ErrorCodes::get_status( $code ) ), $extra_data )
		);
	}

	/**
	 * Create error for post not found.
	 *
	 * @param int $post_id Post ID that was not found.
	 * @return WP_Error
	 */
	public static function post_not_found( int $post_id ): WP_Error {
		return self::create(
			ErrorCodes::POST_NOT_FOUND,
			sprintf(
				/* translators: %d: post ID */
				__( 'Post not found. The post ID %d does not exist or has been deleted.', 'wp-abilities-seo-extension' ),
				$post_id
			),
			array( 'post_id' => $post_id )
		);
	}

	/**
	 * Create error for post type not enabled.
	 *
	 * @param string $post_type Post type that is not enabled.
	 * @return WP_Error
	 */
	public static function post_type_not_enabled( string $post_type ): WP_Error {
		return self::create(
			ErrorCodes::POST_TYPE_NOT_ENABLED,
			sprintf(
				/* translators: %s: post type slug */
				__( "Post type '%s' is not enabled for SEO abilities. Enable it in Settings → SEO Abilities, or contact your site administrator.", 'wp-abilities-seo-extension' ),
				$post_type
			),
			array( 'post_type' => $post_type )
		);
	}

	/**
	 * Create error for permission denied.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $action  Action being performed (view, update).
	 * @return WP_Error
	 */
	public static function permission_denied( int $post_id, string $action = 'view' ): WP_Error {
		return self::create(
			ErrorCodes::PERMISSION_DENIED,
			sprintf(
				/* translators: 1: action (view/update), 2: post ID */
				__( 'You do not have permission to %1$s SEO data for this post. Required capability: edit_post for post ID %2$d.', 'wp-abilities-seo-extension' ),
				$action,
				$post_id
			),
			array(
				'post_id' => $post_id,
				'action'  => $action,
			)
		);
	}

	/**
	 * Create error for permission denied on attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return WP_Error
	 */
	public static function attachment_permission_denied( int $attachment_id ): WP_Error {
		return self::create(
			ErrorCodes::PERMISSION_DENIED,
			sprintf(
				/* translators: %d: attachment ID */
				__( 'You do not have permission to update this attachment. Required capability: edit_post for attachment ID %d.', 'wp-abilities-seo-extension' ),
				$attachment_id
			),
			array( 'attachment_id' => $attachment_id )
		);
	}

	/**
	 * Create error for bulk limit exceeded.
	 *
	 * @param int $requested Number of items requested.
	 * @param int $limit     Maximum allowed items.
	 * @return WP_Error
	 */
	public static function bulk_limit_exceeded( int $requested, int $limit ): WP_Error {
		return self::create(
			ErrorCodes::BULK_LIMIT_EXCEEDED,
			sprintf(
				/* translators: 1: maximum allowed, 2: number requested */
				__( 'Bulk operation limit exceeded. Maximum %1$d items allowed per request. You requested %2$d items. Reduce your request size or ask the administrator to increase the limit in Settings → SEO Abilities.', 'wp-abilities-seo-extension' ),
				$limit,
				$requested
			),
			array(
				'requested' => $requested,
				'max_limit' => $limit,
			)
		);
	}

	/**
	 * Create error for no fields to update.
	 *
	 * @param array $valid_fields List of valid field names.
	 * @return WP_Error
	 */
	public static function no_fields_to_update( array $valid_fields ): WP_Error {
		return self::create(
			ErrorCodes::NO_FIELDS_TO_UPDATE,
			sprintf(
				/* translators: %s: comma-separated list of valid field names */
				__( 'No fields to update. Provide at least one of: %s. All fields are optional, but at least one must be included in the request.', 'wp-abilities-seo-extension' ),
				implode( ', ', $valid_fields )
			),
			array( 'valid_fields' => $valid_fields )
		);
	}

	/**
	 * Create error for provider not active.
	 *
	 * @return WP_Error
	 */
	public static function provider_not_active(): WP_Error {
		return self::create(
			ErrorCodes::PROVIDER_NOT_ACTIVE,
			__( 'Required SEO plugin is not active. This ability requires Rank Math SEO to function. Please install and activate Rank Math SEO.', 'wp-abilities-seo-extension' ),
			array( 'required_plugin' => 'Rank Math SEO' )
		);
	}

	/**
	 * Create error for attachment not found.
	 *
	 * @param int $attachment_id Attachment ID that was not found.
	 * @return WP_Error
	 */
	public static function attachment_not_found( int $attachment_id ): WP_Error {
		return self::create(
			ErrorCodes::ATTACHMENT_NOT_FOUND,
			sprintf(
				/* translators: %d: attachment ID */
				__( 'Attachment not found. ID %d does not exist in the Media Library.', 'wp-abilities-seo-extension' ),
				$attachment_id
			),
			array( 'attachment_id' => $attachment_id )
		);
	}

	/**
	 * Create error for attachment not being an image.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $mime_type     Actual MIME type of the attachment.
	 * @return WP_Error
	 */
	public static function not_an_image( int $attachment_id, string $mime_type ): WP_Error {
		return self::create(
			ErrorCodes::NOT_AN_IMAGE,
			sprintf(
				/* translators: 1: attachment ID, 2: MIME type */
				__( "Attachment %1\$d is not an image. Only image attachments (JPEG, PNG, GIF, WebP) can have alt text updated. This attachment is of type '%2\$s'.", 'wp-abilities-seo-extension' ),
				$attachment_id,
				$mime_type
			),
			array(
				'attachment_id' => $attachment_id,
				'mime_type'     => $mime_type,
			)
		);
	}

	/**
	 * Create error for empty items array.
	 *
	 * @return WP_Error
	 */
	public static function empty_items_array(): WP_Error {
		return self::create(
			ErrorCodes::EMPTY_ITEMS_ARRAY,
			__( "No items provided. The 'items' array must contain at least one item to process.", 'wp-abilities-seo-extension' )
		);
	}

	/**
	 * Create error for invalid issue type.
	 *
	 * @param string $issue_type     Invalid issue type provided.
	 * @param array  $valid_types    List of valid issue types.
	 * @return WP_Error
	 */
	public static function invalid_issue_type( string $issue_type, array $valid_types ): WP_Error {
		return self::create(
			ErrorCodes::INVALID_ISSUE_TYPE,
			sprintf(
				/* translators: 1: invalid issue type, 2: comma-separated list of valid types */
				__( "Invalid issue type '%1\$s'. Must be one of: %2\$s.", 'wp-abilities-seo-extension' ),
				$issue_type,
				implode( ', ', $valid_types )
			),
			array(
				'issue_type'  => $issue_type,
				'valid_types' => $valid_types,
			)
		);
	}
}
