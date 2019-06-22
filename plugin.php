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

define('plugin_version','1.0');
define('plugin_role','alitheia');
define('plugin_name','Alitheia eDocs');
define('plugin_name_singular','AeDocs');
define('plugin_capability_name','manage_options');
define('plugin_site_url', site_url());
define('plugin_home_url', home_url());
define('plugin_url', $this->plugin_url());
define('plugin_path', $this->plugin_path());


require_once 'src/class.access.php';
require_once 'src/class.html.php';
require_once 'src/class.main.php';

use Alitheia\Main;
use Alitheia\Html\Forms;
use Alitheia\Access\PluginAccess;


$forms = new Forms();
$plugin_access = new PluginAccess();






$GLOBALS['alitheia_edocs'] = new alitheia_edocs();


