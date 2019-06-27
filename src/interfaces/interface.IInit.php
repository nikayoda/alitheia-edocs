<?php

namespace Alitheia\IPlugin;

interface IInit
{
    public function loader_operations();
    public function __register_hooks();
    public function plugins_loaded_handler();
    public function check_upgrade();
    public function activate_handler();
    public function admin_notice();
    public function deactivate_handler();
    public function plugin_init();
    public function add_meta_boxes();
    public function plugin_scripts();
    public function add_plugin_action_links($links, $file);
    public function add_options_menu();
    public function options_page();
    public function general_settings();
    public function debug_page();
    public function plugin_meta_box($post);


}