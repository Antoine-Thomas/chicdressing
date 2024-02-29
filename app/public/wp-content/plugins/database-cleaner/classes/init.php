<?php

if ( class_exists( 'MeowPro_DBCLNR_Core' ) && class_exists( 'Meow_DBCLNR_Core' ) ) {
	function dbclnr_thanks_admin_notices() {
		echo wp_kses_post( '<div class="error"><p>' . __( 'Thanks for installing the Pro version of Database Cleaner :) However, the free version is still enabled. Please disable or uninstall it.', 'media-cleaner' ) . '</p></div>' );
	}
	add_action( 'admin_notices', 'dbclnr_thanks_admin_notices' );
	return;
}

spl_autoload_register(function ( $class ) {
  $necessary = true;
  $file = null;
  if ( strpos( $class, 'Meow_DBCLNR_Support_' ) !== false ) {
    $file = DBCLNR_PATH . '/classes/support/' . str_replace( 'meow_dbclnr_support_', '', strtolower( $class ) ) . '.php';
  }
  else if ( strpos( $class, 'Meow_DBCLNR_Queries_' ) !== false ) {
    $file = DBCLNR_PATH . '/classes/queries/' . str_replace( 'meow_dbclnr_queries_', '', strtolower( $class ) ) . '.php';
  }
  else if ( strpos( $class, 'Meow_DBCLNR' ) !== false ) {
    $file = DBCLNR_PATH . '/classes/' . str_replace( 'meow_dbclnr_', '', strtolower( $class ) ) . '.php';
  }
  else if ( strpos( $class, 'MeowCommon_' ) !== false ) {
    $file = DBCLNR_PATH . '/common/' . str_replace( 'meowcommon_', '', strtolower( $class ) ) . '.php';
  }
  else if ( strpos( $class, 'MeowCommonPro_' ) !== false ) {
    $necessary = false;
    $file = DBCLNR_PATH . '/common/premium/' . str_replace( 'meowcommonpro_', '', strtolower( $class ) ) . '.php';
  }
  else if ( strpos( $class, 'MeowPro_DBCLNR' ) !== false ) {
    $necessary = false;
    $file = DBCLNR_PATH . '/premium/' . str_replace( 'meowpro_dbclnr_', '', strtolower( $class ) ) . '.php';
  }
  if ( $file ) {
    if ( !$necessary && !file_exists( $file ) ) {
      return;
    }
    require( $file );
  }
});

//require_once( DBCLNR_PATH . '/classes/api.php');
require_once( DBCLNR_PATH . '/common/helpers.php');

global $dbclnr_core;
$dbclnr_core = new Meow_DBCLNR_Core();

// In admin or Rest API request (REQUEST URI begins with '/wp-json/')
// if ( is_admin() || MeowCommon_Helpers::is_rest() || ( defined( 'WP_CLI' ) && WP_CLI ) )

?>