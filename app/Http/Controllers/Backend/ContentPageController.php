<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ContentPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Content Management — editing the fixed set of written pages.
 *
 * Edit and update only: the rows are created by the migration and the routes
 * are hard-coded, so creating or deleting one here would either orphan a record
 * or break a live footer link.
 */
class ContentPageController extends Controller
{
    public function edit(string $key): View
    {
        return view('backend.content-pages.form', [
            'page'  => $this->page($key),
            'pages' => ContentPage::whereIn('key', array_keys(ContentPage::PAGES))->get(),
        ]);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $page = $this->page($key);

        $data = $request->validate([
            'title'           => ['required', 'string', 'max:150'],
            // Long: a privacy policy runs to thousands of words.
            'content'         => ['nullable', 'string', 'max:200000'],
            'seo_title'       => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'seo_keywords'    => ['nullable', 'string', 'max:255'],
            'is_active'       => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $page->update($data);

        ActivityLog::record('Content Page Updated', "Updated the {$page->title} page");

        return back()->with('success', $page->title . ' saved.');
    }

    /** The page for this key, or a 404 — the key comes off a fixed route. */
    private function page(string $key): ContentPage
    {
        return ContentPage::forKey($key) ?? throw new NotFoundHttpException();
    }
}
