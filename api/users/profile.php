<?php 
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
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
    if($method === "GET") {
        $bearer = explode(" ", $auth);
        $jwt = $bearer[1] ?? false;
        if($jwt) {
            $db = new Database();
            $user = new User($db->get_connection());
            $validator = new Validator($db->get_connection());

            //get data from input
            $data = json_decode(file_get_contents("php://input"));

            if($validator->validate_profile_data($data)) {
                $id = (int)htmlspecialchars(strip_tags(trim($_GET['id'])));
                $user->email = $data->email;
                $response = $user->get_user_by_email();

                if($response['status'] === true) {
                    if($user->id === $id) {
                        try {
                            $decoded = JWT::decode($jwt, Config::JWT_KEY, array('HS256'));
                            //match data from jwt with data from request body
                            if(((int)$decoded->data->id === (int)$user->id) && ($decoded->data->email === $user->email)) {
                                
                                $profile = array(
                                    "id" => $user->id,
                                    "firstname" => $user->firstname,
                                    "lastname" => $user->lastname,
                                    "email" => $user->email,
                                    "subscribed" => $user->subscribed,
                                    "decrypted" => $user->decrypted,
                                    "email_valid" => boolval($user->email_valid)
                                );
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
                                        "id" => (int)$user->id
                                    )
                                );
                                $jwt = JWT::encode($payload, Config::JWT_KEY);

                                http_response_code(200);
                                echo json_encode(array("status" => true, "message" => "Profile loaded successfully", "jwt" => $jwt, "profile" => $profile));
                                
                            } else {
                                http_response_code(403);//forbidden
                                die(json_encode(array("status" => false, "errors" => "Data do not match")));
                            }
                        } catch(Exception $e) {
                            http_response_code(500);//internal server error
                            die(json_encode(array("status" => false, "errors" => "Something went wrong.")));
                        }
                    } else {
                        http_response_code(403);//forbidden
                        die(json_encode(array("status" => false, "errors" => "Data do not match")));
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