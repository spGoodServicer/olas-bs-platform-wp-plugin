<?php

?>

<div class="wrap" id="salon-edit-page">
	<form action="" method="post" name="bspf-salon-edit" id="bspf-salon-edit" enctype="multipart/form-data" class="validate">
		<input name="action" type="hidden" value="edit_salon_kind" />

		<?php wp_nonce_field( 'edit_kind_admin', '_wpnonce_edit_salon_kind_admin' ) ?>
		<h3><?php echo(BSPlatformUtils::getSalonShowLabel());?>種類の作成</h3>
		<table class="form-table">
			<tr class="bspf-admin-edit-saloname">
				<th scope="row"><label for="show_room_name">種類 </label></th>
				<td><input class="regular-text" name="kind_name" type="text" id="kind_name" value="<?php echo esc_attr(isset($name)?$name:''); ?>" /></td>
			</tr>
		</table>
		<?php submit_button( BSPlatformUtils::_('保存'), 'primary', 'edit_salon_kind', true, array( 'id' => 'edit_salon_kind' ) ); ?>
	</form>
</div>

<?php $this->set_error(''); ?>
