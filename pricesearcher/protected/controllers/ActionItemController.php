<?php

class ActionItemController extends WebPageController
{
 
    const FILE_LOCATION = 'test_data.json';


    /**
    * retrieveAllActionItemsDataInJsonFormatAndConvertToPhp()
    * Retrieves the 'list' of all Action Items, assiging a status of open to any with no state
    * 
    * @author Dave E (dave_a_e@hotmail.com)
    * @returns Array
    * 
    */
    public function retrieveAllActionItemsDataInJsonFormatAndConvertToPhp()
    {

        // Read in test_data.json file
        $filename = ActionItemController::FILE_LOCATION;

        $file = @file_get_contents( $filename );
        
        if( empty( $file ) === TRUE )
        {
            // Use the CHttpException class so that in a live environment, the user gets all the branding
            // associated with the web application but a message in the main area; in test environment, he
            // gets the full error message.
            throw new CHttpException( 500, $filename . ' does not appear to contain any data. Terminating without change.' );
        }

        // Convert to PHP srray
        $array = json_decode( $file, TRUE );

        if( empty( $array ) === TRUE )
        {
            // Use the CHttpException class so that in a live environment, the user gets all the branding
            // associated with the web application but a message in the main area; in test environment, he
            // gets the full error message.
            throw new CHttpException( 500, 'Badly formed JSON in ' . $filename . '. Conversion to PHP failed. Terminating without change.' );
        }

        foreach( $array as $i => $keyValuePairs )
        {
            if( isset( $keyValuePairs[ 'action_item_state_id' ] ) == FALSE )
            {
                // Give the action_item a state of open if it does not have one
                $array[ $i ][ 'action_item_state_id' ] = ActionItemState::STATE_OPEN;
                
                // Give the action_item a description of open if it does not have one
                $array[ $i ][ 'action_item_state_desc' ] = 'Open';
            }
        }
        
        return $array;
        
    }



    /**
    * actionIndex()
    * Initial page for the ActionItem list (index is the industry standard for the
    * "list view")
    * 
    * @author Dave E (dave_a_e@hotmail.com)
    * @returns Void
    * 
    */
    
    public function actionIndex()
    {
        
        $array = $this->retrieveAllActionItemsDataInJsonFormatAndConvertToPhp();

        // Show list of actionItem for the user to choose from
        $this->render( '//actionItem/index', array( 'actionItems' => $array ) );
    }

    

    /**
    * actionUpload()
    * Performs all the necessary error-checking for the file selected, moves it to
    * a folder and updates the server-side data so that the list view updates to show
    * current Action Items
    * 
    * @author Dave E (dave_a_e@hotmail.com)
    * @returns Void
    * 
    */

    public function actionUpload()
    {

        if( ( empty( $_FILES ) === TRUE ) || ( isset( $_FILES[ 'uploaded_file' ] ) === FALSE ) )
        {
            // Use the CHttpException class so that in a live environment, the user gets all the branding
            // associated with the web application but a message in the main area; in test environment, he
            // gets the full error message.
            throw new CHttpException( 500, 'Your file was not attached correctly. Please ' .
                '<a href = "' . Yii::app()->request->baseUrl . '/actionItem/">click here</a>' .
                'to try again.' );
        }



        // Check we have received the action_item_id
        if( isset( $_POST[ 'action_item_id' ] ) === FALSE )
        {
            throw new CHttpException( 500, 'No action_item_id received. Unable to match uploaded file to ' .
                'Action Item. Terminating without change. Please ' .
                '<a href = "' . Yii::app()->request->baseUrl . '/actionItem/">click here</a>' .
                'to try again.' );
        }

        
        // If we reach here, then the user has submitted a file
        // We want to match the id for the submitted file to the id of the ActionItem that it corresponds to
        // We therefore want to make sure (first) that the Json file remains available and valid
        $array = $this->retrieveAllActionItemsDataInJsonFormatAndConvertToPhp();

        foreach( $array as $i => $keyValuePairs )
        {
            if( $keyValuePairs[ 'id' ] == $_POST[ 'action_item_id' ] )
            {
                $array[ $i ][ 'action_item_state_id' ] = ActionItemState::STATE_COMPLETE;
                $array[ $i ][ 'action_item_state_desc' ] = 'Complete';
            }
        }

        // Make sure the directory exists; create it if it does not
        $pathToWrite = 'uploads/' . sprintf( '%09d', $_POST[ 'action_item_id' ] );
        
        if( is_dir( $pathToWrite ) === FALSE )
        {
            $createDir = mkdir( $pathToWrite, 0777, TRUE );

            if( $createDir == FALSE )
            {
                throw new CHttpException( 500, 'Permissions error. Please contact the webmaster. The server ' .
                    'needs to have permisson to create a directory for the file you submitted. It currently does not.' );
            }
        }
        
        $filename = $pathToWrite . '/' . $_FILES[ 'uploaded_file' ][ 'name' ];
        
        $r = move_uploaded_file( $_FILES[ 'uploaded_file' ][ 'tmp_name' ], $filename );

        if( $r == FALSE )
        {
            throw new CHttpException( 500, 'Error moving file.' );
        }

        foreach( $array as $i => $keyValuePairs )
        {
            if( $keyValuePairs[ 'id' ] == $_POST[ 'action_item_id' ] )
            {
                $array[ $i ][ 'filename' ] = $filename;
            }
        }

        // Now we have to save the old file over the new one
        
        $json = json_encode( $array );
        
        if( empty( $json ) === FALSE )
        {
            file_put_contents( ActionItemController::FILE_LOCATION, $json );
        }

        $msg = 'Successful completion of Action Item ID ' . $_POST[ 'action_item_id' ] . '. You now need ' .
            'to search for it in the list of completed Items.';
        
        // Show list of actionItem for the user to choose from
        $this->render( '//actionItem/index', array( 'msg' => $msg, 'actionItems' => $array ) );
    }
    
    
}
