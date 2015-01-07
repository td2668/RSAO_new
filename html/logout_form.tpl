<patTemplate:tmpl name="LOGOUT_FORM">

<form id="logoutform" method="post" action="{ACTION}" {EXTRA_FORM_ATTRIBUTES}>
	<input name="action" type="hidden" value="logout"  />
	<input name="submitted" type="hidden" value="yes"  />

	<div class="inputrow">
		<label for="logout">To close your session click the <b>Log out</b> button</label>
	</div>
	<div class="actionsrow">
		<input id="submitbutton" name="submit" value="Log out" type="submit"  />
	</div>
</form>

</patTemplate:tmpl>
