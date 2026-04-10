<?php
defined( 'ABSPATH' ) || exit;

/**
 * Google Search Console integration.
 *
 * OAuth 2.0 flow:
 *   1. User configures Client ID + Secret (from Google Cloud Console).
 *   2. User clicks "Google ile Bağlan" → redirected to Google OAuth consent screen.
 *   3. Google redirects back to ?page=merdusseo-gsc&action=oauth_callback&code=XXX.
 *   4. Plugin exchanges code for access + refresh tokens.
 *   5. Tokens stored in wp_options; used for future API calls.
 */
class MerdusSEO_GSC {

	const OPTION_CLIENT_ID     = 'merdusseo_gsc_client_id';
	const OPTION_CLIENT_SECRET = 'merdusseo_gsc_client_secret';
	const OPTION_ACCESS_TOKEN  = 'merdusseo_gsc_access_token';
	const OPTION_REFRESH_TOKEN = 'merdusseo_gsc_refresh_token';
	const OPTION_TOKEN_EXPIRY  = 'merdusseo_gsc_token_expiry';
	const OPTION_SITE_URL      = 'merdusseo_gsc_site_url';

	private const GOOGLE_AUTH_URL  = 'https://accounts.google.com/o/oauth2/v2/auth';
	private const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';
	private const GSC_API_BASE     = 'https://searchconsole.googleapis.com/webmasters/v3';
	private const SCOPE            = 'https://www.googleapis.com/auth/webmasters.readonly';

	/* ── Connection status ───────────────────────────────────────────── */
	public static function is_connected(): bool {
		return ! empty( get_option( self::OPTION_ACCESS_TOKEN ) );
	}

	public static function is_configured(): bool {
		return ! empty( get_option( self::OPTION_CLIENT_ID ) )
			&& ! empty( get_option( self::OPTION_CLIENT_SECRET ) );
	}

	/* ── OAuth redirect URL ──────────────────────────────────────────── */
	public static function get_redirect_uri(): string {
		return admin_url( 'admin.php?page=merdusseo-gsc&action=oauth_callback' );
	}

	public static function get_auth_url(): string {
		return add_query_arg( [
			'client_id'     => get_option( self::OPTION_CLIENT_ID ),
			'redirect_uri'  => self::get_redirect_uri(),
			'response_type' => 'code',
			'scope'         => self::SCOPE,
			'access_type'   => 'offline',
			'prompt'        => 'consent',
			'state'         => wp_create_nonce( 'merdusseo_gsc_oauth' ),
		], self::GOOGLE_AUTH_URL );
	}

	/* ── Handle OAuth callback ───────────────────────────────────────── */
	public static function handle_callback(): array {
		/* Verify nonce */
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		if ( ! wp_verify_nonce( $state, 'merdusseo_gsc_oauth' ) ) {
			return [ 'error' => 'Güvenlik doğrulaması başarısız.' ];
		}

		$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		if ( empty( $code ) ) {
			$err = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : 'Bilinmeyen hata';
			return [ 'error' => 'Google yetkilendirme hatası: ' . $err ];
		}

		$response = wp_remote_post( self::GOOGLE_TOKEN_URL, [
			'timeout' => 20,
			'body'    => [
				'code'          => $code,
				'client_id'     => get_option( self::OPTION_CLIENT_ID ),
				'client_secret' => get_option( self::OPTION_CLIENT_SECRET ),
				'redirect_uri'  => self::get_redirect_uri(),
				'grant_type'    => 'authorization_code',
			],
		] );

		if ( is_wp_error( $response ) ) {
			return [ 'error' => $response->get_error_message() ];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! empty( $body['error'] ) ) {
			return [ 'error' => $body['error_description'] ?? $body['error'] ];
		}

		update_option( self::OPTION_ACCESS_TOKEN,  $body['access_token'] );
		update_option( self::OPTION_TOKEN_EXPIRY,  time() + (int) ( $body['expires_in'] ?? 3600 ) );

		if ( ! empty( $body['refresh_token'] ) ) {
			update_option( self::OPTION_REFRESH_TOKEN, $body['refresh_token'] );
		}

		return [ 'success' => true ];
	}

	/* ── Refresh access token ────────────────────────────────────────── */
	public static function refresh_token(): bool {
		$refresh = get_option( self::OPTION_REFRESH_TOKEN, '' );
		if ( empty( $refresh ) ) return false;

		$response = wp_remote_post( self::GOOGLE_TOKEN_URL, [
			'timeout' => 20,
			'body'    => [
				'refresh_token' => $refresh,
				'client_id'     => get_option( self::OPTION_CLIENT_ID ),
				'client_secret' => get_option( self::OPTION_CLIENT_SECRET ),
				'grant_type'    => 'refresh_token',
			],
		] );

		if ( is_wp_error( $response ) ) return false;

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['access_token'] ) ) return false;

		update_option( self::OPTION_ACCESS_TOKEN, $body['access_token'] );
		update_option( self::OPTION_TOKEN_EXPIRY, time() + (int) ( $body['expires_in'] ?? 3600 ) );

		return true;
	}

	/* ── Get valid access token (auto-refresh) ───────────────────────── */
	private static function get_access_token(): ?string {
		$token  = get_option( self::OPTION_ACCESS_TOKEN, '' );
		$expiry = (int) get_option( self::OPTION_TOKEN_EXPIRY, 0 );

		if ( empty( $token ) ) return null;

		/* Refresh if token expires in the next 5 minutes */
		if ( time() > ( $expiry - 300 ) ) {
			if ( ! self::refresh_token() ) return null;
			$token = get_option( self::OPTION_ACCESS_TOKEN, '' );
		}

		return $token ?: null;
	}

	/* ── List verified sites ─────────────────────────────────────────── */
	public static function get_sites(): array {
		$token = self::get_access_token();
		if ( ! $token ) return [];

		$response = wp_remote_get( self::GSC_API_BASE . '/sites', [
			'timeout' => 15,
			'headers' => [ 'Authorization' => 'Bearer ' . $token ],
		] );

		if ( is_wp_error( $response ) ) return [];

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return $body['siteEntry'] ?? [];
	}

	/* ── Disconnect ──────────────────────────────────────────────────── */
	public static function disconnect(): void {
		delete_option( self::OPTION_ACCESS_TOKEN );
		delete_option( self::OPTION_REFRESH_TOKEN );
		delete_option( self::OPTION_TOKEN_EXPIRY );
		delete_option( self::OPTION_SITE_URL );
	}

	/* ── Save settings ───────────────────────────────────────────────── */
	public static function save_settings( array $data ): void {
		if ( isset( $data['client_id'] ) ) {
			update_option( self::OPTION_CLIENT_ID, sanitize_text_field( $data['client_id'] ) );
		}
		if ( isset( $data['client_secret'] ) ) {
			update_option( self::OPTION_CLIENT_SECRET, sanitize_text_field( $data['client_secret'] ) );
		}
		if ( isset( $data['site_url'] ) ) {
			update_option( self::OPTION_SITE_URL, esc_url_raw( $data['site_url'] ) );
		}
	}
}
