<?php

class ToggleSalesItemToShopItemController extends ApiController
{
    
    // For ALL API commands that affect an insert
    // or update of an itemElement, the user must be logged in
    public $mustBeLoggedIn = TRUE;
    public $mustBeAdministrator = FALSE;
    
    public $shop_item_id;
    public $sales_item_id;
    public $shopItem;
    public $salesItem;
    public $audit_operation_performed = 'None';

    public function actionIndex()
    {

        // Set the cmd to equal what has been called
        $this->cmd = 'toggleSalesItemToShopItem';

        // Check whether the Url contains the elements
        // required to activate this command
        $validated = $this->validateUrl();

        if( $validated === FALSE )
        {
            $this->failure_reason = 'Invalid URL';
            return $this->getJsonResponse();
        }
        
        $this->validateParams();
        
        $this->shop_item_id = intval( $_GET[ 'shop_item_id' ] );
        $this->sales_item_id = intval( $_GET[ 'sales_item_id' ] );

        // See whether or not the shop_item is connected to the sales_item
        $exists = ShopItemSalesItem::model()->findByAttributes( 
            array( 
                'shop_item_group_id' => ShopItemGroup::SHOP_ITEM_GROUP_STANDARD,
                'shop_item_id' => $this->shop_item_id,
                'sales_item_id' => $this->sales_item_id 
            )
        );

        // If it is then we are toggling it OFF
        if( $exists instanceOf ShopItemSalesItem )
        {
            $sql = 'DELETE FROM `_shop_item_sales_item` WHERE shop_item_sales_item_id = ' .
                $exists->shop_item_sales_item_id . ' LIMIT 1';

            Yii::app()->db->createCommand( $sql )->execute();
            $this->audit_operation_performed = 'Removed';
        }
        else
        {
            // If not we are toggling it ON
            $this->salesItem->addBundledSalesItemToShopItem( $this->shopItem );
            $this->audit_operation_performed = 'Added';
        }

        $this->setReport( 'Success' );

        return $this->getJsonResponse();
        
    }
    


    public function getJsonResponse()
    {
        parent::getJsonResponse();

        $uniqueProperties = array( 
            'shop_item_id',
            'sales_item_id',
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
        if( isset( $_GET[ 'shop_item_id' ] ) === TRUE )
        {
            if( isset( $_GET[ 'sales_item_id' ] ) === TRUE )
            {
                $validated = TRUE;
            }
        }

        return $validated;
    }
    
}