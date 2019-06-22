<?php
/*
  Plugin Name: Alitheia eDocs
  Version: 1.0.1
  Plugin URI: https://alitheiaholdings.com/
  Author: Alitheia Holdings, inc
  Author URI: https://alitheiaholdings.com/
  Description: Generate PDF document.
  Text Domain: Alitheia PDF Generator
  Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

class alitheia_edocs {

    var $plugin_version = '1.0';
    var $plugin_url;
    var $plugin_path;
    private $plugin_role = "alitheia";
    private $plugin_capability_name = "manage_options";
    /**
     * Class constructor for form generator, it generates wordpress specific data about plugin
     *
     */

    function __construct() {
        define('form_generator_VERSION', $this->plugin_version);
        define('form_generator_SITE_URL', site_url());
        define('form_generator_HOME_URL', home_url());
        define('form_generator_URL', $this->plugin_url());
        define('form_generator_PATH', $this->plugin_path());
        
        $debug_enabled = get_option('form_generator_enable_debug');
        if (isset($debug_enabled) && !empty($debug_enabled)) {
            define('form_generator_DEBUG', true);
        } else {
            define('form_generator_DEBUG', false);
        }
        $use_sandbox = get_option('form_generator_enable_testmode');
        if (isset($use_sandbox) && !empty($use_sandbox)) {
            define('form_generator_USE_SANDBOX', true);
        } else {
            define('form_generator_USE_SANDBOX', false);
        }
        define('form_generator_DEBUG_LOG_PATH', $this->debug_log_path());
        $this->plugin_includes();
        $this->loader_operations();
    }

    /**
     * Including required files for plugin as soon as __construct() is called
     *
     */
    function plugin_includes() {
        include_once('alitheia-forms.php');
        include_once('ajax-api.php');
    }

    /**
     * Necessary hooks to activate plugin
     *
     */
    function __register_hooks(){
        register_activation_hook(__FILE__, array($this, 'activate_handler'));
        register_deactivation_hook( __FILE__, array($this, 'deactivate_handler') );
    }

    function loader_operations() {
        // Plugin activation hooks
        $this->__register_hooks();

        add_action('plugins_loaded', array($this, 'plugins_loaded_handler'));
        if (is_admin()) {
            add_filter('plugin_action_links', array($this, 'add_plugin_action_links'), 10, 2);
        }
        add_action('admin_notices', array($this, 'admin_notice'));
        add_action('wp_enqueue_scripts', array($this, 'plugin_scripts'));
        add_action('admin_menu', array($this, 'add_options_menu'));
        add_action('init', array($this, 'plugin_init'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_filter('manage_alitheia_forms_posts_columns', 'alitheia_forms_columns');
        add_action('manage_alitheia_forms_posts_custom_column', 'form_generator_custom_column', 10, 2);
        add_shortcode('generate_form', 'tsf_generate');
    }

    function plugins_loaded_handler() {  //Runs when plugins_loaded action gets fired
        load_plugin_textdomain( 'alitheia-forms', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
        $this->check_upgrade();
    }

    function admin_notice() {
        if (form_generator_DEBUG) {  //debug is enabled. Check to make sure log file is writable
            $real_file = form_generator_DEBUG_LOG_PATH;
            if (!is_writeable($real_file)) {
                echo '<div class="updated"><p>' . __('alitheia Forms Debug log file is not writable. Please check to make sure that it has the correct file permission (ideally 644). Otherwise the plugin will not be able to write to the log file. The log file (log.txt) can be found in the root directory of the plugin - ', 'alitheia_forms') . '<code>' . form_generator_URL . '</code></p></div>';
            }
        }        
    }

    /**
     * Handling new role for specific users to have access to alitheia document generation plugin
     *  
     */
    private function handle_plugin_role_cap(string $plugin_role, bool $activate){
        
        // Check plugin for conflicts
        if(!is_plugin_active(__FILE__) && gettype(get_role( $plugin_role )) === "object" && $activate !== false){
             wp_die("We have role name conflict, aborting plugin activation!");
        }
        
        if($activate){
            if(($role = add_role( $plugin_role, __(ucfirst($plugin_role))." ".__("Member"), 
                array(
                    'read'          => true,
                    'edit_posts'    => true
                )
            ))){
                $role->add_cap($this->plugin_capability_name);
            }else{
                $role->remove_cap($his->plugin_capability_name);
            }
            
        }else{
            remove_role($plugin_role);
        }

        
    }

    function activate_handler() {
        add_option('form_generator_plugin_version', $this->plugin_version);
        add_option('form_generator_email', get_bloginfo('admin_email'));
		$this->handle_plugin_role_cap($this->plugin_role, true);
    }
	
    function deactivate_handler(){
        $this->handle_plugin_role_cap($this->plugin_role, false);
    }


    function plugin_init() {
        //register companies
        alitheia_forms_page();
        //process PayPal IPN
        form_generator_process_ipn();
        get_submited_params();
        
    }


    function check_upgrade() {
        if (is_admin()) {
            $plugin_version = get_option('form_generator_plugin_version');
            if (!isset($plugin_version) || $plugin_version != $this->plugin_version) {
                $this->activate_handler();
                update_option('form_generator_plugin_version', $this->plugin_version);
            }
        }
    }
 
    function add_meta_boxes() {
        add_meta_box('alitheia_forms_alitheia-box', __('Edit company', 'alitheia_forms'), 'alitheia_forms_meta_box', 'alitheia_forms', 'normal', 'high');
    }

    function plugin_scripts() {
        if (!is_admin()) {

        }
    }

    function plugin_url() {
        if ($this->plugin_url)
            return $this->plugin_url;
        return $this->plugin_url = plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__));
    }

    function plugin_path() {
        if ($this->plugin_path)
            return $this->plugin_path;
        return $this->plugin_path = untrailingslashit(plugin_dir_path(__FILE__));
    }

    function debug_log_path() {
        return form_generator_PATH . '/log.txt';
    }

    function add_plugin_action_links($links, $file) {
        if ($file == plugin_basename(dirname(__FILE__) . '/main.php')) {
            $links[] = '<a href="'.esc_url(admin_url('edit.php?post_type=alitheia_forms&page=alitheia_forms_settings')).'">'.__('Settings', 'alitheia_forms').'</a>';
        }
        return $links;
    }

    function add_options_menu() {
        if (is_admin()) {
            add_submenu_page('edit.php?post_type=alitheia_forms', __('Settings', 'alitheia_forms'), __('Settings', 'alitheia_forms'), 'manage_options', 'alitheia_forms_settings', array($this, 'options_page'));
            add_submenu_page('edit.php?post_type=alitheia_forms', __('Debug', 'alitheia_forms'), __('Debug', 'alitheia_forms'), 'manage_options', 'alitheia_forms_debug', array($this, 'debug_page'));
        }
    }

    function options_page() {
        $plugin_tabs = array(
            'alitheia_forms_settings' => __('General', 'alitheia_forms')
        );
        echo '<div class="wrap">' . screen_icon() . '<h2>'.__('alitheia Forms', 'alitheia_forms').' v' . form_generator_VERSION . '</h2>';
        $url = 'https://alitheia.media';
        $link_msg = sprintf( wp_kses( __( 'Please visit the <a target="_blank" href="%s">alitheia Forms</a> documentation page for usage instructions.', 'alitheia_forms' ), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( $url ) );
        echo '<div class="update-nag">'.$link_msg.'</div>';
        echo '<div id="poststuff"><div id="post-body">';

        if (isset($_GET['page'])) {
            $current = $_GET['page'];
            if (isset($_GET['action'])) {
                $current .= "&action=" . $_GET['action'];
            }
        }
        $content = '';
        $content .= '<h2 class="nav-tab-wrapper">';
        foreach ($plugin_tabs as $location => $tabname) {
            if ($current == $location) {
                $class = ' nav-tab-active';
            } else {
                $class = '';
            }
            $content .= '<a class="nav-tab' . $class . '" href="?post_type=alitheia_forms&page=' . $location . '">' . $tabname . '</a>';
        }
        $content .= '</h2>';
        echo $content;

        $this->general_settings();

        echo '</div></div>';
        echo '</div>';
    }

    function general_settings() {
        if (isset($_POST['form_generator_update_settings'])) {
            $nonce = $_REQUEST['_wpnonce'];
            if (!wp_verify_nonce($nonce, 'form_generator_general_settings')) {
                wp_die('Error! Nonce Security Check Failed! please save the settings again.');
            }
            update_option('form_generator_enable_testmode', (isset($_POST["enable_testmode"]) && $_POST["enable_testmode"] == '1') ? '1' : '');
            update_option('form_generator_email', trim($_POST["paypal_email"]));
            update_option('form_generator_currency_code', trim($_POST["currency_code"]));
            echo '<div id="message" class="updated fade"><p><strong>';
            echo __('Settings Saved', 'alitheia_forms').'!';
            echo '</strong></p></div>';
        }
        ?>

        <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
            <?php wp_nonce_field('form_generator_general_settings'); ?>

            <table class="form-table">

                <tbody>

                    <tr valign="top">
                        <th scope="row"><?Php _e('Enable Test Mode', 'alitheia_forms');?></th>
                        <td> <fieldset><legend class="screen-reader-text"><span>Enable Test Mode</span></legend><label for="enable_testmode">
                                    <input name="enable_testmode" type="checkbox" id="enable_testmode" <?php if (get_option('form_generator_enable_testmode') == '1') echo ' checked="checked"'; ?> value="1">
                                    <?Php _e('Check this option if you want to enable sandbox for testing', 'alitheia_forms');?></label>
                            </fieldset></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><label for="paypal_email"><?Php _e('Admin Email', 'alitheia_forms');?></label></th>
                        <td><input name="paypal_email" type="text" id="paypal_email" value="<?php echo get_option('form_generator_email'); ?>" class="regular-text">
                            <p class="description"><?Php _e('Your admin email address', 'alitheia_forms');?></p></td>
                    </tr>

                    <!--<tr valign="top">
                        <th scope="row"><label for="currency_code"><?Php _e('Currency Code', 'alitheia_forms');?></label></th>
                        <td><input name="currency_code" type="text" id="currency_code" value="<?php echo get_option('form_generator_currency_code'); ?>" class="regular-text">
                            <p class="description"><?Php _e('The currency of the payment', 'alitheia_forms');?> (<?Php _e('example', 'alitheia_forms');?>: USD, CAD, GBP, EUR)</p></td>
                    </tr>-->

                </tbody>

            </table>

            <p class="submit"><input type="submit" name="form_generator_update_settings" id="form_generator_update_settings" class="button button-primary" value="<?Php _e('Save Changes', 'alitheia_forms');?>"></p></form>

        <?php
    }

    function debug_page() {
        ?>
        <div class="wrap">
            <h2><?Php _e('alitheia Forms Debug Log', 'alitheia_forms');?></h2>
            <div id="poststuff">
                <div id="post-body">
                    <?php
                    if (isset($_POST['form_generator_update_log_settings'])) {
                        $nonce = $_REQUEST['_wpnonce'];
                        if (!wp_verify_nonce($nonce, 'form_generator_debug_log_settings')) {
                            wp_die('Error! Nonce Security Check Failed! please save the settings again.');
                        }
                        update_option('form_generator_enable_debug', (isset($_POST["enable_debug"]) && $_POST["enable_debug"] == '1') ? '1' : '');
                        echo '<div id="message" class="updated fade"><p>'.__('Settings Saved', 'alitheia_forms').'!</p></div>';
                    }
                    if (isset($_POST['form_generator_reset_log'])) {
                        $nonce = $_REQUEST['_wpnonce'];
                        if (!wp_verify_nonce($nonce, 'form_generator_reset_log_settings')) {
                            wp_die('Error! Nonce Security Check Failed! please save the settings again.');
                        }
                        if (form_generator_reset_log()) {
                            echo '<div id="message" class="updated fade"><p>'.__('Debug log file has been reset', 'alitheia_forms').'!</p></div>';
                        } else {
                            echo '<div id="message" class="error"><p>'.__('Debug log file could not be reset', 'alitheia_forms').'!</p></div>';
                        }
                    }
                    $real_file = form_generator_DEBUG_LOG_PATH;
                    $content = file_get_contents($real_file);
                    $content = esc_textarea($content);
                    ?>
                    <div id="template"><textarea cols="70" rows="25" name="form_generator_log" id="form_generator_log"><?php echo $content; ?></textarea></div>
                    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                        <?php wp_nonce_field('form_generator_debug_log_settings'); ?>
                        <table class="form-table">
                            <tbody>
                                <tr valign="top">
                                    <th scope="row"><?Php _e('Enable Debug', 'alitheia_forms');?></th>
                                    <td> <fieldset><legend class="screen-reader-text"><span>Enable Debug</span></legend><label for="enable_debug">
                                                <input name="enable_debug" type="checkbox" id="enable_debug" <?php if (get_option('form_generator_enable_debug') == '1') echo ' checked="checked"'; ?> value="1">
                                                <?Php _e('Check this option if you want to enable debug', 'alitheia_forms');?></label>
                                        </fieldset></td>
                                </tr>

                            </tbody>

                        </table>
                        <p class="submit"><input type="submit" name="form_generator_update_log_settings" id="form_generator_update_log_settings" class="button button-primary" value="<?Php _e('Save Changes', 'alitheia_forms');?>"></p>
                    </form>
                    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                        <?php wp_nonce_field('form_generator_reset_log_settings'); ?>
                        <p class="submit"><input type="submit" name="form_generator_reset_log" id="form_generator_reset_log" class="button" value="<?Php _e('Reset Log', 'alitheia_forms');?>"></p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

}

$GLOBALS['alitheia_forms'] = new alitheia_forms();


