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

	/** Returns meta title — checks Yoast, RankMath, AIOSEO, then plugin's own field. */
	public static function get_meta_title( WP_Post $post ): string {
		$sources = [
			'_yoast_wpseo_title',
			'rank_math_title',
			'_aioseop_title',
			'_merdusseo_title',
		];
		foreach ( $sources as $key ) {
			$val = get_post_meta( $post->ID, $key, true );
			if ( ! empty( $val ) ) return (string) $val;
		}
		return '';
	}

	/** Returns meta description — checks Yoast, RankMath, AIOSEO, then plugin's own field. */
	public static function get_meta_desc( WP_Post $post ): string {
		$sources = [
			'_yoast_wpseo_metadesc',
			'rank_math_description',
			'_aioseop_description',
			'_merdusseo_desc',
		];
		foreach ( $sources as $key ) {
			$val = get_post_meta( $post->ID, $key, true );
			if ( ! empty( $val ) ) return (string) $val;
		}
		return '';
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
