<?php

    require_once "../../config/Database.php";
    require_once "../../model/User.php";

    $db = new Database();

    $user = new User($db->get_connection());

    $response = $user->get_all_users();
    if($response->rowCount() > 0) {
        echo "Some users found";
        echo "<br />";
        echo password_hash("test1234", PASSWORD_DEFAULT);
    } else {
        echo "No users found";
    }