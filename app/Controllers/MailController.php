<?php
namespace App\Controllers;

class MailController {

    private const TEST_RECIPIENT = 'walid.bouarifi@gmail.com';

    public function sendConfirmation(array $regData, array $event, string $pdfPath = ''): bool {
        $this->requireService();

        $subject = "✅ Confirmation d'inscription — {$event['title']}";
        $body    = $this->buildConfirmationHTML($regData, $event);

        $attachments = [];
        if ($pdfPath && file_exists($pdfPath)) {
            $attachments[] = [
                'path' => $pdfPath,
                'name' => 'ticket_eventhub.pdf',
            ];
        }

        $sent = sendEmail(self::TEST_RECIPIENT, $subject, $body, $attachments);

        if (!$sent) {
            error_log("[MailController] sendConfirmation failed for token={$regData['token']}");
        }
        return $sent;
    }

    public function sendCapacityAlert(array $event, int $registeredCount, string $pdfPath = ''): bool {
        $this->requireService();

        $fillPct = (int) round($registeredCount / max((int)$event['capacity'], 1) * 100);
        $subject = "⚠️ Alerte capacité {$fillPct}% — {$event['title']}";
        $body    = $this->buildAlertHTML($event, $registeredCount, $fillPct);

        $attachments = [];
        if ($pdfPath && file_exists($pdfPath)) {
            $attachments[] = [
                'path' => $pdfPath,
                'name' => 'rapport_organisateur.pdf',
            ];
        }

        $sent = sendEmail(self::TEST_RECIPIENT, $subject, $body, $attachments);

        if (!$sent) {
            error_log("[MailController] sendCapacityAlert failed for event_id={$event['id']}");
        }
        return $sent;
    }

    private function buildConfirmationHTML(array $reg, array $event): string {
        $date          = date('d/m/Y \à H:i', strtotime($event['date']));
        $realRecipient = htmlspecialchars($reg['user_email'], ENT_QUOTES, 'UTF-8');
        $userName      = htmlspecialchars($reg['user_name'],  ENT_QUOTES, 'UTF-8');
        $eventTitle    = htmlspecialchars($event['title'],    ENT_QUOTES, 'UTF-8');
        $location      = htmlspecialchars($event['location'], ENT_QUOTES, 'UTF-8');
        $token         = htmlspecialchars($reg['token'],      ENT_QUOTES, 'UTF-8');

        return <<<HTML
<div style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;
            max-width:600px;margin:0 auto;background:#000000;
            border:1px solid #38444d;border-radius:16px;overflow:hidden;">
  <div style="background:#1d9bf0;padding:32px;text-align:center;">
    <div style="font-size:28px;margin-bottom:8px;">✅</div>
    <h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:900;letter-spacing:-0.5px;">
      Inscription Confirmée !
    </h1>
  </div>
  <div style="padding:32px;">
    <p style="font-size:17px;font-weight:700;color:#e7e9ea;margin:0 0 6px;">
      Bonjour {$userName},
    </p>
    <p style="color:#8899a6;margin:0 0 24px;font-size:14px;line-height:1.6;">
      Votre inscription à l'événement suivant a été enregistrée avec succès.
    </p>
    <div style="background:#15202b;border:1px solid #38444d;border-radius:12px;padding:20px;margin-bottom:24px;">
      <h2 style="color:#1d9bf0;font-size:18px;font-weight:900;margin:0 0 14px;">
        {$eventTitle}
      </h2>
      <p style="color:#8899a6;margin:6px 0;font-size:14px;">📅 &nbsp;{$date}</p>
      <p style="color:#8899a6;margin:6px 0;font-size:14px;">📍 &nbsp;{$location}</p>
      <p style="color:#8899a6;margin:6px 0;font-size:14px;">✉️ &nbsp;Destinataire : <strong style="color:#e7e9ea;">{$realRecipient}</strong></p>
    </div>
    <p style="color:#8899a6;font-size:14px;line-height:1.6;margin:0 0 16px;">
      Votre <strong style="color:#e7e9ea;">ticket PDF</strong> est joint à cet email.
      Présentez-le à l'entrée de l'événement.
    </p>
    <div style="background:#15202b;border-radius:8px;padding:12px 16px;">
      <p style="font-family:'Courier New',monospace;font-size:11px;color:#38444d;
                margin:0;word-break:break-all;">
        Token : {$token}
      </p>
    </div>
  </div>
  <div style="background:#15202b;padding:16px 32px;border-top:1px solid #38444d;text-align:center;">
    <p style="color:#38444d;font-size:12px;margin:0;">
      EventHub Pro &nbsp;·&nbsp; ENSA Marrakech &nbsp;·&nbsp; Université Cadi Ayyad
    </p>
  </div>
</div>
HTML;
    }

    private function buildAlertHTML(array $event, int $registeredCount, int $fillPct): string {
        $date           = date('d/m/Y \à H:i', strtotime($event['date']));
        $remaining      = max((int)$event['capacity'] - $registeredCount, 0);
        $eventTitle     = htmlspecialchars($event['title'],           ENT_QUOTES, 'UTF-8');
        $location       = htmlspecialchars($event['location'],        ENT_QUOTES, 'UTF-8');
        $orgEmail       = htmlspecialchars($event['organizer_email'], ENT_QUOTES, 'UTF-8');
        $barColor       = $fillPct >= 100 ? '#ef4444' : '#f59e0b';

        $filled  = max(1, (int) round($fillPct / 10));
        $empty   = 10 - $filled;
        $cells   = str_repeat(
            "<td style='background:{$barColor};height:12px;width:10%;'></td>",
            $filled
        ) . str_repeat(
            "<td style='background:#38444d;height:12px;width:10%;'></td>",
            $empty
        );

        return <<<HTML
<div style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;
            max-width:600px;margin:0 auto;background:#000000;
            border:1px solid #38444d;border-radius:16px;overflow:hidden;">
  <div style="background:{$barColor};padding:32px;text-align:center;">
    <div style="font-size:28px;margin-bottom:8px;">⚠️</div>
    <h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:900;">
      Alerte Capacité — {$fillPct}%
    </h1>
    <p style="color:rgba(255,255,255,0.85);margin:6px 0 0;font-size:13px;">
      Organisateur : {$orgEmail}
    </p>
  </div>
  <div style="padding:32px;">
    <p style="color:#e7e9ea;font-size:16px;font-weight:700;margin:0 0 20px;line-height:1.5;">
      L'événement <span style="color:{$barColor}">{$eventTitle}</span>
      a atteint <span style="color:{$barColor};font-size:20px;">{$fillPct}%</span>
      de sa capacité maximale.
    </p>
    <div style="background:#15202b;border:1px solid #38444d;border-radius:12px;padding:20px;margin-bottom:24px;">
      <p style="color:#8899a6;margin:6px 0;font-size:14px;">📅 &nbsp;{$date}</p>
      <p style="color:#8899a6;margin:6px 0;font-size:14px;">📍 &nbsp;{$location}</p>
      <p style="color:{$barColor};font-size:26px;font-weight:900;margin:14px 0 4px;">
        {$registeredCount} / {$event['capacity']}
      </p>
      <p style="color:#8899a6;font-size:13px;margin:0;">
        {$remaining} place(s) restante(s)
      </p>
    </div>
    <p style="color:#8899a6;font-size:12px;margin:0 0 6px;">Taux de remplissage :</p>
    <table style="width:100%;border-collapse:separate;border-spacing:2px;">
      <tr>{$cells}</tr>
    </table>
    <p style="color:{$barColor};font-size:12px;font-weight:700;margin:4px 0 0;text-align:right;">
      {$fillPct}%
    </p>
    <p style="color:#8899a6;font-size:14px;margin:20px 0 0;line-height:1.6;">
      Le rapport PDF complet est joint en pièce jointe.
    </p>
  </div>
  <div style="background:#15202b;padding:16px 32px;border-top:1px solid #38444d;text-align:center;">
    <p style="color:#38444d;font-size:12px;margin:0;">
      EventHub Pro &nbsp;·&nbsp; ENSA Marrakech &nbsp;·&nbsp; Université Cadi Ayyad
    </p>
  </div>
</div>
HTML;
    }

    private function requireService(): void {
        require_once dirname(__DIR__, 2) . '/services/mailer.php';
    }
}
