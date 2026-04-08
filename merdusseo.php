<?php
/**
 * Plugin Name: MerdusSEO
 * Plugin URI:  https://merdusseo.com
 * Description: Advanced SEO Analysis & Optimization Plugin — meta denetimi, kırık link tespiti, keyword yamyamlığı analizi ve AI destekli düzeltme.
 * Version:     1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author:      MerdusSEO
 * License:     GPL v2 or later
 * Text Domain: merdusseo
 */

defined( 'ABSPATH' ) || exit;

define( 'MERDUSSEO_VERSION', '1.0.0' );
define( 'MERDUSSEO_DIR',     plugin_dir_path( __FILE__ ) );
define( 'MERDUSSEO_URL',     plugin_dir_url( __FILE__ ) );
define( 'MERDUSSEO_FILE',    __FILE__ );

/* ── Includes ─────────────────────────────────────────────────────────── */
require_once MERDUSSEO_DIR . 'includes/class-installer.php';
require_once MERDUSSEO_DIR . 'includes/class-meta-analyzer.php';
require_once MERDUSSEO_DIR . 'includes/class-link-checker.php';
require_once MERDUSSEO_DIR . 'includes/class-cannibalization.php';
require_once MERDUSSEO_DIR . 'includes/class-ai-fixer.php';
require_once MERDUSSEO_DIR . 'includes/class-csv-handler.php';
require_once MERDUSSEO_DIR . 'includes/class-admin.php';

/* ── Lifecycle hooks ──────────────────────────────────────────────────── */
register_activation_hook( __FILE__,   [ 'MerdusSEO_Installer', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'MerdusSEO_Installer', 'deactivate' ] );

/* ── Bootstrap ────────────────────────────────────────────────────────── */
add_action( 'plugins_loaded', function () {
	new MerdusSEO_Admin();
} );
