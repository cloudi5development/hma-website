@extends('backend.template.layouts.template-base')

@section('title', $page->title)
@section('page_title', $page->title)
@section('page_sub', 'Content Management')

@section('content')

    <div class="page-head">
        <div class="d-inline-flex gap-2">
            {{-- Both pages, so switching between them does not mean going back to
                 the menu. The current one is the brand button. --}}
            @foreach ($pages as $other)
                <a href="{{ route('backend.content-pages.edit', $other->key) }}"
                   class="{{ $other->key === $page->key ? 'btn-brand' : 'btn-ghost' }}">
                    {{ $other->title }}
                </a>
            @endforeach
        </div>

        <a href="{{ $page->url }}" class="btn-ghost" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M8.5 7H17v8.5"/></svg>
            View Page
        </a>
    </div>

    <form method="POST" action="{{ route('backend.content-pages.update', $page->key) }}" novalidate>
        @csrf
        @method('PUT')

        <div class="hm-card mb-3">
            <div class="hm-card__body">

                <div class="form-section">
                    <h2 class="form-section__title">Page</h2>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-8">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="title">Page Title</label>
                            <input type="text" id="title" name="title"
                                   class="form-control-hm @error('title') is-invalid @enderror"
                                   value="{{ old('title', $page->title) }}" required>
                            @error('title') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">Shown on the banner, in the footer link and in the browser tab.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label">Status</label>
                            <div class="d-flex align-items-center" style="min-height:48px">
                                <label class="switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $page->is_active) ? 'checked' : '' }}>
                                    <span class="switch__track"></span>
                                    <span class="switch__label">Published</span>
                                </label>
                            </div>
                            <p class="form-hint">Switched off, the page 404s and its footer link disappears.</p>
                        </div>
                    </div>
                </div>

                <div class="form-row mt-4">
                    <label class="form-label" for="content">Page Content</label>
                    {{-- width:100% overrides .form-control-hm's 500px preferred
                         width for this field only — it is what the editor sizes
                         itself against, and the shared width is meant for
                         one-line inputs. Nothing else in the panel is affected. --}}
                    <textarea id="content" name="content" rows="24"
                              class="form-control-hm @error('content') is-invalid @enderror"
                              style="width:100%">{{ old('content', $page->content) }}</textarea>
                    @error('content') <p class="form-error">{{ $message }}</p> @enderror
                    <p class="form-hint">
                        Write the page as you would in a document — headings, paragraphs, bullet
                        points, links and tables are all in the toolbar, and everything is styled
                        to match the site automatically. Use <strong>&lt;/&gt;</strong> at the end
                        of the toolbar if you would rather edit the HTML directly.
                    </p>
                </div>

                @include('backend.partials.rich-text-editor', ['selector' => '#content'])

                <div class="form-section">
                    <h2 class="form-section__title">SEO</h2>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="seo_title">Meta Title <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="seo_title" name="seo_title"
                                   class="form-control-hm @error('seo_title') is-invalid @enderror"
                                   value="{{ old('seo_title', $page->seo_title) }}" placeholder="Leave blank to use the page title">
                            @error('seo_title') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="seo_keywords">Meta Keywords <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="seo_keywords" name="seo_keywords"
                                   class="form-control-hm @error('seo_keywords') is-invalid @enderror"
                                   value="{{ old('seo_keywords', $page->seo_keywords) }}" placeholder="comma, separated, keywords">
                            @error('seo_keywords') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="form-row mt-4" style="margin-bottom:0">
                    <label class="form-label" for="seo_description">Meta Description <span class="form-hint" style="display:inline">(optional)</span></label>
                    <textarea id="seo_description" name="seo_description" rows="2"
                              class="form-control-hm @error('seo_description') is-invalid @enderror"
                              placeholder="A sentence for search results">{{ old('seo_description', $page->seo_description) }}</textarea>
                    @error('seo_description') <p class="form-error">{{ $message }}</p> @enderror
                    <p class="form-hint">Anything left blank falls back to Settings → SEO Defaults.</p>
                </div>

            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                Save Changes
            </button>
        </div>
    </form>

@endsection
