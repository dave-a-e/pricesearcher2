<?php

class ShopViewController extends ShopWebPageController
{
 
    public function actionView()
    {
        
        $shopItem = ShopItem::model()->findByPk( $_GET[ 'shop_item_id' ] );
        
        $landingHtml = $this->render('//shopView/view',array('shopItem'=>$shopItem),true );
        
        // Now we apply lots of changes to the html before we display it.
        $replacements = array();
        
        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;
    }

}
