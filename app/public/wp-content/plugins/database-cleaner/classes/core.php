<?php

class Meow_DBCLNR_Core
{
	public $admin = null;
	public $is_rest = false;
	public $is_cli = false;
	public $site_url = null;
	public $prefix = null;
	private $option_name = 'dbclnr_options';
	private $customQueryItemPrefix = 'cq-';

	protected $log_file = 'database-cleaner.log';
	protected $metadata_tables = [];

	public function __construct() {
		global $wpdb;
		$this->prefix = $wpdb->prefix;
		$this->site_url = get_site_url();
		$this->is_rest = MeowCommon_Helpers::is_rest();
		$this->is_cli = defined( 'WP_CLI' ) && WP_CLI;

		$this->metadata_tables = [
			'Post Meta' => $this->prefix . 'postmeta',
			'User Meta' => $this->prefix . 'usermeta',
		];

		// TODO: Only load this when required
		new Meow_DBCLNR_Support( $this );
		new Meow_DBCLNR_Background( $this );

		// Advanced core
		if ( class_exists( 'MeowPro_DBCLNR_Core' ) ) {
			new MeowPro_DBCLNR_Core( $this );
		}

		// Actions and Filters
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'init', array( $this, 'init') );
	}

	function plugins_loaded() {
		// Part of the core, settings and stuff
		$this->admin = new Meow_DBCLNR_Admin( $this );

		// Only for REST
		if ( $this->is_rest ) {
			new Meow_DBCLNR_Rest( $this, $this->admin );
		}

		// Dashboard
		if ( is_admin() ) {
			new Meow_DBCLNR_UI( $this, $this->admin );
		}
	}

	function init() {
		register_post_type( 'dbclnr_fake_post',
			[
				'labels' => [
					'name'          => __( 'Database Cleaner Fake Posts', 'database-cleaner' ),
					'singular_name' => __( 'Database Cleaner Fake Post', 'database-cleaner' ),
				],
				'supports' => [
					'title',
					'editor',
					'thumbnail',
					'comments',
					'revisions',
					'custom-fields',
				],
				'public' => false,
				'show_in_rest' => false,
			]
		);
		register_taxonomy( 'dbclnr_fake_taxonomy', 'dbclnr_fake_post',
			[
				'labels' => [
					'name'          => __( 'Database Cleaner Fake Taxonomies', 'database-cleaner' ),
					'singular_name' => __( 'Database Cleaner Fake Taxonomy', 'database-cleaner' ),
				],
				'public' => false,
			]
		);
	}

	function get_metadata_tables() {
		return $this->metadata_tables;
	}

	/**
	 *
	 * Roles & Access Rights
	 *
	 */
	function can_access_settings() {
		return apply_filters( 'dbclnr_allow_setup', current_user_can( 'manage_options' ) );
	}

	function can_access_features() {
		return apply_filters( 'dbclnr_allow_usage', current_user_can( 'administrator' ) );
	}

	/**
	 *
	 * Actions for Settings
	 *
	 */
	function get_post_types() {
		global $wpdb;
		return $wpdb->get_col( "SELECT DISTINCT post_type FROM $wpdb->posts" );
	}

	function calculate_posts_query_parameters( $post_type, $post_status, $age_threshold ) {
		global $wpdb;
		$before_date = new DateTime( '-' . $age_threshold );
			$where_type = "";
			$where_status = "";
			if ($post_type) {
				$where_type = $wpdb->prepare( " AND post_type = %s ", $post_type );
			}
			if ($post_status) {
				$where_status = $wpdb->prepare( " AND post_status = %s ", $post_status );
			}
		return [ $where_type, $where_status, $before_date ];
	}

	function get_entry_count( $item ) {
		$age_threshold = $this->get_option( 'aga_threshold' );
		$age_threshold = $age_threshold === 'none' ? 0 : $age_threshold;

		if ( array_key_exists( $item, Meow_DBCLNR_Queries::$COUNT ) ) {
			$queries = new Meow_DBCLNR_Queries();
			return $queries->query_count( $item, $age_threshold );
		}

		$post_type = null;
		$post_status = null;
		$param = $this->get_item_param( $item );
		if ( !$param ) {
			return false;
		}
		if ( $param['var'] === 'post_type' ) {
			$post_type = $param['value'];
		}

		list( $where_type, $where_status, $before_date ) = $this->calculate_posts_query_parameters( $post_type, $post_status, $age_threshold );
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(ID) 
			FROM   $wpdb->posts 
			WHERE  post_modified < %s 
			$where_type
			$where_status
			",
			$before_date->format('Y-m-d H:i:s')
		) );
	}

	public function get_item_param( $item ) {
		$param = null;
		if ( strpos($item, 'list_post_types_') === 0 ) {
			$param = [
				'var' => 'post_type',
				'value' => str_replace( [ 'list_post_types_' ], '', $item )
			];
		}
		return $param;
	}

	function get_entries( $post_type, $post_status, $age_threshold, $offset = 0 ) {
		global $wpdb;
    list( $where_type, $where_status, $before_date ) =
		$this->calculate_posts_query_parameters( $post_type, $post_status, $age_threshold );
		return $wpdb->get_results( $wpdb->prepare( "
			SELECT *
			FROM   $wpdb->posts
			WHERE  post_modified < %s
			$where_type
			$where_status
			LIMIT %d, %d
			",
			$before_date->format('Y-m-d H:i:s'), $offset, Meow_DBCLNR_Queries::$GET_LIMIT
		), ARRAY_A );
	}

	function do_custom_query_count( $query ) {
		global $wpdb;
		$result = $wpdb->get_var( $query );
		if ( $result === null ) {
			throw new RuntimeException($wpdb->last_error);
		}
		return $result;
	}

	function do_custom_query_delete( $query ) {
		global $wpdb;
		$result = $wpdb->query( $query );
		if ( $result === false ) {
			throw new RuntimeException($wpdb->last_error);
		}
		return $result;
	}

	function delete_entries( $item ) {
		$age_threshold = $this->get_option( 'aga_threshold' );
		$age_threshold = $age_threshold === 'none' ? 0 : $age_threshold;

		if ( array_key_exists( $item, Meow_DBCLNR_Queries::$QUERIES ) ) {
			$queries = new Meow_DBCLNR_Queries();
			return $queries->query_delete( $item, $age_threshold );
		}

		$post_type = null;
		$post_status = null;
		$param = $this->get_item_param($item);
		if ( !$param ) {
			throw new Exception( __( 'This item does not exists.', 'database-cleaner' ) );
		}
		${$param['var']} = $param['value'];

		global $wpdb;
		list( $where_type, $where_status, $before_date ) = $this->calculate_posts_query_parameters( $post_type, $post_status, $age_threshold );
		$limit = $this->get_option( 'bulk_batch_size' );
		$deep_deletions = $this->get_option( 'deep_deletions' );
		$count = false;
		if ( $deep_deletions ) {
			$query = $wpdb->prepare( "SELECT ID 
				FROM $wpdb->posts 
				WHERE post_modified < %s 
				$where_type
				$where_status
				LIMIT %d", $before_date->format('Y-m-d H:i:s'), $limit
			);
			$results = $wpdb->get_results( $query, ARRAY_A );
			$count = 0;
			foreach ( $results as $result ) {
				$result = wp_delete_post( $result[ 'ID' ], true );
				if ( $result ) $count++;
			}
		} else {
			$query = $wpdb->prepare( "DELETE 
				FROM $wpdb->posts 
				WHERE post_modified < %s 
				$where_type
				$where_status
				LIMIT %d", $before_date->format('Y-m-d H:i:s'), $limit
			);
			$count = $wpdb->query( $query );
		}

		if ( $count === false ) {
			throw new Error('Failed to delete entries.');
		}
		return $count;
	}

	function remove_cron_entry( $name, $args = array() ) {
		return !!wp_clear_scheduled_hook( $name, $args );
	}

	function format_cron_info( $list ) {
		$data = array();
		foreach ( $list as $unixtime => $item ) {
			if ( !is_array( $item ) ) { 
				continue;
			}
			foreach ( $item as $cron_name => $detail ) {
				foreach ( $detail as $info ) {
					$data[] = array_merge( $info, [
						'cron_name' => $cron_name,
						'unixtime' => $unixtime,
						'args' => $info['args'],
					] );
				}
			}
		}
		return $data;
	}

	function get_core_entry_counts( $list ) {
		$queries = new Meow_DBCLNR_Queries();
		$age_threshold = $this->get_option( 'aga_threshold' );
		$age_threshold = $age_threshold === 'none' ? 0 : $age_threshold;
		$list = $this->add_clean_style_data( $list );
		$counts = [];
		foreach ( $list as $data ) {
			if ( $data['clean_style'] !== 'auto' ) continue;
			$counts[] = [
				'item' => $data['item'],
				'count' => $queries->query_count( $data['item'], $age_threshold ),
			];
		}
		return $counts;
	}

	function add_clean_style_data ( $list ) {
		$options = $this->get_all_options();
		$data = array();
		foreach ( $list as $item ) {
			$data[] = array_merge( $item, [
				'clean_style' => $options[ $item['item'] . '_clean_style' ]
			] );
		}
		return $data;
	}

	// Database size
	function make_initial_database_size( $db_size ) {
		$today_date = date_i18n( "Y-m-d" );
		$today_time = date_i18n( "H:i:s" );
		$new_data = [
			'date' => $today_date,
			'time' => $today_time,
			'size' => $db_size
		];
		return [
			[
				'date' => date_i18n( "Y-m-d", strtotime( '-1 day' ) ),
				'time' => '12:00:00',
				'size' => $db_size
			],
			$new_data
		];
	}

	function update_database_size( $db_size ) {
		$options = $this->get_all_options();
		$sizes = $options[ 'db_historical_sizes' ];

		// If first time, let's set today's size as yesterday, and we'll keep update today's size
		if ( !count( $sizes ) ) {
			update_option( $this->option_name, array_merge(
				$options,
				[ 'db_historical_sizes' => $this->make_initial_database_size( $db_size ) ]
			) );
			return;
		}

		$today_date = date_i18n( "Y-m-d" );
		$today_time = date_i18n( "H:i:s" );
		$new_data = [
			'date' => $today_date,
			'time' => $today_time,
			'size' => $db_size
		];
		$last = &$sizes[count( $sizes ) - 1];

		// Today is already set, let's update it
		if ( $last['date'] === $today_date ) {
			$last['size'] = $db_size;
			$last['time'] = $today_time;
		}
		// Otherwise, we'll add a new entry
		else {
			$sizes[] = $new_data;
		}

		// Let's keep only the last 60 days
		$new_value = array_slice( $sizes, -60 );
		update_option( $this->option_name, array_merge(
			$options,
			[ 'db_historical_sizes' => $new_value ]
		) );
	}

	function get_table_data_count( $table_name ) {
		global $wpdb;
		return $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
	}

	function get_table_data( $table_name, $offset ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "
			SELECT *
			FROM $table_name
			LIMIT %d, %d
			",
			$offset, Meow_DBCLNR_Queries::$GET_LIMIT
		), ARRAY_A );
	}

	function get_tables_size() {
		global $wpdb;
		$results = $wpdb->get_results( $wpdb->prepare( "
			SELECT TABLE_NAME 'table', ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) 'size'
			FROM information_schema.TABLES
			WHERE table_schema = %s
			ORDER BY size DESC
			",
			DB_NAME
		), ARRAY_A );

		$total = 0;
		foreach ( $results as $item ) {
			$total += $item['size'];
		}
		foreach ( $results as &$item ) {
			$item['percent'] = floor( ( $item['size'] * 100 / $total ) * 100 ) / 100;
		}
		return $results;
	}

	function get_database_size() {
		$db_sizes = $this->get_tables_size();
		$total = 0.0;
		foreach ( $db_sizes as $item ) {
			$total += $item['size'];
		}
		$total_size = round( $total * 100 ) / 100;
		return $total_size;
	}

	function get_yesterday_database_size() {
		$option_db_sizes = $this->get_option( 'db_historical_sizes' );
		if ( !count( $option_db_sizes ) || count( $option_db_sizes ) < 2 ) {
			return null;
		}
		return $option_db_sizes[ count( $option_db_sizes ) - 2 ]['size'];
	}

	function refresh_database_size() {
		$total_size = $this->get_database_size();
		$this->update_database_size( $total_size );
		$previous_size = $this->get_yesterday_database_size();
		$this->log("✅ DB size: {$total_size}MB (yesterday: {$previous_size} MB).");
		return $total_size;
	}

	// Logging
	function get_logs_path() {
    $path = $this->get_option( 'logs_path' );
    if ( $path && file_exists( $path ) ) {
      return $path;
    }
    $uploads_dir = wp_upload_dir();
    $path = trailingslashit( $uploads_dir['basedir'] ) . DBCLNR_PREFIX . "_" . $this->random_ascii_chars() . ".log";
		if ( !file_exists( $path ) ) {
			touch( $path );
		}
    $options = $this->get_all_options();
    $options['logs_path'] = $path;
    $this->update_options( $options );
    return $path;
	}

	function log( $data = null ) {
		$log_file_path = $this->get_logs_path();
		$fh = @fopen( $log_file_path, 'a' );
		if ( !$fh ) { return false; }
		$date = date( "Y-m-d H:i:s" );
		if ( is_null( $data ) ) {
			fwrite( $fh, "\n" );
		}
		else {
			fwrite( $fh, "$date: {$data}\n" );
		}
		fclose( $fh );
		return true;
	}

	function get_logs() {
		$log_file_path = $this->get_logs_path();
		$content = file_get_contents( $log_file_path );
		$lines = explode( "\n", $content );
		$lines = array_filter( $lines );
		$lines = array_reverse( $lines );
		$content = implode( "\n", $lines );
		return $content;
	}

	function clear_logs() {
		unlink( $this->get_logs_path() );
	}

	// Fake data
	function generate_fake_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . "dbclnr_fake_table";

		if ( $this->table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			fake_value varchar(255) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		$wpdb->insert(
			$table_name,
			array(
				'time' => current_time( 'mysql' ),
				'fake_value' => 'fake value',
			)
		);
	}

	function table_exists( $table_name ) {
		global $wpdb;
		return strtolower( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) == strtolower( $table_name );
	}

	function generate_fake_cron_job() {
		if ( ! wp_next_scheduled( 'dbclnr_cron_fake' ) ) {
			wp_schedule_event( time(), 'weekly', 'dbclnr_cron_fake' );
		}
		add_action( 'dbclnr_cron_fake', array( $this, 'cron_fake' ) );
	}

	function cron_fake() {
		// nothing to do.
	}

	// #region Options

	function list_options() {

		$options = [
			'aga_threshold' => '7 days',
			'custom_queries' => [],
			'bulk_batch_size' => 100,
			'options_limit' => 10,
			'cron_jobs_limit' => 10,
			'metadata_limit' => 10,
			'db_historical_sizes' => [],
			'db_historical_sizes_limit' => 10,
			'list_post_types_limit' => 10,
			'post_type_usedby' => [],
			'option_usedby' => [],
			'table_usedby' => [],
			'cron_usedby' => [],
			'metadata_usedby' => [],
			'deep_deletions' => false,
			'mode' => 'easy',
			'hide_message' => false,
			'migrated_option_names' => true, // Flag whether it has migrated option's name or not
			'delay' => 100,
			'sweeper_enabled' => false,
			'sweeper_schedule' => 'hourly', // hourly, twicedaily, daily
			'sweeper_tasks' => [
				'items' => null,
				'next_item' => null,
				'next_action' => 'reset',
				'status' => 'completed',
				'last_execution' => null,
			],
			'module_posttypes' => true,
			'module_tables' => true,
			'module_options' => true,
			'module_metadata' => true,
			'module_cronjobs' => true,
			'module_customequeries' => true,
			'logs_path' => null,
		];

		// Clean Style Options for All Items
		$all_items = [
			Meow_DBCLNR_Items::$POSTS,
			Meow_DBCLNR_Items::$POSTS_METADATA,
			Meow_DBCLNR_Items::$USERS,
			Meow_DBCLNR_Items::$COMMENTS,
			Meow_DBCLNR_Items::$TRANSIENTS
		];
		foreach ( $all_items as $grouped_items ) {
			foreach ( $grouped_items as $item ) {
				$options[$item['item'] . '_clean_style'] = $item['clean_style'];
			}
		}

		// Clean Style Options for Post Types
		$list_post_types = $this->get_post_types();
		foreach ( $list_post_types as $post_type ) {
			$options['list_post_types_' . $post_type . '_clean_style'] = 'manual';
		}
		foreach ( Meow_DBCLNR_Support::$core_post_types as $post_type ) {
			$options['list_post_types_' . $post_type . '_clean_style'] = 'never';
		}

		// Set Db sizes default value if not exist.
		if ( !count( $options['db_historical_sizes'] ) ) {
			$db_historical_sizes = $this->make_initial_database_size( $this->get_database_size() );
			$options['db_historical_sizes'] = $db_historical_sizes;
		}

		return $options;
	}

	function reset_options() {
		delete_option( $this->option_name );
	}

	function get_option( $option_name ) {
		$options = $this->get_all_options();
		return $options[ $option_name ];
	}

	function get_all_options() {
		$options = get_option( $this->option_name, null );
		if ( empty( $options ) ) {
			$this->initialize_options();
			$options = get_option( $this->option_name, null );
		} elseif ( ! isset( $options['migrated_option_names'] ) ) {
			$this->migrate_option_names( $options );
			$options = get_option( $this->option_name, null );
		}
		$options = $this->check_options( $options );
		return $options;
	}

	function update_options( $options ) {
		$previous_options = $this->get_all_options();
		$updated = update_option( $this->option_name, $options, false );
		$options = $this->sanitize_options();
		if ( $updated ) {

			// Make a list of the updated options
			$updated_options = [];
			foreach ( $options as $key => $value ) {
				if ( $value !== $previous_options[$key] ) {
					$updated_options[$key] = $value;
				}
			}

			// Check Nyao Sweeper
			if ( isset( $updated_options['sweeper_enabled'] ) || isset( $updated_options['sweeper_schedule'] ) ) {
				wp_clear_scheduled_hook( 'dbclnr_cron_sweeper' );
				if ( $options['sweeper_enabled'] ) {
					$thirty_minutes_later = time() + 30 * 60;
					wp_schedule_event( $thirty_minutes_later, $options['sweeper_schedule'], 'dbclnr_cron_sweeper' );
				}
			}
		}
		return $options;
	}

	// Upgrade from the old way of storing options to the new way.
	function initialize_options() {
		$plugin_options = $this->list_options();
		$options = [];
		// Check if there are older options (from previous versions)
		foreach ( $plugin_options as $option => $default ) {
			$options[$option] = get_option( 'dbclnr_' . $option, $default );
			delete_option( 'dbclnr_' . $option );
		}
		update_option( $this->option_name , $options );
	}

	// Migrate option names from with prefixes to without one.
	function migrate_option_names( $options ) {
		$plugin_options = $this->list_options();
		$new_options = [];
		// Check if there are older options (from previous versions)
		foreach ( $plugin_options as $key => $value ) {
			if ( ! isset( $options[ 'dbclnr_' . $key ] ) ) {
				$new_options[$key] = $value;
				continue;
			}
			$new_options[$key] = $options[ 'dbclnr_' . $key ];
		}
		update_option( $this->option_name , $new_options );
	}

	function check_options( $options = [] ) {
		$plugin_options = $this->list_options();
		$options = empty( $options ) ? [] : $options;
		$hasChanges = false;
		foreach ( $plugin_options as $option => $default ) {
			// The option already exists
			if ( isset( $options[$option] ) ) {
				continue;
			}
			// The option does not exist, so we need to add it.
			// Let's use the old value if any, or the default value.
			$options[$option] = get_option( 'sclegn_' . $option, $default );
			delete_option( 'sclegn_' . $option );
			$hasChanges = true;
		}
		if ( $hasChanges ) {
			update_option( $this->option_name , $options );
		}
		return $options;
	}

	// Validate and keep the options clean and logical.
	function sanitize_options() {
		$options = $this->get_all_options();

		foreach ( $options['custom_queries'] as &$custom_query ) {
			if ( !isset( $custom_query['item'] ) ) {
				$custom_query['item'] = uniqid( $this->customQueryItemPrefix );
				$now = new DateTime();
				$custom_query['created_at'] = $now->format( 'Y-m-d H:i:s' );
			}
		}
		array_multisort( array_column( $options['custom_queries'], 'created_at' ), SORT_DESC, $options['custom_queries'] );
		update_option( $this->option_name, $options, false );

		return $options;
	}

	// #endregion

	public function build_indexes() {
		global $wpdb;

		// This will make the count query faster for Duplicated Post Meta
		$index_exists = $wpdb->get_var("SHOW INDEX FROM $wpdb->postmeta WHERE key_name = 'idx_postmeta_postid_metakey'");
		if (!$index_exists) {
			$wpdb->query("CREATE INDEX idx_postmeta_postid_metakey ON $wpdb->postmeta(post_id, meta_key)");
		}
	}

	public function remove_indexes() {
		global $wpdb;

		$index_exists = $wpdb->get_var("SHOW INDEX FROM $wpdb->postmeta WHERE key_name = 'idx_postmeta_postid_metakey'");
		if ($index_exists) {
			$wpdb->query("DROP INDEX idx_postmeta_postid_metakey ON $wpdb->postmeta");
		}
	}

	private function random_ascii_chars( $length = 8 ) {
		$characters = array_merge( range( 'A', 'Z' ), range( 'a', 'z' ), range( '0', '9' ) );
		$characters_length = count( $characters );
		$random_string = '';

		for ($i = 0; $i < $length; $i++) {
			$random_string .= $characters[rand(0, $characters_length - 1)];
		}

		return $random_string;
	}
}

?>