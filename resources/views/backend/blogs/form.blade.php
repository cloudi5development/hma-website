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

        <div class="row g-3">
            <div class="col-12 col-lg-8">
                <div class="hm-card">
                    <div class="hm-card__body">

                        <div class="form-row">
                            <label class="form-label" for="title">Title</label>
                            <input type="text" id="title" name="title"
                                   class="form-control-hm @error('title') is-invalid @enderror"
                                   value="{{ old('title', $blog->title) }}" placeholder="e.g. How to Prepare for Your First Technical Interview" required>
                            @error('title') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-row">
                            <label class="form-label" for="slug">Slug <span class="form-hint" style="display:inline">(optional — auto-filled from the title)</span></label>
                            <input type="text" id="slug" name="slug"
                                   class="form-control-hm @error('slug') is-invalid @enderror"
                                   value="{{ old('slug', $blog->slug) }}" placeholder="how-to-prepare-for-your-first-technical-interview">
                            @error('slug') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-row">
                            <label class="form-label" for="excerpt">Excerpt <span class="form-hint" style="display:inline">(short summary on the cards)</span></label>
                            <textarea id="excerpt" name="excerpt" rows="2"
                                      class="form-control-hm @error('excerpt') is-invalid @enderror"
                                      placeholder="A one or two line summary shown on the blog cards…" required>{{ old('excerpt', $blog->excerpt) }}</textarea>
                            @error('excerpt') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-row">
                            <label class="form-label" for="content">Article Body</label>
                            <textarea id="content" name="content" rows="16"
                                      class="form-control-hm @error('content') is-invalid @enderror"
                                      placeholder="The full article. Basic HTML is supported: <h3>Heading</h3> for section titles and <p>…</p> for paragraphs.">{{ old('content', $blog->content) }}</textarea>
                            <p class="form-hint">Use <code>&lt;h3&gt;</code> for section headings and <code>&lt;p&gt;</code> for paragraphs. <code>&lt;strong&gt;</code> and <code>&lt;a href&gt;</code> also work.</p>
                            @error('content') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-row">
                            <label class="form-label" for="image">Cover Image</label>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <span class="tbl-logo" style="width:96px;height:64px">
                                    <img id="imagePreview" src="{{ $editing ? $blog->image_url : '' }}" alt=""
                                         style="{{ $editing ? '' : 'display:none' }}">
                                </span>
                                <div>
                                    <input type="file" id="image" name="image" accept="image/*"
                                           class="form-control-hm @error('image') is-invalid @enderror" style="height:auto;padding:9px 12px">
                                    <p class="form-hint">WebP / PNG / JPG · max 2 MB · used on the cards and the article hero.</p>
                                </div>
                            </div>
                            @error('image') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="hm-card mb-3">
                    <div class="hm-card__head"><h2 class="hm-card__title">Details</h2></div>
                    <div class="hm-card__body">
                        <div class="form-row">
                            <label class="form-label" for="author">Author</label>
                            <input type="text" id="author" name="author"
                                   class="form-control-hm @error('author') is-invalid @enderror"
                                   value="{{ old('author', $blog->author ?? 'Hireminds Academy Admin') }}">
                            @error('author') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-row">
                            <label class="form-label" for="category">Category</label>
                            <input type="text" id="category" name="category"
                                   class="form-control-hm @error('category') is-invalid @enderror"
                                   value="{{ old('category', $blog->category) }}" placeholder="e.g. Career Advice">
                            @error('category') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="published_at">Publish Date</label>
                            <input type="date" id="published_at" name="published_at"
                                   class="form-control-hm @error('published_at') is-invalid @enderror"
                                   value="{{ old('published_at', optional($blog->published_at)->format('Y-m-d')) }}">
                            @error('published_at') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="hm-card mb-3">
                    <div class="hm-card__head"><h2 class="hm-card__title">Where It Shows</h2></div>
                    <div class="hm-card__body d-flex flex-column gap-2">
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

                <div class="hm-card">
                    <div class="hm-card__head"><h2 class="hm-card__title">Status</h2></div>
                    <div class="hm-card__body">
                        <label class="switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $blog->is_active ?? true) ? 'checked' : '' }}>
                            <span class="switch__track"></span>
                            <span class="switch__label">Active (visible on site)</span>
                        </label>
                        <div class="form-row mt-3" style="margin-bottom:0">
                            <label class="form-label" for="sort_order">Display Order</label>
                            <input type="number" id="sort_order" name="sort_order" min="0"
                                   class="form-control-hm @error('sort_order') is-invalid @enderror"
                                   value="{{ old('sort_order', $blog->sort_order ?? 0) }}" style="max-width:140px">
                            @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">Lower numbers show first.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
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
