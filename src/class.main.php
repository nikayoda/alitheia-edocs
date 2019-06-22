<?php

namespace Alitheia\Main;

use Alitheia\Html\Forms;
use Alitheia\Access\PluginAccess;

class MainController
{
    public $plugin_url;
    public $plugin_path;


    /**
     * Class constructor for form generator, it generates wordpress specific data about plugin
     *
     */

    function __construct()
    {

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

        $this->html_forms = new Forms();

        define('form_generator_DEBUG_LOG_PATH', $this->debug_log_path());
        $this->plugin_includes();
        $this->loader_operations();
    }

    function loader_operations()
    {
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
        add_filter('manage_alitheia_edocs_posts_columns', 'alitheia_edocs_columns');
        add_action('manage_alitheia_edocs_posts_custom_column', 'form_generator_custom_column', 10, 2);
        add_shortcode('generate_form', 'tsf_generate');
    }

    /**
     * Necessary hooks to activate plugin
     *
     */
    function __register_hooks()
    {
        register_activation_hook(__FILE__, array($this, 'activate_handler'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_handler'));
    }

    function plugins_loaded_handler()
    {  //Runs when plugins_loaded action gets fired
        load_plugin_textdomain('alitheia-forms', false, plugin_basename(dirname(__FILE__)) . '/languages');
        $this->check_upgrade();
    }

    function check_upgrade()
    {
        if (is_admin()) {
            $plugin_version = get_option('form_generator_plugin_version');
            if (!isset($plugin_version) || $plugin_version != $this->plugin_version) {
                $this->activate_handler();
                update_option('form_generator_plugin_version', $this->plugin_version);
            }
        }
    }

    function activate_handler()
    {
        add_option('form_generator_plugin_version', $this->plugin_version);
        add_option('form_generator_email', get_bloginfo('admin_email'));
        $this->handle_plugin_role_cap($this->plugin_role, true);
    }

    function admin_notice()
    {
        if (form_generator_DEBUG) {  //debug is enabled. Check to make sure log file is writable
            $real_file = form_generator_DEBUG_LOG_PATH;
            if (!is_writeable($real_file)) {
                echo '<div class="updated"><p>' . __(self::$plugin_name . 'Debug log file is not writable. Please check to make sure that it has the correct file permission (ideally 644). Otherwise the plugin will not be able to write to the log file. The log file (log.txt) can be found in the root directory of the plugin - ', 'alitheia_edocs') . '<code>' . form_generator_URL . '</code></p></div>';
            }
        }
    }

    function deactivate_handler()
    {
        $this->handle_plugin_role_cap($this->plugin_role, false);
    }

    function plugin_init()
    {

        $alitheia_edocs_handler = new alitheia_edocs_handler();

        // Register needed methods
        $alitheia_edocs_handler->alitheia_edocs_page();
        $alitheia_edocs_handler->get_submited_params();

    }

    function add_meta_boxes()
    {
        add_meta_box('alitheia_edocs_alitheia-box', __('Edit company', 'alitheia_edocs'), 'alitheia_edocs_meta_box', 'alitheia_edocs', 'normal', 'high');
    }

    function plugin_scripts()
    {
        if (!is_admin()) {

        }
    }

    function plugin_url()
    {
        if ($this->plugin_url)
            return $this->plugin_url;
        return $this->plugin_url = plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__));
    }

    function plugin_path()
    {
        if ($this->plugin_path)
            return $this->plugin_path;
        return $this->plugin_path = untrailingslashit(plugin_dir_path(__FILE__));
    }


    function add_plugin_action_links($links, $file)
    {
        if ($file == plugin_basename(dirname(__FILE__) . '/main.php')) {
            $links[] = '<a href="' . esc_url(admin_url('edit.php?post_type=alitheia_edocs&page=alitheia_edocs_settings')) . '">' . __('Settings', 'alitheia_edocs') . '</a>';
        }
        return $links;
    }

    function add_options_menu()
    {
        if (is_admin()) {
            add_submenu_page('edit.php?post_type=alitheia_edocs', __('Settings', 'alitheia_edocs'), __('Settings', 'alitheia_edocs'), 'manage_options', 'alitheia_edocs_settings', array($this, 'options_page'));
            add_submenu_page('edit.php?post_type=alitheia_edocs', __('Debug', 'alitheia_edocs'), __('Debug', 'alitheia_edocs'), 'manage_options', 'alitheia_edocs_debug', array($this, 'debug_page'));
        }
    }

    function options_page()
    {
        $plugin_tabs = array(
            'alitheia_edocs_settings' => __('General', 'alitheia_edocs')
        );
        echo '<div class="wrap">' . screen_icon() . '<h2>' . __(self::$plugin_name, 'alitheia_edocs') . ' v' . form_generator_VERSION . '</h2>';
        $url = 'https://alitheia.media';
        $link_msg = sprintf(wp_kses(__('Please visit the <a target="_blank" href="%s">' . self::$plugin_name . '</a> documentation page for usage instructions.', 'alitheia_edocs'), array('a' => array('href' => array(), 'target' => array()))), esc_url($url));
        echo '<div class="update-nag">' . $link_msg . '</div>';
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
            $content .= '<a class="nav-tab' . $class . '" href="?post_type=alitheia_edocs&page=' . $location . '">' . $tabname . '</a>';
        }
        $content .= '</h2>';
        echo $content;

        $this->general_settings();

        echo '</div></div>';
        echo '</div>';
    }

    function general_settings()
    {
        if (isset($_POST['form_generator_update_settings'])) {
            $nonce = $_REQUEST['_wpnonce'];
            if (!wp_verify_nonce($nonce, 'form_generator_general_settings')) {
                wp_die('Error! Nonce Security Check Failed! please save the settings again.');
            }
            update_option('form_generator_enable_testmode', (isset($_POST["enable_testmode"]) && $_POST["enable_testmode"] == '1') ? '1' : '');
            update_option('form_generator_email', trim($_POST["paypal_email"]));
            update_option('form_generator_currency_code', trim($_POST["currency_code"]));
            echo '<div id="message" class="updated fade"><p><strong>';
            echo __('Settings Saved', 'alitheia_edocs') . '!';
            echo '</strong></p></div>';
        }
        $this->html_forms->general_settings_html();
    }

    function debug_page()
    {
        $this->html_forms->debug_page_html();
    }

}