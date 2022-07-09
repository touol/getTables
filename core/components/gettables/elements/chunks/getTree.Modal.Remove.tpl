<div class="modal fade gts_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="" method="post" class="gts-form">
          <input type="hidden" name="id" value="{$id}"/>
          <input type="hidden" name="hash" value="{$hash}"/>
          <input type="hidden" name="tree_name" value="{$tree_name}"/>
          <div class="modal-header">
            <h4 class="modal-title" id="myModalLabel"></h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="{'gettables_close' | lexicon}">
              <span aria-hidden="true">×</span>
            </button>
          </div>
          <p>{'gettables_delete_confirm' | lexicon}</p>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">{'gettables_close' | lexicon}</button>
            <button type="submit" name="gts_action" value="getTree/remove" class="btn btn-primary">{'gettables_delete' | lexicon}</button>
          </div>
      </form>
    </div>
  </div>
</div>