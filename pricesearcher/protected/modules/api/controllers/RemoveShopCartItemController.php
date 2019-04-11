<?php

class RemoveShopCartItemController extends ApiController
{
    
    // For ALL API commands that affect an insert
    // or update of an itemElement, the user must be logged in
    public $mustBeLoggedIn = TRUE;
    public $mustBeAdministrator = FALSE;
    
    public $shop_item_id;
    public $audit_operation_performed = 'None';
    
    public $shopItem;

    public function actionIndex()
    {

        // Set the cmd to equal what has been called
        $this->cmd = 'removeShopCartItem';

        // Check whether the Url contains the elements
        // required to activate this command
        $validated = $this->validateUrl();

        if( $validated === FALSE )
        {
            $this->failure_reason = 'Invalid URL';
            return $this->getJsonResponse();
        }
        
        $this->validateParams();
        
        // Now operate on the item
        if( isset( $_GET[ 'shop_item_id' ] ) === TRUE )
        {

            $this->shop_item_id = intval( $_GET[ 'shop_item_id' ] );

            // 1. DELETE the row from the `_shopping_cart_row` table that contains that
            //    sales_item_id

            $scr = ShoppingCartRow::model()->findByAttributes(
                array( 
                    'shop_item_id' => $_GET[ 'shop_item_id' ],
                    'user_id' => Yii::app()->user->getId() 
                    ) 
            );

            if( $scr instanceOf ShoppingCartRow )
            {
                $scr->delete();
            }
            else
            {
                $this->failure_reason = 'Shop Item ' . $this->shop_item_id . 
                    ' not found in User ID ' .
                    Yii::app()->user->getId() . 
                    "'s shopping cart.";

                return $this->getJsonResponse();
            }
            
        }

        $shopItem = ShopItem::model()->findByPk( $this->shop_item_id );
        
        if( $shopItem instanceOf ShopItem )
        {

            // 2. UPDATE the state of the shop_item_id back to available
            $shopItem->shop_item_state_id = ShopItemState::SHOP_ITEM_AVAILABLE;
            $isRemoved = $shopItem->save();
            
            if( $isRemoved !== FALSE )
            {
                ShopLog::logShopItem( $shopItem->shop_item_id, 'Removed From Cart' );
                
                $this->setReport( 'Success' );
                $this->audit_operation_performed = 'Shop Item ' . $shopItem->shop_item_id .
                    ' removed from cart.';
            }
        }        

        return $this->getJsonResponse();
        
    }
    


    public function getJsonResponse()
    {
        parent::getJsonResponse();

        $uniqueProperties = array( 
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
            $validated = TRUE;
        }

        return $validated;
    }
    
}