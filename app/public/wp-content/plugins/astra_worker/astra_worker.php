<?php

/*
  Plugin Name: Astra WordPress Security - Worker
  Plugin URI: https://www.getastra.com/
  Description: Website Security Simplified
  Version: 2.5
  Author: Astra Web Security
  Author URI: https://www.getastra.com/
 */

defined('ABSPATH') or ( 'Plugin file cannot be accessed directly.' );

//error_reporting(E_ALL);
if (!class_exists('Astra_worker')) {

    class Astra_worker {

        protected $base_path = "";
        protected $autoload_file = "Astra.php";
        protected $config_file = "install.json";
        protected $config = array();

        function cz_action_user_login_failed($username) {
            $cp = $this->current_url('astra/libraries/API_connect.php');
            if(file_exists($cp)){
                return false;
            }

            require_once($cp);
            $client_api = new Api_connect();
            $ret = $client_api->send_request("has_loggedin", array("username" => $username, "success" => 0,), "wordpress");

            return true;
        }

        function cz_action_user_login_success($user_info, $u) {
            $cp = $this->current_url('astra/libraries/API_connect.php');
            if(file_exists($cp)){
                return false;
            }

            require_once($cp);

            $user = $u->data;

            unset($user->user_pass, $user->ID, $user->user_nicename, $user->user_url, $user->user_registered, $user->user_activation_key, $user->user_status);

            if (current_user_can('manage_options')) {
                $user->admin = 1;
            }

            $client_api = new Api_connect();
            $ret = $client_api->send_request("has_loggedin", array("user" => $user, "success" => 1,), "wordpress");

            return true;
        }

        protected function check_url($url) {
            try {
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_NOBODY, true);
                $result = curl_exec($curl);
                if ($result !== false) {
                    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    if ($statusCode == 404) {
                        return FALSE;
                    } else {
                        return TRUE;
                    }
                } else {
                    return FALSE;
                }
            } catch (Exception $e) {
                $headers = @get_headers($url);
                if (strpos($headers[0], '404') === false)
                    return TRUE;
                else
                    return FALSE;
            }
        }

        protected function api_file_url() {
            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
                $current_url = "https://";
            } else {
                $current_url = "http://";
            }

            $current_url .= str_replace(realpath($_SERVER["DOCUMENT_ROOT"]), $_SERVER['HTTP_HOST'], realpath(dirname(__FILE__)));

            return $current_url;
        }

        function is_request_from_astra(){
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : "empty";

            if(strpos($user_agent, "astra") !== false){
                return true;
            }

            return false;
        }

        protected function is_tc_eligible(){

            if(empty($_SERVER["SERVER_NAME"]) || $_SERVER["TRIAL"] == 'true'){
                $this->response['message'] = "Website is currently not eligible.";
                return false;
            }else{
                if (isset($_SERVER['themecloud']) && strpos($_SERVER["SERVER_NAME"], 'themecloud.website') === false && strpos($_SERVER["SERVER_NAME"], '.dev') === false && strpos($_SERVER["SERVER_NAME"], 'bizramp.com') === false && strpos($_SERVER["SERVER_NAME"], 'xiahdeh.com') === false && strpos($_SERVER["SERVER_NAME"], 'lignedecode.fr') === false && strpos($_SERVER["SERVER_NAME"], 'vincentmartinat.com') === false && strpos($_SERVER["SERVER_NAME"], 'wperf.fr') === false && strpos($_SERVER["SERVER_NAME"], 'quiosk.fr') === false && strpos($_SERVER["SERVER_NAME"], 'wcloud.colorz.fr') === false && strpos($_SERVER["SERVER_NAME"], 'jengo.website') === false && strpos($_SERVER["SERVER_NAME"], 'mathilderivoire.site') === false && strpos($_SERVER["SERVER_NAME"], 'marcsaffar.website') === false && strpos($_SERVER["SERVER_NAME"], 'evryware-formation.fr') === false && strpos($_SERVER["SERVER_NAME"], 'gilianrosnet.com') === false && strpos($_SERVER["SERVER_NAME"], 'webopale.info') === false && strpos($_SERVER["SERVER_NAME"], 'blackmagic.works') === false && strpos($_SERVER["SERVER_NAME"], 'josspresumey.com') === false && strpos($_SERVER["SERVER_NAME"], 'simplyweb.page') === false && strpos($_SERVER["SERVER_NAME"], 'themecloud.pw') === false && strpos($_SERVER["SERVER_NAME"], 'esante-bretagne.site') === false && strpos($_SERVER["SERVER_NAME"], 'noadmin.io') === false && strpos($_SERVER["SERVER_NAME"], 'bizramp-hosting.com') === false  && strpos($_SERVER["SERVER_NAME"], 'themecloud.site') === false ) {
                    return true;
                }else{
                $this->response['message'] = "Website is currently not eligible";
                return false;
                }

            }
        }

        protected function base_url($uri=''){
            if(empty($uri)){
                return $this->base_path;
            }else{
                return $this->base_path . DIRECTORY_SEPARATOR . $uri;
            }
        }

        protected function load_json($file){

            if(!file_exists($this->current_url($file))){
                return FALSE;
            }

            $content = file_get_contents($this->current_url($file));
            $result = json_decode($content, TRUE);

            if (json_last_error() === JSON_ERROR_NONE) {
                $this->config = $result;
                return TRUE;
            }

            return FALSE;
        }

        protected function get_config($key=''){
            if(empty($this->config)){
                $this->load_json($this->config_file);
            }

            if(isset($this->config[$key])){
                return $this->config[$key];
            }

            return '';
        }

        protected function set_config($key, $value){
            $this->config[$key] = $value;
            $this->save_config();
            return true;
        }

        protected function save_config(){
            $content = json_encode($this->config);
            //echo $content;
            //echo $this->config_file;
            file_put_contents($this->current_url($this->config_file), $content);
        }

        protected function current_url($uri=''){
            if(empty($uri)){
                return __DIR__;
            }else{
                return __DIR__ . DIRECTORY_SEPARATOR . $uri;
            }
        }

        protected function is_astra_installed(){
            $astra_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'astra_tc';

            if(file_exists($astra_path)){
                return true;
            }

            return false;
        }

        protected function rrmdir($dir) {
            if (is_dir($dir)) {
                $objects = scandir($dir);
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..") {
                        if (is_dir($dir."/".$object))
                            $this->rrmdir($dir."/".$object);
                        else
                            unlink($dir."/".$object);
                    }
                }
                rmdir($dir);
            }
        }

        protected function is_tc_setup(){

            /*
            *	Return true if:
            *	1) Astra folder exists
            *	2) Website is not eligible for Astra
            *	3) Do not try to install again on Astra veriication request
            *	4) Installer Library is missing
            */
            if($this->is_astra_installed() || !$this->is_tc_eligible()|| !file_exists($this->current_url('PP_setup.php')) || $this->is_request_from_astra()){
                return TRUE;
            }

            if(empty($this->get_config('first_attempt'))){
                $this->set_config('installed', false);
                $this->set_config('attempt_count', 0);
                $this->set_config('first_attempt', time());
            }

            include($this->current_url('PP_setup.php'));

            $is_first_call = get_option('astra_worker_themecloud_called');

            if(empty($is_first_call))
            {
                add_option('astra_worker_themecloud_called','y');
            }
            elseif ($is_first_call === 'y')
            {
                return true;
            }

            $setup = new PP_setup();
            $register = $setup->register();

            $this->set_config('register', $register);
            $attempts = (int) $this->config['attempt_count'];

            if($attempts > 10){
                return TRUE;
            }

            $this->set_config('attempt_count', ++$attempts);

            if($register['success'] == false){
                $setup->notify_failure();
                $this->set_config('last_attempt', time());


            }elseif($register['success'] == true){
                $installed = $setup->install_astra($register);
                $this->set_config('installed', $installed);
                if($installed == FALSE){
                    return false;
                }

                if(file_exists($this->current_url("PP_setup.php")) && file_exists($this->current_url("astra"))){
                    unlink($this->current_url("PP_setup.php"));
                }
            }

            return false;
        }

        function active_astra_plugin() {

            $plugin = "astra_tc/astra_tc.php";

            if (!is_plugin_active($plugin)) {
                activate_plugin($plugin);
                // opcache_reset();
            }

            if(is_plugin_active($plugin)){
                deactivate_plugins(plugin_basename( __FILE__ ));
                $this->rrmdir(__DIR__);
                delete_option( 'astra_worker_themecloud_called' );
            }
        }

        public function __construct() {
            // Dynamically sets the path to the root of the WordPress install
            $this->base_path = substr(getcwd(), 0, strpos(getcwd(), '/wp-content'));

            if(!$this->is_tc_setup()){
                // Do not proceed as Astra has not yet been installed
                return false;
            }

            include_once(ABSPATH.'wp-admin/includes/plugin.php');
            $this->active_astra_plugin();

            if (function_exists('register_uninstall_hook')) {
                register_uninstall_hook(__FILE__, 'uninstall');
            }
            //add_action('admin_init', array(&$this, 'active_astra_plugin'));

        }

    }

    new Astra_worker;
}