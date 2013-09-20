<script type="text/javascript">
$(document).ready(function() {
 	$("#login_submit").bind("click",function () { 
 		$.mobile.pageLoading(); 
 		$.post("<?php echo base_url("mobile/c_wlinke/wlinke_ajax/");?>", 
 		 	{
 		 	action: "login",
 		 	email: $('#login_email').val(),
 		 	password: $('#login_password').val()
 		 	},
			function(data){
 		 		$.mobile.pageLoading(true); 
				if(data=="success"){
					window.location.reload();
				}else
					$.mobile.changePage("#invalud_user_data_dialog");
				});
 	}); 
 	$("#submit_register").bind("click",function () { 
 		$.mobile.pageLoading(); 
 		$.post("<?php echo base_url("mobile/c_wlinke/wlinke_ajax/");?>", 
 		 	{
 		 	action:'register',
 		 	email:$('#register_email').val(),
 		 	password:$('#register_password').val(),
 		 	real_name:$('#register_real_name').val(),
 		 	bluetooth_mac:$('#register_bluetooth_mac').val()
 		 	},
			function(data){
 		 		$.mobile.pageLoading(true); 
				if(data=="success"){
					window.location.reload();
				}
				else{
					$.mobile.changePage("#register_faild_dilog");
					$("#register_error").html(data);
				}
					
				});
 		});
	});
 	
</script>
<div data-role="page" id="login" data-theme="a"> 
 
	<div data-role="header" data-position="fixed">
		<h1>蜗临客</h1>
	</div><!-- /header --> 
 
	<div data-role="content"> 
	    <form>
		    <h3>邮箱</h3>
			<input id="login_email" name="login_email" type="email" value="" placeholder="请输入您的邮箱"/>
			<h3>密码</h3>
			<input id="login_password" name="login_password" type="password" value="" placeholder="请输入您的密码"/>
	    </form>
	    <button id="login_submit" data-theme="a">登陆</button>
		<a href="#register" data-role="button">注册</a> 
	</div> 
</div><!-- /page --> 

<div data-role="page" id="register" data-theme="a"> 
 
	<div data-role="header" data-position="fixed">
		<h1>蜗临客</h1>
		<a data-rel="back" data-icon="arrow-l">返回</a>
	</div><!-- /header --> 
 
	<div data-role="content"> 
		<form>
			<h3>邮箱</h3>
			<input id="register_email" name="register_email" type="email" value="" placeholder="请输入您的邮箱"/>
			<h3>密码（数字或字母组合）</h3>
			<input id="register_password" name="register_password" type="password" value="" placeholder="请输入您的密码"/>
			<h3>姓名（中文及字母组合）</h3>
			<input id="register_real_name" name="register_real_name" type="text" value="" placeholder="请输入您的姓名"/>
			<h3>蓝牙地址（12位字母数字组合）</h3>
			<input id="register_bluetooth_mac" name="register_bluetooth_mac" type="text" value="" placeholder="请输入您的蓝牙地址"/>
		</form>
		<button id="submit_register"  data-theme="a">提交</button>
	</div>
</div><!-- /page --> 

<div data-role="page" id="register_faild_dilog" data-theme="a"> 
	<div data-role="header">
		<h1>注册失败</h1>
	</div>

	<div data-role="content">
		<div id="register_error">未知错误</div>
		<a data-role="button" data-rel="back">返回</a>       
	</div>
</div><!-- /page --> 


<div data-role="page" id="invalud_user_data_dialog" data-theme="a"> 
	<div data-role="header">
		<h1>登陆失败</h1>
	</div>

	<div data-role="content">
		<h3>用户名不存在或者密码输入错误，请检查您的账号与密码是否填写正确</h3>
		<a data-role="button" data-rel="back">返回</a>       
	</div>
</div><!-- /page --> 