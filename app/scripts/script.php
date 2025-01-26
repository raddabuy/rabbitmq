<?php

require_once('../Database.php');
use Api\Database;

$userId = 3999725;

for ($i = 0; $i < 1500; $i++) {
    $friendId = rand(2999794, 3999724);
    $text = 'Nulla at volutpat diam ut. Sit amet tellus cras adipiscing enim. Eu consequat ac felis donec et odio pellentesque diam. Viverra adipiscing at in tellus integer feugiat scelerisque varius morbi. Elementum pulvinar etiam non quam lacus suspendisse faucibus. Fames ac turpis egestas maecenas pharetra convallis posuere. Massa enim nec dui nunc. Quis ipsum suspendisse ultrices gravida dictum fusce ut placerat. Condimentum lacinia quis vel eros donec ac odio tempor. Donec adipiscing tristique risus nec. Morbi non arcu risus quis varius quam quisque id diam. Id cursus metus aliquam eleifend mi in nulla posuere sollicitudin. Tempor orci eu lobortis elementum. Integer malesuada nunc vel risus commodo viverra.
Id ornare arcu odio ut. Est velit egestas dui id ornare arcu. Tempor orci dapibus ultrices in iaculis nunc. Amet consectetur adipiscing elit duis tristique. Venenatis lectus magna fringilla urna porttitor rhoncus dolor purus non. Hac habitasse platea dictumst quisque sagittis. Eu sem integer vitae justo eget magna fermentum iaculis eu. Laoreet id donec ultrices tincidunt arcu non sodales. Quis commodo odio aenean sed adipiscing diam. Dui vivamus arcu felis bibendum. Habitant morbi tristique senectus et netus et. Nunc congue nisi vitae suscipit tellus mauris a diam maecenas. Turpis egestas maecenas pharetra convallis. Sodales ut eu sem integer vitae justo eget magna. Ullamcorper eget nulla facilisi etiam dignissim diam quis enim lobortis. Elementum pulvinar etiam non quam lacus suspendisse faucibus interdum.';
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->prepare("SELECT * FROM friends WHERE user_id = ? AND friend_id = ?");
    $stmt->execute([$userId, $friendId]);
    $isFriend = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;

    if (!$isFriend) {
        $stmt = $pdo->prepare("INSERT INTO friends(user_id, friend_id) VALUES (?, ?)");
        $stmt->execute([3999725, $friendId]);
        echo("friend is added \n");

        $date = new \Datetime();
        $formattedDate = $date->format('Y-m-d G:i:s');
        $stmt = $pdo->prepare("INSERT INTO posts(user_id, text, created_at) VALUES (?, ?, ?)");
        $stmt->execute([$friendId, $text, $formattedDate]);
        echo("post is added \n");

        $hashedPassword = password_hash('123456', PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $friendId]);
        echo("password is added \n");

    } else {
        echo("already friends \n");
    }

}
