<?php defined( 'ABSPATH' ) || exit;
$cached  = get_transient( 'merdusseo_cannibalization_results' );
$results = is_array( $cached ) ? $cached : [];
$summary = MerdusSEO_Cannibalization::summary( $results );
?>
<div class="mseo-wrap">
	<?php include __DIR__ . '/partials/header.php'; ?>

	<div class="mseo-body">
		<div class="mseo-page-title">
			<h1>Keyword Yamyamlığı</h1>
			<p class="mseo-subtitle">Aynı anahtar kelimeyi hedefleyen blog yazılarını tespit edin ve düzeltin</p>
		</div>

		<!-- Toolbar -->
		<div class="mseo-toolbar">
			<button class="mseo-btn mseo-btn--primary" id="btn-scan-cannibalization">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
				Blog Yazılarını Tara
			</button>
			<?php if ( $results ) : ?>
			<div style="margin-left:auto">
				<input type="text" id="can-search" class="mseo-input" placeholder="Keyword ara…" style="width:220px">
			</div>
			<?php endif; ?>
		</div>

		<!-- Summary cards -->
		<?php if ( $summary['groups'] > 0 ) : ?>
		<div class="mseo-link-cards" style="max-width:500px">
			<div class="mseo-link-card mseo-link-card--danger">
				<div class="mseo-link-card__value"><?php echo esc_html( $summary['groups'] ); ?></div>
				<div class="mseo-link-card__label">Çakışan Keyword Grubu</div>
				<div class="mseo-link-card__hint">Ayrı bir strateji gerekiyor</div>
			</div>
			<div class="mseo-link-card mseo-link-card--restricted">
				<div class="mseo-link-card__value"><?php echo esc_html( $summary['affected_posts'] ); ?></div>
				<div class="mseo-link-card__label">Etkilenen Yazı</div>
				<div class="mseo-link-card__hint">Güncelleme önerilen</div>
			</div>
		</div>

		<!-- Info box -->
		<div class="mseo-info-banner" style="margin-bottom:20px">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
			<div>
				<strong>Keyword yamyamlığı nedir?</strong> Aynı anahtar kelimeyi birden fazla sayfanın hedeflemesi, Google'ın hangi sayfayı öne çıkaracağını bilememesine ve her iki sayfanın da sıralamada zarar görmesine yol açar.
				Tespit kaynakları: <strong>odak kelime</strong> (Yoast / RankMath), <strong>etiket</strong> ve <strong>kategori</strong>.
			</div>
		</div>
		<?php endif; ?>

		<!-- Progress -->
		<div class="mseo-progress" id="can-progress" style="display:none">
			<div class="mseo-progress__bar"><div class="mseo-progress__fill mseo-progress__fill--animated"></div></div>
			<p>Blog yazıları analiz ediliyor…</p>
		</div>

		<!-- Results -->
		<div id="can-results">
		<?php if ( ! empty( $results ) ) : ?>

			<?php foreach ( $results as $group ) : ?>
			<div class="mseo-can-group" data-keyword="<?php echo esc_attr( strtolower( $group['keyword'] ) ); ?>">

				<!-- Group header -->
				<div class="mseo-can-group__header">
					<div class="mseo-can-group__keyword">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17.63 5.84C17.27 5.33 16.67 5 16 5L5 5.01C3.9 5.01 3 5.9 3 7v10c0 1.1.9 1.99 2 1.99L16 19c.67 0 1.27-.33 1.63-.84L22 12l-4.37-6.16z"/></svg>
						<?php echo esc_html( $group['keyword'] ); ?>
					</div>
					<div style="display:flex;align-items:center;gap:8px">
						<!-- Source badges -->
						<?php foreach ( $group['sources'] as $src ) : ?>
						<span class="mseo-badge mseo-can-source--<?php echo esc_attr( MerdusSEO_Cannibalization::source_color( $src ) ); ?>">
							<?php echo esc_html( MerdusSEO_Cannibalization::source_label( $src ) ); ?>
						</span>
						<?php endforeach; ?>
						<span class="mseo-badge mseo-badge--danger"><?php echo esc_html( $group['count'] ); ?> yazı</span>
					</div>
				</div>

				<!-- Why + fix recommendation (collapsible) -->
				<div class="mseo-can-group__info">
					<div class="mseo-can-info-row">
						<div class="mseo-can-info-block mseo-can-info-block--why">
							<div class="mseo-can-info-block__title">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
								Neden tespit edildi?
							</div>
							<ul>
								<?php foreach ( $group['recommendation']['why'] as $why ) : ?>
								<li><?php echo wp_kses_post( $why ); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
						<div class="mseo-can-info-block mseo-can-info-block--fix">
							<div class="mseo-can-info-block__title">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
								Nasıl düzeltilir?
							</div>
							<ul>
								<?php foreach ( $group['recommendation']['fixes'] as $fix ) : ?>
								<li><?php echo wp_kses_post( $fix ); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
				</div>

				<!-- Affected posts table -->
				<table class="mseo-table mseo-table--compact">
					<thead>
						<tr>
							<th>Post Başlığı</th>
							<th>Kaynak</th>
							<th>URL</th>
							<th>Tarih</th>
							<th>Düzenle</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $group['posts'] as $p ) : ?>
						<tr>
							<td style="font-weight:600"><?php echo esc_html( $p['title'] ); ?></td>
							<td>
								<span class="mseo-badge mseo-can-source--<?php echo esc_attr( MerdusSEO_Cannibalization::source_color( $p['source'] ) ); ?>">
									<?php echo esc_html( MerdusSEO_Cannibalization::source_label( $p['source'] ) ); ?>
								</span>
							</td>
							<td>
								<a href="<?php echo esc_url( $p['url'] ); ?>" target="_blank" class="mseo-link">
									<?php echo esc_html( mb_substr( $p['url'], 0, 45 ) . '…' ); ?>
								</a>
							</td>
							<td style="white-space:nowrap;color:var(--mseo-muted);font-size:12px">
								<?php echo esc_html( date( 'd.m.Y', strtotime( $p['date'] ) ) ); ?>
							</td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $p['post_id'] . '&action=edit' ) ); ?>"
								   class="mseo-btn mseo-btn--secondary mseo-btn--sm" target="_blank">Düzenle</a>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endforeach; ?>

		<?php else : ?>
			<div class="mseo-empty" id="can-empty">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
				<h3>Henüz analiz yapılmadı</h3>
				<p>"Blog Yazılarını Tara" butonuna tıklayarak keyword yamyamlığı analizini başlatın.</p>
			</div>
		<?php endif; ?>
		</div>
	</div>
</div>
