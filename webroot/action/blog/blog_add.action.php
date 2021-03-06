<?php
  //引入模块公共方法文件
  require("foundation/fcontent_format.php");
  require("api/base_support.php");
  require("foundation/aanti_refresh.php");
  require("foundation/aintegral.php");
  require("foundation/fplugin_form.php");
  require("foundation/ftag.php");

	//引入语言包
	$b_langpackage=new bloglp;

	//变量取得
  $ulog_title=short_check(get_argp("blog_title"));
  $ulog_sort=intval(get_argp("blog_sort_list"));
  $privacy=short_check(get_argp("privacy"));
  $ulog_txt=big_check(get_argp("CONTENT"));
	$user_id=get_sess_userid();
	$user_name=get_sess_usercnname();
	$uico_url=get_sess_userico();//用户头像
	$blog_sort_name=short_check(get_argp('blog_sort_name'));
	$tag=short_check(get_argp('tag'));
  $can_comment = short_check(get_argp("can_comment"));
  $can_comment = $can_comment=="on"?1:0;
	//防止重复提交
	antiRePost($ulog_title);
	
	if($ulog_title==''){
		action_return(1,'',-1);exit;
	}
	if($privacy==''){
		$privacy = "!all";
	}
	//数据表定义区
	$t_blog=$tablePreStr."blog";

	$dbo = new dbex;
	//读写分离定义函数
	dbtarget('w',$dbServs);
	increase_integral($dbo,$int_blog,$user_id);
  plugin_submit_form();//plugins表单分发
	$sql="insert into $t_blog (user_id,log_title,log_sort,log_content,add_time,edit_time,log_sort_name,user_name,user_ico,privacy,`tag`,`can_comment`) values ($user_id,'$ulog_title',$ulog_sort,'$ulog_txt','".constant('NOWTIME')."','".constant('NOWTIME')."','$blog_sort_name','$user_name','$uico_url','$privacy','$tag',$can_comment)";
	if(!$dbo->exeUpdate($sql)){
		action_return(0,$b_langpackage->b_add_fal,'-1');exit;
	}
	$ublog_id=mysql_insert_id();

	if($privacy==''){
		$title=$b_langpackage->b_write_new_log."<a href=\"home.php?h=".$user_id."&app=blog&id=".$ublog_id."\" target='_blank'>".$ulog_title."</a>";
		$content=get_lentxt($ulog_txt);
		$is_suc=api_proxy("message_set",$ublog_id,$title,$content,3,0);
	}else if($privacy == '!all'){
		
	}else{
		//Logs::addLog("privacy:$privacy");
		//Logs::addLog("id,title,content:$ublog_id,$ulog_title,$ulog_txt,");
		$title=$b_langpackage->b_write_new_log."<a href=\"home.php?h=".$user_id."&app=blog&id=".$ublog_id."\" target=\"_blank\">".$ulog_title."</a>";
		$content=get_lentxt($ulog_txt);
		api_proxy("message_set_remind_privacy",$privacy,$title,$content,1,0);
	}
	
	//标签功能
	tag_add($tag,$ublog_id,0);
	action_return(1,'','');
?>