<?php

function ensureReservationNotificationSchema(PDO $pdo): void
{
    static $schemaChecked = false;

    if ($schemaChecked) {
        return;
    }

    $requiredColumns = [
        'email' => "ALTER TABLE reservations ADD COLUMN email VARCHAR(150) NOT NULL DEFAULT ''",
        'confirmation_email_sent_at' => "ALTER TABLE reservations ADD COLUMN confirmation_email_sent_at DATETIME DEFAULT NULL",
        'reminder_email_sent_at' => "ALTER TABLE reservations ADD COLUMN reminder_email_sent_at DATETIME DEFAULT NULL",
    ];

    foreach ($requiredColumns as $column => $ddl) {
        $stmt = $pdo->prepare("
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'reservations'
              AND COLUMN_NAME = :column
            LIMIT 1
        ");
        $stmt->execute([':column' => $column]);

        if (! $stmt->fetch()) {
            $pdo->exec($ddl);
        }
    }

    $schemaChecked = true;
}

function fetchReservationNotificationData(PDO $pdo, int $reservationId): ?array
{
    ensureReservationNotificationSchema($pdo);

    $sql = "
        SELECT
            r.id,
            r.nom_client,
            r.email,
            r.telephone,
            r.date_reservation,
            r.nombre_personnes,
            r.statut,
            r.code_confirmation,
            r.commentaires,
            r.confirmation_email_sent_at,
            r.reminder_email_sent_at,
            c.heure,
            c.service,
            t.numero AS numero_table,
            t.zone AS zone_table
        FROM reservations r
        LEFT JOIN creneaux c ON r.creneau_id = c.id
        LEFT JOIN tables_restaurant t ON r.table_id = t.id
        WHERE r.id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $reservationId]);

    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    return $reservation ?: null;
}

function getReminderCandidates(PDO $pdo, string $targetDate): array
{
    ensureReservationNotificationSchema($pdo);

    $sql = "
        SELECT r.id
        FROM reservations r
        WHERE r.statut = 'confirmee'
          AND r.date_reservation = :target_date
          AND COALESCE(r.email, '') <> ''
          AND r.reminder_email_sent_at IS NULL
        ORDER BY r.date_reservation ASC, r.id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':target_date' => $targetDate]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function sendReservationConfirmationEmail(PDO $pdo, int $reservationId): bool
{
    $reservation = fetchReservationNotificationData($pdo, $reservationId);

    if (! $reservation || ! isValidReservationEmail($reservation['email'] ?? '')) {
        return false;
    }

    $subject = 'Reservation confirmation - Le Gourmet';
    $body = buildReservationEmailBody($reservation, 'confirmation');

    if (! sendReservationMail($reservation['email'], $subject, $body)) {
        return false;
    }

    $stmt = $pdo->prepare("
        UPDATE reservations
        SET confirmation_email_sent_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([':id' => $reservationId]);

    return true;
}

function sendReservationReminderEmail(PDO $pdo, int $reservationId): bool
{
    $reservation = fetchReservationNotificationData($pdo, $reservationId);

    if (
        ! $reservation
        || ($reservation['statut'] ?? '') !== 'confirmee'
        || ($reservation['date_reservation'] ?? '') !== date('Y-m-d', strtotime('+1 day'))
        || ! isValidReservationEmail($reservation['email'] ?? '')
    ) {
        return false;
    }

    $subject = 'Reservation reminder for tomorrow - Le Gourmet';
    $body = buildReservationEmailBody($reservation, 'reminder');

    if (! sendReservationMail($reservation['email'], $subject, $body)) {
        return false;
    }

    $stmt = $pdo->prepare("
        UPDATE reservations
        SET reminder_email_sent_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([':id' => $reservationId]);

    return true;
}

function sendNextDayReservationReminders(PDO $pdo, ?string $targetDate = null): array
{
    $targetDate = $targetDate ?: date('Y-m-d', strtotime('+1 day'));
    $reservationIds = getReminderCandidates($pdo, $targetDate);

    $result = [
        'target_date' => $targetDate,
        'sent' => 0,
        'failed' => 0,
    ];

    foreach ($reservationIds as $reservationId) {
        if (sendReservationReminderEmail($pdo, (int) $reservationId)) {
            $result['sent']++;
        } else {
            $result['failed']++;
        }
    }

    return $result;
}

function buildReservationEmailBody(array $reservation, string $type): string
{
    $date = ! empty($reservation['date_reservation'])
        ? date('d/m/Y', strtotime($reservation['date_reservation']))
        : 'date a confirmer';
    $time = trim((string) ($reservation['heure'] ?? ''));
    $service = ucfirst((string) ($reservation['service'] ?? ''));
    $tableText = ! empty($reservation['numero_table'])
        ? 'Table ' . $reservation['numero_table'] . (! empty($reservation['zone_table']) ? ' - ' . $reservation['zone_table'] : '')
        : 'Table attribuee a votre arrivee';
    $commentText = trim((string) ($reservation['commentaires'] ?? ''));
    $intro = $type === 'reminder'
        ? "Nous vous rappelons votre reservation prevue demain chez Le Gourmet."
        : "Votre reservation a bien ete enregistree chez Le Gourmet.";

    $lines = [
        'Bonjour ' . trim((string) ($reservation['nom_client'] ?? '')),
        '',
        $intro,
        '',
        'Details de la reservation :',
        '- Code : ' . ($reservation['code_confirmation'] ?? ''),
        '- Date : ' . $date,
        '- Heure : ' . ($time !== '' ? $time : 'A confirmer'),
        '- Service : ' . ($service !== '' ? $service : 'A confirmer'),
        '- Convives : ' . (int) ($reservation['nombre_personnes'] ?? 0),
        '- Table : ' . $tableText,
    ];

    if ($commentText !== '') {
        $lines[] = '- Message du restaurant : ' . $commentText;
    }

    $lines[] = '';
    $lines[] = 'Si vous devez modifier ou annuler votre reservation, merci de nous contacter des que possible.';
    $lines[] = '';
    $lines[] = 'Le Gourmet';

    return implode("\r\n", $lines);
}

function sendReservationMail(string $to, string $subject, string $body): bool
{
    $config = getMailConfig();

    if (! isValidReservationEmail($to) || ! isValidMailConfig($config)) {
        return false;
    }

    $transport = smtpOpenConnection($config);

    if (! $transport) {
        return false;
    }

    try {
        smtpExpect($transport, [220]);
        smtpCommand($transport, 'EHLO localhost', [250]);

        if (($config['encryption'] ?? 'none') === 'tls') {
            smtpCommand($transport, 'STARTTLS', [220]);

            if (! stream_socket_enable_crypto($transport, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Unable to enable TLS encryption.');
            }

            smtpCommand($transport, 'EHLO localhost', [250]);
        }

        if (! empty($config['username'])) {
            smtpCommand($transport, 'AUTH LOGIN', [334]);
            smtpCommand($transport, base64_encode((string) $config['username']), [334]);
            smtpCommand($transport, base64_encode((string) $config['password']), [235]);
        }

        $fromEmail = (string) $config['from_email'];
        smtpCommand($transport, 'MAIL FROM:<' . $fromEmail . '>', [250]);
        smtpCommand($transport, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtpCommand($transport, 'DATA', [354]);

        $headers = buildSmtpHeaders($config, $to, $subject);
        $message = $headers . "\r\n\r\n" . dotStuffSmtpBody($body) . "\r\n.";
        smtpCommand($transport, $message, [250], false);
        smtpCommand($transport, 'QUIT', [221]);

        fclose($transport);
        return true;
    } catch (Throwable $e) {
        if (is_resource($transport)) {
            @fwrite($transport, "QUIT\r\n");
            fclose($transport);
        }

        return false;
    }
}

function getMailConfig(): array
{
    static $config;

    if ($config !== null) {
        return $config;
    }

    $configPath = __DIR__ . '/../config/mail.php';

    if (! file_exists($configPath)) {
        $config = [];
        return $config;
    }

    $loaded = require $configPath;
    $config = is_array($loaded) ? $loaded : [];

    return $config;
}

function isValidMailConfig(array $config): bool
{
    $requiredKeys = ['host', 'port', 'from_email', 'from_name'];

    foreach ($requiredKeys as $key) {
        if (empty($config[$key])) {
            return false;
        }
    }

    if (! isValidReservationEmail((string) $config['from_email'])) {
        return false;
    }

    return true;
}

function smtpOpenConnection(array $config)
{
    $host = (string) $config['host'];
    $port = (int) $config['port'];
    $timeout = (int) ($config['timeout'] ?? 15);
    $encryption = (string) ($config['encryption'] ?? 'none');
    $remoteHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;

    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ],
    ]);

    $transport = @stream_socket_client(
        $remoteHost . ':' . $port,
        $errorNumber,
        $errorMessage,
        $timeout,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if (! $transport) {
        return false;
    }

    stream_set_timeout($transport, $timeout);

    return $transport;
}

function smtpCommand($transport, string $command, array $expectedCodes, bool $appendCrlf = true): string
{
    $payload = $appendCrlf ? $command . "\r\n" : $command . "\r\n";

    if (fwrite($transport, $payload) === false) {
        throw new RuntimeException('Unable to write to SMTP socket.');
    }

    return smtpExpect($transport, $expectedCodes);
}

function smtpExpect($transport, array $expectedCodes): string
{
    $response = '';

    while (($line = fgets($transport, 515)) !== false) {
        $response .= $line;

        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    $code = (int) substr($response, 0, 3);

    if (! in_array($code, $expectedCodes, true)) {
        throw new RuntimeException('Unexpected SMTP response: ' . trim($response));
    }

    return $response;
}

function buildSmtpHeaders(array $config, string $to, string $subject): string
{
    $fromName = encodeMailHeader((string) $config['from_name']);
    $fromEmail = (string) $config['from_email'];
    $replyTo = ! empty($config['reply_to']) ? (string) $config['reply_to'] : $fromEmail;
    $subjectHeader = encodeMailHeader($subject);

    $headers = [
        'Date: ' . date(DATE_RFC2822),
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'To: <' . $to . '>',
        'Reply-To: ' . $replyTo,
        'Subject: ' . $subjectHeader,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Mailer: LeGourmet SMTP',
    ];

    return implode("\r\n", $headers);
}

function dotStuffSmtpBody(string $body): string
{
    $normalized = str_replace(["\r\n", "\r"], "\n", $body);
    $normalized = preg_replace('/^\./m', '..', $normalized);

    return str_replace("\n", "\r\n", $normalized);
}

function encodeMailHeader(string $value): string
{
    if (function_exists('mb_encode_mimeheader')) {
        return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
    }

    return $value;
}

function isValidReservationEmail(string $email): bool
{
    return (bool) filter_var(trim($email), FILTER_VALIDATE_EMAIL);
}
