<?php
/*
Plugin Name: Database Cleaner: Clean, Optimize & Repair
Plugin URI: https://meowapps.com
Description: Not only does Database Cleaner have a user-friendly UI, but it's also equipped to handle large DBs, giving it an edge over other plugins. Plus, it has a range of features that make it easy to repair and optimize your database for top performance!
Version: 1.0.3
Author: Jordy Meow
Author URI: https://meowapps.com
Text Domain: database-cleaner

Dual licensed under the MIT and GPL licenses:
http://www.opensource.org/licenses/mit-license.php
http://www.gnu.org/licenses/gpl.html
*/

define( 'DBCLNR_VERSION', '1.0.3' );
define( 'DBCLNR_PREFIX', 'dbclnr' );
define( 'DBCLNR_DOMAIN', 'database-cleaner' );
define( 'DBCLNR_ENTRY', __FILE__ );
define( 'DBCLNR_PATH', dirname( __FILE__ ) );
define( 'DBCLNR_URL', plugin_dir_url( __FILE__ ) );
define( 'DBCLNR_ITEM_ID', 16156087 );

require_once( 'classes/init.php' );

?>
