<?php
defined( 'ABSPATH' ) || exit;

class MerdusSEO_Installer {

	/** Custom DB table: link check results */
	const TABLE_LINKS = 'merdusseo_links';

	/* ── Activation ──────────────────────────────────────────────────── */
	public static function activate(): void {
		self::create_tables();
		self::set_defaults();
		flush_rewrite_rules();
	}

	/* ── Deactivation ────────────────────────────────────────────────── */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}

	/* ── Tables ──────────────────────────────────────────────────────── */
	private static function create_tables(): void {
		global $wpdb;

		$table   = $wpdb->prefix . self::TABLE_LINKS;
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			source_id    BIGINT UNSIGNED NOT NULL,
			source_url   VARCHAR(2048)  NOT NULL,
			link_url     VARCHAR(2048)  NOT NULL,
			link_type    VARCHAR(16)    NOT NULL DEFAULT 'internal',
			http_status  SMALLINT       NOT NULL DEFAULT 0,
			is_broken    TINYINT(1)     NOT NULL DEFAULT 0,
			anchor_text  VARCHAR(512)   NOT NULL DEFAULT '',
			last_checked DATETIME       NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (id),
			KEY source_id (source_id),
			KEY is_broken (is_broken)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/* ── Defaults ────────────────────────────────────────────────────── */
	private static function set_defaults(): void {
		$defaults = [
			'merdusseo_ai_provider'     => 'openai',
			'merdusseo_ai_model'        => 'gpt-4o-mini',
			'merdusseo_ai_api_key'      => '',
			'merdusseo_meta_title_min'  => 30,
			'merdusseo_meta_title_max'  => 60,
			'merdusseo_meta_desc_min'   => 70,
			'merdusseo_meta_desc_max'   => 160,
			'merdusseo_post_types'      => [ 'post', 'page' ],
			'merdusseo_link_timeout'    => 10,
		];

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}
}
