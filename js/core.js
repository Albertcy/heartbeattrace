BFS.Script = {
	loadedScripts : [],
	STATUS : {
		LOADED : 1,
		LOADING : 2
	},
	cached: {},
	loadScript: function(url, onload) {
		if(BFS.Script.loadedScripts[url])
			return;
		BFS.Script.loadedScripts[url] = BFS.Script.STATUS.LOADING;
		BFS.Script.loadScriptDomElement(url, onload);
	},
	onload : function(url, callback){
		return function(){
			BFS.Script.loadedScripts[url] = BFS.Script.STATUS.LOADED;
			if(callback){
				callback()
			}
			
		}
	},
	loadScripts: function(aUrls, onload, useScriptDom, uniqueOff) {
		// first pass: see if any of the scripts are on a different domain
		var nUrls = aUrls.length;
		var bDifferent = false;
		if(!uniqueOff)
		{
			for(var i = nUrls - 1; i >= 0; i--){
				if(!!BFS.Script.loadedScripts[aUrls[i]] && BFS.Script.loadedScripts[aUrls[i]] == BFS.Script.STATUS.LOADED)
				{
					aUrls.splice(i,1);
				}
			}
			nUrls = aUrls.length;
			if(nUrls == 0 && onload)
			{
				onload();
				return;
			}
		}
		
		
		if(!!useScriptDom)
			bDifferent = true;
		else{
			for ( var i = 0; i < nUrls; i++ ) {
				if ( BFS.Script.differentDomain(aUrls[i]) ) {
					bDifferent = true;
					break;
				}
			}
		}
			
		// pick the best loading function
		var loadFunc = BFS.Script.loadScriptXhrInjection;
		if ( bDifferent ) {
			//loadFunc = BFS.Script.loadScriptDocWrite;/*
			if (!!useScriptDom || -1 != navigator.userAgent.indexOf('Firefox') || 
				 -1 != navigator.userAgent.indexOf('Opera') ) {
				loadFunc = BFS.Script.loadScriptDomElement;
			}
			else {
				loadFunc = BFS.Script.loadScriptDocWrite;
			}
		}
		// second pass: load the scripts
		for ( var i = 0; i < nUrls; i++ ) {
			loadFunc(aUrls[i], ( i+1 == nUrls ? onload : null ), true);
		}
	},

	differentDomain: function(url) {
		if ( 0 === url.indexOf('http://') || 0 === url.indexOf('https://') ) {
			var mainDomain = document.location.protocol + 
				"://" + document.location.host + "/";
			return ( 0 !== url.indexOf(mainDomain) );
		}
		
		return false;
	},

	loadScriptDomElement: function(url, onload) {
		var oHead = document.getElementsByTagName('head')[0];
		var domscript = document.createElement('script');
		domscript.src = url;
		domscript.onloadDone = false;
		BFS.addHandler(domscript, 'load', function() { 
			if ( !domscript.onloadDone ) {
				domscript.onloadDone = true;
				oHead.removeChild(domscript);
				if(onload){
					onload();
				}
			}
		});
		
		BFS.addHandler(domscript, 'readystatechange', function() { 
			if ( ( "loaded" === domscript.readyState || "complete" === domscript.readyState ) && !domscript.onloadDone ) {
				domscript.onloadDone = true;
				oHead.removeChild(domscript);
				if(onload){
					onload();
				}
			}
		});
		oHead.appendChild(domscript);
		//document.getElementsByTagName('head')[0].appendChild(domscript);
	},

	loadScriptDocWrite: function(url, onload) {
		if(BFS.Script.loadedScripts[url] !== BFS.Script.STATUS.LOADING)
		{
			document.write('<scr' + 'ipt src="' + url + 
					   '" type="text/javascript"></scr' + 'ipt>');
			BFS.Script.loadedScripts[url] = BFS.Script.STATUS.LOADING;
		}
		BFS.addHandler(window, "load", BFS.Script.onload(url, onload));
	},

	queuedScripts: new Array(),

	loadScriptXhrInjection: function(url, onload, bOrder) {
		var iQueue = BFS.Script.queuedScripts.length;
		if ( bOrder ) {
			if(!BFS.Script.loadedScripts[url] || (BFS.Script.loadedScripts[url] == BFS.Script.STATUS.LOADING && onload))
			{
				var qScript = { response: null, onload: onload, done: false , url: url};
				BFS.Script.queuedScripts[iQueue] = qScript;
				 if(BFS.Script.loadedScripts[url] == BFS.Script.STATUS.LOADING && onload)
					 qScript.response = BFS.Script.STATUS.LOADING;
			}
		}

		if(BFS.Script.loadedScripts[url] !== BFS.Script.STATUS.LOADING)
		{
			var xhrObj = BFS.Script.getXHRObject();
			xhrObj.onreadystatechange = function() { 
				if ( xhrObj.readyState == 4 ) {
					if ( bOrder ) {
						BFS.Script.queuedScripts[iQueue].response = xhrObj.responseText;
						BFS.Script.injectScripts();
					}
					else {
						var se = document.createElement('script');
						document.getElementsByTagName('head')[0].appendChild(se);
						se.text = xhrObj.responseText;
						if ( onload ) {
							onload();
						}
					}
				}
			};
			BFS.Script.loadedScripts[url] = BFS.Script.STATUS.LOADING;
			xhrObj.open('GET', url, true);
			xhrObj.send('');
		}
	},

	injectScripts: function(url) {
		var len = BFS.Script.queuedScripts.length;
		for ( var i = 0; i < len; i++ ) {
			var qScript = BFS.Script.queuedScripts[i];
			if ( ! qScript.done ) {
				if ( ! qScript.response ) {
					// STOP! need to wait for this response
					break;
				}
				else {
					if(qScript.response !== BFS.Script.STATUS.LOADING)
					{
						var se = document.createElement('script');
						document.getElementsByTagName('head')[0].appendChild(se);
						se.text = qScript.response;
					}
					BFS.Script.loadedScripts[qScript.url] = BFS.Script.STATUS.LOADED;
					if ( qScript.onload ) {
						qScript.onload();
					}
					qScript.done = true;
				}
			}
		}
	},

	getXHRObject: function() {
		var xhrObj = false;
		try {
			xhrObj = new XMLHttpRequest();
		}
		catch(e){
			var aTypes = ["Msxml2.XMLHTTP.6.0", 
						  "Msxml2.XMLHTTP.3.0", 
						  "Msxml2.XMLHTTP", 
						  "Microsoft.XMLHTTP"];
			var len = aTypes.length;
			for ( var i=0; i < len; i++ ) {
				try {
					xhrObj = new ActiveXObject(aTypes[i]);
				}
				catch(e) {
					continue;
				}
				break;
			}
		}
		finally {
			return xhrObj;
		}
	}
};

BFS.addHandler = function(elem, type, func) {
	if ( elem.addEventListener ) {
		elem.addEventListener(type, func, false);
	}
	else if ( elem.attachEvent ) {
		elem.attachEvent("on" + type, func);
	}
};

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

