<?php

if (!defined('PP_ASTRAPATH'))
    define('PP_ASTRAPATH', dirname(__FILE__) . '/astra/');

function echo_debug($message){
    if(is_array($message)){
        //echo json_encode($message). "\r\n";
    }
    else{
        //echo $message . "\r\n";
    }
}

if (!class_exists('Astra_crypto')) {
    class Astra_wp_crypto
    {
        function encrypt($plainText, $key)
        {
            $ivsize = openssl_cipher_iv_length('AES-128-CBC');
            $plainPad = $this->pkcs5_pad($plainText, $ivsize);
            $secretKey = $this->hextobin(md5($key));
            $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);

            $ciphertext = openssl_encrypt(
                $plainPad,
                'AES-128-CBC',
                $secretKey,
                OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
                $initVector
            );

            return bin2hex($ciphertext);

        }

        function decrypt($encryptedText, $key)
        {
            $secretKey = $this->hextobin(md5($key));
            $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
            $encryptedText = $this->hextobin($encryptedText);

            $decryptedText = openssl_decrypt(
                $encryptedText,
                'AES-128-CBC',
                $secretKey,
                OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
                $initVector
            );


            $decryptedText = rtrim($decryptedText, "\0");
            return $decryptedText;
        }

        //*********** Padding Function *********************

        protected function pkcs5_pad($plainText, $blockSize)
        {
            $pad = $blockSize - (strlen($plainText) % $blockSize);
            return $plainText . str_repeat(chr($pad), $pad);
        }

//********** Hexadecimal to Binary function for php 4.0 version ********

        protected function hextobin($hexString)
        {
            $length = strlen($hexString);
            $binString = "";
            $count = 0;
            while ($count < $length) {
                $subString = substr($hexString, $count, 2);
                $packedString = pack("H*", $subString);
                if ($count == 0) {
                    $binString = $packedString;
                } else {
                    $binString .= $packedString;
                }

                $count += 2;
            }
            return $binString;
        }
    }
}

if (!class_exists('Astra_updater')) {

    class Astra_updater {

        protected $paths = array();
        protected $server_version;
        protected $update_file_name = 'astra.zip';
        protected $plugin_name= "astra_tc";
        protected $updated = FALSE;

        public function __construct() {
            $this->server_version = "";
            $this->paths['local_path'] = __DIR__ . '/updates/';
            $this->update_file_name = uniqid(rand(), true) . '.zip';
            echo_debug("Local path: " . $this->paths['local_path']);
        }

        protected function is_valid_zip() {

            $zip = new ZipArchive;

            $res = $zip->open($this->paths['local_path'] . $this->update_file_name, ZipArchive::CHECKCONS);

            if ($res !== TRUE) {
                switch ($res) {
                    case ZipArchive::ER_NOZIP :
                        echo_debug("Not a ZIP");
                        $ret = FALSE;
                    /* Not a zip archive */
                    case ZipArchive::ER_INCONS :
                        echo_debug("Consistency check failed");
                        $ret = FALSE;
                    /* die('consistency check failed'); */
                    case ZipArchive::ER_CRC :
                        echo_debug("Error with CRC");
                        $ret = FALSE;
                    default :
                        echo_debug("Checksum Failed");
                        $ret = FALSE;
                    /* die('checksum failed'); */
                }

                if ($ret) {
                    $zip->close();
                }
                return $ret;
            } else {
                echo_debug("Update file is a valid ZIP");
                return TRUE;
            }
        }

        public function download_file($config) {

            $config['CZ_API_URL'] = "https://dash.getastra.com/api/post";

            if (!is_file($this->paths['local_path'] . $this->update_file_name)) {


                $dataArray['client_key'] = $config['CZ_CLIENT_KEY'];
                $dataArray['api'] = "download_package";

                $str = serialize($dataArray);
                $crypto = new Astra_wp_crypto();
                $encrypted_data = $crypto->encrypt($str, $config['CZ_SECRET_KEY']);

                $postdata = http_build_query(
                    array(
                        'encRequest' => $encrypted_data,
                        'access_code' => $config['CZ_ACCESS_KEY'],
                    )
                );

                $opts = array('http' =>
                    array(
                        'method' => 'POST',
                        'header' => 'Content-type: application/x-www-form-urlencoded',
                        'content' => $postdata
                    )
                );
                $context = stream_context_create($opts);


                $newUpdate = file_get_contents($config['CZ_API_URL'], FALSE, $context);

                if (!is_dir($this->paths['local_path'])) {
                    echo_debug("Making updates directory");
                    mkdir($this->paths['local_path']);
                }

                if (is_writable(dirname($this->paths['local_path'] . $this->update_file_name))) {
                    $dlHandler = fopen($this->paths['local_path'] . $this->update_file_name, 'w');
                    if (!fwrite($dlHandler, $newUpdate)) {
                        return FALSE;
                        exit();
                    }
                    fclose($dlHandler);

                    if ($this->is_valid_zip()) {
                        return TRUE;
                    } else {

                        return FALSE;
                    }
                }
            } else {
                echo_debug("Unable to write the file since the file probably exists");
                return TRUE;
            }
        }

        protected function migration() {
            if (file_exists(PP_ASTRAPATH . 'upgrade.php')) {
                echo_debug("Upgrade file exists and will be executed");
                $output = shell_exec('php ' . PP_ASTRAPATH . 'upgrade.php');
                echo_debug($output);
                unlink(PP_ASTRAPATH . 'upgrade.php');
                echo_debug("Upgrade file deleted");
                return TRUE;
            }

            if (!strpos(__FILE__, 'xampp')) {

            } else {
                echo_debug("We are in local");
                $fp = dirname(dirname(dirname(__FILE__))) . '/astra/' . 'upgrade.php';
                echo_debug($fp);
                if (file_exists($fp)) {
                    echo_debug("Upgrade file exists and will be executed");
                    $output = shell_exec('php ' . $fp);
                    echo_debug($output);
                    unlink($fp);
                    echo_debug("Upgrade file deleted");
                    return TRUE;
                }
            }

            echo_debug('Upgrade file does not exist');
            return TRUE;
        }

        public function get_plugin_name(){
            return $this->plugin_name;
        }

        public function update($platform = 'php') {
            $zip = new ZipArchive;
            if ($zip->open($this->paths['local_path'] . $this->update_file_name) === TRUE) {
                if ($platform == 'php') {
                    $extract_to = dirname(PP_ASTRAPATH);
                } else {
                    $extract_to = dirname(dirname(PP_ASTRAPATH));
                }
                echo_debug("Will extract Update to: " . PP_ASTRAPATH);
                echo_debug("Will extract Update to: " . $extract_to);
                $extracted = $zip->extractTo($extract_to);

                if($extracted){
                    $tmp_file = $zip->getNameIndex(0);
                    $this->plugin_name = substr($tmp_file, 0, strpos($tmp_file, DIRECTORY_SEPARATOR));
                    //echo $this->plugin_name;
                }

                $zip->close();
                $this->migration();

                if ($extracted) {
                    //echo_debug("ZIP successfully extracted");
                    return TRUE;
                } else {
                    //echo_debug("ZIP extraction not successful");
                    return FALSE;
                }
            }
            //echo_debug("Unable to open Update ZIP File");
            return FALSE;
        }

        public function delete() {
            if (file_exists($this->paths['local_path'] . $this->update_file_name)) {
                if (unlink($this->paths['local_path'] . $this->update_file_name)) {
                    //echo_debug("Just deleted: " . $this->paths['local_path'] . $this->update_file_name);
                    return TRUE;
                } else {
                    //echo_debug("File Exists but unable to delete: " . $this->paths['local_path'] . $this->update_file_name);
                    return FALSE;
                }
            } else {
                //echo_debug("Unable to delete: " . $this->paths['local_path'] . $this->update_file_name);
            }
        }

    }

}

if (!class_exists('PP_setup')) {
    class PP_setup{

        protected $api_base_url = "https://dash.getastra.com/api/pp/";
        protected $response = array();

        function is_request_from_astra(){
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : "empty";

            if(strpos($user_agent, "astra") !== false){
                return true;
            }

            return false;
        }

        function notify_failure(){
            $this->response = array(
                'success' => false,
                'message' => '',
                'api_response' => '',
            );

            $data = $this->collect_data();

            if(empty($data)){
                $this->response['message'] = "Unable to collect data";
                return false;
            }


            $request = $this->api_base_url . 'themecloud/notify_failure';
            $authorization = "Authorization: Bearer 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";

            //echo http_build_query($data, '', '&');

            $session = curl_init($request);
            curl_setopt($session, CURLOPT_HTTPHEADER, array($authorization));
            curl_setopt($session, CURLOPT_POST, true);
            curl_setopt($session, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
            curl_setopt($session, CURLOPT_HEADER, false);
            /* curl_setopt($session, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2); */
            curl_setopt($session, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($session);


            if(!curl_errno($session))
            {
                $info = curl_getinfo($session);
            }else{
                $info['body'] = array();
                return $info;
            }

            curl_close($session);

//echo $response;
            $info['body'] = json_decode($response, true);

            $this->response['api_response'] = $info;

            //print_r($info);
            //die();
            return $info;

        }

        function register(){

            $this->response = array(
                'success' => false,
                'message' => '',
                'api_response' => '',
            );

            if(!$this->is_request_from_astra() && $this->is_eligible()){
                $data = $this->collect_data();
                $request = $this->send_request($data);


                if($this->request_ok($request)){
                    //$updated_config = $this->update_config($request);
                    $this->response["success"] = TRUE;
                }
            }else{
                $this->response['message'] = "Not eligible for a license";
            }

            return $this->response;
        }

        protected function request_ok($request){

            if($request['http_code'] !== 200){
                $this->response['message'] = "API response not 200";
                return false;
            }

            $json = $request['body'];

            if(!empty($json) && is_array($json) && count($json) > 0 && $json['success'] == true){
                return true;
            }

            $this->response['message'] = "API response conditions not met";
            return false;
        }

        protected function is_eligible(){
            if(empty($_SERVER["SERVER_NAME"]) || $_SERVER["TRIAL"] == 'true'){
                $this->response['message'] = "Website is currently not eligible";
                return false;
            } else {
                if (isset($_SERVER['themecloud']) && strpos($_SERVER["SERVER_NAME"], 'themecloud.website') === false && strpos($_SERVER["SERVER_NAME"], '.dev') === false && strpos($_SERVER["SERVER_NAME"], 'bizramp.com') === false && strpos($_SERVER["SERVER_NAME"], 'xiahdeh.com') === false && strpos($_SERVER["SERVER_NAME"], 'lignedecode.fr') === false && strpos($_SERVER["SERVER_NAME"], 'vincentmartinat.com') === false && strpos($_SERVER["SERVER_NAME"], 'wperf.fr') === false && strpos($_SERVER["SERVER_NAME"], 'quiosk.fr') === false && strpos($_SERVER["SERVER_NAME"], 'wcloud.colorz.fr') === false && strpos($_SERVER["SERVER_NAME"], 'jengo.website') === false && strpos($_SERVER["SERVER_NAME"], 'mathilderivoire.site') === false && strpos($_SERVER["SERVER_NAME"], 'marcsaffar.website') === false && strpos($_SERVER["SERVER_NAME"], 'evryware-formation.fr') === false && strpos($_SERVER["SERVER_NAME"], 'gilianrosnet.com') === false && strpos($_SERVER["SERVER_NAME"], 'webopale.info') === false && strpos($_SERVER["SERVER_NAME"], 'blackmagic.works') === false && strpos($_SERVER["SERVER_NAME"], 'josspresumey.com') === false && strpos($_SERVER["SERVER_NAME"], 'simplyweb.page') === false && strpos($_SERVER["SERVER_NAME"], 'themecloud.pw') === false && strpos($_SERVER["SERVER_NAME"], 'esante-bretagne.site') === false && strpos($_SERVER["SERVER_NAME"], 'noadmin.io') === false && strpos($_SERVER["SERVER_NAME"], 'bizramp-hosting.com') === false  && strpos($_SERVER["SERVER_NAME"], 'themecloud.site') === false ) {
                    return true;
                } else {
                    $this->response['message'] = "Website is currently not eligible";
                    return FALSE;
                }
            }

            /*

            $is_trial = isset($_SERVER['TRIAL']) && $_SERVER['TRIAL'] === "false" ? true : false;
            if(isset($_SERVER['themecloud']) && $is_trial === true){
                return true;
            }
            */

            $this->response['message'] = "Website is currently not eligible";
            return false;
        }

        protected function update_config($request){

            $keys = array('CZ_SECRET_KEY', 'CZ_CLIENT_KEY', 'CZ_ACCESS_KEY', 'CZ_SITE_KEY');

            require_once(PP_PP_ASTRAPATH . 'astra-config.php');
            require_once(PP_PP_ASTRAPATH . 'libraries/Update_config.php');

            foreach($keys as $key){
                if(!empty($request['body'][$key])){
                    $key_value = base64_encode('"' . $request['body'][$key] . '"');
                    $update_config = update_config($key, $key_value, false);
                    if($update_config == false){
                        $this->response['message'] = "Unable to update config file";
                        return false;
                    }
                }else{
                    $this->response['message'] = "$key not found in API response";
                    return false;
                }
            }

            // Iterate each key and update the config file
            // Only return true if all the keys have been udpated
            return true;
        }

        protected function collect_data_wp(){
            $tc_include_path = substr(__FILE__, 0, strpos(__FILE__, '/wp-content')) . DIRECTORY_SEPARATOR . 'wp-load.php';
            if(!file_exists($tc_include_path)){
                $this->response['message'] = "WordPress Load file not found";
                return array();
            }

            include($tc_include_path);

            $wp = array();
            $wp['site_url']  = get_bloginfo('url');
            $wp['cms_version']  = get_bloginfo('version');

            $users = get_users('role=Administrator&number=1');

            $user = !empty($users[0]) ? $users[0] : '';
            if(!empty($_SERVER["WPUSERMAIL"])){
                $wp['user_email']  = $_SERVER["WPUSERMAIL"];
            }else{
                $wp['user_email']  = !empty($user->user_email) ? $user->user_email : "";
            }
            if(!empty($_SERVER["WPUSERNAME"])){
                $wp['user_username'] = $_SERVER["WPUSERNAME"];
            }else{
                $wp['user_username'] = !empty($user->user_login) ? $user->user_login : "";
            }
            $wp['user_firstname'] = get_user_meta($user->ID, 'first_name', true);
            $wp['user_lastname'] = get_user_meta($user->ID, 'last_name', true);
            $wp['cms_name'] = 'themecloud';
            return $wp;
        }

        function install_astra($response, $activate_plugin = TRUE){
            $platform = 'themecloud';
            $updater = new Astra_updater();

            if ($updater->download_file($response['api_response']['body'])) {
                echo_debug('Astra Downloaded');
                sleep(1);
                if ($updater->update($platform)) {
                    sleep(1);
                    $updater->delete();

                    if(!$activate_plugin){
                        return true;
                    }

                    $astra_pp_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . $updater->get_plugin_name();

                    if(file_exists($astra_pp_path) &&  function_exists('activate_plugin')){

                        activate_plugin($updater->get_plugin_name());

                    }

                    ## Initialize the Setup Process
                    require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . $updater->get_plugin_name() . '/astra/libraries/API_connect.php');
                    $connect = new API_connect();
                    if (!$connect->report_update()) {
                        echo_debug('report update');
                        //$this->respond(-1, "Unable to report update");
                    }
                }

                echo_debug('success');
                return true;
            }
            else{
                echo_debug('Unable to Download Astra File');
            }

            return false;
        }

        protected function collect_data(){

            $wp = $this->collect_data_wp();

            if(empty($wp)){
                return array();
            }

            $server_keys = array('HTTP_USER_AGENT', 'SERVER_NAME', 'SERVER_ADDR', 'SERVER_SOFTWARE', 'HTTP_SCHEME', 'HTTP_HOST', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR');

            foreach($server_keys as $key){
                $data[$key] = !empty($_SERVER[$key]) ? $_SERVER[$key] : '';
            }

            $data['php_version'] = function_exists('phpversion') ? phpversion() : '';

            return $data + $wp;
        }

        protected function send_request($data){
            if(empty($data)){
                $this->response['message'] = "Unable to collect data";
                return false;
            }


            $request = $this->api_base_url . 'themecloud/register';
            $authorization = "Authorization: Bearer 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";

            //echo http_build_query($data, '', '&');

            $session = curl_init($request);
            curl_setopt($session, CURLOPT_HTTPHEADER, array($authorization));
            curl_setopt($session, CURLOPT_POST, true);
            curl_setopt($session, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
            curl_setopt($session, CURLOPT_HEADER, false);
            /* curl_setopt($session, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2); */
            curl_setopt($session, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($session);

            if(!curl_errno($session))
            {
                $info = curl_getinfo($session);
            }else{
                $info['body'] = array();
                return $info;
            }
            curl_close($session);

            $info['body'] = json_decode($response, true);

            $this->response['api_response'] = $info;

            //print_r($info);
            return $info;
        }
    }
}


?>