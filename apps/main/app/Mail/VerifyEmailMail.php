<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $name,
        public readonly string $verifyUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verifikasi Email Anda — Amartha eTicket');
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->buildHtml());
    }

    private function buildHtml(): string
    {
        $name     = htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8');
        $url      = htmlspecialchars($this->verifyUrl, ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="id">
        <head>
          <meta charset="UTF-8" />
          <meta name="viewport" content="width=device-width, initial-scale=1.0" />
          <title>Verifikasi Email</title>
        </head>
        <body style="margin:0;padding:0;background:#f4f4f5;font-family:'Segoe UI',Arial,sans-serif;">
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:40px 0;">
            <tr>
              <td align="center">
                <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.08);">

                  <!-- Header -->
                  <tr>
                    <td style="background:#059669;padding:32px 40px;text-align:center;">
                      <p style="margin:0;font-size:22px;font-weight:700;color:#ffffff;letter-spacing:-.3px;">🎟 Amartha eTicket</p>
                    </td>
                  </tr>

                  <!-- Body -->
                  <tr>
                    <td style="padding:40px 40px 32px;">
                      <p style="margin:0 0 8px;font-size:20px;font-weight:600;color:#111827;">Halo, {$name}!</p>
                      <p style="margin:0 0 24px;font-size:15px;color:#4b5563;line-height:1.6;">
                        Terima kasih sudah mendaftar. Klik tombol di bawah untuk memverifikasi email Anda dan mengaktifkan akun.
                      </p>

                      <table cellpadding="0" cellspacing="0" style="margin:0 0 28px;">
                        <tr>
                          <td style="background:#059669;border-radius:10px;">
                            <a href="{$url}" style="display:inline-block;padding:14px 32px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:10px;">
                              Verifikasi Email Saya
                            </a>
                          </td>
                        </tr>
                      </table>

                      <p style="margin:0 0 8px;font-size:13px;color:#6b7280;">
                        Jika tombol di atas tidak berfungsi, salin dan tempelkan URL berikut ke browser Anda:
                      </p>
                      <p style="margin:0 0 24px;font-size:12px;color:#059669;word-break:break-all;">
                        <a href="{$url}" style="color:#059669;">{$url}</a>
                      </p>

                      <p style="margin:0;font-size:13px;color:#9ca3af;line-height:1.5;">
                        Link verifikasi berlaku selama <strong>24 jam</strong>. Jika Anda tidak mendaftar, abaikan email ini.
                      </p>
                    </td>
                  </tr>

                  <!-- Footer -->
                  <tr>
                    <td style="padding:20px 40px;border-top:1px solid #f3f4f6;text-align:center;">
                      <p style="margin:0;font-size:12px;color:#9ca3af;">© 2026 Amartha eTicket. Semua hak dilindungi.</p>
                    </td>
                  </tr>

                </table>
              </td>
            </tr>
          </table>
        </body>
        </html>
        HTML;
    }
}
