@extends('backend.template.layouts.template-base')

@php $editing = $blog->exists; @endphp

@section('title', $editing ? 'Edit Post' : 'Add Post')
@section('page_title', $editing ? 'Edit Post' : 'Add Post')
@section('page_sub', 'Blog')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.blogs.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Blog
        </a>
    </div>

    <form method="POST"
          action="{{ $editing ? route('backend.blogs.update', $blog) : route('backend.blogs.store') }}"
          enctype="multipart/form-data" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        {{-- One container for the whole form --}}
        <div class="hm-card mb-3">
            <div class="hm-card__body">

                {{-- Row 1 — title + active toggle --}}
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="title">Title</label>
                            <input type="text" id="title" name="title"
                                   class="form-control-hm @error('title') is-invalid @enderror"
                                   value="{{ old('title', $blog->title) }}" placeholder="e.g. How to Prepare for Your First Technical Interview" required>
                            @error('title') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label">Status</label>
                            <div class="d-flex align-items-center" style="height:48px">
                                <label class="switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $blog->is_active ?? true) ? 'checked' : '' }}>
                                    <span class="switch__track"></span>
                                    <span class="switch__label">Active</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 2 — slug + author --}}
                <div class="row g-3 mt-2">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="slug">Slug <span class="form-hint" style="display:inline">(optional — auto-filled from the title)</span></label>
                            <input type="text" id="slug" name="slug"
                                   class="form-control-hm @error('slug') is-invalid @enderror"
                                   value="{{ old('slug', $blog->slug) }}" placeholder="how-to-prepare-for-your-first-technical-interview">
                            @error('slug') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="author">Author</label>
                            <input type="text" id="author" name="author"
                                   class="form-control-hm @error('author') is-invalid @enderror"
                                   value="{{ old('author', $blog->author ?? 'Hireminds Academy Admin') }}">
                            @error('author') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Row 3 — category + publish date --}}
                <div class="row g-3 mt-2">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="category">Category</label>
                            <input type="text" id="category" name="category"
                                   class="form-control-hm @error('category') is-invalid @enderror"
                                   value="{{ old('category', $blog->category) }}" placeholder="e.g. Career Advice">
                            @error('category') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="published_at">Publish Date</label>
                            <input type="date" id="published_at" name="published_at"
                                   class="form-control-hm @error('published_at') is-invalid @enderror"
                                   value="{{ old('published_at', optional($blog->published_at)->format('Y-m-d')) }}">
                            @error('published_at') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Row 4 — excerpt --}}
                <div class="form-row mt-4">
                    <label class="form-label" for="excerpt">Excerpt <span class="form-hint" style="display:inline">(short summary on the cards)</span></label>
                    <textarea id="excerpt" name="excerpt" rows="2"
                              class="form-control-hm @error('excerpt') is-invalid @enderror"
                              placeholder="A one or two line summary shown on the blog cards…" required>{{ old('excerpt', $blog->excerpt) }}</textarea>
                    @error('excerpt') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- Row 5 — article body --}}
                <div class="form-row">
                    <label class="form-label" for="content">Article Body</label>
                    <textarea id="content" name="content" rows="16"
                              class="form-control-hm @error('content') is-invalid @enderror">{{ old('content', $blog->content) }}</textarea>
                    <p class="form-hint">Write the article as it should read. Use the toolbar for headings, bold, lists and links - the page styles them to match the site.</p>
                    @error('content') <p class="form-error">{{ $message }}</p> @enderror
                    {{-- The same editor the Terms / Privacy pages use. It is a
                         progressive enhancement: the plain textarea above still
                         posts the field if the script does not load. --}}
                    @include('backend.partials.rich-text-editor', ['selector' => '#content', 'height' => 460])
                </div>

                {{-- Row 6 — cover image --}}
                <div class="form-row">
                    <label class="form-label" for="image">Cover Image</label>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="tbl-logo" style="width:96px;height:64px">
                            <img id="imagePreview" src="{{ $editing ? $blog->image_url : '' }}" alt=""
                                 style="{{ $editing ? '' : 'display:none' }}">
                        </span>
                        <div>
                            <input type="file" id="image" name="image" accept="image/*"
                                   class="form-control-hm @error('image') is-invalid @enderror" style="height:auto;padding:9px 12px">
                            <p class="form-hint"><strong>1200 × 630 px</strong> (landscape) · WebP / PNG / JPG · max 2 MB · used on the cards and the article hero, so keep the subject centred.</p>
                        </div>
                    </div>
                    @error('image') <p class="form-error">{{ $message }}</p> @enderror

                    {{-- Alt text belongs to the image, so it sits with it
                         rather than in the SEO block. Blank falls back to the
                         post title, which is what the cover carried before. --}}
                    <div class="form-row mt-3" style="margin-bottom:0">
                        <label class="form-label" for="image_alt">Image Alt Tag</label>
                        <input type="text" id="image_alt" name="image_alt"
                               class="form-control-hm @error('image_alt') is-invalid @enderror"
                               value="{{ old('image_alt', $blog->image_alt) }}"
                               placeholder="e.g. Student taking notes during a mock technical interview">
                        @error('image_alt') <p class="form-error">{{ $message }}</p> @enderror
                        <p class="form-hint">Describes the cover for screen readers and search engines. Left blank, the post title is used.</p>
                    </div>
                </div>

                {{-- Row 7 — display order --}}
                <div class="form-row">
                    <label class="form-label" for="sort_order">Display Order</label>
                    <input type="number" id="sort_order" name="sort_order" min="0"
                           class="form-control-hm @error('sort_order') is-invalid @enderror"
                           value="{{ old('sort_order', $blog->sort_order ?? 0) }}" style="max-width:140px">
                    @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                    <p class="form-hint">Lower numbers show first.</p>
                </div>

                {{-- Row 8 — where it shows --}}
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label">Where It Shows</label>
                    <div class="d-flex flex-wrap gap-2">
                        <label class="check-chip">
                            <input type="hidden" name="show_home" value="0">
                            <input type="checkbox" name="show_home" value="1" {{ old('show_home', $blog->show_home ?? false) ? 'checked' : '' }}>
                            <span class="check-chip__box">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            </span>
                            <span class="check-chip__label">Home page “Latest Blog” grid</span>
                        </label>
                        <label class="check-chip">
                            <input type="hidden" name="is_latest" value="0">
                            <input type="checkbox" name="is_latest" value="1" {{ old('is_latest', $blog->is_latest ?? false) ? 'checked' : '' }}>
                            <span class="check-chip__box">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            </span>
                            <span class="check-chip__label">Blog details “The Latest” sidebar</span>
                        </label>
                    </div>
                </div>

                {{-- =============================== SEO ===============================
                     Per post, because every post shares one route: SEO -> Page
                     SEO is keyed by route name and skips the details pages for
                     exactly that reason. Same three fields a course carries. --}}
                <div class="form-section mt-4">
                    <h2 class="form-section__title">SEO</h2>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="meta_title">Meta Title</label>
                            <input type="text" id="meta_title" name="meta_title"
                                   class="form-control-hm @error('meta_title') is-invalid @enderror"
                                   value="{{ old('meta_title', $blog->meta_title) }}"
                                   placeholder="Left blank: the post title is used">
                            @error('meta_title') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="meta_keywords">Meta Keywords <span class="form-hint" style="display:inline">(comma separated)</span></label>
                            <input type="text" id="meta_keywords" name="meta_keywords"
                                   class="form-control-hm @error('meta_keywords') is-invalid @enderror"
                                   value="{{ old('meta_keywords', $blog->meta_keywords) }}"
                                   placeholder="e.g. technical interview, fresher jobs, placement training">
                            @error('meta_keywords') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="form-row mt-3" style="margin-bottom:0">
                    <label class="form-label" for="meta_description">Meta Description</label>
                    <textarea id="meta_description" name="meta_description" rows="2"
                              class="form-control-hm @error('meta_description') is-invalid @enderror"
                              placeholder="The line shown under the title in search results. Left blank, the excerpt is used.">{{ old('meta_description', $blog->meta_description) }}</textarea>
                    @error('meta_description') <p class="form-error">{{ $message }}</p> @enderror
                </div>

            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ $editing ? 'Save Changes' : 'Add Post' }}
            </button>
            <a href="{{ route('backend.blogs.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        // Live cover preview
        var input = document.getElementById('image'), img = document.getElementById('imagePreview');
        if (input) input.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                img.src = URL.createObjectURL(this.files[0]);
                img.style.display = 'block';
            }
        });
    </script>
@endpush
