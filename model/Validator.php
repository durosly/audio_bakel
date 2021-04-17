<?php

class Validator {
    private $conn;
    private $hasError = false;
    public $errors = [];
    public $status;
    public $serialized_data;

    public function __construct($conn = null) {
        $this->conn = $conn;
    }

    //test and sanitize text values [firstname, lastname]
    private function test_name(String $name) {
        $name = trim(htmlspecialchars(strip_tags($name)));

        $length = strlen($name);

        if($length < 1) {
            return ["status" => false, "message" => "is empty"];
        } else if($length > 99) {
            return ["status" => false, "message" => "is too long."];
        } else if(!preg_match("/^[a-zA-Z]+$/", $name)) {
            return ["status" => false, "message" => "should contain only alphabets."];
        }

        return ["status" => true, "data" => $name];
    }

    //test and sanitize email address [email]
    private function test_email(String $email) {
        $email = trim(htmlspecialchars(strip_tags($email)));
        $length = strlen($email);

        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ["status" => false, "message" => "is invalid or not supported."];
        } else if($length > 99) {
            return ["status" => false, "message" => "is too long. Please, use a different email address or contact admin."];
        }

        return ["status" => true, "data" => $email];
    }

    //check email existence
    private function test_email_existence(String $email) {
        try {
            $sql = "SELECT COUNT(*) AS num FROM users WHERE email = :email;";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            if($stmt->rowCount() > 0) {
                $data = $stmt->fetch(PDO::FETCH_OBJ);
                if($data->num > 0) {
                    return ["status" => false, "message" => "E-mail address is taken."];
                }
            } else {
                return ["status" => false, "message" => "Something went wrong."];
            }

            return ["status" => true];
        } catch (PDOException $e) {
            return ["status" => false, "message" => "An error occured. Please, try again."];
        }
    }

    //test and sanitize password
    private function test_password(String $password) {
        $length = strlen($password);

        if($length < 8) {
            return ["status" => false, "message" => "is too short. Minimum of 8 characters."];
        } else if(!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).*$/", $password)) {
            return ["status" => false, "message" => "must contain atleast one uppercase, lowercase letter and a digit."];
        }

        return ["status"=> true, "data" => $password];

    }

    //test code
    private function test_code(int $code) {
        $length = strlen($code);

        if($length < 6 || $length > 6) {
            return ["status" => false, "message" => "code is invalid."];
        } else if(!preg_match("/^\d+$/", $code)) {
            return ["status" => false, "message" => "code is invalid."];
        }

        return ["status"=> true, "data" => $code];
    }

    public function validate_user_signup($data) {
        if(is_object($data)) {
            //check for firstname property
            if(!property_exists($data, "firstname")) {
                $this->errors['firstname'] = "firstname is required";
                $this->hasError = true;
                return !$this->hasError;
            }

            //check for lastname property
            if(!property_exists($data, "lastname")) {
                $this->errors['lastname'] = "lastname is required";
                $this->hasError = true;
                return !$this->hasError;
            }

            //check for email property
            if(!property_exists($data, "email")) {
                $this->errors['email'] = "email is required";
                $this->hasError = true;
                return !$this->hasError;
            }

            //check for password property
            if(!property_exists($data, "password")) {
                $this->errors['password'] = "password is required";
                $this->hasError = true;
                return !$this->hasError;
            }

            //check for re_password property
            if(!property_exists($data, "re_password")) {
                $this->errors['re_password'] = "repeated password is required";
                $this->hasError = true;
                return !$this->hasError;
            }

            //test firstname property value
            $test = $this->test_name($data->firstname);
            if($test['status'] === false) {
                $this->errors['firstname'] = "firstname {$test['message']}";
                $this->hasError = true;
                return !$this->hasError;
            }
            $data->firstname = $test['data'];//replace with serialized data

            //test firstname property value
            $test = $this->test_name($data->lastname);
            if($test['status'] === false) {
                $this->errors['lastname'] = "lastname {$test['message']}";
                $this->hasError = true;
                return !$this->hasError;
            }
            $data->lastname = $test['data'];//replace with serialized data

            //test email property value
            $test = $this->test_email($data->email);
            if($test['status'] === false) {
                $this->errors['email'] = "email {$test['message']}";
                $this->hasError = true;
                return !$this->hasError;
            }
            $data->email = $test['data'];//replace with serialized data

            //test email property value existence
            $test = $this->test_email_existence($data->email);
            if($test['status'] === false) {
                $this->errors['email'] = $test['message'];
                $this->hasError = true;
                return !$this->hasError;
            }

            //test password property value
            $test = $this->test_password($data->password);
            if($test['status'] === false) {
                $this->errors['password'] = "password {$test['message']}";
                $this->hasError = true;
                return !$this->hasError;
            } else if($data->password !== $data->re_password) {
                $this->errors['re_password'] = "passwords do not match.";
                $this->hasError = true;
                return !$this->hasError;
            }
            $data->password = password_hash($data->password, PASSWORD_DEFAULT);//Hash password

            return !$this->hasError;
        } else {
            $this->errors["data"] = "Data passed in is not an object.";
            $this->hasError = true;
            return !$this->hasError;
        }
    }

    public function validate_email_verification($data) {
        if(is_object($data)) {
            //check for email property
            if(!property_exists($data, "email")) {
                $this->errors['email'] = "email is required";
                $this->hasError = true;
                return !$this->hasError;
            }

            //check for email property
            if(!property_exists($data, "code")) {
                $this->errors['code'] = "code is required";
                $this->hasError = true;
                return !$this->hasError;
            }

            //test email property value
            $test = $this->test_email($data->email);
            if($test['status'] === false) {
                $this->errors['email'] = "email {$test['message']}";
                $this->hasError = true;
                return !$this->hasError;
            }
            $data->email = $test['data'];//replace with serialized data
 
            //test email property value existence
            $test = $this->test_email_existence($data->email);
            if($test['status'] !== false) {
                $this->errors['email'] = "User does not exist.";
                $this->hasError = true;
                return !$this->hasError;
            }

            //test code property value existence
            $test = $this->test_code($data->code);
            if($test['status'] === false) {
                $this->errors['code'] = $test['message'];
                $this->hasError = true;
                return !$this->hasError;
            }

            return !$this->hasError;
        } else {
            $this->errors["data"] = "Data passed in is not an object.";
            $this->hasError = true;
            return !$this->hasError;
        }
    }

    public function validate_login_data($data) {
        if(is_object($data)) {
            //check for email property
            if(!property_exists($data, "email")) {
                $this->errors['email'] = "email is required";
                $this->hasError = true;
                return !$this->hasError;
            }

            //check for email property
            if(!property_exists($data, "password")) {
                $this->errors['password'] = "password is required";
                $this->hasError = true;
                return !$this->hasError;
            }

            if(strlen($data->email) < 1) {
                $this->errors['email'] = "email cannot be empty";
                $this->hasError = true;
                return !$this->hasError;
            }

            if(strlen($data->password) < 1) {
                $this->errors['password'] = "password cannot be empty";
                $this->hasError = true;
                return !$this->hasError;
            }

            return !$this->hasError;
        } else {
            $this->errors["data"] = "Data passed in is not an object.";
            $this->hasError = true;
            return !$this->hasError;
        }
    }

    public function validate_subscribe_data($data) {
        if(is_object($data)) {
            //check for user id property
            if(!property_exists($data, "id")) {
                $this->errors['id'] = "user id is required";
                $this->hasError = true;
                return !$this->hasError;
            }

            //check for email property
            if(!property_exists($data, "email")) {
                $this->errors['email'] = "user email is required";
                $this->hasError = true;
                return !$this->hasError;
            }

            //check for transaction reference property
            if(!property_exists($data, "t_reference")) {
                $this->errors['t_reference'] = "transaction reference is required";
                $this->hasError = true;
                return !$this->hasError;
            }

            //check for amount property
            if(!property_exists($data, "amount")) {
                $this->errors['amount'] = "amount is required";
                $this->hasError = true;
                return !$this->hasError;
            }

            //check for subscription volume property
            if(!property_exists($data, "sub_volume")) {
                $this->errors['sub_volume'] = "subscription volume is required";
                $this->hasError = true;
                return !$this->hasError;
            }

            if(empty(trim($data->id))) {
                $this->errors['id'] = "user id cannot be empty";
                $this->hasError = true;
                return !$this->hasError;
            } else if(!is_int($data->id)) {
                $this->errors['id'] = "invalid user id format";
                $this->hasError = true;
                return !$this->hasError;
            }

            if(empty(trim($data->email))) {
                $this->errors['email'] = "email cannot be empty";
                $this->hasError = true;
                return !$this->hasError;
            } else if(!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
                $this->errors['email'] = "email is invalid";
                $this->hasError = true;
                return !$this->hasError;
            }

            if(empty(trim($data->t_reference))) {
                $this->errors['t_reference'] = "transaction reference cannot be empty";
                $this->hasError = true;
                return !$this->hasError;
            }

            if(empty(trim($data->amount))) {
                $this->errors['amount'] = "amount cannot be empty";
                $this->hasError = true;
                return !$this->hasError;
            } else if(!is_int($data->amount)) {
                $this->errors['amount'] = "amount must be an integer";
                $this->hasError = true;
                return !$this->hasError;
            } else if((int)$data->amount < 0) {
                $this->errors['amount'] = "amount cannot be negative";
                $this->hasError = true;
                return !$this->hasError;
            }

            if(empty(trim($data->sub_volume))) {
                $this->errors['sub_volume'] = "subscription volume cannot be empty";
                $this->hasError = true;
                return !$this->hasError;
            } else if(!is_int($data->sub_volume)) {
                $this->errors['sub_volume'] = "subscription volume must be an integer";
                $this->hasError = true;
                return !$this->hasError;
            }

            return !$this->hasError;
        } else {
            $this->errors["data"] = "Data passed in is not an object.";
            $this->hasError = true;
            return !$this->hasError;
        }
    }

    public function validate_transaction_reference(String $ref, String $key) {
        $curl = curl_init();
  
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/transaction/verify/{$ref}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer {$key}",
                "Cache-Control: no-cache",
            ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        
        if ($err) {
            return false;
        } else {
            return $response;
        }
    }

    public function validate_decrypt_data($data) {
        if(is_object($data)) {
            //check for user id property
            if(!property_exists($data, "id")) {
                $this->errors['id'] = "user id is required";
                $this->hasError = true;
                return !$this->hasError;
            } else if(empty(trim($data->id))) {
                $this->errors['id'] = "user id cannot be empty";
                $this->hasError = true;
                return !$this->hasError;
            } else if(!is_int($data->id)) {
                $this->errors['id'] = "invalid user id format";
                $this->hasError = true;
                return !$this->hasError;
            }

            //check for email property
            if(!property_exists($data, "email")) {
                $this->errors['email'] = "user email is required";
                $this->hasError = true;
                return !$this->hasError;
            } else if(empty(trim($data->email))) {
                $this->errors['email'] = "email cannot be empty";
                $this->hasError = true;
                return !$this->hasError;
            } else if(!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
                $this->errors['email'] = "email is invalid";
                $this->hasError = true;
                return !$this->hasError;
            }

            return !$this->hasError;
        } else {
            $this->errors["data"] = "Data passed in is not an object.";
            $this->hasError = true;
            return !$this->hasError;
        }
    }

    public function validate_profile_data($data) {
        if(is_object($data)) {
            //check for email property
            if(!property_exists($data, "email")) {
                $this->errors['email'] = "user email is required";
                $this->hasError = true;
                return !$this->hasError;
            } else if(empty(trim($data->email))) {
                $this->errors['email'] = "email cannot be empty";
                $this->hasError = true;
                return !$this->hasError;
            } else if(!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
                $this->errors['email'] = "email is invalid";
                $this->hasError = true;
                return !$this->hasError;
            }

            return !$this->hasError;
        } else {
            $this->errors["data"] = "Data passed in is not an object.";
            $this->hasError = true;
            return !$this->hasError;
        }
    }

    public function validate_password_reset_data($data) {
        if(is_object($data)) {
            //check for email property
            if(!property_exists($data, "email")) {
                $this->errors['email'] = "user email is required";
                $this->hasError = true;
                return !$this->hasError;
            } else if(empty(trim($data->email))) {
                $this->errors['email'] = "email cannot be empty";
                $this->hasError = true;
                return !$this->hasError;
            } else if(!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
                $this->errors['email'] = "email is invalid";
                $this->hasError = true;
                return !$this->hasError;
            }

            //check for user id property
            if(!property_exists($data, "id")) {
                $this->errors['id'] = "user id is required";
                $this->hasError = true;
                return !$this->hasError;
            } else if(empty(trim($data->id))) {
                $this->errors['id'] = "user id cannot be empty";
                $this->hasError = true;
                return !$this->hasError;
            } else if(!is_int($data->id)) {
                $this->errors['id'] = "invalid user id format";
                $this->hasError = true;
                return !$this->hasError;
            }

            //check for password property
            if(!property_exists($data, "password")) {
                $this->errors['password'] = "password is required";
                $this->hasError = true;
                return !$this->hasError;
            } else if(empty(trim($data->password))) {
                $this->errors['password'] = "password cannot be empty";
                $this->hasError = true;
                return !$this->hasError;
            } else if(!property_exists($data, "new_password")) {
                $this->errors['new_password'] = "new password is required";
                $this->hasError = true;
                return !$this->hasError;
            } else if(empty(trim($data->new_password))) {
                $this->errors['new_password'] = "new password cannot be empty";
                $this->hasError = true;
                return !$this->hasError;
            } else if(!property_exists($data, "re_new_password")) {
                $this->errors['re_new_password'] = "Repeating new password required";
                $this->hasError = true;
                return !$this->hasError;
            } else if(empty(trim($data->re_new_password))) {
                $this->errors['re_new_password'] = "Repeated new password cannot be empty";
                $this->hasError = true;
                return !$this->hasError;
            } else {
                $test = $this->test_password($data->new_password);
                if($test['status'] === false) {
                    $this->errors['new_password'] = "password {$test['message']}";
                    $this->hasError = true;
                    return !$this->hasError;
                } else if($data->new_password !== $data->re_new_password) {
                    $this->errors['re_new_password'] = "passwords do not match.";
                    $this->hasError = true;
                    return !$this->hasError;
                }
            }

            return !$this->hasError;
        } else {
            $this->errors["data"] = "Data passed in is not an object.";
            $this->hasError = true;
            return !$this->hasError;
        }
    }
}