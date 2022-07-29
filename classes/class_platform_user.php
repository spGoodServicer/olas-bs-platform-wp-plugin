<?php
include_once(BS_PLATFORM_PATH . 'classes/class_platform_admin_salon_list.php');

class BSPlatformUser {
	protected static $_intance = null;
	protected $_error = '';
	protected $_swpm_flag = false;

	public static function get_instance() {
        self::$_intance = empty(self::$_intance) ? new BSPlatformUser() : self::$_intance;
        return self::$_intance;
    }

    public function __construct() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if(is_plugin_active( 'simple-membership/simple-wp-membership.php' )) {
			$this->_swpm_flag = true;
		}
    }

	function set_error($error){
		$this->_error = $error;
	}

	function get_error() {
		return $this->_error;
	}

	// load common library
	public function common_library() {
		wp_enqueue_script('jquery');
		wp_enqueue_style('bs_platform_user', BS_PLATFORM_URL . '/css/user.css', array(), BS_PLATFORM_VER);
		wp_enqueue_script( 'bs_platform_user', BS_PLATFORM_URL . '/js/user.js', array( 'jquery' ), BS_PLATFORM_VER, true );
		wp_localize_script( 'bs_platform_user', 'bs_platform_user_ajax_object', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		) );
	}

	public function add_salon_user($salon_id, $mode) {
		$bspf_user = BSPlatformUtils::getBspfUserByWpUserId(get_current_user_id());
		$redirect_url = home_url()."?salon_id=".$salon_id;

		// check possible entrance rank
		if($mode == "entrance") {
			$bs_platform_main_obj = BSPlatformMain::get_instance();
			$entrance_salon_member_level = maybe_unserialize($bs_platform_main_obj->get_option_value("entrance_salon_member_level", ''));

			// $swpm_user = SwpmMemberUtils::get_user_by_id($bspf_user->swpm_user_id);
			$auth = SwpmAuth::get_instance();
			if ($auth->is_logged_in()) {
				$current_memeber_level = $auth->get("membership_level", "", "", true);
				if(!in_array($current_memeber_level, $entrance_salon_member_level)){
					wp_redirect(home_url()."?salon_id=".$salon_id . "&not_entrance=1");
					exit(0);
				}
			}

			$payment_entrance_flag = empty($bs_platform_main_obj->get_option_value("entrance_salon_free_flag", '')) ? true : false;
			$salon = BSPlatformUtils::getSalonData($salon_id, true);
			if(!empty($salon->enter_payment_flag)) {
				$payment_entrance_flag = $salon->enter_payment_flag == 1 ? false : true; // 1: free entrance, 2: payment entrance
			}
			if($payment_entrance_flag){
				$redirect_url = home_url("register") . "?salon_mode=salon_entrance&salon_id=".$salon_id;
			} else {
				self::entrance_user($salon_id, $mode);
			}
		} else if($mode == "favorite") {
			self::entrance_user($salon_id, $mode);
		}

		wp_redirect($redirect_url);
		exit(0);
	}

	public function entrance_user($salon_id, $mode){
		$bspf_user = BSPlatformUtils::getBspfUserByWpUserId(get_current_user_id());

		$check_detail_data = BSPlatformUtils::getTableData('salon_detail', array("salon_id"=>$salon_id, "entrance_user_id"=>$bspf_user->id));

		$detail_id = "";
		if(!empty($check_detail_data)) { 
			$check_detail_data = $check_detail_data[0];
			$detail_id = $check_detail_data->id;
		}

		$regist_data = array(
			'salon_id' => $salon_id,
			'entrance_user_id' => $bspf_user->id,
		);
		if($mode == "entrance") {
			$regist_data['allow_flag'] = 1;
			$regist_data['entrance_date'] = date("Y-m-d H:i:s");
		} else if($mode == "favorite") {
			$regist_data['favorite_flag'] = 1;
		}

		if(empty($detail_id)){ // create
			BSPlatformUtils::insertTableData("salon_detail", $regist_data);
		} else {
			BSPlatformUtils::updateTableData("salon_detail", $regist_data, array('id'=>$detail_id));
		}
		
		do_action("bspf_".$mode."_after_action", $salon_id, $bspf_user->swpm_user_id);
	}

	// custom user list column
	public function manage_users_columns($posts_columns){
		$new_columns = $posts_columns;

		$new_columns['entrance_salon'] = "加入サロン";

		return $new_columns;
	}

	public function manage_users_custom_column($output, $column_name, $user_id) {
		switch( $column_name ) {
			case 'entrance_salon' :
				$output = "";
				$entrance_salons = BSPlatformUtils::getSalonsByUsers($user_id);
				foreach($entrance_salons as $salon) {
					$output .= '<span><a href="'. home_url() . '?salon_id=' . $salon->id.'" target="_blank">'.$salon->name.'</a></span>&nbsp;&nbsp;';
				}
				return $output;
			default:
				break;
		}

		return $output;
	}
}