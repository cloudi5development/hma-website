<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesTableQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FormBuilderRequest;
use App\Models\ActivityLog;
use App\Models\Form;
use App\Models\FormResponse;
use App\Services\FormBuilderService;
use App\Services\FormSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Forms → All Forms.
 *
 * The builder itself lives in FormBuilderService; responses live in
 * FormResponseController. This is the form's own lifecycle: list, build, preview,
 * share, duplicate, publish, delete.
 */
class FormController extends Controller
{
    use HandlesTableQuery;

    public function __construct(private FormBuilderService $builder)
    {
    }

    public function index(Request $request): View
    {
        $forms = $this->applyTableFilters(
                Form::query()->withCount(['fields', 'responses']),
                ['name', 'title', 'slug'],
                // The status column here is a three-way string, not the is_active
                // boolean the shared filter assumes, so it is applied by hand.
                statusColumn: null,
            )
            ->when(
                array_key_exists($request->input('status'), Form::STATUSES),
                fn ($q) => $q->where('status', $request->input('status')),
            )
            ->latest('id')
            ->paginate($this->perPage())->withQueryString();

        return view('backend.forms.index', compact('forms'));
    }

    public function create(): View
    {
        // A new form starts genuinely empty — no sample questions. The admin
        // decides every field, which is the entire point of the module.
        //
        // Published, not draft: the create flow hands the admin the form's link
        // and tells them to share it, so it has to work the moment they do.
        return view('backend.forms.builder', [
            'form'   => new Form(['status' => Form::PUBLISHED, 'settings' => Form::SETTING_DEFAULTS]),
            'fields' => collect(),
        ]);
    }

    public function store(FormBuilderRequest $request): RedirectResponse
    {
        $form = $this->builder->save($request->validated());

        ActivityLog::record('Form Created', "Form “{$form->name}” created with {$form->fields()->count()} field(s)");

        return redirect()->route('backend.forms.edit', $form)
            ->with('success', 'Form created. Add or adjust fields, then publish it when you are ready.');
    }

    /**
     * The address a form with this name would get.
     *
     * The builder's "Generate Link" dialog asks for this rather than slugging
     * the name in JavaScript, because only the server knows whether that slug is
     * already taken — and showing an admin a link that turns out to be
     * "course-enquiry-2" would be showing them the wrong link.
     */
    public function slugPreview(Request $request): JsonResponse
    {
        $name = trim((string) $request->input('name'));

        if ($name === '') {
            return response()->json(['message' => 'Give the form a name first.'], 422);
        }

        $slug = Form::uniqueSlug($name);

        return response()->json([
            'slug' => $slug,
            'url'  => route('frontend.form.show', $slug),
        ]);
    }

    /** The form's own page: what it asks, where it lives, how it is doing. */
    public function show(Form $form): View
    {
        $form->load(['fields.options', 'fields.rows', 'fields.columns'])->loadCount('responses');

        return view('backend.forms.show', [
            'form'  => $form,
            'stats' => $this->stats($form),
        ]);
    }

    public function edit(Form $form): View
    {
        return view('backend.forms.builder', [
            'form'   => $form,
            // Options plus a grid's rows and columns — the builder renders all
            // three managers and needs each list loaded.
            'fields' => $form->fields()->with(['options', 'rows', 'columns'])->get(),
        ]);
    }

    public function update(FormBuilderRequest $request, Form $form): RedirectResponse
    {
        $this->builder->save($request->validated(), $form);

        ActivityLog::record('Form Updated', "Form “{$form->name}” updated");

        return redirect()->route('backend.forms.edit', $form)->with('success', 'Form saved.');
    }

    /**
     * Save the form's settings, from its own page.
     *
     * Deliberately not part of the builder's save: the create and edit screens
     * ask for a name, a description and the questions, and nothing else. This
     * touches the settings column only — see FormBuilderService::saveSettings
     * for why routing it through the builder would be dangerous.
     */
    public function updateSettings(Request $request, Form $form): RedirectResponse
    {
        $data = $request->validate(FormBuilderRequest::settingRules());

        $data['allow_multiple'] = $request->boolean('allow_multiple');
        $data['notify_enabled'] = $request->boolean('notify_enabled');

        $this->builder->saveSettings($form, $data);

        ActivityLog::record('Form Settings Updated', "Settings for form “{$form->name}” updated");

        return back()->with('success', 'Settings saved.');
    }

    public function destroy(Form $form): RedirectResponse
    {
        // Uploads live outside the database, so the rows cascading is not enough
        // — the files have to be swept before the responses go.
        foreach ($form->responses()->with('values')->cursor() as $response) {
            FormSubmissionService::deleteUploads($response);
        }

        ActivityLog::record('Form Deleted', "Form “{$form->name}” and its responses deleted");

        $form->delete();   // fields, options, responses and values cascade

        return redirect()->route('backend.forms.index')->with('success', 'Form deleted.');
    }

    /* =============================== ACTIONS =============================== */

    /**
     * Publish / unpublish from the listing.
     *
     * A form with no fields cannot be published: it would render a page with a
     * title, a button, and nothing to fill in.
     */
    public function toggle(Form $form): RedirectResponse
    {
        if (! $form->isPublished() && $form->fields()->count() === 0) {
            return back()->with('error', 'Add at least one field before publishing this form.');
        }

        $status = $form->isPublished() ? Form::DISABLED : Form::PUBLISHED;

        $form->update(['status' => $status]);

        ActivityLog::record('Form Status Changed', "Form “{$form->name}” → " . Form::STATUSES[$status]);

        return back()->with('success', 'Form ' . ($status === Form::PUBLISHED ? 'published.' : 'disabled.'));
    }

    public function duplicate(Form $form): RedirectResponse
    {
        $copy = $this->builder->duplicate($form);

        ActivityLog::record('Form Duplicated', "Form “{$form->name}” duplicated as “{$copy->name}”");

        return redirect()->route('backend.forms.edit', $copy)
            ->with('success', 'Form duplicated as a draft. Its responses were not copied.');
    }

    /**
     * The real form, rendered from the real configuration, with nothing saved.
     *
     * It posts to previewSubmit rather than being inert, so the admin can test
     * the validation they configured — which is most of what "preview" is for.
     */
    public function preview(Form $form): View
    {
        $form->load(['fields.options', 'fields.rows', 'fields.columns']);

        return view('backend.forms.preview', ['form' => $form]);
    }

    /**
     * Run a preview submission through the real validator and throw the result
     * away. Nothing is written, so an admin testing a form cannot pollute the
     * responses the form is there to collect.
     */
    public function previewSubmit(Request $request, Form $form, FormSubmissionService $submissions): RedirectResponse
    {
        $form->load(['fields.options', 'fields.rows', 'fields.columns']);

        $validator = $submissions->validator($form, $request->all(), $request->allFiles());

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()
                ->with('error', 'Preview: the form reported the errors below. Nothing was saved.');
        }

        return back()->with('success', 'Preview: the form validated successfully. Nothing was saved.');
    }

    /* ================================ STATS ================================ */

    /** The counters on the form's page. One grouped query, not four. */
    private function stats(Form $form): array
    {
        $counts = FormResponse::where('form_id', $form->id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN submitted_at >= ? THEN 1 ELSE 0 END) as today', [now()->startOfDay()])
            ->selectRaw('SUM(CASE WHEN submitted_at >= ? THEN 1 ELSE 0 END) as week', [now()->startOfWeek()])
            ->selectRaw('SUM(CASE WHEN submitted_at >= ? THEN 1 ELSE 0 END) as month', [now()->startOfMonth()])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as unread', [FormResponse::STATUSES[0]])
            ->first();

        return [
            'total'  => (int) $counts->total,
            'today'  => (int) $counts->today,
            'week'   => (int) $counts->week,
            'month'  => (int) $counts->month,
            'unread' => (int) $counts->unread,
        ];
    }
}
