<div data-role="page" id='post_weibo'> 
 
	<div data-role="header" data-position="fixed" >
		<h1>发状态</h1>
		<a data-rel="back" data-icon="arrow-l">返回</a>
	</div><!-- /header --> 
 	
	<div data-role="content"> 
		<form action="<?=base_url("mobile/c_page/post_weibo_submit"); ?>" method="post" data-ajax="false" >
			<textarea rows="15" name="weibo_content" id="weibo_content" placeholder="请输入微博内容" style="width:99%; "></textarea>
			<input type="submit" value="提交" data-theme="a"/>
		</form>
	</div>
</div><!-- /page --> 