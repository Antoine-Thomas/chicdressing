<?php
namespace AIOSEO\BrokenLinkChecker\Models;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Notification DB model class.
 *
 * @since 1.0.0
 */
class Notification extends Model {
	/**
	 * The name of the table in the database, without the prefix.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $table = 'aioseo_blc_notifications';

	/**
	 * An array of fields to set to null if already empty when saving to the database.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $nullFields = [
		'start',
		'end',
		'notification_id',
		'notification_name',
		'button1_label',
		'button1_action',
		'button2_label',
		'button2_action'
	];

	/**
	 * Fields that should be json encoded on save and decoded on get.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $jsonFields = [ 'level' ];

	/**
	 * Fields that should be boolean values.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $booleanFields = [ 'dismissed' ];

	/**
	 * Fields that should be hidden when serialized.
	 *
	 * @var array
	 */
	protected $hidden = [ 'id' ];

	/**
	 * An array of fields attached to this resource.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $columns = [
		'id',
		'slug',
		'notification_id',
		'notification_name',
		'title',
		'content',
		'type',
		'level',
		'start',
		'end',
		'button1_label',
		'button1_action',
		'button2_label',
		'button2_action',
		'dismissed',
		'new',
		'created',
		'updated'
	];

	/**
	 * Returns all notifications.
	 *
	 * @since 1.0.0
	 *
	 * @param  bool  $reset Whether or not to reset the new notifications.
	 * @return array        List of notifications.
	 */
	public static function getNotifications( $reset = true ) {
		return [
			'active'    => self::getAllActiveNotifications(),
			'new'       => self::getNewNotifications( $reset ),
			'dismissed' => self::getAllDismissedNotifications()
		];
	}

	/**
	 * Returns the active notifications.
	 *
	 * @since 1.0.0
	 *
	 * @return array List of active notifications.
	 */
	public static function getAllActiveNotifications() {
		$notifications = array_values( json_decode( wp_json_encode( self::getActiveNotifications() ), true ) );

		return $notifications;
	}

	/**
	 * Retrieve active notifications.
	 *
	 * @since 1.0.0
	 *
	 * @return array List of active notifications or empty.
	 */
	public static function getActiveNotifications() {
		return self::filterNotifications(
			aioseoBrokenLinkChecker()->core->db
				->start( 'aioseo_blc_notifications' )
				->where( 'dismissed', 0 )
				->whereRaw( "(start <= '" . gmdate( 'Y-m-d H:i:s' ) . "' OR start IS NULL)" )
				->whereRaw( "(end >= '" . gmdate( 'Y-m-d H:i:s' ) . "' OR end IS NULL)" )
				->orderBy( 'start DESC, created DESC' )
				->run()
				->models( 'AIOSEO\\BrokenLinkChecker\\Models\\Notification' )
		);
	}

	/**
	 * Returns all new notifications. After retrieving them, this will reset them.
	 * This means that calling this method twice will result in no results
	 * the second time. The only exception is to pass false as a reset variable to prevent it.
	 *
	 * @since 1.0.0
	 *
	 * @param  boolean $reset Whether or not to reset the new notifications.
	 * @return array          List of new notifications if any exist.
	 */
	public static function getNewNotifications( $reset = true ) {
		$notifications = self::filterNotifications(
			aioseoBrokenLinkChecker()->core->db
				->start( 'aioseo_blc_notifications' )
				->where( 'dismissed', 0 )
				->where( 'new', 1 )
				->whereRaw( "(start <= '" . gmdate( 'Y-m-d H:i:s' ) . "' OR start IS NULL)" )
				->whereRaw( "(end >= '" . gmdate( 'Y-m-d H:i:s' ) . "' OR end IS NULL)" )
				->orderBy( 'start DESC, created DESC' )
				->run()
				->models( 'AIOSEO\\BrokenLinkChecker\\Models\\Notification' )
		);

		if ( $reset ) {
			self::resetNewNotifications();
		}

		return $notifications;
	}

	/**
	 * Resets all new notifications.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function resetNewNotifications() {
		aioseoBrokenLinkChecker()->core->db
			->update( 'aioseo_blc_notifications' )
			->where( 'new', 1 )
			->set( 'new', 0 )
			->run();
	}

	/**
	 * Returns a list of dismissed notifications.
	 *
	 * @since 1.0.0
	 *
	 * @return array List of dismissed notifications.
	 */
	public static function getAllDismissedNotifications() {
		return array_values( json_decode( wp_json_encode( self::getDismissedNotifications() ), true ) );
	}

	/**
	 * Retrieve dismissed notifications.
	 *
	 * @since 1.0.0
	 *
	 * @return array List of dismissed notifications or empty.
	 */
	public static function getDismissedNotifications() {
		return self::filterNotifications(
			aioseoBrokenLinkChecker()->core->db
				->start( 'aioseo_blc_notifications' )
				->where( 'dismissed', 1 )
				->orderBy( 'updated DESC' )
				->run()
				->models( 'AIOSEO\\BrokenLinkChecker\\Models\\Notification' )
		);
	}

	/**
	 * Returns a notification by its name.
	 *
	 * @since 1.0.0
	 *
	 * @param  string       $name The notification name.
	 * @return Notification       The notification.
	 */
	public static function getNotificationByName( $name ) {
		return aioseoBrokenLinkChecker()->core->db
			->start( 'aioseo_blc_notifications' )
			->where( 'notification_name', $name )
			->run()
			->model( 'AIOSEO\\BrokenLinkChecker\\Models\\Notification' );
	}

	/**
	 * Stores a new notification in the DB.
	 *
	 * @since 1.0.0
	 *
	 * @param  array        $fields       The fields.
	 * @return Notification $notification The notification.
	 */
	public static function addNotification( $fields ) {
		// Set the dismissed status to false.
		$fields['dismissed'] = 0;

		$notification = new self();
		$notification->set( $fields );
		$notification->save();

		return $notification;
	}

	/**
	 * Deletes a notification by its name.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $name The notification name.
	 * @return void
	 */
	public static function deleteNotificationByName( $name ) {
		aioseoBrokenLinkChecker()->core->db
			->delete( 'aioseo_blc_notifications' )
			->where( 'notification_name', $name )
			->run();
	}

	/**
	 * Filters the notifications based on the targeted plan levels.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $notifications          The notifications
	 * @return array $remainingNotifications The remaining notifications.
	 */
	public static function filterNotifications( $notifications ) {
		$remainingNotifications = [];
		foreach ( $notifications as $notification ) {
			$levels = $notification->level;
			if ( ! is_array( $levels ) ) {
				$levels = empty( $notification->level ) ? [ 'all' ] : [ $notification->level ];
			}

			foreach ( $levels as $level ) {
				if ( ! aioseoBrokenLinkChecker()->notifications->validateType( $level ) ) {
					continue 2;
				}
			}

			$remainingNotifications[] = $notification;
		}

		return $remainingNotifications;
	}
}