<?php

class WebPageController extends Controller
{

    public $layout = 'wl';
    public $pageTitle = '';
    public $metaTagContentLanguage = 'English';
    public $metaCopyright = 'Everygamegoing.com';
    public $metaKeywords = '';
    public $metaDescription = '';
    public $metaAuthor = '';


    
    public static function countOrdersReadyToSend()
    {
        $sql = 'SELECT COUNT(*) AS num_ready_to_send FROM `_order` WHERE order_state_id = ' . OrderState::ORDER_PAID_PENDING_DESPATCH;

        $numReadyToSend = 0;

        $result = Yii::app()->db->createCommand( $sql )->queryAll();
        
        if( empty( $result ) === FALSE )
        {
            $numReadyToSend = intval( $result[ 0 ][ 'num_ready_to_send' ] );
        }

        return $numReadyToSend;
        
    }
    
    /**
    * countNumItemsInCart
    * Extremely simple method that returns an integer of the
    * number of sales items in this user's cart at the current moment.
    * 
    * Remember that rows are added and removed from the shopping cart table
    * corresponding to when the user adds and removes them in the web
    * application. i.e. if you click 'Remove' (or the X) to remove an item
    * from your cart, the shopping_cart_row row for that item is DELETED
    * 
    * @param integer $userID
    * @return integer 
    */

    public static function countNumItemsInCart( $userID )
    {
        // Now we do the query
        $sql = 'SELECT COUNT(*) AS num_items_in_cart FROM `_shopping_cart_row` scr 
            INNER JOIN `_shop_item` shi ON scr.shop_item_id = shi.shop_item_id
            INNER JOIN `_user` u ON shi.user_id = u.user_id
            WHERE scr.user_id = ' . $userID . ' AND shi.shop_item_state_id = ' . ShopItemState::SHOP_ITEM_IN_CART;

        $numItemsInCart = 0;

        $result = Yii::app()->db->createCommand( $sql )->queryAll();
        
        if( empty( $result ) === FALSE )
        {
            $numItemsInCart = intval( $result[ 0 ][ 'num_items_in_cart' ] );
        }

        return $numItemsInCart;
    }



    public static function showPriceInCurrency( Array $params )
    {
        // Set html to be a blank string
        $htmlPrice = '';
        
        // Set the default position of the currency_html_code
        $htmlCodePosition = 'before';
        
        // If we have passed in a parameter of that key, then
        // alter the default to whatever we have passed in (before or after)
        if( isset( $params[ 'html_code_position' ] ) === TRUE )
        {
            $htmlCodePosition = $params[ 'html_code_position' ];
        }
        
        if( $htmlCodePosition == 'before' )
        {
            // If the position is at the front of the string, then
            // the first character(s) are the currency_html_code
            $htmlPrice .= $params[ 'currency_html_code' ];
        }
        
        $htmlPrice .= number_format( $params[ 'price' ], 2 );

        if( $htmlCodePosition == 'after' )
        {
            // If the position is at the end of the string, then
            // the last character(s) are the currency_html_code
            $htmlPrice .= ' ' . $params[ 'currency_html_code' ];
        }

        return $htmlPrice;
                
    }



    public static function showDate( $date, $format = 'jS M Y' )
    {
        if( $date == '0000-00-00 00:00:00' )
        {
            return '';
        }
        
        $d = new DateTime( date( $date ) );
        return $d->format( $format );
    }

        
}