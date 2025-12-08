<?php
/**
 * Main Plugin class.
 *
 * @package SeoAbilities
 */

namespace SeoAbilities;

use SeoAbilities\Providers\ProviderFactory;
use SeoAbilities\Providers\ProviderInterface;
use SeoAbilities\Services\PostService;
use SeoAbilities\Services\ImageService;

/**
 * Plugin singleton class that orchestrates initialization of all components.
 */
class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Ability registrar instance.
	 *
	 * @var AbilityRegistrar|null
	 */
	private ?AbilityRegistrar $ability_registrar = null;

	/**
	 * SEO provider instance.
	 *
	 * @var ProviderInterface|null
	 */
	private ?ProviderInterface $provider = null;

	/**
	 * Services container.
	 *
	 * @var array<string, object>
	 */
	private array $services = array();

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to enforce singleton pattern.
	 */
	private function __construct() {
		$this->init_provider();
		$this->init_services();
		$this->init_components();
		$this->init_hooks();
	}

	/**
	 * Initialize the SEO provider.
	 *
	 * @return void
	 */
	private function init_provider(): void {
		$this->provider = ProviderFactory::create();

		// Show admin notice if no provider available.
		if ( null === $this->provider ) {
			add_action( 'admin_notices', 'SeoAbilities\\seo_abilities_missing_seo_plugin_notice' );
		}
	}

	/**
	 * Initialize services.
	 *
	 * @return void
	 */
	private function init_services(): void {
		$this->services['post']  = new PostService();
		$this->services['image'] = new ImageService();
	}

	/**
	 * Initialize plugin components.
	 *
	 * @return void
	 */
	private function init_components(): void {
		$this->settings = new Settings();

		// Only register abilities if provider is available.
		if ( null !== $this->provider ) {
			$this->ability_registrar = new AbilityRegistrar(
				$this->settings,
				$this->provider,
				$this->services
			);
		}
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Load text domain for translations.
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wp-abilities-seo-extension',
			false,
			dirname( SEO_ABILITIES_BASENAME ) . '/languages'
		);
	}

	/**
	 * Get the SEO provider.
	 *
	 * @return ProviderInterface|null
	 */
	public function get_provider(): ?ProviderInterface {
		return $this->provider;
	}

	/**
	 * Check if a provider is active.
	 *
	 * @return bool
	 */
	public function is_provider_active(): bool {
		return null !== $this->provider;
	}

	/**
	 * Get the settings instance.
	 *
	 * @return Settings
	 */
	public function get_settings(): Settings {
		return $this->settings;
	}

	/**
	 * Get a service by name.
	 *
	 * @param string $name Service name.
	 * @return object|null
	 */
	public function get_service( string $name ): ?object {
		return $this->services[ $name ] ?? null;
	}

	/**
	 * Prevent cloning of singleton.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization of singleton.
	 *
	 * @return void
	 * @throws \Exception When attempting to unserialize.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
