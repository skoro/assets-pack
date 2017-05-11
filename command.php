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
    
    /**
     * Creates a command.
     */
    public function __construct() {
        parent::__construct();
        $this->admin = Assets_Pack_Admin::get_instance();
    }
    
    /**
     * @param boolean $value
     * @return string
     */
    protected function str_enabled( $value ) {
        return $value ? 'enabled' : 'disabled';
    }

    /**
     * @param array $value
     * @return string
     */
    protected function str_skip( $value ) {
        return count( $value ) ? implode( ', ', $value) : '-';
    }
    
    /**
     * Show JS status.
     */
    protected function info_js() {
        WP_CLI::log( 'JS aggregation: ' . $this->str_enabled( $this->admin->get_setting( 'enable_js' ) ) );
        WP_CLI::log( 'JS debug: ' . $this->str_enabled( $this->admin->get_setting( 'debug_js' ) ) );
        WP_CLI::log( 'JS skip: ' . $this->str_skip( $this->admin->get_setting( 'skip_js' ) ) );
    }
    
    /**
     * Show CSS status.
     */
    protected function info_css() {
        WP_CLI::log( 'CSS aggregation: ' . $this->str_enabled( $this->admin->get_setting( 'enable_css' ) ) );
        WP_CLI::log( 'CSS debug: ' . $this->str_enabled( $this->admin->get_setting( 'debug_css' ) ) );
        WP_CLI::log( 'CSS skip: ' . $this->str_skip( $this->admin->get_setting( 'skip_css' ) ) );
        WP_CLI::log( 'CSS inline url: ' . $this->str_enabled( $this->admin->get_setting( 'css_inline_url' ) ) );
    }
    
    /**
     * Show plugin info.
     */
    public function info() {
        WP_CLI::log( 'Assets dir: ' . $this->admin->get_setting( 'assets_dir' ) );
        WP_CLI::log( 'Assets url: ' . $this->admin->get_setting( 'assets_url' ) );
        $this->info_js();
        $this->info_css();
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
     * Show status or change scripts aggregation.
     *
     * [--enable=<on|off>]
     * : Enable of disable scripts aggregation.
     * 
     * [--debug-info=<on|off>]
     * : Enable or disable debug mode.
     * 
     * [--skip=<scripts>]
     * : Skip following scripts on aggregation. Where <scripts> is a list of script names
     * separated by commas. To clear skip list pass empty string: --skip=""
     */
    public function js( $args, $assoc_args ) {

        $this->change_option( 'enable_js', 'enable', $assoc_args );
        $this->change_option( 'debug_js', 'debug-info', $assoc_args );
        $this->change_option( 'skip_js', 'skip', $assoc_args );

        $this->info_js();
    }

    /**
     * CSS styles aggregation.
     *
     * Show status or change styles aggregation.
     *
     * [--enable=<on|off>]
     * : Enable or disable styles aggregation.
     * 
     * [--debug-info=<on|off>]
     * : Enable or disable debug mode.
     * 
     * [--skip=<styles>]
     * : Skip following styles on aggregation. Where <styles> is a list of style names
     * separated by commas. To clear skip list pass empty string: --skip=""
     * 
     * [--inline-url=<on|off>]
     * : Enable or disable url links convertation inside "url()". Assets must
     * be cleared after this setting is changed.
     */
    public function css( $args, $assoc_args ) {

        $this->change_option( 'enable_css', 'enable', $assoc_args );
        $this->change_option( 'debug_css', 'debug-info', $assoc_args );
        $this->change_option( 'skip_css', 'skip', $assoc_args );
        $this->change_option( 'css_inline_url', 'inline-url', $assoc_args );

        $this->info_css();
    }

    /**
     * Change plugin option.
     *
     * @param string $option Setting option name.
     * @param string $cmd_opt Appropriate command line option name.
     * @param array $args Command line arguments.
     */
    protected function change_option( $option, $cmd_opt, $args ) {
        if ( !isset( $args[$cmd_opt] ) ) {
            return;
        }
        
        $status = true;
        $value = $args[$cmd_opt];
        
        switch ( $cmd_opt ) {
            case 'enable':
            case 'debug-info':
            case 'inline-url':
                if ( $value === 'on' || $value === 'off' ) {
                    $value = $value == 'on' ? true : false;
                    $status = $this->admin->set_setting( $option, $value );
                } else {
                    WP_CLI::error( 'Option "' . $cmd_opt . '" accepts only "on" or "off" value.' );
                }
                break;
                
            case 'skip':
                $status = $this->admin->set_setting( $option, $value );
                break;
        }
        
        if ( is_string( $status ) ) {
            WP_CLI::error( $status );
        }
    }
}

// Register 'assets' command.
WP_CLI::add_command( 'assets',  new Assets_Pack_Command() );
