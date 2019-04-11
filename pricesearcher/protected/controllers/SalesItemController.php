<?php

class SalesItemController extends WebPageController
{

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$salesItem = new SalesItem;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($salesItem);

		if(isset($_POST['SalesItem']))
		{

            $target_dir = "images/";
            $filename = str_replace( ' ', '-', mb_strtolower( strip_tags( preg_replace( "/[^A-Za-z0-9 ]/", '', $_POST[ 'SalesItem'][ 'product_name' ] ) ) ) );
            
            $imageFileType = strtolower(pathinfo(basename( $_FILES["imgInp"]["name"]),PATHINFO_EXTENSION));

            $target_file = $target_dir . $filename . '-000.' . $imageFileType;

            $uploadOk = 1;
            $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
            // Check if image file is a actual image or fake image
            if(isset($_POST["submit"])) {
                $check = getimagesize($_FILES["imgInp"]["tmp_name"]);
                if($check !== false) {
                    echo "File is an image - " . $check["mime"] . ".";
                    $uploadOk = 1;
                } else {
                    echo "File is not an image.";
                    $uploadOk = 0;
                }
            }
            // Check if file already exists
            if (file_exists($target_file)) {
                echo "Sorry, file already exists.";
                $uploadOk = 0;
            }
            // Check file size
            if ($_FILES["imgInp"]["size"] > 1000000) {
                echo "Sorry, your file is too large.";
                $uploadOk = 0;
            }
            // Allow certain file formats
            if($imageFileType != "jpg" && $imageFileType != "jpeg" ) {
                echo "Sorry, only JPG & JPEG files are allowed.";
                $uploadOk = 0;
            }
            // Check if $uploadOk is set to 0 by an error
            if ($uploadOk == 0) {
                echo "Sorry, your file was not uploaded.";
            // if everything is ok, try to upload file
            } else {
                if (move_uploaded_file($_FILES["imgInp"]["tmp_name"], $target_file)) {
                    SalesItemController::makeImageSquare( $target_file );
                } else {
                    echo "Sorry, there was an error uploading your file.";
                    die;
                }
            }
            
			$salesItem->attributes = $_POST['SalesItem'];
            $salesItem->sales_item_state_id = SalesItemState::STATE_ACTIVE;
            $salesItem->filename = $filename;

 			if( $salesItem->save() )
            {
                if( isset( $_POST[ 'addSalesItemImmediatelyToShop' ] ) )
                {
                    // We may be able to refactor this later in time
                    
                    $shopItem = $salesItem->placeIndividualSalesItemInShop( $salesItem->price );
                    
                    if( $shopItem instanceOf ShopItem )
                    {
                        $shopItem->shop_item_state_id = ShopItemState::SHOP_ITEM_AVAILABLE;
                        $doSave = $shopItem->save();
                    }
                    else
                    {
                        print 'Failure to add sales_item immediately to shop. Please notify Tech.';
                        die;
                    }

                }

                $this->redirect(array('view','id'=>$salesItem->sales_item_id));
            }
				
		}

		$html = $this->render('create',array(
			'model'=>$salesItem ), true );
        
        $replacements[ '%ACTIVE_PRODUCT_TYPES%' ] = SalesItemController::getActiveProductTypesAsSelectList( 0 );
        
        foreach( $replacements as $search => $replace )
        {
            $html = str_replace( $search, $replace, $html );
        }
        
        print $html;
        die;
	}

    

    public static function makeImageSquare( $target_file )
    {
        
        // Load the image into memory
        $src = imagecreatefromjpeg( $target_file );
        
        $size = getimagesize( $target_file );
        
        $widthOfOriginalImage = $size[ 0 ];
        $heightOfOriginalImage = $size[ 1 ];
        
        $biggestLength[ 'length' ] = $heightOfOriginalImage;
        $biggestLength[ 'set' ] = 'portrait';

        if( $widthOfOriginalImage >= $biggestLength[ 'length' ] )
        {
            $biggestLength[ 'length' ] = $widthOfOriginalImage;
            $biggestLength[ 'set' ] = 'landscape';
        }

        $im = @imagecreatetruecolor( $biggestLength[ 'length' ], $biggestLength[ 'length' ] );

        if( $biggestLength[ 'set' ] == 'portrait' )
        {
            $dst_x = ( $biggestLength[ 'length' ] - $widthOfOriginalImage ) / 2;
            $dst_y = 0;
        }
        else
        {
            $dst_x = 0;
            $dst_y = ( $biggestLength[ 'length' ] - $heightOfOriginalImage ) / 2;
        }

        $bgc = imagecolorallocate($im, 255, 255, 255);

        imagefilledrectangle($im, 0, 0, $biggestLength[ 'length' ], $biggestLength[ 'length' ], $bgc );
        
        imagecopy( $im, $src, $dst_x, $dst_y, 0, 0, $widthOfOriginalImage, $heightOfOriginalImage );

        /*
        header( 'Content-Type: image/jpeg' );
        imagejpeg( $im );
        */
        
        imagejpeg( $im, $target_file );
        
    }



    public static function getActiveProductTypesAsSelectList( $selectedProductTypeID )
    {
        $sql = 'SELECT product_type_id, product_type_desc FROM `_product_type` WHERE product_type_state_id = ' .
            ProductTypeState::STATE_ACTIVE . ' ORDER BY product_type_desc ASC';
        
        $r = Yii::app()->db->createCommand( $sql )->queryAll();
        
        $html = '';
        
        if( empty( $r ) === FALSE )
        {
            foreach( $r as $i => $row )
            {
                $html .= '<option value = "' . $row[ 'product_type_id' ] . '"';
                
                if( intval( $row[ 'product_type_id' ] ) === intval( $selectedProductTypeID ) )
                {
                    $html .= 'selected';
                }
                
                $html .= '>' .
                    $row[ 'product_type_desc' ] . 
                    '</option>';
            }
        }
        
        return $html;
    }


	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
        
		$salesItem = $this->loadModel( $id );
        
		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['SalesItem']))
		{

            
            $filename = str_replace( ' ', '-', mb_strtolower( strip_tags( preg_replace( "/[^A-Za-z0-9 ]/", '', $salesItem->product_name ) ) ) );

            if( empty( $_FILES ) === FALSE )
            {

                $target_dir = "images/";
                
                $imageFileType = strtolower(pathinfo(basename( $_FILES["imgInp"]["name"]),PATHINFO_EXTENSION));

                $target_file = $target_dir . $filename . '-000.' . $imageFileType;

                $uploadOk = 1;
                $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
                // Check if image file is a actual image or fake image
                if(isset($_POST["submit"])) {
                    $check = getimagesize($_FILES["imgInp"]["tmp_name"]);
                    if($check !== false) {
                        echo "File is an image - " . $check["mime"] . ".";
                        $uploadOk = 1;
                    } else {
                        echo "File is not an image.";
                        $uploadOk = 0;
                    }
                }

                // Check file size
                if ($_FILES["imgInp"]["size"] > 1000000) {
                    echo "Sorry, your file is too large.";
                    $uploadOk = 0;
                }
                // Allow certain file formats
                if($imageFileType != "jpg" && $imageFileType != "jpeg" ) {
                    echo "Sorry, only JPG & JPEG files are allowed.";
                    $uploadOk = 0;
                }
                // Check if $uploadOk is set to 0 by an error
                if ($uploadOk == 0) {
                    echo "Sorry, your file was not uploaded.";
                // if everything is ok, try to upload file
                } else {
                    if (move_uploaded_file($_FILES["imgInp"]["tmp_name"], $target_file)) {
                        SalesItemController::makeImageSquare( $target_file );
                    } else {
                        echo "Sorry, there was an error uploading your file.";
                        die;
                    }
                }

                $salesItem->filename = $filename;
                
            }

            $salesItem->attributes = $_POST['SalesItem'];
            $salesItem->modifier_id = Yii::app()->user->getId();
            $salesItem->date_modified = new CDbExpression('UTC_TIMESTAMP()');

            if( $salesItem->save() )
            {
				$this->redirect(array('view','id'=>$salesItem->sales_item_id));
            }
		}

		$html = $this->render('update',array(
			'model'=>$salesItem,
		), true );
        
        $replacements[ '%ACTIVE_PRODUCT_TYPES_WITH_SELECTED%' ] = 
            SalesItemController::getActiveProductTypesAsSelectList( $salesItem->product_type_id );
        
        $replacements[ '%SALES_ITEM_STATES_WITH_SELECTED%' ] =
            SalesItemController::getSalesItemStatesWithSelected( $salesItem );
        
        foreach( $replacements as $search => $replace )
        {
            $html = str_replace( $search, $replace, $html );
        }
        
        print $html;
        die;
	}

    

    public static function getSalesItemStatesWithSelected( SalesItem $salesItem )
    {

        $sql = 'SELECT sales_item_state_id, sales_item_state_desc FROM `_sales_item_state` ORDER BY sales_item_state_id ASC';
        
        $r = Yii::app()->db->createCommand( $sql )->queryAll();
        
        $html = '';
        
        if( empty( $r ) === FALSE )
        {
            foreach( $r as $i => $row )
            {
                $html .= '<option value = "' . $row[ 'sales_item_state_id' ] . '"';
                
                if( intval( $row[ 'sales_item_state_id' ] ) === intval( $salesItem->sales_item_state_id ) )
                {
                    $html .= 'selected';
                }
                
                $html .= '>' .
                    $row[ 'sales_item_state_desc' ] . 
                    '</option>';
            }
        }
        
        return $html;
        
    }



	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		$this->loadModel($id)->delete();

		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
	}

	/**
	 * Lists all models.
	 */
	public function actionList()
	{
		$dataProvider = new CActiveDataProvider('SalesItem');
		$this->render( 'list', array(
			'dataProvider' => $dataProvider,
		) 
        );
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model=new SalesItem('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['SalesItem']))
			$model->attributes=$_GET['SalesItem'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return SalesItem the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
		$model=SalesItem::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param SalesItem $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='sales-item-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
