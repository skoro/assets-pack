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
            'Assets Pack',
            'Assets',
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
                'assets_dir' => $upload_dir['basedir'] . '/assets',
                'assets_url' => $upload_dir['baseurl'] . '/assets',
            	'skip_js' => [],
                'enable_js' => false,
                'debug_js' => false,
            	'skip_css' => [],
                'enable_css' => false,
                'css_inline_url' => false,
                'debug_css' => false,
            ],
        ] );

        // Main options.
        add_settings_section( 'main', '', '__return_false', $this->settings_page );
        add_settings_field( 'assets_dir', 'Assets store location', [$this, 'field_assets_store'], $this->settings_page, 'main' );
        add_settings_field( 'assets_url', 'Assets URL', [$this, 'field_assets_url'], $this->settings_page, 'main' );
        
        // Javascript options.
        add_settings_section( 'js', 'JavaScript aggregation', '__return_false', $this->settings_page );
        add_settings_field( 'enable_js', 'JS aggregation', [$this, 'field_enable_js'], $this->settings_page, 'js' );
        add_settings_field( 'skip_js', 'Skip scripts', [$this, 'field_skip_js'], $this->settings_page, 'js' );
        add_settings_field( 'debug_js', 'Debug', [$this, 'field_debug_js'], $this->settings_page, 'js' );
        
        // CSS options.
        add_settings_section( 'css', 'CSS aggregation', '__return_false', $this->settings_page );
        add_settings_field( 'enable_css', 'CSS aggregation', [$this, 'field_enable_css'], $this->settings_page, 'css' );
        add_settings_field( 'skip_css', 'Skip styles', [$this, 'field_skip_css'], $this->settings_page, 'css' );
        add_settings_field( 'css_inline_url', 'Convert inline urls', [$this, 'field_css_inline_url'], $this->settings_page, 'css' );
        add_settings_field( 'debug_css', 'Debug', [$this, 'field_debug_css'], $this->settings_page, 'css' );
    }
    
    /**
     * Settings page callback.
     */
    public function settings_page() { ?>
        <div class="wrap">
            <h1>Assets pack</h1>
            <form method="POST" action="options.php">
                <?php settings_fields( 'assets_pack' ) ?>
                <?php do_settings_sections( $this->settings_page ) ?>
                <?php submit_button( 'Update settings' ) ?>
            </form>
        </div>
    <?php }
    
    public function field_skip_js() { ?>
    	<input type="text" name="assets_pack[skip_js]" value="<?= esc_attr( implode( ',', $this->get_setting( 'skip_js', [] ) ) ) ?>" size="64"/>
    	<p class="description">
            Do not include these scripts to assets file.
            You should enter handle names separated by commas.
            You can get names from a .js.debug file.
        </p>
    <?php }
    
    public function field_enable_js() { ?>
        <label>
            <input type="checkbox" name="assets_pack[enable_js]" value="true" <?php checked( $this->get_setting( 'enable_js' ) ) ?>/>
            <?php esc_html_e( 'Enabling aggregation all javascripts will concatenated to one asset bundle.' ) ?>
        </label>
    <?php }
    
    public function field_skip_css() { ?>
        <input type="text" name="assets_pack[skip_css]" value="<?= esc_attr( implode( ',', $this->get_setting( 'skip_css', [] ) ) ) ?>" size="64"/>
        <p class="description">
            Do not include these styles to assets file.
            You should enter handle names separated by commas.
            You can get names from a .css.debug file.
        </p>
    <?php }
    
    public function field_enable_css() { ?>
        <label>
            <input type="checkbox" name="assets_pack[enable_css]" value="true" <?php checked( $this->get_setting( 'enable_css' ) ) ?>/>
            <?php esc_html_e( 'Enabling aggregation all CSS styles will concatenated to one asset bundle.' ) ?>
        </label>
    <?php }
    
    public function field_debug_js() { ?>
        <label>
            <input type="checkbox" name="assets_pack[debug_js]" value="true" <?php checked( $this->get_setting( 'debug_js') ) ?>/>
            <?php esc_html_e( 'Create along with js asset file with .js.debug extension which contains script names.' ) ?>
        </label>
    <?php }
    
    public function field_debug_css() { ?>
        <label>
            <input type="checkbox" name="assets_pack[debug_css]" value="true" <?php checked( $this->get_setting( 'debug_css' ) ) ?>/>
            <?php esc_html_e( 'Create along with css asset file with .css.debug extension which contains styles names.' ) ?>
        </label>
    <?php }
    
    public function field_assets_store() { ?>
        <input type="text" name="assets_pack[assets_dir]" value="<?= $this->get_setting( 'assets_dir' ) ?>" size="64"/>
        <p>
            <button name="clear" class="button">Clear assets</button>
        </p>
    <?php }
    
    public function field_assets_url() { ?>
        <input type="text" name="assets_pack[assets_url]" value="<?= $this->get_setting( 'assets_url' ) ?>" size="64"/>
    <?php }
    
    public function field_css_inline_url() { ?>
        <label>
            <input type="checkbox" name="assets_pack[css_inline_url]" value="true" <?php checked( $this->get_setting( 'css_inline_url' ) ) ?>/>
            <?php esc_html_e( 'Convert all url() values to local if it possible.' ) ?>
            <p class="description">
                After this setting is checked or unchecked please clear assets.
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
            add_settings_error( 'assets_dir', 'success', 'Assets clean.', 'updated' );
            return $this->get_setting();
        }
        
        $settings['enable_js']      = !empty( $settings['enable_js'] );
        $settings['debug_js']       = !empty( $settings['debug_js'] );
        $settings['enable_css']     = !empty( $settings['enable_css'] );
        $settings['debug_css']      = !empty( $settings['debug_css'] );
        $settings['css_inline_url'] = !empty( $settings['css_inline_url'] );
        
        if ( !$settings['enable_js'] && $settings['debug_js'] ) {
            add_settings_error( 'debug_js', 'debug_js', 'Js debug is useless when js aggregation is disabled.' );
        }
        
        if ( !$settings['enable_css'] && $settings['debug_css'] ) {
            add_settings_error( 'debug_css', 'debug_css', 'Css debug is useless when css aggregation is disabled.' );
        }

        $dir = $this->validate_assets_dir( $settings['assets_dir'] );
        if ( $dir === false ) {
            $settings['assets_dir'] = $this->get_setting( 'assets_dir' );
            add_settings_error( 'assets_dir', 'error', 'Could not create directory or directory is not writeable.' );
        } else {
            $settings['assets_dir'] = $dir;
        }
        
        // TODO: needs to be proper url validating.
        $settings['assets_url'] = rtrim( $settings['assets_url'], '/' ) . '/';
        
        $settings['skip_js'] = array_filter(
            array_map( 'trim', explode( ',', $settings['skip_js'] ) )
        );
        $settings['skip_css'] = array_filter(
            array_map( 'trim', explode( ',', $settings['skip_css'] ) )
        );

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
