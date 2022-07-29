<?php

if (isset($_REQUEST['platform_action'])) {
	if($_REQUEST['platform_action'] == 'delete'){
		//Delete this record
		$this->delete();
		$success_msg = '<div id="message" class="updated"><p>';
		$success_msg .= BSLiveChatUtils::_('削除されました。');
		$success_msg .= '</p></div>';
		echo $success_msg;
	} else if($_REQUEST['platform_action'] == 'use'){
		$this->set_pulbic_flag(1);
	} else if($_REQUEST['platform_action'] == 'unuse'){
		$this->set_pulbic_flag(0);
	}
}

$this->prepare_items();
?>
<form method="get">
	<div>
		<a href="admin.php?page=bs_platform_salon_kind&platform_action=add" class="add-new-h2">新規作成</a>
		<p class="search-box">
			<input id="search_id-search-input" type="text" name="s" value="<?php echo isset($_REQUEST['s']) ? esc_attr($_REQUEST['s']) : ''; ?>" />
			<input id="search-submit" class="button swpm-admin-search-btn" type="submit" name="" value="<?php echo BSPlatformUtils::_('検索') ?>" />
			<input type="hidden" name="page" value="bs_platform_salon_list" />
		</p>
	</div>
</form>

<form id="tables-filter" method="get" onSubmit="return confirm('選択した項目について、この一括操作を実行してもよろしいですか？');">
    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
    <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
    <!-- Now we can render the completed list table -->
    <?php $this->display(); ?>
</form>
