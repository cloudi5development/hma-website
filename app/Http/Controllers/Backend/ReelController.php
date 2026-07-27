<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\ReelRequest;
use App\Models\Reel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReelController extends Controller
{
    public function index(): View
    {
        $reels = Reel::orderBy('sort_order')->orderBy('id')->paginate(10);

        return view('backend.reels.index', compact('reels'));
    }

    public function create(): View
    {
        return view('backend.reels.form', [
            'reel' => new Reel(['is_active' => true]),
        ]);
    }

    public function store(ReelRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['video'] = $this->storeVideo($request);

        Reel::create($data);

        return redirect()->route('backend.reels.index')->with('success', 'Reel added.');
    }

    public function edit(Reel $reel): View
    {
        return view('backend.reels.form', compact('reel'));
    }

    public function update(ReelRequest $request, Reel $reel): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('video')) {
            $this->deleteVideo($reel->video);
            $data['video'] = $this->storeVideo($request);
        } else {
            unset($data['video']);   // keep the existing one
        }

        $reel->update($data);

        return redirect()->route('backend.reels.index')->with('success', 'Reel updated.');
    }

    public function destroy(Reel $reel): RedirectResponse
    {
        $this->deleteVideo($reel->video);
        $reel->delete();

        return redirect()->route('backend.reels.index')->with('success', 'Reel deleted.');
    }

    /** Store the uploaded clip on the public disk; return a /public-relative path. */
    private function storeVideo(ReelRequest $request): string
    {
        $path = $request->file('video')->store('reels', 'public');

        return 'storage/' . $path;
    }

    /** Delete a previously-uploaded clip, but never the seeded asset files. */
    private function deleteVideo(?string $video): void
    {
        if ($video && str_starts_with($video, 'storage/')) {
            Storage::disk('public')->delete(substr($video, strlen('storage/')));
        }
    }
}
