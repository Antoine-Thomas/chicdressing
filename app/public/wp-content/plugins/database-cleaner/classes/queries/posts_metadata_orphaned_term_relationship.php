<?php

class Meow_DBCLNR_Queries_Posts_Metadata_Orphaned_Term_Relationship extends Meow_DBCLNR_Queries_Core
{
    public function generate_fake_data_query($age_threshold = 0)
    {
        $post_id = $this->generate_fake_post( $age_threshold );
        $term = get_term_by( 'name', $this->fake_data_term_name, $this->fake_data_taxonomy, ARRAY_A );
        if ( !$term ) {
            $term = wp_insert_term( $this->fake_data_term_name, $this->fake_data_taxonomy );
        }
        $result = wp_update_post( [
            'ID' => $post_id,
            'tax_input' => [
                $this->fake_data_taxonomy => [ $this->fake_data_term_name ],
            ],
        ], true );
        if ( is_wp_error( $result ) ) {
            throw new Error( $result->get_error_message() );
        }

        global $wpdb;
        $wpdb->query( $wpdb->prepare(
            "
			DELETE FROM $wpdb->terms
			WHERE term_id = %s
			",
			$term['term_id']
        ) );
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
			SELECT COUNT(tr.term_taxonomy_id)
			FROM $wpdb->term_relationships tr
			LEFT JOIN $wpdb->term_taxonomy p ON tr.term_taxonomy_id = p.term_taxonomy_id
			WHERE p.term_taxonomy_id IS NULL;
			"
        );
        return $result;
    }

    public function delete_query($deep_deletions_enabled, $limit, $age_threshold = 0)
    {
        if ($deep_deletions_enabled) {
            return MeowPro_DBCLNR_Queries::delete_posts_metadata_orphaned_term_relationship();
        }

        global $wpdb;
        $count = $this->count_query();
        if ($count === 0) {
            return 0;
        }
        $result = $wpdb->query( $wpdb->prepare(
            "
			DELETE FROM $wpdb->term_relationships
			WHERE object_id IN (
				SELECT *
				FROM (
					SELECT t.object_id
					FROM $wpdb->term_relationships t
					LEFT JOIN $wpdb->term_taxonomy p ON t.term_taxonomy_id = p.term_taxonomy_id
					WHERE p.term_taxonomy_id IS NULL
					LIMIT %d
				) x
			)
			",
			$limit
        ) );
        if ($result === false) {
            throw new Error('Failed to delete the orphaned term relationship. : ' . $wpdb->last_error);
        }
        return $result;
    }

    public function get_query($offset, $limit, $age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_results( $wpdb->prepare(
            "
			SELECT tr.*
			FROM $wpdb->term_relationships tr
			LEFT JOIN $wpdb->term_taxonomy p ON tr.term_taxonomy_id = p.term_taxonomy_id
			WHERE p.term_taxonomy_id IS NULL
			LIMIT %d, %d
			",
			$offset, $limit
        ), ARRAY_A );

        return $result;
    }
}
