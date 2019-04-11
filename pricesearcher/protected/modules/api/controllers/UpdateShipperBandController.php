<?php

class UpdateShipperBandController extends ApiController
{
    
    // For ALL API commands that affect an insert
    // or update of an itemElement, the user must be logged in
    public $mustBeLoggedIn = TRUE;
    public $mustBeAdministrator = TRUE;
    
    public $shipper_band_id;
    public $max_weight = 0;
    public $shipping_price = 0;
    public $shipperBand;
    public $audit_operation_performed = 'None';

    public function actionIndex()
    {

        // Set the cmd to equal what has been called
        $this->cmd = 'updateShipperBand';

        // Check whether the Url contains the elements
        // required to activate this command
        $validated = $this->validateUrl();

        if( $validated === FALSE )
        {
            $this->failure_reason = 'Invalid URL';
            return $this->getJsonResponse();
        }
        
        $this->validateParams();
        
        $this->shipper_band_id = intval( $_GET[ 'shipper_band_id' ] );
        
        if( isset( $_GET[ 'shipping_price' ] ) === TRUE )
        {
            $this->shipping_price = floatval( $_GET[ 'shipping_price' ] );
        }
        
        if( isset( $_GET[ 'max_weight' ] ) === TRUE )
        {
            $this->max_weight = floatval( $_GET[ 'max_weight' ] );
        }
        
        // Get the shipperBand
        $this->shipperBand = ShipperBand::model()->findByPk( $this->shipper_band_id );

        // If it is then we are toggling it OFF
        if( $this->shipperBand instanceOf ShipperBand )
        {

            if( $this->shipping_price > 0 )
            {
                $this->audit_operation_performed .= 'Updated shipping_price from ' . number_format( $this->shipperBand->shipping_price, 2 );
                $this->shipperBand->shipping_price = $this->shipping_price;
                $this->audit_operation_performed .= ' to ' . number_format( $this->shipperBand->shipping_price, 2 );
            }

            if( $this->max_weight > 0 )
            {
                $this->audit_operation_performed .= 'Updated max_weight from ' . number_format( $this->shipperBand->max_weight, 2 );
                $this->shipperBand->max_weight = $this->max_weight;
                $this->audit_operation_performed .= ' to ' . number_format( $this->shipperBand->max_weight, 2 );
            }

            $this->shipperBand->save();
                
        }
        else
        {
            $this->failure_reason = 'ShipperBand ID is invalid.';
            return $this->getJsonResponse();
        }

        $this->setReport( 'Success' );

        return $this->getJsonResponse();
        
    }
    


    public function getJsonResponse()
    {
        parent::getJsonResponse();

        $uniqueProperties = array( 
            'shipper_band_id',
            'audit_operation_performed',
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
        if( isset( $_GET[ 'shipper_band_id' ] ) === TRUE )
        {
            $validated = TRUE;
        }

        return $validated;
    }
    
}