<?php

class Meow_DBCLNR_Queries_Posts_Metadata_Oembed_Caches_In_Post_Meta extends Meow_DBCLNR_Queries_Core
{
    public function generate_fake_data_query($age_threshold = 0)
    {
        $id = $this->generate_fake_post( $age_threshold );
        add_post_meta( $id, '_oembed_' . $this->fake_data_post_metakey, $this->fake_data_metavalue );
    }

    public function count_query($age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_var(
            "
			SELECT COUNT(pm.meta_id)
			FROM $wpdb->postmeta pm
			WHERE pm.meta_key LIKE '_oembed_%';
			"
        );
        return $result;
    }

    public function delete_query($deep_deletions_enabled, $limit, $age_threshold = 0)
    {
        if ($deep_deletions_enabled) {
            return MeowPro_DBCLNR_Queries::delete_posts_metadata_oembed_caches_in_post_meta();
        }

        global $wpdb;
        $count = $this->count_query();
        if ($count === 0) {
            return 0;
        }
        $result = $wpdb->query( $wpdb->prepare(
            "
			DELETE FROM $wpdb->postmeta
			WHERE meta_key LIKE '_oembed_%'
			LIMIT %d
			",
			$limit
        ) );
        if ($result === false) {
            throw new Error('Failed to delete the oembed caches in post meta. : ' . $wpdb->last_error);
        }
        return $result;
    }

    public function get_query($offset, $limit, $age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_results( $wpdb->prepare(
            "
			SELECT *
			FROM $wpdb->postmeta pm
			WHERE pm.meta_key LIKE '_oembed_%'
			LIMIT %d, %d
			",
			$offset, $limit
        ), ARRAY_A );

        return $result;
    }
}
