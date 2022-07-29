<?php
$salon_search_kind_id = "";
$salon_search_name = "";
if(isset($_REQUEST['salon_search_kind']) && !empty($_REQUEST['salon_search_kind'])){ $salon_search_kind_id = $_REQUEST['salon_search_kind']; }
if(isset($_REQUEST['salon_search_name']) && !empty($_REQUEST['salon_search_name'])){ $salon_search_name = $_REQUEST['salon_search_name']; }
?>
<div class="salon-list">
	<h2 class="text-center"><?php echo(BSPlatformUtils::getSalonShowLabel());?>リスト</h2>

	<?php foreach($page_msg as $message) {?>
		<p class="message"><?php echo($message);?></p>
	<?php }?>

	<div class="search_group">
		<form name="frm_search" method="post" class="search_form">
			<input type="hidden" name="bspf_action" value="salon_list" />
			<select name="salon_search_kind">
				<option value="0">全て</option>
				<?php foreach($salon_kind_data as $key=>$row){
					$selected = "";
					if($salon_search_kind_id == $row->id) {$selected = "selected";}
					echo('<option value="'.$row->id.'" '.$selected.'>'.$row->name.'</option>');
				}?>
			</select>
			<input type="text" name="salon_search_name" value="<?php echo($salon_search_name);?>" placeholder="<?php echo(BSPlatformUtils::getSalonShowLabel());?>名を入力してください" class="salon_search_name"/>
			<input type="submit" value="検索" name="btn_search" class="p-siteinfo__button p-button" style="margin: auto;"/>
		</form>
	</div>

	<div class="p-salon-archive">
	<?php 
		foreach($page_data as $key=>$salon) { 
			$bspf_user = BSPlatformUtils::getTableData('user', array('id'=>$salon->manager_user_id));
			$author = get_user_by( 'ID', $bspf_user[0]->wp_user_id );
	?>
		<article class="p-salon-archive__item" data-aos="fade-zoom-in" data-aos-easing="ease-in-back" data-aos-delay="<?php echo (3*$key + 2); ?>00" data-aos-offset="0" data-aos-duration="1500">
			<a class="item-salon p-hover-effect--type1 u-clearfix" href="<?php echo (home_url() . "?salon_id=" . $salon->id); ?>">
				<div class="p-salon-archive__item-thumbnail">
					<div class="p-salon-archive__item-thumbnail__inner p-hover-effect__image js-object-fit-cover">
						<?php
						if ( !empty($salon->image) ) :
							echo '<img src="' . $salon->image . '" alt="">';
						else :
							echo '<img src="' . get_template_directory_uri() . '/img/no-image-600x600.gif" alt="">';
						endif;
						echo "\n";

						/*
						if ( $args['catlist_float'] ) :
							echo "\t\t\t\t\t\t\t";
							echo '<div class="p-float-category">' . implode( '', $args['catlist_float'] ) . '</div>' . "\n";
						endif;

						if ( isset($args['cb_content']['cb_show_date']) && $args['cb_content']['cb_show_date'] ) :
							echo "\t\t\t\t\t\t\t";
							echo '<div class="p-blog-archive__item-thumbnail_meta p-article__meta">';
							echo '<time class="p-article__date" datetime="' . get_the_time( 'c' ) . '">' . zoomy_get_human_time_diff() . '</time>';
							echo '</div>' . "\n";
						endif;
						*/
						?>
					</div>
				</div>

				<div class="info-blog">
					<h3 class="title-blog p-blog-archive__item-title p-article-post__title p-article__title js-multiline-ellipsis"><?php echo mb_strimwidth( strip_tags( $salon->name ), 0, 80, '...' ); ?></h3>

					<div class="p-blog-archive__item-author p-article__author" data-url="<?php echo esc_attr( get_author_posts_url( $author->ID ) ); ?>">
						<span class="p-blog-archive__item-author_thumbnail p-article__author-thumbnail"><?php echo get_avatar( $author->ID, 96 ); ?></span>
						<span class="p-blog-archive__item-author_name p-article__author-name"><?php echo ( esc_html( $author->display_name ) . get_member_level_icon($author) ); ?></span>
					</div>
					<?php /*
					<div class="text-center">
						<?php if(empty($salon->allow_flag)) {?><span data-url="<?php echo(get_permalink());?>?bspf_action=salon_list&mode=entrance&salon_list_id=<?php echo($salon->id);?>">加入</span>&nbsp;&nbsp;<?php } ?>
						<?php if(empty($salon->favorite_flag)) {?><span data-url="<?php echo(get_permalink());?>?bspf_action=salon_list&mode=favorite&salon_list_id=<?php echo($salon->id);?>">お気に入り</span><?php } ?>
					</div>
					*/?>
				</div>
			</a>
		</article>
	<?php }?>
	</div>
				
	<?php /*?>
	<table>
		<tr>
			<th></th>
			<th>サロン名</th>
			<th>サロンの概要、特徴</th>
			<th>操作</th>
		</tr>
		<?php foreach($page_data as $salon) { ?>
		<tr>
			<td class="text-center">
				<?php if(!empty($salon->image)) { ?>
					<img src="<?php echo($salon->image);?>" width="60px">
				<?php } ?>
			</td>
			<td>
				<a href="<?php echo(get_permalink());?>?bspf_action=salon_detail&salon_detail_id=<?php echo($salon->id);?>"><?php echo($salon->name);?></a>
			</td>
			<td><?php echo($salon->description);?></td>
			<td class="text-center">
				<?php // if(empty($salon->entrance_user_id)) {?>
					<?php if(empty($salon->allow_flag)) {?><a href="<?php echo(get_permalink());?>?bspf_action=salon_list&mode=entrance&salon_list_id=<?php echo($salon->id);?>">加入</a>&nbsp;&nbsp;<?php } ?>
					<?php if(empty($salon->favorite_flag)) {?><a href="<?php echo(get_permalink());?>?bspf_action=salon_list&mode=favorite&salon_list_id=<?php echo($salon->id);?>">お気に入り</a><?php } ?>
				<?php // } ?>
			</td>
		</tr>
		<?php } ?>
	</table>
	<?php */?>
</div>
