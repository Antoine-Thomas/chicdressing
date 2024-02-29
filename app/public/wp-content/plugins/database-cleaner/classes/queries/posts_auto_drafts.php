<?php

class Meow_DBCLNR_Queries_Posts_Auto_Drafts extends Meow_DBCLNR_Queries_Core
{
    public function generate_fake_data_query( $age_threshold = 0 )
    {
        $this->generate_fake_post( $age_threshold, 'auto-draft' );
    }

    public function count_query($age_threshold = 0)
    {
        $week_ago = new DateTime('-' . $age_threshold);
        global $wpdb;
        $result = $wpdb->get_var( $wpdb->prepare(
			"
			SELECT COUNT(ID) 
			FROM   $wpdb->posts 
			WHERE  post_modified < %s 
			AND post_status = 'auto-draft'
			",
			$week_ago->format('Y-m-d H:i:s')
		) );
        return $result;
    }

    public function delete_query($deep_deletions_enabled, $limit, $age_threshold = 0)
    {
        if ($deep_deletions_enabled) {
            return MeowPro_DBCLNR_Queries::delete_posts_auto_drafts( $age_threshold );
        }

        $week_ago = new DateTime('-' . $age_threshold);
        global $wpdb;
        $count = $this->count_query();
        if ($count === 0) {
            return 0;
        }
        $result = $wpdb->query( $wpdb->prepare(
            "
			DELETE FROM $wpdb->posts
			WHERE post_modified < %s
			AND post_status = 'auto-draft'
			LIMIT %d
			",
			$week_ago->format('Y-m-d H:i:s'), $limit
        ) );
        if ($result === false) {
            throw new Error('Failed to delete the post. : ' . $wpdb->last_error);
        }
        return $result;
    }

    public function get_query($offset, $limit, $age_threshold = 0)
    {
        $week_ago = new DateTime('-' . $age_threshold);
        global $wpdb;
        $result = $wpdb->get_results( $wpdb->prepare(
            "
			SELECT *
			FROM   $wpdb->posts
			WHERE  post_modified < %s
			AND post_status = 'auto-draft'
			LIMIT %d, %d
			",
			$week_ago->format('Y-m-d H:i:s'), $offset, $limit
        ), ARRAY_A );

        return $result;
    }
}
