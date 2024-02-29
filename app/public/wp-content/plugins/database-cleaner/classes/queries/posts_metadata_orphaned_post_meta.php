<?php

class Meow_DBCLNR_Queries_Posts_Metadata_Orphaned_Post_Meta extends Meow_DBCLNR_Queries_Core
{
    public function generate_fake_data_query($age_threshold = 0)
    {
        $id = $this->generate_fake_post( $age_threshold );
        add_post_meta( $id, $this->fake_data_post_metakey, $this->fake_data_metavalue );

        global $wpdb;
        $wpdb->query( $wpdb->prepare(
            "
			DELETE FROM $wpdb->posts
			WHERE ID = %s
			",
			$id
        ) );
    }

    public function count_query($age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_var(
            "
			SELECT COUNT(pm.meta_id)
			FROM $wpdb->postmeta pm
			LEFT JOIN $wpdb->posts wp ON wp.ID = pm.post_id
			WHERE wp.ID IS NULL;
			"
        );
        return $result;
    }

    public function delete_query($deep_deletions_enabled, $limit, $age_threshold = 0)
    {
        if ($deep_deletions_enabled) {
            return MeowPro_DBCLNR_Queries::delete_posts_metadata_orphaned_post_meta();
        }

        global $wpdb;
        $count = $this->count_query();
        if ($count === 0) {
            return 0;
        }
        $result = $wpdb->query( $wpdb->prepare(
            "
			DELETE FROM $wpdb->postmeta
			WHERE meta_id IN (
				SELECT *
				FROM (
					SELECT t.meta_id
					FROM $wpdb->postmeta t
					LEFT JOIN $wpdb->posts wp ON wp.ID = t.post_id
					WHERE wp.ID IS NULL
					LIMIT %d
				) x
			)
			",
			$limit
        ) );
        if ($result === false) {
            throw new Error('Failed to delete the orphaned post meta. : ' . $wpdb->last_error);
        }
        return $result;
    }

    public function get_query($offset, $limit, $age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_results( $wpdb->prepare(
            "
			SELECT pm.*
			FROM $wpdb->postmeta pm
			LEFT JOIN $wpdb->posts wp ON wp.ID = pm.post_id
			WHERE wp.ID IS NULL
			LIMIT %d, %d
			",
			$offset, $limit
        ), ARRAY_A );

        return $result;
    }
}
