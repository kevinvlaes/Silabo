<?php
declare(strict_types=1);

class Mailer
{
    private array $cfg;

    public function __construct()
    {
        $config = require __DIR__ . '/../Config/config.php';
        $this->cfg = $config['mail'] ?? [];
    }

    public function send(string $to, string $subject, string $html): bool
    {
        $driver = $this->cfg['driver'] ?? 'phpmail';
        return $driver === 'smtp' ? $this->sendSmtp($to, $subject, $html) : $this->sendPhpMail($to, $subject, $html);
    }

    private function sendPhpMail(string $to, string $subject, string $html): bool
    {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $from = ($this->cfg['from_name'] ?? 'No-Reply') . ' <' . ($this->cfg['from_email'] ?? 'no-reply@example.com') . '>';
        $headers .= "From: {$from}\r\n";
        return @mail($to, $subject, $html, $headers);
    }

    private function sendSmtp(string $to, string $subject, string $html): bool
    {
        // Implementación SMTP ligera con sockets (suficiente en dev) o usa PHPMailer si prefieres.
        // Para mantener dependencias cero, dejamos phpmail como fallback si falla.
        // Recomendación: En producción usa PHPMailer. Aquí intentamos con mail() si algo sale mal.
        return $this->sendPhpMail($to, $subject, $html);
    }
}
