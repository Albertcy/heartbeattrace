<?php

/**
 * 用户登录心跳记录控件
 * 
 * @author albert
 *
 */
class HeartBeatHandle
{

    /**
     * 是否启用 心跳统计
     *
     * @return boolean
     */
    public function isEnable()
    {
        return true;
    }

    /**
     * 获取用户浏览器信息
     *
     * @return int
     */
    public function getBrowserType()
    {
        $type = $this->getUserBrowserInfo('browser');
        return false !== strpos($type, 'IE') ? $this->browserTypeMaps[$type . $this->getBrowserVer()] : $this->browserTypeMaps[$type];
    }

    /**
     * 获取浏览器安装平台信息
     *
     * @return multitype:string
     */
    public function getPlatformType()
    {
        return $this->platformTypeMaps[$this->getUserBrowserInfo('platform')];
    }

    /**
     * 判断ie
     *
     * @return boolean
     */
    public function isIE()
    {
        return strpos($this->getUserBrowserInfo('browser'), 'IE') !== false;
    }

    /**
     * 获取浏览器版本
     *
     * @return string
     */
    public function getBrowserVer()
    {
        return $this->getUserBrowserInfo('version');
    }

    /**
     * 判断windows
     *
     * @return boolean
     */
    public function isWindows()
    {
        return strpos($this->getUserBrowserInfo('platform'), 'Win') !== false;
    }

    /**
     * 获取离线用户信息
     *
     * @return null
     */
    public function getOfflineUsers()
    {
        global $gblDB;
        
        $sql = 'SELECT login_sys, session_id, his_id
            FROM tc_active_user 
            WHERE heartbeat_time <' . (time() - self::HEART_BEAT_INTERVAL) . '
            AND heartbeat_time <> 0
            AND platform_type in (' . implode(',', array(
            self::PLATFORM_TYPE_WINXP,
            self::PLATFORM_TYPE_WIN7,
            self::PLATFORM_TYPE_WIN8
        )) . ')
            AND browser_type in (' . implode(',', array(
            self::BROWSER_TYPE_FF,
            self::BROWSER_TYPE_CHROME,
            self::BROWSER_TYPE_OPERA,
            self::BROWSER_TYPE_IE11
        )) . ')';
        
        $rs = $gblDB->query($sql);
        $temp = array();
        if ($rs) {
            while ($rs->fetchRecord()) {
                $sessionId = $rs->getFieldValueByName('session_id');
                $temp[$sessionId]['login_sys'] = $rs->getFieldValueByName('login_sys');
                $temp[$sessionId]['his_id'] = $rs->getFieldValueByName('his_id');
            }
            $rs->close();
        }
        return $temp;
    }

    /**
     * 从系统登出
     *
     * @param array $userInfos            
     * @return null
     */
    public function doLogou($userInfos = array())
    {
       // do  logout
    }

    /**
     * 删除tc_active_user 中对应数据
     *
     * @param $sessionIds array            
     * @return null
     */
    public function deleteActiveUser($sessionIds)
    {
        global $gblDB;
        if (empty($sessionIds)) {
            return false;
        }
        if (! is_array($sessionIds)) {
            $sessionIds = (array) $sessionIds;
        }
        
        $sql = 'DELETE FROM  tc_active_user 
            WHERE session_id in (' . implode(',', $sessionIds) . ')';
        $gblDB->execute($sql);
    }

    /**
     * 返回 执行计划脚本时间间隔
     *
     * @return number
     */
    public function getcheckHBCronInterval()
    {
        return 600;
    }
    
    /**
     * 检测服务是否运行
     * @return boolean
     */
    public function checkWSService()
    {
        return strpos(exec('tasklist | findstr "websoc"'), 'websocket.exe') !== false;
    }
    
    /**
     * 运行服务
     * 
     * @return null
     */
    public function runWSService()
    {
       pclose(popen('start /B cmd /C "websocket.exe >NUL 2>NUL"', 'r'));
    }

    /**
     * 获取用户登录环境信息 用户浏览器 操作系统等
     *
     * @return int
     */
    private function getUserBrowserInfo($arg)
    {
        empty(self::$browserInfo) ? self::$browserInfo = get_browser($_SERVER['HTTP_USER_AGENT'], true) : '';
        return self::$browserInfo[$arg];
    }

    /**
     * 写入用户心跳信息
     *
     * @param $sessionId sessionid            
     * @return null
     */
    public function updateHeartBeatInfo($sessionId)
    {
        if (empty($sessionId)) {
            return false;
        }
        global $gblDB;
        $sql = "UPDATE tc_active_user SET heartbeat_time = " . time() . " WHERE session_id = N'{$sessionId}'";
        $gblDB->execute($sql);
    }

    /**
     * 记录 在线用户 检测当前有效的用户
     *
     * @return null
     */
    public function tagOnlineUser()
    {
        $mTool = new memcacheTool();
        $mTool->key_pre = 'hb_';
        $mTool->set('onLineUsers', json_encode($this->getOnlineUsers()));
    }

    /**
     * 获取在线用户 session id
     *
     * @return array
     */
    public function getOnlineUsers()
    {
        global $gblDB;
        
        $sql = 'SELECT login_sys, session_id, his_id
            FROM tc_active_user';
        
        $rs = $gblDB->query($sql);
        $temp = array();
        if ($rs) {
            while ($rs->fetchRecord()) {
                $temp[$rs->getFieldValueByName('session_id')] = $rs->getFieldValueByName('login_sys');
            }
            $rs->close();
        }
        return $temp;
    }

    /**
     * 获取用户操作系统信息
     *
     * @return int
     */
    private function getUserPlatformType()
    {}

    /**
     * 用户浏览器信息
     *
     * @var array
     */
    private static $browserInfo = array();

    /**
     * 浏览器类型映射
     *
     * @var array
     */
    private $browserTypeMaps = array(
        'Chrome' => self::BROWSER_TYPE_CHROME,
        'Firefox' => self::BROWSER_TYPE_FF,
        'IE' => self::BROWSER_TYPE_IE,
        'IE8.0' => self::BROWSER_TYPE_IE8,
        'IE9.0' => self::BROWSER_TYPE_IE9,
        'IE10.0' => self::BROWSER_TYPE_IE10,
        'IE11.0' => self::BROWSER_TYPE_IE11,
        'Opera' => self::BROWSER_TYPE_OPERA
    );

    private $platformTypeMaps = array(
        'WinXP' => self::PLATFORM_TYPE_WINXP,
        'Win7' => self::PLATFORM_TYPE_WIN7,
        'Win8' => self::PLATFORM_TYPE_WIN80,
        'Win8.1' => self::PLATFORM_TYPE_WIN8
    );

    const HEART_BEAT_INTERVAL = 30;
    
    // xp winserver 2003
    const PLATFORM_TYPE_WINXP = 1;
    // win7 winserver 2008
    const PLATFORM_TYPE_WIN7 = 2;
    // win8 winserver 2012
    const PLATFORM_TYPE_WIN8 = 3;
    // win8.0
    const PLATFORM_TYPE_WIN80 = 4;

    const BROWSER_TYPE_IE = 1;

    const BROWSER_TYPE_IE7 = 17;

    const BROWSER_TYPE_IE8 = 18;

    const BROWSER_TYPE_IE9 = 19;

    const BROWSER_TYPE_IE10 = 110;

    const BROWSER_TYPE_IE11 = 111;

    const BROWSER_TYPE_FF = 2;

    const BROWSER_TYPE_CHROME = 3;

    const BROWSER_TYPE_OPERA = 4;
} 