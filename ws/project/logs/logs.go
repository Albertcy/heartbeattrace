// logs project logs.go
package logs

import (
	"fmt"
	"github.com/cihub/seelog"
)

var Logger seelog.LoggerInterface

func loadAppConfig() {
	appConfig := `
<seelog minlevel="info">
    <outputs formatid="common">
        <rollingfile type="size" filename="/U8SOFT/turbocrm70/apache/logs/ws/roll.log" maxsize="100000" maxrolls="5"/>
        <filter levels="critical">
            <file path="/U8SOFT/turbocrm70/apache/logs/ws/critical.log" formatid="critical"/>
        </filter>
		<filter levels="error">
            <file path="/U8SOFT/turbocrm70/apache/logs/ws/error.log" formatid="error"/>
        </filter>
    </outputs>
    <formats>
        <format id="common" format="%Date/%Time [%LEV] %Msg%n" />
        <format id="critical" format="%File %FullPath %Func %Msg%n" />
		<format id="error" format="%Date/%Time %File %FullPath %Func %Msg%n" />
    </formats>
</seelog>`
	logger, err := seelog.LoggerFromConfigAsBytes([]byte(appConfig))
	if err != nil {
		fmt.Println(err)
		return
	}
	UseLogger(logger)
}

// doinit
func Init() {
	DisableLog()
	loadAppConfig()
}

// DisableLog disables all library log output
func DisableLog() {
	Logger = seelog.Disabled
}

// UseLogger uses a specified seelog.LoggerInterface to output library log.
// Use this func if you are using Seelog logging system in your app.
func UseLogger(newLogger seelog.LoggerInterface) {
	Logger = newLogger
}
