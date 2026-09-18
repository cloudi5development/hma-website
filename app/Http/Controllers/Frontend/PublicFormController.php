<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Services\FormSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use PDOException;

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

        // Published is the only condition — no cap, and nobody is turned away
        // for having answered before.
        [$accepting, $closedReason] = $form->submissionState();

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

        // The page draws "no questions yet" instead of a form here, so a POST
        // can only come from a stale tab or a script — and it would file an
        // empty response against a form that asks nothing.
        if ($form->fields->isEmpty()) {
            return back()->with('form_error', 'This form has no questions yet, so there is nothing to submit.');
        }

        // The honeypot: invisible to a person, irresistible to a bot that fills
        // every input. Answered with the success page rather than an error, so
        // whatever is submitting learns nothing about why it failed.
        if (filled($request->input(FormSubmissionService::HONEYPOT))) {
            return $this->finish($request, $form);
        }

        $input     = $this->submissions->normalise($form, $request->all());
        $validator = $this->submissions->validator($form, $input, $request->allFiles());

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $this->submissions->store($form, $validator->validated() + $input, $request);
        } catch (PDOException $e) {
            // PDOException, not only QueryException (which extends it): when
            // the server drops the connection over an oversized statement, the
            // transaction's rollback fails as well, and that raw error is the
            // one that arrives here.
            // The module sets no length limit, but the database server has one
            // for a single statement (max_allowed_packet — 1 MB on a stock XAMPP),
            // and an answer pasted past it fails here. The response is written in
            // one transaction, so nothing half-saved is left behind; tell the
            // visitor plainly rather than showing a server error page.
            //
            // No withInput(): the input is what did not fit, and flashing it
            // into the database-backed session would fail the same way.
            Log::warning('A form response could not be stored', ['form' => $form->id, 'error' => $e->getMessage()]);

            return back()->with('form_error', 'Sorry — we could not save your response. '
                . 'If you pasted a very long answer, please shorten it and try again.');
        }

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
