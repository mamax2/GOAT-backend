<?php

if (session_status() !== PHP_SESSION_ACTIVE) {

  session_set_cookie_params([
    'lifetime' => 60 * 60 * 24 * 7,   // 7 giorni
    'path' => '/',
    'domain' => 'localhost',        // IMPORTANTISSIMO
    'secure' => false,              // TRUE solo su HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
  ]);

  session_start();
}
