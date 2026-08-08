<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\OptimizesImageUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\HeroRequest;
use App\Models\Hero;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * The hero is a singleton — there is exactly one row and it is never created or
 * deleted from the panel, only viewed and edited. So this controller exposes an
 * overview (index) and an edit/update pair, nothing else.
 */
class HeroController extends Controller
{
    use OptimizesImageUploads;

    public function index(): View
    {
        return view('backend.hero.index', ['hero' => Hero::current()]);
    }

    public function edit(): View
    {
        return view('backend.hero.edit', ['hero' => Hero::current()]);
    }

    public function update(HeroRequest $request): RedirectResponse
    {
        $hero = Hero::current();
        $data = $request->validated();

        // A new upload replaces the old file; ticking "remove" clears it, which
        // leaves the hero's yellow backdrop standing on its own. Doing neither
        // keeps the current photo.
        if ($request->hasFile('image')) {
            $this->deleteImage($hero->image);
            $data['image'] = $this->storeImage($request);
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($hero->image);
            $data['image'] = null;
        } else {
            unset($data['image']);
        }

        // current() may hand back an unsaved fallback instance if the seeder has
        // never run; save() persists it, update() would no-op on a new model.
        $hero->fill($data)->save();

        return redirect()->route('backend.hero.index')->with('success', 'Hero section updated.');
    }

    /** Store the uploaded image on the public disk; return a /public-relative path. */
    private function storeImage(HeroRequest $request): string
    {
        return $this->storeOptimizedImage($request->file('image'), 'hero');
    }

    /** Delete a previously-uploaded image, but never the seeded asset files. */
    private function deleteImage(?string $image): void
    {
        if ($image && str_starts_with($image, 'storage/')) {
            Storage::disk('public')->delete(substr($image, strlen('storage/')));
        }
    }
}
