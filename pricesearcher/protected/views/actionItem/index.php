<script>
	function showUploadActionItemModal( action_item_id )
	{
		$( '#action-item-upload-modal-action-item-id' ).html( action_item_id );
		$( '#action_item_id' ).val( action_item_id );
		$( '#action-item-upload-modal' ).modal( 'show' );
	}
</script>

<?php
	if( isset( $msg ) === TRUE )
	{
		print '<div class = "alert alert-success">' . $msg . '</div>';
	}
?>

<p>
The following Action Items (if any) are currently in an open state:
</p>

<?php
	if( empty( $actionItems ) === FALSE )
	{
		foreach( $actionItems as $i => $actionItemInfo )
		{
			if( $actionItemInfo[ 'action_item_state_id' ] == ActionItemState::STATE_OPEN )
			{
				$this->renderPartial( '_action_item_row', array( 'actionItemInfo' => $actionItemInfo ) );
			}
		}

		$this->renderPartial( '_action_item_upload_modal_template', array() );
	}
?>