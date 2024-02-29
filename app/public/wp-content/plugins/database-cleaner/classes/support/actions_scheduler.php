<?php

class Meow_DBCLNR_Support_Actions_Scheduler {

  public function __construct() {
    add_filter( 'dbclnr_check_support_for_table', array( $this, 'check_support_for_table' ), 10, 3 );
    add_filter( 'dbclnr_check_support_for_option', array( $this, 'check_support_for_option' ), 10, 3 );
    add_filter( 'dbclnr_check_support_for_cron', array( $this, 'check_support_for_cron' ), 10, 3 );
	}

  function check_support_for_table( $status, $table, $active_plugins ) {
    if ( substr( $table, 0, 17 ) !== "action_scheduler_" && substr( $table, 0, 16 ) !== "actionscheduler_" ) {
      return $status;
    }
    return [ 'status' => 'ok', 'usedBy' => "Action Scheduler",
      'notes' => "Used by a library (Action Scheduler) which is part of many plugins." ];
  }

  function check_support_for_option( $status, $option, $active_plugins ) {
    $options = ['schema-ActionScheduler_StoreSchema', 'schema-ActionScheduler_LoggerSchema'];
    if ( substr( $option, 0, 17 ) !== "action_scheduler_" && !in_array( $option, $options ) ) {
      return $status;
    }
    return [ 'status' => 'ok', 'usedBy' => "Action Scheduler",
      'notes' => "Used by a library (Action Scheduler) which is part of many plugins." ];
  }

  function check_support_for_cron( $status, $cron, $active_plugins ) {
    if ( $cron !== 'action_scheduler_run_queue' ) {
      return $status;
    }
    return [ 'status' => 'ok', 'usedBy' => "Action Scheduler",
      'notes' => "Used by a library (Action Scheduler) which is part of many plugins." ];
  }

  
}