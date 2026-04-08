<?php
defined( 'ABSPATH' ) || exit;

class MerdusSEO_Link_Checker {

	/** @var string */
	private static $table;

	/** WordPress-internal path prefixes that should never be checked. */
	private const WP_INTERNAL_PATHS = [
		'/wp-admin',
		'/wp-login.php',
		'/wp-json',
		'/wp-cron.php',
		'/xmlrpc.php',
		'/wp-signup.php',
		'/wp-activate.php',
		'/wp-comments-post.php',
	];

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
				$absolute = self::to_absolute( $url );

				/* Skip links we should never check */
				if ( self::should_skip( $url, $absolute ) ) {
					continue;
				}

				$parsed = wp_parse_url( $url );
				$host   = $parsed['host'] ?? '';
				$type   = ( $host === $site_host || empty( $host ) ) ? 'internal' : 'external';

				$status = self::check_url( $absolute, $timeout );

				/*
				 * 403 = access forbidden by the remote server, NOT necessarily broken.
				 * We store it but do NOT mark it as is_broken so the "remove" action
				 * won't touch it.
				 * Truly broken: 404, 410, 5xx, timeout (0).
				 */
				$broken = ( $status !== 403 && ( $status >= 400 || $status === 0 ) ) ? 1 : 0;

				/* Skip links that returned a healthy response — no need to store them */
				if ( ! $broken && $status !== 403 ) {
					continue;
				}

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
				$row['id']          = $wpdb->insert_id;
				$row['status_type'] = self::get_status_type( $status );
				$all_links[]        = $row;
			}
		}

		return $all_links;
	}

	/* ── Get stored results ──────────────────────────────────────────── */
	/**
	 * @param string $filter  'problems'(default) | 'broken' | 'restricted' | 'internal' | 'external'
	 */
	public static function get_results( string $filter = 'problems' ): array {
		global $wpdb;

		switch ( $filter ) {
			case 'broken':
				$where = 'WHERE is_broken = 1';
				break;
			case 'restricted':
				$where = 'WHERE http_status = 403';
				break;
			case 'internal':
				$where = "WHERE link_type = 'internal' AND (is_broken = 1 OR http_status = 403)";
				break;
			case 'external':
				$where = "WHERE link_type = 'external' AND (is_broken = 1 OR http_status = 403)";
				break;
			default: /* problems — broken + restricted */
				$where = 'WHERE is_broken = 1 OR http_status = 403';
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results(
			'SELECT * FROM ' . self::table() . " {$where} ORDER BY is_broken DESC, http_status ASC, source_id ASC",
			ARRAY_A
		) ?: [];

		/* Enrich each row with a semantic status_type */
		foreach ( $rows as &$row ) {
			$row['status_type'] = self::get_status_type( (int) $row['http_status'] );
		}
		unset( $row );

		return $rows;
	}

	/* ── Remove broken links from post content ───────────────────────── */
	/** Removes only truly broken links (is_broken = 1). 403-restricted links are left intact. */
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

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		$broken     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_broken = 1" );
		$restricted = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE http_status = 403" );
		$internal   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE link_type = 'internal' AND (is_broken = 1 OR http_status = 403)" );
		$external   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE link_type = 'external' AND (is_broken = 1 OR http_status = 403)" );
		// phpcs:enable

		$problems = $broken + $restricted;

		return compact( 'total', 'broken', 'restricted', 'ok', 'internal', 'external', 'problems' );
	}

	/* ── Status type helper ──────────────────────────────────────────── */
	/** Maps HTTP status code to a semantic type string. */
	public static function get_status_type( int $status ): string {
		if ( $status === 403 )                          return 'restricted';
		if ( $status >= 400 || $status === 0 )          return 'broken';
		if ( $status >= 200 && $status < 400 )          return 'ok';
		return 'unknown';
	}

	/* ── Internal helpers ────────────────────────────────────────────── */

	/**
	 * Returns true if the link should be skipped entirely.
	 * Covers: javascript: pseudo-URIs, data: URIs, WordPress server-side paths.
	 */
	private static function should_skip( string $url, string $absolute_url ): bool {
		$lower = strtolower( $url );

		/* JavaScript and data pseudo-URIs */
		if ( str_starts_with( $lower, 'javascript:' ) ) return true;
		if ( str_starts_with( $lower, 'data:' ) )       return true;

		/* WordPress internal server paths */
		$path = rtrim( wp_parse_url( $absolute_url, PHP_URL_PATH ) ?? '', '/' );
		foreach ( self::WP_INTERNAL_PATHS as $wp_path ) {
			if ( $path === $wp_path || str_starts_with( $path, $wp_path . '/' ) ) {
				return true;
			}
		}

		return false;
	}

	/** Extract all <a href="..."> links from HTML content. */
	private static function extract_links( string $content ): array {
		$links = [];
		if ( ! preg_match_all( '/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $content, $matches, PREG_SET_ORDER ) ) {
			return $links;
		}
		foreach ( $matches as $m ) {
			$url   = trim( $m[1] );
			$lower = strtolower( $url );
			/* Early-exit for clearly non-HTTP targets */
			if (
				empty( $url ) ||
				str_starts_with( $url, '#' ) ||
				str_starts_with( $lower, 'mailto:' ) ||
				str_starts_with( $lower, 'tel:' ) ||
				str_starts_with( $lower, 'javascript:' ) ||
				str_starts_with( $lower, 'data:' ) ||
				str_starts_with( $lower, 'sms:' ) ||
				str_starts_with( $lower, 'whatsapp:' )
			) {
				continue;
			}
			$links[] = [ 'url' => $url, 'text' => wp_strip_all_tags( $m[2] ) ];
		}
		return $links;
	}

	/** Convert relative URL to absolute. */
	private static function to_absolute( string $url ): string {
		if ( str_starts_with( $url, 'http' ) ) return $url;
		if ( str_starts_with( $url, '//' ) )   return 'https:' . $url;
		return rtrim( get_site_url(), '/' ) . '/' . ltrim( $url, '/' );
	}

	/** HEAD request; falls back to GET on failure. Returns HTTP status code (0 = network error/timeout). */
	private static function check_url( string $url, int $timeout ): int {
		$args = [
			'timeout'    => $timeout,
			'user-agent' => 'MerdusSEO Link Checker/1.0',
			'sslverify'  => false,
		];

		$response = wp_remote_head( $url, $args );

		if ( is_wp_error( $response ) ) {
			/* Try GET fallback — some servers reject HEAD */
			$response = wp_remote_get( $url, $args );
		}

		if ( is_wp_error( $response ) ) return 0;

		return (int) wp_remote_retrieve_response_code( $response );
	}
}
