<?php

class Meow_DBCLNR_Items {

  public static $POSTS = [
    [
      'item' => 'posts_revision',
      'name' => 'Revisions',
      'clean_style' => 'auto',
      'info' => 
        'All the posts marked as revisions. Only useful if you would like to rollback to a previous version of a post.',
      'infoType' => 'info'
    ], [
      'item' => 'posts_auto_drafts',
      'name' => 'Auto Drafts',
      'clean_style' => 'auto',
      'info' => 
        'WordPress automatically saves a draft of your post every 60 seconds. This is useful if you lose your connection or your browser crashes. You can safely delete them.',
      'infoType' => 'info'
    ], [
      'item' => 'posts_deleted_posts',
      'name' => 'Deleted Posts',
      'clean_style' => 'auto',
      'info' => 'Posts marked as deleted. You can safely delete them.',
      'infoType' => 'info'
    ],
  ];

  public static $POSTS_METADATA = [
    [ 
      'item' => 'posts_metadata_orphaned_post_meta', 
      'name' => 'Orphaned Post Meta',
      'clean_style' => 'auto',
      'info' => 'Post Meta which does not have any related post anymore.',
      'infoType' => 'info'
    ], [
      'item' => 'posts_metadata_orphaned_term_meta',
      'name' => 'Orphaned Term Meta',
      'clean_style' => 'auto',
      'info' => 'Term Meta which does not have ant related term anymore.',
      'infoType' => 'info'
    ], [
      'item' => 'posts_metadata_orphaned_term_relationship',
      'name' => 'Orphaned Term Relationship',
      'clean_style' => 'auto',
      'info' => 'Relationships which have no terms anymore.',
      'infoType' => 'info'
    ], [
      'item' => 'posts_metadata_oembed_caches_in_post_meta',
      'name' => 'oEmbed Caches',
      'clean_style' => 'auto',
      'info' => 
        'oEmbed is a protocol that allows WordPress to retrieve the embed code for various types of media, such as YouTube videos. You can reset this cache safely.',
      'infoType' => 'info'
    ], [
      'item' => 'posts_metadata_unused_terms',
      'name' => 'Unused Terms',
      'clean_style' => 'auto',
      'info' => 'Terms which are not used anymore.',
      'infoType' => 'info'
    ], [
      'item' => 'posts_metadata_duplicated_term_meta',
      'name' => 'Duplicated Term Meta',
      'clean_style' => 'manual',
      'info' => 'Term Meta are considered duplicates if they have the same Term and Key.',
      'infoType' => 'info'
    ], [
      'item' => 'posts_metadata_duplicated_post_meta',
      'name' => 'Duplicated Post Meta',
      'clean_style' => 'manual',
      'info' => 
        'Post Meta are considered duplicates if they have the same Post, Key, and Value. This check takes times therefore, the count does not check the value. However, the deletion does 😌 Be careful with other DB cleaners, they actually do not check the value, and erase useful entries.',
      'infoType' => 'warn'
    ],
  ];

  public static $USERS = [
    [
      'item' => 'users_orphaned_user_meta',
      'name' => 'Orphaned Meta',
      'clean_style' => 'auto',
      'info' => 'User Meta which does not have any related user anymore.',
      'infoType' => 'info'
    ],
		[
      'item' => 'users_duplicated_user_meta',
      'name' => 'Duplicated Meta',
      'clean_style' => 'manual',
      'info' => 'User Meta are considered duplicates if they have the same User and Key.',
      'infoType' => 'info'
    ],
  ];

  public static $COMMENTS = [
    [
      'item' => 'comments_spammed_comments',
      'name' => 'Spam',
      'clean_style' => 'auto',
      'info' => 'They are annoying, they are here for you to delete.',
      'infoType' => 'info'
    ], [
      'item' => 'comments_deleted_comments',
      'name' => 'Deleted',
      'clean_style' => 'auto',
      'info' => 'You probably deleted them for a good reason. Trash them forever.',
      'infoType' => 'info'
    ], [
      'item' => 'comments_unapproved_comments',
      'name' => 'Unapproved',
      'clean_style' => 'auto',
      'info' => 'Maybe you could check why they are not approved. They are usually safe to delete.',
      'infoType' => 'info'
    ], [
      'item' => 'comments_orphaned_comments_meta',
      'name' => 'Orphaned Meta',
      'clean_style' => 'auto',
      'info' => 'Comment Meta which are not related to any comment anymore.',
      'infoType' => 'info'
    ], [
      'item' => 'comments_duplicated_comments_meta',
      'name' => 'Duplicated Meta',
      'clean_style' => 'manual',
      'info' => 'Comment Meta which are duplicates.',
      'infoType' => 'info'
    ], [
      'item' => 'comments_pingbacks',
      'name' => 'Pingbacks',
      'clean_style' => 'manual',
      'info' => 
        'A pingback is a special type of comment that is created when you link to another blog post, as long as the other blog is set to accept pingbacks. It could have been a good idea, but it is useless and only exploited by spammers.',
      'infoType' => 'info'
    ],
  ];

  public static $TRANSIENTS = [
    [
      'item' => 'options_expired_transients',
      'name' => 'Expired Transients',
      'clean_style' => 'auto',
      'info' => 'Normally, transients are deleted when they expire. However, if the transient was not accessed before it expired, it will remain in the database. It is safe to delete them.',
      'infoType' => 'info'
    ], [
      'item' => 'options_all_transients',
      'name' => 'All Transients',
      'clean_style' => 'manual',
      'info' => 
        'Transients are used to cache data for a set amount of time. You should only delete all of them if they are an utterly annoyance to you! 😆',
      'infoType' => 'info'
    ],
  ];

  public static function getName( $item ) {
    $items = [
      [ 'prefix' => 'posts_metadata_', 'list' => self::$POSTS_METADATA ],
      [ 'prefix' => 'posts_', 'list' => self::$POSTS ],
      [ 'prefix' => 'users_', 'list' => self::$USERS ],
      [ 'prefix' => 'comments_', 'list' => self::$COMMENTS ],
      [ 'prefix' => 'options_', 'list' => self::$TRANSIENTS ],
    ];
    foreach ( $items as $itemData ) {
      if ( strpos( $item, $itemData['prefix'] ) !== 0 ) {
        continue;
      }
      foreach ( $itemData['list'] as $data ) {
        if ( $data['item'] !== $item ) {
          continue;
        }
        return $data['name'];
      }
      break;
    }
    if ( strpos($item, 'list_post_types_') === 0 ) {
      return str_replace( [ 'list_post_types_' ], '', $item );
    }
    return null;
  }
}

?>