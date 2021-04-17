<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/json");

    //models
    require_once "../../config/Database.php";
    require_once "../../model/User.php";
    require_once "../../model/Validator.php";

    $method = $_SERVER['REQUEST_METHOD'];
    if($method === "POST") {
        $db = new Database();
        $user = new User($db->get_connection());
        $validator = new Validator($db->get_connection());

        //get data from input
        $data = json_decode(file_get_contents("php://input"));

        //validate input
        if($validator->validate_email_verification($data)) {
            //load data to user object
            $user->email = $data->email;
            $user->code = $data->code;

            //verify email
            $response = $user->verify_email();
            if($response['status'] === true) {
                http_response_code(200);
                echo json_encode(array("status" => true, "message" => $response['message']));
            } else {
                http_response_code(500);
                die(json_encode(array("status" => false, "message" => $response['message'])));
            }
        } else {
            http_response_code(400);//Bad request
            die(json_encode(array("status" => false, "errors" => $validator->errors)));
        }
    } else {
        http_response_code(400);//Bad request
        die(json_encode(array("status" => false, "message" => "Invalid request method.")));
    }