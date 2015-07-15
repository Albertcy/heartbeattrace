<?php

include_once ("theartbeathandle.lib");
$HBhandle = new HeartBeatHandle();
    // 쇱꿎쌈肝懃契륩蛟角뤠頓契
    if (! $HBhandle->checkWSService()) {
        $HBhandle->runWSService();
    } else {
        $userInfos = $HBhandle->getOfflineUsers();
        // 헌뇝되쩌
        $HBhandle->doLogoutU8($userInfos);
        // 헌뇝active_user깊
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
