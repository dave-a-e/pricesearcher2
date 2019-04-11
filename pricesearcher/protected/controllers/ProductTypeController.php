<?php

class ProductTypeController extends WebPageResultsController
{

    const NUM_PER_PAGE_MAX = 100;

	public function actionCreate()
	{
		$this->render('create');
	}
    
    
    public function actionCreate_Confirm()
    {

        if( isset( $_POST[ 'product_type_desc' ] ) === TRUE )
        {
            // Check that the product_type_desc does not already exist in the database
            
            $productType = ProductType::model()->findByAttributes( 
                array(
                    'product_type_desc' => strip_tags( $_POST[ 'product_type_desc' ] )
                )
            );
            
            if( !( $productType instanceOf ProductType ) )
            {
                $productType = new ProductType();
                $productType->product_type_state_id = ProductTypeState::STATE_ACTIVE;
                $productType->product_type_desc = strip_tags( $_POST[ 'product_type_desc' ] );
                $productType->creator_id = Yii::app()->user->getId();
                $productType->date_created = new CDbExpression('UTC_TIMESTAMP()');
                $productType->modifier_id = Yii::app()->user->getId();
                $productType->date_modified = new CDbExpression('UTC_TIMESTAMP()');
                $productType->save();        
            }
            else
            {
                $productType->product_type_state_id = ProductTypeState::STATE_ACTIVE;
                $productType->date_modified = new CDbExpression('UTC_TIMESTAMP()');
                $productType->save();
            }
        }
        
        $this->render('create_confirm',array(
            'productType'=>$productType,
        ));
    }
    
    
    public function getProductTypeStateOptions( ProductType $productType )
    {

        $sql = 'SELECT * FROM `_product_type_state`';
        
        $r = Yii::app()->db->createCommand( $sql )->queryAll();
        
        $html = '';

        foreach( $r as $i => $row )
        {
            $html .= '<option value = "' .
                $row[ 'product_type_state_id' ] . '" ';
                
            if( intval( $row[ 'product_type_state_id' ] ) === intval( $productType->product_type_state_id ) )
            {
                $html .= ' selected';
            }

            $html .= '>' .
                $row[ 'product_type_state_desc' ] . '</option>';
        }
        
        return $html;

    }
    
    
    public function actionEdit()
    {
        if( isset( $_GET[ 'id' ] ) === TRUE )
        {
            $productType = ProductType::model()->findByPk( array( 'product_type_id' => $_GET[ 'id' ] ) );
            $html = $this->render( 'edit', array( 'productType' => $productType ), true );
            
            $replacements[ '%PRODUCT_TYPE_STATE_OPTIONS%' ] = $this->getProductTypeStateOptions( $productType );
            
            foreach( $replacements as $search => $replace )
            {
                $html = str_replace( $search, $replace, $html );
            }
            
            print $html;
            die;
            
        }

    }
    
    
    public function actionEdit_Confirm()
    {

        // Get the original productType
        $productType = ProductType::model()->findByPk( $_POST[ 'product_type_id' ] );

        if( $productType instanceOf ProductType )
        {
            $productType->product_type_state_id = intval( $_POST[ 'product_type_state_id' ] );
            $productType->product_type_desc = strip_tags( $_POST[ 'product_type_desc' ] );
            $productType->modifier_id = Yii::app()->user->getId();
            $productType->date_modified = new CDbExpression('UTC_TIMESTAMP()');
            $productType->save();        
        }
        
        $this->render('edit_confirm',array(
            'productType'=>$productType,
        ));
    }
 
 
 
    public function generateHtmlProductTypes(
        Array $params )
    {

        $productTypes = ProductTypeController::generateProductTypesList( $params );
        
        $htmlRows = '';

        for( $i = 0; $i < sizeof( $productTypes[ 'product_types' ] ); $i++ )
        {
            $htmlRows .= $this->showProductTypeRow( $params, $productTypes[ 'product_types' ][ $i ] );
        }

        return array( 'total_number_of_hits' => $productTypes[ 'total_number_of_hits' ],
                        'html_product_types' => $htmlRows );

    }



    public function showProductTypeRow( Array $params, Array $productType )
    {
        return $this->renderPartial('_product_type_row', array( 'productType' => $productType ), true );
    }



    public static function generateProductTypesList(
        Array $params )
    {

        $firstLimitParam = $params[ 'page' ] * $params[ 'num_per_page_max' ];

        // Create the Order By Clause for the end of the query
        $orderByClause = WebPageResultsController::formOrderByClause( $params[ 'sort' ] );

        $txn = Yii::app()->db->beginTransaction();
        
        $sql = 'SELECT SQL_CALC_FOUND_ROWS pt.product_type_id, pts.product_type_state_desc, pt.product_type_desc FROM `_product_type` pt ' . 
            'INNER JOIN `_product_type_state` pts ON pt.product_type_state_id = pts.product_type_state_id ' .
            $orderByClause .
            ' LIMIT ' . $firstLimitParam . ', ' . ProductTypeController::NUM_PER_PAGE_MAX;

        $productTypes = Yii::app()->db->createCommand( $sql )->queryAll();
        $txn->commit();

        $totalNumberOfHits = WebPageResultsController::getFoundRows();

        return array( 'product_types' => $productTypes, 'total_number_of_hits' => $totalNumberOfHits );

    }



    public function actionList()
    {

        $params = array();
        $params[ 'list_php_page' ] = 'productType/list/';
        
        $comparitors = array( 
            'product_type_state_id',
        );

        foreach( $comparitors as $key )
        {
            if( isset( $_GET[ $key ] ) )
            {
                // NB. Don't cast the $_GET to an integer here,
                // because the word 'all' is used to search everything
                $params[ $key ] = $_GET[ $key ];
            }
        }
        
        $params[ 'sort' ] = WebPageResultsController::getSortArrayFromGetParameters( '-pt.product_type_desc' );
        $params[ 'page' ] = WebPageResultsController::getThisPageFromGetParameter();
        $params[ 'num_per_page_max' ] = ProductTypeController::NUM_PER_PAGE_MAX;

        $totalNumberOfItemsPlusHtml = $this->generateHtmlProductTypes( $params );

        $params[ 'total_number_of_hits' ] = $totalNumberOfItemsPlusHtml[ 'total_number_of_hits' ];

        $landingHtml = $this->render( 'list', array(), true );

        // Do the searching and replacing        
        $replacements = array(
            '%PRODUCT_TYPES_LIST%' => $totalNumberOfItemsPlusHtml[ 'html_product_types' ],
            //'%SHOP_ITEMS_FILTERS%' => ShopController::generateShopItemsFilterForm( $params ),
            '%PRODUCT_TYPES_PAGE_SELECTOR%' => WebPageResultsController::generatePageSelector( $params ),
        );

        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;
 
    }
    
}