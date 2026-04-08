<?php
defined( 'ABSPATH' ) || exit;

class MerdusSEO_Cannibalization {

	/* ── Scan ────────────────────────────────────────────────────────── */
	public static function scan(): array {
		$posts = get_posts( [
			'post_type'      => [ 'post' ],
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		] );

		/* Build keyword map: keyword → [post, post, ...] */
		$keyword_map = [];

		foreach ( $posts as $post ) {
			$keywords = self::collect_keywords( $post );
			foreach ( $keywords as $keyword ) {
				$kn = self::normalize( $keyword );
				if ( ! $kn ) continue;
				$keyword_map[ $kn ][] = [
					'post_id'   => $post->ID,
					'title'     => $post->post_title,
					'url'       => get_permalink( $post->ID ),
					'date'      => $post->post_date,
					'type'      => 'post',
				];
			}
		}

		/* Only keep keywords that appear in 2+ posts */
		$groups = [];
		foreach ( $keyword_map as $keyword => $items ) {
			if ( count( $items ) < 2 ) continue;
			$groups[] = [
				'keyword' => $keyword,
				'count'   => count( $items ),
				'posts'   => $items,
			];
		}

		/* Sort by count desc */
		usort( $groups, fn( $a, $b ) => $b['count'] <=> $a['count'] );

		return $groups;
	}

	/* ── Helpers ─────────────────────────────────────────────────────── */

	/** Collect all keywords for a post: focus keyword meta, tags, categories, title words. */
	private static function collect_keywords( WP_Post $post ): array {
		$keywords = [];

		/* Focus keyword meta (Yoast / RankMath / our own) */
		$focus_sources = [
			'_yoast_wpseo_focuskw',
			'rank_math_focus_keyword',
			'_merdusseo_focus_kw',
		];
		foreach ( $focus_sources as $meta_key ) {
			$val = get_post_meta( $post->ID, $meta_key, true );
			if ( ! empty( $val ) ) {
				/* RankMath may store comma-separated keywords */
				foreach ( explode( ',', $val ) as $kw ) {
					$kw = trim( $kw );
					if ( $kw ) $keywords[] = $kw;
				}
			}
		}

		/* Tags */
		$tags = get_the_tags( $post->ID );
		if ( $tags ) {
			foreach ( $tags as $tag ) {
				$keywords[] = $tag->name;
			}
		}

		/* Categories */
		$cats = get_the_category( $post->ID );
		if ( $cats ) {
			foreach ( $cats as $cat ) {
				if ( $cat->slug !== 'uncategorized' ) {
					$keywords[] = $cat->name;
				}
			}
		}

		return $keywords;
	}

	/** Lowercase, trim, collapse whitespace. */
	private static function normalize( string $keyword ): string {
		return trim( preg_replace( '/\s+/', ' ', strtolower( $keyword ) ) );
	}

	/* ── Summary ─────────────────────────────────────────────────────── */
	public static function summary( array $groups ): array {
		$total_groups   = count( $groups );
		$affected_posts = 0;
		foreach ( $groups as $g ) {
			$affected_posts += $g['count'];
		}
		return [
			'groups'         => $total_groups,
			'affected_posts' => $affected_posts,
		];
	}
}
