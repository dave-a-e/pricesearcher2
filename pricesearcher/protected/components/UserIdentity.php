<?php

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends CUserIdentity
{
    private $_id;
    private $_user_type_id;
    

	/**
	 * Authenticates a user.
	 * The example implementation makes sure if the username and password
	 * are both 'demo'.
	 * In practical applications, this should be changed to authenticate
	 * against some persistent user identity storage (e.g. database).
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate()
	{

        $user = User::model()->findByAttributes( 
            array(
                'user_state_id' => UserState::STATE_ACTIVE,
                'username' => $this->username
            )
        );
        
        if( $user === null )
        {
            $this->errorCode = self::ERROR_USERNAME_INVALID;
        }
        else
        {

            // Get the encrypted most recent password this user has set that is in active state
            $sql = 'SELECT md5_password FROM `_user_password` up 
                INNER JOIN `_password` p ON up.password_id = p.password_id
                WHERE up.user_id = ' . $user->user_id . ' AND p.password_state_id = ' . PasswordState::STATE_ACTIVE . ' LIMIT 1';

            $r = Yii::app()->db->createCommand( $sql )->queryAll();
            
            if( empty( $r ) === TRUE )
            {
                $this->errorCode = self::ERROR_PASSWORD_INVALID;
            }
            
            $md5_password = $r[ 0 ][ 'md5_password' ];
            
            if( $md5_password !== $user->encrypt( $_POST[ 'LoginForm' ][ 'password' ] ) )
            {
                $this->errorCode = self::ERROR_PASSWORD_INVALID;
            }
            else
            {
                $user->date_last_login = new CDbExpression('UTC_TIMESTAMP()');
                $user->save();
                
                $this->_id = $user->user_id;
                $this->errorCode = self::ERROR_NONE;
                
            }
            
        }
        
		return !$this->errorCode;
        
	}



    public function getId()
    {
        return $this->_id;
    }

}