<patTemplate:tmpl name="LOGIN_FORM">

<form id="loginform" method="post" action="{ACTION}" {EXTRA_FORM_ATTRIBUTES}>
	<input name="action" type="hidden" value="login"  />
	<div class="inputrow">
		<label for="username">User Name</label>
		<input id="username" name="username" />
	</div>
	<div class="inputrow">
		<label for="password">Password</label>
		<input id="password" name="password" type="password" />
	</div>
	<div class="actionsrow">
		<input name="submit" id="submitbutton" value="Log in" type="submit"  />
		<input name="Clear" type="reset"  />
	</div>
</form>

</patTemplate:tmpl>
