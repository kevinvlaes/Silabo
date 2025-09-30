<?php
return [
  'app_url' => 'http://localhost/Silabo/public', // AJUSTA a tu ruta pública

  'db' => [
    'host' => 'localhost',
    'name' => 'silabus_db',
    'user' => 'root',
    'pass' => ''
  ],

  // Configuración de correo
  'mail' => [
    'driver'   => 'smtp',            // 'smtp' | 'phpmail'
    'host'     => 'smtp.gmail.com',  // TU SMTP
    'port'     => 587,
    'encryption' => 'tls',           // 'tls' | 'ssl' | null
    'username' => 'tu-correo@gmail.com',
    'password' => 'tu-password-o-app-password',
    'from_email' => 'no-reply@tu-dominio.test',
    'from_name'  => 'Repositorio de Sílabos'
  ]
];
