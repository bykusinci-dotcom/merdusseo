<?php defined( 'ABSPATH' ) || exit; ?>
<div class="mseo-header">
	<div class="mseo-header__brand">
		<svg class="mseo-header__logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
		<span class="mseo-header__title">MerdusSEO</span>
		<span class="mseo-badge">v<?php echo esc_html( MERDUSSEO_VERSION ); ?></span>
	</div>
	<nav class="mseo-nav">
		<?php
		$current = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'merdusseo';
		$nav_items = [
			'merdusseo'                => 'Dashboard',
			'merdusseo-meta'           => 'Meta Analizi',
			'merdusseo-links'          => 'Kırık Linkler',
			'merdusseo-cannibalization'=> 'Keyword Yamyamlığı',
			'merdusseo-settings'       => 'Ayarlar',
		];
		foreach ( $nav_items as $page => $label ) {
			$active = $current === $page ? 'mseo-nav__item--active' : '';
			printf(
				'<a href="%s" class="mseo-nav__item %s">%s</a>',
				esc_url( admin_url( 'admin.php?page=' . $page ) ),
				esc_attr( $active ),
				esc_html( $label )
			);
		}
		?>
	</nav>
</div>
