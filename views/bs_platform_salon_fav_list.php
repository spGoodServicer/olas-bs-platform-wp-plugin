<div class="salon-list">
	<h2 class="text-center">お気に入り<?php echo(BSPlatformUtils::getSalonShowLabel());?></h2>

	<?php foreach($page_msg as $message) {?>
		<p class="message"><?php echo($message);?></p>
	<?php }?>

	<table>
		<?php /*
		<tr>
			<th></th>
			<th>サロン名</th>
			<th>サロンの概要、特徴</th>
			<th>操作</th>
		</tr>
		*/ ?>
		<?php foreach($page_data as $salon) { ?>
		<tr>
			<td class="text-center" style="min-width:80px;">
				<?php if(!empty($salon->image)) { ?>
					<a href="<?php echo (home_url() . "?salon_id=" . $salon->id); ?>"><img src="<?php echo($salon->image);?>" width="60px"></a>
				<?php } ?>
			</td>
			<td>
				<a href="<?php echo (home_url() . "?salon_id=" . $salon->id); ?>"><?php echo($salon->name);?></a>
			</td>
			<td><?php echo($salon->description);?></td>
			<td class="text-center">
				<a href="<?php echo(get_permalink() . '?bspf_action=salon_fav_list&id='.$salon->id.'&mode=delete');?>" onclick="return confirm('削除してもよろしいですか？')">削除</a>
			</td>
		</tr>
		<?php } ?>
	</table>
</div>
