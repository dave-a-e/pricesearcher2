<?php

class ApiController extends Controller
{
    
    public $cmd;
    private $report = 'Failure';
    public $failure_reason;
    
    public $jsonArray;
    public $item;
    public $salesItem;    

    public function __construct($id,$module=null)
    {
        parent::__construct($id,$module);
        
        if( !array_key_exists( 'HTTP_ORIGIN', $_SERVER ) )
        {
            $_SERVER[ 'HTTP_ORIGIN' ] = $_SERVER[ 'SERVER_NAME' ];
        }
    }



    public static function formUrlForLandingPageItem( Item $item )
    {
        $filePath = ApiController::LandingPageItemUrl;

        // Do the searching and replacing        
        $replacements = array(
            '%MACHINE_TYPE_GROUP_DEFAULT_FOLDER%' => 
                $item->machineTypeGroup->machine_type_group_default_folder,
            '%PUBLISHER_FOLDER%' => 
                $item->publisher->publisher_folder,
            '%FORMAT_FOLDER%' => 
                $item->formatTypeGroup->format_folder,
            '%ITEM_TITLE%' => 
                $item->item_title,
        );

        foreach( $replacements as $search => $replace )
        {
            $filePath = str_replace( $search, $replace, $filePath );
        }
                
        return $filePath;
    }



    public function getJsonResponse()
    {
        $this->jsonArray = array();
        $this->jsonArray[ 'cmd' ] = $this->cmd;
        $this->jsonArray[ 'report' ] = $this->report;
        
        if( $this->failure_reason != '' )
        {
            $this->jsonArray[ 'failure_reason' ] = $this->failure_reason;
        }
    }



    public function setReport( $result = 'Success' )
    {
        $this->report = $result;
        $this->failure_reason = '';
    }
    
    
    public function getReport()
    {
        return $this->report;
    }



    public function deliverJson()
    {
        // Allow any website to receive the JSON that will be prepared
        header( "Access-Control-Allow-Origin: *" );
        
        // Allow any methods to be passed to the methods
        header( "Access-Control-Allow-Methods: *" );
        
        // Give the type of data (JSON) that we're going to pass back
        header( "Content-Type: application/json" );

        header( "HTTP/1.1 200 " . $this->_requestStatus( 200 ) );
        
        print json_encode( $this->jsonArray );
        die;
    }


    private function _requestStatus( $code ) 
    {
        $status = array(  
            200 => 'OK',
            422 => 'Missing Parameter For Given Method',
            404 => 'Not Found',   
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        ); 
        
        return ($status[$code])?$status[$code]:$status[500]; 
    }    



    public function validateParams()
    {

        // If we must be logged in then 
        // make sure that we are!
        if( $this->mustBeLoggedIn === TRUE )
        {
            if( ( Yii::app()->user->isGuest ) === TRUE )
            {
                $this->failure_reason = 'User must be logged in to use this API command.';
                return $this->getJsonResponse();
            }
            
            // Work out if we have the logged-in user's id
            if( intval( Yii::app()->user->getId() ) === 0 )
            {
                $this->failure_reason = 'User ID not recognised';
                return $this->getJsonResponse();
            }
            
        }
        
        // If we must be an administrator
        // make sure that we are!
        if( $this->mustBeAdministrator === TRUE )
        {
            if( Yii::app()->user->getUserTypeId() != UserType::USER_TYPE_ADMIN )
            {
                $this->failure_reason = 'User must be an administrator to use this API command.';
                return $this->getJsonResponse();
            }
        }



        // If we are toggling by an item_id (such as when the user is viewing
        // a list of items on the public search), then check that we have a valid
        // item_id passed in
        if( ( isset( $_GET[ 'item_id' ] ) === TRUE ) && ( intval( $_GET[ 'item_id' ] ) <= 0 ) )
        {
            $this->failure_reason = 'item_id invalid.';
            return $this->getJsonResponse();
        }



        // If we are toggling by an upgrade_item_id (such as when the user is confirming that one item
        // upgrades a particular thing), then check we have a valid item_id passed in
        if( ( isset( $_GET[ 'upgrade_item_id' ] ) === TRUE ) && ( intval( $_GET[ 'upgrade_item_id' ] ) <= 0 ) )
        {
            $this->failure_reason = 'upgrade_item_id invalid.';
            return $this->getJsonResponse();
        }



        // If we are toggling by an upgrade_id
        if( ( isset( $_GET[ 'upgrade_id' ] ) === TRUE ) && ( intval( $_GET[ 'upgrade_id' ] ) <= 0 ) )
        {
            $this->failure_reason = 'upgrade_id invalid.';
            return $this->getJsonResponse();
        }



        // If we are toggling by an necessity_id
        if( ( isset( $_GET[ 'necessity_id' ] ) === TRUE ) && ( intval( $_GET[ 'necessity_id' ] ) <= 0 ) )
        {
            $this->failure_reason = 'necessity_id invalid.';
            return $this->getJsonResponse();
        }



        // If we are toggling by an storage_container_id
        if( ( isset( $_GET[ 'storage_container_id' ] ) === TRUE ) && ( intval( $_GET[ 'storage_container_id' ] ) <= 0 ) )
        {
            $this->failure_reason = 'storage_container_id invalid.';
            return $this->getJsonResponse();
        }



        // If we are doing something with a shop_item_id, such as when the user is editing its price,
        // check that we have a valid shop_item_id passed in
        if( ( isset( $_GET[ 'shop_item_id' ] ) === TRUE ) && ( intval( $_GET[ 'shop_item_id' ] ) <= 0 ) )
        {
            $this->failure_reason = 'shop_item_id invalid.';
            return $this->getJsonResponse();
        }



        // If we are doing something with a mag_id, then check that we have a valid
        // item_id passed in
        if( ( isset( $_GET[ 'mag_id' ] ) === TRUE ) && ( intval( $_GET[ 'mag_id' ] ) <= 0 ) )
        {
            $this->failure_reason = 'mag_id invalid.';
            return $this->getJsonResponse();
        }



        // If we have a thing_video_id as a parameter then check that it is an
        // integer
        if( ( isset( $_GET[ 'thing_video_id' ] ) === TRUE ) && ( intval( $_GET[ 'thing_video_id' ] ) <= 0 ) )
        {
            $this->failure_reason = 'thing_video_id invalid.';
            return $this->getJsonResponse();
        }



        // If we have a mag_issue_id as a parameter then check that it is an
        // integer
        if( ( isset( $_GET[ 'mag_issue_id' ] ) === TRUE ) && ( intval( $_GET[ 'mag_issue_id' ] ) <= 0 ) )
        {
            $this->failure_reason = 'mag_issue_id invalid.';
            return $this->getJsonResponse();
        }



        // If we have got a country_group_id but it evaluates to less than 1, then it is not valid
        if( ( isset( $_GET[ 'country_group_id' ] ) === TRUE ) && ( intval( $_GET[ 'country_group_id' ] ) < -1 ) )
        {
            $this->failure_reason = 'country_group_id invalid.';
            return $this->getJsonResponse();
        }

        

        // If we have got an item_show_title_variation_id in the given
        // parameters, then it must have an integer value
        if( ( isset( $_GET[ 'item_show_title_variation_id' ] ) === TRUE ) && ( intval( $_GET[ 'item_show_title_variation_id' ] ) <= 0 ) )
        {
            $this->failure_reason = 'item_show_title_variation_id invalid.';
            return $this->getJsonResponse();
        }

        

        // If we have got an variation_type_id in the given
        // parameters, then it must have an integer value
        if( ( isset( $_GET[ 'variation_type_id' ] ) === TRUE ) && ( intval( $_GET[ 'variation_type_id' ] ) <= 0 ) )
        {
            $this->failure_reason = 'variation_type_id invalid.';
            return $this->getJsonResponse();
        }



        // If we have got a machine_type_id in the given
        // parameters, then it must have either an integer value
        // or be minus one
        if( isset( $_GET[ 'machine_type_id' ] ) === TRUE )
        {
            if( intval( $_GET[ 'machine_type_id' ] ) < 1 )
            {
                if( intval( $_GET[ 'machine_type_id' ] ) !== -1 )
                {
                    $this->failure_reason = 'machine_type_id invalid.';
                    return $this->getJsonResponse();
                }
            }
            
            if( intval( $_GET[ 'machine_type_id' ] ) > 0 )
            {
                $this->machineType = MachineType::model()->findByPk( $_GET[ 'machine_type_id' ] );
                
                if( !( $this->machineType instanceOf MachineType ) )
                {
                    $this->failure_reason = 'Machine Type ID did not instantiate a valid machineType object!';
                    return $this->getJsonResponse();
                }
            }
        }



        if( isset( $_GET[ 'machine_type_group_id' ] ) === TRUE )
        {
            if( intval( $_GET[ 'machine_type_group_id' ] ) < 1 )
            {
                $this->failure_reason = 'machine_type_group_id invalid.';
                return $this->getJsonResponse();
            }
        }



        // If we have got a show_title in the given
        // parameters, then it must have a value
        if( ( isset( $_GET[ 'show_title' ] ) === TRUE ) && ( empty( $_GET[ 'show_title' ] ) === TRUE ) )
        {
            $this->failure_reason = 'show_title invalid.';
            return $this->getJsonResponse();
        }



        // If we have got a natural_language_id in the given
        // parameters, then it must have a value
        if( ( isset( $_GET[ 'natural_language_id' ] ) === TRUE ) && ( empty( $_GET[ 'natural_language_id' ] ) === TRUE ) )
        {
            $this->failure_reason = 'natural_language_id invalid.';
            return $this->getJsonResponse();
        }



        // If we have got an item_title in the given
        // parameters, then it must have a value
        if( ( isset( $_GET[ 'item_title' ] ) === TRUE ) && ( empty( $_GET[ 'show_title' ] ) === TRUE ) )
        {
            $this->failure_reason = 'item_title invalid.';
            return $this->getJsonResponse();
        }



        // If we have got a thing_id in the given
        // parameters, then it must have an integer value
        if( ( isset( $_GET[ 'thing_id' ] ) === TRUE ) && ( intval( $_GET[ 'thing_id' ] ) <= 0 ) )
        {
            $this->failure_reason = 'thing_id invalid.';
            return $this->getJsonResponse();
        }



        // If we are toggling by a sales_item_id (such as if the user is using the
        // API command directly and following the documentation), then check that we have a valid
        // sales_item_id passed in
        if( ( isset( $_GET[ 'sales_item_id' ] ) === TRUE ) && ( intval( $_GET[ 'sales_item_id' ] ) <= 0 ) )
        {
            $this->failure_reason = 'sales_item_id invalid.';
            return $this->getJsonResponse();
        }

        
        
        // If we are toggling by an item_id then validate the Item
        if( isset( $_GET[ 'item_id' ] ) === TRUE )
        {
            $this->item = Item::model()->findByPk( $_GET[ 'item_id' ] );
            
            if( !( $this->item instanceOf Item ) )
            {
                $this->failure_reason = 'Item ID did not instantiate a valid item.';
                return $this->getJsonResponse();
            }
        }

        
        
        // If we are doing something with an author_id then validate the Author
        if( isset( $_GET[ 'author_id' ] ) === TRUE )
        {
            $this->author = Author::model()->findByPk( $_GET[ 'author_id' ] );
            
            if( !( $this->author instanceOf Author ) )
            {
                $this->failure_reason = 'Author ID did not instantiate a valid Author.';
                return $this->getJsonResponse();
            }
        }



        // If we are doing something with an upgrade_item_id then validate the Item
        if( isset( $_GET[ 'upgrade_item_id' ] ) === TRUE )
        {
            $this->upgradeItem = Item::model()->findByPk( $_GET[ 'upgrade_item_id' ] );
            
            if( !( $this->upgradeItem instanceOf Item ) )
            {
                $this->failure_reason = 'upgrade_item_id did not instantiate a valid item.';
                return $this->getJsonResponse();
            }
        }



        // If we are doing something with a bulk_search_machine_type_group_id then validate the object
        if( isset( $_GET[ 'bulk_search_machine_type_group_id' ] ) === TRUE )
        {
            $this->bulkSearchMachineTypeGroup = 
                BulkSearchMachineTypeGroup::model()->findByPk( $_GET[ 'bulk_search_machine_type_group_id' ] );
            
            if( !( $this->bulkSearchMachineTypeGroup instanceOf BulkSearchMachineTypeGroup ) )
            {
                $this->failure_reason = 'bulk_search_machine_type_group_id did not instantiate a valid object.';
                return $this->getJsonResponse();
            }
        }



        // If we are doing something with a problem then validate the Problem object
        if( isset( $_GET[ 'problem_id' ] ) === TRUE )
        {
            $this->problem = Problem::model()->findByPk( $_GET[ 'problem_id' ] );
            
            if( !( $this->problem instanceOf Problem ) )
            {
                $this->failure_reason = 'problem_id did not instantiate a valid Problem.';
                return $this->getJsonResponse();
            }
        }



        // If we are doing something with an upgrade_type_id then validate the upgrade_type
        if( isset( $_GET[ 'upgrade_type_id' ] ) === TRUE )
        {
            $this->upgradeType = UpgradeType::model()->findByAttributes( array( 'upgrade_type_id' => $_GET[ 'upgrade_type_id' ] ) );
            
            if( !( $this->upgradeType instanceOf UpgradeType ) )
            {
                $this->failure_reason = 'upgrade_type_id did not instantiate a valid upgradeType.';
                return $this->getJsonResponse();
            }
        }



        // If we are doing something with a necessity_id then validate it
        if( isset( $_GET[ 'necessity_id' ] ) === TRUE )
        {
            $this->necessity = Necessity::model()->findByPk( $_GET[ 'necessity_id' ] );
            
            if( !( $this->necessity instanceOf Necessity ) )
            {
                $this->failure_reason = 'necessity_id did not instantiate a valid upgradeType.';
                return $this->getJsonResponse();
            }
        }



        // If we are doing something with packaging then make sure it's a valid id
        if( isset( $_GET[ 'packaging_condition_id' ] ) === TRUE )
        {
            $this->packagingCondition = PackagingCondition::model()->findByPk( $_GET[ 'packaging_condition_id' ] );
            
            if( !( $this->packagingCondition instanceOf PackagingCondition ) )
            {
                $this->failure_reason = 'packaging_condition_id did not instantiate a valid packaging condition.';
                return $this->getJsonResponse();
            }
        }

        
        // If we are doing something with packaging then make sure it's a valid id
        if( isset( $_GET[ 'item_attachment_id' ] ) === TRUE )
        {
            $this->itemAttachment = ItemAttachment::model()->findByPk( $_GET[ 'item_attachment_id' ] );
            
            if( !( $this->itemAttachment instanceOf ItemAttachment ) )
            {
                $this->failure_reason = 'item_attachment_id did not instantiate a valid ItemAttachment object.';
                return $this->getJsonResponse();
            }
        }



        // If we are doing something with sales_item_boxed_state_id then make sure it's a valid id
        if( isset( $_GET[ 'sales_item_boxed_state_id' ] ) === TRUE )
        {
            $this->salesItemBoxedState = SalesItemBoxedState::model()->findByPk( $_GET[ 'sales_item_boxed_state_id' ] );
            
            if( !( $this->salesItemBoxedState instanceOf SalesItemBoxedState ) )
            {
                $this->failure_reason = 'sales_item_boxed_state_id did not instantiate a valid boxed condition.';
                return $this->getJsonResponse();
            }
        }


 
        if( isset( $_GET[ 'storage_container_id' ] ) === TRUE )
        {
            $this->storageContainer = StorageContainer::model()->findByPk( $_GET[ 'storage_container_id' ] );
            
            if( !( $this->storageContainer instanceOf StorageContainer ) )
            {
                $this->failure_reason = 'Storage Container ID did not instantiate a valid StorageContainer object.';
                return $this->getJsonResponse();
            }
        }

        
        if( isset( $_GET[ 'sales_item_id' ] ) === TRUE )
        {
            $this->salesItem = SalesItem::model()->findByPk( $_GET[ 'sales_item_id' ] );
            
            if( !( $this->salesItem instanceOf SalesItem ) )
            {
                $this->failure_reason = 'Sales Item ID did not instantiate a valid SalesItem object.';
                return $this->getJsonResponse();
            }
        }

        
        
        // If we are toggling by a shop_item_id then validate the ShopItem
        if( isset( $_GET[ 'shop_item_id' ] ) === TRUE )
        {
            $this->shopItem = ShopItem::model()->findByPk( intval( $_GET[ 'shop_item_id' ] ) );
            
            if( !( $this->shopItem instanceOf ShopItem ) )
            {
                $this->failure_reason = 'ShopItem ID did not instantiate a valid shop_item.';
                return $this->getJsonResponse();
            }
        }

        
        
        // If we are doing something by mag_issue_id then validate the MagIssue
        if( isset( $_GET[ 'mag_issue_id' ] ) === TRUE )
        {
            $this->magIssue = MagIssue::model()->findByPk( $_GET[ 'mag_issue_id' ] );
            
            if( !( $this->magIssue instanceOf MagIssue ) )
            {
                $this->failure_reason = 'mag_issue_id did not instantiate a valid object.';
                return $this->getJsonResponse();
            }
        }

        
        
        // If we are doing something by mag_article_id then validate the MagArticle
        if( isset( $_GET[ 'mag_article_id' ] ) === TRUE )
        {
            $this->magArticle = MagArticle::model()->findByPk( $_GET[ 'mag_article_id' ] );
            
            if( !( $this->magArticle instanceOf MagArticle ) )
            {
                $this->failure_reason = 'mag_article_id did not instantiate a valid object.';
                return $this->getJsonResponse();
            }
        }



        // If we have got an item_show_title_variation_id in the given parameters, then it must
        // instantiate a valid object
        if( isset( $_GET[ 'item_show_title_variation_id' ] ) === TRUE )
        {
            $this->itemShowTitleVariation = ItemShowTitleVariation::model()->findByPk( $_GET[ 'item_show_title_variation_id' ] );
            
            if( !( $this->itemShowTitleVariation instanceOf ItemShowTitleVariation ) )
            {
                $this->failure_reason = 'item_show_title_variation_id did not instantiate a valid ItemShowTitleVariation object.';
                return $this->getJsonResponse();
            }
        }



        // If we have got an variation_type_id in the given parameters, then it must
        // instantiate a valid object
        if( isset( $_GET[ 'variation_type_id' ] ) === TRUE )
        {
            $this->variationType = VariationType::model()->findByPk( $_GET[ 'variation_type_id' ] );
            
            if( !( $this->variationType instanceOf VariationType ) )
            {
                $this->failure_reason = 'variation_type_id did not instantiate a valid VariationType object.';
                return $this->getJsonResponse();
            }
        }



        if( ( isset( $_GET[ 'machine_type_id' ] ) ) && ( intval( $_GET[ 'machine_type_id' ] ) > 0 ) )
        {

            $this->machineType = MachineType::model()->findByPk( $_GET[ 'machine_type_id' ] );

            if( !( $this->machineType instanceOf MachineType ) )
            {
                $this->failure_reason = 'Given machine_type_id did not instantiate a ' .
                    'Machine Type object.';
                return $this->getJsonResponse();
            }        
            
        }

        // If we are toggling by a sales_item_id then validate the Sales Item
        // (and that it belongs to the logged in user)
        if( isset( $_GET[ 'sales_item_id' ] ) === TRUE )
        {
            $this->salesItem = SalesItem::model()->findByPk( $_GET[ 'sales_item_id' ] );

            if( !( $this->salesItem instanceOf SalesItem ) )
            {
                $this->failure_reason = 'sales_item_id did not instantiate a valid item.';
                return $this->getJsonResponse();
            }
            
        }
        

        // If we have got a thing_id then validate the Thing
        if( isset( $_GET[ 'thing_id' ] ) === TRUE )
        {
            $this->thing = Thing::model()->findByPk( $_GET[ 'thing_id' ] );
                        
            if( !( $this->thing instanceOf Thing ) )
            {
                $this->failure_reason = 'Thing ID did not instantiate a valid thing.';
                return $this->getJsonResponse();
            }
        }
        

        // If we have got a tag_id then validate the Tag
        if( isset( $_GET[ 'tag_id' ] ) === TRUE )
        {
            $this->tag = Tag::model()->findByPk( $_GET[ 'tag_id' ] );

            if( !( $this->tag instanceOf Tag ) )
            {
                $this->failure_reason = 'Tag ID did not instantiate a valid tag.';
                return $this->getJsonResponse();
            }
        }


        // If we have got a publisher_id then validate the Publisher
        if( ( isset( $_GET[ 'publisher_id' ] ) === TRUE ) && ( $_GET[ 'publisher_id' ] != -1 ) )
        {
            $this->publisher = Publisher::model()->findByPk( $_GET[ 'publisher_id' ] );
                        
            if( !( $this->publisher instanceOf Publisher ) )
            {
                $this->failure_reason = 'Publisher ID did not instantiate a valid publisher.';
                return $this->getJsonResponse();
            }
        }



        // If we have got a paarent_id then validate the Paarent
        if( ( isset( $_GET[ 'paarent_id' ] ) === TRUE ) && ( $_GET[ 'paarent_id' ] > 0 ) )
        {
            $this->paarent = Paarent::model()->findByPk( $_GET[ 'paarent_id' ] );
                        
            if( !( $this->paarent instanceOf Paarent ) )
            {
                $this->failure_reason = 'Paarent ID did not instantiate a valid Paarent.';
                return $this->getJsonResponse();
            }
        }

    }

}
