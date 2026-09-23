<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('subject', 'Hire Minds Academy')</title>
    <!--[if mso]><style>table{border-collapse:collapse;}</style><![endif]-->
</head>
<body style="margin:0;padding:0;background:#f1ede8;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <!-- Preheader (hidden) -->
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">@yield('preheader', 'Thanks for reaching out to Hire Minds Academy.')</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f1ede8;">
        <tr>
            <td align="center" style="padding:28px 12px;">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
                       style="width:600px;max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(42,29,20,.10);font-family:Arial,Helvetica,sans-serif;">

                    {{-- ---------- Header / logo ----------
                         The logo is embedded as a CID attachment ($message->embed),
                         never a URL — the recipient's mail client cannot reach this
                         server's APP_URL. Falls back to a text wordmark if missing. --}}
                    @php
                        // Uploaded logo (Settings → Logo) when there is one, else the bundled asset.
                        $logoPath = public_path(\App\Models\Setting::get('site_logo') ?: 'assets/images/branding/logo.png');
                    @endphp
                    <tr>
                        <td align="center" style="background:#843D21;padding:24px 32px;">
                            @if (file_exists($logoPath))
                                <img src="{{ $message->embed($logoPath) }}" alt="Hire Minds Academy"
                                     width="170" style="display:block;max-width:170px;height:auto;border:0;outline:none;text-decoration:none;">
                            @else
                                <span style="color:#ffffff;font-size:20px;font-weight:700;font-family:Arial,Helvetica,sans-serif;">Hire Minds Academy</span>
                            @endif
                        </td>
                    </tr>

                    {{-- ---------- Gold accent bar ---------- --}}
                    <tr><td style="height:4px;background:#E9A320;line-height:4px;font-size:0;">&nbsp;</td></tr>

                    {{-- ---------- Body ---------- --}}
                    <tr>
                        <td style="padding:34px 32px 8px;color:#23180f;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- ---------- Footer ---------- --}}
                    <tr>
                        <td style="padding:26px 32px;background:#2a1d14;color:#e8ddd2;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="font-size:14px;font-weight:700;color:#ffffff;padding-bottom:10px;">Hire Minds Academy</td>
                                </tr>
                                <tr>
                                    <td style="font-size:13px;line-height:1.8;color:#c9bcae;">
                                        📞 <a href="tel:+917824094044" style="color:#e9a320;text-decoration:none;">+91 78240 94044</a><br>
                                        ✉️ <a href="mailto:info@hiremindsacademy.com" style="color:#e9a320;text-decoration:none;">info@hiremindsacademy.com</a><br>
                                        🌐 <a href="{{ config('app.url') }}" style="color:#e9a320;text-decoration:none;">{{ str_replace(['https://','http://'], '', config('app.url')) }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:14px;font-size:13px;">
                                        <a href="https://www.instagram.com/hireminds_academy/" style="color:#e9a320;text-decoration:none;font-weight:600;">Instagram</a>
                                        &nbsp;·&nbsp;
                                        <a href="{{ config('app.url') }}" style="color:#e9a320;text-decoration:none;font-weight:600;">Website</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:16px;border-top:1px solid #43342a;margin-top:14px;font-size:11px;color:#9a8b7c;">
                                        {{-- Overridable: the admin's copy of a submission sets a Reply-To
                                             that reaches the sender, so telling its reader not to reply would
                                             be wrong. Every other email keeps this wording untouched. --}}
                                        &copy; {{ now()->year }} Hire Minds Academy. @yield('footer_note', "This is an automated message — please don't reply directly.")
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>
</body>
</html>
