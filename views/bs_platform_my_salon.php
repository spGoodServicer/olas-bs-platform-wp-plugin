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
}

?>
<div class="salon-content">
	<h2 class="text-center">Myサロン</h2>

	<?php foreach($page_msg as $message) {?>
		<p class="message"><?php echo($message);?></p>
	<?php }?>
	
	<form name="frmSalon" id="frmSalon" method="post" enctype="multipart/form-data">
		<input type="hidden" name="bspf_action" value="my_salon" />
		<input type="hidden" name="mode" id="mode" value="" />
		<input type="hidden" name="my_salon_id" value="<?php echo($page_data->id);?>" />
		<table>
			<tr>
				<th><?php echo(BSPlatformUtils::getSalonShowLabel());?>種類</th>
				<td>
					<select name="salon_kind">
						<option value="0">選択なし</option>
						<?php foreach($salon_kind_data as $key=>$row){
							$selected = "";
							if($page_data->salon_kind_id == $row->id) {$selected = "selected";}
							echo('<option value="'.$row->id.'" '.$selected.'>'.$row->name.'</option>');
						}?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php echo(BSPlatformUtils::getSalonShowLabel());?>名</th>
				<td>
					<input type="text" name="salon_name" value="<?php echo($page_data->name);?>"/>
				</td>
			</tr>
			<tr>
				<th>運営者名</th>
				<td>
					<input type="text" name="mng_nickname" value="<?php echo($page_data->mng_nickname);?>"/>
				</td>
			</tr>
			<tr>
				<th>自己紹介</th>
				<td>
					<textarea rows="4" name="mng_introduction"><?php echo($page_data->mng_introduction);?></textarea>
				</td>
			</tr>
			<tr>
				<th>運営者からのメッセージ</th>
				<td>
					<textarea rows="4" name="mng_message"><?php echo($page_data->mng_message);?></textarea>
				</td>
			</tr>
			<tr>
				<th><?php echo(BSPlatformUtils::getSalonShowLabel());?>の概要、特徴</th>
				<td>
					<textarea rows="4" name="salon_description"><?php echo($page_data->description);?></textarea>
				</td>
			</tr>
			<tr>
				<th><?php echo(BSPlatformUtils::getSalonShowLabel());?>でできること</th>
				<td>
					<textarea rows="4" name="can_doing_work"><?php echo($page_data->can_doing_work);?></textarea>
				</td>
			</tr>
			<tr>
				<th>入会のメリット</th>
				<td>
					<textarea rows="4" name="entrance_merit"><?php echo($page_data->entrance_merit);?></textarea>
				</td>
			</tr>
			<tr>
				<th>ご利用料金</th>
				<td>
					<textarea rows="4" name="usage_fee"><?php echo($page_data->usage_fee);?></textarea>
				</td>
			</tr>
			<tr>
				<th>ご留意いただきたいこと</th>
				<td>
					<textarea rows="4" name="keep_in_mind"><?php echo($page_data->keep_in_mind);?></textarea>
				</td>
			</tr>
			<tr>
				<th>事務局から一言</th>
				<td>
					<textarea rows="4" name="office_word"><?php echo($page_data->office_word);?></textarea>
				</td>
			</tr>
			<tr>
				<th>画像</th>
				<td>
					<input type="file" name="salon_image" />
					<?php if(!empty($page_data->image)) { ?>
						<a href="<?php echo(get_permalink());?>?bspf_action=my_salon&mode=delete_image&my_salon_id=<?php echo($page_data->id);?>">削除</a><br/>
						<img src="<?php echo($page_data->image);?>" width="80%">
					<?php } ?>
				</td>
			</tr>

			<?php 
			for($i=0;$i<3;$i++) { 
				$sub_images	= maybe_unserialize($page_data->sub_images);
				$sub_images_path = maybe_unserialize($page_data->sub_images_path);
			?>
			<tr>
				<th>サブ画像<?php echo($i+1);?></th>
				<td>
					<input type="file" name="sub_images[]" />
					<?php if(!empty($sub_images[$i])) { ?>
						<a href="<?php echo(get_permalink());?>?bspf_action=my_salon&mode=delete_sub_image&sub_id=<?php echo($i);?>&my_salon_id=<?php echo($page_data->id);?>">削除</a><br/>
						<img src="<?php echo($sub_images[$i]);?>" width="80%">
					<?php } ?>
				</td>
			</tr>
			<?php }?>

			<?php 
			for($i=0;$i<3;$i++) { 
				$videos	= maybe_unserialize($page_data->videos);
				$videos_path = maybe_unserialize($page_data->videos_path);
			?>
			<tr>
				<th>動画<?php echo($i+1);?></th>
				<td>
					<input type="file" name="videos[]" />
					<?php if(!empty($videos[$i])) { ?>
						<a href="<?php echo(get_permalink());?>?bspf_action=my_salon&mode=delete_video&sub_id=<?php echo($i);?>&my_salon_id=<?php echo($page_data->id);?>">削除</a><br/>
						<video src="<?php echo($videos[$i]);?>" width="80%" controls></video>
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
							if($page_data->enter_payment_flag == $key) {$selected = "selected";}
							echo('<option value="'.$key.'" '.$selected.'>'.$val.'</option>');
						}
						?>
					</select>
				</td>
			</tr>

			<tr>
				<th>公開状態</th>
				<td>
					<?php echo($public_status); ?>
				</td>
			</tr>
		</table>
		<div class="text-center">
			<input type="button" name="btn_edit" id="bspf_btn_edit" class="btn btn-round" value="保存" >
			<?php if(!empty($page_data->id)) { ?>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="button" name="btn_delete" id="bspf_btn_delete" class="btn btn-round" value="削除" >
				
				<?php if($page_data->public_flag != BS_PF_SALON_STATUS_PUBLIC && $page_data->public_flag != BS_PF_SALON_STATUS_REQUIRE) { ?>
					&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="button" name="btn_request" id="bspf_btn_request" class="btn btn-round" value="開設申請" >
				<?php } ?>
			<?php } ?>
		<div>
	</form>
</div>
