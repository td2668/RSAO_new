<patTemplate:tmpl name="LOGIN_FORM">

<form id="loginform" method="post" action="{ACTION}" {EXTRA_FORM_ATTRIBUTES}>
	<input name="action" type="hidden" value="login"  />
	<p class='enfasis'>You can login to access the personalized functions and more.</p>
            <p class="enfasis">This site is not yet linked to the common login, so you'll need to create an account before you can login</p>
	<div class="">
		<label for="username">User Name (or Student ID#)</label>
		<input id="username" name="username" />
	</div>
	<div class="inputrow">
		<label for="password">Password</label>
		<input id="password" name="password" type="password" />
	</div>
	<div class="actionsrow">
		<input name="submit" id="submitbutton" value="Log in" type="submit"  />
		<button style="border-color: red" type="button" onclick="window.location='/signup.php?target={TARGET}'">Create Account</button>
		<input name="Clear" type="reset"  />
	</div>
</form>

</patTemplate:tmpl>
