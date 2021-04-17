<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/json");
    header("Access-Control-Allow-Headers: Access-Control-Allow-Origin,Access-Control-Allow-Methods,Content-Type,Authorization");

    //vendor
    require_once '../../vendor/php-jwt/src/JWT.php';
    require_once '../../vendor/php-jwt/src/BeforeValidException.php';
    require_once '../../vendor/php-jwt/src/ExpiredException.php';
    require_once '../../vendor/php-jwt/src/SignatureInvalidException.php';

    //models
    require_once "../../config/Database.php";
    require_once "../../config/Config.php";
    require_once "../../model/User.php";
    require_once "../../model/Validator.php";

    use \Firebase\JWT\JWT;

    $method = $_SERVER['REQUEST_METHOD'];
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? "";
    if($method === "POST") {
        $bearer = explode(" ", $auth);
        $jwt = $bearer[1] ?? false;
        if($jwt) {

            $db = new Database();
            $user = new User($db->get_connection());
            $validator = new Validator($db->get_connection());
    
            //get data from input
            $data = json_decode(file_get_contents("php://input"));
    
            if($validator->validate_subscribe_data($data)) {
                //get user
                $user->email = $data->email;
                $response = $user->get_user_by_email();
                if($response['status'] === true) {
                    if($user->email_valid == true) {
                        try {
                            $decoded = JWT::decode($jwt, Config::JWT_KEY, array('HS256'));
                            //match data from jwt with data from request body
                            if(((int)$decoded->data->id === (int)$user->id) && ($decoded->data->email === $user->email)) {
                                $response = $user->add_to_subscribe((int)$data->sub_volume);
                                if($response['status'] === true) {
                                    //refresh JWT
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

                                    http_response_code(200);
                                    echo json_encode(array("status" => true, "message" => $response['message'], "jwt" => $jwt));
                                } else {
                                    http_response_code(500);
                                    die(json_encode(array("status" => false, "errors" => $response['message'])));
                                }
                            } else {
                                http_response_code(403);//forbidden
                                die(json_encode(array("status" => false, "errors" => "Data do not match")));
                            }
                        } catch (Exception $e) {
                            http_response_code(403);//forbidden
                            die(json_encode(array("status" => false, "errors" => "invalid token")));
                        }
                    } else {
                        http_response_code(403);//forbidden
                        die(json_encode(array("status" => false, "errors" => "Email is not yet verified.")));
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
            die(json_encode(array("status" => false, "errors" => "No token specified")));
        }
    } else {
        http_response_code(400);//Bad request
        die(json_encode(array("status" => false, "message" => "Invalid request method.")));
    }