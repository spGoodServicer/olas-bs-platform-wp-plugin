<?php

abstract class BSPlatformUtils {

    public static function is_ajax() {
        return defined('DOING_AJAX') && DOING_AJAX;
    }

    public static function is_multisite_install() {
        if (function_exists('is_multisite') && is_multisite()) {
            return true;
        } else {
            return false;
        }
    }

    public static function is_admin() {
        return current_user_can('manage_options');
    }

    public static function _($msg) {
        return __($msg, 'bs-platform');
    }

    public static function e($msg) {
        _e($msg, 'bs-platform');
    }

    public static function has_admin_management_permission() {
        if (current_user_can(BS_PLATFORM_MANAGEMENT_PERMISSION)) {
            return true;
        } else {
            return false;
        }
    }
    
    public static function getCurrentSalonID() {
        $salon_id = 0;
		if ( is_admin() ){
			$my_bspf_user = BSPlatformUtils::getBspfUserByWpUserId(get_current_user_id());
			$admin_salon = $my_bspf_user ? BSPlatformUtils::getMySalon($my_bspf_user->id) : null;
			$salon_id = $admin_salon ? $admin_salon->id : 0;
		} else {
            if(isset($_GET['salon_id'])) {
                $salon_id = $_GET['salon_id'];
            } else {
                $my_bspf_user = BSPlatformUtils::getBspfUserByWpUserId(get_current_user_id());
                $salon_id = isset($_SESSION['BS_PLATFORM_SALON_SESSSION_KEY']) ? $_SESSION['BS_PLATFORM_SALON_SESSSION_KEY'] : '';
                // check entrance
                if(!empty($salon_id)) {
                    $result = BSPlatformUtils::getTableData("salon_detail", array("salon_id"=>$salon_id, "allow_flag" => "1", "entrance_user_id" => $my_bspf_user->id));
                    if(!$result){
                        $my_salon = $my_bspf_user ? BSPlatformUtils::getMySalon($my_bspf_user->id) : null;
                        $_SESSION['BS_PLATFORM_SALON_SESSSION_KEY'] = $salon_id = $my_salon ? $my_salon->id : "";
                    }
                }
            }
		}

		if($salon_id == '') { $salon_id = 0; }

        return $salon_id;
    }

    public static function getSalonShowLabel(){
        $bspf_main = BSPlatformMain::get_instance();
        return $bspf_main->get_option_value("salon_show_label", 'サロン');
    }

    public static function uploadImageFile($file_tag_name) {
        $wp_handle_upload = null;

        if ( ! empty( $_FILES[$file_tag_name]['name'] ) ) {
            $wp_handle_upload = wp_handle_upload( $_FILES[$file_tag_name], array(
                'mimes' => array(
                    'jpg|jpeg|jpe'	=> 'image/jpeg',
                    'gif'			=> 'image/gif',
                    'png'			=> 'image/png',
                ),
                'test_form' => false,
                'unique_filename_callback' => array(BSPlatformUtils::class, 'unique_filename_callback') 
            ) );

            if ( empty( $wp_handle_upload['file'] ) || empty( $wp_handle_upload['url'] ) ) {
                $wp_handle_upload = null;
            }
        }

        return $wp_handle_upload;
    }

    public static function uploadFileByArray($file) {
        $wp_handle_upload = null;

        if ( ! empty( $file['name'] ) ) {
            $wp_handle_upload = wp_handle_upload( $file, array(
                'mimes' => array(
                    'jpg|jpeg|jpe'	=> 'image/jpeg',
                    'gif'			=> 'image/gif',
                    'png'			=> 'image/png',
                    'mp4'           => 'video/mp4',
                ),
                'test_form' => false,
                'unique_filename_callback' => array(BSPlatformUtils::class, 'unique_filename_callback') 
            ) );

            if ( empty( $wp_handle_upload['file'] ) || empty( $wp_handle_upload['url'] ) ) {
                $wp_handle_upload = null;
            }
        }

        return $wp_handle_upload;
    }

    public static function unique_filename_callback( $dir, $name, $ext ) {
        do {
            // ランダム文字列生成 (英小文字+数字)
            $randname = strtolower( wp_generate_password( 8, false, false ) );
        } while ( file_exists( $dir . '/' . $randname . $ext ) );
        return $randname . $ext;
    }

     /*
     * Checks if the string exists in the array key value of the provided array. If it doesn't exist, it returns the first key element from the valid values.
     */
    public static function sanitize_value_by_array($val_to_check, $valid_values, $str_lower = true) {
        $keys = array_keys($valid_values);
        if($str_lower) {
            $keys = array_map('strtolower', $keys);
        }
        if (in_array($val_to_check, $keys)) {
            return $val_to_check;
        }
        return reset($keys); //Return he first element from the valid values
    }

	public static function sendMailForCreatePlatform($salon_id, $bspf_user)
	{
		if(empty($salon_id)) { return; }
        $bs_platform_main = BSPlatformMain::get_instance();
        if(empty($bs_platform_main->get_option_value("send_create_salon_mail_flag"))) {
            return;
        }

		set_time_limit(0);
        
        $salon = BSPlatformUtils::getSalonData($salon_id, false);
        $salon_wp_user = get_user_by("id", $bspf_user->wp_user_id);

        $subject = $bs_platform_main->get_option_value("create_salon_mail_subject");
        if(empty($subject)) {
            $subject = "新しいサロンの開設通知";
        }
        
        $mail_body = $bs_platform_main->get_option_value("create_salon_mail_body");
        if(empty($mail_body)) {
			$mail_body = <<<EOF
{user_name}様がサロンを開設しました。

********************************
サロン名：{salon_name}
サロンの概要、特徴：{salon_description}
********************************

サロンリストからご確認ください。
EOF;
		}
        $mail_body = str_replace('{user_name}', $salon_wp_user->display_name, $mail_body);
        $mail_body = str_replace('{salon_name}', $salon->name, $mail_body);
        $mail_body = str_replace('{salon_description}', $salon->description, $mail_body);

        $mail_from = $salon_wp_user->display_name . ' <' . $salon_wp_user->user_email . '>';
        $mailto = get_bloginfo('admin_email');

        $headers = array();
		$headers[] = 'From: ' . $mail_from;
        $headers[] = 'Cc: ' . $mail_from;

        return wp_mail( $mailto, $subject, $mail_body, $headers );
	}

    public static function sendMailForPublicPlatform($salon_id)
	{
		if(empty($salon_id)) { return; }

        $bs_platform_main = BSPlatformMain::get_instance();
        if(empty($bs_platform_main->get_option_value("send_public_salon_mail_flag"))) {
            return;
        }

		set_time_limit(0);
        
        $salon = BSPlatformUtils::getSalonData($salon_id, false);
        
        $bspf_user = BSPlatformUtils::getUser($salon->manager_user_id);
        $salon_wp_user = get_user_by("id", $bspf_user->wp_user_id);

        $subject = $bs_platform_main->get_option_value("create_salon_mail_subject");
        if(empty($subject)) {
            $subject = "サロン開設申請が許可されました";
        }
        
        $mail_body = $bs_platform_main->get_option_value("create_salon_mail_body");
        if(empty($mail_body)) {
			$mail_body = <<<EOF
{site_name}サポートデスクです。
{user_name}様のサロン開設申請が許可されました。

下記のURLからアクセスしてください。
{salon_url}
EOF;
		}
        $mail_body = str_replace('{site_name}', get_bloginfo( 'name' ), $mail_body);
        $mail_body = str_replace('{user_name}', $salon_wp_user->display_name, $mail_body);
        $mail_body = str_replace('{salon_url}', home_url() . "?salon_id=" . $salon->id, $mail_body);
        
        $mail_from = get_bloginfo( 'name' ) . ' <' . get_bloginfo('admin_email') . '>';
        $mailto = $salon_wp_user->user_email;

        $headers = array();
		$headers[] = 'From: ' . $mail_from;
        $headers[] = 'Cc: ' . $mail_from;
        return wp_mail( $mailto, $subject, $mail_body, $headers );
	}

	public static function getBspfUserByWpUserId($wp_user_id) {
		if(empty($wp_user_id)) { return null; }

		$query = "SELECT * FROM " . self::getTableName("user") . " WHERE wp_user_id = '" . $wp_user_id . "' ";
		return self::getRowData($query);
	}

    public static function getUser($bspf_user_id) {
        if(empty($bspf_user_id)) { return null; }

		$query = "SELECT * FROM " . self::getTableName("user") . " WHERE id = '" . $bspf_user_id . "' ";
		return self::getRowData($query);
    }
    public static function getMySalon($bspf_user_id){
        if(empty($bspf_user_id)) { return null; }

        $query = "SELECT * FROM " . self::getTableName("salon") . " WHERE manager_user_id = '" . $bspf_user_id . "' ";
		return self::getRowData($query);
    }

    public static function getSalonsByUsers($wp_user_id, $type = 'entrance') { // $type : entrance, favorite or ''
        $bspf_user = self::getBspfUserByWpUserId($wp_user_id);
        if($bspf_user) {
            return self::getSalonListByInnerJoin($bspf_user->id, $type);
        }
        return array();
    }

    public static function getSalonListByInnerJoin($bspf_user_id, $flag=""){
        $query = "SELECT sd.*, s.* FROM " . self::getTableName("salon") . " as s inner join " . self::getTableName("salon_detail") . " as sd on s.id = sd.salon_id  ";

        $query .= " WHERE public_flag = " . BS_PF_SALON_STATUS_PUBLIC; 
        $query .= " AND sd.entrance_user_id = '" . $bspf_user_id . "' ";
        if($flag == "entrance"){
            $query .= " AND allow_flag = 1";
        } else if($flag == "favorite" ) {
            $query .= " AND favorite_flag = 1";
        }
        $query .= " ORDER BY sd.entrance_date ASC ";

        return self::getResultData($query);
    }

    public static function getSalonList($bspf_user_id = "", $flag="", $where_data=array()){
        if(empty($bspf_user_id) && empty($flag)){
            $query = "SELECT s.* FROM " . self::getTableName("salon") . " as s WHERE public_flag = " . BS_PF_SALON_STATUS_PUBLIC;
        } else {
            $query = "SELECT sd.*, s.* FROM " . self::getTableName("salon") . " as s left join " . self::getTableName("salon_detail") . " as sd on s.id = sd.salon_id  ";
            if(!empty($bspf_user_id)) { 
                $query .= " AND sd.entrance_user_id = '" . $bspf_user_id . "' ";
            }
            $query .= " WHERE public_flag = " . BS_PF_SALON_STATUS_PUBLIC; 
            if($flag == "entrance"){
                $query .= " AND allow_flag = 1";
            } else if($flag == "favorite" ) {
                $query .= " AND favorite_flag = 1";
            }
        }

        if(!empty($where_data)){
            foreach($where_data as $key=>$condition){
                $query .= " AND " . $condition;
            }
        }
        $query .= " ORDER BY create_date DESC ";

        return self::getResultData($query);
    }

    public static function getSalonData($salon_id, $public = true){
        if(empty($salon_id)) { return null; }

        $query = "SELECT * FROM " . self::getTableName("salon") . " WHERE id = '" . $salon_id . "' ";
        if($public){
            $query .= " AND public_flag = " . BS_PF_SALON_STATUS_PUBLIC;
        }
		return self::getRowData($query);
    }

    public static function getSalonUsers($salon_id, $type = "allow") {
        $query = "SELECT b.* FROM " .self::getTableName("salon_detail") . " AS a INNER JOIN " . self::getTableName("user") . " AS b ON a.entrance_user_id = b.id";
        $query .= " WHERE salon_id = " . $salon_id;
        if($type == "allow") {
            $query .= " AND entrance_date IS NOT NULL AND allow_flag = 1 ";
        } else if($type == "favorite"){
            $query .= " AND favorite_flag = 1 ";
        }
        
        return self::getResultData($query);
    }

    // ================== db utility ================
    public static function getTableName($table_name){
        global $wpdb;
        
        return $wpdb->prefix . "bs_pf_" . $table_name; 
    }
    
    public static function getTableData($table_name, $where_data = array(), $order_by = null, $count=0, $start = 0) {
        $query = "SELECT * FROM " . self::getTableName($table_name) . " WHERE 1";
        foreach($where_data as $field=>$value) {
            if(is_array($value)) {
                $operation = " = ";
                switch($value['compare']) {
                    case "exclude" :
                        $operation = " != ";
                        break;
                    default:
                        break;
                }
                $query .= " AND " . $field . $operation . "'".$value['value']."'";
            } else {
                $query .= " AND " . $field . " = '".$value."'";
            }
        }
        if($order_by) {
            $temp = "";
            foreach($order_by as $field=>$value) {
                if(!empty($temp)) { $temp .= ","; }
                $temp .= $field . " ".$value;
            }

            $query .= " ORDER BY " . $temp;
        }
        if(!empty($count)){
            $query .= " LIMIT ".$start.", " . $count;
        }
        return self::getResultData($query);
    }

    public static function getRowData($query, $output = OBJECT){
        global $wpdb;

        return $wpdb->get_row($query, $output);
    }

    public static function getResultData($query){
        global $wpdb;

        return $wpdb->get_results($query);
    }

    public static function insertTableData($table, $insert_data){
        global $wpdb;

        $wpdb->insert(self::getTableName($table), $insert_data);
        return $wpdb->insert_id;
    }

    public static function updateTableData($table, $update_data, $where_data){
        global $wpdb;

        return $wpdb->update(self::getTableName($table), $update_data, $where_data);
    }

    public static function deleteTableData($table, $where_data){
        global $wpdb;

        return $wpdb->delete(self::getTableName($table), $where_data);
    }
}
