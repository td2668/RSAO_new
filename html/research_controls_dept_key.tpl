<patTemplate:tmpl name="RESEARCH_CONTROLS">
	<div id="controls">

	<div class="subselection">
		<span>Search by</span>
		<br />
		<ul>
		<li><a href="#" 
			onclick="show('ctrl_department');hide('ctrl_keyword');hide('ctrl_targetyear');">
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
			onclick="hide('ctrl_department');show('ctrl_keyword');hide('ctrl_targetyear');">
			Keyword</a>
			<br />
			<div id="ctrl_keyword" >
				Enter the keyword to search:
				<form method="get" action="student_research.php">
				<input type="hidden" name="action" value="list" />
				<input type="text" name="keyword" style="display:inline;width:80px"/>
				<input type="submit" value="go"></form>
			</div>
			</li>
            
            <li><a href="#" 
            onclick="show('ctrl_targetyear');hide('ctrl_department');hide('ctrl_keyword');">
            Year</a>
            <br />
            <div id="ctrl_targetyear">
               <patTemplate:tmpl name="targetyear" type="condition" conditionVar="BYYEAR">
                    <patTemplate:sub condition="__empty">            
                    n/a
                    </patTemplate:sub>
                    <patTemplate:sub condition="__default">                     
                     {BYYEAR}
                    </patTemplate:sub>                
                </patTemplate:tmpl>    
            </div>
            </li>
	</div>
	</ul>
</div>
</patTemplate:tmpl>