<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Services\FormSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The public side: one route serves every form the admin has ever built.
 *
 * /forms/{slug} is deliberately a single route with a dynamic segment. Nothing
 * about a particular form reaches the router, so publishing a new form needs no
 * deploy — which is the difference between a form builder and a page.
 *
 * The same page serves the embedded copy (?embed=1), which is why an embedded
 * submission is stored by exactly the code path a direct one is.
 */
class PublicFormController extends Controller
{
    public function __construct(private FormSubmissionService $submissions)
    {
    }

    /**
     * Render a form.
     *
     * A draft or disabled form is NOT a 404: its link may already be printed on
     * a flyer, and "this form is currently closed" is a better answer than a
     * missing page. A slug that never existed is a 404.
     */
    public function show(Request $request, string $slug): View
    {
        // Pages and sections ride along because the page is drawn from them —
        // two small queries against however many questions the form has, rather
        // than one per question while FormLayout buckets them.
        $form = Form::where('slug', $slug)
            ->with(['fields.options', 'pages', 'sections'])
            ->firstOrFail();

        [$accepting, $closedReason] = $form->submissionState();

        // "Allow multiple submissions: No" is remembered per browser — see
        // FormSubmissionService::submittedKey for why not by IP.
        if ($accepting && ! $form->allows_multiple && FormSubmissionService::hasSubmitted($form)) {
            $accepting     = false;
            $closedReason  = 'You have already submitted this form.';
        }

        return view('frontend.form', [
            'form'         => $form,
            'accepting'    => $accepting,
            'closedReason' => $closedReason,
            'embed'        => $request->boolean('embed'),
        ]);
    }

    /**
     * Take a submission.
     *
     * Every check the page made is made again here. The page decides what to
     * render; this decides what is true — a POST that arrives straight at this
     * route, from an embed, or from a page left open for an hour has to be
     * judged on the form's state right now.
     */
    public function submit(Request $request, string $slug): RedirectResponse
    {
        $form = Form::where('slug', $slug)->with(['fields.options'])->firstOrFail();

        [$accepting, $closedReason] = $form->submissionState();

        if (! $accepting) {
            return back()->with('form_error', $closedReason);
        }

        if (! $form->allows_multiple && FormSubmissionService::hasSubmitted($form)) {
            return back()->with('form_error', 'You have already submitted this form.');
        }

        // The honeypot: invisible to a person, irresistible to a bot that fills
        // every input. Answered with the success page rather than an error, so
        // whatever is submitting learns nothing about why it failed.
        if (filled($request->input(FormSubmissionService::HONEYPOT))) {
            return $this->finish($request, $form);
        }

        $validator = $this->submissions->validator($form, $request->all(), $request->allFiles());

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $this->submissions->store($form, $validator->validated() + $request->all(), $request);

        $request->session()->put(FormSubmissionService::submittedKey($form), true);

        return $this->finish($request, $form);
    }

    /**
     * Where a completed submission lands: the admin's redirect if they set one,
     * otherwise back to the form with its success message.
     */
    private function finish(Request $request, Form $form): RedirectResponse
    {
        if ($url = $form->redirect_url) {
            return redirect()->away($url);
        }

        return redirect()
            ->route('frontend.form.show', ['slug' => $form->slug] + ($request->boolean('embed') ? ['embed' => 1] : []))
            ->with('form_success', $form->success_message);
    }
}
