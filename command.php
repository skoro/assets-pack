<?php

// Ensure we are executed under wp command.
if ( !defined( 'WP_CLI') ) {
    return;
}

require_once __DIR__ . '/admin.php';

/**
 * Manage assets pack.
 */
class Assets_Pack_Command extends WP_CLI_Command {
    
    /**
     * @var Assets_Pack_Admin
     */
    protected $admin;
    
    public function __construct() {
        $this->admin = Assets_Pack_Admin::get_instance();
    }
    
    /**
     * Show info.
     */
    public function info() {
        $enabled = function ( $value ) {
            return $value ? 'enabled' : 'disabled';
        };
        $skip = function ( $value ) {
            return count( $value ) ? implode( ', ', $value ) : '-';
        };
        WP_CLI::log( 'Assets dir: ' . $this->admin->get_setting( 'assets_dir' ) );
        WP_CLI::log( 'Assets url: ' . $this->admin->get_setting( 'assets_url' ) );
        WP_CLI::log( 'JS aggregation: ' . $enabled( $this->admin->get_setting( 'enable_js' ) ) );
        WP_CLI::log( 'JS debug: ' . $enabled( $this->admin->get_setting( 'debug_js' ) ) );
        WP_CLI::log( 'JS skip: ' . $skip( $this->admin->get_setting( 'skip_js' ) ) );
        WP_CLI::log( 'CSS aggregation: ' . $enabled( $this->admin->get_setting( 'enable_css' ) ) );
        WP_CLI::log( 'CSS debug: ' . $enabled( $this->admin->get_setting( 'debug_css' ) ) );
        WP_CLI::log( 'CSS skip: ' . $skip( $this->admin->get_setting( 'skip_css' ) ) );
    }
    
    /**
     * Clears assets.
     */
    public function clear() {
        $result = $this->admin->clear_assets();
        if ( $result ) {
            WP_CLI::success( 'Done!' );
        }
    }
    
    /**
     * Scripts aggregation.
     *
     * [--enable=<on|off>]
     * : Enable of disable scripts aggregation.
     * 
     * [--debug=<on|off>]
     * : Enable or disable debug mode.
     */
    public function js() {
        
    }
}

// Register 'assets' command.
WP_CLI::add_command( 'assets',  new Assets_Pack_Command() );
