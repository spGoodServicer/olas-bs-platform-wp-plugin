<?php
if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class BSPlatformAdminSalonPaymentList extends WP_List_Table {

	protected static $_intance = null;
	protected $_error = '';
    protected $_swpm_flag = false;

	public static function get_instance() {
        self::$_intance = empty(self::$_intance) ? new BSPlatformAdminSalonPaymentList() : self::$_intance;
        return self::$_intance;
    }

    function __construct() {
        parent::__construct(array(
            'singular' => '支払い履歴',
            'plural' => '支払い履歴',
            'ajax' => false
        ));

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

    function get_columns() {
		global $dp_options;

		$default_columns = array(
            'cb' => '<input type="checkbox" />', 
           	'id' => _('ID'),
            'salon_name' => BSPlatformUtils::getSalonShowLabel() . _('名'),
            'receive_user_id' => '運営者', 
            'history_date' => '入金期間', 
            'in_money' => '入金額',
            'out_money' => '送金額',
            'fee_percent' => '報酬率(%)',
            'process_status' => '処理状態'
        );

        return $default_columns;
    }

    function get_sortable_columns() {
        return array(
            'id' => array('id', true), //True means already sorted
            'process_status' => array('process_status', false),
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
		
        if(empty($item['process_status'])){
            $actions['send'] = sprintf('<a href="admin.php?page=bs_platform_payment_list&platform_action=send&id=%s">支払済</a>', $item['id']);
        } else {
            $actions['unsend'] = sprintf('<a href="admin.php?page=bs_platform_payment_list&platform_action=unsend&id=%s">未払</a>', $item['id']);
        }
        $actions['delete'] = sprintf('<a href="admin.php?page=bs_platform_payment_list&platform_action=delete&id=%s" onclick="return confirm(\''.BSPlatformUtils::_('このレコードを削除してもよろしいですか？').'\')">削除</a>', $item['id']);

        return $item['id'] . $this->row_actions($actions);
    }

    function column_salon_name($item) {
        $salon = BSPlatformUtils::getSalonData($item['salon_id'], false);
        return $salon->name;
    }

    function column_history_date($item) {
        return date("Y-m-d", strtotime($item['history_start'])) . "~" . date("Y-m-d", strtotime($item['history_end']));
    }

	function column_receive_user_id($item) {
        $bspf_user = BSPlatformUtils::getUser($item['receive_user_id']);
		$user = get_user_by("id", $bspf_user->wp_user_id);

		return $user->display_name . "(". $user->user_email . ")";
	}

    function column_process_status($item) {
        return empty($item['process_status']) ? "未払" : "支払済";
    }

    function prepare_items() {
        global $wpdb;

        $this->process_bulk_action();

		$table = BSPlatformUtils::getTableName("payment_history");

        $query = "SELECT * FROM " . $table;

		$filters = array();
        $search_salon = trim(filter_input(INPUT_GET, 'search_salon'));
        if (!empty($search_salon)) {
            $filters[] = "( salon_id = '" . strip_tags($search_salon) . "' )";
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
        _e('支払い履歴がありません。', 'bs-platform');
    }

    function process_bulk_action() {

    }

    function set_process_status($status) {
        if (isset($_REQUEST['id'])) {
            $id = sanitize_text_field($_REQUEST['id']);
            $id = absint($id);

            BSPlatformUtils::updateTableData("payment_history", array('process_status'=>$status), array("id"=>$id));
        }
    }
    
    function delete_payment($record_id) {
        BSPlatformUtils::deleteTableData("payment_history", array('id'=>$record_id));
    }

    function show_all_list() {
        ob_start();
        $status = filter_input(INPUT_GET, 'status');
        include_once(BS_PLATFORM_PATH . 'views/bs_platform_admin_salon_payment_list.php');
        $output = ob_get_clean();
        return $output;
    }

    function process_form_request() {
        include_once(BS_PLATFORM_PATH . 'views/bs_platform_admin_salon_payment.php');
    }

    public function add_payment_history() {
        //Check nonce
		if (!isset($_REQUEST['_wpnonce_add_payment_admin']) || !wp_verify_nonce($_REQUEST['_wpnonce_add_payment_admin'], 'add_payment_history')) {
			//Nonce check failed.
			wp_die(BSPlatformUtils::_("エラー！nonce検証が失敗しました。"));
		}

        if (isset($_POST["salon_id"])) {
            $salon = BSPlatformUtils::getSalonData($_POST["salon_id"], false);
            $bspf_admin_user = BSPlatformUtils::getBspfUserByWpUserId(get_current_user_id());

            $regist_data = array(
                'history_start' => $_POST['history_start'], 
                'history_end' => $_POST['history_end'], 
                'create_date' => date("Y-m-d H:i:s"),
                'update_date' => date("Y-m-d H:i:s"),
                'in_money' => $_POST['in_money'], 
                'out_money' => round($_POST['in_money'] * $salon->fee_percent / 100),
                'send_user_id' => $bspf_admin_user->id,
                'receive_user_id' => $salon->manager_user_id,
                'fee_percent' => $salon->fee_percent,
                'process_status' => 1,
                'salon_id' => $_POST["salon_id"],
            );
            BSPlatformUtils::insertTableData("payment_history", $regist_data);
        }

        wp_safe_redirect('admin.php?page=bs_platform_payment_list');
    }

    function get_salon_payment($salon_id) {
        global $wpdb;

        $result = array();

        // get final paymented date(start date)
        $history_start = "2021-01-01";

        $query = "SELECT MAX(history_end) as last_history_date FROM " . BSPlatformUtils::getTableName('payment_history') . " WHERE salon_id = '" . $salon_id . "'";
        $temp_data = BSPlatformUtils::getRowData($query);
        if(!empty($temp_data->last_history_date)){
            $history_start = date("Y-m-d", strtotime($temp_data->last_history_date));
        }

        // get create end date
        $history_end = date("Y-m-d", strtotime("-1 day"));

        // get sum money during payment date
        $sum_money = 0;
        if($this->_swpm_flag){
            $query = "SELECT MAX(payment_amount) as sum_money FROM " . $wpdb->prefix . "swpm_payments_tbl WHERE salon_id = '" . $salon_id . "' AND ( txn_date BETWEEN  '" . $history_start . "' AND '". $history_end ."' ) ";
            $temp_data = BSPlatformUtils::getRowData($query);
            $sum_money = empty($temp_data->sum_money) ? 0 : $temp_data->sum_money;
        }

        // get salon data
        $salon = BSPlatformUtils::getSalonData($salon_id, false);

        // get salon manager
        $salon_manager = BSPlatformUtils::getUser($salon->manager_user_id);

        $result = array(
            "history_start" => $history_start,
            "history_end" => $history_end,
            "in_money" => $sum_money,
            "salon" => $salon,
            'salon_manager' => $salon_manager,
        );

        return $result;
    }

    function handle_platform_salon_payment_list_menu() {
        do_action('platform_salon_payment_list_menu_start');

        $action = filter_input(INPUT_GET, 'platform_action');
        $action = empty($action) ? filter_input(INPUT_POST, 'platform_action') : $action;
?>
        <div class="wrap swpm-admin-menu-wrap"><!-- start wrap -->

            <h1><?php echo BSPlatformUtils::_('支払い履歴') ?></h1>
<?php
            //Switch case for the various different actions handled by the core plugin.
            switch ($action) {
                case 'create':
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