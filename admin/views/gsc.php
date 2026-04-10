<?php defined( 'ABSPATH' ) || exit;

$is_connected  = MerdusSEO_GSC::is_connected();
$is_configured = MerdusSEO_GSC::is_configured();
$client_id     = get_option( MerdusSEO_GSC::OPTION_CLIENT_ID, '' );
$client_secret = get_option( MerdusSEO_GSC::OPTION_CLIENT_SECRET, '' );
$site_url      = get_option( MerdusSEO_GSC::OPTION_SITE_URL, get_site_url() );

/* Handle OAuth callback on page load */
$oauth_notice = null;
if ( isset( $_GET['action'] ) && $_GET['action'] === 'oauth_callback' ) {
	$result = MerdusSEO_GSC::handle_callback();
	if ( ! empty( $result['success'] ) ) {
		$oauth_notice = [ 'type' => 'success', 'msg' => 'Google Search Console başarıyla bağlandı!' ];
		$is_connected = true;
	} else {
		$oauth_notice = [ 'type' => 'error', 'msg' => $result['error'] ?? 'Bağlantı sırasında bir hata oluştu.' ];
	}
}

/* Load verified sites if connected */
$sites = $is_connected ? MerdusSEO_GSC::get_sites() : [];
?>
<div class="mseo-wrap">
	<?php include __DIR__ . '/partials/header.php'; ?>

	<div class="mseo-body">
		<div class="mseo-page-title">
			<h1>Google Search Console</h1>
			<p class="mseo-subtitle">GSC hesabınızı bağlayın — gelecekte tıklama, gösterim ve sıralama verilerini doğrudan buradan analiz edin</p>
		</div>

		<?php if ( $oauth_notice ) : ?>
		<div class="mseo-notice mseo-notice--<?php echo esc_attr( $oauth_notice['type'] ); ?>" style="margin-bottom:20px">
			<?php echo esc_html( $oauth_notice['msg'] ); ?>
		</div>
		<?php endif; ?>

		<!-- Connection status banner -->
		<div class="mseo-gsc-status-banner <?php echo $is_connected ? 'mseo-gsc-status-banner--connected' : 'mseo-gsc-status-banner--disconnected'; ?>">
			<div class="mseo-gsc-status-banner__icon">
				<?php if ( $is_connected ) : ?>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
				<?php else : ?>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
				<?php endif; ?>
			</div>
			<div class="mseo-gsc-status-banner__body">
				<strong><?php echo $is_connected ? 'Bağlı' : 'Bağlı Değil'; ?></strong>
				<?php if ( $is_connected ) : ?>
				<p>Google Search Console hesabınız başarıyla bağlanmış. Site URL'sini aşağıdan seçin.</p>
				<?php else : ?>
				<p>Google Search Console verilerini kullanmak için aşağıdaki adımları takip edin.</p>
				<?php endif; ?>
			</div>
			<?php if ( $is_connected ) : ?>
			<form method="post" style="margin-left:auto">
				<?php wp_nonce_field( 'merdusseo_gsc_disconnect' ); ?>
				<input type="hidden" name="merdusseo_gsc_action" value="disconnect">
				<button type="submit" class="mseo-btn mseo-btn--secondary mseo-btn--sm" onclick="return confirm('GSC bağlantısını kesmek istediğinize emin misiniz?')">Bağlantıyı Kes</button>
			</form>
			<?php endif; ?>
		</div>

		<div class="mseo-gsc-layout">

			<!-- Left column: setup -->
			<div class="mseo-gsc-setup">

				<!-- Step 1: Google Cloud project -->
				<div class="mseo-settings-card">
					<div class="mseo-settings-card__header">
						<span class="mseo-step-badge">1</span>
						Google Cloud Projesi Oluşturun
					</div>
					<div class="mseo-settings-card__body">
						<ol class="mseo-setup-steps">
							<li><a href="https://console.cloud.google.com/projectcreate" target="_blank" class="mseo-link">Google Cloud Console</a>'da yeni bir proje oluşturun.</li>
							<li><strong>APIs & Services → Enable APIs</strong> bölümünden <strong>Google Search Console API</strong>'yi etkinleştirin.</li>
							<li><strong>APIs & Services → Credentials → Create Credentials → OAuth 2.0 Client ID</strong> seçin.</li>
							<li>Uygulama türü olarak <strong>Web application</strong> seçin.</li>
							<li>Authorized redirect URI olarak şunu ekleyin:<br>
								<code class="mseo-code"><?php echo esc_html( MerdusSEO_GSC::get_redirect_uri() ); ?></code>
							</li>
							<li>Oluşturulan <strong>Client ID</strong> ve <strong>Client Secret</strong>'ı aşağıya yapıştırın.</li>
						</ol>
					</div>
				</div>

				<!-- Step 2: Credentials form -->
				<div class="mseo-settings-card">
					<div class="mseo-settings-card__header">
						<span class="mseo-step-badge">2</span>
						API Kimlik Bilgilerini Girin
					</div>
					<div class="mseo-settings-card__body">
						<form id="gsc-credentials-form">
							<div class="mseo-form-group">
								<label class="mseo-label">Client ID</label>
								<input type="text" name="client_id" class="mseo-input mseo-input--full"
								       value="<?php echo esc_attr( $client_id ); ?>"
								       placeholder="xxxx.apps.googleusercontent.com">
							</div>
							<div class="mseo-form-group">
								<label class="mseo-label">Client Secret</label>
								<div class="mseo-api-key-wrap">
									<input type="password" name="client_secret" class="mseo-input mseo-input--full"
									       value="<?php echo esc_attr( $client_secret ); ?>"
									       placeholder="GOCSPX-…">
									<button type="button" class="mseo-btn mseo-btn--secondary mseo-btn--sm" id="toggle-gsc-secret">Göster</button>
								</div>
							</div>
							<button type="submit" class="mseo-btn mseo-btn--primary">Kaydet</button>
						</form>
					</div>
				</div>

				<!-- Step 3: Connect -->
				<?php if ( $is_configured && ! $is_connected ) : ?>
				<div class="mseo-settings-card">
					<div class="mseo-settings-card__header">
						<span class="mseo-step-badge">3</span>
						Google Hesabınızla Yetkilendirin
					</div>
					<div class="mseo-settings-card__body">
						<p style="margin-bottom:16px;color:var(--mseo-muted);font-size:13.5px">
							Aşağıdaki butona tıklayarak Google hesabınıza yönlendirileceksiniz. Search Console verilerinize <strong>salt okunur</strong> erişim izni verin.
						</p>
						<a href="<?php echo esc_url( MerdusSEO_GSC::get_auth_url() ); ?>" class="mseo-btn mseo-btn--primary">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12.545 10.239v3.821h5.445c-.712 2.315-2.647 3.972-5.445 3.972a6.033 6.033 0 1 1 0-12.064c1.498 0 2.866.549 3.921 1.453l2.814-2.814A9.969 9.969 0 0 0 12.545 2C7.021 2 2.543 6.477 2.543 12s4.478 10 10.002 10c8.396 0 10.249-7.85 9.426-11.748l-9.426-.013z"/></svg>
							Google ile Bağlan
						</a>
					</div>
				</div>
				<?php endif; ?>
			</div>

			<!-- Right column: connected state / site picker -->
			<?php if ( $is_connected ) : ?>
			<div class="mseo-gsc-data">

				<!-- Site selector -->
				<div class="mseo-settings-card">
					<div class="mseo-settings-card__header">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
						Analiz Edilecek Site
					</div>
					<div class="mseo-settings-card__body">
						<?php if ( ! empty( $sites ) ) : ?>
						<form id="gsc-site-form">
							<div class="mseo-form-group">
								<label class="mseo-label">GSC'deki Doğrulanmış Siteleriniz</label>
								<select name="site_url" class="mseo-select mseo-select--full">
									<?php foreach ( $sites as $site ) : ?>
									<option value="<?php echo esc_attr( $site['siteUrl'] ); ?>"
									        <?php selected( $site_url, $site['siteUrl'] ); ?>>
										<?php echo esc_html( $site['siteUrl'] ); ?>
										<?php if ( $site['permissionLevel'] !== 'siteOwner' ) echo ' (' . esc_html( $site['permissionLevel'] ) . ')'; ?>
									</option>
									<?php endforeach; ?>
								</select>
							</div>
							<button type="submit" class="mseo-btn mseo-btn--primary">Siteyi Seç & Kaydet</button>
						</form>
						<?php else : ?>
						<p style="color:var(--mseo-muted)">GSC'de doğrulanmış site bulunamadı. <a href="https://search.google.com/search-console" target="_blank" class="mseo-link">Google Search Console</a>'dan sitenizi doğrulayın.</p>
						<?php endif; ?>
					</div>
				</div>

				<!-- Future data placeholder -->
				<div class="mseo-settings-card">
					<div class="mseo-settings-card__header">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
						Yakında: GSC Verileri
					</div>
					<div class="mseo-settings-card__body">
						<div class="mseo-gsc-coming-soon">
							<div class="mseo-gsc-coming-soon__item">
								<strong>Organik Tıklamalar</strong>
								<p>Hangi sayfalarınız en fazla tıklama alıyor?</p>
							</div>
							<div class="mseo-gsc-coming-soon__item">
								<strong>Gösterimler & CTR</strong>
								<p>Düşük CTR'li sayfaları tespit edin.</p>
							</div>
							<div class="mseo-gsc-coming-soon__item">
								<strong>Ortalama Sıralama</strong>
								<p>İlk sayfaya yakın ama girmemiş sorguları görün.</p>
							</div>
							<div class="mseo-gsc-coming-soon__item">
								<strong>Keyword Fırsatları</strong>
								<p>Mevcut meta title/desc ile karşılaştırın, AI ile optimize edin.</p>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>
		</div><!-- .mseo-gsc-layout -->
	</div>
</div>
