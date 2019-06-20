<?php
/*
  Plugin Name: Tsunami Forms
  Version: 1.0.1
  Plugin URI: https://tsunami.media/
  Author: Tsunami Media, LLC
  Author URI: https://tsunami.media/
  Description: Generate PDF document based on form data.
  Text Domain: Tsunami PDF Generator
  Domain Path: /languages
 */

if (!defined('ABSPATH'))
    exit;


define()

class tsunami_forms {

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
        include_once('tsunami-forms.php');
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
        add_filter('manage_tsunami_forms_posts_columns', 'tsunami_forms_columns');
        add_action('manage_tsunami_forms_posts_custom_column', 'form_generator_custom_column', 10, 2);
        add_shortcode('generate_form', 'tsf_generate');
    }

    function plugins_loaded_handler() {  //Runs when plugins_loaded action gets fired
        load_plugin_textdomain( 'tsunami-forms', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
        $this->check_upgrade();
    }

    function admin_notice() {
        if (form_generator_DEBUG) {  //debug is enabled. Check to make sure log file is writable
            $real_file = form_generator_DEBUG_LOG_PATH;
            if (!is_writeable($real_file)) {
                echo '<div class="updated"><p>' . __('Tsunami Forms Debug log file is not writable. Please check to make sure that it has the correct file permission (ideally 644). Otherwise the plugin will not be able to write to the log file. The log file (log.txt) can be found in the root directory of the plugin - ', 'tsunami_forms') . '<code>' . form_generator_URL . '</code></p></div>';
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

        remove_menu_page( 'edit.php' );                   //Posts  
        
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
        tsunami_forms_page();
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
        add_meta_box('tsunami_forms_tsunami-box', __('Edit company', 'tsunami_forms'), 'tsunami_forms_meta_box', 'tsunami_forms', 'normal', 'high');
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
            $links[] = '<a href="'.esc_url(admin_url('edit.php?post_type=tsunami_forms&page=tsunami_forms_settings')).'">'.__('Settings', 'tsunami_forms').'</a>';
        }
        return $links;
    }

    function add_options_menu() {
        if (is_admin()) {
            add_submenu_page('edit.php?post_type=tsunami_forms', __('Settings', 'tsunami_forms'), __('Settings', 'tsunami_forms'), 'manage_options', 'tsunami_forms_settings', array($this, 'options_page'));
            add_submenu_page('edit.php?post_type=tsunami_forms', __('Debug', 'tsunami_forms'), __('Debug', 'tsunami_forms'), 'manage_options', 'tsunami_forms_debug', array($this, 'debug_page'));
        }
    }

    function options_page() {
        $plugin_tabs = array(
            'tsunami_forms_settings' => __('General', 'tsunami_forms')
        );
        echo '<div class="wrap">' . screen_icon() . '<h2>'.__('Tsunami Forms', 'tsunami_forms').' v' . form_generator_VERSION . '</h2>';
        $url = 'https://tsunami.media';
        $link_msg = sprintf( wp_kses( __( 'Please visit the <a target="_blank" href="%s">Tsunami Forms</a> documentation page for usage instructions.', 'tsunami_forms' ), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( $url ) );
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
            $content .= '<a class="nav-tab' . $class . '" href="?post_type=tsunami_forms&page=' . $location . '">' . $tabname . '</a>';
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
            echo __('Settings Saved', 'tsunami_forms').'!';
            echo '</strong></p></div>';
        }
        ?>

        <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
            <?php wp_nonce_field('form_generator_general_settings'); ?>

            <table class="form-table">

                <tbody>

                    <tr valign="top">
                        <th scope="row"><?Php _e('Enable Test Mode', 'tsunami_forms');?></th>
                        <td> <fieldset><legend class="screen-reader-text"><span>Enable Test Mode</span></legend><label for="enable_testmode">
                                    <input name="enable_testmode" type="checkbox" id="enable_testmode" <?php if (get_option('form_generator_enable_testmode') == '1') echo ' checked="checked"'; ?> value="1">
                                    <?Php _e('Check this option if you want to enable sandbox for testing', 'tsunami_forms');?></label>
                            </fieldset></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><label for="paypal_email"><?Php _e('Admin Email', 'tsunami_forms');?></label></th>
                        <td><input name="paypal_email" type="text" id="paypal_email" value="<?php echo get_option('form_generator_email'); ?>" class="regular-text">
                            <p class="description"><?Php _e('Your admin email address', 'tsunami_forms');?></p></td>
                    </tr>

                    <!--<tr valign="top">
                        <th scope="row"><label for="currency_code"><?Php _e('Currency Code', 'tsunami_forms');?></label></th>
                        <td><input name="currency_code" type="text" id="currency_code" value="<?php echo get_option('form_generator_currency_code'); ?>" class="regular-text">
                            <p class="description"><?Php _e('The currency of the payment', 'tsunami_forms');?> (<?Php _e('example', 'tsunami_forms');?>: USD, CAD, GBP, EUR)</p></td>
                    </tr>-->

                </tbody>

            </table>

            <p class="submit"><input type="submit" name="form_generator_update_settings" id="form_generator_update_settings" class="button button-primary" value="<?Php _e('Save Changes', 'tsunami_forms');?>"></p></form>

        <?php
    }

    function debug_page() {
        ?>
        <div class="wrap">
            <h2><?Php _e('Tsunami Forms Debug Log', 'tsunami_forms');?></h2>
            <div id="poststuff">
                <div id="post-body">
                    <?php
                    if (isset($_POST['form_generator_update_log_settings'])) {
                        $nonce = $_REQUEST['_wpnonce'];
                        if (!wp_verify_nonce($nonce, 'form_generator_debug_log_settings')) {
                            wp_die('Error! Nonce Security Check Failed! please save the settings again.');
                        }
                        update_option('form_generator_enable_debug', (isset($_POST["enable_debug"]) && $_POST["enable_debug"] == '1') ? '1' : '');
                        echo '<div id="message" class="updated fade"><p>'.__('Settings Saved', 'tsunami_forms').'!</p></div>';
                    }
                    if (isset($_POST['form_generator_reset_log'])) {
                        $nonce = $_REQUEST['_wpnonce'];
                        if (!wp_verify_nonce($nonce, 'form_generator_reset_log_settings')) {
                            wp_die('Error! Nonce Security Check Failed! please save the settings again.');
                        }
                        if (form_generator_reset_log()) {
                            echo '<div id="message" class="updated fade"><p>'.__('Debug log file has been reset', 'tsunami_forms').'!</p></div>';
                        } else {
                            echo '<div id="message" class="error"><p>'.__('Debug log file could not be reset', 'tsunami_forms').'!</p></div>';
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
                                    <th scope="row"><?Php _e('Enable Debug', 'tsunami_forms');?></th>
                                    <td> <fieldset><legend class="screen-reader-text"><span>Enable Debug</span></legend><label for="enable_debug">
                                                <input name="enable_debug" type="checkbox" id="enable_debug" <?php if (get_option('form_generator_enable_debug') == '1') echo ' checked="checked"'; ?> value="1">
                                                <?Php _e('Check this option if you want to enable debug', 'tsunami_forms');?></label>
                                        </fieldset></td>
                                </tr>

                            </tbody>

                        </table>
                        <p class="submit"><input type="submit" name="form_generator_update_log_settings" id="form_generator_update_log_settings" class="button button-primary" value="<?Php _e('Save Changes', 'tsunami_forms');?>"></p>
                    </form>
                    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                        <?php wp_nonce_field('form_generator_reset_log_settings'); ?>
                        <p class="submit"><input type="submit" name="form_generator_reset_log" id="form_generator_reset_log" class="button" value="<?Php _e('Reset Log', 'tsunami_forms');?>"></p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

}

$GLOBALS['tsunami_forms'] = new tsunami_forms();

function form_generator_button_handler($atts) {
    $testmode = get_option('form_generator_enable_testmode');
    if (isset($testmode) && !empty($testmode)) {
        $atts['env'] = "sandbox";
    }
    $atts['callback'] = home_url() . '/?form_generator_ipn=1';
    $paypal_email = get_option('form_generator_email');
    $currency = get_option('form_generator_currency_code');
    if (isset($atts['currency']) && !empty($atts['currency'])) {

    } else {
        $atts['currency'] = $currency;
    }
    $target = '';
    if(isset($atts['target']) && !empty($atts['target'])) {
        $target = $atts['target'];
        unset($atts['target']);
    }
    $id = uniqid();
    $button_code = '<div id="'.$id.'">';
    $button_code .= '<script async src="' . form_generator_URL . '/lib/paypal-button.min.js?merchant=' . $paypal_email . '"';
    foreach ($atts as $key => $value) {
        if($key=='button_image'){
            continue;
        }
        $button_code .= ' data-' . $key . '="' . $value . '"';
    }
    //$button_code .= 'async';
    $button_code .= '></script>';
    $button_code .= '</div>';
    if(isset($atts['button_image']) && filter_var($atts['button_image'], FILTER_VALIDATE_URL)){
        $button_image_url = esc_url($atts['button_image']);
        $output = <<<EOT
        <script>
        /* <![CDATA[ */
            jQuery(document).ready(function($){
                $(function(){
                    $('div#$id button').replaceWith('<input type="image" src="$button_image_url">');
                });
            });
            /* ]]> */
        </script>
EOT;
        $button_code .= $output;
    }
    if(!empty($target)){
        $output = <<<EOT
        <script>
        /* <![CDATA[ */
            jQuery(document).ready(function($){
                $(function(){
                    $('div#$id form').prop('target', '$target');
                });
            });
            /* ]]> */
        </script>
EOT;
        $button_code .= $output;
    }
    return $button_code;
}

function form_generator_debug_log($msg, $success, $end = false) {
    if (!form_generator_DEBUG) {
        return;
    }
    $date_time = date('F j, Y g:i a');//the_date('F j, Y g:i a', '', '', FALSE);
    $text = '[' . $date_time . '] - ' . (($success) ? 'SUCCESS :' : 'FAILURE :') . $msg . "\n";
    if ($end) {
        $text .= "\n------------------------------------------------------------------\n\n";
    }
    // Write to log.txt file
    $fp = fopen(form_generator_DEBUG_LOG_PATH, 'a');
    fwrite($fp, $text);
    fclose($fp);  // close file
}

function form_generator_debug_log_array($array_msg, $success, $end = false) {
    if (!form_generator_DEBUG) {
        return;
    }
    $date_time = date('F j, Y g:i a');//the_date('F j, Y g:i a', '', '', FALSE);
    $text = '[' . $date_time . '] - ' . (($success) ? 'SUCCESS :' : 'FAILURE :') . "\n";
    ob_start();
    print_r($array_msg);
    $var = ob_get_contents();
    ob_end_clean();
    $text .= $var;
    if ($end) {
        $text .= "\n------------------------------------------------------------------\n\n";
    }
    // Write to log.txt file
    $fp = fopen(form_generator_DEBUG_LOG_PATH, 'a');
    fwrite($fp, $text);
    fclose($fp);  // close filee
}

function form_generator_reset_log() {
    $log_reset = true;
    $date_time = date('F j, Y g:i a');//the_date('F j, Y g:i a', '', '', FALSE);
    $text = '[' . $date_time . '] - SUCCESS : Log reset';
    $text .= "\n------------------------------------------------------------------\n\n";
    $fp = fopen(form_generator_DEBUG_LOG_PATH, 'w');
    if ($fp != FALSE) {
        @fwrite($fp, $text);
        @fclose($fp);
    } else {
        $log_reset = false;
    }
    return $log_reset;
}