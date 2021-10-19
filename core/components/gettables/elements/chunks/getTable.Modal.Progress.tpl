<div class="modal fade gts_progress" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="myModalLabel"></h4>
      </div>
      <div class="modal-body">
        <div class="progress-info">
          <span class="progress-procent"></span> <span class="progress-message"></span>
        </div>
        <div class="progress">
          <div class="progress-bar" role="progressbar" style="width: 0" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div class="progress-stop-message" style="display:none;">
          {'gettables_stop_message' | lexicon}
        </div>
        <div class="progress-log">
          
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default progress-stop" data-stop="0">{'gettables_stop' | lexicon}</button>
      </div>
    </div>
  </div>
</div>