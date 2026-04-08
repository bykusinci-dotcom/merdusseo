<?php
defined( 'ABSPATH' ) || exit;

class MerdusSEO_Admin {

	public function __construct() {
		add_action( 'admin_menu',            [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		/* AJAX — logged-in only */
		$ajax_actions = [
			'merdusseo_scan_meta'          => 'ajax_scan_meta',
			'merdusseo_scan_links'         => 'ajax_scan_links',
			'merdusseo_scan_cannibalization' => 'ajax_scan_cannibalization',
			'merdusseo_remove_broken_links'=> 'ajax_remove_broken_links',
			'merdusseo_ai_suggest'         => 'ajax_ai_suggest',
			'merdusseo_save_meta'          => 'ajax_save_meta',
			'merdusseo_export_csv'         => 'ajax_export_csv',
			'merdusseo_import_csv'         => 'ajax_import_csv',
			'merdusseo_save_settings'      => 'ajax_save_settings',
		];

		foreach ( $ajax_actions as $action => $method ) {
			add_action( 'wp_ajax_' . $action, [ $this, $method ] );
		}

		/* Handle CSV export (redirect-based, not JSON) */
		add_action( 'admin_init', [ $this, 'maybe_export_csv' ] );
	}

	/* ── Admin Menu ──────────────────────────────────────────────────── */
	public function register_menu(): void {
		add_menu_page(
			'MerdusSEO',
			'MerdusSEO',
			'manage_options',
			'merdusseo',
			[ $this, 'page_dashboard' ],
			'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>' ),
			75
		);

		add_submenu_page( 'merdusseo', 'Dashboard',          'Dashboard',          'manage_options', 'merdusseo',                  [ $this, 'page_dashboard' ] );
		add_submenu_page( 'merdusseo', 'Meta Analizi',       'Meta Analizi',       'manage_options', 'merdusseo-meta',              [ $this, 'page_meta' ] );
		add_submenu_page( 'merdusseo', 'Kırık Linkler',      'Kırık Linkler',      'manage_options', 'merdusseo-links',             [ $this, 'page_links' ] );
		add_submenu_page( 'merdusseo', 'Keyword Yamyamlığı', 'Keyword Yamyamlığı', 'manage_options', 'merdusseo-cannibalization',   [ $this, 'page_cannibalization' ] );
		add_submenu_page( 'merdusseo', 'Ayarlar',            'Ayarlar',            'manage_options', 'merdusseo-settings',          [ $this, 'page_settings' ] );
	}

	/* ── Asset enqueue ───────────────────────────────────────────────── */
	public function enqueue_assets( string $hook ): void {
		$screens = [
			'toplevel_page_merdusseo',
			'merdusseo_page_merdusseo-meta',
			'merdusseo_page_merdusseo-links',
			'merdusseo_page_merdusseo-cannibalization',
			'merdusseo_page_merdusseo-settings',
		];

		if ( ! in_array( $hook, $screens, true ) ) return;

		wp_enqueue_style(
			'merdusseo-admin',
			MERDUSSEO_URL . 'admin/css/admin-style.css',
			[],
			MERDUSSEO_VERSION
		);

		wp_enqueue_script(
			'merdusseo-admin',
			MERDUSSEO_URL . 'admin/js/admin-script.js',
			[ 'jquery' ],
			MERDUSSEO_VERSION,
			true
		);

		wp_localize_script( 'merdusseo-admin', 'merdusSEO', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'merdusseo_nonce' ),
			'exportUrl' => admin_url( 'admin-ajax.php?action=merdusseo_export_csv&_wpnonce=' . wp_create_nonce( 'merdusseo_nonce' ) ),
		] );
	}

	/* ── Pages ───────────────────────────────────────────────────────── */
	public function page_dashboard(): void       { $this->load_view( 'dashboard' ); }
	public function page_meta(): void            { $this->load_view( 'meta-analysis' ); }
	public function page_links(): void           { $this->load_view( 'link-checker' ); }
	public function page_cannibalization(): void { $this->load_view( 'cannibalization' ); }
	public function page_settings(): void        { $this->load_view( 'settings' ); }

	private function load_view( string $name ): void {
		$file = MERDUSSEO_DIR . 'admin/views/' . $name . '.php';
		if ( file_exists( $file ) ) include $file;
	}

	/* ── AJAX: Scan meta ─────────────────────────────────────────────── */
	public function ajax_scan_meta(): void {
		$this->verify_nonce();
		$results = MerdusSEO_Meta_Analyzer::scan();
		set_transient( 'merdusseo_meta_results', $results, HOUR_IN_SECONDS * 12 );
		wp_send_json_success( [
			'results' => $results,
			'summary' => MerdusSEO_Meta_Analyzer::summary( $results ),
		] );
	}

	/* ── AJAX: Scan links ────────────────────────────────────────────── */
	public function ajax_scan_links(): void {
		$this->verify_nonce();
		set_time_limit( 300 );
		$results = MerdusSEO_Link_Checker::scan();
		wp_send_json_success( [
			'count'   => count( $results ),
			'summary' => MerdusSEO_Link_Checker::summary(),
		] );
	}

	/* ── AJAX: Remove broken links ───────────────────────────────────── */
	public function ajax_remove_broken_links(): void {
		$this->verify_nonce();
		$removed = MerdusSEO_Link_Checker::remove_broken_links();
		wp_send_json_success( [ 'removed' => $removed ] );
	}

	/* ── AJAX: Scan cannibalization ──────────────────────────────────── */
	public function ajax_scan_cannibalization(): void {
		$this->verify_nonce();
		$results = MerdusSEO_Cannibalization::scan();
		set_transient( 'merdusseo_cannibalization_results', $results, HOUR_IN_SECONDS * 12 );
		wp_send_json_success( [
			'results' => $results,
			'summary' => MerdusSEO_Cannibalization::summary( $results ),
		] );
	}

	/* ── AJAX: AI suggest ────────────────────────────────────────────── */
	public function ajax_ai_suggest(): void {
		$this->verify_nonce();

		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		$field   = isset( $_POST['field'] ) && $_POST['field'] === 'desc' ? 'desc' : 'title';

		if ( ! $post_id ) {
			wp_send_json_error( [ 'message' => 'Geçersiz post ID.' ] );
		}

		$result = MerdusSEO_AI_Fixer::suggest( $post_id, $field );

		if ( isset( $result['error'] ) ) {
			wp_send_json_error( [ 'message' => $result['error'] ] );
		}

		wp_send_json_success( $result );
	}

	/* ── AJAX: Save meta ─────────────────────────────────────────────── */
	public function ajax_save_meta(): void {
		$this->verify_nonce();

		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		$title   = isset( $_POST['meta_title'] ) ? sanitize_text_field( wp_unslash( $_POST['meta_title'] ) ) : '';
		$desc    = isset( $_POST['meta_desc'] )  ? sanitize_textarea_field( wp_unslash( $_POST['meta_desc'] ) ) : '';

		if ( ! $post_id ) {
			wp_send_json_error( [ 'message' => 'Geçersiz post ID.' ] );
		}

		MerdusSEO_Meta_Analyzer::save_meta( $post_id, $title, $desc );
		wp_send_json_success( [ 'message' => 'Meta veriler kaydedildi.' ] );
	}

	/* ── AJAX: Export CSV ────────────────────────────────────────────── */
	public function maybe_export_csv(): void {
		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'merdusseo_export_csv' ) return;
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Yetkiniz yok.' );
		check_ajax_referer( 'merdusseo_nonce' );

		$results = get_transient( 'merdusseo_meta_results' );
		if ( ! $results ) {
			$results = MerdusSEO_Meta_Analyzer::scan();
		}

		MerdusSEO_CSV_Handler::export( $results );
	}

	public function ajax_export_csv(): void {
		/* Handled by maybe_export_csv() above — redirect download */
		wp_send_json_error( [ 'message' => 'Doğrudan erişim gereklidir.' ] );
	}

	/* ── AJAX: Import CSV ────────────────────────────────────────────── */
	public function ajax_import_csv(): void {
		$this->verify_nonce();

		if ( empty( $_FILES['csv_file'] ) ) {
			wp_send_json_error( [ 'message' => 'Dosya bulunamadı.' ] );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$result = MerdusSEO_CSV_Handler::import( $_FILES['csv_file'] );

		if ( ! empty( $result['errors'] ) && $result['updated'] === 0 ) {
			wp_send_json_error( [ 'message' => implode( ' ', $result['errors'] ) ] );
		}

		wp_send_json_success( [
			'updated' => $result['updated'],
			'errors'  => $result['errors'],
			'message' => $result['updated'] . ' kayıt güncellendi.',
		] );
	}

	/* ── AJAX: Save settings ─────────────────────────────────────────── */
	public function ajax_save_settings(): void {
		$this->verify_nonce();

		$fields = [
			'merdusseo_ai_provider'    => 'sanitize_text_field',
			'merdusseo_ai_api_key'     => 'sanitize_text_field',
			'merdusseo_ai_model'       => 'sanitize_text_field',
			'merdusseo_meta_title_min' => 'absint',
			'merdusseo_meta_title_max' => 'absint',
			'merdusseo_meta_desc_min'  => 'absint',
			'merdusseo_meta_desc_max'  => 'absint',
			'merdusseo_link_timeout'   => 'absint',
		];

		foreach ( $fields as $key => $sanitizer ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_option( $key, $sanitizer( wp_unslash( $_POST[ $key ] ) ) );
			}
		}

		/* Post types (array) */
		if ( isset( $_POST['merdusseo_post_types'] ) && is_array( $_POST['merdusseo_post_types'] ) ) {
			update_option( 'merdusseo_post_types', array_map( 'sanitize_key', $_POST['merdusseo_post_types'] ) );
		}

		wp_send_json_success( [ 'message' => 'Ayarlar kaydedildi.' ] );
	}

	/* ── Helper ──────────────────────────────────────────────────────── */
	private function verify_nonce(): void {
		if ( ! check_ajax_referer( 'merdusseo_nonce', '_ajax_nonce', false ) ) {
			wp_send_json_error( [ 'message' => 'Güvenlik doğrulaması başarısız.' ] );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Yetkiniz yok.' ] );
		}
	}
}
