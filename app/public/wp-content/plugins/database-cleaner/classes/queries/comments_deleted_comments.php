<?php

class Meow_DBCLNR_Queries_Comments_Deleted_Comments extends Meow_DBCLNR_Queries_Core
{
    public function generate_fake_data_query($age_threshold = 0)
    {
        $post_id = $this->generate_fake_post( $age_threshold );
        $this->generate_fake_comment( $age_threshold, $post_id, 'trash' );
    }

    public function count_query($age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_var(
            "SELECT COUNT(comment_ID)
            FROM $wpdb->comments
            WHERE comment_approved = 'trash'
    "
        );
        return $result;
    }

    public function delete_query($deep_deletions_enabled, $limit, $age_threshold = 0)
    {
        if ($deep_deletions_enabled) {
            return MeowPro_DBCLNR_Queries::delete_comments_deleted_comments();
        }

        global $wpdb;
        $count = $this->count_query();
        if ($count === 0) {
            return 0;
        }
        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->comments
			WHERE comment_approved = 'trash'
			LIMIT %d",
            $limit
        ));
        if ($result === false) {
            throw new Error('Failed to delete the deleted comments. : ' . $wpdb->last_error);
        }
        return $result;
    }

    public function get_query($offset, $limit, $age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_results($wpdb->prepare(
            "SELECT *
            FROM $wpdb->comments
            WHERE comment_approved = 'trash'
            LIMIT %d, %d",
            $offset,
            $limit
        ), ARRAY_A);

        return $result;
    }
}
