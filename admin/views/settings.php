<?php defined( 'ABSPATH' ) || exit;
$provider    = get_option( 'merdusseo_ai_provider', 'openai' );
$api_key     = get_option( 'merdusseo_ai_api_key', '' );
$model       = get_option( 'merdusseo_ai_model', 'gpt-4o-mini' );
$title_min   = get_option( 'merdusseo_meta_title_min', 30 );
$title_max   = get_option( 'merdusseo_meta_title_max', 60 );
$desc_min    = get_option( 'merdusseo_meta_desc_min', 70 );
$desc_max    = get_option( 'merdusseo_meta_desc_max', 160 );
$timeout     = get_option( 'merdusseo_link_timeout', 10 );
$post_types  = get_option( 'merdusseo_post_types', [ 'post', 'page' ] );
$all_types   = get_post_types( [ 'public' => true ], 'objects' );
?>
<div class="mseo-wrap">
	<?php include __DIR__ . '/partials/header.php'; ?>

	<div class="mseo-body">
		<div class="mseo-page-title">
			<h1>Ayarlar</h1>
			<p class="mseo-subtitle">AI entegrasyonu ve tarama parametrelerini yapılandırın</p>
		</div>

		<div id="settings-notice" class="mseo-notice" style="display:none"></div>

		<form id="settings-form">

			<!-- AI Settings -->
			<div class="mseo-settings-card">
				<div class="mseo-settings-card__header">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
					AI Ayarları
				</div>
				<div class="mseo-settings-card__body">
					<div class="mseo-form-row">
						<div class="mseo-form-group">
							<label class="mseo-label">AI Sağlayıcı</label>
							<select name="merdusseo_ai_provider" class="mseo-select mseo-select--full" id="ai-provider">
								<option value="openai"   <?php selected( $provider, 'openai' ); ?>>OpenAI</option>
								<option value="anthropic" <?php selected( $provider, 'anthropic' ); ?>>Anthropic (Claude)</option>
							</select>
						</div>
						<div class="mseo-form-group">
							<label class="mseo-label">Model</label>
							<div id="openai-models"   class="<?php echo $provider !== 'openai' ? 'mseo-hidden' : ''; ?>">
								<select name="merdusseo_ai_model" class="mseo-select mseo-select--full" id="model-openai">
									<option value="gpt-4o-mini"     <?php selected( $model, 'gpt-4o-mini' ); ?>>GPT-4o Mini (Hızlı & Ekonomik)</option>
									<option value="gpt-4o"          <?php selected( $model, 'gpt-4o' ); ?>>GPT-4o</option>
									<option value="gpt-4-turbo"     <?php selected( $model, 'gpt-4-turbo' ); ?>>GPT-4 Turbo</option>
								</select>
							</div>
							<div id="anthropic-models" class="<?php echo $provider !== 'anthropic' ? 'mseo-hidden' : ''; ?>">
								<select name="merdusseo_ai_model" class="mseo-select mseo-select--full" id="model-anthropic">
									<option value="claude-haiku-4-5-20251001" <?php selected( $model, 'claude-haiku-4-5-20251001' ); ?>>Claude Haiku 4.5 (Hızlı)</option>
									<option value="claude-sonnet-4-6"         <?php selected( $model, 'claude-sonnet-4-6' ); ?>>Claude Sonnet 4.6</option>
									<option value="claude-opus-4-6"           <?php selected( $model, 'claude-opus-4-6' ); ?>>Claude Opus 4.6</option>
								</select>
							</div>
						</div>
					</div>

					<div class="mseo-form-group">
						<label class="mseo-label">API Anahtarı</label>
						<div class="mseo-api-key-wrap">
							<input type="password" name="merdusseo_ai_api_key" class="mseo-input mseo-input--full" value="<?php echo esc_attr( $api_key ); ?>" placeholder="sk-... veya sk-ant-...">
							<button type="button" class="mseo-btn mseo-btn--secondary mseo-btn--sm" id="toggle-api-key">Göster</button>
						</div>
						<p class="mseo-hint">OpenAI için <strong>sk-</strong> ile başlayan, Anthropic için <strong>sk-ant-</strong> ile başlayan anahtarı girin.</p>
					</div>
				</div>
			</div>

			<!-- Meta Settings -->
			<div class="mseo-settings-card">
				<div class="mseo-settings-card__header">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>
					Meta Uzunluk Limitleri
				</div>
				<div class="mseo-settings-card__body">
					<div class="mseo-form-row mseo-form-row--4">
						<div class="mseo-form-group">
							<label class="mseo-label">Title Min (karakter)</label>
							<input type="number" name="merdusseo_meta_title_min" class="mseo-input mseo-input--full" value="<?php echo esc_attr( $title_min ); ?>" min="10" max="100">
						</div>
						<div class="mseo-form-group">
							<label class="mseo-label">Title Max (karakter)</label>
							<input type="number" name="merdusseo_meta_title_max" class="mseo-input mseo-input--full" value="<?php echo esc_attr( $title_max ); ?>" min="10" max="200">
						</div>
						<div class="mseo-form-group">
							<label class="mseo-label">Description Min (karakter)</label>
							<input type="number" name="merdusseo_meta_desc_min" class="mseo-input mseo-input--full" value="<?php echo esc_attr( $desc_min ); ?>" min="20" max="200">
						</div>
						<div class="mseo-form-group">
							<label class="mseo-label">Description Max (karakter)</label>
							<input type="number" name="merdusseo_meta_desc_max" class="mseo-input mseo-input--full" value="<?php echo esc_attr( $desc_max ); ?>" min="50" max="500">
						</div>
					</div>
				</div>
			</div>

			<!-- Scan Settings -->
			<div class="mseo-settings-card">
				<div class="mseo-settings-card__header">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
					Tarama Ayarları
				</div>
				<div class="mseo-settings-card__body">
					<div class="mseo-form-row">
						<div class="mseo-form-group">
							<label class="mseo-label">Link Kontrol Zaman Aşımı (saniye)</label>
							<input type="number" name="merdusseo_link_timeout" class="mseo-input mseo-input--full" value="<?php echo esc_attr( $timeout ); ?>" min="3" max="60">
						</div>
					</div>

					<div class="mseo-form-group">
						<label class="mseo-label">Taranacak Post Türleri</label>
						<div class="mseo-checkbox-group">
						<?php foreach ( $all_types as $type ) : ?>
							<label class="mseo-checkbox">
								<input type="checkbox" name="merdusseo_post_types[]" value="<?php echo esc_attr( $type->name ); ?>"
									<?php checked( in_array( $type->name, (array) $post_types, true ) ); ?>>
								<span><?php echo esc_html( $type->label ); ?> <small>(<?php echo esc_html( $type->name ); ?>)</small></span>
							</label>
						<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>

			<div class="mseo-form-actions">
				<button type="submit" class="mseo-btn mseo-btn--primary mseo-btn--lg">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
					Ayarları Kaydet
				</button>
			</div>
		</form>
	</div>
</div>
