<?php
require_once __DIR__ . "/../app/Core/Database.php";

try {
    $pdo = Database::pdo();

    // Check existing
    $count = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    if ($count > 0) {
        echo "Ya existen usuarios. No se creó nada. <a href='index.php'>Volver</a>";
        exit;
    }

    $pass = password_hash("123456", PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre,email,password,rol) VALUES (?,?,?,?)");
    $stmt->execute(["Jefe Académico", "jefe@demo.com", $pass, "jefe"]);
    $stmt->execute(["Coordinador", "coordinador@demo.com", $pass, "coordinador"]);
    $stmt->execute(["Docente", "docente@demo.com", $pass, "docente"]);

    echo "✅ Usuarios de demo creados:<br>jefe@demo.com / 123456<br>coordinador@demo.com / 123456<br>docente@demo.com / 123456<br><a href='index.php'>Ir al login</a>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
