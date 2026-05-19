<?php
/**
 * Uninstall cleanup for Sync WordPress to X.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('swtx_settings');
