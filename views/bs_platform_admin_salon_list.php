<?php

if (isset($_REQUEST['platform_action'])) {
	if($_REQUEST['platform_action'] == 'delete'){
		//Delete this record
		$this->delete();
		$success_msg = '<div id="message" class="updated"><p>';
		$success_msg .= BSLiveChatUtils::_('削除されました。');
		$success_msg .= '</p></div>';
		echo $success_msg;
	} else if($_REQUEST['platform_action'] == 'public') {
		$this->set_pulbic_flag(BS_PF_SALON_STATUS_PUBLIC);
	} else if($_REQUEST['platform_action'] == 'cancel') {
		$this->set_pulbic_flag(BS_PF_SALON_STATUS_CANCEL);
	}
}

$this->prepare_items();
?>
<form method="get">
	<div>
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

<!-- 
<div id="user_bank_info">
	<div class="bspf_theme_option_field cf">
		<div class="form-group ">
			<label for="bank_name">種類</label>
			<span id="bank_type">銀行</span>
		</div>
		<div class="form-group ">
			<label for="bank_name">金融機関名</label>
			<span id="bank_name"></span>
		</div>
	</div>
</div>
-->