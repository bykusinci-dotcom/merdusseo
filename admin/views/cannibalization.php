<?php defined( 'ABSPATH' ) || exit;
$cached = get_transient( 'merdusseo_cannibalization_results' );
$results = $cached ?: [];
$summary = MerdusSEO_Cannibalization::summary( $results );
?>
<div class="mseo-wrap">
	<?php include __DIR__ . '/partials/header.php'; ?>

	<div class="mseo-body">
		<div class="mseo-page-title">
			<h1>Keyword Yamyamlığı</h1>
			<p class="mseo-subtitle">Aynı keyword'ü hedefleyen blog yazılarını tespit edin</p>
		</div>

		<!-- Toolbar -->
		<div class="mseo-toolbar">
			<button class="mseo-btn mseo-btn--primary" id="btn-scan-cannibalization">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
				Blog Yazılarını Tara
			</button>
			<input type="text" id="can-search" class="mseo-input" placeholder="Keyword ara...">
		</div>

		<?php if ( $summary['groups'] > 0 ) : ?>
		<div class="mseo-summary-bar">
			<div class="mseo-summary-item mseo-summary-item--danger">
				<span class="mseo-summary-item__value"><?php echo esc_html( $summary['groups'] ); ?></span>
				<span class="mseo-summary-item__label">Çakışan Keyword Grubu</span>
			</div>
			<div class="mseo-summary-item mseo-summary-item--warning">
				<span class="mseo-summary-item__value"><?php echo esc_html( $summary['affected_posts'] ); ?></span>
				<span class="mseo-summary-item__label">Etkilenen Post</span>
			</div>
		</div>
		<?php endif; ?>

		<!-- Progress -->
		<div class="mseo-progress" id="can-progress" style="display:none">
			<div class="mseo-progress__bar"><div class="mseo-progress__fill mseo-progress__fill--animated"></div></div>
			<p>Blog yazıları analiz ediliyor...</p>
		</div>

		<!-- Results -->
		<div id="can-results">
		<?php if ( ! empty( $results ) ) : ?>
			<?php foreach ( $results as $group ) : ?>
			<div class="mseo-can-group" data-keyword="<?php echo esc_attr( strtolower( $group['keyword'] ) ); ?>">
				<div class="mseo-can-group__header">
					<div class="mseo-can-group__keyword">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17.63 5.84C17.27 5.33 16.67 5 16 5L5 5.01C3.9 5.01 3 5.9 3 7v10c0 1.1.9 1.99 2 1.99L16 19c.67 0 1.27-.33 1.63-.84L22 12l-4.37-6.16z"/></svg>
						<?php echo esc_html( $group['keyword'] ); ?>
					</div>
					<span class="mseo-badge mseo-badge--danger"><?php echo esc_html( $group['count'] ); ?> post</span>
				</div>
				<table class="mseo-table mseo-table--compact">
					<thead>
						<tr><th>Post Başlığı</th><th>URL</th><th>Tarih</th></tr>
					</thead>
					<tbody>
					<?php foreach ( $group['posts'] as $p ) : ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $p['post_id'] . '&action=edit' ) ); ?>" class="mseo-link">
									<?php echo esc_html( $p['title'] ); ?>
								</a>
							</td>
							<td><a href="<?php echo esc_url( $p['url'] ); ?>" target="_blank" class="mseo-link"><?php echo esc_html( mb_substr( $p['url'], 0, 50 ) ); ?></a></td>
							<td><?php echo esc_html( date( 'd.m.Y', strtotime( $p['date'] ) ) ); ?></td>
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
