<?php
namespace AIOSEO\BrokenLinkChecker\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Models;

/**
 * Handles notification related routes.
 *
 * @since 1.0.0
 */
class Notifications {
	/**
	 * This allows us to not repeat code over and over.
	 *
	 * @since 1.0.0
	 *
	 * @param  string            $slug The slug of the reminder.
	 * @return \WP_REST_Response       The response.
	 */
	public static function reminder( $slug ) {
		aioseoBrokenLinkChecker()->notifications->remindMeLater( $slug );

		return new \WP_REST_Response( [
			'success'       => true,
			'notifications' => Models\Notification::getNotifications()
		], 200 );
	}

	/**
	 * Dismiss notifications.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function dismissNotifications( $request ) {
		$slugs = $request->get_json_params();

		$notifications = aioseoBrokenLinkChecker()->core->db
			->start( 'aioseo_blc_notifications' )
			->whereIn( 'slug', $slugs )
			->run()
			->models( 'AIOSEO\\BrokenLinkChecker\\Models\\Notification' );

		foreach ( $notifications as $notification ) {
			$notification->dismissed = 1;
			$notification->save();
		}

		return new \WP_REST_Response( [
			'success'       => true,
			'notifications' => Models\Notification::getNotifications()
		], 200 );
	}
}