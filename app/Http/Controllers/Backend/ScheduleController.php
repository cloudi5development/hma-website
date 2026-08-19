<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesTableQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\ScheduleRequest;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Courses → Schedule.
 *
 * Batches used to be a repeater inside the course form, which meant opening and
 * re-saving a whole course to correct one date. They are their own module now:
 * one listing across every course, and a small form that picks the course
 * through its category.
 */
class ScheduleController extends Controller
{
    use HandlesTableQuery;

    public function index(): View
    {
        $schedules = $this->applyTableFilters(
                CourseSchedule::with('course.category'),
                ['duration', 'training_mode', 'course.name'],
            )
            // Soonest first, but finished batches sink to the bottom rather than
            // heading the list forever. Sorted on the end date where there is one
            // — a batch that started last week is still live, so it belongs with
            // the live ones. Today is bound rather than written as CURDATE(),
            // which is MySQL-only.
            ->orderByRaw('CASE WHEN COALESCE(end_date, start_date) >= ? THEN 0 ELSE 1 END', [now()->toDateString()])
            ->orderBy('start_date')->orderBy('id')
            ->when(request('course'), fn ($q, $id) => $q->where('course_id', $id))
            ->paginate($this->perPage())->withQueryString();

        return view('backend.schedules.index', [
            'schedules' => $schedules,
            // Exactly what the site would list — same three conditions as the
            // frontend queries, so the header count and the website agree.
            'onSite' => CourseSchedule::active()->upcoming()
                ->whereHas('course', fn ($q) => $q->where('is_active', true))
                ->count(),
            'courseFilter' => request('course') ? Course::find(request('course')) : null,
        ]);
    }

    public function create(): View
    {
        // "Manage Schedules" on a course form arrives as ?course=<id>, so adding
        // a batch from there opens with that course already chosen.
        $course = request('course') ? Course::find(request('course')) : null;

        return view('backend.schedules.form', [
            'schedule' => new CourseSchedule([
                'is_active' => true,
                'show_fee'  => true,
                'course_id' => $course?->id,
            ]),
            'categoryId' => $course?->category_id,
        ] + $this->options());
    }

    public function store(ScheduleRequest $request): RedirectResponse
    {
        CourseSchedule::create($this->clean($request));

        return redirect()->route('backend.schedules.index')->with('success', 'Schedule added.');
    }

    public function edit(CourseSchedule $schedule): View
    {
        return view('backend.schedules.form', [
            'schedule'   => $schedule,
            'categoryId' => $schedule->course?->category_id,
        ] + $this->options());
    }

    public function update(ScheduleRequest $request, CourseSchedule $schedule): RedirectResponse
    {
        $schedule->update($this->clean($request));

        return redirect()->route('backend.schedules.index')->with('success', 'Schedule updated.');
    }

    public function destroy(CourseSchedule $schedule): RedirectResponse
    {
        $schedule->delete();

        return redirect()->route('backend.schedules.index')->with('success', 'Schedule deleted.');
    }

    /**
     * Validated payload minus category_id — that select only narrows the course
     * list on the form; the batch belongs to the course, and the category is
     * read back off it.
     */
    private function clean(ScheduleRequest $request): array
    {
        return collect($request->validated())
            ->except('category_id')
            ->all();
    }

    /**
     * The two selects on the form. Every course is sent with its category id
     * attached so the "choose category, then course" filtering happens in the
     * browser — a request per keystroke would be a lot of moving parts for a
     * list this size.
     */
    private function options(): array
    {
        return [
            'categories' => Category::where('is_active', true)
                ->with('department')
                ->orderBy('department_id')->orderBy('sort_order')->orderBy('name')
                ->get(['id', 'name', 'department_id']),

            'courses' => Course::orderBy('name')->get(['id', 'name', 'category_id', 'duration', 'is_active']),
        ];
    }
}
