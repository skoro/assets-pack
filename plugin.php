<?php
/*
Plugin Name: Bunch Optimizer
Plugin URI: https://github.com/skoro/bunch-optimizer
Description: Minimize scripts and styles.
Version: 0.1.0
Author: Skorobogatko Alexei
Author URI: 
License: GPLv2
*/

class Bunch_Optimizer {
    
    /**
     * @var Script_Minimizer
     */
    protected static $instance;
    
    /**
     * Assets directory path.
     *
     * @var string
     */
    public $assets_dir;
    
    /**
     * Assets absolute url.
     *
     * @var string
     */
    public $assets_url;
    
    /**
     * @var string
     */
    public $base_url;

    /**
     * Disable class creation via constructor.
     *
     * @see Bunch_Optimizer::get_instance()
     */
    private function __construct() {
        $this->base_url = home_url();
        $this->init_assets_dir();
    }
    
    /**
     * @return Script_Minimizer
     */
    public static function get_instance() {
        if ( static::$instance === null ) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    
    /**
     * Setup WP actions.
     */
    public function setup() {
        if ( !is_admin() ) {
            add_action( 'wp_print_scripts', [$this, 'wp_print_scripts' ], 100 );
            add_action( 'wp_print_styles', [$this, 'wp_print_styles'], 100 );
        }
    }
    
    /**
     * Callback handler for 'wp_print_scripts' action.
     */
    public function wp_print_scripts( ) {
        $scripts = wp_scripts();
        $scripts->all_deps( $scripts->queue );
  
        $load = [];
        $handles = [];
        
        foreach ( $scripts->to_do as $handle ) {
            
            if ( empty( $scripts->registered[$handle] ) ) {
                continue;
            }
            
            $obj = $scripts->registered[$handle];
            $src = $obj->src;
            
            if ( empty( $src ) ) {
                continue;
            }
            
            if ( $this->is_conditional( $obj ) ) {
                continue;
            }
            
            if ( $src[0] !== '/' ) {
                if ( strpos( $src, $this->base_url ) === 0 ) {
                    $src = str_replace( $this->base_url, '', $src );
                } else {
                    // It's a remote script.
                    continue;
                }
            }
            
            $group = isset( $obj->extra['group'] ) ? $obj->extra['group'] : 0;
            $load[$group][] = $src;
            $handles[] = $handle;
        }

        if ( empty( $load ) ) {
            return;
        }
        
        if ( count( $load ) === 1 ) {
            return;
        }
        
        $filename = $this->get_bunch_key( $handles ) . '.js';
        if ( !$this->is_bunch_exists( $filename ) &&
                !$this->create_scripts_bunch( $filename, $load ) ) {
            return;
        }

        foreach ( $handles as $handle ) {
            $scripts->done[] = $handle;
            $scripts->print_extra_script( $handle );
        }
        
        $url = $this->assets_url . $filename;
        print "<script type='text/javascript' src='$url'></script>";
    }
    
    /**
     * Callback handler for 'wp_print_styles' action.
     */
    public function wp_print_styles() {
        $styles = wp_styles();
        $styles->all_deps( $styles->queue );
        
        $load = [];
        
        foreach ( $styles->to_do as $handle ) {
        
            if ( empty( $styles->registered[$handle] ) ) {
                continue;
            }
            
            if ( ! ( $obj = $styles->registered[$handle] ) ) {
                continue;
            }
            if ( strpos( $obj->src, $this->base_url ) !== 0 ) {
                continue;
            }
            
            if ( $this->is_conditional( $obj ) ) {
                continue;
            }
            
            $load[$handle] = str_replace( $this->base_url, '', $obj->src );
        }
        
        if ( empty( $load ) ) {
            return;
        }
        
        if ( count( $load ) === 1 ) {
            return;
        }
        
        $filename = $this->get_bunch_key( array_keys( $load ) ) . '.css';
        if ( !$this->is_bunch_exists( $filename ) &&
                !$this->create_styles_bunch( $filename, $load ) ) {
            return;
        }
        
        $url = $this->assets_url . $filename;
        print "<link rel='stylesheet' id='bunch-css' href='$url' type='text/css' />\n";

        foreach ( array_keys( $load ) as $handle ) {
            $styles->done[] = $handle;
            $styles->print_inline_style( $handle );
        }
    }
    
    /**
     * Is dependency object conditional ?
     *
     * @param _WP_Dependency $obj
     * @return bool
     */
    protected function is_conditional( $obj ) {
        return isset( $obj->extra['conditional'] ) && $obj->extra['conditional'];
    }
    
    /**
     * Get a unique key for handles.
     *
     * @param array $handles
     * @return string
     */
    protected function get_bunch_key( $handles ) {
        
        $value = '';
        
        foreach ( $handles as $handle ) {
            $value .= ',' . $handle;
        }
        
        return md5( $value );
    }
    
    /**
     * Initialize assets directory.
     *
     * @throws RuntimeException When assets directory cannot be created.
     */
    protected function init_assets_dir() {
        $upload = wp_upload_dir();
        $dir = $upload['basedir'] . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
        
        if ( !is_dir( $dir ) && !mkdir( $dir ) ) {
            throw new RuntimeException( 'Cannot create assets directory: ' . $dir );
        }
        
        $this->assets_dir = $dir;
        $this->assets_url = $upload['baseurl'] . '/assets/';
    }
    
    /**
     * Is bunch file exist ?
     *
     * @param string $filename Base file name.
     * @return bool
     */
    protected function is_bunch_exists( $filename ) {
        return file_exists( $this->assets_dir . $filename );
    }
    
    /**
     * Create scripts bunch file.
     *
     * @param string $filename Base file name.
     * @param array $assets Assets in form: group - files.
     * @return bool 
     */
    protected function create_scripts_bunch( $filename, array $assets ) {
        
        if ( ! ( $fh = $this->create_bunch_file( $filename ) ) ) {
            return false;
        }
        
        ksort( $assets );
        
        foreach ( $assets as $group => $files ) {
            foreach ( (array) $files as $file ) {
                $contents = file_get_contents( ABSPATH . $file ) . "\n\n;";
                fwrite( $fh, $contents );
            }
        }
        
        return $this->unlock_file( $fh );
    }
    
    /**
     * Create styles bunch file.
     *
     * @param string $filename
     * @param array $files List of style files.
     * @return bool
     */
    protected function create_styles_bunch( $filename, $files ) {
        if ( ! ( $fh = $this->create_bunch_file( $filename ) ) ) {
            return false;
        }
        
        foreach ( $files as $file ) {
            $contents = file_get_contents( ABSPATH . $file ) . "\n";
            fwrite( $fh, $contents );
        }
        
        return $this->unlock_file( $fh );
    }
    
    /**
     * Create and lock bunch file.
     *
     * @param string $filename
     * @return bool Returns false on create error or file locked.
     */
    protected function create_bunch_file( $filename ) {
        if ( ! ( $fh = fopen( $this->assets_dir . $filename, 'w' ) ) ) {
            // TODO: error_log ?
            return false;
        }
        
        if ( !flock( $fh, LOCK_EX ) ) {
            return false;
        }
        
        return $fh;
    }
    
    /**
     * Unlock file handle.
     *
     * @param resource $fh
     * @return bool
     */
    protected function unlock_file( $fh ) {
        flock( $fh, LOCK_UN );
        fclose( $fh );
        return true;
    }
}

if ( !is_admin() ) {
    add_action( 'plugins_loaded', function () {
        try {
            Bunch_Optimizer::get_instance()->setup();
        } catch ( RuntimeException $e ) {
            // TODO: admin notice.
            error_log( $e->getMessage() );
        }
    } );
}
