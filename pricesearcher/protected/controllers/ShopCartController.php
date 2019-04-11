<?php

class ShopCartController extends ShopWebPageController
{

	public function actionIndex()
	{
        
        ShopWebPageController::showClosedSignIfShopClosed();
        ShopWebPageController::actionIndex();

        $landingHtml = $this->render('index',array(),true );

        $replacements = array();
        
        $params[ 'user_id' ] = Yii::app()->user->getId();
        $replacements[ '%SHOPPING_CART%' ] = $this->renderCart( $params );
    
        $params[ 'link_url' ] = 'welcome';

        $replacements[ '%URL_PARAMS%' ] = ShopWebPageController::getUrlParams();
        $replacements[ '%PAY_BUTTON%' ] = ShopCartController::showPayButtonIfItemsInCart();

        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;
    
    }



    public function actionEmpty()
    {
        
        ShopWebPageController::showClosedSignIfShopClosed();
        ShopWebPageController::actionIndex();

        // New query
        $sql = 'SELECT shopping_cart_row_id, shop_item_id FROM `_shopping_cart_row` WHERE user_id = ' . 
            Yii::app()->user->getId();

        $r = Yii::app()->db->createCommand( $sql )->queryAll();

        foreach( $r as $a => $row )
        {

            // We actually have to instantiate the ShopItem
            // because we want to preserve its other properties
            $shopItem = ShopItem::model()->findByPk( $row[ 'shop_item_id' ] );
            
            if( $shopItem instanceOf ShopItem )
            {
                // UPDATE the state of the sales_item_id back to available
                $shopItem->shop_item_state_id = ShopItemState::SHOP_ITEM_AVAILABLE;
                $isRemoved = $shopItem->save();
                
                if( $isRemoved !== FALSE )
                {
                    ShopLog::logShopItem( $shopItem->shop_item_id, 'Removed From Cart' );
                }
                
            }

            // We can save the query to instantiate the ShoppingCartRow though
            // and just create it and delete the underlying row                
            $scr = ShoppingCartRow::model()
                    ->findByPk( $row[ 'shopping_cart_row_id' ] )
                    ->delete();
        }
            
        return $this->actionIndex();

    }    
    
    

    public function actionAdd_Item()
    {
        
        ShopWebPageController::showClosedSignIfShopClosed();
        ShopWebPageController::actionIndex();

        // Only if the shop item is available, will we add it to the cart
        if( $this->shop_item->shop_item_state_id == ShopItemState::SHOP_ITEM_AVAILABLE )
        {

            $newScr = new ShoppingCartRow();
            $newScr->shop_item_id = $this->shop_item->shop_item_id;
            $newScr->user_id = Yii::app()->user->getId();
            $newScr->date_added = new CDbExpression('UTC_TIMESTAMP()');
            $newScr->save();

            if( $newScr !== FALSE )
            {
                // 2. UPDATE the state of the shop_item to being in the cart
                $this->shop_item->shop_item_state_id = ShopItemState::SHOP_ITEM_IN_CART;
                $isAdded = $this->shop_item->save();
                
                if( $isAdded !== FALSE )
                {
                    ShopLog::logShopItem( $this->shop_item->shop_item_id, 'Added To Cart' );
                }

            }

        }

        return $this->actionIndex();
    }    



    public function actionAbandon_Order_Show_Shopping_Cart()
    {
        
        ShopWebPageController::showClosedSignIfShopClosed();
        ShopWebPageController::actionIndex();
            
        if( isset( $_GET[ 'order_ids' ] ) === TRUE )
        {

            $orderIDs = strval( $_GET[ 'order_ids' ] );
            
            $orderIDsAsArray = explode( ',', $orderIDs );
            
            if( empty( $orderIDsAsArray ) === FALSE )
            {
                foreach( $orderIDsAsArray as $c => $order_id )
                {
                    $order = Order::model()->findByAttributes( 
                        array(
                        'order_id' => $order_id,
                        'user_id' => Yii::app()->user->getId(),
                        )
                    );
                    
                    if( $order instanceOf Order )
                    {
                        $order->order_state_id = OrderState::ORDER_CANCELLED_BY_USER;
                        $order->save();
                    }
                }
            }
            
        }

        return $this->actionIndex();

    }    



    function showPayButtonIfItemsInCart()
    {
        $payButtonHtml = '';

        $user_id = Yii::app()->user->getId();
        
        if( WebPageController::countNumItemsInCart( $user_id ) > 0 )
        {
            $payButtonHtml .= 
                '<a href = "' .
                Yii::app()->request->baseUrl . '/userOrder/new/">' .
                '<button class = "btn btn-danger center">' .
                'Pay' .
                '</button>' .
                '</a>';
        }
        
        return $payButtonHtml;
        
    }

    
    
    /**
    * renderCart
    * Renders the view of the items in the user's cart, along with an X to remove
    * items.
    * 
    * @param mixed $params
    */
    
    public function renderCart( $params )
    {

        $html = '';

        // Get me the distinct user_ids of the sellers this buyer is purchasing from
        $userIDs = ShopWebPageController::getSellersUserIDsFromCart( $params[ 'user_id' ] );
        
        // Now we need to get CartRows for each seller_user_id
        foreach( $userIDs as $d => $sellerUserID )
        {

            $cartRows = ShopWebPageController::getCartRowsByBuyerIDAndSellerID( $params[ 'user_id' ], $sellerUserID );
            
            if( $cartRows > 0 )
            {

                // Get the template for a shopping cart restricted to a single seller but don't do
                // anything with it yet.
                $shoppingCartForSellerX = $this->renderPartial( '//shopCart/_shopping_cart_header', array(), true );
//                        'templates/shopping_cart_header.html' 

                $shoppingCartRows = '';

                $cartSingleRowTemplate = $this->renderPartial( '//shopCart/_shopping_cart_multiple_row', array(), true );
//                    'templates/shopping_cart_multiple_row.html' 
                
                // Start looping through the shopping_cart_rows
                for( $x = 0; $x < sizeof( $cartRows ); $x++ )
                {
                    
                    // There is only one item if we have gone down this branch                    
                    $shopItem = ShopItem::model()->findByPk( $cartRows[ $x ][ 'shop_item_id' ] );

                    // If the shop item contains a single product
                    if( $cartRows[ $x ][ 'count_included' ] == 1 )
                    {
                        $params[ 'template_filename' ] = '_shopping_cart_single_row';
                        $shoppingCartRows .= $this->showSingleRowView( 
                            $shopItem, 
                            $params 
                        );
                    }
                        
                        
                    // If the shop_item contains multiple products
                    if( $cartRows[ $x ][ 'count_included' ] > 1 )
                    {
                        // Get the template for the shopping cart for a
                        // MULTIPLE-SALES-ITEM shopItem
                        $cartMultipleRowTemplateCopy = $this->renderPartial( '//shopCart/_shopping_cart_multiple_row', array(), true );

                        $replacements[ '%SHOP_ITEM_ID%' ] = $shopItem->shop_item_id;
                        $replacements[ '%SHOP_ITEM_WEIGHT%' ] = $shopItem->shop_item_weight;
                        $replacements[ '%SHOP_SHOW_TITLE%' ] = $shopItem->shop_show_title;
                        $replacements[ '%SHOP_SHOW_DESCRIPTION%' ] = $shopItem->shop_show_description;
                        
                        // Get a formatted price for the shopItem
                        $priceParams[ 'price' ] = $shopItem->price;
                        $priceParams[ 'currency_html_code' ] = $shopItem->currency->currency_html_code;
                        $priceParams[ 'html_code_position' ] = $shopItem->currency->html_code_position;
                        $replacements[ '%PRICE%' ] = WebPageController::showPriceInCurrency( $priceParams );

                        // Now we need a different template for showing the buyer the included
                        // sales_items in this shop item
                        $params[ 'template_filename' ] = '_shopping_cart_included_sales_item';

                        $replacements[ '%SHOP_ITEM_CONTENTS%' ] = 
                            $this->showSingleRowView( $shopItem, $params );

                        // Search and replace all the placeholders in the ShopItem
                        foreach( $replacements as $search => $replace )
                        {
                            $cartMultipleRowTemplateCopy = str_replace( $search, $replace, $cartMultipleRowTemplateCopy );
                        }
                        
                        // Add the new row to the string
                        $shoppingCartRows .= $cartMultipleRowTemplateCopy;
                        
                    }

                
                }

                // Now the rows are finished, we merge them into the head template
                $replacements = array();
                
                $replacements[ '%SELLER_USERNAME%' ] = $cartRows[ 0 ][ 'seller_username' ];
                $replacements[ '%SHOPPING_CART_ROWS%' ] = $shoppingCartRows;

                foreach( $replacements as $search => $replace )
                {
                    $shoppingCartForSellerX = str_replace( $search, $replace, $shoppingCartForSellerX );
                }

                // Finally, add the new shoppingCart render to the html
                $html .= $shoppingCartForSellerX;
            }
            
        }
        
                
        return $html;
                     
    }
    
    

    public function showSingleRowView( ShopItem $shopItem, Array $params )
    {

        $shoppingCartHtmlTableRow = '';
        
        // Get the template for a single row with its placeholders
        $cartSingleRowTemplate = $this->renderPartial( '//shopCart/' . $params[ 'template_filename' ], array( 'shopItem' => $shopItem ), true );
//            'templates/' . $params[ 'template_filename' ]

        $replacements[ '%SHOP_ITEM_ID%' ] = $shopItem->shop_item_id;
        $replacements[ '%SHOP_ITEM_WEIGHT%' ] = $shopItem->shop_item_weight;
        $replacements[ '%SHOP_SHOW_TITLE%' ] = $shopItem->shop_show_title;

        $priceParams[ 'price' ] = $shopItem->price;
        $priceParams[ 'currency_html_code' ] = $shopItem->currency->currency_html_code;
        $priceParams[ 'html_code_position' ] = $shopItem->currency->html_code_position;
        $replacements[ '%PRICE%' ] = WebPageController::showPriceInCurrency( $priceParams );

        foreach( $shopItem->shopItemSalesItems as $c => $shopItemSalesItem )
        {
            
            // Make a copy of the template loaded
            $cartSingleRowTemplateCopy = $cartSingleRowTemplate;
            
            $replacements[ '%IMAGE_URL%' ] = 
                $shopItemSalesItem->salesItem->getImageUrl( 'images', 0 );

            $replacements[ '%PRODUCT_NAME%' ] = 
                $shopItemSalesItem->salesItem->product_name;

            $replacements[ '%WEIGHT%' ] = 
                $shopItemSalesItem->salesItem->weight;
            
            foreach( $replacements as $search => $replace )
            {
                $cartSingleRowTemplateCopy = str_replace( $search, $replace, $cartSingleRowTemplateCopy );
            }        
            
            $shoppingCartHtmlTableRow .= $cartSingleRowTemplateCopy;
            
        }

        return $shoppingCartHtmlTableRow;
        
    }
    
}
