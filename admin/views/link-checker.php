<?php defined( 'ABSPATH' ) || exit;
$summary = MerdusSEO_Link_Checker::summary();
$results = MerdusSEO_Link_Checker::get_results();
?>
<div class="mseo-wrap">
	<?php include __DIR__ . '/partials/header.php'; ?>

	<div class="mseo-body">
		<div class="mseo-page-title">
			<h1>Kırık Link Tarayıcı</h1>
			<p class="mseo-subtitle">Site içindeki tüm iç ve dış linkleri kontrol edin</p>
		</div>

		<!-- Toolbar -->
		<div class="mseo-toolbar">
			<button class="mseo-btn mseo-btn--primary" id="btn-scan-links">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
				Tüm Linkleri Tara
			</button>

			<?php if ( $summary['broken'] > 0 ) : ?>
			<button class="mseo-btn mseo-btn--danger" id="btn-remove-broken">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
				Kırık Linkleri Kaldır (<span id="broken-count"><?php echo esc_html( $summary['broken'] ); ?></span>)
			</button>
			<?php endif; ?>

			<div class="mseo-toolbar__filters">
				<select id="link-filter" class="mseo-select">
					<option value="">Tümü</option>
					<option value="broken">Kırık</option>
					<option value="internal">İç Link</option>
					<option value="external">Dış Link</option>
				</select>
				<input type="text" id="link-search" class="mseo-input" placeholder="URL veya kaynak ara...">
			</div>
		</div>

		<!-- Summary bar -->
		<?php if ( $summary['total'] > 0 ) : ?>
		<div class="mseo-summary-bar">
			<div class="mseo-summary-item mseo-summary-item--ok">
				<span class="mseo-summary-item__value"><?php echo esc_html( $summary['ok'] ); ?></span>
				<span class="mseo-summary-item__label">Aktif</span>
			</div>
			<div class="mseo-summary-item mseo-summary-item--danger">
				<span class="mseo-summary-item__value"><?php echo esc_html( $summary['broken'] ); ?></span>
				<span class="mseo-summary-item__label">Kırık</span>
			</div>
			<div class="mseo-summary-item">
				<span class="mseo-summary-item__value"><?php echo esc_html( $summary['internal'] ); ?></span>
				<span class="mseo-summary-item__label">İç Link</span>
			</div>
			<div class="mseo-summary-item">
				<span class="mseo-summary-item__value"><?php echo esc_html( $summary['external'] ); ?></span>
				<span class="mseo-summary-item__label">Dış Link</span>
			</div>
			<div class="mseo-summary-item">
				<span class="mseo-summary-item__value"><?php echo esc_html( $summary['total'] ); ?></span>
				<span class="mseo-summary-item__label">Toplam</span>
			</div>
		</div>
		<?php endif; ?>

		<!-- Progress -->
		<div class="mseo-progress" id="link-progress" style="display:none">
			<div class="mseo-progress__bar"><div class="mseo-progress__fill mseo-progress__fill--animated"></div></div>
			<p>Linkler kontrol ediliyor, bu işlem birkaç dakika sürebilir...</p>
		</div>

		<!-- Table -->
		<?php if ( ! empty( $results ) ) : ?>
		<div class="mseo-table-wrap" id="link-table-wrap">
			<table class="mseo-table" id="link-table">
				<thead>
					<tr>
						<th>Kaynak Sayfa</th>
						<th>Link URL</th>
						<th>Anchor Text</th>
						<th>Tür</th>
						<th>HTTP Durum</th>
						<th>Son Kontrol</th>
					</tr>
				</thead>
				<tbody id="link-table-body">
				<?php foreach ( $results as $row ) : ?>
					<tr class="mseo-row <?php echo $row['is_broken'] ? 'mseo-row--broken' : ''; ?>"
					    data-type="<?php echo esc_attr( $row['link_type'] ); ?>"
					    data-broken="<?php echo esc_attr( $row['is_broken'] ); ?>"
					    data-source="<?php echo esc_attr( strtolower( $row['source_url'] ) ); ?>"
					    data-url="<?php echo esc_attr( strtolower( $row['link_url'] ) ); ?>">
						<td>
							<a href="<?php echo esc_url( $row['source_url'] ); ?>" target="_blank" class="mseo-link">
								<?php echo esc_html( wp_trim_words( $row['source_url'], 5 ) ); ?>
							</a>
						</td>
						<td class="mseo-cell--url">
							<a href="<?php echo esc_url( $row['link_url'] ); ?>" target="_blank" class="mseo-link <?php echo $row['is_broken'] ? 'mseo-link--broken' : ''; ?>">
								<?php echo esc_html( mb_substr( $row['link_url'], 0, 60 ) ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $row['anchor_text'] ?: '—' ); ?></td>
						<td>
							<span class="mseo-badge mseo-badge--<?php echo esc_attr( $row['link_type'] ); ?>">
								<?php echo $row['link_type'] === 'internal' ? 'İç' : 'Dış'; ?>
							</span>
						</td>
						<td>
							<span class="mseo-badge <?php echo $row['is_broken'] ? 'mseo-badge--danger' : 'mseo-badge--success'; ?>">
								<?php echo $row['http_status'] === 0 ? 'Zaman Aşımı' : esc_html( $row['http_status'] ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $row['last_checked'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php else : ?>
		<div class="mseo-empty" id="link-empty">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>
			<h3>Henüz link taraması yapılmadı</h3>
			<p>"Tüm Linkleri Tara" butonuna tıklayarak taramayı başlatın.</p>
		</div>
		<?php endif; ?>
	</div>
</div>
