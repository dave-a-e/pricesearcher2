<?php

class ItemReportController extends WebPageController
{

    public static function gameOrItemShortDescription( Item $item )
    {
        // Default
        $shortDescription = 'No short description is available for this item yet.';

        if( empty( $item->itemThings ) === FALSE )
        {
            if( $item->flag_is_compilation == 0 )
            {
                if( isset( $item->itemThings[ 0 ]->paarentThings[ 0 ]->paarent ) )
                {
                    if( $item->itemThings[ 0 ]->paarentThings[ 0 ]->paarent->paarent_description !== '' )
                    {
                        $shortDescription = $item->itemThings[ 0 ]->paarentThings[ 0 ]->paarent->paarent_description;
                    }
                }
            }
            else
            {
                $shortDescription = $item->item_short_description;
            }
        }
        else
        {
            if( $item->item_short_description !== '' )
            {
                $shortDescription = $item->item_short_description;
            }
        }

        return $shortDescription;        
    }



    public static function htmlTableMagazineReviewQuotes( Item $item, $linkToReadFullReview )
    {
        $html = '';

        $quotes = array();

        // If the item is not a compilation, we want
        // to try and scoop up all the reviews of this game
        // on all of the formats (tape, disk, etc)
        
        $excludedItemTypeIDs = array( ItemType::ITEM_TYPE_BOOK, ItemType::ITEM_TYPE_MACHINE_EXPANSION );

        if( ( in_array( $item->item_type_id, $excludedItemTypeIDs ) === FALSE ) && 
            ( $item->flag_is_compilation == 0 ) )
        {
            
            // Use the game_id to get ALL non-compilation items that include this game
            // as any and all of these may have been reviewed :-)
            if( ( isset( $item->itemThings[ 0 ] ) === TRUE ) && ( $item->itemThings[ 0 ] instanceOf Thing ) )
            {
                $arrOfItemIDs = Thing::getNonCompilationItemsContainingThisThingID( $item->itemThings[ 0 ]->thing_id );
            }

            if( empty( $arrOfItemIDs ) === FALSE )
            {
                $quotes = ItemMagQuote::getArrayOfQuotesByItemIDs( $arrOfItemIDs );
            }            
        }
        else
        {
            if( Item::itemHasMagazineReviewQuotes( $item->item_id ) )
            {
                $quotes = ItemMagQuote::getArrayOfQuotesByItemIDs( array( 0 => $item->item_id ) );
            }
        }

        if( empty( $quotes ) === FALSE )
        {
            // Open the table
            $html .= '<table cellpadding="0" cellspacing="0" style="margin:0 auto 12px auto;color:#FFFFFF;">';

            for( $a = 0; $a < sizeof( $quotes ); $a++ )
            {
                // Open a new row
                $html .= '<tr>' . "\n";

                $landingPageLink = '';
                
                if( empty( $quotes[ $a ][ 'machine_type_group_default_folder' ] ) === FALSE )
                {
                    $magItem = Item::model()->findByPk( $quotes[ $a ][ 'item_id' ] );
                    $landingPageLink .= '<a style="text-decoration:none;" href="' . 
                            ConfigVariables::BASE_URL .
                            WebPageController::formUrlForLandingPageItem( $magItem ) . '">';
                }
                
                // Do table cell featuring quote
                $html .= '<td style="vertical-align:top;padding-bottom:12px;color:#FFFFFF;" width = "*">';
                $html .= '"' . $quotes[ $a ][ 'quote' ] . '"';
                
                if( $linkToReadFullReview === TRUE )
                {
                    
                    if( intval( $quotes[ $a ][ 'mag_article_id' ] ) > 0 )
                    {
                        $magArticle = MagArticle::model()->findByPk( $quotes[ $a ][ 'mag_article_id' ] );
                        
                        if( $magArticle instanceOf MagArticle )
                        {
                            $mag = $magArticle->mag;
                            
                            $filePath = WebPageController::formUrlForLandingPageMagArticle( 
                                $magArticle
                            );

                            if( $filePath !== '' )
                            {
                                $fullFilePath = 
                                    ConfigVariables::BASE_URL .
                                    $filePath;
                            }        

                            $html .= ' (';
                            
                            $html .= '<a href="' . $fullFilePath . '">Read Full Review</a>)';
                        }                        
                        
                    }
                }
                
                $html .= '<br />';
                $html .= '<span style="float:right;color:#FFFFFF;"><i>... ';
                
                if( $linkToReadFullReview === TRUE )
                {
                    $html .= $landingPageLink . $quotes[ $a ][ 'mag_name' ] . '</a>';
                }
                else
                {
                    $html .= $quotes[ $a ][ 'mag_name' ];
                }
                
                $html .= '</i>';

                if( empty( $quotes[ $a ][ 'machine_type_group_default_folder' ] ) === FALSE )
                {
                    $html .= '</a>';
                }
                
                $html .= '</span></td>' . "\n";

                // Do table cell showing small image of the magazine's cover
                $html .= '<td width="40" style="vertical-align: middle; text-align: right;">' . "\n";

                $covlTinyFileName = 'shop/covl_tiny/' . 
                    $quotes[ $a ][ 'machine_type_group_default_folder' ] . '/' .
                    $quotes[ $a ][ 'publisher_folder' ] . '/' .
                    $quotes[ $a ][ 'format_folder' ] . '/' . 
                    $quotes[ $a ][ 'item_title' ] . '-000.jpg';


                if( file_exists( Yii::app()->params[ 'legacyWebRoot' ] . $covlTinyFileName ) === TRUE )
                {
                    $html .= $landingPageLink .
                        '<img itemprop="image" src="' . 
                            ConfigVariables::LEGACY_BASE_URL . 
                            $covlTinyFileName . '"></a>';
                }

                $html .= '</td>' . "\n";

                // Close the row
                $html .= '</tr>' . "\n";
            }        
            
            $html .= '</table>';

        }

        return $html;

    }



    public static function htmlWhatTheySaid( Item $item, $linkToReadFullReview = TRUE )
    {

        $htmlTableMagQuotes = 
            ItemReportController::htmlTableMagazineReviewQuotes( $item, $linkToReadFullReview );
        
        $html = '';

        if( $htmlTableMagQuotes != '' )
        {
            $html .= '<h3 style="line-height:40px;">
                What They Said
                </h3>' . $htmlTableMagQuotes . '</p>';
        }
        
        return $html;
    }



    public static function htmlFormattedItemOriginalPrice( Item $item )
    {

        $html = '';

        // Preloaded items do not display a release price
        if( $item->formatTypeGroup->format_folder == 'preloaded' )
        {
            return $html;
        }        
        
        if( $item->item_type_id != ItemType::ITEM_TYPE_PUBLIC_DOMAIN )
        {
            if( floatval( $item->item_original_price ) > 0 )
            {
                $html .= '&#163;' . sprintf( "%1\$.2f", $item->item_original_price ) . '<br />';
            }
            else
            {
                $html .= 'Unknown<br />';
            }

        }

        return $html;        

    }



    public static function showBracketedReleaseDate( Item $item )
    {
        $bracketedReleaseDate = '';

        if( $item->release_date !== '0000-00-00 00:00:00' )
        {
            $bracketedReleaseDate = '(' . mb_substr( $item->release_date, 0, 4 ) . ')';
        } 
                    
        return $bracketedReleaseDate;
    }                                        



    public static function htmlMoreScreenshots( Item $item )
    {

        $html = '';
        $itemThings0IllsLess3 = 0;

        if( empty( $item->itemThings ) === FALSE )
        {

            // If the item is not a compilation then
            // three screens will already be shown at the top
            // of the Item Landing Page
            // and so we DON'T want to show those again
            if( $item->flag_is_compilation == 0 )
            {
                $ills = $item->itemThings[ 0 ]->ills;

                if( $ills != 0 )
                {
                    $itemThings0IllsLess3 = $item->itemThings[ 0 ]->ills - 3;

                    // Append the game screenshots for the only game (as not compilation)
                    $html .= ItemReportController::htmlGameScreenshots( $item, $itemThings0IllsLess3 );
                }
            }
            else
            {
                // The item is a compilation so we will show a maximum of four screenshots per game on it
                foreach( $item->itemThings as $c => $thing )
                {

                    // Append some information on the included game
                    $html .= '<h3 style = "line-height: 40px;">' . 
                        '<a id = "thing_link" href = "' . 
                        ConfigVariables::BASE_URL .
                        WebPageController::formUrlForLandingPageThing( $thing ) . 
                        '">' .
                        $thing->show_title . '</a> (' . 
                        $thing->machineTypeGroup->machine_type_group_name . 
                        ' Version, ' . $thing->ills . 
                        ' Screenshots)</h3>' . "\n" . 
                        '<div class = "row" style = "background-color: #1B1B1B;">';

                    for( $i = 0; $i < ( $thing->ills ); $i++ )
                    {
                        $filePath = WebPageController::getFilePath( $thing, $i, 'ills_thumbs' );
                    
                        $html .= '<div class = "col-sm-3 align-center">' . "\n";
                        $html .= WebPageController::htmlPopUpScreenshot( 
                            $thing,
                            $i );

                        $html .= '<img itemprop = "image" src = "' . 
                            ConfigVariables::LEGACY_BASE_URL .
                            $filePath . '">' . "\n" .
                            "</a>\n";
                        $html .= WebPageController::appendPopUpHtml( $thing, $i );
                        $html .= "\n" . '</div>' . "\n";
                    }

                    // Is ( $item->itemThings[ $c ]->ills ) divisible by 4 without leaving a remainder?
                    if( ( ( $thing->ills ) % 4 ) != 0 )
                    {
                        for( $j = 0; $j < ( 4 - ( $thing->ills % 4 ) ); $j++ )
                        {
                            $html .= "\n\t" . '<div class = "col-sm-3 align-center">&nbsp;</div>';
                        }                    
                    }

                    $html .= "\n" . '</div>' . "\n" . "\n";
                    
                }

            }

        }

        return $html;        

    }



    public static function showAuthorsIfNotCompilation( Item $item, $withPrefix = TRUE, $linkType )
    {
        
        $html = '';

        if( $item->flag_is_compilation == 0 )
        {
            // If not a compilation, it will only feature one game of course, and that game will be
            // $itemThings[ 0 ]
            
            if( empty( $item->itemThings ) === FALSE )
            {
                if( $item->itemThings[ 0 ] instanceOf Thing )
                {
                    $html .= WebPageController::showAuthors( $item->itemThings[ 0 ], $withPrefix, $linkType );
                }
            }
            else
            {
                $authors = $item->itemAuthors;
                
                if( $withPrefix === TRUE )
                {
                    $html .= 'Author(s): ';
                }

                $html .= Author::convertAuthorsToHtml( $authors, $linkType );            
            }
        }

        return $html;
    }



    public static function explainScreenshotsShown( $itemThings )
    {
        $html = '';

        if( $itemThings[ 0 ]->ills > 0 )
        {
            $html .= 'The screenshots shown are for the ' . $itemThings[ 0 ]->machineTypeGroup->machine_type_group_name . ' ' .
                'version of the game.';
        }
        
        return $html;        
    }



    public static function htmlContainsMultipleVersions( Item $item )
    {

        $htmlContainsMultipleVersions = '';
        
        if( $item->flag_contains_multiple_versions )
        {
            $htmlContainsMultipleVersions = '<h3 style = "line-height: 40px;">' . "\n" .
                'Multiple Versions' . "\n" .
                '</h3>' .
                '<p>' .
                'This item contains versions for ' . 
                ItemReportController::showMultipleVersions( $item->itemThings ) . '<br />' .
                ItemReportController::explainScreenshotsShown( $item->itemThings ) .
                '</p>';
        }
        
        return $htmlContainsMultipleVersions;
        
    }



    public static function showMultipleVersions( Array $itemThings )
    {
        $machines = array();
        
        foreach( $itemThings as $id => $thing )
        {
            if( in_array( $thing->machineTypeGroup->machine_type_group_name, $machines ) === FALSE )
            {
                $machines[] = $thing->machineTypeGroup->machine_type_group_name;
            }
        }
        
        $machinesHtml = '';

        for( $x = 0; $x < sizeof( $machines ); $x++ )
        {
            $machinesHtml .= $machines[ $x ];
            
            if( $x == ( sizeof( $machines ) - 2 ) )
            {
                $machinesHtml .= ' and ';                
            }
            else
            {
                $machinesHtml .=  ', ';
            }
        }
        
        return mb_substr( $machinesHtml, 0, -2 ) . '.';        
    }



    public static function htmlGameScreenshots( Item $item, $itemThings0IllsLess3 )
    {

        $html = '';
                      
        if( ( $item->itemThings[ 0 ]->ills ) > 3 )
        {

            // Write a header saying 'More screenshots'
            $html .= '<h3 style = "line-height: 40px;">More Screenshots (' . 
                ( $item->itemThings[ 0 ]->ills - 3 ) . ')</h3>';

            // If there are more screenshots available then
            if( ( $item->itemThings[ 0 ]->ills - $itemThings0IllsLess3 ) > 0 )
            {
                
                $html .= '<div id = "more_screenshots_area"><section id = "screenshots_section_02">';

                for( $i = 3; $i < ( $item->itemThings[ 0 ]->ills ); $i++ )
                {
                    $html .= 
                        WebPageController::htmlScreenshot( 
                            $item, 
                            $i, 
                            TRUE, 
                            214, 
                            array( 
                                'pre_html' => '<div class = "col-sm-3 align-center">', 
                                'post_html' => '</div>' ) 
                            );
                }            

                // Is ( $item->itemThings[ 0 ]->ills ) divisible by 4 without leaving a remainder?
                
                if( ( ( $item->itemThings[ 0 ]->ills - 3 ) % 4 ) != 0 )
                {
                    for( $j = 0; $j < ( 4 - ( $item->itemThings[ 0 ]->ills - 3 ) % 4 ); $j++ )
                    {
                        $html .= '
                            <div class = "col-sm-3 align-center">&nbsp;</div>';
                    }                    
                }
                
                $html .= "\n" . '</section></div>';
            }
            
        }

        return $html;

    }



    public static function showExpansionRequiredDescriptionByItem( Item $item, $withBrackets = TRUE )
    {
        $html = '';

        if( empty( $item->itemThings ) === FALSE )
        {
            if( $item->itemThings[ 0 ] instanceOf Thing )
            {
                $html .= WebPageController::showExpansionRequiredDescriptionByThing( $item->itemThings[ 0 ] );        
            }
        }        

        return $html;
        
    }



    public static function showMachineCompatibility( Item $item, $linkType )
    {

        $html = '';

        if( $item->item_type_id != ItemType::ITEM_TYPE_MACHINE )
        {

            if( empty( $item->machineTypeGroup->machineTypes ) === FALSE )
            {
                foreach( $item->machineTypeGroup->machineTypes as $c => $machineType )
                {
                    if( $linkType == 'landing' )
                    {
                        // todo alter this to appear correctly
                        $html .= '<a href = "' .
                            ConfigVariables::BASE_URL . 
                            'landingMachineType/index/machine_folder/' .
                            $machineType->machine_folder . '/thing_type/games/">' .
                            $machineType->item->show_title . 
                            ItemReportController::showExpansionRequiredDescriptionByItem( 
                                $item, 
                                $withBrackets = TRUE 
                            ) . '</a>, ';
                    }
                    else
                    {
                        $html .= 
                            $machineType->item->show_title . 
                            ItemReportController::showExpansionRequiredDescriptionByItem( 
                                $item, 
                                $withBrackets = TRUE 
                            ) . ', ';
                    }

                }
                
            }
            
        }
        
                
        return mb_substr( $html, 0, -2 );        

    }



    public static function assessAvailableScreenshots( Item $item )
    {
        $htmlTemplate = '';
        
        // If the item is not a compilation, then work out how many screenshots we have
        // for the one game on it
        if( $item->flag_is_compilation == 0 )
        {
            if( ( isset( $item->itemThings[ 0 ] ) === TRUE ) && ( $item->itemThings[ 0 ]->ills > 3 ) )
            {
                // If there are at least three screenshots then return the holders for each of them
                $htmlTemplate .= '%SCREENSHOT_000%%SCREENSHOT_001%%SCREENSHOT_002%';
            }
            else
            {
                // If there are more than 0 but less than 3 then retur the holders for how many there are
        if( ( isset( $item->itemThings[ 0 ] ) === TRUE ) && ( $item->itemThings[ 0 ]->ills > 0 ) )
                {
                    
                    for( $x = 1; $x <= $item->itemThings[ 0 ]->ills; $x++ )
                    {
                        $htmlTemplate .= '%SCREENSHOT_' . sprintf( '%03d', ( $x - 1 ) ) . '%';
                    }                  

                }
            }
        }

        
        // If the item is a compilation then see if there are "plenty" of ills for it
        // or whether there are zero
        if( $item->flag_is_compilation == 1 )
        {
            $totalNumberOfIlls = 0;

            if( is_array( $item->itemThings ) === TRUE )
            {

                foreach( $item->itemThings as $i => $thing )
                {
                    $totalNumberOfIlls += $thing->ills;
                }

                if( $totalNumberOfIlls > 3 )
                {
                    // If there are at least three screenshots then return the holders for each of them
                    $htmlTemplate .= '%SCREENSHOT_000%%SCREENSHOT_001%%SCREENSHOT_002%';
                }
                else
                {
                    // If there are more than 0 but less than 3 then return the holders for how many there are

            if( ( isset( $item->itemThings[ 0 ] ) === TRUE ) && ( $item->itemThings[ 0 ]->ills > 0 ) )
                    {
                        
                        for( $x = 1; $x <= $totalNumberOfIlls; $x++ )
                        {
                            $htmlTemplate .= '%SCREENSHOT_' . sprintf( '%03d', ( $x - 1 ) ) . '%';
                        }                  

                    }

                }
                
            }
            
        }
        
        $htmlTemplate .= '';

        return $htmlTemplate;
    }



    public static function htmlAnonymousReporting()
    {
        $html = '<p>You are not currently logged in so your report will be anonymous.</p>';

        // If the user is logged in then just blank out this part
        if( Yii::app()->user->getId() > 0 )
        {
            $html = '';
        }

        return $html;    
    }



    public static function htmlCoverArtPanel( Item $item )
    {
        $html = '';

        if( $item->covs > 0 )
        {
            $filePath = $item->getImageFilePath() . 
                $item->item_title;
                
            $html .= '<!-- START OF COVER ART PANEL -->
            <div class = "col-sm-3">
            <a onclick = "popCoverArt( ' . 
                "'" .
                $filePath . 
                "'" . 
                ');">
                
                <div style="width: 100%; height: 274px; ' .
                'background-image: url(\'' .
                ConfigVariables::LEGACY_BASE_URL . 'covs/' .
                $filePath . '-000.jpg' .
                '\'); background-repeat: no-repeat; background-position: center;">
            %ON_WISH_LIST_RIBBON%
            %IN_COLLECTION_RIBBON%
            </div>
            </a>
            </div>

            <div id = "cover-art-popup" class = "cover-art-popup" role = "alert">
            <div id = "cover-art-popup-container" class = "cover-art-popup-container" style = "max-width: 800px; max-height: 800px; text-align: center;">
            <a href = "#0" class = "cover-art-popup-close img-replace"></a>
            <div id = "egg_cover_art_container">
            <a href = "' . 
                ConfigVariables::LEGACY_BASE_URL .
                'covl/' . $filePath . '-000.jpg" target = "_new">
            <img class = "img-responsive align-center" src = "' .
                ConfigVariables::LEGACY_BASE_URL . 
                'covl/' . $filePath . '-000.jpg" height = "360"></a>
            </div>                               
            </div>
            </div>

            </section>
            <!-- END OF COVER ART PANEL -->';
        }
        else
        {
            if( $item->media_scans > 0 )
            {
                $filePath = $item->getImageFilePath() . $item->item_title;
                    
                $html .= '<!-- START OF COVER ART PANEL -->
                <div class = "col-sm-3 align-center">
                <a onclick = "popCoverArt( ' . 
                    "'" .
                    $filePath . 
                    "'" . 
                    ');">
                <img itemprop = "image" class = "egg_item_landing_page_vertical_align_cover_art" src = "' . 
                    ConfigVariables::LEGACY_BASE_URL .
                    'media_scans_rotated_left/' . 
                    $filePath . '-000.jpg" width = "150"></a>
                <div id = "cover-art-popup" class = "cover-art-popup" role = "alert">
                <div id = "cover-art-popup-container" class = "cover-art-popup-container" style = "max-width: 800px; text-align: center;">
                <a href = "#0" class = "cover-art-popup-close img-replace"></a>
                <div id = "egg_cover_art_container">
                <a href = "' . 
                    ConfigVariables::LEGACY_BASE_URL .
                    'media_scans/' . $filePath . '-000.jpg" target = "_new">
                <img class = "img-responsive align-center" src = "' . 
                    ConfigVariables::LEGACY_BASE_URL .
                    'media_scans/' . $filePath . '-000.jpg" height = "360"></a>
                </div>
                </div>
                </div>
                </div>
                </section>
                <!-- END OF COVER ART PANEL -->';
            }
            else
            {
                $html .= '<!-- START OF COVER ART PANEL -->
                <div class = "col-sm-3 align-center">
                &nbsp;
                </div>
                <!-- END OF COVER ART PANEL -->';
            }

        }
        
        return $html;
        
    }

}