<?php

class ShipperController extends WebPageController
{

    const NUM_PER_PAGE_MAX = 30;

    public function generateHtmlShippers(
        Array $params )
    {

        $shippers = ShipperController::generateShippersList( $params );
        
        $htmlRows = '';

        for( $i = 0; $i < sizeof( $shippers[ 'shippers' ] ); $i++ )
        {
            $htmlRows .= $this->showShipperRow( $params, $shippers[ 'shippers' ][ $i ] );
        }

        return array( 'total_number_of_hits' => $shippers[ 'total_number_of_hits' ],
                        'html_product_types' => $htmlRows );

    }



    public function showShipperRow( Array $params, Array $shipper )
    {
        return $this->renderPartial('_shipper_row', array( 'shipper' => $shipper ), true );
    }



    public static function generateShippersList(
        Array $params )
    {

        $firstLimitParam = $params[ 'page' ] * $params[ 'num_per_page_max' ];

        // Create the Order By Clause for the end of the query
        $orderByClause = WebPageResultsController::formOrderByClause( $params[ 'sort' ] );

        $txn = Yii::app()->db->beginTransaction();
        
        $sql = 'SELECT SQL_CALC_FOUND_ROWS s.shipper_id, s.shipper_info, s.delivers_from, s.delivers_to ' . 
            ' FROM `_shipper` s ' .
            $orderByClause .
            ' LIMIT ' . $firstLimitParam . ', ' . ShipperController::NUM_PER_PAGE_MAX;

        $shippers = Yii::app()->db->createCommand( $sql )->queryAll();
        $txn->commit();

        $totalNumberOfHits = WebPageResultsController::getFoundRows();

        return array( 'shippers' => $shippers, 'total_number_of_hits' => $totalNumberOfHits );

    }



	public function actionIndex()
	{

        $params = array();
        $params[ 'list_php_page' ] = 'shipper/index/';
        
        $params[ 'sort' ] = WebPageResultsController::getSortArrayFromGetParameters( '-s.shipper_id' );
        $params[ 'page' ] = WebPageResultsController::getThisPageFromGetParameter();
        $params[ 'num_per_page_max' ] = ShipperController::NUM_PER_PAGE_MAX;

        $totalNumberOfItemsPlusHtml = $this->generateHtmlShippers( $params );

        $params[ 'total_number_of_hits' ] = $totalNumberOfItemsPlusHtml[ 'total_number_of_hits' ];

        $landingHtml = $this->render( 'index', array(), true );

        // Do the searching and replacing        
        $replacements = array(
            '%SHIPPERS_LIST%' => $totalNumberOfItemsPlusHtml[ 'html_product_types' ],
            '%SHIPPERS_PAGE_SELECTOR%' => WebPageResultsController::generatePageSelector( $params ),
        );

        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;
 
	}

}
