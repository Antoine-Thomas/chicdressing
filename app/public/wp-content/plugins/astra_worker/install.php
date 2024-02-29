<?php

// opcache_reset();

/*
 * error_reporting(E_ALL); ini_set('display_errors', 1);
*/

function is_astra_installed(){
    $astra_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . "astra_tc/astra_tc.php";

    if(file_exists($astra_path)){
        return TRUE;
    }

    return false;
}

function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir."/".$object))
                    rrmdir($dir."/".$object);
                else
                    unlink($dir."/".$object);
            }
        }
        rmdir($dir);
    }
}

function destroy_installer(){
    echo 'Astra installed.';
    if(file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'PP_setup.php')){
        unlink(__DIR__ . DIRECTORY_SEPARATOR . 'PP_setup.php');
    }

    rrmdir(__DIR__);
    //unlink(__FILE__);
    die('Bye.');
}

if(is_astra_installed()){
    destroy_installer();
}

include(__DIR__ . DIRECTORY_SEPARATOR . 'PP_setup.php');

$setup = new PP_setup();

$register = $setup->register();


$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR;

if($register['success'] == false){
    //$setup->notify_failure();
    echo "Unable to Create Astra license";
    exit;
}elseif($register['success'] == true){
    $installed = $setup->install_astra($register, false);
    if($installed == FALSE){
        echo "Unable to Download Astra plugin";
        exit;
    }
    else{
        echo "Astra Plugin for WordPress has been installed.";

        wordpress_plugin_activate($path, 'astra_worker/astra_worker.php');
        exit;
    }
}


if(wordpress_plugin_activate($path, 'astra_tc/astra_tc.php')){
    echo "Astra Plugin for WordPress has been installed & activated.";
    exit;
}else{
    echo "Unable to activate Astra Plugin for WordPress.";
    exit;
}


if(is_astra_installed()){
    //destroy_installer();
}

function wordpress_plugin_activate($path, $plugin)
{
    define('WP_USE_THEMES', false);
    require_once($path . "wp-load.php");

    if (!function_exists('activate_plugin')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    if (!is_plugin_active($plugin)) {
        activate_plugin($plugin);
        return is_plugin_active($plugin);
    } else {
        return true;
    }
}