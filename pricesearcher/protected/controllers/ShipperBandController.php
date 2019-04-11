<?php

class ShipperBandController extends WebPageController
{

    const NUM_PER_PAGE_MAX = 30;

    public function generateHtmlShipperBands(
        Array $params )
    {

        $shipperBands = ShipperBandController::generateShipperBandsList( $params );

        $htmlRows = '';

        for( $i = 0; $i < sizeof( $shipperBands[ 'shipper_bands' ] ); $i++ )
        {
            $htmlRows .= $this->showShipperBandRow( $params, $shipperBands[ 'shipper_bands' ][ $i ] );
        }

        return array( 'total_number_of_hits' => $shipperBands[ 'total_number_of_hits' ],
                        'html_shipper_bands' => $htmlRows );

    }



    public function showShipperBandRow( Array $params, Array $shipperBand )
    {
        $html = $this->renderPartial('_shipper_band_row', array( 'shipperBand' => $shipperBand ), true );
        
        $replacements[ '%SHIPPER_BAND_ID%' ] = $shipperBand[ 'shipper_band_id' ];
        $replacements[ '%SHIPPER_BAND_SHIPPING_PRICE%' ] = $shipperBand[ 'shipping_price' ];
        $replacements[ '%SHIPPER_BAND_MAX_WEIGHT%' ] = intval( $shipperBand[ 'max_weight' ] );
        
        foreach( $replacements as $search => $replace )
        {
            $html = str_replace( $search, $replace, $html );
        }
        
        return $html;
    }



    public static function generateShipperBandsList(
        Array $params )
    {

        $firstLimitParam = $params[ 'page' ] * $params[ 'num_per_page_max' ];

        // Create the Order By Clause for the end of the query
        $orderByClause = WebPageResultsController::formOrderByClause( $params[ 'sort' ] );

        $txn = Yii::app()->db->beginTransaction();
        
        $sql = 'SELECT SQL_CALC_FOUND_ROWS sb.shipper_band_id, sb.max_weight, sb.shipping_price ' . 
            ' FROM `_shipper_band` sb WHERE sb.shipper_id = ' . $params[ 'shipper_id' ] . ' ' .
            $orderByClause .
            ' LIMIT ' . $firstLimitParam . ', ' . ShipperBandController::NUM_PER_PAGE_MAX;

        $shipperBands = Yii::app()->db->createCommand( $sql )->queryAll();
        $txn->commit();

        $totalNumberOfHits = WebPageResultsController::getFoundRows();

        return array( 'shipper_bands' => $shipperBands, 'total_number_of_hits' => $totalNumberOfHits );

    }



	public function actionIndex()
	{

        $params = array();
        $params[ 'list_php_page' ] = 'shipperBand/index/';
        
        $params[ 'shipper_id' ] = intval( $_GET[ 'shipper_id' ] );
        $shipper = Shipper::model()->findByPk( $params[ 'shipper_id' ] );
        
        $params[ 'sort' ] = WebPageResultsController::getSortArrayFromGetParameters( '-sb.max_weight' );
        $params[ 'page' ] = WebPageResultsController::getThisPageFromGetParameter();
        $params[ 'num_per_page_max' ] = ShipperBandController::NUM_PER_PAGE_MAX;

        $totalNumberOfItemsPlusHtml = $this->generateHtmlShipperBands( $params );

        $params[ 'total_number_of_hits' ] = $totalNumberOfItemsPlusHtml[ 'total_number_of_hits' ];

        $landingHtml = $this->render( 'index', array(), true );

        // Do the searching and replacing        
        $replacements = array(
            '%SHIPPER_BAND_LIST%' => $totalNumberOfItemsPlusHtml[ 'html_shipper_bands' ],
            '%SHIPPER_BAND_PAGE_SELECTOR%' => WebPageResultsController::generatePageSelector( $params ),
            '%SHIPPER_INFO%' => $shipper->shipper_info,
        );

        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;
 
	}

}
