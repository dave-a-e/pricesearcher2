<div class = "modal" id = "action-item-upload-modal" name = "action-item-upload-modal" tabindex = "-1" role = "dialog">
  <div class = "modal-dialog" role = "document">
    <div class = "modal-content">
      <div class = "modal-header">
        <h5 class = "modal-title">Complete Action Item ID #<span id = "action-item-upload-modal-action-item-id"></span></h5>
        <button type = "button" class="close" data-dismiss = "modal" aria-label = "Close">
          <span aria-hidden = "true">&times;</span>
        </button>
      </div>
	<form action = "<?php print Yii::app()->baseUrl;?>/actionItem/upload/" method = "post" type = "submit" enctype = "multipart/form-data">
		<div class="modal-body">
			<p>Browse your computer for the file that satisifies this Action Item.</p>
			<input type = "hidden" id = "action_item_id" name = "action_item_id">
			<input type = "file" class = "btn btn-default" id = "uploaded_file" name = "uploaded_file">
		</div>
		<div class="modal-footer">
			<button type = "submit" class = "btn btn-primary">Upload</button>
			<button type = "button" class = "btn btn-secondary" data-dismiss = "modal">Close</button>
		</div>
	</form>
    </div>
  </div>
</div>