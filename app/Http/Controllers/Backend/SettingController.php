<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SettingController extends Controller
{
    /* ============================== GENERAL ============================== */

    public function general(): View
    {
        return view('backend.settings.general');
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name'    => ['required', 'string', 'max:120'],
            'site_tagline' => ['nullable', 'string', 'max:200'],
            'footer_about' => ['nullable', 'string', 'max:600'],
        ]);

        Setting::putMany($data);

        return back()->with('success', 'General settings saved.');
    }

    /* ============================== CONTACT ============================== */

    public function contact(): View
    {
        return view('backend.settings.contact');
    }

    public function updateContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contact_phone'             => ['nullable', 'string', 'max:40'],
            'contact_email'             => ['nullable', 'email', 'max:120'],
            'contact_address_chennai'   => ['nullable', 'string', 'max:400'],
            'contact_address_coimbatore'=> ['nullable', 'string', 'max:400'],
        ]);

        Setting::putMany($data);

        return back()->with('success', 'Contact settings saved.');
    }

    /* ============================ SOCIAL MEDIA ============================ */

    public function social(): View
    {
        return view('backend.settings.social');
    }

    public function updateSocial(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'social_facebook'  => ['nullable', 'url', 'max:255'],
            'social_instagram' => ['nullable', 'url', 'max:255'],
            'social_youtube'   => ['nullable', 'url', 'max:255'],
            'social_linkedin'  => ['nullable', 'url', 'max:255'],
            'social_x'         => ['nullable', 'url', 'max:255'],
            'social_whatsapp'  => ['nullable', 'string', 'max:255'],
        ]);

        Setting::putMany($data);

        return back()->with('success', 'Social media links saved.');
    }

    /* ============================ EMAIL / SMTP ============================ */

    public function email(): View
    {
        return view('backend.settings.email');
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mail_mailer'       => ['required', 'in:smtp,log'],
            'mail_host'         => ['nullable', 'string', 'max:190'],
            'mail_port'         => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username'     => ['nullable', 'string', 'max:190'],
            'mail_password'     => ['nullable', 'string', 'max:190'],
            'mail_encryption'   => ['nullable', 'in:tls,ssl,none'],
            'mail_from_address' => ['nullable', 'email', 'max:190'],
            'mail_from_name'    => ['nullable', 'string', 'max:120'],
        ]);

        // Keep the stored password when the field is left blank on save.
        if (blank($data['mail_password'])) {
            unset($data['mail_password']);
        }

        Setting::putMany($data);

        return back()->with('success', 'Email settings saved.');
    }

    /** Send a one-off test email using the current (just-saved) settings. */
    public function sendTestMail(Request $request): RedirectResponse
    {
        $request->validate(['test_email' => ['required', 'email']]);

        try {
            Mail::raw(
                "This is a test email from Hire Minds Academy.\n\nIf you received this, your SMTP settings are working correctly.",
                function ($m) use ($request) {
                    $m->to($request->input('test_email'))
                      ->subject('Test email — Hire Minds Academy');
                }
            );

            return back()->with('success', 'Test email sent to ' . $request->input('test_email') . '. Check the inbox (and spam).');
        } catch (\Throwable $e) {
            return back()->with('error', 'Test email failed: ' . $e->getMessage());
        }
    }

    /* ============================ SEO DEFAULTS ============================ */

    public function seo(): View
    {
        return view('backend.settings.seo');
    }

    public function updateSeo(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'seo_meta_title'       => ['nullable', 'string', 'max:180'],
            'seo_meta_description' => ['nullable', 'string', 'max:320'],
            'seo_meta_keywords'    => ['nullable', 'string', 'max:320'],
        ]);

        Setting::putMany($data);

        return back()->with('success', 'SEO settings saved.');
    }
}
