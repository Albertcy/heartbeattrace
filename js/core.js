BFS.WSM = {
	ws : null,
	protocol: 'ws://',
	port: ':1234',
	url : '/socket',
	serverName : 'localhost',
	step : 15000,
	opt : {
		msg : ''
	},
	tryTimes: 0,
	ini : function(opt){
		var o = BFS.WSM;
		
		o.opt.msg = !!opt&&!!opt.msg ? opt.msg : 'default msg';
		o.serverName = !!opt && !!opt.serverName ? opt.serverName : o.serverName;
		o.ws = new WebSocket(o.getUrl());
		
		o.ws.onpen = o.openHandle();
		o.ws.onerror = function (){
			console.log('errors  occur !');
		};
		o.ws.onmessage= function(msg){
			if(msg.data == 'offline'){
				o.ws.close();
			}
		};
		
	},
	closeHandle : function(){
		var o = BFS.WSM;
		if(o.tryTimes < 5){
			setTimeout(function(){
				o.ws = null;
				o.ws = o.ini();
				o.tryTimes ++;
			}, 1000);
		}else{
			o.tryTimes = 0;
		}
	},
	getUrl : function(){
		var o = BFS.WSM;
		return o.protocol + o.serverName + o.port + o.url;
	},
	openHandle : function(){
		var oMudule = BFS.WSM;
		oMudule.interval = setInterval(function(){
			if(oMudule.ws.readyState != oMudule.ws.OPEN){
				clearInterval(oMudule.interval);
			}
			if(oMudule.ws.bufferedAmount == 0){
				oMudule.ws.send(oMudule.opt.msg);
			}
		}, oMudule.step);
		
	}
};

