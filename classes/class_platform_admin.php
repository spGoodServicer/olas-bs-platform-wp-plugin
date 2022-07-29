<?php
include_once(BS_PLATFORM_PATH . 'classes/class_platform_admin_salon_list.php');
include_once(BS_PLATFORM_PATH . 'classes/class_platform_admin_salon_kind_list.php');
include_once(BS_PLATFORM_PATH . 'classes/class_platform_admin_salon_member_list.php');

class BSPlatformAdmin {
	protected static $_intance = null;
	protected $swpm_flag = false;

	public static function get_instance() {
        self::$_intance = empty(self::$_intance) ? new BSPlatformAdmin() : self::$_intance;
        return self::$_intance;
    }

    public function __construct() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if(is_plugin_active( 'simple-membership/simple-wp-membership.php' )){
            $this->swpm_flag = true;
        }
    }

	// load common library
	public function common_library() {
		wp_enqueue_script('jquery');
        wp_enqueue_style('bs_platform_admin', BS_PLATFORM_URL . '/css/admin.css', array(), BS_PLATFORM_VER);		
	}

	// ================================ show salon' kind list page ================================
	private function show_platform_salon_kind_list() {
		return BSPlatformAdminSalonKindList::get_instance()->handle_platform_salon_kind_list_menu();
    }

	public function handle_admin_bs_platform_salon_kind_list_menu() {
		$this->common_library();
		wp_enqueue_script( 'bs_platform_admin', BS_PLATFORM_URL . '/js/admin.js', array( 'jquery' ), BS_PLATFORM_VER, true );
		wp_localize_script( 'bs_platform_admin', 'bs_platform_admin_ajax_object', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		) );

		echo $this->show_platform_salon_kind_list();
	}

	// =============================== show payment page for salon =================================
	public function handle_admin_bs_platform_salon_payment_list() {
		$this->common_library();
		wp_enqueue_script( 'bs_platform_admin', BS_PLATFORM_URL . '/js/admin.js', array( 'jquery' ), BS_PLATFORM_VER, true );
		wp_localize_script( 'bs_platform_admin', 'bs_platform_admin_ajax_object', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		) );

		echo BSPlatformAdminSalonPaymentList::get_instance()->handle_platform_salon_payment_list_menu();
	}

	// ================================ show salon list page ================================
	private function show_platform_salon_list() {
		return BSPlatformAdminSalonList::get_instance()->handle_platform_salon_list_menu();
    }

	public function handle_admin_bs_platform_salon_list_menu() {
		$this->common_library();
		wp_enqueue_script( 'bs_platform_admin', BS_PLATFORM_URL . '/js/admin.js', array( 'jquery' ), BS_PLATFORM_VER, true );
		wp_localize_script( 'bs_platform_admin', 'bs_platform_admin_ajax_object', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		) );

		echo $this->show_platform_salon_list();
	}

	// ================================ show my salon page ================================
	public function handle_admin_bs_platform_my_salon_menu() {
		$this->common_library();
		wp_enqueue_script( 'bs_platform_admin', BS_PLATFORM_URL . '/js/admin.js', array( 'jquery' ), BS_PLATFORM_VER, true );

		$page_msg = array();
		$bspf_user = BSPlatformUtils::getBspfUserByWpUserId(get_current_user_id());
		$page_data = BSPlatformUtils::getMySalon($bspf_user->id);

		$salon_kind_data = BSPlatformUtils::getTableData("kind", array("use_flag" => 1));
		
		include_once(BS_PLATFORM_PATH . 'views/bs_platform_admin_my_salon.php');
	}

	public function edit_my_salon() {
		$salon_id = isset( $_REQUEST['my_salon_id'] ) ? $_REQUEST['my_salon_id'] : "";
		$mode = isset( $_REQUEST['mode'] ) ? $_REQUEST['mode'] : "";
		$bspf_user = BSPlatformUtils::getBspfUserByWpUserId(get_current_user_id());

		if(!empty($salon_id) && !empty($mode)){
			$old_page_data = BSPlatformUtils::getMySalon($bspf_user->id);

			if($mode == "delete"){ // delete salon
				// delete image file
				if(!empty($old_page_data->image_path)){
					@unlink( $old_page_data->image_path );
				}

				BSPlatformUtils::deleteTableData("salon", array('id'=>$salon_id, 'manager_user_id'=>$bspf_user->id));

				// update user role
				if($bspf_user->role_id == 2) {
					BSPlatformUtils::updateTableData('user', array("role_id"=>3), array("id"=>$bspf_user->id));

					$user = wp_get_current_user();
					$user->remove_role('salon_manager');
				}

				$page_msg[] = BSPlatformUtils::getSalonShowLabel() . "を削除しました。";
				
				wp_safe_redirect(home_url());
				exit();
			} else if($mode == "edit" || $mode == "public_request") { // create or update salon
				// upload file
				$upload_file_main_image = BSPlatformUtils::uploadImageFile('salon_image');
				if($upload_file_main_image) {
					@unlink( $old_page_data->image_path );
				}

				// upload sub image file
				$sub_images = maybe_unserialize($old_page_data->sub_images);
				$sub_images_path = maybe_unserialize($old_page_data->sub_images_path);

				$files = $_FILES['sub_images'];
				for($i=0;$i<count($files['name']);$i++) {
					$file = array(
						'name'     => $files['name'][$i],
						'type'     => $files['type'][$i],
						'tmp_name' => $files['tmp_name'][$i],
						'error'    => $files['error'][$i],
						'size'     => $files['size'][$i]
					);
					$upload_file = BSPlatformUtils::uploadFileByArray($file);
					if($upload_file) {
						@unlink( $sub_images_path[$i] );

						$sub_images[$i] = $upload_file['url'];
						$sub_images_path[$i] = $upload_file['file'];
					}
				}						

				// upload video file
				$videos = maybe_unserialize($old_page_data->videos);
				$videos_path = maybe_unserialize($old_page_data->videos_path);
				$files = $_FILES['videos'];
				for($i=0;$i<count($files['name']);$i++) {
					$file = array(
						'name'     => $files['name'][$i],
						'type'     => $files['type'][$i],
						'tmp_name' => $files['tmp_name'][$i],
						'error'    => $files['error'][$i],
						'size'     => $files['size'][$i]
					);
					$upload_file = BSPlatformUtils::uploadFileByArray($file);
					if($upload_file) {
						@unlink( $videos_path[$i] );

						$videos[$i] = $upload_file['url'];
						$videos_path[$i] = $upload_file['file'];
					}
				}

				$regist_data = array(
					'name' => $_REQUEST['salon_name'], 
					'image' => $upload_file_main_image ? $upload_file_main_image['url'] : $old_page_data->image, 
					'image_path' => $upload_file_main_image ? $upload_file_main_image['file'] : $old_page_data->image_path, 
					'description' => $_REQUEST['salon_description'],
					'manager_user_id' => $bspf_user->id,
					'fee_percent' => BSPlatformMain::get_instance()->get_option_value('salon_fee_percent', '10'),
					'update_date' => date("Y-m-d H:i:s"),
					'salon_kind_id' => $_REQUEST['salon_kind'],
					'mng_nickname' => $_REQUEST['mng_nickname'],
					'mng_introduction' => $_REQUEST['mng_introduction'],
					'mng_message' => $_REQUEST['mng_message'],
					'can_doing_work' => $_REQUEST['can_doing_work'],
					'entrance_merit' => $_REQUEST['entrance_merit'],
					'usage_fee' => $_REQUEST['usage_fee'],
					'keep_in_mind' => $_REQUEST['keep_in_mind'],
					'office_word' => $_REQUEST['office_word'],
					'sub_images' => maybe_serialize($sub_images),
					'sub_images_path' => maybe_serialize($sub_images_path),
					'videos' => maybe_serialize($videos),
					'videos_path' => maybe_serialize($videos_path),
					'enter_payment_flag' => $_REQUEST['enter_payment_flag'],
				);
				
				if(empty($salon_id)){ // create
					$regist_data['create_date'] = date("Y-m-d H:i:s");
					$regist_data['public_flag'] = BS_PF_SALON_STATUS_INIT;

					$salon_id = $insert_salon_id = BSPlatformUtils::insertTableData("salon", $regist_data);

					// update user role
					if($bspf_user->role_id == 3) {
						BSPlatformUtils::updateTableData('user', array("role_id"=>2), array("id"=>$bspf_user->id));

						$user = wp_get_current_user();
						$user->add_role('salon_manager');
					}
					
					// send mail
					BSPlatformUtils::sendMailForCreatePlatform($insert_salon_id, $bspf_user);
				} else { // update
					BSPlatformUtils::updateTableData("salon", $regist_data, array('id'=>$salon_id, 'manager_user_id'=>$bspf_user->id));
				}

				if($mode == "public_request") {
					if(!empty($salon_id)) {
						$regist_data = array(
							'update_date' => date("Y-m-d H:i:s"),
							'public_flag' => BS_PF_SALON_STATUS_REQUIRE,
						);

						BSPlatformUtils::updateTableData("salon", $regist_data, array('id'=>$salon_id));

						$page_msg[] = BSPlatformUtils::getSalonShowLabel() . "開設を申し込みました。";
					}
				} else {
					$page_msg[] = BSPlatformUtils::getSalonShowLabel() . "を保存しました。";
				}
			} else if($mode == "delete_image"){ // image delete
				if(!empty($salon_id) && $salon_id == $old_page_data->id){
					@unlink( $old_page_data->image_path );

					$regist_data = array(
						'image' => '', 
						'image_path' => '', 
						'update_date' => date("Y-m-d H:i:s"),
					);
					BSPlatformUtils::updateTableData("salon", $regist_data, array('id'=>$salon_id));
				}
			} else if($mode == "delete_sub_image"){ // sub image delete
				if(!empty($salon_id) && $salon_id == $old_page_data->id){
					$sub_id = $_REQUEST['sub_id'];

					$sub_images = maybe_unserialize($old_page_data->sub_images);
					$sub_images_path = maybe_unserialize($old_page_data->sub_images_path);

					if(isset($sub_images_path[$sub_id]) && !empty($sub_images_path[$sub_id])) {
						@unlink( $sub_images_path[$sub_id] );

						$sub_images[$sub_id] = "";
						$sub_images_path[$sub_id] = "";
						
						$regist_data = array(
							'sub_images' => maybe_serialize($sub_images),
							'sub_images_path' => maybe_serialize($sub_images_path),
							'update_date' => date("Y-m-d H:i:s"),
						);
						BSPlatformUtils::updateTableData("salon", $regist_data, array('id'=>$salon_id));
					}
				}
			} else if($mode == "delete_video"){ // video delete
				if(!empty($salon_id) && $salon_id == $old_page_data->id){
					$sub_id = $_REQUEST['sub_id'];

					$videos = maybe_unserialize($old_page_data->videos);
					$videos_path = maybe_unserialize($old_page_data->videos_path);

					if(isset($videos_path[$sub_id]) && !empty($videos_path[$sub_id])) {
						@unlink( $videos_path[$sub_id] );

						$videos[$sub_id] = "";
						$videos_path[$sub_id] = "";
						
						$regist_data = array(
							'videos' => maybe_serialize($videos),
							'videos_path' => maybe_serialize($videos_path),
							'update_date' => date("Y-m-d H:i:s"),
						);
						BSPlatformUtils::updateTableData("salon", $regist_data, array('id'=>$salon_id));
					}
				}
			}
		}
	}

	// ================================ show salon member page ================================
	private function show_platform_salon_member_list() {
		return BSPlatformAdminSalonMemberList::get_instance()->handle_platform_salon_member_list_menu();
    }

	public function handle_admin_bs_platform_member_menu() {
		$this->common_library();
		wp_enqueue_script( 'bs_platform_admin', BS_PLATFORM_URL . '/js/admin.js', array( 'jquery' ), BS_PLATFORM_VER, true );
		wp_localize_script( 'bs_platform_admin', 'bs_platform_admin_ajax_object', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		) );

		echo $this->show_platform_salon_member_list();
	}

	// ================================ show bank page ================================
	public function handle_admin_bs_platform_bank_menu() {
		$this->common_library();

		$page_msg = array();
		$page_data = $bspf_user = BSPlatformUtils::getBspfUserByWpUserId(get_current_user_id());
		if(empty($page_data->bank_type)){$page_data->bank_type = "1";}
		
		include_once(BS_PLATFORM_PATH . 'views/bs_platform_admin_salon_bank.php');
	}

	public function edit_bank() {
		if(isset($_POST['user_id']) && !empty($_POST['user_id'])){
			$update_data = array(
				"bank_type" => $_POST['bank_type'], 
				"bank_name" => $_POST['bank_name'], 
				// "bank_code" => $_POST['bank_code'], 
				"bank_area_number" => $_POST['bank_area_number'], 
				"bank_area_name" => $_POST['bank_area_name'], 
				"bank_account_type" => $_POST['bank_account_type'], 
				"bank_account_number" => $_POST['bank_account_number'], 
				"bank_account_name" => $_POST['bank_account_name'], 
				"paypal_address" => $_POST['paypal_address'], 
			);

			BSPlatformUtils::updateTableData("user", $update_data, array("id"=>$_POST['user_id']));
		}
	}

	// ================================ show setting page ================================
	private function show_platform_setting() {
		$output = "";

		ob_start();
        include_once(BS_PLATFORM_PATH . 'views/bs_platform_admin_modal.php');
        $output .= ob_get_clean();

        ob_start();
        include_once(BS_PLATFORM_PATH . 'views/bs_platform_admin_setting.php');
        $output .= ob_get_clean();

        return $output;
    }

	public function handle_admin_bs_platform_setting_menu() {
		$this->common_library();

		wp_enqueue_script( 'bs_platform_admin_setting', BS_PLATFORM_URL . '/js/admin_setting.js', array( 'jquery' ), BS_PLATFORM_VER, true );
		wp_localize_script( 'bs_platform_admin_setting', 'bs_platform_admin_setting_ajax_object', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		) );

		echo $this->show_platform_setting();
	}

	// ==================== ajax process ===============
	// setting
	public static function bs_platform_ajax_setting() {
		$bs_platform_main = BSPlatformMain::get_instance();

		$bs_platform_main->set_option_value("add_salon_member_level", "");
		$bs_platform_main->set_option_value("entrance_salon_member_level", "");
		$bs_platform_main->set_option_value("entrance_salon_payment_flag", "");
		$bs_platform_main->set_option_value("send_create_salon_mail_flag", "");
		$bs_platform_main->set_option_value("send_public_salon_mail_flag", "");
		
		foreach($_REQUEST as $key=>$value) {
			if( $key != "action" && $key != "page" ){
				if($key == "salon_fee_percent") {
					$value = $value > 100 ? 100 : $value;
				}
				$bs_platform_main->set_option_value($key, maybe_serialize($value));
			}
		}
		$bs_platform_main->save_option();

		$result["result"] = "success";
		echo json_encode($result);
		wp_die();
	}
}