<?php defined( 'ABSPATH' ) || exit;
$summary  = MerdusSEO_Link_Checker::summary();
$has_data = $summary['total'] > 0;
?>
<div class="mseo-wrap">
	<?php include __DIR__ . '/partials/header.php'; ?>

	<div class="mseo-body">
		<div class="mseo-page-title">
			<h1>Kırık Link Tarayıcı</h1>
			<p class="mseo-subtitle">
				WP-Admin, <code>javascript:</code> ve sistem linkleri otomatik atlanır. 403 erişim engeli ayrı listelenir.
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
			<div style="margin-left:auto">
				<input type="text" id="link-search" class="mseo-input" placeholder="URL veya kaynak ara…" style="width:240px">
			</div>
		</div>

		<!-- Clickable summary cards -->
		<?php if ( $has_data ) : ?>
		<div class="mseo-link-cards" id="link-cards">
			<div class="mseo-link-card mseo-link-card--danger mseo-link-card--active" data-tab="broken">
				<div class="mseo-link-card__value"><?php echo esc_html( $summary['broken'] ); ?></div>
				<div class="mseo-link-card__label">Kırık Link</div>
				<div class="mseo-link-card__hint">404 · 5xx · Timeout</div>
			</div>
			<div class="mseo-link-card mseo-link-card--restricted" data-tab="restricted">
				<div class="mseo-link-card__value"><?php echo esc_html( $summary['restricted'] ); ?></div>
				<div class="mseo-link-card__label">Kısıtlı</div>
				<div class="mseo-link-card__hint">403 Forbidden</div>
			</div>
			<div class="mseo-link-card mseo-link-card--ok" data-tab="ok">
				<div class="mseo-link-card__value"><?php echo esc_html( $summary['ok'] ); ?></div>
				<div class="mseo-link-card__label">Sorunsuz</div>
				<div class="mseo-link-card__hint">2xx · 3xx</div>
			</div>
		</div>
		<?php endif; ?>

		<!-- Progress bar -->
		<div class="mseo-progress" id="link-progress" style="display:none">
			<div class="mseo-progress__bar"><div class="mseo-progress__fill mseo-progress__fill--animated"></div></div>
			<p>Linkler kontrol ediliyor — site büyüklüğüne bağlı olarak birkaç dakika sürebilir…</p>
		</div>

		<?php if ( $has_data ) : ?>

		<!-- Tab navigation -->
		<div class="mseo-tabs" id="link-tabs">
			<button class="mseo-tab mseo-tab--active" data-tab="broken">
				Kırık Linkler
				<span class="mseo-tab__badge mseo-tab__badge--danger"><?php echo esc_html( $summary['broken'] ); ?></span>
			</button>
			<button class="mseo-tab" data-tab="restricted">
				Kısıtlı (403)
				<span class="mseo-tab__badge mseo-tab__badge--warning"><?php echo esc_html( $summary['restricted'] ); ?></span>
			</button>
			<button class="mseo-tab" data-tab="ok">
				Sorunsuz
				<span class="mseo-tab__badge mseo-tab__badge--ok"><?php echo esc_html( $summary['ok'] ); ?></span>
			</button>
		</div>

		<!-- Table -->
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
				<?php
				/* Load all rows once and let JS/PHP filter by tab */
				$all_rows = MerdusSEO_Link_Checker::get_results( 'all' );
				foreach ( $all_rows as $row ) :
					$stype  = $row['status_type'] ?? MerdusSEO_Link_Checker::get_status_type( (int) $row['http_status'] );
					$status = (int) $row['http_status'];
				?>
					<tr class="mseo-row mseo-row--<?php echo esc_attr( $stype ); ?>"
					    data-tab="<?php echo esc_attr( $stype ); ?>"
					    data-type="<?php echo esc_attr( $row['link_type'] ); ?>"
					    data-source="<?php echo esc_attr( strtolower( $row['source_url'] ) ); ?>"
					    data-url="<?php echo esc_attr( strtolower( $row['link_url'] ) ); ?>">
						<td>
							<a href="<?php echo esc_url( $row['source_url'] ); ?>" target="_blank" class="mseo-link" title="<?php echo esc_attr( $row['source_url'] ); ?>">
								<?php echo esc_html( mb_substr( $row['source_url'], 0, 50 ) . ( mb_strlen( $row['source_url'] ) > 50 ? '…' : '' ) ); ?>
							</a>
						</td>
						<td>
							<a href="<?php echo esc_url( $row['link_url'] ); ?>" target="_blank"
							   class="mseo-link mseo-link--<?php echo esc_attr( $stype ); ?>"
							   title="<?php echo esc_attr( $row['link_url'] ); ?>">
								<?php echo esc_html( mb_substr( $row['link_url'], 0, 55 ) . ( mb_strlen( $row['link_url'] ) > 55 ? '…' : '' ) ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $row['anchor_text'] ?: '—' ); ?></td>
						<td>
							<span class="mseo-badge mseo-badge--<?php echo $row['link_type'] === 'internal' ? 'internal' : 'external'; ?>">
								<?php echo $row['link_type'] === 'internal' ? 'İç' : 'Dış'; ?>
							</span>
						</td>
						<td>
							<?php
							$label = $status === 0 ? 'Timeout' : (string) $status;
							$cls   = 'mseo-badge--' . ( $stype === 'broken' ? 'danger' : ( $stype === 'restricted' ? 'restricted' : 'success' ) );
							?>
							<span class="mseo-badge <?php echo esc_attr( $cls ); ?>"><?php echo esc_html( $label ); ?></span>
						</td>
						<td style="white-space:nowrap;font-size:12px;color:var(--mseo-muted)">
							<?php echo esc_html( substr( $row['last_checked'], 0, 16 ) ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<div class="mseo-table-empty" id="link-tab-empty" style="display:none">
				<p>Bu sekmede gösterilecek link yok.</p>
			</div>
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
