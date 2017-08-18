<?php

/**
 * Plugin uninstall.
 * 
 * Removes styles and scripts bundles from the assets directory.
 * Removes the assets directory itself.
 * Removes the plugin options.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

if ( ! class_exists( 'Assets_Pack_Admin' ) ) {
    require_once __DIR__ . '/admin.php';
}

$admin = Assets_Pack_Admin::get_instance();
$dir = $admin->get_setting( 'assets_dir' );
if ( is_dir( $dir ) ) {
    $admin->clear_assets();
    $files =  glob( $dir . DIRECTORY_SEPARATOR . '*' );
    if ( ! $files ) {
        rmdir( $dir );
    }
}

delete_option( 'assets_pack' );
