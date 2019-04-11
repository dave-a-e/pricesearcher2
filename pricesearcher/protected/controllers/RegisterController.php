<?php

class RegisterController extends WebPageController
{

    /**
    * AT THE MOMENT, THIS IS JUST COPIED FROM EVERYGAMEGOING
    * I HAVE FUDGED IT JUST TO ALLOW THE SITE NOT TO REPORT AN ERROR
    * 
    */
	public function actionIndex()
	{

        print 'Registration Is Disabled Under The Site Has Been Finished!';
        die;
        
        $landingHtml = $this->render('index',array(),true );

        // Do the searching and replacing        
        $replacements = array(
            '%BASE_URL%' => ConfigVariables::BASE_URL,
            '%UP_PATH%' => ConfigVariables::LEGACY_BASE_URL,
            '%HOME_PAGE%' => ConfigVariables::HOME_PAGE,
        );

        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;

    }
    
    

    public function actionConfirm()
    {

        $emailAddress = trim( strip_tags( $_POST[ 'email_address' ] ) );

        $report = User::validateNewSignUpEmailAddress( $emailAddress );

        if( $report[ 'report' ] == 'Failure' )
        {
            $this->renderFailure( $report[ 'failure_reason' ] );
        }

        $isEmailAddressActive = User::isEmailAddressActive( $emailAddress );

        if( $isEmailAddressActive == TRUE )
        {
            // Render the failure
            $htmlFailureReport = '<p>Sorry, the e-mail address ' . $emailAddress . ' has already been registered.</p>';
            $htmlFailureReport .= '<p>Have you <a href = "' . ConfigVariables::BASE_URL . 'recoverPassword/">forgotten your password</a> for this address?</p>';

            $this->renderFailure( $htmlFailureReport );
        }
        
        $reportConfirmedRegister = User::registerNewUser( trim( $_POST[ 'username' ] ), $emailAddress, $_POST[ 'password' ] );
        
        if( $reportConfirmedRegister[ 'report' ] == 'Failure' )
        {
            $this->renderFailure( $report[ 'failure_reason' ] );
        }
        
        // $reportConfirmedRegister element user contains the new user object
        $newUser = $reportConfirmedRegister[ 'user' ];
        $newUser->addUserFollowUserID( 1 );

        $user_id = $newUser->user_id;
        $newUser = User::model()->findByPk( $user_id );
                
        // Login a user with the provided username and password.
        $identity = new UserIdentity( $newUser->username, $newUser->password->password );

        if( $identity->authenticate() )
        {
            Yii::app()->user->login( $identity );
            // Redirect to the account page for now
            $this->redirect( array( 'myAccount/index/' ) ); 
            
        }
        else
        {
            $this->renderFailure( $identity->errorMessage );
        }
        
    }


    public function renderFailure( $failureReason )
    {

        $landingHtml = $this->render('failure',array(),true );

        // Do the searching and replacing        
        $replacements = array(
            '%BASE_URL%' => ConfigVariables::BASE_URL,
            '%UP_PATH%' => ConfigVariables::LEGACY_BASE_URL,
            '%HOME_PAGE%' => ConfigVariables::HOME_PAGE,
            '%WHAT_WENT_WRONG%' => $failureReason,
        );

        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;
        
    }    


    /**
    * accessRules()
    * The register controller-view is only available to a person who is
    * logged out of the site. If a logged in user attempts to go back to the
    * register page, he will receive an error.
    * 
    */

    public function accessRules()
    {
        return array(
            array( 'allow',
                'actions' => array( 'index', 'confirm' ),
                'users' => array( '?' ) 
            ),
                
            array('deny',
                'actions' => array( 'index', 'confirm' ),
            ),
        );
    }



    /**
    * filters()
    * Overrides the filters in the parent controller
    */

    public function filters()
    {
        return array(
            'accessControl', // Restrict access to this controller
        );
    }
}