<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesTableQuery;
use App\Http\Controllers\Backend\Concerns\OptimizesImageUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\TestimonialRequest;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    use HandlesTableQuery;
    use OptimizesImageUploads;

    public function index(): View
    {
        $testimonials = $this->applyTableFilters(Testimonial::query(), ['name', 'role', 'company'])
            ->orderBy('sort_order')->orderBy('id')
            ->paginate($this->perPage())->withQueryString();

        return view('backend.testimonials.index', compact('testimonials'));
    }

    public function create(): View
    {
        return view('backend.testimonials.form', [
            'testimonial' => new Testimonial(['is_active' => true, 'show_home' => true, 'rating' => 5]),
        ]);
    }

    public function store(TestimonialRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['photo'] = $this->storePhoto($request);

        Testimonial::create($data);

        return redirect()->route('backend.testimonials.index')->with('success', 'Testimonial added.');
    }

    public function edit(Testimonial $testimonial): View
    {
        return view('backend.testimonials.form', compact('testimonial'));
    }

    public function update(TestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $this->deletePhoto($testimonial->photo);
            $data['photo'] = $this->storePhoto($request);
        } else {
            unset($data['photo']);   // keep the existing one
        }

        $testimonial->update($data);

        return redirect()->route('backend.testimonials.index')->with('success', 'Testimonial updated.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $this->deletePhoto($testimonial->photo);
        $testimonial->delete();

        return redirect()->route('backend.testimonials.index')->with('success', 'Testimonial deleted.');
    }

    /** Store the uploaded photo on the public disk; return a /public-relative path. */
    private function storePhoto(TestimonialRequest $request): string
    {
        return $this->storeOptimizedImage($request->file('photo'), 'testimonials', 400);
    }

    /** Delete a previously-uploaded photo, but never the seeded asset files. */
    private function deletePhoto(?string $photo): void
    {
        if ($photo && str_starts_with($photo, 'storage/')) {
            Storage::disk('public')->delete(substr($photo, strlen('storage/')));
        }
    }
}
