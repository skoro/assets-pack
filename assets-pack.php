<?php
/*
Plugin Name: Assets Pack
Plugin URI: https://github.com/skoro/assets-pack
Description: Aggregate and minimize javascripts and css.
Version: 0.1.0
Author: Skorobogatko Alexei
Author URI: https://github.com/skoro
License: GPLv2
*/

// Make sure we don't expose any info if it called directly.
defined( 'ABSPATH' ) or die();

require_once __DIR__ . '/admin.php';

use MatthiasMullie\Minify;

/**
 * Scripts and styles optimizer.
 */
class Assets_Pack {
    
    const MIN_JS = 1;
    const MIN_CSS = 2;
    
    /**
     * @var Assets_Pack
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
     * Scripts collected by {@see Assets_Pack::wp_print_scripts}.
     *
     * @var array
     */
    public $script_assets = [];
    
    /**
     * @var Assets_Pack_Admin
     */
    public $admin;

    /**
     * Disable class creation via constructor.
     *
     * @see Assets_Pack::get_instance()
     */
    private function __construct() {
        $this->base_url = home_url();
        $this->admin = Assets_Pack_Admin::get_instance();
        $this->init_assets_dir();
    }
    
    /**
     * @return Assets_Pack
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
        if ( $this->admin->get_setting( 'enable_js') ) {
            add_action( 'wp_print_scripts', [$this, 'wp_print_scripts'], 100 );
            // Must be first.
            add_action( 'wp_print_footer_scripts', [$this, 'wp_print_footer_scripts'], -100 );
        }
        if ( $this->admin->get_setting( 'enable_css' ) ) {
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
        $skip = $this->admin->get_setting( 'skip_js' );
        
        foreach ( $scripts->to_do as $handle ) {
            
            if ( empty( $scripts->registered[$handle] ) ) {
                continue;
            }
            
            // Skip admin defined scripts.
            if ( in_array( $handle, $skip ) ) {
                continue;
            }
            
            /** @var $obj _WP_Dependency */
            $obj = $scripts->registered[$handle];
            $src = $obj->src;
            $group = isset( $obj->extra['group'] ) ? $obj->extra['group'] : 0;

            // Skip already handled scripts.
            if ( isset( $this->script_assets[$group][$handle] ) ) {
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
                $this->script_assets[$group][$handle] = $src;
            }
        }
    }
    
    /**
     * Callback handler for 'wp_print_footer_scripts' action.
     */
    public function wp_print_footer_scripts() {
        $this->wp_print_scripts();
        
        $handles = [];
        foreach ( $this->script_assets as $group ) {
            $handles = array_merge( $handles, array_keys( $group ) );
        }
        
        $filename = $this->get_assets_key( $handles ) . '.js';
        if ( !$this->is_assets_exists( $filename ) &&
                !$this->create_scripts_assets( $filename, $this->script_assets ) ) {
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
        $skip = $this->admin->get_setting( 'skip_css' );
        
        foreach ( $styles->to_do as $handle ) {
        
            if ( empty( $styles->registered[$handle] ) ) {
                continue;
            }
            
            // Skip admin defined styles.
            if ( in_array( $handle, $skip ) ) {
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
        
        $filename = $this->get_assets_key( array_keys( $load ) ) . '.css';
        if ( !$this->is_assets_exists( $filename ) &&
                !$this->create_styles_assets( $filename, $load ) ) {
            return;
        }
        
        $url = $this->assets_url . $filename;
        print "<link rel='stylesheet' id='assets-css' href='$url' type='text/css' />\n";

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
    protected function get_assets_key( $handles ) {
        
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
        $dir = $this->admin->get_setting( 'assets_dir' );
        
        if ( $this->admin->validate_assets_dir() === false ) {
            throw new RuntimeException( 'Cannot create assets directory: ' . $dir );
        }
        
        $this->assets_dir = $dir;
        $this->assets_url = $this->admin->get_setting( 'assets_url' );
    }
    
    /**
     * Is assets file exist ?
     *
     * @param string $filename Base file name.
     * @return bool
     */
    protected function is_assets_exists( $filename ) {
        return file_exists( $this->assets_dir . $filename );
    }
    
    /**
     * Create scripts assets file.
     *
     * @param string $filename Base file name.
     * @param array $assets Assets in form: group - files.
     * @return bool 
     */
    protected function create_scripts_assets( $filename, array $assets ) {
        
        if ( ! ( $fh = $this->create_assets_file( $filename ) ) ) {
            return false;
        }
        
        $debug_data = [];
        foreach ( $assets as $group => $files ) {
            foreach ( (array) $files as $handle => $file ) {
                $file_path = ABSPATH . $file;
                // Skip already minified bundles.
                if ( substr( $file_path, -7 ) === '.min.js' ) {
                    $contents = file_get_contents( $file_path );
                } else {
                    $contents = $this->minify( $file_path, static::MIN_JS );
                }
                fwrite( $fh, $contents . "\n\n;" );
                $debug_data[$handle] = $file;
            }
        }
        
        if ( $this->admin->get_setting( 'debug_js' ) ) {
        	$this->create_debug( $filename, $debug_data );
        }
        
        return $this->unlock_file( $fh );
    }
    
    /**
     * Create styles assets file.
     *
     * @param string $filename
     * @param array $files List of style files.
     * @return bool
     */
    protected function create_styles_assets( $filename, $files ) {
        if ( ! ( $fh = $this->create_assets_file( $filename ) ) ) {
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
        
        if ( $this->admin->get_setting( 'debug_css') ) {
        	$this->create_debug( $filename, $files );
        }
        
        return $this->unlock_file( $fh );
    }
    
    /**
     * Create and lock assets file.
     *
     * @param string $filename
     * @return bool Returns false on create error or file locked.
     */
    protected function create_assets_file( $filename ) {
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
     * Minify assets.
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
    
    /**
     * Create debug file.
     *
     * @param string $filename Debug file name (full path without .debug extension).
     * @param array $data Debug contents.
     * @return bool
     */
    protected function create_debug( $filename, array $data ) {
    	$filename .= '.debug';
    	
    	if ( ( $fh = $this->create_assets_file( $filename ) ) === false ) {
    		return false;
    	}
    	
    	foreach ( $data as $handle => $file ) {
    		fwrite( $fh, sprintf( "%s: %s\n", $handle, $file ) );
    	}
    	
    	return $this->unlock_file( $fh );
    }
}

//===========================================================================
// Plugin starts here.
//===========================================================================

// Admin settings page.
if ( is_admin() ) {
    Assets_Pack_Admin::get_instance()->setup();
}
// Frontend part.
else {
    // Include Composer's autoloader.
    if ( file_exists( $composer = __DIR__ . '/vendor/autoload.php' ) ) {
        require_once $composer;
    } else {
        wp_die( __( 'Composer dependencies are missing. Please make sure that you are executed <strong>composer install</strong> command.' ) );
    }

    // Setup optimizer.
    add_action( 'plugins_loaded', function () {
        try {
            Assets_Pack::get_instance()->setup();
        } catch ( RuntimeException $e ) {
            // TODO: admin notice.
            error_log( $e->getMessage() );
        }
    } );
}
