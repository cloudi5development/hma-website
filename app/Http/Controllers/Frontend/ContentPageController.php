<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ContentPage;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The written pages managed under admin → Content Management.
 *
 * One action for both: the route binds the key, so adding another page is a
 * ContentPage::PAGES entry, a route line and a row — no new controller.
 */
class ContentPageController extends Controller
{
    public function show(string $key): View
    {
        $page = ContentPage::active()->where('key', $key)->first();

        // Switched off, or a key that was never seeded — either way there is
        // nothing to show, and a blank page would look broken.
        if (! $page || ! array_key_exists($key, ContentPage::PAGES)) {
            throw new NotFoundHttpException();
        }

        // The body with an id on each heading, so a clause can be linked to
        // directly. The section list the same pass produces is unused here.
        ['html' => $body] = $page->renderedContent();

        return view('frontend.content-page', compact('page', 'body'));
    }
}
