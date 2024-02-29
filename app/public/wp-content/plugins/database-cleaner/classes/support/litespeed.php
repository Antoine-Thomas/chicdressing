<?php

class Meow_DBCLNR_Support_Litespeed {

  public function __construct() {
    add_filter( 'dbclnr_check_support_for_option', array( $this, 'check_support_for_option' ), 10, 3 );
	}

  function check_support_for_option( $status, $option, $active_plugins ) {
    if ( substr( $option, 0, 15 ) !== "litespeed.conf." ) {
      return $status;
    }
    if ( in_array( 'litespeed-cache', $active_plugins ) ) {
      return [ 'status' => 'ok', 'usedBy' => "Litespeed" ];
    }
    return [ 'status' => 'warn', 'usedBy' => "Litespeed" ];
  }

}