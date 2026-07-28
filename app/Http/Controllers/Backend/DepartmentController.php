<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesTableQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\DepartmentRequest;
use App\Models\Category;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    use HandlesTableQuery;

    public function index(): View
    {
        $departments = $this->applyTableFilters(Department::withCount('categories'), ['name'])
            ->orderBy('sort_order')->orderBy('id')
            ->paginate($this->perPage())->withQueryString();

        return view('backend.departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('backend.departments.form', [
            'department'    => new Department(['is_active' => true]),
            'allCategories' => $this->categoryOptions(),
            'selected'      => [],
        ]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $department = Department::create(Arr::except($request->validated(), ['categories']));

        $this->syncCategories($department, $request->input('categories', []));

        return redirect()->route('backend.departments.index')->with('success', 'Department added.');
    }

    public function edit(Department $department): View
    {
        return view('backend.departments.form', [
            'department'    => $department,
            'allCategories' => $this->categoryOptions(),
            'selected'      => $department->categories()->pluck('id')->all(),
        ]);
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update(Arr::except($request->validated(), ['categories']));

        $this->syncCategories($department, $request->input('categories', []));

        return redirect()->route('backend.departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        // Guard: a department that still has categories can't be deleted.
        if ($department->categories()->exists()) {
            return redirect()->route('backend.departments.index')
                ->with('error', 'Cannot delete "' . $department->name . '" — it still has categories. Move or detach them first.');
        }

        $this->deleteImage($department->image);
        $department->delete();

        return redirect()->route('backend.departments.index')->with('success', 'Department deleted.');
    }

    /** Every category, with its current owner, for the picker on the form. */
    private function categoryOptions()
    {
        return Category::with('department:id,name')
            ->orderBy('name')
            ->get(['id', 'department_id', 'name', 'icon']);
    }

    /**
     * Make the ticked categories belong to this department and release the ones
     * that were unticked. A released category keeps its courses — it just sits
     * unassigned until it is picked up by another department.
     */
    private function syncCategories(Department $department, array $ids): void
    {
        $ids = array_filter(array_map('intval', $ids));

        Category::where('department_id', $department->id)
            ->when($ids, fn ($q) => $q->whereNotIn('id', $ids))
            ->update(['department_id' => null]);

        if ($ids) {
            Category::whereIn('id', $ids)->update(['department_id' => $department->id]);
        }
    }

    private function deleteImage(?string $image): void
    {
        if ($image && str_starts_with($image, 'storage/')) {
            Storage::disk('public')->delete(substr($image, strlen('storage/')));
        }
    }
}
