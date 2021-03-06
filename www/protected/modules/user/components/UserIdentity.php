<?php

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends CUserIdentity
{
	private $_id;
	CONST ERROR_EMAIL_INVALID=3;
	CONST ERROR_STATUS_NOTACTIVE=4;
	CONST ERROR_STATUS_BAN=5;
  CONST ERROR_STATUS_BLOCKED=6;
	/**
	 * Authenticates a user.
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate()
	{
		if (strpos($this->username,"@")) {
			$user=User::model()->notsafe()->findByAttributes(array('email'=>$this->username));
		} else {
			$user=User::model()->notsafe()->findByAttributes(array('username'=>$this->username));
		}
		if($user===null)
			if (strpos($this->username,"@")) {
				$this->errorCode=self::ERROR_EMAIL_INVALID;
			} else {
				$this->errorCode=self::ERROR_USERNAME_INVALID;
			}
		else if(Yii::app()->getModule('user')->encrypting($this->password)!==$user->password)
			$this->errorCode=self::ERROR_PASSWORD_INVALID;
		else if($user->status==0&&Yii::app()->getModule('user')->loginNotActiv==false)
			$this->errorCode=self::ERROR_STATUS_NOTACTIVE;
		else if($user->status==-1)
			$this->errorCode=self::ERROR_STATUS_BAN;
		else {
			$this->_id=$user->id;
			$this->username=$user->username;
      
      // assign roles
      $auth=Yii::app()->authManager;
      if(!$auth->isAssigned($user->role,$this->_id))
      {
        if($auth->assign($user->role,$this->_id))
        {
            Yii::app()->authManager->save();
        }
      }
      
      MGHelper::createSharedSecretAndSession($this->_id, $user->username, true);
      
			$this->errorCode=self::ERROR_NONE;
		}
		return !$this->errorCode;
	}
    
  /**
  * @return integer the ID of the user record
  */
	public function getId()
	{
		return $this->_id;
	}
}