<?php
$DontCheckLogin = true;
$DontValidateSite = true;
$gblOrgID = 0;

include_once ("tglobal.lib");
include_once ("tlogin_u8_dbinfo.lib");
include_once ("theartbeathandle.lib");
$HBhandle = new HeartBeatHandle();

$dbinfo = new LoginU8DbInfo();
$cDatabases = $dbinfo->getU8DBInfo();

foreach ($cDatabases as $cDatabase) {
    $gblObj = TGBL_getObject();
    $gblObj->clearDataCache();
    $errno = $gblObj->LoginWithUser($cDatabase);
    if (! $errno) {
        continue;
    }
    
    // 检测接受心跳服务是否运行
    if (! $HBhandle->checkWSService()) {
        $HBhandle->runWSService();
    } else {
        $userInfos = $HBhandle->getOfflineUsers();
        // 清楚登录
        $HBhandle->doLogoutU8($userInfos);
        // 清楚active_user表
        $HBhandle->deleteActiveUser(array_keys($userInfos));
        
        // Set next scan time
        $sql = 'SELECT bg_server_ip FROM tc_background_task WHERE bg_task_id=' . $ID;
        $rs = $gblDB->query($sql);
        if ($rs) {
            if ($rs->fetchRecord()) {
                $svr = $rs->getFieldValueByName('bg_server_ip');
            }
            $rs->close();
        }
    }
    
    $nexttime = time() + $HBhandle->getcheckHBCronInterval();
    $timestr = date('Y-m-d H:i:s', $nexttime);
    $sql = 'UPDATE tc_background_task SET plan_start_time=' . $gblDB->TDB_ToDateByString($timestr) . ' WHERE bg_task_id=' . $ID;
    $gblDB->execute($sql);
    if (! isEmptyString($svr)) {
        $fp = @fsockopen($svr, $gblObj->getBGPort(), $errno, $errstr, 1);
        if ($fp) {
            $str = TDatadictCache::PreSendInt(5);
            $str .= TDatadictCache::PreSendStr('0');
            $str .= TDatadictCache::PreSendStr($ID . '');
            $str .= TDatadictCache::PreSendInt(1);
            $str .= TDatadictCache::PreSendLong($nexttime);
            $str .= TDatadictCache::PreSendStr('/background/timeoutlogin.php?ID=' . $ID);
            
            fwrite($fp, $str, strlen($str));
            
            fclose($fp);
        } else {
            $sql = "UPDATE tc_background_task SET bg_server_ip='' WHERE bg_task_id=" . $ID;
            $gblDB->execute($sql);
        }
    }
}