<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesTableQuery;
use App\Http\Controllers\Backend\Concerns\OptimizesImageUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\BlogRequest;
use App\Models\Blog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BlogController extends Controller
{
    use HandlesTableQuery;
    use OptimizesImageUploads;

    public function index(): View
    {
        $blogs = $this->applyTableFilters(Blog::query(), ['title', 'author', 'category'])
            ->orderBy('sort_order')->orderBy('id')
            ->paginate($this->perPage())->withQueryString();

        return view('backend.blogs.index', compact('blogs'));
    }

    public function create(): View
    {
        return view('backend.blogs.form', [
            'blog' => new Blog(['is_active' => true, 'show_home' => true, 'is_latest' => true]),
        ]);
    }

    public function store(BlogRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['image'] = $this->storeImage($request);

        Blog::create($data);

        return redirect()->route('backend.blogs.index')->with('success', 'Blog post added.');
    }

    public function edit(Blog $blog): View
    {
        return view('backend.blogs.form', compact('blog'));
    }

    public function update(BlogRequest $request, Blog $blog): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $this->deleteImage($blog->image);
            $data['image'] = $this->storeImage($request);
        } else {
            unset($data['image']);   // keep the existing one
        }

        $blog->update($data);

        return redirect()->route('backend.blogs.index')->with('success', 'Blog post updated.');
    }

    public function destroy(Blog $blog): RedirectResponse
    {
        $this->deleteImage($blog->image);
        $blog->delete();

        return redirect()->route('backend.blogs.index')->with('success', 'Blog post deleted.');
    }

    /** Store the uploaded image on the public disk; return a /public-relative path. */
    private function storeImage(BlogRequest $request): string
    {
        return $this->storeOptimizedImage($request->file('image'), 'blogs');
    }

    /** Delete a previously-uploaded image, but never the seeded asset files. */
    private function deleteImage(?string $image): void
    {
        if ($image && str_starts_with($image, 'storage/')) {
            Storage::disk('public')->delete(substr($image, strlen('storage/')));
        }
    }
}
