<?php
namespace AIOSEO\BrokenLinkChecker\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin deinstallation.
 *
 * @since 1.0.0
 */
class Uninstall {
	/**
	 * Removes all our tables and options.
	 *
	 * @since 1.0.0
	 *
	 * @param  bool $force Whether we should ignore the uninstall option or not. We ignore it when we reset all data via the Debug Panel.
	 * @return void
	 */
	public function dropData( $force = false ) {
		// Confirm that user has decided to remove all data, otherwise stop.
		if (
			! $force &&
			( ! aioseoBrokenLinkChecker()->options->advanced->enable || ! aioseoBrokenLinkChecker()->options->advanced->uninstall )
		) {
			return;
		}

		// Delete all our custom tables.
		global $wpdb;
		foreach ( $this->getDbTables() as $tableName ) {
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $tableName ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// Delete all the plugin settings.
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'aioseo\_blc\_%'" );

		// Remove any transients we've left behind.
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_aioseo\_blc\_%'" );

		// Delete all entries from the action scheduler table.
		$wpdb->query( "DELETE FROM {$wpdb->prefix}actionscheduler_actions WHERE hook LIKE 'aioseo\_blc\_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}actionscheduler_groups WHERE slug = 'aioseo\_blc'" );
	}

	/**
	 * Returns all the DB tables with their prefix.
	 *
	 * @since 1.0.0
	 *
	 * @return array List of tables.
	 */
	private function getDbTables() {
		global $wpdb;

		$tables = [];
		foreach ( aioseoBrokenLinkChecker()->core->db->customTables as $tableName ) {
			$tables[] = $wpdb->prefix . $tableName;
		}

		return $tables;
	}
}