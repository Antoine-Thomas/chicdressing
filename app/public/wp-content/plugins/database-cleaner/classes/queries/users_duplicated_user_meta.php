<?php

class Meow_DBCLNR_Queries_Users_Duplicated_User_Meta extends Meow_DBCLNR_Queries_Core
{
    public function generate_fake_data_query($age_threshold = 0)
    {
        $user_id = null;
        $user = get_user_by( 'slug', $this->fake_data_user_slug );
        if ( !$user ) {
            $result = register_new_user( $this->fake_data_user_slug, 'dbclnr-fake-user@example.com' );
            if ( is_wp_error( $result ) ) {
                throw new Error( $result->get_error_message() );
            }
            $user_id = $result;
        } else {
            $user_id = $user->ID;
        }

        add_user_meta( $user_id, $this->fake_data_user_metakey, $this->fake_data_metavalue );
        add_user_meta( $user_id, $this->fake_data_user_metakey, $this->fake_data_metavalue );
    }

    public function count_query($age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_var(
            "
			SELECT COUNT(um1.umeta_id)
			FROM $wpdb->usermeta um1
			WHERE um1.umeta_id NOT IN(
				SELECT *
				FROM (
					SELECT MAX(um2.umeta_id)
					FROM $wpdb->usermeta um2
					GROUP BY um2.user_id, um2.meta_key
				) x
			);
			"
        );
        return $result;
    }

    public function delete_query($deep_deletions_enabled, $limit, $age_threshold = 0)
    {
        if ($deep_deletions_enabled) {
            return MeowPro_DBCLNR_Queries::delete_users_duplicated_user_meta();
        }

        global $wpdb;
        $count = $this->count_query();
        if ($count === 0) {
            return 0;
        }
        $result = $wpdb->query( $wpdb->prepare(
            "
			DELETE FROM $wpdb->usermeta
			WHERE umeta_id NOT IN(
				SELECT *
				FROM (
					SELECT MAX(um2.umeta_id)
					FROM $wpdb->usermeta um2
					GROUP BY um2.user_id, um2.meta_key
				) x
			)
			LIMIT %d
			",
			$limit
        ) );
        if ($result === false) {
            throw new Error('Failed to delete the duplicated user meta. : ' . $wpdb->last_error);
        }
        return $result;
    }

    public function get_query($offset, $limit, $age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_results( $wpdb->prepare(
            "
			SELECT um1.*
			FROM $wpdb->usermeta um1
			WHERE um1.umeta_id NOT IN(
				SELECT *
				FROM (
					SELECT MAX(um2.umeta_id)
					FROM $wpdb->usermeta um2
					GROUP BY um2.user_id, um2.meta_key
				) x
			)
			LIMIT %d, %d
			",
			$offset, $limit
        ), ARRAY_A );

        return $result;
    }
}
