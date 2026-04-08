<?php defined( 'ABSPATH' ) || exit; ?>
<div class="mseo-wrap">
	<?php include __DIR__ . '/partials/header.php'; ?>

	<div class="mseo-body">
		<div class="mseo-page-title">
			<h1>Dashboard</h1>
			<p class="mseo-subtitle">Sitenizin SEO sağlığına genel bakış</p>
		</div>

		<!-- Quick-action cards -->
		<div class="mseo-card-grid mseo-card-grid--4">

			<div class="mseo-card mseo-card--action" id="dash-meta-card">
				<div class="mseo-card__icon mseo-card__icon--blue">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>
				</div>
				<div class="mseo-card__body">
					<h3>Meta Analizi</h3>
					<p>Title & description sorunlarını tara</p>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=merdusseo-meta' ) ); ?>" class="mseo-card__link">Analizi Başlat →</a>
			</div>

			<div class="mseo-card mseo-card--action" id="dash-links-card">
				<div class="mseo-card__icon mseo-card__icon--red">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>
				</div>
				<div class="mseo-card__body">
					<h3>Kırık Linkler</h3>
					<p>İç ve dış kırık linkleri tara</p>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=merdusseo-links' ) ); ?>" class="mseo-card__link">Taramayı Başlat →</a>
			</div>

			<div class="mseo-card mseo-card--action" id="dash-can-card">
				<div class="mseo-card__icon mseo-card__icon--orange">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
				</div>
				<div class="mseo-card__body">
					<h3>Keyword Yamyamlığı</h3>
					<p>Çakışan keyword'leri tespit et</p>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=merdusseo-cannibalization' ) ); ?>" class="mseo-card__link">Analizi Başlat →</a>
			</div>

			<div class="mseo-card mseo-card--action" id="dash-settings-card">
				<div class="mseo-card__icon mseo-card__icon--purple">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
				</div>
				<div class="mseo-card__body">
					<h3>Ayarlar</h3>
					<p>AI & tarama ayarlarını yapılandır</p>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=merdusseo-settings' ) ); ?>" class="mseo-card__link">Ayarlara Git →</a>
			</div>
		</div>

		<!-- Stats row (loaded after scans) -->
		<div class="mseo-stats-row" id="dash-stats">
			<div class="mseo-stat-card">
				<div class="mseo-stat-card__value" id="stat-total-posts">–</div>
				<div class="mseo-stat-card__label">Toplam Sayfa/Post</div>
			</div>
			<div class="mseo-stat-card mseo-stat-card--danger">
				<div class="mseo-stat-card__value" id="stat-meta-issues">–</div>
				<div class="mseo-stat-card__label">Meta Sorunu</div>
			</div>
			<div class="mseo-stat-card mseo-stat-card--danger">
				<div class="mseo-stat-card__value" id="stat-broken-links">–</div>
				<div class="mseo-stat-card__label">Kırık Link</div>
			</div>
			<div class="mseo-stat-card mseo-stat-card--warning">
				<div class="mseo-stat-card__value" id="stat-cannibalization">–</div>
				<div class="mseo-stat-card__label">Keyword Çakışması</div>
			</div>
		</div>

		<!-- Info banner -->
		<div class="mseo-info-banner">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
			<div>
				<strong>MerdusSEO v<?php echo esc_html( MERDUSSEO_VERSION ); ?></strong> — Ayrıntılı analizler için sol menüdeki bölümlere gidin.
				AI düzeltme özelliğini kullanmak için <a href="<?php echo esc_url( admin_url( 'admin.php?page=merdusseo-settings' ) ); ?>">Ayarlar</a> sayfasından API anahtarınızı girin.
			</div>
		</div>
	</div>
</div>
