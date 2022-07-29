<?php

?>

<div class="wrap" id="salon-edit-page">
	<form action="" method="post" name="bspf-salon-edit" id="bspf-salon-edit" enctype="multipart/form-data" class="validate">
		<input name="action" type="hidden" value="edit_salon" />

		<?php wp_nonce_field( 'edit_salon_admin', '_wpnonce_edit_salon_admin' ) ?>
		<h3><?php echo(BSPlatformUtils::getSalonShowLabel());?>の編集</h3>
		<p>
			<?php echo BSPlatformUtils::_('ID: '); ?>
			<?php echo esc_attr($id); ?>
		</p>
		<table class="form-table">
			<tr class="bspf-admin-edit-saloname">
				<th scope="row"><label for="show_room_name"><?php echo(BSPlatformUtils::getSalonShowLabel());?>名 </label></th>
				<td><input class="regular-text" name="salon_name" type="text" id="salon_name" value="<?php echo esc_attr($name); ?>" /></td>
			</tr>
			
			<tr class="bspf-admin-edit-salon_fee_percent">
				<th scope="row"><label for="show_room_name">報酬率</label></th>
				<td><input name="salon_fee_percent" type="number" id="salon_fee_percent" value="<?php echo esc_attr($fee_percent); ?>" min="0" max="100"/>%</td>
			</tr>

			<tr class="bspf-admin-edit-mng_nickname">
				<th>運営者名</th>
				<td>
					<input type="text" class="regular-text" name="mng_nickname" value="<?php echo($mng_nickname);?>"/>
				</td>
			</tr>
			<tr class="bspf-admin-edit-mng_introduction">
				<th>自己紹介</th>
				<td>
					<textarea rows="4" class="regular-text" name="mng_introduction"><?php echo($mng_introduction);?></textarea>
				</td>
			</tr>
			<tr class="bspf-admin-edit-mng_message">
				<th>運営者からのメッセージ</th>
				<td>
					<textarea rows="4" class="regular-text" name="mng_message"><?php echo($mng_message);?></textarea>
				</td>
			</tr>
			<tr class="bspf-admin-edit-description">
				<th scope="row"><?php echo(BSPlatformUtils::getSalonShowLabel());?>の概要、特徴</th>
				<td>
					<textarea rows="4" class="regular-text" name="salon_description"><?php echo($description);?></textarea>
				</td>
			</tr>
			<tr class="bspf-admin-edit-can_doing_work">
				<th><?php echo(BSPlatformUtils::getSalonShowLabel());?>でできること</th>
				<td>
					<textarea rows="4" class="regular-text" name="can_doing_work"><?php echo($can_doing_work);?></textarea>
				</td>
			</tr>
			<tr class="bspf-admin-edit-entrance_merit">
				<th>入会のメリット</th>
				<td>
					<textarea rows="4" class="regular-text" name="entrance_merit"><?php echo($entrance_merit);?></textarea>
				</td>
			</tr>
			<tr class="bspf-admin-edit-usage_fee">
				<th>ご利用料金</th>
				<td>
					<textarea rows="4" class="regular-text" name="usage_fee"><?php echo($usage_fee);?></textarea>
				</td>
			</tr>
			<tr class="bspf-admin-edit-keep_in_mind">
				<th>ご留意いただきたいこと</th>
				<td>
					<textarea rows="4" class="regular-text" name="keep_in_mind"><?php echo($keep_in_mind);?></textarea>
				</td>
			</tr>
			<tr class="bspf-admin-edit-office_word">
				<th>事務局から一言</th>
				<td>
					<textarea rows="4" class="regular-text" name="office_word"><?php echo($office_word);?></textarea>
				</td>
			</tr>
			
			<tr class="bspf-admin-edit-image">
				<th scope="row">画像</th>
				<td>
					<input type="file" name="salon_image" /><br/>
					<?php if(!empty($image)) { ?>
						<a href="<?php echo("admin.php?page=bs_platform_salon_list&platform_action=edit&mode=delete_image&id=".$id);?>">削除</a><br/>
						<img src="<?php echo($image);?>" width="150px">
					<?php } ?>
				</td>
			</tr>

			<?php 
			for($i=0;$i<3;$i++) { 
				$arr_sub_images	= maybe_unserialize($sub_images);
				$arr_sub_images_path = maybe_unserialize($sub_images_path);
			?>
			<tr class="bspf-admin-edit-image">
				<th>サブ画像<?php echo($i+1);?></th>
				<td>
					<input type="file" name="sub_images[]" /><br/>
					<?php if(!empty($arr_sub_images[$i])) { ?>
						<a href="admin.php?page=bs_platform_salon_list&platform_action=edit&mode=delete_sub_image&sub_id=<?php echo($i);?>&id=<?php echo($id);?>">削除</a><br/>
						<img src="<?php echo($arr_sub_images[$i]);?>" width="150px">
					<?php } ?>
				</td>
			</tr>
			<?php }?>

			<?php 
			for($i=0;$i<3;$i++) { 
				$arr_videos	= maybe_unserialize($videos);
				$arr_videos_path = maybe_unserialize($videos_path);
			?>
			<tr class="bspf-admin-edit-video">
				<th>動画<?php echo($i+1);?></th>
				<td>
					<input type="file" name="videos[]" /><br/>
					<?php if(!empty($arr_videos[$i])) { ?>
						<a href="admin.php?page=bs_platform_salon_list&platform_action=edit&mode=delete_video&sub_id=<?php echo($i);?>&id=<?php echo($id);?>">削除</a><br/>
						<video src="<?php echo($arr_videos[$i]);?>" width="200px" controls></video>
					<?php } ?>
				</td>
			</tr>
			<?php }?>

			<tr>
				<th>加入設定</th>
				<td>
					<select name="enter_payment_flag">
						<?php 
						global $entrance_payment_setting_list;
						foreach($entrance_payment_setting_list as $key=>$val) {
							$selected = "";
							if($enter_payment_flag == $key) {$selected = "selected";}
							echo('<option value="'.$key.'" '.$selected.'>'.$val.'</option>');
						}
						?>
					</select>
				</td>
			</tr>
		</table>
		<?php submit_button( BSPlatformUtils::getSalonShowLabel() . BSPlatformUtils::_('の編集'), 'primary', 'edit_salon', true, array( 'id' => 'edit_salon' ) ); ?>
	</form>
</div>

<?php $this->set_error(''); ?>
