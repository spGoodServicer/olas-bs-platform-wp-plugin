<?php
if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class BSPlatformAdminSalonMemberList extends WP_List_Table {

	protected static $_intance = null;
	protected $_error = '';

	public static function get_instance() {
        self::$_intance = empty(self::$_intance) ? new BSPlatformAdminSalonMemberList() : self::$_intance;
        return self::$_intance;
    }

    function __construct() {
        parent::__construct(array(
            'singular' => '会員情報',
            'plural' => '会員情報',
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
           	'id' => _('ID'),
            'user_login' => _('ユーザー名'),
            'display_name' => _('表示名'),
            'user_email' => _('メール'),
            'entrance_salon' => _('加入サロン'),
        );

        return $default_columns;
    }

    function get_sortable_columns() {
        return array(
            'B.id' => array('id', true), //True means already sorted
        );
    }

    function column_default($item, $column_name) {
        $column_content = "";
        switch($column_name) {
            case "user_login":
                $column_content = get_avatar($item["wp_user_id"], 32) . "<strong>" . $item[$column_name] . "</strong>";
                break;
            case "entrance_salon":
                $entrance_salons = BSPlatformUtils::getSalonsByUsers($item["wp_user_id"]);
				foreach($entrance_salons as $salon) {
					$column_content .= '<span><a href="'. home_url() . '?salon_id=' . $salon->id.'" target="_blank">'.$salon->name.'</a></span>&nbsp;&nbsp;';
				}
                break;
            default:
                $column_content = $item[$column_name];
                break;
        }
        return $column_content;
    }

    function column_id($item) {
        $my_bspf_user_id = $_SESSION['BS_PLATFORM_ADMIN_SESSION']['bspf_user_id'];
        $my_salon_id = $_SESSION['BS_PLATFORM_ADMIN_SESSION']['bspf_salon_id'];

        $actions = array();
        $actions['message'] = sprintf('<a href="'.home_url("message").'?fepaction=newmessage&message_top=%s&salon_id=%s" target="_blank">メッセージ作成</a>', $item['display_name'], $my_salon_id);

        return $item['id'] . $this->row_actions($actions);
    }

	// function column_manager_user_id($item) {
    //     $bspf_user = BSPlatformUtils::getUser($item['manager_user_id']);
	// 	$user = get_user_by("id", $bspf_user->wp_user_id);
		
    //     if($user) {
    //         $member_url = admin_url(). "user-edit.php?user_id=" . $user->ID;
    //     }
        
	// 	return $user->display_name . "(". $user->user_email . ")" . "<br/><a href='".$member_url."'>詳細ページ</a>";
	// }

    function prepare_items() {
        global $wpdb;

        $my_bspf_user_id = $_SESSION['BS_PLATFORM_ADMIN_SESSION']['bspf_user_id'];
        $my_salon_id = $_SESSION['BS_PLATFORM_ADMIN_SESSION']['bspf_salon_id'];

		$salon_detail_table = BSPlatformUtils::getTableName("salon_detail");
        $bspf_user_table = BSPlatformUtils::getTableName("user");
        $wp_user_table = $wpdb->prefix . "users";

        $query = "SELECT C.*, B.* FROM " . $salon_detail_table . " AS A inner join " . $bspf_user_table . " AS B ON A.entrance_user_id = B.id ";
        $query .= " inner join " . $wp_user_table ." AS C ON B.wp_user_id = C.ID " ;

        //Get the search string (if any)
        $s = filter_input(INPUT_GET, 's');
        if (empty($s)) {
            $s = filter_input(INPUT_POST, 's');
        }

		$filters = array("A.salon_id = '".$my_salon_id."'");

		//Add the search parameter to the query
        if (!empty($s)) {
            $s = sanitize_text_field($s);
            $s = trim($s); //Trim the input
            $filters[] = "( C.user_nicename LIKE '%" . strip_tags($s) . "%'  OR C.display_name LIKE '%" . strip_tags($s) . "%')";
        }

        $status = filter_input(INPUT_GET, 'status');
        if($status == "favorite") {
            $filters[] = "(favorite_flag = '1')";
        } else {
            $filters[] = "(allow_flag = '1')";
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
        $orderby = empty($orderby) ? 'B.id' : $orderby;
        $order = filter_input(INPUT_GET, 'order');
        $order = empty($order) ? 'DESC' : $order;
        $sortable_columns = $this->get_sortable_columns();
        $orderby = BSPlatformUtils::sanitize_value_by_array($orderby, $sortable_columns, false);
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

    function show_member_list($action) {
        ob_start();
        $status = filter_input(INPUT_GET, 'status');
        include_once(BS_PLATFORM_PATH . 'views/bs_platform_admin_salon_member_list.php');
        $output = ob_get_clean();
        return $output;
    }

    function handle_platform_salon_member_list_menu() {
        do_action('platform_salon_member_list_menu_start');

        $action = filter_input(INPUT_GET, 'platform_action');
        $action = empty($action) ? filter_input(INPUT_POST, 'platform_action') : $action;
?>
        <div class="wrap swpm-admin-menu-wrap"><!-- start wrap -->

            <h1><?php echo BSPlatformUtils::_('会員情報') ?></h1>
<?php
            //Switch case for the various different actions handled by the core plugin.
            switch ($action) {
                default:
                    echo $this->show_member_list($action);
                    break;
		}

		echo '</div>'; //<!-- end of wrap -->
	}

}