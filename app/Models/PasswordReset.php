<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Database.php';

class PasswordReset
{
    public static function createToken(int $usuarioId, int $ttlMinutes = 60): string
    {
        $token = bin2hex(random_bytes(32)); // 64 hex
        $expires = (new DateTimeImmutable("+{$ttlMinutes} minutes"))->format('Y-m-d H:i:s');

        $pdo = Database::pdo();
        $st = $pdo->prepare("INSERT INTO password_resets (usuario_id, token, expires_at) VALUES (?, ?, ?)");
        $st->execute([$usuarioId, $token, $expires]);

        return $token;
    }

    public static function findValid(string $token): ?array
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? LIMIT 1");
        $st->execute([$token]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        if ((int)$row['used'] === 1) return null;
        if (empty($row['expires_at'])) return null;

        $now = new DateTimeImmutable('now');
        $exp = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string)$row['expires_at']);
        if (!$exp || $now > $exp) return null;

        return $row;
    }

    public static function markUsed(int $id): void
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
        $st->execute([$id]);
    }
}
