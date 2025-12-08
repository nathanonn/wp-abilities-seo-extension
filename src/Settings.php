<?php
/**
 * Settings class for admin settings page.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities;

/**
 * Manages plugin settings and admin settings page.
 */
class Settings {

	/**
	 * Option key for storing settings.
	 *
	 * @var string
	 */
	public const OPTION_KEY = 'seo_abilities_settings';

	/**
	 * Default enabled post types.
	 *
	 * @var array
	 */
	private const DEFAULT_POST_TYPES = array( 'post', 'page' );

	/**
	 * Default bulk operation limit.
	 *
	 * @var int
	 */
	private const DEFAULT_BULK_LIMIT = 10;

	/**
	 * Minimum bulk operation limit.
	 *
	 * @var int
	 */
	private const MIN_BULK_LIMIT = 1;

	/**
	 * Maximum bulk operation limit.
	 *
	 * @var int
	 */
	private const MAX_BULK_LIMIT = 100;

	/**
	 * Post types to exclude from settings.
	 *
	 * @var array
	 */
	private const EXCLUDED_POST_TYPES = array(
		'attachment',
		'revision',
		'nav_menu_item',
		'custom_css',
		'customize_changeset',
		'oembed_cache',
		'wp_block',
		'wp_template',
		'wp_template_part',
		'wp_global_styles',
		'wp_navigation',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add settings page to WordPress admin menu.
	 *
	 * @return void
	 */
	public function add_settings_page(): void {
		add_options_page(
			__( 'SEO Abilities', 'wp-abilities-seo-extension' ),
			__( 'SEO Abilities', 'wp-abilities-seo-extension' ),
			'manage_options',
			'seo-abilities',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings with WordPress Settings API.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'seo_abilities',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(
					'post_types' => self::DEFAULT_POST_TYPES,
					'bulk_limit' => self::DEFAULT_BULK_LIMIT,
				),
			)
		);

		// Post Types Section.
		add_settings_section(
			'seo_abilities_post_types',
			__( 'Post Type Settings', 'wp-abilities-seo-extension' ),
			array( $this, 'render_post_types_section' ),
			'seo-abilities'
		);

		add_settings_field(
			'post_types',
			__( 'Enabled Post Types', 'wp-abilities-seo-extension' ),
			array( $this, 'render_post_types_field' ),
			'seo-abilities',
			'seo_abilities_post_types'
		);

		// Bulk Operations Section.
		add_settings_section(
			'seo_abilities_bulk',
			__( 'Bulk Operation Settings', 'wp-abilities-seo-extension' ),
			array( $this, 'render_bulk_section' ),
			'seo-abilities'
		);

		add_settings_field(
			'bulk_limit',
			__( 'Bulk Operation Limit', 'wp-abilities-seo-extension' ),
			array( $this, 'render_bulk_limit_field' ),
			'seo-abilities',
			'seo_abilities_bulk'
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( 'seo_abilities_messages' ); ?>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'seo_abilities' );
				do_settings_sections( 'seo-abilities' );
				submit_button( __( 'Save Settings', 'wp-abilities-seo-extension' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render post types section description.
	 *
	 * @return void
	 */
	public function render_post_types_section(): void {
		?>
		<p><?php esc_html_e( 'Select which post types the SEO abilities can manage. Only public post types are shown.', 'wp-abilities-seo-extension' ); ?></p>
		<?php
	}

	/**
	 * Render bulk operations section description.
	 *
	 * @return void
	 */
	public function render_bulk_section(): void {
		?>
		<p><?php esc_html_e( 'Configure limits for bulk operations to prevent server timeouts.', 'wp-abilities-seo-extension' ); ?></p>
		<?php
	}

	/**
	 * Render post types checkbox field.
	 *
	 * @return void
	 */
	public function render_post_types_field(): void {
		$options      = get_option( self::OPTION_KEY, array() );
		$selected     = $options['post_types'] ?? self::DEFAULT_POST_TYPES;
		$public_types = get_post_types( array( 'public' => true ), 'objects' );

		echo '<fieldset>';
		foreach ( $public_types as $type ) {
			// Skip excluded post types.
			if ( in_array( $type->name, self::EXCLUDED_POST_TYPES, true ) ) {
				continue;
			}

			$checked = in_array( $type->name, $selected, true ) ? 'checked' : '';
			printf(
				'<label><input type="checkbox" name="%s[post_types][]" value="%s" %s> %s (%s)</label><br>',
				esc_attr( self::OPTION_KEY ),
				esc_attr( $type->name ),
				$checked,
				esc_html( $type->label ),
				esc_html( $type->name )
			);
		}
		echo '</fieldset>';
		echo '<p class="description">' . esc_html__( 'Select the post types that AI agents can manage via SEO abilities.', 'wp-abilities-seo-extension' ) . '</p>';
	}

	/**
	 * Render bulk limit number field.
	 *
	 * @return void
	 */
	public function render_bulk_limit_field(): void {
		$options = get_option( self::OPTION_KEY, array() );
		$value   = $options['bulk_limit'] ?? self::DEFAULT_BULK_LIMIT;

		printf(
			'<input type="number" name="%s[bulk_limit]" value="%d" min="%d" max="%d" class="small-text">',
			esc_attr( self::OPTION_KEY ),
			(int) $value,
			self::MIN_BULK_LIMIT,
			self::MAX_BULK_LIMIT
		);
		echo '<p class="description">' .
			sprintf(
				/* translators: 1: minimum limit, 2: maximum limit */
				esc_html__( 'Maximum number of items allowed per bulk operation (%1$d-%2$d). Lower values reduce server load; higher values improve efficiency for large batches.', 'wp-abilities-seo-extension' ),
				self::MIN_BULK_LIMIT,
				self::MAX_BULK_LIMIT
			) .
			'</p>';
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $input Raw input from form.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( array $input ): array {
		$sanitized = array();

		// Sanitize post types.
		$sanitized['post_types'] = array();
		if ( ! empty( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
			$valid_types = array_keys( get_post_types( array( 'public' => true ) ) );
			foreach ( $input['post_types'] as $type ) {
				$type = sanitize_key( $type );
				if ( in_array( $type, $valid_types, true ) && ! in_array( $type, self::EXCLUDED_POST_TYPES, true ) ) {
					$sanitized['post_types'][] = $type;
				}
			}
		}

		// Default to post and page if nothing selected.
		if ( empty( $sanitized['post_types'] ) ) {
			$sanitized['post_types'] = self::DEFAULT_POST_TYPES;
		}

		// Sanitize bulk limit.
		$bulk_limit = isset( $input['bulk_limit'] ) ? (int) $input['bulk_limit'] : self::DEFAULT_BULK_LIMIT;
		$sanitized['bulk_limit'] = max( self::MIN_BULK_LIMIT, min( self::MAX_BULK_LIMIT, $bulk_limit ) );

		return $sanitized;
	}

	/**
	 * Get enabled post types.
	 *
	 * @return array List of enabled post type slugs.
	 */
	public function get_supported_post_types(): array {
		$options    = get_option( self::OPTION_KEY, array() );
		$post_types = $options['post_types'] ?? self::DEFAULT_POST_TYPES;

		/**
		 * Filter the supported post types for SEO abilities.
		 *
		 * @param array $post_types List of post type slugs.
		 */
		return apply_filters( 'seo_abilities_supported_post_types', $post_types );
	}

	/**
	 * Check if a post type is supported.
	 *
	 * @param string $post_type Post type slug.
	 * @return bool True if supported, false otherwise.
	 */
	public function is_supported_post_type( string $post_type ): bool {
		return in_array( $post_type, $this->get_supported_post_types(), true );
	}

	/**
	 * Get bulk operation limit.
	 *
	 * @return int Maximum items per bulk operation.
	 */
	public function get_bulk_limit(): int {
		$options = get_option( self::OPTION_KEY, array() );
		$limit   = (int) ( $options['bulk_limit'] ?? self::DEFAULT_BULK_LIMIT );

		/**
		 * Filter the bulk operation limit.
		 *
		 * @param int $limit Maximum items per bulk operation.
		 */
		return apply_filters( 'seo_abilities_bulk_limit', $limit );
	}

	/**
	 * Get default settings.
	 *
	 * @return array Default settings array.
	 */
	public static function get_defaults(): array {
		return array(
			'post_types' => self::DEFAULT_POST_TYPES,
			'bulk_limit' => self::DEFAULT_BULK_LIMIT,
		);
	}
}
