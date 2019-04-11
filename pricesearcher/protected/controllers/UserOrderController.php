<?php

class UserOrderController extends ShopWebPageController
{

	public function actionNew()
	{
        
        ShopWebPageController::showClosedSignIfShopClosed();
        ShopWebPageController::actionIndex();

        $landingHtml = $this->render('new',array(),true );

        $user = User::model()->findByPk( Yii::app()->user->getId() );

        $orderIDs = array();

        // We have to determine the number of unique sellers the buyer is buying from first
        $sellerUserIDs = ShopWebPageController::getSellersUserIDsFromCart( $user->user_id );
        
        // If the sellerUserIDs is empty then fail fast
        if( empty( $sellerUserIDs ) === TRUE )
        {
            die( 'Unrecoverable error.' );
        }
        
        $replacements = array();

        foreach( $sellerUserIDs as $e => $sellerUserID )
        {
            
            $seller = User::model()->findByPk( $sellerUserID );
            
            // Create a new order for each of these sellers
            $theOrder = new Order();
            $theOrder->order_state_id = OrderState::ORDER_WAITING_FOR_PAYMENT;
            $theOrder->user_id = $user->user_id;
            $theOrder->seller_user_id = $seller->user_id;
            $theOrder->currency_id = 1; // Fudged because only single currency is supported
            $theOrder->paypal_token = '';
            $theOrder->transaction_id = '';
            $theOrder->total_price_ex_shipping = 0;
            $theOrder->total_price_inc_shipping = 0;
            $theOrder->shipping_materials = 0;
            $theOrder->shipping_cost = 0;
            
            if( $theOrder->save() == FALSE )
            {
                $msg = print_r($theOrder->getErrors(),1);
                
                // Give 500 error
                throw new CHttpException( 500, 'Order error.' );
            }

            $shoppingCartRows = ShopWebPageController::getCartRowsByBuyerIDAndSellerID( 
                Yii::app()->user->getId(), 1
            );

            if( empty( $shoppingCartRows ) === FALSE )
            {

                foreach( $shoppingCartRows as $x => $shoppingCartRowInfo )
                {

                    $newOrderPart = new OrderPart();
                    $newOrderPart->order_id = $theOrder->order_id;
                    $newOrderPart->shopping_cart_row_id = intval( $shoppingCartRowInfo[ 'shopping_cart_row_id' ] );
                    $newOrderPart->shop_item_id = intval( $shoppingCartRowInfo[ 'shop_item_id' ] );
                    $newOrderPart->save();
                }

            }
            
            $orderIDs[] = $theOrder->order_id;
            
        }

        $replacements[ '%ORDER_IDS%' ] = implode( ',', $orderIDs );

        // The defaults are blank
        $replacements[ '%USER_FIRST_NAME%' ] = '';
        $replacements[ '%USER_SURNAME%' ] = '';
        $replacements[ '%USER_ADDRESS_2%' ] = '';
        $replacements[ '%USER_ADDRESS_1%' ] = '';
        $replacements[ '%USER_ADDRESS_2%' ] = '';
        $replacements[ '%USER_ADDRESS_3%' ] = '';
        $replacements[ '%USER_CITY%' ] = '';
        $replacements[ '%USER_COUNTY%' ] = '';
        $replacements[ '%USER_POSTCODE%' ] = '';
        $selectedCountryID = 'GB';
        
        if( $user->userPersonalInfo instanceOf UserPersonalInfo )
        {
            $replacements[ '%USER_FIRST_NAME%' ] = $user->userPersonalInfo->user_first_name;
            $replacements[ '%USER_SURNAME%' ] = $user->userPersonalInfo->user_surname;
        }
        
        $existingAddress = UserOrderController::getExistingAddressForUserIfExists( 
            $user->user_id );
            
        if( $existingAddress instanceOf Address )
        {
            $replacements[ '%USER_ADDRESS_1%' ] = $existingAddress->address_1;
            $replacements[ '%USER_ADDRESS_2%' ] = $existingAddress->address_2;
            $replacements[ '%USER_ADDRESS_3%' ] = $existingAddress->address_3;
            $replacements[ '%USER_CITY%' ] = $existingAddress->city;
            $replacements[ '%USER_COUNTY%' ] = $existingAddress->county;
            $replacements[ '%USER_POSTCODE%' ] = $existingAddress->postcode;
            $selectedCountryID = $existingAddress->country_id;
        }

        $replacements[ '%COUNTRY_OPTIONS%' ] = '';
        
        $countries = Country::getAvailableCountries();
        
        foreach( $countries as $i => $row )
        {
            $replacements[ '%COUNTRY_OPTIONS%' ] .= '<option value = "' .
                $row[ 'country_id' ] . '"';
                
            if( $existingAddress instanceOf Address )
            {
                if( $row[ 'country_id' ] == $existingAddress->country_id )
                {
                    $replacements[ '%COUNTRY_OPTIONS%' ] .= ' selected';
                }
            }
            else
            {
                if( $row[ 'country_id' ] == 'GB' )
                {
                    $replacements[ '%COUNTRY_OPTIONS%' ] .= ' selected';
                }
            }
            
            $replacements[ '%COUNTRY_OPTIONS%' ] .= '>' . $row[ 'short_name' ] . '</option>';
        }

        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;
    
    }





    public function actionIn_Progress()
    {

        ShopWebPageController::showClosedSignIfShopClosed();
        ShopWebPageController::actionIndex();

        $alerts = array();

        $landingHtml = $this->render('in_progress',array(),true );

        $user = User::model()->findByPk( Yii::app()->user->getId() );

        $orderIDs = array();
        $orderIDsAsString = '';
        
        if( isset( $_GET[ 'order_ids' ] ) === TRUE )
        {
            $orderIDs = explode( ',', $_GET[ 'order_ids' ] );
            $orderIDsAsString = $_GET[ 'order_ids' ];
        }
        
        if( isset( $_POST[ 'order_ids' ] ) === TRUE )
        {
            $orderIDs = explode( ',', $_POST[ 'order_ids' ] );
            $orderIDsAsString = $_POST[ 'order_ids' ];
        }

        // We should divert the User back with a suitable error message here
        // if the Order ID is zero
        if( empty( $orderIDs ) === TRUE )
        {
            die( 'Unrecoverable error.' );
        }

        // If the user has entered a first_name and a last_name on his
        // order, but his current user details are blank, we will update
        // them for him (We're nice like that!)
        if( empty( $_POST[ 'address_surname' ] ) !== TRUE )
        {
            // If the user has already previous entered his personal information
            if( $user->userPersonalInfo instanceOf UserPersonalInfo )
            {
                // If his surname is blank, update it
                if( $user->userPersonalInfo->user_surname == '' )
                {
                    $user->userPersonalInfo->user_surname = trim( ucwords( strtolower( $_POST[ 'address_surname' ] ) ) );
                    $user->save();
                }

                // If his first_name is blank, update it
                if( $user->userPersonalInfo->user_first_name == '' )
                {
                    $user->userPersonalInfo->user_firat_name = trim( ucwords( strtolower( $_POST[ 'address_first_name' ] ) ) );
                    $user->save();
                }
            }
            else
            {
                $user->userPersonalInfo = new UserPersonalInfo();
                $user->userPersonalInfo->user_id = Yii::app()->user->getId();
                $user->userPersonalInfo->user_surname = trim( ucwords( strtolower( $_POST[ 'address_surname' ] ) ) );
                $user->userPersonalInfo->user_first_name = trim( ucwords( strtolower( $_POST[ 'address_first_name' ] ) ) );
                $user->userPersonalInfo->save();
            }
        }

        // Firstly, do we have this address already?
        $userAddress = UserOrderController::findExistingAddressOrCreateNewOne();

        if( $userAddress instanceOf Address )
        {
            $addressID = $userAddress->address_id;
            
            $htmlOrderContents = '';
            
            // Now create each Order                
            foreach( $orderIDs as $f => $orderID )
            {

                $theOrder = Order::model()->findByPk( $orderID );
                $theOrder->calculateWeight();
                $theOrder->calculateTotalPriceExShipping();

                $shipperBand = ShipperBand::getCheapestShipperBandByWeight( 
                    $userAddress->country_id, 
                    $theOrder->order_weight
                );

                if( $shipperBand === FALSE )
                {
                    throw new CHttpException( 500, 'Combined weight of ' . $theOrder->order_weight . ' too heavy.' );
                }

                $confirm = $this->saveOrderDetailsIfNotExists( $theOrder, $shipperBand, $addressID );

                if( $confirm === TRUE )
                {
                    // Update the user's order with the amounts he
                    // will be charged for shipping to his country
                    $theOrder->shipping_materials = Shipper::SHIPPING_MATERIALS_COST;
                    $theOrder->shipping_cost = $shipperBand->shipping_price;
                    $theOrder->total_price_inc_shipping = 
                        $theOrder->total_price_ex_shipping + 
                        $theOrder->shipping_cost + 
                        $theOrder->shipping_materials;
                    $theOrder->date_order_started = new CDbExpression('UTC_TIMESTAMP()');
                    $theOrder->save();
                }

                $replacements[ '%ORDER_ID%' ] = $theOrder->order_id;
                
                $params[ 'with_checkout' ] = TRUE;
                $params[ 'with_return_to_shopping_cart' ] = TRUE;
                $params[ 'html_filename' ] = '_html_order';
                $params[ 'order_ids_as_string' ] = $orderIDsAsString;
                
                $renderOrderForThisSeller = $this->renderOrder( $theOrder, $params );
                $htmlOrderContents .= $renderOrderForThisSeller;
            }

            $sellerUserIDs = ShopWebPageController::getSellersUserIDsFromCart( Yii::app()->user->getId() );
            
            if( sizeof( $sellerUserIDs ) > 1 )
            {
                $alerts[] = 'You are buying items from ' . sizeof( $sellerUserIDs ) . ' individuals. ' .
                    'Make sure that you authorise payment to each of them!';
            }

            $replacements[ '%ALERTS%' ] = $this->showAlertsForThisOrder( $alerts );
            $replacements[ '%FIRST_NAME%' ] = $userAddress->address_first_name;
            $replacements[ '%LAST_NAME%' ] = $userAddress->address_surname;
            $replacements[ '%ADDRESS_INFO%' ] = $userAddress->renderAddress();
            $replacements[ '%HTML_ORDER_CONTENTS%' ] = $htmlOrderContents;
            
        }
        
        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;
    }



    public function actionCheckout()
    {

        ShopWebPageController::showClosedSignIfShopClosed();
        ShopWebPageController::actionIndex();

        // Get the order (and check it belongs to this user)
        $theOrder = Order::model()->findByAttributes( 
            array( 
                'order_id' => $_GET[ 'order_id' ],
                'user_id' => Yii::app()->user->getId() ) 
        );
                
        // Report an error if not found
        if( !( $theOrder instanceOf Order ) )
        {
            $errorMessage = 'Your request to check out Order ID ' .
                $_GET[ 'order_id' ] . ' was rejected.';
            $this->reportCheckoutOrderFailure( $errorMessage );
        }

        // Set the order state to reflect that the checkout button was
        // clicked
        $theOrder->order_state_id = OrderState::ORDER_CHECKOUT_BUTTON_CLICKED;
        $theOrder->save();

        // Make sure that order has items in it
        if( empty( $theOrder->orderParts ) === TRUE )
        {
            $errorMessage = 'Unexpected error. The order does not contain any items.';
            $this->reportCheckoutOrderFailure( $errorMessage );
        }

        // If we reach here then we are good to go
        $thePaypalRequest = new Paypal();
        
        $thePaypalRequest->order_id = $theOrder->order_id;
        
        $paymentRequest0Amt = (string)number_format( $theOrder->total_price_inc_shipping, 2 );
        $thePaypalRequest->setOrderParams( 'PAYMENTREQUEST_0_AMT', $paymentRequest0Amt );

        $paymentRequest0ShippingAmt = (string)number_format( $theOrder->shipping_cost + $theOrder->shipping_materials, 2 );
        $thePaypalRequest->setOrderParams( 'PAYMENTREQUEST_0_SHIPPINGAMT', $paymentRequest0ShippingAmt );

        $thePaypalRequest->setOrderParams( 'PAYMENTREQUEST_0_CURRENCYCODE', $theOrder->currency->currency_iso_code );

        $paymentRequest0ItemAmt = (string)number_format( $theOrder->total_price_ex_shipping, 2 );
        $thePaypalRequest->setOrderParams( 'PAYMENTREQUEST_0_ITEMAMT', $paymentRequest0ItemAmt );

        $paymentRequest0Desc = 'Order ID #' . $theOrder->order_id . ' started ' . $theOrder->date_order_started;
        $thePaypalRequest->setOrderParams( 'PAYMENTREQUEST_0_DESC', urlencode( $paymentRequest0Desc ) );

        foreach( $theOrder->orderParts as $m => $orderPart )
        {

            $thePaypalRequest->setPaypalApiItems( 
                'L_PAYMENTREQUEST_0_NAME' . $m, 
                    urlencode( $theOrder->orderParts[ $m ]->shopItem->shop_show_title 
            ) );

            
            $paymentRequest0Desc =
                $theOrder->orderParts[ $m ]->shopItem->shop_show_title .
                ' (Shop Item ID ' . 
                $theOrder->
                    orderParts[ $m ]->
                    shop_item_id . 
                ')';

            $thePaypalRequest->setPaypalApiItems( 
                'L_PAYMENTREQUEST_0_DESC' . $m, urlencode( $paymentRequest0Desc ) );

            $thePaypalRequest->setPaypalApiItems( 'L_PAYMENTREQUEST_0_AMT' . $m, 
                (string)number_format( $theOrder->orderParts[ $m ]->shopItem->price, 2 ) );
            
            $thePaypalRequest->setPaypalApiItems( 'L_PAYMENTREQUEST_0_QTY' . $m, (string)1 );
        }

        $thePaypalRequest->createPostRequest();

        $thePaypalRequest->sendRequestToPaypal();
        
        $thePaypalRequest->populateResponseParams();
        
        $thePaypalRequest->storePaypalTokenIfReturned();

        // If we have not met with success we display the
        // failure
        if( $thePaypalRequest->getResponseParams( 'ACK' ) != 'Success' ) 
        {                        

            // Update the order to a connection failure
            $theOrder->order_state_id = OrderState::ORDER_PAYPAL_CONNECTION_FAILURE;
            $theOrder->save();

            // If we get here a general failure occurred
            $replacements[ '%FAILURE_REPORT%' ] = 
                '<p>Unfortunately, we were unable to connect to Paypal at the current time to process your Order, and therefore we cannot take payment.</p>' .
                '<p>You can <a href = "' . '/' .
                Yii::app()->request->baseUrl . 
                'userOrder/new/">try again</a> in a few moments (by which time Paypal may have resolved the problem), or if you are really desperate to secure these items ' .
                'you should send an e-mail to <a href = "mailto:dave_a_e@hotmail.com">dave_a_e@hotmail.com</a> stating that you were not able to pay.</p>';

            $this->reportCheckoutOrderFailure( $errorMessage );
        }

        // If all is well then relocate to Paypal
        $token = $thePaypalRequest->getResponseParams( 'TOKEN' );
        header( 'Location: https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=' . urlencode( $token ) );
        
    }



    /**
    * actionOrder_Cancelled()
    * This is the method hit if the user:
    * (a) clicks the checkout button, and relocates to Paypal
    * (b) Paypal validates the transaction and sends back a token
    * (c) the user then clicks the link on the Paypal page that
    * says 'Cancel and return to Everygamegoing'
    * 
    */
    
    public function actionOrder_Cancelled()
    {

        ShopWebPageController::showClosedSignIfShopClosed();
        ShopWebPageController::actionIndex();

        if( isset( $_GET[ 'token' ] ) === TRUE )
        {

            $theOrder = Order::model()->findByAttributes( array( 
                'paypal_token' => trim( $_GET[ 'token' ] ) ) );
            
            if( $theOrder instanceOf Order )
            {
                $confirmCancel = $theOrder->cancelOrder( FALSE );
            }

            $landingHtml = $this->render('cancelled',array(),true );

            $replacements = array();
            $replacements[ '%ORDER_ID%' ] = $theOrder->order_id;

            foreach( $replacements as $search => $replace )
            {
                $landingHtml = str_replace( $search, $replace, $landingHtml );
            }

            print $landingHtml;
            die;

        }

    }



    public function actionOrder_Success()
    {

        ShopWebPageController::showClosedSignIfShopClosed();
        ShopWebPageController::actionIndex();

        // Create PaypalObject
        $paypalObject = new Paypal();

        if( isset( $_GET[ 'token' ] ) === TRUE )
        {
            $theOrder = 
                Order::model()->findByAttributes( 
                    array( 
                        'paypal_token' => trim( $_GET[ 'token' ] ),
                        'user_id' => Yii::app()->user->getId(), 
                ) 
            );
                
            $order_id = $theOrder->order_id;
            $paypalObject->order_id = $theOrder->order_id;
            $paypalObject->setPaypalToken( $theOrder->paypal_token );
        }
        else
        {
            $orderInfo = UserOrderController::getMostRecentOrderIDOfUser();

            if( $orderInfo[ 'order_id' ] == FALSE )
            {
                print 'Failed to recover Order ID.';
                die;
            }

            $paypalObject->order_id = $orderInfo[ 'order_id' ];
            $paypalObject->setPaypalToken( $orderInfo[ 'paypal_token' ] );

            $theOrder = Order::model()->findByPk( $orderInfo[ 'order_id' ] );
        }



        if( empty( $theOrder->orderDetails ) === TRUE )
        {
            // If we get here a report that is not a success
            $replacements[ '%FAILURE_REPORT%' ] = 
                '<p>Could not set Order ID ' . $theOrder->order_id . ' ' .
                'to successfully paid, as the order reported it did ' .
                'not contain any items!</p>';

            $this->reportCheckoutOrderFailure( $errorMessage );
        }
        
            
        $paypalObject->getExpressCheckoutDetails();
        $paypalObject->sendRequestToPaypal();
        $paypalObject->populateResponseParams();
        
        // HACK
        $paypalObject->setResponseParams( 'ACK', 'Success' );
        
        // If we don't get success then we immediately skip out!
        if( $paypalObject->getResponseParams( 'ACK' ) != 'Success' )
        {
            $theOrder->order_state_id = OrderState::ORDER_PAYPAL_CONNECTION_FAILURE;
            $theOrder->save();

            // If we get here a report that is not a success
            $errorMessage = 
                '<p>Unfortunately, something went wrong getting the Express Checkout Details from Paypal from your Order.</p>' .
                '<p>Please notify the webmaster.</p>';

            $this->reportCheckoutOrderFailure( $errorMessage );
        }


        // Now we confirm the sale
        $paypalObject->setPayerID( $_GET[ 'PayerID' ] );
        $paypalObject->setPaypalApiItems( 'PAYMENTREQUEST_0_AMT', number_format( $theOrder->total_price_inc_shipping, 2 ) );
        $paypalObject->setPaypalApiItems( 'PAYMENTREQUEST_0_CURRENCYCODE', $theOrder->currency->currency_iso_code );
        $paypalObject->setPaypalApiItems( 'PAYMENTREQUEST_0_PAYMENTACTION' , "Sale" );
        $paypalObject->doExpressCheckout();
        $paypalObject->sendRequestToPaypal();
        $paypalObject->populateResponseParams();

        // HACK
        // $paypalObject->setResponseParams( 'ACK', 'Success' );

        if( $paypalObject->getResponseParams( 'ACK' ) != 'Success' )
        {
            // If we get here a report that is not a success
            $errorMessage = 
                '<p>Unfortunately, something went wrong and Paypal did not return the required Success code for us to mark your Order as Paid.</p>';
                
            $this->reportCheckoutOrderFailure( $errorMessage );
        }
        
        $theOrder->markOrderPaid();

        foreach( $theOrder->orderParts as $m => $orderPart )
        {
            $orderPart->shopItem->shop_item_state_id = 
                ShopItemState::SHOP_ITEM_SOLD;
                
            $orderPart->shopItem->save();
        }

        $sql = 'SELECT paypal_order_param_id FROM `_paypal_order_param` WHERE
            order_id = ' . $theOrder->order_id . 
            ' AND direction_id = ' . PaypalOrderParam::RECEIVED . 
            ' AND `key` = "TRANSACTIONID" LIMIT 1';

        $r = Yii::app()->db->createCommand( $sql )->queryAll();

        if( empty( $r ) === FALSE )
        {
            $transactionIDOrderParam = PaypalOrderParam::model()->findByPk( $r[ 0 ][ 'paypal_order_param_id' ] );

            if( $transactionIDOrderParam instanceOf PaypalOrderParam )
            {
                $theOrder->transaction_id = $transactionIDOrderParam->value;
                $theOrder->save();
            }            
        }

        /*            
        $successEmail = new EmailMessage();
        $successEmail->sendNotificationEmail( $theUser, $theOrder );
        */

        $landingHtml = $this->render('success',array(),true );

        $replacements = array();
        $replacements[ '%ORDER_ID%' ] = $theOrder->order_id;

        $params[ 'order' ] = $theOrder;
        $params[ 'html_filename' ] = '_html_order';
        $params[ 'with_checkout' ] = FALSE;
        $params[ 'with_return_to_shopping_cart' ] = FALSE;

        $replacements[ '%HTML_ORDER_CONTENTS%' ] = $this->renderOrder( 
            $theOrder, 
            $params 
        );

        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;
        
    }



    public function getMostRecentOrderIDOfUser()
    {

        $order_id = FALSE;
        $paypal_token = '';

        $sql = 'SELECT * FROM `_order` WHERE ' .
            'order_state_id = ' . OrderState::ORDER_CHECKOUT_BUTTON_CLICKED . 
            ' AND user_id = ' . 
            Yii::app()->user->getId() . 
            ' AND date_order_paid IS NULL ORDER BY date_order_started DESC LIMIT 1';

        $result = Yii::app()->db->createCommand( $sql )->queryAll();

        if( empty( $result ) === FALSE )
        {
            $order_id = $result[ 0 ][ 'order_id' ];
            $paypal_token = $result[ 0 ][ 'paypal_token' ];
        }

        return array( 'order_id' => $order_id, 'paypal_token' => $paypal_token );

    }



    public function reportCheckoutOrderFailure( $error_message )
    {

        $landingHtml = $this->render('checkout_order_failure',array(),true );

        $replacements[ '%ERROR_MESSAGE%' ] = $error_message;

        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;
    }



    public function renderOrder( Order $theOrder, Array $params )
    {
        // Check params
        $keys = array( 
            'html_filename', 
            'with_checkout', 
            'with_return_to_shopping_cart',
        );

        foreach( $keys as $key )
        {
            if( isset( $params[ $key ] ) === FALSE )
            {
                die( $params[ $key ] . ' not set!' );
            }
        }        

        $orderTemplate = $this->renderPartial( '//userOrder/' . $params[ 'html_filename' ], array(), true );

        $htmlOrderRows = '';

        $orderRowSingleItemTemplate = 
            $this->renderPartial( '//userOrder/_html_order_row_single_item', array(), true );
        
        $orderRowMultipleItemTemplate =
            $this->renderPartial( '//userOrder/_html_order_row_multiple_item', array(), true );
        
        if( empty( $theOrder->orderParts ) === FALSE )
        {

            for( $x = 0; $x < sizeof( $theOrder->orderParts ); $x++ )
            {
                
                $rep = array();

                if( sizeOf( $theOrder->orderParts[ $x ]->shopItem->shopItemSalesItems ) == 1 )
                {

                    $orderRowSingleItemTemplateCopy = $orderRowSingleItemTemplate;
                    
                    $rep[ '%IMAGE_URL%' ] = 
                        $theOrder->orderParts[ $x ]->shopItem->shopItemSalesItems[ 0 ]->salesItem->getImageUrl( 'images', 0 );

                    $rep[ '%SHOP_SHOW_TITLE%' ] = 
                        $theOrder->orderParts[ $x ]->shopItem->shop_show_title;
                        
                    $rep[ '%SHOP_ITEM_WEIGHT%' ] = 
                        $theOrder->orderParts[ $x ]->shopItem->shop_item_weight;

                    $priceParams = array();
                    $priceParams[ 'price' ] = 
                            $theOrder->
                            orderParts[ $x ]->
                            shopItem->
                            price;
                            
                    $priceParams[ 'currency_html_code' ] = 
                            $theOrder->
                            orderParts[ $x ]->
                            shopItem->
                            currency->
                            currency_html_code;
                        
                    $priceParams[ 'html_code_position' ] = 
                            $theOrder->
                            orderParts[ $x ]->
                            shopItem->
                            currency->
                            html_code_position;
                    
                    $rep[ '%PRICE%' ] = WebPageController::showPriceInCurrency( $priceParams );
                    
                    foreach( $rep as $s => $r )
                    {
                        $orderRowSingleItemTemplateCopy = str_replace( $s, $r, $orderRowSingleItemTemplateCopy );
                    }
                    
                    $htmlOrderRows .= $orderRowSingleItemTemplateCopy;
                    
                }
                
                if( sizeOf( $theOrder->orderParts[ $x ]->shopItem->shopItemSalesItems ) > 1 )
                {
                    
                    $orderRowMultipleItemTemplateCopy = $orderRowMultipleItemTemplate;

                    $rep[ '%IMAGE_URL%' ] = 
                        $theOrder->orderParts[ $x ]->shopItem->shopItemSalesItems[ 0 ]->salesItem->getImageUrl( 'images', 0 );

                    $rep[ '%SHOP_SHOW_TITLE%' ] = 
                        $theOrder->orderParts[ $x ]->shopItem->shop_show_title;
                        
                    $rep[ '%SHOP_ITEM_WEIGHT%' ] = 
                        $theOrder->orderParts[ $x ]->shopItem->shop_item_weight;

                    $priceParams = array();
                    $priceParams[ 'price' ] = 
                            $theOrder->
                            orderParts[ $x ]->
                            shopItem->
                            price;
                            
                    $priceParams[ 'currency_html_code' ] = 
                            $theOrder->
                            orderParts[ $x ]->
                            shopItem->
                            currency->
                            currency_html_code;
                        
                    $priceParams[ 'html_code_position' ] = 
                            $theOrder->
                            orderParts[ $x ]->
                            shopItem->
                            currency->
                            html_code_position;
                    
                    $rep[ '%PRICE%' ] = WebPageController::showPriceInCurrency( $priceParams );
                    
                    foreach( $rep as $s => $r )
                    {
                        $orderRowMultipleItemTemplateCopy = str_replace( $s, $r, $orderRowMultipleItemTemplateCopy );
                    }
                    
                    $htmlOrderRows .= $orderRowMultipleItemTemplateCopy;
                    
                }

            }
            
        }

        $theOrder->calculateWeight();
        
        $replacements[ '%ORDER_ROWS%' ] = $htmlOrderRows;
        $replacements[ '%ORDER_ID%' ] = $theOrder->order_id;
        $replacements[ '%TOTAL_WEIGHT%' ] = $theOrder->order_weight . 'g';
        
        $totalPriceParams = array();
        $totalPriceParams[ 'price' ] = $theOrder->total_price_ex_shipping;
        $totalPriceParams[ 'currency_html_code' ] = $theOrder->currency->currency_html_code;
        $totalPriceParams[ 'html_code_position' ] = $theOrder->currency->html_code_position;

        $replacements[ '%TOTAL_PRICE_EX_SHIPPING%' ] = 
            WebPageController::showPriceInCurrency( $totalPriceParams );
        $replacements[ '%SHIPPING_TRACKING_CODE%' ] = '';

        if( isset( $theOrder->orderShippingField->shipping_tracking_code ) === TRUE )
        {
            $replacements[ '%SHIPPING_TRACKING_CODE%' ] = $theOrder->orderShippingField->shipping_tracking_code;
        }

        $replacements[ '%SHIPPER_INFO%' ] = 'No Shipper Info';
        
        $defaultZeroPriceParams = array();
        $defaultZeroPriceParams[ 'price' ] = 0;
        $defaultZeroPriceParams[ 'currency_html_code' ] = $theOrder->currency->currency_html_code;
        $defaultZeroPriceParams[ 'html_code_position' ] = $theOrder->currency->html_code_position;
        
        $replacements[ '%SHIPPING_PRICE%' ] = 
            WebPageController::showPriceInCurrency( $defaultZeroPriceParams );
        $replacements[ '%SHIPPING_MATERIALS_COST%' ] = 
            WebPageController::showPriceInCurrency( $defaultZeroPriceParams );
        $replacements[ '%ORDER_TOTAL_WITH_SHIPPING%' ] = 
            WebPageController::showPriceInCurrency( $defaultZeroPriceParams );

        $replacements[ '%SHIPPER_INFO%' ] = 
            $theOrder->orderDetails->shipper->shipper_info;
        


        $shippingCostParams = array();
        $shippingCostParams[ 'price' ] = 
            $theOrder->shipping_cost;
        $shippingCostParams[ 'currency_html_code' ] = $theOrder->currency->currency_html_code;
        $shippingCostParams[ 'html_code_position' ] = $theOrder->currency->html_code_position;
        
        $replacements[ '%SHIPPING_PRICE%' ] = 
            WebPageController::showPriceInCurrency( $shippingCostParams );


        
        $shippingMaterialsPriceParams = array();
        $shippingMaterialsPriceParams[ 'price' ] = $theOrder->shipping_materials;
        $shippingMaterialsPriceParams[ 'currency_html_code' ] = $theOrder->currency->currency_html_code;
        $shippingMaterialsPriceParams[ 'html_code_position' ] = $theOrder->currency->html_code_position;
        
        $replacements[ '%SHIPPING_MATERIALS_COST%' ] = 
            WebPageController::showPriceInCurrency( $shippingMaterialsPriceParams );
        


        $orderTotalWithShippingPriceParams = array();
        $orderTotalWithShippingPriceParams[ 'price' ] = $theOrder->total_price_inc_shipping;
        $orderTotalWithShippingPriceParams[ 'currency_html_code' ] = $theOrder->currency->currency_html_code;
        $orderTotalWithShippingPriceParams[ 'html_code_position' ] = $theOrder->currency->html_code_position;
        
        $replacements[ '%ORDER_TOTAL_WITH_SHIPPING%' ] = 
            WebPageController::showPriceInCurrency( $orderTotalWithShippingPriceParams );
        

        // %ORDER_IDS_AS_STRING% is on the 'Return To Shopping Cart' button. If the user
        // clicks this, then all of his orders must be set to state 7 for technical reasons
        $replacements[ '%ORDER_IDS_AS_STRING%' ] = '';

        if( isset( $params[ 'order_ids_as_string' ] ) === TRUE )
        {
            $replacements[ '%ORDER_IDS_AS_STRING%' ] = $params[ 'order_ids_as_string' ];
        }
        
        foreach( $replacements as $search => $replace )
        {
            $orderTemplate = str_replace( $search, $replace, $orderTemplate );
        }

        if( $params[ 'with_checkout' ] == TRUE )
        {
            $checkoutWithPaypalHtml = 
                '<a href = "' .
                Yii::app()->request->baseUrl . '/' .
                'userOrder/checkout/order_id/' . $theOrder->order_id . '/">' .
                '<button class = "btn btn-primary btn-block">Checkout With Paypal</button></a>';

            $orderTemplate = str_replace( '%CHECKOUT_WITH_PAYPAL%', $checkoutWithPaypalHtml, $orderTemplate );
        }
        else
        {
            $orderTemplate = str_replace( '%CHECKOUT_WITH_PAYPAL%', '', $orderTemplate );
        }
    
        if( $params[ 'with_return_to_shopping_cart' ] == TRUE )
        {
            if( isset( $params[ 'order_ids_as_string' ] ) === TRUE )
            {
                $orderTemplate = str_replace( '%RETURN_TO_SHOPPING_CART%', 
                    '<a href = "' .
                    Yii::app()->request->baseUrl . '/' .
                    'shopCart/abandon_order_show_shopping_cart/order_ids/' . $params[ 'order_ids_as_string' ] . '">' .
                    '<button class = "btn btn-outline-info btn-block">' .
                    'Return To Shopping Cart' .
                    '</button></a>', $orderTemplate );
            }
        }
        else
        {
            $orderTemplate = str_replace( '%RETURN_TO_SHOPPING_CART%', '', $orderTemplate );
        }

        return $orderTemplate;
    }



    public function saveOrderDetailsIfNotExists( Order $theOrder, ShipperBand $shipperBand, $addressID )
    {

        $existingOrderDetails = OrderDetails::model()->findByAttributes( array(
		    'order_id' => $theOrder->order_id,
		    'address_id' => $addressID,
		    'shipper_id' => $shipperBand->shipper_id,
		    'shipper_band_id' => $shipperBand->shipper_band_id
         ) );

        if( !( $existingOrderDetails instanceOf OrderDetails ) )
        {
            $od = new OrderDetails();
            $od->order_id = $theOrder->order_id;
            $od->shipper_id = $shipperBand->shipper_id;
            $od->shipper_band_id = $shipperBand->shipper_band_id;
            $od->address_id = $addressID;
            
            if( $od->save() === FALSE )
            {
                throw new CHttpException( 500, 'Failed to save Order Details for ' .
                    $theOrder->order_id 
                );
            }
            
        }

        return TRUE;
        
    }



    public static function findExistingAddressOrCreateNewOne()
    {

        $sql = 'SELECT ua.address_id FROM `_user_address` ua 
            INNER JOIN `_address` a ON ua.address_id = a.address_id 
            WHERE ua.user_id = ' . Yii::app()->user->getId() . ' AND a.address_state_id = ' . AddressState::STATE_ACTIVE . ' ' . 
            ' AND a.postcode = "' . trim( $_POST[ 'postcode' ] ) . '" LIMIT 1';

        $r = Yii::app()->db->createCommand( $sql )->queryAll();
        
        if( empty( $r ) === FALSE )
        {
            $address = Address::model()->findByPk( $r[ 0 ][ 'address_id' ] );
        }
        else
        {
            // Otherwise we will create a new one
            if( isset( $_POST[ 'address_1' ] ) === TRUE )
            {
                $address = new Address();
                $address->address_state_id = AddressState::STATE_ACTIVE;
                $address->address_1 = trim( $_POST[ 'address_1' ] );

                if( isset( $_POST[ 'address_2' ] ) === TRUE )
                {
                    $address->address_2 = trim( $_POST[ 'address_2' ] );
                }

                if( isset( $_POST[ 'address_3' ] ) === TRUE )
                {
                    $address->address_3 = trim( $_POST[ 'address_3' ] );
                }
                
                if( isset( $_POST[ 'city' ] ) === TRUE )
                {
                    $address->city = trim( $_POST[ 'city' ] );
                }
                
                if( isset( $_POST[ 'county' ] ) === TRUE )
                {
                    $address->county = trim( $_POST[ 'county' ] );
                }

                if( isset( $_POST[ 'postcode' ] ) === TRUE )
                {
                    $address->postcode = trim( $_POST[ 'postcode' ] );
                }

                $address->country_id = 'GB';

                if( isset( $_POST[ 'country_id' ] ) === TRUE )
                {
                    $address->country_id = $_POST[ 'country_id' ];
                }

                $address->save();

                $newUserAddress = new UserAddress();
                $newUserAddress->user_id = Yii::app()->user->getId();
                $newUserAddress->address_id = $address->address_id;
                $newUserAddress->save();
            }
            
        }

        return $address;

    }



    public static function getExistingAddressForUserIfExists( $userID )
    {

        if( $userID > 0 )
        {

            $sql = 'SELECT ua.address_id FROM `_user_address` ua 
                INNER JOIN `_address` a ON ua.address_id = a.address_id 
                WHERE ua.user_id = ' . $userID . ' AND a.address_state_id = ' . AddressState::STATE_ACTIVE . ' ' . 
                ' LIMIT 1';

            $r = Yii::app()->db->createCommand( $sql )->queryAll();
            
            if( empty( $r ) === FALSE )
            {
                return Address::model()->findByPk( $r[ 0 ][ 'address_id' ] );
            }
                    
        }
        
        return FALSE;

    }
    
}
