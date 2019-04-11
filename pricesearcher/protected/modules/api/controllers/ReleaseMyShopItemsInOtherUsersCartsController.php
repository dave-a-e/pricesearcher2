<?php

class releaseMyShopItemsInOtherUsersCartsController extends ApiController
{

    public $mustBeLoggedIn = TRUE;
    public $mustBeAdministrator = TRUE;

    public $audit_operation_performed;

    public function actionIndex()
    {

        // Set the cmd to equal what has been called
        $this->cmd = 'ReleaseMyShopItemsInOtherUsersCartsController';

        // Delete the row which connects this tag_id to this paarent
        $sql = 'SELECT si.shop_item_id, scr.shopping_cart_row_id FROM `_shop_item` si 
        INNER JOIN `_shopping_cart_row` scr ON si.shop_item_id = scr.shop_item_id
        WHERE si.shop_item_state_id = ' . ShopItemState::SHOP_ITEM_IN_CART;

        $r = Yii::app()->db->createCommand( $sql )->queryAll();
        
        if( empty( $r ) === FALSE )
        {
            foreach( $r as $i => $shopInfo )
            {
                if( isset( $shopInfo[ 'shop_item_id' ] ) )
                {
                    $shopItem = ShopItem::model()->findByAttributes(
                        array(
                            'shop_item_state_id' => ShopItemState::SHOP_ITEM_IN_CART,
                            'shop_item_id' => $shopInfo[ 'shop_item_id' ],
                        )
                    );

                    if( $shopItem instanceOf ShopItem )
                    {
                        $this->setReport( 'Success' );

                        $shopItem->shop_item_state_id = ShopItemState::SHOP_ITEM_AVAILABLE;
                        $shopItem->save();
                        
                        // Remove the row from shopping_cart_row
                        $sql = 'DELETE FROM `_shopping_cart_row` WHERE shopping_cart_row_id = ' .
                            $shopInfo[ 'shopping_cart_row_id' ] . ' LIMIT 1';

                        Yii::app()->db->createCommand( $sql )->execute();
                        
                    }                    
                }
            }
        }
        

        $this->audit_operation_performed = 'No shop items were affected.';

        if( empty( $r ) === FALSE )
        {
            $this->audit_operation_performed = 
                sizeof( $r ) . ' shop items made available in shop.';
        }

        return $this->getJsonResponse();
        
    }



    public function getJsonResponse()
    {
        parent::getJsonResponse();

        $uniqueProperties = array( 
            'audit_operation_performed' 
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
    * 
    */

    public function validateUrl()
    {
        $validated = FALSE;
        
        // If we've got an item_id and a quote_id, then the URL is valid
        if( isset( $_GET[ 'thing_id' ] ) === TRUE )
        {
            if( isset( $_GET[ 'tag_id' ] ) === TRUE )
            {
                $validated = TRUE;
            }
        }

        return $validated;
    }
    
}