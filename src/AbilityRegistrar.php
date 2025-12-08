<?php
/**
 * Ability Registrar - registers categories and abilities with the Abilities API.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities;

use SeoAbilities\Providers\ProviderInterface;
use SeoAbilities\Services\PostService;
use SeoAbilities\Services\ImageService;
use SeoAbilities\Abilities\GetSeoMetaAbility;
use SeoAbilities\Abilities\UpdateSeoMetaAbility;
use SeoAbilities\Abilities\GetSocialMetaAbility;
use SeoAbilities\Abilities\UpdateSocialMetaAbility;
use SeoAbilities\Abilities\BulkUpdateSeoMetaAbility;
use SeoAbilities\Abilities\GetSeoScoreAbility;
use SeoAbilities\Abilities\FindPostsWithSeoIssuesAbility;
use SeoAbilities\Abilities\GetPostImagesAbility;
use SeoAbilities\Abilities\UpdateImageAltTextAbility;
use SeoAbilities\Abilities\BulkUpdateImageAltTextAbility;

/**
 * Registers all SEO ability categories and abilities with the WordPress Abilities API.
 */
class AbilityRegistrar {

	/**
	 * Ability namespace prefix.
	 *
	 * @var string
	 */
	private const ABILITY_NAMESPACE = 'seo-abilities';

	/**
	 * Category definitions.
	 *
	 * @var array
	 */
	private const CATEGORIES = array(
		'seo-meta'     => array(
			'label'       => 'SEO Meta',
			'description' => 'Abilities for reading and writing SEO meta fields (titles, descriptions, focus keywords) and social media meta (Open Graph, Twitter Cards).',
		),
		'seo-analysis' => array(
			'label'       => 'SEO Analysis',
			'description' => 'Abilities for retrieving SEO scores, recommendations, and finding posts with SEO issues.',
		),
		'seo-images'   => array(
			'label'       => 'SEO Images',
			'description' => 'Abilities for managing image alt text for SEO optimization.',
		),
	);

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * SEO provider instance.
	 *
	 * @var ProviderInterface
	 */
	private ProviderInterface $provider;

	/**
	 * Post service instance.
	 *
	 * @var PostService
	 */
	private PostService $post_service;

	/**
	 * Image service instance.
	 *
	 * @var ImageService
	 */
	private ImageService $image_service;

	/**
	 * Constructor.
	 *
	 * @param Settings          $settings Plugin settings.
	 * @param ProviderInterface $provider SEO provider.
	 * @param array             $services Services container.
	 */
	public function __construct(
		Settings $settings,
		ProviderInterface $provider,
		array $services
	) {
		$this->settings      = $settings;
		$this->provider      = $provider;
		$this->post_service  = $services['post'];
		$this->image_service = $services['image'];

		// Register hooks.
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Register ability categories.
	 *
	 * @return void
	 */
	public function register_categories(): void {
		foreach ( self::CATEGORIES as $slug => $data ) {
			wp_register_ability_category(
				$slug,
				array(
					'label'       => __( $data['label'], 'wp-abilities-seo-extension' ),
					'description' => __( $data['description'], 'wp-abilities-seo-extension' ),
				)
			);
		}
	}

	/**
	 * Register all abilities.
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		// SEO Meta abilities.
		$this->register_get_seo_meta();
		$this->register_update_seo_meta();
		$this->register_get_social_meta();
		$this->register_update_social_meta();
		$this->register_bulk_update_seo_meta();

		// SEO Analysis abilities.
		$this->register_get_seo_score();
		$this->register_find_posts_with_seo_issues();

		// SEO Images abilities.
		$this->register_get_post_images();
		$this->register_update_image_alt_text();
		$this->register_bulk_update_image_alt_text();
	}

	/**
	 * Create an ability instance with all dependencies.
	 *
	 * @param string $class_name Ability class name.
	 * @return object Ability instance.
	 */
	private function create_ability( string $class_name ): object {
		return new $class_name(
			$this->provider,
			$this->settings,
			$this->post_service,
			$this->image_service
		);
	}

	/**
	 * Build ability name with namespace.
	 *
	 * @param string $name Ability name without namespace.
	 * @return string Full ability name.
	 */
	private function ability_name( string $name ): string {
		return self::ABILITY_NAMESPACE . '/' . $name;
	}

	/**
	 * Check edit_post permission for a single post.
	 *
	 * @param array $input Input data with post_id.
	 * @return bool True if user can edit.
	 */
	public function check_edit_post_permission( array $input = array() ): bool {
		$post_id = (int) ( $input['post_id'] ?? 0 );
		return $post_id && current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Check edit_posts permission for bulk operations.
	 *
	 * @return bool True if user can edit posts.
	 */
	public function check_edit_posts_permission(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Check edit_post permission for attachment.
	 *
	 * @param array $input Input data with attachment_id.
	 * @return bool True if user can edit.
	 */
	public function check_edit_attachment_permission( array $input = array() ): bool {
		$attachment_id = (int) ( $input['attachment_id'] ?? 0 );
		return $attachment_id && current_user_can( 'edit_post', $attachment_id );
	}

	// =========================================================================
	// SEO Meta Abilities
	// =========================================================================

	/**
	 * Register get-seo-meta ability.
	 *
	 * @return void
	 */
	private function register_get_seo_meta(): void {
		$ability = $this->create_ability( GetSeoMetaAbility::class );

		wp_register_ability(
			$this->ability_name( 'get-seo-meta' ),
			array(
				'label'               => __( 'Get SEO Meta', 'wp-abilities-seo-extension' ),
				'description'         => __( 'Retrieves the current SEO meta title, meta description, and focus keyword for a specified post, page, or custom post type. Returns null for fields that haven\'t been set. Use this ability to understand the current SEO state before making recommendations or changes.', 'wp-abilities-seo-extension' ),
				'category'            => 'seo-meta',
				'input_schema'        => GetSeoMetaAbility::get_input_schema(),
				'output_schema'       => GetSeoMetaAbility::get_output_schema(),
				'execute_callback'    => array( $ability, 'execute' ),
				'permission_callback' => array( $this, 'check_edit_post_permission' ),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
					),
				),
			)
		);
	}

	/**
	 * Register update-seo-meta ability.
	 *
	 * @return void
	 */
	private function register_update_seo_meta(): void {
		$ability = $this->create_ability( UpdateSeoMetaAbility::class );

		wp_register_ability(
			$this->ability_name( 'update-seo-meta' ),
			array(
				'label'               => __( 'Update SEO Meta', 'wp-abilities-seo-extension' ),
				'description'         => __( 'Updates the SEO meta title, meta description, and/or focus keyword for a specified post. Only provided fields are updated; omitted fields remain unchanged. Provide an empty string to clear a field.', 'wp-abilities-seo-extension' ),
				'category'            => 'seo-meta',
				'input_schema'        => UpdateSeoMetaAbility::get_input_schema(),
				'output_schema'       => UpdateSeoMetaAbility::get_output_schema(),
				'execute_callback'    => array( $ability, 'execute' ),
				'permission_callback' => array( $this, 'check_edit_post_permission' ),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/**
	 * Register get-social-meta ability.
	 *
	 * @return void
	 */
	private function register_get_social_meta(): void {
		$ability = $this->create_ability( GetSocialMetaAbility::class );

		wp_register_ability(
			$this->ability_name( 'get-social-meta' ),
			array(
				'label'               => __( 'Get Social Meta', 'wp-abilities-seo-extension' ),
				'description'         => __( 'Retrieves Facebook Open Graph and Twitter Card meta data for a specified post, including titles, descriptions, and images. Use this to understand how content will appear when shared on social platforms.', 'wp-abilities-seo-extension' ),
				'category'            => 'seo-meta',
				'input_schema'        => GetSocialMetaAbility::get_input_schema(),
				'output_schema'       => GetSocialMetaAbility::get_output_schema(),
				'execute_callback'    => array( $ability, 'execute' ),
				'permission_callback' => array( $this, 'check_edit_post_permission' ),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
					),
				),
			)
		);
	}

	/**
	 * Register update-social-meta ability.
	 *
	 * @return void
	 */
	private function register_update_social_meta(): void {
		$ability = $this->create_ability( UpdateSocialMetaAbility::class );

		wp_register_ability(
			$this->ability_name( 'update-social-meta' ),
			array(
				'label'               => __( 'Update Social Meta', 'wp-abilities-seo-extension' ),
				'description'         => __( 'Updates Facebook Open Graph and/or Twitter Card meta data for a specified post. Supports setting titles, descriptions, and images for social sharing. Set twitter_use_facebook to true to have Twitter inherit Facebook values.', 'wp-abilities-seo-extension' ),
				'category'            => 'seo-meta',
				'input_schema'        => UpdateSocialMetaAbility::get_input_schema(),
				'output_schema'       => UpdateSocialMetaAbility::get_output_schema(),
				'execute_callback'    => array( $ability, 'execute' ),
				'permission_callback' => array( $this, 'check_edit_post_permission' ),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/**
	 * Register bulk-update-seo-meta ability.
	 *
	 * @return void
	 */
	private function register_bulk_update_seo_meta(): void {
		$ability = $this->create_ability( BulkUpdateSeoMetaAbility::class );

		wp_register_ability(
			$this->ability_name( 'bulk-update-seo-meta' ),
			array(
				'label'               => __( 'Bulk Update SEO Meta', 'wp-abilities-seo-extension' ),
				'description'         => __( 'Updates SEO meta fields for multiple posts in a single operation. Each post can have different values. Respects the configured maximum items limit (default: 10). Use this for efficient batch updates across multiple posts.', 'wp-abilities-seo-extension' ),
				'category'            => 'seo-meta',
				'input_schema'        => BulkUpdateSeoMetaAbility::get_input_schema(),
				'output_schema'       => BulkUpdateSeoMetaAbility::get_output_schema(),
				'execute_callback'    => array( $ability, 'execute' ),
				'permission_callback' => array( $this, 'check_edit_posts_permission' ),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
					),
				),
			)
		);
	}

	// =========================================================================
	// SEO Analysis Abilities
	// =========================================================================

	/**
	 * Register get-seo-score ability.
	 *
	 * @return void
	 */
	private function register_get_seo_score(): void {
		$ability = $this->create_ability( GetSeoScoreAbility::class );

		wp_register_ability(
			$this->ability_name( 'get-seo-score' ),
			array(
				'label'               => __( 'Get SEO Score', 'wp-abilities-seo-extension' ),
				'description'         => __( 'Retrieves the SEO score and detailed analysis recommendations for a specified post. Includes passed tests, warnings, and failed tests with actionable suggestions for improvement. A focus keyword must be set for full analysis.', 'wp-abilities-seo-extension' ),
				'category'            => 'seo-analysis',
				'input_schema'        => GetSeoScoreAbility::get_input_schema(),
				'output_schema'       => GetSeoScoreAbility::get_output_schema(),
				'execute_callback'    => array( $ability, 'execute' ),
				'permission_callback' => array( $this, 'check_edit_post_permission' ),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
					),
				),
			)
		);
	}

	/**
	 * Register find-posts-with-seo-issues ability.
	 *
	 * @return void
	 */
	private function register_find_posts_with_seo_issues(): void {
		$ability = $this->create_ability( FindPostsWithSeoIssuesAbility::class );

		wp_register_ability(
			$this->ability_name( 'find-posts-with-seo-issues' ),
			array(
				'label'               => __( 'Find Posts with SEO Issues', 'wp-abilities-seo-extension' ),
				'description'         => __( 'Searches for posts that have specific SEO issues such as missing meta titles, missing meta descriptions, missing focus keywords, images without alt text, or SEO scores below a specified threshold. Supports filtering by post type and pagination.', 'wp-abilities-seo-extension' ),
				'category'            => 'seo-analysis',
				'input_schema'        => FindPostsWithSeoIssuesAbility::get_input_schema(),
				'output_schema'       => FindPostsWithSeoIssuesAbility::get_output_schema(),
				'execute_callback'    => array( $ability, 'execute' ),
				'permission_callback' => array( $this, 'check_edit_posts_permission' ),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
					),
				),
			)
		);
	}

	// =========================================================================
	// SEO Images Abilities
	// =========================================================================

	/**
	 * Register get-post-images ability.
	 *
	 * @return void
	 */
	private function register_get_post_images(): void {
		$ability = $this->create_ability( GetPostImagesAbility::class );

		wp_register_ability(
			$this->ability_name( 'get-post-images' ),
			array(
				'label'               => __( 'Get Post Images', 'wp-abilities-seo-extension' ),
				'description'         => __( 'Retrieves all images within a post\'s content and featured image, along with their current alt text. Useful for identifying images that need alt text optimization for SEO and accessibility. External images (not in Media Library) are identified but cannot be updated.', 'wp-abilities-seo-extension' ),
				'category'            => 'seo-images',
				'input_schema'        => GetPostImagesAbility::get_input_schema(),
				'output_schema'       => GetPostImagesAbility::get_output_schema(),
				'execute_callback'    => array( $ability, 'execute' ),
				'permission_callback' => array( $this, 'check_edit_post_permission' ),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
					),
				),
			)
		);
	}

	/**
	 * Register update-image-alt-text ability.
	 *
	 * @return void
	 */
	private function register_update_image_alt_text(): void {
		$ability = $this->create_ability( UpdateImageAltTextAbility::class );

		wp_register_ability(
			$this->ability_name( 'update-image-alt-text' ),
			array(
				'label'               => __( 'Update Image Alt Text', 'wp-abilities-seo-extension' ),
				'description'         => __( 'Updates the alt text for a specific image in the WordPress Media Library. The attachment must be a valid image type (not a document or video). External images cannot be updated through this ability.', 'wp-abilities-seo-extension' ),
				'category'            => 'seo-images',
				'input_schema'        => UpdateImageAltTextAbility::get_input_schema(),
				'output_schema'       => UpdateImageAltTextAbility::get_output_schema(),
				'execute_callback'    => array( $ability, 'execute' ),
				'permission_callback' => array( $this, 'check_edit_attachment_permission' ),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/**
	 * Register bulk-update-image-alt-text ability.
	 *
	 * @return void
	 */
	private function register_bulk_update_image_alt_text(): void {
		$ability = $this->create_ability( BulkUpdateImageAltTextAbility::class );

		wp_register_ability(
			$this->ability_name( 'bulk-update-image-alt-text' ),
			array(
				'label'               => __( 'Bulk Update Image Alt Text', 'wp-abilities-seo-extension' ),
				'description'         => __( 'Updates alt text for multiple images in a single operation. Each image receives its own unique alt text. Respects the configured maximum items limit (default: 10). Only images in the WordPress Media Library can be updated.', 'wp-abilities-seo-extension' ),
				'category'            => 'seo-images',
				'input_schema'        => BulkUpdateImageAltTextAbility::get_input_schema(),
				'output_schema'       => BulkUpdateImageAltTextAbility::get_output_schema(),
				'execute_callback'    => array( $ability, 'execute' ),
				'permission_callback' => array( $this, 'check_edit_posts_permission' ),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
					),
				),
			)
		);
	}
}
