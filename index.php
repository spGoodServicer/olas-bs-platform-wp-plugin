<?php
/*
Plugin Name: ビルドプラットフォーム
Version: 1.1.5
Plugin URI: https://buildsalon.co.jp/
Author: BuildSalon
Description: オンラインサロンシステムをプラットフォーム型で運営するためのWordPressプラグインです。
Text Domain: wp_buildsalon_platform
Domain Path: /
*/

/*アップデート履歴*/
/*
1.0.1 新規作成
1.0.4~1.0.8: BS03-03の適応
1.0.9,1.0.10 BS03-04の適応(1)
1.1.0 BS61の適応
1.1.1 BS03-04の適応(α)
1.1.2 BS03-04の適(☆見積もり)
1.1.3 BS03-子サロンに加入時に決済画面に遷移
1.1.4 有料/無料加入設定
1.1.5 有料/無料加入設定(子サロン別)
*/

//Direct access to this file is not permitted
if (!defined('ABSPATH')){
    exit("Do not access this file directly.");
}

global $platform_use_flag, $post_salon_id;
$platform_use_flag = true;
$post_salon_id = -1; // -1 : not set salon_id

define('BS_PLATFORM_VER', '1.1.5');
define('BS_PLATFORM_PATH', dirname(__FILE__) . '/');
define('BS_PLATFORM_URL', plugins_url('', __FILE__));
define('BS_PLATFORM_DIRNAME', dirname(plugin_basename(__FILE__)));
define('BS_PLATFORM_TEMPLATE_PATH', 'bs-platform');
define('BS_PLATFORM_SALON_SESSSION_KEY', 'bs-pf-salon');

// permission define
define('BS_PLATFORM_MANAGEMENT_PERMISSION', 'manage_options');
define('BS_PLATFORM_SALON_PERMISSION', 'manage_bspf_options');

// salon Pub flag
define('BS_PF_SALON_STATUS_INIT', 0);
define('BS_PF_SALON_STATUS_REQUIRE', 1);
define('BS_PF_SALON_STATUS_CANCEL', 2);
define('BS_PF_SALON_STATUS_PUBLIC', 3);
// define('BS_PF_SALON_STATUS_PRIVATE', 4);


define('BS_PF_SALON_FREE_ENTRANCE', true); // true: 無料加入, false: 有料加入

$entrance_payment_setting_list = array(
    0 => "選択なし",
    1 => "無料加入",
    2 => "有料加入",
);

include_once('classes/class_platform_main.php');
$bs_platform_main_obj = BSPlatformMain::get_instance(); // regist hook function

function custom_query_vars() {
    global $wp;
 
    $wp->add_query_var( 'salon_id' );
    add_rewrite_rule( '^salon/([^/]*)/?', 'index.php?salon_id=$matches[1]', 'top' );
 
    if( !get_option('bspf_permalinks_flushed') ) {
        flush_rewrite_rules(false);
        update_option('bspf_permalinks_flushed', 1);
    }
}

function bs_platform_activate() {
    // // rewrite htaccess
    // custom_query_vars();

    update_option("bspf_manage_permission", BS_PLATFORM_SALON_PERMISSION);
    
	BSPlatformMain::get_instance()->plugin_installer();
}
function bs_platform_deactivate() {
    update_option("bspf_manage_permission", "");
}
register_activation_hook(__FILE__, 'bs_platform_activate');
register_deactivation_hook(__FILE__, 'bs_platform_deactivate');
