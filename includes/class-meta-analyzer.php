<?php
defined( 'ABSPATH' ) || exit;

class MerdusSEO_Meta_Analyzer {

	/* ── Public scan entry point ─────────────────────────────────────── */
	public static function scan(): array {
		$post_types = get_option( 'merdusseo_post_types', [ 'post', 'page' ] );
		$title_min  = (int) get_option( 'merdusseo_meta_title_min', 30 );
		$title_max  = (int) get_option( 'merdusseo_meta_title_max', 60 );
		$desc_min   = (int) get_option( 'merdusseo_meta_desc_min', 70 );
		$desc_max   = (int) get_option( 'merdusseo_meta_desc_max', 160 );

		$posts = get_posts( [
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		] );

		$results = [];

		foreach ( $posts as $post ) {
			$meta_title = self::get_meta_title( $post );
			$meta_desc  = self::get_meta_desc( $post );
			$h1         = self::get_first_h1( $post->post_content );

			$title_len = mb_strlen( strip_tags( $meta_title ) );
			$desc_len  = mb_strlen( strip_tags( $meta_desc ) );

			$issues = [];

			/* Meta Title checks */
			if ( empty( $meta_title ) ) {
				$issues[] = 'missing_title';
			} elseif ( $title_len > $title_max ) {
				$issues[] = 'long_title';
			} elseif ( $title_len < $title_min ) {
				$issues[] = 'short_title';
			}

			/* Meta Description checks */
			if ( empty( $meta_desc ) ) {
				$issues[] = 'missing_desc';
			} elseif ( $desc_len > $desc_max ) {
				$issues[] = 'long_desc';
			} elseif ( $desc_len < $desc_min ) {
				$issues[] = 'short_desc';
			}

			/* Duplicate H1 / meta title */
			if ( ! empty( $meta_title ) && ! empty( $h1 ) ) {
				if ( strtolower( trim( $meta_title ) ) === strtolower( trim( $h1 ) ) ) {
					$issues[] = 'duplicate_h1_title';
				}
			}

			$results[] = [
				'post_id'    => $post->ID,
				'title'      => $post->post_title,
				'url'        => get_permalink( $post->ID ),
				'type'       => $post->post_type,
				'meta_title' => $meta_title,
				'meta_desc'  => $meta_desc,
				'h1'         => $h1,
				'title_len'  => $title_len,
				'desc_len'   => $desc_len,
				'issues'     => $issues,
			];
		}

		return $results;
	}

	/* ── Helpers ─────────────────────────────────────────────────────── */

	/** Returns meta title — checks AIOSEO v4 (custom table), Yoast, RankMath, AIOSEO legacy, plugin own. */
	public static function get_meta_title( WP_Post $post ): string {
		/* AIOSEO v4 — stores data in its own table with token-based templates */
		$aioseo = self::get_aioseo_row( $post->ID );
		if ( $aioseo && ! empty( $aioseo->title ) ) {
			return self::resolve_aioseo_tokens( $aioseo->title, $post );
		}

		$sources = [
			'_yoast_wpseo_title',
			'rank_math_title',
			'_aioseo_title',    /* AIOSEO v4 post-meta fallback */
			'_aioseop_title',   /* AIOSEO legacy (v3) */
			'_merdusseo_title',
		];
		foreach ( $sources as $key ) {
			$val = get_post_meta( $post->ID, $key, true );
			if ( ! empty( $val ) ) {
				/* Resolve tokens if present (AIOSEO style) */
				if ( str_contains( (string) $val, '#' ) ) {
					return self::resolve_aioseo_tokens( (string) $val, $post );
				}
				return (string) $val;
			}
		}
		return '';
	}

	/** Returns meta description — checks AIOSEO v4 (custom table), Yoast, RankMath, AIOSEO legacy, plugin own. */
	public static function get_meta_desc( WP_Post $post ): string {
		/* AIOSEO v4 */
		$aioseo = self::get_aioseo_row( $post->ID );
		if ( $aioseo && ! empty( $aioseo->description ) ) {
			return self::resolve_aioseo_tokens( $aioseo->description, $post );
		}

		$sources = [
			'_yoast_wpseo_metadesc',
			'rank_math_description',
			'_aioseo_description',   /* AIOSEO v4 post-meta fallback */
			'_aioseop_description',  /* AIOSEO legacy (v3) */
			'_merdusseo_desc',
		];
		foreach ( $sources as $key ) {
			$val = get_post_meta( $post->ID, $key, true );
			if ( ! empty( $val ) ) {
				if ( str_contains( (string) $val, '#' ) ) {
					return self::resolve_aioseo_tokens( (string) $val, $post );
				}
				return (string) $val;
			}
		}
		return '';
	}

	/* ── AIOSEO v4 helpers ───────────────────────────────────────────── */

	/** Cache of AIOSEO table existence check. */
	private static ?bool $aioseo_table_exists = null;

	/**
	 * Fetch the AIOSEO v4 row for a post from the custom aioseo_posts table.
	 * Returns null if AIOSEO is not installed or row doesn't exist.
	 */
	private static function get_aioseo_row( int $post_id ): ?object {
		global $wpdb;
		static $cache = [];

		if ( array_key_exists( $post_id, $cache ) ) return $cache[ $post_id ];

		/* Check once whether the table exists */
		if ( null === self::$aioseo_table_exists ) {
			$table = $wpdb->prefix . 'aioseo_posts';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			self::$aioseo_table_exists = ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table );
		}

		if ( ! self::$aioseo_table_exists ) {
			$cache[ $post_id ] = null;
			return null;
		}

		$table = $wpdb->prefix . 'aioseo_posts';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT title, description FROM `{$table}` WHERE post_id = %d LIMIT 1",
			$post_id
		) );

		$cache[ $post_id ] = $row ?: null;
		return $cache[ $post_id ];
	}

	/**
	 * Resolve AIOSEO template tokens (e.g. #post_title, #separator_sa, #site_title)
	 * into their actual string values.
	 */
	private static function resolve_aioseo_tokens( string $template, WP_Post $post ): string {
		$separator = self::get_aioseo_separator();

		/* Primary replacements */
		$map = [
			'#post_title'    => $post->post_title,
			'#separator_sa'  => $separator,
			'#site_title'    => get_bloginfo( 'name' ),
			'#tagline'       => get_bloginfo( 'description' ),
			'#author_name'   => get_the_author_meta( 'display_name', $post->post_author ),
			'#post_date'     => get_the_date( '', $post->ID ),
			'#post_year'     => get_the_date( 'Y', $post->ID ),
			'#post_month'    => get_the_date( 'm', $post->ID ),
			'#post_day'      => get_the_date( 'd', $post->ID ),
			'#post_excerpt'  => wp_trim_words( $post->post_excerpt ?: wp_strip_all_tags( $post->post_content ), 25 ),
			/* Aliases */
			'#separator'     => $separator,
			'#blog_title'    => get_bloginfo( 'name' ),
			'#blog_name'     => get_bloginfo( 'name' ),
		];

		$result = str_replace( array_keys( $map ), array_values( $map ), $template );

		/* Strip any remaining unknown #tokens */
		$result = preg_replace( '/#[a-z_]+/', '', $result );

		/* Collapse multiple spaces / trim */
		return trim( preg_replace( '/\s{2,}/', ' ', $result ) );
	}

	/** Read separator character from AIOSEO options (falls back to " - "). */
	private static function get_aioseo_separator(): string {
		static $sep = null;
		if ( null !== $sep ) return $sep;

		$raw = get_option( 'aioseo_options', '' );
		if ( empty( $raw ) ) { $sep = ' - '; return $sep; }

		$data = is_string( $raw ) ? json_decode( $raw, true ) : (array) $raw;

		/* AIOSEO stores the separator symbol under searchAppearance.global.separator */
		$symbol = $data['searchAppearance']['global']['separator'] ?? '-';

		/* Map common AIOSEO separator slugs to characters */
		$symbol_map = [
			'dash'        => '-',
			'ndash'       => '–',
			'mdash'       => '—',
			'pipe'        => '|',
			'bullet'      => '·',
			'arrow'       => '›',
			'tilde'       => '~',
			'colon'       => ':',
		];

		$char = $symbol_map[ $symbol ] ?? $symbol;
		$sep  = ' ' . $char . ' ';
		return $sep;
	}

	/** Extracts first H1 from post content. */
	public static function get_first_h1( string $content ): string {
		if ( preg_match( '/<h1[^>]*>(.*?)<\/h1>/is', $content, $m ) ) {
			return trim( wp_strip_all_tags( $m[1] ) );
		}
		return '';
	}

	/** Save meta title/desc using plugin's own meta keys. */
	public static function save_meta( int $post_id, string $title, string $desc ): void {
		update_post_meta( $post_id, '_merdusseo_title', sanitize_text_field( $title ) );
		update_post_meta( $post_id, '_merdusseo_desc',  sanitize_textarea_field( $desc ) );
	}

	/** Summary counts for dashboard. */
	public static function summary( array $results ): array {
		$total   = count( $results );
		$ok      = 0;
		$issues  = 0;
		$counts  = [
			'missing_title'    => 0,
			'long_title'       => 0,
			'short_title'      => 0,
			'missing_desc'     => 0,
			'long_desc'        => 0,
			'short_desc'       => 0,
			'duplicate_h1_title' => 0,
		];

		foreach ( $results as $r ) {
			if ( empty( $r['issues'] ) ) {
				$ok++;
			} else {
				$issues++;
				foreach ( $r['issues'] as $issue ) {
					if ( isset( $counts[ $issue ] ) ) $counts[ $issue ]++;
				}
			}
		}

		return compact( 'total', 'ok', 'issues', 'counts' );
	}
}
