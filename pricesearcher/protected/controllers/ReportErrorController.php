<?php

class ReportErrorController extends WebPageController
{

    public function actionIndex()
    {

        $this->pageTitle = 'Report Error - ' . Yii::app()->name;

        $landingHtml = $this->render( 'index', array(), true );

        // Do the searching and replacing        
        $replacements = array(
            '%LOGGED_IN_INFO%' => ReportErrorController::showLoggedInInfo(),
        );

        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;
        
    }



    public static function showLoggedInInfo()
    {
        $html = '';
        
        /*
        if( Yii::app()->user->getId() > 0 )
        {
            $html .= '<p>' .
                'As you are logged into the site, you will be able to track the progress of any errors you report.' .
                '</p>';
        }
        else
        {
            $html .= '<p>' .
                'As you are not logged into the site, this report will be filed anonymously.' .
                '</p>';
        }
        */

        return $html;
    }



    public function actionReport()
    {

        // Create the new problemSite
        $problemSite = new ProblemSite();
        $problemSite->problem_state_id = ProblemState::STATE_ACTIVE;
        $problemSite->problem_description = strip_tags( $_POST[ 'problem_description' ] );
        $problemSite->ip_address = Yii::app()->request->userHostAddress;
        $problemSite->date_created = new CDbExpression('UTC_TIMESTAMP()');
        $problemSite->date_modified = new CDbExpression('UTC_TIMESTAMP()');
        $problemSite->save();
        
        // We will only store the first ten urls. If the user has entered lots of URLs
        // then it's probably going to be spam.
        
        foreach( $_POST as $key => $value )
        {
            if( mb_substr( $key, 0, 4 ) == 'url-' )
            {
                $problemSiteUrl = new ProblemSiteUrl();
                $problemSiteUrl->problem_site_id = $problemSite->problem_site_id;
                $problemSiteUrl->problem_url = $value;
                $problemSiteUrl->save();
            }
        }

        // Finally, if this is a logged in user then we record his user_id too
        if( Yii::app()->user->getId() > 0 )
        {
            $problemSiteUser = new ProblemSiteUser();
            $problemSiteUser->problem_site_id = $problemSite->problem_site_id;
            $problemSiteUser->user_id = Yii::app()->user->getId();
            $problemSiteUser->save();
        }

        // It needs to send *me* an e-mail
        /*        
        $adminUser = User::model()->findByPk( 1 );
        
        if( $adminUser->email_address_id > 0 )
        {
            $adminUserEmail = EmailAddress::model()->findByPk( $adminUser->email_address_id );
            
            $emailMessage = new EmailMessage();
            $emailMessage->to = strip_tags( 
                $adminUserEmail->email_address );
            $emailMessage->email_message_state_id = 
                EmailMessageState::STATE_NOT_SENT;
            $emailMessage->email_message_type_id = 
                EmailMessageType::TYPE_ERROR_REPORT;
            $emailMessage->to_email_address_id = 
                $adminUserEmail->email_address_id;
            $emailMessage->subject = 'Error Reported On ' . ConfigVariables::SITE_NAME;
            $emailMessage->message = 
                $problemSite->problem_description;
            $emailMessage->date_modified = 
                new CDbExpression('UTC_TIMESTAMP()');
            $emailMessage->save();
            $emailMessage->sendIt();
        }
        */
        
        $this->pageTitle = 'Report Error - ' . Yii::app()->name;
        
        $landingHtml = $this->render('report',array(),true );

        // Do the searching and replacing        
        $replacements = array(
            '%PROBLEM_DESCRIPTION%' => $problemSite->problem_description,
        );

        foreach( $replacements as $search => $replace )
        {
            $landingHtml = str_replace( $search, $replace, $landingHtml );
        }

        print $landingHtml;
        die;
        
    }
        
}
