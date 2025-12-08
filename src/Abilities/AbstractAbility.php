<?php
/**
 * Abstract base class for abilities.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Abilities;

use SeoAbilities\Providers\ProviderInterface;
use SeoAbilities\Settings;
use SeoAbilities\Services\PostService;
use SeoAbilities\Services\ImageService;
use SeoAbilities\Errors\ErrorFactory;
use WP_Error;

/**
 * Abstract base class providing common functionality for all abilities.
 */
abstract class AbstractAbility {

	/**
	 * SEO provider instance.
	 *
	 * @var ProviderInterface
	 */
	protected ProviderInterface $provider;

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	protected Settings $settings;

	/**
	 * Post service instance.
	 *
	 * @var PostService
	 */
	protected PostService $post_service;

	/**
	 * Image service instance.
	 *
	 * @var ImageService
	 */
	protected ImageService $image_service;

	/**
	 * Constructor.
	 *
	 * @param ProviderInterface $provider      SEO provider.
	 * @param Settings          $settings      Plugin settings.
	 * @param PostService       $post_service  Post service.
	 * @param ImageService      $image_service Image service.
	 */
	public function __construct(
		ProviderInterface $provider,
		Settings $settings,
		PostService $post_service,
		ImageService $image_service
	) {
		$this->provider      = $provider;
		$this->settings      = $settings;
		$this->post_service  = $post_service;
		$this->image_service = $image_service;
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $input Input data.
	 * @return array|WP_Error Result or error.
	 */
	abstract public function execute( array $input );

	/**
	 * Validate that a post exists and is of an enabled post type.
	 *
	 * @param int $post_id Post ID to validate.
	 * @return WP_Error|null Error if validation fails, null if valid.
	 */
	protected function validate_post( int $post_id ): ?WP_Error {
		$post = $this->post_service->get_post( $post_id );

		if ( ! $post ) {
			return ErrorFactory::post_not_found( $post_id );
		}

		if ( ! $this->settings->is_supported_post_type( $post->post_type ) ) {
			return ErrorFactory::post_type_not_enabled( $post->post_type );
		}

		return null;
	}

	/**
	 * Validate that user can edit a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $action  Action being performed (view, update).
	 * @return WP_Error|null Error if permission denied, null if allowed.
	 */
	protected function validate_permission( int $post_id, string $action = 'view' ): ?WP_Error {
		if ( ! $this->post_service->can_edit_post( $post_id ) ) {
			return ErrorFactory::permission_denied( $post_id, $action );
		}

		return null;
	}

	/**
	 * Validate post and permission together.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $action  Action being performed.
	 * @return WP_Error|null Error if validation fails, null if valid.
	 */
	protected function validate_post_and_permission( int $post_id, string $action = 'view' ): ?WP_Error {
		$error = $this->validate_post( $post_id );
		if ( $error ) {
			return $error;
		}

		return $this->validate_permission( $post_id, $action );
	}

	/**
	 * Validate an image attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return WP_Error|null Error if validation fails, null if valid.
	 */
	protected function validate_image_attachment( int $attachment_id ): ?WP_Error {
		$attachment = get_post( $attachment_id );

		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return ErrorFactory::attachment_not_found( $attachment_id );
		}

		if ( ! $this->image_service->is_image_attachment( $attachment_id ) ) {
			$mime_type = $this->image_service->get_attachment_mime_type( $attachment_id );
			return ErrorFactory::not_an_image( $attachment_id, $mime_type );
		}

		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			return ErrorFactory::attachment_permission_denied( $attachment_id );
		}

		return null;
	}

	/**
	 * Validate bulk operation items count.
	 *
	 * @param array $items Items array.
	 * @return WP_Error|null Error if validation fails, null if valid.
	 */
	protected function validate_bulk_items( array $items ): ?WP_Error {
		if ( empty( $items ) ) {
			return ErrorFactory::empty_items_array();
		}

		$limit = $this->settings->get_bulk_limit();
		$count = count( $items );

		if ( $count > $limit ) {
			return ErrorFactory::bulk_limit_exceeded( $count, $limit );
		}

		return null;
	}

	/**
	 * Get basic post data for response.
	 *
	 * @param int $post_id Post ID.
	 * @return array Post data.
	 */
	protected function get_post_data( int $post_id ): array {
		return $this->post_service->get_post_data( $post_id );
	}

	/**
	 * Get extended post data for response.
	 *
	 * @param int $post_id Post ID.
	 * @return array Extended post data.
	 */
	protected function get_extended_post_data( int $post_id ): array {
		return $this->post_service->get_extended_post_data( $post_id );
	}
}
