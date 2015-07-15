<?php

include_once ("theartbeathandle.lib");
$HBhandle = new HeartBeatHandle();
    // 检测后台服务是否启用
    if (! $HBhandle->checkWSService()) {
        $HBhandle->runWSService();
    } else {
        $userInfos = $HBhandle->getOfflineUsers();
        // 登出系统
        $HBhandle->doLogout($userInfos);
        // 清楚当前活动用户
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
}
