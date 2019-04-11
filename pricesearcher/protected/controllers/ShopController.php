<?php

class ShopController extends ShopWebPageController
{
 
    public function actionIndex()
    {
        
        ShopWebPageController::showClosedSignIfShopClosed();

        $school = '';
        
        if( isset( $_GET[ 'school_id' ] ) === TRUE )
        {
            $school = School::model()->findByPk( $_GET[ 'school_id' ] );
        }

        if( $school instanceof School )
        {
            $landingHtml = $this->render('//shop/index', array( 'school' => $school ), true );
        }
        else
        {
            $landingHtml = $this->render('//shop/index', array(), true );
        }
        
        // Now we apply lots of changes to the html before we display it.
        
        // Do the searching and replacing        
        $replacements[ '%ALL_SHOP_ITEMS%' ] = ShopController::showAllShopItems();

        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;
    }



    public function productTypeFilter()
    {

        $html = '';
        
        $sql = 'SELECT si.product_type_id, CONCAT( pt.product_type_desc, "s" ) AS product_type_desc FROM `_shop_item` shi 
            INNER JOIN `_shop_item_sales_item` sisi ON shi.shop_item_id = sisi.shop_item_id
            INNER JOIN `_sales_item` si ON sisi.sales_item_id = si.sales_item_id
            INNER JOIN `_product_type` pt ON si.product_type_id = pt.product_type_id
            WHERE 
            pt.product_type_state_id = ' . ProductTypeState::STATE_ACTIVE . ' AND
            shi.shop_item_state_id = ' . ShopItemState::SHOP_ITEM_AVAILABLE . ' GROUP BY si.product_type_id
            ORDER BY pt.product_type_desc ASC';

        $r = Yii::app()->db->createCommand( $sql )->queryAll();
        
        if( empty( $r ) === FALSE )
        {
            $html .= '<div class="row align-items-center">
                        <div class="col text-center">
                            <div class="new_arrivals_sorting">
                                <ul class="arrivals_grid_sorting clearfix button-group filters-button-group">';


            $html .= '<li class="grid_sorting_button button d-flex flex-column justify-content-center align-items-center active is-checked" data-filter="*">all</li>';
                                
            foreach( $r as $i => $row )
            {
                $productType = ProductType::model()->findByPk( $row[ 'product_type_id' ] );
                
                $html .= '<li class="grid_sorting_button button d-flex flex-column justify-content-center align-items-center" ' .
                    'data-filter=".' . $productType->convertProductTypeDescToClass() . '">' .
                    $row[ 'product_type_desc' ] . '</li>';
            }                                

            $html .= '</ul>
                        </div>
                    </div>
                </div>';
            
        }
                
        return $html;
    }
    
    
    public function showAllShopItems()
    {

        $html = '';

        $sql = 'SELECT shi.shop_item_id FROM `_shop_item` shi
            INNER JOIN `_shop_item_sales_item` sisi ON shi.shop_item_id = sisi.shop_item_id
            INNER JOIN `_sales_item` si ON sisi.sales_item_id = si.sales_item_id
            INNER JOIN `_product_type` pt ON si.product_type_id = pt.product_type_id
            WHERE pt.product_type_state_id = ' . ProductTypeState::STATE_ACTIVE . ' AND
            shi.shop_item_state_id = ' . ShopItemState::SHOP_ITEM_AVAILABLE . ' GROUP BY shi.shop_item_id';

        $r = Yii::app()->db->createCommand( $sql )->queryAll();
        
        if( empty( $r ) === FALSE )
        {
            foreach( $r as $i => $row )
            {
                $shopItem = ShopItem::model()->findByPk( $row[ 'shop_item_id' ] );
                $html .= $this->renderPartial( '//shop/_shop_tile', array( 'shopItem' => $shopItem ), true );
            }
        }

        return $html;                            

    }
}