<?php defined( 'ABSPATH' ) || exit;
$summary = MerdusSEO_Link_Checker::summary();
$results = MerdusSEO_Link_Checker::get_results( 'problems' );
?>
<div class="mseo-wrap">
	<?php include __DIR__ . '/partials/header.php'; ?>

	<div class="mseo-body">
		<div class="mseo-page-title">
			<h1>Kırık Link Tarayıcı</h1>
			<p class="mseo-subtitle">
				Yalnızca sorunlu linkler gösterilir.
				<span class="mseo-hint-inline">WP-Admin, javascript: ve benzeri sistem linkleri otomatik atlanır.</span>
			</p>
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
					<option value="">Tüm Sorunlar</option>
					<option value="broken">Yalnızca Kırık (4xx/5xx)</option>
					<option value="restricted">Yalnızca Kısıtlı (403)</option>
					<option value="internal">İç Linkler</option>
					<option value="external">Dış Linkler</option>
				</select>
				<input type="text" id="link-search" class="mseo-input" placeholder="URL veya kaynak ara...">
			</div>
		</div>

		<!-- Summary bar -->
		<?php if ( $summary['problems'] > 0 || $summary['total'] > 0 ) : ?>
		<div class="mseo-summary-bar">
			<div class="mseo-summary-item mseo-summary-item--danger">
				<span class="mseo-summary-item__value"><?php echo esc_html( $summary['broken'] ); ?></span>
				<span class="mseo-summary-item__label">Kırık Link</span>
			</div>
			<div class="mseo-summary-item mseo-summary-item--warning">
				<span class="mseo-summary-item__value"><?php echo esc_html( $summary['restricted'] ); ?></span>
				<span class="mseo-summary-item__label">Kısıtlı (403)</span>
			</div>
			<div class="mseo-summary-item">
				<span class="mseo-summary-item__value"><?php echo esc_html( $summary['internal'] ); ?></span>
				<span class="mseo-summary-item__label">Sorunlu İç</span>
			</div>
			<div class="mseo-summary-item">
				<span class="mseo-summary-item__value"><?php echo esc_html( $summary['external'] ); ?></span>
				<span class="mseo-summary-item__label">Sorunlu Dış</span>
			</div>
		</div>

		<!-- Legend -->
		<div class="mseo-legend">
			<span class="mseo-legend__item">
				<span class="mseo-badge mseo-badge--danger">404 / 5xx / Timeout</span>
				Kırık — kaldırılabilir
			</span>
			<span class="mseo-legend__item">
				<span class="mseo-badge mseo-badge--restricted">403</span>
				Kısıtlı — karşı taraf erişime izin vermiyor, kırık olmayabilir
			</span>
		</div>
		<?php endif; ?>

		<!-- Progress -->
		<div class="mseo-progress" id="link-progress" style="display:none">
			<div class="mseo-progress__bar"><div class="mseo-progress__fill mseo-progress__fill--animated"></div></div>
			<p>Linkler kontrol ediliyor — bu işlem site büyüklüğüne bağlı olarak birkaç dakika sürebilir...</p>
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
				<?php foreach ( $results as $row ) :
					$stype = $row['status_type'] ?? MerdusSEO_Link_Checker::get_status_type( (int) $row['http_status'] );
				?>
					<tr class="mseo-row mseo-row--<?php echo esc_attr( $stype ); ?>"
					    data-type="<?php echo esc_attr( $row['link_type'] ); ?>"
					    data-status="<?php echo esc_attr( $stype ); ?>"
					    data-source="<?php echo esc_attr( strtolower( $row['source_url'] ) ); ?>"
					    data-url="<?php echo esc_attr( strtolower( $row['link_url'] ) ); ?>">
						<td>
							<a href="<?php echo esc_url( $row['source_url'] ); ?>" target="_blank" class="mseo-link">
								<?php echo esc_html( mb_substr( $row['source_url'], 0, 55 ) ); ?>
							</a>
						</td>
						<td class="mseo-cell--url">
							<a href="<?php echo esc_url( $row['link_url'] ); ?>" target="_blank"
							   class="mseo-link <?php echo $stype === 'broken' ? 'mseo-link--broken' : ( $stype === 'restricted' ? 'mseo-link--restricted' : '' ); ?>">
								<?php echo esc_html( mb_substr( $row['link_url'], 0, 60 ) ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $row['anchor_text'] ?: '—' ); ?></td>
						<td>
							<span class="mseo-badge mseo-badge--<?php echo $row['link_type'] === 'internal' ? 'internal' : 'external'; ?>">
								<?php echo $row['link_type'] === 'internal' ? 'İç' : 'Dış'; ?>
							</span>
						</td>
						<td>
							<?php if ( $stype === 'broken' ) : ?>
								<span class="mseo-badge mseo-badge--danger">
									<?php echo $row['http_status'] === '0' || $row['http_status'] === 0 ? 'Timeout' : esc_html( $row['http_status'] ); ?>
								</span>
							<?php elseif ( $stype === 'restricted' ) : ?>
								<span class="mseo-badge mseo-badge--restricted">403 Kısıtlı</span>
							<?php else : ?>
								<span class="mseo-badge mseo-badge--success"><?php echo esc_html( $row['http_status'] ); ?></span>
							<?php endif; ?>
						</td>
						<td style="white-space:nowrap;font-size:12px"><?php echo esc_html( substr( $row['last_checked'], 0, 16 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php else : ?>
		<div class="mseo-empty" id="link-empty">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>
			<?php if ( $summary['total'] > 0 ) : ?>
				<h3>Sorunlu link bulunamadı</h3>
				<p>Tüm linkler sağlıklı görünüyor. Yeni bir tarama için butona basın.</p>
			<?php else : ?>
				<h3>Henüz link taraması yapılmadı</h3>
				<p>"Tüm Linkleri Tara" butonuna tıklayarak taramayı başlatın.</p>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	</div>
</div>
