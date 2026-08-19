<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\OptimizesImageUploads;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    use OptimizesImageUploads;

    /* ============================== GENERAL ============================== */

    public function general(): View
    {
        return view('backend.settings.general');
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name'      => ['required', 'string', 'max:120'],
            'site_tagline'   => ['nullable', 'string', 'max:200'],
            'footer_about'   => ['nullable', 'string', 'max:600'],
            'copyright_text' => ['nullable', 'string', 'max:200'],
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
            'contact_phone'        => ['nullable', 'string', 'max:40'],
            'contact_email'        => ['nullable', 'email', 'max:120'],
            'branches'             => ['nullable', 'array', 'max:12'],
            'branches.*.name'      => ['nullable', 'string', 'max:80'],
            'branches.*.address'   => ['nullable', 'string', 'max:400'],
            'branches.*.map'       => ['nullable', 'string', 'max:2000'],
        ], [
            'branches.max' => 'You can add up to 12 branches.',
        ]);

        $branches = [];

        foreach ($request->input('branches', []) as $row) {
            $name    = trim((string) ($row['name'] ?? ''));
            $address = trim((string) ($row['address'] ?? ''));

            // A row with neither name nor address is a leftover blank — drop it.
            if ($name === '' && $address === '') {
                continue;
            }

            if ($name === '' || $address === '') {
                return back()->withInput()
                    ->with('error', 'Every branch needs both a name and an address.');
            }

            $branches[] = [
                'name'    => $name,
                'address' => $address,
                // Accepts the whole <iframe> snippet or a bare URL; anything that
                // is not a Google Maps link is dropped and the address is used.
                'map'     => Setting::normaliseMapUrl($row['map'] ?? null),
            ];
        }

        Setting::putMany([
            'contact_phone'    => $data['contact_phone'] ?? null,
            'contact_email'    => $data['contact_email'] ?? null,
            'contact_branches' => json_encode($branches, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

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

    /* ============================ LOGO / FAVICON ============================ */

    public function logo(): View
    {
        return view('backend.settings.logo');
    }

    /**
     * Site logo and favicon. Each field is optional on its own — uploading one
     * leaves the other alone — and either can be reset to the bundled default.
     */
    public function updateLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'site_logo'    => ['nullable', 'image', 'mimes:webp,png,jpg,jpeg,svg', 'max:2048'],
            'site_favicon' => ['nullable', 'image', 'mimes:png,ico,webp,jpg,jpeg,svg', 'max:1024'],
        ], [
            'site_logo.max'    => 'The logo must be 2 MB or smaller.',
            'site_favicon.max' => 'The favicon must be 1 MB or smaller.',
        ]);

        $changes = [];

        foreach (['site_logo', 'site_favicon'] as $key) {
            if ($request->boolean('remove_' . $key)) {
                $this->deleteUpload(Setting::get($key));
                $changes[$key] = null;

                continue;
            }

            if ($request->hasFile($key)) {
                $this->deleteUpload(Setting::get($key));

                // The logo is downscaled and re-encoded; the favicon is stored as
                // uploaded, because it may legitimately be an .ico and the <link>
                // tags in the layout declare its type.
                $changes[$key] = $key === 'site_logo'
                    ? $this->storeOptimizedImage($request->file($key), 'branding', 600)
                    : 'storage/' . $request->file($key)->store('branding', 'public');
            }
        }

        if (! $changes) {
            return back()->with('error', 'Nothing to save — choose a file or tick “remove”.');
        }

        Setting::putMany($changes);

        return back()->with('success', 'Logo settings saved.');
    }

    /** Remove a previously uploaded branding file (never the bundled defaults). */
    private function deleteUpload(?string $path): void
    {
        if ($path && str_starts_with($path, 'storage/')) {
            Storage::disk('public')->delete(substr($path, strlen('storage/')));
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
            'seo_meta_title'                => ['nullable', 'string', 'max:180'],
            'seo_meta_description'          => ['nullable', 'string', 'max:320'],
            'seo_meta_keywords'             => ['nullable', 'string', 'max:320'],
            'seo_meta_robots'               => ['nullable', 'string', 'max:80'],
            'seo_default_og_image'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:2048'],
            'seo_google_site_verification'  => ['nullable', 'string', 'max:255'],
            'seo_bing_site_verification'    => ['nullable', 'string', 'max:255'],
            'seo_google_analytics_id'       => ['nullable', 'string', 'max:120'],
            'seo_google_tag_manager_id'     => ['nullable', 'string', 'max:120'],
        ]);

        if ($request->hasFile('seo_default_og_image')) {
            $this->deleteUpload(Setting::get('seo_default_og_image'));
            // 1200px is the Open Graph reference width, so nothing larger is useful.
            $data['seo_default_og_image'] = $this->storeOptimizedImage($request->file('seo_default_og_image'), 'seo', 1200);
        }

        Setting::putMany($data);

        return back()->with('success', 'SEO settings saved.');
    }
}
