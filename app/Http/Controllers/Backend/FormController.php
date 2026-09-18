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
        // A hand-edited ?q[]=x or ?status[]=x arrives as an array, which the
        // search, the status check and the toolbar's own inputs all take as a
        // string — a server error for a URL typo. Read as "no filter".
        foreach (['q', 'status', 'per_page'] as $key) {
            if (is_array($request->input($key))) {
                $request->merge([$key => null]);
                $request->query->remove($key);
            }
        }

        $status = (string) $request->input('status');

        $forms = $this->applyTableFilters(
                Form::query()->withCount(['fields', 'responses']),
                ['name', 'title', 'slug'],
                // The status column here is a three-way string, not the is_active
                // boolean the shared filter assumes, so it is applied by hand.
                statusColumn: null,
            )
            ->when(
                array_key_exists($status, Form::STATUSES),
                fn ($q) => $q->where('status', $status),
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
        // Plain, because that is what most forms are and because it is the one
        // structure that asks the admin to configure nothing at all. The other
        // three are one click away and keep whatever has been built so far.
        return view('backend.forms.builder', [
            'form' => new Form([
                'status'         => Form::PUBLISHED,
                'structure_type' => Form::PLAIN,
                'settings'       => Form::SETTING_DEFAULTS,
            ]),
        ]);
    }

    public function store(FormBuilderRequest $request): RedirectResponse
    {
        $form = $this->builder->save($request->validated());

        ActivityLog::record('Form Created', "Form “{$form->name}” created with {$form->fields()->count()} field(s)");

        if ($next = $this->afterSave($request)) {
            return redirect()->to($next)->with('success', "Form “{$form->name}” created.");
        }

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
        $form->load(['fields.options', 'fields.rows', 'fields.columns', 'pages', 'sections'])
            ->loadCount('responses');

        return view('backend.forms.show', [
            'form'  => $form,
            'stats' => $this->stats($form),
        ]);
    }

    public function edit(Form $form): View
    {
        // The containers the questions hang off. The fields themselves — with
        // their options and a grid's rows and columns — are loaded by
        // FormBuilderTree, which is also what decides whether they come from
        // here or from old input after a failed save.
        $form->load(['pages', 'sections']);

        return view('backend.forms.builder', ['form' => $form]);
    }

    public function update(FormBuilderRequest $request, Form $form): RedirectResponse
    {
        $this->builder->save($request->validated(), $form);

        ActivityLog::record('Form Updated', "Form “{$form->name}” updated");

        if ($next = $this->afterSave($request)) {
            return redirect()->to($next)->with('success', "Changes to “{$form->name}” saved.");
        }

        return redirect()->route('backend.forms.edit', $form)->with('success', 'Form saved.');
    }

    /**
     * Where to go once the save has worked, when it was asked for on the way out.
     *
     * The builder's "unsaved changes" dialog saves the form and carries on to
     * the page the admin was heading for, so the link they clicked still takes
     * them there. That address comes from the browser, so it is taken only when
     * it is on THIS site — a posted `after_save` of https://elsewhere.example or
     * //elsewhere.example is ignored, and the save lands on the builder as usual.
     * Anything else would be an open redirect riding on an admin's session.
     *
     * Not consulted when validation fails: the admin is sent back to the builder
     * to see what went wrong, with their work intact.
     */
    private function afterSave(Request $request): ?string
    {
        $next = trim((string) $request->input('after_save'));
        $home = rtrim(url('/'), '/');

        if ($next === '' || preg_match('/[\x00-\x1F\x7F]/', $next)) {
            return null;
        }

        return $next === $home || str_starts_with($next, $home . '/') ? $next : null;
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

        $data['notify_enabled'] = $request->boolean('notify_enabled');

        $this->builder->saveSettings($form, $data);

        ActivityLog::record('Form Settings Updated', "Settings for form “{$form->name}” updated");

        return back()->with('success', 'Settings saved.');
    }

    public function destroy(Form $form): RedirectResponse
    {
        // Uploads live outside the database, so the rows cascading is not enough
        // — the files have to be swept before the responses go.
        // lazy(), not cursor(): cursor() ignores with(), which made this one
        // query per response. lazy() streams in chunks and eager-loads each.
        foreach ($form->responses()->with('values')->lazy() as $response) {
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
        $form->load(['fields.options', 'fields.rows', 'fields.columns', 'pages', 'sections']);

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

        $validator = $submissions->validator($form, $submissions->normalise($form, $request->all()), $request->allFiles());

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
