<?php
/**
 * Error codes and HTTP status mapping.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Errors;

/**
 * Error codes constants with HTTP status mapping.
 */
class ErrorCodes {

	// 404 - Not Found errors.
	public const POST_NOT_FOUND       = 'seo_abilities_post_not_found';
	public const ATTACHMENT_NOT_FOUND = 'seo_abilities_attachment_not_found';

	// 400 - Bad Request errors.
	public const POST_TYPE_NOT_ENABLED = 'seo_abilities_post_type_not_enabled';
	public const INVALID_ATTACHMENT    = 'seo_abilities_invalid_attachment';
	public const NOT_AN_IMAGE          = 'seo_abilities_not_an_image';
	public const NO_FIELDS_TO_UPDATE   = 'seo_abilities_no_fields_to_update';
	public const BULK_LIMIT_EXCEEDED   = 'seo_abilities_bulk_limit_exceeded';
	public const EMPTY_ITEMS_ARRAY     = 'seo_abilities_empty_items_array';
	public const INVALID_ISSUE_TYPE    = 'seo_abilities_invalid_issue_type';

	// 403 - Forbidden errors.
	public const PERMISSION_DENIED = 'seo_abilities_permission_denied';

	// 503 - Service Unavailable errors.
	public const PROVIDER_NOT_ACTIVE = 'seo_abilities_provider_not_active';

	/**
	 * HTTP status code mapping.
	 *
	 * @var array<string, int>
	 */
	private static array $status_map = array(
		self::POST_NOT_FOUND       => 404,
		self::ATTACHMENT_NOT_FOUND => 404,
		self::POST_TYPE_NOT_ENABLED => 400,
		self::INVALID_ATTACHMENT   => 400,
		self::NOT_AN_IMAGE         => 400,
		self::NO_FIELDS_TO_UPDATE  => 400,
		self::BULK_LIMIT_EXCEEDED  => 400,
		self::EMPTY_ITEMS_ARRAY    => 400,
		self::INVALID_ISSUE_TYPE   => 400,
		self::PERMISSION_DENIED    => 403,
		self::PROVIDER_NOT_ACTIVE  => 503,
	);

	/**
	 * Get HTTP status code for an error code.
	 *
	 * @param string $code Error code.
	 * @return int HTTP status code.
	 */
	public static function get_status( string $code ): int {
		return self::$status_map[ $code ] ?? 500;
	}
}
