<?php
defined( 'ABSPATH' ) || exit;

class MerdusSEO_Link_Checker {

	/** @var string */
	private static $table;

	private static function table(): string {
		global $wpdb;
		if ( ! self::$table ) {
			self::$table = $wpdb->prefix . MerdusSEO_Installer::TABLE_LINKS;
		}
		return self::$table;
	}

	/* ── Full site scan ──────────────────────────────────────────────── */
	public static function scan(): array {
		global $wpdb;

		$post_types = get_option( 'merdusseo_post_types', [ 'post', 'page' ] );
		$timeout    = (int) get_option( 'merdusseo_link_timeout', 10 );
		$site_host  = wp_parse_url( get_site_url(), PHP_URL_HOST );

		$posts = get_posts( [
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		] );

		/* Clear previous results */
		$wpdb->query( 'TRUNCATE TABLE ' . self::table() ); // phpcs:ignore

		$all_links = [];

		foreach ( $posts as $post ) {
			$links = self::extract_links( $post->post_content );

			foreach ( $links as $link ) {
				$url      = $link['url'];
				$parsed   = wp_parse_url( $url );
				$host     = $parsed['host'] ?? '';
				$type     = ( $host === $site_host || empty( $host ) ) ? 'internal' : 'external';
				$absolute = self::to_absolute( $url );

				$status = self::check_url( $absolute, $timeout );
				$broken = ( $status >= 400 || $status === 0 ) ? 1 : 0;

				$row = [
					'source_id'    => $post->ID,
					'source_url'   => get_permalink( $post->ID ),
					'link_url'     => $url,
					'link_type'    => $type,
					'http_status'  => $status,
					'is_broken'    => $broken,
					'anchor_text'  => mb_substr( $link['text'], 0, 512 ),
					'last_checked' => current_time( 'mysql' ),
				];

				$wpdb->insert( self::table(), $row ); // phpcs:ignore
				$row['id'] = $wpdb->insert_id;
				$all_links[] = $row;
			}
		}

		return $all_links;
	}

	/* ── Get stored results ──────────────────────────────────────────── */
	public static function get_results( string $filter = 'all' ): array {
		global $wpdb;

		$where = '';
		if ( $filter === 'broken' )   $where = 'WHERE is_broken = 1';
		if ( $filter === 'ok' )       $where = 'WHERE is_broken = 0';
		if ( $filter === 'internal' ) $where = "WHERE link_type = 'internal'";
		if ( $filter === 'external' ) $where = "WHERE link_type = 'external'";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( 'SELECT * FROM ' . self::table() . " {$where} ORDER BY is_broken DESC, source_id ASC", ARRAY_A ) ?: [];
	}

	/* ── Remove broken links from post content ───────────────────────── */
	public static function remove_broken_links(): int {
		global $wpdb;

		$broken = $wpdb->get_results(
			'SELECT * FROM ' . self::table() . ' WHERE is_broken = 1', // phpcs:ignore
			ARRAY_A
		);

		if ( empty( $broken ) ) return 0;

		/* Group by source post */
		$by_post = [];
		foreach ( $broken as $row ) {
			$by_post[ $row['source_id'] ][] = $row['link_url'];
		}

		$removed = 0;

		foreach ( $by_post as $post_id => $urls ) {
			$post = get_post( $post_id );
			if ( ! $post ) continue;

			$content = $post->post_content;

			foreach ( $urls as $url ) {
				/* Replace <a href="URL">text</a> → just the anchor text */
				$content = preg_replace(
					'/<a\s[^>]*href=["\']' . preg_quote( $url, '/' ) . '["\'][^>]*>(.*?)<\/a>/is',
					'$1',
					$content
				);
				$removed++;
			}

			wp_update_post( [ 'ID' => $post_id, 'post_content' => $content ] );
		}

		/* Remove from DB */
		$wpdb->query( 'DELETE FROM ' . self::table() . ' WHERE is_broken = 1' ); // phpcs:ignore

		return $removed;
	}

	/* ── Summary ─────────────────────────────────────────────────────── */
	public static function summary(): array {
		global $wpdb;

		$table = self::table();

		$total    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore
		$broken   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_broken = 1" ); // phpcs:ignore
		$internal = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE link_type = 'internal'" ); // phpcs:ignore
		$external = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE link_type = 'external'" ); // phpcs:ignore
		$ok       = $total - $broken;

		return compact( 'total', 'broken', 'ok', 'internal', 'external' );
	}

	/* ── Internal helpers ────────────────────────────────────────────── */

	/** Extract all <a href="..."> links from HTML content. */
	private static function extract_links( string $content ): array {
		$links = [];
		if ( ! preg_match_all( '/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $content, $matches, PREG_SET_ORDER ) ) {
			return $links;
		}
		foreach ( $matches as $m ) {
			$url = trim( $m[1] );
			if ( empty( $url ) || str_starts_with( $url, '#' ) || str_starts_with( $url, 'mailto:' ) || str_starts_with( $url, 'tel:' ) ) {
				continue;
			}
			$links[] = [ 'url' => $url, 'text' => wp_strip_all_tags( $m[2] ) ];
		}
		return $links;
	}

	/** Convert relative URL to absolute. */
	private static function to_absolute( string $url ): string {
		if ( str_starts_with( $url, 'http' ) ) return $url;
		if ( str_starts_with( $url, '//' ) ) return 'https:' . $url;
		return rtrim( get_site_url(), '/' ) . '/' . ltrim( $url, '/' );
	}

	/** HEAD request; falls back to GET on failure. Returns HTTP status code (0 = network error). */
	private static function check_url( string $url, int $timeout ): int {
		$args = [
			'timeout'    => $timeout,
			'user-agent' => 'MerdusSEO Link Checker/1.0',
			'sslverify'  => false,
		];

		$response = wp_remote_head( $url, $args );

		if ( is_wp_error( $response ) ) {
			/* Try GET fallback */
			$response = wp_remote_get( $url, array_merge( $args, [ 'timeout' => $timeout ] ) );
		}

		if ( is_wp_error( $response ) ) return 0;

		return (int) wp_remote_retrieve_response_code( $response );
	}
}
