<?php

class Meow_DBCLNR_Support
{
  public $core = null;
	public $admin = null;
	public $is_rest = false;
	public $is_cli = false;
	public $site_url = null;
  public $prefix = "";
  public $active_plugins = null;

  static public $core_tables = [ 'postmeta', 'posts',
    'terms', 'termmeta', 'term_relationships', 'term_taxonomy',
    'usermeta', 'users', 'commentmeta', 'comments', 'links', 'options' ];
  static public $core_post_types = [ 'attachment', 'page', 'post', 'revision', 'custom_css',
    'nav_menu_item', 'oembed_cache', 'wp_block', 'customize_changeset', 'wp_global_styles' ];

  private $support_loaded = false;
  private $table_to_plugin = null;
  private $post_type_to_plugin = null;
  private $option_to_plugin = null;
  private $cron_to_plugin = null;
  private $metadata_to_plugin = null;

	public function __construct( $core ) {
    $this->core = $core;
    add_filter( 'dbclnr_check_table_info', array( $this, 'check_table_info' ), 10, 1 );
		add_filter( 'dbclnr_check_post_type_info', array( $this, 'check_post_type_info' ), 10, 1 );
		add_filter( 'dbclnr_check_option_info', array( $this, 'check_option_info' ), 10, 1 );
		add_filter( 'dbclnr_check_cron_info', array( $this, 'check_cron_info' ), 10, 1 );
		add_filter( 'dbclnr_check_metadata_info', array( $this, 'check_metadata_info' ), 10, 1 );
	}

  function load_support_db() {
    if ( !$this->support_loaded ) {
      $this->support_loaded = new Meow_DBCLNR_Support_Core();
      do_action( 'dbclnr_support_db_loaded' );
    }
  }

  function get_table_to_plugin() {
    if ( $this->table_to_plugin === null ) {
      $this->load_support_db();
      $this->table_to_plugin = apply_filters( 'dbclnr_table_to_plugin', [] );
    }
    return $this->table_to_plugin;
  }

  function get_post_type_to_plugin() {
    if ( $this->post_type_to_plugin === null ) {
      $this->load_support_db();
      $this->post_type_to_plugin = apply_filters( 'dbclnr_post_type_to_plugin', [] );
    }
    return $this->post_type_to_plugin;
  }

  function get_option_to_plugin() {
    if ( $this->option_to_plugin === null ) {
      $this->load_support_db();
      $this->option_to_plugin = apply_filters( 'dbclnr_option_to_plugin', [] );
    }
    return $this->option_to_plugin;
  }

  function get_cron_to_plugin() {
    if ( $this->cron_to_plugin === null ) {
      $this->load_support_db();
      $this->cron_to_plugin = apply_filters( 'dbclnr_cron_to_plugin', [] );
    }
    return $this->cron_to_plugin;
  }

  function get_metadata_to_plugin() {
    if ( $this->metadata_to_plugin === null ) {
      $this->load_support_db();
      $this->metadata_to_plugin = apply_filters( 'dbclnr_metadata_to_plugin', [] );
    }
    return $this->metadata_to_plugin;
  }

  function get_active_plugins_list() {
    if ( $this->active_plugins === null ) {
      $this->active_plugins = (array)get_option( 'active_plugins', [] );
      foreach ( $this->active_plugins as &$plugin ) {
        $exploded = explode( '/', $plugin );
        $plugin = preg_replace( '/\-pro$/', '', $exploded[0] );
      }
    }
    return $this->active_plugins;
  }

  function check_support( $item_name, $support_table, $kind = 'table' ) {
    $active_plugins = $this->get_active_plugins_list();
    $status = [ 'status' => 'n/a', 'usedBy' => "" ];
    $hasFound = false;
    if ( key_exists( $item_name, $support_table ) ) {
      $plugins = $support_table[$item_name];
      if ( $plugins === 'WP' ) {
        $status = [ 'status' => 'ok', 'usedBy' => "WordPress" ];
        $hasFound = true;
      }
      else {
        $main_plugin = null;
        $hasFound = false;
        foreach ( $plugins as $plugin ) {
          if ( $hasFound ) {
            // I don't like this double break! Maybe we could make this more beautiful (in terms of coding).
            break;
          }
          $main_plugin = ( empty( $main_plugin ) || isset( $plugin['native']) ) ? $plugin : $main_plugin;
          if ( is_array( $plugin ) ) {
            foreach ( $plugin['slugs'] as $slug ) {
              if ( $slug === '*' || in_array( $slug, $active_plugins ) ) {
                $status = [
                  'status' => 'ok', 
                  'usedBy' => $plugin['plugin'],
                  'custom' => isset( $plugin['custom'] ) ? $plugin['custom'] : null
                ];
                $hasFound = true;
                break;
              }
            }
          }
          else {
            error_log( 'Database Cleaner: Plugin support was badly set up (' . $plugin . ')' );
          }
        }
      }
      if ( !$hasFound ) {
        $status = [ 
          'status' => 'warn',
          'usedBy' => $main_plugin['plugin'],
          'custom' => isset( $main_plugin['custom'] ) ? $main_plugin['custom'] : null
        ];
      }
    }

    // if ( $item_name === 'edd_sl_4a863a79162200176337a215508377b7' ) {
    //   error_log( "Let's look into item : " . $item_name );
    // }


    // If custom, should be returned right away
    if ( isset( $status['custom'] ) && $status['custom'] ) {
      return $status;
    }

    // If not found, let's look a bit more
    if ( !$hasFound ) {
      $status = apply_filters( "dbclnr_check_support_for_{$kind}", $status, $item_name, $active_plugins );
    }

    return $status;
  }

  function check_table_info( $table_name )
	{
    // Remove the prefix (to original the real table name)
    if ( strpos( $table_name, $this->core->prefix ) === 0 ) {
      $table_name = substr( $table_name, strlen( $this->core->prefix ) );
    }
    return $this->check_support( $table_name, $this->get_table_to_plugin(), 'table' );
	}

	function check_post_type_info( $post_type ) {
    return $this->check_support( $post_type, $this->get_post_type_to_plugin(), 'post_type' );
	}

  function check_option_info( $option ) {
    return $this->check_support( $option, $this->get_option_to_plugin(), 'option' );
	}

  function check_cron_info( $cron ) {
    return $this->check_support( $cron, $this->get_cron_to_plugin(), 'cron' );
	}

  function check_metadata_info( $metadata_key ) {
    return $this->check_support( $metadata_key, $this->get_metadata_to_plugin(), 'metadata' );
	}
}