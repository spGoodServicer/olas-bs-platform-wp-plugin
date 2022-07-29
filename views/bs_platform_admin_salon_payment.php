<?php
$salon_list = BSPlatformUtils::getTableData("salon");
$payment_salon_id = isset($_REQUEST['payment_salon']) ? $_REQUEST['payment_salon'] : '';
?>
<?php // BS-Platform Create Payment  ?>
<form id="frm_bs_platform_payment" class="bs_platform_payment_form" method="post">
	<div class="bspf_theme_option_field cf">
		<h3 class="bspf_theme_option_headline"><?php BSPlatformUtils::e( '支払い作成' ); ?></h3>

		<select name="payment_salon" id="sb_payment_salon">
			<option value="">選択してください</option>
			<?php 
			foreach($salon_list as $row) {
				echo ("<option value='".$row->id."' ".selected($row->id, $payment_salon_id, false).">".$row->name."</option>");
			}
			?>
		</select>
		<input type="button" id="btn_select_salon" class="bs_platform_button-ml" value="選択">

		<?php 
		if(!empty($payment_salon_id)) {
			$payment_data = $this->get_salon_payment($payment_salon_id);
			$salon_data = $payment_data['salon'];
			$salon_manager = $payment_data['salon_manager'];
		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th><label><?php BSPlatformUtils::e( '期間' ); ?></label></th>
					<td><?php echo($payment_data['history_start'] . " ~ " . $payment_data['history_end']);?></td>
				</tr>
				<tr>
					<th><label><?php BSPlatformUtils::e( '入金額' ); ?></label></th>
					<td><?php echo($payment_data['in_money']);?></td>
				</tr>
				<tr>
					<th><label><?php BSPlatformUtils::e( '報酬率(%)' ); ?></label></th>
					<td><?php echo($salon_data->fee_percent);?></td>
				</tr>
				<tr>
					<th><label><?php BSPlatformUtils::e( '送金額' ); ?></label></th>
					<td><?php echo(round($payment_data['in_money'] * $salon_data->fee_percent / 100));?></td>
				</tr>

				<tr>
					<th colspan="2"><hr/></th>
				</tr>
				<?php if($salon_manager->bank_type == 1) { ?>
					<tr>
						<th colspan="2"><label>銀行</label></th>
					</tr>
					
					<tr>
						<th><label>金融機関名</label></th>
						<td><?php echo($salon_manager->bank_name);?></td>
					</tr>
					<?php /* ?>
					<tr>
						<th><label>銀行コード</label></th>
						<td><?php echo($salon_manager->bank_code);?></td>
					</tr>
					<?php */ ?>
					<tr>
						<th><label>支店番号</label></th>
						<td><?php echo($salon_manager->bank_area_number);?></td>
					</tr>
					<tr>
						<th><label>支店名</label></th>
						<td><?php echo($salon_manager->bank_area_name);?></td>
					</tr>
					<tr>
						<th><label>口座種別</label></th>
						<td><?php echo(($salon_manager->bank_account_type == 2)? "当座": "普通");?></td>
					</tr>
					<tr>
						<th><label>口座番号</label></th>
						<td><?php echo($salon_manager->bank_account_number);?></td>
					</tr>
					<tr>
						<th><label>口座名義人</label></th>
						<td><?php echo($salon_manager->bank_account_name);?></td>
					</tr>
				<?php } else {?>
					<tr>
						<th colspan="2"><label>Paypal</label></th>
					</tr>
					
					<tr>
						<th><label>メールアドレス</label></th>
						<td><?php echo($salon_manager->paypal_address);?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>

		<input type="hidden" name="history_start" value="<?php echo($payment_data['history_start']);?>" />
		<input type="hidden" name="history_end" value="<?php echo($payment_data['history_end']);?>" />
		<input type="hidden" name="in_money" value="<?php echo($payment_data['in_money']);?>" />
		<input type="hidden" name="salon_id" value="<?php echo($salon_data->id);?>" />

		<input type="hidden" name="mode" value="add" />
		<?php wp_nonce_field( 'add_payment_history', '_wpnonce_add_payment_admin' ) ?>
		<input type="submit" style="padding:10px 30px; background-color:#0471BB; color:white;" name="add_payment" class="bs_platform_button-ml" value="<?php BSPlatformUtils::e( '作成' ); ?>">
		<?php }?>
	</div>
</form>