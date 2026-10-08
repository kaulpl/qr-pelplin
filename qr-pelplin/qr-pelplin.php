<?php
/**
 * Plugin Name: QR Pelplin
 * Description: Portal treści QR, landing page, generator kodów i statystyki.
 * Version: 0.1.0
 * Requires PHP: 7.4
 */
defined('ABSPATH') || exit;
define('QRP_DIR', plugin_dir_path(__FILE__));
require_once QRP_DIR . 'includes/core.php';
register_activation_hook(__FILE__, 'qrp_activate');
register_deactivation_hook(__FILE__, 'qrp_deactivate');
