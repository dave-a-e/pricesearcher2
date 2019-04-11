<?php

class ShopWebPageController extends ItemReportController
{

    public $shop_item_id;
    public $shop_item;

	public function showClosedSignIfShopClosed()
	{

        $isShopOpen = ConfigVariables::isShopOpen();
        
        if( $isShopOpen === FALSE )
        {

            $landingHtml = $this->render('application.views.shopWebPage.shop_closed',array(),true );

            // Do the searching and replacing        
            $replacements = array();

            foreach( $replacements as $search => $replace )
            {
                $landingHtml = str_replace( $search, $replace, $landingHtml );
            }

            print $landingHtml;
            die;
            
        }

    }



    public function getUrlParams()
    {

        $html = '';

        $comparitors = array( 
            'shop_item_state_id', 
            'machine_type_id', 
            'format_type_id', 
            'seller_user_id', 
            'publisher_id', 
            'sort', 
            'page',
            'num_per_page_max'
        );
        
        foreach( $comparitors as $key )
        {
            if( property_exists( get_class( $this ), $key ) === TRUE )
            {
                $html .= $key . '/' . $this->$key . '/';
            }
        }                        

        return $html;
        
    }



    public function actionIndex()
    {
        if( isset( $_GET[ 'shop_item_id' ] ) === TRUE )
        {
            $this->shop_item_id = intval( $_GET[ 'shop_item_id' ] );
            $this->shop_item = ShopItem::model()->findByPk( $this->shop_item_id );
        }
    }



    public static function getSellersUserIDsFromCart( $userID )
    {
        
        $sellerUserIDs = array();
        
        $sql = 'SELECT si.user_id AS seller_user_id FROM `_shopping_cart_row` scr 
            INNER JOIN `_shop_item` si ON scr.shop_item_id = si.shop_item_id
            WHERE scr.user_id = ' . $userID . '
            GROUP BY si.user_id
            ORDER BY si.user_id';

        $result = Yii::app()->db->createCommand( $sql )->queryAll();

        if( empty( $result ) === FALSE )
        {
            foreach( $result as $c => $row )
            {
                $sellerUserIDs[] = intval( $row[ 'seller_user_id' ] );
            }
        }

        return $sellerUserIDs;
        
    }



    public static function getCartRowsByBuyerIDAndSellerID( $buyerUserID, $sellerUserID )
    {

        // This query seems to get a lot of fields it does not need to!
        $sql = 
            'SELECT scr.shopping_cart_row_id, 
            scr.shop_item_id, 
            shi.user_id AS seller_user_id,
            u.username AS seller_username,
            scr.user_id, 
            scr.date_added, 
            shi.price, 
            COUNT( sisi.sales_item_id ) AS count_included
            FROM `_shopping_cart_row` scr
            INNER JOIN `_shop_item` shi ON scr.shop_item_id = shi.shop_item_id
            INNER JOIN `_shop_item_sales_item` sisi ON scr.shop_item_id = sisi.shop_item_id
            INNER JOIN `_user` u ON shi.user_id = u.user_id
            WHERE shi.shop_item_state_id = ' . ShopItemState::SHOP_ITEM_IN_CART . ' AND scr.user_id = ' . $buyerUserID . 
            ' GROUP BY scr.shopping_cart_row_id';
        
        return Yii::app()->db->createCommand( $sql )->queryAll();
    }
    
    
    
    public function showAlertsForThisOrder( $alerts )
    {
        // Set default to empty string
        $html = '';
        $template = $this->renderPartial( '//userOrder/_order_alert', array(), true );

        foreach( $alerts as $alertNum => $msg )
        {
            $thisAlertHtml = $template;

            $replacements = array();
            $replacements[ '%ALERT_MESSAGE%' ] = $msg;

            foreach( $replacements as $search => $replace )
            {
                $thisAlertHtml = str_replace( $search, $replace, $thisAlertHtml );
            }
            
            $html .= $thisAlertHtml;
        }    

        return $html;
    }
    
}
