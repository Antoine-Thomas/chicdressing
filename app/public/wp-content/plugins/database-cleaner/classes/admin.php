<?php
class Meow_DBCLNR_Admin extends MeowCommon_Admin {
	public $core;

	public function __construct( $core ) {
		parent::__construct( DBCLNR_PREFIX, DBCLNR_ENTRY, DBCLNR_DOMAIN, class_exists( 'MeowPro_DBCLNR_Core' ) );
		$this->core = $core;
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'app_menu' ) );

			// Load the scripts only if they are needed by the current screen
			$page = isset( $_GET["page"] ) ? sanitize_text_field( $_GET["page"] ) : null;
			$is_dbclnr_screen = in_array( $page, [ 'dbclnr_settings', 'dbclnr_dashboard' ] );
			$is_meowapps_dashboard = $page === 'meowapps-main-menu';
			if ( $is_meowapps_dashboard || $is_dbclnr_screen ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			}
		}
	}

	function admin_enqueue_scripts() {

		// Load the scripts
		$physical_file = DBCLNR_PATH . '/app/index.js';
		$cache_buster = file_exists( $physical_file ) ? filemtime( $physical_file ) : DBCLNR_VERSION;
		wp_register_script( 'dbclnr-vendor', DBCLNR_URL . 'app/vendor.js', ['wp-element', 'wp-i18n'], $cache_buster );
		wp_register_script( 'dbclnr', DBCLNR_URL . 'app/index.js', ['dbclnr-vendor', 'wp-i18n'], $cache_buster );
		wp_set_script_translations( 'dbclnr', 'database-cleaner' );
		wp_enqueue_script( 'dbclnr' );

		// The MD5 of the translation file built by WP uses app/i18n.js instead of app/index.js
		add_filter( 'load_script_translation_file', function( $file, $handle, $domain ) {
			if ( $domain !== 'database-cleaner' ) { return $file; }
			$file = str_replace( md5( 'app/index.js' ), md5( 'app/i18n.js' ), $file );
			return $file;
		}, 10, 3 );

		// Localize and options
		wp_set_script_translations( 'dbclnr', 'database-cleaner' );
		wp_localize_script( 'dbclnr', 'dbclnr', [
			'api_url' => rest_url( 'database-cleaner/v1' ),
			'rest_url' => rest_url(),
			'plugin_url' => DBCLNR_URL,
			'prefix' => DBCLNR_PREFIX,
			'db_prefix' => $this->core->prefix,
			'domain' => DBCLNR_DOMAIN,
			'is_pro' => class_exists( 'MeowPro_DBCLNR_Core' ),
			'is_registered' => !!$this->is_registered(),
			'rest_nonce' => wp_create_nonce( 'wp_rest' ),
			'core' => [
				'posts' => $this->core->add_clean_style_data( Meow_DBCLNR_Items::$POSTS ),
				'posts_metadata' => $this->core->add_clean_style_data( Meow_DBCLNR_Items::$POSTS_METADATA ),
				'users' => $this->core->add_clean_style_data( Meow_DBCLNR_Items::$USERS ),
				'comments' => $this->core->add_clean_style_data( Meow_DBCLNR_Items::$COMMENTS ),
				'transients' => $this->core->add_clean_style_data( Meow_DBCLNR_Items::$TRANSIENTS ),
			],
			'core_count' => [
				'posts' => $this->core->get_core_entry_counts( Meow_DBCLNR_Items::$POSTS ),
				'posts_metadata' => $this->core->get_core_entry_counts( Meow_DBCLNR_Items::$POSTS_METADATA ),
				'users' => $this->core->get_core_entry_counts( Meow_DBCLNR_Items::$USERS ),
				'comments' => $this->core->get_core_entry_counts( Meow_DBCLNR_Items::$COMMENTS ),
				'transients' => $this->core->get_core_entry_counts( Meow_DBCLNR_Items::$TRANSIENTS ),
			],
			'options' => $this->core->get_all_options(),
			'metadata_tables' => $this->core->get_metadata_tables(),
		] );
	}

	function is_registered() {
		return apply_filters( DBCLNR_PREFIX . '_meowapps_is_registered', false, DBCLNR_PREFIX );
	}

	function app_menu() {
		add_submenu_page( 'meowapps-main-menu', 'Database Cleaner', 'Database Cleaner', 'manage_options',
			'dbclnr_settings', array( $this, 'admin_settings' ) );
	}

	function admin_settings() {
		echo wp_kses_post( '<div id="dbclnr-admin-settings"></div>' );
	}

	function get_db_tables() {
		global $wpdb;
		$results = $wpdb->get_results( $wpdb->prepare( "
			SELECT TABLE_NAME 'table'
			FROM information_schema.TABLES
			WHERE table_schema = %s
			",
			DB_NAME
		), ARRAY_A );

		return $results;
	}

	function get_options() {
		global $wpdb;
		$where = "WHERE option_name NOT LIKE '_transient_%' AND option_name NOT LIKE '_site_transient_%' ";
		$result = $wpdb->get_results( "
				SELECT option_name, length(option_value) AS option_value_length, autoload
				FROM $wpdb->options
				$where
				ORDER BY option_value_length DESC
				", ARRAY_A);

		return $result;
	}

	function get_option_value( $option_name ) {
		global $wpdb;
		$result = $wpdb->get_results( $wpdb->prepare( "
				SELECT option_value
				FROM $wpdb->options
				WHERE option_name = %s
			", $option_name ) );
		return $result;
	}

	function delete_options( $option_names ) {
		global $wpdb;
		$placeholder = array_fill( 0, count( $option_names ), '%s' );
		$placeholder = implode( ', ', $placeholder );
		$result = $wpdb->query( $wpdb->prepare( "
			DELETE t
			FROM $wpdb->options t
			WHERE option_name IN ($placeholder)
		", $option_names ) );

		if ($result === false) {
			throw new Error('Failed to delete the autoloaded options:' . $wpdb->last_error);
		}
		return $result;
	}

	function switch_autoloaded_option( $option_name, $autoload ) {
		global $wpdb;
		$result = $wpdb->query( $wpdb->prepare( "
			UPDATE $wpdb->options
			SET autoload = %s
			WHERE option_name = %s
		", $autoload, $option_name ) );

		return $result;
	}

	function get_metadata( $table, $ids = null, $order_by = 'meta_value_length', $order = 'desc',
		$skip = 0, $limit = 0, $search_list = '', $sizes = []
	) {
		global $wpdb;
		switch ($table) {
			case $wpdb->postmeta:
				return $this->get_postmeta( $ids, $order_by, $order, $skip, $limit, $search_list, $sizes );
			case $wpdb->usermeta:
				return $this->get_usermeta( $ids, $order_by, $order, $skip, $limit, $search_list, $sizes );
		}
		throw new Error("Invalid table specified");
	}

	function get_metadata_count( $table, $ids = null, $search_list = '', $sizes = [] ) {
		global $wpdb;
		switch ($table) {
			case $wpdb->postmeta:
				return $this->get_postmeta_count( $ids, $search_list, $sizes );
			case $wpdb->usermeta:
				return $this->get_usermeta_count( $ids, $search_list, $sizes );
		}
		throw new Error("Invalid table specified");
	}

	function get_postmeta( $ids, $order_by, $order, $skip, $limit, $search_list, $sizes ) {
		global $wpdb;

		$where_clause = $this->get_metadata_where_clause( $wpdb->postmeta, $ids, $search_list, $sizes );
		$order_clause = $this->get_metadata_order_clause( $order_by, $order );
		$limit_clause = $this->get_metadata_limit_clause( $skip, $limit );

		$result = $wpdb->get_results( "
			SELECT meta_id AS id, post_id, meta_key, length(meta_value) AS meta_value_length
			FROM $wpdb->postmeta
			$where_clause
			$order_clause
			$limit_clause
			", ARRAY_A );

		return $result;
	}

	function get_postmeta_count( $ids, $search_list, $sizes ) {
		global $wpdb;

		$where_clause = $this->get_metadata_where_clause( $wpdb->postmeta, $ids, $search_list, $sizes );

		$result = (int)$wpdb->get_var( "
			SELECT COUNT(meta_id)
			FROM $wpdb->postmeta
			$where_clause
			" );

		return $result;
	}

	function get_usermeta( $ids, $order_by, $order, $skip, $limit, $search_list, $sizes ) {
		global $wpdb;

		$where_clause = $this->get_metadata_where_clause( $wpdb->usermeta, $ids, $search_list, $sizes );
		$order_clause = $this->get_metadata_order_clause( $order_by, $order );
		$limit_clause = $this->get_metadata_limit_clause( $skip, $limit );

		$result = $wpdb->get_results( "
			SELECT umeta_id AS id, user_id, meta_key, length(meta_value) AS meta_value_length
			FROM $wpdb->usermeta
			$where_clause
			$order_clause
			$limit_clause
			", ARRAY_A );

		return $result;
	}

	function get_usermeta_count( $ids, $search_list, $sizes ) {
		global $wpdb;

		$where_clause = $this->get_metadata_where_clause( $wpdb->usermeta, $ids, $search_list, $sizes );

		$result = (int)$wpdb->get_var( "
			SELECT COUNT(umeta_id)
			FROM $wpdb->usermeta
			$where_clause
			" );

		return $result;
	}

	function get_metadata_order_clause( $order_by, $order ) {
		$order_clause = 'ORDER BY ';
		$order_column = 'meta_value_length';
		$order = $order === 'asc' ? 'ASC' : 'DESC';
		switch ($order_by) {
			case 'postId':
				$order_column = 'post_id';
				break;

			case 'userId':
				$order_column = 'user_id';
				break;

			case 'name':
				$order_column = 'meta_key';
				break;

			case 'usedBy':
				// nothing to do. "usedBy" sort is done in client side.
				break;

			case 'size':
				$order_column = 'meta_value_length';
				break;
		}
		return $order_clause . $order_column . ' ' . $order;
	}

	function get_metadata_limit_clause( $skip, $limit ) {
		global $wpdb;
		$limit_clause = '';
		if ( !empty($limit) ) {
			$limit_clause = $wpdb->prepare( "LIMIT %d, %d", $skip, $limit );
		}
		return $limit_clause;
	}

	function get_metadata_where_clause( $table, $ids, $search_list, $sizes ) {
		global $wpdb;
		$where_clause = 'WHERE 1=1 ';
		if ( !empty($ids) ) {
			switch ($table) {
				case $wpdb->postmeta:
					$meta_id_column = 'meta_id';
					break;

				case $wpdb->usermeta:
					$meta_id_column = 'umeta_id';
					break;
			}
			$placeholder = array_fill( 0, count( $ids ), '%s' );
			$placeholder = implode( ', ', $placeholder );
			$where_clause .= $wpdb->prepare( "AND $meta_id_column IN ($placeholder)", $ids );
		}
		if ( !empty($search_list) ) {
			$search_where = [];
			foreach ( $search_list as $search ) {
				$search_where[] = $wpdb->prepare( "meta_key LIKE %s", '%' . $search . '%' );
			}
			$search_where_clause = implode( ' AND ', $search_where );
			$where_clause .= "AND ($search_where_clause)";
		}
		if ( count( $sizes ) > 0 ) {
			$size_where = [];
			foreach ( $sizes as $size ) {
				$size_where[] = $wpdb->prepare( "meta_value_length >= %d", (int)$size );
			}
			$size_where_clause = implode( ' OR ', $size_where );
			$where_clause .= "AND ($size_where_clause)";
		}
		return $where_clause;
	}

	function get_metadata_value( $table, $id ) {
		$postmeta_table = $this->core->prefix . 'postmeta';
		$usermeta_table = $this->core->prefix . 'usermeta';
		switch ( $table ) {
			case $postmeta_table:
				return $this->get_postmeta_value( $id );
			case $usermeta_table:
				return $this->get_usermeta_value( $id );
		}
		throw new Error( "Invalid table specified" );
	}

	function get_postmeta_value( $id ) {
		global $wpdb;
		$result = $wpdb->get_results( $wpdb->prepare( "
				SELECT meta_value
				FROM $wpdb->postmeta
				WHERE meta_id = %d
			", $id ) );
		return $result;
	}

	function get_usermeta_value( $id ) {
		global $wpdb;
		$result = $wpdb->get_results( $wpdb->prepare( "
				SELECT meta_value
				FROM $wpdb->usermeta
				WHERE umeta_id = %d
			", $id ) );
		return $result;
	}

	function delete_metadata( $table, $ids ) {
		global $wpdb;

		switch ( $table ) {
			case $wpdb->postmeta:
				return $this->delete_postmeta( $ids );
			case $wpdb->usermeta:
				return $this->delete_usermeta( $ids );
		}

		throw new Error( "Invalid table specified: $table" );
	}

	function delete_postmeta( $ids ) {
		global $wpdb;
		$placeholder = array_fill( 0, count( $ids ), '%s' );
		$placeholder = implode( ', ', $placeholder );
		$result = $wpdb->query( $wpdb->prepare( "
			DELETE t
			FROM $wpdb->postmeta t
			WHERE meta_id IN ($placeholder)
		", $ids ) );

		if ($result === false) {
			throw new Error("Failed to delete the post metadata :" . $wpdb->last_error);
		}
		return $result;
	}
	function delete_usermeta( $ids ) {
		global $wpdb;
		$placeholder = array_fill( 0, count( $ids ), '%s' );
		$placeholder = implode( ', ', $placeholder );
		$result = $wpdb->query( $wpdb->prepare( "
			DELETE t
			FROM $wpdb->usermeta t
			WHERE umeta_id IN ($placeholder)
		", $ids ) );

		if ($result === false) {
			throw new Error("Failed to delete the user metadata :" . $wpdb->last_error);
		}
		return $result;
	}

	function delete_crons( $crons ) {
		foreach ( $crons as $cron ) {
			$result = $this->core->remove_cron_entry( $cron['name'], $cron['args'] );
			if ( $result === false ) {
				throw new Error('Failed to delete the cron option: ' . $cron['name'] );
			}
		}
		return true;
	}

	function delete_table( $table_name ) {
		global $wpdb;
		$result = $wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`;" );
		if ($result === false) {
			error_log('PHP Exception: ' . $wpdb->last_error);
		}
		return $result;
	}

	function optimize_table( $table_name ) {
		global $wpdb;
		$result = $wpdb->query( "OPTIMIZE TABLE `{$table_name}`;" );
		if ($result === false) {
			error_log('PHP Exception: ' . $wpdb->last_error);
		}
		return $result;
	}

	function repair_table( $table_name ) {
		global $wpdb;
		$result = $wpdb->query( "OPTIMIZE TABLE `{$table_name}`;" );
		if ($result === false) {
			error_log('PHP Exception: ' . $wpdb->last_error);
		}
		return $result;
	}

	function valid_item_operation( $item, $is_auto_clean = false ) {
		$clean_style = $this->core->get_option( $item . '_clean_style' );
		if ( $clean_style === 'never' ) {
			return false;
		}
		return !$is_auto_clean || ($is_auto_clean && $clean_style === 'auto');
	}

	function valid_custom_query_operation( $clean_style, $is_auto_clean = false ) {
		if ( !$clean_style || $clean_style === 'never') {
			return false;
		}

		return !$is_auto_clean || ($is_auto_clean && $clean_style === 'auto');
	}

	function valid_table_name( $table_name ) {
		$tables = array_column( $this->get_db_tables(), 'table' );
		return in_array( $table_name, $tables, true );
	}

	function valid_deletable_table_name( $table_name ) {
		$data = apply_filters( 'dbclnr_check_table_info', $this->core->prefix . $table_name, null );
		return strtolower($data['usedBy']) !== 'wordpress';
	}

	function valid_deletable_option_name( $option_name ) {
		$data = apply_filters( 'dbclnr_check_option_info', $option_name, null );
		return strtolower($data['usedBy']) !== 'wordpress';
	}

	function valid_deletable_cron_name( $option_name ) {
		$data = apply_filters( 'dbclnr_check_cron_info', $option_name, null );
		return strtolower($data['usedBy']) !== 'wordpress';
	}

	function valid_deletable_metadata( $metadata_key ) {
		$data = apply_filters( 'dbclnr_check_metadata_info', $metadata_key, null );
		return strtolower($data['usedBy']) !== 'wordpress';
	}

	function valid_metadata_table( $table ) {
		return $table && in_array( $table, $this->core->get_metadata_tables(), true );
	}
}

?>