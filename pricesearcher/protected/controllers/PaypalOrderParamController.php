<?php

class PaypalOrderParamController extends WebPageController
{

    const NUM_PER_PAGE_MAX = 50;
    
	public function actionList()
	{

        $params = array();
        
        $params[ 'order_id' ] = $_GET[ 'order_id' ];
        
        $order = Order::model()->findByPk( $params[ 'order_id' ] );

        $params[ 'user_id' ] = $order->user_id;
        $params[ 'html_style' ] = 'table';

        $params[ 'sort' ] = '-pop.paypal_order_param_id';
        $params[ 'attempt_id' ] = 'all';
        
        if( ( isset( $_GET[ 'attempt_id' ] ) === TRUE ) && ( $_GET[ 'attempt_id' ] > 0 ) )
        {
            $params[ 'attempt_id' ] = intval( $_GET[ 'attempt_id' ] );
        }

        $params[ 'direction_id' ] = 'both';
        
        if( ( isset( $_GET[ 'direction_id' ] ) === TRUE ) && ( $_GET[ 'direction_id' ] > 0 ) )
        {
            $params[ 'direction_id' ] = intval( $_GET[ 'direction_id' ] );
        }

        $params[ 'page' ] = WebPageResultsController::getThisPageFromGetParameter();
        $params[ 'num_per_page_max' ] = PaypalOrderParamController::NUM_PER_PAGE_MAX;
        $params[ 'list_php_page' ] = 'paypalOrderParam/list/';

        $this->pageTitle = 'Paypal Logs - ' . Yii::app()->name;
        $this->metaDescription = "Shows the logs of Paypal, so you can see how far a user progressed.";

        $landingHtml = $this->render('list',array(),true );

        $searchResults = PaypalOrderParamController::generatePaypalOrderParamsResultList( $params );
        
        $params[ 'total_number_of_hits' ] = $searchResults[ 'total_number_of_hits' ];

        $htmlSearchResults = PaypalOrderParamController::generateHtmlOrderSearchResultList( $params, $searchResults );
        
        // Do the searching and replacing        
        $replacements = array( 
            '%ORDER_ID%' => $order->order_id,
            '%ATTEMPT_ID_OPTIONS%' => PaypalOrderParamController::getAttemptSelectList( $order->order_id ),
            '%HTML_SEARCH_RESULTS%' => $htmlSearchResults[ 'html_search_results' ],
            '%HTML_PAGE_SELECTOR%' => WebPageResultsController::generatePageSelector( $params ),
        );

        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;

	}
    
    


    public static function getAllAttemptsForOrderAsArray( $order_id )
    {
        $sql = 'SELECT attempt_id FROM `_paypal_order_param` WHERE order_id = ' . $order_id . ' GROUP BY attempt_id';
        
        $r = Yii::app()->db->createCommand( $sql )->queryAll();
        
        return $r;
    }



    public static function getAttemptSelectList( $order_id )
    {

        $html = '';
        $html .= '<option value = "all">ALL</option>';

        $r = PaypalOrderParamController::getAllAttemptsForOrderAsArray( $order_id );

        if( empty( $r ) === FALSE )
        {
            foreach( $r as $i => $row )
            {
                $html .= '<option value = "' . $row[ 'attempt_id' ] . '"';
                
                if( $row[ 'attempt_id' ] == 1 )
                {
                    $html .= ' selected';
                }
                
                $html .= '>' . $row[ 'attempt_id' ] . '</option>';
            }

        }        
        
        return $html;

    }



    public static function generatePaypalOrderParamsResultList( Array $params )
    {

        $firstLimitParam = $params[ 'page' ] * $params[ 'num_per_page_max' ];

        // Create the Order By Clause for the end of the query
        
        $orderByClause = '';

        if( is_array( $params[ 'sort' ] ) === TRUE )
        {
            $orderByClause = WebPageResultsController::formOrderByClause( $params[ 'sort' ] );
        }

        $whereAttemptIdClause = '';
        
        if( ( isset( $params[ 'attempt_id' ] ) === TRUE ) && ( $params[ 'attempt_id' ] !== 'all' ) )
        {
            $whereAttemptIdClause = ' AND pop.attempt_id = "' . $params[ 'attempt_id' ] . '" ';
        }

        $whereDirectionIdClause = '';
        
        if( ( isset( $params[ 'direction_id' ] ) === TRUE ) && ( $params[ 'direction_id' ] !== 'both' ) )
        {
            $whereDirectionIdClause = ' AND pop.direction_id = "' . $params[ 'direction_id' ] . '" ';
        }
        
        $txn = Yii::app()->db->beginTransaction();

        $sql = 'SELECT SQL_CALC_FOUND_ROWS 
            pop.paypal_order_param_id,
            pop.attempt_id,
            pop.direction_id,
            pop.key,
            pop.value,
            pop.date_created
            FROM `_paypal_order_param` pop 
            INNER JOIN `_order` o ON pop.order_id = o.order_id
            WHERE pop.order_id = ' . $params[ 'order_id' ] . 
            ' AND o.user_id = ' . $params[ 'user_id' ] .
            $whereAttemptIdClause . ' ' .
            $whereDirectionIdClause . ' ' .
            $orderByClause . ' LIMIT ' . $firstLimitParam . ', ' . $params[ 'num_per_page_max' ];
        
        $result = Yii::app()->db->createCommand( $sql )->queryAll();
        $txn->commit();

        $totalNumberOfHits = WebPageResultsController::getFoundRows();

        return array( 'search_results' => $result, 'total_number_of_hits' => $totalNumberOfHits );

    }



    public static function generateHtmlOrderSearchResultList( Array $params, Array $searchResults )
    {

        $returnHtml = '';
                    
        if( empty( $searchResults[ 'search_results' ] ) === FALSE )
        {
            $returnHtml .= '<table class = "table table-condensed">';
            $returnHtml .= '<tr valign = "top">';
            $returnHtml .= '<th>ID</th>';
            $returnHtml .= '<th>Attempt ID</th>';
            $returnHtml .= '<th>Direction</th>';
            $returnHtml .= '<th>Key Value Pair</th>';
            $returnHtml .= '<th style = "text-align: right;">Date</th>';
            $returnHtml .= '</tr>';
            
            foreach( $searchResults[ 'search_results' ] as $i => $searchRes )
            {

                $returnHtml .= '<tr valign = "top">';

                if( empty( $searchRes ) === FALSE )
                {
                    $returnHtml .= '<td>' . $searchRes[ 'paypal_order_param_id' ] . '</td>';
                    $returnHtml .= '<td>' . $searchRes[ 'attempt_id' ] . '</td>';
                    $returnHtml .= '<td>' . PaypalOrderParamController::showDirection( $searchRes[ 'direction_id' ] ) . '</td>';
                    $returnHtml .= '<td><span id = "typed">' . PaypalOrderParamController::concatenateString( $searchRes[ 'key' ] . '=' .
                        $searchRes[ 'value' ], 70 ) . '</span></td>';
                    $returnHtml .= '<td style = "text-align: right;">' . $searchRes[ 'date_created' ] . '</td>';
                }
                
                $returnHtml .= '</tr>';
                
            }
            
            $returnHtml .= '</table>';
            
        }
            
        return array( 'total_number_of_hits' => $params[ 'total_number_of_hits' ],
                        'html_search_results' => $returnHtml );

    }



    public static function concatenateString( $string, $maxLength = 100 )
    {
        $html = '';
        
        if( mb_strlen( $string ) > $maxLength )
        {
            $html .= '<span title = "' . $string . '">' .
                mb_substr( $string, 0, $maxLength ) . '</span>';
        }
        else
        {
            $html .= $string;
        }
        
        return $html;
    }



    public static function showDirection( $num )
    {
        if( $num == 1 )
        {
            return 'OUT';
        }
        
        return 'IN';
    }
    
}