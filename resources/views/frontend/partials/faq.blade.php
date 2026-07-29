{{--
|--------------------------------------------------------------------------
| FAQ — shared section
|--------------------------------------------------------------------------
|
| Used by the home page and the about page:
|   @include('frontend.partials.faq')
|
| Push its stylesheet from the page:
|   <link rel="stylesheet" href="{{ asset('assets/css/frontend/faq.css') }}?v={{ filemtime(public_path('assets/css/frontend/faq.css')) }}">
|
| NOTE: the accordion uses Bootstrap's collapse (data-bs-toggle), so the page
| MUST also load the Bootstrap JS bundle.
--}}
    @php
        // Fed by AppServiceProvider's view composer (every active question for
        // this page — the module is not capped). Mapped to the exact q/a array
        // shape the accordion markup expects, so the rendered output is unchanged.
        $faqs = collect($faqs ?? [])->map(fn ($f) => [
            'q' => $f->question,
            'a' => $f->answer,
        ])->values()->all();
    @endphp
    @if (count($faqs))
    <section class="hm-faq" id="faq" data-io aria-labelledby="hmFaqTitle">

        {{-- Same slow-rotating hero background + soft warm overlay --}}
        <div class="hm-faq__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.webp') }}" alt="" role="presentation" loading="lazy">
        </div>
        <div class="hm-faq__overlay" aria-hidden="true"></div>

        <div class="container hm-faq__container">
            <div class="row align-items-center g-5">

                {{-- Left: heading + description + illustration --}}
                <div class="col-lg-5 hm-faq__left hm-anim hm-anim--left">
                    <span class="hm-faq__label">
                        <span class="hm-cats__label-icon" aria-hidden="true"></span>
                        <span class="hm-faq__label-text">FAQ</span>
                    </span>
                    <h2 class="hm-faq__title" id="hmFaqTitle">Frequently Asking Questions</h2>
                    <p class="hm-faq__desc">
                        Gain practical skills, learn from industry experts, and receive career guidance
                        that prepares you for real-world opportunities.
                    </p>

                    <div class="hm-faq__visual">
                        <img class="hm-faq__deco hm-faq__deco--star1" src="{{ asset('assets/images/faq/star.png') }}" alt="" aria-hidden="true" loading="lazy">
                        <img class="hm-faq__deco hm-faq__deco--star2" src="{{ asset('assets/images/faq/star.png') }}" alt="" aria-hidden="true" loading="lazy">
                        <img class="hm-faq__deco hm-faq__deco--dots" src="{{ asset('assets/images/faq/component.png') }}" alt="" aria-hidden="true" loading="lazy">
                        <span class="hm-faq__deco hm-faq__deco--stripe" aria-hidden="true"></span>
                        <img class="hm-faq__img" src="{{ asset('assets/images/faq/faq-img.webp') }}"
                             alt="A learner considering the Hire Minds Academy programs" loading="lazy">
                    </div>
                </div>

                {{-- Right: Bootstrap accordion --}}
                <div class="col-lg-7 hm-faq__right">
                    <div class="accordion hm-faq__accordion" id="hmFaqAccordion">
                        @foreach ($faqs as $i => $faq)
                            @php $open = $i === 2; @endphp
                            <div class="accordion-item hm-faq__item hm-anim hm-anim--up hm-anim--d{{ $i + 1 }}">
                                <h3 class="accordion-header">
                                    <button class="accordion-button {{ $open ? '' : 'collapsed' }}" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#hmFaqBody{{ $i }}"
                                            aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="hmFaqBody{{ $i }}">
                                        {{ $faq['q'] }}
                                    </button>
                                </h3>
                                <div id="hmFaqBody{{ $i }}" class="accordion-collapse collapse {{ $open ? 'show' : '' }}"
                                     data-bs-parent="#hmFaqAccordion">
                                    <div class="accordion-body">{{ $faq['a'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif
