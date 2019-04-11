<?php

class WebPageResultsController extends WebPageController
{

    public static function displayAjaxOnClickIfNotOnPageBeingDisplayed( $pageNum, $sortOrder, Array $params )
    {
        if( $params[ 'page' ] == $pageNum )
        {
            $html = $pageNum + 1;
        }
        else
        {
            $html = "\n" . '<a style = "cursor: pointer;" onclick = "htmlNonShopSalesItems(' . $pageNum . ');">' . ( $pageNum + 1 ) . '</a>';
        }

        return $html;
    }



    /**
    * generateAjaxPageSelector
    * Currently this is only used by the bundling sales_items pages
    * 
    * @param Array $params
    * (The $params array is scalable. It should contain:
    *   ['total_number_of_hits']
    *   ['num_per_page_max']
    *   ['sort']
    *   ['page']
    */
        
    public static function generateAjaxPageSelector( Array $params )
    {
        
        $divPlusHtml = '<div style = "clear: both; text-align: center;">';

        $html = '';
        
        if( intval( $params[ 'total_number_of_hits' ] ) > 0 )
        {
            // If divides exactly by ten then we say Great we know how many pages
            if( ( $params[ 'total_number_of_hits' ] % $params[ 'num_per_page_max' ] ) == 0 )
            {
                $totalPages = $params[ 'total_number_of_hits' ] / $params[ 'num_per_page_max' ];                
            }
            else
            {
                $totalPages = ceil( $params[ 'total_number_of_hits' ] / $params[ 'num_per_page_max' ] );                
            }

            $sortOrder = '';

            if( isset( $params[ 'sort' ] ) === TRUE )
            {
                $sortOrder = $params[ 'sort' ];
            }

            for( $pageNum = 0; $pageNum < $totalPages; $pageNum++ )
            {

                // Display the link if we are *not* on the page that is being displayed
                $html .= WebPageResultsController::displayAjaxOnClickIfNotOnPageBeingDisplayed( 
                    $pageNum, 
                    $sortOrder, 
                    $params 
                );

                if( ( ( $pageNum + 1 ) % 10 ) == 0 )
                {
                    $html .= '<br />';
                }
                else
                {
                    $html .= '&nbsp;|&nbsp;';
                }

            }

        }
        
        if( mb_substr( $html, ( mb_strlen( $html ) - 6 ), 7 ) !== '<br />' )
        {
            $divPlusHtml .= mb_substr( $html, 0, -13 );
        }
        else
        {
            $divPlusHtml .= $html;
        }
        
        $divPlusHtml .= '</div>';

        return $divPlusHtml;
    }
    


    /**
    * getHtmlStyleFromGetParam
    * 
    * 
    */

    public static function getHtmlStyleFromGetParam( $resultDisplayType )
    {
        if( isset( $_GET[ 'html_style' ] ) === TRUE )
        {
            $resultDisplayType = trim( $_GET[ 'html_style' ] );
        }
        
        return $resultDisplayType;
    }



    /**
    * getSortArrayFromGetParameters
    * 
    * 
    */

    public static function getSortArrayFromGetParameters
    ( $defaultSort )
    {
        
        if( isset( $_GET[ 'sort' ] ) )
        {
            return $_GET[ 'sort' ];
        }
        
        return $defaultSort;
        
    }



    /**
    * generatePageSelector
    * Displays a html page selector based on the given parameters
    * 
    * @param Array $params
    * (The $params array is scalable. It should contain:
    *   ['total_number_of_hits']
    *   ['num_per_page_max']
    *   ['sort']
    *   ['page']
    */
    
    public static function generatePageSelector( Array $params )
    {
        
        $divPlusHtml = '<div style = "clear: both; text-align: center;">';

        $html = '';
        
        if( intval( $params[ 'total_number_of_hits' ] ) > 0 )
        {
            // If divides exactly by ten then we say Great we know how many pages
            if( ( $params[ 'total_number_of_hits' ] % $params[ 'num_per_page_max' ] ) == 0 )
            {
                $totalPages = $params[ 'total_number_of_hits' ] / $params[ 'num_per_page_max' ];                
            }
            else
            {
                $totalPages = ceil( $params[ 'total_number_of_hits' ] / $params[ 'num_per_page_max' ] );                
            }

            $sortOrder = '';

            if( isset( $params[ 'sort' ] ) === TRUE )
            {
                $sortOrder = $params[ 'sort' ];
            }

            if( $totalPages < 11 )
            {

                for( $pageNum = 0; $pageNum < $totalPages; $pageNum++ )
                {

                    // Display the link if we are *not* on the page that is being displayed
                    $html .= WebPageResultsController::displayLinkIfNotOnPageBeingDisplayed( 
                        $pageNum, 
                        $sortOrder, 
                        $params 
                    );

                    if( ( ( $pageNum + 1 ) % 10 ) == 0 )
                    {
                        $html .= '<br />';
                    }
                    else
                    {
                        $html .= '&nbsp;|&nbsp;';
                    }

                }
                
            }
            else
            {
                $arrayOfPages = array();
                
                // We want links for pages 1, 2 and 3
                for( $pageNum = 0; $pageNum < 3; $pageNum++ )
                {
                    // Display the link if we are *not* on the page that is being displayed
                    $arrayOfPages[ $pageNum ] = WebPageResultsController::displayLinkIfNotOnPageBeingDisplayed( $pageNum, $sortOrder, $params );
                }

                
                // We want the link for pages -2 to +2 (except if +2 would take us over totalPages)
                if( ( $params[ 'page' ] > 1 ) && ( $params[ 'page' ] < $totalPages ) )
                {
                    for( $pageNum = ( intval( $params[ 'page' ] ) - 2 ); $pageNum < ( intval( $params[ 'page' ] ) + 3 ); $pageNum++ )
                    {
                        // Display the link if we are *not* on the page that is being displayed
                        if( $pageNum < $totalPages )
                        {
                            $arrayOfPages[ $pageNum ] = WebPageResultsController::displayLinkIfNotOnPageBeingDisplayed( $pageNum, $sortOrder, $params );
                        }
                        
                    }
                }


                // We want links for the last pages
                for( $pageNum = ( $totalPages - 3 ); $pageNum < $totalPages; $pageNum++ )
                {
                    // Display the link if we are *not* on the page that is being displayed
                    $arrayOfPages[ $pageNum ] = WebPageResultsController::displayLinkIfNotOnPageBeingDisplayed( $pageNum, $sortOrder, $params );
                }

                
                $expectedCountInc = 1;
                $previousPageNum = -1;

                foreach( $arrayOfPages as $pageNum => $link )
                {
                    if( ( $pageNum - 1 ) != $previousPageNum )
                    {
                        $previousPageNum = $pageNum;
                        $html .= '...&nbsp;|&nbsp;' . $link . '&nbsp;|&nbsp;';
                    }
                    else
                    {
                        $html .= $link . '&nbsp;|&nbsp;';
                    }

                    $previousPageNum = intval( $pageNum );
                
                }
            }

        }
        
        if( mb_substr( $html, ( mb_strlen( $html ) - 6 ), 7 ) !== '<br />' )
        {
            $divPlusHtml .= mb_substr( $html, 0, -13 );
        }
        else
        {
            $divPlusHtml .= $html;
        }
        
        $divPlusHtml .= '</div>';

        return $divPlusHtml;
    }
    




    public static function formOrderByClause( $sortOrder )
    {
 
        $sorting = explode( '|', $sortOrder );

        $orderBy = '';

        if( empty( $sorting ) === FALSE )
        {
            $orderBy .= 'ORDER BY ';

            foreach( $sorting as $x => $sortOrder )
            {
                if( mb_substr( $sortOrder, 0, 1 ) !== '-' )
                {
                    $orderBy .= $sortOrder . ' DESC, ';
                }
                else
                {
                    $orderBy .= mb_substr( $sortOrder, 1 ) . ' ASC, ';
                }
            }
        }

        return mb_substr( $orderBy, 0, -2 );
        
    }



    public static function rollUpAdditionalParameters( Array $params, &$html )
    {

        // Make an array of all the params we do not want to roll up
        $excludedParams = array( 
            'sort', 
            'page', 
            'num_per_page_max', 
            'list_php_page', 
            'total_number_of_hits' );

        // Now go through all of the params we have
        $arrOfKeys = array_keys( $params );

        // And unset any param which is in the excluded list        
        foreach( $arrOfKeys as $count => $pm )
        {
            if( in_array( $pm, $excludedParams ) === TRUE )
            {
                unset( $params[ $pm ] );
            }
        }
        
        // With the params we have left, roll 'em up
        foreach( $params as $key => $value )
        {
            if( is_array( $value ) === FALSE )
            {
                $html .= $key . '/' . str_replace( '/', '-', $value ) . '/';
            }
        }
    }



    public static function displayLinkIfNotOnPageBeingDisplayed( $pageNum, $sortOrder, Array $params )
    {
        if( $params[ 'page' ] == $pageNum )
        {
            $html = $pageNum + 1;
        }
        else
        {
            $html = "\n" . '<a href = "' .
                Yii::app()->request->baseUrl . '/' .
                $params[ 'list_php_page' ];

            WebPageResultsController::rollUpAdditionalParameters( $params, $html );
            
            $html .= 'sort/' . $sortOrder . 
                '/page/' . $pageNum . 
                '/num_per_page_max/' . $params[ 'num_per_page_max' ] . '/">' . ( $pageNum + 1 ) . '</a>';
        }

        return $html;
    }



    public static function getFoundRows()
    {
        $fountRows = 0;
        
        $sql = 'SELECT FOUND_ROWS() AS found_rows;';

        $result = Yii::app()->db->createCommand( $sql )->queryAll();

        $foundRows = $result[ 0 ][ 'found_rows' ];

        return $foundRows;
    }



    /**
    * getThisPageFromGetParameter
    * If we have a page in the $_GET then use this, otherwise use 0 to show
    * the first page
    * 
    */

    public static function getThisPageFromGetParameter()
    {
                
        $thisPage = 0;

        if( isset( $_GET[ 'page' ] ) )
        {
            $thisPage = intval( $_GET[ 'page' ] );
        }

        return $thisPage;        
    }
        
}