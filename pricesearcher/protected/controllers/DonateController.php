<?php

class DonateController extends ShopWebPageController
{
 
    public function actionIndex()
    {
        $this->render( '//donate/index', true );
    }

}
