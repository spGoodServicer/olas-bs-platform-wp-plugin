<?php

if (isset($_REQUEST['platform_action'])) {
	if($_REQUEST['platform_action'] == 'send'){
		$this->set_process_status(1);
	} else if($_REQUEST['platform_action'] == 'unsend'){
		$this->set_process_status(0);
	} else if($_REQUEST['platform_action'] == 'delete'){
		$record_id = sanitize_text_field($_REQUEST['id']);
        $record_id = absint($record_id);

		$this->delete_payment($record_id);
		
		$success_msg = '<div id="message" class="updated"><p><strong>';
        $success_msg .= '削除しました。';
        $success_msg .= '</strong></p></div>';
        echo $success_msg;
	}
}
$salon_list = BSPlatformUtils::getTableData("salon");

$this->prepare_items();
?>
<form method="get">
	<div>
		<a href="admin.php?page=bs_platform_payment_list&platform_action=create" class="add-new-h2">支払い作成</a>
		<p class="search-box">
			<select name="search_salon">
				<option value="">全て</option>
				<?php 
				foreach($salon_list as $row) {
					echo ("<option value='".$row->id."' ".selected($row->id, $_REQUEST['search_salon'], false).">".$row->name."</option>");
				}
				?>
			</select>
			<input id="search-submit" class="button swpm-admin-search-btn" type="submit" name="" value="<?php echo BSPlatformUtils::_('検索') ?>" />
			<input type="hidden" name="page" value="bs_platform_payment_list" />
		</p>
	</div>
</form>

<form id="tables-filter" method="get" onSubmit="return confirm('選択した項目について、この一括操作を実行してもよろしいですか？');">
    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
    <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
    <!-- Now we can render the completed list table -->
    <?php $this->display(); ?>
</form>