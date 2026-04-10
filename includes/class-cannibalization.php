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

		/* Build keyword map: keyword → [ { post_id, title, url, date, source }, … ] */
		$keyword_map = [];

		foreach ( $posts as $post ) {
			$kw_entries = self::collect_keywords( $post );

			foreach ( $kw_entries as $entry ) {
				$kn = self::normalize( $entry['keyword'] );
				if ( ! $kn || mb_strlen( $kn ) < 3 ) continue;

				$keyword_map[ $kn ][] = [
					'post_id' => $post->ID,
					'title'   => $post->post_title,
					'url'     => get_permalink( $post->ID ),
					'date'    => $post->post_date,
					'source'  => $entry['source'], /* tag | category | focus_keyword */
				];
			}
		}

		/* Only keep keywords that appear in 2+ posts (actual cannibalization) */
		$groups = [];

		foreach ( $keyword_map as $keyword => $items ) {
			if ( count( $items ) < 2 ) continue;

			/* Collect source types present in this group */
			$sources = array_unique( array_column( $items, 'source' ) );

			/* Build recommendation based on group */
			$recommendation = self::build_recommendation( $keyword, $sources, count( $items ) );

			$groups[] = [
				'keyword'        => $keyword,
				'count'          => count( $items ),
				'sources'        => $sources,
				'recommendation' => $recommendation,
				'posts'          => $items,
			];
		}

		/* Sort by count desc */
		usort( $groups, fn( $a, $b ) => $b['count'] <=> $a['count'] );

		return $groups;
	}

	/* ── Helpers ─────────────────────────────────────────────────────── */

	/**
	 * Collect all keywords for a post with their source type.
	 * @return array<array{keyword: string, source: string}>
	 */
	private static function collect_keywords( WP_Post $post ): array {
		$entries = [];

		/* Focus keyword meta (Yoast / RankMath / our own) */
		$focus_sources = [
			'_yoast_wpseo_focuskw'    => 'focus_keyword',
			'rank_math_focus_keyword' => 'focus_keyword',
			'_merdusseo_focus_kw'     => 'focus_keyword',
		];
		foreach ( $focus_sources as $meta_key => $source ) {
			$val = get_post_meta( $post->ID, $meta_key, true );
			if ( ! empty( $val ) ) {
				foreach ( explode( ',', $val ) as $kw ) {
					$kw = trim( $kw );
					if ( $kw ) $entries[] = [ 'keyword' => $kw, 'source' => $source ];
				}
			}
		}

		/* Tags */
		$tags = get_the_tags( $post->ID );
		if ( $tags ) {
			foreach ( $tags as $tag ) {
				$entries[] = [ 'keyword' => $tag->name, 'source' => 'tag' ];
			}
		}

		/* Categories (skip "Uncategorized") */
		$cats = get_the_category( $post->ID );
		if ( $cats ) {
			foreach ( $cats as $cat ) {
				if ( $cat->slug !== 'uncategorized' ) {
					$entries[] = [ 'keyword' => $cat->name, 'source' => 'category' ];
				}
			}
		}

		return $entries;
	}

	/** Lowercase + trim + collapse whitespace. */
	private static function normalize( string $keyword ): string {
		return trim( preg_replace( '/\s+/', ' ', strtolower( $keyword ) ) );
	}

	/** Build a plain-language recommendation for the cannibalization group. */
	private static function build_recommendation( string $keyword, array $sources, int $count ): array {
		$has_focus = in_array( 'focus_keyword', $sources, true );
		$has_tag   = in_array( 'tag', $sources, true );
		$has_cat   = in_array( 'category', $sources, true );

		$why   = [];
		$fixes = [];

		if ( $has_focus ) {
			$why[]   = '"' . $keyword . '" aynı odak anahtar kelime olarak ' . $count . ' farklı yazıda kullanılmış.';
			$fixes[] = 'En kapsamlı yazıyı belirleyin ve diğerlerine <strong>canonical URL</strong> ekleyin.';
			$fixes[] = 'İkincil yazıları birincil yazıyla birleştirmeyi (merge) düşünün.';
			$fixes[] = 'Her yazıya farklı bir alt konu / long-tail varyant atayın.';
		}
		if ( $has_tag ) {
			$why[]   = '"' . $keyword . '" etiketi birden fazla yazıda kullanılmış.';
			$fixes[] = 'Etiketi kaldırarak ya da yeniden adlandırarak farklılaştırın.';
			$fixes[] = 'Etiket arşiv sayfasına noindex ekleyin (AIOSEO / Yoast üzerinden).';
		}
		if ( $has_cat ) {
			$why[]   = '"' . $keyword . '" kategorisi birden fazla yazıyı kapsıyor.';
			$fixes[] = 'Kategoriyi daha spesifik alt kategorilere bölün.';
			$fixes[] = 'Kategori sayfasını bu konunun ana "pillar" sayfası olarak güçlendirin.';
		}

		if ( empty( $why ) ) {
			$why[]   = '"' . $keyword . '" aynı anahtar kelime ' . $count . ' yazıda tespit edildi.';
			$fixes[] = 'Yazıların konu farklılaşmasını gözden geçirin.';
		}

		return [
			'why'   => $why,
			'fixes' => $fixes,
		];
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

	/* ── Source label helper ─────────────────────────────────────────── */
	public static function source_label( string $source ): string {
		return match ( $source ) {
			'focus_keyword' => 'Odak Kelime',
			'tag'           => 'Etiket',
			'category'      => 'Kategori',
			default         => $source,
		};
	}

	public static function source_color( string $source ): string {
		return match ( $source ) {
			'focus_keyword' => 'purple',
			'tag'           => 'blue',
			'category'      => 'orange',
			default         => 'gray',
		};
	}
}
