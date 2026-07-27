<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FaqRequest;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(): View
    {
        $faqs = Faq::orderBy('sort_order')->orderBy('id')->paginate(10);

        return view('backend.faqs.index', compact('faqs'));
    }

    public function create(): RedirectResponse|View
    {
        if ($redirect = $this->guardMax()) {
            return $redirect;
        }

        return view('backend.faqs.form', ['faq' => new Faq(['is_active' => true, 'show_home' => true])]);
    }

    public function store(FaqRequest $request): RedirectResponse
    {
        if ($redirect = $this->guardMax()) {
            return $redirect;
        }

        Faq::create($request->validated());

        return redirect()->route('backend.faqs.index')->with('success', 'FAQ added.');
    }

    public function edit(Faq $faq): View
    {
        return view('backend.faqs.form', compact('faq'));
    }

    public function update(FaqRequest $request, Faq $faq): RedirectResponse
    {
        $faq->update($request->validated());

        return redirect()->route('backend.faqs.index')->with('success', 'FAQ updated.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()->route('backend.faqs.index')->with('success', 'FAQ deleted.');
    }

    /**
     * The FAQ section is designed for at most Faq::MAX questions. Block adding a
     * new one past the cap and bounce back with an error the index popup shows.
     */
    private function guardMax(): ?RedirectResponse
    {
        if (Faq::count() >= Faq::MAX) {
            return redirect()->route('backend.faqs.index')
                ->with('error', 'Maximum ' . Faq::MAX . ' FAQs allowed.');
        }

        return null;
    }
}
