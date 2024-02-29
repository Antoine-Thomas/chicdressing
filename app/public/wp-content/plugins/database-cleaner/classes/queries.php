<?php

class Meow_DBCLNR_Queries
{
	public static $COUNT = [
		'posts_revision' => 'count_posts_revision',
		'posts_auto_drafts' => 'count_posts_auto_drafts',
		'posts_deleted_posts' => 'count_posts_deleted_posts',
		'posts_metadata_orphaned_post_meta' => 'count_posts_metadata_orphaned_post_meta',
		'posts_metadata_duplicated_post_meta' => 'count_posts_metadata_duplicated_post_meta',
		'posts_metadata_oembed_caches_in_post_meta' => 'count_posts_metadata_oembed_caches_in_post_meta',
		'posts_metadata_orphaned_term_meta' => 'count_posts_metadata_orphaned_term_meta',
		'posts_metadata_duplicated_term_meta' => 'count_posts_metadata_duplicated_term_meta',
		'posts_metadata_orphaned_term_relationship' => 'count_posts_metadata_orphaned_term_relationship',
		'posts_metadata_unused_terms' => 'count_posts_metadata_unused_terms',
		'users_orphaned_user_meta' => 'count_users_orphaned_user_meta',
		'users_duplicated_user_meta' => 'count_users_duplicated_user_meta',
		'comments_unapproved_comments' => 'count_comments_unapproved_comments',
		'comments_spammed_comments' => 'count_comments_spammed_comments',
		'comments_deleted_comments' => 'count_comments_deleted_comments',
		'comments_orphaned_comments_meta' => 'count_comments_orphaned_comments_meta',
		'comments_duplicated_comments_meta' => 'count_comments_duplicated_comments_meta',
		'comments_pingbacks' => 'count_comments_pingbacks',
		'options_expired_transients' => 'count_options_expired_transients',
		'options_all_transients' => 'count_options_all_transients',
	];

	public static $QUERIES = [
		'posts_revision' => 'delete_posts_revision',
		'posts_auto_drafts' => 'delete_posts_auto_drafts',
		'posts_deleted_posts' => 'delete_posts_deleted_posts',
		'posts_metadata_orphaned_post_meta' => 'delete_posts_metadata_orphaned_post_meta',
		'posts_metadata_duplicated_post_meta' => 'delete_posts_metadata_duplicated_post_meta',
		'posts_metadata_oembed_caches_in_post_meta' => 'delete_posts_metadata_oembed_caches_in_post_meta',
		'posts_metadata_orphaned_term_meta' => 'delete_posts_metadata_orphaned_term_meta',
		'posts_metadata_duplicated_term_meta' => 'delete_posts_metadata_duplicated_term_meta',
		'posts_metadata_orphaned_term_relationship' => 'delete_posts_metadata_orphaned_term_relationship',
		'posts_metadata_unused_terms' => 'delete_posts_metadata_unused_terms',
		'users_orphaned_user_meta' => 'delete_users_orphaned_user_meta',
		'users_duplicated_user_meta' => 'delete_users_duplicated_user_meta',
		'comments_unapproved_comments' => 'delete_comments_unapproved_comments',
		'comments_spammed_comments' => 'delete_comments_spammed_comments',
		'comments_deleted_comments' => 'delete_comments_deleted_comments',
		'comments_orphaned_comments_meta' => 'delete_comments_orphaned_comments_meta',
		'comments_duplicated_comments_meta' => 'delete_comments_duplicated_comments_meta',
		'comments_pingbacks' => 'delete_comments_pingbacks',
		'options_expired_transients' => 'delete_options_expired_transients',
		'options_all_transients' => 'delete_options_all_transients',
	];

	public static $GET = [
		'posts_revision' => 'get_posts_revision',
		'posts_auto_drafts' => 'get_posts_auto_drafts',
		'posts_deleted_posts' => 'get_posts_deleted_posts',
		'posts_metadata_orphaned_post_meta' => 'get_posts_metadata_orphaned_post_meta',
		'posts_metadata_duplicated_post_meta' => 'get_posts_metadata_duplicated_post_meta',
		'posts_metadata_oembed_caches_in_post_meta' => 'get_posts_metadata_oembed_caches_in_post_meta',
		'posts_metadata_orphaned_term_meta' => 'get_posts_metadata_orphaned_term_meta',
		'posts_metadata_duplicated_term_meta' => 'get_posts_metadata_duplicated_term_meta',
		'posts_metadata_orphaned_term_relationship' => 'get_posts_metadata_orphaned_term_relationship',
		'posts_metadata_unused_terms' => 'get_posts_metadata_unused_terms',
		'users_orphaned_user_meta' => 'get_users_orphaned_user_meta',
		'users_duplicated_user_meta' => 'get_users_duplicated_user_meta',
		'comments_unapproved_comments' => 'get_comments_unapproved_comments',
		'comments_spammed_comments' => 'get_comments_spammed_comments',
		'comments_deleted_comments' => 'get_comments_deleted_comments',
		'comments_orphaned_comments_meta' => 'get_comments_orphaned_comments_meta',
		'comments_duplicated_comments_meta' => 'get_comments_duplicated_comments_meta',
		'comments_pingbacks' => 'get_comments_pingbacks',
		'options_expired_transients' => 'get_options_expired_transients',
		'options_all_transients' => 'get_options_all_transients',
	];
	public static $GET_LIMIT = 10;

	public static $GENERATE_FAKE_DATA = [
		'posts_revision' => true,
		'posts_auto_drafts' => true,
		'posts_deleted_posts' => true,
		'posts_metadata_orphaned_post_meta' => true,
		'posts_metadata_duplicated_post_meta' => true,
		'posts_metadata_oembed_caches_in_post_meta' => true,
		'posts_metadata_orphaned_term_meta' => true,
		'posts_metadata_duplicated_term_meta' => true,
		'posts_metadata_orphaned_term_relationship' => true,
		'posts_metadata_unused_terms' => true,
		'users_orphaned_user_meta' => true,
		'users_duplicated_user_meta' => true,
		'comments_unapproved_comments' => true,
		'comments_spammed_comments' => true,
		'comments_deleted_comments' => true,
		'comments_orphaned_comments_meta' => true,
		'comments_duplicated_comments_meta' => true,
		'comments_pingbacks' => true,
		'options_expired_transients' => true,
		'options_all_transients' => true,
	];

	public function __construct() {
		new Meow_DBCLNR_Queries_Comments_Deleted_Comments();
		new Meow_DBCLNR_Queries_Comments_Duplicated_Comments_Meta();
		new Meow_DBCLNR_Queries_Comments_Orphaned_Comments_Meta();
		new Meow_DBCLNR_Queries_Comments_Pingbacks();
		new Meow_DBCLNR_Queries_Comments_Spammed_Comments();
		new Meow_DBCLNR_Queries_Comments_Unapproved_Comments();
		new Meow_DBCLNR_Queries_Options_All_Transients();
		new Meow_DBCLNR_Queries_Options_Expired_Transients();
		new Meow_DBCLNR_Queries_Posts_Auto_Drafts();
		new Meow_DBCLNR_Queries_Posts_Deleted_Posts();
		new Meow_DBCLNR_Queries_Posts_Metadata_Duplicated_Post_Meta();
		new Meow_DBCLNR_Queries_Posts_Metadata_Duplicated_Term_Meta();
		new Meow_DBCLNR_Queries_Posts_Metadata_Oembed_Caches_In_Post_Meta();
		new Meow_DBCLNR_Queries_Posts_Metadata_Orphaned_Post_Meta();
		new Meow_DBCLNR_Queries_Posts_Metadata_Orphaned_Term_Meta();
		new Meow_DBCLNR_Queries_Posts_Metadata_Orphaned_Term_Relationship();
		new Meow_DBCLNR_Queries_Posts_Metadata_Unused_Terms();
		new Meow_DBCLNR_Queries_Posts_Revision();
		new Meow_DBCLNR_Queries_Users_Duplicated_User_Meta();
		new Meow_DBCLNR_Queries_Users_Orphaned_User_Meta();
	}

	public static function query_count( $item, $age_threshold = 0 ) {
		$functions = apply_filters( 'dbclnr_count_queries', [] );
		if ( !isset( $functions[$item] ) ) {
			throw new Error( "Count query for $item not found." );
		}
		return $functions[$item]( $age_threshold );
	}

	public static function query_delete( $item, $age_threshold = 0 ) {
		$functions = apply_filters( 'dbclnr_delete_queries', [] );
		if ( !isset( $functions[$item] ) ) {
			throw new Error( "Delete query for $item not found." );
		}
		return $functions[$item]( self::is_deep_deletions_enabled(), self::get_bulk_delete_threshold(), $age_threshold );
	}

	public static function query_get( $item, $offset = 0, $age_threshold = 0 ) {
		$functions = apply_filters( 'dbclnr_get_queries', [] );
		if ( !isset( $functions[$item] ) ) {
			throw new Error( "Get query for $item not found." );
		}
		return $functions[$item]( $offset, self::$GET_LIMIT, $age_threshold );
	}

	public static function query_generate_fake_data( $item, $age_threshold = 0 ) {
		$functions = apply_filters( 'dbclnr_generate_fake_data_queries', [] );
		if ( !isset( $functions[$item] ) ) {
			throw new Error( "Generate fake data query for $item not found." );
		}
		return $functions[$item]( $age_threshold );
	}

	public static function generate_fake_post_type( $age_threshold = 0 ) {
		$query = new Meow_DBCLNR_Queries_Core();
		$query->generate_fake_post( $age_threshold );
	}

	public static function is_deep_deletions_enabled()
	{
		$options = get_option( 'dbclnr_options', null );
		return ( $options['deep_deletions'] ?? false ) && class_exists( 'MeowPro_DBCLNR_Queries' );
	}

	public static function get_bulk_delete_threshold()
	{
		$options = get_option( 'dbclnr_options', null );
		return $options['bulk_batch_size'] ?? 100;
	}
}
