<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesTableQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\CounterRequest;
use App\Models\Counter;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CounterController extends Controller
{
    use HandlesTableQuery;

    public function index(): View
    {
        $counters = $this->applyTableFilters(Counter::query(), ['label', 'number'])
            ->orderBy('sort_order')->orderBy('id')
            ->paginate($this->perPage())->withQueryString();

        return view('backend.counters.index', compact('counters'));
    }

    public function create(): View
    {
        return view('backend.counters.form', ['counter' => new Counter(['is_active' => true, 'show_home' => true])]);
    }

    public function store(CounterRequest $request): RedirectResponse
    {
        Counter::create($request->validated());

        return redirect()->route('backend.counters.index')->with('success', 'Counter added.');
    }

    public function edit(Counter $counter): View
    {
        return view('backend.counters.form', compact('counter'));
    }

    public function update(CounterRequest $request, Counter $counter): RedirectResponse
    {
        $counter->update($request->validated());

        return redirect()->route('backend.counters.index')->with('success', 'Counter updated.');
    }

    public function destroy(Counter $counter): RedirectResponse
    {
        $counter->delete();

        return redirect()->route('backend.counters.index')->with('success', 'Counter deleted.');
    }
}
