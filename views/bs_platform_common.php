<style>
/*
.p-entry__body {
    border-bottom: none;
}
*/

@media only screen and (max-width: 991px){
	
}
</style>

<p class="text-center">会員が開設したオンライン<?php echo(BSPlatformUtils::getSalonShowLabel());?>が一覧で表示されます</p>
<div class="bspf_content">
	<div class="left_side">
		<ul>
			<?php if(!BSPlatformUtils::is_admin() && $this->is_create_salon()) { ?>
				<li><a href="<?php echo(get_permalink());?>?bspf_action=my_salon" class="p-siteinfo__button p-button">Myサロン</a></li>
			<?php } ?>
			<li><a href="<?php echo(get_permalink());?>?bspf_action=salon_list" class="p-siteinfo__button p-button"><?php echo(BSPlatformUtils::getSalonShowLabel());?>リスト</a></li>
			<li><a href="<?php echo(get_permalink());?>?bspf_action=salon_fav_list" class="p-siteinfo__button p-button">お気に入り<?php echo(BSPlatformUtils::getSalonShowLabel());?></a></li>
			<li><a href="<?php echo(get_permalink());?>?bspf_action=salon_entrance_list" class="p-siteinfo__button p-button">加入済<?php echo(BSPlatformUtils::getSalonShowLabel());?></a></li>
			<?php /* if($my_salon_data) { ?>
			<li><a href="<?php echo(get_permalink());?>?bspf_action=my_bank">銀行情報</a></li>
			<?php } */?>
		</ul>
	</div>
	<div class="main">
		<?php include(BS_PLATFORM_PATH . 'views/bs_platform_'.$page.'.php'); ?>
	</div>
</div>

