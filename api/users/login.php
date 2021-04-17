<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/json");

    //vendor
    require_once '../../vendor/php-jwt/src/JWT.php';

    //models
    require_once "../../config/Database.php";
    require_once "../../config/Config.php";
    require_once "../../model/User.php";
    require_once "../../model/Validator.php";

    use \Firebase\JWT\JWT;

    $method = $_SERVER['REQUEST_METHOD'];
    if($method === "POST") {

        $db = new Database();
        $user = new User($db->get_connection());
        $validator = new Validator($db->get_connection());
        
        //get data from input
        $data = json_decode(file_get_contents("php://input"));

        if($validator->validate_login_data($data)) {
            
            //load data
            $user->email = $data->email;
            //$user->password = $data->password;

            //get user with email
            $response = $user->get_user_by_email();

            if($response['status'] === true) {
                //check password match
                if(password_verify($data->password, $user->password)) {
                    //check if email if valid
                    if($user->email_valid == true) {
                        $iat = time();
                        $expire = $iat + (6 * 60 * 60);
                        $payload = array(
                            "iss" => Config::BASE_URL,
                            "iat" => $iat,
                            "nbf" => $iat,
                            "exp" => $expire,
                            "data" => array(
                                "email" => $user->email,
                                "id" => $user->id
                            )
                        );
                        $jwt = JWT::encode($payload, Config::JWT_KEY);
                        $user_data = array(
                            "id" => (int)$user->id,
                            "firstname" => $user->firstname,
                            "lastname" => $user->lastname,
                            "email" => $user->email,
                            "email_valid" => boolval($user->email_valid),
                            "subscribed" => (int)$user->subscribed,
                            "decrypted" => (int)$user->decrypted
                        );
    
                        http_response_code(200);
                        echo json_encode(array("status" => true, "user" => $user_data, "jwt" => $jwt));
                    } else {
                        http_response_code(403);
                        die(json_encode(array("status" => false, "errors" => "Email is not yet verified.")));
                    }
                } else {
                    http_response_code(401);//Unathourized request
                    die(json_encode(array("status" => false, "errors" => "Invalid credentials.")));
                }
            } else {
                http_response_code(401);//Unathourized request
                die(json_encode(array("status" => false, "errors" => "Invalid credentials.")));
            }

        } else {
            http_response_code(400);//Bad request
            die(json_encode(array("status" => false, "errors" => $validator->errors)));
        }

    } else {
        http_response_code(400);//Bad request
        die(json_encode(array("status" => false, "message" => "Invalid request method.")));
    }