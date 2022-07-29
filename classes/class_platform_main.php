<?php
include_once(BS_PLATFORM_PATH . 'classes/class_platform_utils.php');
include_once(BS_PLATFORM_PATH . 'classes/class_platform_admin.php');
include_once(BS_PLATFORM_PATH . 'classes/class_platform_admin_salon_list.php');
include_once(BS_PLATFORM_PATH . 'classes/class_platform_admin_salon_payment_list.php');
include_once(BS_PLATFORM_PATH . 'classes/class_platform_user.php');

// 必要ファイルインクルード
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

class BSPlatformMain {
	private $bs_platform_options = [];

	protected static $_intance = null;
	protected $_swpm_flag = false;

	public static function get_instance() {
        self::$_intance = empty(self::$_intance) ? new BSPlatformMain() : self::$_intance;
        return self::$_intance;
    }

    public function __construct() {
		$this->bs_platform_options = (array) get_option( 'bs_platform_options', array() );

		add_action('plugins_loaded', array(&$this, "plugins_loaded"));

		add_action('user_register', array(&$this, 'bs_platform_user_registration'), 20);

		add_action('init', array(&$this, 'init_hook'));
		add_action('wp', array(&$this, 'wp_hook') );
        add_action('wp_loaded', array(&$this, 'handle_wp_loaded_tasks'), 20);
		
        add_action('admin_menu', array(&$this, 'admin_menu_hook'), 20);
		add_action('admin_init', array(&$this, 'admin_init_hook'));

		add_action( 'load-profile.php', array(&$this, 'disable_user_profile') );

		// custom post hook
		// add_filter( 'posts_join', array($this, 'posts_join'), 10, 2 );
		add_filter( 'posts_clauses_request', array($this, 'posts_clauses_request') );
		add_filter('wp_count_posts', array($this, 'wp_count_posts'), 10, 3);
		// add_filter('views_edit-post', array($this, 'views_edit_posts'), 10, 1);
		// add_action( 'pre_get_posts', array($this, 'pre_get_posts'), 20 ); 

		// add_action( 'pre_post_update', array(&$this, 'pre_post_update_hook'), 20, 2 );
		add_action( 'save_post', array(&$this, 'save_post_hook'), 20, 3 );
		// add_action( 'post_updated', array(&$this, 'post_updated_hook'), 20, 3 );

		// add_action('generate_rewrite_rules', array($this, 'add_rewrite_rules'));
		add_filter('post_link', array(&$this, "post_link"), 20, 3);
		// add_filter( 'the_content', array(&$this, 'the_content'), 90, 1 );

		// register shortcode
		add_shortcode('bs_pf_salon', array(&$this, 'bs_pf_salon_function'));
		add_shortcode('bs_pf_salon_blog', array(&$this, 'bs_pf_salon_blog_function'));

		//AJAX hooks (Admin)
        add_action('wp_ajax_bs_platform_ajax_setting', 'BSPlatformAdmin::bs_platform_ajax_setting');
        add_action('wp_ajax_nopriv_bs_platform_ajax_setting', 'BSPlatformAdmin::bs_platform_ajax_setting');

		// user list hook
		add_filter( 'manage_users_columns', array(BSPlatformUser::get_instance(), 'manage_users_columns'), 10 );
		add_filter( 'manage_users_custom_column', array(BSPlatformUser::get_instance(), 'manage_users_custom_column'), 10, 3 );

		// swpm hook
		add_filter('swpm_admin_member_prepare_itmes_filter', array($this, 'swpm_admin_member_prepare_itmes_filter'), 10, 2);

		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if(is_plugin_active( 'simple-membership/simple-wp-membership.php' )) {
			$this->_swpm_flag = true;

			add_action('swpm_create_membership_level_after_action', array(&$this, 'bs_pf_create_membership_level'), 10, 1);
		}

		// custom action 
		add_action('bspf_entracne_salon_user', array(BSPlatformUser::get_instance(), 'entrance_user'), 10, 2);
    }

	// ================= swpm plugin hook ==============
	public function swpm_admin_member_prepare_itmes_filter($query, $filters) {
		// $admin_user_id = get_current_user_id();
		$admin_user_id = $_SESSION['BS_PLATFORM_ADMIN_SESSION']['bspf_user_id'];

		$query .=  " INNER JOIN " . BSPlatformUtils::getTableName("user") . " as pu ON ( tbl_detail.member_id = pu.swpm_user_id ) ";
        $query .= " INNER JOIN " . BSPlatformUtils::getTableName("salon_detail") . " as psd ON ( psd.entrance_user_id = pu.id AND psd.allow_flag = 1) ";
		$query .= " INNER JOIN " . BSPlatformUtils::getTableName("salon") . " as ps ON ( ps.id = psd.salon_id AND ps.manager_user_id = '".$admin_user_id."') ";

		$filters[] = sprintf("tbl_detail.salon_id = '%d'", $_SESSION['BS_PLATFORM_ADMIN_SESSION']['bspf_salon_id']);
		return array($query, $filters);
	}

	// ================== post customize ================
	function views_edit_posts( $views ) {
		foreach ( $views as $index => $view ) {
			$views[ $index ] = preg_replace( '/ <span class="count">\([0-9]+\)<\/span>/', '', $view );
		}
	
		return $views;
	}

	function pre_get_posts($query) {

	}

	public function posts_join( $join, $wp_query ) {
		if ( ! is_admin() ){
			return $join;
		}

		$user = wp_get_current_user();

		if (in_array( 'administrator', $user->roles ) ) {
			return $join;
		}

		global $wpdb;
		
		$salon_id = BSPlatformUtils::getCurrentSalonID();
		$join .= " JOIN {$wpdb->postmeta} as my_postmeta on my_postmeta.post_id = {$wpdb->posts}.ID AND meta_key = 'salon_id' AND meta_value = '{$salon_id}'";
		return $join;
	}

	public function posts_clauses_request($clauses) {
		if ( ! is_admin() ){
			return $clauses;
		}

		$user = wp_get_current_user();
		global $wpdb;

		if (in_array( 'administrator', $user->roles ) ) {
			$clauses['where'] .= "  AND ({$wpdb->posts}.post_salon_id = 0) ";
			return $clauses;
		}

		$user_id = get_current_user_id();
		$salon_id = BSPlatformUtils::getCurrentSalonID();

		// $custom_author_query = "  AND ({$wpdb->posts}.post_salon_id = {$salon_id} OR {$wpdb->posts}.post_author = {$user_id}) ";
		$custom_author_query = "  AND ({$wpdb->posts}.post_salon_id = {$salon_id}) ";

		$clauses['where'] .= $custom_author_query;
		
		return $clauses;
	}

	function wp_count_posts( $counts, $type, $perm ) {	
		if ( ! is_admin() || 'readable' !== $perm ) {
			return $counts;
		}
	
		// // Only modify the counts if the user is not allowed to edit the posts of others
		// $post_type_object = get_post_type_object($type);
		// if (current_user_can( $post_type_object->cap->edit_others_posts ) ) {
		// 	return $counts;
		// }
	
		// $user = wp_get_current_user();
		// if (in_array( 'administrator', $user->roles ) ) {
		// 	return $counts;
		// }

		global $wpdb;
		
		// $query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s AND (post_author = %d) GROUP BY post_status";
		// $results = (array) $wpdb->get_results( $wpdb->prepare( $query, $type, get_current_user_id() ), ARRAY_A );

		$salon_id = BSPlatformUtils::getCurrentSalonID();
		$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s AND (post_salon_id = %d) GROUP BY post_status";
		$results = (array) $wpdb->get_results( $wpdb->prepare( $query, $type, $salon_id ), ARRAY_A );
		
		$counts = array_fill_keys( get_post_stati(), 0 );
	
		foreach ( $results as $row ) {
			$counts[ $row['post_status'] ] = $row['num_posts'];
		}
	
		return (object) $counts;
	}

	function pre_post_update_hook($post_id, $data) {
		$salon_id = BSPlatformUtils::getCurrentSalonID();
		$data['post_salon_id'] = $salon_id;

		return $data;
	}

	function save_post_hook($post_id, $post, $update){
		global $post_salon_id;

		// if(empty($post->post_salon_id)){
			$salon_id = ($post_salon_id == -1) ? BSPlatformUtils::getCurrentSalonID() : $post_salon_id;

			global $wpdb;
			$query = "UPDATE {$wpdb->posts} SET post_salon_id = '{$salon_id}' WHERE ID = {$post_id}";
			$wpdb->query($query);
			
			update_post_meta($post_id, "salon_id", $salon_id);
		// }

		$post_salon_id = -1;
	}

	function post_updated_hook($post_id, $post_after, $post_before){

	}
	// ==================================== plugin install & update =====================================
    public function plugins_loaded() {
        //Runs when plugins_loaded action gets fired
        if (is_admin() && BSPlatformUtils::has_admin_management_permission()) {
			$installed_version = $this->get_option_value('bs_platform_active_version');
			if (!empty($installed_version) && $this->get_option_value('bs_platform_active_version') != BS_PLATFORM_VER) {
                $this->run_update_installer();
            }
        }
    }
	
	public function plugin_installer() {
		global $wpdb;
		
		$regist_version = $this->get_option_value('bs_platform_active_version', '');

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$charset_collate = $wpdb->get_charset_collate();

		if(empty($regist_version)) {
			// ********************** regist db
			// role table
			$sql = "CREATE TABLE " . BSPlatformUtils::getTableName("role") . " (
				id int(2) NOT NULL PRIMARY KEY AUTO_INCREMENT, 
				role_name varchar(20) NOT NULL,
				wp_role varchar(20) NOT NULL
			) " . $charset_collate . ";";
			dbDelta($sql);

			$sql = "INSERT INTO  " . BSPlatformUtils::getTableName("role") . " (
				id , role_name , wp_role
			) VALUES (1 , '管理者', 'administrator');";
			$wpdb->query($sql);

			$sql = "INSERT INTO  " . BSPlatformUtils::getTableName("role") . " (
				id , role_name , wp_role
			) VALUES (2 , '運営者', 'salon_manager');";
			$wpdb->query($sql);

			$sql = "INSERT INTO  " . BSPlatformUtils::getTableName("role") . " (
				id , role_name , wp_role
			) VALUES (3 , '会員', 'contributor');";
			$wpdb->query($sql);

			// user table
			$sql = "CREATE TABLE " . BSPlatformUtils::getTableName("user") . " (
				id int(12) NOT NULL PRIMARY KEY AUTO_INCREMENT, 
				wp_user_id int(12) NOT NULL,
				swpm_user_id int(12) default 0,
				role_id int(2) default 3,
				bank_type tinyint(1) DEFAULT 0,
				bank_name varchar(255) DEFAULT NULL,
				bank_code varchar(10) DEFAULT NULL,
				bank_area_number varchar(10) DEFAULT NULL,
				bank_area_name varchar(100) DEFAULT NULL,
				bank_account_type tinyint(1) DEFAULT 0,
				bank_account_number varchar(10) DEFAULT NULL,
				bank_account_name varchar(100) DEFAULT NULL,
				paypal_address varchar(100) DEFAULT NULL
			) " . $charset_collate . ";";
			dbDelta($sql);

			// salon kind table
			$sql = "CREATE TABLE " . BSPlatformUtils::getTableName("kind") . " (
				id int(12) NOT NULL PRIMARY KEY AUTO_INCREMENT, 
				name varchar(255) NOT NULL,
				use_flag tinyint(1) DEFAULT 0,
				create_date datetime,
				update_date datetime
			) " . $charset_collate . ";";
			dbDelta($sql);

			// salon table
			$sql = "CREATE TABLE " . BSPlatformUtils::getTableName("salon") . " (
				id int(12) NOT NULL PRIMARY KEY AUTO_INCREMENT, 
				name varchar(255) NOT NULL,
				mng_nickname varchar(30) NULL,
				mng_introduction text default NULL,
				mng_message text default NULL,
				description text default NULL,
				can_doing_work text default NULL,
				entrance_merit text default NULL,
				usage_fee text NULL,
				keep_in_mind text default NULL,
				office_word text default NULL,
				image text default NULL,
				image_path text default NULL,
				sub_images text default NULL,
				sub_images_path text default NULL,
				videos text default NULL,
				videos_path text default NULL,
				manager_user_id int(12) NOT NULL,
				public_flag tinyint(1) DEFAULT 0,
				fee_percent decimal(5,2) DEFAULT 0.00,
				salon_kind_id int(12) NOT NULL,
				memo text default NULL,
				enter_payment_flag tinyint(1) DEFAULT 0,
				create_date datetime,
				update_date datetime
			) " . $charset_collate . ";";
			dbDelta($sql);

			// salon detail table
			$sql = "CREATE TABLE " . BSPlatformUtils::getTableName("salon_detail") . " (
				id int(16) NOT NULL PRIMARY KEY AUTO_INCREMENT, 
				salon_id int(12) NOT NULL,
				entrance_user_id int(12) NOT NULL,
				entrance_date datetime,
				allow_flag tinyint(1) DEFAULT 0,
				favorite_flag tinyint(1) DEFAULT 0
			) " . $charset_collate . ";";
			dbDelta($sql);

			// payment to salon manager from admin
			$sql = "CREATE TABLE " . BSPlatformUtils::getTableName("payment_history") . " (
				id int(20) NOT NULL PRIMARY KEY AUTO_INCREMENT, 
				salon_id int(12) NOT NULL,
				history_start datetime,
				history_end datetime,
				create_date datetime,
				update_date datetime,
				in_money DECIMAL(10,2) NOT NULL DEFAULT 0.00,
				out_money DECIMAL(10,2) NOT NULL DEFAULT 0.00,
				send_user_id int(12) DEFAULT 0,
				receive_user_id int(12) DEFAULT 0,
				fee_percent decimal(5,2) DEFAULT 0.00,
				process_status tinyint(1) DEFAULT 0
			) " . $charset_collate . ";";
			dbDelta($sql);

			/* 既に存在する管理者アカウント=>Salonのuserテーブルに登録 */
			$sql = "SELECT * FROM " . $wpdb->prefix . "users ";
			$users = $wpdb->get_results($sql);

			foreach($users as $user){
				$user_data = get_userdata($user->ID);
				$user_role = isset($user_data->wp_capabilities['administrator']) ? '1' : '3';
				$this->_user_registration($user->ID, $user_role);
			}

			// *************** create page *******************
			$bs_pf_salon_page = get_page_by_path('bs_pf_salon');
			if($bs_pf_salon_page === null) { // insert salon page
				$page_id = wp_insert_post(
					array(
						'post_title'   => 'サロンページ',
						'post_name'    => 'bs_pf_salon',
						'post_status'  => 'publish',
						'post_type'    => 'page',
						'post_content' => '[bs_pf_salon]',
					)
				);
			}

			// ************ set option *************
			$this->set_option_value('salon_fee_percent', '10');
		}

		// db update
		$row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$wpdb->posts}' AND column_name = 'post_salon_id'"  );
		if(empty($row)){
			$wpdb->query("ALTER TABLE {$wpdb->posts} ADD COLUMN post_salon_id int(12) NULL DEFAULT 0");
		}

		// ******************* add user role(salon_manager) ********************
		// refernece : https://wordpress.org/support/article/roles-and-capabilities/
		// remove role
		remove_role('salon_manager');

		// add_role
		add_role(
			'salon_manager',
			'サロン運営者',
			array(
				'read'         => true,
				'upload_files' => true,
				'edit_posts'   => true,
				'edit_others_posts' => true,
				'edit_private_posts' => true,
				'edit_published_posts' => true,
				'delete_posts' => true,
				// 'delete_others_posts' => false,
				'delete_private_posts' => true,
				'delete_published_posts' => true,
				'publish_posts' => true,
				// 'publish_pages' => true,
				// 'edit_pages' => true,
				// 'delete_pages' => true,
				// 'edit_others_pages' => false,
				// 'delete_others_pages' => false,
				'edit_theme_options' => true,
				// 'list_users' => true
			),
		);

		// add cap
		$custom_permission = array(
			'manage_bspf_options',
			'manage_polls',
		);

		$salon_manager = get_role( "salon_manager" );
		foreach ($custom_permission as $cap){
			$salon_manager->add_cap( $cap );
		}

		$administrator = get_role( 'administrator' );
		foreach ($custom_permission as $cap){
			$administrator->add_cap( $cap );
		}
		
		// check swpm level(サロンを開設できる会員ランク)
		if($this->_swpm_flag) {
			$add_salon_member_level = maybe_unserialize($this->get_option_value('add_salon_member_level', ''));
			if(empty($add_salon_member_level)) {
				$swpm_levels = SwpmMembershipLevelUtils::get_all_membership_levels_in_array();
				$add_salon_member_level = array();
				foreach($swpm_levels as $level_id=>$level_name){
					$add_salon_member_level[] = $level_id;
				}

				$this->set_option_value('add_salon_member_level', maybe_serialize($add_salon_member_level));
			}
		}

		$this->save_option();

		// ********************** update process **********************
		if($regist_version != BS_PLATFORM_VER) {
			$this->run_update_installer();
		}
	}

	private function db_update() {
		global $wpdb;

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$charset_collate = $wpdb->get_charset_collate();
		
		$regist_version = $this->get_option_value('bs_platform_active_version', '');
		if($regist_version != BS_PLATFORM_VER) {
			// user table update
			$sql = "CREATE TABLE " . BSPlatformUtils::getTableName("user") . " (
				id int(12) NOT NULL PRIMARY KEY AUTO_INCREMENT, 
				wp_user_id int(12) NOT NULL,
				swpm_user_id int(12) default 0,
				role_id int(2) default 3,
				bank_type tinyint(1) DEFAULT 0,
				bank_name varchar(255) DEFAULT NULL,
				bank_code varchar(10) DEFAULT NULL,
				bank_area_number varchar(10) DEFAULT NULL,
				bank_area_name varchar(100) DEFAULT NULL,
				bank_account_type tinyint(1) DEFAULT 0,
				bank_account_number varchar(10) DEFAULT NULL,
				bank_account_name varchar(100) DEFAULT NULL,
				paypal_address varchar(100) DEFAULT NULL
			) " . $charset_collate . ";";
			dbDelta($sql);

			// payment to salon manager from admin
			$sql = "CREATE TABLE " . BSPlatformUtils::getTableName("payment_history") . " (
				id int(20) NOT NULL PRIMARY KEY AUTO_INCREMENT, 
				salon_id int(12) NOT NULL,
				history_start datetime,
				history_end datetime,
				create_date datetime,
				update_date datetime,
				in_money DECIMAL(10,2) NOT NULL DEFAULT 0.00,
				out_money DECIMAL(10,2) NOT NULL DEFAULT 0.00,
				send_user_id int(12) DEFAULT 0,
				receive_user_id int(12) DEFAULT 0,
				fee_percent decimal(5,2) DEFAULT 0.00,
				process_status tinyint(1) DEFAULT 0
			) " . $charset_collate . ";";
			dbDelta($sql);

			// salon kind table
			$sql = "CREATE TABLE " . BSPlatformUtils::getTableName("kind") . " (
				id int(12) NOT NULL PRIMARY KEY AUTO_INCREMENT, 
				name varchar(255) NOT NULL,
				use_flag tinyint(1) DEFAULT 0,
				create_date datetime,
				update_date datetime
			) " . $charset_collate . ";";
			dbDelta($sql);

			// salon table
			$sql = "CREATE TABLE " . BSPlatformUtils::getTableName("salon") . " (
				id int(12) NOT NULL PRIMARY KEY AUTO_INCREMENT, 
				name varchar(255) NOT NULL,
				mng_nickname varchar(30) NULL,
				mng_introduction text default NULL,
				mng_message text default NULL,
				description text default NULL,
				can_doing_work text default NULL,
				entrance_merit text default NULL,
				usage_fee text NULL,
				keep_in_mind text default NULL,
				office_word text default NULL,
				image text default NULL,
				image_path text default NULL,
				sub_images text default NULL,
				sub_images_path text default NULL,
				videos text default NULL,
				videos_path text default NULL,
				manager_user_id int(12) NOT NULL,
				public_flag tinyint(1) DEFAULT 0,
				fee_percent decimal(5,2) DEFAULT 0.00,
				salon_kind_id int(12) NOT NULL,
				memo text default NULL,
				enter_payment_flag tinyint(1) DEFAULT 0,
				create_date datetime,
				update_date datetime
			) " . $charset_collate . ";";
			dbDelta($sql);
		}
	}

	public function run_update_installer() {
		$this->db_update();

		// ------------------- サロン開設時、メール設定 -------------------------
		$this->set_option_value('send_create_salon_mail_flag', '1');		

		$create_salon_mail_subject = "新しいサロンの開設通知";
		$this->set_option_value('create_salon_mail_subject', $create_salon_mail_subject);

		$create_salon_mail_body = <<<EOF
{user_name}様がサロンを開設しました。

********************************
サロン名：{salon_name}
サロンの概要、特徴：{salon_description}
********************************

サロンリストからご確認ください。
EOF;
		$this->set_option_value('create_salon_mail_body', $create_salon_mail_body);	
		
		// ------------------- サロン開設承認時、メール設定 -------------------------
		$this->set_option_value('send_public_salon_mail_flag', '1');

		$public_salon_mail_subject = "サロン開設申請が許可されました";
		$this->set_option_value('public_salon_mail_subject', $public_salon_mail_subject);

		$public_salon_mail_body = <<<EOF
{site_name}サポートデスクです。
{user_name}様のサロン開設申請が許可されました。

下記のURLからアクセスしてください。
{salon_url}
EOF;
		$this->set_option_value('public_salon_mail_body', $public_salon_mail_body);	

		// ------------------- save version -------------------
		$this->set_option_value('bs_platform_active_version', BS_PLATFORM_VER);		

		$this->save_option();
	}

	// ==================================== process after user register ============================
	private function _user_registration($user_id, $role_id = 3){
		// check exist user
		$query = "select id from " . BSPlatformUtils::getTableName("user") . " Where wp_user_id = '".$user_id."'";
		$user_data = BSPlatformUtils::getRowData($query);

		if($user_data){ return; }

		$user = get_userdata($user_id);
		$insert_data = array(
			'wp_user_id' => $user_id,
			'role_id' => $role_id, // general member role
			'swpm_user_id' => 0
		);
		if($this->_swpm_flag){
			$swpm_user = SwpmMemberUtils::get_user_by_email($user->user_email);
			$insert_data['swpm_user_id'] = $swpm_user->member_id;
		}

		BSPlatformUtils::insertTableData('user', $insert_data);
	}

	public function bs_platform_user_registration($user_id) {
		$this->_user_registration($user_id);
	}

	// ==================================== plugin hook =====================================
    public function handle_wp_loaded_tasks() {
		if ( is_admin() ) return;

		// using user page
		BSPlatformUser::get_instance()->common_library();

		// entrance or favorite
		$bspf_action = filter_input(INPUT_GET, 'bspf_action');
        if (!empty($bspf_action) && $bspf_action == "add_salon") {
            $salon_id = filter_input(INPUT_GET, 'salon_id', FILTER_VALIDATE_INT);
			$mode = filter_input(INPUT_GET, 'mode');
            BSPlatformUser::get_instance()->add_salon_user($salon_id, $mode);
        }
    }

	// ------------------------------- admin page ----------------------------
	public function admin_bs_platform_salon_kind_list() {
        BSPlatformAdmin::get_instance()->handle_admin_bs_platform_salon_kind_list_menu();
    }

    public function admin_bs_platform_salon_list() {
        BSPlatformAdmin::get_instance()->handle_admin_bs_platform_salon_list_menu();
    }

	public function admin_bs_platform_setting() {
        BSPlatformAdmin::get_instance()->handle_admin_bs_platform_setting_menu();
    }

	public function admin_bs_platform_payment_list() {
        BSPlatformAdmin::get_instance()->handle_admin_bs_platform_salon_payment_list();
    }

	public function admin_bs_platform_bank() {
		BSPlatformAdmin::get_instance()->handle_admin_bs_platform_bank_menu();
	}

	public function admin_bs_platform_member() {
		BSPlatformAdmin::get_instance()->handle_admin_bs_platform_member_menu();
	}

	public function admin_bs_platform_my_salon() {
		BSPlatformAdmin::get_instance()->handle_admin_bs_platform_my_salon_menu();
	}

	public function admin_menu_hook() {
        $menu_parent_slug = 'bs_platform_salon_list';

		// Add menu
        add_menu_page(__("プラットフォーム", 'bs-platform'), "プラットフォーム", BS_PLATFORM_MANAGEMENT_PERMISSION, $menu_parent_slug, array(&$this, "admin_bs_platform_salon_list"), 'dashicons-admin-multisite', 2);

		add_submenu_page($menu_parent_slug, __("サロンリスト", 'bs-platform'), 'サロンリスト', BS_PLATFORM_MANAGEMENT_PERMISSION, 'bs_platform_salon_list', array(&$this, "admin_bs_platform_salon_list"));
		add_submenu_page($menu_parent_slug, __("サロン種類管理", 'bs-platform'), 'サロン種類管理', BS_PLATFORM_MANAGEMENT_PERMISSION, 'bs_platform_salon_kind', array(&$this, "admin_bs_platform_salon_kind_list"));
		add_submenu_page($menu_parent_slug, __("支払い管理", 'bs-platform'), '支払い管理', BS_PLATFORM_MANAGEMENT_PERMISSION, 'bs_platform_payment_list', array(&$this, "admin_bs_platform_payment_list"));
		add_submenu_page($menu_parent_slug, __("設定", 'bs-platform'), '設定', BS_PLATFORM_MANAGEMENT_PERMISSION, 'bs_platform_setting', array(&$this, "admin_bs_platform_setting"));

		if ( current_user_can( 'salon_manager' ) ) {
			add_submenu_page($menu_parent_slug, __("Myサロン", 'bs-platform'), 'Myサロン', BS_PLATFORM_SALON_PERMISSION, 'bs_platform_my_salon', array(&$this, "admin_bs_platform_my_salon"));
			add_submenu_page($menu_parent_slug, __("会員情報", 'bs-platform'), '会員情報', BS_PLATFORM_SALON_PERMISSION, 'bs_platform_member', array(&$this, "admin_bs_platform_member"));
			add_submenu_page($menu_parent_slug, __("銀行情報", 'bs-platform'), '銀行情報', BS_PLATFORM_SALON_PERMISSION, 'bs_platform_bank', array(&$this, "admin_bs_platform_bank"));
		}

		// remove profile menu
		if ( ! current_user_can('administrator') && current_user_can( 'salon_manager' ) ) {
			// theme menu
			@remove_menu_page('themes.php');
			@remove_submenu_page('theme_options', 'theme_options');
			@remove_submenu_page('theme_options', 'tcd_membership_options');
			@remove_submenu_page('theme_options', 'tcd_membership_export_users');

			// other page
			@remove_submenu_page('users.php', 'profile.php');
			@remove_submenu_page('users.php', 'edit.php?post_type=data_upload');
			@remove_menu_page('profile.php');
			@remove_menu_page( 'edit-comments.php' ); //Comments
		}
    }

	public function disable_user_profile() {
		if ( is_admin() ) {
			$user = wp_get_current_user();
	
			if ( current_user_can( 'salon_manager' ) ) {
				wp_die( 'You are not allowed to edit the user profile.' );
			}
		}	
	}

	public function admin_init_hook() {
		$edit_salon = filter_input(INPUT_POST, 'edit_salon');
        if (!empty($edit_salon)) {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            BSPlatformAdminSalonList::get_instance()->edit_salon($id);
        }

		$add_payment = filter_input(INPUT_POST, 'add_payment');
        if (!empty($add_payment)) {
            BSPlatformAdminSalonPaymentList::get_instance()->add_payment_history();
        }

		$bank_edit = filter_input(INPUT_POST, 'btn_bank_edit');
        if (!empty($bank_edit)) {
            BSPlatformAdmin::get_instance()->edit_bank();
        }

		$edit_salon_kind = filter_input(INPUT_POST, 'edit_salon_kind');
        if (!empty($edit_salon_kind)) {
            BSPlatformAdminSalonKindList::get_instance()->edit_salon_kind();
        }

		$bspf_admin_action = isset($_REQUEST['bspf_admin_action']) ? $_REQUEST['bspf_admin_action'] : ''; // filter_input(INPUT_POST, 'bspf_admin_action');
        if (!empty($bspf_admin_action) && $bspf_admin_action == "my_salon") {
            BSPlatformAdmin::get_instance()->edit_my_salon();
        }
	}

	public function wp_hook() {
		if ( is_admin() ) return;
	}

	public function getCurrentUrl(){
		return sprintf(
			"%s://%s%s",
			isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
			$_SERVER['SERVER_NAME'],
			$_SERVER['REQUEST_URI']
		);
	}

	public function add_rewrite_rules($wp_rewrite) {
		$new_rules = array(
			"/salon_page/1" => "index.php?salon_id=1",
		);

		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
	}

	public function post_link($permalink, $post, $leavename) {
		if( is_admin() ) { return $permalink; }
		
		$salon_id = BSPlatformUtils::getCurrentSalonID();
		if ( !empty($salon_id) ) {
			$permalink = add_query_arg( 'salon_id', $salon_id, $permalink );
		}

		return $permalink;
	}
	
	public function the_content($content) {
		global $post;

		if ( is_single() ) {
			$salon_id = BSPlatformUtils::getCurrentSalonID();
			if($salon_id != $post->post_salon_id) {
				return "存在しない記事です。";
			}
		}
		return $content;
	}

	public function init_hook() {
		if ( is_admin() ) {
			if ( current_user_can( 'salon_manager' ) ) {
				if(!isset($_SESSION['BS_PLATFORM_ADMIN_SESSION']['bspf_user_id'])) {
					$bspf_user = BSPlatformUtils::getBspfUserByWpUserId(get_current_user_id());
					$_SESSION['BS_PLATFORM_ADMIN_SESSION']['bspf_user_id'] = $bspf_user->id;
				}
				$bspf_user_id = $_SESSION['BS_PLATFORM_ADMIN_SESSION']['bspf_user_id'];

				if(!isset($_SESSION['BS_PLATFORM_ADMIN_SESSION']['bspf_salon_id'])) {
					$bspf_salon = BSPlatformUtils::getMySalon($bspf_user_id);
					if($bspf_salon) {
						$_SESSION['BS_PLATFORM_ADMIN_SESSION']['bspf_salon_id'] = $bspf_salon->id;
					}
				}
			}

			return;
		}

		$salon_id = isset($_REQUEST['salon_id']) ? $_REQUEST['salon_id'] : '';
		if( home_url('/') == $this->getCurrentUrl() && empty($salon_id)) {
			$_SESSION['BS_PLATFORM_SALON_SESSSION_KEY'] = "";
		}
		if(!empty($salon_id)) { $_SESSION['BS_PLATFORM_SALON_SESSSION_KEY'] = $salon_id; }

		// *************** hook home page ***************
		if(!empty($salon_id) && isset($_REQUEST['not_entrance'])) {
			echo('<script>alert("現在の会員ランクでは加入できません。")</script>');
		}
/*
		if( home_url('/') == $this->getCurrentUrl() || strpos($this->getCurrentUrl(), "bs_pf_salon") !== false ) { 

		}else{
			if(isset($_REQUEST['BS_PLATFORM_SALON_SESSSION_KEY'])){
				$_SESSION['BS_PLATFORM_SALON_SESSSION_KEY'] = $_REQUEST['BS_PLATFORM_SALON_SESSSION_KEY'];
			}

			if(empty($_SESSION['BS_PLATFORM_SALON_SESSSION_KEY'])){
				wp_safe_redirect( home_url() );
				exit(0);
			}
		}
*/		
    }

	private function bs_pf_create_membership_level($create_level_id) {
		$add_salon_member_level = maybe_unserialize($this->get_option_value('add_salon_member_level', array()));
		$add_salon_member_level[] = $create_level_id;
		$this->set_option_value('add_salon_member_level', maybe_serialize($add_salon_member_level));
		$this->save_option();
	}

	public function is_create_salon(){
		$create_salon_flag = true;
		if($this->_swpm_flag) {
			$create_salon_flag = false;

			$auth = SwpmAuth::get_instance();
			if ($auth->is_logged_in()) {
				$bs_platform_main_obj = BSPlatformMain::get_instance(); 
				$add_salon_member_level = maybe_unserialize($bs_platform_main_obj->get_option_value("add_salon_member_level", array()));
				if(empty($add_salon_member_level)){ $add_salon_member_level = array(); }

				$my_current_memeber_level = $auth->get("membership_level", "", "", true); 
				if(in_array($my_current_memeber_level, $add_salon_member_level)){
					$create_salon_flag = true;
				}
			}
		}

		return $create_salon_flag;
	}

	public function bs_pf_salon_blog_function($attrs){
		if ( is_admin() ){ return; }
		
		if (!is_array($attrs)) {
			$attrs = array();
		}

		$attrs = shortcode_atts(
			array(
				'type' => "list",
				'title' => '',
			), $attrs
		);

		return $this->_render_blog($attrs);
	}

	private function _render_blog($attrs) {
		$result_html = "";

		$current_bspf_user = BSPlatformUtils::getBspfUserByWpUserId(get_current_user_id());
		if($attrs['type'] == "entrance" || $attrs['type'] == "favorite") {
			if(!$current_bspf_user) {
				return '<p style="text-align:center;"><a href="'.home_url().'?memberpage=login">ログイン</a>してください</p>';
			}
		}

		$blog_title = "";
		$blog_action = "";
		$blog_data = array();
		switch($attrs['type']) {
			case 'entrance': // 加入済サロン
				$blog_title = "加入済" . BSPlatformUtils::getSalonShowLabel();
				$blog_action = "salon_entrance_list";
				$blog_data = BSPlatformUtils::getSalonList($current_bspf_user->id, "entrance");
				break;
			case 'favorite': // お気に入りサロン
				$blog_title = "お気に入り" . BSPlatformUtils::getSalonShowLabel();
				$blog_action = "salon_fav_list";
				$blog_data = BSPlatformUtils::getSalonList($current_bspf_user->id, "favorite");

				break;
			default: // サロンリスト
				$blog_title = BSPlatformUtils::getSalonShowLabel() . "リスト";
				$blog_action = "salon_list";
				$blog_data = BSPlatformUtils::getSalonList();

				break;
		}
		$blog_title = !empty($attrs['title']) ? $attrs['title'] : $blog_title;

		// // blog header start
		// $result_html .= '<div id="cb_100" class="p-cb__item p-cb__item--blog has-bg aos-init aos-animate" style="background-color: #cccccc;" data-aos="fade-up" data-aos-duration="1500">';
		// $result_html .= '<div class="p-cb__item-inner l-inner">';
		
		// blog title
		$result_html .= '<div class="top-content-title t-has-btn t-btn-home"><div class="title-left">';
		$result_html .= '<div class="main-title"><h2 class="title">'.$blog_title.'</h2></div>';
		$result_html .= '</div></div>';

		// blog body		
		$result_html .= '<div class="p-index-archive p-blog-archive inner-blog p-blog-slider owl-carousel">';
		foreach($blog_data as $key=>$salon) {
			$bspf_user = BSPlatformUtils::getTableData('user', array('id'=>$salon->manager_user_id));
			$author = get_user_by( 'ID', $bspf_user[0]->wp_user_id );
			
			ob_start();
			$args = array(
				'salon' => $salon,
				'key' => $key,
				'author' => $author,
			);
			set_query_var( 'args', $args );
			get_template_part( 'template-parts/salon-item', null, $args );
			$result_html .= ob_get_contents();
			ob_end_clean();
		}
		$result_html .= '</div>';

		// blog button
		$result_html .= '<div class="single-button">';
		$result_html .= '<a class="button white-btn has-arrow " href="' . esc_url( get_tcd_membership_memberpage_url( 'bs_pf_salon' ) )  .'?bspf_action='.$blog_action.'">一覧ページへ</a>';
		$result_html .= '</div>';
		
		// // blog header end
		// $result_html .= '</div>';		
		// $result_html .= '</div>';

		return $result_html;
	}

	public function bs_pf_salon_function($attrs) {
		if ( is_admin() ){ return; }

		if( !is_user_logged_in() ) {
			return '<p style="text-align:center;"><a href="'.home_url().'?memberpage=login">ログイン</a>してください</p>';
		}

		if (!is_array($attrs)) {
			$attrs = array();
		}

		$page = 'salon_list';
		if(!BSPlatformUtils::is_admin() && $this->is_create_salon()) {
			$page = "my_salon";
		}

		$attrs = shortcode_atts(
			array(
				'page' => $page,
				'salon_id' => '',
				'salon_name' => '',
			), $attrs
		);

		$page = ( isset( $_REQUEST['bspf_action'] ) && $_REQUEST['bspf_action'] ) ? $_REQUEST['bspf_action'] : $attrs['page'];
		echo $this->_render($page, $attrs);
	}

	private function _render($page, $attrs) {
		$bspf_user = BSPlatformUtils::getBspfUserByWpUserId(get_current_user_id());
		$page_data = array();
		$page_msg = array();

		$my_salon_data = null;
		if($bspf_user) {
			$mode = isset( $_REQUEST['mode'] ) ? $_REQUEST['mode'] : "";
			if($page == 'my_salon') {
				$salon_id = isset( $_REQUEST['my_salon_id'] ) ? $_REQUEST['my_salon_id'] : "";

				if(!empty($mode)){
					$old_page_data = BSPlatformUtils::getMySalon($bspf_user->id);

					if($mode == "delete"){ // delete salon
						// delete image file
						if(!empty($old_page_data->image_path)){
							@unlink( $old_page_data->image_path );
						}

						// delete sub image file
						$images_path = maybe_unserialize($old_page_data->sub_images_path);
						if(is_array($images_path)) {
							foreach($images_path as $img_path) {
								if(!empty($img_path)){
									@unlink( $img_path );
								}
							}
						}

						// delete video file
						$videos_path = maybe_unserialize($old_page_data->videos_path);
						if(is_array($videos_path)) {
							foreach($videos_path as $video_path) {
								if(!empty($video_path)){
									@unlink( $video_path );
								}
							}
						}

						BSPlatformUtils::deleteTableData("salon", array('id'=>$salon_id, 'manager_user_id'=>$bspf_user->id));

						// update user role
						if($bspf_user->role_id == 2) {
							BSPlatformUtils::updateTableData('user', array("role_id"=>3), array("id"=>$bspf_user->id));

							$user = wp_get_current_user();
							$user->remove_role('salon_manager');
						}

						$page_msg[] = "サロンを削除しました。";
					} else if($mode == "edit" || $mode == "public_request"){ // create or update salon
						$error_flag = false;
						// check salon name
						$salon_name = $_REQUEST['salon_name'];
						$check_condtion = array("name"=>$salon_name);
						if(!empty($salon_id)) {
							$check_condtion['id'] = array("value"=>$salon_id, "compare" => "exclude");
						}
						$exist_salon_list = BSPlatformUtils::getTableData("salon", $check_condtion);
						if($exist_salon_list && count($exist_salon_list) > 0) {
							$error_flag = true;
							$page_msg[] = "同名のサロンが既に存在しています。";
						}

						if(!$error_flag) {
							// upload image file
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
								'fee_percent' => $this->get_option_value('salon_fee_percent', '10'),
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
								
								// // send mail
								// BSPlatformUtils::sendMailForCreatePlatform($insert_salon_id, $bspf_user);
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

									BSPlatformUtils::sendMailForCreatePlatform($salon_id, $bspf_user);

									$page_msg[] = "サロン開設を申し込みました。";
								}
							} else {
								$page_msg[] = "サロンを保存しました。";
							}
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
				
				$page_data = BSPlatformUtils::getMySalon($bspf_user->id);
				if(!$page_data){
					$page_data =  (object) array( 
						'id' => '', 'name' => '', 'image' => '', 'image_path' => '', 'description' => '', 'public_flag' => '',
						'salon_kind_id' => '', 'mng_nickname' => '', 'mng_introduction' => '', 'mng_message' => '', 'can_doing_work' => '', 'entrance_merit' => '',
						'usage_fee' => '', 'keep_in_mind' => '', 'office_word' => '', 'sub_images' => '', 'sub_images_path' => '', 'videos' => '', 'videos_path' => '', 
						'enter_payment_flag' => 0,
					);
				}
			} else if($page == 'salon_list') {
				$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

				if(!empty($mode)) {
					$salon_id = isset($_REQUEST['salon_list_id']) ? $_REQUEST['salon_list_id'] : '';
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
						// if(empty($detail_id)) {
							$regist_data['entrance_date'] = date("Y-m-d H:i:s");
						// }
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

				$where = array();
				if(isset($_REQUEST['salon_search_kind']) && !empty($_REQUEST['salon_search_kind'])){
					$where[] = "salon_kind_id = '".$_REQUEST['salon_search_kind']."'";
				}
				if(isset($_REQUEST['salon_search_name'])  && !empty($_REQUEST['salon_search_name'])){
					$where[] = "name Like '%".$_REQUEST['salon_search_name']."%'";
				}
				$page_data = BSPlatformUtils::getSalonList($bspf_user->id, "", $where);
			} else if($page == 'salon_entrance_list') {
				$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
				if($mode == "withdrawal"){ // delete salon
					$salon_id = $_REQUEST['id'];
					if(!empty($salon_id)){
						$regist_data = array();
						$regist_data['allow_flag'] = 0;
						$regist_data['entrance_date'] = null;
						BSPlatformUtils::updateTableData("salon_detail", $regist_data, array('salon_id'=>$salon_id, 'entrance_user_id'=>$bspf_user->id));

						// BSPlatformUtils::deleteTableData("salon_detail", array('salon_id'=>$salon_id, 'entrance_user_id'=>$bspf_user->id));
						do_action("bspf_".$mode."_after_action", $salon_id, $bspf_user->swpm_user_id);
					}
				}
				$page_data = BSPlatformUtils::getSalonList($bspf_user->id, "entrance");
			} else if($page == 'salon_fav_list') {
				$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
				if($mode == "delete"){ // delete salon
					$salon_id = $_REQUEST['id'];
					if(!empty($salon_id)){
						$regist_data = array();
						$regist_data['favorite_flag'] = 0;
						BSPlatformUtils::updateTableData("salon_detail", $regist_data, array('salon_id'=>$salon_id, 'entrance_user_id'=>$bspf_user->id));
					}
				}
				$page_data = BSPlatformUtils::getSalonList($bspf_user->id, "favorite");
			} else if($page == 'salon_detail') {
				$salon_id = isset($_REQUEST['salon_detail_id'])?$_REQUEST['salon_detail_id']:'';
				$page_data = BSPlatformUtils::getSalonData($salon_id);
			} else if($page == 'my_bank') {
				$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

				if(!empty($mode)) {
					switch($mode) {
						case "edit":
							$update_data = array(
								"bank_type" => $_POST['bank_type'], 
								"bank_name" => $_POST['bank_name'], 
								"bank_code" => $_POST['bank_code'], 
								"bank_area_number" => $_POST['bank_area_number'], 
								"bank_area_name" => $_POST['bank_area_name'], 
								"bank_account_type" => $_POST['bank_account_type'], 
								"bank_account_number" => $_POST['bank_account_number'], 
								"bank_account_name" => $_POST['bank_account_name'], 
								"paypal_address" => $_POST['paypal_address'], 
							);

							BSPlatformUtils::updateTableData("user", $update_data, array("id"=>$bspf_user->id));
							break;
						default:
							break;
					}
				}

				$page_data = $bspf_user = BSPlatformUtils::getBspfUserByWpUserId(get_current_user_id());
				if(empty($page_data->bank_type)){$page_data->bank_type = "1";}
			}

			$salon_kind_data = BSPlatformUtils::getTableData("kind", array("use_flag" => 1));
			$my_salon_data = BSPlatformUtils::getMySalon($bspf_user->id);
		}

		ob_start();
		include_once(BS_PLATFORM_PATH . 'views/bs_platform_common.php');
		$data = ob_get_contents();
		ob_end_clean();
		
		return $data;
	}

	// ================================== utility function ============================
	public function set_option_value($key, $value) {	
		$this->bs_platform_options[$key] = $value;
	}

	public function get_option_value($key, $default = '') {	
		return isset($this->bs_platform_options[$key])? $this->bs_platform_options[$key] : $default;
	}

	public function save_option() {
		update_option('bs_platform_options', $this->bs_platform_options);
	}
}