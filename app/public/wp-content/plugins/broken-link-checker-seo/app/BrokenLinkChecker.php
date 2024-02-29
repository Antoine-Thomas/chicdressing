<?php
namespace AIOSEO\BrokenLinkChecker {
	// Exit if accessed directly.

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * The main BrokenLinkChecker class.
	 *
	 * @since 1.0.0
	 */
	final class BrokenLinkChecker {
		/**
		 * Holds the instance of the plugin currently in use.
		 *
		 * @since 1.0.0
		 *
		 * @var BrokenLinkChecker
		 */
		private static $instance = null;

		/**
		 * Plugin version for enqueueing, etc.
		 * The value is retrieved from the AIOSEO_BROKEN_LINK_CHECKER_BROKEN_LINK_CHECKER_VERSION constant.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $version = '';

		/**
		 * Whether we're in a dev environment.
		 *
		 * @since 1.0.0
		 *
		 * @var bool
		 */
		public $isDev = false;

		/**
		 * Core class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Core\Core
		 */
		public $core = null;

		/**
		 * InternalOptions class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Options\InternalOptions
		 */
		public $internalOptions = null;

		/**
		 * Pre updates class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Main\PreUpdates
		 */
		public $preUpdates = null;

		/**
		 * Helpers class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Utils\Helpers
		 */
		public $helpers = null;

		/**
		 * Options class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Options\Options
		 */
		public $options = null;

		/**
		 * Updates class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Main\Updates
		 */
		public $updates = null;

		/**
		 * Action scheduler class.
		 *
		 * @since 1.0.0
		 *
		 * @var Utils\ActionScheduler
		 */
		public $actionScheduler = null;

		/**
		 * License class.
		 *
		 * @since 1.0.0
		 *
		 * @var Admin\License
		 */
		public $license = null;

		/**
		 * Access class.
		 *
		 * @since 1.0.0
		 *
		 * @var Utils\Access
		 */
		public $access = null;

		/**
		 * Main class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Main\Main
		 */
		public $main = null;

		/**
		 * API class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Api\Api
		 */
		public $api = null;

		/**
		 * Standalone class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Standalone\Standalone
		 */
		public $standalone = null;

		/**
		 * Notifications class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Admin\Notifications
		 */
		public $notifications = null;

		/**
		 * VueSettings class instance.
		 *
		 * @since 1.1.0
		 *
		 * @var Utils\VueSettings
		 */
		public $vueSettings = null;

		/**
		 * The main BrokenLinkChecker Instance.
		 *
		 * Insures that only one instance of BrokenLinkChecker exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0.0
		 *
		 * @return BrokenLinkChecker The broken link checker instance.
		 */
		public static function instance() {
			if ( null === self::$instance || ! self::$instance instanceof self ) {
				self::$instance = new self();

				self::$instance->init();
			}

			return self::$instance;
		}

		/**
		 * Initialize Broken Link Checker!
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function init() {
			$this->constants();
			$this->includes();
			$this->preLoad();
			$this->load();
		}

		/**
		 * Setup plugin constants.
		 * All the path/URL related constants are defined in main plugin file.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function constants() {
			$defaultHeaders = [
				'name'    => 'Plugin Name',
				'version' => 'Version',
			];

			$pluginData = get_file_data( AIOSEO_BROKEN_LINK_CHECKER_FILE, $defaultHeaders );

			$constants = [
				'AIOSEO_BROKEN_LINK_CHECKER_PLUGIN_BASENAME'  => plugin_basename( AIOSEO_BROKEN_LINK_CHECKER_FILE ),
				'AIOSEO_BROKEN_LINK_CHECKER_PLUGIN_NAME'      => $pluginData['name'],
				'AIOSEO_BROKEN_LINK_CHECKER_PLUGIN_URL'       => plugin_dir_url( AIOSEO_BROKEN_LINK_CHECKER_FILE ),
				'AIOSEO_BROKEN_LINK_CHECKER_VERSION'          => $pluginData['version'],
				'AIOSEO_BROKEN_LINK_CHECKER_MARKETING_URL'    => 'https://aioseo.com/',
				'AIOSEO_BROKEN_LINK_CHECKER_MARKETING_DOMAIN' => 'aioseo.com'
			];

			foreach ( $constants as $constant => $value ) {
				if ( ! defined( $constant ) ) {
					define( $constant, $value );
				}
			}

			$this->version = AIOSEO_BROKEN_LINK_CHECKER_VERSION;
		}

		/**
		 * Including the new files with PHP 5.3 style.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function includes() {
			$dependencies = [
				'/vendor/autoload.php',
				'/vendor/woocommerce/action-scheduler/action-scheduler.php'
			];

			foreach ( $dependencies as $path ) {
				if ( ! file_exists( AIOSEO_BROKEN_LINK_CHECKER_DIR . $path ) ) {
					// Something is not right.
					status_header( 500 );
					wp_die( esc_html__( 'Plugin is missing required dependencies. Please contact support for more information.', 'aioseo-broken-link-checker' ) );
				}
				require_once AIOSEO_BROKEN_LINK_CHECKER_DIR . $path;
			}

			$this->loadVersion();
		}

		/**
		 * Load the version of the plugin we are currently using.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function loadVersion() {
			if (
				! class_exists( '\Dotenv\Dotenv' ) ||
				! file_exists( AIOSEO_BROKEN_LINK_CHECKER_DIR . '/build/.env' )
			) {
				return;
			}

			$dotenv = \Dotenv\Dotenv::createUnsafeImmutable( AIOSEO_BROKEN_LINK_CHECKER_DIR, '/build/.env' );
			$dotenv->load();

			$devPort = strtolower( getenv( 'VITE_AIOSEO_BROKEN_LINK_CHECKER_DEV_PORT' ) );
			if ( ! empty( $devPort ) ) {
				$this->isDev = true;

				// Fix SSL certificate invalid in our local environments.
				add_filter( 'https_ssl_verify', '__return_false' );
			}
		}

		/**
		 * Runs before we load the plugin.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function preLoad() {
			$this->core            = new Core\Core();
			$this->internalOptions = new Options\InternalOptions();
			$this->preUpdates      = new Main\PreUpdates();
		}

		/**
		 * Load our classes.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function load() {
			$this->helpers         = new Utils\Helpers();
			$this->options         = new Options\Options();
			$this->updates         = new Main\Updates();
			$this->actionScheduler = new Utils\ActionScheduler();
			$this->license         = new Admin\License();
			$this->access          = new Utils\Access();
			$this->main            = new Main\Main();
			$this->api             = new Api\Api();
			$this->standalone      = new Standalone\Standalone();
			$this->notifications   = new Admin\Notifications();

			new Admin\Admin();

			add_action( 'init', [ $this, 'loadInit' ], 999 );
		}

		/**
		 * Things that need to load after init.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function loadInit() {
			$this->vueSettings = new Utils\VueSettings( '_aioseo_blc_settings' );
		}
	}
}

namespace {
	// Exit if accessed directly.
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * The function which returns the one AIOSEO instance.
	 *
	 * @since 1.0.0
	 *
	 * @return AIOSEO\BrokenLinkChecker\BrokenLinkChecker The instance.
	 */
	function aioseoBrokenLinkChecker() {
		return AIOSEO\BrokenLinkChecker\BrokenLinkChecker::instance();
	}
}