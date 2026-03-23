<?php

define('Guest', [
    'index.php',
    'items.php',
    'items-detail.php',
    'news.php',
    'about.php',
    'auth/login.php',
    'auth/register.php',
    'auth/google-callback.php',
    'auth/discord-callback.php',
]);

define('RegisteredUser', [
    'post-time.php',
    'claim-item.php',
    'contact.php',
    'messages.php',
    'profile.php',
    'dashboard/index.php',
    'dashboard/myitems.php',
    'dashboard/claims.php',
    'dashboard/profile.php',
    'dashboard/edit-item.php',
]);

function canDo(string $action): bool{
    switch ($action){

    }
}

?>