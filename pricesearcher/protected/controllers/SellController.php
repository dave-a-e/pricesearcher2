<?php

class SellController extends WebPageController
{

//    const NUM_PER_PAGE_MAX = 50;
    const NUM_PER_PAGE_MAX = 8;

    public function getCommonReplacements( SalesItem $salesItem )
    {
        
        $replacements = array( 
            '%SALES_ITEM_ID%' => $salesItem->sales_item_id,
            '%PRODUCT_NAME%' => $salesItem->product_name,
            '%SALES_ITEM_PRICE%' => number_format( $salesItem->price, 2 ),
        );

        return $replacements;
    }



    public function actionIndex()
    {
        
        $salesItem = SalesItem::model()->findByPk( $_GET[ 'sales_item_id' ] );
        
        $html = $this->render( 'index', array( 'model'=>$salesItem ), true );
                
        $replacements = $this->getCommonReplacements( $salesItem );

        foreach( $replacements as $search => $replace )
        {
            $html = str_replace( $search, $replace, $html );
        }

        print $html;
        die;
    }


    


    public function actionIndividual_Step_1()
    {

        $salesItem = SalesItem::model()->findByPk( $_GET[ 'sales_item_id' ] );
        
        if( $salesItem->price < 0.01 )
        {
            $html = $this->render( 'fatal_error', array( 'model'=>$salesItem), true );

            $replacements = $this->getCommonReplacements( $salesItem );

            foreach( $replacements as $search => $replace )
            {
                $html = str_replace( $search, $replace, $html );
            }

            print $html;
            die;
        }
        
        $html = $this->render( 'individual_step_1', array(), true );

        // Here is where we create the shop_item
        $shopItem = $salesItem->placeIndividualSalesItemInShop( $salesItem->price );
        
        if( $shopItem instanceOf ShopItem )
        {
            $shopItem->shop_item_state_id = ShopItemState::SHOP_ITEM_AVAILABLE;
            $doSave = $shopItem->save();
        }
        
        $replacements = $this->getCommonReplacements( $salesItem );

        if( $shopItem instanceOf ShopItem )
        {
            $replacements[ '%SHOP_ITEM_ID%' ] = $shopItem->shop_item_id;
            $replacements[ '%URL_TITLE%' ] = $shopItem->url_title;
            $replacements[ '%SHOP_ITEM_PRICE%' ] = number_format( $shopItem->price, 2 );
        }

        foreach( $replacements as $search => $replace )
        {
            $html = str_replace( $search, $replace, $html );
        }

        print $html;
        die;
    }



    public function actionBundle_Step_1()
    {

        $salesItem = SalesItem::model()->findByPk( $_GET[ 'sales_item_id' ] );
        
        $html = $this->render( 'bundle_step_1', array(), true );

        // Here is where we create the shop_item
        $shopItem = $salesItem->placeIndividualSalesItemInShop( $salesItem->price );

        // The shopItem is created in state 997 which means it is not yet finalised
        $replacements = $this->getCommonReplacements( $salesItem );
        
        $replacements[ '%BASE_URL%' ] = Yii::app()->request->baseUrl;
        $replacements[ '%SHOP_ITEM_ID%' ] = $shopItem->shop_item_id;
        $replacements[ '%HTML_SALES_ITEMS_IN_BUNDLE%' ] = $this->htmlSalesItemsInBundle( $shopItem );

        $replacements[ '%SALES_ITEM_PRODUCT_TYPES%' ] = SellController::getProductTypesForUserWithSelectedDefault(
            $salesItem->product_type_id );

        $params[ 'excluded_sales_item_ids' ] = array( 0 => $shopItem->shopItemSalesItems[ 0 ]->salesItem->sales_item_id );
        $params[ 'sort' ] = '-si.product_name';
        $params[ 'page' ] = 0;
        $params[ 'num_per_page_max' ] = SellController::NUM_PER_PAGE_MAX;

        $searchResults = SellController::getSearchResults( $params );
        
        $searchResultsPlusHtml = SellController::renderHtmlSearchResults( $params, $searchResults );

        $params[ 'total_number_of_hits' ] = $searchResults[ 'total_number_of_hits' ];
        
        $replacements[ '%HTML_SEARCH_RESULTS%' ] = $searchResultsPlusHtml[ 'html_search_results' ];
        $replacements[ '%HTML_PAGE_SELECTOR%' ] = WebPageResultsController::generateAjaxPageSelector( $params );
        
        foreach( $replacements as $search => $replace )
        {
            $html = str_replace( $search, $replace, $html );
        }

        print $html;
        die;
    }
    
    
    
    public function actionBundle_Step_2()
    {

        // Instantiate the shop_item that the user has created
        $shopItem = ShopItem::model()->findByAttributes(
            array(
                'shop_item_id' => $_GET[ 'shop_item_id' ],
                'shop_item_state_id' => ShopItemState::SHOP_ITEM_NOT_YET_FINALISED ) 
        );

        if(! ( $shopItem instanceOf ShopItem ) )
        {
            $this->render( 'fatal_error', array() );
            die;
        }
        
        $html = $this->render( 'bundle_step_2', array(), true );

        $replacements = $this->getCommonReplacements( $shopItem->shopItemSalesItems[ 0 ]->salesItem );

        $replacements[ '%COUNT_NUM_IN_BUNDLE%' ] = SellController::countNumInBundle( $shopItem );
        $replacements[ '%SHOP_ITEM_ID%' ] = $shopItem->shop_item_id;
        $replacements[ '%SHOW_BUNDLED_SALES_ITEMS%' ] = 
            SellController::showBundledSalesItems( $shopItem );
        $replacements[ '%BUNDLED_PRICE_WITH_DISCOUNT%' ] = 
            number_format( SellController::suggestBundledPriceWithDiscount( $shopItem, 10 ), 2 );
        
        foreach( $replacements as $search => $replace )
        {
            $html = str_replace( $search, $replace, $html );
        }

        print $html;
        die;
    }



    public static function suggestBundledPriceWithDiscount( $shopItem, $percentageDiscount )
    {
        
        $price = 0;
        
        if( empty( $shopItem->shopItemSalesItems ) === FALSE )
        {
            foreach( $shopItem->shopItemSalesItems as $i => $shopItemSalesItem )
            {
                $price += $shopItemSalesItem->salesItem->price;
            }
        }

        if( $price > 0 )
        {
            $priceWithDiscount = ( $price / 100 ) * ( 100 - $percentageDiscount );
        }        
        
        return $priceWithDiscount;
        
    }



    public static function showBundledSalesItems( ShopItem $shopItem )
    {

        $html = '';
                
        if( empty( $shopItem->shopItemSalesItems ) === FALSE )
        {
            foreach( $shopItem->shopItemSalesItems as $i => $shopItemSalesItem )
            {
                $html .= $shopItemSalesItem->salesItem->product_name;
                $html .= ' (' . $shopItemSalesItem->salesItem->productType->product_type_desc . ')';
                $html .= '<br />';
                
            }
        }
        
        return $html;
        
    }



    public function actionBundle_Step_3()
    {

        // Instantiate the shop_item that the user has created
        $shopItem = ShopItem::model()->findByAttributes(
            array(
                'shop_item_id' => $_POST[ 'shop_item_id' ],
                'shop_item_state_id' => ShopItemState::SHOP_ITEM_NOT_YET_FINALISED ) 
        );

        
        if(! ( $shopItem instanceOf ShopItem ) )
        {
            $this->render( 'fatal_error', array() );
            die;
        }
        
        $shopItem->shop_show_title = trim( strip_tags( $_POST[ 'shop_show_title' ] ) );
        $shopItem->shop_show_description = trim( strip_tags( $_POST[ 'shop_show_description' ] ) );
        $shopItem->price = number_format( $_POST[ 'shop_item_price' ], 2 );
        
        $weight = 0;
        // Have to calculate the weight of all salesItems in it
        foreach( $shopItem->shopItemSalesItems as $i => $shopItemSalesItem )
        {
            if( $shopItemSalesItem->shop_item_group_id == ShopItemGroup::SHOP_ITEM_GROUP_STANDARD )
            {
                $weight += $shopItemSalesItem->salesItem->weight;
            }
        }
        
        $shopItem->shop_item_weight = $weight;
        $shopItem->shop_item_state_id = ShopItemState::SHOP_ITEM_AVAILABLE;
        $shopItem->user_id = 1; // We fudge this because all items belong to mary_m
        $shopItem->save();

        $html = $this->render( 'bundle_step_3', array(), true );
        
        $replacements = $this->getCommonReplacements( $shopItem->shopItemSalesItems[ 0 ]->salesItem );

        $replacements[ '%PRODUCT_NAME%' ] = $shopItem->shopItemSalesItems[ 0 ]->salesItem->product_name;
        $replacements[ '%SHOP_ITEM_ID%' ] = $shopItem->shop_item_id;
        $replacements[ '%SHOP_SHOW_TITLE%' ] = $shopItem->shop_show_title;
        $replacements[ '%SHOP_SHOW_DESCRIPTION%' ] = $shopItem->shop_show_description;
        $replacements[ '%SHOP_ITEM_PRICE%' ] = number_format( $shopItem->price, 2 );

        foreach( $replacements as $search => $replace )
        {
            $html = str_replace( $search, $replace, $html );
        }

        print $html;
        die;
    }



    public function renderHtmlSearchResults( Array $params, Array $searchResults )
    {

        $returnHtml = '';
                
        // Get the template for a sales item
        $availableSalesItemTemplate = $this->renderPartial( '//sell/available_sales_item_thumbnail', array(), true );
        
        foreach( $searchResults[ 'search_results' ] as $i => $searchRes )
        {

            if( empty( $searchRes ) === FALSE )
            {

                $newTile = $availableSalesItemTemplate;

                $rep = SellController::commonSalesItemReplacements( $searchRes[ 'sales_item' ] );
                
                foreach( $rep as $search => $replace )
                {
                    $newTile = str_replace( $search, $replace, $newTile );
                }
                
                $returnHtml .= $newTile;
                
            }
            
        }
        
        return array( 'total_number_of_hits' => $searchResults[ 'total_number_of_hits' ],
                        'html_search_results' => $returnHtml );

    }



    public static function getSearchResults( Array $params )
    {

        $sortOrder = $params[ 'sort' ];
        $thisPage = $params[ 'page' ];
        $firstLimitParam = $params[ 'page' ] * $params[ 'num_per_page_max' ];

        // Create the Order By Clause for the end of the query
        
        $orderByClause = WebPageResultsController::formOrderByClause( $sortOrder );

        $txn = Yii::app()->db->beginTransaction();

        $sql = 'SELECT SQL_CALC_FOUND_ROWS si.sales_item_id FROM `_sales_item` si
            INNER JOIN `_product_type` pt ON si.product_type_id = pt.product_type_id
            LEFT JOIN `_shop_item_sales_item` sisi ON si.sales_item_id = sisi.sales_item_id AND sisi.shop_item_group_id = ' . ShopItemGroup::SHOP_ITEM_GROUP_STANDARD . 
            ' WHERE sisi.shop_item_sales_item_id IS NULL ';
            
        if( isset( $params[ 'product_name' ] ) === TRUE )
        {
            if( $params[ 'product_name' ] != '' )
            {
                $sql .= ' AND si.product_name LIKE "%' . $params[ 'product_name' ] . '%" ';
            }
        }
        
        if( isset( $params[ 'product_type_id' ] ) === TRUE )
        {
            if( $params[ 'product_type_id' ] != '' )
            {
                $sql .= ' AND si.product_type_id = ' . intval( $params[ 'product_type_id' ] ) . ' ';
            }
        }

        if( empty( $params[ 'excluded_sales_item_ids' ] ) === FALSE )
        {
            $sql .=
                ' AND si.sales_item_id NOT IN (' . join( ', ', $params[ 'excluded_sales_item_ids' ] ) . ') ';
        }

        $sql .= $orderByClause . 
            ' LIMIT ' . $firstLimitParam . ', ' . $params[ 'num_per_page_max' ];

        $result = Yii::app()->db->createCommand( $sql )->queryAll();
        $txn->commit();

        $totalNumberOfHits = WebPageResultsController::getFoundRows();

        $hits = array();
        $i = 0;
        
        if( $totalNumberOfHits > 0 )
        {
            foreach( $result as $i => $row )
            {
                $hits[ $i ] = $row;
                $hits[ $i ][ 'sales_item' ] = SalesItem::model()->findByPk( $row[ 'sales_item_id' ] );
            }
        }

        return array( 'search_results' => $hits, 'total_number_of_hits' => $totalNumberOfHits );
        
    }



    public static function countNumInBundle( ShopItem $shopItem )
    {
        $count = 0;
        
        $sql = 'SELECT COUNT(*) AS num_in_bundle FROM `_shop_item_sales_item` WHERE
            shop_item_group_id = ' . ShopItemGroup::SHOP_ITEM_GROUP_STANDARD . ' AND shop_item_id = ' .
            $shopItem->shop_item_id . ';';
            
        $result = Yii::app()->db->createCommand( $sql )->queryAll();
        
        if( empty( $result ) === FALSE )
        {
            $count = intval( $result[ 0 ][ 'num_in_bundle' ] );
        }
        
        return $count;
    }
    


    public function htmlSalesItemsInBundle( ShopItem $shopItem )
    {

        $html = '';

        $salesItemTemplate = $this->renderPartial( '//sell/sales_item_thumbnail', array(), true );

        $html = '';
        
        foreach( $shopItem->shopItemSalesItems as $x => $shopItemSalesItem )
        {

            $salesItemTemplateCopy = $salesItemTemplate;
            
            $rep = SellController::commonSalesItemReplacements( $shopItemSalesItem->salesItem );
            
            foreach( $rep as $search => $replace )
            {
                $salesItemTemplateCopy = str_replace( $search, $replace, $salesItemTemplateCopy );
            }
            
            $html .= $salesItemTemplateCopy;

        }

        return $html;
        
    }

    


    public static function commonSalesItemReplacements( $thisSalesItem )
    {
        
        $rep = array(
            '%SALES_ITEM_ID%' => $thisSalesItem->sales_item_id,
            '%SHOW_TITLE%' => $thisSalesItem->product_name,
            '%IMAGE_TAG%' => 
                '<img src = "' . $thisSalesItem->getImageUrl( 'images', 0 ) . '" class = "img-thumbnail">',
        );

        return $rep;
        
    }



    public static function getProductTypesForUserWithSelectedDefault( $defaultProductTypeID )
    {
        
        $html = '';

        $sql = 'SELECT pt.product_type_id, pt.product_type_desc FROM `_sales_item` si
            INNER JOIN `_product_type` pt ON si.product_type_id = pt.product_type_id
            LEFT JOIN `_shop_item_sales_item` sisi ON si.sales_item_id = sisi.sales_item_id AND sisi.shop_item_group_id = 1
            WHERE sisi.shop_item_sales_item_id IS NULL
            GROUP BY pt.product_type_id
            ORDER BY pt.product_type_desc ASC';

        $result = Yii::app()->db->createCommand( $sql )->queryAll();
        
        if( empty( $result ) === FALSE )
        {
            foreach( $result as $i => $row )
            {
                $html .= '<option value = "' . $row[ 'product_type_id' ] . '"';
                
                if( $row[ 'product_type_id' ] == $defaultProductTypeID )
                {
                    $html .= ' selected';
                }

                $html .= '>' . $row[ 'product_type_desc' ] . '</option>';
            }
        }

        return $html;
    }
    
}
