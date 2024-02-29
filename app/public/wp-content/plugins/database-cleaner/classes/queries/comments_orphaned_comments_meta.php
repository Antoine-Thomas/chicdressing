<?php

class Meow_DBCLNR_Queries_Comments_Orphaned_Comments_Meta extends Meow_DBCLNR_Queries_Core
{
    public function generate_fake_data_query($age_threshold = 0)
    {
        $post_id = $this->generate_fake_post( $age_threshold );
        $comment_id = $this->generate_fake_comment( $age_threshold, $post_id );
        if ( !$comment_id ) {
            return;
        }

        add_comment_meta( $comment_id, $this->fake_data_comment_metakey, $this->fake_data_metavalue );

        global $wpdb;
        $wpdb->query( $wpdb->prepare(
            "
			DELETE FROM $wpdb->comments
			WHERE comment_ID = %s
			",
            $comment_id
        ) );
    }

    public function count_query($age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_var(
            "
			SELECT COUNT(cm.comment_id)
			FROM $wpdb->commentmeta cm
			LEFT JOIN $wpdb->comments c ON c.comment_ID = cm.comment_id
			WHERE c.comment_ID IS NULL;
			"
        );
        return $result;
    }

    public function delete_query($deep_deletions_enabled, $limit, $age_threshold = 0)
    {
        if ($deep_deletions_enabled) {
            return MeowPro_DBCLNR_Queries::delete_comments_orphaned_comments_meta();
        }

        global $wpdb;
        $count = $this->count_query();
        if ($count === 0) {
            return 0;
        }
        $result = $wpdb->query( $wpdb->prepare(
            "
			DELETE FROM $wpdb->commentmeta
			WHERE meta_id IN (
				SELECT *
				FROM (
					SELECT t.meta_id
					FROM $wpdb->commentmeta t
					LEFT JOIN $wpdb->comments c ON c.comment_ID = t.comment_id
					WHERE c.comment_ID IS NULL
					LIMIT %d
				) x
			)
			",
            $limit
        ) );
        if ($result === false) {
            throw new Error('Failed to delete the orphaned comments meta. : ' . $wpdb->last_error);
        }
        return $result;
    }

    public function get_query($offset, $limit, $age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_results( $wpdb->prepare(
            "
			SELECT cm.*
			FROM $wpdb->commentmeta cm
			LEFT JOIN $wpdb->comments c ON c.comment_ID = cm.comment_id
			WHERE c.comment_ID IS NULL
			LIMIT %d, %d
			",
            $offset,
            $limit
        ), ARRAY_A );

        return $result;
    }
}
