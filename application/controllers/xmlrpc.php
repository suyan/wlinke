<?php

class Xmlrpc extends WE_Controller {
	
	/**
	 * XMLRPC的函数列�&#65533;
	 * @var array
	 */
	var $methods;
	
	/**
	 * 当前的时�&#65533;
	 * @var integer
	 */
	var $current;

	/**
	 * 构造函�&#65533;
	 * 
	 * 初始化各API，以及当前请求的时间
	 */
	function __construct() {
		$this->current=time();
		log_message("error","xmlrpc_request");
		log_message("error",$GLOBALS['HTTP_RAW_POST_DATA']);
		$this->methods = array (
				'we.addUser'=>'this:add_user',
				'we.userLogin'=>'this:user_login',
				'we.addFriend'=>'this:add_friend',
				'we.addGroup'=>'this:add_group',
				'we.joinGroup'=>'this:join_group',
				'we.postWeibo'=>'this:post_weibo',
				'we.transpondWeibo'=>'this:transpond_weibo',
				'we.uploadPicture'=>'this:upload_pirture',
				'we.uploadAvatar'=>'this:upload_avatar',
				'we.getNearbyBluetoothDatas'=>'this:get_near_by_bluetooth_datas',
				'we.groupCheckInbyBluetoothDatas'=>'this:group_check_in_by_bluetooth_datas',
				'we.getGroupDatasbyCategory'=>'this:get_group_datas_by_category',
				'we.getGroupDatasbyUserId'=>'this:get_group_datas_by_user_id',
				'we.handleNotify'=>'this:handle_notify',
				'we.getUserWeibo'=>'this:get_user_weibo',
				'we.getFriendsWeibo'=>'this:get_friends_weibo',
				'we.addEvent'=>'this:add_event',
				'we.sayHello'=>'this:say_hello',
				'we.getAllPublicWeibo'=>'this:get_all_public_weibo',
				'we.getAllUserData'=>'this:get_all_user_data',
				'we.addPlace'=>'this:add_place',
				'we.getPlaceDatasbyCategory'=>'this:get_place_datas_by_category',
				'we.commentWeibo'=>'this:comment_weibo',
				'we.getPlaceRecentMembers'=>'this:get_place_recent_members',
				'we.addPlaceMember'=>'this:add_place_member',
				'we.getHistoryNearUserDatas'=>'this:get_history_near_user_datas',
				'we.getUserbyToken'=>'this:get_user_by_token',
				'we.getGroupMembersbyGroupId'=>'this:get_group_members_by_group_id',
				'we.getGroupDatabyGroupId'=>'this:get_group_data_by_group_id',
				'we.getUserDatabyDisplayName'=>'this:get_user_data_by_display_name'
		);
		parent::__construct ( $this->methods );
		
	}

	/**
	 * 增加一个新用户
	 * 
	 * 增加一条数据库用户信息，包含并且自动创建一个默认相册，发送欢迎通知
	 * 
	 * @param array $args <br>
	 * 		string email 用户邮箱 <br>
	 * 		string password 用户密码 <br>
	 * 		string real_name 真实名字<br>
	 * 		string bluetooth_mac 手机蓝牙地址 <br>
	 * @return string|boolean  <br>
	 * 		增加成功 <br>
	 * 		string "success_1" 返回用户ID<br>
	 * 		增加失败<br>
	 * 		string "invalid_email" 邮箱格式不对<br>
	 *		string "existing_email" 邮箱已存�&#65533;<br>
	 * 		string "invalid_password" 密码格式不对<br>
	 * 		string "invalid_real_name" 真实名字不规�&#65533;<br>
	 * 		string "invalid_bluetooth_mac" 蓝牙地址不可�&#65533;<br>
	 * 		string "existing_bluetooth_mac" 蓝牙地址已存�&#65533;
	 */
	function add_user($args){
		$this->load->model('m_user');
		$this->load->helper('album');
		
		$data['email']=$args[0];
		
		if(!valid_email($data['email']))
			return array("error"=>"invalid_email");

		if($this->m_user->check_exist('email',$data['email']))
			return 	array("error"=>"existing_email");
		
		$data['password']=$args[1];
		
		if(!valid_password($data['password']))
			return array("error"=>"invalid_password");
		
		$data['real_name']=$args[2];
		
		if(!valid_real_name($data['real_name']))
			return array("error"=>"invalid_real_name");
		
		if(isset($args[3]))
			$bluetooth_mac=$args[3];
		else return array("error"=>"invalid_bluetooth_mac");
			
		if(!valid_bluetooth_mac($bluetooth_mac))
			return array("error"=>"invalid_bluetooth_mac");
		
		$bluetooth_mac=strtoupper($bluetooth_mac);
		
		if(isset($args[4]))
			$bluetooth_name=$args[4];
		else
			$bluetooth_name="";
		
		$data['bluetooth_id']=$this->m_user->get_user_id_by_bluetooth_mac($bluetooth_mac);
		
		if($data['bluetooth_id']){
			return array("error"=>"existing_bluetooth_mac");
		}else{
			$data['bluetooth_id']=$this->m_user->add_bluetooth(array(
					'bluetooth_mac'=>$bluetooth_mac,
					'bluetooth_name'=>$bluetooth_name,
					'create_time'=>$this->current
					));
		}
	
		$data['password'] = md5($data['password']);
		
		$data['display_name']=$data['real_name'];
		
		$num=$this->m_user->check_exist('real_name',$data['real_name']);
		$data['real_name']=$data['real_name'].'('.($num+1).')';
		$data['create_time']=time();
		$data['user_type']='user';
		
		$result=$this->m_user->add_user($data);
		
		if($result){
			
			$this->m_user->add_bluetooth(
					array(
					'user_id'=>$result,
					'bluetooth_mac'=>$bluetooth_mac,
					'bluetooth_name'=>$bluetooth_name,
					'create_time'=>$this->current
			));
			
			//创建用户的默认相�&#65533;
			$this->create_album($result,'default','default album');
			create_user_dir($result);
			
			//设置用户的默认头�&#65533;
			$this->m_user->add_user_meta(array(
					'user_id'=>$result,
					'meta_key'=>'user_avatar',
					'meta_value'=>base_url().'upload/default.jpg'
					));
			
			//设置用户好友数量
			$this->m_user->add_user_meta(array(
					'user_id'=>$result,
					'meta_key'=>'friend_count',
					'meta_value'=>0
			));
			
			//设置用户微博数量
			$this->m_user->add_user_meta(array(
					'user_id'=>$result,
					'meta_key'=>'weibo_count',
					'meta_value'=>0
			));
			
			//设置用户最后发表状�&#65533;
			$this->m_user->add_user_meta(array(
					'user_id'=>$result,
					'meta_key'=>'latest_update',
					'meta_value'=>"我刚刚注册了蜗邻客哦，快来发现我吧！"
			));
			
			
			//给用户欢迎信�&#65533;
			$this->send_notify(0, $result, 'welcome','0','欢迎加入蜗临客大家庭�&#65533;');
			
			//为用户登�&#65533;
			$user_data=$this->user_login($args);
			
			return $user_data;
		}
		else return array("error"=>"unknown_error");
	}
	
	/**
	 * 用户登录，生成token，供下次使用
	 * 
	 * @param array $args(email,password)
	 * @return fail "invalid_user_data"
     * @return success $token
	 */
 	function user_login($args){
 		//$this->load->library('session');
               // $this->session->set_userdata('session_id','65a572eb0f3315b4cead19a216d731f0');
              // $this->session->set_userdata('user_data','123');
               // return 1;
		$this->load->model('m_user');
		$this->load->model('m_online');
		
		//$this->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));
		
		$this->load->driver('cache',array('adapter'=>'file'));
		
		$data['email']=$args[0];
		$data['password']=$args[1];
		
		$user_data=$this->m_user->validate_user($data);
		
		if($user_data)
			$token=md5($data['email']);
		else return array("error"=>"invalid_user_data");
		
		$user_data['token']=$token;
		//根据用户此次登陆的情况生成一个token
		$this->cache->save($token,serialize($user_data),300);
		
		$data=array(
				'user_id'=>$user_data['user_id'],
				'display_name'=>$user_data['display_name'],
				'create_time'=>time(),
				'online_type'=>'电脑在线'
					);
		$this->m_online->add_online($data);
		
		unset($user_data['password']);
		if(!isset($user_data['latest_update'])){
			$user_data['latest_update']="";
		}
		$user_data['relationship']="self";
		return $user_data;
	} 
	
	/**
	 * 申请和某人成为好�&#65533;
	 * @param unknown_type $args
	 */
	function add_friend($args){
		
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array('error'=>"not_login");
		
		if(isset($args[1])&&$args[1]){
			$friend_id=$args[1];
			$this->load->model('m_user');
			$friend_data=$this->m_user->get_user_data_by_user_id($friend_id);
			if(!$friend_data)
				return array('error'=>"not_user");
		}
		
		if(isset($args[2]))
			$content=$args[2];
		else
			$content="";
		
		$notify_content['display_name']=$user_data['display_name'];
		$notify_content['content']=$content;
		$notify_content=serialize($notify_content);
		
		$num=$this->send_notify($user_data['user_id'], $friend_id, 'friend_application','0', $notify_content);
		
		if($num)
			return $friend_data;
		else
			return array("error"=>"unknown_error");
	}
	
	/**
	 * 处理通知
	 * @param unknown_type $args
	 */
	function handle_notify($args){
		
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array("error"=>"not_login");
		
		if(isset($args[1])&&$args[1]){
			$notify_id=$args[1];
		}else{
			return array("error"=>"no_notify");
		}
		
		if(isset($args[2])){
			$choice=$args[2];
		}else{
			$choice=0;
		}
		
		$this->load->model('m_notify');
		
		$notify_data=$this->m_notify->get_notify_data_by_notify_id($notify_id);
		
		if($notify_data){
			if($notify_data['notify_type']=="welcome"){	
				$this->igniore_notify($notify_id);
				return $notify_data;
			}else if($notify_data['notify_type']=="friend_application"){
				if($choice){
					
					//处理添加好友
					$this->load->model('m_friend');
					$friend_data=array();
					$friend_data['user_id']=$notify_data['from_id'];
					$friend_data['friend_id']=$notify_data['to_id'];
					$friend_data['create_time']=$this->current;
					$this->m_friend->add_friend($friend_data);
					$friend_data=array();
					$friend_data['user_id']=$notify_data['to_id'];
					$friend_data['friend_id']=$notify_data['from_id'];
					$friend_data['create_time']=$this->current;
					$this->m_friend->add_friend($friend_data);
					//消息标记为已�&#65533;
					$this->igniore_notify($notify_id);
					return $notify_data;
				}else{
					$this->igniore_notify($notify_id);
					return $notify_data;
				}
			}
		}else{
			return array("error"=>'no_notify');
		}
		
		
	}
	
	/**
	 * 创建一个群�&#65533;
	 * @param unknown_type $args
	 * @return string|boolean
	 */
	function add_group($args){
		
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array("error"=>"not_login");
		
		$this->load->model('m_group');
		
		$group['user_id']=$user_data['user_id'];
		
		$group['group_name']=$args[1];
		
		$group['group_destription']=$args[2];
		
		$group['group_category']=$args[3];
		
		$group['group_states']=1;
		
		$group['member_count']=1;
		
		$group['create_time']=$this->current;
		
		$result=$this->m_group->add_group($group);
		
		if($result){
			$group_member=array();
			$group_member['group_id']=$result;
			$group_member['user_id']=$user_data['user_id'];
			$group_member['inviter_id']=0;
			$group_member['is_admin']=1;
			$group_member['is_confirmed']=1;
			$group_member['create_time']=$this->current;
			$this->m_group->add_group_member($group_member);
			$group['group_id']=$result;
			return $group;
		}
		else return array("error"=>"unknown_error");
	}
	
	/**
	 * 申请加入群组
	 * @param unknown_type $args
	 * @return string|boolean
	 */
	function join_group($args){
		
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array("error"=>"not_login");
		
		$this->load->model('m_group');
		$data=array();
		
		$data['group_id']=$args[1];
		
		$group_data=$this->m_group->get_group_data_by_group_id($data['group_id']);
		if(!$group_data)
			return array('error',"no_group");
		
		$data['user_id']=$user_data['user_id'];
		
		$row=$this->m_group->get_group_member_id_by_user_id($data['user_id'],$data['group_id']);
		
		if($row){
			return $row['is_confirmed']?array('error'=>"be_in"):array('error'=>"wait_verify");
		}
		
		$data['inviter_id']=0;
		
		$data['is_admin']=0;
		
		$data['is_confirmed']=1;
		
		$data['create_time']=$this->current;
		
		$result=$this->m_group->add_group_member($data);
		
		if($result){
			$this->m_group->increase_group_member_count($args[1]);
			
			$create_user_id=$this->m_group->get_group_create_user_id_by_group_id($args[1]);
			
			$this->send_notify($user_data['user_id'], $create_user_id, 'group_application','0', $args[2]);
			
			return $group_data;
		}else{
			return array("error"=>"unknown_error");
		}
	}
	
	/**
	 * 根据群组ID获得群组中不在自己身边的成员
	 * @param unknown_type $args
	 */
	function group_check_in_by_bluetooth_datas($args){
		
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array(array("error"=>"not_login"));
	
		$group_id=$args[1];
		
		$this->load->model('m_user');
		$this->load->model('m_group');
		//如果传入了蓝牙数据，根据蓝牙数据增加蓝牙的发现关�&#65533;
		if(isset($args[2])&&!empty($args[2])){
			foreach($args[2] as $key=>$value){
				$args[2][$key]=array($value,"","0");
			}
			$this->m_user->add_bluetooth_searchs_by_bluetooth_datas($user_data['bluetooth_id'],$args[2]);
		}
		//根据时间和用户获得发现蓝牙的ID
		$bluetooth_datas=$this->m_user->get_double_bluetooth_search_datas_by_search_time($user_data['bluetooth_id'],$this->current);
		
		//根据蓝牙ID获取用户数据
		$user_datas=$this->m_user->get_user_datas_by_bluetooth_search_datas($bluetooth_datas);
		
		//获得群组内未到的用户资料
		$absent_member_datas=$this->m_group->get_absent_member_datas_by_user_datas($group_id,$user_datas,$user_data['user_id']);	
		
		if($group_id==1){
			//到了的人的名�&#65533;
			$cnet=array();
			foreach($user_datas as $key=>$value){
				$cnet[]=$value['display_name'];
			}
			//所有人名字
			$real_cnet=array(
					'朱俊�&#65533;','张实�&#65533;','冯晨','刘越','冯国�&#65533;','詹永�&#65533;','陈迅','陈圆�&#65533;','卢腾','魏璐','田大�&#65533;',
					'洪语','李添�&#65533;','濮阳瑞青','葛俊�&#65533;','郭健','王德','金琰','王腾�&#65533;','闫肃','胡俊�&#65533;','李法�&#65533;',
					'张文�&#65533;','李政�&#65533;','崔腾�&#65533;','曾庚','李蔚','崔卓�&#65533;','连建�&#65533;','刘俊�&#65533;','陆军','银庆�&#65533;','安延�&#65533;',
					'刘燮','汤明�&#65533;','张光','洪海','孙晓�&#65533;','刘钟�&#65533;','郑连�&#65533;','庞进','纳宁','马宏�&#65533;','牛�&#65533;',
					'朱辛�&#65533;','朱伟�&#65533;','章程','彭腾�&#65533;','王欣�&#65533;','余敏�&#65533;'
					);
			
			$real_absent=array_diff($real_cnet,$cnet);
			
			$cnet=array();
			foreach($absent_member_datas as $key=>$value){
				$cnet[]=$value['display_name'];
			}
			$cnet=array_flip($cnet);

			foreach($real_absent as $value){
				if(array_key_exists($value, $cnet)){
					continue;
				}else{
					$absent_member_datas[]=array(
							'user_id'=>0,
							'real_name'=>$value,
							'display_name'=>$value,
							'last_activity'=>$this->current,
							'create_time'=>$this->current,
							'user_avatar'=>base_url()."upload/default.jpg",
							'friend_count' => 0,
							'weibo_count' =>0,
							'latest_update' =>'我还没有注册或还没有加入这个组，快提醒一下吧~'
							);
				}
			}
		
		}
		
		if($absent_member_datas){
			$result=array();
			//发表一条签到状�&#65533;
			$group_data=$this->m_group->get_group_data_by_group_id($group_id);
			$feed_content="我给#".$group_data['group_name']."#进行点名，没到的成员有：";
			foreach($absent_member_datas as $key=>$value){
				$feed_content.="@".$value['display_name'];
				$value['relationship']=$this->get_users_relationship($user_data['user_id'], $value['user_id']);
				$result[]=(array)$value;
			}
			if($group_id==1)
				$group_data['member_count']=53;
			$absent_rate=intval((intval($group_data['member_count'])-count($absent_member_datas))/intval($group_data['member_count'])*100);
			$feed_content.="。出勤率�&#65533;".$absent_rate."%�&#65533;";
			$this->load->model("m_feed");
			$feed_data=array(
					'user_id'=>$user_data['user_id'],
					'feed_type'=>'weibo',
					'feed_content'=>$feed_content,
					'picture_url'=>"",
					'create_time'=>$this->current,
					'transpond_id'=>0,
					'transpond_count'=>0,
					'comment_count'=>0,
					'visibility'=>"public"
			);
			$feed_id=$this->m_feed->add_feed($feed_data);
			if($feed_id){
				$this->update_last_activity($user_data['user_id'], $this->current,$feed_content);
				$this->m_user->increase_user_meta($feed_data['user_id'], 'weibo_count');
			}
			return $result;
		}
		else {
			$this->load->model("m_feed");
			$group_data=$this->m_group->get_group_data_by_group_id($group_id);
			$feed_content="我给#".$group_data['group_name']."#进行点名，成员全到，出勤率为100%�&#65533;";
			$feed_data=array(
					'user_id'=>$user_data['user_id'],
					'feed_type'=>'weibo',
					'feed_content'=>$feed_content,
					'picture_url'=>"",
					'create_time'=>$this->current,
					'transpond_id'=>0,
					'transpond_count'=>0,
					'comment_count'=>0,
					'visibility'=>"public"
			);
			$feed_id=$this->m_feed->add_feed($feed_data);
			if($feed_id){
				$this->update_last_activity($user_data['user_id'], $this->current,$feed_content);
				$this->m_user->increase_user_meta($feed_data['user_id'], 'weibo_count');
			}
			return array(array("error"=>"no_absent"));
		}
			
	}
	
	/**
	 * 根据周边蓝牙资料主动签到
	 * @param unknown_type $args
	 */
	function group_sign_in_by_bluetooth_datas($args){
		
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array(array("error"=>"not_login"));
		
		$group_id=$args[1];
		
		$this->load->model('m_user');
		$this->load->model('m_group');
		//如果传入了蓝牙数据，根据蓝牙数据增加蓝牙的发现关�&#65533;
		if(isset($args[2])&&!empty($args[2])){
			foreach($args[2] as $key=>$value){
				$args[2][$key]=array($value,"","0");
			}
			$this->m_user->add_bluetooth_searchs_by_bluetooth_datas($user_data['bluetooth_id'],$args[2]);
		}
		//根据时间和用户获得发现蓝牙的信息
		$bluetooth_datas=$this->m_user->get_double_bluetooth_search_datas_by_search_time($user_data['bluetooth_id'],$this->current);
		
	}
	
	/**
	 * 获得所有用户的数据，all或friend
	 * @param unknown_type $args
	 * @return string|Ambigous <multitype:Ambigous, multitype:Ambigous <unknown, multitype:unknown, multitype:unknown_type unknown > >
	 */
	function get_all_user_data($args){
		
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array(array('error'=>"not_login"));
		
		$this->load->model('m_user');
		
		if(isset($args[1])&&$args[1]){
			if(isset($args[2])&&$args[2]){
				$user_ids=$this->m_user->get_all_user_id($args[1],$args[2]);
			}else{
				$user_ids=$this->m_user->get_all_user_id($args[1]);
			}
		}else{
			$user_ids=$this->m_user->get_all_user_id();
		}
		
		$user_datas=$this->m_user->get_user_datas_by_user_ids($user_ids);
		if($user_datas)
			return $user_datas;
		else
			return array(array('error',"not_user"));
		
	}
	
	function get_user_data_by_display_name($args){
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array(array('error'=>"not_login"));
		
		$this->load->model('m_user');
		
		$display_name=$args[1];
		
		$user_datas=$this->m_user->get_user_data_by_display_name($display_name);
		
		if($user_datas){
			foreach($user_datas as $key=>$value){
				$value['relationship']=$this->get_users_relationship($user_data['user_id'], $value['user_id']);
				$user_datas[$key]=$value;
			}
			return $user_datas;
		}else{
			return array(array('error'=>"no_user"));
		}
	}
	/**
	 * 发表一个状�&#65533;
	 * @param unknown_type $args
     * @return success "success_{id}"
     * 
	 */
	function post_weibo($args){
		$this->load->model('m_feed');	
		
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array("error"=>"not_login");
		
		//发表状态内�&#65533;
		$content=$args[1];
		
		$visibility=$args[2];
		
		$data=array(
				'user_id'=>$user_data['user_id'],
				'feed_type'=>'weibo',
				'feed_content'=>$content,
				'picture_url'=>"",
				'create_time'=>$this->current,
				'transpond_id'=>0,
				'transpond_count'=>0,
				'comment_count'=>0,
				'visibility'=>$visibility
		);
		
		$feed_id=$this->m_feed->add_feed($data);
		if($feed_id){
			$this->update_last_activity($user_data['user_id'], $this->current,$content);
			$data=array_merge(array('feed_id'=>$feed_id),$data);
			$data=array_merge($data,$this->m_user->get_user_data_by_user_id($data['user_id']));
			$this->load->model('m_user');
			$this->m_user->increase_user_meta($data['user_id'], 'weibo_count');
			return $data;
		}	
		else 
			return array('error'=>"unknown_error");
	}
	
	/**
	 * 转发一条状�&#65533;
	 * @param array $args(token,feed_id,content)
	 * @return string|boolean
	 */
	function transpond_weibo($args){
		
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array("error"=>"not_login");
		
		$this->load->model('m_feed');
		
		//获得转发的feed_id
		$transpond_id=$args[1];
		
		//发表状态内�&#65533;
		$content=$args[2];
		
		$visibility=$args[3];
		
		$data=array(
				'user_id'=>$user_data['user_id'],
				'feed_type'=>'weibo',
				'feed_content'=>$content,
				'picture_url'=>"",
				'create_time'=>$this->current,
				'transpond_id'=>$transpond_id,
				'transpond_count'=>0,
				'comment_count'=>0,
				'visibility'=>$visibility
		);
		
		$feed_id=$this->m_feed->add_feed($data);
		
		if($feed_id){
			$this->update_last_activity($user_data['user_id'], $this->current);
			$this->m_feed->increase_transpond_count($transpond_id);
			$data=array_merge(array('feed_id'=>$feed_id),$data);
			$data['source_feed']=$this->m_feed->get_feed_by_feed_id($feed_id);
			$this->m_user->increase_user_meta($user_data['user_id'], 'weibo_count');
			return $data;
		}
		else
			return array("error"=>"unknown_error");
	}
	
	/**
	 * 获得所有公共微�&#65533;
	 * @param unknown_type $args
	 * @return string|Ambigous <boolean, unknown>
	 */
	function get_all_public_weibo($args){
		
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array(array("error"=>"not_login"));
		
		if(isset($args[1])&&$args[1])
			$filter=$args[1];
		else
			$filter="old";
		
		if(isset($args[2])&&$args[2])
			$id=$args[2];
		else
			$id="";
		
		$this->load->model('m_feed');
		
		$feeds=$this->m_feed->get_all_public_feeds($filter, $id, "", "");
		
		if(!$feeds)
			return array(array("error"=>"no_weibo"));
		else
			return $feeds;
	}
	
	/**
	 * 获得某用户的微博（待修改�&#65533;
	 * @param unknown_type $args
	 * @return string
	 */
	function get_user_weibo($args){
		
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array(array("error"=>"not_login"));
		
		if(isset($args[1])&&$args[1])
			$user_id=$args[1];
		else
			$user_id=$user_data['user_id'];
		
		if(isset($args[2])&&$args[2])
			$page=$args[2];
		else
			$page="";
		
		if(isset($args[3])&&$args[3])
			$page_count=$args[3];
		else
			$page_count="";
		
		$relationship=$this->get_users_relationship($user_data['user_id'], $user_id);
		
		
		$this->load->model('m_feed');
		
		$feeds=$this->m_feed->get_feeds_by_user_id($user_id,$relationship,$page,$page_count);
		
		if($feeds){
			return $feeds;
		}else{
			return array(array("error"=>"no_weibo"));
		}
		
	}
	
	/**
	 * 获得所有好友的状态（待修改）
	 * @param unknown_type $args
	 * @return string|Ambigous <boolean, multitype:, unknown>
	 */
	function get_friends_weibo($args){
		
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array(array("error"=>"not_login"));
		
		if(isset($args[1]))
			$page=$args[1];
		else
			$page="";
		
		if(isset($args[2]))
			$page_count=$args[2];
		else
			$page_count="";
		
		$this->load->model('m_friend');
		
		$friend_ids=$this->m_friend->get_user_friend_ids($user_data['user_id']);
		
		if(empty($friend_ids))
			return array(array("error"=>"no_friend"));
		
		$this->load->model('m_feed');
		
		$feeds=$this->m_feed->get_feeds_by_friends_ids($friend_ids,$page,$page_count);
		
		if(empty($feeds))
			return array(array("error"=>"no_weibo"));
		return $feeds;
		
		
	}
	
	/**
	 * 上传照片
	 * @param unknown_type $args
	 * @return string
	 */
	function upload_pirture($args){
		$this->load->model('m_album');
		$this->load->helper('file');
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array("error"=>"not_login");
		
		$data['album_id']=$args[1];
		
		if($user_data['user_id']!=$this->m_album->get_user_id_by_album_id($data['album_id'])){
			$data['album_id']=$this->m_album->get_user_album_id($user_data['user_id']);
		} 
		
		$data['user_id']=$user_data['user_id'];
		
		$data['picture_name']=$args[2];
		
		$data['picture_destription']=$args[3];
		
		$bits=base64_decode($args[4]);
		
		$data['file_size']=$args[5];
		
		$data['file_type']=$args[6];
		
		$data['create_time']=$this->current;
		
		switch($data['file_type']){
			case 1:$img_type="gif";break;
			case 2:$img_type="jpg";break;
			case 3:$img_type="png";break;
		}
		
		$data['file_name']=$data['user_id'].'_'.$data['create_time'].'.'.$img_type;
		
		
		write_file('upload/'.$data['user_id'].'/'.$data['file_name'], $bits);
		
		
		$picture_id=$this->m_album->add_picture($data);
		
		return array("success"=>"success");
		
	}
	
	/**
	 * 上传用户的头�&#65533;
	 * @param unknown_type $args
	 * @return string
	 */
	function upload_avatar($args){
		$this->load->model(array('m_album','m_user'));
		$this->load->helper('file');
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array("error"=>"not_login");
		
		$data['album_id']=$args[1];
		
		if($user_data['user_id']!=$this->m_album->get_user_id_by_album_id($data['album_id'])){
			$data['album_id']=$this->m_album->get_user_album_id($user_data['user_id']);
		} 
		
		$data['user_id']=$user_data['user_id'];
		
		$data['picture_name']=$args[2];
		
		$data['picture_destription']=$args[3];
		
		
		$bits=base64_decode($args[4]);
		
		$data['file_size']=$args[5];
		
		$data['file_type']=$args[6];
		
		$data['create_time']=$this->current;
		
		switch($data['file_type']){
			case 1:$img_type="gif";break;
			case 2:$img_type="jpg";break;
			case 3:$img_type="png";break;
		}
		
		$data['file_name']=$data['user_id'].'_'.$data['create_time'].'.'.$img_type;
		
		$file_path='upload/'.$data['user_id'].'/'.$data['file_name'];
		
	
		write_file($file_path, $bits);
		
		
		$picture_id=$this->m_album->add_picture($data);
		
		$data=array(
				'user_id'=>$user_data['user_id'],
				'meta_key'=>'user_avatar',
				'meta_value'=>base_url().$file_path
				);
		
		$this->m_user->add_user_meta($data);
		
		return array("success"=>"success");
	}
	
	/**
	 * 根据蓝牙数据获得刚刚身边用户数据
	 * @param unknown_type $args
	 * @return string|unknown
	 */
	function get_near_by_bluetooth_datas($args){
		
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array(array("error"=>"not_login"));
			
		$this->load->model('m_user');
		
		//如果传入了蓝牙数据，根据蓝牙数据增加蓝牙的发现关�&#65533;
		if(isset($args[1])&&$args[1]){
			$this->m_user->add_bluetooth_searchs_by_bluetooth_datas($user_data['bluetooth_id'],$args[1]);
		}
					
		//根据时间和用户获得发现蓝牙的ID
		$bluetooth_datas=$this->m_user->get_bluetooth_search_datas_by_search_time($user_data['bluetooth_id'],$this->current);
		
		//根据蓝牙ID获取用户数据
		$user_datas=$this->m_user->get_user_datas_by_bluetooth_search_datas($bluetooth_datas);
		
		//查看一下结果中user_id�&#65533;0的用户是否是地点，是就把地点信息加进�&#65533;
		$this->load->model('m_place');
		$this->load->model('m_event');
		foreach($user_datas as $key=>$value){
			if($value['user_id']==0){
				$place=$this->m_place->get_place_data_by_bluetooth_id($value['bluetooth_id']);
				if($place){
					$recent_event=$this->m_event->get_place_recent_event($place['place_id'], $this->current);
					if($recent_event){
						$place['recent_event']=$recent_event['event_name'];
					}else{
						$place['recent_event']="空闲状�&#65533;";
					}
					$place['place_avatar']=base_url().'upload/place_default.jpg';
					$place['last_search_time']=$value['last_search_time'];
					$place['search_count']=$value['search_count'];
					$value=$place;
					$value['type']='place';
				}else{
					$value['type']='user';
				}
			}else{
				$value['relationship']=$this->get_users_relationship($user_data['user_id'], $value['user_id']);
				$value['type']='user';
			}
			$user_datas[$key]=$value;
		}
		
		if($user_datas)
			return $user_datas;
		else
			return array(array("error"=>"no_user"));
	}
	
	/**
	 * 获得群组的列�&#65533;
	 * @param unknown_type $args
	 * @return string|unknown
	 */
	function get_group_datas_by_category($args){
		
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array(array("error"=>"not_login"));
		
		$this->load->model('m_group');
		
		$category=(isset($args[1])&&$args[1])?$args[1]:0;
		
		$page=isset($args[2])?$args[2]:0;
		
		$page_count=isset($args[3])?$args[3]:0;
		
		$group_datas=$this->m_group->get_group_datas_by_category($category,$page,$page_count);
		
		if($group_datas){
			foreach($group_datas as $key=>$value){
				$user_status=$this->m_group->get_user_status_by_user_id_and_group_id($user_data['user_id'], $value['group_id']);
				if($user_status){
					$value['is_in']=1;
					$value['is_confirmed']=$user_status['is_confirmed'];
					
				}else{
					$value['is_in']=0;
				}
				$group_datas[$key]=$value;
			}
			return $group_datas;
		}
		else 
			return array(array("error"=>"no_group"));
		
	}
	
	/**
	 * 根据用户的id获得用户的加入群组的资料
	 * @param unknown_type $args
	 * @return string|Ambigous <boolean, multitype:Ambigous <unknown, boolean, unknown> , multitype:unknown >
	 */
	function get_group_datas_by_user_id($args){
		
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array(array("error"=>"not_login"));
		$this->load->model('m_group');
		
		$user_id=$user_data['user_id'];
		
		$is_admin=isset($args[1])?$args[1]:'';
		
		$page=isset($args[2])?$args[2]:'';
		
		$page_count=isset($args[3])?$args[3]:'';
		
		$group_datas=array();
		
		$group_datas=$this->m_group->get_group_datas_by_user_id($user_id, $is_admin,$page,$page_count);
		
		if($group_datas)
			return $group_datas;
		else 
			return array(array("error"=>"no_group"));
	}

	function get_history_near_user_datas($args){
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array(array("error"=>"not_login"));
		
		if(isset($args[1])&&$args[1])
			$sort=$args[1];
		else
			$sort="search_count";
		
		if(isset($args[2])&&$args[2])
			$page=$args[2];
		else
			$page=1;
		
		if(isset($args[3])&&$args[3])
			$page_count=$args[3];
		else
			$page_count=20;
		
		$this->load->model('m_user');
		
		//根据蓝牙ID获得发现的蓝牙数�&#65533;
		$bluetooth_datas=$this->m_user->get_bluetooth_search_datas_by_bluetooth_id($user_data['bluetooth_id'],$sort,$page,$page_count);
		
		//根据蓝牙数据获取用户数据
		$user_datas=$this->m_user->get_user_datas_by_bluetooth_search_datas($bluetooth_datas);
		
		//查看一下结果中user_id�&#65533;0的用户是否是地点，是就把地点信息加进�&#65533;
		$this->load->model('m_place');
		$this->load->model('m_event');
		foreach($user_datas as $key=>$user_data){
			if($user_data['user_id']==0){
				$place=$this->m_place->get_place_data_by_bluetooth_id($user_data['bluetooth_id']);
				if($place){
					$recent_event=$this->m_event->get_place_recent_event($place['place_id'], $this->current);
					if($recent_event){
						$place['recent_event']=$recent_event;
					}
					$place['last_search_time']=$user_data['last_search_time'];
					$place['search_count']=$user_data['search_count'];
					$user_data=$place;
					$user_data['type']='place';
				}else{
					$user_data['type']='user';
				}
			}else{
				$user_data['type']='user';
			}
			$user_datas[$key]=$user_data;
		}
		
		if($user_datas)
			return $user_datas;
		else
			return array(array("error"=>"no_user"));
	}
	
	/**
	 * 添加一个事�&#65533;
	 * @param unknown_type $args
	 * @return string
	 */
	function add_event($args){
		
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array("error"=>"not_login");
		
		//事件名称
		$data['event_name']=$args[1];
		//事件描述
		$data['event_destription']=$args[2];
		//事件开始时间，从一�&#65533;0�&#65533;00开始的秒数
		$data['start_time']=$args[3];
		//事件结束时间，从一�&#65533;0:00开始秒�&#65533;
		$data['end_time']=$args[4];
		//创建者ID
		$data['user_id']=$user_data['user_id'];
		//绑定地点ID
		if(isset($args[5])&&$args[5])
			$data['place_id']=$args[5];
		$data['status_count']=0;
		//事件参与人数
		$data['member_count']=1;
		
		$this->load->model('m_event');
		$num=$this->m_event->add_event($data);
		if($num){
			$event_member['event_id']=$num;
			$event_member['user_id']=$user_data['user_id'];
			$event_member['is_admin']=1;
			$event_member['is_confirmed']=1;
			$this->m_event->add_event_member($event_member);
			$data['event_id']=$num;
			return $data;
		}else{
			return array("error"=>"unknown_error");
		}
		
	}
	
	function add_place($args){
		
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array("error"=>"not_login");
		
		$place['place_name']=$args[1];
		$place['place_destription']=$args[2];
		$place['place_category']=$args[3];
		$place['place_states']=1;
		$place['member_count']=1;
		
		if(isset($args[4])&&$args[4]){
			$wifi_mac=$args[4];
			if(!valid_bluetooth_mac($wifi_mac))
				return array("error"=>"invalid_wifi_mac");
			$wifi_mac=strtoupper($wifi_mac);
		}
		
		if(isset($args[5])&&$args[5]){
			$bluetooth_mac=$args[5];
			if(!valid_bluetooth_mac($bluetooth_mac))
				return array("error"=>"invalid_bluetooth_mac");
			$bluetooth_mac=strtoupper($bluetooth_mac);
			
			$this->load->model('m_user');
			$bluetooth_id=$this->m_user->get_user_id_by_bluetooth_mac($bluetooth_mac);
			
			if($bluetooth_id){
				return array("error"=>"existing_bluetooth_mac");
			}else{
				$bluetooth_id=$this->m_user->add_bluetooth(array(
						'user_id'=>0,
						'bluetooth_mac'=>$bluetooth_mac,
						'bluetooth_name'=>"",
						'create_time'=>$this->current
				));
			}
			$place['bluetooth_id']=$bluetooth_id;
		}
		$place['create_time']=$this->current;
		
		$this->load->model('m_place');
		$num=$this->m_place->add_place($place);
		if($num){
			$place_member['place_id']=$num;
			$place_member['user_id']=$user_data['user_id'];
			$place_member['is_admin']=1;
			$place_member['is_confirmed']=1;
			$place_member['create_time']=$this->current;
			$this->m_place->add_place_member($place_member);
			$place['place_id']=$num;
			return $place;
		}else{
			array("error"=>"unknown_error");
		}
	}
	
	function add_place_member($args){
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array("error"=>"not_login");
		$data['place_id']=$args[1];
		$data['user_id']=$user_data['user_id'];
		$data['is_admin']=0;
		$data['is_confirmed']=0;
		$data['create_time']=$this->current;
		
		$this->load->model('m_place');
		$num=$this->m_place->add_place_member($data);
		
		if($num){
			$data['place_member_id']=$num;
			$this->m_place->increase_place_member_count($data['place_id']);
			return array("success"=>"success");
		}else{
			return array("error"=>"unknown_error");
		}
	}
	
	function get_place_datas_by_category($args){
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array(array("error"=>"not_login"));
		
		$this->load->model('m_place');
		
		$category=(isset($args[1])&&$args[1])?$args[1]:0;
		
		$page=(isset($args[2])&&$args[2])?$args[2]:0;
		
		$page_count=(isset($args[3])&&$args[3])?$args[3]:0;
		
		$place_datas=$this->m_place->get_place_datas_by_category($category,$page,$page_count);
		
		
		if($place_datas){
			$this->load->model('m_event');
			foreach($place_datas as $key=>$value){
				$recent_event=$this->m_event->get_place_recent_event($value['place_id'], $this->current);
				if($recent_event){
					$value['recent_event']=$recent_event;
					$place_datas[$key]=$value;
				}
			}
			return $place_datas;
		}else
			return array(array("error"=>"no_place"));
	}
	
	function get_place_recent_members($args){
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array(array("error"=>"not_login"));
		
		$this->load->model('m_place');
		
		$place_id=$args[1];
		
		$members=$this->m_place->get_recent_place_members($place_id);
		
		if($members){
			$this->load->model('m_user');
			$members=$this->m_user->get_user_datas_by_user_ids($members);
			return $members;
		}else
			return array(array("error"=>"no_member"));
	}
	
	function get_group_members_by_group_id($args){
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array(array("error"=>"not_login"));
		
		$this->load->model('m_group');
		
		$group_id=$args[1];
		
		$members=$this->m_group->get_group_member_datas_by_group_id($group_id,$user_data['user_id']);
		
		if($members){
			return $members;
		}else
			return array(array("error"=>"no_member"));
	}
	
	function get_group_data_by_group_id($args){
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array(array("error"=>"not_login"));
		$this->load->model('m_group');
		
		$group_id=$args[1];
		
		$group_data=$this->m_group->get_group_data_by_group_id($group_id);
		
		if($group_data){
			$members=$this->m_group->get_group_member_datas_by_group_id($group_id,$user_data['user_id']);
			if($members)
				$group_data['group_members']=$members;
			return $group_data;
		}
		else
			return array("error"=>"no_group");
		
	}
	
	function comment_weibo($args){
		
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array("error"=>"not_login");
		
		$comment['user_id']=$user_data['user_id'];
		$comment['comment_type']='weibo';
		$comment['comment_type_id']=$args[1];
		$comment['comment_content']=$args[2];
		$comment['create_time']=$this->current;
		
		$this->load->model('m_comment');
		
		$num=$this->m_comment->add_comment($comment);
		
		if($num){
			
			//增加微博评论�&#65533;
			$this->load->model('m_feed');
			$feed=$this->m_feed->get_feed_by_feed_id($comment['comment_type_id']);
			$this->m_feed->increase_comment_count($feed['feed_id']);
			
			//给被评论人通知
			$this->send_notify($user_data['user_id'], $feed['user_id'], 'comment_weibo',$feed['feed_id'],$user_data['display_name'].'评论了您的微�&#65533;');
			
			$comment['comment_id']=$num;
			//返回此条评论的具体内�&#65533;
			return $comment;
		}else{
			return array("error"=>"unknown_error");
		}
	}
	
	function say_hello($args){
		$user_data=$this->get_user_by_token($args);
		return $user_data;
	}
	
	//和终端保持联系，要获得最新的消息，通知，或者@内容，或者评�&#65533;
	function user_keep_alive($args){
		//获得用户的令�&#65533;
		$token=$args[0];
		
		//从缓存中获得用户资料
		$user_data=$this->get_user_by_token($token);
		
		if(!$user_data)
			return array(array("error"=>"not_login"));
		
		//获得用户未读的通知
		$this->load->model('m_notify');
		
	}
	
	/**
	 * 根据令牌获得用户信息
	 * @param unknown_type $token
	 * @return mixed|boolean
	 */
	function get_user_by_token($token){
		//$this->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));
		$this->load->driver('cache',array('adapter' => 'file'));
		$this->load->model('m_online');
		
		$user_data=$this->cache->get($token);
		if($user_data){
			
			//获得token内容
			$user_data=unserialize($user_data);
			
			$this->load->model('m_user');
			
			$new_user_data=$this->m_user->get_user_data_by_user_id($user_data['user_id']);
			$user_data=array_merge($user_data,$new_user_data);
			
			//更新用户最后活动时�&#65533;
			$this->update_last_activity($user_data['user_id'], $this->current);
			
			//更新在线�&#65533;
			$data=array(
					'user_id'=>$user_data['user_id'],
					'display_name'=>$user_data['display_name'],
					'create_time'=>$this->current,
					'online_type'=>'电脑在线'
			);
			$this->m_online->add_online($data);
			
			$user_data=serialize($user_data);
			//更新token时间
			$this->cache->save($token,$user_data,3000000);
			return unserialize($user_data);
		}
		else
			return FALSE;
	}
	
	/**
	 * 更新用户最后活动时�&#65533;
	 * @param unknown_type $user_id
	 * @param unknown_type $last_activity
	 */
	function update_last_activity($user_id,$last_activity,$latest_update=""){
		$this->load->model('m_user');
		$this->m_user->update_last_activity($user_id,$last_activity);
		if(!empty($latest_update)){
			$data['user_id']=$user_id;
			$data['meta_key']='latest_update';
			$data['meta_value']=$latest_update;
			$this->m_user->add_user_meta($data);
		}
			
	}
	
	/**
	 * 创建用户相册
	 * @param unknown_type $user_id
	 * @param unknown_type $album_name
	 * @param unknown_type $album_password
	 * @return unknown
	 */
	function create_album($user_id,$album_name,$album_destription,$album_password=NULL){
		$this->load->model('m_album');
		$data['album_name']=$album_name;
		$data['user_id']=$user_id;
		$data['album_destription']=$album_destription;
		$data['create_time']=time();
		$data['picture_count']=0;
		$data['album_visible']=0;
		if(!is_null($album_password))
			$data['album_password']=md5($album_password);
		
		$num=$this->m_album->add_album($data);
		return $num;
	}
	
	/**
	 * 给用户发送一条通知
	 * @param unknown_type $from_id
	 * @param unknown_type $to_id
	 * @param unknown_type $notify_type
	 * @param unknown_type $notify_content
	 */
	function send_notify($from_id,$to_id,$notify_type,$notify_type_id,$notify_content){
		$this->load->model('m_notify');
		$data['from_id']=$from_id;
		$data['to_id']=$to_id;
		$data['notify_type']=$notify_type;
		$data['notify_type_id']=$notify_type_id;
		$data['notify_content']=$notify_content;
		$data['is_read']=0;
		$data['create_time']=$this->current;
		
		$num=$this->m_notify->add_notify($data);
		return $num;
	}
	
	/**
	 * 查看某通知
	 * @param unknown_type $notify_id
	 */
	function igniore_notify($notify_id){
		$this->load->model('m_notify');
		return $this->m_notify->ignore_notify($notify_id);
	}

	/**
	 * 根据两用户ID获得用户间的关系
	 * @param unknown_type $from_id
	 * @param unknown_type $to_id
	 */
	function get_users_relationship($from_id,$to_id){
		if($from_id==$to_id)
			return 'self';
		$this->load->model('m_friend');
		if($this->m_friend->get_friendship_id_by_user_id($from_id, $to_id))
			return 'friend';
		else
			return 'stranger';
	}
	
	
}

?>