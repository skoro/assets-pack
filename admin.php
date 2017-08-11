<?php

/**
 * Administrative interface.
 */
class Assets_Pack_Admin {
    
    /**
     * @var Assets_Pack_Admin
     */
    protected static $instance;
    
    /**
     * @var array
     */
    protected $settings;
    
    /**
     * @var string
     */
    protected $settings_page;
    
    /**
     * @return Assets_Pack_Admin
     */
    public static function get_instance() {
        if ( static::$instance === null ) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    
    /**
     * Setup interface actions.
     */
    public function setup() {
        add_action( 'admin_menu', [$this, 'admin_menu'] );
        add_action( 'admin_init', [$this, 'init_settings'] );
    }
    
    /**
     * Options menu item.
     */
    public function admin_menu() {
        $this->settings_page = add_options_page(
            __( 'Assets Pack', 'assets-pack' ),
            __( 'Assets', 'assets-pack' ),
            apply_filters( 'assets_settings_capability', 'manage_options' ),
            'assets-settings',
            [$this, 'settings_page']
        );
    }
    
    /**
     * Settings initialization.
     */
    public function init_settings() {

        $upload_dir = wp_upload_dir();
        register_setting( 'assets_pack', 'assets_pack', [
            'sanitize_callback' => [$this, 'validate_settings'],
            'default' => [
                'assets_dir'     => $upload_dir['basedir'] . '/assets',
                'assets_url'     => $upload_dir['baseurl'] . '/assets',
            	'skip_js'        => [],
                'enable_js'      => false,
                'debug_js'       => false,
            	'skip_css'       => [],
                'enable_css'     => false,
                'css_inline_url' => false,
                'debug_css'      => false,
            ],
        ] );

        // Main options.
        add_settings_section( 'main', '', '__return_false', $this->settings_page );
        add_settings_field( 'assets_dir', __( 'Assets store location', 'assets-pack' ), [$this, 'field_assets_store'], $this->settings_page, 'main' );
        add_settings_field( 'assets_url', __( 'Assets URL', 'assets-pack' ), [$this, 'field_assets_url'], $this->settings_page, 'main' );
        
        // Javascript options.
        add_settings_section( 'js', __( 'JavaScript aggregation', 'assets-pack' ), '__return_false', $this->settings_page );
        add_settings_field( 'enable_js', __( 'JavaScript aggregation', 'assets-pack' ), [$this, 'field_enable_js'], $this->settings_page, 'js' );
        add_settings_field( 'skip_js', __( 'Skip scripts', 'assets-pack' ), [$this, 'field_skip_js'], $this->settings_page, 'js' );
        add_settings_field( 'debug_js', __( 'Debug', 'assets-pack' ), [$this, 'field_debug_js'], $this->settings_page, 'js' );
        
        // CSS options.
        add_settings_section( 'css', __( 'CSS aggregation', 'assets-pack' ), '__return_false', $this->settings_page );
        add_settings_field( 'enable_css', __( 'CSS aggregation', 'assets-pack' ), [$this, 'field_enable_css'], $this->settings_page, 'css' );
        add_settings_field( 'skip_css', __( 'Skip styles', 'assets-pack' ), [$this, 'field_skip_css'], $this->settings_page, 'css' );
        add_settings_field( 'css_inline_url', __( 'Convert inline urls', 'assets-pack' ), [$this, 'field_css_inline_url'], $this->settings_page, 'css' );
        add_settings_field( 'debug_css', __( 'Debug', 'assets-pack' ), [$this, 'field_debug_css'], $this->settings_page, 'css' );
    }
    
    /**
     * Settings page callback.
     */
    public function settings_page() { ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Assets pack', 'assets-pack' ) ?></h1>
            <form method="POST" action="options.php">
                <?php settings_fields( 'assets_pack' ) ?>
                <?php do_settings_sections( $this->settings_page ) ?>
                <?php submit_button( __( 'Update settings', 'assets-pack' ) ) ?>
            </form>
        </div>
    <?php }
    
    public function field_skip_js() { ?>
    	<input type="text" name="assets_pack[skip_js]" value="<?= esc_attr( implode( ',', $this->get_setting( 'skip_js', [] ) ) ) ?>" size="64"/>
    	<p class="description">
            <?php esc_html_e( 'Do not include these scripts to assets file. You should enter handle names separated by commas. You can get names from a .js.debug file.', 'assets-pack' ) ?>
        </p>
    <?php }
    
    public function field_enable_js() { ?>
        <label>
            <input type="checkbox" name="assets_pack[enable_js]" value="true" <?php checked( $this->get_setting( 'enable_js' ) ) ?>/>
            <?php esc_html_e( 'Enabling aggregation all javascripts will concatenated to one asset bundle.', 'assets-pack' ) ?>
        </label>
    <?php }
    
    public function field_skip_css() { ?>
        <input type="text" name="assets_pack[skip_css]" value="<?= esc_attr( implode( ',', $this->get_setting( 'skip_css', [] ) ) ) ?>" size="64"/>
        <p class="description">
            <?php esc_html_e( 'Do not include these styles to assets file. You should enter handle names separated by commas. You can get names from a .css.debug file.', 'assets-pack' ) ?>
        </p>
    <?php }
    
    public function field_enable_css() { ?>
        <label>
            <input type="checkbox" name="assets_pack[enable_css]" value="true" <?php checked( $this->get_setting( 'enable_css' ) ) ?>/>
            <?php esc_html_e( 'Enabling aggregation all CSS styles will concatenated to one asset bundle.', 'assets-pack' ) ?>
        </label>
    <?php }
    
    public function field_debug_js() { ?>
        <label>
            <input type="checkbox" name="assets_pack[debug_js]" value="true" <?php checked( $this->get_setting( 'debug_js') ) ?>/>
            <?php esc_html_e( 'Create along with js asset file with .js.debug extension which contains script names.', 'assets-pack' ) ?>
        </label>
    <?php }
    
    public function field_debug_css() { ?>
        <label>
            <input type="checkbox" name="assets_pack[debug_css]" value="true" <?php checked( $this->get_setting( 'debug_css' ) ) ?>/>
            <?php esc_html_e( 'Create along with css asset file with .css.debug extension which contains styles names.', 'assets-pack' ) ?>
        </label>
    <?php }
    
    public function field_assets_store() { ?>
        <input type="text" name="assets_pack[assets_dir]" value="<?= $this->get_setting( 'assets_dir' ) ?>" size="64"/>
        <p>
            <button name="clear" class="button"><?php esc_html_e( 'Clear assets', 'assets-pack' ) ?></button>
        </p>
    <?php }
    
    public function field_assets_url() { ?>
        <input type="text" name="assets_pack[assets_url]" value="<?= $this->get_setting( 'assets_url' ) ?>" size="64"/>
    <?php }
    
    public function field_css_inline_url() { ?>
        <label>
            <input type="checkbox" name="assets_pack[css_inline_url]" value="true" <?php checked( $this->get_setting( 'css_inline_url' ) ) ?>/>
            <?php esc_html_e( 'Convert all url() values to local if it possible.', 'assets-pack' ) ?>
            <p class="description">
                <?php esc_html_e( 'After this setting is checked or unchecked please clear assets.', 'assets-pack' ) ?>
            </p>
        </label>
    <?php }
    
    /**
     * Validate and sanitize settings.
     *
     * @param array $settings
     * @return array Sanitized settings.
     */
    public function validate_settings( $settings ) {
        if ( isset( $_POST['clear'] ) ) {
            $this->clear_assets();
            add_settings_error( 'assets_dir', 'success', __( 'Assets has been clean.', 'assets-pack' ), 'updated' );
            return $this->get_setting();
        }
        
        $settings['enable_js']      = !empty( $settings['enable_js'] );
        $settings['debug_js']       = !empty( $settings['debug_js'] );
        $settings['enable_css']     = !empty( $settings['enable_css'] );
        $settings['debug_css']      = !empty( $settings['debug_css'] );
        $settings['css_inline_url'] = !empty( $settings['css_inline_url'] );
        
        if ( !$settings['enable_js'] && $settings['debug_js'] ) {
            add_settings_error( 'debug_js', 'debug_js', __( 'Js debug is useless when js aggregation is disabled.', 'assets-pack' ) );
        }
        
        if ( !$settings['enable_css'] && $settings['debug_css'] ) {
            add_settings_error( 'debug_css', 'debug_css', __( 'Css debug is useless when css aggregation is disabled.', 'assets-pack' ) );
        }

        $dir = $this->validate_assets_dir( $settings['assets_dir'] );
        if ( $dir === false ) {
            $settings['assets_dir'] = $this->get_setting( 'assets_dir' );
            add_settings_error( 'assets_dir', 'error', __( 'Could not create directory or directory is not writeable.', 'assets-pack' ) );
        } else {
            $settings['assets_dir'] = $dir;
        }
        
        // TODO: needs to be proper url validating.
        $settings['assets_url'] = rtrim( $settings['assets_url'], '/' ) . '/';
        
        if ( is_string( $settings['skip_js'] ) ) {
            $settings['skip_js'] = array_filter(
                array_map( 'trim', explode( ',', $settings['skip_js'] ) )
            );
        }

        if ( is_string( $settings['skip_css'] ) ) {
            $settings['skip_css'] = array_filter(
                array_map( 'trim', explode( ',', $settings['skip_css'] ) )
            );
        }

        return $settings;
    }
    
    /**
     * Validate assets directory.
     *
     * @param string $dir Directory.
     * @return string|false Full directory path or false if cannot create
     *                      directory or directory is not writable.
     */
    public function validate_assets_dir( $dir = null ) {
        if ( $dir === null ) {
            $dir = $this->get_setting( 'assets_dir' );
        }
        
        // TODO: normalize path.
        if ( !is_dir( $dir ) && !mkdir( $dir ) ) {
            return false;
        }
        if ( !is_writable( $dir ) ) {
            return false;
        }
        
        return rtrim( $dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
    }

    /**
     * Get setting field value.
     *
     * @param string $field Optional. Field name.
     * @param mixed $default
     * @return mixed
     */
    public function get_setting( $field = null, $default = false ) {

        if ( empty( $this->settings ) ) {
            $this->settings = get_option( 'assets_pack' );
        }

        if ( $field === null ) {
            return $this->settings;
        }

        return isset( $this->settings[$field] ) ? $this->settings[$field] : $default;
    }
    
    /**
     * Set setting field value.
     *
     * @param string $field
     * @param mixed $value
     * @return true|string Success or validation error message.
     */
    public function set_setting( $field, $value ) {
        global $wp_settings_errors;
        
        $settings = $this->get_setting();
        if ( !isset( $settings[$field] ) ) {
            return 'Setting field not registered.';
        }

        $settings[$field] = $value;

        $settings = $this->validate_settings( $settings );

        if ( !empty( $wp_settings_errors ) ) {
            foreach ( $wp_settings_errors as $error ) {
                if ( $error['setting'] === $field ) {
                    return $error['message'];
                }
            }
        }

        // Update option in db.
        update_option( 'assets_pack', $settings );
        
        // Update object settings.
        $this->settings[$field] = $settings[$field];
        
        return true;
    }
    
    /**
     * Clears asset files.
     *
     * @param string $dir Assets dir.
     * @return bool
     */
    public function clear_assets( $dir = null ) {
        if ( $dir === null ) {
            $dir = $this->get_setting( 'assets_dir' );
        }
        
        $success = true;
        $pattern = $dir . '*.{js,css,js.debug,css.debug}';
        $files = glob( $pattern, GLOB_BRACE | GLOB_NOSORT );

        foreach ( $files as $file ) {
            if ( !unlink( $file ) ) {
                $success = false;
            }
        }
        
        return $success;
    }
}
