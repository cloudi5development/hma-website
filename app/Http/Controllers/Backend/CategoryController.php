<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\CategoryRequest;
use App\Models\Category;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::with('department')
            ->withCount('courses')
            ->orderBy('department_id')->orderBy('sort_order')->orderBy('id')
            ->paginate(10);

        return view('backend.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('backend.categories.form', [
            'category'    => new Category(['is_active' => true, 'show_home' => true]),
            'departments' => $this->departments(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['icon'] = $this->storeIcon($request);

        Category::create($data);

        return redirect()->route('backend.categories.index')->with('success', 'Category added.');
    }

    public function edit(Category $category): View
    {
        return view('backend.categories.form', [
            'category'    => $category,
            'departments' => $this->departments(),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('icon')) {
            $this->deleteIcon($category->icon);
            $data['icon'] = $this->storeIcon($request);
        } else {
            unset($data['icon']);
        }

        $category->update($data);

        return redirect()->route('backend.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        // Guard: don't cascade-delete a category's courses by accident.
        if ($category->courses()->exists()) {
            return redirect()->route('backend.categories.index')
                ->with('error', 'Cannot delete "' . $category->name . '" — it still has courses. Move or delete them first.');
        }

        $this->deleteIcon($category->icon);
        $category->delete();

        return redirect()->route('backend.categories.index')->with('success', 'Category deleted.');
    }

    /** Active departments for the select dropdown. */
    private function departments()
    {
        return Department::orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
    }

    private function storeIcon(CategoryRequest $request): string
    {
        return 'storage/' . $request->file('icon')->store('categories', 'public');
    }

    private function deleteIcon(?string $icon): void
    {
        if ($icon && str_starts_with($icon, 'storage/')) {
            Storage::disk('public')->delete(substr($icon, strlen('storage/')));
        }
    }
}
