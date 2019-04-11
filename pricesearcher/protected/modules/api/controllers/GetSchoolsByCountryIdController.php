<?php

class GetSchoolsByCountryIdController extends ApiController
{
    
    public $mustBeLoggedIn = FALSE;
    public $mustBeAdministrator = FALSE;
    
    public $country_id;
    public $optionsList;
    public $audit_operation_performed = 'None';
    
    public $shopItem;

    public function actionIndex()
    {

        // Set the cmd to equal what has been called
        $this->cmd = 'getSchoolsByCountryId';

        // Check whether the Url contains the elements
        // required to activate this command
        $validated = $this->validateUrl();

        if( $validated === FALSE )
        {
            $this->failure_reason = 'Invalid URL';
            return $this->getJsonResponse();
        }
        
        $this->validateParams();

        $criteria = new CDbCriteria();
        $criteria->order = 'school_name';
        $schools = School::model()->findAllByAttributes( 
            array( 'country_id' => $_GET[ 'country_id' ] ),
            $criteria
        );

        foreach( $schools as $i => $school )
        {
            $this->optionsList .= '<option value = "' . $school->school_id . '">' . $school->school_name . '</option>';
        }

        $this->setReport( 'Success' );
        return $this->getJsonResponse();
        
    }
    


    public function getJsonResponse()
    {
        parent::getJsonResponse();

        $uniqueProperties = array( 
            'audit_operation_performed',
            'optionsList',
        );
            
        $arrayKeys = array_values( $uniqueProperties );
        
        foreach( $arrayKeys as $a => $key )
        {
            if( property_exists( get_class( $this ), $arrayKeys[ $a ] ) === TRUE )
            {
                $this->jsonArray[ $key ] = $this->$key;
            }
        }
        
        return $this->deliverJson();
    }



    /**
    * validateUrl()
    * Only one key-value pair is needed: item_id
    * Remember that the item_id is always checked against a valid item by
    * calling $this->checkParams()
    * 
    */

    public function validateUrl()
    {
        $validated = FALSE;
        
        // If we've got an item_id, then the URL is valid
        if( isset( $_GET[ 'country_id' ] ) === TRUE )
        {
            $validated = TRUE;
        }

        return $validated;
    }
    
}