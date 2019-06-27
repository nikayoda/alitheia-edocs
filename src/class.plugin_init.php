<?php

namespace Alitheia\IPlugin;



use Spipu\Html2Pdf\Html2Pdf;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;


class Init implements IInit
{
    public $plugin_url;
    public $plugin_path;
    private $handler;
    private $plugin_access;
    private $html;

    /**
     * Class constructor for form generator, it generates wordpress specific data about plugin
     * @param Handler $handler
     * @param PluginAccess $plugin_access
     * @param Html $html
     */
    public function __construct(Handler $handler, PluginAccess $plugin_access, Html $html){

        // Assigning method params to class params
        $this->handler          = $handler;
        $this->plugin_access    = $plugin_access;
        $this->html             = $html;

        $debug_enabled = get_option(plugin_name_singular.'_enable_debug');
        if (isset($debug_enabled) && !empty($debug_enabled)) {
            define(plugin_name_singular.'_debug', true);
        } else {
            define(plugin_name_singular.'_debug', false);
        }
        $use_sandbox = get_option(plugin_name_singular.'_enable_testmode');
        if (isset($use_sandbox) && !empty($use_sandbox)) {
            define(plugin_name_singular.'_use_sandbox', true);
        } else {
            define(plugin_name_singular.'use_sandbox', false);
        }

        $this->loader_operations();
    }


    /**
     * Plugin Loader
     */
    public function loader_operations()
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
        add_action('add_meta_boxes', array($this, 'add_meta_boxess'));
        add_filter('manage_alitheia_edocs_posts_columns', array($this->handler, 'plugin_columns'));
        add_action('manage_alitheia_edocs_posts_custom_column', array($this->handler,'plugin_custom_column', 10, 2));
        add_shortcode('generate_form', array($this->handler, 'tsf_generate'));
    }

    /**
     * Necessary hooks to activate plugin
     *
     */
    public function __register_hooks()
    {
        register_activation_hook(__FILE__, array($this, 'activate_handler'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_handler'));
    }


    /**
     * Loading necessary stuff for plugin
     *
     */
    public function plugins_loaded_handler()
    {  //Runs when plugins_loaded action gets fired
        load_plugin_textdomain(plugin_name_singular, false, plugin_basename(dirname(__FILE__)) . '/languages');
        $this->check_upgrade();
    }


    /**
     * Check for plugin upgrades
     *
     */
    public function check_upgrade()
    {
        if (is_admin()) {
            $plugin_version = get_option(plugin_name_singular.'_plugin_version');
            if (!isset($plugin_version) || $plugin_version != plugin_version) {
                $this->activate_handler();
                update_option(plugin_name_singular.'_plugin_version', plugin_version);
            }
        }
    }


    /**
     * Add, or remove necessary options from database
     *
     * @param $enable_options
     */
    public function add_remove_options($enable_options)
    {
        if($enable_options){
            add_option(plugin_name_singular.'_plugin_version',plugin_version);
            add_option(plugin_name_singular.'_email', get_bloginfo('admin_email'));
        }else{

            // Unnecessary options are being deleted
            delete_option(plugin_name_singular.'_plugin_version');
            delete_option(plugin_name_singular.'_email');
        }
    }
    /**
     * Activation hook
     */
    public function activate_handler()
    {
        $this->add_remove_options(true);
        $this->plugin_access->register_role_cap(plugin_role, true);
    }

    /**
     * De-activation hook
     */
    public function deactivate_handler()
    {
        $this->add_remove_options(false);
        $this->plugin_access->register_role_cap(plugin_role, false);
    }


    /**
     * Admin notice for debug mode
     */
    public function admin_notice()
    {
        if (${plugin_name_singular.'_debug'}) {  //debug is enabled. Check to make sure log file is writable
            $real_file = plugin_debug_log_path;
            if (!is_writeable($real_file)) {
                echo '<div class="updated"><p>' . __(plugin_name. 'Debug log file is not writable. Please check to make sure that it has the correct file permission (ideally 644). Otherwise the plugin will not be able to write to the log file. The log file (log) can be found in the root directory of the plugin - ', plugin_name_singular) . '<code>' . plugin_path . '</code></p></div>';
            }
        }
    }
    public function plugin_init()
    {
        // Register needed methods
        $this->handler->plugin_page();
        $this->handler->get_submited_params();

    }

   /* public function add_meta_boxes()
    {
        add_meta_box('alitheia_edocs_alitheia-box', __('Edit company', plugin_name_singular), array($this, 'plugin_meta_box'), plugin_name_singular, 'normal', 'high');
    }*/
    function add_meta_boxes() {
        add_meta_box('alitheia_edocs_alitheia-box', __('Edit company', 'alitheia_edocs'), array($this, 'plugin_meta_box'), 'alitheia_edocs', 'normal', 'high');
    }

    public function plugin_scripts()
    {
        if (!is_admin()) {

        }
    }


    public function add_plugin_action_links($links, $file)
    {
        //die(plugin_basename(dirname(__FILE__) . '/class.plugin_init.php'));
        if ($file == plugin_basename(dirname(__FILE__) . '/class.plugin_init.php')) {
            $links[] = '<a href="' . esc_url(admin_url('edit.php?post_type=alitheia_edocs&page=alitheia_edocs_settings')) . '">' . __('Settings', plugin_name_singular) . '</a>';
        }
        return $links;
    }

    public function add_options_menu()
    {
        if (is_admin()) {
            add_submenu_page('edit.php?post_type=alitheia_edocs', __('Settings', plugin_name_singular), __('Settings', plugin_name_singular), 'manage_options', plugin_name_singular.'_settings', array($this, 'options_page'));
            //add_submenu_page('edit.php?post_type=alitheia_edocs', __('Debug', plugin_name_singular), __('Debug', plugin_name_singular), 'manage_options', 'alitheia_edocs_debug', array($this, 'debug_page'));
        }
    }

    public function options_page()
    {
        $plugin_tabs = array(
            plugin_name_singular.'_settings' => __('General', plugin_name_singular)
        );
        echo '<div class="wrap">' . screen_icon() . '<h2>' . __(plugin_name, plugin_name_singular) . ' v' . plugin_version . '</h2>';
        $url = 'https://alitheiaholdings.com/';
        $link_msg = sprintf(wp_kses(__('Please visit the <a target="_blank" href="%s">' . plugin_name. '</a> documentation page for usage instructions.', plugin_name_singular), array('a' => array('href' => array(), 'target' => array()))), esc_url($url));
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

    public function general_settings()
    {
        if (isset($_POST['form_generator_update_settings'])) {
            $nonce = $_REQUEST['_wpnonce'];
            if (!wp_verify_nonce($nonce, 'form_generator_general_settings')) {
                wp_die('Error! Nonce Security Check Failed! please save the settings again.');
            }
            update_option(plugin_name_singular.'_enable_testmode', (isset($_POST["enable_testmode"]) && $_POST["enable_testmode"] == '1') ? '1' : '');
            update_option(plugin_name_singular.'_email', trim($_POST["paypal_email"]));
            update_option(plugin_name_singular.'_currency_code', trim($_POST["currency_code"]));
            echo '<div id="message" class="updated fade"><p><strong>';
            echo __('Settings Saved', plugin_name_singular) . '!';
            echo '</strong></p></div>';
        }
        $this->html->general_settings_html();
    }

    public function debug_page()
    {
        $this->html->debug_page_html();
    }
    public function plugin_meta_box($post)
    {
        $tsf_name = get_post_meta($post->ID, '_tsf_name', true);
        $tsf_tel = get_post_meta($post->ID, '_tsf_tel', true);
        $tsf_email = get_post_meta($post->ID, '_tsf_email', true);
        $tsf_company = get_post_meta($post->ID, '_tsf_company', true);
        $tsf_address = get_post_meta($post->ID, '_tsf_address', true);
        // Add an nonce field so we can check for it later.
        wp_nonce_field('tsfgen_meta_box', 'tsfgen_meta_box_nonce');
        ?>
        <table class="form-table">
            <tbody>
            <tr valign="top">
                <th scope="row"><label for="tsf_name"><?php _e('Full name', 'alitheia_edocs'); ?></label></th>
                <td><input name="tsf_name" type="text" id="tsf_name" value="<?php echo $tsf_name; ?>"
                           class="regular-text">
                    <p class="description">Enter your name (John Doe)</p></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="tsf_email"><?php _e('E-mail', 'alitheia_edocs'); ?></label></th>
                <td><input name="tsf_email" type="text" id="tsf_email" value="<?php echo $tsf_email; ?>"
                           class="regular-text">
                    <p class="description">Enter email ( john.doe@icloud.com ) </p></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="tsf_tel"><?php _e('Phone Number', 'alitheia_edocs'); ?></label></th>
                <td><input name="tsf_tel" type="text" id="tsf_tel" value="<?php echo $tsf_tel; ?>"
                           class="regular-text">
                    <p class="description">Enter phone number ( Country code + region + number, for example
                        1xxxxxxxxx)</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="_tsf_company"><?php _e('Company name', 'alitheia_edocs'); ?></label></th>
                <td><input name="tsf_company" type="text" id="tsf_company" value="<?php echo $tsf_company; ?>"
                           class="regular-text">
                    <p class="description">Enter your company name (Apple, inc)</p></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="tsf_address"><?php _e('Address', 'alitheia_edocs'); ?></label></th>
                <td><input name="tsf_address" type="text" id="tsf_address" value="<?php echo $tsf_address; ?>"
                           class="regular-text">
                    <p class="description">Enter your address</p></td>
            </tr>
            </tbody>

        </table>

    <?php }

}
