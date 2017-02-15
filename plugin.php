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

// Make sure we don't expose any info if it called directly.
defined( 'ABSPATH' ) or die();

use MatthiasMullie\Minify;

/**
 * Scripts and styles optimizer.
 */
class Bunch_Optimizer {
    
    const MIN_JS = 1;
    const MIN_CSS = 2;
    
    /**
     * @var Bunch_Optimizer
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
     * Scripts collected by {@see Bunch_Optimizer::wp_print_scripts}.
     *
     * @var array
     */
    public $scripts_bunch = [];

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
     * @return Bunch_Optimizer
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
            add_action( 'wp_print_scripts', [$this, 'wp_print_scripts'], 100 );
            add_action( 'wp_print_styles', [$this, 'wp_print_styles'], 100 );
            // Must be first.
            add_action( 'wp_print_footer_scripts', [$this, 'wp_print_footer_scripts'], -100 );
        }
    }
    
    /**
     * Callback handler for 'wp_print_scripts' action.
     */
    public function wp_print_scripts( ) {
        $scripts = wp_scripts();
        $scripts->all_deps( $scripts->queue );
  
        $load = [];
        
        foreach ( $scripts->to_do as $handle ) {
            
            if ( empty( $scripts->registered[$handle] ) ) {
                continue;
            }
            
            /** @var $obj _WP_Dependency */
            $obj = $scripts->registered[$handle];
            $src = $obj->src;
            $group = isset( $obj->extra['group'] ) ? $obj->extra['group'] : 0;

            // Skip already bunched script.
            if ( isset( $this->scripts_bunch[$group][$handle] ) ) {
                continue;
            }

            if ( empty( $src ) ) {
                continue;
            }
            
            if ( $this->is_conditional( $obj ) ) {
                continue;
            }
            
            if ( ( $src = $this->get_dependency_src( $obj ) ) === false ) {
                continue;
            }

            $load[$group][$handle] = $src;
        }

        if ( empty( $load ) ) {
            return;
        }

        // Sort by group order.
        ksort( $load );
        
        foreach ( $load as $group => $handles) {
            foreach ( $handles as $handle => $src ) {
                $scripts->done[] = $handle;
                // TODO: enqueue ?
                $scripts->print_extra_script( $handle );
                $this->scripts_bunch[$group][$handle] = $src;
            }
        }
    }
    
    /**
     * Callback handler for 'wp_print_footer_scripts' action.
     */
    public function wp_print_footer_scripts() {
        $this->wp_print_scripts();
        
        $handles = [];
        foreach ( $this->scripts_bunch as $group ) {
            $handles = array_merge( $handles, array_keys( $group ) );
        }
        
        $filename = $this->get_bunch_key( $handles ) . '.js';
        if ( !$this->is_bunch_exists( $filename ) &&
                !$this->create_scripts_bunch( $filename, $this->scripts_bunch ) ) {
            return;
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
            
            /** @var $obj _WP_Dependency */
            if ( ! ( $obj = $styles->registered[$handle] ) ) {
                continue;
            }
            if ( ( $src = $this->get_dependency_src( $obj ) ) === false ) {
                continue;
            }
            
            if ( $this->is_conditional( $obj ) ) {
                continue;
            }
            
            $load[$handle] = $src;
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
     * Get dependency source without host name.
     *
     * @param _WP_Dependency $obj
     * @return string|false
     */
    protected function get_dependency_src( $obj ) {
        $src = $obj->src;
        
        if ( $src[0] === '/' ) {
            return $src;
        }
        
        // Remote resource.
        if ( strpos( $src, $this->base_url) !== 0 ) {
            return false;
        }
        
        // Strip base url and GET parameters from url.
        $src = str_replace( $this->base_url, '', $src );
        if ( ( $pos = strpos( $src, '?' ) ) !== false ) {
            $src = substr( $src, 0, $pos );
        }

        return $src;
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
        
        foreach ( $assets as $group => $files ) {
            foreach ( (array) $files as $file ) {
                $file_path = ABSPATH . $file;
                // Skip already minified bundles.
                if ( substr( $file_path, -7 ) === '.min.js' ) {
                    $contents = file_get_contents( $file_path );
                } else {
                    $contents = $this->minify( $file_path, static::MIN_JS );
                }
                fwrite( $fh, $contents . "\n\n;" );
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
            $file_path = ABSPATH . $file;
            // Skip already minified bundles.
            if ( substr( $file_path, -8 ) === '.min.css' ) {
                $contents = file_get_contents( $file_path ) . "\n";
            } else {
                $contents = $this->minify( $file_path, static::MIN_CSS );
            }
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
    
    /**
     * Minify bunch.
     *
     * @param string $file
     * @param integer $min
     * @return string
     * @throws InvalidArgumentException
     */
    protected function minify( $file, $min ) {
        switch ( $min ) {
            case static::MIN_JS :
                $minify = new Minify\JS( $file );
                break;
            
            case static::MIN_CSS:
                $minify = new Minify\CSS( $file );
                break;
            
            default:
                throw new InvalidArgumentException('Minify id is unknown');
        }
        
        return $minify->minify();
    }
}

// Frontend part.
if ( !is_admin() ) {

    // Include Composer's autoloader.
    if ( file_exists( $composer = __DIR__ . '/vendor/autoload.php' ) ) {
        require_once $composer;
    } else {
        wp_die( __( 'Composer dependencies are missing. Please make sure that you are executed <strong>composer install</strong> command.' ) );
    }

    // Setup optimizer.
    add_action( 'plugins_loaded', function () {
        try {
            Bunch_Optimizer::get_instance()->setup();
        } catch ( RuntimeException $e ) {
            // TODO: admin notice.
            error_log( $e->getMessage() );
        }
    } );
}
