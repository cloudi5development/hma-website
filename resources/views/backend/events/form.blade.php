@extends('backend.template.layouts.template-base')

@php
    $editing = $event->exists;

    // Repeater rows: whatever the last failed submit posted, else what is stored.
    // old() keeps the admin's typing on a validation error; the DB copy is what
    // they see on a normal edit.
    $highlightRows = old('highlights', $editing
        ? $event->highlights->map(fn ($h) => $h->only(['icon', 'title', 'description', 'is_active']))->all()
        : []);

    $speakerRows = old('speakers', $editing
        ? $event->speakers->map(fn ($s) => [
            'name'           => $s->name,
            'designation'    => $s->designation,
            'company'        => $s->company,
            'linkedin'       => $s->linkedin,
            'existing_photo' => $s->photo,
            'is_active'      => $s->is_active,
          ])->all()
        : []);

    $faqRows = old('event_faqs', $editing
        ? $event->faqs->map(fn ($f) => $f->only(['question', 'answer', 'is_active']))->all()
        : []);

    $maxRows = 12;
@endphp

@section('title', $editing ? 'Edit Event' : 'Add Event')
@section('page_title', $editing ? 'Edit Event' : 'Add Event')
@section('page_sub', 'Upcoming Events')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.events.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Events
        </a>
        @if ($editing && $event->slug)
            <a href="{{ route('frontend.event-details', $event->slug) }}" class="btn-ghost" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M8.5 7H17v8.5"/></svg>
                View Details Page
            </a>
        @endif
    </div>
    

    <form method="POST"
          action="{{ $editing ? route('backend.events.update', $event) : route('backend.events.store') }}"
          enctype="multipart/form-data" novalidate
          data-max-rows="{{ $maxRows }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        {{-- Events only appear on the home page, so visibility is fixed to home. --}}
        <input type="hidden" name="show_home" value="1">

        {{-- One container for the whole form --}}
        <div class="hm-card mb-3">
            <div class="hm-card__body">

                {{-- ============================ THE EVENT ============================ --}}
                <div class="form-section">
                    <h2 class="form-section__title">Event</h2>
                </div>

                {{-- Row 1 — speaker + active toggle --}}
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="speaker">Speaker</label>
                            <input type="text" id="speaker" name="speaker"
                                   class="form-control-hm @error('speaker') is-invalid @enderror"
                                   value="{{ old('speaker', $event->speaker) }}" placeholder="e.g. Rochelle Fernandez" required>
                            @error('speaker') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label">Status</label>
                            <div class="d-flex align-items-center gap-4 flex-wrap" style="min-height:48px">
                                <label class="switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $event->is_active ?? true) ? 'checked' : '' }}>
                                    <span class="switch__track"></span>
                                    <span class="switch__label">Active</span>
                                </label>
                                <label class="switch">
                                    <input type="hidden" name="is_featured" value="0">
                                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $event->is_featured ?? false) ? 'checked' : '' }}>
                                    <span class="switch__track"></span>
                                    <span class="switch__label">Featured</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 2 — title --}}
                <div class="form-row mt-2">
                    <label class="form-label" for="title">Event Title</label>
                    <input type="text" id="title" name="title"
                           class="form-control-hm @error('title') is-invalid @enderror"
                           value="{{ old('title', $event->title) }}" placeholder="e.g. Learn about no-code tools" required>
                    @error('title') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-row">
                    <label class="form-label" for="slug">URL Slug <span class="form-hint" style="display:inline">(optional)</span></label>
                    <input type="text" id="slug" name="slug"
                           class="form-control-hm @error('slug') is-invalid @enderror"
                           value="{{ old('slug', $event->slug) }}" placeholder="ai-future-tech-bootcamp-2026">
                    @error('slug') <p class="form-error">{{ $message }}</p> @enderror
                    <p class="form-hint">The details page address: {{ url('/events') }}/<strong id="slugEcho">{{ $event->slug ?: 'your-event-title' }}</strong>. Leave blank to build it from the title.</p>
                </div>

                <div class="form-row">
                    <label class="form-label" for="short_description">Short Description <span class="form-hint" style="display:inline">(optional)</span></label>
                    <textarea id="short_description" name="short_description" rows="2"
                              class="form-control-hm @error('short_description') is-invalid @enderror"
                              placeholder="One or two lines shown under the title on the details page.">{{ old('short_description', $event->short_description) }}</textarea>
                    @error('short_description') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-row">
                    <label class="form-label" for="description">About This Event <span class="form-hint" style="display:inline">(optional)</span></label>
                    <textarea id="description" name="description" rows="6"
                              class="form-control-hm @error('description') is-invalid @enderror"
                              placeholder="The full description. Leave a blank line between paragraphs.">{{ old('description', $event->description) }}</textarea>
                    @error('description') <p class="form-error">{{ $message }}</p> @enderror
                    <p class="form-hint">Plain text — each blank line starts a new paragraph on the page.</p>
                </div>

                {{-- ============================= SCHEDULE ============================= --}}
                <div class="form-section">
                    <h2 class="form-section__title">Schedule &amp; Venue</h2>
                </div>

                {{-- These drive the date / time / location rows on the home-page
                     card; leave one blank and that row is simply not shown. --}}
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="event_date">
                                Event Date <span class="form-hint" style="display:inline">(optional)</span>
                            </label>

                            <input type="date" id="event_date" name="event_date"
                                   class="form-control-hm @error('event_date') is-invalid @enderror"
                                   value="{{ old('event_date', $event->event_date?->format('Y-m-d')) }}">
                            @error('event_date') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">Shown as “20 July, 2026”.</p>
                        </div>
                    </div> 
                    <div class="col-12 col-md-4">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="event_time">
                                Start Time <span class="form-hint" style="display:inline">(optional)</span>
                            </label>
                            <input type="time" id="event_time" name="event_time"
                                   class="form-control-hm @error('event_time') is-invalid @enderror"
                                   value="{{ old('event_time', $event->time_input_value) }}">
                            @error('event_time') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">Shown as “10:00 AM”.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="end_time">
                                End Time <span class="form-hint" style="display:inline">(optional)</span>
                            </label>
                            <input type="time" id="end_time" name="end_time"
                                   class="form-control-hm @error('end_time') is-invalid @enderror"
                                   value="{{ old('end_time', $event->end_time_input_value) }}">
                            @error('end_time') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">Makes the range “10:00 AM - 6:00 PM”.</p>
                        </div>
                    </div>
                </div>

                <div class="form-row mt-4">
                    <label class="form-label" for="location">
                        Location <span class="form-hint" style="display:inline">(optional)</span>
                    </label>
                    <input type="text" id="location" name="location"
                           class="form-control-hm @error('location') is-invalid @enderror"
                           value="{{ old('location', $event->location) }}"
                           placeholder="e.g. Plot 456, T. Nagar, Chennai, Tamil Nadu, 600020">
                    @error('location') <p class="form-error">{{ $message }}</p> @enderror
                    <p class="form-hint">The one-line address used on the cards. Keep it short — long addresses are trimmed to two lines.</p>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="venue">Venue <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="venue" name="venue"
                                   class="form-control-hm @error('venue') is-invalid @enderror"
                                   value="{{ old('venue', $event->venue) }}" placeholder="e.g. Hire Minds Academy Campus">
                            @error('venue') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="city">City <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="city" name="city"
                                   class="form-control-hm @error('city') is-invalid @enderror"
                                   value="{{ old('city', $event->city) }}" placeholder="e.g. Chennai">
                            @error('city') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="state">State <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="state" name="state"
                                   class="form-control-hm @error('state') is-invalid @enderror"
                                   value="{{ old('state', $event->state) }}" placeholder="e.g. Tamil Nadu">
                            @error('state') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="country">Country <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="country" name="country"
                                   class="form-control-hm @error('country') is-invalid @enderror"
                                   value="{{ old('country', $event->country) }}" placeholder="e.g. India">
                            @error('country') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
                <p class="form-hint">Filled in, these four replace Location on the details page. Left blank, the details page falls back to Location.</p>

                {{-- ======================== PRICING & SEATS ========================= --}}
                <div class="form-section">
                    <h2 class="form-section__title">Pricing, Seats &amp; Format</h2>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="type">Type</label>
                            @php $type = old('type', $event->type ?: \App\Models\Event::TYPES[0]); @endphp
                            <select id="type" name="type" class="form-control-hm @error('type') is-invalid @enderror">
                                {{-- Types typed in before this became a dropdown (e.g. "Live Event")
                                     stay selectable, so editing an old event keeps its badge. --}}
                                @if (filled($type) && ! in_array($type, \App\Models\Event::TYPES, true))
                                    <option value="{{ $type }}" selected>{{ $type }}</option>
                                @endif
                                @foreach (\App\Models\Event::TYPES as $option)
                                    <option value="{{ $option }}" {{ $type === $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('type') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">Printed as the badge on the event card.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="price">Price <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="price" name="price"
                                   class="form-control-hm @error('price') is-invalid @enderror"
                                   value="{{ old('price', $event->price) }}" placeholder="e.g. ₹999/-">
                            @error('price') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">Blank means the card reads “Free”.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="offer_price">Offer Price <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="offer_price" name="offer_price"
                                   class="form-control-hm @error('offer_price') is-invalid @enderror"
                                   value="{{ old('offer_price', $event->offer_price) }}" placeholder="e.g. ₹499/-">
                            @error('offer_price') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">Set this and Price is struck through.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="discount_percentage">Discount % <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="number" id="discount_percentage" name="discount_percentage" min="0" max="100"
                                   class="form-control-hm @error('discount_percentage') is-invalid @enderror"
                                   value="{{ old('discount_percentage', $event->discount_percentage) }}" placeholder="50">
                            @error('discount_percentage') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">Shown as a “50% OFF” badge.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="total_seats">Total Seats <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="number" id="total_seats" name="total_seats" min="0"
                                   class="form-control-hm @error('total_seats') is-invalid @enderror"
                                   value="{{ old('total_seats', $event->total_seats) }}" placeholder="100">
                            @error('total_seats') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="available_seats">Seats Left <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="number" id="available_seats" name="available_seats" min="0"
                                   class="form-control-hm @error('available_seats') is-invalid @enderror"
                                   value="{{ old('available_seats', $event->available_seats) }}" placeholder="25">
                            @error('available_seats') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">Drives the “Only 25 Left” line.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="duration">Duration <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="duration" name="duration"
                                   class="form-control-hm @error('duration') is-invalid @enderror"
                                   value="{{ old('duration', $event->duration) }}" placeholder="e.g. 1 Day">
                            @error('duration') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="language">Language <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="language" name="language"
                                   class="form-control-hm @error('language') is-invalid @enderror"
                                   value="{{ old('language', $event->language) }}" placeholder="e.g. English">
                            @error('language') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="level">Level <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="level" name="level"
                                   class="form-control-hm @error('level') is-invalid @enderror"
                                   value="{{ old('level', $event->level) }}" placeholder="e.g. Beginner">
                            @error('level') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="organizer">Organised By <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="organizer" name="organizer"
                                   class="form-control-hm @error('organizer') is-invalid @enderror"
                                   value="{{ old('organizer', $event->organizer) }}" placeholder="e.g. Hire Minds Academy">
                            @error('organizer') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="link">Registration Link <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="link" name="link"
                                   class="form-control-hm @error('link') is-invalid @enderror"
                                   value="{{ old('link', $event->link) }}" placeholder="https://…">
                            @error('link') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">Leave blank and “Register Now” opens the registration form on the event page — those go to Leads → Event Registration. Fill it in to send people to an outside booking page instead.</p>
                        </div>
                    </div>
                </div>

                {{-- ============================== IMAGES ============================== --}}
                <div class="form-section">
                    <h2 class="form-section__title">Images</h2>
                </div>

                <div class="form-row">
                    <label class="form-label" for="image">Speaker Photo</label>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="tbl-logo tbl-logo--round" style="width:64px;height:64px">
                            <img id="imagePreview" src="{{ $editing ? $event->image_url : '' }}" alt=""
                                 style="{{ $editing ? '' : 'display:none' }}">
                        </span>
                        <div>
                            <input type="file" id="image" name="image" accept="image/*"
                                   class="form-control-hm @error('image') is-invalid @enderror" style="height:auto;padding:9px 12px">
                            <p class="form-hint"><strong>800 × 1000 px</strong> (portrait) · WebP / PNG / JPG · max 2 MB · a transparent cut-out of the person works best.</p>
                        </div>
                    </div>
                    @error('image') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-row">
                    <label class="form-label" for="banner_image">Details Page Banner <span class="form-hint" style="display:inline">(optional)</span></label>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="tbl-logo" style="width:120px;height:64px">
                            <img id="bannerPreview" src="{{ $event->banner_image ? asset($event->banner_image) : '' }}" alt=""
                                 style="{{ $event->banner_image ? '' : 'display:none' }}">
                        </span>
                        <div>
                            <input type="file" id="banner_image" name="banner_image" accept="image/*"
                                   class="form-control-hm @error('banner_image') is-invalid @enderror" style="height:auto;padding:9px 12px">
                            <p class="form-hint"><strong>1920 × 480 px</strong> (wide strip behind the title) · WebP / PNG / JPG · max 3 MB.</p>
                            @if ($event->banner_image)
                                <label class="d-inline-flex align-items-center gap-2 form-hint" style="cursor:pointer">
                                    <input type="checkbox" name="remove_banner_image" value="1"> Remove the current banner
                                </label>
                            @endif
                        </div>
                    </div>
                    @error('banner_image') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-row">
                    <label class="form-label" for="thumbnail">Sidebar Thumbnail <span class="form-hint" style="display:inline">(optional)</span></label>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="tbl-logo" style="width:64px;height:64px">
                            <img id="thumbPreview" src="{{ $event->thumbnail ? asset($event->thumbnail) : '' }}" alt=""
                                 style="{{ $event->thumbnail ? '' : 'display:none' }}">
                        </span>
                        <div>
                            <input type="file" id="thumbnail" name="thumbnail" accept="image/*"
                                   class="form-control-hm @error('thumbnail') is-invalid @enderror" style="height:auto;padding:9px 12px">
                            <p class="form-hint"><strong>600 × 600 px</strong> (square) for the “Upcoming Events” list · WebP / PNG / JPG · max 2 MB. Blank uses the speaker photo.</p>
                            @if ($event->thumbnail)
                                <label class="d-inline-flex align-items-center gap-2 form-hint" style="cursor:pointer">
                                    <input type="checkbox" name="remove_thumbnail" value="1"> Remove the current thumbnail
                                </label>
                            @endif
                        </div>
                    </div>
                    @error('thumbnail') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                {{-- ===================== WHAT YOU WILL LEARN ======================== --}}
                <div class="form-section">
                    <h2 class="form-section__title">
                        What You Will Learn <span class="form-hint" style="display:inline">(max {{ $maxRows }})</span>
                    </h2>
                    <button type="button" class="btn-soft" data-repeater-add="highlights">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        Add Highlight
                    </button>
                </div>

                <div data-repeater="highlights" data-max="{{ $maxRows }}">
                    @foreach ($highlightRows as $r => $row)
                        @include('backend.events.partials.highlight-row', ['r' => $r, 'row' => $row])
                    @endforeach
                </div>
                <p class="form-hint" data-repeater-empty="highlights" @if (count($highlightRows)) style="display:none" @endif>
                    No highlights yet — the “What You Will Learn” block is hidden until you add one.
                </p>

                {{-- ========================= MEET OUR SPEAKERS ====================== --}}
                <div class="form-section">
                    <h2 class="form-section__title">
                        Meet Our Speakers <span class="form-hint" style="display:inline">(max {{ $maxRows }})</span>
                    </h2>
                    <button type="button" class="btn-soft" data-repeater-add="speakers">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        Add Speaker
                    </button>
                </div>

                <div data-repeater="speakers" data-max="{{ $maxRows }}">
                    @foreach ($speakerRows as $r => $row)
                        @include('backend.events.partials.speaker-row', ['r' => $r, 'row' => $row])
                    @endforeach
                </div>
                <p class="form-hint" data-repeater-empty="speakers" @if (count($speakerRows)) style="display:none" @endif>
                    No speakers yet — the “Meet Our Speakers” block is hidden until you add one.
                </p>

                {{-- ================================ FAQ ============================= --}}
                <div class="form-section">
                    <h2 class="form-section__title">
                        FAQs <span class="form-hint" style="display:inline">(max {{ $maxRows }})</span>
                    </h2>
                    <button type="button" class="btn-soft" data-repeater-add="event_faqs">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        Add FAQ
                    </button>
                </div>

                <div data-repeater="event_faqs" data-max="{{ $maxRows }}">
                    @foreach ($faqRows as $r => $row)
                        @include('backend.events.partials.faq-row', ['r' => $r, 'row' => $row])
                    @endforeach
                </div>
                <p class="form-hint" data-repeater-empty="event_faqs" @if (count($faqRows)) style="display:none" @endif>
                    No FAQs yet — the accordion is hidden until you add one.
                </p>

                {{-- =============================== SEO =============================== --}}
                <div class="form-section">
                    <h2 class="form-section__title">SEO</h2>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="seo_title">Meta Title <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="seo_title" name="seo_title"
                                   class="form-control-hm @error('seo_title') is-invalid @enderror"
                                   value="{{ old('seo_title', $event->seo_title) }}" placeholder="Leave blank to use the event title">
                            @error('seo_title') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="seo_keywords">Meta Keywords <span class="form-hint" style="display:inline">(optional)</span></label>
                            <input type="text" id="seo_keywords" name="seo_keywords"
                                   class="form-control-hm @error('seo_keywords') is-invalid @enderror"
                                   value="{{ old('seo_keywords', $event->seo_keywords) }}" placeholder="comma, separated, keywords">
                            @error('seo_keywords') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="form-row mt-4">
                    <label class="form-label" for="seo_description">Meta Description <span class="form-hint" style="display:inline">(optional)</span></label>
                    <textarea id="seo_description" name="seo_description" rows="2"
                              class="form-control-hm @error('seo_description') is-invalid @enderror"
                              placeholder="Leave blank to use the short description">{{ old('seo_description', $event->seo_description) }}</textarea>
                    @error('seo_description') <p class="form-error">{{ $message }}</p> @enderror
                    <p class="form-hint">Anything left blank here falls back to Settings → SEO Defaults.</p>
                </div>

                <div class="form-row">
                    <label class="form-label" for="og_image">Social Share Image <span class="form-hint" style="display:inline">(optional)</span></label>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="tbl-logo" style="width:120px;height:64px">
                            <img id="ogPreview" src="{{ $event->og_image ? asset($event->og_image) : '' }}" alt=""
                                 style="{{ $event->og_image ? '' : 'display:none' }}">
                        </span>
                        <div>
                            <input type="file" id="og_image" name="og_image" accept="image/*"
                                   class="form-control-hm @error('og_image') is-invalid @enderror" style="height:auto;padding:9px 12px">
                            <p class="form-hint"><strong>1200 × 630 px</strong> (landscape) · WebP / PNG / JPG · max 2 MB · used when the page is shared.</p>
                            @if ($event->og_image)
                                <label class="d-inline-flex align-items-center gap-2 form-hint" style="cursor:pointer">
                                    <input type="checkbox" name="remove_og_image" value="1"> Remove the current share image
                                </label>
                            @endif
                        </div>
                    </div>
                    @error('og_image') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-row">
                    <label class="form-label" for="schema_json">Extra Schema Markup <span class="form-hint" style="display:inline">(optional)</span></label>
                    <textarea id="schema_json" name="schema_json" rows="4"
                              class="form-control-hm @error('schema_json') is-invalid @enderror"
                              {{-- @@context, not @context: the single @ is a real Blade
                                   directive and would open a block that never closes. --}}
                              placeholder='{"@@context":"https://schema.org", …}' style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px">{{ old('schema_json', $event->schema_json) }}</textarea>
                    @error('schema_json') <p class="form-error">{{ $message }}</p> @enderror
                    <p class="form-hint">Valid JSON only. Blank means the page builds its own Event schema from the fields above.</p>
                </div>

                {{-- ============================= PLACEMENT =========================== --}}
                <div class="form-section">
                    <h2 class="form-section__title">Card Appearance</h2>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="sort_order">Display Order</label>
                            <input type="number" id="sort_order" name="sort_order" min="0"
                                   class="form-control-hm @error('sort_order') is-invalid @enderror"
                                   value="{{ old('sort_order', $event->sort_order ?? 0) }}" style="max-width:140px">
                            @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">
                                Lower numbers show first. The card's colour is assigned
                                automatically — each new event takes the next shade, so the
                                carousel and the listing stay varied.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ $editing ? 'Save Changes' : 'Add Event' }}
            </button>
            <a href="{{ route('backend.events.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

    {{-- Row templates cloned by the repeater script. __I__ becomes the row key. --}}
    <template id="highlightsTemplate">
        @include('backend.events.partials.highlight-row', ['r' => '__I__', 'row' => []])
    </template>

    <template id="speakersTemplate">
        @include('backend.events.partials.speaker-row', ['r' => '__I__', 'row' => []])
    </template>

    <template id="event_faqsTemplate">
        @include('backend.events.partials.faq-row', ['r' => '__I__', 'row' => []])
    </template>

@endsection

@push('scripts')
    <script>
        (function () {
            'use strict';

            // ---- Live image previews ----
            [['image', 'imagePreview'], ['banner_image', 'bannerPreview'],
             ['thumbnail', 'thumbPreview'], ['og_image', 'ogPreview']].forEach(function (pair) {
                var input = document.getElementById(pair[0]), img = document.getElementById(pair[1]);
                if (!input || !img) return;
                input.addEventListener('change', function () {
                    if (this.files && this.files[0]) {
                        img.src = URL.createObjectURL(this.files[0]);
                        img.style.display = 'block';
                    }
                });
            });

            // ---- Slug echo: show what the URL will look like while typing ----
            var titleInput = document.getElementById('title'),
                slugInput  = document.getElementById('slug'),
                slugEcho   = document.getElementById('slugEcho');

            function slugify(value) {
                return value.toLowerCase().trim()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
            function echoSlug() {
                if (!slugEcho) return;
                var value = slugify(slugInput.value) || slugify(titleInput.value);
                slugEcho.textContent = value || 'your-event-title';
            }
            if (titleInput && slugInput) {
                titleInput.addEventListener('input', echoSlug);
                slugInput.addEventListener('input', echoSlug);
            }

            /* ---- Repeaters ------------------------------------------------------
               One driver for all three. A row's position in the DOM is its display
               order — the form posts fields in document order, so the ▲ / ▼ buttons
               are all the reordering that is needed and no order field is stored. */
            document.querySelectorAll('[data-repeater]').forEach(function (box) {
                var key      = box.getAttribute('data-repeater'),
                    max      = parseInt(box.getAttribute('data-max'), 10) || 12,
                    tpl      = document.getElementById(key + 'Template'),
                    addBtn   = document.querySelector('[data-repeater-add="' + key + '"]'),
                    empty    = document.querySelector('[data-repeater-empty="' + key + '"]'),
                    counter  = box.querySelectorAll('[data-row]').length;

                function rows() { return Array.prototype.slice.call(box.querySelectorAll('[data-row]')); }

                function refresh() {
                    var list = rows();
                    if (empty) empty.style.display = list.length ? 'none' : '';
                    list.forEach(function (row, i) {
                        var label = row.querySelector('[data-row-index]');
                        if (label) label.textContent = '#' + (i + 1);
                        // Nothing to move past at the ends.
                        var up = row.querySelector('[data-row-up]'), down = row.querySelector('[data-row-down]');
                        if (up) up.disabled = i === 0;
                        if (down) down.disabled = i === list.length - 1;
                    });
                }

                if (addBtn) addBtn.addEventListener('click', function () {
                    if (rows().length >= max) {
                        window.alert('Maximum ' + max + ' rows allowed.');
                        return;
                    }
                    box.insertAdjacentHTML('beforeend', tpl.innerHTML.replace(/__I__/g, 'n' + (counter++)));
                    refresh();
                });

                box.addEventListener('click', function (e) {
                    var row, sibling;

                    if (e.target.closest('[data-row-remove]')) {
                        row = e.target.closest('[data-row]');
                        if (row) row.remove();
                        refresh();
                        return;
                    }

                    if (e.target.closest('[data-row-up]')) {
                        row = e.target.closest('[data-row]');
                        sibling = row && row.previousElementSibling;
                        if (sibling) sibling.before(row);
                        refresh();
                        return;
                    }

                    if (e.target.closest('[data-row-down]')) {
                        row = e.target.closest('[data-row]');
                        sibling = row && row.nextElementSibling;
                        if (sibling) sibling.after(row);
                        refresh();
                    }
                });

                // Photo previews inside speaker rows, bound once by delegation so
                // cloned rows work without re-binding.
                box.addEventListener('change', function (e) {
                    var input = e.target;
                    if (input.type !== 'file') return;
                    var img = input.closest('[data-row]').querySelector('[data-row-preview]');
                    if (img && input.files && input.files[0]) {
                        img.src = URL.createObjectURL(input.files[0]);
                        img.style.display = 'block';
                    }
                });

                refresh();
            });
        })();
    </script>
@endpush
