<?php
if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class BSPlatformAdminSalonKindList extends WP_List_Table {

	protected static $_intance = null;
	protected $_error = '';

	public static function get_instance() {
        self::$_intance = empty(self::$_intance) ? new BSPlatformAdminSalonKindList() : self::$_intance;
        return self::$_intance;
    }

    function __construct() {
        parent::__construct(array(
            'singular' => 'サロン種類',
            'plural' => 'サロン種類',
            'ajax' => false
        ));
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
            'name' => BSPlatformUtils::getSalonShowLabel() ._('種類'),
            'use_flag' => '利用状態'
        );

        return $default_columns;
    }

    function get_sortable_columns() {
        return array(
            'id' => array('id', true), //True means already sorted
            'use_flag' => array('use_flag', false),
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
		
        $actions['edit'] = sprintf('<a href="admin.php?page=bs_platform_salon_kind&platform_action=edit&id=%s">編集</a>', $item['id']);
        if(empty($item['use_flag'])){
            $actions['use'] = sprintf('<a href="admin.php?page=bs_platform_salon_kind&platform_action=use&id=%s">公開</a>', $item['id']);
        } else {
            $actions['unuse'] = sprintf('<a href="admin.php?page=bs_platform_salon_kind&platform_action=unuse&id=%s">非公開</a>', $item['id']);
        }
        $actions['delete'] = sprintf('<a href="admin.php?page=bs_platform_salon_kind&platform_action=delete&id=%s&delete_nonce=%s" onclick="return confirm(\''.BSPlatformUtils::_('削除してもよろしいですか？').'\')">'._('削除').'</a>', $item['id'], wp_create_nonce('delete_salon_kind_admin'));
        
        return $item['id'] . $this->row_actions($actions);
    }

    function column_use_flag($item) {
        return empty($item['use_flag']) ? "非公開" : "公開";
    }

    function prepare_items() {
        global $wpdb;

		$table = BSPlatformUtils::getTableName("kind");

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
        _e('データがありません。', 'bs-platform');
    }

    function process_form_request() {
        if (isset($_REQUEST['id'])) {
            //This is a member profile edit action
            $record_id = sanitize_text_field($_REQUEST['id']);
            if (!is_numeric($record_id)) {
                wp_die('Error! ID must be numeric.');
            }
            return $this->edit(absint($record_id));
        } else {
            return $this->add();
        }
    }
	
    function add() {
        include_once(BS_PLATFORM_PATH . 'views/bs_platform_admin_salon_kind_edit.php');
        return false;
    }

    function edit($id) {
        $mode = isset($_REQUEST['mode'])? $_REQUEST['mode'] : '';

        $query = "SELECT * FROM " . BSPlatformUtils::getTableName("kind") . " WHERE id = '" . $id . "' ";
        $kind_data = BSPlatformUtils::getRowData($query, ARRAY_A);
        extract($kind_data, EXTR_SKIP);

        include_once(BS_PLATFORM_PATH . 'views/bs_platform_admin_salon_kind_edit.php');
        return false;
    }

    function edit_salon_kind() {
		//Check nonce
		if (!isset($_REQUEST['_wpnonce_edit_salon_kind_admin']) || !wp_verify_nonce($_REQUEST['_wpnonce_edit_salon_kind_admin'], 'edit_kind_admin')) {
			//Nonce check failed.
			wp_die(BSPlatformUtils::_("エラー！nonce検証が失敗しました。"));
		}

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (isset($_POST["edit_salon_kind"])) {
            $regist_data = array(
                'name' => $_POST['kind_name'], 
                'update_date' => date("Y-m-d H:i:s"),
            );
            if(empty($id)){ 
                $regist_data['use_flag'] = 1;
                $regist_data['create_date'] = date("Y-m-d H:i:s");
                BSPlatformUtils::insertTableData("kind", $regist_data);
            } else {
                BSPlatformUtils::updateTableData("kind", $regist_data, array('id'=>$id));
            }
        }
        
		wp_safe_redirect('admin.php?page=bs_platform_salon_kind');
	}

    function delete() {
        if (isset($_REQUEST['id'])) {
            //Check nonce
            if (!isset($_REQUEST['delete_nonce']) || !wp_verify_nonce($_REQUEST['delete_nonce'], 'delete_salon_kind_admin')) {
                //Nonce check failed.
                wp_die(BSPlatformUtils::_("エラー！ 管理側からの削除のnonce検証が失敗しました。"));
            }

            $id = sanitize_text_field($_REQUEST['id']);
            $id = absint($id);
            if (!is_numeric($id)) {
                wp_die('エラー！IDは数値でなければなりません。');
            }

            BSPlatformUtils::deleteTableData("kind", array("id"=>$id));
        }
    }
    
    function set_pulbic_flag($public) {
        if (isset($_REQUEST['id'])) {
            $id = sanitize_text_field($_REQUEST['id']);
            $id = absint($id);

            BSPlatformUtils::updateTableData("kind", array('use_flag'=>$public), array("id"=>$id));
        }
    }
    
    function show_all_list() {
        ob_start();
        include_once(BS_PLATFORM_PATH . 'views/bs_platform_admin_salon_kind_list.php');
        $output = ob_get_clean();
        return $output;
    }

    function handle_platform_salon_kind_list_menu() {
        do_action('platform_salon_kind_list_menu_start');

        $action = filter_input(INPUT_GET, 'platform_action');
        $action = empty($action) ? filter_input(INPUT_POST, 'platform_action') : $action;
?>
        <div class="wrap swpm-admin-menu-wrap"><!-- start wrap -->

            <h1><?php echo BSPlatformUtils::getSalonShowLabel() . '種類' ?></h1>
<?php
            //Switch case for the various different actions handled by the core plugin.
            switch ($action) {
                case 'add':
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