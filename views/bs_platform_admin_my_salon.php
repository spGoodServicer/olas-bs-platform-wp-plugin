<?php
$public_status = "作成中";
switch($page_data->public_flag){
	case BS_PF_SALON_STATUS_REQUIRE:
		$public_status = "開設申請中";
		break;
	case BS_PF_SALON_STATUS_CANCEL:
		$public_status = "キャンセル";
		break;
	case BS_PF_SALON_STATUS_PUBLIC:
		$public_status = "公開";
		break;
	// case BS_PF_SALON_STATUS_PRIVATE:
	// 	$public_status = "公開";
	// 	break;
}

?>
<div class="salon-content">
	<form name="frmSalon" id="frmSalon" method="post" enctype="multipart/form-data">
		<input type="hidden" name="bspf_admin_action" value="my_salon" />
		<input type="hidden" name="mode" id="mode" value="" />
		<input type="hidden" name="my_salon_id" value="<?php echo($page_data->id);?>" />

		<div id="bspf_tab-panel">
			<div class="bspf_theme_option_field cf">
				<h3 class="bspf_theme_option_headline"><?php BSPlatformUtils::e( 'Myサロン' ); ?></h3>

				<?php foreach($page_msg as $message) {?>
					<p class="message"><?php echo($message);?></p>
				<?php }?>
				
				<div class="form-group ">
					<label for="salon_kind" class="caption"><?php echo(BSPlatformUtils::getSalonShowLabel());?>種類</label>
					<select name="salon_kind">
						<option value="0">選択なし</option>
						<?php foreach($salon_kind_data as $key=>$row){
							$selected = "";
							if($page_data->salon_kind_id == $row->id) {$selected = "selected";}
							echo('<option value="'.$row->id.'" '.$selected.'>'.$row->name.'</option>');
						}?>
					</select>
				</div>

				<div class="form-group ">
					<label for="salon_name" class="caption"><?php echo(BSPlatformUtils::getSalonShowLabel());?>名</label>
					<input type="text" name="salon_name" value="<?php echo($page_data->name);?>"/>
				</div>

				<div class="form-group ">
					<label for="mng_nickname" class="caption">運営者名</label>
					<input type="text" name="mng_nickname" value="<?php echo($page_data->mng_nickname);?>"/>
				</div>

				<div class="form-group ">
					<label for="mng_introduction" class="caption">自己紹介</label>
					<textarea rows="4" class="regular-text" name="mng_introduction"><?php echo($page_data->mng_introduction);?></textarea>
				</div>

				<div class="form-group ">
					<label for="mng_message" class="caption">運営者からのメッセージ</label>
					<textarea rows="4" class="regular-text" name="mng_message"><?php echo($page_data->mng_message);?></textarea>
				</div>

				<div class="form-group ">
					<label for="salon_description" class="caption"><?php echo(BSPlatformUtils::getSalonShowLabel());?>の概要、特徴</label>
					<textarea rows="4" class="regular-text" name="salon_description"><?php echo($page_data->description);?></textarea>
				</div>

				<div class="form-group ">
					<label for="can_doing_work" class="caption"><?php echo(BSPlatformUtils::getSalonShowLabel());?>でできること</label>
					<textarea rows="4" class="regular-text" name="can_doing_work"><?php echo($page_data->can_doing_work);?></textarea>
				</div>

				<div class="form-group ">
					<label for="entrance_merit" class="caption">入会のメリット</label>
					<textarea rows="4" class="regular-text" name="entrance_merit"><?php echo($page_data->entrance_merit);?></textarea>
				</div>

				<div class="form-group ">
					<label for="usage_fee" class="caption">ご利用料金</label>
					<textarea rows="4" class="regular-text" name="usage_fee"><?php echo($page_data->usage_fee);?></textarea>
				</div>

				<div class="form-group ">
					<label for="keep_in_mind" class="caption">ご留意いただきたいこと</label>
					<textarea rows="4" class="regular-text" name="keep_in_mind"><?php echo($page_data->keep_in_mind);?></textarea>
				</div>

				<div class="form-group ">
					<label for="office_word" class="caption">事務局から一言</label>
					<textarea rows="4" class="regular-text" name="office_word"><?php echo($page_data->office_word);?></textarea>
				</div>

				<div class="form-group ">
					<label class="caption">画像</label>
					<input type="file" name="salon_image" />
					<?php if(!empty($page_data->image)) { ?>
						<a href="<?php echo("admin.php?page=bs_platform_my_salon&bspf_admin_action=my_salon&mode=delete_image&my_salon_id=" . $page_data->id);?>">削除</a><br/>
						<label class="caption"></label><img src="<?php echo($page_data->image);?>" width="20%">
					<?php } ?>
				</div>

				<?php 
				for($i=0;$i<3;$i++) { 
					$sub_images	= maybe_unserialize($page_data->sub_images);
					$sub_images_path = maybe_unserialize($page_data->sub_images_path);
				?>
				<div class="form-group ">
					<label class="caption">サブ画像<?php echo($i+1);?></label>
					<input type="file" name="sub_images[]" />
					<?php if(!empty($sub_images[$i])) { ?>
						<a href="admin.php?page=bs_platform_my_salon&bspf_admin_action=my_salon&mode=delete_sub_image&sub_id=<?php echo($i);?>&my_salon_id=<?php echo($page_data->id);?>">削除</a><br/>
						<label class="caption"></label><img src="<?php echo($sub_images[$i]);?>" width="20%">
					<?php } ?>
				</div>
				<?php }?>

				<?php 
				for($i=0;$i<3;$i++) { 
					$videos	= maybe_unserialize($page_data->videos);
					$videos_path = maybe_unserialize($page_data->videos_path);
				?>
				<div class="form-group ">
					<label class="caption">動画<?php echo($i+1);?></label>
					<input type="file" name="videos[]" />
					<?php if(!empty($videos[$i])) { ?>
						<a href="admin.php?page=bs_platform_my_salon&bspf_admin_action=my_salon&mode=delete_video&sub_id=<?php echo($i);?>&my_salon_id=<?php echo($page_data->id);?>">削除</a><br/>
						<label class="caption"></label><video src="<?php echo($videos[$i]);?>" width="20%" controls></video>
					<?php } ?>
				</div>
				<?php }?>

				<div class="form-group ">
					<label class="caption">加入設定</label>
					<select name="enter_payment_flag">
						<?php 
						global $entrance_payment_setting_list;
						foreach($entrance_payment_setting_list as $key=>$val) {
							$selected = "";
							if($page_data->enter_payment_flag == $key) {$selected = "selected";}
							echo('<option value="'.$key.'" '.$selected.'>'.$val.'</option>');
						}
						?>
					</select>
				</div>

				<div class="form-group ">
					<label for="bank_name" class="caption">公開状態</label>
					<?php echo($public_status); ?>
				</div>

				<div class="form-group " style="display:inline-flex">
					<input type="button" name="btn_edit" id="bspf_btn_edit" class="bs_platform_button-ml" value="保存" >
					<?php if(!empty($page_data->id)) { ?>
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" name="btn_delete" id="bspf_btn_delete" class="bs_platform_button-ml" value="削除" >
						
						<?php if($page_data->public_flag != BS_PF_SALON_STATUS_PUBLIC && $page_data->public_flag != BS_PF_SALON_STATUS_REQUIRE) { ?>
							&nbsp;&nbsp;&nbsp;&nbsp;
							<input type="button" name="btn_request" id="bspf_btn_request" class="bs_platform_button-ml" value="開設申請" >
						<?php } ?>
					<?php } ?>
				</div>
			</div>
		</div>
	</form>
</div>
