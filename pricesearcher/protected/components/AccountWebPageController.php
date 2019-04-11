<?php

class AccountWebPageController extends WebPageController
{

    public static function showCountMyShopItemsInCarts( User $theUser )
    {
        
        $total = 0;

        $sql = 'SELECT COUNT(scr.shopping_cart_row_id) AS total FROM `_shop_item` si 
            INNER JOIN `_shopping_cart_row` scr ON si.shop_item_id = scr.shop_item_id
            WHERE si.shop_item_state_id = ' . ShopItemState::SHOP_ITEM_IN_CART;

        $r = Yii::app()->db->createCommand( $sql )->queryAll();
        
        if( empty( $r ) === FALSE )
        {
            $total = $r[ 0 ][ 'total' ];
        }

        $html = '';
        
        if( $total > 0 )
        {
            $html .= 'Currently there are ' . $total . ' of your shop items in the Shopping Carts of other users.';
            $html .= '&nbsp;';
            $html .= '<button class = "btn btn-danger" onclick = "releaseMyShopItemsInOtherUsersCartsController();">Release</button>';
        }

        return $html;
    }



    public static function renderCurrentAddress( User $user )
    {

        $html = '';
        
        $sql = 'SELECT a.address_id FROM `_user_address` ua 
            INNER JOIN `_address` a ON ua.address_id = a.address_id
            WHERE ua.user_id = ' . $user->user_id . ' AND a.address_state = ' . Address::STATE_ACTIVE . ' LIMIT 1';

        $result = Yii::app()->db->createCommand( $sql )->queryAll();
        
        if( empty( $result ) === FALSE )
        {
            $address = Address::model()->findByPk( $result[ 0 ][ 'address_id' ] );
            $html .= Address::renderAddress();
        }

        return $html;
    }
        
}