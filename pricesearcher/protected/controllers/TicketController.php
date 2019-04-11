<?php

class TicketController extends WebPageController
{

    const NUM_PER_PAGE_MAX = 50;

	public function actionIndex()
	{
		$html = $this->render('index', array(), true);

        $replacements = array();
                
        $replacements[ '%SUMMARISE_TICKETS%' ] = TicketController::htmlSummariseTickets();
        
        foreach( $replacements as $search => $replace )
        {
            $html = str_replace( $search, $replace, $html );
        }

        print $html;
        die;
	}

    

    public static function htmlSummariseTickets()
    {

        $matrix = TicketController::summariseTickets();
        
        $html = '';

        foreach( $matrix as $ticket_type_desc => $ticket_state_desc_pair )
        {
            
            $html .= '<tr>';
            $html .= '<td>' . $ticket_type_desc . '</td>';

            foreach( $ticket_state_desc_pair as $ticket_state_desc => $keyValues )
            {
                
                $html .= '<td style = "text-align: center;">';
                
                if( isset( $keyValues[ 'count' ] ) === TRUE )
                {
                    $html .= '<a href = "' . 
                    Yii::app()->request->baseUrl . 
                    '/ticket/list_all/ticket_type_id/' . $keyValues[ 'ticket_type_id' ] . '/' . 
                    'ticket_state_id/' . $keyValues[ 'ticket_state_id' ] . '/">' . 
                    $keyValues[ 'count' ] . '</a>';
                }
                else
                {
                    $html .= '0';
                }
                
            }

            $html .= '</tr>';

        }
        
        return $html;
    }



    public static function summariseTickets()
    {

        $sql = 'SELECT ticket_type_desc FROM `_ticket_type';
        
        $r = Yii::app()->db->createCommand( $sql )->queryAll();
        
        foreach( $r as $i => $row )
        {
            $ticketTypeDescs[ $row[ 'ticket_type_desc' ] ] = $row[ 'ticket_type_desc' ];
        }

        $sql = 'SELECT ticket_state_desc FROM `_ticket_state';
        
        $r = Yii::app()->db->createCommand( $sql )->queryAll();
        
        foreach( $r as $i => $row )
        {
            $ticketStateDescs[ $row[ 'ticket_state_desc' ] ] = $row[ 'ticket_state_desc' ];
        }

        $sql = 'SELECT COUNT(*) AS count, t.ticket_state_id, t.ticket_type_id, ts.ticket_state_desc, tt.ticket_type_desc FROM `_ticket` t
            INNER JOIN `_ticket_state` ts ON t.ticket_state_id = ts.ticket_state_id
            INNER JOIN `_ticket_type` tt ON t.ticket_type_id = tt.ticket_type_id
            GROUP BY t.ticket_state_id, t.ticket_type_id ORDER BY t.ticket_type_id ASC';
            
        $r = Yii::app()->db->createCommand( $sql )->queryAll();
        
        // Convert the information into an array
        $matrix = array();

        foreach( $ticketTypeDescs as $ticketTypeDesc )
        {
            $matrix[ $ticketTypeDesc ] = array();

            foreach( $ticketStateDescs as $ticketStateDesc )
            {
                $matrix[ $ticketTypeDesc ][ $ticketStateDesc ] = array();
            }
        }

        foreach( $r as $i => $row )
        {
            $matrix[ $row[ 'ticket_type_desc' ] ][ $row[ 'ticket_state_desc' ] ][ 'count' ] = $row[ 'count' ];
            $matrix[ $row[ 'ticket_type_desc' ] ][ $row[ 'ticket_state_desc' ] ][ 'ticket_state_id' ] = $row[ 'ticket_state_id' ];
            $matrix[ $row[ 'ticket_type_desc' ] ][ $row[ 'ticket_state_desc' ] ][ 'ticket_type_id' ] = $row[ 'ticket_type_id' ];
        }
                
        return $matrix;
        
    }



	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{

        $ticket = Ticket::model()->findByPk( $id );

        $this->pageTitle = 'View Ticket #' . $id . ' - ' . Yii::app()->name;

		$html = $this->render('view',array(
			'model'=>$this->loadModel($id),
		), true );
        
        $replacements = array(
            '%PREVIOUS_TICKET_ID%' => TicketController::getPreviousTicketIfExists( $id ),
            '%NEXT_TICKET_ID%' => TicketController::getNextTicketIfExists( $id ),
            '%SHOW_TICKET_DESCRIPTION_IF_NOT_EMPTY%' =>
                TicketController::showTicketDescriptionIfNotEmpty( $ticket ),
            '%SHOW_TICKET_TYPE_WITH_LINK_TO_TICKET_TYPES_OF_SAME_STATE_AND_TYPE%' => 
                $ticket->showTicketTypeWithLinkToTicketTypesOfSameStateAndType(),
	        '%OPTIONS%' => TicketController::showTicketOptions( $ticket ),

        );
        
        foreach( $replacements as $search => $replace )
        {
            $html = str_replace( $search, $replace, $html );
        }
        
        print $html;
        die;
        
        
	}


	public static function showTicketOptions( Ticket $ticket )
	{
		$html = '<a href = "' . 
			Yii::app()->request->baseUrl . '/ticket/update/id/' . 
			$ticket->ticket_id . 
			'/"><button class = "btn btn-primary btn-sm">Edit</button></a>';

		return $html;
	}



    public static function showTicketDescriptionIfNotEmpty( Ticket $ticket )
    {
        
        if( empty( $ticket->ticket_description ) === FALSE )
        {

            $htmlTemplate = '<div class = "row">
                <div class = "col-lg-12">Ticket Description</div>
            </div>
            <div class = "row">
                <div class = "col-lg-12" style = "margin: 12px; border: double;">%SHOW_FORMATTED_TICKET_DESCRIPTION%</div>
            </div>';

            $replacements[ '%SHOW_FORMATTED_TICKET_DESCRIPTION%' ] = $ticket->showFormattedTicketDescription();
            
            foreach( $replacements as $search => $replace )
            {
                $htmlTemplate = str_replace( $search, $replace, $htmlTemplate );
            }
            
            return $htmlTemplate;
        }
        
        return '';

    }


    public static function getNextTicketIfExists( $ticket_id )
    {
        $sql = 'SELECT ticket_id FROM `_ticket` WHERE ticket_id > ' . $ticket_id . ' ORDER BY ticket_id ASC LIMIT 1';
            
        $result = Yii::app()->db->createCommand( $sql )->queryAll();
        
        if( empty( $result ) === FALSE )
        {
            return $result[ 0 ][ 'ticket_id' ];
        }
        
        return $ticket_id;
    }



    public static function getPreviousTicketIfExists( $ticket_id )
    {
        $sql = 'SELECT ticket_id FROM `_ticket` WHERE ticket_id < ' . $ticket_id . ' ORDER BY ticket_id DESC LIMIT 1';
            
        $result = Yii::app()->db->createCommand( $sql )->queryAll();
        
        if( empty( $result ) === FALSE )
        {
            return $result[ 0 ][ 'ticket_id' ];
        }
        
        return $ticket_id;
    }



	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
        
	        $this->pageTitle = 'Create New Ticket - ' . Yii::app()->name;
        
		$model = new Ticket;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Ticket']))
		{
			$model->attributes=$_POST['Ticket'];
			if($model->save())
				$this->redirect(array('view','id'=>$model->ticket_id));
		}

		$html = $this->render( 'create', array(
			'model' => $model,
		), true );
        
        $defaultTicketType = TicketType::TYPE_BUG;
        
        if( isset( $_GET[ 'ticket_type_id' ] ) === TRUE )
        {
            $defaultTicketType = intval( $_GET[ 'ticket_type_id' ] );
        }
        $replacements[ '%TICKET_STATE_OPTIONS%' ] = TicketState::getAllTicketStates( TicketState::STATE_NOT_STARTED );
        $replacements[ '%TICKET_TYPE_OPTIONS%' ] = TicketType::getAllTicketTypes( $defaultTicketType );
        $replacements[ '%TICKET_PRIORITY_OPTIONS%' ] = TicketPriority::getAllTicketPriorities( TicketPriority::AVERAGE );
        
        foreach( $replacements as $search => $replace )
        {
            $html = str_replace( $search, $replace, $html );
        }
        
        print $html;
        die;
	}







    



	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */

	public function actionUpdate($id)
	{
		$model=$this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Ticket']))
		{
			$model->attributes=$_POST['Ticket'];
			if($model->save())
				$this->redirect(array('view','id'=>$model->ticket_id));
		}

        $html = $this->render( 'update', array(
            'model' => $model,
        ), true );
        
        $replacements[ '%TICKET_STATE_OPTIONS%' ] = TicketState::getAllTicketStates( $model->ticket_state_id );
        $replacements[ '%TICKET_TYPE_OPTIONS%' ] = TicketType::getAllTicketTypes( $model->ticket_type_id );
        $replacements[ '%TICKET_PRIORITY_OPTIONS%' ] = TicketPriority::getAllTicketPriorities( $model->ticket_priority_id );
        
        foreach( $replacements as $search => $replace )
        {
            $html = str_replace( $search, $replace, $html );
        }
        
        print $html;
        die;
        
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




    public function actionList_All()
    {

        
        $landingHtml = $this->render('list_active',array(),true );

        $params = array();
        
        $params[ 'ticket_type_id' ] = 1;

        if( isset( $_GET[ 'ticket_type_id' ] ) === TRUE )
        {
            $params[ 'ticket_type_id' ] = intval( $_GET[ 'ticket_type_id' ] );
        }
        
        $params[ 'ticket_state_id' ] = 1;

        if( isset( $_GET[ 'ticket_state_id' ] ) === TRUE )
        {
            $params[ 'ticket_state_id' ] = intval( $_GET[ 'ticket_state_id' ] );
        }

        $ticketType = TicketType::model()->findByPk( $params[ 'ticket_type_id' ] );

        $params[ 'page' ] = WebPageResultsController::getThisPageFromGetParameter();
        $params[ 'sort' ] = WebPageResultsController::getSortArrayFromGetParameters( 'ticket_priority_id|ticket_id' );
        $params[ 'num_per_page_max' ] = TicketController::NUM_PER_PAGE_MAX;
        
        $searchResults = TicketController::generateTicketSearchResultList( $params );
        
        $totalNumberOfItemsPlusHtml = $this->generateHtmlSearchResultList( 
            $params, 
            $searchResults );

        $totalNumberOfHits = $totalNumberOfItemsPlusHtml[ 'total_number_of_hits' ];

        $htmlSearchResults = $totalNumberOfItemsPlusHtml[ 'html_search_results' ];

        $params[ 'list_php_page' ] = 'ticket/list_all/';
        $params[ 'total_number_of_hits' ] = $totalNumberOfHits;

        // Now we apply lots of changes to the html before we display it.            
        $replacements = array(
            '%TICKET_TYPE_ID%' => $ticketType->ticket_type_id,
            '%TICKET_TYPE_DESC%' => $ticketType->ticket_type_desc,
            '%EXPLAIN_TYPE_TICKET%' => $ticketType->explainTypeTicket(),
            '%HTML_SEARCH_RESULTS%' => $htmlSearchResults,
            '%HTML_FORM_FOOTER_OPTIONS%' => 
                WebPageResultsController::generatePageSelector( $params ),
        );

        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;

    }



    public static function generateTicketSearchResultList( Array $params )
    {

        $firstLimitParam = $params[ 'page' ] * $params[ 'num_per_page_max' ];

        // Create the Order By Clause for the end of the query
        $orderByClause = '';

        if( isset( $params[ 'sort' ] ) === TRUE )
        {
            $orderByClause = WebPageResultsController::formOrderByClause( $params[ 'sort' ] );
        }

        $txn = Yii::app()->db->beginTransaction();
                
        $sql = 'SELECT SQL_CALC_FOUND_ROWS t.*
        FROM `_ticket` t ' . 
        'WHERE t.ticket_type_id = ' . 
        $params[ 'ticket_type_id' ] . ' AND t.ticket_state_id = ' . $params[ 'ticket_state_id' ] . ' ' .
        $orderByClause . ' LIMIT ' . $firstLimitParam . ', ' . $params[ 'num_per_page_max' ];
        
        $result = Yii::app()->db->createCommand( $sql )->queryAll();
        $txn->commit();

        $totalNumberOfHits = WebPageResultsController::getFoundRows();

        $hits = array();

        if( $totalNumberOfHits > 0 )
        {
            foreach( $result as $i => $row )
            {
                $hits[ $i ] = $row;
                $hits[ $i ][ 'ticket' ] = Ticket::model()->findByPk( $row[ 'ticket_id' ] );
            }
            
        }

        return array( 'search_results' => $hits, 'total_number_of_hits' => $totalNumberOfHits );

    }

    


    public function generateHtmlSearchResultList( 
        Array $params, 
        Array $searchResults
    )
    {

        $returnHtml = '';
                
        // Get the template for this type of view
        $thisItemHtmlLocation = 
            Yii::app()->params[ 'legacyWebRoot' ] . 
            'templates/ticket/ticket_row.html';

	$templateHtml = $this->renderPartial( '_ticket_row', array(), true );

        foreach( $searchResults[ 'search_results' ] as $i => $searchResult )
        {

            $thisTicketHtml = $templateHtml;
            
            $replacements[ '%TICKET_ID%' ] = $searchResult[ 'ticket' ]->ticket_id;
            $replacements[ '%TICKET_TYPE_DESC%' ] = $searchResult[ 'ticket' ]->ticketType->ticket_type_desc;
            $replacements[ '%TICKET_PRIORITY_ID%' ] = $searchResult[ 'ticket' ]->ticket_priority_id;
            $replacements[ '%TICKET_NAME%' ] = $searchResult[ 'ticket' ]->ticket_name;
            $replacements[ '%PERCENTAGE_COMPLETE%' ] = $searchResult[ 'ticket' ]->percentage_complete;
            $replacements[ '%OPTIONS%' ] = TicketController::showTicketOptions( $searchResult[ 'ticket' ] );
            
            foreach( $replacements as $search => $replace )
            {
                $thisTicketHtml = str_replace( $search, $replace, $thisTicketHtml );
            }

            $returnHtml .= $thisTicketHtml;
            
        }
        
        return array( 'total_number_of_hits' => $searchResults[ 'total_number_of_hits' ],
                        'html_search_results' => $returnHtml );

    }
    

    
	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model=new Ticket('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['Ticket']))
			$model->attributes=$_GET['Ticket'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Ticket the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
		$model=Ticket::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param Ticket $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='ticket-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
