<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesTableQuery;
use App\Http\Controllers\Backend\Concerns\OptimizesImageUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\CourseRequest;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CourseController extends Controller
{
    use HandlesTableQuery;
    use OptimizesImageUploads;

    public function index(): View
    {
        $courses = $this->applyTableFilters(
                Course::with('category.department'),
                ['name', 'duration', 'skill_level', 'category.name']
            )
            ->orderBy('sort_order')->orderBy('id')
            ->paginate($this->perPage())->withQueryString();

        $popularCount = Course::popular()->count();

        return view('backend.courses.index', compact('courses', 'popularCount'));
    }

    public function create(): View
    {
        return view('backend.courses.form', [
            'course'     => new Course(['is_active' => true, 'rating' => 4.5]),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        $data = $this->clean($request);
        $data['image'] = $this->storeImage($request);

        if ($request->hasFile('brochure')) {
            $data['brochure'] = $this->storeBrochure($request);
        }

        $course = Course::create($data);
        $this->syncFaqs($course, $request->input('faqs', []));

        return redirect()->route('backend.courses.index')->with('success', 'Course added.');
    }

    public function edit(Course $course): View
    {
        $course->load('faqs');

        return view('backend.courses.form', [
            'course'     => $course,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        $data = $this->clean($request);

        if ($request->hasFile('image')) {
            $this->deleteImage($course->image);
            $data['image'] = $this->storeImage($request);
        }

        // A new upload replaces the old file; ticking "remove" clears it. Doing
        // neither leaves the existing brochure alone.
        if ($request->hasFile('brochure')) {
            $this->deleteUpload($course->brochure);
            $data['brochure'] = $this->storeBrochure($request);
        } elseif ($request->boolean('remove_brochure')) {
            $this->deleteUpload($course->brochure);
            $data['brochure'] = null;
        }

        $course->update($data);
        $this->syncFaqs($course, $request->input('faqs', []));

        return redirect()->route('backend.courses.index')->with('success', 'Course updated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->deleteImage($course->image);
        $this->deleteUpload($course->brochure);
        $course->delete();   // FAQs and schedules cascade via their FKs

        return redirect()->route('backend.courses.index')->with('success', 'Course deleted.');
    }

    /**
     * Validated payload minus the fields handled separately: the FAQ repeater, and
     * the two file inputs (whose column values are computed, not posted).
     */
    private function clean(CourseRequest $request): array
    {
        return collect($request->validated())
            ->except(['faqs', 'image', 'brochure', 'remove_brochure'])
            ->all();
    }

    /** Replace the course's FAQs with the submitted rows (blank rows dropped, max 5). */
    private function syncFaqs(Course $course, array $rows): void
    {
        $course->faqs()->delete();

        $order = 0;
        foreach (array_slice($rows, 0, Course::MAX_FAQS) as $row) {
            $q = trim($row['question'] ?? '');
            $a = trim($row['answer'] ?? '');
            if ($q === '' || $a === '') {
                continue;
            }
            $course->faqs()->create(['question' => $q, 'answer' => $a, 'sort_order' => $order++]);
        }
    }

    /** Categories grouped by department for the optgroup select. */
    private function categoryOptions()
    {
        return Category::with('department')
            ->orderBy('department_id')->orderBy('sort_order')->orderBy('name')
            ->get()
            ->groupBy(fn ($c) => $c->department?->name ?? 'Other');
    }

    private function storeImage(CourseRequest $request): string
    {
        return $this->storeOptimizedImage($request->file('image'), 'courses');
    }

    /**
     * Store the brochure PDF and return its /public-relative path. Kept in its own
     * directory so brochures are never mixed in with the course thumbnails.
     */
    private function storeBrochure(CourseRequest $request): string
    {
        return 'storage/' . $request->file('brochure')->store('courses/brochures', 'public');
    }

    private function deleteImage(?string $image): void
    {
        $this->deleteUpload($image);
    }

    /** Remove a previously-uploaded file, but never the seeded asset files. */
    private function deleteUpload(?string $path): void
    {
        if ($path && str_starts_with($path, 'storage/')) {
            Storage::disk('public')->delete(substr($path, strlen('storage/')));
        }
    }
}
