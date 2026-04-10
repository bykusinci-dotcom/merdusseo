<?php defined( 'ABSPATH' ) || exit;
/* Load cached results so data survives page refresh */
$cached         = get_transient( 'merdusseo_meta_results' );
$cached_results = is_array( $cached ) ? $cached : [];
$cached_summary = $cached_results ? MerdusSEO_Meta_Analyzer::summary( $cached_results ) : null;
?>
<div class="mseo-wrap">
	<?php include __DIR__ . '/partials/header.php'; ?>

	<div class="mseo-body">
		<div class="mseo-page-title">
			<h1>Meta Analizi</h1>
			<p class="mseo-subtitle">Meta title, description ve H1 başlık sorunlarını tespit edin</p>
		</div>

		<!-- Toolbar -->
		<div class="mseo-toolbar">
			<button class="mseo-btn mseo-btn--primary" id="btn-scan-meta">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
				Tüm Siteyi Tara
			</button>
			<button class="mseo-btn mseo-btn--secondary" id="btn-export-csv" <?php echo ! $cached_results ? 'disabled' : ''; ?>>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
				CSV İndir
			</button>
			<label class="mseo-btn mseo-btn--secondary" for="csv-import-input">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
				CSV İmport
			</label>
			<input type="file" id="csv-import-input" accept=".csv" style="display:none">
			<div style="margin-left:auto">
				<input type="text" id="meta-search" class="mseo-input" placeholder="Başlık ara…" style="width:200px">
			</div>
		</div>

		<!-- Progress -->
		<div class="mseo-progress" id="meta-progress" style="display:none">
			<div class="mseo-progress__bar"><div class="mseo-progress__fill mseo-progress__fill--animated"></div></div>
			<p>Taranıyor, lütfen bekleyin…</p>
		</div>

		<!-- Summary + Tab bar (hidden until scan) -->
		<div id="meta-summary-wrap" style="<?php echo $cached_results ? '' : 'display:none'; ?>">

			<!-- Clickable summary cards -->
			<div class="mseo-link-cards" id="meta-cards">
				<div class="mseo-link-card mseo-link-card--danger mseo-link-card--active" data-meta-tab="issues">
					<div class="mseo-link-card__value" id="mc-issues"><?php echo esc_html( $cached_summary['issues'] ?? 0 ); ?></div>
					<div class="mseo-link-card__label">Sorunlu</div>
					<div class="mseo-link-card__hint">Tüm hatalar</div>
				</div>
				<div class="mseo-link-card mseo-link-card--ok" data-meta-tab="ok">
					<div class="mseo-link-card__value" id="mc-ok"><?php echo esc_html( $cached_summary['ok'] ?? 0 ); ?></div>
					<div class="mseo-link-card__label">Sorunsuz</div>
					<div class="mseo-link-card__hint">Tümü geçerli</div>
				</div>
				<div class="mseo-link-card" data-meta-tab="missing_title">
					<div class="mseo-link-card__value" id="mc-mt"><?php echo esc_html( $cached_summary['counts']['missing_title'] ?? 0 ); ?></div>
					<div class="mseo-link-card__label">Eksik Title</div>
					<div class="mseo-link-card__hint">&nbsp;</div>
				</div>
				<div class="mseo-link-card" data-meta-tab="long_title">
					<div class="mseo-link-card__value" id="mc-lt"><?php echo esc_html( ( $cached_summary['counts']['long_title'] ?? 0 ) + ( $cached_summary['counts']['short_title'] ?? 0 ) ); ?></div>
					<div class="mseo-link-card__label">Uzun/Kısa Title</div>
					<div class="mseo-link-card__hint">&nbsp;</div>
				</div>
				<div class="mseo-link-card" data-meta-tab="missing_desc">
					<div class="mseo-link-card__value" id="mc-md"><?php echo esc_html( $cached_summary['counts']['missing_desc'] ?? 0 ); ?></div>
					<div class="mseo-link-card__label">Eksik Açıklama</div>
					<div class="mseo-link-card__hint">&nbsp;</div>
				</div>
				<div class="mseo-link-card" data-meta-tab="long_desc">
					<div class="mseo-link-card__value" id="mc-ld"><?php echo esc_html( ( $cached_summary['counts']['long_desc'] ?? 0 ) + ( $cached_summary['counts']['short_desc'] ?? 0 ) ); ?></div>
					<div class="mseo-link-card__label">Uzun/Kısa Açıklama</div>
					<div class="mseo-link-card__hint">&nbsp;</div>
				</div>
				<div class="mseo-link-card" data-meta-tab="duplicate_h1_title">
					<div class="mseo-link-card__value" id="mc-dup"><?php echo esc_html( $cached_summary['counts']['duplicate_h1_title'] ?? 0 ); ?></div>
					<div class="mseo-link-card__label">Duplicate H1</div>
					<div class="mseo-link-card__hint">&nbsp;</div>
				</div>
			</div>

			<!-- Active tab label -->
			<div class="mseo-tabs">
				<button class="mseo-tab mseo-tab--active" data-meta-tab="issues">Sorunlu</button>
				<button class="mseo-tab" data-meta-tab="ok">Sorunsuz</button>
				<button class="mseo-tab" data-meta-tab="missing_title">Eksik Title</button>
				<button class="mseo-tab" data-meta-tab="long_title">Uzun/Kısa Title</button>
				<button class="mseo-tab" data-meta-tab="missing_desc">Eksik Açıklama</button>
				<button class="mseo-tab" data-meta-tab="long_desc">Uzun/Kısa Açıklama</button>
				<button class="mseo-tab" data-meta-tab="duplicate_h1_title">Duplicate H1</button>
				<button class="mseo-tab" data-meta-tab="all">Tümü</button>
			</div>
		</div>

		<!-- Table -->
		<div class="mseo-table-wrap" id="meta-table-wrap" style="<?php echo $cached_results ? '' : 'display:none'; ?>">
			<table class="mseo-table" id="meta-table">
				<thead>
					<tr>
						<th>Sayfa / Post</th>
						<th>Meta Title <small>(karakter)</small></th>
						<th>Meta Description <small>(karakter)</small></th>
						<th>H1</th>
						<th>Sorunlar</th>
						<th>İşlemler</th>
					</tr>
				</thead>
				<tbody id="meta-table-body">
					<!-- filled by JS -->
				</tbody>
			</table>
			<div class="mseo-table-empty" id="meta-tab-empty" style="display:none">
				<p>Bu filtreye uygun kayıt bulunamadı.</p>
			</div>
		</div>

		<!-- Empty state -->
		<div class="mseo-empty" id="meta-empty" style="<?php echo $cached_results ? 'display:none' : ''; ?>">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
			<h3>Henüz tarama yapılmadı</h3>
			<p>"Tüm Siteyi Tara" butonuna tıklayarak meta analizini başlatın.</p>
		</div>
	</div>
</div>

<!-- Edit Modal -->
<div class="mseo-modal-overlay" id="edit-modal" style="display:none">
	<div class="mseo-modal">
		<div class="mseo-modal__header">
			<h2 id="edit-modal-title">Meta Düzenle</h2>
			<button class="mseo-modal__close" id="edit-modal-close">&times;</button>
		</div>
		<div class="mseo-modal__body">
			<input type="hidden" id="edit-post-id">

			<div class="mseo-form-group">
				<label class="mseo-label">Meta Title</label>
				<div class="mseo-input-wrap">
					<input type="text" id="edit-meta-title" class="mseo-input mseo-input--full" maxlength="120">
					<span class="mseo-char-counter" id="title-counter">0 / 60</span>
				</div>
				<div class="mseo-char-bar"><div class="mseo-char-bar__fill" id="title-bar"></div></div>
				<div class="mseo-ai-bar">
					<button class="mseo-btn mseo-btn--ai mseo-btn--sm" data-field="title">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
						AI ile Öner
					</button>
				</div>
				<div class="mseo-ai-suggestions" id="title-suggestions" style="display:none"></div>
			</div>

			<div class="mseo-form-group">
				<label class="mseo-label">Meta Description</label>
				<div class="mseo-input-wrap">
					<textarea id="edit-meta-desc" class="mseo-textarea" rows="3" maxlength="320"></textarea>
					<span class="mseo-char-counter" id="desc-counter">0 / 160</span>
				</div>
				<div class="mseo-char-bar"><div class="mseo-char-bar__fill" id="desc-bar"></div></div>
				<div class="mseo-ai-bar">
					<button class="mseo-btn mseo-btn--ai mseo-btn--sm" data-field="desc">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
						AI ile Öner
					</button>
				</div>
				<div class="mseo-ai-suggestions" id="desc-suggestions" style="display:none"></div>
			</div>

			<div class="mseo-form-group">
				<label class="mseo-label">H1 (salt okunur)</label>
				<input type="text" id="edit-h1" class="mseo-input mseo-input--full" readonly>
			</div>
		</div>
		<div class="mseo-modal__footer">
			<button class="mseo-btn mseo-btn--secondary" id="edit-modal-cancel">İptal</button>
			<button class="mseo-btn mseo-btn--primary" id="edit-modal-save">Kaydet</button>
		</div>
	</div>
</div>

<?php if ( $cached_results ) : ?>
<script>
window.merdusSEOCachedMeta    = <?php echo wp_json_encode( $cached_results ); ?>;
window.merdusSEOCachedSummary = <?php echo wp_json_encode( $cached_summary ); ?>;
</script>
<?php endif; ?>
