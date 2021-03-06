<?php
//全局的$api_includes用来管理文件的唯一调用
global $api_includes;
if(!isset($api_path))$api_path=dirname(__file__);
if(!isset($api_includes))$api_includes=array();
//开通系统的支持API的基本文件支持
if(!isset($api_includes['api']))
{
	//此处不要改动
	include($api_path."/../configuration.php");
	if(!isset($api_includes['session']))
	{
		if(!isset($_SESSION))session_start();
		include_once($webRoot."foundation/fsession.php");
		$api_includes['session']=true;
	}
	include_once($webRoot.$baseLibsPath."conf/dbconf.php");
	include_once($webRoot.$baseLibsPath."fdbtarget.php");
	include_once($webRoot.$baseLibsPath."libs_inc.php");
	include_once($webRoot.$baseLibsPath."cdbex.class.php");
	include_once($webRoot."foundation/freq_filter.php");
	include_once($api_path."/Check_MC.php");
	$api_includes['api']=true;
}
?>