<?php
//引入公共模块
require("foundation/module_users.php");
require("foundation/aintegral.php");
require("api/base_support.php");
require("foundation/module_mypals.php");

//引入语言包
$re_langpackage=new reglp;
$data = array();

//数据表定义区
$t_users=$tablePreStr."users";
$t_online=$tablePreStr."online";
$t_pals_def_sort=$tablePreStr."pals_def_sort";
$t_pals_sort=$tablePreStr."pals_sort";
$t_mypals=$tablePreStr."pals_mine";
$t_invite_code=$tablePreStr."invite_code";
$t_user_activation=$tablePreStr."user_activation";

$dbo=new dbex;
dbtarget('r',$dbServs);

//ajax校验email和验证码
if(get_argg('ajax')==1){
	$user_email=short_check(get_argg("user_email"));
	$user_vericode=get_argg("veriCode");
	if($user_email){
		$sql="select user_id from $t_users where user_email='$user_email'";
		$user_info=$dbo->getRow($sql);
		if($user_info){
			echo $re_langpackage->re_rep_mail;exit;
		}
		if(!checkmail($user_email)) {
		    echo $re_langpackage->re_right_email;exit;
		}
	}

	if($user_vericode){
		if(strtolower($_SESSION['verifyCode'])!=strtolower($user_vericode)){
			echo $re_langpackage->re_wrong_val;exit;
		}
	}
	exit;
}

function checkmail($user_email){   //验证电子邮件地址
	if(preg_match("/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/",$user_email))
		return checkmail_hack($user_email);
	else
		return false;
}
//黑名单后缀
function checkmail_hack($user_email){
	$arr = explode(',', ".con,.con.cn,.cnn,.om,.on");
	foreach($arr as $end){
		if(checkmail_hack_one($user_email,$end)) return false;
	}
	return true;
}

function checkmail_hack_one($str,$end){
	return (substr($str, 0-strlen($end)) == $end);
}

if(!checkmail(get_argp("user_email"))) {
    action_return(0,$re_langpackage->re_right_email,"-1");
}

//if(strlen(get_argp("user_name"))<4){
//   action_return(0,$re_langpackage->re_right_name,"-1");
//}

if(strlen(get_argp("user_repassword"))<6){
	  action_return(0,$re_langpackage->re_pass_limit,"-1");
}
//$user_name=short_check(get_argp("user_name"));
$user_pws=md5(get_argp("user_password"));
$user_sex=intval(get_argp("user_sex"));
$user_email=short_check(get_argp("user_email"));
$_arr = explode("@",$user_email);
$user_name = $_arr[0];
$is_pass=1;
$user_vericode=get_argp("veriCode");
$invite_fromuid=0;
if(get_session('InviteFromUid')){
	  $invite_fromuid=get_session('InviteFromUid');
}

if(strtolower($_SESSION['verifyCode'])!=strtolower($user_vericode)){
	//action_return(0,$re_langpackage->re_wrong_val,"-1");//验证码
}

unset($_SESSION['verifyCode']);

$data['user_email'] = $user_email;
//读取数据
$sql="select user_id from $t_users where user_email='$user_email'";
$user_info=$dbo->getRow($sql);
$sort_rs = api_proxy("pals_sort_def");

if($user_info){
	action_return(0,$re_langpackage->re_rep_mail,"-1");
}else{
//检测邀请码
	if($inviteCode){
		$is_check=array();
		$invite_code=short_check(get_argp('invite_code'));
		if($invite_code==''){
			action_return(0,'请填写邀请码',"-1");exit;
		}
		$sql="select id from $t_invite_code where code_txt='$invite_code'";
		$is_check=$dbo->getRow($sql);
		if(empty($is_check)){
			action_return(0,'邀请码不正确或已经失效',"-1");exit;
		}
		$sql="delete from $t_invite_code where code_txt='$invite_code'";
		$dbo->exeUpdate($sql);
	}

	//写入数据
	$user_ico=($user_sex==0)?"skin/$skinUrl/images/d_ico_0_small.gif":"skin/$skinUrl/images/d_ico_1_small.gif";
	dbtarget('w',$dbServs);
	$sql="insert into $t_users (user_name,user_pws,user_sex,user_email,user_add_time,user_ico,invite_from_uid,is_pass,lastlogin_datetime,birth_year , birth_month , birth_day ,login_ip )"
					." values('$user_name','$user_pws',$user_sex,'$user_email','".constant('NOWTIME')."','$user_ico',$invite_fromuid,$is_pass,'".constant('NOWTIME')."','','','','$_SERVER[REMOTE_ADDR]')";

	if(!$dbo->exeUpdate($sql)){
		action_return(0,$re_langpackage->re_reg_false,"-1");
	}

	$user_id=mysql_insert_id();
	$now_time=time();

	$sql="insert into $t_online (user_id,user_name,user_sex,user_ico,active_time,hidden) values ($user_id,'$user_name',$user_sex,'$user_ico','$now_time',0)";
	$dbo->exeUpdate($sql);

	foreach($sort_rs as $rs){
		$sort_id=$rs['id'];
		$sort_name=$rs['name'];
		$sql="insert into $t_pals_sort ( name , user_id ) values ( '$sort_name' , $user_id )";
		$dbo->exeUpdate($sql);
	}

	if($invite_fromuid){
		increase_integral($dbo,$int_invited,$invite_fromuid);
		//取得介绍人的资料信息
		$user_row = api_proxy("user_self_by_uid","user_id,user_name,user_sex,user_ico,palsreq_limit,cn_name",$invite_fromuid);
		if($user_row){
			$touser_id=$user_row['user_id'];
			$touser_name=$user_row['user_name'];
			$touser_sex=$user_row['user_sex'];
			$touser_ico=$user_row['user_ico'];
			$touser_pals_limit=$user_row['palsreq_limit'];
			$touser_cnname = $user_row['cn_name'];
		}
		//if($touser_pals_limit==0){
			$sql="insert into $t_mypals (user_id,pals_id,pals_name,pals_sex,add_time,pals_ico,accepted,cn_name) " .
				"values ($user_id,$invite_fromuid,'$touser_name','$touser_sex','".constant('NOWTIME')."','$touser_ico',1,'$touser_cnname')";
			$dbo->exeUpdate($sql);
			set_sess_mypals($invite_fromuid);
			//提示自己
		set_sess_userid($user_id);
		set_sess_usersex($user_sex);
		set_sess_username($user_name);
		set_sess_userico($user_ico);
		
			api_proxy("message_set_remind",$user_id,"{$touser_name}的名片已添加进您的名片夹","{$invite_fromuid}",0,1);
			
			$sql="insert into $t_mypals (user_id,pals_id,pals_name,pals_sex,add_time,pals_ico,accepted,cn_name) " .
				"values ($invite_fromuid,$user_id,'$user_name','$user_sex','".constant('NOWTIME')."','$user_ico',1,'$user_name')";
			$dbo->exeUpdate($sql);
			//提示对方
			api_proxy("message_set_remind",$invite_fromuid,"{$user_name}的名片已添加进您的名片夹","{$user_id}",0,1);
		//}
	}



	//不需要激活时直接添加session
	if($mailActivation == 0){
		set_sess_userid($user_id);
		set_sess_usersex($user_sex);
		set_sess_username($user_name);
		set_sess_userico($user_ico);
		set_sess_online('0');
		
set_session('pals_count',api_proxy("pals_self_count_by_uid",$user_id));
set_session('blog_count',api_proxy("blog_self_count_by_uid",$user_id));
set_session('msg_count',api_proxy("scrip_inbox_count_by_uid",$user_id));
		
//自动添加小秘书。692
$invite_fromuid = 692;
$user_row = api_proxy("user_self_by_uid","user_id,user_name,user_sex,user_ico,palsreq_limit,cn_name",$invite_fromuid);
		if($user_row){
			$touser_id=$user_row['user_id'];
			$touser_name=$user_row['user_name'];
			$touser_sex=$user_row['user_sex'];
			$touser_ico=$user_row['user_ico'];
			$touser_pals_limit=$user_row['palsreq_limit'];
			$touser_cnname = $user_row['cn_name'];
		}
		//if($touser_pals_limit==0){
			$sql="insert into $t_mypals (user_id,pals_id,pals_name,pals_sex,add_time,pals_ico,accepted,cn_name) " .
				"values ($user_id,$invite_fromuid,'$touser_name','$touser_sex','".constant('NOWTIME')."','$touser_ico',1,'$touser_cnname')";
			$dbo->exeUpdate($sql);
			
			$mypals=getMypals($dbo,$user_id,$t_mypals);
			set_sess_mypals($mypals);
			//提示自己
//		set_sess_userid($user_id);
//		set_sess_usersex($user_sex);
//		set_sess_username($user_name);
//		set_sess_userico($user_ico);
		
			api_proxy("message_set_remind",$user_id,"{$touser_name}的名片已添加进您的名片夹","{$invite_fromuid}",0,1);
			
			$sql="insert into $t_mypals (user_id,pals_id,pals_name,pals_sex,add_time,pals_ico,accepted,cn_name) " .
				"values ($invite_fromuid,$user_id,'$user_name','$user_sex','".constant('NOWTIME')."','$user_ico',1,'$user_name')";
			$dbo->exeUpdate($sql);
			//提示对方
			api_proxy("message_set_remind",$invite_fromuid,"{$user_name}的名片已添加进您的名片夹","{$user_id}",0,1);
		//}
		
?>
	<script type='text/javascript'>
		var login_time=new Date();
		login_time.setTime(login_time.getTime() +3600*250 );
		document.cookie="IsReged=Y;expires="+ login_time.toGMTString();
	  </script>
<?php
	//需要激活时的操作
	}
	
?>
	<script type='text/javascript'>
		location.href='modules.php?app=set_mycard&is_finish=1';
	</script>
<?php
	
}
?>