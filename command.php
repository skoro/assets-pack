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
     * Clears assets.
     */
    public function clear() {
        $result = $this->admin->clear_assets();
        echo '<pre>'; var_dump($result); die();
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
