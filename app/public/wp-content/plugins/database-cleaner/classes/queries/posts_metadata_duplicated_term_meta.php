<?php

class Meow_DBCLNR_Queries_Posts_Metadata_Duplicated_Term_Meta extends Meow_DBCLNR_Queries_Core
{
    public function generate_fake_data_query($age_threshold = 0)
    {
		$term = get_term_by( 'name', $this->fake_data_term_name, $this->fake_data_taxonomy, ARRAY_A );
        if ( !$term ) {
            $term = wp_insert_term( $this->fake_data_term_name, $this->fake_data_taxonomy );
        }
        add_term_meta( $term['term_id'], $this->fake_data_term_metakey, $this->fake_data_metavalue );
        add_term_meta( $term['term_id'], $this->fake_data_term_metakey, $this->fake_data_metavalue );
    }

    public function count_query($age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_var(
            "
			SELECT COUNT(tm1.meta_id)
			FROM $wpdb->termmeta tm1
			WHERE tm1.meta_id NOT IN(
				SELECT *
				FROM (
					SELECT MAX(tm2.meta_id)
					FROM $wpdb->termmeta tm2
					GROUP BY tm2.term_id, tm2.meta_key
				) x
			);
			"
        );
        return $result;
    }

    public function delete_query($deep_deletions_enabled, $limit, $age_threshold = 0)
    {
        if ($deep_deletions_enabled) {
            return MeowPro_DBCLNR_Queries::delete_posts_metadata_duplicated_term_meta();
        }

        global $wpdb;
        $count = $this->count_query();
        if ($count === 0) {
            return 0;
        }
        $result = $wpdb->query( $wpdb->prepare(
            "
			DELETE FROM $wpdb->termmeta
			WHERE meta_id IN(
				SELECT *
				FROM (
					SELECT t1.meta_id
					FROM $wpdb->termmeta t1
					INNER JOIN $wpdb->termmeta t2
					WHERE  t1.meta_id < t2.meta_id
					AND  t1.meta_key = t2.meta_key
					AND t1.term_id = t2.term_id
					LIMIT %d
				) x
			)
			",
			$limit
        ) );
        if ($result === false) {
            throw new Error('Failed to delete the duplicated term meta. : ' . $wpdb->last_error);
        }
        return $result;
    }

    public function get_query($offset, $limit, $age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_results( $wpdb->prepare(
            "
			SELECT tm.*
			FROM $wpdb->termmeta tm1
			WHERE tm1.meta_id NOT IN(
				SELECT *
				FROM (
					SELECT MAX(tm2.meta_id)
					FROM $wpdb->termmeta tm2
					GROUP BY tm2.term_id, tm2.meta_key
				) x
			)
			LIMIT %d, %d
			",
			$offset, $limit
        ), ARRAY_A );

        return $result;
    }
}
