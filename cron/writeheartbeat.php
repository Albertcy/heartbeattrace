<?php
/**
 *
 * �û�������¼
 */
$DontCheckLogin = true;
$DontValidateSite = true;

$sessionId = addslashes($_REQUEST['usersessionid']);
session_id($sessionId);

include_once ("tglobal.lib");
include_once ("theartbeathandle.lib");

// ��������
$hbHandle = new HeartBeatHandle();
$hbHandle->updateHeartBeatInfo($sessionId);

// ��¼������Ա
$hbHandle->tagOnlineUser();
