/************************************************************************************************************
Ajax dynamic content
Copyright (C) 2006  DTHMLGoodies.com, Alf Magne Kalleland

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA

Dhtmlgoodies.com., hereby disclaims all copyright interest in this script
written by Alf Magne Kalleland.

Alf Magne Kalleland, 2006
Owner of DHTMLgoodies.com


************************************************************************************************************/	

var enableCache = true;
var jsCache = new Array();

var dynamicContent_ajaxObjects = new Array();

function ajax_showContent(divId,ajaxIndex,url,callbackOnComplete)
{
	var targetObj = document.getElementById(divId);
	targetObj.innerHTML = dynamicContent_ajaxObjects[ajaxIndex].response;
	if(enableCache){
		jsCache[url] = 	dynamicContent_ajaxObjects[ajaxIndex].response;
	}
	dynamicContent_ajaxObjects[ajaxIndex] = false;
	
	ajax_parseJs(targetObj);
	
	if(callbackOnComplete) {
		executeCallback(callbackOnComplete);
	}
}

function executeCallback(callbackString) {
	if(callbackString.indexOf('(')==-1) {
		callbackString = callbackString + '()';
	}
	try{
		eval(callbackString);
	}catch(e){

	}
	
	
}

function ajax_loadContent(divId,url,callbackOnComplete)
{
	if(enableCache && jsCache[url]){
		document.getElementById(divId).innerHTML = jsCache[url];
		ajax_parseJs(document.getElementById(divId))
		evaluateCss(document.getElementById(divId))
		if(callbackOnComplete) {
			executeCallback(callbackOnComplete);
		}		
		return;
	}
	
	var ajaxIndex = dynamicContent_ajaxObjects.length;
	document.getElementById(divId).innerHTML = 'Loading content - please wait';
	dynamicContent_ajaxObjects[ajaxIndex] = new sack();
	
	if(url.indexOf('?')>=0){
		dynamicContent_ajaxObjects[ajaxIndex].method='GET';
		var string = url.substring(url.indexOf('?'));
		url = url.replace(string,'');
		string = string.replace('?','');
		var items = string.split(/&/g);
		for(var no=0;no<items.length;no++){
			var tokens = items[no].split('=');
			if(tokens.length==2){
				dynamicContent_ajaxObjects[ajaxIndex].setVar(tokens[0],tokens[1]);
			}	
		}	
		url = url.replace(string,'');
	}

	
	dynamicContent_ajaxObjects[ajaxIndex].requestFile = url;	// Specifying which file to get
	dynamicContent_ajaxObjects[ajaxIndex].onCompletion = function(){ ajax_showContent(divId,ajaxIndex,url,callbackOnComplete); };	// Specify function that will be executed after file has been found
	dynamicContent_ajaxObjects[ajaxIndex].runAJAX();		// Execute AJAX function	
	
	
}

function ajax_parseJs(obj)
{
	var scriptTags = obj.getElementsByTagName('SCRIPT');
	var string = '';
	var jsCode = '';
	for(var no=0;no<scriptTags.length;no++){	
		if(scriptTags[no].src){
	        var head = document.getElementsByTagName("head")[0];
	        var scriptObj = document.createElement("script");
	
	        scriptObj.setAttribute("type", "text/javascript");
	        scriptObj.setAttribute("src", scriptTags[no].src);  	
		}else{
			if(navigator.userAgent.toLowerCase().indexOf('opera')>=0){
				jsCode = jsCode + scriptTags[no].text + '\n';
			}
			else
				jsCode = jsCode + scriptTags[no].innerHTML;	
		}
		
	}

	if(jsCode)ajax_installScript(jsCode);
}


function ajax_installScript(script)
{		
    if (!script)
        return;		
    if (window.execScript){        	
    	window.execScript(script)
    }else if(window.jQuery && jQuery.browser.safari){ // safari detection in jQuery
        window.setTimeout(script,0);
    }else{        	
        window.setTimeout( script, 0 );
    } 
}	
	
	
function evaluateCss(obj)
{
	var cssTags = obj.getElementsByTagName('STYLE');
	var head = document.getElementsByTagName('HEAD')[0];
	for(var no=0;no<cssTags.length;no++){
		head.appendChild(cssTags[no]);
	}	
}

/* Simple AJAX Code-Kit (SACK) v1.6.1 */
/* �2005 Gregory Wild-Smith */
/* www.twilightuniverse.com */
/* Software licenced under a modified X11 licence,
 see documentation or authors website for more details */

function sack(file) {
    this.xmlhttp = null;

    this.resetData = function() {
        this.method = "POST";
        this.queryStringSeparator = "?";
        this.argumentSeparator = "&";
        this.URLString = "";
        this.encodeURIString = true;
        this.execute = false;
        this.element = null;
        this.elementObj = null;
        this.requestFile = file;
        this.vars = new Object();
        this.responseStatus = new Array(2);
    };

    this.resetFunctions = function() {
        this.onLoading = function() { };
        this.onLoaded = function() { };
        this.onInteractive = function() { };
        this.onCompletion = function() { };
        this.onError = function() { };
        this.onFail = function() { };
    };

    this.reset = function() {
        this.resetFunctions();
        this.resetData();
    };

    this.createAJAX = function() {
        try {
            this.xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
        } catch (e1) {
            try {
                this.xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            } catch (e2) {
                this.xmlhttp = null;
            }
        }

        if (! this.xmlhttp) {
            if (typeof XMLHttpRequest != "undefined") {
                this.xmlhttp = new XMLHttpRequest();
            } else {
                this.failed = true;
            }
        }
    };

    this.setVar = function(name, value){
        this.vars[name] = Array(value, false);
    };

    this.encVar = function(name, value, returnvars) {
        if (true == returnvars) {
            return Array(encodeURIComponent(name), encodeURIComponent(value));
        } else {
            this.vars[encodeURIComponent(name)] = Array(encodeURIComponent(value), true);
        }
    }

    this.processURLString = function(string, encode) {
        encoded = encodeURIComponent(this.argumentSeparator);
        regexp = new RegExp(this.argumentSeparator + "|" + encoded);
        varArray = string.split(regexp);
        for (i = 0; i < varArray.length; i++){
            urlVars = varArray[i].split("=");
            if (true == encode){
                this.encVar(urlVars[0], urlVars[1]);
            } else {
                this.setVar(urlVars[0], urlVars[1]);
            }
        }
    }

    this.createURLString = function(urlstring) {
        if (this.encodeURIString && this.URLString.length) {
            this.processURLString(this.URLString, true);
        }

        if (urlstring) {
            if (this.URLString.length) {
                this.URLString += this.argumentSeparator + urlstring;
            } else {
                this.URLString = urlstring;
            }
        }

        // prevents caching of URLString
        this.setVar("rndval", new Date().getTime());

        urlstringtemp = new Array();
        for (key in this.vars) {
            if (false == this.vars[key][1] && true == this.encodeURIString) {
                encoded = this.encVar(key, this.vars[key][0], true);
                delete this.vars[key];
                this.vars[encoded[0]] = Array(encoded[1], true);
                key = encoded[0];
            }

            urlstringtemp[urlstringtemp.length] = key + "=" + this.vars[key][0];
        }
        if (urlstring){
            this.URLString += this.argumentSeparator + urlstringtemp.join(this.argumentSeparator);
        } else {
            this.URLString += urlstringtemp.join(this.argumentSeparator);
        }
    }

    this.runResponse = function() {
        eval(this.response);
    }

    this.runAJAX = function(urlstring) {
        if (this.failed) {
            this.onFail();
        } else {
            this.createURLString(urlstring);
            if (this.element) {
                this.elementObj = document.getElementById(this.element);
            }
            if (this.xmlhttp) {
                var self = this;
                if (this.method == "GET") {
                    totalurlstring = this.requestFile + this.queryStringSeparator + this.URLString;
                    this.xmlhttp.open(this.method, totalurlstring, true);
                } else {
                    this.xmlhttp.open(this.method, this.requestFile, true);
                    try {
                        this.xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded")
                    } catch (e) { }
                }

                this.xmlhttp.onreadystatechange = function() {
                    switch (self.xmlhttp.readyState) {
                        case 1:
                            self.onLoading();
                            break;
                        case 2:
                            self.onLoaded();
                            break;
                        case 3:
                            self.onInteractive();
                            break;
                        case 4:
                            self.response = self.xmlhttp.responseText;
                            self.responseXML = self.xmlhttp.responseXML;
                            self.responseStatus[0] = self.xmlhttp.status;
                            self.responseStatus[1] = self.xmlhttp.statusText;

                            if (self.execute) {
                                self.runResponse();
                            }

                            if (self.elementObj) {
                                elemNodeName = self.elementObj.nodeName;
                                elemNodeName.toLowerCase();
                                if (elemNodeName == "input"
                                    || elemNodeName == "select"
                                    || elemNodeName == "option"
                                    || elemNodeName == "textarea") {
                                    self.elementObj.value = self.response;
                                } else {
                                    self.elementObj.innerHTML = self.response;
                                }
                            }
                            if (self.responseStatus[0] == "200") {
                                self.onCompletion();
                            } else {
                                self.onError();
                            }

                            self.URLString = "";
                            break;
                    }
                };

                this.xmlhttp.send(this.URLString);
            }
        }
    };

    this.reset();
    this.createAJAX();
}
