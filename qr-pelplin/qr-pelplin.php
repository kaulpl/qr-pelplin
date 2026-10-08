<?php
/**
 * Plugin Name: QR Pelplin — treści, kody i statystyki
 * Description: Miejski portal QR z ciemnym landing page, CMS, mapą, kodami SVG/PNG i statystykami.
 * Version: 1.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.0
 * Author: QR Pelplin
 * Plugin URI: https://github.com/kaulpl/qr-pelplin
 * License: GPL-2.0-or-later
 * Text Domain: qr-pelplin
 * Update URI: https://github.com/kaulpl/qr-pelplin
 */
defined('ABSPATH') || exit;
define('QRP_VERSION', '1.1.0');
define('QRP_DIR', plugin_dir_path(__FILE__));
define('QRP_URL', plugin_dir_url(__FILE__));
foreach (['settings', 'core', 'media', 'analytics', 'api', 'admin', 'updater'] as $part) require_once QRP_DIR . 'includes/' . $part . '.php';
register_activation_hook(__FILE__, 'qrp_activate');
register_deactivation_hook(__FILE__, 'qrp_deactivate');
