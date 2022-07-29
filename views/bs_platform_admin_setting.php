<?php
$bs_platform_main_obj = BSPlatformMain::get_instance();
?>
<?php // BS-Platform Plugin機能設定 ?>
<form id="frm_bs_platform_setting" class="bs_platform_setting_form" method="post">

<div id="bspf_tab-panel">
	<div class="bspf_theme_option_field cf">
		<h3 class="bspf_theme_option_headline"><?php BSPlatformUtils::e( 'プラットフォーム設定' ); ?></h3>
		<p><strong>運営者によるZOOM開催</strong><br/>プラットフォームで運営者がZOOMを開催できるようにする場合、管理者によるZOOMプロアカウントのAPIキー発行・入力が必要です。</br>※運営者は設定の必要はありません。管理者のみです。<br/><a href="admin.php?page=bs_zoom_setting">こちら</a>の「Zoom API設定」に入力をお願いします。<br/><br/></p>
		<div class="form-group display-flex ">
			<label for="salon_fee_percent" class="caption">報酬率(%)</label>
			<div>
				<span>設定した報酬率の分母に当たる部分は親サロンの料金です</span>
				<input type="number" id="salon_fee_percent" name="salon_fee_percent" value="<?php echo($bs_platform_main_obj->get_option_value("salon_fee_percent", '10'));?>" min="0" max="100" /><br/>
				<span>管理者（あなた）は、会費からこの割合を運営者に支払う必要があります</span>
			</div>
		</div>

		<div class="form-group display-flex ">
			<label for="salon_fee_percent" class="caption">サロン名称</label>
			<div>
				<span>「サロン」を入力した名称に変更します。</span>
				<input type="text" id="salon_show_label" name="salon_show_label" value="<?php echo($bs_platform_main_obj->get_option_value("salon_show_label", 'サロン'));?>" />
			</div>
		</div>

		<?php
		if($this->swpm_flag) {
			$swpm_levels = SwpmMembershipLevelUtils::get_all_membership_levels_in_array();

			$add_salon_member_level = maybe_unserialize($bs_platform_main_obj->get_option_value("add_salon_member_level", ''));
			if(empty($add_salon_member_level)){ $add_salon_member_level = array(); }

			$entrance_salon_member_level = maybe_unserialize($bs_platform_main_obj->get_option_value("entrance_salon_member_level", ''));
			if(empty($entrance_salon_member_level)){ $entrance_salon_member_level = array(); }
		?>
		<div class="form-group display-flex " >
			<label class="caption">サロンを開設できる会員ランク</label>
			<div>
				<?php foreach ( $swpm_levels as $id => $swpm_level ) { ?>
					<label ><input type="checkbox" name="add_salon_member_level[]" value="<?php echo($id);?>" <?php if(@in_array($id, $add_salon_member_level)) { echo('checked="checked"'); } ?>　/><?php echo $swpm_level; ?></label><br/>
				<?php } ?>
				<p><strong>開設申請できない場合は、このチェックを必ず確認してください。</strong></p>
			</div>
		</div>

		<div class="form-group display-flex " >
			<label class="caption">サロンに参加できる会員ランク</label>
			<div>
				<?php foreach ( $swpm_levels as $id => $swpm_level ) { ?>
					<label ><input type="checkbox" name="entrance_salon_member_level[]" value="<?php echo($id);?>" <?php if(@in_array($id, $entrance_salon_member_level)) { echo('checked="checked"'); } ?>　/><?php echo $swpm_level; ?></label><br/>
				<?php } ?>
				<p><strong>開設申請できない場合は、このチェックを必ず確認してください。</strong></p>
			</div>
		</div>
		<?php } ?>

		<div class="form-group display-flex " >
			<label class="caption">サロン加入設定</label>
			<div>
				<label><input type="checkbox" name="entrance_salon_free_flag" value="1" <?php checked( $bs_platform_main_obj->get_option_value("entrance_salon_free_flag", ''), '1' ) ?> />無料で加入する</label>
				<p>チェックすると、有料でサロンに加入できる様になります</p>
			</div>
		</div>

		<div class="form-group display-flex " >
			<label class="caption">サロン開設時、メール設定</label>
			<div>
				<label ><input type="checkbox" name="send_create_salon_mail_flag" value="1" <?php checked( $bs_platform_main_obj->get_option_value("send_create_salon_mail_flag", ''), '1' ) ?> />サロン開設時、管理者にメールを送信する</label><br/>
				<span>管理者メールアドレス：<?php echo(get_bloginfo('admin_email'));?></span><br/>
				<p>メールの件名</p>
				<input type="text" name="create_salon_mail_subject" value="<?php echo($bs_platform_main_obj->get_option_value("create_salon_mail_subject", ''));?>" />
				<p>メールの本文</p>
				<textarea name="create_salon_mail_body" rows="4" style="width:100%"><?php echo($bs_platform_main_obj->get_option_value("create_salon_mail_body", ''));?></textarea>
			</div>
		</div>

		<div class="form-group display-flex " >
			<label class="caption">サロン開設承認時、メール設定</label>
			<div>
				<label ><input type="checkbox" name="send_public_salon_mail_flag" value="1" <?php checked( $bs_platform_main_obj->get_option_value("send_public_salon_mail_flag", ''), '1' ) ?> />サロン開設承認時、運営者にメールを送信する</label><br/>
				<p>メールの件名</p>
				<input type="text" name="public_salon_mail_subject" value="<?php echo($bs_platform_main_obj->get_option_value("public_salon_mail_subject", ''));?>" />
				<p>メールの本文</p>
				<textarea name="public_salon_mail_body" rows="4" style="width:100%"><?php echo($bs_platform_main_obj->get_option_value("public_salon_mail_body", ''));?></textarea>
			</div>
		</div>

		<button type="submit" class="bs_platform_button-ml ajax_button"><?php BSPlatformUtils::e( '設定を保存' ); ?></button>
	</div>
</div>

</form>