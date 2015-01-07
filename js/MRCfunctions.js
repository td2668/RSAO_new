/****************************/
/* MRU Research             */
/****************************/

function show(elem) {
	var e;
	if(elem == null || elem =="" ) return false;
	e = document.getElementById(elem);
	if(e== null) return false;
	//e.style.display = 'block';

	if(e.className.indexOf("hide")==-1) {
	if(e.className.indexOf("show")==-1)
		e.className+=" show";
	}
	else e.className=e.className.replace("hide","show");
	return false;
}


function hide(elem) {
	var e;
	if(elem == null || elem =="" ) return false;
	e = document.getElementById(elem);
	if(e== null) return false;
	//e.style.display = 'none';
	if(e.className.indexOf("show")==-1) {
	if(e.className.indexOf("hide")==-1)
		e.className+=" hide";
	}
	else e.className=e.className.replace("show","hide");
	return false;
}

function toggle(elem) {
	var e;
	if(elem == null || elem =="" ) return false;
	e = document.getElementById(elem);
	if(e== null) return false;

	if(e.className.indexOf("hide")>-1) show(elem);
	else hide(elem);
	return false;
}

function login_logout_form_submit() {
	var postString;

	login=$('loginform');
	logout=$('logoutform');
	if(login) theform=login;
	else
		theform=logout;

	if(login)
		postString="action=login&username=" + $('username2').value + "&password=" + $('password2').value;
	else
		postString="action=logout";
	var req = new Request.HTML({url:"login.php?ajax=yes",
		method: 'post',
		data: postString,
		onSuccess: function(html) {
			//Clear the text currently inside the results div.
			$('ajaxyfied').set('text', '');
			//Inject the new DOM elements into the results div.
			$('ajaxyfied').adopt(html);
		},
		//Our request will most likely succeed, but just in case, we'll add an
		//onFailure method which will let the user know what happened.
		onFailure: function() {
			$('ajaxyfied').set('text', 'The request failed.');
		}
	});


	req.send();

}



function formFX(targetFormId) {
	window.addEvent('domready', function() {
		//theform=document.forms[targetForm];
		theform=document.getElementById(targetFormId);
		inputs=theform.elements;

		for ( var idx=0 ; idx < inputs.length ; idx++ ) {
			item=inputs[idx];

			if(item.type=="text" || item.type=="textarea") {
				if (item.value=="") {
							item.style.fontStyle="italic";
							item.style.color="gray";
							item.value=item.title;
				} else item.style.color="black";
				item.addEvents({
					focus: function() {
							this.style.color="black";
							this.style.fontStyle="normal";
							this.className=this.className.replace("selected","");
							this.className=this.className+" selected";
							if (this.title!="" && this.value==this.title) this.value = '';
							},
					blur: function() {
							this.className=this.className.replace("selected","");
							if (this.value=="") {
								this.style.color="gray";
								this.style.fontStyle="italic";
								this.value=this.title;

								}
							}
					});

			}

		};


		theform.formSubmit=function() {
				//console.log("submit cleanup event");
				inputs=this.elements;

				for ( var idx=0 ; idx < inputs.length ; idx++ ) {
					item=inputs[idx];
					if(item.type=="text" || item.type=="textarea")
						if(item.style.color=="gray") item.value="";
					}
				//console.log("Submiting the form");
				this.submit();

				};


		theform.addEvent("submit", function(e) {
				console.log("submit cleanup event");
				new Event(e).stop();

				theForm.formSubmit();

				});


	});
}


function cv_item_toggle(item_id,element_id,field_name,change_to) {
	var postString;

	ele=document.getElementById(element_id);

	//disable current event as this one is now running
	ele.onClick="";
	//remove the img. A spinner would be fine
	ele.set('text', '...');


	if(change_to=="1") { // currently turning it on, so next img display that now is checked and event would turn it off
		newevent="cv_item_toggle('"+item_id+"','"+element_id+"','"+field_name+"','0')";
		newimg='<img src="images/myactivities/check.gif" />';
	}
	else { // nextimg will be X and event would turnit back on
		newevent="cv_item_toggle('"+item_id+"','"+element_id+"','"+field_name+"','1')";
		newimg='<img src="images/myactivities/x.gif" />';
	}
	newimg=newimg+'';

	var req = new Request.HTML({url:"my_research.php?ajax=yes&section=my_research&action=cv_item_toggle&fname=" + field_name + "&changeto="+change_to,
		method: 'get',
		onSuccess: function(html) {
			if(html=="ok") { // request was success
				// Set the event and img to new values as the request was a success
				ele.onClick=newevent;

				ele.set('text', newimg);
			}
			else //Inject the new DOM elements into the results div.
				ele.adopt(html);

		},
		//Our request will most likely succeed, but just in case, we'll add an
		//onFailure method which will let the user know what happened.
		onFailure: function() {
			ele.set('text', 'The request failed.');
		}
	});


	req.send();

}

function save_research_items_list(item_id){

	var form = document.forms['research_list_form'];
	var cv = form.elements["item_"+item_id+"_cv"].checked;
	var profile = form.elements["item_"+item_id+"_profile"].checked;

	/*
	new Ajax.Request("myactivities.php?section=my_research&subsection=save_visibility", {
		parameters: {item_id: item_id, cv: cv, profile: profile},
		method: 'post'
	});
	*/

	var myRequest = new Request({method: 'post', url: 'myactivities.php'});

	myRequest.send("section=my_research&subsection=save_visibility&item_id="+item_id+"&cv="+cv+"&profile="+profile);



}

function toggleMe(a){
  var e=document.getElementById(a);
  if(!e)return true;
  if(e.style.display=="none"){
    e.style.display="block"
  } else {
    e.style.display="none"
  }
  return true;
}
