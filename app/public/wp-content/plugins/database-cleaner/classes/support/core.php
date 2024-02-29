<?php

class Meow_DBCLNR_Support_Core {
  protected $theme_slug = null;

  public function __construct() {
    new Meow_DBCLNR_Support_Freemius();
    new Meow_DBCLNR_Support_Actions_Scheduler();
    new Meow_DBCLNR_Support_Litespeed();
    new Meow_DBCLNR_Support_MeowApps();

    // WordPress Data
    add_filter( 'dbclnr_table_to_plugin', array( $this, 'core_table_to_plugin' ), 10, 1 );
    add_filter( 'dbclnr_post_type_to_plugin', array( $this, 'core_post_type_to_plugin' ), 10, 1 );
    add_filter( 'dbclnr_option_to_plugin', array( $this, 'core_option_to_plugin' ), 10, 1 );
    add_filter( 'dbclnr_cron_to_plugin', array( $this, 'core_cron_to_plugin' ), 10, 1 );
    add_filter( 'dbclnr_metadata_to_plugin', array( $this, 'core_postmeta_to_plugin' ), 10, 1 );
    add_filter( 'dbclnr_metadata_to_plugin', array( $this, 'core_usermeta_to_plugin' ), 10, 1 );

    // Common Plugins Data
    add_filter( 'dbclnr_table_to_plugin', array( $this, 'common_table_to_plugin' ), 10, 1 );
    add_filter( 'dbclnr_post_type_to_plugin', array( $this, 'common_post_type_to_plugin' ), 10, 1 );
    add_filter( 'dbclnr_option_to_plugin', array( $this, 'common_option_to_plugin' ), 10, 1 );
    add_filter( 'dbclnr_cron_to_plugin', array( $this, 'common_cron_to_plugin' ), 10, 1 );
    add_filter( 'dbclnr_metadata_to_plugin', array( $this, 'common_postmeta_to_plugin' ), 10, 1 );
    add_filter( 'dbclnr_metadata_to_plugin', array( $this, 'common_usermeta_to_plugin' ), 10, 1 );

    // User Data: Set by the user through the "Used By" modal
    add_filter( 'dbclnr_table_to_plugin', array( $this, 'user_table_to_plugin' ), 10, 1 );
    add_filter( 'dbclnr_post_type_to_plugin', array( $this, 'user_post_type_to_plugin' ), 10, 1 );
    add_filter( 'dbclnr_option_to_plugin', array( $this, 'user_option_to_plugin' ), 10, 1 );
    add_filter( 'dbclnr_cron_to_plugin', array( $this, 'user_cron_to_plugin' ), 10, 1 );
    add_filter( 'dbclnr_metadata_to_plugin', array( $this, 'user_postmeta_to_plugin' ), 10, 1 );
    add_filter( 'dbclnr_metadata_to_plugin', array( $this, 'user_usermeta_to_plugin' ), 10, 1 );

    // Additional checks
    add_filter( 'dbclnr_check_support_for_option', array( $this, 'check_support_for_option_theme_mods' ), 10, 3 );
	}

/* #region WordPress Data */

  function core_table_to_plugin( $tables ) {
    // That's not very optimized yet but for now that will be okay.
    $lines = file( dirname( __FILE__ ) . '/core_tables.csv', FILE_IGNORE_NEW_LINES );
    foreach ( $lines as $line ) {
      $tables[$line] = "WP";
    }
    return $tables;
  }

  function core_post_type_to_plugin( $post_types ) {
    // That's not very optimized yet but for now that will be okay.
    $lines = file( dirname( __FILE__ ) . '/core_post_types.csv', FILE_IGNORE_NEW_LINES );
    foreach ( $lines as $line ) {
      $post_types[$line] = "WP";
    }
    return $post_types;
  }

  function core_option_to_plugin( $options ) {
    // That's not very optimized yet but for now that will be okay.
    $lines = file( dirname( __FILE__ ) . '/core_options.csv', FILE_IGNORE_NEW_LINES );
    foreach ( $lines as $line ) {
      $options[$line] = "WP";
    }

    // Additional options which need the DB prefix in front of it
    global $wpdb;
    $options[$wpdb->prefix . "user_roles"] = "WP";

    return $options;
  }

  function core_cron_to_plugin( $crons ) {
    // That's not very optimized yet but for now that will be okay.
    $lines = file( dirname( __FILE__ ) . '/core_crons.csv', FILE_IGNORE_NEW_LINES );
    foreach ( $lines as $line ) {
      $crons[$line] = "WP";
    }
    return $crons;
  }

  function core_postmeta_to_plugin( $crons ) {
    // That's not very optimized yet but for now that will be okay.
    $lines = file( dirname( __FILE__ ) . '/core_postmeta.csv', FILE_IGNORE_NEW_LINES );
    foreach ( $lines as $line ) {
      $crons[$line] = "WP";
    }
    return $crons;
  }

  function core_usermeta_to_plugin( $crons ) {
    // That's not very optimized yet but for now that will be okay.
    $lines = file( dirname( __FILE__ ) . '/core_usermeta.csv', FILE_IGNORE_NEW_LINES );
    foreach ( $lines as $line ) {
      $crons[$line] = "WP";
    }
    return $crons;
  }

/* #endregion */

/* #region User Data */

  function user_item_to_plugin( $items, $itemType ) {
    $options = get_option( 'dbclnr_options', null );
    $custom = $options["{$itemType}_usedby"];
    foreach ( $custom as $info ) {
      $item = $info['item'];
      $items[$item] = isset( $items[$item] ) ? $items[$item] : array();
      // if ( $items[$item] === 'WP' ) {
      //   error_log( "Database Cleaner: ${item} (${itemType}) is already native to WP." );
      //   continue;
      // }
      if ( !is_array( $items[$item] ) ) {
        $items[$item] = array();
        //error_log( "Database Cleaner: ${item} (${itemType}) does not return array." );
        //continue;
      }
      array_push( $items[$item], array(
        'plugin' => $info['name'],
        'slugs' => [ $info['slug'] ],
        'native' => true,
        'custom' => true
      ) );
    }
    return $items;
  }

  function user_table_to_plugin( $tables ) {
    return $this->user_item_to_plugin( $tables, 'table' );
  }

  function user_post_type_to_plugin( $tables ) {
    return $this->user_item_to_plugin( $tables, 'post_type' );
  }

  function user_option_to_plugin( $tables ) {
    return $this->user_item_to_plugin( $tables, 'option' );
  }

  function user_cron_to_plugin( $tables ) {
    return $this->user_item_to_plugin( $tables, 'cron' );
  }

  function user_postmeta_to_plugin( $tables ) {
    return $this->user_item_to_plugin( $tables, 'metadata' );
  }

  function user_usermeta_to_plugin( $tables ) {
    return $this->user_item_to_plugin( $tables, 'metadata' );
  }

/* #endregion */

/* #region Common Plugins Data */

  function common_table_to_plugin( $tables ) {
    // That's not very optimized yet but for now that will be okay.

    $lines = file( dirname( __FILE__ ) . '/common_tables.csv', FILE_IGNORE_NEW_LINES );
    foreach ( $lines as $line ) {
      if ( !empty($line) && $line[0] !== '#' && strlen( $line ) > 8 ) {
        list( $plugin, $table, $slugs ) = explode( '|', $line, 3 );
        if ( isset( $tables[$table] ) && $tables[$table] === 'WP' ) { continue; }
        $slugs_arr = explode( ',', $slugs, 8 );
        foreach ( $slugs_arr as $slug ) {
          $tables[$table] = isset( $tables[$table] ) ? $tables[$table] : [];
          $tables[$table][] = [ 'plugin' => $plugin, 'slugs' => [ $slug ], 'native' => true ];
        }
      }
    }
    return $tables;
  }

  function common_post_type_to_plugin( $post_types ) {
    // That's not very optimized yet but for now that will be okay.
    $lines = file( dirname( __FILE__ ) . '/common_post_types.csv', FILE_IGNORE_NEW_LINES );
    foreach ( $lines as $line ) {
      if ( !empty($line) && $line[0] !== '#' && strlen( $line ) > 8 ) {
        list( $plugin, $post_type, $slugs ) = explode( '|', $line, 3 );
        if ( isset( $post_types[$post_type] ) && $post_types[$post_type] === 'WP' ) { continue; }
        $slugs_arr = explode( ',', $slugs, 8 );
        foreach ( $slugs_arr as $slug ) {
          $post_types[$post_type] = isset( $post_types[$post_type] ) ? $post_types[$post_type] : [];
          $post_types[$post_type][] = [ 'plugin' => $plugin, 'slugs' => [ $slug ], 'native' => true ];
        }
      }
    }
    return $post_types;
  }

  function common_option_to_plugin( $options ) {
    // That's not very optimized yet but for now that will be okay.
    $lines = file( dirname( __FILE__ ) . '/common_options.csv', FILE_IGNORE_NEW_LINES );
    foreach ( $lines as $line ) {
      if ( !empty($line) && $line[0] !== '#' && strlen( $line ) > 8 ) {
        list( $plugin, $option, $slugs ) = explode( '|', $line, 3 );
        if ( isset( $options[$option] ) && $options[$option] === 'WP' ) { continue; }
        $slugs_arr = explode( ',', $slugs, 8 );
        foreach ( $slugs_arr as $slug ) {
          $options[$option] = isset( $options[$option] ) ? $options[$option] : [];
          $options[$option][] = [ 'plugin' => $plugin, 'slugs' => [ $slug ], 'native' => true ];
        }
      }
    }
    return $options;
  }

  function common_cron_to_plugin( $crons ) {
    // That's not very optimized yet but for now that will be okay.
    $lines = file( dirname( __FILE__ ) . '/common_crons.csv', FILE_IGNORE_NEW_LINES );
    foreach ( $lines as $line ) {
      if ( !empty($line) && $line[0] !== '#' && strlen( $line ) > 8 ) {
        list( $plugin, $cron, $slugs ) = explode( '|', $line, 3 );
        if ( isset( $crons[$cron] ) && $crons[$cron] === 'WP' ) { continue; }
        $slugs_arr = explode( ',', $slugs, 8 );
        foreach ( $slugs_arr as $slug ) {
          $crons[$cron] = isset( $crons[$cron] ) ? $crons[$cron] : [];
          $crons[$cron][] = [ 'plugin' => $plugin, 'slugs' => [ $slug ], 'native' => true ];
        }
      }
    }
    return $crons;
  }

  function common_postmeta_to_plugin( $crons ) {
    // That's not very optimized yet but for now that will be okay.
    $lines = file( dirname( __FILE__ ) . '/common_postmeta.csv', FILE_IGNORE_NEW_LINES );
    foreach ( $lines as $line ) {
      if ( !empty($line) && $line[0] !== '#' && strlen( $line ) > 8 ) {
        list( $plugin, $cron, $slugs ) = explode( '|', $line, 3 );
        if ( isset( $crons[$cron] ) && $crons[$cron] === 'WP' ) { continue; }
        $slugs_arr = explode( ',', $slugs, 8 );
        foreach ( $slugs_arr as $slug ) {
          $crons[$cron] = isset( $crons[$cron] ) ? $crons[$cron] : [];
          $crons[$cron][] = [ 'plugin' => $plugin, 'slugs' => [ $slug ], 'native' => true ];
        }
      }
    }
    return $crons;
  }

  function common_usermeta_to_plugin( $crons ) {
    // That's not very optimized yet but for now that will be okay.
    $lines = file( dirname( __FILE__ ) . '/common_usermeta.csv', FILE_IGNORE_NEW_LINES );
    foreach ( $lines as $line ) {
      if ( !empty($line) && $line[0] !== '#' && strlen( $line ) > 8 ) {
        list( $plugin, $cron, $slugs ) = explode( '|', $line, 3 );
        if ( isset( $crons[$cron] ) && $crons[$cron] === 'WP' ) { continue; }
        $slugs_arr = explode( ',', $slugs, 8 );
        foreach ( $slugs_arr as $slug ) {
          $crons[$cron] = isset( $crons[$cron] ) ? $crons[$cron] : [];
          $crons[$cron][] = [ 'plugin' => $plugin, 'slugs' => [ $slug ], 'native' => true ];
        }
      }
    }
    return $crons;
  }

/* endregion */

  function get_theme_slug() {
    if ( empty( $this->theme_slug ) ) {
      $this->theme_slug = get_option( 'stylesheet' );
    }
    return $this->theme_slug;
  }

  function check_support_for_option_theme_mods( $status, $option, $active_plugins ) {
    // Theme Mods (Customizer Options)
    if ( substr( $option, 0, 11 ) !== "theme_mods_" ) {
      return $status;
    }
    $theme = str_replace( 'theme_mods_', '', $option );
    if ( $theme === $this->get_theme_slug() ) {
      return [ 'status' => 'ok', 'usedBy' => "Theme Customizer" ];
    }
    return [ 'status' => 'warn', 'usedBy' => "Theme Customizer" ];
  }

}