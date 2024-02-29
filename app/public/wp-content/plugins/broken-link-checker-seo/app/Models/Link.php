<?php
namespace AIOSEO\BrokenLinkChecker\Models;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Core\Database;

/**
 * The Link DB model class.
 *
 * @since 1.0.0
 */
class Link extends Model {
	/**
	 * The name of the table in the database, without the prefix.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $table = 'aioseo_blc_links';

	/**
	 * Fields that should be numeric values.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $numericFields = [ 'id', 'post_id', 'blc_link_status_id' ];

	/**
	 * Fields that are nullable.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $nullFields = [ 'blc_link_status_id' ];

	/**
	 * Fields that are booleans.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $booleanFields = [ 'external' ];

	/**
	 * Appended as an extra column, but not stored in the DB.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $appends = [ 'context' ];

	/**
	 * Returns the Link with the given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $linkId The Link ID.
	 * @return Link         The Link.
	 */
	public static function getById( $linkId ) {
		return aioseoBrokenLinkChecker()->core->db->start( 'aioseo_blc_links' )
			->where( 'id', $linkId )
			->run()
			->model( 'AIOSEO\\BrokenLinkChecker\\Models\\Link' );
	}

	/**
	 * Returns the Links with the given Link Status ID.
	 *
	 * @since 1.1.0
	 *
	 * @param  int   $linkStatusId The Link Status ID.
	 * @return array               The Links.
	 */
	public static function getByLinkStatusId( $linkStatusId ) {
		return aioseoBrokenLinkChecker()->core->db->start( 'aioseo_blc_links' )
			->where( 'blc_link_status_id', $linkStatusId )
			->run()
			->models( 'AIOSEO\\BrokenLinkChecker\\Models\\Link' );
	}

	/**
	 * Deletes all Links for the given post.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $postId The post ID.
	 * @return void
	 */
	public static function deleteLinks( $postId ) {
		aioseoBrokenLinkChecker()->core->db->delete( 'aioseo_blc_links' )
			->where( 'post_id', $postId )
			->run();
	}

	/**
	 * Sanitizes the link object.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $link The link data.
	 * @return array       The sanitized link data.
	 */
	public static function sanitizeLink( $link ) {
		$instance = new self();

		$sanitizedLink = [];
		foreach ( $link as $k => $v ) {
			switch ( $k ) {
				case 'post_id':
				case 'blc_link_status_id':
					if ( null === $v && in_array( $k, $instance->nullFields, true ) ) {
						break;
					}
					$v = intval( $v );
					break;
				case 'external':
					$v = rest_sanitize_boolean( $v );
					break;
				case 'url':
					$v = esc_url( $v );
					break;
				case 'url_hash':
				case 'hostname':
				case 'hostname_hash':
				case 'anchor':
				case 'phrase':
				case 'paragraph':
					$v = sanitize_text_field( $v );
					break;
				case 'phrase_html':
				case 'paragraph_html':
					$v = aioseoBrokenLinkChecker()->helpers->wpKsesPhrase( $v );
					break;
				default:
					break;
			}

			if (
				empty( $v ) &&
				! in_array( $k, $instance->booleanFields, true ) &&
				! in_array( $k, $instance->nullFields, true )
			) {
				return [];
			}

			$sanitizedLink[ $k ] = esc_sql( $v );
		}

		return $sanitizedLink;
	}

	/**
	 * Checks whether the given link object is a valid one in the context of Broken Link Checker.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $link The link data.
	 * @return bool        Whether the link is valid or not.
	 */
	public static function validateLink( $link ) {
		$propsToCheck = [
			'url',
			'hostname',
			'anchor',
			'phrase',
			'phrase_html',
			'paragraph',
			'paragraph_html'
		];

		foreach ( $propsToCheck as $prop ) {
			$value = wp_strip_all_tags( $link[ $prop ] );
			if ( empty( $value ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns link row results based on the given arguments.
	 *
	 * @since 1.1.0
	 *
	 * @param  int    $limit       The limit.
	 * @param  int    $offset      The offset.
	 * @param  string $whereClause The WHERE clause.
	 * @return array               List of Link instances.
	 */
	public static function rowQuery( $linkStatusId, $limit = 5, $offset = 0, $whereClause = '' ) {
		$linkRows = self::baseQuery( $linkStatusId, $whereClause )
			->select( 'al.id, al.post_id, al.external, al.anchor, al.phrase' )
			->limit( $limit, $offset )
			->run()
			->result();

		if ( empty( $linkRows ) ) {
			return [];
		}

		$rowsWithData = [];
		foreach ( $linkRows as $linkRow ) {
			$linkRow->context = [
				'permalink' => get_permalink( $linkRow->post_id ),
				'postTitle' => aioseoBrokenLinkChecker()->helpers->getPostTitle( $linkRow->post_id ),
				'editLink'  => get_edit_post_link( $linkRow->post_id, '' )
			];

			$rowsWithData[] = $linkRow;
		}

		return $rowsWithData;
	}

	/**
	 * Returns link row count based on the given arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param  int    $linkStatusId The link status ID.
	 * @param  string $whereClause  The WHERE clause.
	 * @return int                  The row count.
	 */
	public static function rowQueryCount( $linkStatusId, $whereClause = '' ) {
		$query = self::baseQuery( $linkStatusId, $whereClause )
			->count();

		return $query;
	}

	/**
	 * Returns the base query for the rowQuery() and rowCountQuery() methods.
	 *
	 * @since 1.0.0
	 *
	 * @param  int      $linkStatusId The link status ID.
	 * @param  string   $whereClause  The WHERE clause.
	 * @return Database               The query.
	 */
	public static function baseQuery( $linkStatusId, $whereClause = '' ) {
		$query = aioseoBrokenLinkChecker()->core->db->start( 'aioseo_blc_links as al' )
			->join( 'posts as p', 'p.ID = al.post_id', 'RIGHT' )
			->where( 'al.blc_link_status_id', $linkStatusId );

		if ( ! empty( $whereClause ) ) {
			$query->whereRaw( $whereClause );
		}

		$excludedDomains = aioseoBrokenLinkChecker()->helpers->getExcludedDomains();
		if ( ! empty( $excludedDomains ) ) {
			$query->whereNotIn( 'al.hostname', $excludedDomains );
		}

		return $query;
	}

	/**
	 * Get a WHERE clause for the Broken Links report search term.
	 *
	 * @since   1.0.0
	 * @version 1.1.0 Moved from Vue.php to Link model.
	 *
	 * @param  string $searchTerm The search term.
	 * @return string             The search where clause.
	 */
	public static function getLinkWhereClause( $searchTerm ) {
		if ( ! $searchTerm || 'null' === $searchTerm ) {
			return '';
		}

		$searchTerm = esc_sql( $searchTerm );
		if ( ! $searchTerm ) {
			return '';
		}

		$where = '';
		if ( intval( $searchTerm ) ) {
			$where .= '
				p.ID = ' . (int) $searchTerm . ' OR
			';
		}

		$where .= "
			al.url LIKE '%" . $searchTerm . "%' OR
			al.anchor LIKE '%" . $searchTerm . "%' OR
			p.post_title LIKE '%" . $searchTerm . "%' OR
			p.post_name LIKE '%" . $searchTerm . "%'
		";

		return "( $where )";
	}
}