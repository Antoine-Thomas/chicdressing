<?php
namespace AIOSEO\BrokenLinkChecker\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Models;

/**
 * Handles all common table action handlers.
 *
 * @since 1.1.0
 */
abstract class CommonTableActions {
	/**
	 * Unlinks the given link.
	 *
	 * @since 1.1.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function unlink( $request ) {
		$body   = $request->get_json_params();
		$linkStatusId = ! empty( $body['linkStatusId'] ) ? intval( $body['linkStatusId'] ) : null;
		$linkId       = ! empty( $body['linkId'] ) ? intval( $body['linkId'] ) : null;
		if ( empty( $linkStatusId ) && empty( $linkId ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'No link status ID or link ID given.'
			], 400 );
		}

		if ( ! empty( $linkStatusId ) ) {
			$links = Models\Link::getByLinkStatusId( $linkStatusId );
			foreach ( $links as $link ) {
				self::removeLink( $link->id );
			}

			return new \WP_REST_Response( [
				'success' => true
			], 200 );
		}

		$success = self::removeLink( $linkId );
		if ( empty( $success ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'Link could not be removed.'
			], 400 );
		}

		return new \WP_REST_Response( [
			'success' => true
		], 200 );
	}

	/**
	 * Rechecks the given links.
	 *
	 * @since   1.0.0
	 * @version 1.1.0 Moved from BrokenLinks to TableActions and add support for bulk-checking rows.
	 *
	 * @param  array       $linkStatusRows The Link Status rows.
	 * @return object|bool                 The response or false if the links could not be checked.
	 */
	protected static function recheckLinks( $linkStatusRows ) {
		$linkStatusIds = array_map( function( $linkStatusRow ) {
			return $linkStatusRow['id'];
		}, $linkStatusRows );

		$linkStatuses = Models\LinkStatus::getByIds( $linkStatusIds );
		if ( empty( $linkStatuses ) ) {
			return false;
		}

		$rows = [];
		foreach ( $linkStatuses as $linkStatus ) {
			$rows[ $linkStatus->id ] = $linkStatus->url;
		}

		$requestBody = array_merge(
			aioseoBrokenLinkChecker()->main->linkStatus->data->getBaseData(),
			[ 'rows' => $rows ]
		);

		$response     = aioseoBrokenLinkChecker()->main->linkStatus->doPostRequest( 'recheck-bulk', $requestBody );
		$responseCode = (int) wp_remote_retrieve_response_code( $response );
		$responseBody = json_decode( wp_remote_retrieve_body( $response ) );
		if ( is_wp_error( $response ) && 200 !== $responseCode || empty( $responseBody->success ) || empty( $responseBody->rows ) ) {
			return false;
		}

		foreach ( $responseBody->rows as $row ) {
			// Parse the data into a useable format and then save the updated results.
			aioseoBrokenLinkChecker()->main->linkStatus->parseResultsHelper( $row );
		}

		return $responseBody;
	}

	/**
	 * Updates a given link with a new anchor and/or URL.
	 *
	 * @since 1.1.0
	 *
	 * @param  int    $linkId    The Link ID.
	 * @param  string $newAnchor The new anchor.
	 * @param  string $newUrl    The new URL.
	 * @return bool              Whether the Link was updated.
	 */
	protected static function updateLink( $linkId, $newAnchor = '', $newUrl = '' ) {
		if ( empty( $newAnchor ) && empty( $newUrl ) ) {
			return false;
		}

		$link = Models\Link::getById( $linkId );
		if ( ! $link->exists() ) {
			return false;
		}

		$post = get_post( $link->post_id );
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return false;
		}

		// First, update the link in the phrase.
		$oldAnchor     = aioseoBrokenLinkChecker()->helpers->escapeRegex( $link->anchor );
		$oldUrl        = aioseoBrokenLinkChecker()->helpers->escapeRegex( $link->url );
		$escapedAnchor = aioseoBrokenLinkChecker()->helpers->escapeRegexReplacement( $newAnchor ? $newAnchor : $link->anchor );
		$escapedUrl    = aioseoBrokenLinkChecker()->helpers->escapeRegexReplacement( $newUrl ? $newUrl : $link->url );

		$newPhraseHtml = preg_replace( "/(<a.*?href=\")({$oldUrl})(\".*?>)({$oldAnchor})(<\/a>)/i", "$1{$escapedUrl}$3{$escapedAnchor}$5", $link->phrase_html );

		// Then, replace the phrase in the post content.
		$postContent   = str_replace( '&nbsp;', ' ', $post->post_content );
		$oldPhraseHtml = aioseoBrokenLinkChecker()->helpers->escapeRegex( $link->phrase_html );
		$pattern       = "/$oldPhraseHtml/i";

		$postContent = preg_replace( $pattern, $newPhraseHtml, $postContent );

		// Confirm that the old phrase is no longer there.
		if ( preg_match( $pattern, $postContent ) ) {
			return false;
		}

		// Reset modified date when the post is updated if the option is enabled.
		$limitModifiedDate = aioseoBrokenLinkChecker()->options->general->linkTweaks->limitModifiedDate;
		if ( $limitModifiedDate ) {
			add_filter( 'wp_insert_post_data', function ( $data ) use ( $post ) {
				$data['post_modified']     = $post->post_modified;
				$data['post_modified_gmt'] = $post->post_modified_gmt;

				return $data;
			}, 99999, 1 );
		}

		aioseoBrokenLinkChecker()->main->links->postsToRescan[] = $link->post_id;

		// Now, update the post with the modified post content.
		$error = wp_update_post( [
			'ID'           => $link->post_id,
			'post_content' => $postContent,
		], true );

		if ( 0 === $error || is_a( $error, 'WP_Error' ) ) {
			return false;
		}

		$linkStatus = Models\LinkStatus::getByUrl( $newUrl );
		if ( ! $linkStatus->exists() ) {
			$linkStatusId = aioseoBrokenLinkChecker()->core->db->insert( 'aioseo_blc_link_status' )
				->set( [
					'url'      => $newUrl,
					'url_hash' => sha1( $newUrl ),
					'created'  => aioseoBrokenLinkChecker()->helpers->timeToMysql( time() ),
					'updated'  => aioseoBrokenLinkChecker()->helpers->timeToMysql( time() )
				] )
				->run()
				->insertId();

			if ( $linkStatusId ) {
				$link->blc_link_status_id = $linkStatusId;
			}
		}

		// The "save_post" callback will trigger a rescan of the post, so we can delete the existing Link record.
		$link->delete();

		return true;
	}

	/**
	 * Removes a given link.
	 *
	 * @since   1.0.0
	 * @version 1.1.0 Moved from BrokenLinks to TableActions.
	 *
	 * @param  int  $linkId The Link ID.
	 * @return bool         Whether the Link was unlinked.
	 */
	protected static function removeLink( $linkId ) {
		$link = Models\Link::getById( $linkId );
		if ( ! $link->exists() ) {
			return false;
		}

		$post = get_post( $link->post_id );
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return false;
		}

		// First, remove the link in the phrase.
		$anchor        = $link->anchor;
		$newPhraseHtml = preg_replace( "/<a.*?>({$anchor})<\/a>/", '$1', $link->phrase_html );

		// Then, replace the phrase in the post content.
		$postContent   = str_replace( '&nbsp;', ' ', $post->post_content );
		$oldPhraseHtml = aioseoBrokenLinkChecker()->helpers->escapeRegex( $link->phrase_html );
		$pattern       = "/$oldPhraseHtml/i";

		$postContent = preg_replace( $pattern, $newPhraseHtml, $postContent );

		// Confirm that the old phrase is no longer there.
		if ( preg_match( $pattern, $postContent ) ) {
			return false;
		}

		aioseoBrokenLinkChecker()->main->links->postsToRescan[] = $link->post_id;

		// Now, update the post with the modified post content.
		$error = wp_update_post( [
			'ID'           => $link->post_id,
			'post_content' => $postContent
		], true );

		if ( 0 === $error || is_a( $error, 'WP_Error' ) ) {
			return false;
		}

		$link->delete();

		return true;
	}
}