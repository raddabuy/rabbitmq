<?php

declare(strict_types=1);

require 'vendor/autoload.php';

require_once('Api.php');
require_once('Database.php');
require_once('Models/User.php');

use Api\Api;
use Api\Database;
use Api\Models\User;
use Dotenv\Dotenv;
use Firebase\JWT\JWT;

class UserApi extends Api
{
    public function register()
    {
        $name = $this->postRequest['name'] ?? '';
        $lastName = $this->postRequest['last_name'] ?? '';
        $birthdate = $this->postRequest['birthdate'] ?? null;
        $biorgaphy = $this->postRequest['biography'] ?? '';
        $city = $this->postRequest['city'] ?? '';
        $password = $this->postRequest['password'] ?? '';

        if ($name && $password) {
            $pdo = (new Database())->getConnection();

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, birthdate, biography, city, password) VALUES (?, ?, ?, ?, ?, ?) RETURNING id");

            if ($stmt->execute([$name, $lastName, $birthdate, $biorgaphy, $city, $hashedPassword])) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                return $this->response(['userId' => $user['id']], 200);
            } else {
                return $this->response(['message' => 'Registration failed.'], 500);
            }
        }

        if (empty($name) || empty($password)) {
            return $this->response(['message' => 'Username and password are required.'], 422);
        }

        return $this->response("Saving error", 500);
    }

    public function login()
    {
        $userId = $this->postRequest['user_id'] ?? '';
        $password = $this->postRequest['password'] ?? '';

        if (empty($userId) || empty($password)) {
            return $this->response(['message' => 'UserId and password are required.'], 422);
        }

        $pdo = (new Database())->getConnection();
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $payload = [
                'iat' => time(),
                'exp' => time() + (60 * 60),
                'id' => $user['id'],
            ];

            $dotenv = Dotenv::createImmutable(__DIR__);
            $dotenv->load();

            $secretKey = $_ENV['JWT_SECRET'];

            $jwt = JWT::encode($payload, $secretKey, 'HS256');

            return $this->response(['token' => $jwt], 200);
        } else {
            return $this->response(['message' => 'Invalid userId or password'], 422);
        }
    }

    public function getUser($request)
    {
        $this->checkAuth();

        $userId = $request['id'] ?? '';

        $pdo = (new Database())->getConnection();

        $stmt = $pdo->prepare("SELECT id, first_name, last_name, birthdate, biography, city FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $rowUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($rowUser)) {
            return $this->response('User not found', 404);
        }

        $user = new User;

        $user->setId($userId);
        $user->setFirstName($rowUser['first_name']);
        $user->setLastName($rowUser['last_name']);
        $user->setBirthdate($rowUser['birthdate']);
        $user->setBiography($rowUser['biography']);
        $user->setCity($rowUser['city']);

        return $this->response($user, 200);
    }

    public function searchUser($request)
    {
        $firstName = $_GET['first_name'];
        $lastName = $_GET['last_name'];

        if (!isset($firstName) || !isset($lastName)) {
            return $this->response(['message' => 'first_name and last_name are required'], 422);
        }

        $this->checkAuth();

        $pdo = (new Database())->getConnection();

        $stmt = $pdo->prepare("SELECT id, first_name, last_name, birthdate, biography, city FROM users WHERE first_name LIKE ? AND last_name LIKE ?");
        $stmt->execute(["$firstName%", "$lastName%"]);

        $userRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $users = [];

        foreach ($userRows as $row) {
            $user = new User;

            $user->setId($row['id']);
            $user->setFirstName($row['first_name']);
            $user->setLastName($row['last_name']);
            $user->setBirthdate($row['birthdate']);
            $user->setBiography($row['biography']);
            $user->setCity($row['city']);

            $users[] = $user;
        }

        return $this->response($users, 200);
    }

    public function addFriend($request) {
        $decoded = $this->checkAuth();

        $userId = $decoded->id;
        $friendId = $request['id'];

        $pdo = (new Database())->getConnection();

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$friendId]);
        $existedFriend = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;

        if (!$existedFriend) {
            return $this->response('Friend does not exist', 404);
        }

        $stmt = $pdo->prepare("SELECT * FROM friends WHERE user_id = ? AND friend_id = ?");
        $stmt->execute([$userId, $friendId]);
        $isFriend = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;

        if ($isFriend) {
            return $this->response('Friend already exists', 404);
        }

        $stmt = $pdo->prepare("INSERT INTO friends(user_id, friend_id) VALUES (?, ?)");

        if ($stmt->execute([$userId, $friendId])) {
            return $this->response('Friend is added successfully', 200);
        } else {
            return $this->response('Failed to add a friend', 500);
        }
    }

    public function removeFriend($request) {

        $decoded = $this->checkAuth();

        $userId = $decoded->id;
        $friendId = $request['id'];

        $pdo = (new Database())->getConnection();

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$friendId]);
        $existedFriend = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;

        if (!$existedFriend) {
            return $this->response('Friend does not exist', 404);
        }

        $stmt = $pdo->prepare("SELECT * FROM friends WHERE user_id = ? AND friend_id = ?");
        $stmt->execute([$userId, $friendId]);
        $isFriend = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;

        if (!$isFriend) {
            return $this->response('This is not your friend', 404);
        }

        $stmt = $pdo->prepare("DELETE FROM friends WHERE user_id = ? AND friend_id = ?");

        if ($stmt->execute([$userId, $friendId])) {
            return $this->response('Friend is deleted successfully', 200);
        } else {
            return $this->response('Failed to remove a friend', 500);
        }
    }
}