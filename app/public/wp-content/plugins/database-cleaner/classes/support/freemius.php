<?php

class Meow_DBCLNR_Support_Freemius {

  public function __construct() {
    add_filter( 'dbclnr_check_support_for_option', array( $this, 'check_support_for_option' ), 10, 3 );
	}

  function check_support_for_option( $status, $option, $active_plugins ) {

    $freemius_options = array(
      'fs_accounts',
      'fs_active_plugins',
      'fs_api_cache',
      'fs_debug_mode',
      'fs_gdpr'
    );

    if ( in_array( $option, $freemius_options ) ) {
      return [ 'status' => 'ok', 'usedBy' => "Freemius" ];
    }
    
    if ( substr( $option, 0, 27 ) === "fs_accounts_default_filter_" ) {
      return [ 'status' => 'ok', 'usedBy' => "Freemius" ];
    }

    return $status;
  }

}