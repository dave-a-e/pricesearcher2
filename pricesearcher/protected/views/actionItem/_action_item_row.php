<div class = "row">
	
	<div class = "col-sm-2">
		<?php print $actionItemInfo[ 'id' ];?>
	</div>
	
	<div class = "col-sm-2">
		<?php print $actionItemInfo[ 'action_item_state_desc' ];?>
	</div>

	<div class = "col-sm-6">
		<?php print $actionItemInfo[ 'descr' ];?>
	</div>
	
	<div class = "col-sm-2">
		<?php
			if( $actionItemInfo[ 'action_item_state_id' ] == ActionItemState::STATE_OPEN )
			{
				$this->renderPartial( '_action_item_upload_button', array( 'actionItemInfo' => $actionItemInfo ) );
			}
		?>
	</div>
	
</div>