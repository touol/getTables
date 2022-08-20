<li class="ac-tree-li {$classes | join : ' '}" data-id="{$id}">
    {if $wraper or $tree_parent}   
        <span class="caret1 {if $expanded}caret-down1{/if}"></span>
    {/if}
    {if $active}
        <a href="#" class="ac-tree-a" data-id="{$id}">{$pagetitle}</a>
    {else}
        <span href="#" class="ac-tree-span" data-id="{$id}">{$pagetitle}</span>
    {/if}
        {$wraper}
</li>