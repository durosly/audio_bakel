<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/json");
    //vendor
    require_once '../../vendor/PHPMailer/src/PHPMailer.php';
    require_once '../../vendor/PHPMailer/src/SMTP.php';
    require_once '../../vendor/PHPMailer/src/Exception.php';
    //models
    require_once "../../config/Database.php";
    require_once "../../config/Config.php";
    require_once "../../model/User.php";
    require_once "../../model/Validator.php";

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;


    $method = $_SERVER['REQUEST_METHOD'];
    if($method === "POST") {
        $db = new Database();
        $user = new User($db->get_connection());
        $validator = new Validator($db->get_connection());
        
        //get data from input
        $data = json_decode(file_get_contents("php://input"));

        //begin validation
        if($validator->validate_profile_data($data)) {
            //load user data
            $user->email = $data->email;
            $response = $user->get_user_by_email();

            if($response['status'] === true) {
                //setup verification code
                $response = $user->email_verification_code_setup();
                if($response['status'] === true) {
                    //send email
                    // Instantiation and passing `true` enables exceptions
                    $mail = new PHPMailer(true);
                    //Server settings
                    $mail->SMTPDebug = SMTP::DEBUG_OFF;                      // Enable verbose debug output
                    $mail->isSMTP();                                            // Send using SMTP
                    $mail->Host       = Config::MAILER['host'];                    // Set the SMTP server to send through
                    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
                    $mail->Username   = Config::MAILER['username'];                     // SMTP username
                    $mail->Password   = Config::MAILER['password'];                               // SMTP password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
                    $mail->Port       = 587;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

                    $response = $user->send_email_verification_code($mail);
                    if($response['status'] === true) {
                        http_response_code(200);
                        echo json_encode(array("status" => true, "message" => "Check inbox for new verification code"));
                    } else {
                        http_response_code(500);//internal server error
                        die(json_encode(array("status" => false, "message" => $response['message'])));
                    }
                } else {
                    http_response_code(500);//internal server error
                    die(json_encode(array("status" => false, "message" => $response['message'])));
                }
            } else {
                http_response_code(401);//Unathourized request
                die(json_encode(array("status" => false, "message" => "Invalid credentials.")));
            }
        } else {
            http_response_code(400);//Bad request
            die(json_encode(array("status" => false, "message" => "Invalid data", "errors" => $validator->errors)));
        }
    } else {
        http_response_code(400);//Bad request
        die(json_encode(array("status" => false, "message" => "Invalid request method.")));
    }