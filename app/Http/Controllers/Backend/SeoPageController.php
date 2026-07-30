<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesTableQuery;
use App\Http\Controllers\Backend\Concerns\OptimizesImageUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\SeoPageRequest;
use App\Models\SeoPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Per-page SEO. Each row targets one frontend route; what is set here overrides
 * the meta written into that page's Blade file.
 */
class SeoPageController extends Controller
{
    use HandlesTableQuery;
    use OptimizesImageUploads;

    /** Upload fields on the form, handled identically on store/update/destroy. */
    private const IMAGE_FIELDS = ['og_image', 'twitter_image'];

    public function index(): View
    {
        $pages = $this->applyTableFilters(SeoPage::query(), ['label', 'key', 'title'])
            ->orderBy('label')
            ->paginate($this->perPage())->withQueryString();

        return view('backend.seo-pages.index', compact('pages'));
    }

    public function create(): View
    {
        return view('backend.seo-pages.form', [
            'seoPage'    => new SeoPage(['is_active' => true, 'meta_robots' => 'index, follow']),
            'routeNames' => $this->frontendRoutes(),
        ]);
    }

    public function store(SeoPageRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach (self::IMAGE_FIELDS as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $this->storeImage($request, $field);
            } else {
                unset($data[$field]);
            }
        }

        SeoPage::create($data);
        SeoPage::flushCache();

        return redirect()->route('backend.seo-pages.index')->with('success', 'Page SEO added.');
    }

    public function edit(SeoPage $seoPage): View
    {
        return view('backend.seo-pages.form', [
            'seoPage'    => $seoPage,
            'routeNames' => $this->frontendRoutes(),
        ]);
    }

    public function update(SeoPageRequest $request, SeoPage $seoPage): RedirectResponse
    {
        $data = $request->validated();

        foreach (self::IMAGE_FIELDS as $field) {
            if ($request->hasFile($field)) {
                $this->deleteImage($seoPage->{$field});
                $data[$field] = $this->storeImage($request, $field);
            } else {
                unset($data[$field]);
            }
        }

        $seoPage->update($data);
        SeoPage::flushCache();

        return redirect()->route('backend.seo-pages.index')->with('success', 'Page SEO updated.');
    }

    public function destroy(SeoPage $seoPage): RedirectResponse
    {
        foreach (self::IMAGE_FIELDS as $field) {
            $this->deleteImage($seoPage->{$field});
        }

        $seoPage->delete();
        SeoPage::flushCache();

        return redirect()->route('backend.seo-pages.index')
            ->with('success', 'Page SEO deleted — that page falls back to the meta in its template.');
    }

    /**
     * Frontend route names offered in the key dropdown, minus the ones whose
     * meta already comes from the record being viewed (course/blog details).
     */
    private function frontendRoutes(): array
    {
        return collect(Route::getRoutes()->getRoutesByName())
            ->keys()
            ->filter(fn ($name) => str_starts_with($name, 'frontend.'))
            ->map(fn ($name) => substr($name, strlen('frontend.')))
            ->reject(fn ($name) => str_contains($name, '.') || str_ends_with($name, '-details'))
            ->sort()
            ->values()
            ->all();
    }

    private function storeImage(SeoPageRequest $request, string $field): string
    {
        return $this->storeOptimizedImage($request->file($field), 'seo', 1200);
    }

    private function deleteImage(?string $image): void
    {
        if ($image && str_starts_with($image, 'storage/')) {
            Storage::disk('public')->delete(substr($image, strlen('storage/')));
        }
    }
}
