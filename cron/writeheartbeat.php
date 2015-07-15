<?php
/**
 *
 * 用户心跳记录
 */
$DontCheckLogin = true;
$DontValidateSite = true;

$sessionId = addslashes($_REQUEST['usersessionid']);
session_id($sessionId);

include_once ("tglobal.lib");
include_once ("theartbeathandle.lib");

// 处理心跳
$hbHandle = new HeartBeatHandle();
$hbHandle->updateHeartBeatInfo($sessionId);

// 记录在线人员
$hbHandle->tagOnlineUser();
