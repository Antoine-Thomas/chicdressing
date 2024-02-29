<?php

class Meow_DBCLNR_Queries_Core
{
  private $item = "";
  protected $fake_data_post_type = 'dbclnr_fake_post';
  protected $fake_data_post_metakey = '_dbclnr_fake_post_metakey';
  protected $fake_data_metavalue = 'dbclnr_fake_metavalue';
  protected $fake_data_taxonomy = 'dbclnr_fake_taxonomy';
  protected $fake_data_term_metakey = '_dbclnr_fake_term_metakey';
  protected $fake_data_term_name = 'term_name';
  protected $fake_data_user_metakey = '_dbclnr_fake_user_metakey';
  protected $fake_data_user_slug = 'dbclnr-fake-user';
  protected $fake_data_comment_metakey = '_dbclnr_fake_comment_metakey';

  public function __construct()
  {
    $class = get_called_class();
    $this->item = strtolower( str_replace( 'Meow_DBCLNR_Queries_', '', $class ) );
    add_filter( 'dbclnr_get_queries', function ( $queries ) {
      $queries[$this->item] = function (...$args) { return $this->get_query(...$args); };
      return $queries;
    }, 10, 1 );
    add_filter( 'dbclnr_delete_queries', function ( $queries ) {
      $queries[$this->item] = function (...$args) { return $this->delete_query(...$args); };
      return $queries;
    }, 10, 1 );
    add_filter( 'dbclnr_count_queries', function ( $queries ) {
      $queries[$this->item] = function (...$args) { return $this->count_query(...$args); };
      return $queries;
    }, 10, 1 );
    add_filter( 'dbclnr_generate_fake_data_queries', function ( $queries ) {
      $queries[$this->item] = function (...$args) { return $this->generate_fake_data_query(...$args); };
      return $queries;
    }, 10, 1 );
  }

  public function count_query( $age_threshold = 0 )
  {
    throw new Error( 'Not implemented' );
  }

  public function delete_query( $deep_deletions_enabled, $limit, $age_threshold = 0 )
  {
    throw new Error( 'Not implemented' );
  }

  public function get_query( $offset, $limit, $age_threshold = 0 )
  {
    throw new Error( 'Not implemented' );
  }

  public function generate_fake_data_query($age_threshold = 0)
  {
    throw new Error( 'Not implemented' );
  }

  public function generate_fake_post( $age_threshold, $post_status = 'draft' )
  {
    list( $post_modified, $post_modified_gmt ) = $this->get_dates_with_age_threshold( $age_threshold );

    $post = [
      'post_content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
      'post_title' => 'Fake Post',
      'post_status' => $post_status,
      'post_type' => $this->fake_data_post_type,
      'post_date' => $post_modified,
      'post_date_gmt' => $post_modified_gmt,
      'comments_status' => 'open',
    ];

    $result = wp_insert_post( $post, true );
    if ( is_wp_error( $result ) ) {
      throw new Error( $result->get_error_message() );
    }

    return $result;
  }

  public function generate_fake_comment( $age_threshold, $post_id, $comment_approved = '0', $comment_type = 'comment' )
  {
    list( $comment_modified, $comment_modified_gmt ) = $this->get_dates_with_age_threshold( $age_threshold );
    $comment = [
        'comment_post_ID' => $post_id,
        'comment_approved' => $comment_approved,
        'comment_content' => 'fake comment',
        'comment_date' => $comment_modified,
        'comment_date_gmt' => $comment_modified_gmt,
        'comment_type' => $comment_type,
    ];
    return wp_insert_comment( $comment );
  }

  protected function get_dates_with_age_threshold( $age_threshold )
  {
    $week_ago = new DateTime('-' . $age_threshold);
    $week_ago = $week_ago->modify('-1 day');
    $post_modified = $week_ago->format('Y-m-d H:i:s');
    $post_modified_gmt = $week_ago->setTimezone(new DateTimeZone('GMT'))->format('Y-m-d H:i:s');

    return [ $post_modified, $post_modified_gmt ];
  }
}
