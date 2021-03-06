<div data-role="page" id='post_weibo' class="type-interior"> 
 
	<div data-role="header" data-position="fixed"  data-theme="f">
		<h1>蜗语</h1>
		<a data-rel="back" data-icon="arrow-l">返回</a>
	</div><!-- /header --> 
 	
	<div data-role="content"> 
		<div class="content-primary">	
			<div class="ui-grid-a">
				<div class="ui-block-a" >
					<img name="name" height="80" src="<?php echo $feed['user_avatar'];?>">
				</div>
				<div class="ui-block-b" >
					<h3><?php echo $feed['display_name']?></h3>
				</div>
			</div>
			<hr/>
			<div>
				<ul data-role="listview" data-inset="true">
					<li>
						<lable>蜗语：<?php echo $feed['feed_content'];?></lable>
					</li>
					<li>
						<lable>发布时间：<?php echo date("Y-m-d G:i",$feed['create_time']+8*60*60);?></lable>
					</li>
					<li>
						<lable>评论：<?php echo $feed['comment_count'];?></lable>
					</li>
					<li>
						<lable>转发：<?php echo $feed['transpond_count'];?></lable>
					</li>
					<li>
						<lable>上次活动时间：<?php echo date("Y-m-d G:i",$feed['last_activity']+8*60*60);?></lable>
					</li>
				</ul>
				<hr/>
				<ul data-role="listview" data-inset="true">
				    <li style="text-align:center;">
						<a href="<?=base_url('mobile/c_page/comment_weibo/'.$feed['feed_id']);?>" style="text-align:center;">评论</a>
					</li>
					<li style="text-align:center;">
						<a href="<?=base_url('mobile/c_page/transpond_weibo/'.$feed['feed_id']);?>" style="text-align:center;">转发</a>
					</li>
				</ul>
			</div>
			<hr/>
			
		</div><!--/content-primary -->		
	</div>
</div><!-- /page --> 