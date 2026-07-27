<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\CourseRequest;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        $courses = Course::with('category.department')
            ->orderBy('sort_order')->orderBy('id')
            ->paginate(10);

        $popularCount = Course::popular()->count();

        return view('backend.courses.index', compact('courses', 'popularCount'));
    }

    public function create(): View
    {
        return view('backend.courses.form', [
            'course'       => new Course(['is_active' => true, 'rating' => 4.5]),
            'categories'   => $this->categoryOptions(),
            'popularOther' => Course::popular()->count(),
        ]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        if ($error = $this->popularGuard($request)) {
            return $error;
        }

        $data = $this->clean($request);
        $data['image'] = $this->storeImage($request);

        $course = Course::create($data);
        $this->syncFaqs($course, $request->input('faqs', []));

        return redirect()->route('backend.courses.index')->with('success', 'Course added.');
    }

    public function edit(Course $course): View
    {
        $course->load('faqs');

        return view('backend.courses.form', [
            'course'       => $course,
            'categories'   => $this->categoryOptions(),
            'popularOther' => Course::popular()->whereKeyNot($course->id)->count(),
        ]);
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        if ($error = $this->popularGuard($request, $course)) {
            return $error;
        }

        $data = $this->clean($request);

        if ($request->hasFile('image')) {
            $this->deleteImage($course->image);
            $data['image'] = $this->storeImage($request);
        }

        $course->update($data);
        $this->syncFaqs($course, $request->input('faqs', []));

        return redirect()->route('backend.courses.index')->with('success', 'Course updated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->deleteImage($course->image);
        $course->delete();   // FAQs cascade via the FK

        return redirect()->route('backend.courses.index')->with('success', 'Course deleted.');
    }

    /**
     * Enforce the four-course Popular cap. Only matters when this request marks
     * the course popular AND it isn't already counted in the current four.
     */
    private function popularGuard(CourseRequest $request, ?Course $course = null): ?RedirectResponse
    {
        if (! $request->boolean('is_popular')) {
            return null;
        }

        $alreadyPopular = $course?->is_popular ?? false;
        $currentCount   = Course::popular()->when($course, fn ($q) => $q->whereKeyNot($course->id))->count();

        if (! $alreadyPopular && $currentCount >= Course::MAX_POPULAR) {
            return back()->withInput()
                ->with('error', 'You can only select ' . Course::MAX_POPULAR . ' Popular Courses.');
        }

        return null;
    }

    /** Validated payload minus the FAQ repeater (handled separately). */
    private function clean(CourseRequest $request): array
    {
        return collect($request->validated())->except(['faqs', 'image'])->all();
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
        return 'storage/' . $request->file('image')->store('courses', 'public');
    }

    private function deleteImage(?string $image): void
    {
        if ($image && str_starts_with($image, 'storage/')) {
            Storage::disk('public')->delete(substr($image, strlen('storage/')));
        }
    }
}
