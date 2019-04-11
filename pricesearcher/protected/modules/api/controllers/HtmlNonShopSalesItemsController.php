<?php

class HtmlNonShopSalesItemsController extends ApiController
{
    
    public $mustBeLoggedIn = TRUE;
    public $mustBeAdministrator = FALSE;
    
    public $optional_product_name;
    public $product_type_id;
    
    public $excluded_sales_item_ids;
    
    public $page;
    public $sort;
    public $num_per_page_max;

    public $html;
    public $html_page_selector;
    
    public function actionIndex()
    {

        // Force the include of the SearchController
        require Yii::getPathOfAlias('application.controllers.SellController') . '.php';

        // Set the cmd to equal what has been called
        $this->cmd = 'htmlNonShopSalesItems';

        // Check whether the Url contains the elements
        // required to activate this command
        $validated = $this->validateUrl();

        if( $validated === FALSE )
        {
            $this->failure_reason = 'Invalid URL';
            return $this->getJsonResponse();
        }
        
        $this->validateParams();

        $this->optional_product_name = trim( $_GET[ 'optional_product_name' ] );
        $this->product_type_id = trim( $_GET[ 'product_type_id' ] );
        $this->excluded_sales_item_ids = explode( '|', $_GET[ 'excluded_sales_item_ids' ] );

        // If the url exploded but produced an empty field then make entire
        // array empty
        if( empty( $this->excluded_sales_item_ids[ 0 ] ) === TRUE )
        {
            $this->excluded_sales_item_ids = array();
        }

        $this->page = 0;
                
        // If the api command contains a page then the search has to bring back
        // a specific 'page' of information in the search results
        if( isset( $_GET[ 'page' ] ) === TRUE )
        {
            $this->page = intval( $_GET[ 'page' ] );
        }

        $this->sort = '-si.product_name';
                
        // If the api command contains a sort then the search has to sort the
        // 'pages' by a particular field
        if( isset( $_GET[ 'sort' ] ) === TRUE )
        {
            $this->sort = intval( $_GET[ 'sort' ] );
        }
        
        $this->num_per_page_max = SellController::NUM_PER_PAGE_MAX;

        // Now put all the attributes we need into a $params array
        $params = array();
        $params[ 'product_name' ] = $this->optional_product_name;
        $params[ 'product_type_id' ] = $this->product_type_id;
        $params[ 'excluded_sales_item_ids' ] = $this->excluded_sales_item_ids;
        $params[ 'sort' ] = $this->sort;
        $params[ 'page' ] = $this->page;
        $params[ 'num_per_page_max' ] = $this->num_per_page_max;

        $searchResults = SellController::getSearchResults( $params );
        $searchResultsPlusHtml = SellController::renderHtmlSearchResults( $params, $searchResults );
        
        $params[ 'total_number_of_hits' ] = $searchResults[ 'total_number_of_hits' ];
        
        $this->html = $searchResultsPlusHtml[ 'html_search_results' ];
        $this->html_page_selector = WebPageResultsController::generateAjaxPageSelector( $params );
        $this->setReport( 'Success' );

        return $this->getJsonResponse();
        
    }
    

    public function getJsonResponse()
    {
        parent::getJsonResponse();
        
        $uniqueProperties = array( 
            'html',
            'html_page_selector' 
        );
            
        $arrayKeys = array_values( $uniqueProperties );
        
        foreach( $arrayKeys as $a => $key )
        {
            if( property_exists( get_class( $this ), $arrayKeys[ $a ] ) === TRUE )
            {
                $this->jsonArray[ $key ] = $this->$key;
            }
        }
        
        return $this->deliverJson();
    }



    /**
    * validateUrl()
    * Note: We do not check the product_name here because the parent
    * validate will not allow a blank product_name to be passed in
    */

    public function validateUrl()
    {
        $validated = FALSE;
        
        // If we've got the following then the url is validated
        if( isset( $_GET[ 'excluded_sales_item_ids' ] ) === TRUE )
        {
            $validated = TRUE;
        }

        return $validated;
    }
    
}