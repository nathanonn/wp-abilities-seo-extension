<?php
/**
 * Provider factory for creating SEO plugin providers.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities\Providers;

/**
 * Factory for detecting and creating the appropriate SEO provider.
 */
class ProviderFactory {

	/**
	 * Create the appropriate provider based on active SEO plugins.
	 *
	 * @return ProviderInterface|null Provider instance or null if no supported plugin is active.
	 */
	public static function create(): ?ProviderInterface {
		// Check Rank Math first (v1 supported plugin).
		if ( self::is_rank_math_active() ) {
			return new RankMathProvider();
		}

		// Future: Check for other SEO plugins.
		// if ( self::is_yoast_active() ) {
		//     return new YoastProvider();
		// }
		// if ( self::is_aioseo_active() ) {
		//     return new AIOSEOProvider();
		// }

		return null;
	}

	/**
	 * Check if Rank Math is active.
	 *
	 * @return bool True if active, false otherwise.
	 */
	public static function is_rank_math_active(): bool {
		return class_exists( '\\RankMath\\Helper' );
	}

	/**
	 * Get list of supported SEO plugins for admin notices.
	 *
	 * @return array<string, string> Plugin name => plugin file.
	 */
	public static function get_supported_plugins(): array {
		return array(
			'Rank Math SEO' => 'seo-by-rank-math/rank-math.php',
			// Future providers:
			// 'Yoast SEO' => 'wordpress-seo/wp-seo.php',
			// 'All in One SEO' => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
		);
	}

	/**
	 * Get the name of the currently active provider.
	 *
	 * @return string|null Provider name or null if none active.
	 */
	public static function get_active_provider_name(): ?string {
		if ( self::is_rank_math_active() ) {
			return 'Rank Math SEO';
		}

		return null;
	}
}
