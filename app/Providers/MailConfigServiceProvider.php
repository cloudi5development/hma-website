<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;

/**
 * Applies the admin-configured SMTP settings (Settings → Email) on top of the
 * mailer config at boot, so email is managed from the panel instead of .env.
 * When no settings are saved yet, the .env values stand — nothing breaks.
 */
class MailConfigServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $s = Setting::allCached();
        if (empty($s)) {
            return;   // no DB settings → keep whatever .env / config already has
        }

        // ---- SMTP transport ----
        if (! empty($s['mail_host'])) {
            // Encryption choice → Symfony scheme: ssl (implicit TLS, 465) = smtps;
            // tls/none rely on STARTTLS auto-negotiation on the given port.
            $scheme = ($s['mail_encryption'] ?? null) === 'ssl' ? 'smtps' : null;

            config([
                'mail.default'                => ($s['mail_mailer'] ?? '') ?: 'smtp',
                'mail.mailers.smtp.host'      => $s['mail_host'],
                'mail.mailers.smtp.port'      => (int) ($s['mail_port'] ?? 587),
                'mail.mailers.smtp.username'  => $s['mail_username'] ?? null,
                'mail.mailers.smtp.password'  => $s['mail_password'] ?? null,
                'mail.mailers.smtp.scheme'    => $scheme,
            ]);
        } elseif (! empty($s['mail_mailer'])) {
            config(['mail.default' => $s['mail_mailer']]);
        }

        // ---- From address ----
        if (! empty($s['mail_from_address'])) {
            config([
                'mail.from.address' => $s['mail_from_address'],
                'mail.from.name'    => ($s['mail_from_name'] ?? '') ?: config('app.name'),
            ]);
        }
    }
}
