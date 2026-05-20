<?php
namespace App\Controllers;

class PdfController {

    private $tempDir;

    public function __construct() {
        $this->tempDir = dirname(__DIR__, 2) . '/storage/tmp/';
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    public function generateTicket(array $regData, array $event): string {
        $this->requireService();

        $html = $this->buildTicketHTML($regData, $event);
        $path = $this->tempDir . 'ticket_' . preg_replace('/[^a-f0-9]/i', '', $regData['token']) . '.pdf';

        generatePDF($html, $path, 'F');

        return file_exists($path) ? $path : '';
    }

    public function generateOrganizerReport(array $event, int $registeredCount): string {
        $this->requireService();

        $html = $this->buildReportHTML($event, $registeredCount);
        $path = $this->tempDir . 'report_event' . (int)$event['id'] . '_' . time() . '.pdf';

        generatePDF($html, $path, 'F');

        return file_exists($path) ? $path : '';
    }

    public function cleanup(string $path): void {
        if ($path && file_exists($path)) {
            @unlink($path);
        }
    }

    private function buildTicketHTML(array $reg, array $event): string {
        $date      = date('d/m/Y \à H:i', strtotime($event['date']));
        $capacity  = (int) $event['capacity'];
        $regCount  = (int) $reg['registered_count'];
        $fillPct   = $capacity > 0 ? min(round($regCount / $capacity * 100), 100) : 0;
        $barColor  = $fillPct >= 100 ? '#ef4444' : ($fillPct >= 80 ? '#f59e0b' : '#1d9bf0');
        $catLabel  = ucfirst($event['category'] ?? '');

        $qrData   = urlencode('eventhub|event=' . (int)$event['id'] . '|token=' . $reg['token']);
        $qrUrl    = "https://api.qrserver.com/v1/create-qr-code/?size=130x130&data={$qrData}&bgcolor=FFFFFF&color=0f172a&margin=6";

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; background: #f0f4f8; }
  .ticket { max-width: 560px; margin: 20px auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
  .hdr { background: #1d9bf0; color: #fff; padding: 26px 30px; }
  .hdr-title { font-size: 21px; font-weight: 900; letter-spacing: -0.5px; }
  .hdr-sub   { font-size: 12px; opacity: 0.85; margin-top: 4px; }
  .body { padding: 26px 30px; }
  .event-name { font-size: 19px; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
  .event-cat  { display: inline-block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; background: #dbeafe; color: #1d4ed8; padding: 2px 8px; border-radius: 99px; margin-bottom: 18px; }
  .grid { width: 100%; border-collapse: collapse; }
  .col-info { width: 62%; vertical-align: top; padding-right: 16px; }
  .col-qr   { width: 38%; vertical-align: middle; text-align: center; }
  .info-label { font-size: 10px; text-transform: uppercase; letter-spacing: .07em; color: #64748b; font-weight: 700; margin-bottom: 2px; }
  .info-value { font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 12px; }
  .divider { border: none; border-top: 2px dashed #e2e8f0; margin: 18px 0; }
  .bar-row   { display: flex; justify-content: space-between; font-size: 11px; color: #64748b; margin-bottom: 5px; }
  .bar-bg    { background: #e2e8f0; border-radius: 3px; height: 7px; }
  .bar-fill  { height: 7px; border-radius: 3px; }
  .footer { background: #f8fafc; padding: 12px 30px; border-top: 1px solid #e2e8f0; text-align: center; }
  .token  { font-family: "Courier New", monospace; font-size: 10px; color: #94a3b8; word-break: break-all; }
</style>
</head>
<body>
<div class="ticket">
  <div class="hdr">
    <div class="hdr-title">EventHub Pro — Ticket de Participation</div>
    <div class="hdr-sub">ENSA Marrakech · Université Cadi Ayyad</div>
  </div>
  <div class="body">
    <div class="event-name">{$event['title']}</div>
    <span class="event-cat">{$catLabel}</span>
    <table class="grid">
      <tr>
        <td class="col-info">
          <div class="info-label">📅 Date</div>
          <div class="info-value">{$date}</div>
          <div class="info-label">📍 Lieu</div>
          <div class="info-value">{$event['location']}</div>
          <div class="info-label">👤 Participant</div>
          <div class="info-value">{$reg['user_name']}</div>
          <div class="info-label">✉️ Email</div>
          <div class="info-value">{$reg['user_email']}</div>
        </td>
        <td class="col-qr">
          <img src="{$qrUrl}" width="120" height="120" alt="QR Code" style="border-radius:8px;">
        </td>
      </tr>
    </table>
    <hr class="divider">
    <div class="bar-row">
      <span>Taux de remplissage</span>
      <strong style="color:{$barColor}">{$regCount} / {$capacity} ({$fillPct}%)</strong>
    </div>
    <div class="bar-bg">
      <div class="bar-fill" style="width:{$fillPct}%;background:{$barColor}"></div>
    </div>
  </div>
  <div class="footer">
    <div class="token">Token : {$reg['token']}</div>
  </div>
</div>
</body>
</html>
HTML;
    }

    private function buildReportHTML(array $event, int $registeredCount): string {
        $date      = date('d/m/Y \à H:i', strtotime($event['date']));
        $capacity  = (int) $event['capacity'];
        $fillPct   = $capacity > 0 ? min(round($registeredCount / $capacity * 100), 100) : 0;
        $remaining = max($capacity - $registeredCount, 0);
        $genDate   = date('d/m/Y H:i:s');
        $barColor  = $fillPct >= 100 ? '#ef4444' : ($fillPct >= 80 ? '#f59e0b' : '#22c55e');

        $filledCols = (int) round($fillPct / 10);
        $emptyCols  = 10 - $filledCols;
        $chartCells = '';
        for ($i = 0; $i < $filledCols; $i++) {
            $chartCells .= "<td style='background:{$barColor};height:24px;'></td>";
        }
        for ($i = 0; $i < $emptyCols; $i++) {
            $chartCells .= "<td style='background:#e2e8f0;height:24px;'></td>";
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; background: #f0f4f8; }
  .report { max-width: 560px; margin: 20px auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
  .hdr { background: #f59e0b; color: #fff; padding: 26px 30px; }
  .hdr-title { font-size: 20px; font-weight: 900; }
  .hdr-sub   { font-size: 12px; opacity: 0.9; margin-top: 4px; }
  .body { padding: 26px 30px; }
  .section-lbl { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: #64748b; margin-bottom: 10px; }
  table.info { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
  table.info td { padding: 9px 12px; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
  table.info td:first-child { color: #64748b; width: 42%; }
  table.info td:last-child  { font-weight: 700; color: #1e293b; }
  table.chart { width: 100%; border-collapse: separate; border-spacing: 2px; margin-bottom: 6px; }
  .bar-labels { display: flex; justify-content: space-between; font-size: 11px; color: #64748b; margin-top: 4px; }
  .alert-box { background: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px; padding: 14px 16px; margin-top: 22px; }
  .alert-box p { font-size: 13px; color: #92400e; line-height: 1.55; }
  .footer { background: #f8fafc; padding: 12px 30px; border-top: 1px solid #e2e8f0; text-align: center; }
  .footer p { font-size: 11px; color: #94a3b8; }
</style>
</head>
<body>
<div class="report">
  <div class="hdr">
    <div class="hdr-title">⚠️ Rapport d'Alerte — Capacité à {$fillPct}%</div>
    <div class="hdr-sub">Généré le {$genDate}</div>
  </div>
  <div class="body">
    <p class="section-lbl">Détails de l'événement</p>
    <table class="info">
      <tr><td>📌 Titre</td>      <td>{$event['title']}</td></tr>
      <tr><td>📅 Date</td>       <td>{$date}</td></tr>
      <tr><td>📍 Lieu</td>       <td>{$event['location']}</td></tr>
      <tr><td>🏷️ Catégorie</td>  <td>{$event['category']}</td></tr>
      <tr><td>✉️ Organisateur</td><td>{$event['organizer_email']}</td></tr>
    </table>
    <p class="section-lbl">Statistiques</p>
    <table class="info">
      <tr><td>👥 Inscrits</td>          <td style="color:{$barColor}">{$registeredCount}</td></tr>
      <tr><td>🪑 Capacité totale</td>   <td>{$capacity}</td></tr>
      <tr><td>📊 Taux de remplissage</td><td style="color:{$barColor};font-size:18px;font-weight:900">{$fillPct}%</td></tr>
    </table>
    <p class="section-lbl">Graphique</p>
    <table class="chart"><tr>{$chartCells}</tr></table>
    <div class="bar-labels"><span>0%</span><span style="color:{$barColor};font-weight:700">{$fillPct}%</span><span>100%</span></div>
    <div class="alert-box">
      <p>
        L'événement <strong>{$event['title']}</strong> a atteint <strong>{$fillPct}%</strong> de sa capacité.
      </p>
    </div>
  </div>
  <div class="footer">
    <p>EventHub Pro · ENSA Marrakech · {$genDate}</p>
  </div>
</div>
</body>
</html>
HTML;
    }

    private function requireService(): void {
        require_once dirname(__DIR__, 2) . '/services/pdf_service.php';
    }
}
