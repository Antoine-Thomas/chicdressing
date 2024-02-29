<?php

class Meow_DBCLNR_Queries_Comments_Duplicated_Comments_Meta extends Meow_DBCLNR_Queries_Core
{
    public function generate_fake_data_query($age_threshold = 0)
    {
        $post_id = $this->generate_fake_post( $age_threshold );
        $comment_id = $this->generate_fake_comment( $age_threshold, $post_id );
        if ( !$comment_id ) {
            return;
        }

        add_comment_meta( $comment_id, $this->fake_data_comment_metakey, $this->fake_data_metavalue );
        add_comment_meta( $comment_id, $this->fake_data_comment_metakey, $this->fake_data_metavalue );
    }

    public function count_query($age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_var(
            "
			SELECT COUNT(cm1.meta_id)
			FROM $wpdb->commentmeta cm1
			WHERE cm1.meta_id NOT IN(
				SELECT *
				FROM (
					SELECT MAX(cm2.meta_id)
					FROM $wpdb->commentmeta cm2
					GROUP BY cm2.comment_id, cm2.meta_key
				) x
			);
			"
        );
        return $result;
    }

    public function delete_query($deep_deletions_enabled, $limit, $age_threshold = 0)
    {
        if ($deep_deletions_enabled) {
            return MeowPro_DBCLNR_Queries::delete_comments_duplicated_comments_meta();
        }

        global $wpdb;
        $count = $this->count_query();
        if ($count === 0) {
            return 0;
        }
        $result = $wpdb->query( $wpdb->prepare(
            "
			DELETE FROM $wpdb->commentmeta
			WHERE meta_id NOT IN(
				SELECT *
				FROM (
					SELECT MAX(cm2.meta_id)
					FROM $wpdb->commentmeta cm2
					GROUP BY cm2.comment_id, cm2.meta_key
				) x
			)
			LIMIT %d
			",
            $limit
        ) );
        if ($result === false) {
            throw new Error('Failed to delete the duplicated comments meta. : ' . $wpdb->last_error);
        }
        return $result;
    }

    public function get_query($offset, $limit, $age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_results( $wpdb->prepare(
            "
			SELECT cm1.*
			FROM $wpdb->commentmeta cm1
			WHERE cm1.meta_id NOT IN(
				SELECT *
				FROM (
					SELECT MAX(cm2.meta_id)
					FROM $wpdb->commentmeta cm2
					GROUP BY cm2.comment_id, cm2.meta_key
				) x
			)
			LIMIT %d, %d
			",
            $offset,
            $limit
        ), ARRAY_A );

        return $result;
    }
}
