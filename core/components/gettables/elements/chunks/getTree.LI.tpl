<li class="get-tree-li {$classes | join : ' '}" data-id="{$id}">
    {if $wraper}   
        <span class="caret1 {if $expanded}caret-down1{/if}"></span>
    {/if}
    <a href="#" class="get-tree-a">{$pagetitle}</a>
        {if $actions}
            <div class="get-tree-actions">
                <button type="button" class="btn btn-sm btn-secondary dropdown-toggle blue" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="glyphicon glyphicon-cog"></i>  
                </button>
                <ul class="dropdown-menu dropdown-menu-right">
                    {foreach $actions as $action_key=>$action}
                        <li>
                            <button class="btn get-tree-action" data-action_key="{$action_key}" data-action="{$action.action}">
                                {$action.label}
                            </button>
                        </li>
                    {/foreach}
                </ul>
            </div>
        {/if}
        {$wraper}
</li>