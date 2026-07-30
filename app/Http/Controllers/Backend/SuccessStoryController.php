<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesTableQuery;
use App\Http\Controllers\Backend\Concerns\OptimizesImageUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\SuccessStoryRequest;
use App\Models\SuccessStory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SuccessStoryController extends Controller
{
    use HandlesTableQuery;
    use OptimizesImageUploads;

    public function index(): View
    {
        $stories = $this->applyTableFilters(SuccessStory::query(), ['name', 'role', 'salary'])
            ->orderBy('sort_order')->orderBy('id')
            ->paginate($this->perPage())->withQueryString();

        return view('backend.success-stories.index', compact('stories'));
    }

    public function create(): View
    {
        return view('backend.success-stories.form', [
            'story' => new SuccessStory(['is_active' => true, 'show_home' => true, 'tone' => 'olive']),
        ]);
    }

    public function store(SuccessStoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['image'] = $this->storeImage($request);

        SuccessStory::create($data);

        return redirect()->route('backend.success-stories.index')->with('success', 'Success story added.');
    }

    public function edit(SuccessStory $successStory): View
    {
        return view('backend.success-stories.form', ['story' => $successStory]);
    }

    public function update(SuccessStoryRequest $request, SuccessStory $successStory): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $this->deleteImage($successStory->image);
            $data['image'] = $this->storeImage($request);
        } else {
            unset($data['image']);   // keep the existing one
        }

        $successStory->update($data);

        return redirect()->route('backend.success-stories.index')->with('success', 'Success story updated.');
    }

    public function destroy(SuccessStory $successStory): RedirectResponse
    {
        $this->deleteImage($successStory->image);
        $successStory->delete();

        return redirect()->route('backend.success-stories.index')->with('success', 'Success story deleted.');
    }

    /** Store the uploaded image on the public disk; return a /public-relative path. */
    private function storeImage(SuccessStoryRequest $request): string
    {
        return $this->storeOptimizedImage($request->file('image'), 'success-stories');
    }

    /** Delete a previously-uploaded image, but never the seeded asset files. */
    private function deleteImage(?string $image): void
    {
        if ($image && str_starts_with($image, 'storage/')) {
            Storage::disk('public')->delete(substr($image, strlen('storage/')));
        }
    }
}
