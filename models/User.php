<?php

namespace app\models;

use Yii;
use yii\helpers\Json;

class User extends \yii\base\Object implements \yii\web\IdentityInterface
{
	public $id;
	public $username;
    public $name;
    public $email;
    public $isActive;
    public $role_id;
    public $isFirstTime;
    public $onCreated;
    public $onPasswordChanged;
    public $isBlocked;
    public $sfid;
    public $onDeleted;
    public $avatarInfo;
    public $secretKey;
    public $phone;
    public $avatar_id;
    public $groups;
    public $role;
	public $intercomUserHash;
	public $authKey;

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
		$session = Yii::$app->session;
		return new static($session->get('user'));
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
		$session = Yii::$app->session;
		if ($session->get('user')) {
			return new static($session->get('user'));
		}
		if (!$token) {
			$token = $session->get('token');
		}
		Yii::info("TOKEN = ".$token);

		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://api.shamandev.com/user/current",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
			"authorization: Bearer ".$token,
			"cache-control: no-cache"
		  ),
		));
		$response = curl_exec($curl);
		$err = curl_error($curl);
		$_user;
		if ($err) {
			Yii::info("findIdentityByAccessToken #:".$token."  ".$error);
		} else {
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$_user = Json::decode($response);
			Yii::info("findIdentityByAccessToken response :".$response);
		}
		curl_close($curl);
		$_user["authKey"] = $token;
		$_user["username"] = $_user["email"];
		$_user["secretKey"] = base64_decode($_user["secretKey"]);
		$session->set('user', $_user);
		return new static($_user);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }
}
