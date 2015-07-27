// websocket project main.go
package main

import (
	"code.google.com/p/go.net/websocket"
	"encoding/json"
	"fmt"
	"github.com/bradfitz/gomemcache/memcache"
	"log"
	"net/http"
	"net/url"
	"project/logs"
	"time"
)

// 节流
var throtting = make(map[string]int64)

// 记录访问信息
var bLogMsg = true

// 检测离线用户
var bCheckOfflineUser = false

// 回显
var bEcho = false

/**
* websocket 回调方法
 */
func Echo(ws *websocket.Conn) {
	var err error
	var msg string
	var curSessionId string
	const TIME_INTERVAL = 15

	for {
		var reply string

		// 错误处理 链接断开 ，客户端刷新
		if err = websocket.Message.Receive(ws, &reply); err != nil {
			logError(err, "Can't receive")
			delete(throtting, curSessionId)
			break
		}
		curSessionId = reply
		if bLogMsg {
			logMsg("Received back from client: " + reply)
		}

		// 写入节流控制

		timeStamp, ok := throtting[reply]
		if ok && (time.Now().Unix()-timeStamp < TIME_INTERVAL) {
			continue
		}

		delete(throtting, curSessionId)
		throtting[reply] = time.Now().Unix()
		wirteHeartBeat(reply)

		// session 超时用户停止发送心跳

		if bCheckOfflineUser && !checkOnlineUsers(reply) {
			if bLogMsg {
				msg = "Sending to client: " + "Received:  " + reply
				logMsg(msg)
			}
			if err = websocket.Message.Send(ws, "offline"); err != nil {
				logError(err, "Can't send")
				break
			} else {
				delete(throtting, curSessionId)
			}
		}

	}
}

// 检测session 是否有效
func checkOnlineUsers(sessionId string) bool {
	mc := memcache.New("127.0.0.1:11211")
	data, err := mc.Get("hb_onLineUsers")

	if err == nil && string(data.Value) != "[]" {
		var i interface{}
		json.Unmarshal(data.Value, &i)
		m := i.(map[string]interface{})

		if sessionId != "" {
			_, ok := m[sessionId]
			if !ok {
				return false
			}
		}
	} else {

		logError(err, " memcache error occur")
	}

	return true
}

/**
* 调用脚本写入用户心跳值
* @param userSessionId  string  用户当前sessionid
* @return null
 */
func wirteHeartBeat(userSessionId string) {
	resp, err := http.PostForm("http://localhost:8072/background/writeheartbeat.php", url.Values{"usersessionid": {userSessionId}})
	if err != nil {
		logError(err, "")
	}
	defer resp.Body.Close()

}

/**
记录错误日志
@param e error
@param msg  string 错误信息
@return null
*/
func logError(e error, msg string) {
	if bEcho {
		fmt.Println(time.Now(), msg, e)
	} else {
		logs.Logger.Errorf(" error occurred: %s %v", msg, e)
	}

}

// 打印信息
func logMsg(msg string) {
	if bEcho {
		fmt.Println(time.Now(), msg)
	} else {
		logs.Logger.Info(msg)
	}

}

func main() {
	logs.Init()
	if bLogMsg {
		logMsg("begin")
	}

	http.Handle("/", http.FileServer(http.Dir("."))) // <-- note this line

	http.Handle("/socket", websocket.Handler(Echo))

	if err := http.ListenAndServe(":2324", nil); err != nil {
		log.Fatal("ListenAndServe:", err)
	}

	fmt.Println("end")
}
