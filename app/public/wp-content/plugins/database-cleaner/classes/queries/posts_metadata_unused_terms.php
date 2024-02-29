<?php

class Meow_DBCLNR_Queries_Posts_Metadata_Unused_Terms extends Meow_DBCLNR_Queries_Core
{
    public function generate_fake_data_query($age_threshold = 0)
    {
        $term = get_term_by( 'name', $this->fake_data_term_name, $this->fake_data_taxonomy, ARRAY_A );
        if ( !$term ) {
            $term = wp_insert_term( $this->fake_data_term_name, $this->fake_data_taxonomy );
        }

        global $wpdb;
        $wpdb->query( $wpdb->prepare(
            "
			DELETE FROM $wpdb->term_taxonomy
			WHERE term_id = %s
			",
			$term['term_id']
        ) );
    }

    public function count_query($age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_var(
            "
			SELECT COUNT(t.term_id)
			FROM $wpdb->terms t
			LEFT JOIN $wpdb->term_taxonomy tt ON tt.term_id = t.term_id
			WHERE tt.term_taxonomy_id IS NULL;
			"
        );
        return $result;
    }

    public function delete_query($deep_deletions_enabled, $limit, $age_threshold = 0)
    {
        if ($deep_deletions_enabled) {
            return MeowPro_DBCLNR_Queries::delete_posts_metadata_unused_terms();
        }

        global $wpdb;
        $count = $this->count_query();
        if ($count === 0) {
            return 0;
        }
        $result = $wpdb->query( $wpdb->prepare(
            "
			DELETE FROM $wpdb->terms
			WHERE term_id IN (
				SELECT *
				FROM (
					SELECT t1.term_id
					FROM $wpdb->terms t1
					LEFT JOIN $wpdb->term_taxonomy tt ON tt.term_id = t1.term_id
					WHERE tt.term_taxonomy_id IS NULL
					LIMIT %d
				) x
			)
			",
			$limit
        ) );
        if ($result === false) {
            throw new Error('Failed to delete the unused terms. : ' . $wpdb->last_error);
        }
        return $result;
    }

    public function get_query($offset, $limit, $age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_results( $wpdb->prepare(
            "
			SELECT t.*
			FROM $wpdb->terms t
			LEFT JOIN $wpdb->term_taxonomy tt ON tt.term_id = t.term_id
			WHERE tt.term_taxonomy_id IS NULL
			LIMIT %d, %d
			",
			$offset, $limit
        ), ARRAY_A );

        return $result;
    }
}
