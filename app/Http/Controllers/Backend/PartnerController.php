<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesTableQuery;
use App\Http\Controllers\Backend\Concerns\OptimizesImageUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\PartnerRequest;
use App\Models\Partner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PartnerController extends Controller
{
    use HandlesTableQuery;
    use OptimizesImageUploads;

    public function index(): View
    {
        $partners = $this->applyTableFilters(Partner::query(), ['name'])
            ->orderBy('sort_order')->orderBy('id')
            ->paginate($this->perPage())->withQueryString();

        return view('backend.partners.index', compact('partners'));
    }

    public function create(): View
    {
        return view('backend.partners.form', ['partner' => new Partner(['is_active' => true, 'show_home' => true])]);
    }

    public function store(PartnerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['logo'] = $this->storeLogo($request);

        Partner::create($data);

        return redirect()->route('backend.partners.index')->with('success', 'Partner added.');
    }

    public function edit(Partner $partner): View
    {
        return view('backend.partners.form', compact('partner'));
    }

    public function update(PartnerRequest $request, Partner $partner): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $this->deleteLogo($partner->logo);
            $data['logo'] = $this->storeLogo($request);
        } else {
            unset($data['logo']);   // keep the existing one
        }

        $partner->update($data);

        return redirect()->route('backend.partners.index')->with('success', 'Partner updated.');
    }

    public function destroy(Partner $partner): RedirectResponse
    {
        $this->deleteLogo($partner->logo);
        $partner->delete();

        return redirect()->route('backend.partners.index')->with('success', 'Partner deleted.');
    }

    /** Store the uploaded logo on the public disk; return a /public-relative path. */
    private function storeLogo(PartnerRequest $request): string
    {
        return $this->storeOptimizedImage($request->file('logo'), 'partners', 480);
    }

    /** Delete a previously-uploaded logo, but never the seeded asset files. */
    private function deleteLogo(?string $logo): void
    {
        if ($logo && str_starts_with($logo, 'storage/')) {
            Storage::disk('public')->delete(substr($logo, strlen('storage/')));
        }
    }
}
