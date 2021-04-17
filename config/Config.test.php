<?php

    class Config {
        const BASE_URL = "localhost";
        const MAILER = [
            "host" => "smtp.gmail.com",
            "username" => "your username",
            "password" => "your password"
        ];
        const JWT_KEY = "Q3$-audio-&34hEn87";//used paystack for payment validation
        const PAYSTACK = [
            "validate" => false,
            "key" => "sk_test_0669e5fb5b00dbb8026c3845abea0b1501d95eb3"
        ];
    }