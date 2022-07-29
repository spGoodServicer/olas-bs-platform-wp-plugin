<?php
$this->prepare_items();
?>
<form method="get">
	<div>
		<ul class="subsubsub">
			<li class="entrance"><a href="admin.php?page=bs_platform_member&status=entrance" class="<?php if(empty($status) || $status =="entrance") { echo('current'); }?>">加入済み会員</a> |</li>
			<li class="favorite"><a href="admin.php?page=bs_platform_member&status=favorite" class="<?php if($status =="favorite") { echo('current'); }?>">お気に入りの会員</a></li>
		</ul>
		<p class="search-box">
			<input id="search_id-search-input" type="text" name="s" value="<?php echo isset($_REQUEST['s']) ? esc_attr($_REQUEST['s']) : ''; ?>" />
			<input id="search-submit" class="button swpm-admin-search-btn" type="submit" name="" value="<?php echo BSPlatformUtils::_('会員検索') ?>" />
			<input type="hidden" name="page" value="bs_platform_member" />
			<input type="hidden" name="status" value="<?php echo($status); ?>" />
		</p>
	</div>
</form>

<form id="tables-filter" method="get" onSubmit="return confirm('選択した項目について、この一括操作を実行してもよろしいですか？');">
    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
    <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
    <!-- Now we can render the completed list table -->
    <?php $this->display(); ?>
</form>
