<?php

class Meow_DBCLNR_Queries_Posts_Metadata_Duplicated_Post_Meta extends Meow_DBCLNR_Queries_Core
{
  public function generate_fake_data_query($age_threshold = 0)
  {
    $id = $this->generate_fake_post($age_threshold);
    add_post_meta($id, $this->fake_data_post_metakey, $this->fake_data_metavalue);
    add_post_meta($id, $this->fake_data_post_metakey, $this->fake_data_metavalue);
    add_post_meta($id, $this->fake_data_post_metakey . "_bis", $this->fake_data_metavalue);
    add_post_meta($id, $this->fake_data_post_metakey . "_bis", $this->fake_data_metavalue);
  }

  public function count_query($age_threshold = 0)
  {
    global $wpdb;

    $ignored_ids = $this->get_ignored_meta_ids();

    // Construct NOT IN clause if there are ignored_ids
    $not_in_clause = "";
    if (!empty($ignored_ids)) {
      $not_in = implode(', ', array_fill(0, count($ignored_ids), '%d'));
      $not_in_clause = "AND t1.meta_id NOT IN ($not_in)";
    }

    $sql = "
      SELECT COUNT(t1.meta_id) 
      FROM $wpdb->postmeta t1 
      INNER JOIN $wpdb->postmeta t2  
      WHERE t1.meta_id < t2.meta_id 
      AND t1.meta_key = t2.meta_key 
      AND t1.post_id = t2.post_id
      $not_in_clause
      ";

    if (!empty($ignored_ids)) {
      $sql = $wpdb->prepare($sql, $ignored_ids);
    }

    return $wpdb->get_var($sql);
  }


  // This is the query used after September 13, 2023. It's faster.
//   public function count_query($age_threshold = 0)
// {
//     global $wpdb;

//     $ignored_ids = $this->get_ignored_meta_ids();
//     $not_in_clause = "";

//     if (!empty($ignored_ids)) {
//         $not_in = implode(', ', array_fill(0, count($ignored_ids), '%d'));
//         $not_in_clause = "AND meta_id NOT IN ($not_in)";
//     }

//     // Step 1: Get potential candidates
//     $sql_candidates = "
//         SELECT post_id, meta_key
//         FROM $wpdb->postmeta
//         WHERE 1=1 $not_in_clause
//         GROUP BY post_id, meta_key
//         HAVING COUNT(meta_id) > 1
//     ";
//     if (!empty($ignored_ids)) {
//         $sql_candidates = $wpdb->prepare($sql_candidates, $ignored_ids);
//     }

//     $candidates = $wpdb->get_results($sql_candidates);
//     $count = 0;

//     // Step 2: Process in batches (pseudo batching, not fetching data in batches)
//     foreach ($candidates as $candidate) {
//         $sql_check = "
//             SELECT COUNT(*)
//             FROM (
//                 SELECT meta_id
//                 FROM $wpdb->postmeta
//                 WHERE post_id = %d AND meta_key = %s $not_in_clause
//                 LIMIT 2
//             ) AS derived_table
//         ";

//         $sql_check = $wpdb->prepare($sql_check, $candidate->post_id, $candidate->meta_key, ...$ignored_ids);
//         $result = $wpdb->get_var($sql_check);

//         if ($result > 1) {
//             $count++;
//         }
//     }

//     return $count;
// }


  // Add the $ids to the dbclnr_ignored_postmeta_ids.txt file. Each ID on a new line.
  public function ignore_meta_id($ids)
  {
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/dbclnr_ignored_postmeta_ids.txt';
    $file = fopen($file_path, 'a');
    foreach ($ids as $id) {
      fwrite($file, $id . "\n");
    }
    fclose($file);
  }

  public function get_ignored_meta_ids()
  {
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/dbclnr_ignored_postmeta_ids.txt';
    if (!file_exists($file_path)) {
      return [];
    }
    $file = fopen($file_path, 'r');
    $ids = [];
    while (!feof($file)) {
      $id = fgets($file);
      if ($id !== false) {
        $ids[] = intval($id);
      }
    }
    fclose($file);
    return $ids;
  }

  public function delete_query($deep_deletions_enabled, $limit, $age_threshold = 0)
  {
    global $wpdb;
    $ignored_meta_ids = [];
    $count = $this->count_query();
    if ($count === 0) {
      return 0;
    }

    $potential_duplicated_postmeta = $wpdb->get_results("
            SELECT
                t1.*
            FROM
                $wpdb->postmeta t1
            INNER JOIN (
                SELECT DISTINCT
                    t1.post_id,
                    t1.meta_key
                FROM
                    $wpdb->postmeta t1
                    INNER JOIN $wpdb->postmeta t2
                WHERE
                    t1.meta_id < t2.meta_id
                    AND t1.meta_key = t2.meta_key
                    AND t1.post_id = t2.post_id
            ) d ON t1.post_id = d.post_id AND t1.meta_key = d.meta_key;
        ", OBJECT);

    $grouped = array_reduce($potential_duplicated_postmeta, function ($result, $item) {
      $key = $item->post_id . '-' . $item->meta_key;
      if (!isset($result[$key])) {
        $result[$key] = [];
      }
      $result[$key][] = $item;
      return $result;
    }, []);

    $meta_ids = [];
    foreach ($grouped as $group) {
      $meta_values = array_map(function ($item) {
        return $item->meta_value;
      }, $group);
      $value_counts = array_count_values($meta_values);

      $duplicates = array_filter($group, function ($item) use ($value_counts) {
        return $value_counts[$item->meta_value] > 1;
      });

      // Left the latest record and retrieved the else meta_ids.
      $target_duplicates = array_slice($duplicates, 0, -1);
      $new_meta_ids = array_map(function ($item) {
        return $item->meta_id;
      }, $target_duplicates);

      if (count($new_meta_ids) === 0) {
        $meta_id = $group[0]->meta_id;
        $ignored_meta_ids[] = $meta_id;
        continue;
      } else {
        $meta_ids = array_merge($meta_ids, $new_meta_ids);
      }
    }

    if (count($ignored_meta_ids) > 0) {
      $this->ignore_meta_id($ignored_meta_ids);
    }

    if (count($meta_ids) === 0) {
      return 0;
    }

    if ($deep_deletions_enabled) {
      return MeowPro_DBCLNR_Queries::delete_posts_metadata_duplicated_post_meta($meta_ids);
    }

    $placeholder = implode(', ', array_fill(0, count($meta_ids), '%d'));
    $result = $wpdb->query($wpdb->prepare(
      "
			DELETE FROM $wpdb->postmeta
			WHERE meta_id IN ($placeholder)
            LIMIT %d
			",
      array_merge($meta_ids, [$limit])
    ));
    if ($result === false) {
      throw new Error('Failed to delete the duplicated post meta. : ' . $wpdb->last_error);
    }
    return $result;
  }

  public function get_query($offset, $limit, $age_threshold = 0)
  {
    global $wpdb;
    $result = $wpdb->get_results($wpdb->prepare(
      "
			SELECT t1.*
			FROM $wpdb->postmeta t1
			INNER JOIN $wpdb->postmeta t2
			WHERE  t1.meta_id < t2.meta_id
			AND  t1.meta_key = t2.meta_key
			AND t1.post_id = t2.post_id
			LIMIT %d, %d
			",
      $offset,
      $limit
    ), ARRAY_A);

    return $result;
  }
}
