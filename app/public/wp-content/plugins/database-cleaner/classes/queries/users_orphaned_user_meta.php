<?php

class Meow_DBCLNR_Queries_Users_Orphaned_User_Meta extends Meow_DBCLNR_Queries_Core
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
        $umeta_id = add_user_meta( $user_id, $this->fake_data_user_metakey, $this->fake_data_metavalue );

        global $wpdb;
        $result = $wpdb->query( $wpdb->prepare(
            "
			DELETE FROM $wpdb->usermeta
			WHERE user_id = %s AND umeta_id <> %s
			",
			$user_id, $umeta_id
        ) );
        $result = $wpdb->query( $wpdb->prepare(
            "
			DELETE FROM $wpdb->users
			WHERE ID = %s
			",
			$user_id
        ) );
    }

    public function count_query($age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_var(
            "
			SELECT COUNT(um.umeta_id)
			FROM $wpdb->usermeta um
			LEFT JOIN $wpdb->users u ON u.ID = um.user_id
			WHERE u.ID IS NULL;
			"
        );
        return $result;
    }

    public function delete_query($deep_deletions_enabled, $limit, $age_threshold = 0)
    {
        if ($deep_deletions_enabled) {
            return MeowPro_DBCLNR_Queries::delete_users_orphaned_user_meta();
        }

        global $wpdb;
        $count = $this->count_query();
        if ($count === 0) {
            return 0;
        }
        $result = $wpdb->query( $wpdb->prepare(
            "
			DELETE FROM $wpdb->usermeta
			WHERE umeta_id IN (
				SELECT *
				FROM (
					SELECT t.umeta_id
					FROM $wpdb->usermeta t
					LEFT JOIN $wpdb->users u ON u.ID = t.user_id
					WHERE u.ID IS NULL
					LIMIT %d
				) x
			)
			",
			$limit
        ) );
        if ($result === false) {
            throw new Error('Failed to delete the orphaned user meta. : ' . $wpdb->last_error);
        }
        return $result;
    }

    public function get_query($offset, $limit, $age_threshold = 0)
    {
        global $wpdb;
        $result = $wpdb->get_results( $wpdb->prepare(
            "
			SELECT um.*
			FROM $wpdb->usermeta um
			LEFT JOIN $wpdb->users u ON u.ID = um.user_id
			WHERE u.ID IS NULL
			LIMIT %d, %d
			",
			$offset, $limit
        ), ARRAY_A );

        return $result;
    }
}
