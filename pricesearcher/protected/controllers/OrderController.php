<?php

class OrderController extends AccountWebPageController
{

    const NUM_PER_PAGE_MAX = 50;

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
			'postOnly + delete', // we only allow deletion via POST request
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('index'),
				'users'=>array('*'),
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array(
                    'view_sold_order',
                    'update_sold_order',
                    'update_sold_order_confirm',
                    'list_failures',
                    'list_ready_to_send',
                    'list_sent',
                    'send_order_update_email_message',
                    ),
				'users'=>array('@'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}




    public function actionList_Ready_To_Send()
    {

        $params = array();
        $params[ 'sort' ] = 'o.date_order_paid';
        $params[ 'page' ] = WebPageResultsController::getThisPageFromGetParameter();
        $params[ 'num_per_page_max' ] = OrderController::NUM_PER_PAGE_MAX;
        $params[ 'list_php_page' ] = 'order/list_ready_to_send/';
        $params[ 'order_state_id' ] = OrderState::ORDER_PAID_PENDING_DESPATCH;

        $this->pageTitle = 'My Sold Items Ready To Send - ' . Yii::app()->name;
        
        $landingHtml = $this->render('list_ready_to_send',array(),true );

        $readyToSendOrders = OrderController::getAllOrdersByState( $params );
        $params[ 'total_number_of_hits' ] = $readyToSendOrders[ 'total_number_of_hits' ];

        $arrOfOrders = $readyToSendOrders[ 'search_results' ];
        
        // Do the searching and replacing        
        $replacements = array( 
            '%HTML_READY_TO_SEND_ORDERS%' => $this->renderOrderMenuAsHtml( $arrOfOrders ),
            '%HTML_READY_TO_SEND_PAGE_SELECTOR%' => WebPageResultsController::generatePageSelector( $params ),
        );

        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;

    }

    
    
    public function actionList_Sent()
    {

        $params = array();
        $params[ 'sort' ] = 'o.date_order_paid';
        $params[ 'page' ] = WebPageResultsController::getThisPageFromGetParameter();
        $params[ 'num_per_page_max' ] = OrderController::NUM_PER_PAGE_MAX;
        $params[ 'list_php_page' ] = 'order/list_ready_to_send/';
        $params[ 'order_state_id' ] = OrderState::ORDER_POSTED;

        $this->pageTitle = 'My Sold Items Posted - ' . Yii::app()->name;
        
        $landingHtml = $this->render('list_sent',array(),true );

        $readyToSendOrders = OrderController::getAllOrdersByState( $params );
        $params[ 'total_number_of_hits' ] = $readyToSendOrders[ 'total_number_of_hits' ];

        $arrOfOrders = $readyToSendOrders[ 'search_results' ];
        
        // Do the searching and replacing        
        $replacements = array( 
            '%HTML_READY_TO_SEND_ORDERS%' => $this->renderOrderMenuAsHtml( $arrOfOrders ),
            '%HTML_READY_TO_SEND_PAGE_SELECTOR%' => WebPageResultsController::generatePageSelector( $params ),
        );

        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;

    }



    public function actionList_Failures()
    {

        $params = array();
        $params[ 'sort' ] = 'o.order_id';
        $params[ 'page' ] = WebPageResultsController::getThisPageFromGetParameter();
        $params[ 'num_per_page_max' ] = OrderController::NUM_PER_PAGE_MAX;
        $params[ 'list_php_page' ] = 'order/list_failures/';

        $this->pageTitle = 'Failed Orders - ' . Yii::app()->name;
        
        $landingHtml = $this->render('list_failures',array(),true );

        $allOrders = OrderController::getAllFailedOrders( $params );
        $params[ 'total_number_of_hits' ] = $allOrders[ 'total_number_of_hits' ];

        $user = User::model()->findByPk( Yii::app()->user->getId() );
        
        $arrOfOrders = $allOrders[ 'search_results' ];
        
        // Do the searching and replacing        
        $replacements = array( 
            '%SHOP_ITEMS_IN_OTHER_USERS_SHOPPING_CARTS%' => AccountWebPageController::showCountMyShopItemsInCarts( $user ),
            '%HTML_ALL_ORDERS%' => $this->renderAllFailedOrdersHtml( $arrOfOrders ),
            '%HTML_ALL_ORDERS_PAGE_SELECTOR%' => WebPageResultsController::generatePageSelector( $params ),
        );

        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;

    }



    public function renderOrderMenuAsHtml( Array &$arrOfOrders )
    {

        $allHtml = '';
        
        if( empty( $arrOfOrders ) == TRUE )
        {
            $allHtml = '<p>You do not currently have any orders ready to send out.</p>';
            return $allHtml;
        }

        $emptyAdminOrderMenuTemplate = $this->renderPartial( 'my-ready-to-send-grid', array(), true );

        for( $i = 0; $i < sizeOf( $arrOfOrders ); $i++ )
        {
            $thisOrderObj = $arrOfOrders[ $i ][ 'order' ];
            
            $newRow = '<div class = "row">
                <div class = "col-lg-1">' . $thisOrderObj->order_id . '</div>
                <div class = "col-lg-2">' . WebPageController::showDate( $thisOrderObj->date_order_paid ) . '</div>
                <div class = "col-lg-5">Username: ' . $thisOrderObj->user->username . '<br />' .
                    'E-mail Address: ' . $thisOrderObj->user->showActiveEmail() . '<br />' .
                    'Paypal Transaction ID: ' .$thisOrderObj->transaction_id . '</div>
                <div class = "col-lg-4">';

            $newRow .= OrderController::showShowButton( $thisOrderObj );
            $newRow .= OrderController::showSoldOrderOptions( $thisOrderObj );
            $newRow .= '</div>
            </div>';

            $allHtml .= $newRow;
            
        }

        $ordersHtml = str_replace( '%ORDERS%', $allHtml, $emptyAdminOrderMenuTemplate );

        return $ordersHtml;

    }



    public function renderAllFailedOrdersHtml( Array &$arrOfOrders )
    {

        $allHtml = '';
        
        if( empty( $arrOfOrders ) == TRUE )
        {
            $allHtml = '<p>You do not currently have any orders.</p>';
            return $allHtml;
        }

        $emptyAdminOrderMenuTemplate = $this->renderPartial( '_my_orders_grid', array(), true );

        for( $i = 0; $i < sizeOf( $arrOfOrders ); $i++ )
        {
            $thisOrderObj = $arrOfOrders[ $i ][ 'order' ];
            
            $newRow = '<div class = "row">
                <div class = "col-lg-1">' . $thisOrderObj->order_id . '</div>
                <div class = "col-lg-2">' . $thisOrderObj->orderState->order_state_desc . '</div>
                <div class = "col-lg-2">' . $thisOrderObj->user->username . '</div>
                <div class = "col-lg-3">' . $thisOrderObj->user->showActiveEmail() . '</div>
                <div class = "col-lg-4">';

            $newRow .= OrderController::showShowButton( $thisOrderObj );

            $newRow .= '</div>
            </div>';

            $allHtml .= $newRow;
            
        }

        $ordersHtml = str_replace( '%ORDERS%', $allHtml, $emptyAdminOrderMenuTemplate );

        return $ordersHtml;

    }



    public function actionSend_Order_Update_Email_Message()
    {
        $order = Order::model()->findByAttributes( array( 
            'order_id' => $_GET[ 'order_id' ],
            'seller_user_id' => Yii::app()->user->getId() ) );
        OrderController::sendUpdateEmailMessage( $order );
        die;
    }



    public static function sendUpdateEmailMessage( Order $order )
    {

        $emailMessage = new EmailMessage();
        $emailMessage->to = strip_tags( 
            $order->user->emailAddress->email_address );
        $emailMessage->email_message_state_id = 
            EmailMessageState::STATE_NOT_SENT;
        $emailMessage->email_message_type_id = 
            EmailMessageType::TYPE_PASSWORD_REMINDER;
        $emailMessage->to_email_address_id = 
            $order->user->emailAddress->email_address_id;
        $emailMessage->subject = 
            'Order #' . $order->order_id . ' Updated On ' . ConfigVariables::SITE_NAME;
        $emailMessage->message = 
            $order->renderBasicOrderUpdateMessage();
        $emailMessage->date_modified = 
            new CDbExpression('UTC_TIMESTAMP()');
        $emailMessage->save();
        $emailMessage->sendIt();
    }



    public static function showShowButton( Order $thisOrderObj )
    {
        $html = '';
        $html .= '<a href = "' . 
            Yii::app()->request->baseUrl . '/order/view_sold_order/id/' . $thisOrderObj->order_id . '"><button class = "btn btn-info btn-sm">Show</button></a>';
        $html .= '&nbsp;';
        
        return $html;
    }



    public function renderSoldOrder( Order $order )
    {

        $orderTemplate = 
            $this->renderPartial( '_html_sold_order', array( 'order' => $order ), true );

        $htmlOrderRows = '';

        $orderRowSingleItemTemplate = 
            $this->renderPartial( '//userOrder/_html_order_row_single_item', array(), true );
        
        $orderRowMultipleItemTemplate = 
            $this->renderPartial( '//userOrder/_html_order_row_multiple_item', array(), true );
        
        if( empty( $order->orderParts ) === FALSE )
        {

            for( $x = 0; $x < sizeof( $order->orderParts ); $x++ )
            {
                
                $rep = array();

                if( sizeOf( $order->orderParts[ $x ]->shopItem->shopItemSalesItems ) == 1 )
                {

                    $orderRowSingleItemTemplateCopy = $orderRowSingleItemTemplate;
                    
                    $rep[ '%IMAGE_URL%' ] = 
                        $order->orderParts[ $x ]->shopItem->shopItemSalesItems[ 0 ]->salesItem->getImageUrl( 'images', 0 );

                    $rep[ '%SHOP_ITEM_ID%' ] = 
                        $order->orderParts[ $x ]->shopItem->shop_item_id;

                    $rep[ '%SHOP_SHOW_TITLE%' ] = 
                        $order->orderParts[ $x ]->shopItem->shop_show_title;
                        
                    $rep[ '%SHOP_SHOW_DESCRIPTION%' ] = 
                        $order->orderParts[ $x ]->shopItem->shop_show_description;
                        
                    $rep[ '%SHOP_ITEM_WEIGHT%' ] = 
                        $order->orderParts[ $x ]->shopItem->shop_item_weight;

                    $priceParams = array();
                    $priceParams[ 'price' ] = 
                            $order->
                            orderParts[ $x ]->
                            shopItem->
                            price;
                            
                    $priceParams[ 'currency_html_code' ] = 
                            $order->
                            orderParts[ $x ]->
                            shopItem->
                            currency->
                            currency_html_code;
                        
                    $priceParams[ 'html_code_position' ] = 
                            $order->
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
                
                if( sizeOf( $order->orderParts[ $x ]->shopItem->shopItemSalesItems ) > 1 )
                {
                    
                    $orderRowMultipleItemTemplateCopy = $orderRowMultipleItemTemplate;

                    $rep[ '%IMAGE_URL%' ] = 
                        $order->orderParts[ $x ]->shopItem->shopItemSalesItems[ 0 ]->salesItem->getImageUrl( 'images', 0 );

                    $rep[ '%SHOP_ITEM_ID%' ] = 
                        $order->orderParts[ $x ]->shopItem->shop_item_id;

                    $rep[ '%SHOP_SHOW_TITLE%' ] = 
                        $order->orderParts[ $x ]->shopItem->shop_show_title;

                    $rep[ '%SHOP_SHOW_DESCRIPTION%' ] = 
                        $order->orderParts[ $x ]->shopItem->shop_show_description;
                        
                    $rep[ '%SHOP_ITEM_WEIGHT%' ] = 
                        $order->orderParts[ $x ]->shopItem->shop_item_weight;

                    $priceParams = array();
                    $priceParams[ 'price' ] = 
                            $order->
                            orderParts[ $x ]->
                            shopItem->
                            price;
                            
                    $priceParams[ 'currency_html_code' ] = 
                            $order->
                            orderParts[ $x ]->
                            shopItem->
                            currency->
                            currency_html_code;
                        
                    $priceParams[ 'html_code_position' ] = 
                            $order->
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

        $order->calculateWeight();
        
        $replacements[ '%ORDER_ROWS%' ] = $htmlOrderRows;
        $replacements[ '%ORDER_ID%' ] = $order->order_id;
        $replacements[ '%TOTAL_WEIGHT%' ] = $order->order_weight . 'g';
        $replacements[ '%FIRST_NAME%' ] = '';
        $replacements[ '%LAST_NAME%' ] = '';
        $replacements[ '%ADDRESS_INFO%' ] = '';

        if( isset( $order->orderDetails->address ) === TRUE )
        {
            if( $order->orderDetails->address instanceOf Address )
            {
                $replacements[ '%FIRST_NAME%' ] = $order->orderDetails->address->address_first_name;
                $replacements[ '%LAST_NAME%' ] = $order->orderDetails->address->address_surname;
                $replacements[ '%ADDRESS_INFO%' ] = $order->orderDetails->address->renderAddress();
            }
        }

        $totalPriceParams = array();
        $totalPriceParams[ 'price' ] = $order->total_price_ex_shipping;
        $totalPriceParams[ 'currency_html_code' ] = $order->currency->currency_html_code;
        $totalPriceParams[ 'html_code_position' ] = $order->currency->html_code_position;

        $replacements[ '%TOTAL_PRICE_EX_SHIPPING%' ] = 
            WebPageController::showPriceInCurrency( $totalPriceParams );
        $replacements[ '%SHIPPING_TRACKING_CODE%' ] = '';

        if( isset( $order->orderShippingField->shipping_tracking_code ) === TRUE )
        {
            $replacements[ '%SHIPPING_TRACKING_CODE%' ] = $order->orderShippingField->shipping_tracking_code;
        }

        $replacements[ '%SHIPPER_INFO%' ] = 'No Shipper Info';
        
        $defaultZeroPriceParams = array();
        $defaultZeroPriceParams[ 'price' ] = 0;
        $defaultZeroPriceParams[ 'currency_html_code' ] = $order->currency->currency_html_code;
        $defaultZeroPriceParams[ 'html_code_position' ] = $order->currency->html_code_position;
        
        $replacements[ '%SHIPPING_PRICE%' ] = 
            WebPageController::showPriceInCurrency( $defaultZeroPriceParams );
        $replacements[ '%SHIPPING_MATERIALS_COST%' ] = 
            WebPageController::showPriceInCurrency( $defaultZeroPriceParams );
        $replacements[ '%ORDER_TOTAL_WITH_SHIPPING%' ] = 
            WebPageController::showPriceInCurrency( $defaultZeroPriceParams );

        $replacements[ '%SHIPPER_INFO%' ] = '';

        if( isset( $order->orderDetails->shipper ) === TRUE )
        {
            $replacements[ '%SHIPPER_INFO%' ] = 
                $order->orderDetails->shipper->shipper_info;
        }

        $shippingCostParams = array();
        $shippingCostParams[ 'price' ] = 
            $order->shipping_cost;
        $shippingCostParams[ 'currency_html_code' ] = $order->currency->currency_html_code;
        $shippingCostParams[ 'html_code_position' ] = $order->currency->html_code_position;
        
        $replacements[ '%SHIPPING_PRICE%' ] = 
            WebPageController::showPriceInCurrency( $shippingCostParams );


        $shippingMaterialsPriceParams = array();
        $shippingMaterialsPriceParams[ 'price' ] = $order->shipping_materials;
        $shippingMaterialsPriceParams[ 'currency_html_code' ] = $order->currency->currency_html_code;
        $shippingMaterialsPriceParams[ 'html_code_position' ] = $order->currency->html_code_position;
        
        $replacements[ '%SHIPPING_MATERIALS_COST%' ] = 
            WebPageController::showPriceInCurrency( $shippingMaterialsPriceParams );
        


        $orderTotalWithShippingPriceParams = array();
        $orderTotalWithShippingPriceParams[ 'price' ] = $order->total_price_inc_shipping;
        $orderTotalWithShippingPriceParams[ 'currency_html_code' ] = $order->currency->currency_html_code;
        $orderTotalWithShippingPriceParams[ 'html_code_position' ] = $order->currency->html_code_position;
        
        $replacements[ '%ORDER_TOTAL_WITH_SHIPPING%' ] = 
            WebPageController::showPriceInCurrency( $orderTotalWithShippingPriceParams );

        foreach( $replacements as $search => $replace )
        {
            $orderTemplate = str_replace( $search, $replace, $orderTemplate );
        }

        return $orderTemplate;
    }



    public static function showSoldOrderOptions( Order $thisOrderObj )
    {

        $html = '<a href = "' . 
            Yii::app()->request->baseUrl . '/' .
            'order/update_sold_order/id/' . $thisOrderObj->order_id . '"><button class = "btn btn-primary btn-sm">Edit</button></a>';
        
        if( $thisOrderObj->hasBeenSubmittedAtLeastOnceToPaypal() === TRUE )
        {
            $html .= '&nbsp;<a href = "' . 
                Yii::app()->request->baseUrl . '/paypalOrderParam/list/order_id/' . $thisOrderObj->order_id . '"><button class = "btn btn-info btn-sm">Paypal Log</button></a>';
        }

        $html .= '&nbsp;';
        $html .= '<a href = "' . 
            Yii::app()->request->baseUrl . '/order/send_order_update_email_message/order_id/' . $thisOrderObj->order_id . '"><button class = "btn btn-danger btn-sm">Send Update</button></a>';
        $html .= '&nbsp;';
        $html .= '<a href = "' . 
            Yii::app()->request->baseUrl . '/order/list/user_id/' . $thisOrderObj->user->user_id . '/"><button class = "btn btn-info btn-sm">List All Orders By This User (NW)</button></a>';

        return $html;
    }



    public static function getAllOrdersByState( Array &$params )
    {

        $arrOfOrders = array();

        $sortOrder = $params[ 'sort' ];
        $thisPage = $params[ 'page' ];
        $firstLimitParam = $params[ 'page' ] * $params[ 'num_per_page_max' ];

        // Create the Order By Clause for the end of the query
        $orderByClause = WebPageResultsController::formOrderByClause( $sortOrder );

        $txn = Yii::app()->db->beginTransaction();

        $sql = 'SELECT SQL_CALC_FOUND_ROWS o.order_id FROM `_order` o
            WHERE o.order_state_id = ' . 
            $params[ 'order_state_id' ] . ' ' .
            $orderByClause . 
            ' LIMIT ' . $firstLimitParam . ', ' . $params[ 'num_per_page_max' ];

        $result = Yii::app()->db->createCommand( $sql )->queryAll();
        $txn->commit();

        $totalNumberOfHits = WebPageResultsController::getFoundRows();

        if( empty( $result ) === FALSE )
        {
            foreach( $result as $i => $row )
            {
                $result[ $i ][ 'order' ] = Order::model()->findByPk( $row[ 'order_id' ] );
            }
        }

        return array( 'search_results' => $result, 'total_number_of_hits' => $totalNumberOfHits );
        
    }




    public static function getAllFailedOrders( Array &$params )
    {

        $arrOfOrders = array();

        $sortOrder = $params[ 'sort' ];
        $thisPage = $params[ 'page' ];
        $firstLimitParam = $params[ 'page' ] * $params[ 'num_per_page_max' ];

        // Create the Order By Clause for the end of the query
        $orderByClause = WebPageResultsController::formOrderByClause( $sortOrder );

        $txn = Yii::app()->db->beginTransaction();

        $sql = 'SELECT SQL_CALC_FOUND_ROWS o.order_id FROM `_order` o
            WHERE o.order_state_id NOT IN ( ' .
            OrderState::ORDER_PAID_PENDING_DESPATCH . ', ' . OrderState::ORDER_POSTED . 
            ') ' .
            $orderByClause . 
            ' LIMIT ' . $firstLimitParam . ', ' . $params[ 'num_per_page_max' ];

        $result = Yii::app()->db->createCommand( $sql )->queryAll();
        $txn->commit();

        $totalNumberOfHits = WebPageResultsController::getFoundRows();

        if( empty( $result ) === FALSE )
        {
            foreach( $result as $i => $row )
            {
                $result[ $i ][ 'order' ] = Order::model()->findByPk( $row[ 'order_id' ] );
            }
        }

        return array( 'search_results' => $result, 'total_number_of_hits' => $totalNumberOfHits );
        
    }



	public function actionView_Sold_Order($id)
	{

        $order = Order::model()->findByPk( array( 
            'order_id' => $id ) );

		$html = $this->render('view_sold_order',array(
			'order'=>$order,
		),true);

        $replacements[ '%ORDER_ID%' ] = $order->order_id;
        $replacements[ '%ORDER_OPTIONS%' ] = OrderController::showSoldOrderOptions( $order );
        $replacements[ '%ORDER_STATE%' ] = $order->orderState->order_state_desc;
        $replacements[ '%ORDER_STATE_ID%' ] = $order->order_state_id;
        $replacements[ '%USERNAME%' ] = $order->user->username;
        $replacements[ '%SELLER_USER_ID%' ] = $order->seller_user_id;
        $replacements[ '%PAYPAL_TOKEN%' ] = $order->paypal_token;
        $replacements[ '%TRANSACTION_ID%' ] = $order->transaction_id;
        $replacements[ '%DATE_ORDER_STARTED%' ] = $order->date_order_started;
        $replacements[ '%DATE_ORDER_PAID%' ] = $order->date_order_paid;
        $replacements[ '%DATE_ORDER_SHIPPED%' ] = $order->date_order_shipped;
        $replacements[ '%SHIPPING_METHOD_NAME%' ] = ''; // Deliberately blank

        if( isset( $order->orderShippingField->shippingMethod->shipping_method_name ) == TRUE )
        {
            $replacements[ '%SHIPPING_METHOD_NAME%' ] = 
                $order->orderShippingField->shippingMethod->shipping_method_name;
        }

        $replacements[ '%SHIPPING_TRACKING_CODE%' ] = ''; // Deliberately blank

        if( isset( $order->orderShippingField->shipping_tracking_code ) == TRUE )
        {
            $replacements[ '%SHIPPING_TRACKING_CODE%' ] = $order->orderShippingField->shipping_tracking_code;
        }

        $replacements[ '%HTML_SALE_ORDER%' ] = $this->renderSoldOrder( $order );

        foreach( $replacements as $search => $replace )
        {
            $html = str_replace( $search, $replace, $html );
        }        
        
        print $html;
        die;
	}



    public function actionUpdate_Sold_Order()
    {

        $order = Order::model()->findByPk( $_GET[ 'id' ] );

        $html = $this->render('update_sold_order',array(
            'order'=>$order,
        ),true);

        $replacements[ '%ORDER_ID%' ] = $order->order_id;
        $replacements[ '%ORDER_STATE_ID_OPTIONS%' ] = OrderController::assessOrderStateAndPresentLogicalOptions( $order );
        $replacements[ '%SHIPPING_METHOD_ID_OPTIONS%' ] = OrderController::getShippingMethodsAsKeyValuePairs( $order );
        $replacements[ '%SHIPPING_TRACKING_CODE%' ] = ''; // Deliberately blank

        if( isset( $order->orderShippingField->shipping_tracking_code ) == TRUE )
        {
            $replacements[ '%SHIPPING_TRACKING_CODE%' ] = $order->orderShippingField->shipping_tracking_code;
        }

        foreach( $replacements as $search => $replace )
        {
            $html = str_replace( $search, $replace, $html );
        }        
        
        print $html;
        die;
    }



    public function actionUpdate_Sold_Order_Confirm()
    {

        $order = Order::model()->findByPk( $_POST[ 'order_id' ] );

        // We want to update the date_shipped when the order_state_id is changed to shipped
        if( $order->order_state_id == OrderState::ORDER_PAID_PENDING_DESPATCH && $_POST[ 'order_state_id' ] == OrderState::ORDER_POSTED )
        {
            $order->date_order_shipped = date( 'Y-m-d H:i:s' );
        }

        $order->order_state_id = intval( $_POST[ 'order_state_id' ] );
        $order->save();

        $osf = OrderShippingField::model()->findByAttributes( array( 'order_id' => $order->order_id ) );

        if( $osf instanceOf OrderShippingField )
        {

            if( isset( $_POST[ 'shipping_method_id' ] ) === TRUE )
            {
                if( intval( $osf->shipping_method_id ) !== intval( $_POST[ 'shipping_method_id' ] ) )
                {
                    $osf->shipping_method_id = intval( $_POST[ 'shipping_method_id' ] );
                }
            }

            if( 
                ( isset( $_POST[ 'shipping_tracking_code' ] ) === TRUE ) 
                && 
                ( empty( $_POST[ 'shipping_tracking_code' ] ) === FALSE ) 
                )
            {
                if( $osf->shipping_tracking_code != trim( $_POST[ 'shipping_tracking_code' ] ) )
                {
                    $osf->shipping_tracking_code = trim( $_POST[ 'shipping_tracking_code' ] );
                }
            }

            $osf->save();        
        }
        else
        {
            $newOrderShippingField = new OrderShippingField();
            $newOrderShippingField->order_id = $order->order_id;
            $newOrderShippingField->shipping_method_id = intval( $_POST[ 'shipping_method_id' ] );
            $newOrderShippingField->shipping_tracking_code = trim( $_POST[ 'shipping_tracking_code' ] );
            $newOrderShippingField->save();
        }

        header( 'Location: ' . Yii::app()->request->baseUrl . '/order/view_sold_order/id/' . $_POST[ 'order_id' ] . '/' );

    }



    public static function assessOrderStateAndPresentLogicalOptions( Order $order )
    {
        $options = array();

        if( $order->order_state_id == OrderState::ORDER_PAID_PENDING_DESPATCH )
        {
            $options[ 'Paid (Pending Dispatch)' ] = OrderState::ORDER_PAID_PENDING_DESPATCH;
            $options[ 'Posted' ] = OrderState::ORDER_POSTED;
        }
        
        if( $order->order_state_id == OrderState::ORDER_POSTED )
        {
            $options[ 'Paid (Pending Dispatch)' ] = OrderState::ORDER_PAID_PENDING_DESPATCH;
            $options[ 'Posted' ] = OrderState::ORDER_POSTED;
            $options[ 'Lost In Transit' ] = OrderState::ORDER_LOST_IN_TRANSIT;
        }

        if( $order->order_state_id == OrderState::ORDER_LOST_IN_TRANSIT )
        {
            $options[ 'Lost In Transit' ] = OrderState::ORDER_LOST_IN_TRANSIT;
            $options[ 'Paid (Pending Dispatch)' ] = OrderState::ORDER_PAID_PENDING_DESPATCH;
            $options[ 'Posted' ] = OrderState::ORDER_POSTED;
        }

        $html = '';
        
        if( empty( $options ) === FALSE )
        {
            foreach( $options as $desc => $order_state_id )
            {
                $html .= '<option value = "';
                $html .= $order_state_id;

                if( intval( $order->order_state_id ) === intval( $order_state_id ) )
                {
                    $html .= 'selected';
                }

                $html .= '">';
                $html .= $desc;
                $html .= '</option>' . "\n";
            }
        }
        
        return $html;
    }


    public static function getShippingMethodsAsKeyValuePairs( Order $order )
    {

        $html = '';

        $sql = 'SELECT shipping_method_id, shipping_method_name FROM `_shipping_method`
            WHERE shipping_method_state_id = 1';
                                    
        $result = Yii::app()->db->createCommand( $sql )->queryAll();

        if( empty( $result ) === FALSE )
        {
            foreach( $result as $i => $row )
            {
                $html .= '<option value = "' . $row[ 'shipping_method_id' ] . '"';
                
                if( isset( $order->orderShippingField->shipping_method_id ) === TRUE )
                {
                    if( intval( $row[ 'shipping_method_id' ] ) === intval( $order->orderShippingField->shipping_method_id ) )
                    {
                        $html .= 'selected';
                    }
                }

                $html .= '>' . $row[ 'shipping_method_name' ] . '</option>' . "\n";
            }
        }

        return $html;
    }

}
