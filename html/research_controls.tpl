<patTemplate:tmpl name="RESEARCH_CONTROLS">
	<div id="controls">
	
	<div class="typeselection">
    <table><tr><td nowrap="true">View: {RESEARCHERS_SELECTED}&nbsp;|&nbsp;{PROJECTS_SELECTED}
        </td></tr></table>
<!--		<span>View: <a href="research.php?action=list&type=researchers" {RESEARCHERS_SELECTED}>People</a> 
		| <a href="research.php?action=list&type=projects" {PROJECTS_SELECTED}>Projects</a>
		</span>  -->
		<br />		
	</div>
	
	<div class="subselection">
		<span>Search by</span>
		<br />
		
		<ul>			
		<li><a href="#" 
			onclick="show('ctrl_faculty');hide('ctrl_department');hide('ctrl_topic');hide('ctrl_keyword');">
				Faculty</a>
			<br />
			<div id="ctrl_faculty" >
			 
				<patTemplate:tmpl name="faculties" type="condition" conditionVar="BYFACULTY">
					<patTemplate:sub condition="__empty">
					n/a
					</patTemplate:sub>
					<patTemplate:sub condition="__default">
					 {BYFACULTY}
					</patTemplate:sub>
				</patTemplate:tmpl>
			 </div>
		 </li>
		<li><a href="#" 
			onclick="hide('ctrl_faculty');show('ctrl_department');hide('ctrl_topic');hide('ctrl_keyword');">
			Department</a>
			<br />
			<div id="ctrl_department">			
				<patTemplate:tmpl name="departments" type="condition" conditionVar="BYDEPARTMENT">
					<patTemplate:sub condition="__empty">			
					n/a
					</patTemplate:sub>
					<patTemplate:sub condition="__default">					 
					 {BYDEPARTMENT}
					</patTemplate:sub>				
				</patTemplate:tmpl>			
			</div>
			</li>
		<li><a href="#" 
			onclick="hide('ctrl_faculty');hide('ctrl_department');show('ctrl_topic');hide('ctrl_keyword');">
			Topic</a>
			<br />
				<div id="ctrl_topic" >
				<patTemplate:tmpl name="topics" type="condition" conditionVar="BYTOPIC">				
					<patTemplate:sub condition="__empty">			
					n/a
					</patTemplate:sub>
					<patTemplate:sub condition="__default">					 
					 {BYTOPIC}					 
					</patTemplate:sub>				
				</patTemplate:tmpl>
				</div>
			</li>
		<li><a href="#" 
			onclick="hide('ctrl_faculty');hide('ctrl_department');hide('ctrl_topic');show('ctrl_keyword');">
			Keyword</a>
			<br />
			<div id="ctrl_keyword" >
				Enter the keyword to search:
				<form method="get" action="research.php">
				<input type="hidden" name="action" value="list" />
				<input type="text" name="keyword" style="display:inline;width:80px"/>
				<input type="submit" value="go"></form>
			</div>
			</li>
	</div>
	</ul>
</div>
</patTemplate:tmpl>