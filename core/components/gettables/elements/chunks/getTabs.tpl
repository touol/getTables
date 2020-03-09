<div id="{$name}" class="get-tables-tabs {$cls}">
	<ul class="nav nav-tabs">
	  {foreach $tabs as $tab}
		  <li class="{$tab.active}"><a data-toggle="tab" href="#{$tab.name}">{$tab.label}</a></li>
	  {/foreach}
	</ul>
	 
	<div class="tab-content">
	  {foreach $tabs as $tab}
		  <div id="{$tab.name}" class="tab-pane fade in {$tab.active}">
			{$tab.content}
		  </div>
	  {/foreach}
	</div>
</div>