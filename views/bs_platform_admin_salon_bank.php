<div class="salon-content">
	<form name="frmBank" id="frmBank" method="post" enctype="multipart/form-data">
		<input type="hidden" name="user_id" id="user_id" value="<?php echo($page_data->id);?>" />
	
		<div id="bspf_tab-panel">
			<div class="bspf_theme_option_field cf">
				<h3 class="bspf_theme_option_headline"><?php BSPlatformUtils::e( '銀行情報' ); ?></h3>

				<?php foreach($page_msg as $message) {?>
					<p class="message"><?php echo($message);?></p>
				<?php }?>
				
				<div class="form-group form-checkbox_field ">
					<label><input type="radio" name="bank_type" id="bank_type_bank" value="1" <?php checked(1, $page_data->bank_type) ?>>銀行</label>
				</div>

				<div class="form-group ">
					<label for="bank_name" class="caption">金融機関名</label>
					<input type="text" name="bank_name" value="<?php echo($page_data->bank_name);?>"/>
				</div>

				<div class="form-group ">
					<label for="bank_area_number" class="caption">支店番号</label>
					<input type="text" name="bank_area_number" value="<?php echo($page_data->bank_area_number);?>"/>
				</div>

				<div class="form-group ">
					<label for="bank_area_name" class="caption">支店名</label>
					<input type="text" name="bank_area_name" value="<?php echo($page_data->bank_area_name);?>"/>
				</div>

				<div class="form-group ">
					<label for="bank_account_type" class="caption">口座種別</label>
					<select name="bank_account_type">
						<option value="1" <?php selected(1, $page_data->bank_account_type) ?>>普通</option>
						<option value="2" <?php selected(2, $page_data->bank_account_type) ?>>当座</option>
					</select>
				</div>

				<div class="form-group ">
					<label for="bank_account_number" class="caption">口座番号</label>
					<input type="text" name="bank_account_number" value="<?php echo($page_data->bank_account_number);?>"/>
				</div>

				<div class="form-group ">
					<label for="bank_account_name" class="caption">口座名義人</label>
					<input type="text" name="bank_account_name" value="<?php echo($page_data->bank_account_name);?>"/>
				</div>


				<div class="form-group form-checkbox_field ">
					<label><input type="radio" name="bank_type" id="bank_type_paypal" value="2" <?php checked(2, $page_data->bank_type) ?>>Paypal</label>
				</div>

				<div class="form-group ">
					<label for="paypal_address" class="caption">メールアドレス</label>
					<input type="email" name="paypal_address" value="<?php echo($page_data->paypal_address);?>"/>
				</div>

				<input type="submit" class="bs_platform_button-ml" name="btn_bank_edit" id="bspf_btn_bank_edit" value="<?php BSPlatformUtils::e( '保存' ); ?>">
			</div>
		</div>

	</form>

</div>
