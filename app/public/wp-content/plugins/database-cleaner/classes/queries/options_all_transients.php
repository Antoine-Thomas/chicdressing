<?php

class Meow_DBCLNR_Queries_Options_All_Transients extends Meow_DBCLNR_Queries_Core
{
    public function generate_fake_data_query($age_threshold = 0)
    {
        set_transient( 'dbclnr_fake', 'dbclnr_fake_transient_data' );
    }

    public function count_query($age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_var(
            "
			SELECT COUNT(option_id)
			FROM $wpdb->options
			WHERE option_name LIKE '_transient_%';
			"
        );
        return $result;
    }

    public function delete_query($deep_deletions_enabled, $limit, $age_threshold = 0)
    {
        if ($deep_deletions_enabled) {
            return MeowPro_DBCLNR_Queries::delete_options_all_transients();
        }

        global $wpdb;
        $count = $this->count_query();
        if ($count === 0) {
            return 0;
        }
        $result = $wpdb->query( $wpdb->prepare(
            "
			DELETE FROM $wpdb->options
			WHERE option_name LIKE '_transient_%'
			LIMIT %d
			",
            $limit
        ) );
        if ($result === false) {
            throw new Error('Failed to delete all transients. : ' . $wpdb->last_error);
        }
        return $result;
    }

    public function get_query($offset, $limit, $age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_results( $wpdb->prepare(
            "
			SELECT *
			FROM $wpdb->options
			WHERE option_name LIKE '_transient_%'
			LIMIT %d, %d
			",
            $offset,
            $limit
        ), ARRAY_A );

        return $result;
    }
}
