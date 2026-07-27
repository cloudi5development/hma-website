<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\DepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $departments = Department::withCount('categories')->orderBy('sort_order')->orderBy('id')->paginate(10);

        return view('backend.departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('backend.departments.form', ['department' => new Department(['is_active' => true])]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request);
        }

        Department::create($data);

        return redirect()->route('backend.departments.index')->with('success', 'Department added.');
    }

    public function edit(Department $department): View
    {
        return view('backend.departments.form', compact('department'));
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $this->deleteImage($department->image);
            $data['image'] = $this->storeImage($request);
        } else {
            unset($data['image']);
        }

        $department->update($data);

        return redirect()->route('backend.departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        // Guard: a department that still has categories can't be deleted.
        if ($department->categories()->exists()) {
            return redirect()->route('backend.departments.index')
                ->with('error', 'Cannot delete "' . $department->name . '" — it still has categories. Move or delete them first.');
        }

        $this->deleteImage($department->image);
        $department->delete();

        return redirect()->route('backend.departments.index')->with('success', 'Department deleted.');
    }

    private function storeImage(DepartmentRequest $request): string
    {
        return 'storage/' . $request->file('image')->store('departments', 'public');
    }

    private function deleteImage(?string $image): void
    {
        if ($image && str_starts_with($image, 'storage/')) {
            Storage::disk('public')->delete(substr($image, strlen('storage/')));
        }
    }
}
