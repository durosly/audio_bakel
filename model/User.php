<?php

class User {
    //connection properties
    private $table = "users";
    private $conn = null;

    //Model properties
    public $id;
    public $firstname;
    public $lastname;
    public $email;
    public $email_valid;
    public $password;
    public $subscribed;
    public $decrypted;
    public $code;
    public $trial;

    //actions

    //set connection on instantiation
    public function __construct($conn) {
        $this->conn = $conn;
    }

    //get all users
    public function get_all_users() {
        try {
            $sql = "SELECT * FROM {$this->table};";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            return false; //improve error handling
        }
    }

    //sign up user
    public function signup() {
        try {
            $sql = "INSERT INTO users (firstname, lastname, email, password) VALUES (:firstname, :lastname, :email, :password);";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":firstname", $this->firstname);
            $stmt->bindParam(":lastname", $this->lastname);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":password", $this->password);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                return ["status" => true, "message" => "signup successful"];
            } else {
                return ["status" => false, "message" => "signup failed"];
            }
        } catch (PDOException $e) {
            return ["status" => false, "message" => "An error occured when attempting signup process."];
        }
    }

    //generates verification code
    private function generate_code() {
        $this->code = rand(123456, 999999);
    }

    //create verification code
    private function create_verification_code() {
        try {
            $sql = "INSERT INTO email_validation (email, code, expires_at) VALUES (:email, :code, (NOW() + INTERVAL 6 HOUR));";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":code", $this->code);
            $stmt->bindParam(":email", $this->email);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                return true;
            } else {
                return false;
            }
        } catch(PDOException $e) {
            return false;
        }
    }

    //update verification code
    private function update_verification_code() {
        try {
            $sql = "UPDATE email_validation SET code = :code, expires_at = (NOW() + INTERVAL 6 HOUR) WHERE email = :email;";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":code", $this->code);
            $stmt->bindParam(":email", $this->email);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                return true;
            } else {
                return false;
            }
        } catch(PDOException $e) {
            return false;
        }
    }
    
    //update user email verification status
    private function update_verification_email_status() {
        try {
            $sql = "UPDATE {$this->table} SET email_valid = true WHERE email = :email LIMIT 1;";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":email", $this->email);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                return true;
            }

            return false;
            
        } catch(PDOException $e) {
            return false;
        }
    }

    //set email verification code
    public function email_verification_code_setup() {
        try {
            $this->generate_code();

            $sql = "SELECT COUNT(*) AS num FROM email_validation WHERE email = :email;";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":email", $this->email);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                $response = $stmt->fetch(PDO::FETCH_OBJ);
                if($response->num > 0) {
                    if($this->update_verification_code() === true) {
                        return ["status" => true, "message" => "Update successfully"];
                    }
                } else {
                    if($this->create_verification_code() === true) {
                        return ["status" => true, "message" => "Token created successfully"];
                    }
                }
            }

            return ["status" => false, "message" => "There seem to be some database error."];
            
        } catch(PDOException $e) {
            return ["status" => false, "message" => "An error occured."];
        }
    }

    //send email verification code
    public function send_email_verification_code($mail) {

        try {
            //Recipients
            $mail->setFrom('from@example.com', 'Audio');
            $mail->addAddress($this->email, "$this->firstname $this->lastname");     // Add a recipient
            $mail->addReplyTo('info@example.com', 'Information');

            // Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = 'Email verification code';
            $mail->Body    = "<h1>$this->code</h1>";
            $mail->AltBody = $this->code;

            $mail->send();
            return ["status" => true, "message" => "Message sent"];
        } catch (Exception $e) {
            return ["status" => false, "message" => "Message not sent"];;
        }
    }

    //verify email
    public function verify_email() {
        try {

            $sql = "DELETE FROM email_validation WHERE email = :email AND code = :code AND (NOW() < expires_at);";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":code", $this->code);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                if($this->update_verification_email_status() === true) {
                    return ["status" => true, "message" => "Update successfully"];
                }
            }

            return ["status" => false, "message" => "Failed to verify email. Please, try again."];
            
        } catch(PDOException $e) {
            return ["status" => false, "message" => "An error occured."];
        }
    }

    //get user info
    public function get_user_by_email() {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1;";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":email", $this->email);
            $stmt->execute();
            if($stmt->rowCount() > 0) {
                $data = $stmt->fetch(PDO::FETCH_OBJ);
                //load data
                $this->id = (int)$data->id;
                $this->firstname = $data->firstname;
                $this->lastname = $data->lastname;
                $this->password = $data->password;
                $this->email_valid = $data->email_valid;
                $this->subscribed = (int)$data->subscribed;
                $this->trial = $data->trial;
                $this->decrypted = (int)$data->decryted;
                return ["status" => true, "message" => "User loaded successfully"];
            } else {
                return ["status" => false, "message" => "Invalid credentials."];
            }
        } catch(PDOException $e) {
            return ["status" => false, "message" => "An error occured connecting to database"];
        }
    }

    public function add_to_subscribe(int $amt) {
        $sql = "UPDATE {$this->table} SET subscribed = subscribed + :amt WHERE id = :id AND email = :email;";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":id", $this->id);
            $stmt->bindParam(":amt", $amt);
            $stmt->execute();
            if($stmt->rowCount() > 0) {
                return ["status" => true, "message" => "subscription successful"];
            } else {
                return ["status" => false, "message" => "subscription failed"];
            }
        } catch(PDOException $e) {
            return ["status" => false, "message" => "An error occured connecting to database"];
        }
    }

    public function decrypt() {
        $sql = "UPDATE {$this->table} SET subscribed = subscribed - 1, decryted = decryted + 1 WHERE id = :id AND email = :email AND subscribed > 0;";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":id", $this->id);
            $stmt->execute();
            if($stmt->rowCount() > 0) {
                return ["status" => true, "message" => "decryption successful"];
            } else {
                return ["status" => false, "message" => "decryption failed"];
            }
        } catch(PDOException $e) {
            return ["status" => false, "message" => "An error occured connecting to database"];
        }
    }

    public function get_trial() {
        $sql = "UPDATE {$this->table} SET subscribed = subscribed + 5, trial = true WHERE id = :id AND email = :email AND trial = false;";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":id", $this->id);
            $stmt->execute();
            if($stmt->rowCount() > 0) {
                return ["status" => true, "message" => "trial successful"];
            } else {
                return ["status" => false, "message" => "trial failed"];
            }
        } catch(PDOException $e) {
            return ["status" => false, "message" => "An error occured connecting to database"];
        }
    }

    public function reset_password(String $new_password) {
        $sql = "UPDATE {$this->table} SET password = :password WHERE email = :email LIMIT 1;";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":password", $new_password);
            $stmt->execute();
            if($stmt->rowCount() > 0) {
                return ["status" => true, "message" => "password update successful"];
            } else {
                return ["status" => false, "message" => "password update failed"];
            }
        } catch(PDOException $e) {
            return ["status" => false, "message" => "An error occured connecting to database"];
        }
    }
}