<?php

declare(strict_types=1);

namespace Api;

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Api
{
    public $requestParams = [];
    public $postRequest = [];

    public function __construct()
    {
        header("Access-Control-Allow-Orgin: *");
        header("Access-Control-Allow-Methods: *");
        header("Content-Type: application/json");

        $this->requestParams = $_REQUEST;
        $this->postRequest = json_decode(file_get_contents('php://input'), true);
    }

    protected function response($data, $status = 500)
    {
        header("HTTP/1.1 " . $status . " " . $this->requestStatus($status));

        return json_encode($data);
    }

    private function requestStatus($code)
    {
        $status = [
            200 => 'OK',
            403 => 'Forbidden',
            422 => 'Unprocessable Entity',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        ];

        return ($status[$code]) ? $status[$code] : $status[500];
    }

    private function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
                $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
            } elseif (function_exists('apache_request_headers')) {
                $requestHeaders = apache_request_headers();
                // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
                $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
                //print_r($requestHeaders);
                if (isset($requestHeaders['Authorization'])) {
                    $headers = trim($requestHeaders['Authorization']);
                }
            }
        }

        return $headers;
    }

    public function getBearerToken()
    {
        $headers = $this->getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public function checkAuth(){
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();

        $secretKey = $_ENV['JWT_SECRET'];

        $jwt = $this->getBearerToken();

        if ($jwt) {
            try {
                $decoded = JWT::decode($this->getBearerToken(), new Key($secretKey, 'HS256'));
            } catch (\Exception $e) {
                return $this->response(['message' => 'Invalid token'], 403);
            }
        } else {
            return $this->response(['message' => 'Token is missing'], 422);
        }

        return $decoded;
    }
}