<?php

if (session_status() !== PHP_SESSION_ACTIVE) {

  session_set_cookie_params([
    'lifetime' => 60 * 60 * 24 * 7,   // 7 giorni
    'path' => '/',
    'domain' => 'localhost',        
    'secure' => false,             
    'httponly' => true,
    'samesite' => 'Lax'
  ]);

  session_start();
}
