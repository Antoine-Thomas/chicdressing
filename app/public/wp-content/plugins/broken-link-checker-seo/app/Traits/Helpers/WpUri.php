<?php
namespace AIOSEO\BrokenLinkChecker\Traits\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contains all WordPress related URL, URI, path, slug, etc. related helper methods.
 *
 * @since 1.0.0
 */
trait WpUri {
	/**
	 * Returns the site domain.
	 *
	 * @since 1.0.0
	 *
	 * @return string The site's domain.
	 */
	public function getSiteDomain() {
		return wp_parse_url( home_url(), PHP_URL_HOST );
	}

	/**
	 * Returns the site URL.
	 * NOTE: For multisites inside a sub-directory, this returns the URL for the main site.
	 * This is intentional.
	 *
	 * @since 1.0.0
	 *
	 * @return string The site's domain.
	 */
	public function getSiteUrl() {
		return wp_parse_url( home_url(), PHP_URL_SCHEME ) . '://' . wp_parse_url( home_url(), PHP_URL_HOST );
	}

	/**
	 * Retrieve the home path.
	 *
	 * @since 1.0.0
	 *
	 * @return string The home path.
	 */
	public function getHomePath() {
		$path = wp_parse_url( get_home_url(), PHP_URL_PATH );

		return $path ? trailingslashit( $path ) : '/';
	}
}