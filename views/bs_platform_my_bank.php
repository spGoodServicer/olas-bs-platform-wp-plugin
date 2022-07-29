<div class="salon-content">
	<h2 class="text-center">銀行情報</h2>

	<?php foreach($page_msg as $message) {?>
		<p class="message"><?php echo($message);?></p>
	<?php }?>
	
	<form name="frmBank" id="frmBank" method="post" enctype="multipart/form-data">
		<input type="hidden" name="bspf_action" value="my_bank" />
		<input type="hidden" name="mode" id="mode" value="" />
	
		<div style="margin-bottom:10px;">
			<label><input type="radio" name="bank_type" id="bank_type_bank" value="1" <?php checked(1, $page_data->bank_type) ?>>銀行</label>
		</div>

		<div id="bank_content">
			<table>
				<tr>
					<th>金融機関名</th>
					<td>
						<input type="text" name="bank_name" value="<?php echo($page_data->bank_name);?>"/>
					</td>
				</tr>
				<?php /* ?>
				<tr>
					<th>銀行コード</th>
					<td>
						<input type="text" name="bank_code" value="<?php echo($page_data->bank_code);?>"/>
					</td>
				</tr>
				<?php */ ?>
				
				<tr>
					<th>支店番号</th>
					<td>
						<input type="text" name="bank_area_number" value="<?php echo($page_data->bank_area_number);?>"/>
					</td>
				</tr>
				<tr>
					<th>支店名</th>
					<td>
						<input type="text" name="bank_area_name" value="<?php echo($page_data->bank_area_name);?>"/>
					</td>
				</tr>

				<tr>
					<th>口座種別</th>
					<td>
						<select name="bank_account_type">
							<option value="1" <?php selected(1, $page_data->bank_account_type) ?>>普通</option>
							<option value="2" <?php selected(2, $page_data->bank_account_type) ?>>当座</option>
						</select>
					</td>
				</tr>
				<tr>
					<th>口座番号</th>
					<td>
						<input type="text" name="bank_account_number" value="<?php echo($page_data->bank_account_number);?>"/>
					</td>
				</tr>
				<tr>
					<th>口座名義人</th>
					<td>
						<input type="text" name="bank_account_name" value="<?php echo($page_data->bank_account_name);?>"/>
					</td>
				</tr>
			</table>
		</div>

		<div style="margin-bottom:10px;">
			<label><input type="radio" name="bank_type" id="bank_type_paypal" value="2" <?php checked(2, $page_data->bank_type) ?>>Paypal</label>
		</div>
		<div id="paypal_content">
			<table>
				<tr>
					<th>メールアドレス</th>
					<td>
						<input type="email" name="paypal_address" value="<?php echo($page_data->paypal_address);?>"/>
					</td>
				</tr>
			</table>
		</div>
		
		<div class="text-center">
			<input type="button" name="btn_bank_edit" id="bspf_btn_bank_edit" class="btn btn-round" value="保存" >
		<div>
	</form>
</div>
