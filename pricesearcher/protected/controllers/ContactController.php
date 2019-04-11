<?php

class ContactController extends WebPageController
{
	public function actionIndex()
	{
	    
	    $landingHtml = $this->render('index',array(),true );
	    
	    // Now we apply lots of changes to the html before we display it.
	    
	    // Do the searching and replacing        
	    $replacements = array(
            '%EMAIL_ADDRESS_INFO_FOR_VISITORS%' => ContactController::giveEmailAddressInfoForVisitors(),
	    );

	    foreach( $replacements as $search => $replace )
	    {
	        $landingHtml = str_replace( $search, $replace, $landingHtml );
	    }

	    print $landingHtml;
	    die;
	}
    
    

    public function actionSend()
    {
            $landingHtml = $this->render('send',array(),true );
            
            // Now we apply lots of changes to the html before we display it.
            $report = ContactController::createContactMessageAfterSantizing();
            
            if( $report === FALSE )
            {
                $your_contact_message = '<span style = "color: red;"><b>Your message failed to save! This can occur if you attempt ' .
                    'to send exactly the same message more than once. Please be patient until we read and action it.</b></span>';
            }
            else
            {
                $your_contact_message = $_POST[ 'contact_message' ];
            }
            // Do the searching and replacing        
            $replacements = array(
                '%YOUR_CONTACT_MESSAGE%' => $your_contact_message,
            );
    
            foreach( $replacements as $search => $replace )
            {
                $landingHtml = str_replace( $search, $replace, $landingHtml );
            }
    
            print $landingHtml;
            die;
    }



    public static function createContactMessageAfterSantizing()
    {

        $newContact = ContactMessage::model()->findByAttributes( 
            array( 'contact_message_state_id' => ContactMessageState::STATE_UNREAD,
            'contact_email_address' => trim( $_POST[ 'contact_email_address' ] ),
            'contact_message' => $_POST[ 'contact_message' ] ) );
            
        if( !( $newContact instanceOf ContactMessage ) )
        {
            $newContact = new ContactMessage();
            $newContact->contact_message_state_id = ContactMessageState::STATE_UNREAD;
            $newContact->contact_email_address = trim( $_POST[ 'contact_email_address' ] );
            $newContact->contact_message = strip_tags( trim( str_replace( 'DELETE ', '', $_POST[ 'contact_message' ] ) ) );
            $newContact->contact_message = strip_tags( trim( str_replace( 'DROP ', '', $newContact->contact_message ) ) );
            $newContact->contact_user_id = 0;
            
            if( Yii::app()->user->getId() > 0 )
            {
                $newContact->contact_user_id = Yii::app()->user->getId();
            }
            
            $newContact->date_created = new CDbExpression('UTC_TIMESTAMP()');
            return $newContact->save();
        }
        
        return FALSE;
        
    }    



    public function giveEmailAddressInfoForVisitors()
    {
        $html = '';

        /*
        if( Yii::app()->user->getId() > 0 )
        {
            $html .= 'As you are logged into the site, you will be able to monitor ' .
                'when your message is read through your account.';
        }
        else
        {
            $html .= 'If you want a reply, then make sure to enter a valid e-mail address ' .
                "(Don't worry, we won't divulge it to any third parties!).";
        }
        */

        return $html;
    }
}
