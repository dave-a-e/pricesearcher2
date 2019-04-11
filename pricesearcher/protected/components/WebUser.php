<?php
 
// this file must be stored in:
// protected/components/WebUser.php
 
class WebUser extends CWebUser
{

    // Store model to not repeat query.
    private $_model;

    function getUserTypeId()
    {
        if( Yii::app()->user->id > 0 )
        {
            $user = $this->loadUser( Yii::app()->user->id );
            return $user->user_type_id;
        }
    }

    

    function getUsername()
    {
        if( Yii::app()->user->id > 0 )
        {
            $user = $this->loadUser( Yii::app()->user->id );
            return $user->username;
        }
    }



    // Load user model.
    protected function loadUser($id=null)
    {
        if($this->_model===null)
        {
            if( $id !== null )
            {
                $this->_model=User::model()->findByPk($id);
            }
        }
        return $this->_model;
    }
    
}

?>