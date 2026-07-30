<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesTableQuery;
use App\Http\Controllers\Backend\Concerns\OptimizesImageUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\CategoryRequest;
use App\Models\Category;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use HandlesTableQuery;
    use OptimizesImageUploads;

    public function index(): View
    {
        $categories = $this->applyTableFilters(
                Category::with('department')->withCount('courses'),
                ['name', 'department.name']
            )
            ->orderBy('department_id')->orderBy('sort_order')->orderBy('id')
            ->paginate($this->perPage())->withQueryString();

        return view('backend.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('backend.categories.form', [
            'category'    => new Category(['is_active' => true, 'show_home' => true]),
            'departments' => $this->departments(),
            'allCourses'  => $this->courseOptions(),
            'selected'    => [],
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = Arr::except($request->validated(), ['courses']);
        $data['icon'] = $this->storeIcon($request);

        $category = Category::create($data);

        $this->syncCourses($category, $request->input('courses', []));

        return redirect()->route('backend.categories.index')->with('success', 'Category added.');
    }

    public function edit(Category $category): View
    {
        return view('backend.categories.form', [
            'category'    => $category,
            'departments' => $this->departments(),
            'allCourses'  => $this->courseOptions(),
            'selected'    => $category->courses()->pluck('id')->all(),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = Arr::except($request->validated(), ['courses']);

        if ($request->hasFile('icon')) {
            $this->deleteIcon($category->icon);
            $data['icon'] = $this->storeIcon($request);
        } else {
            unset($data['icon']);
        }

        $category->update($data);

        $this->syncCourses($category, $request->input('courses', []));

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

    /** Every course, with its current owner, for the picker on the form. */
    private function courseOptions()
    {
        return Course::with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'category_id', 'name', 'image']);
    }

    /**
     * Make the picked courses belong to this category and release the ones whose
     * tag was removed. A released course keeps its FAQs — it just sits unassigned
     * until another category picks it up.
     */
    private function syncCourses(Category $category, array $ids): void
    {
        $ids = array_filter(array_map('intval', $ids));

        Course::where('category_id', $category->id)
            ->when($ids, fn ($q) => $q->whereNotIn('id', $ids))
            ->update(['category_id' => null]);

        if ($ids) {
            Course::whereIn('id', $ids)->update(['category_id' => $category->id]);
        }
    }

    private function storeIcon(CategoryRequest $request): string
    {
        return $this->storeOptimizedImage($request->file('icon'), 'categories', 512);
    }

    private function deleteIcon(?string $icon): void
    {
        if ($icon && str_starts_with($icon, 'storage/')) {
            Storage::disk('public')->delete(substr($icon, strlen('storage/')));
        }
    }
}
