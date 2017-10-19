<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\Json;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class LoginForm extends Model
{
    public $email;
	public $password;
	public $company;
	public $commonError;
	public $rememberMe = true;
	public $token;

    private $_user = false;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
			// email and password are both required
			['email', 'email'],
            [['email', 'password', 'company'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
			['password', 'validatePassword']
        ];
	}

	/**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {

		if ($this->hasErrors()) {
			Yii::info("hasErrors = ".$this->hasErrors());
			return false;
		}

		$curl = curl_init();
		curl_setopt_array($curl, array(
				CURLOPT_URL => "https://api.shamandev.com/auth/login",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => "{\"email\": \"".$this->email."\",\"password\": \"".$this->password."\",\"curlname\": \"".$this->company."\"}",
				CURLOPT_HTTPHEADER => array("cache-control: no-cache", "content-type: application/json")
		));
		$response = curl_exec($curl);
		$err = curl_error($curl);


		if ($err) {
			Yii::info("cURL Error #:".$err);
			$this->commonError = "Oops! Something wrong...";
			$this->addError("commonError", "commonError");
		} else {
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$decode = Json::decode($response);
			if ($http_code == 500) {
				$this->commonError = "Oops! Something wrong...";
				$this->addError("commonError", "commonError");
			} else if ($http_code != 200) {
				foreach ($decode["message"] as $key => $value) {
					if ($key == "email") {
						$this->addError("password", $value);
					}
					$this->addError($key, $value);
				}
			} else {
				$this->token = $decode["token"];
				Yii::info("Form token #:".$this->token);
			}
		}

		curl_close($curl);
    }

    /**
     * Logs in a user using the provided email and password.
     * @return bool whether the user is logged in successfully
     */
    public function login()
    {
		Yii::info("login()");
        if ($this->validate() && !$this->hasErrors()) {
			Yii::info("hasErrors = ".!$this->hasErrors());
			$session = Yii::$app->session;
			$session->set('token', $this->token);
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600*24*30 : 0);
        }
        return false;
    }

    /**
     * Finds user by [[email]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findIdentityByAccessToken($this->token);
        }

        return $this->_user;
    }
}
