<?php
if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class BSPlatformAdminSalonList extends WP_List_Table {

	protected static $_intance = null;
	protected $_error = '';
    protected $salon_kind_data = array();

	public static function get_instance() {
        self::$_intance = empty(self::$_intance) ? new BSPlatformAdminSalonList() : self::$_intance;
        return self::$_intance;
    }

    function __construct() {
        parent::__construct(array(
            'singular' => 'サロンリスト',
            'plural' => 'サロンリスト',
            'ajax' => false
        ));

        $kind_data = BSPlatformUtils::getTableData("kind", array());
        $this->salon_kind_data[0] = "選択なし";
        foreach($kind_data as $key=>$row){
            $this->salon_kind_data[$row->id] = $row->name;
        }
        
    }

	function set_error($error){
		$this->_error = $error;
	}

	function get_error() {
		return $this->_error;
	}

    function get_columns() {
		global $dp_options;

		$default_columns = array(
            'cb' => '<input type="checkbox" />', 
           	'id' => _('ID'),
            'salon_kind_id' => BSPlatformUtils::getSalonShowLabel() . _('種類'),
            'name' => BSPlatformUtils::getSalonShowLabel() . _('名'),
            'image' => _('画像'),
            'manager_user_id' => '運営者', 
            'fee_percent' => '報酬率(%)',
            'bank_info' => '銀行情報',
            'public_flag' => '公開状態'
        );

        return $default_columns;
    }

    function get_sortable_columns() {
        return array(
            'id' => array('id', true), //True means already sorted
            'salon_kind_id' => array('name', false),
            'name' => array('name', false),
            'public_flag' => array('public_flag', false),
        );
    }

    function get_bulk_actions() {
		$actions = array();

        return $actions;
    }

    function column_default($item, $column_name) {
        return $item[$column_name];
    }

    function column_id($item) {
        $actions = array();
		
        $actions['edit'] = sprintf('<a href="admin.php?page=bs_platform_salon_list&platform_action=edit&id=%s">確認</a>', $item['id']);
        // $actions['payment'] = sprintf('<a href="admin.php?page=bs_platform_payment&salon_id=%s">支払い処理</a>', $item['id']);
        // $actions['payment_history'] = sprintf('<a href="admin.php?page=bs_platform_payment_history&salon_id=%s">支払い履歴</a>', $item['id']);
        if($item['public_flag'] == BS_PF_SALON_STATUS_REQUIRE){
            $actions['cancel'] = sprintf('<a href="admin.php?page=bs_platform_salon_list&platform_action=cancel&id=%s">キャンセル</a>', $item['id']);
            $actions['public'] = sprintf('<a href="admin.php?page=bs_platform_salon_list&platform_action=public&id=%s">公開</a>', $item['id']);
        } else if($item['public_flag'] == BS_PF_SALON_STATUS_CANCEL) {
            $actions['public'] = sprintf('<a href="admin.php?page=bs_platform_salon_list&platform_action=public&id=%s">公開</a>', $item['id']);
        } else if($item['public_flag'] == BS_PF_SALON_STATUS_PUBLIC) {
            $actions['cancel'] = sprintf('<a href="admin.php?page=bs_platform_salon_list&platform_action=cancel&id=%s">キャンセル</a>', $item['id']);
        }
        $actions['delete'] = sprintf('<a href="admin.php?page=bs_platform_salon_list&platform_action=delete&id=%s&delete_nonce=%s" onclick="return confirm(\''.BSPlatformUtils::_('削除してもよろしいですか？').'\')">'._('削除').'</a>', $item['id'], wp_create_nonce('delete_salon_admin'));
        if($item['public_flag'] > BS_PF_SALON_STATUS_REQUIRE){
            $actions['view'] = sprintf('<a href="'.home_url().'?salon_id=%s" target="_blank">表示</a>', $item['id']);
        }

        return $item['id'] . $this->row_actions($actions);
    }

    function column_salon_kind_id($item) {
        return $this->salon_kind_data[$item['salon_kind_id']];
    }

    function column_image($item) {
        return "<img src='".$item['image']."' width='60px'>";
    }

	function column_manager_user_id($item) {
        $bspf_user = BSPlatformUtils::getUser($item['manager_user_id']);
		$user = get_user_by("id", $bspf_user->wp_user_id);
		
        if($user) {
            $member_url = admin_url(). "user-edit.php?user_id=" . $user->ID;
        }
        
		return $user->display_name . "(". $user->user_email . ")" . "<br/><a href='".$member_url."'>詳細ページ</a>";
	}

    function column_bank_info($item) {
        $bspf_user = BSPlatformUtils::getUser($item['manager_user_id']);

        $column = "";
        if($bspf_user) {
            if($bspf_user->bank_type == 1) {
                $bank_account_type = ($bspf_user->bank_account_type == 1) ? "普通" : "当座";

                $column = "<div><p>種類：銀行</p><p>金融機関名：".$bspf_user->bank_name."</p>";
                $column .= "<p>支店番号：".$bspf_user->bank_area_number."</p><p>支店名：".$bspf_user->bank_area_name."</p>";
                $column .= "<p>口座種別：".$bank_account_type."</p><p>口座番号：".$bspf_user->bank_account_number."</p>";
                $column .= "<p>口座名義人：".$bspf_user->bank_account_name."</p></div>";
            } else {
                $column = "<div><p>種類：Paypal</p><p>メールアドレス：".$bspf_user->paypal_address."</p></div>";
            }
        }

        return $column;
    }

    function column_public_flag($item) {
        $public_status = "作成中";
        switch($item['public_flag']){
            case "1":
                $public_status = "開設申請中";
                break;
            case "2":
                $public_status = "キャンセル";
                break;
            case "3":
                $public_status = "公開";
                break;
        }
        return $public_status;
    }

    function prepare_items() {
        global $wpdb;

        $this->process_bulk_action();

		$table = BSPlatformUtils::getTableName("salon");

        $query = "SELECT * FROM " . $table;

        //Get the search string (if any)
        $s = filter_input(INPUT_GET, 's');
        if (empty($s)) {
            $s = filter_input(INPUT_POST, 's');
        }

		$filters = array();

		//Add the search parameter to the query
        if (!empty($s)) {
            $s = sanitize_text_field($s);
            $s = trim($s); //Trim the input
            $filters[] = "( name LIKE '%" . strip_tags($s) . "%' )";
        }

        //Build the WHERE clause of the query string
        if (!empty($filters)) {
            $filter_str = '';
            foreach ($filters as $ind => $filter) {
                $filter_str .= $ind === 0 ? $filter : " AND " . $filter;
            }
            $query .= " WHERE " . $filter_str;
        }

        //Build the orderby and order query parameters
        $orderby = filter_input(INPUT_GET, 'orderby');
        $orderby = empty($orderby) ? 'id' : $orderby;
        $order = filter_input(INPUT_GET, 'order');
        $order = empty($order) ? 'DESC' : $order;
        $sortable_columns = $this->get_sortable_columns();
        $orderby = BSPlatformUtils::sanitize_value_by_array($orderby, $sortable_columns);
        $order = BSPlatformUtils::sanitize_value_by_array($order, array('DESC' => '1', 'ASC' => '1'));
        $query .= ' ORDER BY ' . $orderby . ' ' . $order;

        //Execute the query
        $totalitems = $wpdb->query($query); //return the total number of affected rows
        //Pagination setup
        $perpage = 50;
        $paged = filter_input(INPUT_GET, 'paged');
        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }
        $totalpages = ceil($totalitems / $perpage);
        if (!empty($paged) && !empty($perpage)) {
            $offset = ($paged - 1) * $perpage;
            $query .= ' LIMIT ' . (int) $offset . ',' . (int) $perpage;
        }
        $this->set_pagination_args(array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage,
        ));

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $wpdb->get_results($query, ARRAY_A);

        $count_normal = $totalitems;
    }

    function no_items() {
        BSPlatformUtils::getSalonShowLabel() . _e('がありません。', 'bs-platform');
    }

    function process_bulk_action() {
		// global $wpdb;

        // //Detect when a bulk action is being triggered... then perform the action.
        // $platform_meetings = isset($_REQUEST['platform_meetings']) ? $_REQUEST['platform_meetings'] : array();
        // $platform_meetings = array_map('sanitize_text_field', $platform_meetings);

        // $current_action = $this->current_action();
        // if (!empty($current_action)) {
        //     //Bulk operation action. Lets make sure multiple records were selected before going ahead.
        //     if (empty($platform_meetings)) {
        //         echo '<div id="message" class="error"><p>エラーです。一括操作を実行するには、複数のレコードを選択する必要があります。</p></div>';
        //         return;
        //     }
        // } else {
        //     //No bulk operation.
        //     return;
        // }

        // echo '<div id="message" class="updated fade"><p>一括操作が正常に完了しました。</p></div>';
    }

    function process_form_request() {
        if (isset($_REQUEST['id'])) {
            //This is a member profile edit action
            $record_id = sanitize_text_field($_REQUEST['id']);
            if (!is_numeric($record_id)) {
                wp_die('Error! ID must be numeric.');
            }
            return $this->edit(absint($record_id));
        }
    }
	
    function edit($id) {
        $mode = isset($_REQUEST['mode'])? $_REQUEST['mode'] : '';

        // delete image
        if($mode == "delete_image") {
            $salon_data = BSPlatformUtils::getSalonData($id, false);

            @unlink( $salon_data->image_path );

            $regist_data = array(
                'image' => '', 
                'image_path' => '', 
                'update_date' => date("Y-m-d H:i:s"),
            );
            BSPlatformUtils::updateTableData("salon", $regist_data, array('id'=>$id));
        } else if($mode == "delete_sub_image"){ // sub image delete
            $salon_data = BSPlatformUtils::getSalonData($id, false);

            $sub_id = $_REQUEST['sub_id'];

            $sub_images = maybe_unserialize($salon_data->sub_images);
            $sub_images_path = maybe_unserialize($salon_data->sub_images_path);

            if(isset($sub_images_path[$sub_id]) && !empty($sub_images_path[$sub_id])) {
                @unlink( $sub_images_path[$sub_id] );

                $sub_images[$sub_id] = "";
                $sub_images_path[$sub_id] = "";
                
                $regist_data = array(
                    'sub_images' => maybe_serialize($sub_images),
                    'sub_images_path' => maybe_serialize($sub_images_path),
                    'update_date' => date("Y-m-d H:i:s"),
                );
                BSPlatformUtils::updateTableData("salon", $regist_data, array('id'=>$id));
            }
        } else if($mode == "delete_video"){ // video delete
            $salon_data = BSPlatformUtils::getSalonData($id, false);

            $sub_id = $_REQUEST['sub_id'];

            $videos = maybe_unserialize($salon_data->videos);
            $videos_path = maybe_unserialize($salon_data->videos_path);

            if(isset($videos_path[$sub_id]) && !empty($videos_path[$sub_id])) {
                @unlink( $videos_path[$sub_id] );

                $videos[$sub_id] = "";
                $videos_path[$sub_id] = "";
                
                $regist_data = array(
                    'videos' => maybe_serialize($videos),
                    'videos_path' => maybe_serialize($videos_path),
                    'update_date' => date("Y-m-d H:i:s"),
                );
                BSPlatformUtils::updateTableData("salon", $regist_data, array('id'=>$id));
            }
        }
        
        $salon_data = (array)BSPlatformUtils::getSalonData($id, false);
        extract($salon_data, EXTR_SKIP);

        include_once(BS_PLATFORM_PATH . 'views/bs_platform_admin_salon_edit.php');
        return false;
    }

    function edit_salon($id) {
		//Check nonce
		if (!isset($_REQUEST['_wpnonce_edit_salon_admin']) || !wp_verify_nonce($_REQUEST['_wpnonce_edit_salon_admin'], 'edit_salon_admin')) {
			//Nonce check failed.
			wp_die(BSPlatformUtils::_("エラー！nonce検証が失敗しました。"));
		}

		$id = absint($id);
		if(empty($id)){ 
			wp_safe_redirect('admin.php?page=bs_platform_salon_list'); 
		}

        if (isset($_POST["edit_salon"])) {
            $default_salon_fee_percent = BSPlatformMain::get_instance()->get_option_value('salon_fee_percent', '10');

            $old_page_data = BSPlatformUtils::getSalonData($id, false);

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

            $fee_percent = !empty($_POST['salon_fee_percent'])?$_POST['salon_fee_percent']:$default_salon_fee_percent;
            if($fee_percent > 100) {$fee_percent = 100;}

            $regist_data = array(
                'name' => $_POST['salon_name'], 
                'image' => $upload_file_main_image ? $upload_file_main_image['url'] : $old_page_data->image, 
                'image_path' => $upload_file_main_image ? $upload_file_main_image['file'] : $old_page_data->image_path, 
                'description' => $_POST['salon_description'],
                // 'public_flag' => BS_PF_SALON_STATUS_REQUIRE,
                'fee_percent' => $fee_percent,
                'update_date' => date("Y-m-d H:i:s"),
                'mng_nickname' => $_POST['mng_nickname'],
                'mng_introduction' => $_POST['mng_introduction'],
                'mng_message' => $_POST['mng_message'],
                'can_doing_work' => $_POST['can_doing_work'],
                'entrance_merit' => $_POST['entrance_merit'],
                'usage_fee' => $_POST['usage_fee'],
                'keep_in_mind' => $_POST['keep_in_mind'],
                'office_word' => $_POST['office_word'],
                'sub_images' => maybe_serialize($sub_images),
                'sub_images_path' => maybe_serialize($sub_images_path),
                'videos' => maybe_serialize($videos),
                'videos_path' => maybe_serialize($videos_path),
                'enter_payment_flag' => $_POST['enter_payment_flag'],
            );
            BSPlatformUtils::updateTableData("salon", $regist_data, array('id'=>$id));
        }
        
		wp_safe_redirect('admin.php?page=bs_platform_salon_list');
	}

    function delete() {
        if (isset($_REQUEST['id'])) {
            //Check nonce
            if (!isset($_REQUEST['delete_nonce']) || !wp_verify_nonce($_REQUEST['delete_nonce'], 'delete_salon_admin')) {
                //Nonce check failed.
                wp_die(BSPlatformUtils::_("エラー！ 管理側からの削除のnonce検証が失敗しました。"));
            }

            $id = sanitize_text_field($_REQUEST['id']);
            $id = absint($id);
            if (!is_numeric($id)) {
                wp_die('エラー！IDは数値でなければなりません。');
            }

            BSPlatformUtils::deleteTableData("salon", array("id"=>$id));
        }
    }
    
    function set_pulbic_flag($public) {
        if (isset($_REQUEST['id'])) {
            $id = sanitize_text_field($_REQUEST['id']);
            $id = absint($id);

            BSPlatformUtils::updateTableData("salon", array('public_flag'=>$public), array("id"=>$id));

            // send mail
            if($public == BS_PF_SALON_STATUS_PUBLIC){
                BSPlatformUtils::sendMailForPublicPlatform($id);
            }
        }
    }
    
    function show_all_list() {
        ob_start();
        $status = filter_input(INPUT_GET, 'status');
        include_once(BS_PLATFORM_PATH . 'views/bs_platform_admin_salon_list.php');
        $output = ob_get_clean();
        return $output;
    }

    function handle_platform_salon_list_menu() {
        do_action('platform_salon_list_menu_start');

        $action = filter_input(INPUT_GET, 'platform_action');
        $action = empty($action) ? filter_input(INPUT_POST, 'platform_action') : $action;
?>
        <div class="wrap swpm-admin-menu-wrap"><!-- start wrap -->

            <h1><?php echo BSPlatformUtils::getSalonShowLabel() . BSPlatformUtils::_('リスト') ?></h1>
<?php
            //Switch case for the various different actions handled by the core plugin.
            switch ($action) {
                case 'edit':
                    //Process member profile edit
                    $this->process_form_request();
                    break;
                default:
                    //Show the listing page by default.
                    echo $this->show_all_list();
                    break;
		}

		echo '</div>'; //<!-- end of wrap -->
	}

}