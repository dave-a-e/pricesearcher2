<?php

class HtmlShopSalesItemsController extends ApiController
{
    
    public $mustBeLoggedIn = TRUE;
    public $mustBeAdministrator = FALSE;
    
    public $shop_item_id;
    public $shopItem;
    public $html;
    public $num_in_bundle;

    public function actionIndex()
    {

        // Force the include of the SearchController
        require Yii::getPathOfAlias('application.controllers.SellController') . '.php';

        // Set the cmd to equal what has been called
        $this->cmd = 'htmlShopSalesItems';

        // Check whether the Url contains the elements
        // required to activate this command
        $validated = $this->validateUrl();

        if( $validated === FALSE )
        {
            $this->failure_reason = 'Invalid URL';
            return $this->getJsonResponse();
        }
        
        $this->validateParams();

        // As this API command can only be used with a
        // bundle that is being prepared, we can check the
        // shop_item at this point
        if( $this->shopItem->shop_item_state_id != ShopItemState::SHOP_ITEM_NOT_YET_FINALISED )
        {
            $this->failure_reason = 'Shop Item ' . $this->shopItem->shop_item_id . ' already finalised!';
            return $this->getJsonResponse();
        }

        $this->html = SellController::htmlSalesItemsInBundle( $this->shopItem );
        $this->num_in_bundle = SellController::countNumInBundle( $this->shopItem );
        $this->setReport( 'Success' );

        return $this->getJsonResponse();
        
    }
    

    public function getJsonResponse()
    {
        parent::getJsonResponse();
        
        $uniqueProperties = array( 
            'html',
            'num_in_bundle' );
            
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
    * Note: We do not check the show_title here because the parent
    * validate will not allow a blank show_title to be passed in
    */

    public function validateUrl()
    {
        $validated = FALSE;
        
        // If we've got the following then the url is validated
        if( isset( $_GET[ 'shop_item_id' ] ) === TRUE )
        {
            $validated = TRUE;
        }

        return $validated;
    }
    
}